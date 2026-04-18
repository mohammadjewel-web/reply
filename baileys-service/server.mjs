import express from 'express';
import makeWASocket, {
  DisconnectReason,
  fetchLatestBaileysVersion,
  useMultiFileAuthState,
} from '@whiskeysockets/baileys';
import pino from 'pino';
import QRCode from 'qrcode';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const PORT = Number(process.env.BAILEYS_PORT || 3710);
const _secretEnv = process.env.BAILEYS_SERVICE_SECRET;
const SECRET =
  typeof _secretEnv === 'string' && _secretEnv.trim() !== ''
    ? _secretEnv.trim()
    : 'change-me';
const AUTH_ROOT = path.join(__dirname, 'auth');

if (!fs.existsSync(AUTH_ROOT)) {
  fs.mkdirSync(AUTH_ROOT, { recursive: true });
}

const app = express();
app.use(express.json({ limit: '32kb' }));

function sanitizeKey(key) {
  return String(key || '')
    .replace(/[^a-zA-Z0-9_-]/g, '_')
    .slice(0, 120);
}

function authDir(key) {
  return path.join(AUTH_ROOT, sanitizeKey(key));
}

function authMiddleware(req, res, next) {
  const sent = String(req.get('X-Baileys-Secret') ?? '').trim();
  if (sent !== SECRET) {
    return res.status(401).json({ ok: false, error: 'Unauthorized' });
  }
  next();
}

const MAX_RECONNECT_ATTEMPTS = 12;
const BASE_RECONNECT_MS = 3000;

/** @type {Map<string, { status: string, qrDataUrl: string | null, error: string | null, sock: ReturnType<typeof makeWASocket> | null, starting: Promise<void> | null, reconnectTimer: ReturnType<typeof setTimeout> | null, reconnectAttempts: number }>} */
const sessions = new Map();

function getOrCreateSlot(key) {
  const k = sanitizeKey(key);
  if (!k) {
    return null;
  }
  if (!sessions.has(k)) {
    sessions.set(k, {
      status: 'idle',
      qrDataUrl: null,
      error: null,
      sock: null,
      starting: null,
      reconnectTimer: null,
      reconnectAttempts: 0,
    });
  }
  return sessions.get(k);
}

function clearReconnectTimer(slot) {
  if (slot.reconnectTimer) {
    clearTimeout(slot.reconnectTimer);
    slot.reconnectTimer = null;
  }
}

function scheduleReconnect(rawKey, slot, lastError) {
  const k = sanitizeKey(rawKey);
  slot.reconnectAttempts += 1;
  if (slot.reconnectAttempts > MAX_RECONNECT_ATTEMPTS) {
    slot.status = 'error';
    slot.error =
      lastError ||
      'Connection failed after multiple retries. Click Generate pairing QR again.';
    return;
  }
  const delay = Math.min(BASE_RECONNECT_MS * slot.reconnectAttempts, 30_000);
  const errBit = lastError ? `${lastError} ` : '';
  slot.status = 'reconnecting';
  slot.error = `${errBit}(reconnecting ${slot.reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS} in ${Math.round(delay / 1000)}s)`;

  clearReconnectTimer(slot);
  slot.reconnectTimer = setTimeout(() => {
    slot.reconnectTimer = null;
    slot.starting = attachConnection(rawKey).catch((e) => {
      slot.status = 'error';
      slot.error = e instanceof Error ? e.message : String(e);
    });
  }, delay);
}

async function destroySession(key) {
  const k = sanitizeKey(key);
  const slot = sessions.get(k);
  if (!slot) {
    return;
  }
  clearReconnectTimer(slot);
  slot.reconnectAttempts = 0;
  if (slot.sock) {
    try {
      slot.sock.end(new Error('session_restart'));
    } catch {
      // ignore
    }
  }
  slot.sock = null;
  slot.starting = null;
  slot.qrDataUrl = null;
  slot.error = null;
  slot.status = 'idle';
}

/**
 * Create socket and wire events. Used for initial start and auto-reconnect (same auth folder).
 */
async function attachConnection(rawKey) {
  const k = sanitizeKey(rawKey);
  if (!k) {
    throw new Error('Invalid sessionKey');
  }

  const slot = getOrCreateSlot(k);
  if (slot.sock) {
    try {
      slot.sock.end(new Error('session_replace'));
    } catch {
      // ignore
    }
    slot.sock = null;
  }

  slot.status = 'starting';

  const { state, saveCreds } = await useMultiFileAuthState(authDir(k));
  let version;
  try {
    const v = await fetchLatestBaileysVersion();
    version = v.version;
  } catch {
    version = undefined;
  }

  const sock = makeWASocket({
    version,
    auth: state,
    printQRInTerminal: false,
    logger: pino({ level: 'silent' }),
    browser: ['Reply', 'Chrome', '1.0.0'],
  });

  slot.sock = sock;

  sock.ev.on('creds.update', saveCreds);

  sock.ev.on('connection.update', async (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      try {
        slot.qrDataUrl = await QRCode.toDataURL(qr, { width: 280, margin: 2 });
        slot.status = 'awaiting_scan';
      } catch (e) {
        slot.error = e instanceof Error ? e.message : String(e);
        slot.status = 'error';
      }
    }

    if (connection === 'open') {
      slot.reconnectAttempts = 0;
      clearReconnectTimer(slot);
      slot.status = 'connected';
      slot.qrDataUrl = null;
      slot.error = null;
    }

    if (connection === 'close') {
      const statusCode = lastDisconnect?.error?.output?.statusCode;
      const errMsg = lastDisconnect?.error?.message
        ? String(lastDisconnect.error.message)
        : 'Connection closed';

      slot.qrDataUrl = null;
      slot.sock = null;

      if (statusCode === DisconnectReason.loggedOut) {
        clearReconnectTimer(slot);
        slot.reconnectAttempts = 0;
        slot.status = 'logged_out';
        slot.error = 'Logged out';
        return;
      }

      scheduleReconnect(rawKey, slot, errMsg);
    }
  });
}

async function startSession(rawKey) {
  const k = sanitizeKey(rawKey);
  if (!k) {
    throw new Error('Invalid sessionKey');
  }

  await destroySession(k);
  const slot = getOrCreateSlot(k);
  slot.error = null;
  slot.qrDataUrl = null;

  await attachConnection(rawKey);
}

app.get('/', (req, res) => {
  res.type('html').send(`<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Baileys service</title></head>
<body style="font-family:system-ui,sans-serif;max-width:42rem;margin:2rem auto;padding:0 1rem">
  <h1>Baileys WhatsApp Web service</h1>
  <p>This is the Node helper for Laravel. There is no web UI here.</p>
  <ul>
    <li><code>GET /health</code> — health check (JSON)</li>
    <li><code>POST /session/start</code> — start pairing (requires <code>X-Baileys-Secret</code> header)</li>
    <li><code>GET /session/:key/status</code> — poll QR / status (requires <code>X-Baileys-Secret</code>)</li>
  </ul>
  <p>Use <strong>/whatsapp/connect</strong> in your Laravel app to generate the QR.</p>
</body>
</html>`);
});

app.get('/health', (req, res) => {
  res.json({ ok: true, service: 'baileys' });
});

app.post('/session/start', authMiddleware, async (req, res) => {
  const sessionKey = String(req.body.sessionKey || '');
  if (!sanitizeKey(sessionKey)) {
    return res.status(400).json({ ok: false, error: 'sessionKey required' });
  }

  const slot = getOrCreateSlot(sessionKey);
  slot.starting = startSession(sessionKey).catch((e) => {
    slot.status = 'error';
    slot.error = e instanceof Error ? e.message : String(e);
  });

  res.json({ ok: true, sessionKey: sanitizeKey(sessionKey) });
});

app.get('/session/:key/status', authMiddleware, async (req, res) => {
  const k = sanitizeKey(req.params.key);
  if (!k) {
    return res.json({
      ok: true,
      status: 'idle',
      qrDataUrl: null,
      error: null,
    });
  }

  const slot = sessions.get(k);
  if (!slot) {
    return res.json({
      ok: true,
      status: 'idle',
      qrDataUrl: null,
      error: null,
    });
  }

  if (slot.starting) {
    try {
      await slot.starting;
    } catch {
      // error already on slot
    }
  }

  return res.json({
    ok: true,
    status: slot.status,
    qrDataUrl: slot.qrDataUrl,
    error: slot.error,
  });
});

app.listen(PORT, '127.0.0.1', () => {
  // eslint-disable-next-line no-console
  console.log(`Baileys service listening on http://127.0.0.1:${PORT}`);
});

import express from 'express';
import makeWASocket, {
  DisconnectReason,
  extractMessageContent,
  fetchLatestBaileysVersion,
  getContentType,
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
/** Bump when deploy instructions change — curl /health to confirm the running process picked up new code. */
const SERVICE_REV = 5;

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

function wipeAuthDir(key) {
  const dir = authDir(key);
  try {
    fs.rmSync(dir, { recursive: true, force: true });
    fs.mkdirSync(dir, { recursive: true });
  } catch (e) {
    throw e instanceof Error ? e : new Error(String(e));
  }
}

/** @param {unknown} error Boom or similar from Baileys */
function getDisconnectStatusCode(error) {
  if (error == null || typeof error !== 'object') {
    return undefined;
  }
  const o = /** @type {{ output?: { statusCode?: number }; statusCode?: number }} */ (error);
  const fromOutput = o.output?.statusCode;
  if (typeof fromOutput === 'number') {
    return fromOutput;
  }
  if (typeof o.statusCode === 'number') {
    return o.statusCode;
  }
  return undefined;
}

/**
 * These WhatsApp close codes usually mean on-disk creds are useless; wipe once and reconnect for a new QR.
 */
function shouldWipeAuthOnce(code) {
  if (code === undefined) {
    return false;
  }
  return (
    code === DisconnectReason.loggedOut ||
    code === DisconnectReason.badSession ||
    code === DisconnectReason.multideviceMismatch
  );
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

/** @type {Map<string, { status: string, qrDataUrl: string | null, error: string | null, sock: ReturnType<typeof makeWASocket> | null, starting: Promise<void> | null, reconnectTimer: ReturnType<typeof setTimeout> | null, reconnectAttempts: number, logoutClearedOnce: boolean }>} */
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
      logoutClearedOnce: false,
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
  slot.logoutClearedOnce = false;
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

function summarizeInboundText(msg) {
  const inner = extractMessageContent(msg.message);
  if (!inner) {
    return null;
  }
  if (inner.conversation) {
    return inner.conversation;
  }
  if (inner.extendedTextMessage?.text) {
    return inner.extendedTextMessage.text;
  }
  if (inner.imageMessage) {
    return inner.imageMessage.caption?.trim() || '[image]';
  }
  if (inner.videoMessage) {
    return inner.videoMessage.caption?.trim() || '[video]';
  }
  if (inner.audioMessage) {
    return '[audio]';
  }
  if (inner.documentMessage) {
    return inner.documentMessage.caption?.trim() || '[document]';
  }
  if (inner.stickerMessage) {
    return '[sticker]';
  }
  if (inner.contactMessage) {
    return '[contact]';
  }
  if (inner.locationMessage) {
    return '[location]';
  }
  const t = getContentType(inner);
  return t ? `[${t}]` : '[message]';
}

async function forwardInboundToLaravel(sessionKeyRaw, baileysMsg, notifyType) {
  const url = String(process.env.BAILEYS_LARAVEL_WEBHOOK_URL ?? '').trim();
  if (!url) {
    return;
  }
  const body = summarizeInboundText(baileysMsg);
  if (body === null) {
    return;
  }
  const remote = baileysMsg.key.remoteJid;
  const ts = baileysMsg.messageTimestamp
    ? Number(baileysMsg.messageTimestamp)
    : undefined;
  const payload = {
    key: baileysMsg.key,
    baileys_type: notifyType,
  };
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Baileys-Secret': SECRET,
      },
      body: JSON.stringify({
        session_key: sessionKeyRaw,
        from: remote,
        body,
        external_message_id: baileysMsg.key.id ?? undefined,
        message_timestamp: Number.isFinite(ts) ? ts : undefined,
        payload,
      }),
    });
    if (!res.ok) {
      // eslint-disable-next-line no-console
      console.warn('Baileys ingest webhook HTTP', res.status, await res.text().catch(() => ''));
    }
  } catch (e) {
    // eslint-disable-next-line no-console
    console.error('Baileys ingest forward failed', e);
  }
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

  sock.ev.on('messages.upsert', async ({ messages, type }) => {
    if (type !== 'notify') {
      return;
    }
    for (const msg of messages) {
      if (!msg.message || msg.key.fromMe) {
        continue;
      }
      const remote = msg.key.remoteJid;
      if (!remote || remote === 'status@broadcast' || remote.endsWith('@g.us')) {
        continue;
      }
      await forwardInboundToLaravel(rawKey, msg, type);
    }
  });

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
      const statusCode = getDisconnectStatusCode(lastDisconnect?.error);
      const errMsg = lastDisconnect?.error?.message
        ? String(lastDisconnect.error.message)
        : 'Connection closed';

      slot.qrDataUrl = null;
      slot.sock = null;

      if (shouldWipeAuthOnce(statusCode)) {
        clearReconnectTimer(slot);
        slot.reconnectAttempts = 0;

        // Stale or revoked on-disk creds often produce immediate close. Wipe once per
        // user-initiated start and reconnect so a fresh QR can appear.
        if (!slot.logoutClearedOnce) {
          slot.logoutClearedOnce = true;
          try {
            wipeAuthDir(k);
          } catch (e) {
            slot.status = 'error';
            slot.error = e instanceof Error ? e.message : String(e);
            return;
          }
          slot.status = 'starting';
          slot.error = null;
          slot.starting = attachConnection(rawKey).catch((e) => {
            slot.status = 'error';
            slot.error = e instanceof Error ? e.message : String(e);
          });
          return;
        }

        if (statusCode === DisconnectReason.loggedOut) {
          slot.status = 'logged_out';
          slot.error =
            'WhatsApp closed this session (logged out). On the phone: WhatsApp → Settings → Linked devices — remove this session if it appears, then click Generate pairing QR again.';
        } else {
          slot.status = 'error';
          slot.error =
            'WhatsApp rejected the saved session (invalid or mismatched). Click Generate pairing QR again. If it repeats, stop the Baileys process, delete this session folder under baileys-service/auth on the server, then retry.';
        }
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
    <li><code>GET /health</code> — health check (JSON, includes <code>rev</code>)</li>
    <li><code>POST /session/start</code> — start pairing (requires <code>X-Baileys-Secret</code> header)</li>
    <li><code>POST /session/reset</code> — body <code>{"sessionKey":"…"}</code>, clears saved auth for that key</li>
    <li><code>GET /session/:key/status</code> — poll QR / status (requires <code>X-Baileys-Secret</code>)</li>
  </ul>
  <p>Use <strong>/whatsapp/connect</strong> in your Laravel app to generate the QR.</p>
</body>
</html>`);
});

app.get('/health', (req, res) => {
  res.json({ ok: true, service: 'baileys', rev: SERVICE_REV });
});

/**
 * Drop socket + delete saved creds for this session key (same auth as other routes).
 * Use after deploy or corrupted auth if the UI still shows logged_out / error.
 */
app.post('/session/reset', authMiddleware, async (req, res) => {
  const sessionKey = String(req.body.sessionKey || '');
  if (!sanitizeKey(sessionKey)) {
    return res.status(400).json({ ok: false, error: 'sessionKey required' });
  }
  await destroySession(sessionKey);
  try {
    wipeAuthDir(sanitizeKey(sessionKey));
  } catch (e) {
    return res.status(500).json({
      ok: false,
      error: e instanceof Error ? e.message : String(e),
    });
  }
  res.json({ ok: true, sessionKey: sanitizeKey(sessionKey) });
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

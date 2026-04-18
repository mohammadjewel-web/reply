# Baileys WhatsApp Web QR service

This small Node service pairs a number using **WhatsApp → Linked devices → Link a device** (Baileys / WhatsApp Web protocol). It is **separate from Meta Cloud API** embedded signup.

## Requirements

- Node.js **20+** (Baileys / tooling expect a current runtime).

## Setup

```bash
cd baileys-service
npm install
cp .env.example .env
# Set BAILEYS_SERVICE_SECRET to match Laravel .env
```

## Run

```bash
npm start
```

Default port: `3710`. Override with `BAILEYS_PORT`.

## Laravel

Set in the main app `.env`:

- `BAILEYS_SERVICE_ENABLED=true`
- `BAILEYS_SERVICE_URL=http://127.0.0.1:3710`
- `BAILEYS_SERVICE_SECRET=...` (same value as this service)

Session auth files are stored under `baileys-service/auth/<sessionKey>/`.

## Inbox (inbound messages)

Pairing only starts a WhatsApp Web session; **Laravel does not see messages until you forward them**. Set on **this** service (Node env or `baileys-service/.env`):

- `BAILEYS_LARAVEL_WEBHOOK_URL` — full URL to the Laravel route **`POST /webhooks/whatsapp-baileys`** (e.g. `https://your-domain.com/webhooks/whatsapp-baileys`).

Use the same `BAILEYS_SERVICE_SECRET` as Laravel for the `X-Baileys-Secret` header. Only **direct chats** (`@s.whatsapp.net`) are forwarded; groups are ignored for now.

**Outbound:** Laravel calls `POST /session/send` with `sessionKey`, `to` (digits only), and `text` while that session status is `connected`.

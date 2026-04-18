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

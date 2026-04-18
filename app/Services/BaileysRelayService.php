<?php

namespace App\Services;

use App\Models\ChannelAccount;
use Illuminate\Support\Facades\Http;
use Throwable;

class BaileysRelayService
{
    /**
     * @return array{ok: bool, message_id: ?string, error: ?string}
     */
    /**
     * @param  string|null  $remoteJid  Full WhatsApp JID from inbound (e.g. …@s.whatsapp.net or …@lid). Required for some chats.
     */
    public function sendTextMessage(
        ChannelAccount $account,
        string $sessionKey,
        string $toWaDigits,
        string $text,
        ?string $remoteJid = null,
    ): array {
        if (! config('services.baileys.enabled')) {
            return ['ok' => false, 'message_id' => null, 'error' => 'Baileys is disabled'];
        }

        $base = rtrim((string) config('services.baileys.url'), '/');
        if ($base === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'BAILEYS_SERVICE_URL is not set'];
        }

        $secret = config('services.baileys.secret');
        $secret = is_string($secret) ? trim($secret) : '';
        if ($secret === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'BAILEYS_SERVICE_SECRET is not set'];
        }

        $to = preg_replace('/\D+/', '', $toWaDigits) ?? '';
        if (strlen($to) < 8) {
            return ['ok' => false, 'message_id' => null, 'error' => 'Invalid recipient number'];
        }

        $remoteJid = $remoteJid !== null ? trim($remoteJid) : '';
        $body = [
            'sessionKey' => $sessionKey,
            'to' => $to,
            'text' => $text,
        ];
        if ($remoteJid !== '' && str_contains($remoteJid, '@')) {
            $body['jid'] = $remoteJid;
        }

        try {
            $response = Http::timeout(45)
                ->withHeaders(['X-Baileys-Secret' => $secret])
                ->acceptJson()
                ->post($base.'/session/send', $body);
        } catch (Throwable $e) {
            return ['ok' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }

        if ($response->status() === 409) {
            return [
                'ok' => false,
                'message_id' => null,
                'error' => __('WhatsApp Web session is not connected. Open WhatsApp → Connect, generate the QR again for this connection, then retry.'),
            ];
        }

        if ($response->status() === 401) {
            return [
                'ok' => false,
                'message_id' => null,
                'error' => __(
                    'Baileys rejected the send (401): BAILEYS_SERVICE_SECRET must match exactly in Laravel .env and baileys-service/.env (or the shell that starts Node). Run php artisan config:clear, restart PHP-FPM/Octane if applicable, restart Baileys. Verify with: curl -s -X POST -H "X-Baileys-Secret: …" http://127.0.0.1:3710/session/ping'
                ),
            ];
        }

        if (! $response->successful()) {
            $err = $response->json('error') ?? $response->body();

            return ['ok' => false, 'message_id' => null, 'error' => is_string($err) ? $err : json_encode($err)];
        }

        $id = $response->json('messageId');

        return ['ok' => true, 'message_id' => $id ? (string) $id : null, 'error' => null];
    }

    /**
     * @param  string  $mediaType  image|video|audio|ptt
     * @return array{ok: bool, message_id: ?string, error: ?string}
     */
    public function sendMediaMessage(
        ChannelAccount $account,
        string $sessionKey,
        string $toWaDigits,
        string $absolutePath,
        string $filename,
        string $mime,
        string $mediaType,
        ?string $remoteJid = null,
        ?string $caption = null,
    ): array {
        if (! config('services.baileys.enabled')) {
            return ['ok' => false, 'message_id' => null, 'error' => 'Baileys is disabled'];
        }

        $base = rtrim((string) config('services.baileys.url'), '/');
        if ($base === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'BAILEYS_SERVICE_URL is not set'];
        }

        $secret = config('services.baileys.secret');
        $secret = is_string($secret) ? trim($secret) : '';
        if ($secret === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'BAILEYS_SERVICE_SECRET is not set'];
        }

        $to = preg_replace('/\D+/', '', $toWaDigits) ?? '';
        if (strlen($to) < 8 && ($remoteJid === null || ! str_contains($remoteJid, '@'))) {
            return ['ok' => false, 'message_id' => null, 'error' => 'Invalid recipient'];
        }

        $remoteJid = $remoteJid !== null ? trim($remoteJid) : '';
        $mt = strtolower($mediaType);
        if (! in_array($mt, ['image', 'video', 'audio', 'ptt'], true)) {
            return ['ok' => false, 'message_id' => null, 'error' => 'Invalid media type'];
        }

        try {
            $request = Http::timeout(120)
                ->withHeaders(['X-Baileys-Secret' => $secret])
                ->attach('file', file_get_contents($absolutePath), $filename, ['Content-Type' => $mime]);

            $fields = [
                'sessionKey' => $sessionKey,
                'to' => $to,
                'mediaType' => $mt,
            ];
            if ($remoteJid !== '' && str_contains($remoteJid, '@')) {
                $fields['jid'] = $remoteJid;
            }
            if ($caption !== null && $caption !== '' && in_array($mt, ['image', 'video'], true)) {
                $fields['caption'] = $caption;
            }

            $response = $request->post($base.'/session/send-media', $fields);
        } catch (Throwable $e) {
            return ['ok' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }

        if ($response->status() === 409) {
            return [
                'ok' => false,
                'message_id' => null,
                'error' => __('WhatsApp Web session is not connected. Open WhatsApp → Connect, generate the QR again for this connection, then retry.'),
            ];
        }

        if ($response->status() === 401) {
            return [
                'ok' => false,
                'message_id' => null,
                'error' => __('Baileys rejected the send (401): check BAILEYS_SERVICE_SECRET matches in Laravel and baileys-service.'),
            ];
        }

        if (! $response->successful()) {
            $err = $response->json('error') ?? $response->body();

            return ['ok' => false, 'message_id' => null, 'error' => is_string($err) ? $err : json_encode($err)];
        }

        $id = $response->json('messageId');

        return ['ok' => true, 'message_id' => $id ? (string) $id : null, 'error' => null];
    }
}

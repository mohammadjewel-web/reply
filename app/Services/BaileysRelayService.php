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
    public function sendTextMessage(
        ChannelAccount $account,
        string $sessionKey,
        string $toWaDigits,
        string $text,
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

        try {
            $response = Http::timeout(45)
                ->withHeaders(['X-Baileys-Secret' => $secret])
                ->acceptJson()
                ->post($base.'/session/send', [
                    'sessionKey' => $sessionKey,
                    'to' => $to,
                    'text' => $text,
                ]);
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
                'error' => __('Baileys rejected the send (401): use the same BAILEYS_SERVICE_SECRET in Laravel .env and the Node process, then php artisan config:clear and restart Baileys.'),
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

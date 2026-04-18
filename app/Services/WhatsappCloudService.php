<?php

namespace App\Services;

use App\Models\ChannelAccount;
use App\Models\WhatsappLinkSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappCloudService
{
    public function verifySignature(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = config('services.whatsapp.app_secret');
        if (! $secret || ! $signatureHeader || ! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }
        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }

    public function accessToken(): ?string
    {
        $linked = WhatsappLinkSession::query()
            ->where('status', 'linked')
            ->whereNotNull('access_token')
            ->orderByDesc('updated_at')
            ->first();

        return $linked?->access_token ?? config('services.whatsapp.access_token');
    }

    public function phoneNumberId(): ?string
    {
        $linked = WhatsappLinkSession::query()
            ->where('status', 'linked')
            ->whereNotNull('phone_number_id')
            ->orderByDesc('updated_at')
            ->first();

        return $linked?->phone_number_id ?? config('services.whatsapp.phone_number_id');
    }

    public function accessTokenForChannel(ChannelAccount $account): ?string
    {
        if ($account->access_token) {
            return $account->access_token;
        }

        $linked = WhatsappLinkSession::query()
            ->where('status', 'linked')
            ->whereNotNull('access_token')
            ->when($account->external_id, fn ($q) => $q->where('phone_number_id', $account->external_id))
            ->orderByDesc('updated_at')
            ->first();

        return $linked?->access_token ?? config('services.whatsapp.access_token');
    }

    public function phoneNumberIdForChannel(ChannelAccount $account): ?string
    {
        if ($account->external_id) {
            return $account->external_id;
        }

        return $this->phoneNumberId();
    }

    /**
     * @return array{ok: bool, message_id: ?string, error: ?string}
     */
    public function sendTextMessageForChannel(ChannelAccount $account, string $toWaId, string $text): array
    {
        $token = $this->accessTokenForChannel($account);
        $phoneId = $this->phoneNumberIdForChannel($account);
        if (! $token || ! $phoneId) {
            Log::warning('WhatsApp send skipped: missing token or phone_number_id for channel account', ['account_id' => $account->id]);

            return ['ok' => false, 'message_id' => null, 'error' => 'Missing WhatsApp token or phone_number_id for this connection'];
        }

        return $this->postWhatsAppMessage($token, $phoneId, $toWaId, $text);
    }

    /**
     * @return array{ok: bool, message_id: ?string, error: ?string}
     */
    public function sendTextMessage(string $toWaId, string $text): array
    {
        $token = $this->accessToken();
        $phoneId = $this->phoneNumberId();
        if (! $token || ! $phoneId) {
            Log::warning('WhatsApp send skipped: missing token or phone_number_id');

            return ['ok' => false, 'message_id' => null, 'error' => 'Missing WhatsApp token or phone_number_id'];
        }

        return $this->postWhatsAppMessage($token, $phoneId, $toWaId, $text);
    }

    /**
     * @return array{ok: bool, message_id: ?string, error: ?string}
     */
    private function postWhatsAppMessage(string $token, string $phoneId, string $toWaId, string $text): array
    {
        $version = config('services.whatsapp.graph_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        $response = Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => $toWaId,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $text],
        ]);

        if (! $response->successful()) {
            Log::error('WhatsApp send failed', ['body' => $response->body()]);

            return ['ok' => false, 'message_id' => null, 'error' => $response->body()];
        }

        $id = $response->json('messages.0.id');

        return ['ok' => true, 'message_id' => $id ? (string) $id : null, 'error' => null];
    }

    /**
     * Upload binary media to WhatsApp Cloud; returns Graph media id.
     *
     * @return array{ok: bool, media_id: ?string, error: ?string}
     */
    public function uploadMediaForChannel(ChannelAccount $account, string $absolutePath, string $mime): array
    {
        $token = $this->accessTokenForChannel($account);
        $phoneId = $this->phoneNumberIdForChannel($account);
        if (! $token || ! $phoneId) {
            return ['ok' => false, 'media_id' => null, 'error' => 'Missing WhatsApp token or phone_number_id for this connection'];
        }

        $version = config('services.whatsapp.graph_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$phoneId}/media";

        $response = Http::withToken($token)
            ->timeout(120)
            ->attach('file', file_get_contents($absolutePath), basename($absolutePath), ['Content-Type' => $mime])
            ->post($url, ['messaging_product' => 'whatsapp']);

        if (! $response->successful()) {
            Log::error('WhatsApp media upload failed', ['body' => $response->body()]);

            return ['ok' => false, 'media_id' => null, 'error' => $response->body()];
        }

        $mid = $response->json('id');

        return ['ok' => true, 'media_id' => $mid ? (string) $mid : null, 'error' => null];
    }

    /**
     * @param  string  $waType  image|video|audio
     * @return array{ok: bool, message_id: ?string, error: ?string}
     */
    public function sendMediaMessageForChannel(
        ChannelAccount $account,
        string $toWaId,
        string $waType,
        string $mediaId,
        ?string $caption,
    ): array {
        $token = $this->accessTokenForChannel($account);
        $phoneId = $this->phoneNumberIdForChannel($account);
        if (! $token || ! $phoneId) {
            return ['ok' => false, 'message_id' => null, 'error' => 'Missing WhatsApp token or phone_number_id for this connection'];
        }

        $version = config('services.whatsapp.graph_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toWaId,
        ];

        if ($waType === 'image') {
            $payload['type'] = 'image';
            $payload['image'] = array_filter([
                'id' => $mediaId,
                'caption' => $caption !== null && $caption !== '' ? $caption : null,
            ]);
        } elseif ($waType === 'video') {
            $payload['type'] = 'video';
            $payload['video'] = array_filter([
                'id' => $mediaId,
                'caption' => $caption !== null && $caption !== '' ? $caption : null,
            ]);
        } elseif ($waType === 'audio') {
            $payload['type'] = 'audio';
            $payload['audio'] = ['id' => $mediaId];
        } else {
            return ['ok' => false, 'message_id' => null, 'error' => 'Unsupported media type'];
        }

        $response = Http::withToken($token)->timeout(60)->post($url, $payload);

        if (! $response->successful()) {
            Log::error('WhatsApp media send failed', ['body' => $response->body()]);

            return ['ok' => false, 'message_id' => null, 'error' => $response->body()];
        }

        $id = $response->json('messages.0.id');

        return ['ok' => true, 'message_id' => $id ? (string) $id : null, 'error' => null];
    }

    public function exchangeOAuthCode(string $code, string $redirectUri): array
    {
        $appId = config('services.facebook.app_id');
        $appSecret = config('services.facebook.app_secret');
        if (! $appId || ! $appSecret) {
            return ['ok' => false, 'error' => 'FACEBOOK_APP_ID and FACEBOOK_APP_SECRET must be set'];
        }

        $version = config('services.whatsapp.graph_version', 'v21.0');
        $response = Http::get("https://graph.facebook.com/{$version}/oauth/access_token", [
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if (! $response->successful()) {
            return ['ok' => false, 'error' => $response->body()];
        }

        return ['ok' => true, 'data' => $response->json()];
    }
}

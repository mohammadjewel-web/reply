<?php

namespace App\Services;

use App\Models\ChannelAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessengerGraphService
{
    /**
     * @return array{ok: bool, error: ?string}
     */
    public function subscribePageApp(string $pageAccessToken, ?string $expectedPageId = null): array
    {
        $version = config('services.messenger.graph_version', 'v21.0');

        $me = Http::get("https://graph.facebook.com/{$version}/me", [
            'access_token' => $pageAccessToken,
            'fields' => 'id,name',
        ]);

        if (! $me->successful()) {
            Log::error('Messenger subscribe failed: could not read page profile', ['body' => $me->body()]);

            return ['ok' => false, 'error' => $me->body()];
        }

        $actualPageId = (string) ($me->json('id') ?? '');
        if ($expectedPageId && $actualPageId !== '' && $actualPageId !== (string) $expectedPageId) {
            $error = "Token page id mismatch. Expected {$expectedPageId}, got {$actualPageId}.";
            Log::warning('Messenger subscribe mismatch', ['expected' => $expectedPageId, 'actual' => $actualPageId]);

            return ['ok' => false, 'error' => $error];
        }

        $subscribe = Http::asForm()->post("https://graph.facebook.com/{$version}/me/subscribed_apps", [
            'subscribed_fields' => 'messages,messaging_postbacks,message_reads,message_deliveries',
            'access_token' => $pageAccessToken,
        ]);

        if (! $subscribe->successful()) {
            Log::error('Messenger subscribe failed: subscribed_apps request failed', ['body' => $subscribe->body()]);

            return ['ok' => false, 'error' => $subscribe->body()];
        }

        return ['ok' => true, 'error' => null];
    }

    public function verifySignature(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = config('services.messenger.app_secret');
        if (! $secret || ! $signatureHeader || ! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }
        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }

    public function pageAccessToken(): ?string
    {
        return config('services.messenger.page_access_token');
    }

    public function pageAccessTokenForChannel(ChannelAccount $account): ?string
    {
        if ($account->access_token) {
            return $account->access_token;
        }

        return config('services.messenger.page_access_token');
    }

    /**
     * @return array{ok: bool, message_id: ?string, error: ?string}
     */
    public function sendTextMessageForChannel(ChannelAccount $account, string $recipientPsid, string $text): array
    {
        $token = $this->pageAccessTokenForChannel($account);
        if (! $token) {
            Log::warning('Messenger send skipped: missing page access token for channel account', ['account_id' => $account->id]);

            return ['ok' => false, 'message_id' => null, 'error' => 'Missing page access token for this connection'];
        }

        return $this->postMessengerMessage($token, $recipientPsid, $text);
    }

    /**
     * @return array{ok: bool, message_id: ?string, error: ?string}
     */
    public function sendTextMessage(string $recipientPsid, string $text): array
    {
        $token = $this->pageAccessToken();
        if (! $token) {
            Log::warning('Messenger send skipped: MESSENGER_PAGE_ACCESS_TOKEN not set');

            return ['ok' => false, 'message_id' => null, 'error' => 'Missing page access token'];
        }

        return $this->postMessengerMessage($token, $recipientPsid, $text);
    }

    /**
     * @return array{ok: bool, message_id: ?string, error: ?string}
     */
    /**
     * Resolve the Facebook / Messenger display name for a PSID (best-effort; requires page token).
     */
    public function fetchMessengerSenderName(string $psid, string $pageAccessToken): ?string
    {
        if ($psid === '' || $pageAccessToken === '') {
            return null;
        }

        $version = config('services.messenger.graph_version', 'v21.0');
        $response = Http::timeout(4)->get("https://graph.facebook.com/{$version}/{$psid}", [
            'fields' => 'name,first_name,last_name',
            'access_token' => $pageAccessToken,
        ]);

        if (! $response->successful()) {
            Log::debug('Messenger profile lookup failed', ['psid' => $psid, 'body' => $response->body()]);

            return null;
        }

        $name = $response->json('name');
        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        $first = $response->json('first_name');
        $last = $response->json('last_name');
        $combined = trim(implode(' ', array_filter([(is_string($first) ? $first : ''), (is_string($last) ? $last : '')])));

        return $combined !== '' ? $combined : null;
    }

    private function postMessengerMessage(string $token, string $recipientPsid, string $text): array
    {
        $version = config('services.messenger.graph_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/me/messages";

        $response = Http::withToken($token)->post($url, [
            'recipient' => ['id' => $recipientPsid],
            'messaging_type' => 'RESPONSE',
            'message' => ['text' => $text],
        ]);

        if (! $response->successful()) {
            Log::error('Messenger send failed', ['body' => $response->body()]);

            return ['ok' => false, 'message_id' => null, 'error' => $response->body()];
        }

        $id = $response->json('message_id');

        return ['ok' => true, 'message_id' => $id ? (string) $id : null, 'error' => null];
    }
}

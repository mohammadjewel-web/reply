<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Services\MessageIngestService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class WhatsappBaileysWebhookController extends Controller
{
    public function __construct(
        private MessageIngestService $ingest,
    ) {}

    public function handle(Request $request): Response
    {
        if (! config('services.baileys.enabled')) {
            return response('Disabled', 503);
        }

        $expected = config('services.baileys.secret');
        if (! is_string($expected) || $expected === '') {
            return response('Not configured', 503);
        }

        $sent = trim((string) $request->header('X-Baileys-Secret'));
        if ($sent === '' || ! hash_equals($expected, $sent)) {
            return response('Unauthorized', 401);
        }

        $data = $request->validate([
            'session_key' => ['required', 'string', 'max:200'],
            'from' => ['required', 'string', 'max:128'],
            'body' => ['nullable', 'string', 'max:65535'],
            'external_message_id' => ['nullable', 'string', 'max:128'],
            'message_timestamp' => ['nullable', 'integer'],
            'payload' => ['nullable', 'array'],
        ]);

        if (! preg_match('/^wa-(\d+)-u-(\d+)$/', $data['session_key'], $m)) {
            return response('Bad session key', 400);
        }

        $accountId = (int) $m[1];
        $sessionUserId = (int) $m[2];
        $account = ChannelAccount::query()
            ->where('type', ChannelAccount::TYPE_WHATSAPP)
            ->whereKey($accountId)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            return response('OK', 200);
        }

        if ($account->baileys_session_user_id !== $sessionUserId) {
            $account->forceFill(['baileys_session_user_id' => $sessionUserId])->save();
        }

        $threadKey = $this->normalizeJidToThreadKey($data['from']);
        $body = (string) ($data['body'] ?? '');
        if ($body === '') {
            $body = '[message]';
        }

        $sentAt = null;
        if (isset($data['message_timestamp'])) {
            $sentAt = Carbon::createFromTimestamp((int) $data['message_timestamp']);
        }

        $this->ingest->ingestInbound(
            $account,
            $threadKey,
            null,
            $body,
            $data['external_message_id'] ?? null,
            $data['payload'] ?? null,
            $sentAt,
        );

        return response('OK', 200);
    }

    private function normalizeJidToThreadKey(string $jid): string
    {
        $jid = trim($jid);
        $local = str_contains($jid, '@') ? explode('@', $jid, 2)[0] : $jid;
        $digits = preg_replace('/\D+/', '', $local) ?? '';

        if ($digits !== '' && strlen($digits) >= 8) {
            return $digits;
        }

        return $local;
    }
}

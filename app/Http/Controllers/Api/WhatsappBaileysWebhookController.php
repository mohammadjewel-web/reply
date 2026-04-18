<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Services\MessageIngestService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class WhatsappBaileysWebhookController extends Controller
{
    public function __construct(
        private MessageIngestService $ingest,
    ) {}

    public function handle(Request $request): Response
    {
        $auth = $this->baileysAuthFailureResponse($request);
        if ($auth !== null) {
            return $auth;
        }

        if ($request->hasFile('media')) {
            return $this->handleMultipartMedia($request);
        }

        $data = $request->validate([
            'session_key' => ['required', 'string', 'max:200'],
            'from' => ['required', 'string', 'max:128'],
            'routing_jid' => ['nullable', 'string', 'max:128'],
            'from_me' => ['sometimes', 'boolean'],
            'push_name' => ['nullable', 'string', 'max:512'],
            'body' => ['nullable', 'string', 'max:65535'],
            'external_message_id' => ['nullable', 'string', 'max:128'],
            'message_timestamp' => ['nullable', 'integer'],
            'payload' => ['nullable', 'array'],
        ]);

        return $this->ingestBaileysWebhook($data, $data['payload'] ?? null);
    }

    private function handleMultipartMedia(Request $request): Response
    {
        $data = $request->validate([
            'session_key' => ['required', 'string', 'max:200'],
            'from' => ['required', 'string', 'max:128'],
            'routing_jid' => ['nullable', 'string', 'max:128'],
            'from_me' => ['sometimes'],
            'push_name' => ['nullable', 'string', 'max:512'],
            'body' => ['nullable', 'string', 'max:65535'],
            'external_message_id' => ['nullable', 'string', 'max:128'],
            'message_timestamp' => ['nullable', 'integer'],
            'payload_json' => ['nullable', 'string', 'max:131072'],
            'media_kind' => ['required', 'string', 'in:image,video,audio,ptt,file'],
            'media_mime' => ['nullable', 'string', 'max:255'],
            'media' => ['required', 'file', 'max:25600'],
        ]);

        $payload = null;
        if (! empty($data['payload_json'])) {
            $decoded = json_decode($data['payload_json'], true);
            $payload = is_array($decoded) ? $decoded : null;
        }

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

        $file = $request->file('media');
        $mime = trim((string) ($data['media_mime'] ?? ''));
        if ($mime === '') {
            $mime = $file->getMimeType() ?: 'application/octet-stream';
        }

        $ext = $file->guessExtension();
        if (! $ext) {
            $ext = match ($data['media_kind']) {
                'image' => 'jpg',
                'video' => 'mp4',
                'audio', 'ptt' => 'ogg',
                default => 'bin',
            };
        }

        $storedPath = $file->storeAs(
            'chat-inbound/baileys/'.$account->id,
            uniqid('', true).'.'.$ext,
            'public',
        );

        $inbound = [
            'kind' => $data['media_kind'],
            'path' => $storedPath,
            'mime' => $mime,
            'original_name' => $file->getClientOriginalName() ?: null,
            'via' => 'baileys_inbound',
        ];

        if (is_array($payload)) {
            $payload['inbound_media'] = $inbound;
        } else {
            $payload = ['inbound_media' => $inbound];
        }

        $data['from_me'] = $request->boolean('from_me');

        Log::info('webhooks.baileys.multipart_received', [
            'account_id' => $account->id,
            'media_kind' => $data['media_kind'],
            'stored_path' => $storedPath,
            'external_message_id' => $data['external_message_id'] ?? null,
            'bytes' => $file->getSize(),
        ]);

        return $this->ingestBaileysWebhook($data, $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $payload
     */
    private function ingestBaileysWebhook(array $data, ?array $payload): Response
    {
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
        $routingJid = trim((string) ($data['routing_jid'] ?? $data['from']));
        $body = (string) ($data['body'] ?? '');
        if ($body === '') {
            $body = '[message]';
        }

        $sentAt = null;
        if (isset($data['message_timestamp'])) {
            $sentAt = Carbon::createFromTimestamp((int) $data['message_timestamp']);
        }

        $fromMe = (bool) ($data['from_me'] ?? false);
        $direction = $fromMe ? ChannelMessage::DIRECTION_OUTBOUND : ChannelMessage::DIRECTION_INBOUND;

        $pushName = trim((string) ($data['push_name'] ?? ''));
        $displayName = (! $fromMe && $pushName !== '') ? $pushName : null;

        $this->ingest->ingestInbound(
            $account,
            $threadKey,
            $displayName,
            $body,
            $data['external_message_id'] ?? null,
            $payload,
            $sentAt,
            $routingJid,
            $direction,
            ! $fromMe,
        );

        return response('OK', 200);
    }

    private function baileysAuthFailureResponse(Request $request): ?Response
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

        return null;
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

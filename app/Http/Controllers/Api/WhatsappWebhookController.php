<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Services\MessageIngestService;
use App\Services\WhatsappCloudService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function __construct(
        private WhatsappCloudService $whatsapp,
        private MessageIngestService $ingest,
    ) {}

    public function verify(Request $request): Response
    {
        // Meta sends dotted keys (hub.verify_token), but PHP may normalize dots to underscores.
        $mode = $request->query('hub.mode') ?? $request->query('hub_mode');
        $token = $request->query('hub.verify_token') ?? $request->query('hub_verify_token');
        $challenge = $request->query('hub.challenge') ?? $request->query('hub_challenge');
        $expected = config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && $expected && hash_equals((string) $expected, (string) $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request): Response
    {
        $raw = $request->getContent();
        if (config('services.whatsapp.app_secret')
            && ! $this->whatsapp->verifySignature($raw, $request->header('X-Hub-Signature-256'))) {
            return response('Invalid signature', 403);
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return response('OK', 200);
        }

        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return response('OK', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                $metadata = $value['metadata'] ?? [];
                $phoneNumberId = isset($metadata['phone_number_id']) ? (string) $metadata['phone_number_id'] : null;
                $account = $this->resolveWhatsappAccount($phoneNumberId);
                if (! $account) {
                    Log::warning('WhatsApp webhook: no matching channel account', ['phone_number_id' => $phoneNumberId]);

                    continue;
                }

                $messages = $value['messages'] ?? [];
                $contacts = $value['contacts'] ?? [];
                $nameByWaId = [];
                foreach ($contacts as $c) {
                    $waId = $c['wa_id'] ?? null;
                    if ($waId) {
                        $nameByWaId[$waId] = $c['profile']['name'] ?? null;
                    }
                }
                foreach ($messages as $msg) {
                    $from = $msg['from'] ?? null;
                    if (! $from) {
                        continue;
                    }
                    $msg = $this->whatsapp->attachInboundMediaIfPresent($account, $msg);
                    [$body, $ts] = $this->whatsappMessageBodyAndTime($msg);
                    $this->ingest->ingestInbound(
                        $account,
                        $from,
                        $nameByWaId[$from] ?? null,
                        $body,
                        $msg['id'] ?? null,
                        $msg,
                        $ts,
                    );
                }

                // Messages sent from the WhatsApp Business app on a phone (field smb_message_echoes).
                // Subscribe to this webhook field in the Meta app or echoes are never delivered.
                foreach ($value['message_echoes'] ?? [] as $msg) {
                    $to = $msg['to'] ?? null;
                    if (! $to) {
                        continue;
                    }
                    $msg = $this->whatsapp->attachInboundMediaIfPresent($account, $msg);
                    [$body, $ts] = $this->whatsappMessageBodyAndTime($msg);
                    $this->ingest->ingestInbound(
                        $account,
                        $to,
                        null,
                        $body,
                        $msg['id'] ?? null,
                        $msg,
                        $ts,
                        null,
                        ChannelMessage::DIRECTION_OUTBOUND,
                        false,
                    );
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * @return array{0: string|null, 1: Carbon|null}
     */
    private function whatsappMessageBodyAndTime(array $msg): array
    {
        $type = $msg['type'] ?? 'unknown';
        $body = null;
        if ($type === 'text') {
            $body = $msg['text']['body'] ?? null;
        } elseif ($type === 'button') {
            $body = $msg['button']['text'] ?? $msg['button']['payload'] ?? null;
        } elseif ($type === 'interactive') {
            $body = $msg['interactive']['button_reply']['title']
                ?? $msg['interactive']['list_reply']['title']
                ?? null;
        } elseif ($type === 'image') {
            $body = trim((string) ($msg['image']['caption'] ?? ''));
            $body = $body !== '' ? $body : '[image]';
        } elseif ($type === 'video') {
            $body = trim((string) ($msg['video']['caption'] ?? ''));
            $body = $body !== '' ? $body : '[video]';
        } elseif ($type === 'audio') {
            $body = '[audio]';
        } elseif ($type === 'document') {
            $body = trim((string) ($msg['document']['caption'] ?? ''));
            $body = $body !== '' ? $body : '[document]';
        } elseif ($type === 'sticker') {
            $body = '[sticker]';
        } else {
            $body = '['.$type.']';
        }
        $ts = isset($msg['timestamp']) ? Carbon::createFromTimestamp((int) $msg['timestamp']) : null;

        return [$body, $ts];
    }

    private function resolveWhatsappAccount(?string $phoneNumberId): ?ChannelAccount
    {
        if ($phoneNumberId) {
            $match = ChannelAccount::query()
                ->whatsapp()
                ->active()
                ->where('external_id', $phoneNumberId)
                ->first();
            if ($match) {
                return $match;
            }
        }

        return ChannelAccount::query()
            ->whatsapp()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }
}

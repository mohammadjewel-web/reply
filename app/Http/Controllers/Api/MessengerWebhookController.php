<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Services\MessageIngestService;
use App\Services\MessengerGraphService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class MessengerWebhookController extends Controller
{
    public function __construct(
        private MessengerGraphService $messenger,
        private MessageIngestService $ingest,
    ) {}

    public function verify(Request $request): Response
    {
        // Meta sends dotted keys (hub.verify_token), but PHP may normalize dots to underscores.
        $mode = $request->query('hub.mode') ?? $request->query('hub_mode');
        $token = $request->query('hub.verify_token') ?? $request->query('hub_verify_token');
        $challenge = $request->query('hub.challenge') ?? $request->query('hub_challenge');
        $expected = config('services.messenger.verify_token');

        if ($mode === 'subscribe' && $expected && hash_equals((string) $expected, (string) $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request): Response
    {
        $raw = $request->getContent();
        if (config('services.messenger.app_secret')
            && ! $this->messenger->verifySignature($raw, $request->header('X-Hub-Signature-256'))) {
            return response('Invalid signature', 403);
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return response('OK', 200);
        }

        if (($payload['object'] ?? null) !== 'page') {
            return response('OK', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $pageId = isset($entry['id']) ? (string) $entry['id'] : '';
            $account = $this->resolveMessengerAccount($pageId !== '' ? $pageId : null);
            if (! $account) {
                Log::warning('Messenger webhook: no matching channel account', ['page_id' => $pageId]);

                continue;
            }

            foreach ($entry['messaging'] ?? [] as $event) {
                $senderId = $event['sender']['id'] ?? null;
                if (! $senderId) {
                    continue;
                }
                if (isset($event['message'])) {
                    $mid = $event['message']['mid'] ?? null;
                    $text = $event['message']['text'] ?? null;
                    if ($text === null && isset($event['message']['attachments'])) {
                        $text = '[attachment]';
                    }
                    $ts = null;
                    if (isset($event['timestamp'])) {
                        $rawTs = (int) $event['timestamp'];
                        $ts = $rawTs > 10_000_000_000
                            ? Carbon::createFromTimestampMs($rawTs)
                            : Carbon::createFromTimestamp($rawTs);
                    }
                    $this->ingest->ingestInbound(
                        $account,
                        $senderId,
                        null,
                        $text,
                        $mid,
                        $event,
                        $ts,
                    );
                }
            }
        }

        return response('OK', 200);
    }

    private function resolveMessengerAccount(?string $pageId): ?ChannelAccount
    {
        if ($pageId) {
            $match = ChannelAccount::query()
                ->messenger()
                ->active()
                ->where('external_id', $pageId)
                ->first();
            if ($match) {
                return $match;
            }
        }

        return ChannelAccount::query()
            ->messenger()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }
}

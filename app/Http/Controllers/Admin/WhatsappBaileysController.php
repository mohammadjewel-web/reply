<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsappBaileysController extends Controller
{
    private function sessionKey(Request $request, ChannelAccount $account): string
    {
        return 'wa-'.$account->id.'-u-'.$request->user()->id;
    }

    private function baseUrl(): ?string
    {
        $url = rtrim((string) config('services.baileys.url'), '/');

        return $url !== '' ? $url : null;
    }

    public function start(Request $request): JsonResponse
    {
        if (! config('services.baileys.enabled')) {
            return response()->json(['ok' => false, 'error' => __('Baileys service is disabled.')], 503);
        }

        $base = $this->baseUrl();
        if (! $base) {
            return response()->json(['ok' => false, 'error' => __('BAILEYS_SERVICE_URL is not set.')], 503);
        }

        $secret = config('services.baileys.secret');
        if (! is_string($secret) || $secret === '') {
            return response()->json(['ok' => false, 'error' => __('BAILEYS_SERVICE_SECRET is not set.')], 503);
        }

        $validated = $request->validate([
            'channel_account_id' => ['required', 'integer'],
        ]);

        $account = ChannelAccount::query()
            ->where('type', ChannelAccount::TYPE_WHATSAPP)
            ->whereKey($validated['channel_account_id'])
            ->firstOrFail();

        $key = $this->sessionKey($request, $account);

        try {
            $response = Http::timeout(20)
                ->withHeaders(['X-Baileys-Secret' => $secret])
                ->acceptJson()
                ->post($base.'/session/start', ['sessionKey' => $key]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => __('Cannot reach Baileys at :url. Start Node in baileys-service (npm start) and check the same BAILEYS_SERVICE_SECRET. Details: :msg', [
                    'url' => $base,
                    'msg' => $e->getMessage(),
                ]),
            ], 502);
        }

        if (! $response->successful()) {
            $detail = $response->json('error')
                ?? $response->json('message')
                ?? (strlen($response->body()) < 500 ? $response->body() : null);

            $message = $detail
                ? (string) $detail
                : __('Baileys HTTP :status. Check X-Baileys-Secret matches Node BAILEYS_SERVICE_SECRET.', [
                    'status' => $response->status(),
                ]);

            return response()->json([
                'ok' => false,
                'error' => $message,
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'sessionKey' => $key,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        if (! config('services.baileys.enabled')) {
            return response()->json(['ok' => false, 'error' => __('Baileys service is disabled.')], 503);
        }

        $base = $this->baseUrl();
        if (! $base) {
            return response()->json(['ok' => false, 'error' => __('BAILEYS_SERVICE_URL is not set.')], 503);
        }

        $secret = config('services.baileys.secret');
        if (! is_string($secret) || $secret === '') {
            return response()->json(['ok' => false, 'error' => __('BAILEYS_SERVICE_SECRET is not set.')], 503);
        }

        $validated = $request->validate([
            'channel_account_id' => ['required', 'integer'],
        ]);

        $account = ChannelAccount::query()
            ->where('type', ChannelAccount::TYPE_WHATSAPP)
            ->whereKey($validated['channel_account_id'])
            ->firstOrFail();

        $key = $this->sessionKey($request, $account);

        try {
            $response = Http::timeout(12)
                ->withHeaders(['X-Baileys-Secret' => $secret])
                ->acceptJson()
                ->get($base.'/session/'.rawurlencode($key).'/status');
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => __('Cannot reach Baileys: :msg', ['msg' => $e->getMessage()]),
            ], 502);
        }

        if (! $response->successful()) {
            return response()->json([
                'ok' => false,
                'error' => $response->json('error') ?? __('Baileys status HTTP :status.', ['status' => $response->status()]),
            ], 502);
        }

        return response()->json($response->json());
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        $response = Http::timeout(20)
            ->withHeaders(['X-Baileys-Secret' => $secret])
            ->acceptJson()
            ->post($base.'/session/start', ['sessionKey' => $key]);

        if (! $response->successful()) {
            return response()->json([
                'ok' => false,
                'error' => $response->json('error') ?? $response->body() ?: __('Baileys start failed.'),
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

        $response = Http::timeout(12)
            ->withHeaders(['X-Baileys-Secret' => $secret])
            ->acceptJson()
            ->get($base.'/session/'.rawurlencode($key).'/status');

        if (! $response->successful()) {
            return response()->json([
                'ok' => false,
                'error' => __('Could not read Baileys session status.'),
            ], 502);
        }

        return response()->json($response->json());
    }
}

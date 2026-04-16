<?php

namespace App\Http\Controllers;

use App\Models\ChannelAccount;
use App\Models\WhatsappLinkSession;
use App\Services\WhatsappCloudService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WhatsappOAuthController extends Controller
{
    public function callback(Request $request, WhatsappCloudService $whatsapp): RedirectResponse
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error_description') ?? $request->query('error');

        if ($error) {
            return redirect()->route('login')->with('error', (string) $error);
        }

        if (! $code || ! $state) {
            return redirect()->route('login')->with('error', 'Missing OAuth parameters.');
        }

        $session = WhatsappLinkSession::query()->where('token', $state)->first();
        if (! $session || $session->isExpired()) {
            return redirect()->route('login')->with('error', 'Invalid or expired link session.');
        }

        $redirectUri = rtrim((string) config('app.url'), '/').'/'.ltrim(route('whatsapp.oauth.callback', [], false), '/');
        $exchange = $whatsapp->exchangeOAuthCode($code, $redirectUri);
        if (! $exchange['ok']) {
            $session->markFailed($exchange['error'] ?? 'Token exchange failed');

            return redirect()->route('whatsapp.pair.status', ['token' => $session->token])
                ->with('error', 'Could not complete WhatsApp signup.');
        }

        $data = $exchange['data'] ?? [];
        $accessToken = $data['access_token'] ?? null;
        if (! $accessToken) {
            $session->markFailed('No access_token in response');

            return redirect()->route('whatsapp.pair.status', ['token' => $session->token])
                ->with('error', 'OAuth response incomplete.');
        }

        $session->markLinked(
            $accessToken,
            $session->phone_number_id,
            $session->waba_id,
        );

        $session->refresh();
        $account = $session->channel_account_id
            ? ChannelAccount::query()->find($session->channel_account_id)
            : ChannelAccount::query()->whatsapp()->active()->orderBy('id')->first();
        if ($account && $account->type === ChannelAccount::TYPE_WHATSAPP) {
            $updates = ['access_token' => $accessToken];
            if ($session->phone_number_id) {
                $updates['external_id'] = $session->phone_number_id;
            }
            if ($session->waba_id) {
                $updates['waba_id'] = $session->waba_id;
            }
            $account->update($updates);
        }

        return redirect()->route('whatsapp.pair.status', ['token' => $session->token])
            ->with('status', 'WhatsApp is linked. In the admin panel, add your Phone number ID if outbound messages fail.');
    }
}

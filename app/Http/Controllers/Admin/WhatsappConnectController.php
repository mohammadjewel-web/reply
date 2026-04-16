<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Models\WhatsappLinkSession;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsappConnectController extends Controller
{
    public function show(Request $request): View
    {
        $whatsappAccounts = ChannelAccount::query()->whatsapp()->orderBy('name')->get();

        $selectedAccount = null;
        if ($request->filled('account')) {
            $selectedAccount = ChannelAccount::query()
                ->whatsapp()
                ->whereKey((int) $request->query('account'))
                ->first();
        }

        $session = WhatsappLinkSession::start(45);
        if ($selectedAccount) {
            $session->channel_account_id = $selectedAccount->id;
            $session->save();
        }

        $linkUrl = $this->buildEmbeddedSignupUrl($session->token);
        $writer = new SvgWriter;
        $qrSvg = $writer->write(new QrCode($linkUrl))->getString();

        $credentialAccount = $selectedAccount
            ?? $whatsappAccounts->first()
            ?? ChannelAccount::query()->whatsapp()->orderBy('id')->first();

        return view('admin.whatsapp-connect', [
            'session' => $session,
            'linkUrl' => $linkUrl,
            'qrSvg' => $qrSvg,
            'webhookUrl' => $this->appUrl('/webhooks/whatsapp'),
            'verifyToken' => config('services.whatsapp.verify_token'),
            'whatsappAccounts' => $whatsappAccounts,
            'selectedAccount' => $selectedAccount,
            'credentialAccount' => $credentialAccount,
            'appUrlIsPublic' => $this->appUrlIsPublic(),
        ]);
    }

    private function buildEmbeddedSignupUrl(string $stateToken): string
    {
        $appId = config('services.facebook.app_id');
        $configId = config('services.whatsapp.embedded_config_id');
        $redirect = $this->appUrl(route('whatsapp.oauth.callback', [], false));

        if ($appId && $configId) {
            $query = http_build_query([
                'client_id' => $appId,
                'config_id' => $configId,
                'response_type' => 'code',
                'override_default_response_type' => 'true',
                'redirect_uri' => $redirect,
                'state' => $stateToken,
            ]);

            $version = config('services.whatsapp.graph_version', 'v21.0');

            return "https://www.facebook.com/{$version}/dialog/oauth?{$query}";
        }

        return $this->appUrl(route('whatsapp.pair.status', ['token' => $stateToken], false));
    }

    private function appUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }

    private function appUrlIsPublic(): bool
    {
        $base = (string) config('app.url');

        if ($base === '') {
            return false;
        }

        $host = parse_url($base, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1' || Str::endsWith($host, '.local')) {
            return false;
        }

        return ! Str::startsWith($host, ['10.', '192.168.', '172.16.', '172.17.', '172.18.', '172.19.', '172.20.', '172.21.', '172.22.', '172.23.', '172.24.', '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.', '172.31.']);
    }
}

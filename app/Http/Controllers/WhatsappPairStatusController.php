<?php

namespace App\Http\Controllers;

use App\Models\WhatsappLinkSession;
use Illuminate\View\View;

class WhatsappPairStatusController extends Controller
{
    public function show(string $token): View
    {
        $session = WhatsappLinkSession::query()->where('token', $token)->firstOrFail();

        return view('whatsapp.pair-status', [
            'session' => $session,
            'webhookUrl' => url('/webhooks/whatsapp'),
        ]);
    }
}

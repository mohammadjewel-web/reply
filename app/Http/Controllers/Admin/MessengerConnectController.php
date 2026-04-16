<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MessengerConnectController extends Controller
{
    public function show(): View
    {
        return view('admin.messenger-connect', [
            'webhookUrl' => url('/webhooks/messenger'),
            'verifyToken' => config('services.messenger.verify_token'),
        ]);
    }
}

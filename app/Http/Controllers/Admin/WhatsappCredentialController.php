<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WhatsappCredentialController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'channel_account_id' => ['required', 'integer', 'exists:channel_accounts,id'],
            'phone_number_id' => ['sometimes', 'nullable', 'string', 'max:128'],
            'waba_id' => ['sometimes', 'nullable', 'string', 'max:128'],
        ]);

        $account = ChannelAccount::query()->findOrFail($validated['channel_account_id']);
        if ($account->type !== ChannelAccount::TYPE_WHATSAPP) {
            abort(404);
        }

        $phoneId = $validated['phone_number_id'] ?? null;
        $wabaId = $validated['waba_id'] ?? null;
        if ($phoneId !== null && $phoneId !== '') {
            $account->external_id = $phoneId;
        }
        if ($wabaId !== null && $wabaId !== '') {
            $account->waba_id = $wabaId;
        }
        $account->save();

        return back()->with('status', __('WhatsApp IDs updated for :name.', ['name' => $account->name]));
    }
}

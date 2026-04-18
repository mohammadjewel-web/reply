<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ChannelAccount;
use App\Models\WhatsappLinkSession;
use App\Services\MessengerGraphService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChannelAccountController extends Controller
{
    public function __construct(
        private MessengerGraphService $messenger,
    ) {}

    public function index(): View
    {
        $whatsapp = ChannelAccount::query()->whatsapp()->orderBy('sort_order')->orderBy('name')->get();
        $messenger = ChannelAccount::query()->messenger()->orderBy('sort_order')->orderBy('name')->get();
        $meta = AppSetting::current();

        return view('admin.connections.index', compact('whatsapp', 'messenger', 'meta'));
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $q = trim((string) $request->input('q', ''));

        $like = '%'.$q.'%';

        $whatsapp = ChannelAccount::query()
            ->whatsapp()
            ->when($q !== '', function ($query) use ($like) {
                $query->where(function ($w) use ($like) {
                    $w->where('name', 'like', $like)
                        ->orWhere('external_id', 'like', $like)
                        ->orWhere('waba_id', 'like', $like);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $messenger = ChannelAccount::query()
            ->messenger()
            ->when($q !== '', function ($query) use ($like) {
                $query->where(function ($w) use ($like) {
                    $w->where('name', 'like', $like)
                        ->orWhere('external_id', 'like', $like);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'whatsapp_html' => view('admin.connections.partials.whatsapp-rows', compact('whatsapp'))->render(),
            'messenger_html' => view('admin.connections.partials.messenger-rows', compact('messenger'))->render(),
        ]);
    }

    public function storeWhatsapp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:128'],
            'access_token' => ['nullable', 'string', 'max:65000'],
            'waba_id' => ['nullable', 'string', 'max:128'],
        ]);

        ChannelAccount::query()->create([
            'type' => ChannelAccount::TYPE_WHATSAPP,
            'name' => $validated['name'],
            'is_active' => true,
            'external_id' => ($validated['external_id'] ?? null) ?: null,
            'access_token' => ($validated['access_token'] ?? null) ?: null,
            'waba_id' => ($validated['waba_id'] ?? null) ?: null,
            'sort_order' => (int) (ChannelAccount::query()->whatsapp()->max('sort_order') ?? 0) + 1,
        ]);

        return back()->with('status', __('WhatsApp connection added.'));
    }

    public function storeMessenger(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'external_id' => ['required', 'string', 'max:128'],
            'access_token' => ['required', 'string', 'max:65000'],
        ]);

        ChannelAccount::query()->create([
            'type' => ChannelAccount::TYPE_MESSENGER,
            'name' => $validated['name'],
            'is_active' => true,
            'external_id' => $validated['external_id'],
            'access_token' => $validated['access_token'],
            'waba_id' => null,
            'sort_order' => (int) (ChannelAccount::query()->messenger()->max('sort_order') ?? 0) + 1,
        ]);

        $subscribe = $this->messenger->subscribePageApp($validated['access_token'], $validated['external_id']);
        if (! $subscribe['ok']) {
            return back()->withErrors([
                'messenger' => __('Page added, but automatic page subscription failed. Check token permissions. Details: :error', [
                    'error' => (string) $subscribe['error'],
                ]),
            ])->with('status', __('Messenger page added.'));
        }

        return back()->with('status', __('Messenger page added and subscribed successfully.'));
    }

    public function update(Request $request, ChannelAccount $channelAccount): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:128'],
            'access_token' => ['nullable', 'string', 'max:65000'],
            'waba_id' => ['nullable', 'string', 'max:128'],
        ];

        if ($channelAccount->type === ChannelAccount::TYPE_MESSENGER) {
            $rules['external_id'] = ['required', 'string', 'max:128'];
        }

        $validated = $request->validate($rules);

        $channelAccount->name = $validated['name'];
        $channelAccount->external_id = ($validated['external_id'] ?? null) ?: null;
        if (! empty($validated['access_token'] ?? null)) {
            $channelAccount->access_token = $validated['access_token'];
        }
        if ($channelAccount->type === ChannelAccount::TYPE_WHATSAPP) {
            $channelAccount->waba_id = ($validated['waba_id'] ?? null) ?: null;
        }
        $channelAccount->save();

        if ($channelAccount->type === ChannelAccount::TYPE_MESSENGER && ! empty($validated['access_token'] ?? null)) {
            $subscribe = $this->messenger->subscribePageApp($validated['access_token'], $channelAccount->external_id);
            if (! $subscribe['ok']) {
                return back()->withErrors([
                    'messenger' => __('Connection updated, but automatic page subscription failed. Details: :error', [
                        'error' => (string) $subscribe['error'],
                    ]),
                ])->with('status', __('Connection updated.'));
            }

            return back()->with('status', __('Connection updated and page subscription refreshed.'));
        }

        return back()->with('status', __('Connection updated.'));
    }

    public function toggleActive(ChannelAccount $channelAccount): RedirectResponse
    {
        $channelAccount->update(['is_active' => ! $channelAccount->is_active]);

        return back()->with('status', $channelAccount->is_active
            ? __('Connection enabled.')
            : __('Connection disabled.'));
    }

    public function destroy(ChannelAccount $channelAccount): RedirectResponse
    {
        $name = $channelAccount->name;
        $typeLabel = $channelAccount->type === ChannelAccount::TYPE_WHATSAPP
            ? __('WhatsApp')
            : __('Messenger');

        DB::transaction(function () use ($channelAccount) {
            WhatsappLinkSession::query()
                ->where('channel_account_id', $channelAccount->id)
                ->delete();

            $channelAccount->delete();
        });

        return redirect()
            ->route('connections.index')
            ->with('status', __('The :type connection “:name” was removed. Existing chats and message history stay in the inbox.', [
                'type' => $typeLabel,
                'name' => $name,
            ]));
    }

    public function updateMetaConfig(Request $request): RedirectResponse
    {
        $section = (string) $request->input('section', '');
        if (! in_array($section, ['global', 'whatsapp', 'messenger'], true)) {
            return back()->withErrors([
                'meta' => __('Invalid credentials section.'),
            ]);
        }

        $rules = match ($section) {
            'global' => [
                'facebook_app_id' => ['required', 'string', 'max:100'],
                'facebook_app_secret' => ['nullable', 'string', 'max:255'],
                'facebook_client_token' => ['nullable', 'string', 'max:255'],
            ],
            'whatsapp' => [
                'whatsapp_verify_token' => ['required', 'string', 'max:255'],
                'whatsapp_app_secret' => ['nullable', 'string', 'max:255'],
                'whatsapp_embedded_config_id' => ['required', 'string', 'max:255'],
            ],
            'messenger' => [
                'messenger_verify_token' => ['required', 'string', 'max:255'],
                'messenger_app_secret' => ['nullable', 'string', 'max:255'],
            ],
        };

        $validated = $request->validate($rules);

        $settings = AppSetting::current();
        if ($section === 'global') {
            $settings->facebook_app_id = trim($validated['facebook_app_id']);
            if (($validated['facebook_app_secret'] ?? '') !== '') {
                $settings->facebook_app_secret = trim($validated['facebook_app_secret']);
            }
            if (($validated['facebook_client_token'] ?? '') !== '') {
                $settings->facebook_client_token = trim($validated['facebook_client_token']);
            }
        }
        if ($section === 'whatsapp') {
            $settings->whatsapp_verify_token = trim($validated['whatsapp_verify_token']);
            $settings->whatsapp_embedded_config_id = trim($validated['whatsapp_embedded_config_id']);
            if (($validated['whatsapp_app_secret'] ?? '') !== '') {
                $settings->whatsapp_app_secret = trim($validated['whatsapp_app_secret']);
            }
        }
        if ($section === 'messenger') {
            $settings->messenger_verify_token = trim($validated['messenger_verify_token']);
            if (($validated['messenger_app_secret'] ?? '') !== '') {
                $settings->messenger_app_secret = trim($validated['messenger_app_secret']);
            }
        }

        $settings->save();
        $settings->applyMetaConfig();

        return back()->with('status', __('Credentials saved.'));
    }
}

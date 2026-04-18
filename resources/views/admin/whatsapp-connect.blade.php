<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('WhatsApp connection') }}</h2>
    </x-slot>

    <div class="app-page app-page--narrow space-y-6">
        @unless ($appUrlIsPublic)
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                {{ __('QR login requires a public APP_URL (not localhost/127.0.0.1/private IP). Set APP_URL to your HTTPS domain, then run php artisan optimize:clear and reload this page.') }}
            </div>
        @endunless

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('status') }}</div>
        @endif

        @php
            $embeddedReady = filled(config('services.facebook.app_id')) && filled(config('services.whatsapp.embedded_config_id'));
        @endphp

        @if ($whatsappAccounts->isNotEmpty())
            <div class="app-card">
                <h3 class="app-card__title">{{ __('Which connection?') }}</h3>
                <p class="app-card__lead">{{ __('OAuth and phone IDs apply to the selected row. Manage all lines under Connections.') }}</p>
                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    @foreach ($whatsappAccounts as $acc)
                        <a
                            href="{{ route('whatsapp.connect', ['account' => $acc->id]) }}"
                            class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition {{ $credentialAccount && $credentialAccount->id === $acc->id ? 'border-emerald-500 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                        >
                            <svg class="h-5 w-5 text-[#25d366]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            {{ $acc->name }}
                            @if (! $acc->is_active)
                                <span class="text-xs font-normal text-amber-700">({{ __('off') }})</span>
                            @endif
                        </a>
                    @endforeach
                    @if (auth()->user()->allows('connections.manage'))
                        <a href="{{ route('connections.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-900">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Add another in Connections') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <div class="app-card">
            <h3 class="app-card__title">{{ __('Connect WhatsApp (Meta Cloud API)') }}</h3>
            <p class="app-card__lead">
                @if ($embeddedReady)
                    {{ __('Click the button below to open Meta and link your WhatsApp Business account (Embedded Signup). You can also scan the QR code on your phone—it opens the same link.') }}
                @else
                    {{ __('Click the button to open the connection page, then follow the steps. Add Facebook App ID and WhatsApp Embedded Config ID in Connections → Meta app credentials for Meta’s full signup flow. After linking, add your Phone number ID below if needed.') }}
                @endif
            </p>

            <div class="mt-6 flex flex-col items-stretch gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <a
                    href="{{ $linkUrl }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#25d366] px-6 py-3.5 text-sm font-semibold text-white shadow-md transition hover:bg-[#20bd5a] focus:outline-none focus:ring-2 focus:ring-[#25d366] focus:ring-offset-2"
                >
                    <svg class="h-6 w-6 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    {{ __('Connect WhatsApp') }}
                </a>
                <a
                    href="{{ $linkUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    {{ __('Open in new tab') }}
                </a>
            </div>

            <p class="mt-4 text-xs text-slate-500">
                {{ __('This link expires after a few minutes. Refresh the page to generate a new one.') }}
            </p>

            <div class="mt-8 border-t border-slate-100 pt-8">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Or scan QR (opens Meta link)') }}</p>
                <div class="mt-4 flex justify-center rounded-xl bg-slate-50 p-6 ring-1 ring-slate-200/80">
                    {!! $qrSvg !!}
                </div>
                <p class="mt-3 break-all text-xs text-slate-400">{{ $linkUrl }}</p>
            </div>
        </div>

        @if ($baileys['enabled'] && $baileys['channelAccountId'])
            <div
                class="app-card border-violet-200/60 bg-gradient-to-br from-violet-50/80 to-white"
                x-data="replyBaileysPairing"
            >
                <h3 class="app-card__title">{{ __('WhatsApp Web QR (Baileys)') }}</h3>
                <p class="app-card__lead">
                    {{ __('Uses the WhatsApp Web protocol. On your phone open WhatsApp → Settings → Linked devices → Link a device, then scan the QR below. This is not Meta Cloud API embedded signup.') }}
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                        x-on:click="startPairing()"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        {{ __('Generate pairing QR') }}
                    </button>
                    <span class="self-center text-xs text-slate-500" x-text="statusLabel + ': ' + lineStatus"></span>
                </div>
                <template x-if="lineError">
                    <p class="mt-3 text-sm text-red-700" x-text="lineError"></p>
                </template>
                <template x-if="lineStatus === 'connected'">
                    <p class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
                        {{ __('Device linked in Baileys. Session files are stored on the Baileys server; inbox messaging still uses Cloud API unless you integrate Baileys events into this app.') }}
                    </p>
                </template>
                <div class="mt-6 flex justify-center rounded-xl bg-white p-6 ring-1 ring-violet-200/80">
                    <template x-if="!qrDataUrl">
                        <p class="text-center text-sm text-slate-500">{{ __('No QR yet. Click “Generate pairing QR” and keep this page open.') }}</p>
                    </template>
                    <template x-if="qrDataUrl">
                        <img :src="qrDataUrl" alt="{{ __('WhatsApp Web QR') }}" class="h-64 w-64 max-w-full rounded-lg bg-white object-contain" width="280" height="280" />
                    </template>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    {{ __('Requires Node 20+: run `npm install` and `npm start` in /baileys-service with BAILEYS_SERVICE_* set. After `git pull`, restart that Node process (old code stays in memory until you do). On the server, `curl http://127.0.0.1:3710/health` should show a `rev` field that matches the latest deploy.') }}
                </p>
            </div>
        @elseif ($credentialAccount)
            <div class="app-card border-slate-200 bg-slate-50/60">
                <h3 class="app-card__title">{{ __('WhatsApp Web QR (Baileys)') }}</h3>
                <p class="app-card__lead text-slate-600">
                    {{ __('Enable the Baileys service to show a WhatsApp Web–style QR here: set BAILEYS_SERVICE_ENABLED=true, BAILEYS_SERVICE_URL, and BAILEYS_SERVICE_SECRET, then start the Node process in baileys-service.') }}
                </p>
            </div>
        @endif

        <div class="app-card">
            <h3 class="app-card__title">{{ __('Webhook (Cloud API)') }}</h3>
            <p class="app-card__lead">{{ __('In the Meta developer app, set the callback URL and verify token:') }}</p>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="font-medium text-slate-600">{{ __('Callback URL') }}</dt>
                    <dd class="app-code-block mt-1">{{ $webhookUrl }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-600">{{ __('Verify token') }}</dt>
                    <dd class="app-code-block mt-1">{{ $verifyToken ?: __('(set WhatsApp Verify Token in Connections)') }}</dd>
                </div>
            </dl>
        </div>

        <div class="app-card">
            <h3 class="app-card__title">{{ __('Phone number & WABA IDs') }}</h3>
            <p class="app-card__lead">{{ __('After signup, paste IDs from Meta Business Suite / WhatsApp → API setup.') }}</p>
            @if ($credentialAccount)
                <form method="post" action="{{ route('whatsapp.credentials') }}" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="channel_account_id" value="{{ $credentialAccount->id }}" />
                    <div>
                        <x-input-label :value="__('Saving to')" />
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ $credentialAccount->name }}</p>
                    </div>
                    <div>
                        <x-input-label for="phone_number_id" :value="__('Phone number ID')" />
                        <x-text-input id="phone_number_id" name="phone_number_id" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('phone_number_id', $credentialAccount->external_id)" />
                        <x-input-error :messages="$errors->get('phone_number_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="waba_id" :value="__('WhatsApp Business Account ID (optional)')" />
                        <x-text-input id="waba_id" name="waba_id" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('waba_id', $credentialAccount->waba_id)" />
                        <x-input-error :messages="$errors->get('waba_id')" class="mt-2" />
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('Save') }}
                    </button>
                </form>
            @else
                <p class="mt-4 text-sm text-amber-800">{{ __('Add a WhatsApp connection first (Connections page).') }}</p>
            @endif
        </div>
    </div>

    @if ($baileys['enabled'] && $baileys['channelAccountId'])
        @push('scripts')
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('replyBaileysPairing', () => ({
                        accountId: {{ (int) $baileys['channelAccountId'] }},
                        timer: null,
                        qrDataUrl: null,
                        lineStatus: 'idle',
                        lineError: null,
                        startUrl: @json(route('whatsapp.baileys.start')),
                        statusUrl: @json(route('whatsapp.baileys.status')),
                        csrf: @json(csrf_token()),
                        msgStartError: @json(__('Could not start Baileys session.')),
                        msgNetwork: @json(__('Network error while polling Baileys.')),
                        statusLabel: @json(__('Status')),
                        async startPairing() {
                            this.lineError = null;
                            this.qrDataUrl = null;
                            this.lineStatus = 'starting';
                            if (this.timer) {
                                clearInterval(this.timer);
                                this.timer = null;
                            }
                            const res = await fetch(this.startUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                },
                                body: JSON.stringify({ channel_account_id: this.accountId }),
                            });
                            const data = await res.json().catch(function () {
                                return {};
                            });
                            if (!res.ok) {
                                this.lineError =
                                    data.error ||
                                    this.msgStartError + ' (HTTP ' + res.status + ')';
                                this.lineStatus = 'error';
                                return;
                            }
                            if (!data.ok) {
                                this.lineError = data.error || this.msgStartError;
                                this.lineStatus = 'error';
                                return;
                            }
                            this.pollOnce();
                            this.timer = setInterval(() => this.pollOnce(), 2000);
                        },
                        async pollOnce() {
                            try {
                                const url =
                                    this.statusUrl +
                                    '?channel_account_id=' +
                                    encodeURIComponent(this.accountId);
                                const res = await fetch(url, {
                                    headers: { Accept: 'application/json' },
                                });
                                const data = await res.json().catch(function () {
                                    return {};
                                });
                                if (!data.ok && data.error) {
                                    this.lineError = data.error;
                                    this.lineStatus = 'error';
                                    if (this.timer) {
                                        clearInterval(this.timer);
                                        this.timer = null;
                                    }
                                    return;
                                }
                                this.lineStatus = data.status || 'unknown';
                                if (data.qrDataUrl) {
                                    this.qrDataUrl = data.qrDataUrl;
                                }
                                if (data.error) {
                                    this.lineError = data.error;
                                }
                                if (['connected', 'error', 'logged_out'].includes(this.lineStatus)) {
                                    if (this.timer) {
                                        clearInterval(this.timer);
                                        this.timer = null;
                                    }
                                }
                            } catch (e) {
                                this.lineError = this.msgNetwork;
                                if (this.timer) {
                                    clearInterval(this.timer);
                                    this.timer = null;
                                }
                            }
                        },
                    }));
                });
            </script>
        @endpush
    @endif
</x-app-layout>

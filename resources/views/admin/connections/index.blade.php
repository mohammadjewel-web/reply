<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Connections') }}</h2>
    </x-slot>

    @php
        $globalReady = filled($meta->facebook_app_id) && filled($meta->facebook_app_secret);
        $whatsappReady = filled($meta->whatsapp_verify_token) && filled($meta->whatsapp_embedded_config_id) && filled($meta->whatsapp_app_secret);
        $messengerReady = filled($meta->messenger_verify_token) && filled($meta->messenger_app_secret);
    @endphp

    <div class="app-page space-y-8">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <section class="app-card">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-lg font-semibold text-slate-900">{{ __('Global credentials (add once)') }}</h3>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $globalReady ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $globalReady ? __('Saved') : __('Not set') }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ __('These are app-level values shared by both WhatsApp and Messenger.') }}</p>
            <form method="post" action="{{ route('connections.meta') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                @method('PATCH')
                <input type="hidden" name="section" value="global" />
                <div>
                    <x-input-label for="meta_facebook_app_id" :value="__('Facebook App ID')" />
                    <x-text-input id="meta_facebook_app_id" name="facebook_app_id" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('facebook_app_id', $meta->facebook_app_id)" required />
                </div>
                <div>
                    <x-input-label for="meta_facebook_client_token" :value="__('Facebook Client Token')" />
                    <x-text-input id="meta_facebook_client_token" name="facebook_client_token" type="text" class="mt-1 block w-full font-mono text-sm" placeholder="{{ __('Leave blank to keep existing') }}" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="meta_facebook_app_secret" :value="__('Facebook App Secret')" />
                    <x-text-input id="meta_facebook_app_secret" name="facebook_app_secret" type="password" class="mt-1 block w-full font-mono text-sm" placeholder="{{ __('Leave blank to keep existing') }}" autocomplete="new-password" />
                </div>
                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('Save global credentials') }}
                    </button>
                </div>
            </form>
        </section>

        <div class="app-card">
            <label for="connections-search" class="block text-sm font-medium text-slate-700">{{ __('Search accounts') }}</label>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('Filter by name, phone or page ID, or WABA ID.') }}</p>
            <div class="relative mt-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input
                    type="search"
                    id="connections-search"
                    name="q"
                    autocomplete="off"
                    placeholder="{{ __('Type to search…') }}"
                    class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                />
            </div>
        </div>

        {{-- WhatsApp accounts --}}
        <section id="connections-whatsapp" class="scroll-mt-24">
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#25d366]/15 text-[#25d366] ring-1 ring-[#25d366]/30">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </span>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('WhatsApp Business accounts') }}</h3>
                    <p class="text-sm text-slate-500">{{ __('Each number appears as its own line in the inbox. Match Phone number ID to Meta webhooks.') }}</p>
                </div>
            </div>

            <div class="app-card mb-6">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="text-sm font-semibold text-slate-800">{{ __('WhatsApp credentials') }}</h4>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $whatsappReady ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $whatsappReady ? __('Saved') : __('Not set') }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ __('Add once for WhatsApp webhook verification and embedded signup.') }}</p>
                <form method="post" action="{{ route('connections.meta') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="section" value="whatsapp" />
                    <div>
                        <x-input-label for="meta_whatsapp_verify_token" :value="__('WhatsApp Verify Token')" />
                        <x-text-input id="meta_whatsapp_verify_token" name="whatsapp_verify_token" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('whatsapp_verify_token', $meta->whatsapp_verify_token)" required />
                    </div>
                    <div>
                        <x-input-label for="meta_whatsapp_embedded_config_id" :value="__('WhatsApp Embedded Config ID')" />
                        <x-text-input id="meta_whatsapp_embedded_config_id" name="whatsapp_embedded_config_id" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('whatsapp_embedded_config_id', $meta->whatsapp_embedded_config_id)" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="meta_whatsapp_app_secret" :value="__('WhatsApp App Secret')" />
                        <x-text-input id="meta_whatsapp_app_secret" name="whatsapp_app_secret" type="password" class="mt-1 block w-full font-mono text-sm" placeholder="{{ __('Leave blank to keep existing') }}" autocomplete="new-password" />
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#25d366] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#20bd5a]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('Save WhatsApp credentials') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="app-card mb-6">
                <h4 class="text-sm font-semibold text-slate-800">{{ __('Add WhatsApp connection') }}</h4>
                <p class="mt-1 text-xs text-slate-500">{{ __('Then use WhatsApp setup to run OAuth or paste tokens. Phone number ID routes inbound webhooks to this row.') }}</p>
                <form method="post" action="{{ route('connections.store.whatsapp') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div class="sm:col-span-2">
                        <x-input-label for="wa_name" :value="__('Display name')" />
                        <x-text-input id="wa_name" name="name" type="text" class="mt-1 block w-full" required placeholder="{{ __('e.g. Sales line') }}" />
                    </div>
                    <div>
                        <x-input-label for="wa_ext" :value="__('Phone number ID')" />
                        <x-text-input id="wa_ext" name="external_id" type="text" class="mt-1 block w-full font-mono text-sm" placeholder="123456789012345" />
                    </div>
                    <div>
                        <x-input-label for="wa_token" :value="__('System user token (optional)')" />
                        <x-text-input id="wa_token" name="access_token" type="password" class="mt-1 block w-full font-mono text-sm" autocomplete="new-password" />
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#25d366] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#20bd5a]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Add account') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3">{{ __('Name') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Phone number ID') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('WABA') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="connections-tbody-whatsapp" class="divide-y divide-slate-100">
                            @include('admin.connections.partials.whatsapp-rows')
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- Messenger accounts --}}
        <section id="connections-messenger" class="scroll-mt-24">
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0084ff]/15 text-[#0084ff] ring-1 ring-[#0084ff]/30">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </span>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('Facebook / Messenger pages') }}</h3>
                    <p class="text-sm text-slate-500">{{ __('Page ID in webhooks must match a row here. Each page uses its own token for sending.') }}</p>
                </div>
            </div>

            <div class="app-card mb-6">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="text-sm font-semibold text-slate-800">{{ __('Messenger credentials') }}</h4>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $messengerReady ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $messengerReady ? __('Saved') : __('Not set') }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ __('Add once for Messenger webhook verification and signature checks.') }}</p>
                <form method="post" action="{{ route('connections.meta') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="section" value="messenger" />
                    <div>
                        <x-input-label for="meta_messenger_verify_token" :value="__('Messenger Verify Token')" />
                        <x-text-input id="meta_messenger_verify_token" name="messenger_verify_token" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('messenger_verify_token', $meta->messenger_verify_token)" required />
                    </div>
                    <div>
                        <x-input-label for="meta_messenger_app_secret" :value="__('Messenger App Secret')" />
                        <x-text-input id="meta_messenger_app_secret" name="messenger_app_secret" type="password" class="mt-1 block w-full font-mono text-sm" placeholder="{{ __('Leave blank to keep existing') }}" autocomplete="new-password" />
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#0084ff] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('Save Messenger credentials') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="app-card mb-6">
                <h4 class="text-sm font-semibold text-slate-800">{{ __('Add Messenger page') }}</h4>
                <form method="post" action="{{ route('connections.store.messenger') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div class="sm:col-span-2">
                        <x-input-label for="ms_name" :value="__('Display name')" />
                        <x-text-input id="ms_name" name="name" type="text" class="mt-1 block w-full" required placeholder="{{ __('e.g. Brand page') }}" />
                    </div>
                    <div>
                        <x-input-label for="ms_page" :value="__('Page ID')" />
                        <x-text-input id="ms_page" name="external_id" type="text" class="mt-1 block w-full font-mono text-sm" required />
                    </div>
                    <div>
                        <x-input-label for="ms_tok" :value="__('Page access token')" />
                        <x-text-input id="ms_tok" name="access_token" type="password" class="mt-1 block w-full font-mono text-sm" required autocomplete="new-password" />
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#0084ff] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Add page') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3">{{ __('Name') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Page ID') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="connections-tbody-messenger" class="divide-y divide-slate-100">
                            @include('admin.connections.partials.messenger-rows')
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <dialog
        id="connection-remove-dialog"
        class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl [&::backdrop]:bg-slate-900/50"
        aria-labelledby="connection-remove-title"
    >
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 id="connection-remove-title" class="text-lg font-semibold text-slate-900">{{ __('Remove connection?') }}</h2>
            <p class="mt-2 text-sm text-slate-600">
                {{ __('You are about to remove') }}
                <strong id="connection-remove-name" class="text-slate-900"></strong>
                {{ __('from Connections. Chat history stays in the inbox; sending stays disabled until you add a connection again.') }}
            </p>
            <p class="mt-2 text-xs text-slate-500">{{ __('This only deletes the connection row and credentials. It does not erase past messages.') }}</p>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2 px-6 py-4">
            <button
                type="button"
                id="connection-remove-cancel"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                {{ __('Cancel') }}
            </button>
            <button
                type="button"
                id="connection-remove-confirm"
                class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700"
            >
                {{ __('Remove connection') }}
            </button>
        </div>
    </dialog>
    <form id="connection-destroy-form" method="post" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    @push('scripts')
        <script>
            (function () {
                const searchUrl = @json(route('connections.search'));
                const input = document.getElementById('connections-search');
                const waBody = document.getElementById('connections-tbody-whatsapp');
                const msBody = document.getElementById('connections-tbody-messenger');
                if (!input || !waBody || !msBody) return;

                let timer = null;
                let lastController = null;

                function fetchRows(q) {
                    if (lastController) lastController.abort();
                    lastController = new AbortController();
                    const url = new URL(searchUrl, window.location.origin);
                    url.searchParams.set('q', q);
                    fetch(url.toString(), {
                        signal: lastController.signal,
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (res) {
                            if (!res.ok) throw new Error('Search failed');
                            return res.json();
                        })
                        .then(function (data) {
                            if (typeof data.whatsapp_html === 'string') waBody.innerHTML = data.whatsapp_html;
                            if (typeof data.messenger_html === 'string') msBody.innerHTML = data.messenger_html;
                        })
                        .catch(function (err) {
                            if (err.name === 'AbortError') return;
                        });
                }

                input.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        fetchRows(input.value.trim());
                    }, 300);
                });

                document.addEventListener('click', function (e) {
                    const btn = e.target.closest('[data-toggle-edit]');
                    if (!btn) return;
                    const tr = btn.closest('tr');
                    if (!tr || !tr.nextElementSibling) return;
                    const next = tr.nextElementSibling;
                    if (next.hasAttribute('data-edit-row')) {
                        next.classList.toggle('hidden');
                    }
                });

                const removeDialog = document.getElementById('connection-remove-dialog');
                const destroyForm = document.getElementById('connection-destroy-form');
                const removeNameEl = document.getElementById('connection-remove-name');
                const removeConfirm = document.getElementById('connection-remove-confirm');
                const removeCancel = document.getElementById('connection-remove-cancel');
                let removeTargetUrl = '';

                document.addEventListener('click', function (e) {
                    const trigger = e.target.closest('[data-connection-remove]');
                    if (!trigger || !removeDialog || !destroyForm || !removeNameEl) return;
                    e.preventDefault();
                    removeTargetUrl = trigger.getAttribute('data-remove-url') || '';
                    removeNameEl.textContent = trigger.getAttribute('data-remove-name') || '';
                    if (typeof removeDialog.showModal === 'function') {
                        removeDialog.showModal();
                    }
                });

                if (removeCancel && removeDialog) {
                    removeCancel.addEventListener('click', function () {
                        removeDialog.close();
                        removeTargetUrl = '';
                    });
                }

                if (removeConfirm && destroyForm && removeDialog) {
                    removeConfirm.addEventListener('click', function () {
                        if (!removeTargetUrl) {
                            removeDialog.close();
                            return;
                        }
                        destroyForm.action = removeTargetUrl;
                        destroyForm.submit();
                    });
                }

                if (removeDialog) {
                    removeDialog.addEventListener('cancel', function () {
                        removeTargetUrl = '';
                    });
                }
            })();
        </script>
    @endpush
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Settings') }}</h2>
    </x-slot>

    <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col space-y-8 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        {{-- Hero --}}
        <div class="relative overflow-hidden rounded-3xl border border-[color:var(--app-card-border)] bg-gradient-to-br from-[color:var(--app-card-bg)] via-[color:var(--app-card-bg)] to-[color:var(--app-shell-bg)] p-8 shadow-[0_4px_24px_-4px_rgba(0,0,0,0.08)] ring-1 ring-black/[0.03] sm:p-10">
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[color:var(--app-primary)]/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-16 left-1/4 h-48 w-48 rounded-full bg-[color:var(--app-accent)]/10 blur-3xl"></div>
            <div class="relative">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--app-primary)]">{{ __('Workspace') }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-[color:var(--app-text)] sm:text-4xl">{{ __('Settings') }}</h1>
                <p class="mt-3 max-w-xl text-base leading-relaxed text-[color:var(--app-text-muted)]">
                    {{ __('Manage notifications, appearance, and integrations in one place.') }}
                </p>
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-4 py-2.5 text-sm font-semibold text-[color:var(--app-text)] shadow-sm transition hover:border-[color:var(--app-primary)]/30 hover:shadow-md">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[color:var(--app-primary)]/20 to-[color:var(--app-accent)]/15 text-sm font-bold text-[color:var(--app-primary)]">{{ strtoupper(mb_substr((string) auth()->user()->name, 0, 1)) }}</span>
                        {{ __('Profile & password') }}
                    </a>
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 shadow-sm ring-1 ring-emerald-500/10 sm:px-5">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
                <span class="pt-1">{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="flex items-start gap-3 rounded-2xl border border-red-200/80 bg-red-50/90 px-4 py-3 text-sm text-red-900 shadow-sm ring-1 ring-red-500/10 sm:px-5">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
                <span class="pt-1">{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- Notifications --}}
        <section class="overflow-hidden rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] shadow-[0_1px_3px_rgba(0,0,0,0.05)] ring-1 ring-black/[0.03]">
            <div class="border-b border-[color:var(--app-card-border)]/80 bg-gradient-to-r from-[color:var(--app-primary)]/[0.06] to-transparent px-6 py-5 sm:px-8">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[color:var(--app-primary)]/20 to-[color:var(--app-accent)]/10 text-[color:var(--app-primary)] ring-1 ring-[color:var(--app-primary)]/15">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-[color:var(--app-text)]">{{ __('Notifications') }}</h2>
                        <p class="mt-0.5 text-sm text-[color:var(--app-text-muted)]">{{ __('Sounds, desktop alerts, and the header bell.') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6 sm:px-8 sm:py-8">
                <form method="post" action="{{ route('settings.notifications') }}" class="space-y-5">
                    @csrf
                    @method('PATCH')
                    <label class="group flex cursor-pointer items-start gap-4 rounded-xl border border-transparent p-3 transition hover:border-[color:var(--app-card-border)] hover:bg-[color:var(--app-shell-bg)]/80">
                        <input type="checkbox" name="mute_sound" value="1" @checked(auth()->user()->notificationPreferencesResolved()['mute_sound']) class="mt-0.5 h-4 w-4 rounded border-[color:var(--app-card-border)] text-[color:var(--app-primary)] focus:ring-[color:var(--app-primary)]" />
                        <span>
                            <span class="block text-sm font-semibold text-[color:var(--app-text)]">{{ __('Mute sound alerts') }}</span>
                            <span class="mt-1 block text-sm leading-relaxed text-[color:var(--app-text-muted)]">{{ __('No beep when new messages arrive while this page is open.') }}</span>
                        </span>
                    </label>
                    <label class="group flex cursor-pointer items-start gap-4 rounded-xl border border-transparent p-3 transition hover:border-[color:var(--app-card-border)] hover:bg-[color:var(--app-shell-bg)]/80">
                        <input type="checkbox" name="mute_desktop" value="1" @checked(auth()->user()->notificationPreferencesResolved()['mute_desktop']) class="mt-0.5 h-4 w-4 rounded border-[color:var(--app-card-border)] text-[color:var(--app-primary)] focus:ring-[color:var(--app-primary)]" />
                        <span>
                            <span class="block text-sm font-semibold text-[color:var(--app-text)]">{{ __('Mute desktop notifications') }}</span>
                            <span class="mt-1 block text-sm leading-relaxed text-[color:var(--app-text-muted)]">{{ __('Do not show system notifications (you can still allow them in the browser).') }}</span>
                        </span>
                    </label>
                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[color:var(--app-primary)] px-5 py-2.5 text-sm font-semibold text-[color:var(--app-primary-text)] shadow-sm transition hover:bg-[color:var(--app-primary-hover)] focus:outline-none focus:ring-2 focus:ring-[color:var(--app-primary)]/40">
                            {{ __('Save notification preferences') }}
                        </button>
                        <button type="button" id="request-desktop-notifications" class="inline-flex items-center rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-4 py-2.5 text-sm font-semibold text-[color:var(--app-text)] shadow-sm transition hover:bg-[color:var(--app-shell-bg)]">
                            {{ __('Enable browser notifications') }}
                        </button>
                    </div>
                </form>
            </div>
        </section>

        @if (auth()->user()->allows('settings.integrations'))
            {{-- Email configuration --}}
            <section class="overflow-hidden rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] shadow-[0_1px_3px_rgba(0,0,0,0.05)] ring-1 ring-black/[0.03]">
                <div class="border-b border-[color:var(--app-card-border)]/80 bg-gradient-to-r from-sky-500/[0.08] to-transparent px-6 py-5 sm:px-8">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100/70 text-sky-700 ring-1 ring-sky-200/70">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8m-18 8h18a2 2 0 002-2V8a2 2 0 00-2-2H3a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-[color:var(--app-text)]">{{ __('Email configuration') }}</h2>
                            <p class="mt-0.5 text-sm text-[color:var(--app-text-muted)]">{{ __('Configure SMTP used for verification and password reset emails.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-6 sm:px-8 sm:py-8">
                    <form method="post" action="{{ route('settings.email') }}" class="space-y-5">
                        @csrf
                        @method('PATCH')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="mail_host" :value="__('SMTP host')" />
                                <x-text-input id="mail_host" name="mail_host" type="text" class="mt-2 block w-full rounded-xl border-[color:var(--app-card-border)] shadow-sm focus:border-[color:var(--app-primary)] focus:ring-[color:var(--app-primary)]/20" :value="old('mail_host', $mail['mail_host'])" required />
                            </div>
                            <div>
                                <x-input-label for="mail_port" :value="__('SMTP port')" />
                                <x-text-input id="mail_port" name="mail_port" type="number" class="mt-2 block w-full rounded-xl border-[color:var(--app-card-border)] shadow-sm focus:border-[color:var(--app-primary)] focus:ring-[color:var(--app-primary)]/20" :value="old('mail_port', (string) $mail['mail_port'])" min="1" max="65535" required />
                            </div>
                            <div>
                                <x-input-label for="mail_username" :value="__('SMTP username')" />
                                <x-text-input id="mail_username" name="mail_username" type="text" class="mt-2 block w-full rounded-xl border-[color:var(--app-card-border)] shadow-sm focus:border-[color:var(--app-primary)] focus:ring-[color:var(--app-primary)]/20" :value="old('mail_username', $mail['mail_username'])" />
                            </div>
                            <div>
                                <x-input-label for="mail_password" :value="__('SMTP password')" />
                                <x-text-input id="mail_password" name="mail_password" type="password" class="mt-2 block w-full rounded-xl border-[color:var(--app-card-border)] shadow-sm focus:border-[color:var(--app-primary)] focus:ring-[color:var(--app-primary)]/20" placeholder="{{ __('Leave blank to keep existing password') }}" />
                            </div>
                            <div>
                                <x-input-label for="mail_encryption" :value="__('Encryption')" />
                                <select id="mail_encryption" name="mail_encryption" class="mt-2 block w-full rounded-xl border-[color:var(--app-card-border)] shadow-sm focus:border-[color:var(--app-primary)] focus:ring-[color:var(--app-primary)]/20">
                                    @php($mailEnc = old('mail_encryption', $mail['mail_encryption'] ?? 'tls'))
                                    <option value="tls" @selected($mailEnc === 'tls')>TLS</option>
                                    <option value="ssl" @selected($mailEnc === 'ssl')>SSL</option>
                                    <option value="null" @selected($mailEnc === null || $mailEnc === 'null' || $mailEnc === '')>{{ __('None') }}</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="mail_mailer" :value="__('Mailer')" />
                                <x-text-input id="mail_mailer" name="mail_mailer" type="text" class="mt-2 block w-full rounded-xl border-[color:var(--app-card-border)] bg-slate-50 shadow-sm" :value="old('mail_mailer', $mail['mail_mailer'])" readonly />
                            </div>
                            <div>
                                <x-input-label for="mail_from_address" :value="__('From email')" />
                                <x-text-input id="mail_from_address" name="mail_from_address" type="email" class="mt-2 block w-full rounded-xl border-[color:var(--app-card-border)] shadow-sm focus:border-[color:var(--app-primary)] focus:ring-[color:var(--app-primary)]/20" :value="old('mail_from_address', $mail['mail_from_address'])" required />
                            </div>
                            <div>
                                <x-input-label for="mail_from_name" :value="__('From name')" />
                                <x-text-input id="mail_from_name" name="mail_from_name" type="text" class="mt-2 block w-full rounded-xl border-[color:var(--app-card-border)] shadow-sm focus:border-[color:var(--app-primary)] focus:ring-[color:var(--app-primary)]/20" :value="old('mail_from_name', $mail['mail_from_name'])" required />
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[color:var(--app-primary)] px-5 py-2.5 text-sm font-semibold text-[color:var(--app-primary-text)] shadow-sm transition hover:bg-[color:var(--app-primary-hover)] focus:outline-none focus:ring-2 focus:ring-[color:var(--app-primary)]/40">
                                {{ __('Save email configuration') }}
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            @include('settings.partials.appearance', [
                'appSettings' => $appSettings,
                'theme' => $theme,
                'themeLabels' => $themeLabels,
            ])
        @endif

        @if (auth()->user()->allows('settings.integrations'))
            <section class="overflow-hidden rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] shadow-[0_1px_3px_rgba(0,0,0,0.05)] ring-1 ring-black/[0.03]">
                <div class="border-b border-[color:var(--app-card-border)]/80 bg-gradient-to-r from-teal-500/10 to-transparent px-6 py-5 sm:px-8">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-500/15 text-teal-700 ring-1 ring-teal-500/20">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-[color:var(--app-text)]">{{ __('Integrations') }}</h2>
                            <p class="mt-0.5 text-sm text-[color:var(--app-text-muted)]">{{ __('Webhook URLs for Meta Cloud API.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-6 sm:px-8 sm:py-8">
                    <dl class="space-y-5">
                        <div>
                            <dt class="text-sm font-medium text-[color:var(--app-text-muted)]">{{ __('WhatsApp') }}</dt>
                            <dd class="app-code-block mt-2 rounded-xl">{{ $whatsappWebhook }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-[color:var(--app-text-muted)]">{{ __('Messenger') }}</dt>
                            <dd class="app-code-block mt-2 rounded-xl">{{ $messengerWebhook }}</dd>
                        </div>
                    </dl>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('whatsapp.connect') }}" class="inline-flex items-center rounded-xl bg-[#25d366] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#20bd5a]">{{ __('WhatsApp setup') }}</a>
                        <a href="{{ route('messenger.connect') }}" class="inline-flex items-center rounded-xl bg-[#0084ff] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-600">{{ __('Messenger setup') }}</a>
                    </div>
                </div>
            </section>
        @endif

        <section class="overflow-hidden rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] shadow-[0_1px_3px_rgba(0,0,0,0.05)] ring-1 ring-black/[0.03]">
            <div class="border-b border-[color:var(--app-card-border)]/80 bg-gradient-to-r from-slate-500/10 to-transparent px-6 py-5 sm:px-8">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-500/15 text-slate-700 ring-1 ring-slate-500/15">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-[color:var(--app-text)]">{{ __('Application') }}</h2>
                        <p class="mt-0.5 text-sm text-[color:var(--app-text-muted)]">{{ __('Server configuration lives in .env and your host.') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6 sm:px-8 sm:py-8">
                <ul class="space-y-2 text-sm text-[color:var(--app-text-muted)]">
                    <li class="flex items-center gap-2 before:h-1.5 before:w-1.5 before:rounded-full before:bg-[color:var(--app-primary)]/60">APP_ENV · APP_DEBUG · APP_URL</li>
                    <li class="flex items-center gap-2 before:h-1.5 before:w-1.5 before:rounded-full before:bg-[color:var(--app-primary)]/60">DB_* · Queue · Cache drivers</li>
                </ul>
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            (function () {
                const root = document.getElementById('theme-preview-root');
                const map = @json($themeKeyCss);
                const btn = document.getElementById('request-desktop-notifications');

                function expandHex3(v) {
                    if (!/^#[0-9A-Fa-f]{3}$/.test(v)) return v;
                    return '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
                }

                function isHexColor(v) {
                    return /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(v.trim());
                }

                function applyPreview(key, value) {
                    if (!root || !map) return;
                    const prop = map[key];
                    if (!prop) return;
                    root.style.setProperty(prop, value);
                }

                function updateSwatchForRow(row, raw) {
                    const swatch = row.querySelector('.theme-swatch');
                    const picker = row.querySelector('.theme-color-picker');
                    const v = typeof raw === 'string' ? raw.trim() : '';
                    if (swatch) {
                        if (v === '') {
                            swatch.style.background =
                                'repeating-conic-gradient(#e5e7eb 0% 25%, #f3f4f6 0% 50%) 50% / 12px 12px';
                        } else {
                            swatch.style.background = v;
                        }
                        swatch.setAttribute('title', v);
                    }
                    if (picker && isHexColor(v)) {
                        picker.value = expandHex3(v);
                    }
                }

                document.querySelectorAll('.theme-color-row').forEach(function (row) {
                    const text = row.querySelector('.theme-input');
                    const picker = row.querySelector('.theme-color-picker');
                    if (!text) return;

                    text.addEventListener('input', function () {
                        const key = text.getAttribute('data-theme-key');
                        updateSwatchForRow(row, text.value);
                        applyPreview(key, text.value);
                    });

                    if (picker) {
                        picker.addEventListener('input', function () {
                            text.value = picker.value;
                            const key = text.getAttribute('data-theme-key');
                            updateSwatchForRow(row, text.value);
                            applyPreview(key, text.value);
                        });
                    }
                });

                if (btn && 'Notification' in window) {
                    btn.addEventListener('click', function () {
                        Notification.requestPermission().catch(function () {});
                    });
                }
            })();
        </script>
    @endpush
</x-app-layout>

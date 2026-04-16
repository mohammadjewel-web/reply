<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Overview') }}</h2>
    </x-slot>

    <div class="app-page space-y-8">
        {{-- Hero --}}
        <section
            class="relative overflow-hidden rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] p-6 shadow-sm ring-1 ring-black/[0.03] sm:p-8"
        >
            <div
                class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full opacity-[0.12]"
                style="background: radial-gradient(circle at center, var(--app-primary), transparent 70%)"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-20 -left-10 h-48 w-48 rounded-full opacity-[0.08]"
                style="background: radial-gradient(circle at center, var(--app-accent), transparent 70%)"
            ></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-[color:var(--app-text-muted)]">{{ $greeting }}</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-[color:var(--app-text)] sm:text-3xl">
                        {{ auth()->user()->name }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-[color:var(--app-text-muted)]">
                        @if (auth()->user()->canSeeWorkbench())
                            {{ __('Here is a snapshot of your workspace. Jump into chats, manage connections, or adjust settings from the shortcuts below.') }}
                        @else
                            {{ __('Your account is active. Open Settings or Profile anytime. Ask an administrator if you need messaging or team tools.') }}
                        @endif
                    </p>
                    <p class="mt-3 text-xs text-[color:var(--app-text-muted)]">
                        {{ now()->translatedFormat('l, F j') }}
                    </p>
                </div>
                @if ($unreadNotifications > 0)
                    <div class="shrink-0">
                        <span
                            class="inline-flex items-center gap-2 rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)] px-4 py-2.5 text-sm font-medium text-[color:var(--app-text)] shadow-sm"
                        >
                            <span
                                class="inline-flex h-2 w-2 animate-pulse rounded-full"
                                style="background: var(--app-primary)"
                            ></span>
                            {{ trans_choice(':count unread notification|:count unread notifications', $unreadNotifications, ['count' => $unreadNotifications]) }}
                        </span>
                    </div>
                @endif
            </div>
        </section>

        {{-- Quick actions --}}
        <section>
            <h3 class="text-sm font-semibold uppercase tracking-wider text-[color:var(--app-text-muted)]">
                {{ __('Shortcuts') }}
            </h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @if (auth()->user()->allows('inbox.access'))
                    <a
                        href="{{ route('inbox') }}"
                        class="group flex items-center gap-4 rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] p-4 shadow-sm transition hover:border-emerald-500/35 hover:shadow-md"
                    >
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-700 ring-1 ring-emerald-500/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-semibold text-[color:var(--app-text)] group-hover:text-emerald-800">{{ __('Chats') }}</span>
                            <span class="block text-xs text-[color:var(--app-text-muted)]">{{ __('Open the unified inbox') }}</span>
                        </span>
                    </a>
                @endif

                @if (auth()->user()->allows('connections.manage'))
                    <a
                        href="{{ route('connections.index') }}"
                        class="group flex items-center gap-4 rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] p-4 shadow-sm transition hover:border-teal-500/35 hover:shadow-md"
                    >
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-500/15 text-teal-700 ring-1 ring-teal-500/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-semibold text-[color:var(--app-text)] group-hover:text-teal-800">{{ __('Connections') }}</span>
                            <span class="block text-xs text-[color:var(--app-text-muted)]">{{ __('WhatsApp & Messenger accounts') }}</span>
                        </span>
                    </a>
                @endif

                <a
                    href="{{ route('settings.index') }}"
                    class="group flex items-center gap-4 rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] p-4 shadow-sm transition hover:border-[color:var(--app-primary)]/40 hover:shadow-md"
                >
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ring-1"
                        style="background: color-mix(in srgb, var(--app-primary) 12%, transparent); color: var(--app-primary); border-color: color-mix(in srgb, var(--app-primary) 25%, transparent)"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block font-semibold text-[color:var(--app-text)]">{{ __('Settings') }}</span>
                        <span class="block text-xs text-[color:var(--app-text-muted)]">{{ __('Notifications & appearance') }}</span>
                    </span>
                </a>

                <a
                    href="{{ route('profile.edit') }}"
                    class="group flex items-center gap-4 rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] p-4 shadow-sm transition hover:border-violet-400/40 hover:shadow-md"
                >
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-500/12 text-violet-700 ring-1 ring-violet-500/25">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block font-semibold text-[color:var(--app-text)] group-hover:text-violet-900">{{ __('Profile') }}</span>
                        <span class="block text-xs text-[color:var(--app-text-muted)]">{{ __('Account & security') }}</span>
                    </span>
                </a>
            </div>

            @if (auth()->user()->allows('whatsapp.manage') || auth()->user()->allows('messenger.manage'))
                <div class="mt-3 flex flex-wrap gap-2">
                    @if (auth()->user()->allows('whatsapp.manage'))
                        <a
                            href="{{ route('whatsapp.connect') }}"
                            class="inline-flex items-center gap-1.5 rounded-full bg-[#25d366]/12 px-3 py-1.5 text-xs font-semibold text-[#128c3a] ring-1 ring-[#25d366]/30 transition hover:bg-[#25d366]/20"
                        >
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            {{ __('WhatsApp setup') }}
                        </a>
                    @endif
                    @if (auth()->user()->allows('messenger.manage'))
                        <a
                            href="{{ route('messenger.connect') }}"
                            class="inline-flex items-center gap-1.5 rounded-full bg-[#0084ff]/12 px-3 py-1.5 text-xs font-semibold text-[#0064c8] ring-1 ring-[#0084ff]/30 transition hover:bg-[#0084ff]/18"
                        >
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            {{ __('Messenger setup') }}
                        </a>
                    @endif
                </div>
            @endif
        </section>

        @if ($stats)
            <div class="grid gap-6 lg:grid-cols-3">
                {{-- Metric cards --}}
                <div class="lg:col-span-2 space-y-6">
                    <section>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-[color:var(--app-text-muted)]">
                            {{ __('Activity') }}
                        </h3>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <div class="app-card !p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">{{ __('Conversations') }}</p>
                                <p class="mt-2 text-3xl font-semibold tabular-nums text-[color:var(--app-text)]">{{ number_format($stats['conversations']) }}</p>
                            </div>
                            <div class="app-card !p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">{{ __('Messages stored') }}</p>
                                <p class="mt-2 text-3xl font-semibold tabular-nums text-[color:var(--app-text)]">{{ number_format($stats['messages']) }}</p>
                            </div>
                            <div class="app-card !p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">{{ __('Today') }}</p>
                                <p class="mt-2 text-3xl font-semibold tabular-nums text-[color:var(--app-text)]">{{ number_format($stats['messages_today']) }}</p>
                                <p class="mt-1 text-[11px] text-[color:var(--app-text-muted)]">{{ __('Inbound & outbound') }}</p>
                            </div>
                            <div class="app-card !p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">{{ __('WhatsApp threads') }}</p>
                                <p class="mt-2 text-3xl font-semibold tabular-nums text-[color:var(--app-text)]">{{ number_format($stats['whatsapp_conversations']) }}</p>
                            </div>
                            <div class="app-card !p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">{{ __('Messenger threads') }}</p>
                                <p class="mt-2 text-3xl font-semibold tabular-nums text-[color:var(--app-text)]">{{ number_format($stats['messenger_conversations']) }}</p>
                            </div>
                            <div class="app-card !p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">{{ __('Assigned to you') }}</p>
                                <p class="mt-2 text-3xl font-semibold tabular-nums text-[color:var(--app-text)]">{{ number_format($stats['assigned_to_me']) }}</p>
                                <p class="mt-1 text-[11px] text-[color:var(--app-text-muted)]">
                                    {{ __(':count unassigned', ['count' => number_format($stats['unassigned'])]) }}
                                </p>
                            </div>
                        </div>
                    </section>

                    {{-- Recent conversations --}}
                    <section class="app-card !overflow-hidden !p-0">
                        <div class="flex items-center justify-between border-b border-[color:var(--app-card-border)] px-5 py-4">
                            <div>
                                <h3 class="app-card__title !mt-0">{{ __('Recent conversations') }}</h3>
                                <p class="app-card__lead !mt-0.5">{{ __('Latest activity across your channels') }}</p>
                            </div>
                            <a href="{{ route('inbox') }}" class="app-btn-primary shrink-0 !py-2 !text-xs">{{ __('Open inbox') }}</a>
                        </div>
                        @if ($recentConversations->isEmpty())
                            <div class="px-5 py-12 text-center">
                                <p class="text-sm text-[color:var(--app-text-muted)]">{{ __('No conversations yet. Connect an account and wait for the first message.') }}</p>
                            </div>
                        @else
                            <ul class="divide-y divide-[color:var(--app-card-border)]">
                                @foreach ($recentConversations as $conv)
                                    @php
                                        $isWa = $conv->platform === \App\Models\Conversation::PLATFORM_WHATSAPP;
                                        $href = route('inbox', array_filter(['conversation' => $conv->id, 'account' => $conv->channel_account_id]));
                                    @endphp
                                    <li>
                                        <a
                                            href="{{ $href }}"
                                            class="flex items-center gap-4 px-5 py-3.5 transition hover:bg-[color:var(--app-shell-bg)]"
                                        >
                                            <span
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-bold {{ $isWa ? 'bg-[#25d366]/15 text-[#128c3a] ring-1 ring-[#25d366]/30' : 'bg-[#0084ff]/12 text-[#0064c8] ring-1 ring-[#0084ff]/28' }}"
                                            >
                                                {{ $isWa ? 'WA' : 'MS' }}
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate font-medium text-[color:var(--app-text)]">{{ $conv->display_name ?: __('Unknown') }}</span>
                                                <span class="block truncate text-xs text-[color:var(--app-text-muted)]">
                                                    {{ $conv->channelAccount?->name ?? __('Connection') }}
                                                    @if ($conv->last_message_at)
                                                        · {{ $conv->last_message_at->diffForHumans() }}
                                                    @endif
                                                </span>
                                            </span>
                                            <svg class="h-5 w-5 shrink-0 text-[color:var(--app-text-muted)] opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>

                {{-- Side column --}}
                <div class="space-y-6">
                    @if ($channelSummary)
                        <section class="app-card">
                            <h3 class="app-card__title">{{ __('Connected lines') }}</h3>
                            <p class="app-card__lead">{{ __('Active channel accounts') }}</p>
                            <dl class="mt-4 space-y-3">
                                <div class="flex items-center justify-between gap-2 rounded-xl bg-[color:var(--app-shell-bg)] px-3 py-2.5">
                                    <dt class="text-sm font-medium text-[color:var(--app-text)]">{{ __('WhatsApp') }}</dt>
                                    <dd class="text-lg font-semibold tabular-nums text-[color:var(--app-text)]">{{ number_format($channelSummary['whatsapp_active']) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-2 rounded-xl bg-[color:var(--app-shell-bg)] px-3 py-2.5">
                                    <dt class="text-sm font-medium text-[color:var(--app-text)]">{{ __('Messenger') }}</dt>
                                    <dd class="text-lg font-semibold tabular-nums text-[color:var(--app-text)]">{{ number_format($channelSummary['messenger_active']) }}</dd>
                                </div>
                            </dl>
                            <a href="{{ route('connections.index') }}" class="mt-4 inline-flex w-full items-center justify-center rounded-lg border border-[color:var(--app-card-border)] py-2 text-sm font-semibold text-[color:var(--app-text)] transition hover:bg-[color:var(--app-shell-bg)]">
                                {{ __('Manage connections') }}
                            </a>
                        </section>
                    @endif

                    <section class="app-card relative overflow-hidden">
                        <div
                            class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full opacity-[0.07]"
                            style="background: radial-gradient(circle at center, var(--app-primary), transparent 70%)"
                        ></div>
                        <h3 class="app-card__title">{{ __('Tips') }}</h3>
                        <ul class="mt-3 space-y-2.5 text-sm leading-relaxed text-[color:var(--app-text-muted)]">
                            <li class="flex gap-2">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full" style="background: var(--app-primary)"></span>
                                {{ __('Use filters in the inbox to see only your assignments or unassigned threads.') }}
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full" style="background: var(--app-primary)"></span>
                                {{ __('Enable browser notifications in Settings so you do not miss new messages.') }}
                            </li>
                            @if (auth()->user()->canSeeWorkbench())
                                <li class="flex gap-2">
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full" style="background: var(--app-primary)"></span>
                                    {{ __('On phones, open the menu (top left) to reach every tool.') }}
                                </li>
                            @endif
                        </ul>
                    </section>
                </div>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-2">
                <section class="app-card">
                    <h3 class="app-card__title">{{ __('Get started') }}</h3>
                    <p class="app-card__lead">{{ __('You do not have inbox access yet. You can still manage your profile and preferences.') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('settings.index') }}" class="app-btn-primary">{{ __('Settings') }}</a>
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center rounded-lg border border-[color:var(--app-card-border)] px-4 py-2.5 text-sm font-semibold text-[color:var(--app-text)] hover:bg-[color:var(--app-shell-bg)]">{{ __('Profile') }}</a>
                    </div>
                </section>
                <section class="app-card border-dashed">
                    <h3 class="app-card__title">{{ __('Need more access?') }}</h3>
                    <p class="app-card__lead">{{ __('Ask an administrator to assign roles with inbox, connections, or team permissions.') }}</p>
                </section>
        </div>
        @endif
    </div>
</x-app-layout>

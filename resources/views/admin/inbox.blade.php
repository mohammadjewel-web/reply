@php
    $filterQs = static function (array $merge = []) {
        $base = array_filter(
            array_merge(request()->only(['assignee', 'account']), $merge),
            fn ($v) => $v !== null && $v !== ''
        );

        return $base === [] ? '' : '?'.http_build_query($base);
    };
    $waHeader = 'bg-[#075e54]';
    $fbHeader = 'bg-[#0084ff]';
    $headerClass = $active && $active->platform === 'whatsapp' ? $waHeader : ($active && $active->platform === 'messenger' ? $fbHeader : 'bg-slate-700');
    $showThreadMobile = (bool) $active;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Chats') }}</h2>
    </x-slot>

    <div class="app-page--flush">
        <div
            class="mx-auto flex w-full max-w-7xl min-h-0 flex-1 flex-col px-0 sm:px-2"
            data-inbox-page
            x-data="inboxPage({
                mobileListOpen: @json(! $showThreadMobile),
                lastMessageId: @json((int) ($active ? ($messages->max('id') ?? 0) : 0)),
                conversationId: @json($active?->id),
                pollUrl: @json(route('inbox.poll', [], false)),
                listAssignee: @json(request('assignee', 'all')),
                listAccount: @json(request('account')),
            })"
            x-init="inboxStart()"
        >
            <div
                class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-none border-0 bg-white shadow-md sm:rounded-2xl sm:border sm:border-slate-200/90 md:flex-row md:min-h-[28rem]"
                style="min-height: min(520px, calc(100dvh - 11rem)); max-height: min(900px, calc(100dvh - 7rem));"
            >
                {{-- Conversation list --}}
                {{-- Mobile drawer backdrop --}}
                <div
                    class="fixed inset-0 z-20 bg-slate-900/40 backdrop-blur-[1px] md:hidden"
                    x-show="mobileListOpen"
                    x-transition.opacity
                    x-on:click="mobileListOpen = false"
                    x-cloak
                    aria-hidden="true"
                ></div>

                {{-- Mobile drawer list --}}
                <aside
                    class="fixed inset-x-0 top-0 bottom-0 z-30 flex w-full shrink-0 flex-col border-b border-slate-200 bg-white shadow-2xl md:hidden"
                    x-show="mobileListOpen"
                    x-transition
                    x-cloak
                >
                    <div class="border-b border-slate-100 bg-slate-50/95 px-3 py-3 sm:px-4">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Inbox') }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ __('All WhatsApp & Messenger lines') }}</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 md:hidden"
                                x-on:click="mobileListOpen = false"
                                title="{{ __('Close') }}"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            @if (auth()->user()->allows('connections.manage'))
                                <a href="{{ route('connections.index') }}" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-50" title="{{ __('Manage connections') }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="hidden sm:inline">{{ __('Connections') }}</span>
                                </a>
                            @endif
                        </div>

                        {{-- Filters --}}
                        <div class="mt-3 flex flex-col gap-2">
                            <div class="flex flex-wrap gap-1.5">
                                <a href="{{ route('inbox', array_filter(['account' => request('account')])) }}" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ ($assigneeFilter ?? 'all') === 'all' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                                    <svg class="h-3.5 w-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                    {{ __('All') }}
                                </a>
                                <a href="{{ route('inbox', array_filter(['assignee' => 'me', 'account' => request('account')])) }}" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ ($assigneeFilter ?? '') === 'me' ? 'bg-emerald-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                                    <svg class="h-3.5 w-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    {{ __('Mine') }}
                                </a>
                                <a href="{{ route('inbox', array_filter(['assignee' => 'unassigned', 'account' => request('account')])) }}" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ ($assigneeFilter ?? '') === 'unassigned' ? 'bg-amber-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                                    <svg class="h-3.5 w-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    {{ __('Unassigned') }}
                                </a>
                            </div>
                            <label class="block">
                                <span class="sr-only">{{ __('Filter by account') }}</span>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-2 text-slate-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </span>
                                    <select
                                        class="block w-full rounded-lg border-slate-200 py-2 ps-8 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        onchange="if (this.value) window.location.href = this.value"
                                    >
                                        @php
                                            $accBase = array_filter(['assignee' => request('assignee')], fn ($v) => $v !== null && $v !== '');
                                        @endphp
                                        <option value="{{ route('inbox', $accBase) }}">{{ __('All connections') }}</option>
                                        @foreach ($channelAccounts as $ca)
                                            <option
                                                value="{{ route('inbox', array_merge($accBase, ['account' => $ca->id])) }}"
                                                @selected((string) $selectedAccountId === (string) $ca->id)
                                            >
                                                {{ $ca->name }} ({{ $ca->type === 'whatsapp' ? 'WA' : 'MS' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="js-inbox-conversation-list min-h-0 flex-1 overflow-y-auto">
                        @include('admin.inbox.partials.inbox-conversation-rows', [
                            'conversations' => $conversations,
                            'selectedConversationId' => $active?->id,
                            'filterRequest' => request(),
                        ])
                    </div>
                </aside>

                {{-- Desktop list --}}
                <aside class="hidden w-full shrink-0 flex-col border-b border-slate-200 bg-white md:flex md:max-h-none md:w-[min(100%,320px)] md:border-b-0 md:border-e">
                    <div class="border-b border-slate-100 bg-slate-50/95 px-3 py-3 sm:px-4">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Inbox') }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ __('All WhatsApp & Messenger lines') }}</p>
                            </div>
                            @if (auth()->user()->allows('connections.manage'))
                                <a href="{{ route('connections.index') }}" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-50" title="{{ __('Manage connections') }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="hidden sm:inline">{{ __('Connections') }}</span>
                                </a>
                            @endif
                        </div>

                        {{-- Filters --}}
                        <div class="mt-3 flex flex-col gap-2">
                            <div class="flex flex-wrap gap-1.5">
                                <a href="{{ route('inbox', array_filter(['account' => request('account')])) }}" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ ($assigneeFilter ?? 'all') === 'all' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                                    <svg class="h-3.5 w-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                    {{ __('All') }}
                                </a>
                                <a href="{{ route('inbox', array_filter(['assignee' => 'me', 'account' => request('account')])) }}" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ ($assigneeFilter ?? '') === 'me' ? 'bg-emerald-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                                    <svg class="h-3.5 w-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    {{ __('Mine') }}
                                </a>
                                <a href="{{ route('inbox', array_filter(['assignee' => 'unassigned', 'account' => request('account')])) }}" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ ($assigneeFilter ?? '') === 'unassigned' ? 'bg-amber-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                                    <svg class="h-3.5 w-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    {{ __('Unassigned') }}
                                </a>
                            </div>
                            <label class="block">
                                <span class="sr-only">{{ __('Filter by account') }}</span>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-2 text-slate-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </span>
                                    <select
                                        class="block w-full rounded-lg border-slate-200 py-2 ps-8 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        onchange="if (this.value) window.location.href = this.value"
                                    >
                                        @php
                                            $accBase = array_filter(['assignee' => request('assignee')], fn ($v) => $v !== null && $v !== '');
                                        @endphp
                                        <option value="{{ route('inbox', $accBase) }}">{{ __('All connections') }}</option>
                                        @foreach ($channelAccounts as $ca)
                                            <option
                                                value="{{ route('inbox', array_merge($accBase, ['account' => $ca->id])) }}"
                                                @selected((string) $selectedAccountId === (string) $ca->id)
                                            >
                                                {{ $ca->name }} ({{ $ca->type === 'whatsapp' ? 'WA' : 'MS' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="js-inbox-conversation-list min-h-0 flex-1 overflow-y-auto">
                        @include('admin.inbox.partials.inbox-conversation-rows', [
                            'conversations' => $conversations,
                            'selectedConversationId' => $active?->id,
                            'filterRequest' => request(),
                        ])
                    </div>
                </aside>

                {{-- Chat pane --}}
                <section class="min-h-0 min-w-0 flex flex-1 flex-col bg-[#ece5dd] {{ $showThreadMobile ? 'flex' : 'hidden md:flex' }} relative">
                    @if ($active)
                        @php
                            $assignQs = $filterQs();
                            $assignAction = route('inbox.assign', $active, false).($assignQs !== '' ? $assignQs : '');
                            $replyQs = $filterQs();
                            $replyAction = route('inbox.reply', $active, false).($replyQs !== '' ? $replyQs : '');
                            $canReply = $active->channelAccount && $active->channelAccount->is_active;
                        @endphp
                        <div
                            class="z-10 flex shrink-0 flex-wrap items-center gap-2 px-3 py-2.5 {{ $headerClass }} text-white shadow-md sm:px-4"
                            role="banner"
                        >
                            <button
                                type="button"
                                class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-full bg-white/15 px-3 text-xs font-semibold text-white hover:bg-white/25 md:hidden"
                                x-on:click="mobileListOpen = true"
                                title="{{ __('Chats') }}"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h10"/></svg>
                                {{ __('Chats') }}
                            </button>
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border border-white/30 bg-white/20 text-sm font-bold">
                                <span x-ref="inboxHdrAvatarLetter" class="leading-none">{{ $active->inboxContactAvatarLetter() }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 x-ref="inboxHdrTitle" class="truncate text-[15px] font-semibold leading-tight">{{ $active->inboxContactTitle() }}</h3>
                                <p x-ref="inboxHdrSub" class="truncate text-xs text-white/85">{{ $active->inboxHeaderSubtitlePlain() }}</p>
                            </div>
                            <form method="post" action="{{ $assignAction }}" class="flex w-full min-w-0 flex-wrap items-center gap-2 sm:w-auto sm:flex-nowrap md:max-w-[min(100%,22rem)]">
                                @csrf
                                @method('PATCH')
                                <label class="sr-only" for="assignee-select">{{ __('Assign to') }}</label>
                                <div class="relative min-w-0 flex-1 sm:flex-initial sm:min-w-[10rem]">
                                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-2 text-white/70">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    </span>
                                    <select id="assignee-select" name="assigned_to_user_id" class="block w-full rounded-lg border-0 bg-white/15 py-2 ps-8 text-sm text-white shadow-sm ring-1 ring-white/30 focus:ring-2 focus:ring-white/50 [&>option]:text-slate-900">
                                        <option value="">{{ __('Unassigned') }}</option>
                                        @foreach ($assignableUsers as $u)
                                            <option value="{{ $u->id }}" @selected($active->assigned_to_user_id === $u->id)>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-white/20 px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/40 hover:bg-white/30">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ __('Assign') }}
                                </button>
                            </form>
                        </div>

                        <div
                            class="relative flex min-h-0 flex-1 flex-col"
                            x-init="$nextTick(() => scrollToEnd())"
                        >
                            <div
                                class="pointer-events-none absolute inset-0 opacity-[0.35] [background-image:radial-gradient(circle_at_1px_1px,rgb(148_163_184/0.45)_1px,transparent_0)] [background-size:20px_20px]"
                                aria-hidden="true"
                            ></div>

                            <div
                                x-ref="thread"
                                data-inbox-thread
                                class="relative min-h-0 flex-1 space-y-2 overflow-y-auto overflow-x-hidden scroll-smooth px-3 py-4 sm:px-5"
                            >
                                @if ($active->assigned_to_user_id === null)
                                    <div class="mx-auto mb-2 max-w-lg rounded-xl border border-amber-200/80 bg-amber-50/95 px-3 py-2 text-center text-[12px] text-amber-950 shadow-sm">
                                        <span class="inline-flex items-center justify-center gap-1 font-medium">
                                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ __('Unassigned — the first teammate to reply will be assigned automatically.') }}
                                        </span>
                                    </div>
                                @endif

                                @if ($messages->isEmpty())
                                    <div data-inbox-empty class="flex min-h-[120px] items-center justify-center px-4 text-center">
                                        <p class="max-w-sm rounded-2xl border border-slate-100 bg-white/90 px-4 py-3 text-sm text-slate-600 shadow-sm">
                                            {{ __('No messages in this chat yet.') }}
                                        </p>
                                    </div>
                                @endif

                                @foreach ($messages as $m)
                                    @include('admin.inbox.partials.message-bubble', ['m' => $m, 'active' => $active])
                                @endforeach
                            </div>

                            <div class="z-10 shrink-0 border-t border-slate-200/80 bg-[#f0f0f0] px-3 py-3 shadow-[0_-4px_12px_rgba(15,23,42,0.06)] sm:px-4">
                                @if ($canReply)
                                    <form method="post" action="{{ $replyAction }}" class="flex items-end gap-2" @submit="sendReply($event)">
                                        @csrf
                                        <label for="chat-body" class="sr-only">{{ __('Message') }}</label>
                                        <div class="min-w-0 flex-1 rounded-3xl border border-slate-200 bg-white shadow-inner transition-shadow focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-500/30">
                                            <textarea
                                                id="chat-body"
                                                name="body"
                                                rows="1"
                                                required
                                                class="block max-h-32 w-full resize-none rounded-3xl border-0 bg-transparent px-4 py-3 text-[15px] text-slate-900 placeholder:text-slate-400 focus:ring-0"
                                                placeholder="{{ $active->platform === 'whatsapp' ? __('Message') : __('Aa') }}…"
                                                @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 128) + 'px'"
                                            >{{ old('body') }}</textarea>
                                        </div>
                                        <button
                                            type="submit"
                                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-white shadow-md transition-transform active:scale-95 disabled:opacity-50
                                                {{ $active->platform === 'whatsapp' ? 'bg-[#25d366] hover:bg-[#20bd5a]' : 'bg-[#0084ff] hover:bg-[#0073e6]' }}"
                                            title="{{ __('Send') }}"
                                        >
                                            <svg class="-ms-0.5 h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                            </svg>
                                            <span class="sr-only">{{ __('Send') }}</span>
                                        </button>
                                    </form>
                                @else
                                    <div class="rounded-2xl border border-slate-200/90 bg-white/95 px-4 py-3 text-center text-sm text-slate-600 shadow-sm">
                                        {{ __('This chat is not linked to an active connection. You can read the history; add a connection again to send messages.') }}
                                    </div>
                                @endif
                                <p x-show="replyError" x-cloak class="mt-2 text-center text-sm text-red-600" x-text="replyError"></p>
                                <x-input-error :messages="$errors->get('body')" class="mt-2 text-center" />
                                <x-input-error :messages="$errors->get('assigned_to_user_id')" class="mt-2 text-center" />
                            </div>
                        </div>
                    @else
                        <div class="flex flex-1 flex-col items-center justify-center bg-slate-50 p-8 text-center">
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-200">
                                <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </div>
                            <p class="font-medium text-slate-600">{{ __('Select a chat') }}</p>
                            <p class="mt-2 max-w-xs text-sm text-slate-500">{{ __('Pick a thread from the list. On your phone, use the back control above when a chat is open.') }}</p>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-app-layout>

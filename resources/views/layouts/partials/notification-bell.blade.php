<div
    class="js-notification-root relative shrink-0"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    data-notifications-url="{{ route('notifications.index') }}"
    data-read-all-url="{{ route('notifications.read_all') }}"
    data-read-template="{{ route('notifications.read', ['id' => '__ID__']) }}"
    data-empty-text="{{ __('No unread notifications.') }}"
    data-csrf="{{ csrf_token() }}"
>
    <button
        type="button"
        class="js-notification-toggle relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-[color:var(--app-header-border)] bg-[color:var(--app-header-bg)] text-[color:var(--app-header-text)] shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[color:var(--app-primary)]/25"
        @click="open = !open"
        aria-expanded="false"
        :aria-expanded="open"
    >
        <span class="sr-only">{{ __('Notifications') }}</span>
        <svg class="h-5 w-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span
            class="js-notification-badge absolute -right-0.5 -top-0.5 hidden min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white"
        >0</span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity
        @click.outside="open = false"
        class="absolute right-0 z-50 mt-2 w-[min(100vw-2rem,22rem)] origin-top-right rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] py-2 shadow-xl"
        role="menu"
    >
        <div class="flex items-center justify-between border-b border-[color:var(--app-card-border)] px-3 pb-2">
            <span class="text-sm font-semibold text-[color:var(--app-text)]">{{ __('Notifications') }}</span>
            <button type="button" class="js-notification-mark-all text-xs font-medium text-[color:var(--app-primary)] hover:underline">
                {{ __('Mark all read') }}
            </button>
        </div>
        <ul class="js-notification-list max-h-[min(70vh,24rem)] list-none overflow-y-auto py-1 text-sm">
            <li class="px-3 py-6 text-center text-[color:var(--app-text-muted)]">{{ __('Loading…') }}</li>
        </ul>
    </div>
</div>

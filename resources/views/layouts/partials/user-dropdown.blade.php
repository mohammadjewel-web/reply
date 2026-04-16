<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        class="flex items-center gap-3 rounded-2xl bg-white/5 px-2.5 py-2 ring-1 ring-white/10 transition hover:bg-white/10"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="true"
    >
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-[color:var(--app-primary-text)] ring-2 ring-white/15" style="background: linear-gradient(145deg, var(--app-primary), #ff4d6d)">
            {{ strtoupper(mb_substr((string) auth()->user()->name, 0, 1)) }}
        </div>
        <div class="min-w-0 max-w-[12rem] text-left">
            <p class="truncate text-sm font-medium text-[color:var(--app-header-text)]">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-[color:var(--app-text-muted)]">{{ auth()->user()->email }}</p>
        </div>
        <svg class="h-4 w-4 shrink-0 text-[color:var(--app-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-transition.origin.top.right
        x-cloak
        @click.outside="open = false"
        class="absolute right-0 z-50 mt-2 w-48 overflow-hidden rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] py-1 shadow-lg"
    >
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-[color:var(--app-text)] transition hover:bg-[color:var(--app-shell-bg)]" @click="open = false">
            {{ __('Profile') }}
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-700 transition hover:bg-red-50">
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</div>

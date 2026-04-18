<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $appBrandName ?? config('app.name', 'Laravel') }}</title>

        @if (! empty($appBrandFaviconUrl))
            <link rel="icon" href="{{ $appBrandFaviconUrl }}" sizes="any">
            <link rel="apple-touch-icon" href="{{ $appBrandFaviconUrl }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <style id="app-theme-vars">
            {!! $appThemeStyle ?? '' !!}
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-[color:var(--app-text)]">
        <div class="app-layout-shell min-h-screen min-h-[100dvh] flex">
            {{-- Sidebar open state lives in Alpine.store so main content (e.g. inboxPage) is not nested under layout x-data (avoids ReferenceError on replyError, emojiOpen, etc.). --}}
            <div
                x-data="{}"
                x-show="$store.layout.sidebarOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-[2px] lg:hidden"
                @click="$store.layout.sidebarOpen = false"
            ></div>

            @include('layouts.sidebar')

            <div class="app-layout-main flex flex-1 flex-col min-w-0 min-h-0">
                <div class="sticky top-0 z-30" x-data="{}" @keydown.window.escape="$store.layout.sidebarOpen = false">
                <header class="flex h-14 shrink-0 items-center gap-2 border-b border-[color:var(--app-header-border)] bg-[color:var(--app-header-bg)] px-3 lg:hidden">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-[color:var(--app-text-muted)] transition hover:bg-slate-100 hover:text-[color:var(--app-text)] focus:outline-none focus:ring-2 focus:ring-[color:var(--app-primary)]/30"
                        @click="$store.layout.sidebarOpen = true"
                        aria-controls="app-sidebar"
                        :aria-expanded="$store.layout.sidebarOpen"
                    >
                        <span class="sr-only">{{ __('Open menu') }}</span>
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <span class="truncate font-semibold text-[color:var(--app-header-text)] flex-1 min-w-0">{{ $appBrandName ?? config('app.name') }}</span>
                    @auth
                        @include('layouts.partials.notification-bell')
                        <div class="hidden sm:block">
                            @include('layouts.partials.user-dropdown')
                        </div>
                    @endauth
                </header>

                @isset($header)
                    <div class="app-header-slot hidden shrink-0 border-b border-[color:var(--app-header-border)] bg-[color:var(--app-header-bg)] lg:block">
                        <div class="mx-auto max-w-7xl px-4 py-2 sm:px-6 lg:px-8 flex flex-wrap items-start justify-between gap-2.5">
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                <div class="min-w-0 flex-1">{{ $header }}</div>
                            </div>
                            @auth
                                <div class="flex items-center gap-2">
                                    @include('layouts.partials.notification-bell')
                                    @include('layouts.partials.user-dropdown')
                                </div>
                            @endauth
                        </div>
                    </div>
                    <div class="app-header-slot shrink-0 border-b border-[color:var(--app-header-border)] bg-[color:var(--app-header-bg)] px-3 py-3 lg:hidden">
                        <div class="min-w-0">{{ $header }}</div>
                    </div>
                @endisset

                @auth
                    @if (! isset($header))
                        <div class="hidden shrink-0 border-b border-[color:var(--app-header-border)] bg-[color:var(--app-header-bg)] px-4 py-2 lg:flex lg:justify-end">
                            <div class="flex items-center gap-2">
                                @include('layouts.partials.notification-bell')
                                @include('layouts.partials.user-dropdown')
                            </div>
                        </div>
                    @endif
                @endauth
                </div>

                <main class="flex min-h-0 flex-1 flex-col">
                    {{ $slot }}
                </main>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>

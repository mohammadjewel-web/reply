<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        @if (! empty($appBrandFaviconUrl ?? null))
            <link rel="icon" href="{{ $appBrandFaviconUrl }}" />
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-[color:var(--app-text)]">
        @if (! empty($appThemeStyle ?? null))
            <style>{!! $appThemeStyle !!}</style>
        @endif
        <div class="flex min-h-screen min-h-[100dvh] flex-col items-center justify-center bg-[color:var(--app-shell-bg)] px-4 py-8 sm:px-6">
            <div class="mb-5">
                <a href="{{ route('login') }}" class="inline-flex flex-col items-center gap-2 text-center">
                    @if (! empty($appBrandLogoUrl ?? null))
                        <img src="{{ $appBrandLogoUrl }}" alt="{{ $appBrandName ?? config('app.name') }}" class="h-12 w-12 rounded-lg border border-[color:var(--app-card-border)] bg-white object-contain p-1.5" />
                    @else
                        <x-application-logo class="h-12 w-12 fill-current text-[color:var(--app-primary)]" />
                    @endif
                    <span class="text-sm font-semibold tracking-wide text-[color:var(--app-text-muted)]">{{ $appBrandName ?? config('app.name') }}</span>
                </a>
            </div>

            <div class="w-full overflow-hidden rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-6 py-6 shadow-sm sm:max-w-md sm:px-8 sm:py-7">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>

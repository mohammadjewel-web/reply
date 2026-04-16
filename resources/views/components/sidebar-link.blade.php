@props([
    'active' => false,
    'href' => '#',
    'disabled' => false,
])

@php
    if ($disabled) {
        $classes = 'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500/70 cursor-not-allowed select-none';
    } elseif ($active) {
        $classes = 'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-white shadow-inner ring-1 ring-[color:var(--app-primary)]/45 bg-[color-mix(in_srgb,var(--app-primary)_24%,transparent)]';
    } else {
        $classes = 'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300/95 hover:bg-white/10 hover:text-white transition-colors duration-150';
    }
@endphp

@if ($disabled)
    <span {{ $attributes->merge(['class' => $classes]) }} title="{{ __('Administrator access required') }}">
        {{ $slot }}
    </span>
@else
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@endif

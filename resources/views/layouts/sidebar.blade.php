@php
    $u = auth()->user();
    $canWhatsapp = $u->allows('whatsapp.manage');
    $canMessenger = $u->allows('messenger.manage');
    $canConnections = $u->allows('connections.manage');
    $canInbox = $u->allows('inbox.access');
    $canEmployees = $u->allows('employees.manage');
    $canRoles = $u->allows('roles.manage');
    $canPermissions = $u->allows('permissions.manage');
    $canSeeWorkbench = $u->canSeeWorkbench();
@endphp

<aside
    id="app-sidebar"
    x-data="{}"
    class="app-sidebar-shell fixed z-50 inset-y-0 left-0 w-[17.5rem] shrink-0 flex flex-col shadow-2xl transform transition-transform duration-200 ease-out -translate-x-full lg:sticky lg:top-0 lg:z-auto lg:h-[100dvh] lg:max-h-[100dvh] lg:translate-x-0 lg:self-start lg:inset-auto lg:shadow-none"
    :class="{ '!translate-x-0': $store.layout.sidebarOpen }"
    aria-label="{{ __('Main navigation') }}"
>
    {{-- Brand --}}
    <div class="h-[4.25rem] flex items-center gap-3 border-b border-white/10 px-4 backdrop-blur-md shrink-0" style="background: color-mix(in srgb, var(--app-sidebar-via) 35%, transparent)">
        <a href="{{ route('dashboard') }}" class="group flex min-w-0 items-center gap-3" @click="$store.layout.sidebarOpen = false">
            @if (!empty($appBrandLogoUrl))
                <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white/10 ring-1 ring-white/25 shadow-lg shadow-black/20">
                    <img src="{{ $appBrandLogoUrl }}" alt="" class="h-full w-full object-cover" />
                </span>
            @else
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white shadow-lg shadow-[color:var(--app-primary)]/35 ring-1 ring-white/25" style="background: linear-gradient(145deg, var(--app-primary), #ff4d6d 55%, #8f001f)">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </span>
            @endif
            <div class="min-w-0">
                <span class="block font-semibold truncate leading-tight" style="color: var(--app-sidebar-text)">{{ $appBrandName ?? config('app.name') }}</span>
                <span class="block text-[11px] truncate" style="color: var(--app-sidebar-muted)">{{ __('Inbox') }}</span>
            </div>
        </a>
    </div>

    <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden py-4 px-3 space-y-0.5">
        <p class="px-3 pt-1 pb-2 text-[10px] font-bold uppercase tracking-[0.12em]" style="color: var(--app-sidebar-nav-muted)">{{ __('Menu') }}</p>

        <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" @click="$store.layout.sidebarOpen = false">
            <svg class="h-5 w-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span class="truncate">{{ __('Home') }}</span>
        </x-sidebar-link>

        <p class="px-3 pt-5 pb-2 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">{{ __('Connections') }}</p>

        <x-sidebar-link
            :href="route('whatsapp.connect')"
            :active="$canWhatsapp && request()->routeIs('whatsapp.connect')"
            :disabled="! $canWhatsapp"
            @click="$store.layout.sidebarOpen = false"
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#25d366]/20 text-[#25d366] ring-1 ring-[#25d366]/30">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </span>
            <span class="truncate">{{ __('WhatsApp') }}</span>
        </x-sidebar-link>

        <x-sidebar-link
            :href="route('messenger.connect')"
            :active="$canMessenger && request()->routeIs('messenger.connect')"
            :disabled="! $canMessenger"
            @click="$store.layout.sidebarOpen = false"
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#0084ff]/20 text-[#5eb3ff] ring-1 ring-[#0084ff]/35">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </span>
            <span class="truncate">{{ __('Facebook / Messenger') }}</span>
        </x-sidebar-link>

        <x-sidebar-link
            :href="route('connections.index')"
            :active="$canConnections && request()->routeIs('connections.*')"
            :disabled="! $canConnections"
            @click="$store.layout.sidebarOpen = false"
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-500/15 text-teal-200 ring-1 ring-teal-500/25">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
            </span>
            <span class="truncate">{{ __('Connections') }}</span>
        </x-sidebar-link>

        <p class="px-3 pt-5 pb-2 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">{{ __('Work') }}</p>

        <x-sidebar-link
            :href="route('inbox')"
            :active="$canInbox && request()->routeIs('inbox')"
            :disabled="! $canInbox"
            @click="$store.layout.sidebarOpen = false"
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/25">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </span>
            <span class="truncate">{{ __('Chats') }}</span>
        </x-sidebar-link>

        <x-sidebar-link
            :href="route('employees.index')"
            :active="$canEmployees && request()->routeIs('employees.*')"
            :disabled="! $canEmployees"
            @click="$store.layout.sidebarOpen = false"
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300 ring-1 ring-violet-500/25">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </span>
            <span class="truncate">{{ __('Employees') }}</span>
        </x-sidebar-link>

        <x-sidebar-link
            :href="route('roles.index')"
            :active="$canRoles && request()->routeIs('roles.*')"
            :disabled="! $canRoles"
            @click="$store.layout.sidebarOpen = false"
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-200 ring-1 ring-amber-500/25">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </span>
            <span class="truncate">{{ __('Roles') }}</span>
        </x-sidebar-link>

        <x-sidebar-link
            :href="route('permissions.index')"
            :active="$canPermissions && request()->routeIs('permissions.*')"
            :disabled="! $canPermissions"
            @click="$store.layout.sidebarOpen = false"
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-500/15 text-sky-200 ring-1 ring-sky-500/25">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </span>
            <span class="truncate">{{ __('Permissions') }}</span>
        </x-sidebar-link>

        <p class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-[0.12em]" style="color: var(--app-sidebar-nav-muted)">{{ __('System') }}</p>

        <x-sidebar-link :href="route('settings.index')" :active="request()->routeIs('settings.*')" @click="$store.layout.sidebarOpen = false">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-600/50 text-slate-200 ring-1 ring-slate-500/40">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            <span class="truncate">{{ __('Settings') }}</span>
        </x-sidebar-link>

        @unless ($canSeeWorkbench)
            <p class="mx-3 mt-4 rounded-lg bg-amber-500/10 px-3 py-2.5 text-[11px] leading-relaxed text-amber-200/90 ring-1 ring-amber-500/20">
                {{ __('Messaging tools need matching permissions on your roles, or a role with full admin panel access. Ask an administrator to update your access.') }}
            </p>
        @endunless
    </nav>

</aside>

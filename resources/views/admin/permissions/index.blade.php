<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Permissions') }}</h2>
    </x-slot>

    <div class="app-page space-y-6">
        @if (session('status'))
            <div
                class="flex items-start gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
                role="status"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="max-w-2xl space-y-2">
                <p class="text-sm leading-relaxed text-[color:var(--app-text-muted)]">
                    {{ __('Permissions are the building blocks for access. Assign them to roles, then assign roles to users on the Employees page.') }}
                </p>
                <p class="text-xs text-[color:var(--app-text-muted)]">
                    {{ __('Any permission can be deleted only when it is not attached to any role.') }}
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <a href="{{ route('permissions.create') }}" class="app-btn-primary">
                    <svg class="h-4 w-4 shrink-0 opacity-95" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('New permission') }}
                </a>
                <a
                    href="{{ route('roles.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-4 py-2.5 text-sm font-semibold text-[color:var(--app-text)] shadow-sm transition hover:bg-[color:var(--app-shell-bg)]"
                >
                    {{ __('Manage roles') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 text-sm">
            <span class="inline-flex items-center gap-2 rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-3 py-1.5 tabular-nums text-[color:var(--app-text)]">
                <span class="text-[color:var(--app-text-muted)]">{{ __('Total') }}</span>
                <strong>{{ number_format($stats['total']) }}</strong>
            </span>
            <span class="inline-flex items-center gap-2 rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-3 py-1.5 tabular-nums text-[color:var(--app-text)]">
                <span class="text-[color:var(--app-text-muted)]">{{ __('System') }}</span>
                <strong>{{ number_format($stats['system']) }}</strong>
            </span>
            <span class="inline-flex items-center gap-2 rounded-lg border border-sky-200/80 bg-sky-50/90 px-3 py-1.5 tabular-nums text-sky-950">
                <span class="text-sky-800/90">{{ __('Custom') }}</span>
                <strong>{{ number_format($stats['custom']) }}</strong>
            </span>
            <span class="inline-flex items-center gap-2 rounded-lg border border-amber-200/90 bg-amber-50/90 px-3 py-1.5 tabular-nums text-amber-950">
                <span class="text-amber-900/90">{{ __('Used by roles') }}</span>
                <strong>{{ number_format($stats['used_by_roles']) }}</strong>
            </span>
        </div>

        <div class="rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)]/80 p-4 sm:p-5">
            <h3 class="text-sm font-semibold text-[color:var(--app-text)]">{{ __('Quick reference') }}</h3>
            <ul class="mt-3 list-inside list-disc space-y-1.5 text-sm text-[color:var(--app-text-muted)]">
                <li>{{ __('Permissions are assigned to roles, and users inherit access from their assigned roles.') }}</li>
                <li>{{ __('The Administrator role is kept in sync with all permissions automatically.') }}</li>
                <li>{{ __('Delete is available only when a permission is not attached to any role.') }}</li>
            </ul>
        </div>

        <div class="overflow-hidden rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[color:var(--app-card-border)] text-sm">
                    <thead>
                        <tr class="bg-[color:var(--app-shell-bg)] text-left text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">
                            <th scope="col" class="px-4 py-3 sm:px-6">{{ __('Name') }}</th>
                            <th scope="col" class="px-4 py-3 sm:px-6">{{ __('Slug') }}</th>
                            <th scope="col" class="px-4 py-3 sm:px-6">{{ __('Roles') }}</th>
                            <th scope="col" class="min-w-[10rem] px-4 py-3 text-end sm:px-6">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--app-card-border)]">
                        @foreach ($permissions as $permission)
                            @php
                                $canDeletePermission = auth()->user()?->hasElevatedPanelAccess() || (int) $permission->roles_count === 0;
                            @endphp
                            <tr class="align-top transition hover:bg-[color:var(--app-shell-bg)]/60">
                                <td class="px-4 py-4 sm:px-6">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-[color:var(--app-text)]">{{ $permission->name }}</span>
                                        @if ($permission->is_system)
                                            <span class="inline-flex rounded-full bg-[color:var(--app-shell-bg)] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[color:var(--app-text-muted)] ring-1 ring-[color:var(--app-card-border)]">{{ __('System') }}</span>
                                        @endif
                                    </div>
                                    @if ($permission->description)
                                        <p class="mt-1 line-clamp-2 text-xs text-[color:var(--app-text-muted)]">{{ $permission->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 font-mono text-xs text-[color:var(--app-text-muted)] sm:px-6">{{ $permission->slug }}</td>
                                <td class="px-4 py-4 tabular-nums text-[color:var(--app-text)] sm:px-6">
                                    {{ number_format($permission->roles_count) }}
                                    @if ((int) $permission->roles_count > 0 && ! auth()->user()?->hasElevatedPanelAccess())
                                        <span class="mt-1 block text-[11px] font-normal text-amber-800/90">{{ __('In use — detach from roles before deleting') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-end sm:px-6">
                                    <div class="flex flex-col items-end gap-2 sm:flex-row sm:justify-end">
                                        <a
                                            href="{{ route('permissions.edit', $permission) }}"
                                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-3 py-2 text-xs font-semibold text-[color:var(--app-text)] shadow-sm transition hover:bg-[color:var(--app-shell-bg)] sm:w-auto"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            {{ __('Edit') }}
                                        </a>
                                        @if ($canDeletePermission)
                                            <button
                                                type="button"
                                                class="inline-flex w-full items-center justify-center rounded-lg border border-red-200/90 bg-white px-3 py-2 text-xs font-semibold text-red-800 shadow-sm transition hover:bg-red-50 sm:w-auto"
                                                x-on:click="$dispatch('open-modal', 'permission-delete-{{ $permission->id }}')"
                                            >
                                                {{ __('Delete') }}
                                            </button>
                                            <x-delete-confirm-modal
                                                name="permission-delete-{{ $permission->id }}"
                                                :title="__('Delete this permission?')"
                                                :description="__(':name will be removed permanently. This cannot be undone.', ['name' => $permission->name])"
                                                :action="route('permissions.destroy', $permission)"
                                                :confirm-label="__('Delete permission')"
                                            />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($permissions->hasPages())
                <div class="border-t border-[color:var(--app-card-border)] px-4 py-3 sm:px-6">
                    {{ $permissions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

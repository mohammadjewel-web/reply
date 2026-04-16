@php
    $selected = old('permission_ids', isset($role) ? $role->permissions->pluck('id')->all() : []);
    $isAdminRole = isset($role) && $role->slug === \App\Models\Role::SLUG_ADMIN;
@endphp

<div>
    <x-input-label :value="__('Permissions')" />
    <p class="mt-1 text-xs text-slate-500">{{ __('Users gain access when any of their roles includes a permission. Roles with full admin panel access still bypass these checks.') }}</p>
    @if ($isAdminRole)
        <div class="mt-3 rounded-lg border border-sky-200 bg-sky-50/90 px-4 py-3 text-sm text-sky-950">
            <p class="font-medium">{{ __('Full permission set') }}</p>
            <p class="mt-1 text-xs text-sky-900/90">{{ __('The Administrator role always includes every permission. New permissions are attached automatically when they are created.') }}</p>
        </div>
    @else
    <fieldset class="mt-3 space-y-2 rounded-lg border border-slate-200 bg-slate-50/80 p-4 max-h-64 overflow-y-auto">
        <legend class="sr-only">{{ __('Permission assignment') }}</legend>
        @forelse ($permissions as $permission)
            <label class="flex items-start gap-2 cursor-pointer text-sm">
                <input
                    type="checkbox"
                    name="permission_ids[]"
                    value="{{ $permission->id }}"
                    class="mt-0.5 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                    @checked(in_array($permission->id, $selected, true))
                />
                <span>
                    <span class="font-medium text-slate-800">{{ $permission->name }}</span>
                    <span class="block font-mono text-[11px] text-slate-500">{{ $permission->slug }}</span>
                </span>
            </label>
        @empty
            <p class="text-sm text-slate-500">{{ __('No permissions defined yet. Create permissions first.') }}</p>
        @endforelse
    </fieldset>
    @endif
    <x-input-error class="mt-2" :messages="$errors->get('permission_ids')" />
    <x-input-error class="mt-2" :messages="$errors->get('permission_ids.*')" />
</div>

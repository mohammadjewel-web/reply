<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Edit permission') }}</h2>
    </x-slot>

    <div class="app-page app-page--narrow space-y-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200/80 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <p class="text-sm text-[color:var(--app-text-muted)]">
            <a href="{{ route('permissions.index') }}" class="font-semibold text-[color:var(--app-primary)] hover:underline">{{ __('← Back to permissions') }}</a>
        </p>

        <div class="app-card">
            <div class="mb-6 border-b border-[color:var(--app-card-border)] pb-4">
                <p class="text-sm leading-relaxed text-[color:var(--app-text-muted)]">
                    {{ __('Update permission details used by your role matrix. Changes apply wherever this permission is granted through roles.') }}
                </p>
            </div>
            <form method="post" action="{{ route('permissions.update', $permission) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $permission->name)" required autofocus />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                @if ($permission->is_system)
                    <div class="rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-900">
                        <p class="font-medium">{{ __('System permission') }}</p>
                        <p class="mt-1 text-xs text-amber-800/90">{{ __('The slug is fixed so routes and policies stay stable. You can change the display name and description.') }}</p>
                    </div>
                    <div>
                        <x-input-label :value="__('Slug')" />
                        <p class="mt-1 font-mono text-sm text-slate-700">{{ $permission->slug }}</p>
                    </div>
                @else
                    <div>
                        <x-input-label for="slug" :value="__('Slug')" />
                        <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('slug', $permission->slug)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                    </div>
                @endif

                <div>
                    <x-input-label for="description" :value="__('Description')" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('description', $permission->description) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="app-btn-primary">
                        <svg class="h-4 w-4 shrink-0 opacity-95" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('Save changes') }}
                    </button>
                    <a href="{{ route('permissions.index') }}" class="inline-flex items-center justify-center rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-4 py-2.5 text-sm font-semibold text-[color:var(--app-text)] shadow-sm transition hover:bg-[color:var(--app-shell-bg)]">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>

        @if (auth()->user()?->hasElevatedPanelAccess() || (int) ($permission->roles_count ?? 0) === 0)
            <div class="app-card border-red-200/80 bg-red-50/40">
                <h3 class="text-sm font-semibold text-red-950">{{ __('Remove permission') }}</h3>
                <p class="mt-2 text-sm text-[color:var(--app-text-muted)]">
                    {{ __('Permanently remove this permission definition. This cannot be undone.') }}
                </p>
                <div class="mt-4">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-800 shadow-sm transition hover:bg-red-50"
                        x-on:click="$dispatch('open-modal', 'permission-delete-{{ $permission->id }}')"
                    >
                        {{ __('Delete permission') }}
                    </button>
                </div>
            </div>

            <x-delete-confirm-modal
                name="permission-delete-{{ $permission->id }}"
                :title="__('Delete this permission?')"
                :description="__(':name will be removed permanently. This cannot be undone.', ['name' => $permission->name])"
                :action="route('permissions.destroy', $permission)"
                :confirm-label="__('Delete permission')"
            />
        @endif
    </div>
</x-app-layout>

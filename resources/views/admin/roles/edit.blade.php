<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Edit role') }}</h2>
    </x-slot>

    <div class="app-page app-page--narrow">
        <div class="app-card">
            <form method="post" action="{{ route('roles.update', $role) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $role->name)" required autofocus />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                @if ($role->is_system)
                    <div class="rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-900">
                        <p class="font-medium">{{ __('System role') }}</p>
                        <p class="mt-1 text-xs text-amber-800/90">{{ __('Slug and panel access are fixed for built-in roles. You can still change the display name and description.') }}</p>
                    </div>
                    <div>
                        <x-input-label :value="__('Slug')" />
                        <p class="mt-1 font-mono text-sm text-slate-700">{{ $role->slug }}</p>
                    </div>
                    <div>
                        <x-input-label :value="__('Admin panel access')" />
                        <p class="mt-1 text-sm text-slate-700">{{ $role->grants_admin_panel ? __('Yes') : __('No') }}</p>
                    </div>
                @else
                    <div>
                        <x-input-label for="slug" :value="__('Slug')" />
                        <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('slug', $role->slug)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                    </div>

                    <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                        <input id="grants_admin_panel" name="grants_admin_panel" type="checkbox" value="1" class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" @checked(old('grants_admin_panel', $role->grants_admin_panel)) />
                        <div>
                            <x-input-label for="grants_admin_panel" :value="__('Grants admin panel access')" class="!mb-0" />
                            <p class="mt-1 text-xs text-slate-600">{{ __('Users with this role can use WhatsApp, Messenger, Chats, Employees, Roles, and integration settings.') }}</p>
                        </div>
                    </div>
                @endif

                <div>
                    <x-input-label for="description" :value="__('Description')" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('description', $role->description) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                </div>

                @include('admin.roles._permission-fields', ['role' => $role])

                <div class="flex flex-wrap gap-3">
                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                    <a href="{{ route('roles.index') }}" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

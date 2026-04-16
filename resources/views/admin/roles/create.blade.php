<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('New role') }}</h2>
    </x-slot>

    <div class="app-page app-page--narrow">
        <div class="app-card">
            <form method="post" action="{{ route('roles.store') }}" class="space-y-6">
                @csrf

                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="slug" :value="__('Slug (optional)')" />
                    <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('slug')" placeholder="e.g. team-lead" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('Lowercase letters, numbers, and hyphens. Leave blank to generate from the name.') }}</p>
                    <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                </div>

                <div>
                    <x-input-label for="description" :value="__('Description')" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('description') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                </div>

                <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                    <input id="grants_admin_panel" name="grants_admin_panel" type="checkbox" value="1" class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" @checked(old('grants_admin_panel')) />
                    <div>
                        <x-input-label for="grants_admin_panel" :value="__('Grants admin panel access')" class="!mb-0" />
                        <p class="mt-1 text-xs text-slate-600">{{ __('Full access: bypasses individual permission checks. Use fine-grained permissions below when this is off.') }}</p>
                    </div>
                </div>

                @include('admin.roles._permission-fields', ['role' => null])

                <div class="flex flex-wrap gap-3">
                    <x-primary-button>{{ __('Create role') }}</x-primary-button>
                    <a href="{{ route('roles.index') }}" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

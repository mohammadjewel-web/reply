<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('New permission') }}</h2>
    </x-slot>

    <div class="app-page app-page--narrow space-y-6">
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
                    {{ __('Create a permission to control access to a specific feature. Permissions are assigned to roles, and users inherit access from their role memberships.') }}
                </p>
            </div>
            <form
                method="post"
                action="{{ route('permissions.store') }}"
                class="space-y-6"
                x-data="{
                    name: @js(old('name', '')),
                    slug: @js(old('slug', '')),
                    slugTouched: @js(filled(old('slug'))),
                    slugify(s) {
                        if (! s) return '';
                        return s
                            .toLowerCase()
                            .normalize('NFD')
                            .replace(/\p{M}/gu, '')
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-+|-+$/g, '')
                            .replace(/-+/g, '-');
                    },
                    syncSlugFromName() {
                        if (! this.slugTouched) {
                            this.slug = this.slugify(this.name);
                        }
                    },
                    markSlugTouched() {
                        this.slugTouched = true;
                    },
                    init() {
                        this.syncSlugFromName();
                    },
                }"
            >
                @csrf

                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input
                        id="name"
                        name="name"
                        type="text"
                        class="mt-1 block w-full"
                        x-model="name"
                        @input.debounce.150ms="syncSlugFromName()"
                        required
                        autofocus
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="slug" :value="__('Slug (optional)')" />
                    <x-text-input
                        id="slug"
                        name="slug"
                        type="text"
                        class="mt-1 block w-full font-mono text-sm"
                        x-model="slug"
                        @input="markSlugTouched()"
                        placeholder="e.g. reports.view"
                    />
                    <p class="mt-1 text-xs text-[color:var(--app-text-muted)]">
                        {{ __('Suggested from the name as you type. Edit the slug any time to keep a custom value.') }}
                    </p>
                    <p class="mt-1 font-mono text-xs text-[color:var(--app-text-muted)]" x-show="name.length && slugify(name)" x-cloak>
                        <span class="font-sans not-italic">{{ __('Preview') }}:</span>
                        <span x-text="slugify(name)"></span>
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                </div>

                <div>
                    <x-input-label for="description" :value="__('Description')" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('description') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="app-btn-primary">
                        <svg class="h-4 w-4 shrink-0 opacity-95" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('Create permission') }}
                    </button>
                    <a href="{{ route('permissions.index') }}" class="inline-flex items-center justify-center rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-4 py-2.5 text-sm font-semibold text-[color:var(--app-text)] shadow-sm transition hover:bg-[color:var(--app-shell-bg)]">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

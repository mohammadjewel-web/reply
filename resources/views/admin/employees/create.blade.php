<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('New employee') }}</h2>
    </x-slot>

    <div class="app-page app-page--narrow space-y-6">
        <div class="space-y-4">
            <p class="text-sm leading-relaxed text-[color:var(--app-text-muted)]">
                {{ __('Create an account with a password. A verification email is sent automatically, and the user signs in after verifying their email.') }}
            </p>
            <div class="rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)]/90 p-4 sm:p-5">
                <h3 class="text-sm font-semibold text-[color:var(--app-text)]">{{ __('What gets created') }}</h3>
                <ul class="mt-2 list-inside list-disc space-y-1.5 text-sm text-[color:var(--app-text-muted)]">
                    <li>{{ __('A new user record with the name and email you enter.') }}</li>
                    <li>{{ __('A hashed password—they can sign in with the password you set here (or change it later in Profile).') }}</li>
                    <li>{{ __('Roles you attach here; permissions follow those roles (same rules as editing existing employees).') }}</li>
                    <li>{{ __('At least one admin-panel role must remain in the system—usually you or another administrator.') }}</li>
                </ul>
            </div>
            <div class="rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] p-4 sm:p-5">
                <h3 class="text-sm font-semibold text-[color:var(--app-text)]">{{ __('Password requirements') }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-[color:var(--app-text-muted)]">
                    {{ __('Passwords must meet your application’s security rules (length, letters, numbers, and symbols). If you see an error after submitting, adjust the password and try again.') }}
                </p>
            </div>
        </div>

        <div class="app-card">
            <form method="post" action="{{ route('employees.store') }}" class="space-y-6">
                @csrf

                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input
                        id="name"
                        name="name"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('name')"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input
                        id="email"
                        name="email"
                        type="email"
                        class="mt-1 block w-full"
                        :value="old('email')"
                        required
                        autocomplete="username"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="new-password"
                    />
                    <p class="mt-1.5 text-xs text-[color:var(--app-text-muted)]">
                        {{ __('Use a strong password and share it through a secure channel (password manager or encrypted message)—not plain email.') }}
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                    <x-text-input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="new-password"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
                </div>

                <div class="border-t border-[color:var(--app-card-border)] pt-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[color:var(--app-text-muted)]">{{ __('Contact & work') }}</p>
                    <p class="mt-1 text-xs text-[color:var(--app-text-muted)]">{{ __('Optional. Shown on the team list and editable later.') }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input
                                id="phone"
                                name="phone"
                                type="tel"
                                class="mt-1 block w-full"
                                :value="old('phone')"
                                autocomplete="tel"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>
                        <div>
                            <x-input-label for="job_title" :value="__('Job title')" />
                            <x-text-input
                                id="job_title"
                                name="job_title"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('job_title')"
                                autocomplete="organization-title"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="department" :value="__('Department / team')" />
                            <x-text-input
                                id="department"
                                name="department"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('department')"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('department')" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="notes" :value="__('Internal notes')" />
                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >{{ old('notes') }}</textarea>
                            <p class="mt-1 text-xs text-[color:var(--app-text-muted)]">{{ __('For admins only. Not shown to the employee.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[color:var(--app-text-muted)]">{{ __('Roles') }}</p>
                    <p class="mt-1 text-xs text-[color:var(--app-text-muted)]">{{ __('Select at least one. Roles with “Panel” grant full admin panel access.') }}</p>
                    <fieldset class="mt-3 space-y-2">
                        <legend class="sr-only">{{ __('Roles') }}</legend>
                        @foreach ($allRoles as $role)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)] px-3 py-2">
                                <input
                                    type="checkbox"
                                    name="role_ids[]"
                                    value="{{ $role->id }}"
                                    class="rounded border-[color:var(--app-card-border)] text-emerald-600 focus:ring-emerald-500"
                                    @checked(in_array($role->id, array_map('intval', (array) old('role_ids', [])), true))
                                />
                                <span class="text-sm text-[color:var(--app-text)]">{{ $role->name }}</span>
                                @if ($role->grants_admin_panel)
                                    <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-emerald-800">{{ __('Panel') }}</span>
                                @endif
                            </label>
                        @endforeach
                    </fieldset>
                    <x-input-error class="mt-2" :messages="$errors->get('role_ids')" />
                </div>

                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)] px-4 py-3">
                    <input type="hidden" name="is_active" value="0" />
                    <input
                        id="is_active"
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="rounded border-[color:var(--app-card-border)] text-emerald-600 focus:ring-emerald-500"
                        @checked((string) old('is_active', '1') === '1')
                    />
                    <x-input-label for="is_active" :value="__('Account active')" class="!mb-0 cursor-pointer text-sm font-medium text-[color:var(--app-text)]" />
                </div>
                <x-input-error class="-mt-2" :messages="$errors->get('is_active')" />

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="app-btn-primary">
                        <svg class="h-4 w-4 shrink-0 opacity-95" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('Create employee') }}
                    </button>
                    <a
                        href="{{ route('employees.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-4 py-2.5 text-sm font-semibold text-[color:var(--app-text)] shadow-sm transition hover:bg-[color:var(--app-shell-bg)]"
                    >
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

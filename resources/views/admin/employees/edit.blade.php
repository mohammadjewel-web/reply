<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Edit employee') }}</h2>
    </x-slot>

    <div class="app-page app-page--narrow space-y-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200/80 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->has('employee'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                {{ $errors->first('employee') }}
            </div>
        @endif

        <p class="text-sm text-[color:var(--app-text-muted)]">
            <a href="{{ route('employees.index') }}" class="font-semibold text-[color:var(--app-primary)] hover:underline">{{ __('← Back to employees') }}</a>
        </p>

        @php
            $isOwnAdmin = auth()->id() === $employee->id && auth()->user()->hasElevatedPanelAccess();
        @endphp

        <div class="app-card">
            <form method="post" action="{{ route('employees.update', $employee) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $employee->name)" required autocomplete="name" />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $employee->email)" required autocomplete="username" />
                    <p class="mt-1 text-xs text-[color:var(--app-text-muted)]">{{ __('Changing the email clears verification until the user confirms the new address (if your app requires it).') }}</p>
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>

                <div class="border-t border-[color:var(--app-card-border)] pt-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[color:var(--app-text-muted)]">{{ __('Contact & work') }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $employee->phone)" autocomplete="tel" />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>
                        <div>
                            <x-input-label for="job_title" :value="__('Job title')" />
                            <x-text-input id="job_title" name="job_title" type="text" class="mt-1 block w-full" :value="old('job_title', $employee->job_title)" autocomplete="organization-title" />
                            <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="department" :value="__('Department / team')" />
                            <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department', $employee->department)" />
                            <x-input-error class="mt-2" :messages="$errors->get('department')" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="notes" :value="__('Internal notes')" />
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $employee->notes) }}</textarea>
                            <p class="mt-1 text-xs text-[color:var(--app-text-muted)]">{{ __('For administrators only.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[color:var(--app-text-muted)]">{{ __('Roles') }}</p>
                    @if ($isOwnAdmin)
                        <div class="mt-3 rounded-lg border border-amber-200/90 bg-amber-50/90 px-3 py-2.5 text-sm text-amber-950">
                            {{ __('Your roles are fixed. Another administrator can change them if needed.') }}
                        </div>
                        @foreach ($employee->roles as $r)
                            <input type="hidden" name="role_ids[]" value="{{ $r->id }}" />
                        @endforeach
                        <ul class="mt-3 space-y-2 text-sm text-[color:var(--app-text)]">
                            @foreach ($employee->roles as $r)
                                <li class="flex flex-wrap items-center gap-2">
                                    <span>{{ $r->name }}</span>
                                    @if ($r->grants_admin_panel)
                                        <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-emerald-800">{{ __('Panel') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-1 text-xs text-[color:var(--app-text-muted)]">{{ __('Select at least one.') }}</p>
                        <fieldset class="mt-3 space-y-2">
                            <legend class="sr-only">{{ __('Roles') }}</legend>
                            @foreach ($allRoles as $role)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)] px-3 py-2">
                                    <input
                                        type="checkbox"
                                        name="role_ids[]"
                                        value="{{ $role->id }}"
                                        class="rounded border-[color:var(--app-card-border)] text-emerald-600 focus:ring-emerald-500"
                                        @checked(in_array($role->id, array_map('intval', (array) old('role_ids', $employee->roles->pluck('id')->all())), true))
                                    />
                                    <span class="text-sm text-[color:var(--app-text)]">{{ $role->name }}</span>
                                    @if ($role->grants_admin_panel)
                                        <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-emerald-800">{{ __('Panel') }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </fieldset>
                    @endif
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
                        @checked((string) old('is_active', $employee->is_active ? '1' : '0') === '1')
                    />
                    <x-input-label for="is_active" :value="__('Account active')" class="!mb-0 cursor-pointer text-sm font-medium text-[color:var(--app-text)]" />
                </div>
                <x-input-error class="-mt-2" :messages="$errors->get('is_active')" />

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="app-btn-primary">
                        <svg class="h-4 w-4 shrink-0 opacity-95" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('Save changes') }}
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

        @if ($canDeleteEmployee)
            <div class="app-card border-red-200/80 bg-red-50/40">
                <h3 class="text-sm font-semibold text-red-950">{{ __('Remove employee') }}</h3>
                <p class="mt-2 text-sm text-[color:var(--app-text-muted)]">
                    {{ __('Permanently remove this account from the workspace. This cannot be undone.') }}
                </p>
                <div class="mt-4">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-800 shadow-sm transition hover:bg-red-50"
                        x-on:click="$dispatch('open-modal', 'employee-delete-{{ $employee->id }}')"
                    >
                        {{ __('Delete employee') }}
                    </button>
                </div>
            </div>

            <x-delete-confirm-modal
                name="employee-delete-{{ $employee->id }}"
                :title="__('Remove this employee?')"
                :description="__('They will lose access immediately. This action cannot be undone.')"
                :action="route('employees.destroy', $employee)"
                :confirm-label="__('Delete employee')"
            />
        @endif
    </div>
</x-app-layout>

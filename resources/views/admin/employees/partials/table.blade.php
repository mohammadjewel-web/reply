<div class="overflow-hidden rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-[color:var(--app-card-border)] text-sm">
            <thead>
                <tr class="bg-[color:var(--app-shell-bg)] text-left text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">
                    <th scope="col" class="px-4 py-3 sm:px-6">{{ __('Name') }}</th>
                    <th scope="col" class="px-4 py-3 sm:px-6">{{ __('Email & verification') }}</th>
                    <th scope="col" class="min-w-[14rem] px-4 py-3 sm:px-6">{{ __('Roles') }}</th>
                    <th scope="col" class="hidden px-4 py-3 sm:table-cell sm:px-6">{{ __('Status') }}</th>
                    <th scope="col" class="min-w-[8.5rem] px-4 py-3 text-end sm:px-6">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[color:var(--app-card-border)]">
                @forelse ($users as $employee)
                    @php
                        $formId = 'employee-roles-'.$employee->id;
                        $initial = strtoupper(mb_substr((string) $employee->name, 0, 1));
                        $isOwnRowAdmin = auth()->id() === $employee->id && auth()->user()->hasElevatedPanelAccess();
                    @endphp
                    <tr class="align-top transition hover:bg-[color:var(--app-shell-bg)]/60">
                        <td class="px-4 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-[color:var(--app-primary-text)]" style="background: linear-gradient(145deg, var(--app-primary), var(--app-primary-hover))" aria-hidden="true">{{ $initial }}</span>
                                <div class="min-w-0">
                                    <span class="font-semibold text-[color:var(--app-text)]">{{ $employee->name }}</span>
                                    <p class="mt-1 text-[11px] tabular-nums text-[color:var(--app-text-muted)]">
                                        {{ __('ID') }} {{ $employee->id }}
                                        <span class="text-[color:var(--app-card-border)]" aria-hidden="true">·</span>
                                        {{ __('Member since :date', ['date' => $employee->created_at?->translatedFormat('M j, Y') ?? '—']) }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 sm:px-6">
                            @if ($employee->email_verified_at)
                                <p class="break-all text-[color:var(--app-text)]">{{ $employee->email }}</p>
                            @else
                                <form method="post" action="{{ route('employees.verification.send', $employee) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="break-all text-left font-medium text-[color:var(--app-primary)] underline decoration-dotted underline-offset-2 hover:text-[color:var(--app-primary-hover)]" title="{{ __('Send verification email') }}">{{ $employee->email }}</button>
                                </form>
                            @endif
                            @if ($employee->email_verified_at)
                                <p class="mt-1 inline-flex items-center gap-1 text-[11px] font-medium text-emerald-800">{{ __('Email verified') }}</p>
                            @else
                                <p class="mt-1 inline-flex items-center gap-1 text-[11px] font-medium text-amber-800">{{ __('Email not verified') }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-4 sm:px-6">
                            @if ($isOwnRowAdmin)
                                <ul class="space-y-1 text-sm text-[color:var(--app-text)]">
                                    @foreach ($employee->roles as $r)
                                        <li>{{ $r->name }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <form id="{{ $formId }}" method="post" action="{{ route('employees.update', $employee) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="roles_only" value="1" />
                                    <fieldset class="space-y-2">
                                        @foreach ($allRoles as $role)
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" class="rounded border-[color:var(--app-card-border)] text-emerald-600 focus:ring-emerald-500" @checked($employee->roles->contains('id', $role->id)) />
                                                <span class="text-[color:var(--app-text)]">{{ $role->name }}</span>
                                            </label>
                                        @endforeach
                                    </fieldset>
                                </form>
                            @endif
                        </td>
                        <td class="hidden px-4 py-4 sm:table-cell sm:px-6">
                            @if ($employee->is_active)
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700">{{ __('Yes') }}</span>
                            @else
                                <span class="text-xs font-medium text-[color:var(--app-text-muted)]">{{ __('No') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-end sm:px-6">
                            <div class="flex flex-col items-end gap-2 sm:flex-row sm:justify-end">
                                <a
                                    href="{{ route('employees.profile', $employee) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-3 py-2 text-xs font-semibold text-[color:var(--app-text)] shadow-sm transition hover:bg-[color:var(--app-shell-bg)] sm:w-auto"
                                >{{ __('Profile') }}</a>
                                <a href="{{ route('employees.edit', $employee) }}" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-3 py-2 text-xs font-semibold text-[color:var(--app-text)] shadow-sm transition hover:bg-[color:var(--app-shell-bg)] sm:w-auto">{{ __('Edit') }}</a>
                                @unless ($isOwnRowAdmin)
                                    <button type="submit" form="{{ $formId }}" class="app-btn-primary whitespace-nowrap">{{ __('Save') }}</button>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-[color:var(--app-text-muted)]">{{ __('No employees found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="border-t border-[color:var(--app-card-border)] px-4 py-3 sm:px-6">
            {{ $users->links() }}
        </div>
    @endif
</div>

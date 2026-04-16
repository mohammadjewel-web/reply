<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Employees') }}</h2>
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
                    {{ __('Assign roles to each team member. Access comes from each role\'s permissions (or from full admin panel access on a role). At least one user must keep a role with admin panel access.') }}
                </p>
                <p class="text-xs text-[color:var(--app-text-muted)]">
                    {{ trans_choice('This workspace has :count defined role.|This workspace has :count defined roles.', $rolesCount) }}
                    {{ __('Edit role permissions under Manage roles. Use Edit for account details, contact info, and notes.') }}
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <a href="{{ route('employees.create') }}" class="app-btn-primary">
                    <svg class="h-4 w-4 shrink-0 opacity-95" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('New employee') }}
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
            <span class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50/80 px-3 py-1.5 tabular-nums text-emerald-900">
                <span class="text-emerald-700/90">{{ __('Active') }}</span>
                <strong>{{ number_format($stats['active']) }}</strong>
            </span>
            <span class="inline-flex items-center gap-2 rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-3 py-1.5 tabular-nums text-[color:var(--app-text)]">
                <span class="text-[color:var(--app-text-muted)]">{{ __('Inactive') }}</span>
                <strong>{{ number_format($stats['inactive']) }}</strong>
            </span>
            <span class="inline-flex items-center gap-2 rounded-lg border border-sky-200/80 bg-sky-50/90 px-3 py-1.5 tabular-nums text-sky-950">
                <span class="text-sky-800/90">{{ __('Verified email') }}</span>
                <strong>{{ number_format($stats['verified_emails']) }}</strong>
            </span>
            @if ($stats['pending_email'] > 0)
                <span class="inline-flex items-center gap-2 rounded-lg border border-amber-200/90 bg-amber-50/90 px-3 py-1.5 tabular-nums text-amber-950">
                    <span class="text-amber-900/90">{{ __('Email not verified') }}</span>
                    <strong>{{ number_format($stats['pending_email']) }}</strong>
                </span>
            @endif
        </div>

        <div class="rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)]/80 p-4 sm:p-5">
            <h3 class="text-sm font-semibold text-[color:var(--app-text)]">{{ __('Quick reference') }}</h3>
            <ul class="mt-3 list-inside list-disc space-y-1.5 text-sm text-[color:var(--app-text-muted)]">
                <li>{{ __('Save on each row updates roles only (not for your own row if you have admin panel access—your roles are locked). Use Edit for profile and account details.') }}</li>
                <li>{{ __('Inactive accounts cannot sign in. You cannot deactivate your own account from Edit.') }}</li>
                <li>{{ __('Deleting an employee removes their account; you cannot delete yourself or the last administrator.') }}</li>
                <li>{{ __('“Panel” means the role grants full admin panel access (all areas unless you use granular permissions on other roles).') }}</li>
                <li>{{ __('New accounts you add from “New employee” receive a verification email and can sign in after confirming it.') }}</li>
            </ul>
        </div>

        <div class="rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] p-4 shadow-sm">
            <label for="employees-search" class="text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">{{ __('Search employees') }}</label>
            <input id="employees-search" type="text" placeholder="{{ __('Name, email, phone, job title, department') }}" class="mt-2 block w-full rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)]/50 px-3 py-2.5 text-sm text-[color:var(--app-text)] shadow-inner focus:border-[color:var(--app-primary)] focus:outline-none focus:ring-2 focus:ring-[color:var(--app-primary)]/20" />
        </div>

        <div id="employees-table-container">
            @include('admin.employees.partials.table', [
                'users' => $users,
                'allRoles' => $allRoles,
                'canDeleteById' => $canDeleteById,
            ])
        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('employees-search');
            const container = document.getElementById('employees-table-container');
            if (!input || !container) return;
            let timer = null;
            let activeController = null;

            async function fetchTable(url) {
                if (activeController) activeController.abort();
                activeController = new AbortController();
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    signal: activeController.signal
                });
                if (!response.ok) return;
                const payload = await response.json();
                if (!payload.html) return;
                container.innerHTML = payload.html;
                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    window.Alpine.initTree(container);
                }
            }

            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    const q = encodeURIComponent(input.value || '');
                    fetchTable('{{ route('employees.search') }}?q=' + q);
                }, 250);
            });

            container.addEventListener('click', function (event) {
                const link = event.target.closest('a[href]');
                if (!link) return;
                const href = link.getAttribute('href') || '';
                if (!href.includes('page=')) return;
                event.preventDefault();
                const q = encodeURIComponent(input.value || '');
                const separator = href.includes('?') ? '&' : '?';
                const url = href + separator + 'q=' + q;
                fetchTable(url);
            });
        })();
    </script>
</x-app-layout>

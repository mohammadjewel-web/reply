@php
    /** @var \App\Models\User $employee */
    /** @var int $totalMessagesSent */
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $messages */
    $canOpenInbox = auth()->user()?->allows('inbox.access') ?? false;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Employee profile') }}</h2>
    </x-slot>

    <div class="app-page app-page--narrow space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-[color:var(--app-text-muted)]">
                <a href="{{ route('employees.index') }}" class="font-semibold text-[color:var(--app-primary)] hover:underline">{{ __('← Back to employees') }}</a>
            </p>
            <a
                href="{{ route('employees.profile', $employee) }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-3 py-2 text-xs font-semibold text-[color:var(--app-text)] shadow-sm transition hover:bg-[color:var(--app-shell-bg)]"
            >
                {{ __('Refresh') }}
            </a>
        </div>

        <div class="app-card space-y-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3">
                    @php
                        $initial = strtoupper(mb_substr((string) $employee->name, 0, 1));
                    @endphp
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-[color:var(--app-primary-text)]" style="background: linear-gradient(145deg, var(--app-primary), var(--app-primary-hover))" aria-hidden="true">{{ $initial }}</span>
                    <div>
                        <h3 class="text-lg font-semibold text-[color:var(--app-text)]">{{ $employee->name }}</h3>
                        <p class="mt-1 text-sm text-[color:var(--app-text-muted)]">{{ $employee->email }}</p>
                        <p class="mt-2 text-xs text-[color:var(--app-text-muted)]">
                            {{ __('ID') }} {{ $employee->id }}
                            <span class="text-[color:var(--app-card-border)]" aria-hidden="true">·</span>
                            {{ __('Member since :date', ['date' => $employee->created_at?->translatedFormat('M j, Y') ?? '—']) }}
                        </p>
                    </div>
                </div>
                <div class="rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)]/80 px-4 py-3 text-center sm:text-end">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">{{ __('Messages sent') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-[color:var(--app-text)]">{{ number_format($totalMessagesSent) }}</p>
                    <p class="mt-1 text-[11px] text-[color:var(--app-text-muted)]">{{ __('Outbound replies from the inbox') }}</p>
                </div>
            </div>

            @if ($employee->roles->isNotEmpty())
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">{{ __('Roles') }}</p>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($employee->roles as $role)
                            <li class="rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)]/60 px-2.5 py-1 text-xs font-medium text-[color:var(--app-text)]">{{ $role->name }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-wrap gap-2 border-t border-[color:var(--app-card-border)] pt-4">
                <a href="{{ route('employees.edit', $employee) }}" class="app-btn-primary inline-flex items-center gap-2">
                    {{ __('Edit employee') }}
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] shadow-sm">
            <div class="border-b border-[color:var(--app-card-border)] px-4 py-3 sm:px-6">
                <h3 class="text-sm font-semibold text-[color:var(--app-text)]">{{ __('Sent messages') }}</h3>
                <p class="mt-1 text-xs text-[color:var(--app-text-muted)]">{{ __('Each row is an outbound message this team member sent. Open the inbox to see the full thread.') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[color:var(--app-card-border)] text-sm">
                    <thead>
                        <tr class="bg-[color:var(--app-shell-bg)] text-left text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">
                            <th scope="col" class="px-4 py-3 sm:px-6">{{ __('Sent') }}</th>
                            <th scope="col" class="px-4 py-3 sm:px-6">{{ __('Conversation') }}</th>
                            <th scope="col" class="min-w-[12rem] px-4 py-3 sm:px-6">{{ __('Message') }}</th>
                            <th scope="col" class="px-4 py-3 text-end sm:px-6">{{ __('Open') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--app-card-border)]">
                        @forelse ($messages as $message)
                            @php
                                $conv = $message->conversation;
                                $acct = $conv?->channelAccount;
                                $platformLabel = $conv?->platform === \App\Models\Conversation::PLATFORM_WHATSAPP ? __('WhatsApp') : ($conv?->platform === \App\Models\Conversation::PLATFORM_MESSENGER ? __('Messenger') : (string) ($conv?->platform ?? ''));
                            @endphp
                            <tr class="align-top transition hover:bg-[color:var(--app-shell-bg)]/60">
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-[color:var(--app-text-muted)] sm:px-6">
                                    {{ $message->sent_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    @if ($conv)
                                        <p class="font-medium text-[color:var(--app-text)]">{{ $conv->display_name ?: $conv->external_thread_key }}</p>
                                        <p class="mt-0.5 text-[11px] text-[color:var(--app-text-muted)]">
                                            {{ $platformLabel }}
                                            @if ($acct?->name)
                                                <span class="text-[color:var(--app-card-border)]" aria-hidden="true">·</span>
                                                {{ $acct->name }}
                                            @endif
                                        </p>
                                    @else
                                        <span class="text-[color:var(--app-text-muted)]">—</span>
                                    @endif
                                </td>
                                <td class="max-w-md px-4 py-3 text-[color:var(--app-text)] sm:px-6">
                                    <p class="whitespace-pre-wrap break-words">{{ \Illuminate\Support\Str::limit(trim((string) $message->body), 280) ?: '—' }}</p>
                                </td>
                                <td class="px-4 py-3 text-end sm:px-6">
                                    @if ($canOpenInbox && $conv)
                                        <a
                                            href="{{ route('inbox', array_filter(['conversation' => $conv->id, 'account' => $conv->channel_account_id])) }}"
                                            class="text-xs font-semibold text-[color:var(--app-primary)] hover:underline"
                                        >{{ __('Inbox') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-[color:var(--app-text-muted)]">{{ __('No sent messages yet for this employee.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($messages->hasPages())
                <div class="border-t border-[color:var(--app-card-border)] px-4 py-3 sm:px-6">
                    {{ $messages->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Conversation> $conversations */
    /** @var int|null $selectedConversationId */
    /** @var \Illuminate\Http\Request $filterRequest */
    $selectedConversationId = $selectedConversationId ?? null;
    $inboxLink = static function (\App\Models\Conversation $c) use ($filterRequest) {
        return route('inbox', array_filter([
            'conversation' => $c->id,
            'assignee' => $filterRequest->query('assignee'),
            'account' => $filterRequest->query('account'),
        ], fn ($v) => $v !== null && $v !== ''));
    };
@endphp
@forelse ($conversations as $c)
    <a
        href="{{ $inboxLink($c) }}"
        class="js-inbox-thread-link group flex gap-3 border-b border-slate-100/90 px-3 py-3 transition-colors hover:bg-slate-50/95 sm:px-4 {{ (int) $selectedConversationId === (int) $c->id ? 'border-s-[3px] border-s-emerald-600 bg-emerald-50/95 ring-1 ring-inset ring-emerald-600/10' : 'border-s-[3px] border-s-transparent' }}"
    >
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-base font-semibold text-white shadow-md ring-2 ring-white
            {{ $c->platform === 'whatsapp' ? 'bg-gradient-to-br from-[#25d366] to-[#128c7e]' : 'bg-gradient-to-br from-[#0084ff] to-[#0064d1]' }}">
            {{ $c->inboxContactAvatarLetter() }}
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <span class="truncate font-semibold text-[15px] leading-snug text-slate-900 group-hover:text-slate-950">{{ $c->inboxContactTitle() }}</span>
                <div class="flex shrink-0 flex-col items-end gap-0.5">
                    <span class="text-[10px] tabular-nums text-slate-400">{{ $c->last_message_at?->format('g:i A') ?? '—' }}</span>
                    <span class="rounded px-1 py-px text-[9px] font-bold uppercase tracking-wide {{ $c->platform === 'whatsapp' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ $c->platform === 'whatsapp' ? 'WA' : 'MS' }}
                    </span>
                </div>
            </div>
            @if ($c->channelAccount)
                <p class="mt-0.5 truncate text-[11px] font-medium text-slate-500">{{ $c->channelAccount->name }}</p>
            @else
                <p class="mt-0.5 truncate text-[11px] font-medium text-amber-700/90">{{ __('Connection removed — history kept') }}</p>
            @endif
            @if (($preview = $c->inboxListPreview()) !== '')
                <p class="mt-1 line-clamp-2 text-[12px] leading-snug text-slate-600">{{ $preview }}</p>
            @endif
            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                @if ($c->assignee)
                    <span class="inline-flex max-w-full items-center gap-0.5 truncate rounded-full bg-violet-100 px-1.5 py-0.5 text-[10px] font-medium text-violet-800">
                        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="truncate">{{ $c->assignee->name }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-900">
                        {{ __('Unassigned') }}
                    </span>
                @endif
                <span class="text-[10px] text-slate-400">{{ $c->last_message_at?->diffForHumans() ?? '—' }}</span>
            </div>
        </div>
    </a>
@empty
    <p class="px-4 py-10 text-center text-sm text-slate-500">{{ __('No conversations match these filters.') }}</p>
@endforelse

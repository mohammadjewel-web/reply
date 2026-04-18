@php
    /** @var \App\Models\ChannelMessage $m */
    /** @var \App\Models\Conversation $active */
    $isOutbound = $m->direction === 'outbound';
    if ($active->platform === 'whatsapp') {
        $bubbleOut = 'bg-[#d9fdd3] text-slate-900 rounded-tr-sm';
        $bubbleIn = 'border border-slate-100/80 bg-white text-slate-900 rounded-tl-sm shadow-sm';
    } else {
        $bubbleOut = 'bg-[#0084ff] text-white rounded-tr-sm';
        $bubbleIn = 'border border-slate-200/80 bg-slate-100 text-slate-900 rounded-tl-sm';
    }
    $om = is_array($m->payload) ? ($m->payload['outbound_media'] ?? null) : null;
    $mediaPath = is_array($om) && ! empty($om['path']) ? $om['path'] : null;
    $mediaKind = is_array($om) ? ($om['kind'] ?? '') : '';
    $mediaUrl = $mediaPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($mediaPath) : null;
@endphp
<div class="flex w-full {{ $isOutbound ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $m->id }}">
    <div class="flex max-w-[min(100%,28rem)] {{ $isOutbound ? 'flex-row-reverse' : 'flex-row' }} items-end gap-2">
        @unless ($isOutbound)
            <div class="mb-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white
                {{ $active->platform === 'whatsapp' ? 'bg-green-600' : 'bg-blue-600' }}">
                {{ $active->inboxContactAvatarLetter() }}
            </div>
        @endunless
        <div class="group relative">
            <div
                class="inline-block max-w-full rounded-2xl px-3.5 py-2 text-[15px] leading-snug {{ $isOutbound ? $bubbleOut : $bubbleIn }}"
                style="word-break: break-word;"
            >
                @if ($mediaUrl && $mediaKind === 'image')
                    <a href="{{ $mediaUrl }}" target="_blank" rel="noopener noreferrer" class="block">
                        <img src="{{ $mediaUrl }}" alt="" class="max-h-64 max-w-full rounded-lg object-contain" loading="lazy" />
                    </a>
                    @if (filled($m->body) && ! str_starts_with((string) $m->body, '['))
                        <p class="mt-2 whitespace-pre-wrap">{{ $m->body }}</p>
                    @endif
                @elseif ($mediaUrl && $mediaKind === 'video')
                    <video src="{{ $mediaUrl }}" controls class="max-h-64 max-w-full rounded-lg" preload="metadata"></video>
                    @if (filled($m->body) && ! str_starts_with((string) $m->body, '['))
                        <p class="mt-2 whitespace-pre-wrap">{{ $m->body }}</p>
                    @endif
                @elseif ($mediaUrl && in_array($mediaKind, ['audio', 'ptt'], true))
                    <audio src="{{ $mediaUrl }}" controls class="w-full min-w-[12rem] max-w-full"></audio>
                    @if (filled($m->body) && ! str_starts_with((string) $m->body, '['))
                        <p class="mt-2 whitespace-pre-wrap">{{ $m->body }}</p>
                    @endif
                @elseif ($mediaUrl && $mediaKind === 'file')
                    <a href="{{ $mediaUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium underline decoration-2 underline-offset-2">
                        {{ __('Download attachment') }}
                    </a>
                @else
                    <p class="whitespace-pre-wrap">{{ $m->body }}</p>
                @endif
            </div>
            <div class="mt-1 flex items-center gap-1.5 px-1 {{ $isOutbound ? 'justify-end' : 'justify-start' }}">
                <span class="text-[11px] tabular-nums text-slate-500">
                    {{ $m->sent_at?->format('g:i A') }}
                </span>
                @if ($isOutbound && $m->user)
                    <span class="text-[11px] text-slate-400">· {{ $m->user->name }}</span>
                @elseif ($isOutbound && ! $m->user && $active->platform === 'whatsapp')
                    <span class="text-[11px] text-slate-400">· {{ __('Sent from WhatsApp') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

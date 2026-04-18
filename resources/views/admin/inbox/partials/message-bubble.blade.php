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
    $im = is_array($m->payload) ? ($m->payload['inbound_media'] ?? null) : null;
    $media = is_array($om) ? $om : (is_array($im) ? $im : null);
    $mediaPath = is_array($media) && ! empty($media['path']) ? $media['path'] : null;
    $mediaKind = is_array($media) ? ($media['kind'] ?? '') : '';
    $mediaUrl = $mediaPath
        ? route('storage.public_file', ['path' => ltrim(str_replace('\\', '/', $mediaPath), '/')], absolute: false)
        : null;
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
                    <a href="{{ $mediaUrl }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl ring-1 ring-black/10">
                        <img
                            src="{{ $mediaUrl }}"
                            alt=""
                            class="max-h-72 max-w-full cursor-zoom-in object-contain transition hover:opacity-95"
                            loading="lazy"
                            decoding="async"
                        />
                    </a>
                    @if (filled($m->body) && ! str_starts_with((string) $m->body, '['))
                        <p class="mt-2 whitespace-pre-wrap">{{ $m->body }}</p>
                    @endif
                @elseif ($mediaUrl && $mediaKind === 'video')
                    <div class="overflow-hidden rounded-xl bg-black/90 ring-1 ring-black/15">
                        <video
                            src="{{ $mediaUrl }}"
                            controls
                            playsinline
                            preload="metadata"
                            class="max-h-72 w-full max-w-full object-contain"
                        >{{ __('Your browser does not support video.') }}</video>
                    </div>
                    @if (filled($m->body) && ! str_starts_with((string) $m->body, '['))
                        <p class="mt-2 whitespace-pre-wrap">{{ $m->body }}</p>
                    @endif
                @elseif ($mediaUrl && in_array($mediaKind, ['audio', 'ptt'], true))
                    <div class="rounded-xl bg-slate-100/90 px-2 py-2 ring-1 ring-slate-200/80">
                        <audio src="{{ $mediaUrl }}" controls preload="metadata" class="w-full min-w-[12rem] max-w-full">{{ __('Your browser does not support audio.') }}</audio>
                    </div>
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Models\Conversation;
use App\Services\WhatsappCloudService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InboxMessageMediaController extends Controller
{
    /**
     * Serve chat media for staff (session auth). Resolves files on the public disk, or lazily
     * downloads WhatsApp Cloud media when the webhook payload has a media id but no stored file.
     */
    public function show(ChannelMessage $message, WhatsappCloudService $whatsapp): StreamedResponse|Response
    {
        $message->load(['conversation.channelAccount']);

        $path = $this->resolveStoredMediaPath($message);
        if ($path !== null && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path);
        }

        $conversation = $message->conversation;
        $account = $conversation?->channelAccount;
        if (
            $account
            && $account->type === ChannelAccount::TYPE_WHATSAPP
            && $conversation->platform === Conversation::PLATFORM_WHATSAPP
        ) {
            $path = $this->tryHydrateWhatsappCloudMedia($message, $account, $whatsapp);
            if ($path !== null && Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->response($path);
            }
        }

        abort(404);
    }

    private function resolveStoredMediaPath(ChannelMessage $message): ?string
    {
        $p = $message->payload;
        if (! is_array($p)) {
            return null;
        }
        $om = $p['outbound_media'] ?? null;
        $im = $p['inbound_media'] ?? null;
        if (is_array($om) && filled($om['path'] ?? null)) {
            return (string) $om['path'];
        }
        if (is_array($im) && filled($im['path'] ?? null)) {
            return (string) $im['path'];
        }

        return null;
    }

    private function tryHydrateWhatsappCloudMedia(ChannelMessage $message, ChannelAccount $account, WhatsappCloudService $whatsapp): ?string
    {
        $p = $message->payload;
        if (! is_array($p)) {
            return null;
        }
        $type = $p['type'] ?? '';
        $mediaId = match ($type) {
            'image' => $p['image']['id'] ?? null,
            'video' => $p['video']['id'] ?? null,
            'audio' => $p['audio']['id'] ?? null,
            'document' => $p['document']['id'] ?? null,
            'sticker' => $p['sticker']['id'] ?? null,
            default => null,
        };
        if (! is_string($mediaId) || $mediaId === '') {
            return null;
        }

        $merged = $whatsapp->attachInboundMediaIfPresent($account, $p);
        $im = $merged['inbound_media'] ?? null;
        if (! is_array($im) || empty($im['path'])) {
            return null;
        }

        $message->forceFill(['payload' => $merged])->save();

        return (string) $im['path'];
    }
}

<?php

namespace App\Services;

use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Models\Conversation;
use Illuminate\Support\Carbon;

class MessageIngestService
{
    public function __construct(
        private InboundMessageNotifier $inboundNotifier,
    ) {}

    public function ingestInbound(
        ChannelAccount $account,
        string $externalThreadKey,
        ?string $displayName,
        ?string $body,
        ?string $externalMessageId,
        ?array $payload,
        ?Carbon $sentAt = null,
    ): ChannelMessage {
        $platform = $account->type === ChannelAccount::TYPE_WHATSAPP
            ? Conversation::PLATFORM_WHATSAPP
            : Conversation::PLATFORM_MESSENGER;

        $conversation = Conversation::query()->firstOrCreate(
            [
                'channel_account_id' => $account->id,
                'external_thread_key' => $externalThreadKey,
            ],
            [
                'platform' => $platform,
                'display_name' => $displayName,
                'metadata' => [],
            ]
        );

        if ($displayName && $conversation->display_name !== $displayName) {
            $conversation->update(['display_name' => $displayName]);
        }

        if ($externalMessageId) {
            $existing = ChannelMessage::query()
                ->where('conversation_id', $conversation->id)
                ->where('external_message_id', $externalMessageId)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $sentAt ??= now();

        $message = ChannelMessage::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => ChannelMessage::DIRECTION_INBOUND,
            'external_message_id' => $externalMessageId,
            'body' => $body,
            'payload' => $payload,
            'sent_at' => $sentAt,
        ]);

        $conversation->update(['last_message_at' => $sentAt]);

        $this->inboundNotifier->notify($message);

        return $message;
    }
}

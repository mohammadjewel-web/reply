<?php

namespace App\Services;

use App\Models\ChannelMessage;
use App\Models\User;
use App\Notifications\InboundMessageNotification;

class InboundMessageNotifier
{
    public function notify(ChannelMessage $message): void
    {
        if ($message->direction !== ChannelMessage::DIRECTION_INBOUND) {
            return;
        }

        $message->loadMissing('conversation');
        $conversation = $message->conversation;

        User::query()
            ->inboxNotifiable()
            ->where('is_active', true)
            ->each(function (User $user) use ($message, $conversation) {
                $user->notify(new InboundMessageNotification($message, $conversation));
            });
    }
}

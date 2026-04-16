<?php

namespace App\Notifications;

use App\Models\ChannelMessage;
use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class InboundMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ChannelMessage $message,
        public Conversation $conversation,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $preview = Str::limit((string) ($this->message->body ?? ''), 140);

        return [
            'category' => 'inbound_message',
            'title' => __('New message'),
            'body' => $preview !== '' ? $preview : __('(no text)'),
            'sender' => $this->conversation->display_name ?? __('Unknown'),
            'platform' => $this->conversation->platform,
            'conversation_id' => $this->conversation->id,
            'message_id' => $this->message->id,
            'href' => route('inbox', ['conversation' => $this->conversation->id]),
        ];
    }
}

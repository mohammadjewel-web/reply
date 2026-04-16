<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    public const PLATFORM_WHATSAPP = 'whatsapp';

    public const PLATFORM_MESSENGER = 'messenger';

    protected $fillable = [
        'channel_account_id',
        'assigned_to_user_id',
        'platform',
        'external_thread_key',
        'display_name',
        'metadata',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function channelMessages(): HasMany
    {
        return $this->hasMany(ChannelMessage::class)->orderBy('sent_at')->orderBy('id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChannelAccount extends Model
{
    public const TYPE_WHATSAPP = 'whatsapp';

    public const TYPE_MESSENGER = 'messenger';

    protected $fillable = [
        'type',
        'name',
        'is_active',
        'external_id',
        'access_token',
        'waba_id',
        'sort_order',
        'baileys_session_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'access_token' => 'encrypted',
            'sort_order' => 'integer',
            'baileys_session_user_id' => 'integer',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function whatsappLinkSessions(): HasMany
    {
        return $this->hasMany(WhatsappLinkSession::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWhatsapp($query)
    {
        return $query->where('type', self::TYPE_WHATSAPP);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeMessenger($query)
    {
        return $query->where('type', self::TYPE_MESSENGER);
    }

    public function label(): string
    {
        return $this->name;
    }
}

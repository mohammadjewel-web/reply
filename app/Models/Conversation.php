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

    public function resolvedWhatsappPhone(): ?string
    {
        if ($this->platform !== self::PLATFORM_WHATSAPP) {
            return null;
        }
        $meta = $this->metadata ?? [];
        if (! empty($meta['wa_e164']) && is_string($meta['wa_e164'])) {
            return $meta['wa_e164'];
        }
        $key = (string) $this->external_thread_key;
        if (preg_match('/^\d{8,15}$/', $key)) {
            return '+'.$key;
        }

        return null;
    }

    public function inboxContactTitle(): string
    {
        if ($this->platform === self::PLATFORM_WHATSAPP) {
            $phone = $this->resolvedWhatsappPhone();
            if ($phone !== null) {
                return $phone;
            }
        }

        return $this->display_name ?? $this->external_thread_key;
    }

    /**
     * Profile name (or similar) when the title is already the phone number.
     */
    public function inboxContactSecondaryLine(): ?string
    {
        if ($this->platform !== self::PLATFORM_WHATSAPP) {
            return null;
        }
        $phone = $this->resolvedWhatsappPhone();
        $name = $this->display_name;
        if (! is_string($name) || $name === '') {
            return null;
        }
        if ($phone !== null) {
            $compact = preg_replace('/\D+/', '', $phone) ?? '';
            $nameDigits = preg_replace('/\D+/', '', $name) ?? '';
            if ($name === $phone || ($compact !== '' && $nameDigits === $compact)) {
                return null;
            }
        }

        return $name;
    }

    public function inboxContactAvatarLetter(): string
    {
        $phone = $this->resolvedWhatsappPhone();
        if ($phone !== null && preg_match('/\+(\d)/', $phone, $m)) {
            return $m[1];
        }
        $title = (string) $this->inboxContactTitle();
        $ch = mb_substr($title, 0, 1);

        return $ch !== '' ? mb_strtoupper($ch) : '?';
    }
}

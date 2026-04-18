<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

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

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChannelMessage::class)->latestOfMany();
    }

    /**
     * Short preview for the inbox sidebar (last message in thread).
     */
    public function inboxListPreview(): string
    {
        if (! $this->relationLoaded('latestMessage')) {
            return '';
        }
        $m = $this->latestMessage;
        if (! $m) {
            return '';
        }
        $raw = trim((string) $m->body);
        if ($raw === '') {
            return '';
        }
        $prefix = $m->direction === ChannelMessage::DIRECTION_OUTBOUND
            ? __('You').': '
            : '';

        return $prefix.Str::limit($raw, 64, '…');
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

    /**
     * True when $name is a real label (contact / profile / push name), not just the raw number.
     */
    public function isLikelyContactDisplayName(?string $name, ?string $phoneE164): bool
    {
        if (! is_string($name) || trim($name) === '') {
            return false;
        }
        $t = trim($name);
        if (preg_match('/^\d+$/', preg_replace('/\s+/', '', $t))) {
            return false;
        }
        if ($phoneE164 !== null) {
            $nameDigits = preg_replace('/\D+/', '', $t) ?? '';
            $phoneDigits = preg_replace('/\D+/', '', $phoneE164) ?? '';
            if ($phoneDigits !== '' && $nameDigits === $phoneDigits) {
                return false;
            }
        }

        return true;
    }

    /**
     * Primary header / list label: contact name when known (phonebook / profile / push), else WhatsApp E.164, else PSID or fallback.
     */
    public function inboxContactTitle(): string
    {
        if ($this->platform === self::PLATFORM_WHATSAPP) {
            $phone = $this->resolvedWhatsappPhone();
            $name = $this->display_name;
            if ($this->isLikelyContactDisplayName($name, $phone)) {
                return trim((string) $name);
            }
            if ($phone !== null) {
                return $phone;
            }
        }

        if ($this->platform === self::PLATFORM_MESSENGER) {
            $name = $this->display_name;
            if ($this->isLikelyContactDisplayName($name, null)) {
                return trim((string) $name);
            }
        }

        return $this->display_name ?? $this->external_thread_key;
    }

    /**
     * Second line under title: opposite of title (phone under name, or name under phone) for WhatsApp; PSID when title is Messenger name.
     */
    public function inboxContactSecondaryLine(): ?string
    {
        if ($this->platform === self::PLATFORM_WHATSAPP) {
            $phone = $this->resolvedWhatsappPhone();
            $name = $this->display_name;
            $hasName = $this->isLikelyContactDisplayName($name, $phone);
            if ($hasName && $phone !== null) {
                return $phone;
            }
            if (! $hasName && is_string($name) && trim($name) !== '' && $phone !== null) {
                $compact = preg_replace('/\D+/', '', $phone) ?? '';
                $nameDigits = preg_replace('/\D+/', '', $name) ?? '';
                if ($compact !== '' && $nameDigits !== $compact) {
                    return trim($name);
                }
            }

            return null;
        }

        if ($this->platform === self::PLATFORM_MESSENGER) {
            $name = $this->display_name;
            if ($this->isLikelyContactDisplayName($name, null)) {
                return __('PSID: :id', ['id' => $this->external_thread_key]);
            }

            return null;
        }

        return null;
    }

    /**
     * Plain-text chat header subtitle (platform · line · extra) for live polling.
     */
    public function inboxHeaderSubtitlePlain(): string
    {
        $this->loadMissing('channelAccount:id,name,type,is_active');

        $parts = [];
        if ($this->platform === self::PLATFORM_WHATSAPP) {
            $parts[] = __('WhatsApp');
        } else {
            $parts[] = __('Messenger');
        }

        if ($this->channelAccount) {
            $parts[] = $this->channelAccount->name;
        } else {
            $parts[] = __('Connection removed');
        }

        if ($this->platform === self::PLATFORM_WHATSAPP && ($wa = $this->inboxContactSecondaryLine())) {
            $parts[] = $wa;
        } elseif ($this->platform === self::PLATFORM_MESSENGER) {
            if ($ms = $this->inboxContactSecondaryLine()) {
                $parts[] = $ms;
            } else {
                $parts[] = __('PSID: :id', ['id' => $this->external_thread_key]);
            }
        }

        return implode(' · ', $parts);
    }

    public function inboxContactAvatarLetter(): string
    {
        $phone = $this->resolvedWhatsappPhone();
        $name = $this->display_name;

        if ($this->platform === self::PLATFORM_WHATSAPP && $this->isLikelyContactDisplayName($name, $phone)) {
            $ch = mb_substr(trim((string) $name), 0, 1);

            return $ch !== '' ? mb_strtoupper($ch) : '?';
        }

        if ($this->platform === self::PLATFORM_MESSENGER && $this->isLikelyContactDisplayName($name, null)) {
            $ch = mb_substr(trim((string) $name), 0, 1);

            return $ch !== '' ? mb_strtoupper($ch) : '?';
        }

        if ($phone !== null && preg_match('/\+(\d)/', $phone, $m)) {
            return $m[1];
        }

        $title = (string) $this->inboxContactTitle();
        $ch = mb_substr($title, 0, 1);

        return $ch !== '' ? mb_strtoupper($ch) : '?';
    }
}

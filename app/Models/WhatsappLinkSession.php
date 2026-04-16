<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WhatsappLinkSession extends Model
{
    protected $fillable = [
        'channel_account_id',
        'token',
        'status',
        'access_token',
        'phone_number_id',
        'waba_id',
        'error_message',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'access_token' => 'encrypted',
        ];
    }

    public static function start(int $ttlMinutes = 30): self
    {
        return self::query()->create([
            'token' => (string) Str::uuid(),
            'status' => 'pending',
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function markLinked(string $accessToken, ?string $phoneNumberId, ?string $wabaId): void
    {
        $this->update([
            'status' => 'linked',
            'access_token' => $accessToken,
            'phone_number_id' => $phoneNumberId,
            'waba_id' => $wabaId,
            'error_message' => null,
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }
}

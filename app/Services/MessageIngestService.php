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
        ?string $baileysRemoteJid = null,
        string $direction = ChannelMessage::DIRECTION_INBOUND,
        bool $notify = true,
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

        if ($direction === ChannelMessage::DIRECTION_INBOUND && $displayName && $conversation->display_name !== $displayName) {
            $conversation->update(['display_name' => $displayName]);
        }

        $this->syncBaileysRemoteJid($conversation, $baileysRemoteJid);
        $this->syncWhatsappDisplayPhone($conversation, $externalThreadKey, $baileysRemoteJid);

        if ($externalMessageId) {
            $existing = ChannelMessage::query()
                ->where('conversation_id', $conversation->id)
                ->where('external_message_id', $externalMessageId)
                ->first();
            if ($existing) {
                $this->syncBaileysRemoteJid($existing->conversation, $baileysRemoteJid);
                $this->syncWhatsappDisplayPhone($existing->conversation, $externalThreadKey, $baileysRemoteJid);
                $this->upgradeExistingPayloadWithInboundMedia($existing, $payload);

                return $existing;
            }
        }

        $sentAt ??= now();

        $message = ChannelMessage::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => $direction,
            'external_message_id' => $externalMessageId,
            'body' => $body,
            'payload' => $payload,
            'sent_at' => $sentAt,
            'user_id' => null,
        ]);

        $conversation->update(['last_message_at' => $sentAt]);

        if ($notify && $direction === ChannelMessage::DIRECTION_INBOUND) {
            $this->inboundNotifier->notify($message);
        }

        return $message;
    }

    private function syncBaileysRemoteJid(Conversation $conversation, ?string $remoteJid): void
    {
        $remoteJid = $remoteJid !== null ? trim($remoteJid) : '';
        if ($remoteJid === '' || ! str_contains($remoteJid, '@')) {
            return;
        }
        if ($conversation->platform !== Conversation::PLATFORM_WHATSAPP) {
            return;
        }
        $meta = $conversation->metadata ?? [];
        if (($meta['baileys_remote_jid'] ?? null) === $remoteJid) {
            return;
        }
        $meta['baileys_remote_jid'] = $remoteJid;
        $conversation->update(['metadata' => $meta]);
    }

    /**
     * Store E.164-style label for inbox headers (Cloud + Baileys), independent of profile display names.
     */
    private function syncWhatsappDisplayPhone(Conversation $conversation, string $externalThreadKey, ?string $baileysRemoteJid): void
    {
        if ($conversation->platform !== Conversation::PLATFORM_WHATSAPP) {
            return;
        }
        $digits = null;
        if (preg_match('/^\d{8,15}$/', $externalThreadKey)) {
            $digits = $externalThreadKey;
        }
        if ($digits === null && $baileysRemoteJid && str_ends_with($baileysRemoteJid, '@s.whatsapp.net')) {
            $local = explode('@', $baileysRemoteJid, 2)[0];
            $d = preg_replace('/\D+/', '', $local) ?? '';
            if ($d !== '' && strlen($d) >= 8) {
                $digits = $d;
            }
        }
        if ($digits === null) {
            return;
        }
        $e164 = '+'.$digits;
        $meta = $conversation->metadata ?? [];
        if (($meta['wa_e164'] ?? null) === $e164) {
            return;
        }
        $meta['wa_e164'] = $e164;
        $conversation->update(['metadata' => $meta]);
    }

    /**
     * Baileys/WhatsApp may deliver the same external_message_id twice (e.g. notify then append, or JSON
     * before multipart). The first row can lack inbound_media; merge when the new payload has a file path.
     */
    private function upgradeExistingPayloadWithInboundMedia(ChannelMessage $existing, ?array $incoming): void
    {
        if ($incoming === null) {
            return;
        }
        $newIm = $incoming['inbound_media'] ?? null;
        if (! is_array($newIm) || empty($newIm['path'])) {
            return;
        }
        $cur = $existing->payload;
        $cur = is_array($cur) ? $cur : [];
        $oldIm = $cur['inbound_media'] ?? null;
        if (is_array($oldIm) && filled($oldIm['path'] ?? null)) {
            return;
        }
        $cur['inbound_media'] = $newIm;
        $existing->forceFill(['payload' => $cur])->save();
    }
}

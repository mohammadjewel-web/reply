<?php

namespace Tests\Feature;

use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Services\MessageIngestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageIngestInboundMediaMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_ingest_with_same_external_id_merges_inbound_media(): void
    {
        $user = User::factory()->create();
        $account = ChannelAccount::query()->create([
            'type' => ChannelAccount::TYPE_WHATSAPP,
            'name' => 'WA',
            'is_active' => true,
            'external_id' => null,
            'baileys_session_user_id' => $user->id,
            'sort_order' => 0,
        ]);

        $conversation = Conversation::query()->create([
            'channel_account_id' => $account->id,
            'external_thread_key' => '15551234567',
            'platform' => Conversation::PLATFORM_WHATSAPP,
            'display_name' => 'Test',
            'metadata' => [],
        ]);

        $ingest = app(MessageIngestService::class);

        $ingest->ingestInbound(
            $account,
            '15551234567',
            'Contact',
            '[image]',
            'MSG_DUP_1',
            ['baileys_type' => 'notify'],
        );

        $this->assertDatabaseCount('channel_messages', 1);

        $ingest->ingestInbound(
            $account,
            '15551234567',
            'Contact',
            '[image]',
            'MSG_DUP_1',
            [
                'baileys_type' => 'append',
                'inbound_media' => [
                    'kind' => 'image',
                    'path' => 'chat-inbound/baileys/'.$account->id.'/fake.jpg',
                    'mime' => 'image/jpeg',
                    'via' => 'baileys_inbound',
                ],
            ],
        );

        $this->assertDatabaseCount('channel_messages', 1);

        $row = ChannelMessage::query()->first();
        $this->assertNotNull($row);
        $this->assertSame('MSG_DUP_1', $row->external_message_id);
        $p = $row->payload;
        $this->assertIsArray($p);
        $this->assertArrayHasKey('inbound_media', $p);
        $this->assertSame('image', $p['inbound_media']['kind'] ?? null);
        $this->assertSame(
            'chat-inbound/baileys/'.$account->id.'/fake.jpg',
            $p['inbound_media']['path'] ?? null
        );
    }
}

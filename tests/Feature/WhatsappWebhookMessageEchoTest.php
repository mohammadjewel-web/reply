<?php

namespace Tests\Feature;

use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappWebhookMessageEchoTest extends TestCase
{
    use RefreshDatabase;

    public function test_smb_message_echoes_are_stored_as_outbound(): void
    {
        $account = ChannelAccount::query()->create([
            'type' => ChannelAccount::TYPE_WHATSAPP,
            'name' => 'Test WA',
            'is_active' => true,
            'external_id' => '1234567890',
            'sort_order' => 0,
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'WABA_ID',
                'changes' => [[
                    'field' => 'smb_message_echoes',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '15550001111',
                            'phone_number_id' => '1234567890',
                        ],
                        'message_echoes' => [[
                            'from' => '15550001111',
                            'to' => '15559876543',
                            'id' => 'wamid.echo123',
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'Replied from phone'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/webhooks/whatsapp', $payload)->assertOk();

        $this->assertDatabaseHas('conversations', [
            'channel_account_id' => $account->id,
            'external_thread_key' => '15559876543',
        ]);

        $this->assertDatabaseHas('channel_messages', [
            'direction' => ChannelMessage::DIRECTION_OUTBOUND,
            'external_message_id' => 'wamid.echo123',
            'body' => 'Replied from phone',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsappBaileysInboundMediaWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_multipart_inbound_media_is_stored_and_payload_has_inbound_media(): void
    {
        Storage::fake('public');

        config([
            'services.baileys.enabled' => true,
            'services.baileys.secret' => 'test-baileys-secret',
        ]);

        $user = User::factory()->create();

        $account = ChannelAccount::query()->create([
            'type' => ChannelAccount::TYPE_WHATSAPP,
            'name' => 'Test WA',
            'is_active' => true,
            'external_id' => null,
            'baileys_session_user_id' => $user->id,
            'sort_order' => 0,
        ]);

        $image = UploadedFile::fake()->image('photo.jpg', 40, 40);

        $response = $this->withHeaders(['X-Baileys-Secret' => 'test-baileys-secret'])
            ->post('/webhooks/whatsapp-baileys', [
                'session_key' => 'wa-'.$account->id.'-u-'.$user->id,
                'from' => '15559876543@s.whatsapp.net',
                'routing_jid' => '15559876543@s.whatsapp.net',
                'from_me' => '0',
                'body' => '[image]',
                'external_message_id' => 'BAEYS_MEDIA_1',
                'message_timestamp' => (string) now()->timestamp,
                'media_kind' => 'image',
                'media_mime' => 'image/jpeg',
                'payload_json' => json_encode(['baileys_type' => 'notify']),
                'media' => $image,
            ]);

        $response->assertOk();

        $message = ChannelMessage::query()->where('external_message_id', 'BAEYS_MEDIA_1')->first();
        $this->assertNotNull($message);
        $payload = $message->payload;
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('inbound_media', $payload);
        $this->assertSame('image', $payload['inbound_media']['kind'] ?? null);
        $this->assertNotEmpty($payload['inbound_media']['path'] ?? null);

        Storage::disk('public')->assertExists($payload['inbound_media']['path']);
    }
}

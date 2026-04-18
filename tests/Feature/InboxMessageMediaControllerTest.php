<?php

namespace Tests\Feature;

use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InboxMessageMediaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_staff_can_stream_stored_inbox_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('chat-inbound/baileys/1/abc.jpg', 'fake-image-bytes');

        $account = ChannelAccount::query()->create([
            'type' => ChannelAccount::TYPE_WHATSAPP,
            'name' => 'WA',
            'is_active' => true,
            'external_id' => '123',
            'sort_order' => 0,
        ]);

        $conversation = Conversation::query()->create([
            'channel_account_id' => $account->id,
            'external_thread_key' => '15550001111',
            'platform' => Conversation::PLATFORM_WHATSAPP,
            'display_name' => 'C',
            'metadata' => [],
        ]);

        $message = ChannelMessage::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => ChannelMessage::DIRECTION_INBOUND,
            'external_message_id' => 'mid1',
            'body' => '[image]',
            'payload' => [
                'inbound_media' => [
                    'kind' => 'image',
                    'path' => 'chat-inbound/baileys/1/abc.jpg',
                    'mime' => 'image/jpeg',
                ],
            ],
            'sent_at' => now(),
        ]);

        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('inbox.message.media', $message));

        $response->assertOk();
        $this->assertSame('fake-image-bytes', $response->streamedContent());
    }

    public function test_guests_cannot_access_inbox_media(): void
    {
        Storage::fake('public');

        $account = ChannelAccount::query()->create([
            'type' => ChannelAccount::TYPE_WHATSAPP,
            'name' => 'WA',
            'is_active' => true,
            'external_id' => '123',
            'sort_order' => 0,
        ]);

        $conversation = Conversation::query()->create([
            'channel_account_id' => $account->id,
            'external_thread_key' => '15550001111',
            'platform' => Conversation::PLATFORM_WHATSAPP,
            'display_name' => 'C',
            'metadata' => [],
        ]);

        $message = ChannelMessage::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => ChannelMessage::DIRECTION_INBOUND,
            'body' => '[image]',
            'payload' => null,
            'sent_at' => now(),
        ]);

        $this->get(route('inbox.message.media', $message))->assertRedirect(route('login'));
    }
}

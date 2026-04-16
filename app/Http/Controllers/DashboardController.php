<?php

namespace App\Http\Controllers;

use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Models\Conversation;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $hour = (int) now()->format('G');
        $greeting = match (true) {
            $hour < 12 => __('Good morning'),
            $hour < 17 => __('Good afternoon'),
            default => __('Good evening'),
        };

        $unreadNotifications = $user->unreadNotifications()->count();

        $stats = null;
        $recentConversations = collect();
        $channelSummary = null;

        if ($user->allows('inbox.access')) {
            $stats = [
                'conversations' => Conversation::query()->count(),
                'messages' => ChannelMessage::query()->count(),
                'whatsapp_conversations' => Conversation::query()
                    ->where('platform', Conversation::PLATFORM_WHATSAPP)
                    ->count(),
                'messenger_conversations' => Conversation::query()
                    ->where('platform', Conversation::PLATFORM_MESSENGER)
                    ->count(),
                'assigned_to_me' => Conversation::query()
                    ->where('assigned_to_user_id', $user->id)
                    ->count(),
                'unassigned' => Conversation::query()
                    ->whereNull('assigned_to_user_id')
                    ->count(),
                'messages_today' => ChannelMessage::query()
                    ->where('sent_at', '>=', now()->startOfDay())
                    ->count(),
            ];

            $recentConversations = Conversation::query()
                ->with(['channelAccount:id,name,type'])
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->limit(6)
                ->get();
        }

        if ($user->allows('connections.manage')) {
            $channelSummary = [
                'whatsapp_active' => ChannelAccount::query()->active()->whatsapp()->count(),
                'messenger_active' => ChannelAccount::query()->active()->messenger()->count(),
            ];
        }

        return view('dashboard', [
            'greeting' => $greeting,
            'stats' => $stats,
            'recentConversations' => $recentConversations,
            'channelSummary' => $channelSummary,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }
}

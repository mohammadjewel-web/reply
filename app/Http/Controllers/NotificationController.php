<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $prefs = $user->notificationPreferencesResolved();

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->limit(25)
            ->get()
            ->map(function ($n) {
                /** @var DatabaseNotification $n */
                $data = $n->data;

                return [
                    'id' => $n->id,
                    'read_at' => $n->read_at?->toIso8601String(),
                    'created_at' => $n->created_at?->toIso8601String(),
                    'title' => $data['title'] ?? '',
                    'body' => $data['body'] ?? '',
                    'href' => $data['href'] ?? null,
                    'category' => $data['category'] ?? 'general',
                ];
            });

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
            'preferences' => $prefs,
            'server_time' => Carbon::now()->toIso8601String(),
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}

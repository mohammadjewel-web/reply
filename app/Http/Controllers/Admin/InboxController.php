<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Services\BaileysRelayService;
use App\Services\MessengerGraphService;
use App\Services\WhatsappCloudService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function index(Request $request): View
    {
        $query = Conversation::query()
            ->with(['channelAccount:id,name,type,is_active', 'assignee:id,name'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($request->filled('account')) {
            $query->where('channel_account_id', (int) $request->query('account'));
        }

        $assigneeFilter = $request->query('assignee', 'all');
        if ($assigneeFilter === 'me') {
            $query->where('assigned_to_user_id', $request->user()->id);
        } elseif ($assigneeFilter === 'unassigned') {
            $query->whereNull('assigned_to_user_id');
        } elseif (is_numeric($assigneeFilter)) {
            $query->where('assigned_to_user_id', (int) $assigneeFilter);
        }

        $conversations = $query->limit(250)->get();

        $activeId = $request->query('conversation');
        $active = $activeId
            ? Conversation::query()
                ->with(['channelAccount:id,name,type,is_active', 'assignee:id,name'])
                ->find($activeId)
            : $conversations->first();

        $messages = collect();
        if ($active) {
            $messages = $active->channelMessages()
                ->with('user:id,name')
                ->orderByDesc('sent_at')
                ->orderByDesc('id')
                ->limit(200)
                ->get()
                ->sortBy(fn (ChannelMessage $m) => [$m->sent_at?->timestamp ?? 0, $m->id])
                ->values();
        }

        $channelAccounts = ChannelAccount::query()
            ->active()
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $assignableUsers = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.inbox', [
            'conversations' => $conversations,
            'active' => $active,
            'messages' => $messages,
            'channelAccounts' => $channelAccounts,
            'assignableUsers' => $assignableUsers,
            'assigneeFilter' => $assigneeFilter,
            'selectedAccountId' => $request->query('account'),
        ]);
    }

    public function assign(Request $request, Conversation $conversation): RedirectResponse
    {
        $request->merge([
            'assigned_to_user_id' => $request->filled('assigned_to_user_id') ? $request->integer('assigned_to_user_id') : null,
        ]);

        $validated = $request->validate([
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $userId = $validated['assigned_to_user_id'];
        if ($userId !== null) {
            $assignee = User::query()->where('id', $userId)->where('is_active', true)->first();
            if (! $assignee) {
                return back()->withErrors(['assigned_to_user_id' => __('That team member is not active.')]);
            }
            $conversation->update(['assigned_to_user_id' => $assignee->id]);
        } else {
            $conversation->update(['assigned_to_user_id' => null]);
        }

        return redirect()->route('inbox', [
            'conversation' => $conversation->id,
            'assignee' => $request->query('assignee'),
            'account' => $request->query('account'),
        ]);
    }

    public function reply(
        Request $request,
        Conversation $conversation,
        WhatsappCloudService $whatsapp,
        MessengerGraphService $messenger,
        BaileysRelayService $baileysRelay,
    ): RedirectResponse {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4096'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $body = $validated['body'];
        $sentAt = now();

        $account = $conversation->channelAccount;
        if (! $account || ! $account->is_active) {
            return back()->withErrors(['body' => __('This conversation is not linked to an active channel account.')])->withInput();
        }

        if ($conversation->platform === Conversation::PLATFORM_WHATSAPP) {
            // Only treat Cloud API as available when *this connection* has token + phone id.
            // Global WHATSAPP_* .env placeholders must not force Cloud sends (invalid token → "Unauthorized")
            // when the inbox is actually using Baileys.
            $perAccountCloud = filled($account->access_token) && filled($account->external_id);
            $sessionUserId = $account->baileys_session_user_id ?? $user->id;
            $useBaileys = config('services.baileys.enabled')
                && ($account->baileys_session_user_id !== null || ! $perAccountCloud);

            if ($useBaileys) {
                $sessionKey = 'wa-'.$account->id.'-u-'.$sessionUserId;
                $remoteJid = $this->resolveBaileysRemoteJidForSend($conversation);
                $result = $baileysRelay->sendTextMessage(
                    $account,
                    $sessionKey,
                    $conversation->external_thread_key,
                    $body,
                    $remoteJid,
                );
            } else {
                $result = $whatsapp->sendTextMessageForChannel($account, $conversation->external_thread_key, $body);
            }

            if (! $result['ok']) {
                return back()->withErrors(['body' => $result['error'] ?? 'WhatsApp send failed'])->withInput();
            }
            ChannelMessage::query()->create([
                'conversation_id' => $conversation->id,
                'direction' => ChannelMessage::DIRECTION_OUTBOUND,
                'external_message_id' => $result['message_id'],
                'body' => $body,
                'payload' => $useBaileys ? ['via' => 'baileys'] : null,
                'sent_at' => $sentAt,
                'user_id' => $user->id,
            ]);
        } elseif ($conversation->platform === Conversation::PLATFORM_MESSENGER) {
            $result = $messenger->sendTextMessageForChannel($account, $conversation->external_thread_key, $body);
            if (! $result['ok']) {
                return back()->withErrors(['body' => $result['error'] ?? 'Messenger send failed'])->withInput();
            }
            ChannelMessage::query()->create([
                'conversation_id' => $conversation->id,
                'direction' => ChannelMessage::DIRECTION_OUTBOUND,
                'external_message_id' => $result['message_id'],
                'body' => $body,
                'payload' => null,
                'sent_at' => $sentAt,
                'user_id' => $user->id,
            ]);
        } else {
            return back()->withErrors(['body' => 'Unknown platform'])->withInput();
        }

        $updates = ['last_message_at' => $sentAt];
        if ($conversation->assigned_to_user_id === null) {
            $updates['assigned_to_user_id'] = $user->id;
        }
        $conversation->update($updates);

        return redirect()->route('inbox', [
            'conversation' => $conversation->id,
            'assignee' => $request->query('assignee'),
            'account' => $request->query('account'),
        ]);
    }

    /**
     * WhatsApp Web often needs the exact inbound JID (@lid, multi-device), not only digits @s.whatsapp.net.
     */
    private function resolveBaileysRemoteJidForSend(Conversation $conversation): ?string
    {
        $jid = data_get($conversation->metadata, 'baileys_remote_jid');
        if (is_string($jid) && str_contains($jid, '@')) {
            return $jid;
        }

        $lastInbound = ChannelMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', ChannelMessage::DIRECTION_INBOUND)
            ->orderByDesc('id')
            ->first();

        $jid = data_get($lastInbound?->payload, 'key.remoteJid');
        if (! is_string($jid) || ! str_contains($jid, '@')) {
            return null;
        }

        $meta = $conversation->metadata ?? [];
        $meta['baileys_remote_jid'] = $jid;
        $conversation->update(['metadata' => $meta]);

        return $jid;
    }
}

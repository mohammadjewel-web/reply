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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class InboxController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = $this->loadInboxConversations($request);
        $assigneeFilter = $request->query('assignee', 'all');

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

    public function poll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation' => ['required', 'integer'],
            'after' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! $request->user()->allows('inbox.access')) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $after = (int) ($validated['after'] ?? 0);
        $conversation = Conversation::query()
            ->with(['channelAccount:id,name,type,is_active'])
            ->find($validated['conversation']);

        if (! $conversation) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $messages = $conversation->channelMessages()
            ->with('user:id,name')
            ->where('id', '>', $after)
            ->orderBy('id')
            ->get();

        $conversation->refresh();
        $conversation->loadMissing('channelAccount:id,name,type,is_active');

        $html = '';
        foreach ($messages as $message) {
            $html .= view('admin.inbox.partials.message-bubble', [
                'm' => $message,
                'active' => $conversation,
            ])->render();
        }

        $lastId = $messages->isEmpty() ? $after : (int) $messages->last()->id;

        $payload = [
            'messages_html' => $html,
            'last_message_id' => $lastId,
            'contact' => [
                'title' => $conversation->inboxContactTitle(),
                'subtitle' => $conversation->inboxHeaderSubtitlePlain(),
                'avatar' => $conversation->inboxContactAvatarLetter(),
            ],
        ];

        if ($request->boolean('sync_list')) {
            $listRequest = $this->inboxListFilterRequest($request);
            $payload['list_html'] = $this->renderInboxConversationListHtml(
                $listRequest,
                (int) $validated['conversation'],
            );
        }

        return response()
            ->json($payload)
            ->header('Cache-Control', 'private, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Vary', 'Cookie');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Conversation>
     */
    protected function loadInboxConversations(Request $request)
    {
        $query = Conversation::query()
            ->with([
                'channelAccount:id,name,type,is_active',
                'assignee:id,name',
                'latestMessage',
            ])
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

        return $query->limit(250)->get();
    }

    protected function inboxListFilterRequest(Request $request): Request
    {
        $assigneeRaw = $request->get('list_assignee');
        if ($assigneeRaw === null || $assigneeRaw === '') {
            $assigneeRaw = $request->get('assignee', 'all');
        }
        $accountRaw = $request->get('list_account');
        if ($accountRaw === null || $accountRaw === '') {
            $accountRaw = $request->get('account');
        }

        $query = ['assignee' => is_string($assigneeRaw) && $assigneeRaw !== '' ? $assigneeRaw : 'all'];
        if ($accountRaw !== null && $accountRaw !== '') {
            $query['account'] = $accountRaw;
        }

        $sub = Request::create('/inbox', 'GET', $query);
        $sub->setUserResolver($request->getUserResolver());

        return $sub;
    }

    protected function renderInboxConversationListHtml(Request $filterRequest, int $selectedConversationId): string
    {
        $conversations = $this->loadInboxConversations($filterRequest);

        return view('admin.inbox.partials.inbox-conversation-rows', [
            'conversations' => $conversations,
            'selectedConversationId' => $selectedConversationId,
            'filterRequest' => $filterRequest,
        ])->render();
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
    ): RedirectResponse|JsonResponse {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:4096'],
            'attachment' => ['nullable', 'file', 'max:25600'],
            'voice_note' => ['sometimes', 'boolean'],
        ]);

        $bodyTrim = trim((string) ($validated['body'] ?? ''));
        $file = $request->file('attachment');
        $voiceNote = $request->boolean('voice_note');

        if ($file === null && $bodyTrim === '') {
            throw ValidationException::withMessages([
                'body' => [__('Type a message, pick an emoji, or attach a photo, video, or voice note.')],
            ]);
        }

        /** @var User $user */
        $user = $request->user();
        $sentAt = now();
        $wantsJson = $request->ajax() || $request->wantsJson();

        $account = $conversation->channelAccount;
        if (! $account || ! $account->is_active) {
            $msg = __('This conversation is not linked to an active channel account.');
            if ($wantsJson) {
                return response()->json(['message' => $msg, 'errors' => ['body' => [$msg]]], 422);
            }

            return back()->withErrors(['body' => $msg])->withInput();
        }

        $disk = Storage::disk('public');
        $storedPath = null;
        $message = null;

        if ($file !== null) {
            $mimeRaw = $file->getMimeType() ?: 'application/octet-stream';
            $origName = $file->getClientOriginalName() ?: 'attachment';

            if ($conversation->platform === Conversation::PLATFORM_WHATSAPP) {
                $waKind = $this->outboundWhatsappMediaKind($mimeRaw);
                if ($waKind === null) {
                    return $this->replySendError(
                        __('This file type is not supported for WhatsApp.'),
                        $wantsJson,
                    );
                }
                $storedPath = $file->store('chat-outbound/'.$conversation->id, 'public');
                $absolutePath = $disk->path($storedPath);
                $caption = $bodyTrim !== '' ? $bodyTrim : null;
                $displayBody = $caption ?? $this->outboundMediaPlaceholder($waKind);

                $perAccountCloud = filled($account->access_token) && filled($account->external_id);
                $sessionUserId = $account->baileys_session_user_id ?? $user->id;
                $useBaileys = config('services.baileys.enabled')
                    && ($account->baileys_session_user_id !== null || ! $perAccountCloud);

                if ($useBaileys) {
                    $sessionKey = 'wa-'.$account->id.'-u-'.$sessionUserId;
                    $remoteJid = $this->resolveBaileysRemoteJidForSend($conversation);
                    $baileysType = ($waKind === 'audio' && $voiceNote) ? 'ptt' : $waKind;
                    $result = $baileysRelay->sendMediaMessage(
                        $account,
                        $sessionKey,
                        $conversation->external_thread_key,
                        $absolutePath,
                        $origName,
                        $mimeRaw,
                        $baileysType,
                        $remoteJid,
                        $caption,
                    );
                } else {
                    $up = $whatsapp->uploadMediaForChannel($account, $absolutePath, $mimeRaw);
                    if (! $up['ok']) {
                        $disk->delete($storedPath);
                        $err = $up['error'] ?? 'Upload failed';

                        return $this->replySendError($err, $wantsJson);
                    }
                    $result = $whatsapp->sendMediaMessageForChannel(
                        $account,
                        $conversation->external_thread_key,
                        $waKind,
                        (string) $up['media_id'],
                        $caption,
                    );
                }

                if (! $result['ok']) {
                    $disk->delete($storedPath);
                    $err = $result['error'] ?? 'WhatsApp send failed';

                    return $this->replySendError($err, $wantsJson);
                }

                $message = ChannelMessage::query()->create([
                    'conversation_id' => $conversation->id,
                    'direction' => ChannelMessage::DIRECTION_OUTBOUND,
                    'external_message_id' => $result['message_id'],
                    'body' => $displayBody,
                    'payload' => [
                        'via' => $useBaileys ? 'baileys' : 'whatsapp_cloud',
                        'outbound_media' => [
                            'kind' => $waKind,
                            'path' => $storedPath,
                            'mime' => $mimeRaw,
                            'original_name' => $origName,
                        ],
                    ],
                    'sent_at' => $sentAt,
                    'user_id' => $user->id,
                ]);
            } elseif ($conversation->platform === Conversation::PLATFORM_MESSENGER) {
                $msKind = $this->outboundMessengerAttachmentType($mimeRaw);
                $storedPath = $file->store('chat-outbound/'.$conversation->id, 'public');
                $absolutePath = $disk->path($storedPath);
                $caption = $bodyTrim !== '' ? $bodyTrim : null;
                $displayBody = $caption ?? $this->outboundMediaPlaceholder($msKind === 'file' ? 'file' : $msKind);

                $result = $messenger->sendAttachmentForChannel(
                    $account,
                    $conversation->external_thread_key,
                    $msKind,
                    $absolutePath,
                    $origName,
                );

                if (! $result['ok']) {
                    $disk->delete($storedPath);
                    $err = $result['error'] ?? 'Messenger send failed';

                    return $this->replySendError($err, $wantsJson);
                }

                $message = ChannelMessage::query()->create([
                    'conversation_id' => $conversation->id,
                    'direction' => ChannelMessage::DIRECTION_OUTBOUND,
                    'external_message_id' => $result['message_id'],
                    'body' => $displayBody,
                    'payload' => [
                        'outbound_media' => [
                            'kind' => $msKind,
                            'path' => $storedPath,
                            'mime' => $mimeRaw,
                            'original_name' => $origName,
                        ],
                    ],
                    'sent_at' => $sentAt,
                    'user_id' => $user->id,
                ]);
            } else {
                return $this->replySendError('Unknown platform', $wantsJson);
            }
        } elseif ($conversation->platform === Conversation::PLATFORM_WHATSAPP) {
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
                    $bodyTrim,
                    $remoteJid,
                );
            } else {
                $result = $whatsapp->sendTextMessageForChannel($account, $conversation->external_thread_key, $bodyTrim);
            }

            if (! $result['ok']) {
                $err = $result['error'] ?? 'WhatsApp send failed';

                return $this->replySendError($err, $wantsJson);
            }
            $message = ChannelMessage::query()->create([
                'conversation_id' => $conversation->id,
                'direction' => ChannelMessage::DIRECTION_OUTBOUND,
                'external_message_id' => $result['message_id'],
                'body' => $bodyTrim,
                'payload' => $useBaileys ? ['via' => 'baileys'] : null,
                'sent_at' => $sentAt,
                'user_id' => $user->id,
            ]);
        } elseif ($conversation->platform === Conversation::PLATFORM_MESSENGER) {
            $result = $messenger->sendTextMessageForChannel($account, $conversation->external_thread_key, $bodyTrim);
            if (! $result['ok']) {
                $err = $result['error'] ?? 'Messenger send failed';

                return $this->replySendError($err, $wantsJson);
            }
            $message = ChannelMessage::query()->create([
                'conversation_id' => $conversation->id,
                'direction' => ChannelMessage::DIRECTION_OUTBOUND,
                'external_message_id' => $result['message_id'],
                'body' => $bodyTrim,
                'payload' => null,
                'sent_at' => $sentAt,
                'user_id' => $user->id,
            ]);
        } else {
            return $this->replySendError('Unknown platform', $wantsJson);
        }

        $updates = ['last_message_at' => $sentAt];
        if ($conversation->assigned_to_user_id === null) {
            $updates['assigned_to_user_id'] = $user->id;
        }
        $conversation->update($updates);

        $message->load('user:id,name');
        $conversation->refresh();
        $conversation->loadMissing('channelAccount:id,name,type,is_active');

        if ($wantsJson) {
            $html = view('admin.inbox.partials.message-bubble', [
                'm' => $message,
                'active' => $conversation,
            ])->render();

            $payload = [
                'ok' => true,
                'messages_html' => $html,
                'last_message_id' => $message->id,
                'contact' => [
                    'title' => $conversation->inboxContactTitle(),
                    'subtitle' => $conversation->inboxHeaderSubtitlePlain(),
                    'avatar' => $conversation->inboxContactAvatarLetter(),
                ],
            ];

            if ($request->boolean('sync_list')) {
                $payload['list_html'] = $this->renderInboxConversationListHtml(
                    $this->inboxListFilterRequest($request),
                    $conversation->id,
                );
            }

            return response()->json($payload);
        }

        return redirect()->route('inbox', [
            'conversation' => $conversation->id,
            'assignee' => $request->query('assignee'),
            'account' => $request->query('account'),
        ]);
    }

    private function replySendError(string $err, bool $wantsJson): RedirectResponse|JsonResponse
    {
        if ($wantsJson) {
            return response()->json(['message' => $err, 'errors' => ['body' => [$err]]], 422);
        }

        return back()->withErrors(['body' => $err])->withInput();
    }

    private function outboundWhatsappMediaKind(string $mime): ?string
    {
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        return null;
    }

    /**
     * @return string image|video|audio|file
     */
    private function outboundMessengerAttachmentType(string $mime): string
    {
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        return 'file';
    }

    private function outboundMediaPlaceholder(string $kind): string
    {
        return match ($kind) {
            'image' => '['.__('Photo').']',
            'video' => '['.__('Video').']',
            'audio', 'ptt' => '['.__('Voice message').']',
            'file' => '['.__('File').']',
            default => '['.__('Attachment').']',
        };
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

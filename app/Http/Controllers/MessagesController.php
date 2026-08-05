<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageGroup;
use App\Models\MessageReaction;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MessagesController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasPermission('view_messages')) {
            return redirect()->route('user.dashboard')->with('error', "You don't have permission to access Messages.");
        }

        $me = auth()->id();

        // Last message preview per direct conversation (keyed by other user's ID)
        $lastMsgsRaw = Message::where(function ($q) use ($me) {
                $q->where('sender_id', $me)->orWhere('receiver_id', $me);
            })
            ->whereNull('group_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn($m) => $m->sender_id === $me ? $m->receiver_id : $m->sender_id);

        // Friendly attachment preview — never leak a raw filename into the sidebar
        // (matches the mime-type logic in messages/_mobile-index.blade.php's $preview closure).
        $attachmentPreview = function ($msg) {
            $mime = (string) $msg->file_type;
            return str_starts_with($mime, 'image/') ? '📎 Photo'
                 : (str_starts_with($mime, 'video/') ? '📎 Video'
                 : (str_starts_with($mime, 'audio/') ? '🎤 Voice note' : '📎 Attachment'));
        };

        $lastMsgs = $lastMsgsRaw->map(fn($msgs) => [
                'body'      => $msgs->first()->deleted_at
                                ? '🚫 Message deleted'
                                : ($msgs->first()->body ?: ($msgs->first()->file_name ? $attachmentPreview($msgs->first()) : '📎 Attachment')),
                'time'      => $msgs->first()->created_at->diffForHumans(null, true, true, 1),
                'mine'      => $msgs->first()->sender_id === $me,
                'timestamp' => $msgs->first()->created_at->timestamp,
            ])
            ->toArray();

        // Sort contacts: users with messages first (most recent first), then the rest alphabetically
        $teamMembers = User::where('id', '!=', $me)->orderBy('name')->get()
            ->sortByDesc(fn($u) => $lastMsgs[$u->id]['timestamp'] ?? 0)
            ->values();

        // Online status map: userId → true/false (active within 3 minutes)
        $onlineMap = $teamMembers->mapWithKeys(fn($u) => [
            $u->id => $u->presence_status === 'online'
                      && $u->last_seen_at
                      && $u->last_seen_at->gt(now()->subMinutes(3)),
        ])->toArray();

        // Last-seen map: userId → epoch ms (or null if never seen) — client formats "X ago"
        $lastSeenMap = $teamMembers->mapWithKeys(fn($u) => [
            $u->id => $u->last_seen_at?->valueOf(),
        ])->toArray();

        $groups = MessageGroup::whereHas('members', fn($q) => $q->where('user_id', $me))
            ->with(['members:users.id,users.name', 'creator:id,name'])
            ->get()
            ->map(function ($g) use ($me) {
                return [
                    'id'         => $g->id,
                    'name'       => $g->name,
                    'created_by' => $g->created_by,
                    'unread'     => $g->unreadCountFor($me),
                    'members'    => $g->members->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values(),
                ];
            });

        [$directThreads, $groupThreads, $unreadTotal] = $this->mobileThreads($me, $teamMembers, $onlineMap, $lastMsgsRaw);

        return view('messages.index', compact(
            'teamMembers', 'groups', 'lastMsgs', 'onlineMap', 'lastSeenMap',
            'directThreads', 'groupThreads', 'unreadTotal'
        ));
    }

    /**
     * Unified Direct/Group thread view-models for the mobile list
     * (resources/views/messages/_mobile-index.blade.php). Not used by the
     * desktop UI, which drives its own contact list from $teamMembers/$groups.
     */
    private function mobileThreads(int $me, $teamMembers, array $onlineMap, $lastMsgsRaw): array
    {
        $colors = ['#6366F1', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#3B82F6'];

        $directUnread = Message::where('receiver_id', $me)->whereNull('read_at')
            ->get()->groupBy('sender_id')->map->count();

        $toThread = function ($lastMsg) {
            return $lastMsg ? (object) [
                'body'            => $lastMsg->deleted_at ? null : $lastMsg->body,
                'deleted'         => (bool) $lastMsg->deleted_at,
                'attachment_name' => $lastMsg->file_name,
                'mime_type'       => $lastMsg->file_type,
                'from_me'         => $lastMsg->sender_id === auth()->id(),
            ] : null;
        };

        $directThreads = $teamMembers->values()->map(function ($u, $i) use ($colors, $onlineMap, $lastMsgsRaw, $directUnread, $toThread) {
            $lastMsg = $lastMsgsRaw->get($u->id)?->first();

            return (object) [
                'id'              => $u->id,
                'kind'            => 'direct',
                'display_name'    => $u->name,
                'avatar_color'    => $colors[$i % count($colors)],
                'avatar_url'      => $u->avatarUrl(),
                'is_online'       => $onlineMap[$u->id] ?? false,
                'unread_count'    => $directUnread->get($u->id, 0),
                'last_message_at' => $lastMsg?->created_at,
                'role_label'      => ucfirst($u->role),
                'last_message'    => $toThread($lastMsg),
            ];
        })->sortByDesc(fn ($t) => $t->last_message_at?->timestamp ?? 0)->values();

        $groupThreads = MessageGroup::whereHas('members', fn ($q) => $q->where('user_id', $me))
            ->get()
            ->map(function ($g, $i) use ($me, $colors, $toThread) {
                $lastMsg = $g->messages()->latest('created_at')->first();

                return (object) [
                    'id'              => $g->id,
                    'kind'            => 'group',
                    'display_name'    => $g->name,
                    'avatar_color'    => $colors[($i + 3) % count($colors)],
                    'avatar_url'      => null,
                    'is_online'       => false,
                    'unread_count'    => $g->unreadCountFor($me),
                    'last_message_at' => $lastMsg?->created_at,
                    'role_label'      => null,
                    'last_message'    => $toThread($lastMsg),
                ];
            })->sortByDesc(fn ($t) => $t->last_message_at?->timestamp ?? 0)->values();

        $unreadTotal = $directThreads->sum('unread_count') + $groupThreads->sum('unread_count');

        return [$directThreads, $groupThreads, $unreadTotal];
    }

    /** GET /messages/conversation/{user} */
    public function conversation(User $user)
    {
        $me = auth()->id();

        $clearedAt = DB::table('direct_chat_clears')
            ->where('user_id', $me)->where('other_user_id', $user->id)
            ->value('cleared_at');
        $clearedAt = $clearedAt ? \Carbon\Carbon::parse($clearedAt) : null;

        // Mark ALL unread messages from this sender as read — no clearedAt filter here,
        // so a prior chat clear doesn't leave phantom unread badges.
        Message::where('sender_id', $user->id)
            ->where('receiver_id', $me)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Mark bell notifications from this sender as read
        auth()->user()->notifications()
            ->whereNull('read_at')
            ->where('data->notif_type', 'new_message')
            ->where('data->sender_id', $user->id)
            ->whereNull('data->group_id')
            ->update(['read_at' => now()]);

        $messages = Message::conversation($me, $user->id, $clearedAt)
            ->with(['sender:id,name', 'replyTo.sender:id,name', 'reactions'])
            ->get()
            ->map(fn($m) => $this->formatMessage($m, $me));

        return response()->json($messages);
    }

    /** POST /messages/send */
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body'        => 'nullable|string|max:2000',
            'file'        => 'nullable|file',
            'reply_to_id' => 'nullable|exists:messages,id',
            'is_voice'    => 'nullable|boolean',
        ]);

        if (! $request->filled('body') && ! $request->hasFile('file')) {
            return response()->json(['error' => 'Message or file required.'], 422);
        }

        [$filePath, $fileName, $fileType] = $this->handleFileUpload($request);

        $message = Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'body'        => $request->body ?? '',
            'file_path'   => $filePath,
            'file_name'   => $fileName,
            'file_type'   => $fileType,
            'reply_to_id' => $request->reply_to_id,
        ]);

        $message->load(['sender:id,name', 'replyTo.sender:id,name', 'reactions']);

        $formatted = $this->formatMessage($message, auth()->id());

        // Push to receiver via MQTT — override mine:false so it appears on their left
        MqttService::publish("tm/user/{$request->receiver_id}/messages/new", [
            'type'      => 'direct',
            'sender_id' => auth()->id(),
            'message'   => array_merge($formatted, ['mine' => false]),
        ]);

        // Bell notification — one entry per message so the badge count reflects reality.
        $receiver = User::find($request->receiver_id);
        if ($receiver) {
            $receiver->notify(new NewMessageNotification($message));
        }

        return response()->json($formatted, 201);
    }

    /** POST /messages/typing — ephemeral typing-indicator ping, nothing persisted */
    public function typing(Request $request)
    {
        $request->validate([
            'receiver_id' => 'nullable|exists:users,id',
            'group_id'    => 'nullable|exists:message_groups,id',
        ]);

        $me = auth()->user();

        if ($request->filled('group_id')) {
            $group = MessageGroup::find($request->group_id);
            if (! $group || ! $group->members()->where('user_id', $me->id)->exists()) {
                return response()->json(['ok' => false], 403);
            }

            $group->members()
                ->where('user_id', '!=', $me->id)
                ->pluck('user_id')
                ->each(fn ($uid) => MqttService::publish("tm/user/{$uid}/messages/typing", [
                    'from'     => $me->id,
                    'name'     => $me->name,
                    'group_id' => $group->id,
                ]));
        } elseif ($request->filled('receiver_id')) {
            MqttService::publish("tm/user/{$request->receiver_id}/messages/typing", [
                'from' => $me->id,
                'name' => $me->name,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /** GET /messages/unread */
    public function unread()
    {
        $me = auth()->id();

        $clears = DB::table('direct_chat_clears')
            ->where('user_id', $me)
            ->pluck('cleared_at', 'other_user_id');

        $direct = Message::where('receiver_id', $me)
            ->whereNull('read_at')
            ->whereNull('group_id')
            ->get(['id', 'sender_id', 'created_at'])
            ->filter(function ($m) use ($clears) {
                $clearedAt = $clears[$m->sender_id] ?? null;
                return !$clearedAt || $m->created_at > \Carbon\Carbon::parse($clearedAt);
            })
            ->groupBy('sender_id')
            ->map->count();

        $groups = MessageGroup::whereHas('members', fn($q) => $q->where('user_id', $me))
            ->get()
            ->mapWithKeys(fn($g) => ['g_' . $g->id => $g->unreadCountFor($me)]);

        return response()->json(['direct' => $direct, 'groups' => $groups]);
    }

    /** POST /messages/groups */
    public function createGroup(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'manager'])) {
            abort(403, 'Only admins and managers can create groups.');
        }

        $request->validate([
            'name'         => 'required|string|max:100',
            'member_ids'   => 'required|array|min:1',
            'member_ids.*' => 'exists:users,id',
        ]);

        $group = MessageGroup::create([
            'name'       => $request->name,
            'created_by' => auth()->id(),
        ]);

        $ids = array_unique(array_merge([auth()->id()], $request->member_ids));
        $group->members()->attach(array_fill_keys($ids, ['last_read_at' => now()]));

        return response()->json([
            'id'         => $group->id,
            'name'       => $group->name,
            'created_by' => $group->created_by,
            'unread'     => 0,
            'members'    => $group->members()->select('users.id', 'users.name')->get()
                                ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values(),
        ], 201);
    }

    /** GET /messages/groups/{group} */
    public function groupConversation(MessageGroup $group)
    {
        $me = auth()->id();

        $pivot = $group->members()->where('user_id', $me)->first();
        if (!$pivot) abort(403);

        $clearedAt = $pivot->pivot->cleared_at ? \Carbon\Carbon::parse($pivot->pivot->cleared_at) : null;

        $group->members()->updateExistingPivot($me, ['last_read_at' => now()]);

        // Mark bell notifications for this group as read
        auth()->user()->notifications()
            ->whereNull('read_at')
            ->where('data->notif_type', 'new_message')
            ->where('data->group_id', $group->id)
            ->update(['read_at' => now()]);

        $messages = $group->messages()
            ->when($clearedAt, fn($q) => $q->where('created_at', '>', $clearedAt))
            ->with(['sender:id,name', 'replyTo.sender:id,name', 'reactions'])
            ->get()
            ->map(fn($m) => $this->formatMessage($m, $me));

        $members = $group->members()->select('users.id', 'users.name', 'users.role')->get()
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'role' => ucfirst($u->role)]);

        $canManage = $group->created_by === $me || in_array(auth()->user()->role, ['admin', 'manager']);

        return response()->json(['messages' => $messages, 'members' => $members, 'canManage' => $canManage]);
    }

    /** POST /messages/groups/{group}/send */
    public function sendToGroup(MessageGroup $group, Request $request)
    {
        $me = auth()->id();

        if (!$group->members()->where('user_id', $me)->exists()) {
            abort(403);
        }

        $request->validate([
            'body'        => 'nullable|string|max:2000',
            'file'        => 'nullable|file',
            'reply_to_id' => 'nullable|exists:messages,id',
            'is_voice'    => 'nullable|boolean',
        ]);

        if (!$request->filled('body') && !$request->hasFile('file')) {
            return response()->json(['error' => 'Message or file required.'], 422);
        }

        [$filePath, $fileName, $fileType] = $this->handleFileUpload($request);

        $message = Message::create([
            'sender_id'   => $me,
            'receiver_id' => null,
            'group_id'    => $group->id,
            'body'        => $request->body ?? '',
            'file_path'   => $filePath,
            'file_name'   => $fileName,
            'file_type'   => $fileType,
            'reply_to_id' => $request->reply_to_id,
        ]);

        $message->load(['sender:id,name', 'replyTo.sender:id,name', 'reactions']);

        $formatted = $this->formatMessage($message, $me);

        // Push to every group member except sender — override mine:false for recipients
        $formattedForRecipient = array_merge($formatted, ['mine' => false]);
        $group->members()
            ->where('user_id', '!=', $me)
            ->get()
            ->each(function ($member) use ($formattedForRecipient, $group, $message) {
                MqttService::publish("tm/user/{$member->id}/messages/new", [
                    'type'     => 'group',
                    'group_id' => $group->id,
                    'message'  => $formattedForRecipient,
                ]);
                $member->notify(new NewMessageNotification($message));
            });

        return response()->json($formatted, 201);
    }

    /** POST /messages/groups/{group}/members */
    public function addGroupMember(MessageGroup $group, Request $request)
    {
        $me = auth()->id();
        if ($group->created_by !== $me && !in_array(auth()->user()->role, ['admin', 'manager'])) {
            abort(403, 'Only the group creator, an admin, or a manager can add members.');
        }

        $request->validate(['user_id' => 'required|exists:users,id']);

        $group->members()->syncWithoutDetaching([
            $request->user_id => ['last_read_at' => null],
        ]);

        $user = User::find($request->user_id);

        return response()->json(['id' => $user->id, 'name' => $user->name]);
    }

    /** DELETE /messages/groups/{group}/leave */
    public function leaveGroup(MessageGroup $group)
    {
        $group->members()->detach(auth()->id());

        if ($group->members()->count() === 0) {
            $group->delete();
        }

        return response()->json(['ok' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function handleFileUpload(Request $request): array
    {
        if (!$request->hasFile('file')) {
            return [null, null, null];
        }

        $file     = $request->file('file');
        $filePath = $file->store('messages', 'public');
        $fileName = $file->getClientOriginalName();

        if ($request->boolean('is_voice')) {
            $ext      = strtolower($file->getClientOriginalExtension() ?: 'webm');
            $fileType = 'audio/' . $ext;
        } else {
            $fileType = $file->getMimeType();
        }

        return [$filePath, $fileName, $fileType];
    }

    /** POST /messages/{message}/react */
    public function react(Message $message, Request $request)
    {
        $me = auth()->id();
        $request->validate(['emoji' => 'required|string|max:10']);

        $key = ['message_id' => $message->id, 'user_id' => $me, 'emoji' => $request->emoji];
        $exists = DB::table('message_reactions')->where($key)->exists();
        if ($exists) {
            DB::table('message_reactions')->where($key)->delete();
        } else {
            DB::table('message_reactions')->insert(array_merge($key, ['created_at' => now()]));
        }

        $message->load(['sender:id,name', 'replyTo.sender:id,name', 'reactions']);
        $formatted = $this->formatMessage($message, $me);

        // Push updated message to the other party
        if ($message->group_id) {
            $group = MessageGroup::find($message->group_id);
            $group?->members()->where('user_id', '!=', $me)->pluck('user_id')
                ->each(function ($uid) use ($formatted, $message) {
                    $msg = $formatted;
                    $msg['reactions'] = $this->getReactionsFor($message->id, $uid);
                    MqttService::publish("tm/user/{$uid}/messages/updated", ['type' => 'message_reacted', 'message' => $msg]);
                });
        } elseif ($message->receiver_id || $message->sender_id) {
            $other = $message->sender_id === $me ? $message->receiver_id : $message->sender_id;
            if ($other) {
                $msg = $formatted;
                $msg['reactions'] = $this->getReactionsFor($message->id, $other);
                MqttService::publish("tm/user/{$other}/messages/updated", ['type' => 'message_reacted', 'message' => $msg]);
            }
        }

        return response()->json($formatted);
    }

    private function getReactionsFor(int $messageId, int $userId): array
    {
        return DB::table('message_reactions')
            ->where('message_id', $messageId)
            ->selectRaw("emoji, COUNT(*) as cnt, SUM(CASE WHEN user_id = ? THEN 1 ELSE 0 END) as me", [$userId])
            ->groupBy('emoji')
            ->get()
            ->map(fn($r) => ['emoji' => $r->emoji, 'count' => (int)$r->cnt, 'mine' => (bool)$r->me])
            ->values()->toArray();
    }

    /** DELETE /messages/{message} — soft-deletes; receiver sees a "deleted" tombstone */
    public function deleteMessage(Message $message)
    {
        if ($message->sender_id !== auth()->id()) abort(403);

        $message->update(['deleted_at' => now()]);
        $message->load(['sender:id,name', 'replyTo.sender:id,name', 'reactions']);

        $formatted = $this->formatMessage($message, auth()->id());

        // Notify receiver so their UI updates in real-time
        if ($message->group_id) {
            $group = \App\Models\MessageGroup::find($message->group_id);
            $group?->members()->where('user_id', '!=', auth()->id())->pluck('user_id')
                ->each(fn($uid) => MqttService::publish("tm/user/{$uid}/messages/updated", [
                    'type'    => 'message_deleted',
                    'message' => $formatted,
                ]));
        } elseif ($message->receiver_id) {
            MqttService::publish("tm/user/{$message->receiver_id}/messages/updated", [
                'type'    => 'message_deleted',
                'message' => $formatted,
            ]);
        }

        return response()->json($formatted);
    }

    /** PATCH /messages/{message} */
    public function editMessage(Message $message, Request $request)
    {
        if ($message->sender_id !== auth()->id()) abort(403);
        $request->validate(['body' => 'required|string|max:2000']);
        $message->update(['body' => $request->body]);

        $message->load(['sender:id,name', 'replyTo.sender:id,name', 'reactions']);
        $formatted = $this->formatMessage($message->fresh()->load(['sender:id,name', 'replyTo.sender:id,name', 'reactions']), auth()->id());

        // Notify receiver of the edit
        if ($message->group_id) {
            $group = \App\Models\MessageGroup::find($message->group_id);
            $group?->members()->where('user_id', '!=', auth()->id())->pluck('user_id')
                ->each(fn($uid) => MqttService::publish("tm/user/{$uid}/messages/updated", [
                    'type'    => 'message_edited',
                    'message' => $formatted,
                ]));
        } elseif ($message->receiver_id) {
            MqttService::publish("tm/user/{$message->receiver_id}/messages/updated", [
                'type'    => 'message_edited',
                'message' => $formatted,
            ]);
        }

        return response()->json($formatted);
    }

    /** DELETE /messages/clear/direct/{userId} — clears only from the requesting user's side */
    public function clearDirectChat(int $userId)
    {
        $me = auth()->id();
        DB::table('direct_chat_clears')->upsert(
            [['user_id' => $me, 'other_user_id' => $userId, 'cleared_at' => now()]],
            ['user_id', 'other_user_id'],
            ['cleared_at']
        );
        return response()->json(['ok' => true]);
    }

    /** DELETE /messages/clear/group/{group} — clears only from the requesting user's side */
    public function clearGroupChat(MessageGroup $group)
    {
        $me = auth()->id();
        if (!$group->members()->where('user_id', $me)->exists()) {
            abort(403);
        }
        $group->members()->updateExistingPivot($me, ['cleared_at' => now()]);
        return response()->json(['ok' => true]);
    }

    /**
     * GET /messages/poll/direct/{userId}?after_id={id}
     * GET /messages/poll/group/{groupId}?after_id={id}
     * Returns only new messages since after_id — lightweight, called every second.
     */
    public function pollDirect(int $userId)
    {
        $me      = auth()->id();
        $afterId = request()->integer('after_id', 0);

        $clearedAt = DB::table('direct_chat_clears')
            ->where('user_id', $me)->where('other_user_id', $userId)
            ->value('cleared_at');
        $clearedAt = $clearedAt ? \Carbon\Carbon::parse($clearedAt) : null;

        $messages = Message::where(function ($q) use ($me, $userId) {
                $q->where('sender_id', $me)->where('receiver_id', $userId)
                  ->orWhere('sender_id', $userId)->where('receiver_id', $me);
            })
            ->whereNull('group_id')
            ->when($afterId, fn($q) => $q->where('id', '>', $afterId))
            ->when($clearedAt, fn($q) => $q->where('created_at', '>', $clearedAt))
            ->with(['sender:id,name', 'replyTo.sender:id,name', 'reactions'])
            ->orderBy('id')
            ->get()
            ->map(fn($m) => $this->formatMessage($m, $me));

        return response()->json($messages);
    }

    public function pollGroup(MessageGroup $group)
    {
        $me      = auth()->id();
        $afterId = request()->integer('after_id', 0);

        $pivot = $group->members()->where('user_id', $me)->first();
        if (!$pivot) abort(403);

        $clearedAt = $pivot->pivot->cleared_at ? \Carbon\Carbon::parse($pivot->pivot->cleared_at) : null;

        $messages = $group->messages()
            ->when($afterId, fn($q) => $q->where('id', '>', $afterId))
            ->when($clearedAt, fn($q) => $q->where('created_at', '>', $clearedAt))
            ->with(['sender:id,name', 'replyTo.sender:id,name', 'reactions'])
            ->orderBy('id')
            ->get()
            ->map(fn($m) => $this->formatMessage($m, $me));

        return response()->json($messages);
    }

    private function formatMessage(Message $m, int $me): array
    {
        $isDeleted = $m->deleted_at !== null;

        $reactions = $m->relationLoaded('reactions')
            ? $m->reactions->groupBy('emoji')->map(fn($g) => [
                'emoji' => $g->first()->emoji,
                'count' => $g->count(),
                'mine'  => $g->where('user_id', $me)->isNotEmpty(),
            ])->values()->toArray()
            : [];

        $data = [
            'id'         => $m->id,
            'body'       => $isDeleted ? '' : $m->body,
            'mine'       => $m->sender_id === $me,
            'sender'     => $m->sender->name,
            'sender_id'  => $m->sender_id,
            'created_at' => $m->created_at->format('H:i'),
            'date'       => $m->created_at->format('Y-m-d'),
            'is_edited'  => !$isDeleted && $m->updated_at->diffInSeconds($m->created_at) > 2,
            'is_deleted' => $isDeleted,
            'is_read'    => $m->read_at !== null,
            'file'       => null,
            'reply_to'   => null,
            'reactions'  => $reactions,
        ];

        if (!$isDeleted && $m->file_path) {
            $data['file'] = [
                'url'   => Storage::url($m->file_path),
                'name'  => $m->file_name,
                'type'  => $m->file_type,
                'image' => str_starts_with($m->file_type ?? '', 'image/'),
                'audio' => str_starts_with($m->file_type ?? '', 'audio/')
                           || str_starts_with($m->file_name ?? '', 'voice-'),
            ];
        }

        if ($m->replyTo) {
            $data['reply_to'] = [
                'id'         => $m->replyTo->id,
                'sender'     => $m->replyTo->sender->name ?? 'Unknown',
                'body'       => $m->replyTo->deleted_at
                                    ? '🚫 This message was deleted'
                                    : ($m->replyTo->body ?: ($m->replyTo->file_name ?? '📎 File')),
                'is_deleted' => $m->replyTo->deleted_at !== null,
            ];
        }

        return $data;
    }
}

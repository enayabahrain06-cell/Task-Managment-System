<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessagesController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasPermission('view_messages')) {
            return redirect()->route('user.dashboard')->with('error', "You don't have permission to access Messages.");
        }

        $me = auth()->id();

        $teamMembers = User::where('id', '!=', $me)->orderBy('name')->get();

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

        return view('messages.index', compact('teamMembers', 'groups'));
    }

    /** GET /messages/conversation/{user} */
    public function conversation(User $user)
    {
        $me = auth()->id();

        Message::where('sender_id', $user->id)
            ->where('receiver_id', $me)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::conversation($me, $user->id)
            ->with(['sender:id,name', 'replyTo.sender:id,name'])
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
            'file'        => 'nullable|file|max:20480',
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

        $message->load(['sender:id,name', 'replyTo.sender:id,name']);

        return response()->json($this->formatMessage($message, auth()->id()), 201);
    }

    /** GET /messages/unread */
    public function unread()
    {
        $me = auth()->id();

        $direct = Message::where('receiver_id', $me)
            ->whereNull('read_at')
            ->whereNull('group_id')
            ->selectRaw('sender_id, count(*) as count')
            ->groupBy('sender_id')
            ->pluck('count', 'sender_id');

        $groups = MessageGroup::whereHas('members', fn($q) => $q->where('user_id', $me))
            ->get()
            ->mapWithKeys(fn($g) => ['g_' . $g->id => $g->unreadCountFor($me)]);

        return response()->json(['direct' => $direct, 'groups' => $groups]);
    }

    /** POST /messages/groups */
    public function createGroup(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Only admins can create groups.');
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

        if (!$group->members()->where('user_id', $me)->exists()) {
            abort(403);
        }

        $group->members()->updateExistingPivot($me, ['last_read_at' => now()]);

        $messages = $group->messages()
            ->with(['sender:id,name', 'replyTo.sender:id,name'])
            ->get()
            ->map(fn($m) => $this->formatMessage($m, $me));

        $members = $group->members()->select('users.id', 'users.name', 'users.role')->get()
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'role' => ucfirst($u->role)]);

        $canManage = $group->created_by === $me || auth()->user()->role === 'admin';

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
            'file'        => 'nullable|file|max:20480',
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

        $message->load(['sender:id,name', 'replyTo.sender:id,name']);

        return response()->json($this->formatMessage($message, $me), 201);
    }

    /** POST /messages/groups/{group}/members */
    public function addGroupMember(MessageGroup $group, Request $request)
    {
        $me = auth()->id();
        if ($group->created_by !== $me && auth()->user()->role !== 'admin') {
            abort(403, 'Only the group creator or an admin can add members.');
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

    private function formatMessage(Message $m, int $me): array
    {
        $data = [
            'id'         => $m->id,
            'body'       => $m->body,
            'mine'       => $m->sender_id === $me,
            'sender'     => $m->sender->name,
            'sender_id'  => $m->sender_id,
            'created_at' => $m->created_at->format('H:i'),
            'date'       => $m->created_at->format('Y-m-d'),
            'file'       => null,
            'reply_to'   => null,
        ];

        if ($m->file_path) {
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
                'id'     => $m->replyTo->id,
                'sender' => $m->replyTo->sender->name ?? 'Unknown',
                'body'   => $m->replyTo->body ?: ($m->replyTo->file_name ?? '📎 File'),
            ];
        }

        return $data;
    }
}

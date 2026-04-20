<?php

namespace App\Http\Controllers;

use App\Models\Message;
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

        $teamMembers = User::where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get();

        return view('messages.index', compact('teamMembers'));
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

        $filePath = null;
        $fileName = null;
        $fileType = null;

        if ($request->hasFile('file')) {
            $file     = $request->file('file');
            $filePath = $file->store('messages', 'public');
            $fileName = $file->getClientOriginalName();
            // If the client flagged this as a voice message, force audio MIME
            if ($request->boolean('is_voice')) {
                $ext      = strtolower($file->getClientOriginalExtension() ?: 'webm');
                $fileType = 'audio/' . $ext;
            } else {
                $fileType = $file->getMimeType();
            }
        }

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
        $counts = Message::where('receiver_id', auth()->id())
            ->whereNull('read_at')
            ->selectRaw('sender_id, count(*) as count')
            ->groupBy('sender_id')
            ->pluck('count', 'sender_id');

        return response()->json($counts);
    }

    private function formatMessage(Message $m, int $me): array
    {
        $data = [
            'id'         => $m->id,
            'body'       => $m->body,
            'mine'       => $m->sender_id === $me,
            'sender'     => $m->sender->name,
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

<?php

namespace App\Http\Controllers;

use App\Models\ActivityReaction;
use App\Models\ActivityReply;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivitiesController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $teams = User::withCount('tasks')
            ->where('role', '!=', 'admin')
            ->orderBy('role')
            ->get()
            ->groupBy('role');

        $query = TaskLog::with(['user', 'task.project', 'reactions.user', 'replies.user']);

        // User filter (sidebar)
        $selectedUserId = $request->input('user_id');
        $selectedUser   = null;
        if ($selectedUserId) {
            $query->where('user_id', $selectedUserId);
            $selectedUser = User::find($selectedUserId);
        }

        // Action type filter
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Date range filter
        switch ($request->input('date_range')) {
            case 'today':     $query->whereDate('created_at', today()); break;
            case 'yesterday': $query->whereDate('created_at', today()->subDay()); break;
            case 'week':      $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]); break;
            case 'month':     $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year); break;
        }

        // Sort
        $sort = $request->input('sort', 'newest');
        $sort === 'oldest' ? $query->oldest() : $query->latest();

        $activities = $query->paginate(20)->withQueryString();

        // Distinct action types for filter dropdown
        $actionTypes = TaskLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('activities.index', compact('teams', 'activities', 'selectedUser', 'actionTypes'));
    }

    public function release(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:100',
            'version'     => 'nullable|string|max:30',
            'description' => 'nullable|string|max:2000',
        ]);

        TaskLog::create([
            'task_id' => null,
            'user_id' => auth()->id(),
            'action'  => 'release_published',
            'note'    => json_encode([
                'title'       => $request->title,
                'version'     => $request->version,
                'description' => $request->description,
            ]),
        ]);

        return back()->with('success', 'Release published successfully.');
    }

    public function react(Request $request, TaskLog $log)
    {
        $emoji  = $request->input('emoji');
        $userId = auth()->id();

        $existing = ActivityReaction::where([
            'task_log_id' => $log->id,
            'user_id'     => $userId,
            'emoji'       => $emoji,
        ])->first();

        if ($existing) {
            $existing->delete();
            $reacted = false;
        } else {
            ActivityReaction::create([
                'task_log_id' => $log->id,
                'user_id'     => $userId,
                'emoji'       => $emoji,
            ]);
            $reacted = true;
        }

        $counts = $log->reactions()
            ->selectRaw('emoji, count(*) as total')
            ->groupBy('emoji')
            ->pluck('total', 'emoji');

        return response()->json(['reacted' => $reacted, 'counts' => $counts]);
    }

    public function reply(Request $request, TaskLog $log)
    {
        $request->validate(['body' => 'required|string|max:1000']);

        $reply = ActivityReply::create([
            'task_log_id' => $log->id,
            'user_id'     => auth()->id(),
            'body'        => $request->body,
        ]);

        $reply->load('user');

        return response()->json([
            'id'         => $reply->id,
            'body'       => $reply->body,
            'user'       => $reply->user->name,
            'initial'    => strtoupper(substr($reply->user->name, 0, 1)),
            'time'       => $reply->created_at->diffForHumans(),
            'mine'       => true,
            'delete_url' => route('activities.reply.delete', $reply),
        ]);
    }

    public function deleteReply(ActivityReply $reply)
    {
        if ($reply->user_id !== auth()->id() && !auth()->user()->hasPermission('view_audit_log')) {
            abort(403);
        }

        $reply->delete();
        return response()->json(['ok' => true]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ActivityReaction;
use App\Models\ActivityReply;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use App\Notifications\TaskTimelineReply;
use Illuminate\Http\Request;

class ActivitiesController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $isPrivileged = in_array(auth()->user()->role, ['admin', 'manager']);

        $teams = $isPrivileged
            ? User::withCount('tasks')->where('role', '!=', 'admin')->orderBy('role')->get()->groupBy('role')
            : collect();

        $query = TaskLog::with(['user', 'task.project', 'reactions.user', 'replies.user']);

        // Regular users always see only their own activity
        if (!$isPrivileged) {
            $query->where('user_id', auth()->id());
            $selectedUser   = auth()->user();
            $selectedUserId = auth()->id();
        } else {
            // User filter (sidebar) — privileged only
            $selectedUserId = $request->input('user_id');
            $selectedUser   = null;
            if ($selectedUserId) {
                $query->where('user_id', $selectedUserId);
                $selectedUser = User::find($selectedUserId);
            }
        }

        // Action type filter
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Search by note or task title
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('note', 'like', "%{$s}%")
                  ->orWhereHas('task', fn($tq) => $tq->where('title', 'like', "%{$s}%"));
            });
        }

        // Date range filter
        $dateRange = $request->input('date_range', '');
        switch ($dateRange) {
            case 'today':     $query->whereDate('created_at', today()); break;
            case 'yesterday': $query->whereDate('created_at', today()->subDay()); break;
            case 'week':      $query->where('created_at', '>=', now()->subDays(7)); break;
            case 'month':     $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year); break;
        }

        // Sort
        $sort = $request->input('sort', 'newest');
        $sort === 'oldest' ? $query->oldest() : $query->latest();

        $activities = $query->paginate(20)->withQueryString();

        // Distinct action types for filter dropdown
        $actionTypes = TaskLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('activities.index', compact('teams', 'activities', 'selectedUser', 'actionTypes', 'isPrivileged'));
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

        // Notify admins, managers, and the task assignee (skip the poster)
        if (Setting::get('notify_on_comment', '1') === '1') {
            $task     = $log->task()->with('assignee')->first();
            $posterId = auth()->id();

            if ($task) {
                $notified = collect();

                // Admins + managers
                User::whereIn('role', ['admin', 'manager'])->get()
                    ->each(function ($u) use ($reply, $task, $posterId, &$notified) {
                        if ($u->id !== $posterId && !$notified->contains($u->id)) {
                            $u->notify(new TaskTimelineReply($task, $reply));
                            $notified->push($u->id);
                        }
                    });

                // Task assignee (if not admin/manager and not the poster)
                if ($task->assignee && $task->assignee->id !== $posterId && !$notified->contains($task->assignee->id)) {
                    $task->assignee->notify(new TaskTimelineReply($task, $reply));
                }
            }
        }

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

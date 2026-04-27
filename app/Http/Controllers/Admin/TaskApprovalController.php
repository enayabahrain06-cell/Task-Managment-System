<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskSocialPost;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Notifications\SocialMediaAssigned;
use App\Notifications\SocialMediaPosted;
use App\Notifications\TaskApproved;
use App\Notifications\TaskRejected;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskApprovalController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('view_approvals')) {
            abort(403, 'You do not have permission to view Approvals.');
        }

        $tab = $request->get('tab', 'pending');

        $tasks = Task::where('status', 'submitted')
            ->with(['project', 'assignee', 'assignees', 'submissions' => fn($q) => $q->latest()])
            ->latest()
            ->paginate(20, ['*'], 'page');

        $hSort     = $request->get('hsort', 'date');
        $hDir      = $request->get('hdir', 'desc') === 'asc' ? 'asc' : 'desc';
        $hFrom     = $request->get('hfrom');
        $hTo       = $request->get('hto');
        $hDecision = $request->get('hdecision');
        $hSearch   = $request->get('hsearch');

        $historyQuery = TaskSubmission::whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('reviewed_at')
            ->with(['task.project', 'task.assignee', 'task.socialAssignee', 'task.socialPosts.user', 'reviewer']);

        if ($hDecision) {
            $historyQuery->where('status', $hDecision);
        }
        if ($hFrom) {
            $historyQuery->whereDate('reviewed_at', '>=', $hFrom);
        }
        if ($hTo) {
            $historyQuery->whereDate('reviewed_at', '<=', $hTo);
        }
        if ($hSearch) {
            $historyQuery->where(function ($q) use ($hSearch) {
                $q->whereHas('task', fn($q2) => $q2->where('title', 'like', "%{$hSearch}%"))
                  ->orWhereHas('task.assignee', fn($q2) => $q2->where('name', 'like', "%{$hSearch}%"))
                  ->orWhereHas('reviewer', fn($q2) => $q2->where('name', 'like', "%{$hSearch}%"));
            });
        }

        match ($hSort) {
            'task'     => $historyQuery->orderByRaw("(SELECT title FROM tasks WHERE tasks.id = task_submissions.task_id) {$hDir}"),
            'assignee' => $historyQuery->orderByRaw("(SELECT name FROM users WHERE users.id = (SELECT assigned_to FROM tasks WHERE tasks.id = task_submissions.task_id)) {$hDir}"),
            'reviewer' => $historyQuery->orderByRaw("(SELECT name FROM users WHERE users.id = task_submissions.reviewed_by) {$hDir}"),
            'decision' => $historyQuery->orderBy('task_submissions.status', $hDir),
            default    => $historyQuery->orderBy('task_submissions.reviewed_at', $hDir),
        };

        $history = $historyQuery->paginate(20, ['*'], 'hpage');

        $socialTasks = Task::whereNotNull('social_assigned_to')
            ->whereNull('social_posted_at')
            ->with(['project', 'assignee', 'socialAssignee', 'socialPosts.user'])
            ->latest()
            ->paginate(20, ['*'], 'spage');

        $publishedSocialTasks = Task::whereNotNull('social_assigned_to')
            ->whereNotNull('social_posted_at')
            ->with(['project', 'assignee', 'socialAssignee', 'socialPosts'])
            ->orderByDesc('social_posted_at')
            ->paginate(20, ['*'], 'ppage');

        $socialUsers = User::where('role', 'user')->orderBy('name')->get();

        return view('admin.approvals.index', compact(
            'tasks', 'history', 'tab', 'socialTasks', 'publishedSocialTasks', 'socialUsers',
            'hSort', 'hDir', 'hFrom', 'hTo', 'hDecision', 'hSearch'
        ));
    }

    public function approve(Request $request, Task $task)
    {
        $request->validate([
            'note'               => 'nullable|string|max:500',
            'social_required'    => 'nullable|in:1,0',
            'social_assigned_to' => 'nullable|exists:users,id',
        ]);

        $latestSub = TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->orderByDesc('version')
            ->first();

        $task->update(['status' => 'delivered']);

        TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->update([
                'status'      => 'approved',
                'admin_note'  => $request->note,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_delivered',
            'note'     => $request->note ? 'Approved & delivered: ' . $request->note : 'Approved and delivered by admin',
            'metadata' => [
                'old_status'         => 'submitted',
                'new_status'         => 'delivered',
                'reviewer_id'        => auth()->id(),
                'reviewer_name'      => auth()->user()->name,
                'submission_version' => $latestSub?->version,
                'approval_note'      => $request->note,
            ],
        ]);

        AuditLogger::log(
            'task.approved',
            $task,
            'Task "' . $task->title . '" approved' . ($request->note ? ': ' . $request->note : ''),
            ['task_id' => $task->id, 'task_title' => $task->title, 'note' => $request->note]
        );

        if ($task->assignee) {
            $task->assignee->notify(new TaskApproved($task, $request->note));
        }

        $task->project?->autoComplete();

        // Handle social media decision made during approval
        if ($request->filled('social_required')) {
            $needed = (bool) $request->input('social_required');
            $task->update(['social_required' => $needed]);

            if (!$needed) {
                $task->update(['social_assigned_to' => null, 'social_posted_at' => null]);
            } elseif ($request->filled('social_assigned_to')) {
                $socialUser = User::find($request->social_assigned_to);
                if ($socialUser) {
                    $task->update(['social_assigned_to' => $socialUser->id]);
                    TaskLog::create([
                        'task_id' => $task->id,
                        'user_id' => auth()->id(),
                        'action'  => 'social_assigned',
                        'note'    => 'Assigned to ' . $socialUser->name . ' for social media posting',
                    ]);
                    $socialUser->notify(new SocialMediaAssigned($task, auth()->user()));
                }
            }
        }

        $successMsg = 'Task approved.';
        if ($request->input('social_required') === '1' && $request->filled('social_assigned_to')) {
            $assignedUser = User::find($request->social_assigned_to);
            if ($assignedUser) {
                $successMsg = 'Task approved and assigned to ' . $assignedUser->name . ' for social media posting.';
            }
        } elseif ($request->input('social_required') === '0') {
            $successMsg = 'Task approved. No social media posting required.';
        }

        return back()->with('success', $successMsg);
    }

    public function reject(Request $request, Task $task)
    {
        $request->validate(['note' => 'required|string|max:500']);

        $latestSub = TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->orderByDesc('version')
            ->first();

        $task->update(['status' => 'revision_requested']);

        TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->update([
                'status'      => 'rejected',
                'admin_note'  => $request->note,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_revision_requested',
            'note'     => 'Revision requested: ' . $request->note,
            'metadata' => [
                'old_status'         => 'submitted',
                'new_status'         => 'revision_requested',
                'reviewer_id'        => auth()->id(),
                'reviewer_name'      => auth()->user()->name,
                'submission_version' => $latestSub?->version,
                'rejection_reason'   => $request->note,
            ],
        ]);

        AuditLogger::log(
            'task.rejected',
            $task,
            'Revision requested for "' . $task->title . '": ' . $request->note,
            ['task_id' => $task->id, 'task_title' => $task->title, 'reason' => $request->note]
        );

        if ($task->assignee) {
            $task->assignee->notify(new TaskRejected($task, $request->note));
        }

        return back()->with('success', 'Revision requested — assignee has been notified.');
    }

    public function setSocialRequired(Request $request, Task $task)
    {
        $request->validate(['required' => 'required|in:1,0']);

        $needed = (bool) $request->input('required');
        $task->update(['social_required' => $needed]);

        if (!$needed) {
            $task->update(['social_assigned_to' => null, 'social_posted_at' => null]);
        }

        return back()->with('success', $needed
            ? '"' . $task->title . '" marked for social media posting.'
            : '"' . $task->title . '" — social media posting not required.');
    }

    public function assignSocial(Request $request, Task $task)
    {
        $request->validate(['social_user_id' => 'required|exists:users,id']);

        $user = User::findOrFail($request->social_user_id);

        $task->update([
            'social_assigned_to' => $user->id,
            'social_posted_at'   => null,
        ]);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action'  => 'social_assigned',
            'note'    => 'Assigned to ' . $user->name . ' for social media posting',
        ]);

        $user->notify(new SocialMediaAssigned($task, auth()->user()));

        return back()->with('success', '"' . $task->title . '" assigned to ' . $user->name . ' for social media posting.');
    }

    public function updateSocialPost(Request $request, TaskSocialPost $post)
    {
        $request->validate([
            'platform' => 'required|string|in:facebook,instagram,twitter,linkedin,tiktok,youtube,snapchat,other',
            'post_url' => 'nullable|url|max:1000',
            'note'     => 'nullable|string|max:1000',
        ]);

        $post->update([
            'platform' => $request->platform,
            'post_url' => $request->post_url ?: null,
            'note'     => $request->note ?: null,
        ]);

        TaskLog::create([
            'task_id'  => $post->task_id,
            'user_id'  => auth()->id(),
            'action'   => 'social_post_edited',
            'note'     => 'Edited ' . $post->platformLabel() . ' post record',
            'metadata' => ['platform' => $request->platform, 'post_url' => $request->post_url, 'note' => $request->note],
        ]);

        return back()->with('success', 'Post record updated.');
    }

    public function deleteSocialPost(TaskSocialPost $post)
    {
        $taskId = $post->task_id;
        $label  = $post->platformLabel();
        $post->delete();

        if (!TaskSocialPost::where('task_id', $taskId)->exists()) {
            Task::find($taskId)?->update(['social_posted_at' => null]);
        }

        TaskLog::create([
            'task_id' => $taskId,
            'user_id' => auth()->id(),
            'action'  => 'social_post_deleted',
            'note'    => 'Deleted ' . $label . ' post record',
        ]);

        return back()->with('success', 'Post record removed.');
    }

    public function addPost(Request $request, Task $task)
    {
        $user = auth()->user();
        if ($user->id !== (int) $task->social_assigned_to && !in_array($user->role, ['admin', 'manager'])) {
            abort(403, 'You are not assigned to this social media post.');
        }

        $request->validate([
            'platform'   => 'required|array|min:1',
            'platform.*' => 'required|string|in:facebook,instagram,twitter,linkedin,tiktok,youtube,snapchat,other',
            'post_url'   => 'nullable|array',
            'post_url.*' => 'nullable|url|max:1000',
            'note'       => 'nullable|array',
            'note.*'     => 'nullable|string|max:1000',
        ]);

        $platformLabels = [
            'facebook' => 'Facebook', 'instagram' => 'Instagram', 'twitter' => 'Twitter/X',
            'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube',
            'snapchat' => 'Snapchat', 'other' => 'Other',
        ];

        $platforms = $request->input('platform', []);
        $urls      = $request->input('post_url', []);
        $notes     = $request->input('note', []);
        $recorded  = [];

        foreach ($platforms as $i => $platform) {
            $url  = $urls[$i] ?? null;
            $note = $notes[$i] ?? null;

            TaskSocialPost::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'platform' => $platform,
                'post_url' => $url ?: null,
                'note'     => $note ?: null,
            ]);

            $label = $platformLabels[$platform] ?? ucfirst($platform);
            $recorded[] = $label;

            TaskLog::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'action'   => 'social_posted',
                'note'     => 'Posted on ' . $label . ($url ? ' — ' . $url : ''),
                'metadata' => ['platform' => $platform, 'post_url' => $url, 'note' => $note],
            ]);
        }

        if (!$task->social_posted_at) {
            $task->update(['social_posted_at' => now()]);
        }

        User::whereIn('role', ['admin', 'manager'])->get()
            ->each(fn($u) => $u->notify(new SocialMediaPosted($task, auth()->user())));

        if ($task->assignee && $task->assignee->id !== auth()->id()) {
            $task->assignee->notify(new SocialMediaPosted($task, auth()->user()));
        }

        $summary = count($recorded) === 1
            ? $recorded[0]
            : implode(', ', array_slice($recorded, 0, -1)) . ' & ' . last($recorded);

        return back()->with('success', count($recorded) . ' ' . Str::plural('post', count($recorded)) . ' recorded on ' . $summary . '! The team has been notified.');
    }

    public function reopenSocial(Task $task)
    {
        $task->update(['social_posted_at' => null]);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action'  => 'social_reopened',
            'note'    => 'Social media submission reopened by ' . auth()->user()->name,
        ]);

        return redirect()->route('admin.approvals.index', ['tab' => 'published'])
            ->with('success', '"' . $task->title . '" has been reopened — the assignee can now record posts again.');
    }

    // Legacy — kept for backward compatibility, redirects to add-post
    public function markPosted(Task $task)
    {
        return redirect()->route('social.show', $task);
    }

    public function showSocial(Task $task)
    {
        $user = auth()->user();
        if ($user->id !== (int) $task->social_assigned_to && !in_array($user->role, ['admin', 'manager'])) {
            abort(403, 'You are not assigned to this social media post.');
        }

        $task->load([
            'project.creator',
            'project.members',
            'assignee',
            'assignees',
            'socialAssignee',
            'creator',
            'socialPosts.user',
            'submissions' => fn($q) => $q->latest(),
        ]);

        $projectTaskCount     = $task->project?->tasks()->count() ?? 0;
        $projectCompletedCount = $task->project?->tasks()->whereIn('status', ['approved','delivered','archived'])->count() ?? 0;
        $projectProgress      = $projectTaskCount > 0 ? round($projectCompletedCount / $projectTaskCount * 100) : 0;

        return view('social.show', compact('task', 'projectTaskCount', 'projectCompletedCount', 'projectProgress'));
    }
}

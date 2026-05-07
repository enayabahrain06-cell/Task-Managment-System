<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskComment;
use Carbon\Carbon;
use App\Models\TaskCommentEdit;
use App\Models\TaskLog;
use App\Models\TaskSubmission;
use App\Models\TaskSubmissionEdit;
use App\Models\TaskTimerSegment;
use App\Models\TaskTransfer;
use App\Models\User;
use App\Notifications\TaskAutoPaused;
use App\Notifications\TaskCommentPosted;
use App\Notifications\TaskCompleted;
use App\Notifications\TaskViewed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user   = auth()->user();
        $filter = $request->input('filter', 'all');

        $pendingStatuses    = ['draft', 'assigned', 'viewed', 'revision_requested'];
        $inProgressStatuses = ['in_progress', 'paused', 'submitted'];
        $completedStatuses  = ['approved', 'delivered', 'archived'];

        $base = $user->tasks()->with('project');

        match ($filter) {
            'pending'     => $base->whereIn('status', $pendingStatuses),
            'in_progress' => $base->whereIn('status', $inProgressStatuses),
            'completed'   => $base->whereIn('status', $completedStatuses),
            default       => null,
        };

        $tasks = $base->latest()->paginate(15)->withQueryString();

        $counts = [
            'all'         => $user->tasks()->count(),
            'pending'     => $user->tasks()->whereIn('status', $pendingStatuses)->count(),
            'in_progress' => $user->tasks()->whereIn('status', $inProgressStatuses)->count(),
            'completed'   => $user->tasks()->whereIn('status', $completedStatuses)->count(),
        ];

        return view('user.tasks.index', compact('tasks', 'filter', 'counts'));
    }

    // ── Timer helpers ────────────────────────────────────────────────────────

    /** Close all open timer segments for this user on this task. */
    private function closeOpenSegments(Task $task, int $userId, string $reason): void
    {
        TaskTimerSegment::where('task_id', $task->id)
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->each(function (TaskTimerSegment $seg) use ($reason) {
                $seconds = (int) $seg->started_at->diffInSeconds(now());
                $seg->update([
                    'ended_at'         => now(),
                    'duration_seconds' => $seconds,
                    'pause_reason'     => $reason,
                ]);
            });
    }

    /** Auto-pause any other in_progress task this user has a running timer on. */
    private function autoPauseOtherTasks(Task $currentTask, int $userId): void
    {
        $otherSegments = TaskTimerSegment::where('user_id', $userId)
            ->where('task_id', '!=', $currentTask->id)
            ->whereNull('ended_at')
            ->with('task')
            ->get();

        foreach ($otherSegments as $seg) {
            $seconds = (int) $seg->started_at->diffInSeconds(now());
            $seg->update([
                'ended_at'         => now(),
                'duration_seconds' => $seconds,
                'pause_reason'     => 'task_switch',
            ]);

            if ($seg->task && in_array($seg->task->status, ['in_progress'])) {
                $seg->task->update(['status' => 'paused']);
                TaskLog::create([
                    'task_id'  => $seg->task_id,
                    'user_id'  => $userId,
                    'action'   => 'auto_paused',
                    'note'     => 'Timer auto-paused — another task was started.',
                    'metadata' => [
                        'pause_reason'         => 'task_switch',
                        'paused_by_task_id'    => $currentTask->id,
                        'paused_by_task_title' => $currentTask->title,
                    ],
                ]);
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $user->notify(new TaskAutoPaused($seg->task, 'task_switch', $currentTask->title));
                }
            }
        }
    }

    // ── Timer endpoints ──────────────────────────────────────────────────────

    /** Check work hours; returns a warning string if outside, null if inside. */
    private function outsideHoursWarning(): ?string
    {
        $timezone  = Setting::get('timezone', 'UTC');
        $startTime = Setting::get('work_start_time', '09:00');
        $endTime   = Setting::get('work_end_time', '18:00');
        $workDays  = json_decode(Setting::get('work_days', '[1,2,3,4,5]'), true) ?? [1,2,3,4,5];

        $now     = Carbon::now($timezone);
        $nowTime = $now->format('H:i');
        $isWorkDay = in_array($now->dayOfWeekIso, $workDays);

        if (!$isWorkDay) {
            return "You're starting the timer on a non-work day ({$now->format('l')}). Time still counts — it's your responsibility.";
        }
        if ($nowTime < $startTime) {
            return "You're starting before work hours begin ({$startTime}). Time still counts — it's your responsibility.";
        }
        if ($nowTime >= $endTime) {
            return "You're starting after work hours ended ({$endTime}). Time still counts — it's your responsibility.";
        }
        return null;
    }

    public function startTimer(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $startable = ['viewed', 'in_progress', 'paused'];
        if (!in_array($task->status, $startable)) {
            return back()->with('error', 'Timer cannot be started at this stage.');
        }

        $userId = auth()->id();

        // Auto-pause other running tasks
        $this->autoPauseOtherTasks($task, $userId);

        // If already running on this task, don't create duplicate
        $alreadyRunning = TaskTimerSegment::where('task_id', $task->id)
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->exists();

        if (!$alreadyRunning) {
            $phase = in_array($task->status, ['revision_requested', 'paused']) ? 'revision' : 'work';

            TaskTimerSegment::create([
                'task_id'    => $task->id,
                'user_id'    => $userId,
                'phase'      => $phase,
                'started_at' => now(),
            ]);
        }

        $oldStatus = $task->status;
        if (in_array($task->status, ['viewed', 'paused'])) {
            $task->update(['status' => 'in_progress']);
        }

        $outsideWarning = $this->outsideHoursWarning();

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => $userId,
            'action'   => 'timer_started',
            'note'     => 'Timer started' . ($outsideWarning ? ' (outside work hours)' : '') . '.',
            'metadata' => [
                'old_status'      => $oldStatus,
                'new_status'      => $task->fresh()->status,
                'outside_hours'   => $outsideWarning !== null,
            ],
        ]);

        if ($outsideWarning) {
            return back()
                ->with('success', 'Timer started.')
                ->with('timer_warning', $outsideWarning);
        }

        return back()->with('success', 'Timer started.');
    }

    public function pauseTimer(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        if (!in_array($task->status, ['in_progress'])) {
            return back()->with('error', 'Timer is not running.');
        }

        $this->closeOpenSegments($task, auth()->id(), 'manual');

        $task->update(['status' => 'paused']);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'timer_paused',
            'note'     => 'Timer paused manually.',
            'metadata' => ['old_status' => 'in_progress', 'new_status' => 'paused'],
        ]);

        return back()->with('success', 'Timer paused.');
    }

    public function acknowledgeRevision(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        if ($task->status !== 'revision_requested') {
            return back()->with('error', 'No pending revision to acknowledge.');
        }

        $userId = auth()->id();

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => $userId,
            'action'   => 'revision_acknowledged',
            'note'     => 'Employee acknowledged revision request and resumed work.',
            'metadata' => ['old_status' => 'revision_requested', 'new_status' => 'in_progress'],
        ]);

        // Auto-pause other running tasks
        $this->autoPauseOtherTasks($task, $userId);

        // Start a revision-phase timer segment
        TaskTimerSegment::create([
            'task_id'    => $task->id,
            'user_id'    => $userId,
            'phase'      => 'revision',
            'started_at' => now(),
        ]);

        $task->update(['status' => 'in_progress']);

        $outsideWarning = $this->outsideHoursWarning();

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => $userId,
            'action'   => 'timer_started',
            'note'     => 'Timer started for revision phase' . ($outsideWarning ? ' (outside work hours)' : '') . '.',
            'metadata' => [
                'phase'         => 'revision',
                'old_status'    => 'revision_requested',
                'new_status'    => 'in_progress',
                'outside_hours' => $outsideWarning !== null,
            ],
        ]);

        $response = back()->with('success', 'Revision acknowledged — your timer has started.');
        if ($outsideWarning) {
            $response = $response->with('timer_warning', $outsideWarning);
        }
        return $response;
    }

    // ── Main CRUD ────────────────────────────────────────────────────────────

    public function show(Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        // Auto-advance to "viewed" on first open
        if (is_null($task->first_viewed_at)) {
            $updates = ['first_viewed_at' => now()];
            if ($task->status === 'assigned') {
                $updates['status'] = 'viewed';
            }
            $task->update($updates);

            TaskLog::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'action'   => 'first_viewed',
                'note'     => auth()->user()->name . ' opened this task for the first time.',
                'metadata' => [
                    'viewer_id'   => auth()->id(),
                    'viewer_name' => auth()->user()->name,
                ],
            ]);

            if (Setting::get('notify_on_viewed', '0') === '1') {
                User::where('role', 'admin')->each(
                    fn($admin) => $admin->notify(new TaskViewed($task, auth()->user()))
                );
            }
        }

        $task->load('project.attachments', 'project.customer', 'assignees', 'reviewer', 'creator', 'customer', 'logs.user', 'submissions.user', 'submissions.reviewer', 'submissions.noteEdits.editor', 'comments.user', 'comments.edits.editor', 'transfers.fromUser', 'transfers.transferredBy', 'timerSegments');

        // Find the transfer that handed this task TO the current user
        $incomingTransfer = $task->transfers
            ->where('to_user_id', auth()->id())
            ->sortByDesc('transferred_at')
            ->first();

        // Timer data for the view
        $userId = auth()->id();
        $completedTimerSeconds = $task->timerSegments
            ->where('user_id', $userId)
            ->whereNotNull('ended_at')
            ->sum('duration_seconds');
        $activeSegment = $task->timerSegments
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->sortByDesc('started_at')
            ->first();

        return view('user.tasks.show', compact('task', 'incomingTransfer', 'completedTimerSeconds', 'activeSegment'));
    }

    public function updateStatus(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $allowed = match($task->status) {
            'viewed', 'revision_requested' => ['in_progress'],
            'in_progress'                  => [],
            default                        => [],
        };

        $request->validate([
            'status' => 'required|in:' . implode(',', $allowed ?: ['in_progress']),
            'note'   => 'nullable|string|max:500',
        ]);

        if (!in_array($request->status, $allowed)) {
            return back()->with('error', 'This status transition is not allowed.');
        }

        $oldStatus = $task->status;
        $task->update(['status' => $request->status]);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_' . $request->status,
            'note'     => $request->note,
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => $request->status,
            ],
        ]);

        return back()->with('success', 'Status updated to ' . ucfirst(str_replace('_', ' ', $request->status)) . '.');
    }

    public function submitVersion(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $submittable = ['viewed', 'in_progress', 'paused', 'revision_requested'];
        if (!in_array($task->status, $submittable)) {
            return back()->with('error', 'You cannot submit at this stage.');
        }

        // Auto-advance from viewed → in_progress on first submission
        if ($task->status === 'viewed') {
            $task->update(['status' => 'in_progress']);
            TaskLog::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'action'   => 'status_updated_in_progress',
                'note'     => 'Started working (triggered by first submission)',
                'metadata' => ['old_status' => 'viewed', 'new_status' => 'in_progress'],
            ]);
        }

        $request->validate([
            'note' => 'nullable|string|max:1000',
            'body' => 'nullable|string|max:1000',
            'file' => 'nullable|file',
        ]);

        $note = $request->body ?? $request->note;

        if (!$request->filled('note') && !$request->filled('body') && !$request->hasFile('file')) {
            return back()->withErrors(['body' => 'Please add a note or attach a file.']);
        }

        $version = TaskSubmission::where('task_id', $task->id)->max('version') + 1;

        $filePath         = null;
        $originalFilename = null;
        if ($request->hasFile('file')) {
            $file             = $request->file('file');
            $originalFilename = $file->getClientOriginalName();
            $filePath         = $file->store('task-submissions/' . $task->id, 'public');
        }

        TaskSubmission::create([
            'task_id'           => $task->id,
            'user_id'           => auth()->id(),
            'version'           => $version,
            'note'              => $note,
            'file_path'         => $filePath,
            'original_filename' => $originalFilename,
            'status'            => 'submitted',
        ]);

        // Close any open timer segment on submission
        $this->closeOpenSegments($task, auth()->id(), 'submitted');

        $oldStatus = $task->status;
        $task->update(['status' => 'submitted']);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_submitted',
            'note'     => 'Submitted version ' . $version . ($note ? ': ' . $note : ''),
            'metadata' => [
                'old_status'      => $oldStatus,
                'new_status'      => 'submitted',
                'version'         => $version,
                'has_file'        => !is_null($filePath),
                'filename'        => $originalFilename,
                'submission_note' => $note,
            ],
        ]);

        if (Setting::get('notify_on_complete', '1') === '1') {
            $task->load('assignee');
            $hasFile = !is_null($filePath);
            User::where('role', 'admin')->each(fn($admin) => $admin->notify(new TaskCompleted($task, $hasFile)));
        }

        return back()->with('success', 'Version ' . $version . ' submitted for review.');
    }

    public function addComment(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'body' => 'required|string|max:1000',
            'file' => 'nullable|file',
        ]);

        $filePath = null;
        $originalFilename = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalFilename = $file->getClientOriginalName();
            $filePath = $file->store("task-comment-files/{$task->id}", 'public');
        }

        // Auto-advance from viewed → in_progress on first comment
        if ($task->status === 'viewed') {
            $task->update(['status' => 'in_progress']);
            TaskLog::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'action'   => 'status_updated_in_progress',
                'note'     => 'Started working (triggered by first comment)',
                'metadata' => ['old_status' => 'viewed', 'new_status' => 'in_progress'],
            ]);
        }

        $comment = TaskComment::create([
            'task_id'           => $task->id,
            'user_id'           => auth()->id(),
            'body'              => $request->body,
            'file_path'         => $filePath,
            'original_filename' => $originalFilename,
        ]);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'comment_added',
            'note'     => \Illuminate\Support\Str::limit($request->body, 120),
            'metadata' => ['comment_id' => $comment->id, 'author_role' => 'user'],
        ]);

        $comment->load('user');
        if (Setting::get('notify_on_comment', '1') === '1') {
            User::where('role', 'admin')->each(fn($admin) => $admin->notify(new TaskCommentPosted($task, $comment)));
        }

        return back()->with('success', 'Comment posted.');
    }

    public function editComment(Request $request, Task $task, TaskComment $comment)
    {
        if ($comment->task_id !== $task->id || $comment->user_id !== auth()->id()) {
            abort(403);
        }
        $request->validate(['body' => 'required|string|max:1000']);
        TaskCommentEdit::create([
            'task_comment_id'       => $comment->id,
            'old_body'              => $comment->body,
            'old_file_path'         => $comment->file_path,
            'old_original_filename' => $comment->original_filename,
            'edited_by_id'          => auth()->id(),
            'created_at'            => now(),
        ]);
        $comment->update(['body' => $request->body]);
        return back()->with('success', 'Comment updated.');
    }

    public function editSubmissionNote(Request $request, Task $task, TaskSubmission $submission)
    {
        if ($submission->task_id !== $task->id || $submission->user_id !== auth()->id()) {
            abort(403);
        }
        $request->validate(['note' => 'nullable|string|max:1000']);
        TaskSubmissionEdit::create([
            'task_submission_id' => $submission->id,
            'old_note'           => $submission->note,
            'edited_by_id'       => auth()->id(),
            'created_at'         => now(),
        ]);
        $submission->update(['note' => $request->note]);
        return back()->with('success', 'Submission note updated.');
    }
}

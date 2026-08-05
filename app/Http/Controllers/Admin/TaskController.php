<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeadlineExtensionRequest;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskCommentEdit;
use App\Models\TaskLog;
use App\Models\TaskSubmission;
use App\Models\TaskSubmissionEdit;
use App\Models\TaskTransfer;
use App\Models\User;
use App\Notifications\DeadlineExtensionResponded;
use App\Notifications\SocialMediaAssigned;
use App\Notifications\TaskCommentPosted;
use App\Notifications\TaskDelivered;
use App\Notifications\TaskReassigned;
use App\Services\AuditLogger;
use App\Services\NasService;
use App\Support\TaskStatusColors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->hasPermission('manage_tasks')) {
            abort(403, 'You do not have permission to manage Tasks.');
        }

        if ($request->isMobileDevice()) {
            return redirect()->route('mobile.tasks', $request->only('filter', 'project'));
        }

        $query = Task::with(['project:id,name', 'assignee:id,name,avatar', 'socialAssignee:id,name', 'socialPosts'])
            ->withCount('assignees');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('project')) {
            $query->where('project_id', $request->project);
        }
        if ($request->filled('assignee')) {
            $uid = $request->assignee;
            $query->where(function ($q) use ($uid) {
                $q->where('assigned_to', $uid)
                    ->orWhere('social_assigned_to', $uid)
                    ->orWhereExists(fn ($x) => $x->selectRaw('1')->from('task_assignees')
                        ->whereColumn('task_assignees.task_id', 'tasks.id')
                        ->where('task_assignees.user_id', $uid));
            });
        }
        $doneStatuses = ['approved', 'delivered', 'archived'];
        $isDoneTab = ($request->get('tab') === 'done');

        if ($request->boolean('overdue')) {
            $query->whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->whereNotIn('status', $doneStatuses);
        }
        if ($request->filled('filter')) {
            match ($request->filter) {
                'pending' => $query->whereIn('status', ['draft', 'assigned', 'viewed']),
                'due_this_week' => $query->whereNotNull('deadline')
                    ->whereBetween('deadline', [now()->startOfWeek(\Carbon\Carbon::MONDAY), now()->endOfWeek(\Carbon\Carbon::SUNDAY)])
                    ->whereNotIn('status', $doneStatuses),
                default => null,
            };
        }

        // Tab-based separation: active tab hides done, done tab shows only done
        if (! $request->filled('status')) {
            if ($isDoneTab) {
                $query->whereIn('status', $doneStatuses);
            } else {
                $query->whereNotIn('status', $doneStatuses);
            }
        }

        $tasks = $isDoneTab
            ? $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString()
            : $query->orderBy('created_at', 'desc')->paginate(24)->withQueryString();

        $projects = Project::where('is_quick', false)->orderBy('name')->get(['id', 'name']);
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $assignableUsers = User::whereIn('role', ['user', 'manager'])->orderBy('name')->get(['id', 'name']);

        $stats = [
            'total' => Task::count(),
            'active' => Task::whereNotIn('status', $doneStatuses)->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'in_review' => Task::where('status', 'submitted')->count(),
            'overdue' => Task::whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->whereNotIn('status', $doneStatuses)
                ->count(),
            'done' => Task::whereIn('status', $doneStatuses)->count(),
            'approved' => Task::where('status', 'approved')->count(),
            'delivered' => Task::where('status', 'delivered')->count(),
            'archived' => Task::where('status', 'archived')->count(),
        ];

        return view('admin.tasks.index', compact('tasks', 'projects', 'stats', 'customers', 'assignableUsers'));
    }

    public function show(Task $task)
    {
        $task->load([
            'project.attachments' => fn ($q) => $q->whereNull('task_id')->whereHas('project', fn ($p) => $p->where('is_quick', false)),
            'project.members', 'project.customer',
            'assignee', 'assignees', 'reviewer', 'creator', 'customer',
            'socialAssignee', 'socialPosts',
            'logs.user',
            'submissions.user', 'submissions.reviewer', 'submissions.noteEdits.editor',
            'comments.user', 'comments.edits.editor',
            'transfers.fromUser', 'transfers.toUser', 'transfers.transferredBy',
            'attachments',
            'dependencies.project',
        ]);
        $users = User::whereIn('role', ['user', 'manager'])->orderBy('name')->get();
        $socialUsers = User::where('role', 'user')->orderBy('name')->get();
        $projects = Project::where('is_quick', false)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $depsFeatureOn = Setting::get('show_task_dependencies', '1') === '1';
        $timeFeatureOn = Setting::get('show_time_tracking', '1') === '1';
        $timeByUser = $timeFeatureOn ? $task->timerSecondsByUser() : collect();
        $totalSeconds = $timeFeatureOn ? $task->totalTimerSeconds() : 0;

        return view('admin.tasks.show', compact('task', 'users', 'socialUsers', 'projects', 'customers', 'depsFeatureOn', 'timeFeatureOn', 'timeByUser', 'totalSeconds'));
    }

    public function update(Request $request, Task $task)
    {
        if (! auth()->user()->hasPermission('manage_tasks')) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'customer_id' => 'nullable|exists:customers,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:high,medium,low',
            'deadline' => 'nullable|date',
            'description' => 'nullable|string',
            'social_assigned_to' => 'nullable|exists:users,id',
            'new_attachments' => 'nullable|array',
            'new_attachments.*' => 'file',
            'delete_attachments' => 'nullable|array',
            'delete_attachments.*' => 'integer|exists:project_attachments,id',
        ]);

        $changes = [];
        $fields = ['title', 'project_id', 'customer_id', 'assigned_to', 'priority', 'deadline', 'description'];
        foreach ($fields as $field) {
            $old = $task->$field instanceof Carbon
                ? $task->$field->toDateString()
                : $task->$field;
            $new = $field === 'deadline' && $request->filled('deadline')
                ? \Carbon\Carbon::parse($request->deadline)->toDateString()
                : ($request->input($field) ?: null);
            if ((string) $old !== (string) ($new ?? '')) {
                $changes[$field] = ['from' => $old, 'to' => $new];
            }
        }

        $projectId = $request->project_id
            ?: Project::where('is_quick', true)->value('id')
            ?: $task->project_id;

        $task->update([
            'title' => $request->title,
            'project_id' => $projectId,
            'customer_id' => $request->customer_id ?: null,
            'assigned_to' => $request->assigned_to ?: null,
            'priority' => $request->priority ?: null,
            'deadline' => $request->filled('deadline') ? $request->deadline : null,
            'description' => $request->description ?: null,
        ]);

        if (! empty($changes)) {
            TaskLog::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'action' => 'task_updated',
                'note' => 'Task details updated by '.auth()->user()->name,
                'metadata' => ['changes' => $changes, 'changed_by' => auth()->user()->name],
            ]);
        }

        if ($task->social_required && $request->filled('social_assigned_to')
            && (int) $request->social_assigned_to !== (int) $task->social_assigned_to) {
            $newSocialUser = User::findOrFail($request->social_assigned_to);
            $task->update(['social_assigned_to' => $newSocialUser->id]);

            TaskLog::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'action' => 'social_assigned',
                'note' => 'Reassigned to '.$newSocialUser->name.' for social media posting',
            ]);

            if (Setting::get('notify_on_social', '1') === '1') {
                $newSocialUser->notify(new SocialMediaAssigned($task, auth()->user()));
            }
        }

        // Delete checked attachments (only task-specific ones belonging to this task)
        if ($request->filled('delete_attachments')) {
            $toDelete = ProjectAttachment::whereIn('id', $request->delete_attachments)
                ->where('task_id', $task->id)
                ->get();
            foreach ($toDelete as $att) {
                Storage::disk('public')->delete($att->path);
                $att->delete();
            }
        }

        // Store new uploaded attachments
        if ($request->hasFile('new_attachments')) {
            $nas = app(NasService::class);
            foreach ($request->file('new_attachments') as $file) {
                $path = $file->store("task-attachments/{$task->id}", 'public');
                $nasPath = $nas->copyToNas($task, $path, $file->getClientOriginalName(), '03_Working');
                $nas->copyToNasReference($task, $path, $file->getClientOriginalName());
                ProjectAttachment::create([
                    'project_id' => $task->project_id,
                    'task_id' => $task->id,
                    'type' => 'file',
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'nas_path' => $nasPath,
                    'size' => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        AuditLogger::log(
            'task.updated',
            $task,
            'Task "'.$task->title.'" details updated',
            ['task_id' => $task->id, 'changes' => $changes]
        );

        return back()->with('success', 'Task "'.$task->title.'" updated successfully.');
    }

    public function comment(Request $request, Task $task)
    {
        $request->validate([
            'body' => 'required|string',
            'comment_files' => 'nullable|array',
            'comment_files.*' => 'file',
        ]);

        $nas = app(NasService::class);
        $storedFiles = [];

        if ($request->hasFile('comment_files')) {
            foreach ($request->file('comment_files') as $file) {
                $name = $file->getClientOriginalName();
                $path = $file->store("task-comment-files/{$task->id}", 'public');
                $nasPath = $nas->copyToNas($task, $path, $name, '03_Working');
                $nas->copyToNasReference($task, $path, $name);
                $storedFiles[] = [
                    'path' => $path,
                    'original_filename' => $name,
                    'nas_path' => $nasPath,
                ];
            }
        }

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'body' => $request->body,
            'files' => $storedFiles ?: null,
        ]);

        $filenames = array_column($storedFiles, 'original_filename');

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'comment_added',
            'note' => Str::limit($request->body, 120),
            'metadata' => array_filter([
                'comment_id' => $comment->id,
                'author_role' => 'admin',
                'filenames' => $filenames ?: null,
            ]),
        ]);

        $comment->load('user');

        if ($task->assignee && Setting::get('notify_on_comment', '1') === '1') {
            $task->assignee->notify(new TaskCommentPosted($task, $comment));
        }

        return back()->with('success', 'Comment posted.');
    }

    public function editComment(Request $request, Task $task, TaskComment $comment)
    {
        if ($comment->task_id !== $task->id || $comment->user_id !== auth()->id()) {
            abort(403);
        }
        $request->validate(['body' => 'required|string']);
        TaskCommentEdit::create([
            'task_comment_id' => $comment->id,
            'old_body' => $comment->body,
            'old_file_path' => $comment->file_path,
            'old_original_filename' => $comment->original_filename,
            'edited_by_id' => auth()->id(),
            'created_at' => now(),
        ]);
        $comment->update(['body' => $request->body]);

        return back()->with('success', 'Comment updated.');
    }

    public function editSubmissionNote(Request $request, Task $task, TaskSubmission $submission)
    {
        if ($submission->task_id !== $task->id) {
            abort(403);
        }
        $request->validate(['note' => 'nullable|string|max:1000']);
        TaskSubmissionEdit::create([
            'task_submission_id' => $submission->id,
            'old_note' => $submission->note,
            'edited_by_id' => auth()->id(),
            'created_at' => now(),
        ]);
        $submission->update(['note' => $request->note]);

        return back()->with('success', 'Submission note updated.');
    }

    // ── Task Dependencies ────────────────────────────────────────────────────

    public function storeDependency(Request $request, Task $task)
    {
        $request->validate([
            'depends_on_task_id' => 'required|integer|exists:tasks,id',
        ]);

        $dependsOnId = (int) $request->depends_on_task_id;

        if ($dependsOnId === $task->id) {
            return response()->json(['error' => 'A task cannot depend on itself.'], 422);
        }

        // Prevent circular: check that the target doesn't already depend on $task
        $circular = \DB::table('task_dependencies')
            ->where('task_id', $dependsOnId)
            ->where('depends_on_task_id', $task->id)
            ->exists();
        if ($circular) {
            return response()->json(['error' => 'Circular dependency detected.'], 422);
        }

        $task->dependencies()->syncWithoutDetaching([
            $dependsOnId => ['created_by' => auth()->id()],
        ]);

        $dep = Task::with('project:id,name,is_quick')->find($dependsOnId);
        AuditLogger::log('task.dependency_added', $task, "Dependency added: #{$dep->id} {$dep->title}", ['depends_on_id' => $dependsOnId]);

        return response()->json([
            'ok' => true,
            'dep' => [
                'id' => $dep->id,
                'title' => $dep->title,
                'status' => $dep->status,
                'project' => ($dep->project && ! $dep->project->is_quick) ? $dep->project->name : null,
            ],
        ]);
    }

    public function destroyDependency(Task $task, int $dependsOnId)
    {
        $task->dependencies()->detach($dependsOnId);
        AuditLogger::log('task.dependency_removed', $task, "Dependency removed: #{$dependsOnId}", ['depends_on_id' => $dependsOnId]);

        return response()->json(['ok' => true]);
    }

    public function searchTasksForDependency(Request $request, Task $task)
    {
        $q = $request->input('q', '');
        $alreadyIds = $task->dependencies()->pluck('depends_on_task_id')->push($task->id);

        $results = Task::where('id', '!=', $task->id)
            ->whereNotIn('id', $alreadyIds)
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('id', 'like', "%{$q}%");
            })
            ->with('project:id,name,is_quick')
            ->limit(10)
            ->get(['id', 'title', 'status', 'project_id']);

        return response()->json($results->map(fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'status' => $t->status,
            'project' => ($t->project && ! $t->project->is_quick) ? $t->project->name : null,
        ]));
    }

    public function deliver(Request $request, Task $task)
    {
        $request->validate(['note' => 'nullable|string|max:500']);

        if ($task->status !== 'approved') {
            return back()->with('error', 'Only approved tasks can be marked as delivered.');
        }

        $task->update(['status' => 'delivered']);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'status_updated_delivered',
            'note' => $request->note ? 'Delivered: '.$request->note : 'Marked as delivered',
            'metadata' => [
                'old_status' => 'approved',
                'new_status' => 'delivered',
                'delivered_by_id' => auth()->id(),
                'delivered_by_name' => auth()->user()->name,
                'delivery_note' => $request->note,
            ],
        ]);

        AuditLogger::log(
            'task.delivered',
            $task,
            'Task "'.$task->title.'" marked as delivered',
            ['task_id' => $task->id, 'task_title' => $task->title, 'note' => $request->note]
        );

        if ($task->assignee && Setting::get('notify_on_deliver', '1') === '1') {
            $task->assignee->notify(new TaskDelivered($task, $request->note));
        }

        $task->project?->autoComplete();

        return back()->with('success', 'Task marked as delivered — '.($task->assignee->name ?? 'assignee').' has been notified.');
    }

    public function forceClose(Request $request, Task $task)
    {
        $closeable = ['assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested', 'approved'];
        if (! in_array($task->status, $closeable)) {
            return back()->with('error', 'This task is already closed or archived.');
        }

        $closeDate = $request->filled('close_date')
            ? \Carbon\Carbon::parse($request->close_date)->endOfDay()
            : now();

        $oldStatus = $task->status;
        $task->update(['status' => 'delivered', 'delivered_at' => $closeDate]);

        $log = TaskLog::make([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'status_updated_delivered',
            'note' => 'Task force-closed by '.auth()->user()->name.($request->note ? ': '.$request->note : '').($request->filled('close_date') ? ' (backdated to '.$closeDate->format('d M Y').')' : ''),
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => 'delivered',
                'delivered_by_id' => auth()->id(),
                'delivered_by_name' => auth()->user()->name,
                'force_closed' => true,
                'backdated' => $request->filled('close_date'),
            ],
        ]);
        $log->created_at = $closeDate;
        $log->updated_at = $closeDate;
        $log->save();

        AuditLogger::log(
            'task.delivered',
            $task,
            'Task "'.$task->title.'" force-closed by admin (was '.$oldStatus.')',
            ['task_id' => $task->id, 'task_title' => $task->title, 'old_status' => $oldStatus]
        );

        if ($task->assignee && Setting::get('notify_on_deliver', '1') === '1') {
            $task->assignee->notify(new TaskDelivered($task, $request->note));
        }

        $task->project?->autoComplete();

        return back()->with('success', 'Task closed successfully.');
    }

    public function destroy(Task $task)
    {
        if (! auth()->user()->hasPermission('delete_tasks')) {
            abort(403);
        }

        $title = $task->title;
        AuditLogger::log(
            'task.deleted',
            $task,
            'Task "'.$title.'" moved to recycle bin',
            ['task_id' => $task->id, 'task_title' => $title]
        );
        $task->delete();

        return redirect()->route('admin.tasks.index')
            ->with('success', '"'.$title.'" moved to the Recycle Bin.');
    }

    public function trash(Request $request)
    {
        if (! auth()->user()->hasPermission('view_trash')) {
            abort(403);
        }

        $query = Task::onlyTrashed()->with(['project:id,name', 'assignee:id,name']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $tasks = $query->latest('deleted_at')->paginate(24)->withQueryString();

        return view('admin.tasks.trash', compact('tasks'));
    }

    public function restore(int $id)
    {
        if (! auth()->user()->hasPermission('view_trash')) {
            abort(403);
        }

        $task = Task::onlyTrashed()->findOrFail($id);
        $task->restore();

        return back()->with('success', '"'.$task->title.'" has been restored.');
    }

    public function forceDelete(int $id)
    {
        if (! auth()->user()->hasPermission('delete_tasks')) {
            abort(403);
        }

        $task = Task::onlyTrashed()->findOrFail($id);
        $title = $task->title;
        AuditLogger::log(
            'task.force_deleted',
            $task,
            'Task "'.$title.'" permanently deleted',
            ['task_id' => $task->id, 'task_title' => $title]
        );
        $task->forceDelete();

        return back()->with('success', '"'.$title.'" has been permanently deleted.');
    }

    public function reopen(Request $request, Task $task)
    {
        if (! in_array($task->status, ['approved', 'delivered', 'archived'])) {
            return back()->with('error', 'Only approved, delivered, or archived tasks can be reopened.');
        }

        $oldStatus = $task->status;
        $task->update(['status' => 'in_progress']);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'status_updated_reopened',
            'note' => 'Task reopened by '.auth()->user()->name,
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => 'in_progress',
                'reopened_by_id' => auth()->id(),
                'reopened_by_name' => auth()->user()->name,
            ],
        ]);

        AuditLogger::log(
            'task.reopened',
            $task,
            'Task "'.$task->title.'" reopened (was '.$oldStatus.')',
            ['task_id' => $task->id, 'task_title' => $task->title, 'old_status' => $oldStatus]
        );

        if ($task->assignee && Setting::get('notify_on_reassign', '1') === '1') {
            $task->assignee->notify(new TaskReassigned($task, true));
        }

        // If the project was auto-completed, reopen it
        if ($task->project && $task->project->status === 'completed') {
            $task->project->update(['status' => 'active']);
        }

        return back()->with('success', 'Task "'.$task->title.'" has been reopened and is now In Progress.');
    }

    public function archive(Request $request, Task $task)
    {
        $task->update(['status' => 'archived']);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'status_updated_archived',
            'note' => 'Task archived by '.auth()->user()->name,
            'metadata' => [
                'old_status' => $task->getOriginal('status'),
                'new_status' => 'archived',
                'archived_by_id' => auth()->id(),
                'archived_by_name' => auth()->user()->name,
            ],
        ]);

        AuditLogger::log(
            'task.archived',
            $task,
            'Task "'.$task->title.'" archived',
            ['task_id' => $task->id, 'task_title' => $task->title]
        );

        $task->project?->autoComplete();

        return back()->with('success', 'Task archived.');
    }

    public function panel(Task $task): JsonResponse
    {
        $task->load([
            'project:id,name',
            'assignee:id,name,avatar',
            'creator:id,name',
            'reviewer:id,name',
            'logs' => fn ($q) => $q->with('user:id,name')->orderBy('created_at'),
            'submissions' => fn ($q) => $q->with(['user:id,name', 'reviewer:id,name'])->orderBy('version', 'desc'),
            'comments' => fn ($q) => $q->with('user:id,name')->latest(),
            'socialPosts',
        ]);

        $priorityMeta = [
            'high' => ['label' => 'High',   'color' => '#EF4444', 'bg' => '#FEF2F2'],
            'medium' => ['label' => 'Medium', 'color' => '#F59E0B', 'bg' => '#FFFBEB'],
            'low' => ['label' => 'Low',    'color' => '#10B981', 'bg' => '#ECFDF5'],
        ];
        $statusMeta = TaskStatusColors::for($task->status);
        $sm = ['label' => $statusMeta['label'], 'color' => $statusMeta['text'], 'bg' => $statusMeta['bg']];
        $pm = $priorityMeta[$task->priority] ?? null;

        $isOverdue = $task->deadline && $task->deadline->isPast()
            && ! in_array($task->status, ['approved', 'delivered', 'archived']);

        return response()->json([
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'statusLabel' => $sm['label'],
            'statusColor' => $sm['color'],
            'statusBg' => $sm['bg'],
            'priority' => $task->priority,
            'priorityMeta' => $pm,
            'deadline' => $task->deadline?->format(config('app.date_format', 'M d, Y')),
            'isOverdue' => $isOverdue,
            'createdAt' => $task->created_at->format('M d, Y · H:i'),
            'updatedAt' => $task->updated_at->format('M d, Y · H:i'),
            'project' => $task->project ? ['name' => $task->project->name] : null,
            'assignee' => $task->assignee ? ['name' => $task->assignee->name, 'initials' => $this->initials($task->assignee->name)] : null,
            'creator' => $task->creator ? ['name' => $task->creator->name,  'initials' => $this->initials($task->creator->name)] : null,
            'reviewer' => $task->reviewer ? ['name' => $task->reviewer->name, 'initials' => $this->initials($task->reviewer->name)] : null,
            'taskUrl' => route('admin.tasks.show', $task->id),
            'logs' => $task->logs->sortByDesc('created_at')->values()->map(fn ($l) => [
                'label' => $l->actionLabel(),
                'style' => $l->actionStyle(),
                'note' => $l->note,
                'user' => $l->user?->name,
                'createdAt' => $l->created_at->format('M d, Y · H:i'),
                'diffHumans' => $l->created_at->diffForHumans(),
            ]),
            'submissions' => $task->submissions->map(fn ($s) => [
                'version' => $s->version,
                'status' => $s->status,
                'note' => $s->note,
                'adminNote' => $s->admin_note,
                'fileUrl' => $s->fileUrl(),
                'filename' => $s->original_filename,
                'fileType' => $this->fileType($s->original_filename),
                'user' => $s->user?->name,
                'reviewer' => $s->reviewer?->name,
                'reviewedAt' => $s->reviewed_at?->format('M d, Y · H:i'),
                'submittedAt' => $s->created_at->format('M d, Y · H:i'),
            ]),
            'comments' => $task->comments->map(fn ($c) => [
                'body' => $c->body,
                'user' => $c->user?->name,
                'initials' => $this->initials($c->user?->name ?? 'U'),
                'createdAt' => $c->created_at->format('M d, Y · H:i'),
                'diffHumans' => $c->created_at->diffForHumans(),
            ]),
            'socialPosts' => $task->socialPosts->map(fn ($sp) => [
                'platform' => $sp->platform,
                'postUrl' => $sp->post_url,
                'caption' => $sp->caption ?? null,
                'postedAt' => $sp->created_at->format('M d, Y · H:i'),
            ]),
        ]);
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))->map(fn ($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
    }

    private function fileType(?string $filename): string
    {
        if (! $filename) {
            return 'file';
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'])) {
            return 'image';
        }
        if (in_array($ext, ['mp4', 'mov', 'avi', 'webm', 'mkv'])) {
            return 'video';
        }
        if ($ext === 'pdf') {
            return 'pdf';
        }

        return 'file';
    }

    public function reassign(Request $request, Task $task)
    {
        if (in_array($task->status, ['approved', 'delivered', 'archived'])) {
            return back()->with('error', 'This task is closed. Reopen it before reassigning.');
        }

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        if ((int) $request->assigned_to === (int) $task->assigned_to) {
            return back()->with('error', 'Task is already assigned to that user.');
        }

        $oldAssignee = $task->assignee;
        $task->update(['assigned_to' => $request->assigned_to]);
        $newAssignee = User::find($request->assigned_to);

        $reason = trim($request->input('reason', ''));
        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'task_reassigned',
            'note' => 'Reassigned from '.($oldAssignee->name ?? 'unknown').' to '.($newAssignee->name ?? 'unknown'),
            'metadata' => [
                'from_user_id' => $oldAssignee?->id,
                'from_user_name' => $oldAssignee?->name,
                'to_user_id' => $newAssignee?->id,
                'to_user_name' => $newAssignee?->name,
                'reassigned_by' => auth()->user()->name,
                'reason' => $reason ?: null,
                'is_bulk' => false,
            ],
        ]);

        TaskTransfer::create([
            'task_id' => $task->id,
            'from_user_id' => $oldAssignee?->id,
            'to_user_id' => $newAssignee?->id,
            'transferred_by' => auth()->id(),
            'reason' => $reason ?: null,
            'transferred_at' => now(),
        ]);

        AuditLogger::log(
            'task.reassigned',
            $task,
            'Task "'.$task->title.'" reassigned from '.($oldAssignee->name ?? 'unknown').' to '.($newAssignee->name ?? 'unknown'),
            [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'from_user' => $oldAssignee?->name,
                'to_user' => $newAssignee?->name,
                'reason' => $reason ?: null,
            ]
        );

        if ($newAssignee && Setting::get('notify_on_reassign', '1') === '1') {
            $newAssignee->notify(new TaskReassigned($task, true));
        }

        if ($oldAssignee && $oldAssignee->id !== (int) $request->assigned_to && Setting::get('notify_on_reassign', '1') === '1') {
            $oldAssignee->notify(new TaskReassigned($task, false));
        }

        return back()->with('success', 'Task reassigned to '.($newAssignee->name ?? 'user').'.');
    }

    public function updateDeadline(Request $request, Task $task)
    {
        if (in_array($task->status, ['approved', 'delivered', 'archived'])) {
            return back()->with('error', 'Cannot change the deadline of a closed task.');
        }

        if ($task->project && $task->project->status === 'completed') {
            return back()->with('error', 'Cannot change the deadline — the project is already completed.');
        }

        $request->validate([
            'deadline' => 'required|date',
            'reason' => 'nullable|string|max:500',
        ]);

        $oldDeadline = $task->deadline->format(config('app.date_format', 'M d, Y'));
        $newDeadline = Carbon::parse($request->deadline);

        if ($newDeadline->toDateString() === $task->deadline->toDateString()) {
            return back()->with('error', 'The new deadline is the same as the current one.');
        }

        $task->update(['deadline' => $newDeadline]);

        $reason = trim($request->input('reason', ''));
        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'deadline_updated',
            'note' => $reason ?: null,
            'metadata' => [
                'old_deadline' => $oldDeadline,
                'new_deadline' => $newDeadline->format(config('app.date_format', 'M d, Y')),
                'changed_by_name' => auth()->user()->name,
                'reason' => $reason ?: null,
            ],
        ]);

        AuditLogger::log(
            'task.deadline_updated',
            $task,
            'Deadline changed from '.$oldDeadline.' to '.$newDeadline->format(config('app.date_format', 'M d, Y')),
            ['task_id' => $task->id, 'old_deadline' => $oldDeadline, 'new_deadline' => $newDeadline->format(config('app.date_format', 'M d, Y'))]
        );

        if ($task->assignee && Setting::get('notify_on_reassign', '1') === '1') {
            $task->assignee->notify(new TaskReassigned($task, true));
        }

        return back()->with('success', 'Deadline updated to '.$newDeadline->format(config('app.date_format', 'M d, Y')).'.');
    }

    public function addAttachment(Request $request, Task $task)
    {
        abort_unless(auth()->user()->hasPermission('manage_tasks'), 403);

        $request->validate([
            'attachments' => 'required|array|min:1',
            'attachments.*' => 'file',
        ]);

        $names = [];
        $nas = app(NasService::class);
        foreach ($request->file('attachments') as $file) {
            $path = $file->store("task-attachments/{$task->id}", 'public');
            $nasPath = $nas->copyToNas($task, $path, $file->getClientOriginalName(), '03_Working');
            $nas->copyToNasReference($task, $path, $file->getClientOriginalName());
            ProjectAttachment::create([
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'type' => 'file',
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'nas_path' => $nasPath,
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
            $names[] = $file->getClientOriginalName();
        }

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'attachment_added',
            'note' => auth()->user()->name.' added '.count($names).' attachment(s): '.implode(', ', $names),
            'metadata' => [
                'filenames' => $names,
                'uploaded_by' => auth()->user()->name,
            ],
        ]);

        return back()->with('success', count($names).' attachment(s) added.');
    }

    public function deleteAttachment(Task $task, ProjectAttachment $attachment)
    {
        abort_unless(auth()->user()->hasPermission('delete_tasks'), 403);

        $isTaskSpecific = (int) $attachment->task_id === (int) $task->id;
        $isProjectLevel = is_null($attachment->task_id) && (int) $attachment->project_id === (int) $task->project_id;
        abort_unless($isTaskSpecific || $isProjectLevel, 403);

        $filename = $attachment->name;
        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'attachment_deleted',
            'note' => auth()->user()->name.' deleted attachment "'.$filename.'"',
            'metadata' => [
                'filename' => $filename,
                'deleted_by' => auth()->user()->name,
            ],
        ]);

        AuditLogger::log(
            'task.attachment_deleted',
            $task,
            'Attachment "'.$filename.'" deleted from task "'.$task->title.'"',
            ['task_id' => $task->id, 'filename' => $filename]
        );

        return back()->with('success', '"'.$filename.'" deleted.');
    }

    public function approveDeadlineExtension(Task $task, DeadlineExtensionRequest $extensionRequest)
    {
        $extensionRequest->update([
            'status' => 'approved',
            'responded_by' => auth()->id(),
            'responded_at' => now(),
        ]);

        $oldDeadline = $task->deadline?->format('Y-m-d');
        $task->update(['deadline' => $extensionRequest->requested_deadline]);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'deadline_updated',
            'note' => 'Deadline extended via admin approval.',
            'metadata' => [
                'old_deadline' => $oldDeadline,
                'new_deadline' => $extensionRequest->requested_deadline->format('Y-m-d'),
                'changed_by_name' => auth()->user()->name,
                'reason' => 'Extension request approved',
            ],
        ]);

        $extensionRequest->user->notify(new DeadlineExtensionResponded($task, $extensionRequest));

        return back()->with('success', 'Deadline extension approved — task deadline updated.');
    }

    public function rejectDeadlineExtension(Request $request, Task $task, DeadlineExtensionRequest $extensionRequest)
    {
        $request->validate(['admin_note' => 'required|string|min:3|max:1000']);

        $extensionRequest->update([
            'status' => 'rejected',
            'admin_note' => $request->input('admin_note'),
            'responded_by' => auth()->id(),
            'responded_at' => now(),
        ]);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'deadline_extension_rejected',
            'note' => 'Deadline extension request rejected.'.($request->input('admin_note') ? ' Note: '.$request->input('admin_note') : ''),
            'metadata' => ['admin_note' => $request->input('admin_note')],
        ]);

        $extensionRequest->user->notify(new DeadlineExtensionResponded($task, $extensionRequest));

        return back()->with('success', 'Deadline extension rejected.');
    }
}

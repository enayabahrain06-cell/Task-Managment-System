<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Notifications\SocialMediaAssigned;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        if (!auth()->user()->hasPermission('manage_projects')) {
            abort(403, 'You do not have permission to manage Projects.');
        }

        $query = Project::where('is_quick', false)
            ->withCount('tasks')
            ->withCount(['tasks as completed_tasks_count' => fn($q) => $q->whereIn('status', ['completed', 'delivered', 'approved'])])
            ->withCount(['tasks as social_pending_count'  => fn($q) => $q->where('social_required', true)->whereNull('social_posted_at')])
            ->with([
                'members'  => fn($q) => $q->select('users.id','users.name','users.avatar')->limit(5),
                'customer' => fn($q) => $q->select('id','name','company'),
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif (!$request->boolean('overdue')) {
            $query->where('status', '!=', 'completed');
        }
        if ($request->boolean('overdue')) {
            $query->whereNotNull('deadline')
                  ->where('deadline', '<', now())
                  ->where('status', '!=', 'completed');
        }

        $projects = $query
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderBy('deadline', 'asc')
            ->paginate(15)
            ->withQueryString();
        $users = User::whereIn('role', ['user', 'manager'])->orderBy('name')->get();
        $stats = [
            'total'     => Project::where('is_quick', false)->count(),
            'active'    => Project::where('status', 'active')->where('is_quick', false)->count(),
            'completed' => Project::where('status', 'completed')->where('is_quick', false)->count(),
            'overdue'   => Project::where('is_quick', false)->whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->where('status', '!=', 'completed')
                ->count(),
        ];

        // Mobile card view (resources/views/admin/projects/index.blade.php, <=768px partial):
        // full, unpaginated Active/Completed splits since the mobile UI has no pager.
        $mobileProjectsQuery = fn () => Project::where('is_quick', false)
            ->withCount('tasks')
            ->withCount(['tasks as completed_tasks_count' => fn ($q) => $q->whereIn('status', ['completed', 'delivered', 'approved'])]);
        $activeProjects = $mobileProjectsQuery()->where('status', '!=', 'completed')->orderBy('deadline')->get();
        $completedProjects = $mobileProjectsQuery()->where('status', 'completed')->orderByDesc('updated_at')->get();

        return view('admin.projects.index', compact('projects', 'users', 'stats', 'activeProjects', 'completedProjects'));
    }

    public function create()
    {
        $users     = User::whereIn('role', ['user', 'manager'])->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        return view('admin.projects.create', compact('users', 'customers'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_projects')) {
            abort(403);
        }

        $request->validate([
            'name'                            => 'required|string|max:255',
            'description'                     => 'nullable|string',
            'deadline'                        => 'nullable|date|after_or_equal:today',
            'first_review_date'               => 'nullable|date',
            'customer_id'                     => 'nullable|exists:customers,id',
            'members'                         => 'nullable|array',
            'members.*'                       => 'exists:users,id',
            'tasks'                           => 'nullable|array',
            'tasks.*.title'                   => 'nullable|string|max:255',
            'tasks.*.task_type'               => 'nullable|string|max:100',
            'tasks.*.tags'                    => 'nullable|string|max:500',
            'tasks.*.reviewer_id'             => 'nullable|exists:users,id',
            'tasks.*.priority'                => 'nullable|in:low,medium,high',
            'tasks.*.deadline'                => 'nullable|date',
            'tasks.*.description'             => 'nullable|string',
            'tasks.*.assignees'               => 'nullable|array',
            'tasks.*.assignees.*.user_id'     => 'nullable|exists:users,id',
            'tasks.*.assignees.*.role'        => 'nullable|string|max:255',
            // Attachments
            'attachments'                     => 'nullable|array',
            'attachments.*'                   => 'file',
            'links'                           => 'nullable|array',
            'links.*.url'                     => 'nullable|url|max:500',
            'links.*.label'                   => 'nullable|string|max:200',
        ]);

        $project = Project::create([
            'name'              => $request->name,
            'description'       => $request->description,
            'deadline'          => $request->deadline,
            'first_review_date' => $request->first_review_date ?: null,
            'status'            => $request->input('status', 'active'),
            'created_by'        => auth()->id(),
            'customer_id'       => $request->customer_id ?: null,
        ]);

        if ($request->filled('members')) {
            $project->members()->sync($request->members);
        }

        // Store uploaded files
        if ($request->hasFile('attachments')) {
            $nas = app(\App\Services\NasService::class);
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("project-attachments/{$project->id}", 'public');
                $nasPath = $nas->copyToNasProjectAttachment($project, $path, $file->getClientOriginalName());
                ProjectAttachment::create([
                    'project_id'  => $project->id,
                    'type'        => 'file',
                    'name'        => $file->getClientOriginalName(),
                    'path'        => $path,
                    'nas_path'    => $nasPath,
                    'size'        => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        // Store links
        foreach ($request->input('links', []) as $link) {
            if (!empty($link['url'])) {
                ProjectAttachment::create([
                    'project_id'  => $project->id,
                    'type'        => 'link',
                    'name'        => $link['label'] ?: $link['url'],
                    'path'        => $link['url'],
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        $taskCount         = 0;
        $allTaskAssigneeIds = [];

        foreach ($request->input('tasks', []) as $taskData) {
            if (empty($taskData['title'])) {
                continue;
            }

            $validAssignees = collect($taskData['assignees'] ?? [])
                ->filter(fn($a) => !empty($a['user_id']))
                ->values();

            $primaryAssigneeId = $validAssignees->first()['user_id'] ?? null;

            $tags = null;
            if (!empty($taskData['tags'])) {
                $tags = collect(preg_split('/[\s,]+/', trim($taskData['tags'])))
                    ->map(fn($t) => ltrim(trim($t), '#'))
                    ->filter()
                    ->values()
                    ->toArray();
            }

            $task = Task::create([
                'title'       => $taskData['title'],
                'description' => $taskData['description'] ?? null,
                'assigned_to' => $primaryAssigneeId,
                'priority'    => $taskData['priority'] ?? Setting::get('default_task_priority', 'medium'),
                'deadline'    => $taskData['deadline'] ?? $request->deadline,
                'project_id'  => $project->id,
                'status'      => $primaryAssigneeId ? 'assigned' : 'draft',
                'created_by'  => auth()->id(),
                'reviewer_id' => $taskData['reviewer_id'] ?? null,
                'task_type'   => $taskData['task_type'] ?? null,
                'tags'        => $tags,
            ]);

            $syncData = [];
            foreach ($validAssignees as $a) {
                $syncData[$a['user_id']] = ['role_in_task' => $a['role'] ?? null];
                $allTaskAssigneeIds[] = (int) $a['user_id'];
            }
            if (!empty($syncData)) {
                $task->assignees()->sync($syncData);
            }

            $taskCount++;

            if (Setting::get('notify_on_assign', '1') === '1') {
                foreach ($validAssignees as $a) {
                    $assignee = User::find($a['user_id']);
                    if ($assignee && $assignee->id !== auth()->id()) {
                        $assignee->notify(new TaskAssigned($task));
                    }
                }
            }
        }

        // Auto-add task assignees as project members
        if (!empty($allTaskAssigneeIds)) {
            $project->members()->syncWithoutDetaching(array_unique($allTaskAssigneeIds));
        }

        $msg = $taskCount > 0
            ? "Project created with {$taskCount} task(s) assigned."
            : 'Project created successfully.';

        AuditLogger::log(
            'project.created',
            $project,
            'Project "' . $project->name . '" created' . ($taskCount > 0 ? " with {$taskCount} task(s)" : ''),
            ['project_id' => $project->id, 'project_name' => $project->name, 'task_count' => $taskCount]
        );

        return redirect()->route('admin.projects.show', $project)->with('success', $msg);
    }

    public function show(Project $project)
    {
        $project->load('tasks.assignee', 'tasks.socialAssignee', 'members', 'customer');
        $pendingApprovalCount = $project->tasks()->where('status', 'submitted')->count();
        $pendingSocialCount   = $project->tasks()->where('social_required', true)->whereNull('social_posted_at')->count();
        return view('admin.projects.show', compact('project', 'pendingApprovalCount', 'pendingSocialCount'));
    }

    public function edit(Project $project)
    {
        $users     = User::whereIn('role', ['user', 'manager'])->orderBy('name')->get();
        $memberIds = $project->members()->pluck('users.id')->toArray();
        $customers = Customer::orderBy('name')->get();
        return view('admin.projects.edit', compact('project', 'users', 'memberIds', 'customers'));
    }

    public function update(Request $request, Project $project)
    {
        if (!auth()->user()->hasPermission('manage_projects')) {
            abort(403);
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'required|date',
            'status'      => 'required|in:active,completed,overdue',
            'customer_id' => 'nullable|exists:customers,id',
            'members'     => 'nullable|array',
            'members.*'   => 'exists:users,id',
        ]);

        $project->update(array_merge(
            $request->only('name', 'description', 'deadline', 'status'),
            ['customer_id' => $request->customer_id ?: null]
        ));
        $project->members()->sync($request->members ?? []);

        AuditLogger::log(
            'project.updated',
            $project,
            'Project "' . $project->name . '" updated',
            ['project_id' => $project->id, 'project_name' => $project->name, 'status' => $project->status]
        );

        return redirect()->route('admin.projects.index')->with('success', 'Project updated.');
    }

    public function reopen(Project $project)
    {
        if ($project->status !== 'completed') {
            return back()->with('error', 'Only completed projects can be reopened.');
        }

        $project->update(['status' => 'active']);

        AuditLogger::log(
            'project.reopened',
            $project,
            'Project "' . $project->name . '" reopened',
            ['project_id' => $project->id, 'project_name' => $project->name]
        );

        return back()->with('success', 'Project "' . $project->name . '" has been reopened and set back to Active.');
    }

    public function close(Project $project)
    {
        if ($project->status === 'completed') {
            return back()->with('error', 'Project is already completed.');
        }

        $pendingSocial = $project->tasks()->where('social_required', true)->whereNull('social_posted_at')->count();
        if ($pendingSocial > 0) {
            return back()->with('error', "Cannot complete project: {$pendingSocial} task(s) still have pending social media posts that haven't been published.");
        }

        $project->update(['status' => 'completed']);

        AuditLogger::log(
            'project.closed',
            $project,
            'Project "' . $project->name . '" closed and marked as Completed',
            ['project_id' => $project->id, 'project_name' => $project->name]
        );

        return back()->with('success', 'Project "' . $project->name . '" has been closed and marked as Completed.');
    }

    public function destroy(Project $project)
    {
        if (!auth()->user()->hasPermission('delete_projects')) {
            abort(403);
        }

        $name = $project->name;
        AuditLogger::log(
            'project.deleted',
            $project,
            'Project "' . $name . '" deleted',
            ['project_id' => $project->id, 'project_name' => $name]
        );
        $project->delete();
        return redirect()->route('admin.projects.index')->with('success', 'Project deleted.');
    }

    public function tasksCreate(Project $project)
    {
        if (!auth()->user()->hasPermission('manage_tasks')) {
            abort(403);
        }

        $members = $project->members()->get();
        if ($members->isEmpty()) {
            $members = User::where('role', '!=', 'admin')->get();
        }
        $customers = Customer::orderBy('name')->get();
        return view('admin.projects.tasks-create', compact('project', 'members', 'customers'));
    }

    public function checkDuplicateTitle(Request $request)
    {
        $title = trim((string) $request->input('title', ''));
        $customerId = $request->input('customer_id');
        if (!$customerId && $request->filled('project_id')) {
            $customerId = Project::find($request->input('project_id'))?->customer_id;
        }

        if ($title === '' || !$customerId) {
            return response()->json(['duplicate' => false]);
        }

        $matches = Task::where('customer_id', $customerId)
            ->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'deadline']);

        return response()->json([
            'duplicate' => $matches->isNotEmpty(),
            'count'     => $matches->count(),
            'tasks'     => $matches->map(fn($t) => [
                'id'       => $t->id,
                'status'   => $t->status,
                'deadline' => $t->deadline?->format('M d, Y'),
                'url'      => route('admin.tasks.show', $t->id),
            ]),
        ]);
    }

    public function tasksStore(Request $request, Project $project)
    {
        if (!auth()->user()->hasPermission('manage_tasks')) {
            abort(403);
        }

        $request->validate([
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            'task_type'              => 'nullable|string|max:100',
            'tags'                   => 'nullable|string|max:500',
            'customer_id'            => 'nullable|exists:customers,id',
            'reviewer_id'            => 'nullable|exists:users,id',
            'priority'               => 'required|in:low,medium,high',
            'deadline'               => 'required|date',
            'assignees'              => 'nullable|array',
            'assignees.*.user_id'    => 'required|exists:users,id',
            'assignees.*.role'       => 'nullable|string|max:255',
            'recurring_type'         => 'nullable|in:daily,weekly,monthly',
            'recurring_end_date'     => 'nullable|date|after:deadline',
            'recurring_max'          => 'nullable|integer|min:1|max:365',
        ]);

        $customerIdForDupCheck = $request->customer_id ?: $project->customer_id;
        if ($customerIdForDupCheck) {
            $isDuplicateTitle = Task::where('customer_id', $customerIdForDupCheck)
                ->whereRaw('LOWER(title) = ?', [mb_strtolower(trim($request->title))])
                ->exists();

            if ($isDuplicateTitle) {
                return back()->withErrors([
                    'title' => 'A task with this title already exists for this customer. Please use a different title.',
                ])->withInput();
            }
        }

        $validAssignees = collect($request->input('assignees', []))
            ->filter(fn($a) => !empty($a['user_id']))
            ->values();

        $primaryAssigneeId = $validAssignees->first()['user_id'] ?? null;

        $tags = null;
        if ($request->filled('tags')) {
            $tags = collect(preg_split('/[\s,]+/', trim($request->tags)))
                ->map(fn($t) => ltrim(trim($t), '#'))
                ->filter()
                ->values()
                ->toArray();
        }

        $recurringType = $request->filled('recurring_type') ? $request->recurring_type : null;

        $task = Task::create([
            'title'               => $request->title,
            'description'         => $request->description,
            'assigned_to'         => $primaryAssigneeId,
            'priority'            => $request->priority,
            'deadline'            => $request->deadline,
            'project_id'          => $project->id,
            'customer_id'         => $request->customer_id ?: null,
            'status'              => $primaryAssigneeId ? 'assigned' : 'draft',
            'created_by'          => auth()->id(),
            'reviewer_id'         => $request->reviewer_id,
            'task_type'           => $request->task_type,
            'tags'                => $tags,
            'is_recurring'        => (bool) $recurringType,
            'recurring_type'      => $recurringType,
            'recurring_end_date'  => $recurringType && $request->filled('recurring_end_date') ? $request->recurring_end_date : null,
            'recurring_max'       => $recurringType && $request->filled('recurring_max') ? (int)$request->recurring_max : null,
        ]);

        $syncData = [];
        $assigneeIds = [];
        foreach ($validAssignees as $a) {
            $syncData[$a['user_id']] = ['role_in_task' => $a['role'] ?? null];
            $assigneeIds[] = (int) $a['user_id'];
        }
        if (!empty($syncData)) {
            $task->assignees()->sync($syncData);
        }

        // Auto-add task assignees as project members
        if (!empty($assigneeIds)) {
            $project->members()->syncWithoutDetaching(array_unique($assigneeIds));
        }

        if (Setting::get('notify_on_assign', '1') === '1') {
            foreach ($validAssignees as $a) {
                $assignee = User::find($a['user_id']);
                if ($assignee && $assignee->id !== auth()->id()) {
                    $assignee->notify(new TaskAssigned($task));
                }
            }
        }

        return redirect()->route('admin.projects.show', $project)->with('success', 'Task created.');
    }

    public function quickTaskStore(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_tasks')) {
            abort(403);
        }

        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'project_id'     => 'nullable|exists:projects,id',
            'customer_id'    => 'nullable|exists:customers,id',
            'assigned_to'    => 'required|exists:users,id',
            'priority'       => 'required|in:low,medium,high',
            'deadline'       => 'required|date',
            'attachments'    => 'nullable|array',
            'attachments.*'  => 'file',
        ]);

        $customerIdForDupCheck = $request->customer_id
            ?: ($request->project_id ? Project::find($request->project_id)?->customer_id : null);

        if ($customerIdForDupCheck) {
            $isDuplicateTitle = Task::where('customer_id', $customerIdForDupCheck)
                ->whereRaw('LOWER(title) = ?', [mb_strtolower(trim($request->title))])
                ->exists();

            if ($isDuplicateTitle) {
                return back()->withErrors([
                    'title' => 'A task with this title already exists for this customer. Please use a different title.',
                ])->withInput();
            }
        }

        $projectId = $request->project_id;
        if (!$projectId) {
            $quickProject = Project::firstOrCreate(
                ['name' => 'Quick Tasks'],
                [
                    'description' => 'Auto-created project for standalone quick tasks.',
                    'status'      => 'active',
                    'is_quick'    => true,
                    'deadline'    => now()->addYears(10),
                    'created_by'  => auth()->id(),
                ]
            );
            if (!$quickProject->is_quick) {
                $quickProject->update(['is_quick' => true]);
            }
            $projectId = $quickProject->id;
        }

        $task = Task::create(array_merge(
            $request->only('title', 'description', 'assigned_to', 'priority', 'deadline'),
            [
                'project_id'  => $projectId,
                'customer_id' => $request->customer_id ?: null,
                'status'      => 'assigned',
                'created_by'  => auth()->id(),
            ]
        ));

        if ($request->hasFile('attachments')) {
            $nas = app(\App\Services\NasService::class);
            foreach ($request->file('attachments') as $file) {
                $path    = $file->store("task-attachments/{$task->id}", 'public');
                $nasPath = $nas->copyToNas($task, $path, $file->getClientOriginalName(), '03_Working', 0, keepLocal: true);
                ProjectAttachment::create([
                    'project_id'  => $task->project_id,
                    'task_id'     => $task->id,
                    'type'        => 'file',
                    'name'        => $file->getClientOriginalName(),
                    'path'        => $path,
                    'nas_path'    => $nasPath,
                    'size'        => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        if (Setting::get('notify_on_assign', '1') === '1') {
            $assignee = User::find($request->assigned_to);
            if ($assignee && $assignee->id !== auth()->id()) {
                $assignee->notify(new TaskAssigned($task));
            }
        }

        return redirect()->route('admin.dashboard')->with('success', 'Task created and assigned.');
    }

    public function quickSMPostStore(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_tasks')) {
            abort(403);
        }

        // Fallback: never let max_upload_mb of 0 break uploads — treat 0 as 20 MB
        $maxKb = max(1, (int) Setting::get('max_upload_mb', 20)) * 1024;

        $request->validate([
            'title'              => 'required|string|max:255',
            'social_assigned_to' => 'required|exists:users,id',
            'social_platforms'   => 'required|array|min:1',
            'social_platforms.*' => 'string',
            'social_description' => 'nullable|string',
            'social_caption'     => 'nullable|string',
            'social_budget'      => 'nullable|numeric|min:0',
            'deadline'           => 'required|date',
            'customer_id'        => 'nullable|exists:customers,id',
            'attachments'        => 'nullable|array',
            'attachments.*'      => 'nullable|file|max:' . $maxKb,
        ]);

        if ($request->customer_id) {
            $isDuplicateTitle = Task::where('customer_id', $request->customer_id)
                ->whereRaw('LOWER(title) = ?', [mb_strtolower(trim($request->title))])
                ->exists();

            if ($isDuplicateTitle) {
                return back()->withErrors([
                    'title' => 'A task with this title already exists for this customer. Please use a different title.',
                ])->withInput();
            }
        }

        $quickProject = Project::firstOrCreate(
            ['name' => 'Quick Tasks'],
            [
                'description' => 'Auto-created project for standalone quick tasks.',
                'status'      => 'active',
                'is_quick'    => true,
                'deadline'    => now()->addYears(10),
                'created_by'  => auth()->id(),
            ]
        );
        if (!$quickProject->is_quick) {
            $quickProject->update(['is_quick' => true]);
        }

        $task = Task::create([
            'project_id'         => $quickProject->id,
            'customer_id'        => $request->customer_id ?: null,
            'title'              => $request->title,
            'assigned_to'        => auth()->id(),
            'social_assigned_to' => $request->social_assigned_to,
            'social_required'    => true,
            'social_platforms'   => $request->social_platforms,
            'social_description' => $request->social_description,
            'social_caption'     => $request->social_caption,
            'social_budget'      => $request->social_budget ?: null,
            'deadline'           => $request->deadline,
            'status'             => 'approved',
            'priority'           => 'medium',
            'created_by'         => auth()->id(),
            'task_type'          => 'social',
        ]);

        if ($request->hasFile('attachments')) {
            $nas = app(\App\Services\NasService::class);
            foreach ($request->file('attachments') as $file) {
                $path    = $file->store("task-attachments/{$task->id}", 'public');
                $nasPath = $nas->copyToNas($task, $path, $file->getClientOriginalName(), '03_Working', 0, keepLocal: true);
                ProjectAttachment::create([
                    'project_id'  => $task->project_id,
                    'task_id'     => $task->id,
                    'type'        => 'file',
                    'name'        => $file->getClientOriginalName(),
                    'path'        => $path,
                    'nas_path'    => $nasPath,
                    'size'        => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        // Log the task creation and social assignment in the activity feed
        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'task_created',
            'note'     => 'Quick SM Post created by ' . auth()->user()->name,
            'metadata' => ['creator_id' => auth()->id(), 'creator_name' => auth()->user()->name],
        ]);

        $assignee = User::find($request->social_assigned_to);
        if ($assignee) {
            TaskLog::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'action'   => 'social_assigned',
                'note'     => 'Social media post assigned to ' . $assignee->name,
                'metadata' => [
                    'assignee_id'   => $assignee->id,
                    'assignee_name' => $assignee->name,
                    'assigned_by'   => auth()->user()->name,
                ],
            ]);

            if ($assignee->id !== auth()->id()) {
                $assignee->notify(new SocialMediaAssigned($task, auth()->user()));
            }
        }

        AuditLogger::log('quick_sm_post_created', $task, "Quick SM Post created: {$task->title}");

        return redirect()->route('admin.dashboard')
            ->with('success', '✓ SM Post "' . $task->title . '" assigned to ' . ($assignee?->name ?? 'user') . ' successfully.');
    }
}

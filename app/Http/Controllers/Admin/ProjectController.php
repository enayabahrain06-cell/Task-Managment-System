<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        if (!auth()->user()->hasPermission('manage_projects')) {
            abort(403, 'You do not have permission to manage Projects.');
        }

        $query = Project::withCount('tasks')
            ->withCount(['tasks as completed_tasks_count' => fn($q) => $q->whereIn('status', ['completed', 'delivered', 'approved'])])
            ->with(['members' => fn($q) => $q->select('users.id','users.name','users.avatar')->limit(5)]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
            'total'     => Project::count(),
            'active'    => Project::where('status', 'active')->count(),
            'completed' => Project::where('status', 'completed')->count(),
            'overdue'   => Project::whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->where('status', '!=', 'completed')
                ->count(),
        ];
        return view('admin.projects.index', compact('projects', 'users', 'stats'));
    }

    public function create()
    {
        $users = User::whereIn('role', ['user', 'manager'])->orderBy('name')->get();
        return view('admin.projects.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                            => 'required|string|max:255',
            'description'                     => 'nullable|string',
            'deadline'                        => 'nullable|date|after:now',
            'first_review_date'               => 'nullable|date',
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
            'attachments.*'                   => 'file|max:20480',
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
        ]);

        if ($request->filled('members')) {
            $project->members()->sync($request->members);
        }

        // Store uploaded files
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("project-attachments/{$project->id}", 'public');
                ProjectAttachment::create([
                    'project_id'  => $project->id,
                    'type'        => 'file',
                    'name'        => $file->getClientOriginalName(),
                    'path'        => $path,
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
                'priority'    => $taskData['priority'] ?? 'medium',
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
        $project->load('tasks.assignee', 'members');
        $pendingApprovalCount = $project->tasks()->where('status', 'submitted')->count();
        return view('admin.projects.show', compact('project', 'pendingApprovalCount'));
    }

    public function edit(Project $project)
    {
        $users          = User::whereIn('role', ['user', 'manager'])->orderBy('name')->get();
        $memberIds      = $project->members()->pluck('users.id')->toArray();
        return view('admin.projects.edit', compact('project', 'users', 'memberIds'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'required|date',
            'status'      => 'required|in:active,completed,overdue',
            'members'     => 'nullable|array',
            'members.*'   => 'exists:users,id',
        ]);

        $project->update($request->only('name', 'description', 'deadline', 'status'));
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
        $members = $project->members()->get();
        // Fall back to all non-admin users if no members assigned yet
        if ($members->isEmpty()) {
            $members = User::where('role', '!=', 'admin')->get();
        }
        return view('admin.projects.tasks-create', compact('project', 'members'));
    }

    public function tasksStore(Request $request, Project $project)
    {
        $request->validate([
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            'task_type'              => 'nullable|string|max:100',
            'tags'                   => 'nullable|string|max:500',
            'reviewer_id'            => 'nullable|exists:users,id',
            'priority'               => 'required|in:low,medium,high',
            'deadline'               => 'required|date',
            'assignees'              => 'nullable|array',
            'assignees.*.user_id'    => 'required|exists:users,id',
            'assignees.*.role'       => 'nullable|string|max:255',
        ]);

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

        $task = Task::create([
            'title'       => $request->title,
            'description' => $request->description,
            'assigned_to' => $primaryAssigneeId,
            'priority'    => $request->priority,
            'deadline'    => $request->deadline,
            'project_id'  => $project->id,
            'status'      => $primaryAssigneeId ? 'assigned' : 'draft',
            'created_by'  => auth()->id(),
            'reviewer_id' => $request->reviewer_id,
            'task_type'   => $request->task_type,
            'tags'        => $tags,
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
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id'  => 'nullable|exists:projects,id',
            'assigned_to' => 'required|exists:users,id',
            'priority'    => 'required|in:low,medium,high',
            'deadline'    => 'required|date',
        ]);

        $projectId = $request->project_id;
        if (!$projectId) {
            $quickProject = Project::firstOrCreate(
                ['name' => 'Quick Tasks'],
                [
                    'description' => 'Auto-created project for standalone quick tasks.',
                    'status'      => 'active',
                    'deadline'    => now()->addYears(10),
                    'created_by'  => auth()->id(),
                ]
            );
            $projectId = $quickProject->id;
        }

        $task = Task::create(array_merge(
            $request->only('title', 'description', 'assigned_to', 'priority', 'deadline'),
            ['project_id' => $projectId, 'status' => 'assigned', 'created_by' => auth()->id()]
        ));

        if (Setting::get('notify_on_assign', '1') === '1') {
            $assignee = User::find($request->assigned_to);
            if ($assignee && $assignee->id !== auth()->id()) {
                $assignee->notify(new TaskAssigned($task));
            }
        }

        return redirect()->route('admin.dashboard')->with('success', 'Task created and assigned.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::withCount('tasks')
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderBy('deadline', 'asc')
            ->paginate(15);
        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        $users = User::whereIn('role', ['user', 'manager'])->orderBy('name')->get();
        return view('admin.projects.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
            'deadline'              => 'required|date|after:now',
            'members'               => 'nullable|array',
            'members.*'             => 'exists:users,id',
            'tasks'                 => 'nullable|array',
            'tasks.*.title'         => 'required_with:tasks.*.assigned_to|string|max:255',
            'tasks.*.assigned_to'   => 'nullable|exists:users,id',
            'tasks.*.priority'      => 'nullable|in:low,medium,high',
            'tasks.*.deadline'      => 'nullable|date',
        ]);

        $project = Project::create([
            'name'        => $request->name,
            'description' => $request->description,
            'deadline'    => $request->deadline,
            'created_by'  => auth()->id(),
        ]);

        if ($request->filled('members')) {
            $project->members()->sync($request->members);
        }

        $taskCount = 0;
        foreach ($request->input('tasks', []) as $taskData) {
            if (empty($taskData['title']) || empty($taskData['assigned_to']) || empty($taskData['deadline'])) {
                continue;
            }
            $task = Task::create([
                'title'       => $taskData['title'],
                'description' => $taskData['description'] ?? null,
                'assigned_to' => $taskData['assigned_to'],
                'priority'    => $taskData['priority'] ?? 'medium',
                'deadline'    => $taskData['deadline'],
                'project_id'  => $project->id,
                'status'      => 'pending',
            ]);
            $taskCount++;
            if (Setting::get('notify_on_assign', '1') === '1') {
                $assignee = User::find($taskData['assigned_to']);
                if ($assignee && $assignee->id !== auth()->id()) {
                    $assignee->notify(new TaskAssigned($task));
                }
            }
        }

        $msg = $taskCount > 0
            ? "Project created with {$taskCount} task(s) assigned."
            : 'Project created successfully.';

        return redirect()->route('admin.projects.show', $project)->with('success', $msg);
    }

    public function show(Project $project)
    {
        $project->load('tasks.assignee', 'members');
        $pendingApprovalCount = $project->tasks()->where('status', 'pending_approval')->count();
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

        return redirect()->route('admin.projects.index')->with('success', 'Project updated.');
    }

    public function destroy(Project $project)
    {
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
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'priority'    => 'required|in:low,medium,high',
            'deadline'    => 'required|date',
        ]);

        $task = Task::create(array_merge(
            $request->only('title', 'description', 'assigned_to', 'priority', 'deadline'),
            ['project_id' => $project->id]
        ));

        if (Setting::get('notify_on_assign', '1') === '1') {
            $assignee = User::find($request->assigned_to);
            if ($assignee && $assignee->id !== auth()->id()) {
                $assignee->notify(new TaskAssigned($task));
            }
        }

        return redirect()->route('admin.projects.show', $project)->with('success', 'Task created.');
    }

    public function quickTaskStore(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id'  => 'required|exists:projects,id',
            'assigned_to' => 'required|exists:users,id',
            'priority'    => 'required|in:low,medium,high',
            'deadline'    => 'required|date',
        ]);

        $task = Task::create($request->only('title', 'description', 'project_id', 'assigned_to', 'priority', 'deadline'));

        if (Setting::get('notify_on_assign', '1') === '1') {
            $assignee = User::find($request->assigned_to);
            if ($assignee && $assignee->id !== auth()->id()) {
                $assignee->notify(new TaskAssigned($task));
            }
        }

        return redirect()->route('admin.dashboard')->with('success', 'Task created and assigned.');
    }
}

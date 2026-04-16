<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::withCount('tasks')->latest()->paginate(15);
        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.projects.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date|after:now',
        ]);

        Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.projects.index')->with('success', 'Project created.');
    }

    public function show(Project $project)
    {
        $project->load('tasks.assignee');
        return view('admin.projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        return view('admin.projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
            'status' => 'required|in:active,completed,overdue',
        ]);

        $project->update($request->only('name', 'description', 'deadline', 'status'));

        return redirect()->route('admin.projects.index')->with('success', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('admin.projects.index')->with('success', 'Project deleted.');
    }

    public function tasksCreate(Project $project)
    {
        return view('admin.projects.tasks-create', compact('project'));
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

        Task::create(array_merge($request->only('title', 'description', 'assigned_to', 'priority', 'deadline'), [
            'project_id' => $project->id,
        ]));

        return redirect()->route('admin.projects.show', $project)->with('success', 'Task created.');
    }

    /**
     * Quick-create a task directly from the dashboard modal.
     */
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

        Task::create($request->only('title', 'description', 'project_id', 'assigned_to', 'priority', 'deadline'));

        return redirect()->route('admin.dashboard')->with('success', 'Task created and assigned successfully.');
    }
}


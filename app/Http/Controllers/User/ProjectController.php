<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Project;

class ProjectController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasPermission('view_projects')) {
            return redirect()->route('user.dashboard')->with('error', "You don't have permission to access Projects.");
        }

        $projects = auth()->user()->projects()
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn($q) => $q->where('status', 'completed')])
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderBy('deadline')
            ->get();

        return view('user.projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        if (!auth()->user()->hasPermission('view_projects')) {
            return redirect()->route('user.dashboard')->with('error', "You don't have permission to access Projects.");
        }

        // Ensure user is a member
        if (!$project->members()->where('users.id', auth()->id())->exists()) {
            abort(403);
        }

        $project->load([
            'tasks.assignee',
            'tasks.submissions',
            'members',
        ]);

        $stats = [
            'total'            => $project->tasks->count(),
            'completed'        => $project->tasks->where('status', 'completed')->count(),
            'in_progress'      => $project->tasks->where('status', 'in_progress')->count(),
            'pending'          => $project->tasks->where('status', 'pending')->count(),
            'submitted'        => $project->tasks->where('status', 'submitted')->count(),
        ];
        $stats['rate'] = $stats['total'] > 0 ? round($stats['completed'] / $stats['total'] * 100) : 0;

        return view('user.projects.show', compact('project', 'stats'));
    }
}

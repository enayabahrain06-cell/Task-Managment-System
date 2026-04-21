<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('view_team')) {
            return redirect()->route('user.dashboard')->with('error', "You don't have permission to access Team Members.");
        }

        $allowedViews = ['team', 'permissions', 'roles', 'former'];
        $view = in_array($request->input('view'), $allowedViews) ? $request->input('view') : 'team';

        $doneStatuses = ['delivered', 'approved', 'archived'];
        $allRoles     = Role::ordered();

        $stats = [
            'total'    => User::count(),
            'active'   => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'archived' => User::where('status', 'archived')->count(),
            'admins'   => User::where('role', 'admin')->count(),
            'managers' => User::where('role', 'manager')->count(),
        ];

        $query = User::withCount([
            'assignedTasks as total_tasks',
            'assignedTasks as completed_tasks' => fn($q) => $q->whereIn('status', $doneStatuses),
            'assignedTasks as pending_tasks'   => fn($q) => $q->whereNotIn('status', $doneStatuses),
            'tasks',
        ]);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('username', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->where('status', '!=', 'archived');
        $members = $query->orderBy('role')->paginate(20)->withQueryString();

        $doneFormer = ['delivered', 'approved', 'archived'];
        $formerEmployees = User::where('status', 'archived')
            ->withCount([
                'assignedTasks as total_tasks',
                'assignedTasks as completed_tasks' => fn($q) => $q->whereIn('status', $doneFormer),
            ])
            ->with('archivedBy')
            ->orderByDesc('archived_at')
            ->get();

        $totalMembers   = $stats['total'] - ($stats['archived'] ?? 0);
        $activeMembers  = $stats['active'];
        $totalCompleted = Task::whereIn('status', $doneStatuses)->count();
        $totalPending   = Task::whereNotIn('status', $doneStatuses)->count();

        return view('team.index', compact(
            'members', 'totalMembers', 'activeMembers', 'totalCompleted', 'totalPending',
            'allRoles', 'view', 'stats', 'formerEmployees'
        ));
    }
}

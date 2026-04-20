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

        $authUser = auth()->user();
        $view = $request->input('view', 'team');

        $doneStatuses = ['delivered', 'approved', 'archived'];

        $members = User::withCount([
            'assignedTasks as total_tasks',
            'assignedTasks as completed_tasks' => fn($q) => $q->whereIn('status', $doneStatuses),
            'assignedTasks as pending_tasks'   => fn($q) => $q->whereNotIn('status', $doneStatuses),
        ])->orderBy('role')->get();

        $totalMembers   = $members->count();
        $activeMembers  = $members->where('status', 'active')->count();
        $totalCompleted = Task::whereIn('status', $doneStatuses)->count();
        $totalPending   = Task::whereNotIn('status', $doneStatuses)->count();

        $allRoles = Role::ordered();

        // Admin manage tab data
        $users = null;
        $stats = null;

        if ($authUser->role === 'admin' && $view === 'manage') {
            $query = User::withCount('tasks');

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $users = $query->latest()->paginate(15)->withQueryString();

            $stats = [
                'total'    => User::count(),
                'active'   => User::where('status', 'active')->count(),
                'inactive' => User::where('status', 'inactive')->count(),
                'archived' => User::where('status', 'archived')->count(),
                'admins'   => User::where('role', 'admin')->count(),
                'managers' => User::where('role', 'manager')->count(),
            ];
        }

        return view('team.index', compact(
            'members', 'totalMembers', 'activeMembers', 'totalCompleted', 'totalPending',
            'allRoles', 'view', 'users', 'stats'
        ));
    }
}

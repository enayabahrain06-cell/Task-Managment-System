<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Setting;
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

        if ($view === 'roles' && auth()->user()->role === 'manager' && Setting::get('manager_can_view_roles', '0') !== '1') {
            return redirect()->route('team.index')->with('error', 'You do not have permission to view Roles.');
        }

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

        $doneIn = implode("','", $doneStatuses);

        $query = User::selectRaw("users.*,
            (SELECT COUNT(DISTINCT t.id) FROM tasks t
             WHERE t.assigned_to = users.id
                OR t.social_assigned_to = users.id
                OR t.id IN (SELECT task_id FROM task_assignees WHERE user_id = users.id)
            ) as total_tasks,
            (SELECT COUNT(DISTINCT t.id) FROM tasks t
             WHERE (t.assigned_to = users.id OR t.social_assigned_to = users.id OR t.id IN (SELECT task_id FROM task_assignees WHERE user_id = users.id))
               AND t.status IN ('{$doneIn}')
            ) as completed_tasks,
            (SELECT COUNT(DISTINCT t.id) FROM tasks t
             WHERE (t.assigned_to = users.id OR t.social_assigned_to = users.id OR t.id IN (SELECT task_id FROM task_assignees WHERE user_id = users.id))
               AND t.status NOT IN ('{$doneIn}')
            ) as pending_tasks");

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

        // Admin/Manager: replace counts with tasks they created instead of tasks assigned to them
        $members->getCollection()->transform(function ($member) use ($doneStatuses) {
            if (in_array($member->role, ['admin', 'manager'])) {
                $base = Task::where('created_by', $member->id);
                $member->total_tasks     = (clone $base)->count();
                $member->completed_tasks = (clone $base)->whereIn('status', $doneStatuses)->count();
                $member->pending_tasks   = (clone $base)->whereNotIn('status', $doneStatuses)->count();
            }
            return $member;
        });

        $doneFormer  = ['delivered', 'approved', 'archived'];
        $doneFormerIn = implode("','", $doneFormer);

        $formerEmployees = User::where('status', 'archived')
            ->selectRaw("users.*,
                (SELECT COUNT(DISTINCT t.id) FROM tasks t
                 WHERE t.assigned_to = users.id
                    OR t.id IN (SELECT task_id FROM task_assignees WHERE user_id = users.id)
                ) as total_tasks,
                (SELECT COUNT(DISTINCT t.id) FROM tasks t
                 WHERE (t.assigned_to = users.id OR t.id IN (SELECT task_id FROM task_assignees WHERE user_id = users.id))
                   AND t.status IN ('{$doneFormerIn}')
                ) as completed_tasks")
            ->with('archivedBy')
            ->orderByDesc('archived_at')
            ->get()
            ->each(function ($former) use ($doneFormer) {
                if (in_array($former->role, ['admin', 'manager'])) {
                    $base = Task::where('created_by', $former->id);
                    $former->total_tasks     = (clone $base)->count();
                    $former->completed_tasks = (clone $base)->whereIn('status', $doneFormer)->count();
                }
            });

        $totalMembers   = $stats['total'] - ($stats['archived'] ?? 0);
        $activeMembers  = $stats['active'];
        $totalCompleted = Task::whereIn('status', $doneStatuses)->count();
        $totalPending   = Task::whereNotIn('status', $doneStatuses)->count();

        $minPasswordLength     = max(6, (int) Setting::get('min_password_length', 8));
        $requireStrongPassword = Setting::get('require_strong_password', '0') === '1';
        $passwordRequirementText = $requireStrongPassword
            ? "At least {$minPasswordLength} characters, including an uppercase letter, a lowercase letter, a number, and a special character."
            : "At least {$minPasswordLength} characters.";

        return view('team.index', compact(
            'members', 'totalMembers', 'activeMembers', 'totalCompleted', 'totalPending',
            'allRoles', 'view', 'stats', 'formerEmployees',
            'minPasswordLength', 'requireStrongPassword', 'passwordRequirementText'
        ));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class SocialBudgetController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('view_reports')) {
            abort(403, 'You do not have permission to view this page.');
        }

        $projectId  = $request->input('project_id');
        $customerId = $request->input('customer_id');
        $status     = $request->input('status', 'all'); // all | pending | posted

        $query = Task::with(['project.customer', 'customer', 'socialAssignee'])
            ->where('social_required', true)
            ->when($projectId,  fn($q) => $q->where('project_id', $projectId))
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when($status === 'posted',  fn($q) => $q->whereNotNull('social_posted_at'))
            ->when($status === 'pending', fn($q) => $q->whereNull('social_posted_at'))
            ->orderByRaw('social_posted_at IS NOT NULL, created_at DESC');

        $tasks = $query->get()->map(fn($t) => [
            'id'          => $t->id,
            'title'       => $t->title,
            'project'     => $t->project->name ?? '—',
            'project_id'  => $t->project_id,
            'customer'    => $t->customer->name ?? $t->project?->customer?->name ?? '—',
            'social_user' => $t->socialAssignee?->name ?? '—',
            'budget'      => $t->social_budget,
            'caption'     => $t->social_caption,
            'description' => $t->social_description,
            'posted'      => (bool) $t->social_posted_at,
            'posted_at'   => $t->social_posted_at?->format(config('app.date_format', 'M d, Y')),
            'created_at'  => $t->created_at->format(config('app.date_format', 'M d, Y')),
            'status'      => $t->status,
        ]);

        // Summary counts (always unfiltered by status for the top cards)
        $baseCount   = Task::where('social_required', true)
            ->when($projectId,  fn($q) => $q->where('project_id', $projectId))
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId));

        $totalCount   = (clone $baseCount)->count();
        $postedCount  = (clone $baseCount)->whereNotNull('social_posted_at')->count();
        $pendingCount = (clone $baseCount)->whereNull('social_posted_at')->count();
        $withBudget   = (clone $baseCount)->whereNotNull('social_budget')->count();

        $allProjects  = Project::where('is_quick', false)->orderBy('name')->get(['id', 'name']);
        $allCustomers = Customer::orderBy('name')->get(['id', 'name', 'company']);

        return view('admin.social-budget.index', compact(
            'tasks', 'projectId', 'customerId', 'status',
            'totalCount', 'postedCount', 'pendingCount', 'withBudget',
            'allProjects', 'allCustomers'
        ));
    }
}

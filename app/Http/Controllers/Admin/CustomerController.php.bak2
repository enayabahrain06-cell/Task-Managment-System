<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskLog;
use App\Services\AuditLogger;
use App\Services\NasService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount(['projects', 'tasks'])->with('creator:id,name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('company', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(20)->withQueryString();

        // Summary data — default range: 1 month back → today
        $summaryDefaultFrom = now()->subMonth()->startOfDay();
        $summaryDefaultTo   = now()->endOfDay();

        $summaryList = Customer::withCount([
            'projects',
            'tasks' => fn($q) => $q->where('tasks.created_at', '>=', $summaryDefaultFrom)
                                   ->where('tasks.created_at', '<=', $summaryDefaultTo),
            'tasks as delivered_count' => fn($q) => $q->whereIn('status', ['delivered', 'approved'])
                                                       ->where('tasks.created_at', '>=', $summaryDefaultFrom)
                                                       ->where('tasks.created_at', '<=', $summaryDefaultTo),
        ])->orderByDesc('tasks_count')->get();

        $summaryTotals = [
            'customers' => $summaryList->count(),
            'projects'  => $summaryList->sum('projects_count'),
            'tasks'     => $summaryList->sum('tasks_count'),
            'delivered' => $summaryList->sum('delivered_count'),
        ];

        $summaryDefaultFromStr = $summaryDefaultFrom->toDateString();
        $summaryDefaultToStr   = $summaryDefaultTo->toDateString();

        return view('admin.customers.index', compact(
            'customers', 'summaryList', 'summaryTotals',
            'summaryDefaultFromStr', 'summaryDefaultToStr'
        ));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'notes'   => 'nullable|string',
            'logo'    => 'nullable|image|mimes:jpg,jpeg,png,webp,svg',
        ]);

        $data = [
            'name'       => $request->name,
            'company'    => $request->company,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'notes'      => $request->notes,
            'created_by' => auth()->id(),
        ];

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('customer-logos', 'public');
        }

        $customer = Customer::create($data);

        AuditLogger::log(
            'customer.created',
            $customer,
            'Customer "' . $customer->name . '" created',
            ['customer_id' => $customer->id]
        );

        app(NasService::class)->createCustomerFolders($customer);

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'projects' => fn($q) => $q->where('is_quick', false)->withCount('tasks')->orderBy('created_at', 'desc'),
            'socialAccounts' => fn($q) => $q->with('users:id,name,email,avatar,role')->orderBy('platform')->orderBy('name'),
        ]);

        // Load tasks from both direct customer_id and tasks inside the customer's projects
        $customerTasks = \App\Models\Task::with([
            'assignee:id,name',
            'project:id,name',
            'submissions' => fn($q) => $q->whereNotNull('file_path')->orderByDesc('version'),
        ])
            ->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                  ->orWhereHas('project', fn($pq) => $pq->where('customer_id', $customer->id)->where('is_quick', false));
            })
            ->get();

        $customer->setRelation('tasks', $customerTasks);

        return view('admin.customers.show', compact('customer'));
    }

    public function report(Customer $customer)
    {
        $customerScope = fn($q) => $q->where('customer_id', $customer->id)
            ->orWhereHas('project', fn($p) => $p->where('customer_id', $customer->id)->where('is_quick', false));

        $allTasks = Task::where($customerScope)
            ->with(['assignee:id,name', 'project:id,name'])
            ->orderBy('created_at')
            ->get();

        $taskIds = $allTasks->pluck('id');

        // ── Delivery logs (when each task was actually delivered/approved) ───
        $deliveryLogs = TaskLog::whereIn('task_id', $taskIds)
            ->whereIn('action', ['status_updated_delivered', 'status_updated_approved'])
            ->orderBy('created_at')
            ->get();

        // ── Monthly activity ─────────────────────────────────────────────────
        $monthlyCreated   = $allTasks->groupBy(fn($t) => $t->created_at->format('Y-m'))->map->count()->sortKeys();
        $monthlyDelivered = $deliveryLogs->groupBy(fn($l) => $l->created_at->format('Y-m'))->map->count()->sortKeys();
        $allMonths        = $monthlyCreated->keys()->merge($monthlyDelivered->keys())->unique()->sort()->values();

        // ── Completion time (created → delivered) ────────────────────────────
        $createdAtMap = $allTasks->pluck('created_at', 'id');
        $completionHours = $deliveryLogs
            ->map(fn($l) => isset($createdAtMap[$l->task_id])
                ? $createdAtMap[$l->task_id]->diffInMinutes($l->created_at) / 60.0
                : null)
            ->filter()
            ->values();

        $completionBuckets = [
            'same_day' => $completionHours->filter(fn($h) => $h < 24)->count(),
            'one_three'=> $completionHours->filter(fn($h) => $h >= 24 && $h < 72)->count(),
            'three_seven'=> $completionHours->filter(fn($h) => $h >= 72 && $h < 168)->count(),
            'over_week'=> $completionHours->filter(fn($h) => $h >= 168)->count(),
        ];
        $avgCompletionHours = round($completionHours->avg() ?? 0, 1);

        // ── Approval performance (customer: design sent → approved) ──────────
        $approvalItems = $allTasks
            ->filter(fn($t) => $t->design_sent_at && $t->customer_approved_at)
            ->map(fn($t) => [
                'title' => $t->title,
                'hours' => round(Carbon::parse($t->design_sent_at)->diffInMinutes(Carbon::parse($t->customer_approved_at)) / 60.0, 2),
            ])
            ->sortBy('hours')
            ->values();

        $approvalHours   = $approvalItems->pluck('hours');
        $avgApprovalHours = round($approvalHours->avg() ?? 0, 1);
        $approvalBuckets = [
            'under_24'  => $approvalHours->filter(fn($h) => $h < 24)->count(),
            'under_72'  => $approvalHours->filter(fn($h) => $h >= 24 && $h < 72)->count(),
            'over_72'   => $approvalHours->filter(fn($h) => $h >= 72)->count(),
        ];

        // ── Revisions ────────────────────────────────────────────────────────
        $totalRevisions = TaskLog::whereIn('task_id', $taskIds)
            ->where('action', 'status_updated_revision_requested')
            ->count();
        $revisionPerTask = TaskLog::whereIn('task_id', $taskIds)
            ->where('action', 'status_updated_revision_requested')
            ->selectRaw('task_id, COUNT(*) as cnt')
            ->groupBy('task_id')
            ->orderByDesc('cnt')
            ->limit(3)
            ->pluck('cnt', 'task_id');
        $topRevisionTasks = $allTasks->whereIn('id', $revisionPerTask->keys())
            ->map(fn($t) => ['title' => $t->title, 'count' => $revisionPerTask[$t->id]])
            ->sortByDesc('count')
            ->values();

        // ── Team workload ─────────────────────────────────────────────────────
        $workload = $allTasks
            ->groupBy('assigned_to')
            ->map(fn($g) => [
                'name'      => $g->first()->assignee?->name ?? 'Unassigned',
                'total'     => $g->count(),
                'delivered' => $g->whereIn('status', ['delivered', 'approved'])->count(),
            ])
            ->sortByDesc('total')
            ->values();

        // ── Status counts ────────────────────────────────────────────────────
        $total     = $allTasks->count();
        $completed = $allTasks->whereIn('status', ['delivered', 'approved'])->count();
        $active    = $allTasks->whereNotIn('status', ['delivered', 'approved', 'archived'])->count();
        $overdue   = $allTasks->filter(fn($t) =>
            $t->deadline && Carbon::parse($t->deadline)->isPast()
            && !in_array($t->status, ['delivered', 'approved', 'archived'])
        )->count();
        $completionRate = $total > 0 ? round($completed / $total * 100) : 0;
        $revisionRate   = $total > 0 ? round($totalRevisions / $total * 100) : 0;

        // ── Work period ──────────────────────────────────────────────────────
        $firstTaskAt = $allTasks->first()?->created_at;
        $lastTaskAt  = $allTasks->last()?->created_at;
        $workDays    = $firstTaskAt && $lastTaskAt ? (int) $firstTaskAt->diffInDays($lastTaskAt) + 1 : 0;

        // ── Peak month ───────────────────────────────────────────────────────
        $peakMonth      = $monthlyCreated->sortDesc()->keys()->first();
        $peakCount      = $monthlyCreated->max();
        $peakLabel      = $peakMonth ? Carbon::createFromFormat('Y-m', $peakMonth)->format('F Y') : null;

        // ── Ad Budget ────────────────────────────────────────────────────────
        $adBudgetTasks = $allTasks
            ->filter(fn($t) => !empty($t->social_budget))
            ->values();

        $adBudgetPosted  = $adBudgetTasks->filter(fn($t) => !empty($t->social_posted_at))->values();
        $adBudgetPending = $adBudgetTasks->filter(fn($t) => empty($t->social_posted_at))->values();

        $adBudgetNumericTotal = $adBudgetTasks
            ->filter(fn($t) => is_numeric(trim($t->social_budget)))
            ->sum(fn($t) => (float) trim($t->social_budget));
        $adBudgetHasNumeric = $adBudgetTasks->contains(fn($t) => is_numeric(trim($t->social_budget)));

        // ── On-time deliveries ───────────────────────────────────────────────
        $deliveredOnTime = $deliveryLogs->filter(function ($l) use ($allTasks) {
            $task = $allTasks->find($l->task_id);
            return $task?->deadline && $l->created_at->lte(Carbon::parse($task->deadline)->endOfDay());
        })->count();

        return view('admin.customers.report', compact(
            'customer',
            'allTasks',
            'total', 'completed', 'active', 'overdue', 'completionRate', 'revisionRate',
            'allMonths', 'monthlyCreated', 'monthlyDelivered',
            'completionHours', 'completionBuckets', 'avgCompletionHours',
            'approvalItems', 'avgApprovalHours', 'approvalBuckets',
            'totalRevisions', 'topRevisionTasks',
            'workload',
            'firstTaskAt', 'lastTaskAt', 'workDays',
            'peakLabel', 'peakCount',
            'deliveredOnTime',
            'adBudgetTasks', 'adBudgetPosted', 'adBudgetPending',
            'adBudgetNumericTotal', 'adBudgetHasNumeric'
        ));
    }

    public function summaryData(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo   = $request->filled('date_to')   ? Carbon::parse($request->date_to)->endOfDay()     : null;

        $summaryList = Customer::withCount([
            'projects',
            'tasks' => function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->where('tasks.created_at', '>=', $dateFrom);
                if ($dateTo)   $q->where('tasks.created_at', '<=', $dateTo);
            },
            'tasks as delivered_count' => function ($q) use ($dateFrom, $dateTo) {
                $q->whereIn('status', ['delivered', 'approved']);
                if ($dateFrom) $q->where('tasks.created_at', '>=', $dateFrom);
                if ($dateTo)   $q->where('tasks.created_at', '<=', $dateTo);
            },
        ])->orderByDesc('tasks_count')->get(['id', 'name', 'company', 'logo']);

        $totalTasks = $summaryList->sum('tasks_count');

        $list = $summaryList->map(function ($c) use ($totalTasks) {
            $rate = $c->tasks_count > 0 ? round($c->delivered_count / $c->tasks_count * 100) : 0;
            $barW = $totalTasks > 0     ? round($c->tasks_count      / $totalTasks      * 100) : 0;
            return [
                'id'              => $c->id,
                'name'            => $c->name,
                'company'         => $c->company,
                'logo'            => $c->logo ? Storage::url($c->logo) : null,
                'initial'         => strtoupper(substr($c->name, 0, 1)),
                'tasks_count'     => $c->tasks_count,
                'delivered_count' => $c->delivered_count,
                'projects_count'  => $c->projects_count,
                'rate'            => $rate,
                'bar_width'       => $barW,
            ];
        })->values();

        $totals = [
            'customers' => $summaryList->count(),
            'projects'  => $summaryList->sum('projects_count'),
            'tasks'     => $totalTasks,
            'delivered' => $summaryList->sum('delivered_count'),
        ];

        return response()->json(['list' => $list, 'totals' => $totals]);
    }

    public function summary(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo   = $request->filled('date_to')   ? Carbon::parse($request->date_to)->endOfDay()     : null;

        $summaryList = Customer::withCount([
            'projects',
            'tasks' => function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->where('tasks.created_at', '>=', $dateFrom);
                if ($dateTo)   $q->where('tasks.created_at', '<=', $dateTo);
            },
            'tasks as delivered_count' => function ($q) use ($dateFrom, $dateTo) {
                $q->whereIn('status', ['delivered', 'approved']);
                if ($dateFrom) $q->where('tasks.created_at', '>=', $dateFrom);
                if ($dateTo)   $q->where('tasks.created_at', '<=', $dateTo);
            },
            'tasks as active_count' => function ($q) use ($dateFrom, $dateTo) {
                $q->whereNotIn('status', ['delivered', 'approved', 'archived']);
                if ($dateFrom) $q->where('tasks.created_at', '>=', $dateFrom);
                if ($dateTo)   $q->where('tasks.created_at', '<=', $dateTo);
            },
            'tasks as overdue_count' => function ($q) use ($dateFrom, $dateTo) {
                $q->whereNotIn('status', ['delivered', 'approved', 'archived'])
                  ->whereNotNull('deadline')->where('deadline', '<', now());
                if ($dateFrom) $q->where('tasks.created_at', '>=', $dateFrom);
                if ($dateTo)   $q->where('tasks.created_at', '<=', $dateTo);
            },
        ])->orderByDesc('tasks_count')->get();

        $summaryTotals = [
            'customers' => $summaryList->count(),
            'projects'  => $summaryList->sum('projects_count'),
            'tasks'     => $summaryList->sum('tasks_count'),
            'delivered' => $summaryList->sum('delivered_count'),
            'active'    => $summaryList->sum('active_count'),
            'overdue'   => $summaryList->sum('overdue_count'),
        ];

        return view('admin.customers.summary', compact('summaryList', 'summaryTotals', 'dateFrom', 'dateTo'));
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'company'     => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'notes'       => 'nullable|string',
            'logo'        => 'nullable|image|mimes:jpg,jpeg,png,webp,svg',
            'remove_logo' => 'nullable|boolean',
        ]);

        $data = $request->only('name', 'company', 'email', 'phone', 'notes');

        if ($request->hasFile('logo')) {
            if ($customer->logo) {
                Storage::disk('public')->delete($customer->logo);
            }
            $data['logo'] = $request->file('logo')->store('customer-logos', 'public');
        } elseif ($request->boolean('remove_logo')) {
            if ($customer->logo) {
                Storage::disk('public')->delete($customer->logo);
            }
            $data['logo'] = null;
        }

        $customer->update($data);

        AuditLogger::log(
            'customer.updated',
            $customer,
            'Customer "' . $customer->name . '" updated',
            ['customer_id' => $customer->id]
        );

        return redirect()->route('admin.customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->logo) {
            Storage::disk('public')->delete($customer->logo);
        }

        $name = $customer->name;
        AuditLogger::log(
            'customer.deleted',
            $customer,
            'Customer "' . $name . '" deleted',
            ['customer_id' => $customer->id]
        );
        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', "Customer \"{$name}\" deleted.");
    }
}

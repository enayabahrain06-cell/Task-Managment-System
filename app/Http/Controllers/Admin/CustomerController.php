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
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::select('customers.*')
            ->withCount(['projects' => fn($q) => $q->where('is_quick', false)])
            ->selectRaw($this->taskCountRaw() . ' as tasks_count')
            ->with('creator:id,name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('company', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(10)->withQueryString();

        // Summary default date range — passed to view for the date picker defaults only.
        // The actual heavy summary query runs async via /admin/customers/summary-data.
        $summaryDefaultFromStr = now()->subMonth()->toDateString();
        $summaryDefaultToStr   = now()->toDateString();

        // Mobile header sub-stat (resources/views/admin/customers/_mobile-index.blade.php):
        // must be a real full-table count, not scoped to the current page like $customers->where(...).
        $withOpenWork = Customer::whereRaw($this->taskCountRaw() . ' > 0')->count();

        return view('admin.customers.index', compact(
            'customers', 'summaryDefaultFromStr', 'summaryDefaultToStr', 'withOpenWork'
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
            $this->resizeLogo($data['logo']);
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
            'domains' => fn($q) => $q->with('responsibleUsers:id,name')->orderBy('expires_at'),
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
            ->with(['assignee:id,name', 'project:id,name,is_quick'])
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

    /**
     * Receive the browser-generated customer report PDF and push it to
     * Customers/{name}/Reports/ on the NAS. Best-effort — the browser
     * download already succeeded regardless of this call's outcome.
     */
    public function savePdfReport(Request $request, Customer $customer)
    {
        $request->validate(['pdf' => 'required|file|mimes:pdf|max:20480']);

        $nas = app(NasService::class);
        if (!$nas->isEnabled()) {
            return response()->json(['ok' => false]);
        }

        $filename = Str::slug($customer->name) . '-report-' . now()->format('Y-m-d') . '.pdf';
        $tmpPath  = sys_get_temp_dir() . '/' . uniqid('customer_report_') . '.pdf';
        $request->file('pdf')->move(dirname($tmpPath), basename($tmpPath));

        $nasPath = $nas->copyToNasCustomerReport($customer, $tmpPath, $filename);
        @unlink($tmpPath);

        return response()->json(['ok' => (bool) $nasPath]);
    }

    public function aiBrief(Customer $customer)
    {
        $customerScope = fn($q) => $q->where('customer_id', $customer->id)
            ->orWhereHas('project', fn($p) => $p->where('customer_id', $customer->id)->where('is_quick', false));

        $allTasks = Task::where($customerScope)->with(['assignee:id,name'])->orderBy('created_at')->get();
        $taskIds  = $allTasks->pluck('id');

        $deliveryLogs = TaskLog::whereIn('task_id', $taskIds)
            ->whereIn('action', ['status_updated_delivered', 'status_updated_approved'])
            ->orderBy('created_at')->get();

        $total     = $allTasks->count();
        $completed = $allTasks->whereIn('status', ['delivered', 'approved'])->count();
        $active    = $allTasks->whereNotIn('status', ['delivered', 'approved', 'archived'])->count();
        $overdue   = $allTasks->filter(fn($t) =>
            $t->deadline && Carbon::parse($t->deadline)->isPast()
            && !in_array($t->status, ['delivered', 'approved', 'archived'])
        )->count();
        $completionRate = $total > 0 ? round($completed / $total * 100) : 0;

        $totalRevisions = TaskLog::whereIn('task_id', $taskIds)->where('action', 'status_updated_revision_requested')->count();
        $revisionRate   = $total > 0 ? round($totalRevisions / $total * 100) : 0;

        $createdAtMap    = $allTasks->pluck('created_at', 'id');
        $completionHours = $deliveryLogs->map(fn($l) => isset($createdAtMap[$l->task_id])
            ? $createdAtMap[$l->task_id]->diffInMinutes($l->created_at) / 60.0 : null)->filter()->values();
        $avgCompletionHours = round($completionHours->avg() ?? 0, 1);
        $avgDays = $avgCompletionHours > 0 ? round($avgCompletionHours / 24, 1) : 0;

        $deliveredOnTime = $deliveryLogs->filter(function ($l) use ($allTasks) {
            $task = $allTasks->find($l->task_id);
            return $task?->deadline && $l->created_at->lte(Carbon::parse($task->deadline)->endOfDay());
        })->count();
        $onTimePct = $completed > 0 ? round($deliveredOnTime / $completed * 100) : 0;

        $firstTaskAt = $allTasks->first()?->created_at;
        $lastTaskAt  = $allTasks->last()?->created_at;
        $workDays    = $firstTaskAt && $lastTaskAt ? (int) $firstTaskAt->diffInDays($lastTaskAt) + 1 : 0;

        $monthlyCreated = $allTasks->groupBy(fn($t) => $t->created_at->format('Y-m'))->map->count()->sortKeys();
        $peakMonth  = $monthlyCreated->sortDesc()->keys()->first();
        $peakCount  = $monthlyCreated->max() ?? 0;
        $peakLabel  = $peakMonth ? Carbon::createFromFormat('Y-m', $peakMonth)->format('F Y') : null;

        $workload = $allTasks->groupBy('assigned_to')
            ->map(fn($g) => ['name' => $g->first()->assignee?->name ?? 'Unassigned', 'total' => $g->count(), 'delivered' => $g->whereIn('status', ['delivered', 'approved'])->count()])
            ->sortByDesc('total')->values();

        $adBudgetTasks   = $allTasks->filter(fn($t) => !empty($t->social_budget))->values();
        $adBudgetNumeric = $adBudgetTasks->filter(fn($t) => is_numeric(trim($t->social_budget)))->sum(fn($t) => (float) trim($t->social_budget));

        $topRevisionMap = TaskLog::whereIn('task_id', $taskIds)->where('action', 'status_updated_revision_requested')
            ->selectRaw('task_id, COUNT(*) as cnt')->groupBy('task_id')->orderByDesc('cnt')->limit(3)->pluck('cnt', 'task_id');
        $topRevItems = $allTasks->whereIn('id', $topRevisionMap->keys())
            ->map(fn($t) => ['title' => $t->title, 'count' => $topRevisionMap[$t->id]])
            ->sortByDesc('count')->values();

        $topTeamMember  = $workload->first();
        $teamCount      = $workload->count();
        $periodStr      = $firstTaskAt ? $firstTaskAt->format('M j, Y').' – '.($lastTaskAt?->format('M j, Y') ?? 'present') : 'N/A';
        $clientName     = $customer->name.($customer->company ? ' ('.$customer->company.')' : '');

        // ── Performance tier classification ─────────────────────────────────
        $perfTier = $completionRate >= 80 ? 'excellent' : ($completionRate >= 50 ? 'good' : 'developing');
        $timeTier = $overdue === 0 ? 'on-track' : ($overdue <= 2 ? 'minor-delays' : 'attention-needed');
        $revTier  = $revisionRate <= 20 ? 'low' : ($revisionRate <= 50 ? 'moderate' : 'high');

        // ── Headline ────────────────────────────────────────────────────────
        $headlines = [
            'excellent' => "{$clientName} — {$completionRate}% completion rate reflects a high-performing, well-managed engagement.",
            'good'      => "{$clientName} — Solid progress with {$completed} of {$total} tasks delivered across a {$workDays}-day collaboration.",
            'developing'=> "{$clientName} — Active engagement with {$total} tasks underway; {$active} items currently in progress.",
        ];
        $headline = $headlines[$perfTier];

        // ── Overview paragraph ───────────────────────────────────────────────
        $overviewParts = [];
        $overviewParts[] = "This report covers the full engagement period from {$periodStr} ({$workDays} calendar days) and includes all {$total} tasks assigned to {$clientName}.";

        if ($completionRate >= 80) {
            $overviewParts[] = "The collaboration has achieved an outstanding {$completionRate}% completion rate — {$completed} tasks delivered out of {$total} — placing this engagement in top-tier performance.";
        } elseif ($completionRate >= 50) {
            $overviewParts[] = "With {$completed} tasks delivered ({$completionRate}% completion) and {$active} currently active, the engagement is progressing steadily.";
        } else {
            $overviewParts[] = "Of the {$total} tasks initiated, {$completed} have been delivered so far, with {$active} tasks actively in progress.";
        }

        if ($overdue > 0) {
            $overviewParts[] = "There are currently {$overdue} overdue task".($overdue > 1 ? 's' : '')." that require attention to maintain schedule adherence.";
        } else {
            $overviewParts[] = "No tasks are currently overdue, indicating strong schedule management across the engagement.";
        }
        $overview = implode(' ', $overviewParts);

        // ── Highlights (3 key metrics) ───────────────────────────────────────
        $highlights = [
            [
                'icon'  => 'fa-circle-check',
                'color' => $completionRate >= 80 ? '#059669' : ($completionRate >= 50 ? '#d97706' : '#dc2626'),
                'label' => 'Completion Rate',
                'value' => "{$completionRate}%",
            ],
            [
                'icon'  => 'fa-clock',
                'color' => '#6366f1',
                'label' => 'Avg. Delivery Time',
                'value' => $avgDays > 0 ? "{$avgDays}d" : ($avgCompletionHours > 0 ? "{$avgCompletionHours}h" : 'N/A'),
            ],
            [
                'icon'  => 'fa-rotate-left',
                'color' => $revTier === 'low' ? '#059669' : ($revTier === 'moderate' ? '#d97706' : '#dc2626'),
                'label' => 'Revision Rate',
                'value' => "{$revisionRate}%",
            ],
        ];

        // ── Section 1 — Performance Summary ─────────────────────────────────
        $s1 = [];
        if ($completionRate >= 80) {
            $s1[] = "The engagement demonstrates excellent delivery performance with a {$completionRate}% completion rate — {$completed} tasks fully delivered out of {$total}.";
        } elseif ($completionRate >= 50) {
            $s1[] = "The project is showing solid momentum with {$completed} tasks delivered ({$completionRate}%) and {$active} tasks actively progressing.";
        } else {
            $s1[] = "The engagement is in an active phase with {$active} tasks in progress and {$completed} already delivered.";
        }
        if ($onTimePct > 0) {
            $s1[] = "{$onTimePct}% of completed tasks were delivered on or before their deadline".($onTimePct >= 90 ? ', reflecting exceptional schedule adherence.' : '.');
        }
        if ($totalRevisions > 0) {
            $s1[] = "A total of {$totalRevisions} revision request".($totalRevisions > 1 ? 's were' : ' was')." logged ({$revisionRate}% of tasks)".($revisionRate <= 20 ? ', which is within a healthy range.' : ' — reviewing feedback loops may help reduce rework.');
        }
        $sections[] = ['title' => 'Performance Summary', 'body' => implode(' ', $s1)];

        // ── Section 2 — Team & Delivery ──────────────────────────────────────
        $s2 = [];
        if ($teamCount === 1 && $topTeamMember) {
            $s2[] = "All {$total} tasks are handled by {$topTeamMember['name']}, with {$topTeamMember['delivered']} delivered — a fully focused single-resource engagement.";
        } elseif ($teamCount > 1 && $topTeamMember) {
            $s2[] = "Work is distributed across {$teamCount} team members. {$topTeamMember['name']} leads the workload with {$topTeamMember['total']} tasks ({$topTeamMember['delivered']} delivered).";
        }
        if ($avgDays > 0) {
            $s2[] = "The average time from task creation to delivery is {$avgDays} day".($avgDays != 1 ? 's' : '').($avgDays <= 3 ? ' — an impressive turnaround that clients will notice.' : '.');
        }
        if ($peakLabel && $peakCount > 0) {
            $s2[] = "The busiest period was {$peakLabel} with {$peakCount} tasks initiated, marking the peak of activity in this engagement.";
        }
        $sections[] = ['title' => 'Team & Delivery', 'body' => implode(' ', $s2)];

        // ── Section 3 — Key Insights ─────────────────────────────────────────
        $s3 = [];
        if ($completionRate >= 80 && $overdue === 0) {
            $s3[] = "This engagement stands out for both high completion ({$completionRate}%) and zero overdue tasks — a combination that reflects strong project management and client alignment.";
        } elseif ($overdue > 0 && $active > 0) {
            $s3[] = "With {$overdue} overdue and {$active} active tasks, the current focus should be on unblocking in-progress work to recover schedule momentum.";
        } else {
            $s3[] = "The data shows a consistent delivery pattern with {$completed} tasks completed against {$total} initiated over the engagement period.";
        }
        if ($topRevItems->isNotEmpty()) {
            $topTitle = $topRevItems->first()['title'];
            $topCount = $topRevItems->first()['count'];
            $s3[] = "The task \"{$topTitle}\" received the most revision requests ({$topCount}), suggesting scope or expectation alignment may be worth reviewing for similar future work.";
        }
        if ($adBudgetNumeric > 0) {
            $s3[] = '$'.number_format($adBudgetNumeric, 2).' in ad budget was managed across '.count($adBudgetTasks).' social media task'.($adBudgetTasks->count() > 1 ? 's' : '').' for this customer.';
        }
        $sections[] = ['title' => 'Key Insights', 'body' => implode(' ', $s3)];

        // ── Section 4 — Recommendations ──────────────────────────────────────
        $s4 = [];
        if ($overdue > 0) {
            $s4[] = "Prioritize resolution of the {$overdue} overdue task".($overdue > 1 ? 's' : '')." — share a revised delivery timeline with the client to maintain trust.";
        }
        if ($revisionRate > 30) {
            $s4[] = "The {$revisionRate}% revision rate suggests potential gaps in briefing or approval workflow. Consider a structured sign-off checklist before task execution.";
        }
        if ($completionRate < 60 && $active > 0) {
            $s4[] = "With {$active} tasks still active, a focused sprint or progress review session could accelerate delivery and improve the overall completion rate.";
        }
        if (empty($s4)) {
            if ($completionRate >= 80) {
                $s4[] = "This is a high-performing engagement. Consider sharing this report with the client as a trust-building touchpoint and to set expectations for future projects.";
            } else {
                $s4[] = "Continue the current delivery cadence and schedule a mid-point review with the client to align on remaining priorities and timelines.";
            }
        }
        if ($teamCount > 1) {
            $s4[] = "With {$teamCount} contributors involved, maintaining clear task ownership and regular status syncs will help prevent bottlenecks as the engagement scales.";
        }
        $sections[] = ['title' => 'Recommendations', 'body' => implode(' ', $s4)];

        return response()->json([
            'brief' => compact('headline', 'overview', 'highlights', 'sections'),
        ]);
    }

    public function summaryData(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo   = $request->filled('date_to')   ? Carbon::parse($request->date_to)->endOfDay()     : null;

        [$tcSql, $tcBind] = $this->taskCountRawBound($dateFrom, $dateTo);
        [$dcSql, $dcBind] = $this->taskCountRawBound($dateFrom, $dateTo, ['delivered', 'approved']);

        $summaryList = Customer::select('customers.*')
            ->withCount(['projects' => fn($q) => $q->where('is_quick', false)])
            ->selectRaw($tcSql . ' as tasks_count', $tcBind)
            ->selectRaw($dcSql . ' as delivered_count', $dcBind)
            ->orderByDesc('tasks_count')
            ->get();

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

        [$tcSql,  $tcBind]  = $this->taskCountRawBound($dateFrom, $dateTo);
        [$dcSql,  $dcBind]  = $this->taskCountRawBound($dateFrom, $dateTo, ['delivered', 'approved']);
        [$acSql,  $acBind]  = $this->taskCountRawBound($dateFrom, $dateTo, [], ['delivered', 'approved', 'archived']);
        [$ocSql,  $ocBind]  = $this->taskCountRawBound($dateFrom, $dateTo, [], ['delivered', 'approved', 'archived'], true);

        $summaryList = Customer::select('customers.*')
            ->withCount(['projects' => fn($q) => $q->where('is_quick', false)])
            ->selectRaw($tcSql . ' as tasks_count',     $tcBind)
            ->selectRaw($dcSql . ' as delivered_count', $dcBind)
            ->selectRaw($acSql . ' as active_count',    $acBind)
            ->selectRaw($ocSql . ' as overdue_count',   $ocBind)
            ->orderByDesc('tasks_count')
            ->get();

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
            $this->resizeLogo($data['logo']);
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

    // Resize a freshly uploaded logo to fit within 300×300px and re-save it in-place.
    // Requires PHP GD (php8.2-gd). Silently skips non-image or unsupported formats.
    private function resizeLogo(string $storagePath): void
    {
        $fullPath = Storage::disk('public')->path($storagePath);
        if (!file_exists($fullPath)) return;

        $info = @getimagesize($fullPath);
        if (!$info) return;

        [$srcW, $srcH, $type] = $info;
        $maxDim = 300;

        // Skip if already small enough
        if ($srcW <= $maxDim && $srcH <= $maxDim) return;

        $scale  = min($maxDim / $srcW, $maxDim / $srcH);
        $dstW   = (int) round($srcW * $scale);
        $dstH   = (int) round($srcH * $scale);

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($fullPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($fullPath),
            default        => null,
        };
        if (!$src) return;

        $dst = imagecreatetruecolor($dstW, $dstH);

        // Preserve PNG transparency
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($dst, $fullPath, 85),
            IMAGETYPE_PNG  => imagepng($dst, $fullPath, 7),
            IMAGETYPE_WEBP => imagewebp($dst, $fullPath, 85),
            default        => null,
        };

        imagedestroy($src);
        imagedestroy($dst);
    }

    // Returns a raw SQL fragment (no bindings) counting all tasks for a customer,
    // including tasks that belong via the customer's projects (not just direct customer_id).
    private function taskCountRaw(): string
    {
        return '(SELECT COUNT(*) FROM tasks WHERE tasks.customer_id = customers.id OR tasks.project_id IN (SELECT id FROM projects WHERE projects.customer_id = customers.id AND projects.is_quick = 0))';
    }

    // Returns [sql, bindings] for a bound task count with optional date range, status IN, status NOT IN, and overdue filters.
    private function taskCountRawBound(
        ?Carbon $from = null,
        ?Carbon $to = null,
        array $statusIn = [],
        array $statusNotIn = [],
        bool $overdueOnly = false
    ): array {
        $sql      = '(SELECT COUNT(*) FROM tasks WHERE (tasks.customer_id = customers.id OR tasks.project_id IN (SELECT id FROM projects WHERE projects.customer_id = customers.id AND projects.is_quick = 0))';
        $bindings = [];

        if ($statusIn) {
            $placeholders = implode(',', array_fill(0, count($statusIn), '?'));
            $sql .= " AND tasks.status IN ($placeholders)";
            $bindings = array_merge($bindings, $statusIn);
        }
        if ($statusNotIn) {
            $placeholders = implode(',', array_fill(0, count($statusNotIn), '?'));
            $sql .= " AND tasks.status NOT IN ($placeholders)";
            $bindings = array_merge($bindings, $statusNotIn);
        }
        if ($overdueOnly) {
            $sql .= ' AND tasks.deadline IS NOT NULL AND tasks.deadline < ?';
            $bindings[] = now();
        }
        if ($from) { $sql .= ' AND tasks.created_at >= ?'; $bindings[] = $from; }
        if ($to)   { $sql .= ' AND tasks.created_at <= ?'; $bindings[] = $to; }

        $sql .= ')';
        return [$sql, $bindings];
    }
}

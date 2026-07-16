<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportPdfService
{
    /**
     * Build customer distribution stats including tasks linked only via their project.
     * Returns all customers sorted by (tasks + projects) descending, no take() applied.
     */
    public function customerDistStats(
        $fromDate,
        $toDate,
        array $doneStatuses,
        $customerId = null
    ): \Illuminate\Support\Collection {
        $doneIn = implode(',', array_map(fn ($s) => "'{$s}'", $doneStatuses));

        // Tasks with direct customer_id on the task row
        $direct = DB::table('tasks')
            ->select(
                'tasks.customer_id as cid',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN tasks.status IN ({$doneIn}) THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN tasks.status IN ('assigned','viewed','in_progress') THEN 1 ELSE 0 END) as active"),
                DB::raw("SUM(CASE WHEN tasks.deadline < CURRENT_TIMESTAMP AND tasks.status NOT IN ({$doneIn}) THEN 1 ELSE 0 END) as overdue")
            )
            ->whereNotNull('tasks.customer_id')
            ->when($customerId, fn ($q) => $q->where('tasks.customer_id', $customerId))
            ->when($fromDate,   fn ($q) => $q->where('tasks.created_at', '>=', $fromDate))
            ->when($toDate,     fn ($q) => $q->where('tasks.created_at', '<=', $toDate))
            ->groupBy('tasks.customer_id')
            ->get()->keyBy('cid');

        // Tasks with no customer_id but in a project that belongs to a customer
        $viaProject = DB::table('tasks')
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->select(
                'projects.customer_id as cid',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN tasks.status IN ({$doneIn}) THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN tasks.status IN ('assigned','viewed','in_progress') THEN 1 ELSE 0 END) as active"),
                DB::raw("SUM(CASE WHEN tasks.deadline < CURRENT_TIMESTAMP AND tasks.status NOT IN ({$doneIn}) THEN 1 ELSE 0 END) as overdue")
            )
            ->whereNull('tasks.customer_id')
            ->where('projects.is_quick', false)
            ->whereNotNull('projects.customer_id')
            ->when($customerId, fn ($q) => $q->where('projects.customer_id', $customerId))
            ->when($fromDate,   fn ($q) => $q->where('tasks.created_at', '>=', $fromDate))
            ->when($toDate,     fn ($q) => $q->where('tasks.created_at', '<=', $toDate))
            ->groupBy('projects.customer_id')
            ->get()->keyBy('cid');

        // Project counts per customer in the same period
        $projCounts = DB::table('projects')
            ->select('customer_id as cid', DB::raw('COUNT(*) as cnt'))
            ->where('is_quick', false)
            ->whereNotNull('customer_id')
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($fromDate,   fn ($q) => $q->where('projects.created_at', '>=', $fromDate))
            ->when($toDate,     fn ($q) => $q->where('projects.created_at', '<=', $toDate))
            ->groupBy('customer_id')
            ->get()->keyBy('cid');

        $allIds = $direct->keys()->merge($viaProject->keys())->merge($projCounts->keys())->unique();

        return Customer::whereIn('id', $allIds)
            ->orderBy('name')
            ->get(['id', 'name', 'company'])
            ->map(function ($c) use ($direct, $viaProject, $projCounts) {
                $d        = $direct->get($c->id);
                $v        = $viaProject->get($c->id);
                $total    = ($d->total    ?? 0) + ($v->total    ?? 0);
                $done     = ($d->completed ?? 0) + ($v->completed ?? 0);
                $active   = ($d->active   ?? 0) + ($v->active   ?? 0);
                $overdue  = ($d->overdue  ?? 0) + ($v->overdue  ?? 0);
                $projects = $projCounts->get($c->id)?->cnt ?? 0;
                return [
                    'id'        => $c->id,
                    'name'      => $c->name,
                    'company'   => $c->company ?? null,
                    'projects'  => $projects,
                    'total'     => $total,
                    'completed' => $done,
                    'active'    => $active,
                    'overdue'   => $overdue,
                    'rate'      => $total > 0 ? round($done / $total * 100) : 0,
                ];
            })
            ->filter(fn ($c) => $c['total'] > 0 || $c['projects'] > 0)
            ->sortByDesc(fn ($c) => $c['total'] + $c['projects'])
            ->values();
    }

    /**
     * Attach a 'share_pct' to each row (workload = total tasks + projects) using the
     * largest-remainder method so percentages sum to exactly 100.
     */
    public function withSharePercentages(\Illuminate\Support\Collection $rows): \Illuminate\Support\Collection
    {
        $workloads = $rows->map(fn ($c) => $c['total'] + ($c['projects'] ?? 0));
        $grand     = $workloads->sum();
        if ($grand <= 0) {
            return $rows->map(fn ($c) => $c + ['share_pct' => 0]);
        }

        $exact  = $workloads->map(fn ($w) => $w / $grand * 100)->values();
        $floors = $exact->map(fn ($p) => (int) floor($p))->values();
        $remain = $exact->map(fn ($p, $i) => $p - $floors[$i])->values();
        $deficit = 100 - $floors->sum();

        $order = $remain->keys()->sortByDesc(fn ($i) => $remain[$i])->values();
        $rounded = $floors->values();
        for ($i = 0; $i < $deficit; $i++) {
            $idx = $order[$i];
            $rounded[$idx] = $rounded[$idx] + 1;
        }

        return $rows->values()->map(fn ($c, $i) => $c + ['share_pct' => $rounded[$i]]);
    }

    /**
     * Monthly customer task distribution for the last $months calendar months
     * (oldest first). Each entry: ['label' => 'July 2026', 'stats' => Collection].
     */
    public function monthlyCustomerDistStats(int $months = 6): array
    {
        $doneStatuses = ['approved', 'delivered'];
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd   = now()->subMonths($i)->endOfMonth();
            $stats = $this->customerDistStats($monthStart, $monthEnd, $doneStatuses);
            $result[] = [
                'label' => $monthStart->format('F Y'),
                'stats' => $this->withSharePercentages($stats),
            ];
        }
        return $result;
    }

    /**
     * Company-wide summary report: task % by customer for a single calendar month
     * (defaults to the current month). Pushed to the NAS root as its own report —
     * each month gets its own file, rather than bundling several months together.
     */
    public function buildCompanySummaryReportPdf(?Carbon $forMonth = null): string
    {
        $forMonth   = $forMonth ?? now();
        $doneStatuses = ['approved', 'delivered'];
        $stats = $this->withSharePercentages(
            $this->customerDistStats($forMonth->copy()->startOfMonth(), $forMonth->copy()->endOfMonth(), $doneStatuses)
        );

        $monthLabel  = $forMonth->format('F Y');
        $narrative   = $this->companyNarrative($monthLabel, $stats);
        $appName     = Setting::get('app_name', config('app.name'));
        $companyName = Setting::get('company_name', '') ?: $appName;
        $logoBase64  = $this->imageBase64FromStorage(Setting::get('logo_path', ''));
        $generatedAt = now()->format('d M Y, H:i');

        return Pdf::loadView('admin.reports.pdf-summary-export', compact(
            'monthLabel', 'stats', 'narrative', 'appName', 'companyName', 'logoBase64', 'generatedAt'
        ))->setPaper('a4', 'portrait')->output();
    }

    private function companyNarrative(string $monthLabel, \Illuminate\Support\Collection $stats): string
    {
        $totalTasks    = $stats->sum('total');
        $totalProjects = $stats->sum('projects');
        $workload      = $totalTasks + $totalProjects;

        $sentences = [];
        $sentences[] = "This report covers <strong>{$monthLabel}</strong>, summarizing task distribution across all customers.";

        if ($workload > 0) {
            $sentences[] = "A total of <strong>{$totalTasks} task" . ($totalTasks !== 1 ? 's' : '') . '</strong>'
                . ($totalProjects > 0 ? " and <strong>{$totalProjects} project" . ($totalProjects !== 1 ? 's' : '') . '</strong>' : '')
                . " were recorded across <strong>{$stats->count()} customer" . ($stats->count() !== 1 ? 's' : '') . '</strong>.';

            $top = $stats->first();
            if ($top) {
                $sentences[] = "<strong>{$top['name']}</strong> had the highest workload: <strong>{$top['total']} task" . ($top['total'] !== 1 ? 's' : '') . '</strong>'
                    . (($top['projects'] ?? 0) > 0 ? " + <strong>{$top['projects']} project" . ($top['projects'] !== 1 ? 's' : '') . '</strong>' : '')
                    . " ({$top['share_pct']}% of total).";
            }
        } else {
            $sentences[] = 'No task activity was recorded for any customer this month.';
        }

        return implode(' ', $sentences);
    }

    private function userTaskScope(User $user)
    {
        return Task::where(function ($q) use ($user) {
            $q->where('assigned_to', $user->id)
              ->orWhere('social_assigned_to', $user->id)
              ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                  ->from('task_assignees')
                  ->whereColumn('task_assignees.task_id', 'tasks.id')
                  ->where('task_assignees.user_id', $user->id));
        });
    }

    public function userHasTasks(User $user): bool
    {
        return $this->userTaskScope($user)->exists();
    }

    public function buildUserReportPdf(User $user): string
    {
        $doneStatuses = ['approved', 'delivered', 'archived'];

        $totalTasks     = $this->userTaskScope($user)->count();
        $completedTasks = $this->userTaskScope($user)->whereIn('status', $doneStatuses)->count();
        $pendingTasks   = $this->userTaskScope($user)->whereIn('status', ['in_progress', 'paused'])->count();
        $inReviewTasks  = $this->userTaskScope($user)->whereIn('status', ['submitted', 'revision_requested'])->count();
        $completionRate = $totalTasks > 0 ? round($completedTasks / $totalTasks * 100) : 0;

        $tasks = $this->userTaskScope($user)
            ->with(['project:id,name,is_quick'])
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->get();

        $narrative = $this->userNarrative($user, $totalTasks, $completedTasks, $pendingTasks, $inReviewTasks, $completionRate);

        $appName      = Setting::get('app_name', config('app.name'));
        $companyName  = Setting::get('company_name', '') ?: $appName;
        $logoBase64   = $this->imageBase64FromStorage(Setting::get('logo_path', ''));
        $avatarBase64 = $user->avatar ? $this->imageBase64FromStorage($user->avatar) : '';
        $generatedAt  = now()->format('d M Y, H:i');

        return Pdf::loadView('user.reports.pdf-export', compact(
            'user', 'tasks', 'totalTasks', 'completedTasks', 'pendingTasks', 'inReviewTasks',
            'completionRate', 'narrative', 'logoBase64', 'avatarBase64', 'appName', 'companyName', 'generatedAt'
        ))->setPaper('a4', 'portrait')->output();
    }

    private function userNarrative(User $user, int $totalTasks, int $completedTasks, int $pendingTasks, int $inReviewTasks, int $completionRate): string
    {
        $sentences = [];
        $sentences[] = "This report summarizes <strong>{$user->name}</strong>'s task performance as of the generation date.";

        if ($totalTasks > 0) {
            $sentences[] = "A total of <strong>{$totalTasks} task" . ($totalTasks !== 1 ? 's' : '') . '</strong> '
                . ($totalTasks !== 1 ? 'are' : 'is') . " assigned, of which <strong>{$completedTasks}</strong> "
                . ($completedTasks !== 1 ? 'have' : 'has') . " been completed, yielding a completion rate of <strong>{$completionRate}%</strong>.";
            if ($pendingTasks > 0) {
                $sentences[] = "<strong>{$pendingTasks} task" . ($pendingTasks !== 1 ? 's' : '') . '</strong> ' . ($pendingTasks !== 1 ? 'are' : 'is') . ' currently in progress.';
            }
            if ($inReviewTasks > 0) {
                $sentences[] = "<strong>{$inReviewTasks} task" . ($inReviewTasks !== 1 ? 's' : '') . '</strong> ' . ($inReviewTasks !== 1 ? 'are' : 'is') . ' awaiting review.';
            }
        } else {
            $sentences[] = 'No tasks are currently assigned.';
        }

        return implode(' ', $sentences);
    }

    private function customerTaskScope(Customer $customer)
    {
        return Task::where(function ($q) use ($customer) {
            $q->where('customer_id', $customer->id)
              ->orWhereHas('project', fn ($p) => $p->where('customer_id', $customer->id)->where('is_quick', false));
        });
    }

    public function customerHasTasks(Customer $customer): bool
    {
        return $this->customerTaskScope($customer)->exists();
    }

    public function buildCustomerReportPdf(Customer $customer): string
    {
        $allTasks = $this->customerTaskScope($customer)->with(['assignee:id,name', 'project:id,name,is_quick'])->orderBy('created_at')->get();
        $taskIds  = $allTasks->pluck('id');

        $total     = $allTasks->count();
        $completed = $allTasks->whereIn('status', ['delivered', 'approved'])->count();
        $active    = $allTasks->whereNotIn('status', ['delivered', 'approved', 'archived'])->count();
        $overdue   = $allTasks->filter(fn ($t) => $t->deadline
            && Carbon::parse($t->deadline)->isPast()
            && !in_array($t->status, ['delivered', 'approved', 'archived'])
        )->count();
        $completionRate = $total > 0 ? round($completed / $total * 100) : 0;

        $totalRevisions = TaskLog::whereIn('task_id', $taskIds)
            ->where('action', 'status_updated_revision_requested')
            ->count();
        $revisionRate = $total > 0 ? round($totalRevisions / $total * 100) : 0;

        $monthlyCreated = $allTasks->groupBy(fn ($t) => $t->created_at->format('Y-m'))->map->count()->sortKeys();

        $workload = $allTasks
            ->groupBy('assigned_to')
            ->map(fn ($g) => [
                'name'      => $g->first()->assignee?->name ?? 'Unassigned',
                'total'     => $g->count(),
                'delivered' => $g->whereIn('status', ['delivered', 'approved'])->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $narrative = $this->customerNarrative($customer, $total, $completed, $active, $overdue, $completionRate, $totalRevisions, $revisionRate, $workload);

        $appName     = Setting::get('app_name', config('app.name'));
        $companyName = Setting::get('company_name', '') ?: $appName;
        $logoBase64  = $this->imageBase64FromStorage(Setting::get('logo_path', ''));
        $generatedAt = now()->format('d M Y, H:i');

        return Pdf::loadView('admin.customers.pdf-export', compact(
            'customer', 'allTasks', 'total', 'completed', 'active', 'overdue', 'completionRate',
            'totalRevisions', 'revisionRate', 'monthlyCreated', 'workload', 'narrative',
            'appName', 'companyName', 'logoBase64', 'generatedAt'
        ))->setPaper('a4', 'portrait')->output();
    }

    private function customerNarrative(
        Customer $customer,
        int $total,
        int $completed,
        int $active,
        int $overdue,
        int $completionRate,
        int $totalRevisions,
        int $revisionRate,
        \Illuminate\Support\Collection $workload
    ): string {
        $sentences = [];
        $sentences[] = "This report summarizes <strong>{$customer->name}</strong>'s engagement as of the generation date.";

        if ($total > 0) {
            $sentences[] = "A total of <strong>{$total} task" . ($total !== 1 ? 's' : '') . '</strong> '
                . ($total !== 1 ? 'have' : 'has') . " been recorded, of which <strong>{$completed}</strong> "
                . ($completed !== 1 ? 'have' : 'has') . " been delivered, yielding a completion rate of <strong>{$completionRate}%</strong>.";
            if ($active > 0) {
                $sentences[] = "<strong>{$active} task" . ($active !== 1 ? 's' : '') . '</strong> ' . ($active !== 1 ? 'remain' : 'remains') . ' active.';
            }
            if ($overdue > 0) {
                $sentences[] = "<strong>{$overdue} task" . ($overdue !== 1 ? 's' : '') . '</strong> ' . ($overdue !== 1 ? 'are' : 'is') . ' overdue and require attention.';
            }
            if ($totalRevisions > 0) {
                $sentences[] = "<strong>{$totalRevisions}</strong> revision request" . ($totalRevisions !== 1 ? 's have' : ' has') . " been logged ({$revisionRate}% of tasks).";
            }
            $top = $workload->first();
            if ($top && $workload->count() > 1) {
                $sentences[] = "<strong>{$top['name']}</strong> handled the most tasks: <strong>{$top['total']}</strong>.";
            }
        } else {
            $sentences[] = 'No tasks have been recorded for this customer yet.';
        }

        return implode(' ', $sentences);
    }

    private function imageBase64FromStorage(string $storagePath): string
    {
        if (empty($storagePath)) return '';
        $full = storage_path('app/public/' . ltrim($storagePath, '/'));
        if (!file_exists($full)) return '';
        $ext  = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $mime = match ($ext) { 'png' => 'png', 'gif' => 'gif', 'webp' => 'webp', default => 'jpeg' };

        return 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($full));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GanttController extends Controller
{
    public function index(Request $request)
    {
        if (Setting::get('show_gantt_chart', '1') !== '1') {
            abort(403, 'Gantt Chart feature is disabled.');
        }

        $projects = Project::where('is_quick', false)
            ->with(['tasks' => function ($q) {
                $q->whereNotNull('deadline')
                  ->with(['assignee:id,name', 'assignees:id,name'])
                  ->orderBy('deadline');
            }])
            ->orderBy('name')
            ->get()
            ->filter(fn($p) => $p->tasks->isNotEmpty())
            ->values();

        // Compute overall date range
        $allDates = $projects->flatMap(fn($p) => $p->tasks->flatMap(fn($t) => [
            $t->created_at,
            $t->deadline,
        ]))->filter()->sort();

        $rangeStart = $allDates->first()
            ? Carbon::parse($allDates->first())->startOfWeek()->subWeek()
            : now()->startOfMonth();
        $rangeEnd   = $allDates->last()
            ? Carbon::parse($allDates->last())->endOfMonth()->addWeek()
            : now()->addMonths(2)->endOfMonth();

        // Build JSON-serialisable data for the chart
        $statusColors = [
            'draft'               => ['bg' => '#F3F4F6', 'text' => '#6B7280', 'label' => 'Draft'],
            'assigned'            => ['bg' => '#DBEAFE', 'text' => '#1D4ED8', 'label' => 'Assigned'],
            'viewed'              => ['bg' => '#E0E7FF', 'text' => '#4338CA', 'label' => 'Viewed'],
            'in_progress'         => ['bg' => '#EDE9FE', 'text' => '#6D28D9', 'label' => 'In Progress'],
            'submitted'           => ['bg' => '#FEF3C7', 'text' => '#B45309', 'label' => 'Submitted'],
            'approved'            => ['bg' => '#D1FAE5', 'text' => '#065F46', 'label' => 'Approved'],
            'revision_requested'  => ['bg' => '#FEE2E2', 'text' => '#991B1B', 'label' => 'Revision'],
            'delivered'           => ['bg' => '#ECFDF5', 'text' => '#047857', 'label' => 'Delivered'],
            'archived'            => ['bg' => '#F9FAFB', 'text' => '#9CA3AF', 'label' => 'Archived'],
        ];

        $chartData = $projects->map(function ($project) use ($statusColors) {
            return [
                'id'    => $project->id,
                'name'  => $project->name,
                'tasks' => $project->tasks->map(function ($task) use ($statusColors) {
                    $color = $statusColors[$task->status] ?? $statusColors['draft'];
                    $assignee = $task->assignee?->name ?? ($task->assignees->first()?->name ?? '—');
                    return [
                        'id'       => $task->id,
                        'title'    => $task->title,
                        'status'   => $task->status,
                        'color'    => $color,
                        'assignee' => $assignee,
                        'start'    => $task->created_at->format('Y-m-d'),
                        'end'      => $task->deadline->format('Y-m-d'),
                        'url'      => route('admin.tasks.show', $task->id),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return view('admin.gantt', [
            'chartData'  => $chartData,
            'rangeStart' => $rangeStart->format('Y-m-d'),
            'rangeEnd'   => $rangeEnd->format('Y-m-d'),
            'projects'   => $projects,
        ]);
    }
}

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
@php
use Carbon\Carbon;

// ── App branding ──────────────────────────────────────────────────────
$primaryColor = $settings['primary_color'] ?? '#4F46E5';

// ── Build week list from task date range ──────────────────────────────
$allDates = $projects->flatMap(fn($p) => $p->tasks->flatMap(fn($t) => [$t->created_at, $t->deadline]))->filter();
if ($allDates->isEmpty()) {
    $rangeStart = now()->startOfMonth();
    $rangeEnd   = now()->addMonths(2)->endOfMonth();
} else {
    $rangeStart = Carbon::parse($allDates->min())->startOfWeek()->subWeeks(1);
    $rangeEnd   = Carbon::parse($allDates->max())->endOfWeek()->addWeeks(2);
}

$weeks = [];
$cur = $rangeStart->copy();
while ($cur <= $rangeEnd) {
    $weeks[] = $cur->copy();
    $cur->addWeek();
}
$totalWeeks = count($weeks);

// Month groups for the top header row
$monthGroups = [];
foreach ($weeks as $week) {
    $key = $week->format('Y-m');
    if (!isset($monthGroups[$key])) {
        $monthGroups[$key] = ['label' => $week->format('M Y'), 'span' => 0];
    }
    $monthGroups[$key]['span']++;
}

$doneStatuses = ['approved', 'delivered', 'archived'];

// Calculate bar position [before-cols, bar-cols, after-cols]
$getBar = function($task) use ($weeks, $totalWeeks) {
    $ts = Carbon::parse($task->created_at)->startOfWeek();
    $te = Carbon::parse($task->deadline)->endOfWeek();
    $before = 0; $span = 0;
    foreach ($weeks as $week) {
        if ($week->copy()->endOfWeek() < $ts) $before++;
        elseif ($week->lte($te))              $span++;
    }
    $span  = max(1, $span);
    $after = max(0, $totalWeeks - $before - $span);
    return [$before, $span, $after];
};

$today = now()->startOfDay();

// Auto-size rows to fill the A4 landscape page
$rh      = $rowHeight ?? 24;
$rhPx    = $rh . 'px';
$projRh  = max($rh + 6, 22) . 'px';
$barH    = max(10, $rh - 8);
$rowFont = max(7.5, min(11, 7.5 + ($rh - 22) * 0.12)) . 'px';
$barFont = max(6.5, min(10,  6.5 + ($rh - 22) * 0.10)) . 'px';
@endphp
<style>
@page  { size: A4 landscape; margin: 5mm; }
*      { margin:0; padding:0; box-sizing:border-box; }
body   { font-family: DejaVu Sans, Arial, sans-serif; font-size:8px; color:#1F2937; background:#fff; overflow:hidden; }
table  { page-break-inside: avoid; }
tr     { page-break-inside: avoid; page-break-after: avoid; }

/* ── Page header ── */
.pg-hdr   { display:table; width:100%; background:#fff; padding-bottom:2px; margin-bottom:2px; }
.pg-hdr-l { display:table-cell; vertical-align:middle; white-space:nowrap; }
.pg-hdr-r { display:table-cell; vertical-align:middle; text-align:right; white-space:nowrap; }
.brand    { font-size:11px; font-weight:800; color:#111827; }
.pg-sep   { color:#D1D5DB; margin:0 4px; }
.pg-sub   { font-size:7.5px; color:#6B7280; }
.pg-stats { font-size:6.5px; color:#9CA3AF; }
.pg-stats strong { color:#374151; }
.red      { color:#DC2626; font-weight:700; }

/* ── Legend — own compact row ── */
.legend-bar { width:100%; padding:2px 0; border-bottom:1px solid #E5E7EB; margin-bottom:3px; }
.legend     { display:table; width:100%; }
.leg-item   { display:table-cell; vertical-align:middle; white-space:nowrap; text-align:center; }
.leg-dot    { display:inline-block; width:7px; height:7px; border-radius:2px; margin-right:2px; vertical-align:middle; }
.leg-lbl    { font-size:5.5px; color:#6B7280; vertical-align:middle; }

/* ── Gantt table ── */
.gantt    { width:100%; border-collapse:collapse; table-layout:fixed; }
.left-col { width:150px; background:#FAFAFA; }

/* Header rows — matching web: #F8F9FA bg, #4F46E5 month text, #9CA3AF day text */
.hdr-left  { background:#F8F9FA; border-right:1px solid #E5E7EB; border-bottom:1px solid #E5E7EB; }
.hdr-month { background:#F8F9FA; color:#4F46E5; text-align:left; font-size:6.5px; font-weight:700;
             padding:2px 4px 1px; border-right:1px solid #E5E7EB; border-bottom:1px solid #E5E7EB;
             letter-spacing:.04em; text-transform:uppercase; }
.hdr-week  { background:#F8F9FA; color:#9CA3AF; text-align:center; font-size:5.5px;
             padding:1px 0; border-right:1px solid #F3F4F6; border-bottom:2px solid #E5E7EB; }
.hdr-week.today-col { background:#FFFBEB; color:#D97706; font-weight:700; }

/* Project separator — matching web: #F5F3FF bg, #4F46E5 text */
.proj-row td { background:#F5F3FF; color:#4F46E5; font-size:{{ $rowFont }}; font-weight:700;
               padding:3px 7px; text-transform:uppercase; letter-spacing:.05em;
               border-top:1px solid #E5E7EB; border-bottom:1px solid #E5E7EB; }

/* Task label column — matching web: #FAFAFA sidebar */
.task-lbl  { font-size:{{ $rowFont }}; color:#374151; padding:1px 6px 1px 14px;
             vertical-align:middle; white-space:nowrap; overflow:hidden;
             border-bottom:1px solid #F3F4F6; border-right:1px solid #E5E7EB;
             background:#FAFAFA; }
.task-lbl.overdue-lbl { background:#FFF5F5; color:#EF4444; }

/* Empty timeline cells */
.tc      { border-bottom:1px solid #F3F4F6; border-right:1px solid #F3F4F6; padding:0; background:#fff; }
.tc.overdue-row { background:#FFF5F5; }
.tc.today-col   { background:#FFFBEB; border-right:1px solid #FDE68A; }

/* Bar cell */
.bar-td  { padding:1px 0; vertical-align:middle; border-bottom:1px solid #F3F4F6; background:#fff; }
.bar-td.overdue-row { background:#FFF5F5; }
.bar-td.today-col   { background:#FFFBEB; }

/* Footer */
.footer  { display:table; width:100%; margin-top:2px; border-top:1px solid #E5E7EB; padding-top:2px; }
.ft-l    { display:table-cell; font-size:6.5px; font-weight:700; color:#4F46E5; }
.ft-m    { display:table-cell; text-align:center; font-size:6px; color:#9CA3AF; }
.ft-r    { display:table-cell; text-align:right; font-size:5.5px; color:#CBD5E1; font-style:italic; }
</style>
</head>
<body>

{{-- ══ PAGE HEADER ══ --}}
<div class="pg-hdr">
    <div class="pg-hdr-l">
        @if($logoPath)
        <img src="{{ $logoPath }}" style="height:20px;max-width:28px;object-fit:contain;vertical-align:middle;margin-right:5px;">
        @endif
        <span class="brand">{{ $appName }}</span>
        <span class="pg-sep">|</span>
        <span class="pg-sub">Gantt Chart Report</span>
    </div>
    <div class="pg-hdr-r">
        <span class="pg-stats">
            <strong>{{ $summary['total_projects'] }}</strong> projects &nbsp;·&nbsp;
            <strong>{{ $summary['total_tasks'] }}</strong> tasks &nbsp;·&nbsp;
            <strong>{{ $summary['done_tasks'] }}</strong> completed &nbsp;·&nbsp;
            <span class="red">{{ $summary['overdue_tasks'] }}</span> overdue &nbsp;·&nbsp;
            Generated {{ $summary['generated_at'] }}
        </span>
    </div>
</div>

{{-- ══ LEGEND (full-width, evenly distributed) ══ --}}
<div class="legend-bar">
    <div class="legend">
        @foreach($statusColors as $sc)
        <div class="leg-item">
            <span class="leg-dot" style="background:{{ $sc['bg'] }};border:1px solid {{ $sc['color'] }};"></span>
            <span class="leg-lbl">{{ $sc['label'] }}</span>
        </div>
        @endforeach
        <div class="leg-item">
            <span class="leg-dot" style="background:#EF4444;border:1px solid #EF4444;"></span>
            <span class="leg-lbl" style="color:#EF4444;">Overdue</span>
        </div>
    </div>
</div>

{{-- ══ GANTT TABLE ══ --}}
@if($totalWeeks > 0)
<table class="gantt">

    {{-- Month header row --}}
    <tr>
        <th class="hdr-left left-col" style="text-align:left;padding:4px 6px;font-size:6.5px;font-weight:600;color:#9CA3AF;letter-spacing:.06em;text-transform:uppercase;border-bottom:1px solid #E5E7EB;">
            PROJECT / TASK
        </th>
        @foreach($monthGroups as $month)
        <th class="hdr-month" colspan="{{ $month['span'] }}">{{ $month['label'] }}</th>
        @endforeach
    </tr>

    {{-- Week/day header row --}}
    <tr>
        <th class="hdr-left left-col" style="border-bottom:2px solid #E5E7EB;"></th>
        @foreach($weeks as $week)
        @php $isTodayW = $week->lte($today) && $week->copy()->endOfWeek()->gte($today); @endphp
        <th class="hdr-week{{ $isTodayW ? ' today-col' : '' }}">{{ $week->format('d') }}</th>
        @endforeach
    </tr>

    @php $rowNum = 0; @endphp
    @foreach($projects as $project)

    {{-- Project separator (light lavender, indigo text — exactly as web) --}}
    <tr class="proj-row" style="height:{{ $projRh }};">
        <td colspan="{{ $totalWeeks + 1 }}" style="height:{{ $projRh }};">
            {{ $project->name }}
            <span style="font-size:{{ $barFont }};font-weight:600;color:#4338CA;margin-left:8px;">
                {{ $project->tasks->count() }} {{ Str::plural('task', $project->tasks->count()) }}
                @php $oc = $project->tasks->filter(fn($t) => $t->deadline->isPast() && !in_array($t->status,$doneStatuses))->count(); @endphp
                @if($oc) <span style="color:#991B1B;">&nbsp;·&nbsp; &#9888; {{ $oc }} overdue</span>@endif
            </span>
        </td>
    </tr>

    @foreach($project->tasks as $task)
    @php
        [$before, $barSpan, $after] = $getBar($task);
        $sc     = $statusColors[$task->status] ?? $statusColors['draft'];
        $isOver = $task->deadline->isPast() && !in_array($task->status, $doneStatuses);
        $overCls = $isOver ? ' overdue-row' : '';
        $rowNum++;
    @endphp
    <tr style="height:{{ $rhPx }};">
        {{-- Task label (sidebar) --}}
        <td class="task-lbl{{ $isOver ? ' overdue-lbl' : '' }}" style="height:{{ $rhPx }};">
            <span style="display:inline-block;width:7px;height:7px;border-radius:2px;background:{{ $sc['bg'] }};border:1px solid {{ $sc['color'] }};margin-right:4px;vertical-align:middle;"></span>
            <span>{{ Str::limit($task->title, 24) }}</span>
        </td>

        {{-- Empty cells before bar --}}
        @if($before > 0)
        @foreach(range(1, $before) as $b)
        @php $wIdx = $b - 1; $isTodayCol = isset($weeks[$wIdx]) && $weeks[$wIdx]->lte($today) && $weeks[$wIdx]->copy()->endOfWeek()->gte($today); @endphp
        <td class="tc{{ $overCls }}{{ $isTodayCol ? ' today-col' : '' }}" style="height:{{ $rhPx }};"></td>
        @endforeach
        @endif

        {{-- Bar cell --}}
        <td class="bar-td{{ $overCls }}" colspan="{{ $barSpan }}" style="height:{{ $rhPx }};">
            <div style="
                background:{{ $sc['bg'] }};
                color:{{ $sc['color'] }};
                border-left:3px solid {{ $sc['color'] }};
                border-radius:3px;
                padding:1px 5px;
                font-size:{{ $barFont }};
                font-weight:600;
                white-space:nowrap;
                overflow:hidden;
                height:{{ $barH }}px;
                line-height:{{ $barH }}px;
                {{ $isOver ? 'border:1.5px solid #EF4444;border-left:3px solid #EF4444;' : '' }}
            ">{{ Str::limit($task->title, 24) }}</div>
        </td>

        {{-- Empty cells after bar --}}
        @if($after > 0)
        @foreach(range(1, $after) as $a)
        @php $wIdx = $before + $barSpan + $a - 1; $isTodayCol = isset($weeks[$wIdx]) && $weeks[$wIdx]->lte($today) && $weeks[$wIdx]->copy()->endOfWeek()->gte($today); @endphp
        <td class="tc{{ $overCls }}{{ $isTodayCol ? ' today-col' : '' }}" style="height:{{ $rhPx }};"></td>
        @endforeach
        @endif
    </tr>
    @endforeach
    @endforeach
</table>
@else
<p style="color:#9CA3AF;text-align:center;padding:30px;">No tasks with deadlines found.</p>
@endif

{{-- ══ FOOTER ══ --}}
<div class="footer">
    <div class="ft-l">{{ $appName }} — Gantt Chart</div>
    <div class="ft-m">{{ now()->format('d M Y') }} &nbsp;·&nbsp; {{ $summary['total_projects'] }} projects &nbsp;·&nbsp; {{ $summary['total_tasks'] }} tasks</div>
    <div class="ft-r">Confidential — Internal Use Only</div>
</div>

</body>
</html>

@extends('layouts.app')
@section('title', 'Reports & Analytics')

@section('content')

{{-- ══ Print CSS ══ --}}
<style>
/* ── Screen layout helpers ── */
.rpt-grid-4  { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px; }
.rpt-grid-2  { display:grid; grid-template-columns:1fr 1fr;       gap:16px; margin-bottom:20px; }
.rpt-grid-3  { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:20px; }
@media(max-width:1100px){ .rpt-grid-4 { grid-template-columns:repeat(2,1fr); } }
@media(max-width:900px) { .rpt-grid-2,.rpt-grid-3 { grid-template-columns:1fr; } }
@media(max-width:600px) { .rpt-grid-4 { grid-template-columns:1fr; } }

.rpt-card {
    background:#fff; border-radius:14px;
    border:1px solid #E5E7EB;
    box-shadow:0 1px 4px rgba(0,0,0,.05);
    padding:20px;
}
.rpt-section-title {
    font-size:13px; font-weight:700; color:#374151;
    text-transform:uppercase; letter-spacing:.06em;
    margin:0 0 14px; display:flex; align-items:center; gap:8px;
}
.rpt-table { width:100%; border-collapse:collapse; font-size:13px; }
.rpt-table th {
    text-align:left; padding:9px 12px;
    font-size:11px; font-weight:700; color:#6B7280;
    text-transform:uppercase; letter-spacing:.05em;
    background:#F9FAFB; border-bottom:1px solid #E5E7EB;
}
.rpt-table td { padding:10px 12px; border-bottom:1px solid #F3F4F6; color:#374151; }
.rpt-table tr:last-child td { border-bottom:none; }
.rpt-table tr:hover td { background:#FAFAFA; }
.rpt-bar-track { height:6px; background:#F3F4F6; border-radius:4px; overflow:hidden; margin-top:4px; }
.rpt-bar-fill  { height:6px; border-radius:4px; transition:width .3s; }
.rpt-badge { display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600; }
.rpt-rate-circle { position:relative; display:inline-flex; align-items:center; justify-content:center; }

/* ── Priority chips ── */
.chip-low    { background:#D1FAE5;color:#059669; }
.chip-medium { background:#FEF3C7;color:#D97706; }
.chip-high   { background:#FEE2E2;color:#DC2626; }

/* ══ PRINT STYLES ══════════════════════════════════════ */
@media print {
    /* ── Hide app chrome ── */
    .app-sidebar, .app-topbar,
    #rpt-filter-bar, .no-print { display:none !important; }

    /* ── Full-page overflow for printing ── */
    html, body { height:auto !important; overflow:visible !important; background:#fff !important; }
    .app-shell   { display:block !important; height:auto !important; overflow:visible !important; }
    .app-main    { display:block !important; height:auto !important; overflow:visible !important; }
    .app-content { height:auto !important; overflow:visible !important; padding:0 !important; animation:none !important; background:#fff !important; }

    /* ── Report area ── */
    #rpt-capture-zone { padding:0 !important; background:#fff !important; }
    #rpt-main-content { margin:0 !important; padding:0 !important; }
    #rpt-print-header { display:block !important; margin-bottom:18px !important; }

    /* ── Cards & grids preserve layout ── */
    .rpt-card {
        border:1px solid #D1D5DB !important;
        box-shadow:none !important;
        break-inside:avoid;
        -webkit-column-break-inside:avoid;
        page-break-inside:avoid;
    }
    .rpt-grid-4 { grid-template-columns:repeat(4,1fr) !important; }
    .rpt-grid-2 { grid-template-columns:1fr 1fr !important; }
    .rpt-grid-3 { grid-template-columns:repeat(3,1fr) !important; }

    /* ── Progress bars & badges print in color ── */
    .rpt-bar-fill  { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rpt-badge     { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    * { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }

    /* ── Page breaks ── */
    .rpt-page-break { page-break-before:always; break-before:page; }
    h2, h3, .rpt-section-title { page-break-after:avoid; }
    table { page-break-inside:auto; }
    tr    { page-break-inside:avoid; page-break-after:auto; }

    @page { size:A4 landscape; margin:10mm 12mm; }
}

/* Print header (hidden on screen, shown when printing/PDF) */
#rpt-print-header { display:none; }
</style>

{{-- ══ Capture zone: wraps everything html2canvas captures ══ --}}
<div id="rpt-capture-zone" style="background:#F8FAFC;">

{{-- ══ Print Header (visible only when printing / exporting PDF) ══ --}}
<div id="rpt-print-header" style="margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid #6366F1;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0;">Reports & Analytics</h1>
            <p style="font-size:12px;color:#6B7280;margin:4px 0 0;">
                Generated: {{ now()->format('F d, Y — H:i') }} &nbsp;|&nbsp;
                Period: {{ $from ? $from->format('M d, Y').' – '.now()->format('M d, Y') : 'All Time' }}
            </p>
        </div>
        <div style="text-align:right;">
            <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ config('app.name') }}</p>
            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Admin Report — Confidential</p>
        </div>
    </div>
</div>

<div id="rpt-main-content">

{{-- ══ Filter / Action Bar ══ --}}
<div id="rpt-filter-bar" class="no-print" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Reports & Analytics</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">
            {{ $from ? 'From '.$from->format('M d, Y').' to '.now()->format('M d, Y') : 'All time data' }}
        </p>
    </div>
    <div id="rpt-actions-bar" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

        {{-- Range selector --}}
        <form method="GET" action="{{ route('admin.reports.index') }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:2px;background:#F3F4F6;border-radius:9px;padding:3px;">
                @foreach(['7'=>'7D','30'=>'30D','90'=>'90D','365'=>'1Y','all'=>'All'] as $val=>$label)
                <button type="submit" name="range" value="{{ $val }}"
                        @if($projectId) onclick="this.form.querySelector('[name=project_id]').value='{{ $projectId }}'" @endif
                        style="padding:5px 13px;font-size:12px;font-weight:600;border:none;border-radius:7px;cursor:pointer;transition:all .15s;{{ $range===$val ? 'background:#fff;color:#4F46E5;box-shadow:0 1px 3px rgba(0,0,0,.1);' : 'background:none;color:#6B7280;' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>

            {{-- Project filter --}}
            <select name="project_id" onchange="this.form.submit()"
                    style="font-size:12px;border:1px solid #E5E7EB;border-radius:8px;padding:7px 28px 7px 10px;background:#fff;color:#374151;outline:none;-webkit-appearance:none;appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 10px center;">
                <option value="">All Projects</option>
                @foreach($allProjects as $p)
                <option value="{{ $p->id }}" {{ $projectId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
            <input type="hidden" name="range" value="{{ $range }}">
        </form>

        {{-- Print --}}
        <button id="print-btn" onclick="printReport()"
                style="display:flex;align-items:center;gap:7px;padding:8px 15px;background:#fff;color:#374151;font-size:13px;font-weight:600;border:1.5px solid #E5E7EB;border-radius:9px;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.06);transition:all .15s;white-space:nowrap;"
                onmouseover="this.style.borderColor='#6B7280';this.style.color='#111827'"
                onmouseout="this.style.borderColor='#E5E7EB';this.style.color='#374151'">
            <i class="fas fa-print" style="font-size:12px;"></i> Print
        </button>

        {{-- Export PDF --}}
        <button id="pdf-btn" onclick="exportPDF()"
                style="display:flex;align-items:center;gap:7px;padding:8px 15px;background:#fff;color:#DC2626;font-size:13px;font-weight:600;border:1.5px solid #FECACA;border-radius:9px;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.06);transition:all .15s;white-space:nowrap;"
                onmouseover="this.style.background='#FEF2F2'"
                onmouseout="this.style.background='#fff'">
            <i class="fas fa-file-pdf" style="font-size:12px;"></i> Export PDF
        </button>

        {{-- Export Excel --}}
        <button id="excel-btn" onclick="exportExcel()"
                style="display:flex;align-items:center;gap:7px;padding:8px 15px;background:#fff;color:#16A34A;font-size:13px;font-weight:600;border:1.5px solid #BBF7D0;border-radius:9px;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.06);transition:all .15s;white-space:nowrap;"
                onmouseover="this.style.background='#F0FDF4'"
                onmouseout="this.style.background='#fff'">
            <i class="fas fa-file-excel" style="font-size:12px;"></i> Export Excel
        </button>
    </div>
</div>

{{-- ══ KPI Summary Row ══ --}}
<div class="rpt-grid-4" style="margin-bottom:20px;">
    @php
    $kpis = [
        ['label'=>'Total Tasks',      'value'=>$totalTasks,     'icon'=>'fa-list-check',          'color'=>'#6366F1','bg'=>'#EEF2FF', 'sub'=>'In selected period'],
        ['label'=>'Completed',        'value'=>$completedTasks, 'icon'=>'fa-circle-check',        'color'=>'#10B981','bg'=>'#D1FAE5', 'sub'=>'Approved + Delivered'],
        ['label'=>'Completion Rate',  'value'=>$completionRate.'%','icon'=>'fa-chart-pie',         'color'=>'#F59E0B','bg'=>'#FEF3C7', 'sub'=>'Of all tasks done'],
        ['label'=>'On-time Rate',     'value'=>$onTimeRate.'%', 'icon'=>'fa-clock',               'color'=>'#8B5CF6','bg'=>'#EDE9FE', 'sub'=>'Finished before deadline'],
        ['label'=>'Overdue',          'value'=>$overdueTasks,   'icon'=>'fa-triangle-exclamation','color'=>'#EF4444','bg'=>'#FEE2E2', 'sub'=>'Need immediate attention'],
        ['label'=>'Active Projects',  'value'=>$activeProjects, 'icon'=>'fa-diagram-project',     'color'=>'#3B82F6','bg'=>'#DBEAFE', 'sub'=>'Currently running'],
        ['label'=>'Pending Review',   'value'=>$pendingReview,  'icon'=>'fa-gavel',               'color'=>'#7C3AED','bg'=>'#EDE9FE', 'sub'=>'Awaiting approval'],
        ['label'=>'Team Members',     'value'=>$teamMembers->count(),'icon'=>'fa-users',          'color'=>'#059669','bg'=>'#ECFDF5', 'sub'=>'Active contributors'],
    ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="rpt-card" style="padding:16px 18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div style="width:36px;height:36px;border-radius:10px;background:{{ $kpi['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas {{ $kpi['icon'] }}" style="color:{{ $kpi['color'] }};font-size:14px;"></i>
            </div>
        </div>
        <p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">{{ $kpi['value'] }}</p>
        <p style="font-size:12px;font-weight:600;color:#374151;margin:4px 0 2px;">{{ $kpi['label'] }}</p>
        <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $kpi['sub'] }}</p>
    </div>
    @endforeach
</div>

{{-- ══ Row 2: Status Breakdown + Priority ══ --}}
<div class="rpt-grid-2" style="margin-bottom:20px;">

    {{-- Status Breakdown --}}
    <div class="rpt-card">
        <p class="rpt-section-title">
            <span style="width:26px;height:26px;border-radius:7px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-bars-progress" style="color:#6366F1;font-size:11px;"></i>
            </span>
            Task Status Breakdown
        </p>
        <div style="display:flex;flex-direction:column;gap:12px;">
            @foreach($statusBreakdown as $key => $s)
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <div style="display:flex;align-items:center;gap:7px;">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $s['color'] }};display:inline-block;flex-shrink:0;"></span>
                        <span style="font-size:13px;font-weight:600;color:#374151;">{{ $s['label'] }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:13px;font-weight:700;color:#111827;">{{ $s['count'] }}</span>
                        <span style="font-size:11px;color:#9CA3AF;min-width:32px;text-align:right;">{{ $s['pct'] }}%</span>
                    </div>
                </div>
                <div class="rpt-bar-track">
                    <div class="rpt-bar-fill" style="width:{{ $s['pct'] }}%;background:{{ $s['color'] }};"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Priority Distribution --}}
    <div class="rpt-card">
        <p class="rpt-section-title">
            <span style="width:26px;height:26px;border-radius:7px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-flag" style="color:#F59E0B;font-size:11px;"></i>
            </span>
            Priority Distribution
        </p>

        {{-- Big visual cards for each priority --}}
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;">
            @foreach($priorityBreakdown as $p => $data)
            <div style="background:{{ $data['bg'] }};border-radius:12px;padding:16px;text-align:center;">
                <p style="font-size:30px;font-weight:800;color:{{ $data['color'] }};margin:0;line-height:1;">{{ $data['count'] }}</p>
                <p style="font-size:12px;font-weight:700;color:{{ $data['color'] }};margin:4px 0 2px;">{{ $data['label'] }}</p>
                <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $data['pct'] }}% of total</p>
            </div>
            @endforeach
        </div>

        {{-- Bar chart canvas --}}
        <div style="position:relative;height:120px;">
            <canvas id="priorityChart"></canvas>
        </div>
    </div>

</div>

{{-- ══ Row 3: Monthly Trend (full width) ══ --}}
<div class="rpt-card" style="margin-bottom:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
        <p class="rpt-section-title" style="margin:0;">
            <span style="width:26px;height:26px;border-radius:7px;background:#DBEAFE;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-chart-line" style="color:#3B82F6;font-size:11px;"></i>
            </span>
            6-Month Task Trend
        </p>
        <div style="display:flex;align-items:center;gap:16px;">
            <div style="display:flex;align-items:center;gap:5px;">
                <span style="width:12px;height:3px;border-radius:2px;background:#6366F1;display:inline-block;"></span>
                <span style="font-size:12px;color:#6B7280;">Created</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;">
                <span style="width:12px;height:3px;border-radius:2px;background:#10B981;display:inline-block;"></span>
                <span style="font-size:12px;color:#6B7280;">Completed</span>
            </div>
        </div>
    </div>
    <div style="position:relative;height:200px;">
        <canvas id="trendChart"></canvas>
    </div>
</div>

{{-- ══ Row 4: Project Performance ══ --}}
<div class="rpt-card" style="margin-bottom:20px;">
    <p class="rpt-section-title">
        <span style="width:26px;height:26px;border-radius:7px;background:#D1FAE5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-diagram-project" style="color:#10B981;font-size:11px;"></i>
        </span>
        Project Performance
    </p>
    @if($projects->isEmpty())
    <p style="text-align:center;color:#9CA3AF;font-size:13px;padding:24px 0;">No project data available.</p>
    @else
    <div style="overflow-x:auto;">
        <table class="rpt-table" id="proj-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;">Total Tasks</th>
                    <th style="text-align:center;">Completed</th>
                    <th style="text-align:center;">In Progress</th>
                    <th style="text-align:center;">Overdue</th>
                    <th style="min-width:130px;">Progress</th>
                    <th>Deadline</th>
                </tr>
            </thead>
            <tbody>
                @foreach($projects as $proj)
                @php
                    $statusColor = match($proj['status']) {
                        'active'    => ['#DCFCE7','#16A34A'],
                        'completed' => ['#DBEAFE','#1D4ED8'],
                        default     => ['#F3F4F6','#6B7280'],
                    };
                    $ddColor = is_null($proj['days_left']) ? '#9CA3AF' : ($proj['days_left'] < 0 ? '#EF4444' : ($proj['days_left'] <= 7 ? '#F59E0B' : '#6B7280'));
                @endphp
                <tr>
                    <td>
                        <p style="font-weight:600;color:#111827;margin:0;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj['name'] }}</p>
                    </td>
                    <td style="text-align:center;">
                        <span class="rpt-badge" style="background:{{ $statusColor[0] }};color:{{ $statusColor[1] }};">
                            {{ ucfirst($proj['status']) }}
                        </span>
                    </td>
                    <td style="text-align:center;font-weight:600;">{{ $proj['total'] }}</td>
                    <td style="text-align:center;">
                        <span style="color:#10B981;font-weight:700;">{{ $proj['completed'] }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span style="color:#F59E0B;font-weight:700;">{{ $proj['in_progress'] }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span style="color:{{ $proj['overdue'] > 0 ? '#EF4444' : '#9CA3AF' }};font-weight:700;">
                            {{ $proj['overdue'] }}
                        </span>
                    </td>
                    <td style="min-width:130px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;height:6px;background:#F3F4F6;border-radius:3px;overflow:hidden;">
                                <div style="height:6px;width:{{ $proj['rate'] }}%;background:{{ $proj['rate'] >= 80 ? '#10B981' : ($proj['rate'] >= 40 ? '#F59E0B' : '#EF4444') }};border-radius:3px;"></div>
                            </div>
                            <span style="font-size:12px;font-weight:700;color:#374151;min-width:32px;">{{ $proj['rate'] }}%</span>
                        </div>
                    </td>
                    <td>
                        @if($proj['deadline'])
                        <span style="font-size:12px;color:{{ $ddColor }};font-weight:600;">
                            {{ $proj['deadline']->format('M d, Y') }}
                            @if(!is_null($proj['days_left']))
                                <br><span style="font-size:10px;font-weight:400;">
                                    {{ $proj['days_left'] < 0 ? abs($proj['days_left']).'d overdue' : $proj['days_left'].'d left' }}
                                </span>
                            @endif
                        </span>
                        @else
                        <span style="font-size:12px;color:#D1D5DB;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ══ Row 5: Team Productivity ══ --}}
<div class="rpt-card rpt-page-break" style="margin-bottom:20px;">
    <p class="rpt-section-title">
        <span style="width:26px;height:26px;border-radius:7px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-users" style="color:#7C3AED;font-size:11px;"></i>
        </span>
        Team Productivity
    </p>
    @if($teamMembers->isEmpty())
    <p style="text-align:center;color:#9CA3AF;font-size:13px;padding:24px 0;">No team data for this period.</p>
    @else
    <div style="overflow-x:auto;">
        <table class="rpt-table" id="team-table">
            <thead>
                <tr>
                    <th>Member</th>
                    <th style="text-align:center;">Role</th>
                    <th style="text-align:center;">Assigned</th>
                    <th style="text-align:center;">Completed</th>
                    <th style="text-align:center;">In Progress</th>
                    <th style="text-align:center;">In Review</th>
                    <th style="text-align:center;">Overdue</th>
                    <th style="min-width:120px;">Completion</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teamMembers->sortByDesc('completed') as $member)
                <tr>
                    <td>
                        <p style="font-weight:600;color:#111827;margin:0;">{{ $member['name'] }}</p>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-size:11px;color:#6B7280;background:#F3F4F6;padding:2px 8px;border-radius:20px;font-weight:600;">
                            {{ $member['role'] }}
                        </span>
                    </td>
                    <td style="text-align:center;font-weight:600;">{{ $member['total'] }}</td>
                    <td style="text-align:center;"><span style="color:#10B981;font-weight:700;">{{ $member['completed'] }}</span></td>
                    <td style="text-align:center;"><span style="color:#F59E0B;font-weight:700;">{{ $member['in_progress'] }}</span></td>
                    <td style="text-align:center;"><span style="color:#8B5CF6;font-weight:700;">{{ $member['in_review'] }}</span></td>
                    <td style="text-align:center;">
                        <span style="color:{{ $member['overdue'] > 0 ? '#EF4444' : '#9CA3AF' }};font-weight:700;">{{ $member['overdue'] }}</span>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;height:6px;background:#F3F4F6;border-radius:3px;overflow:hidden;">
                                <div style="height:6px;width:{{ $member['rate'] }}%;background:{{ $member['rate'] >= 80 ? '#10B981' : ($member['rate'] >= 40 ? '#F59E0B' : '#EF4444') }};border-radius:3px;"></div>
                            </div>
                            <span style="font-size:12px;font-weight:700;color:#374151;min-width:32px;">{{ $member['rate'] }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ══ Row 6: Overdue Tasks ══ --}}
@if($overdueList->isNotEmpty())
<div class="rpt-card" style="margin-bottom:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <p class="rpt-section-title" style="margin:0;">
            <span style="width:26px;height:26px;border-radius:7px;background:#FEE2E2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-triangle-exclamation" style="color:#EF4444;font-size:11px;"></i>
            </span>
            Overdue Tasks ({{ $overdueList->count() }})
        </p>
        <span style="font-size:12px;color:#EF4444;background:#FEE2E2;padding:3px 10px;border-radius:20px;font-weight:600;">
            Needs Attention
        </span>
    </div>
    <div style="overflow-x:auto;">
        <table class="rpt-table" id="overdue-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Project</th>
                    <th>Assignee</th>
                    <th>Deadline</th>
                    <th style="text-align:center;">Days Late</th>
                    <th style="text-align:center;">Priority</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($overdueList as $task)
                <tr>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <span style="font-weight:600;color:#111827;">{{ $task['title'] }}</span>
                    </td>
                    <td style="color:#6B7280;">{{ $task['project'] }}</td>
                    <td style="color:#6B7280;">{{ $task['assignee'] }}</td>
                    <td style="color:#EF4444;font-weight:600;">{{ $task['deadline'] }}</td>
                    <td style="text-align:center;">
                        <span style="background:#FEE2E2;color:#DC2626;padding:2px 8px;border-radius:20px;font-size:12px;font-weight:700;">
                            +{{ $task['days_late'] }}d
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <span class="rpt-badge chip-{{ $task['priority'] }}">{{ ucfirst($task['priority']) }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:20px;font-weight:600;">
                            {{ ucwords(str_replace('_',' ',$task['status'])) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══ Footer ══ --}}
<div style="text-align:center;padding:16px 0;color:#D1D5DB;font-size:11px;" class="no-print">
    Report generated on {{ now()->format('F d, Y \a\t H:i') }} &nbsp;·&nbsp; {{ config('app.name') }}
</div>

</div>{{-- #rpt-main-content --}}
</div>{{-- #rpt-capture-zone --}}

@endsection

@push('scripts')
{{-- ── Export libraries ── --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
/* ══════════════════════════════════════════════════════════
   Charts
══════════════════════════════════════════════════════════ */
Chart.defaults.font = { family: 'Inter, system-ui, sans-serif', size: 12 };
Chart.defaults.color = '#9CA3AF';

new Chart(document.getElementById('priorityChart'), {
    type: 'bar',
    data: {
        labels: ['Low', 'Medium', 'High'],
        datasets: [{
            data: [
                {{ $priorityBreakdown['low']['count'] ?? 0 }},
                {{ $priorityBreakdown['medium']['count'] ?? 0 }},
                {{ $priorityBreakdown['high']['count'] ?? 0 }}
            ],
            backgroundColor: ['rgba(16,185,129,0.82)','rgba(245,158,11,0.82)','rgba(239,68,68,0.82)'],
            borderRadius: 6, borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: { grid: { color: '#F3F4F6' }, border: { display: false }, beginAtZero: true, ticks: { stepSize: 1, maxTicksLimit: 5 } }
        }
    }
});

new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: @json($monthLabels),
        datasets: [
            { label: 'Created',   data: @json($monthlyCreated),   backgroundColor: 'rgba(99,102,241,0.8)',  borderRadius: 5, borderSkipped: false, barPercentage: 0.55 },
            { label: 'Completed', data: @json($monthlyCompleted), backgroundColor: 'rgba(16,185,129,0.8)', borderRadius: 5, borderSkipped: false, barPercentage: 0.55 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y } }
        },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: { grid: { color: '#F3F4F6' }, border: { display: false }, beginAtZero: true, ticks: { stepSize: 1, maxTicksLimit: 6 } }
        }
    }
});

/* ══════════════════════════════════════════════════════════
   Shared helpers: show/hide UI for export capture
══════════════════════════════════════════════════════════ */
function prepareCapture() {
    document.getElementById('rpt-print-header').style.display = 'block';
    document.getElementById('rpt-filter-bar').style.display   = 'none';
}
function restoreCapture() {
    document.getElementById('rpt-print-header').style.display = 'none';
    document.getElementById('rpt-filter-bar').style.display   = '';
}

/* Convert <canvas> elements to <img> so browser/html2canvas renders them */
let _savedCanvases = [];
function canvasToImages() {
    _savedCanvases = [];
    document.querySelectorAll('#rpt-capture-zone canvas').forEach(cv => {
        const img = document.createElement('img');
        img.src = cv.toDataURL('image/png');
        img.style.width  = cv.offsetWidth  + 'px';
        img.style.height = cv.offsetHeight + 'px';
        img.style.display = 'block';
        cv.parentNode.insertBefore(img, cv);
        cv.style.display = 'none';
        _savedCanvases.push({ cv, img });
    });
}
function restoreCanvases() {
    _savedCanvases.forEach(({ cv, img }) => { cv.style.display = ''; img.remove(); });
    _savedCanvases = [];
}

/* ══════════════════════════════════════════════════════════
   PRINT  (preserves exact screen layout + colors)
══════════════════════════════════════════════════════════ */
window.addEventListener('beforeprint', () => { prepareCapture(); canvasToImages(); });
window.addEventListener('afterprint',  () => { restoreCapture(); restoreCanvases(); });

function printReport() {
    window.print();
}

/* ══════════════════════════════════════════════════════════
   EXPORT PDF  — html2canvas + jsPDF, multi-page A4 landscape
══════════════════════════════════════════════════════════ */
async function exportPDF() {
    const btn = document.getElementById('pdf-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;font-size:12px;"></i>Generating…';
    btn.disabled  = true;

    prepareCapture();
    // Wait two frames so the DOM reflows
    await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));

    let canvas;
    try {
        canvas = await html2canvas(document.getElementById('rpt-capture-zone'), {
            scale:       1.6,
            useCORS:     true,
            allowTaint:  false,
            backgroundColor: '#F8FAFC',
            logging:     false,
            scrollX:     0,
            scrollY:     -window.scrollY,
            windowWidth: document.documentElement.scrollWidth,
        });
    } catch (e) {
        alert('PDF generation failed: ' + e.message);
        restoreCapture();
        btn.innerHTML = '<i class="fas fa-file-pdf" style="margin-right:6px;font-size:12px;"></i>Export PDF';
        btn.disabled  = false;
        return;
    }

    restoreCapture();

    const { jsPDF } = window.jspdf;
    const pdf   = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    const pageW = pdf.internal.pageSize.getWidth();   // 297 mm
    const pageH = pdf.internal.pageSize.getHeight();  // 210 mm

    const imgW = canvas.width;
    const imgH = canvas.height;

    // mm per canvas pixel so the image fills the full page width
    const mmPerPx  = pageW / imgW;
    const pageHpx  = pageH / mmPerPx;   // how many canvas px fit on one page

    let yPx = 0;
    while (yPx < imgH) {
        if (yPx > 0) pdf.addPage();

        const sliceHpx = Math.min(pageHpx, imgH - yPx);

        // Slice the canvas vertically
        const slice    = document.createElement('canvas');
        slice.width    = imgW;
        slice.height   = Math.ceil(sliceHpx);
        const ctx      = slice.getContext('2d');
        ctx.fillStyle  = '#F8FAFC';
        ctx.fillRect(0, 0, imgW, sliceHpx);
        ctx.drawImage(canvas, 0, -yPx, imgW, imgH);

        const imgData  = slice.toDataURL('image/jpeg', 0.93);
        pdf.addImage(imgData, 'JPEG', 0, 0, pageW, sliceHpx * mmPerPx);

        yPx += pageHpx;
    }

    pdf.setProperties({ title: 'Reports & Analytics — {{ config('app.name') }}' });
    pdf.save('report-{{ now()->format('Y-m-d') }}.pdf');

    btn.innerHTML = '<i class="fas fa-file-pdf" style="margin-right:6px;font-size:12px;"></i>Export PDF';
    btn.disabled  = false;
}

/* ══════════════════════════════════════════════════════════
   EXPORT EXCEL  — SheetJS, 4 sheets
══════════════════════════════════════════════════════════ */
function exportExcel() {
    const btn = document.getElementById('excel-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;font-size:12px;"></i>Exporting…';
    btn.disabled  = true;

    try {
        const wb = XLSX.utils.book_new();

        /* ── Sheet 1: Summary ── */
        const rows = [
            ['Reports & Analytics'],
            ['Generated', '{{ now()->format('F d, Y H:i') }}'],
            ['Period',    '{{ $from ? $from->format('M d, Y').' – '.now()->format('M d, Y') : 'All Time' }}'],
            [],
            ['KEY PERFORMANCE INDICATORS'],
            ['Metric', 'Value', 'Notes'],
            ['Total Tasks',     {{ $totalTasks }},            'In selected period'],
            ['Completed Tasks', {{ $completedTasks }},        'Approved + Delivered'],
            ['Completion Rate', '{{ $completionRate }}%',     ''],
            ['On-time Rate',    '{{ $onTimeRate }}%',         'Finished before deadline'],
            ['Overdue Tasks',   {{ $overdueTasks }},          'Need attention'],
            ['Active Projects', {{ $activeProjects }},        ''],
            ['Pending Review',  {{ $pendingReview }},         'Awaiting approval'],
            ['Team Members',    {{ $teamMembers->count() }},  'Active contributors'],
            [],
            ['TASK STATUS BREAKDOWN'],
            ['Status', 'Count', 'Percentage'],
            @foreach($statusBreakdown as $s)
            ['{{ $s['label'] }}', {{ $s['count'] }}, '{{ $s['pct'] }}%'],
            @endforeach
            [],
            ['PRIORITY BREAKDOWN'],
            ['Priority', 'Count', 'Percentage'],
            @foreach($priorityBreakdown as $data)
            ['{{ $data['label'] }}', {{ $data['count'] }}, '{{ $data['pct'] }}%'],
            @endforeach
            [],
            ['MONTHLY TREND (last 6 months)'],
            ['Month', 'Created', 'Completed'],
            @foreach($monthLabels as $i => $label)
            ['{{ $label }}', {{ $monthlyCreated[$i] ?? 0 }}, {{ $monthlyCompleted[$i] ?? 0 }}],
            @endforeach
        ];
        const ws1 = XLSX.utils.aoa_to_sheet(rows);
        ws1['!cols'] = [{wch:32}, {wch:16}, {wch:28}];
        XLSX.utils.book_append_sheet(wb, ws1, 'Summary');

        /* ── Sheet 2: Project Performance ── */
        const projTbl = document.getElementById('proj-table');
        if (projTbl) {
            const ws2 = XLSX.utils.table_to_sheet(projTbl, { raw: false });
            XLSX.utils.book_append_sheet(wb, ws2, 'Projects');
        }

        /* ── Sheet 3: Team Productivity ── */
        const teamTbl = document.getElementById('team-table');
        if (teamTbl) {
            const ws3 = XLSX.utils.table_to_sheet(teamTbl, { raw: false });
            XLSX.utils.book_append_sheet(wb, ws3, 'Team Productivity');
        }

        /* ── Sheet 4: Overdue Tasks ── */
        const overdueTbl = document.getElementById('overdue-table');
        if (overdueTbl) {
            const ws4 = XLSX.utils.table_to_sheet(overdueTbl, { raw: false });
            XLSX.utils.book_append_sheet(wb, ws4, 'Overdue Tasks');
        }

        XLSX.writeFile(wb, 'report-{{ now()->format('Y-m-d') }}.xlsx');
    } catch (e) {
        alert('Excel export failed: ' + e.message);
    }

    btn.innerHTML = '<i class="fas fa-file-excel" style="margin-right:6px;font-size:12px;"></i>Export Excel';
    btn.disabled  = false;
}
</script>
@endpush

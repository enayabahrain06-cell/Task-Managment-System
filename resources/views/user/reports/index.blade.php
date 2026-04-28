@extends('layouts.app')
@section('title', 'My Reports')

@section('content')

<style>
/* ── Screen layout ── */
.rpt-grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:12px; }
.rpt-grid-2 { display:grid; grid-template-columns:1fr 1fr;       gap:12px; margin-bottom:12px; }
.rpt-grid-6 { display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:16px; }
@media(max-width:1100px){ .rpt-grid-6 { grid-template-columns:repeat(3,1fr); } .rpt-grid-3 { grid-template-columns:repeat(2,1fr); } }
@media(max-width:700px)  { .rpt-grid-6,.rpt-grid-3,.rpt-grid-2 { grid-template-columns:1fr; } }

.rpt-card {
    background:#fff;
    border-radius:14px;
    border:1px solid #E5E7EB;
    box-shadow:0 1px 3px rgba(0,0,0,.04);
    padding:16px;
    margin-bottom:12px;
}
.rpt-section-title {
    font-size:12px; font-weight:700; color:#374151;
    text-transform:uppercase; letter-spacing:.06em;
    margin:0 0 12px; display:flex; align-items:center; gap:7px;
}
.rpt-table { width:100%; border-collapse:collapse; font-size:13px; }
.rpt-table th {
    text-align:left; padding:8px 10px;
    font-size:10px; font-weight:700; color:#6B7280;
    text-transform:uppercase; letter-spacing:.05em;
    background:#F9FAFB; border-bottom:1px solid #E5E7EB;
    position:sticky; top:0; z-index:1;
}
.rpt-table td { padding:8px 10px; border-bottom:1px solid #F3F4F6; color:#374151; font-size:12px; }
.rpt-table tr:last-child td { border-bottom:none; }
.rpt-table tr:hover td { background:#FAFAFA; }
.rpt-bar-track { height:6px; background:#F3F4F6; border-radius:4px; overflow:hidden; margin-top:5px; }
.rpt-bar-fill  { height:6px; border-radius:4px; }
.chip-low    { background:#D1FAE5;color:#059669;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600; }
.chip-medium { background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600; }
.chip-high   { background:#FEE2E2;color:#DC2626;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600; }
.rpt-scroll  { overflow-y:auto; max-height:240px; }

/* ── Tabs ── */
.rpt-tab { padding:8px 18px;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;display:inline-flex;align-items:center; }
.rpt-tab-active   { background:#fff;color:#111827;box-shadow:0 1px 4px rgba(0,0,0,.10); }
.rpt-tab-inactive { background:transparent;color:#6B7280; }
.rpt-tab-inactive:hover { color:#374151; }

/* ── Print ── */
#rpt-print-header { display:none; }

@media print {
    .app-sidebar, .app-topbar, .no-print { display:none !important; }
    html, body { height:auto !important; overflow:visible !important; background:#fff !important; }
    .app-shell, .app-main { display:block !important; height:auto !important; overflow:visible !important; }
    .app-content { height:auto !important; overflow:visible !important; padding:0 !important; animation:none !important; background:#fff !important; }
    .rpt-scroll { max-height:none !important; overflow-y:visible !important; }
    .rpt-card { border:1px solid #D1D5DB !important; box-shadow:none !important; break-inside:avoid; page-break-inside:avoid; }
    .rpt-grid-6 { grid-template-columns:repeat(6,1fr) !important; }
    .rpt-grid-2 { grid-template-columns:1fr 1fr !important; }
    .rpt-bar-fill { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    * { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }
    @page { size:A4 portrait; margin:14mm; }
    #rpt-print-header { display:block !important; margin-bottom:20px; }
    #rpt-header-gradient { display:none !important; }
}
</style>

{{-- ══ Print Header (hidden on screen) ══ --}}
<div id="rpt-print-header">
    {{-- Top accent bar --}}
    <div style="height:5px;background:linear-gradient(90deg,#4F46E5,#6366F1,#818CF8);border-radius:3px;margin-bottom:20px;"></div>

    {{-- Logo + company block --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        {{-- Left: logo + company name --}}
        <div style="display:flex;align-items:center;gap:14px;">
            @if(!empty($appSettings['logo_path']))
                <img src="{{ Storage::url($appSettings['logo_path']) }}"
                     alt="{{ $appSettings['company_name'] ?? $appSettings['app_name'] ?? 'Logo' }}"
                     style="height:48px;width:auto;max-width:160px;object-fit:contain;"
                     crossorigin="anonymous">
            @else
                <div style="width:44px;height:44px;background:#4F46E5;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <span style="color:#fff;font-size:20px;font-weight:800;line-height:1;">
                        {{ strtoupper(substr($appSettings['company_name'] ?? $appSettings['app_name'] ?? 'D', 0, 1)) }}
                    </span>
                </div>
            @endif
            <div>
                <div style="font-size:18px;font-weight:800;color:#111827;line-height:1.2;">
                    {{ $appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name') }}
                </div>
                @if(!empty($appSettings['app_tagline']))
                <div style="font-size:11px;color:#9CA3AF;margin-top:2px;">{{ $appSettings['app_tagline'] }}</div>
                @endif
            </div>
        </div>
        {{-- Right: report label --}}
        <div style="text-align:right;">
            <div style="font-size:20px;font-weight:800;color:#4F46E5;line-height:1.2;">My Performance Report</div>
            <div style="font-size:11px;color:#9CA3AF;margin-top:3px;">Confidential — Internal Use Only</div>
        </div>
    </div>

    {{-- Divider --}}
    <div style="border-top:1.5px solid #E5E7EB;margin-bottom:14px;"></div>

    {{-- Meta row --}}
    <div style="display:flex;gap:32px;flex-wrap:wrap;">
        <div>
            <span style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Employee</span>
            <div style="font-size:12px;font-weight:600;color:#374151;margin-top:2px;">{{ auth()->user()->name }}</div>
        </div>
        <div>
            <span style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Role</span>
            <div style="font-size:12px;font-weight:600;color:#374151;margin-top:2px;">{{ auth()->user()->job_title ?? ucfirst(auth()->user()->role) }}</div>
        </div>
        <div>
            <span style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Generated</span>
            <div style="font-size:12px;font-weight:600;color:#374151;margin-top:2px;">{{ now()->format('F d, Y') }} at {{ now()->format('H:i') }}</div>
        </div>
        <div>
            <span style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Period</span>
            <div style="font-size:12px;font-weight:600;color:#374151;margin-top:2px;">{{ $from ? $from->format('M d, Y').' – '.now()->format('M d, Y') : 'All Time' }}</div>
        </div>
    </div>

    {{-- Bottom divider --}}
    <div style="border-top:1.5px solid #E5E7EB;margin-top:14px;"></div>
</div>

{{-- ══ Header ══ --}}
<div id="rpt-header-gradient" style="background:linear-gradient(135deg,#4F46E5 0%,#7C3AED 100%);border-radius:16px;padding:22px 28px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:20px;font-weight:800;color:#fff;margin:0 0 4px;">My Reports</h1>
        <p style="font-size:12px;color:rgba(255,255,255,.7);margin:0;">
            Personal performance &amp; task analytics
            @if($from) &mdash; {{ $from->format('M d, Y') }} to {{ now()->format('M d, Y') }}
            @else &mdash; All time
            @endif
        </p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        {{-- Export dropdown --}}
        <div x-data="{ exportOpen: false }" class="no-print" style="position:relative;" @click.outside="exportOpen=false">
            <button @click="exportOpen=!exportOpen"
                    style="display:flex;align-items:center;gap:7px;padding:8px 16px;background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3);border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;backdrop-filter:blur(4px);transition:background .15s;"
                    onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                <i class="fas fa-file-export" style="font-size:11px;"></i>
                Export
                <i class="fas fa-chevron-down" style="font-size:9px;transition:transform .15s;"
                   :style="exportOpen ? 'transform:rotate(180deg)' : ''"></i>
            </button>
            <div x-show="exportOpen" x-transition
                 style="position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #E5E7EB;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.14);min-width:185px;z-index:200;overflow:hidden;">

                <button onclick="window.print()" @click="exportOpen=false"
                        style="display:flex;align-items:center;gap:9px;padding:10px 14px;font-size:13px;color:#374151;width:100%;border:none;background:transparent;cursor:pointer;text-align:left;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-print" style="font-size:12px;color:#6B7280;width:14px;text-align:center;"></i>
                    Print
                </button>

                <div style="height:1px;background:#F3F4F6;"></div>

                <a href="{{ route('user.reports.export') }}" @click="exportOpen=false"
                   style="display:flex;align-items:center;gap:9px;padding:10px 14px;font-size:13px;color:#374151;text-decoration:none;"
                   onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-file-csv" style="font-size:12px;color:#16A34A;width:14px;text-align:center;"></i>
                    Export as CSV
                </a>

                <button onclick="exportTasksPDF()" @click="exportOpen=false"
                        style="display:flex;align-items:center;gap:9px;padding:10px 14px;font-size:13px;color:#374151;width:100%;border:none;background:transparent;cursor:pointer;text-align:left;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-file-pdf" style="font-size:12px;color:#DC2626;width:14px;text-align:center;"></i>
                    Export as PDF
                </button>
            </div>
        </div>
        {{-- Range selector --}}
        <form method="GET" action="{{ route('user.reports.index') }}" class="no-print">
            <div style="display:flex;align-items:center;gap:3px;background:rgba(255,255,255,.15);border-radius:10px;padding:3px;">
                @foreach(['7'=>'7D','30'=>'30D','90'=>'90D','365'=>'1Y','all'=>'All'] as $val=>$label)
                <button type="submit" name="range" value="{{ $val }}"
                        style="padding:6px 14px;font-size:12px;font-weight:700;border:none;border-radius:8px;cursor:pointer;transition:all .15s;{{ $range===$val ? 'background:#fff;color:#4F46E5;' : 'background:transparent;color:rgba(255,255,255,.85);' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </form>
    </div>
</div>

<div x-data="{ tab: 'overview' }">

{{-- ══ Tab Bar ══ --}}
<div class="no-print" style="display:flex;gap:2px;background:#F3F4F6;border-radius:12px;padding:4px;margin-bottom:16px;width:fit-content;">
    <button @click="tab='overview'" :class="tab==='overview' ? 'rpt-tab-active' : 'rpt-tab-inactive'" class="rpt-tab">
        <i class="fas fa-chart-bar" style="font-size:11px;margin-right:5px;"></i> Overview
    </button>
    <button @click="tab='all-tasks'" :class="tab==='all-tasks' ? 'rpt-tab-active' : 'rpt-tab-inactive'" class="rpt-tab">
        <i class="fas fa-table-list" style="font-size:11px;margin-right:5px;"></i> All Tasks
        <span style="background:#EEF2FF;color:#4F46E5;font-size:10px;padding:1px 6px;border-radius:20px;margin-left:4px;font-weight:700;">{{ count($allTaskDetails) }}</span>
    </button>
</div>

<div x-show="tab === 'overview'">

{{-- ══ KPI Cards ══ --}}
<div class="rpt-grid-6">
    @foreach([
        ['Total Tasks',     $totalTasks,        'fa-list-check',           '#EEF2FF','#4F46E5'],
        ['Completed',       $completedTasks,     'fa-circle-check',         '#F0FDF4','#16A34A'],
        ['In Progress',     $inProgress,         'fa-spinner',              '#FFFBEB','#D97706'],
        ['In Review',       $inReview,           'fa-hourglass-half',       '#F5F3FF','#7C3AED'],
        ['Overdue',         $overdueTasks,       'fa-triangle-exclamation', $overdueTasks>0?'#FEF2F2':'#F8FAFC',$overdueTasks>0?'#DC2626':'#9CA3AF'],
        ['Completion Rate', $completionRate.'%', 'fa-chart-pie',            '#F0FDF4','#10B981'],
    ] as [$lbl,$val,$icon,$bg,$color])
    <div class="rpt-card" style="padding:18px;margin-bottom:0;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <span style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">{{ $lbl }}</span>
            <div style="width:32px;height:32px;border-radius:9px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;">
                <i class="fas {{ $icon }}" style="font-size:13px;color:{{ $color }};"></i>
            </div>
        </div>
        <p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">{{ $val }}</p>
        <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">
            @if($lbl === 'Completed' || $lbl === 'Completion Rate') {{ $onTimeRate }}% on time
            @else &nbsp;
            @endif
        </p>
    </div>
    @endforeach
</div>
<div style="margin-bottom:12px;"></div>

{{-- ══ Status Breakdown + Priority ══ --}}
<div class="rpt-grid-2" style="margin-bottom:0;">

    <div class="rpt-card">
        <p class="rpt-section-title"><i class="fas fa-chart-pie" style="color:#6366F1;"></i> Status Breakdown</p>
        @forelse($statusBreakdown as $key => $s)
        <div style="margin-bottom:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
                <div style="display:flex;align-items:center;gap:7px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:{{ $s['color'] }};flex-shrink:0;display:inline-block;"></span>
                    <span style="font-size:12px;font-weight:600;color:#374151;">{{ $s['label'] }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:11px;color:#6B7280;">{{ $s['count'] }}</span>
                    <span style="font-size:11px;font-weight:700;color:#374151;min-width:32px;text-align:right;">{{ $s['pct'] }}%</span>
                </div>
            </div>
            <div class="rpt-bar-track">
                <div class="rpt-bar-fill" style="width:{{ $s['pct'] }}%;background:{{ $s['color'] }};"></div>
            </div>
        </div>
        @empty
        <p style="font-size:12px;color:#9CA3AF;text-align:center;padding:20px 0;">No tasks found.</p>
        @endforelse
    </div>

    <div class="rpt-card">
        <p class="rpt-section-title"><i class="fas fa-flag" style="color:#F59E0B;"></i> Priority Breakdown</p>
        @forelse($priorityBreakdown as $key => $p)
        <div style="margin-bottom:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                <span style="font-size:12px;font-weight:600;color:#374151;">{{ $p['label'] }}</span>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:11px;color:#6B7280;">{{ $p['count'] }} tasks</span>
                    <span style="font-size:11px;font-weight:700;color:#374151;min-width:32px;text-align:right;">{{ $p['pct'] }}%</span>
                </div>
            </div>
            <div class="rpt-bar-track" style="height:8px;">
                <div class="rpt-bar-fill" style="width:{{ $p['pct'] }}%;background:{{ $p['color'] }};height:8px;"></div>
            </div>
        </div>
        @empty
        <p style="font-size:12px;color:#9CA3AF;text-align:center;padding:20px 0;">No tasks found.</p>
        @endforelse
        <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;">
            @foreach($priorityBreakdown as $p)
            <div style="flex:1;min-width:70px;background:{{ $p['bg'] }};border-radius:10px;padding:10px;text-align:center;">
                <p style="font-size:20px;font-weight:800;color:{{ $p['color'] }};margin:0;line-height:1;">{{ $p['count'] }}</p>
                <p style="font-size:10px;font-weight:600;color:{{ $p['color'] }};margin:3px 0 0;opacity:.8;">{{ $p['label'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ══ 6-Month Trend ══ --}}
<div class="rpt-card">
    <p class="rpt-section-title"><i class="fas fa-chart-line" style="color:#6366F1;"></i> 6-Month Activity Trend</p>
    <canvas id="trendChart" height="80"></canvas>
</div>

{{-- ══ Project Performance ══ --}}
@if($projects->count())
<div class="rpt-card">
    <p class="rpt-section-title"><i class="fas fa-diagram-project" style="color:#8B5CF6;"></i> My Project Performance</p>
    <div class="rpt-scroll">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Status</th>
                    <th>My Tasks</th>
                    <th>Done</th>
                    <th>In Progress</th>
                    <th>Overdue</th>
                    <th>Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($projects as $proj)
                <tr>
                    <td style="font-weight:600;color:#111827;">{{ $proj['name'] }}</td>
                    <td>
                        <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;{{ $proj['status']==='active' ? 'background:#D1FAE5;color:#059669;' : 'background:#F3F4F6;color:#6B7280;' }}">
                            {{ ucfirst($proj['status']) }}
                        </span>
                    </td>
                    <td style="font-weight:600;">{{ $proj['total'] }}</td>
                    <td style="color:#16A34A;font-weight:600;">{{ $proj['completed'] }}</td>
                    <td style="color:#D97706;font-weight:600;">{{ $proj['in_progress'] }}</td>
                    <td style="{{ $proj['overdue']>0 ? 'color:#DC2626;font-weight:700;' : 'color:#9CA3AF;' }}">{{ $proj['overdue'] }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;background:#F3F4F6;border-radius:4px;height:6px;overflow:hidden;min-width:50px;">
                                <div style="height:6px;border-radius:4px;width:{{ $proj['rate'] }}%;background:{{ $proj['rate']>=75?'#10B981':($proj['rate']>=40?'#F59E0B':'#EF4444') }};"></div>
                            </div>
                            <span style="font-size:11px;font-weight:700;color:#374151;min-width:30px;">{{ $proj['rate'] }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══ Overdue Tasks + Recent Activity ══ --}}
<div class="rpt-grid-2" style="margin-bottom:0;">

    <div class="rpt-card">
        <p class="rpt-section-title" style="color:#DC2626;">
            <i class="fas fa-triangle-exclamation" style="color:#EF4444;"></i>
            Overdue Tasks
            @if($overdueList->count())
            <span style="background:#FEE2E2;color:#DC2626;font-size:10px;padding:1px 7px;border-radius:20px;font-weight:700;">{{ $overdueList->count() }}</span>
            @endif
        </p>
        @forelse($overdueList as $t)
        <a href="{{ route('user.tasks.show', $t['id']) }}"
           style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px 0;border-bottom:1px solid #F3F4F6;text-decoration:none;"
           onmouseover="this.style.background='#FFF5F5'" onmouseout="this.style.background='transparent'">
            <div style="flex:1;min-width:0;">
                <p style="font-size:12px;font-weight:600;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $t['title'] }}</p>
                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $t['project'] }}</p>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <span class="chip-{{ $t['priority'] }}">{{ ucfirst($t['priority']) }}</span>
                <p style="font-size:10px;color:#DC2626;font-weight:700;margin:3px 0 0;">{{ $t['days_late'] }}d late</p>
            </div>
        </a>
        @empty
        <div style="text-align:center;padding:30px 0;">
            <i class="fas fa-circle-check" style="font-size:28px;color:#10B981;opacity:.5;margin-bottom:8px;display:block;"></i>
            <p style="font-size:12px;color:#9CA3AF;margin:0;">No overdue tasks!</p>
        </div>
        @endforelse
    </div>

    <div class="rpt-card">
        <p class="rpt-section-title"><i class="fas fa-clock-rotate-left" style="color:#6B7280;"></i> Recent Activity</p>
        <div class="rpt-scroll">
            @forelse($recentLogs as $log)
            @php
                $actionLabels = [
                    'task_viewed'                       => ['Viewed task',          '#6B7280','fa-eye'],
                    'status_updated_in_progress'        => ['Started work',         '#D97706','fa-play'],
                    'status_updated_submitted'          => ['Submitted task',       '#7C3AED','fa-paper-plane'],
                    'status_updated_approved'           => ['Task approved',        '#16A34A','fa-circle-check'],
                    'status_updated_delivered'          => ['Task delivered',       '#047857','fa-truck'],
                    'status_updated_revision_requested' => ['Revision requested',   '#EA580C','fa-rotate-left'],
                    'comment_added'                     => ['Added comment',        '#6366F1','fa-comment'],
                    'file_submitted'                    => ['Submitted files',      '#0369A1','fa-file-arrow-up'],
                    'task_reassigned'                   => ['Task reassigned',      '#9CA3AF','fa-arrows-rotate'],
                    'status_updated_reopened'           => ['Task reopened',        '#F59E0B','fa-rotate-right'],
                ];
                [$lbl2,$color2,$icon2] = $actionLabels[$log->action] ?? [ucfirst(str_replace('_',' ',$log->action)),'#9CA3AF','fa-circle'];
            @endphp
            <div style="display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid #F3F4F6;">
                <div style="width:28px;height:28px;border-radius:8px;background:#F9FAFB;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                    <i class="fas {{ $icon2 }}" style="font-size:11px;color:{{ $color2 }};"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:12px;font-weight:600;color:#374151;margin:0;">{{ $lbl2 }}</p>
                    @if($log->task)
                    <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $log->task->title }}</p>
                    @endif
                </div>
                <span style="font-size:10px;color:#9CA3AF;flex-shrink:0;white-space:nowrap;">{{ $log->created_at->diffForHumans() }}</span>
            </div>
            @empty
            <p style="font-size:12px;color:#9CA3AF;text-align:center;padding:20px 0;">No recent activity.</p>
            @endforelse
        </div>
    </div>

</div>

{{-- ══ Reassigned Tasks ══ --}}
@if($reassignedList->count())
<div class="rpt-card">
    <p class="rpt-section-title">
        <i class="fas fa-arrows-rotate" style="color:#6366F1;"></i>
        Reassigned Tasks
        <span style="background:#EEF2FF;color:#4F46E5;font-size:10px;padding:1px 7px;border-radius:20px;font-weight:700;">{{ $reassignedList->count() }}</span>
    </p>
    <div class="rpt-scroll">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Project</th>
                    <th>From</th>
                    <th>To</th>
                    <th>By</th>
                    <th>Reason</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reassignedList as $r)
                <tr>
                    <td>
                        <a href="{{ route('user.tasks.show', $r['task_id']) }}"
                           style="font-weight:600;color:#4F46E5;text-decoration:none;">
                            {{ $r['task'] }}
                        </a>
                    </td>
                    <td style="color:#6B7280;">{{ $r['project'] }}</td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;">
                            <span style="width:20px;height:20px;border-radius:50%;background:#FEF3C7;display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#D97706;flex-shrink:0;">
                                {{ strtoupper(substr($r['from_user'],0,1)) }}
                            </span>
                            {{ $r['from_user'] }}
                        </span>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;">
                            <span style="width:20px;height:20px;border-radius:50%;background:#D1FAE5;display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#059669;flex-shrink:0;">
                                {{ strtoupper(substr($r['to_user'],0,1)) }}
                            </span>
                            {{ $r['to_user'] }}
                        </span>
                    </td>
                    <td style="color:#6B7280;font-size:11px;">{{ $r['by'] }}</td>
                    <td style="color:#6B7280;font-size:11px;max-width:160px;">
                        @if($r['reason'])
                        <span style="display:inline-block;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $r['reason'] }}">{{ $r['reason'] }}</span>
                        @else
                        <span style="color:#D1D5DB;">—</span>
                        @endif
                    </td>
                    <td style="color:#6B7280;white-space:nowrap;">
                        {{ $r['date'] }}<br>
                        <span style="font-size:10px;color:#9CA3AF;">{{ $r['time'] }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══ Reopened Tasks ══ --}}
@if($reopenedList->count())
<div class="rpt-card">
    <p class="rpt-section-title">
        <i class="fas fa-rotate-right" style="color:#F59E0B;"></i>
        Reopened Tasks
        <span style="background:#FEF3C7;color:#D97706;font-size:10px;padding:1px 7px;border-radius:20px;font-weight:700;">{{ $reopenedList->count() }}</span>
    </p>
    <div class="rpt-scroll">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Project</th>
                    <th>Previous Status</th>
                    <th>Reopened By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reopenedList as $r)
                <tr>
                    <td>
                        <a href="{{ route('user.tasks.show', $r['task_id']) }}"
                           style="font-weight:600;color:#4F46E5;text-decoration:none;">
                            {{ $r['task'] }}
                        </a>
                    </td>
                    <td style="color:#6B7280;">{{ $r['project'] }}</td>
                    <td>
                        <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:#FEF3C7;color:#D97706;">
                            {{ $r['old_status'] }}
                        </span>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;">
                            <span style="width:20px;height:20px;border-radius:50%;background:#EEF2FF;display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#4F46E5;flex-shrink:0;">
                                {{ strtoupper(substr($r['by'],0,1)) }}
                            </span>
                            {{ $r['by'] }}
                        </span>
                    </td>
                    <td style="color:#6B7280;white-space:nowrap;">
                        {{ $r['date'] }}<br>
                        <span style="font-size:10px;color:#9CA3AF;">{{ $r['time'] }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($monthLabels),
            datasets: [
                {
                    label: 'Tasks Assigned',
                    data: @json($monthlyCreated),
                    backgroundColor: 'rgba(99,102,241,.18)',
                    borderColor: '#6366F1',
                    borderWidth: 2,
                    borderRadius: 6,
                    order: 2,
                },
                {
                    label: 'Completed',
                    data: @json($monthlyCompleted),
                    type: 'line',
                    backgroundColor: 'rgba(16,185,129,.1)',
                    borderColor: '#10B981',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#10B981',
                    pointRadius: 4,
                    fill: true,
                    tension: 0.4,
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { font: { size: 12 }, usePointStyle: true } },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 11 }, precision: 0 } },
            },
        },
    });
})();
</script>

</div>{{-- /overview tab --}}

{{-- ══ All Tasks Tab ══ --}}
<div x-show="tab === 'all-tasks'" style="display:none;">

    {{-- Top bar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap;">
        <div style="position:relative;flex:1;min-width:180px;max-width:320px;">
            <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;pointer-events:none;"></i>
            <input type="text" id="atSearch" placeholder="Search tasks or projects…"
                   oninput="filterAllTasks(this.value)"
                   style="width:100%;padding:8px 10px 8px 30px;border:1px solid #E5E7EB;border-radius:9px;font-size:13px;color:#374151;outline:none;background:#F9FAFB;box-sizing:border-box;">
        </div>
    </div>

    {{-- Table --}}
    <div class="rpt-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="rpt-table" id="allTasksTable">
                <thead>
                    <tr>
                        <th style="min-width:180px;">Task</th>
                        <th style="min-width:110px;">Project</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th style="min-width:90px;">Started</th>
                        <th style="min-width:90px;">Completed</th>
                        <th style="min-width:80px;text-align:center;">Days to Submit</th>
                        <th style="min-width:90px;">Deadline</th>
                        <th style="min-width:90px;">Result</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allTaskDetails as $t)
                    @php
                        $pc  = ['high'=>['#FEE2E2','#DC2626'],'medium'=>['#FEF3C7','#D97706'],'low'=>['#D1FAE5','#059669']];
                        [$pbg,$pfg] = $pc[$t['priority']] ?? ['#F3F4F6','#6B7280'];
                        $sc  = ['draft'=>['#F3F4F6','#6B7280'],'assigned'=>['#EEF2FF','#4F46E5'],'viewed'=>['#E0F2FE','#0369A1'],'in_progress'=>['#FEF3C7','#D97706'],'submitted'=>['#EDE9FE','#7C3AED'],'revision_requested'=>['#FEE2E2','#DC2626'],'approved'=>['#D1FAE5','#059669'],'delivered'=>['#ECFDF5','#047857'],'archived'=>['#F3F4F6','#6B7280']];
                        $sl  = ['draft'=>'Draft','assigned'=>'Assigned','viewed'=>'Viewed','in_progress'=>'In Progress','submitted'=>'In Review','revision_requested'=>'Revision','approved'=>'Approved','delivered'=>'Delivered','archived'=>'Archived'];
                        [$sbg,$sfg] = $sc[$t['status']] ?? ['#F3F4F6','#6B7280'];
                    @endphp
                    <tr data-search="{{ strtolower($t['title'].' '.$t['project']) }}">
                        <td>
                            <a href="{{ route('user.tasks.show', $t['id']) }}"
                               style="font-weight:600;color:#4F46E5;text-decoration:none;display:block;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                               title="{{ $t['title'] }}">{{ $t['title'] }}</a>
                        </td>
                        <td style="color:#6B7280;font-size:11px;">{{ $t['project'] }}</td>
                        <td><span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:{{ $pbg }};color:{{ $pfg }};">{{ ucfirst($t['priority']) }}</span></td>
                        <td><span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:{{ $sbg }};color:{{ $sfg }};white-space:nowrap;">{{ $sl[$t['status']] ?? ucfirst($t['status']) }}</span></td>
                        <td style="font-size:11px;color:#6B7280;white-space:nowrap;">{{ $t['started_at'] ?? '—' }}</td>
                        <td style="font-size:11px;color:#6B7280;white-space:nowrap;">{{ $t['completed_at'] ?? '—' }}</td>
                        <td style="text-align:center;">
                            @if($t['days_to_submit'] !== null)
                                <span style="font-size:12px;font-weight:700;color:#374151;">{{ $t['days_to_submit'] }}<span style="font-size:10px;font-weight:400;color:#9CA3AF;">d</span></span>
                            @else
                                <span style="color:#D1D5DB;">—</span>
                            @endif
                        </td>
                        <td style="font-size:11px;white-space:nowrap;{{ $t['is_overdue'] ? 'color:#DC2626;font-weight:700;' : 'color:#6B7280;' }}">
                            {{ $t['deadline'] ?? '—' }}
                        </td>
                        <td>
                            @if($t['is_overdue'])
                                <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:#FEE2E2;color:#DC2626;white-space:nowrap;">Overdue</span>
                            @elseif($t['is_late'])
                                <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:#FEE2E2;color:#DC2626;white-space:nowrap;">Late {{ $t['days_late'] }}d</span>
                            @elseif($t['is_done'])
                                <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:#D1FAE5;color:#059669;white-space:nowrap;">On Time{{ $t['days_early'] ? ' +'.($t['days_early']).'d' : '' }}</span>
                            @else
                                <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:#FEF3C7;color:#D97706;white-space:nowrap;">In Progress</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:48px;color:#9CA3AF;">
                            <i class="fas fa-inbox" style="font-size:28px;opacity:.35;display:block;margin-bottom:10px;"></i>
                            No tasks found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @php
        $pdfLogoB64  = null;
        $pdfLogoType = null;
        $logoPath    = $appSettings['logo_path'] ?? '';
        if (!empty($logoPath)) {
            $fullPath = storage_path('app/public/' . $logoPath);
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if (file_exists($fullPath) && in_array($ext, ['png','jpg','jpeg','webp'])) {
                $pdfLogoB64  = base64_encode(file_get_contents($fullPath));
                $pdfLogoType = in_array($ext, ['jpg','jpeg']) ? 'JPEG' : strtoupper($ext);
            }
        }
    @endphp

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.6.0/jspdf.plugin.autotable.min.js"></script>
    <script>
    function filterAllTasks(q) {
        q = q.toLowerCase();
        document.querySelectorAll('#allTasksTable tbody tr[data-search]').forEach(function(row) {
            row.style.display = row.dataset.search.includes(q) ? '' : 'none';
        });
    }

    var _allTaskDetails = @json($allTaskDetails);
    var _logoB64   = @json($pdfLogoB64);
    var _logoType  = @json($pdfLogoType);
    var _company   = @json($appSettings['company_name'] ?? '');
    var _appName   = @json($appSettings['app_name'] ?? 'Task Manager');

    function exportTasksPDF() {
        var jsPDF = window.jspdf.jsPDF;
        var doc   = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        var pw    = doc.internal.pageSize.getWidth();  // 210
        var ml    = 14, mr = 14;

        // ── Header ───────────────────────────────────────────────────
        var y = 12;
        if (_logoB64) {
            doc.addImage('data:image/' + _logoType.toLowerCase() + ';base64,' + _logoB64,
                         _logoType, ml, y, 16, 16);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(13);
            doc.setTextColor(17, 24, 39);
            doc.text(_company || _appName, ml + 20, y + 6);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8);
            doc.setTextColor(120);
            doc.text(_appName, ml + 20, y + 12);
            y += 21;
        } else {
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(13);
            doc.setTextColor(17, 24, 39);
            doc.text(_company || _appName, ml, y + 5);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8);
            doc.setTextColor(120);
            doc.text(_appName, ml, y + 11);
            y += 15;
        }

        // Divider
        doc.setDrawColor(209, 213, 219);
        doc.line(ml, y, pw - mr, y);
        y += 5;

        // Report title + meta
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(11);
        doc.setTextColor(17, 24, 39);
        doc.text('My Task Report', ml, y + 5);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(107, 114, 128);
        doc.text('{{ auth()->user()->name }}  ·  Generated {{ now()->format("M d, Y") }}', ml, y + 11);
        doc.setTextColor(0);
        y += 17;

        // ── Table ────────────────────────────────────────────────────
        var statusLabels = {
            draft: 'Draft', assigned: 'Assigned', viewed: 'Viewed',
            in_progress: 'In Progress', submitted: 'In Review',
            revision_requested: 'Revision', approved: 'Approved',
            delivered: 'Delivered', archived: 'Archived'
        };

        var rows = _allTaskDetails.map(function(t) {
            var result = 'In Progress';
            if (t.is_overdue)   result = 'Overdue';
            else if (t.is_late) result = 'Late ' + t.days_late + 'd';
            else if (t.is_done) result = t.days_early ? 'On Time +' + t.days_early + 'd' : 'On Time';

            return [
                t.title,
                t.project,
                t.priority ? t.priority.charAt(0).toUpperCase() + t.priority.slice(1) : '-',
                statusLabels[t.status] || t.status,
                t.started_at   || '-',
                t.completed_at || '-',
                t.days_to_submit !== null ? t.days_to_submit + 'd' : '-',
                t.deadline || '-',
                result,
            ];
        });

        doc.autoTable({
            startY: y,
            margin: { left: ml, right: mr },
            head: [['Task', 'Project', 'Priority', 'Status', 'Started', 'Completed', 'Days\nSubmit', 'Deadline', 'Result']],
            body: rows,
            styles: { fontSize: 7, cellPadding: 2, overflow: 'linebreak' },
            headStyles: { fillColor: [79, 70, 229], textColor: 255, fontStyle: 'bold', fontSize: 7.5, halign: 'left' },
            alternateRowStyles: { fillColor: [249, 250, 251] },
            columnStyles: {
                0: { cellWidth: 42 },
                1: { cellWidth: 24 },
                2: { cellWidth: 13, halign: 'center' },
                3: { cellWidth: 18 },
                4: { cellWidth: 16 },
                5: { cellWidth: 16 },
                6: { cellWidth: 12, halign: 'center' },
                7: { cellWidth: 16 },
                8: { cellWidth: 18 },
            },
            didParseCell: function(data) {
                if (data.section !== 'body' || data.column.index !== 8) return;
                var v = data.cell.raw;
                if (v === 'Overdue' || (typeof v === 'string' && v.indexOf('Late') === 0)) {
                    data.cell.styles.textColor = [220, 38, 38];
                    data.cell.styles.fontStyle = 'bold';
                } else if (typeof v === 'string' && v.indexOf('On Time') === 0) {
                    data.cell.styles.textColor = [5, 150, 105];
                    data.cell.styles.fontStyle = 'bold';
                }
            },
        });

        // ── Footer: page numbers ──────────────────────────────────────
        var pages = doc.internal.getNumberOfPages();
        for (var i = 1; i <= pages; i++) {
            doc.setPage(i);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7);
            doc.setTextColor(156, 163, 175);
            doc.text('Page ' + i + ' of ' + pages, pw / 2, doc.internal.pageSize.getHeight() - 8, { align: 'center' });
        }

        doc.save('my-tasks-' + new Date().toISOString().slice(0, 10) + '.pdf');
    }
    </script>

</div>{{-- /all-tasks tab --}}

</div>{{-- /x-data tab wrapper --}}

@endsection

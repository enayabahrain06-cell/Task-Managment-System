<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; background: #fff; }

.page { padding: 36px 40px; }

/* Header */
.header { border-bottom: 3px solid #4F46E5; padding-bottom: 18px; margin-bottom: 28px; }
.header-top { display: table; width: 100%; }
.header-left { display: table-cell; vertical-align: middle; }
.header-right { display: table-cell; text-align: right; vertical-align: middle; }
.app-name { font-size: 20px; font-weight: bold; color: #4F46E5; }
.report-title { font-size: 13px; color: #6B7280; margin-top: 3px; }
.meta { font-size: 10px; color: #9CA3AF; margin-top: 2px; }

/* KPI row */
.kpi-row { display: table; width: 100%; margin-bottom: 28px; border-spacing: 8px 0; }
.kpi-cell { display: table-cell; width: 20%; }
.kpi-box { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 14px 12px; text-align: center; }
.kpi-box.indigo { border-top: 3px solid #4F46E5; }
.kpi-box.green  { border-top: 3px solid #10B981; }
.kpi-box.orange { border-top: 3px solid #F59E0B; }
.kpi-box.blue   { border-top: 3px solid #3B82F6; }
.kpi-box.purple { border-top: 3px solid #8B5CF6; }
.kpi-val  { font-size: 26px; font-weight: bold; color: #111827; line-height: 1.1; }
.kpi-lbl  { font-size: 9px; color: #6B7280; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

/* Section */
.section { margin-bottom: 24px; }
.section-title { font-size: 11px; font-weight: bold; color: #374151; text-transform: uppercase; letter-spacing: 0.8px; border-bottom: 1px solid #E5E7EB; padding-bottom: 6px; margin-bottom: 12px; }

/* Table */
table { width: 100%; border-collapse: collapse; font-size: 11px; }
th { background: #F3F4F6; padding: 8px 10px; text-align: left; font-size: 10px; font-weight: bold; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #E5E7EB; }
td { padding: 8px 10px; border-bottom: 1px solid #F3F4F6; color: #374151; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:nth-child(even) td { background: #FAFAFA; }

/* Badge */
.badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: bold; text-transform: capitalize; }
.badge-delivered   { background: #D1FAE5; color: #065F46; }
.badge-approved    { background: #E0E7FF; color: #3730A3; }
.badge-submitted   { background: #FEF3C7; color: #92400E; }
.badge-in_progress { background: #DBEAFE; color: #1E40AF; }
.badge-revision    { background: #FEE2E2; color: #991B1B; }
.badge-draft       { background: #F3F4F6; color: #6B7280; }
.badge-other       { background: #F3F4F6; color: #6B7280; }

/* Progress bar */
.bar-wrap { background: #E5E7EB; border-radius: 4px; height: 7px; width: 100%; }
.bar-fill { background: #4F46E5; border-radius: 4px; height: 7px; }
.bar-fill.green  { background: #10B981; }

/* Two-col layout */
.two-col { display: table; width: 100%; border-spacing: 16px 0; }
.col-left  { display: table-cell; width: 48%; vertical-align: top; }
.col-right { display: table-cell; width: 48%; vertical-align: top; }

/* Footer */
.footer { border-top: 1px solid #E5E7EB; padding-top: 12px; margin-top: 32px; display: table; width: 100%; }
.footer-left  { display: table-cell; font-size: 9px; color: #9CA3AF; }
.footer-right { display: table-cell; text-align: right; font-size: 9px; color: #9CA3AF; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-top">
            <div class="header-left">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" style="height:32px;max-width:140px;object-fit:contain;display:block;margin-bottom:5px;">
                @else
                    <div class="app-name">{{ $appName }}</div>
                @endif
                <div class="report-title">System Summary Report</div>
                <div class="meta">Generated: {{ $generatedAt }}</div>
            </div>
            <div class="header-right">
                <div style="font-size:11px;color:#6B7280;">Backup Snapshot</div>
                <div style="font-size:10px;color:#9CA3AF;margin-top:2px;">{{ $generatedAt }}</div>
            </div>
        </div>
    </div>

    {{-- KPI Row --}}
    <div class="kpi-row">
        <div class="kpi-cell"><div class="kpi-box indigo">
            <div class="kpi-val">{{ $totalTasks }}</div>
            <div class="kpi-lbl">Total Tasks</div>
        </div></div>
        <div class="kpi-cell"><div class="kpi-box green">
            <div class="kpi-val">{{ $completedTasks }}</div>
            <div class="kpi-lbl">Completed</div>
        </div></div>
        <div class="kpi-cell"><div class="kpi-box orange">
            <div class="kpi-val">{{ $pendingTasks }}</div>
            <div class="kpi-lbl">In Progress</div>
        </div></div>
        <div class="kpi-cell"><div class="kpi-box blue">
            <div class="kpi-val">{{ $totalUsers }}</div>
            <div class="kpi-lbl">Active Users</div>
        </div></div>
        <div class="kpi-cell"><div class="kpi-box purple">
            <div class="kpi-val">{{ $totalProjects }}</div>
            <div class="kpi-lbl">Projects</div>
        </div></div>
    </div>

    {{-- Completion rate --}}
    <div class="section">
        <div class="section-title">Overall Completion Rate</div>
        <div style="display:table;width:100%;margin-bottom:6px;">
            <div style="display:table-cell;font-size:22px;font-weight:bold;color:#4F46E5;width:60px;">{{ $completionRate }}%</div>
            <div style="display:table-cell;vertical-align:middle;padding-left:12px;">
                <div class="bar-wrap"><div class="bar-fill" style="width:{{ $completionRate }}%;"></div></div>
                <div style="font-size:9px;color:#9CA3AF;margin-top:3px;">{{ $completedTasks }} of {{ $totalTasks }} tasks completed</div>
            </div>
        </div>
    </div>

    <div class="two-col">
        {{-- Task status breakdown --}}
        <div class="col-left">
            <div class="section">
                <div class="section-title">Tasks by Status</div>
                <table>
                    <thead>
                        <tr><th>Status</th><th>Count</th><th>Share</th></tr>
                    </thead>
                    <tbody>
                        @foreach($tasksByStatus as $row)
                        <tr>
                            <td><span class="badge badge-{{ str_replace('_','-',$row->status) ?? 'other' }}">{{ ucfirst(str_replace('_',' ',$row->status)) }}</span></td>
                            <td><strong>{{ $row->cnt }}</strong></td>
                            <td>
                                @if($totalTasks > 0)
                                <div class="bar-wrap" style="width:80px;">
                                    <div class="bar-fill {{ in_array($row->status,['approved','delivered']) ? 'green' : '' }}"
                                         style="width:{{ round($row->cnt/$totalTasks*100) }}%;"></div>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Projects --}}
        <div class="col-right">
            <div class="section">
                <div class="section-title">Projects Overview</div>
                <table>
                    <thead>
                        <tr><th>Project</th><th>Tasks</th><th>Done</th></tr>
                    </thead>
                    <tbody>
                        @foreach($projects as $proj)
                        <tr>
                            <td style="max-width:120px;overflow:hidden;">{{ $proj->name }}</td>
                            <td>{{ $proj->task_count }}</td>
                            <td>
                                @if($proj->task_count > 0)
                                    {{ round($proj->done_count / $proj->task_count * 100) }}%
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">{{ $appName }} — Backup Report · Confidential</div>
        <div class="footer-right">Generated on {{ $generatedAt }}</div>
    </div>

</div>
</body>
</html>

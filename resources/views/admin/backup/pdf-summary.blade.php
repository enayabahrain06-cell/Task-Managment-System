<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; background: #fff; }
.page { padding: 32px 36px; }

.app-name  { font-size: 20px; font-weight: bold; color: #4F46E5; }
.sub-title { font-size: 12px; color: #6B7280; margin-top: 2px; }
.meta-txt  { font-size: 10px; color: #9CA3AF; margin-top: 2px; }

.kpi-box { border: 1px solid #E5E7EB; border-radius: 6px; padding: 12px 8px; text-align: center; background: #FAFAFA; }
.kpi-val { font-size: 26px; font-weight: bold; color: #111827; }
.kpi-lbl { font-size: 9px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }

.section-title { font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase;
    letter-spacing: 0.8px; border-bottom: 1px solid #E5E7EB; padding-bottom: 5px; margin-bottom: 10px; }

table.data { width: 100%; border-collapse: collapse; font-size: 11px; }
table.data th { background: #F3F4F6; padding: 7px 8px; text-align: left; font-size: 9px;
    font-weight: bold; color: #6B7280; text-transform: uppercase; border-bottom: 2px solid #E5E7EB; }
table.data td { padding: 7px 8px; border-bottom: 1px solid #F3F4F6; color: #374151; }
table.data tr:last-child td { border-bottom: none; }
table.data tr:nth-child(even) td { background: #FAFAFA; }

.badge { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: 9px; font-weight: bold; }
.badge-delivered { background: #D1FAE5; color: #065F46; }
.badge-approved  { background: #E0E7FF; color: #3730A3; }
.badge-submitted { background: #FEF3C7; color: #92400E; }
.badge-in-progress { background: #DBEAFE; color: #1E40AF; }
.badge-other     { background: #F3F4F6; color: #6B7280; }

.bar-bg   { background: #E5E7EB; border-radius: 4px; height: 7px; }
.bar-fill { background: #4F46E5; border-radius: 4px; height: 7px; }
.bar-green { background: #10B981; }

.footer-txt { font-size: 9px; color: #9CA3AF; }
</style>
</head>
<body>
<div class="page">

{{-- Header --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:3px solid #4F46E5;padding-bottom:14px;margin-bottom:22px;">
<tr>
    <td>
        @if(!empty($logoBase64))
            <img src="{{ $logoBase64 }}" style="height:52px;max-width:200px;"><br>
        @else
            <div class="app-name">{{ $appName }}</div>
        @endif
        <div class="sub-title">System Summary Report</div>
        <div class="meta-txt">Generated: {{ $generatedAt }}</div>
    </td>
    <td align="right" valign="middle">
        <div style="font-size:11px;color:#6B7280;">Backup Snapshot</div>
        <div style="font-size:10px;color:#9CA3AF;margin-top:2px;">{{ $generatedAt }}</div>
    </td>
</tr>
</table>

{{-- KPI boxes --}}
<table width="100%" cellpadding="0" cellspacing="6" style="margin-bottom:22px;">
<tr>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #4F46E5;">
        <div class="kpi-val">{{ $totalTasks }}</div><div class="kpi-lbl">Total Tasks</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #10B981;">
        <div class="kpi-val">{{ $completedTasks }}</div><div class="kpi-lbl">Completed</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #F59E0B;">
        <div class="kpi-val">{{ $pendingTasks }}</div><div class="kpi-lbl">In Progress</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #3B82F6;">
        <div class="kpi-val">{{ $totalUsers }}</div><div class="kpi-lbl">Active Users</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #8B5CF6;">
        <div class="kpi-val">{{ $totalProjects }}</div><div class="kpi-lbl">Projects</div>
    </div></td>
</tr>
</table>

{{-- Completion rate --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px;">
<tr>
    <td width="60" valign="middle" style="font-size:22px;font-weight:bold;color:#4F46E5;">{{ $completionRate }}%</td>
    <td valign="middle" style="padding-left:12px;">
        <div class="bar-bg"><div class="bar-fill" style="width:{{ $completionRate }}%;"></div></div>
        <div style="font-size:9px;color:#9CA3AF;margin-top:3px;">{{ $completedTasks }} of {{ $totalTasks }} tasks completed</div>
    </td>
</tr>
</table>

{{-- Two-col: status + projects --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:0;">
<tr>
    <td width="48%" valign="top" style="padding-right:16px;">
        <div class="section-title">Tasks by Status</div>
        <table class="data" width="100%">
            <thead><tr><th>Status</th><th>Count</th><th>Share</th></tr></thead>
            <tbody>
            @foreach($tasksByStatus as $row)
            <tr>
                <td><span class="badge badge-{{ str_replace('_','-',$row->status) }}">{{ ucfirst(str_replace('_',' ',$row->status)) }}</span></td>
                <td><strong>{{ $row->cnt }}</strong></td>
                <td>
                    @if($totalTasks > 0)
                    <div class="bar-bg" style="width:80px;">
                        <div class="{{ in_array($row->status,['approved','delivered']) ? 'bar-fill bar-green' : 'bar-fill' }}"
                             style="width:{{ round($row->cnt/$totalTasks*100) }}%;"></div>
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </td>
    <td width="4%"></td>
    <td width="48%" valign="top">
        <div class="section-title">Projects Overview</div>
        <table class="data" width="100%">
            <thead><tr><th>Project</th><th>Tasks</th><th>Done</th></tr></thead>
            <tbody>
            @foreach($projects as $proj)
            <tr>
                <td>{{ $proj->name }}</td>
                <td>{{ $proj->task_count }}</td>
                <td>{{ $proj->task_count > 0 ? round($proj->done_count/$proj->task_count*100).'%' : '—' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </td>
</tr>
</table>

{{-- Footer --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E5E7EB;padding-top:10px;margin-top:28px;">
<tr>
    <td class="footer-txt">{{ $appName }} — Backup Report · Confidential</td>
    <td class="footer-txt" align="right">Generated on {{ $generatedAt }}</td>
</tr>
</table>

</div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
.page { padding: 28px 32px; }

.app-name  { font-size: 14px; font-weight: bold; color: #4F46E5; }
.sub-title { font-size: 11px; color: #6B7280; margin-top: 2px; }
.meta-txt  { font-size: 9px; color: #9CA3AF; margin-top: 2px; }

.customer-card { background: #F0F4FF; border: 1px solid #C7D2FE; border-radius: 8px;
             padding: 12px 14px; margin-bottom: 18px; }
.customer-name { font-size: 16px; font-weight: bold; color: #111827; }
.customer-meta { font-size: 10px; color: #6B7280; margin-top: 3px; }

.kpi-box { border: 1px solid #E5E7EB; border-radius: 6px; padding: 10px 8px;
           text-align: center; background: #FAFAFA; }
.kpi-val { font-size: 22px; font-weight: bold; color: #111827; }
.kpi-lbl { font-size: 8px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; }

.bar-bg   { background: #E5E7EB; border-radius: 4px; height: 8px; }
.bar-fill { border-radius: 4px; height: 8px; }

.section-title { font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase;
    letter-spacing: 0.8px; border-bottom: 1px solid #E5E7EB; padding-bottom: 5px;
    margin-top: 18px; margin-bottom: 10px; }

table.data { width: 100%; border-collapse: collapse; font-size: 10px; }
table.data th { background: #F3F4F6; padding: 7px 8px; text-align: left; font-size: 9px;
    font-weight: bold; color: #6B7280; text-transform: uppercase; border-bottom: 2px solid #E5E7EB; }
table.data td { padding: 7px 8px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
table.data tr:last-child td { border-bottom: none; }
table.data tr:nth-child(even) td { background: #FAFAFA; }

.st { display: inline-block; padding: 2px 6px; border-radius: 20px; font-size: 8px; font-weight: bold; white-space: nowrap; }
.st-delivered          { background: #D1FAE5; color: #065F46; }
.st-archived           { background: #D1FAE5; color: #065F46; }
.st-approved           { background: #E0E7FF; color: #3730A3; }
.st-submitted          { background: #FEF3C7; color: #92400E; }
.st-in_progress        { background: #DBEAFE; color: #1E40AF; }
.st-revision_requested { background: #FEE2E2; color: #991B1B; }
.st-pending_customer   { background: #FEF3C7; color: #92400E; }
.st-assigned           { background: #F3F4F6; color: #374151; }
.st-viewed             { background: #F3F4F6; color: #374151; }
.st-draft              { background: #F3F4F6; color: #9CA3AF; }
.st-paused             { background: #FEF3C7; color: #92400E; }

.footer-txt { font-size: 8px; color: #9CA3AF; }
</style>
</head>
<body>
<div class="page">

{{-- Header --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:3px solid #4F46E5;padding-bottom:12px;margin-bottom:16px;">
<tr>
    <td>
        @if(!empty($logoBase64))
        <table cellpadding="0" cellspacing="0">
        <tr>
            <td valign="middle"><img src="{{ $logoBase64 }}" style="height:44px;max-width:160px;display:block;"></td>
            <td valign="middle" style="padding-left:10px;">
                <div style="font-size:15px;font-weight:bold;color:#111827;line-height:1.2;">{{ $companyName }}</div>
                <div style="font-size:9px;color:#9CA3AF;margin-top:2px;">{{ $appName }}</div>
            </td>
        </tr>
        </table>
        @else
            <div class="app-name">{{ $companyName }}</div>
        @endif
        <div class="sub-title" style="margin-top:6px;">Customer Report</div>
        <div class="meta-txt">Generated: {{ $generatedAt }}</div>
    </td>
    <td></td>
</tr>
</table>

{{-- Customer card --}}
<div class="customer-card">
    <div class="customer-name">{{ $customer->name }}</div>
    <div class="customer-meta">
        @if($customer->company) {{ $customer->company }} &middot; @endif
        @if($customer->email) {{ $customer->email }} @endif
    </div>
</div>

{{-- KPI boxes --}}
<table width="100%" cellpadding="0" cellspacing="6" style="margin-bottom:14px;">
<tr>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #4F46E5;">
        <div class="kpi-val">{{ $total }}</div><div class="kpi-lbl">Total Tasks</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #10B981;">
        <div class="kpi-val">{{ $completed }}</div><div class="kpi-lbl">Completed</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #F59E0B;">
        <div class="kpi-val">{{ $active }}</div><div class="kpi-lbl">Active</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #EF4444;">
        <div class="kpi-val">{{ $overdue }}</div><div class="kpi-lbl">Overdue</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #3B82F6;">
        <div class="kpi-val">{{ $completionRate }}%</div><div class="kpi-lbl">Completion Rate</div>
    </div></td>
</tr>
</table>

{{-- Progress bar --}}
@if($total > 0)
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:4px;">
<tr>
    <td style="font-size:9px;color:#6B7280;">Completion progress</td>
    <td align="right" style="font-size:9px;color:#4F46E5;font-weight:bold;">{{ $completionRate }}%</td>
</tr>
</table>
<div class="bar-bg" style="margin-bottom:16px;">
    <div class="bar-fill" style="width:{{ $completionRate }}%;background:{{ $completionRate >= 80 ? '#10B981' : ($completionRate >= 50 ? '#F59E0B' : '#EF4444') }};"></div>
</div>
@endif

{{-- Revisions --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:6px;">
<tr>
    <td style="font-size:9px;color:#6B7280;">Revision requests: {{ $totalRevisions }} ({{ $revisionRate }}% of tasks)</td>
</tr>
</table>

{{-- Monthly activity --}}
<div class="section-title">Tasks Created by Month</div>
@if($monthlyCreated->isEmpty())
    <p style="text-align:center;padding:14px;color:#9CA3AF;">No task activity yet.</p>
@else
<table class="data" width="100%">
    <thead><tr><th>Month</th><th>Tasks Created</th></tr></thead>
    <tbody>
    @foreach($monthlyCreated as $month => $count)
    <tr>
        <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</td>
        <td>{{ $count }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

{{-- Team workload --}}
<div class="section-title">Team Workload</div>
@if($workload->isEmpty())
    <p style="text-align:center;padding:14px;color:#9CA3AF;">No assignees yet.</p>
@else
<table class="data" width="100%">
    <thead><tr><th>Team Member</th><th>Total Tasks</th><th>Delivered</th></tr></thead>
    <tbody>
    @foreach($workload as $w)
    <tr>
        <td>{{ $w['name'] }}</td>
        <td>{{ $w['total'] }}</td>
        <td>{{ $w['delivered'] }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

{{-- Task list --}}
<div class="section-title">Task List ({{ $allTasks->count() }} tasks)</div>
@if($allTasks->isEmpty())
    <p style="text-align:center;padding:14px;color:#9CA3AF;">No tasks yet.</p>
@else
<table class="data" width="100%">
    <thead>
    <tr>
        <th width="24">#</th>
        <th>Task Title</th>
        <th>Project</th>
        <th>Assigned To</th>
        <th>Status</th>
        <th>Deadline</th>
        <th>Created</th>
    </tr>
    </thead>
    <tbody>
    @foreach($allTasks as $i => $task)
    @php
        $isOverdue = $task->deadline
            && \Carbon\Carbon::parse($task->deadline)->isPast()
            && !in_array($task->status, ['approved','delivered','archived']);
    @endphp
    <tr>
        <td style="color:#9CA3AF;">{{ $i + 1 }}</td>
        <td>{{ $task->title }}</td>
        <td style="color:#6B7280;">{{ ($task->project && !$task->project->is_quick) ? $task->project->name : '—' }}</td>
        <td style="color:#6B7280;">{{ $task->assignee?->name ?? 'Unassigned' }}</td>
        <td><span class="st st-{{ $task->status }}">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span></td>
        <td style="color:{{ $isOverdue ? '#DC2626' : '#374151' }};white-space:nowrap;">
            {{ $task->deadline ? \Carbon\Carbon::parse($task->deadline)->format('d M Y') : '—' }}
        </td>
        <td style="color:#9CA3AF;white-space:nowrap;">{{ $task->created_at->format('d M Y') }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

@include('admin.reports.partials.pdf-summary-block')

{{-- Footer --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E5E7EB;padding-top:10px;margin-top:20px;">
<tr>
    <td class="footer-txt">{{ $appName }} — {{ $customer->name }} · Customer Report</td>
    <td class="footer-txt" align="right">Generated on {{ $generatedAt }}</td>
</tr>
</table>

</div>
</body>
</html>

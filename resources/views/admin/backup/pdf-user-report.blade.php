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

.user-card { background: #F0F4FF; border: 1px solid #C7D2FE; border-radius: 8px;
             padding: 12px 14px; margin-bottom: 18px; }
.user-name { font-size: 16px; font-weight: bold; color: #111827; }
.user-meta { font-size: 10px; color: #6B7280; margin-top: 3px; }

.badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: bold; }
.badge-admin   { background: #FEE2E2; color: #991B1B; }
.badge-manager { background: #E0E7FF; color: #3730A3; }
.badge-user    { background: #D1FAE5; color: #065F46; }
.badge-active  { background: #D1FAE5; color: #065F46; }
.badge-held    { background: #FEF3C7; color: #92400E; }

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
.st-approved           { background: #E0E7FF; color: #3730A3; }
.st-submitted          { background: #FEF3C7; color: #92400E; }
.st-in_progress        { background: #DBEAFE; color: #1E40AF; }
.st-revision_requested { background: #FEE2E2; color: #991B1B; }
.st-assigned           { background: #F3F4F6; color: #374151; }
.st-viewed             { background: #F3F4F6; color: #374151; }
.st-draft              { background: #F3F4F6; color: #9CA3AF; }
.st-paused             { background: #FEF3C7; color: #92400E; }

.dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 4px; }
.dot-high   { background: #EF4444; }
.dot-medium { background: #F59E0B; }
.dot-low    { background: #10B981; }
.dot-urgent { background: #7C3AED; }

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
            <img src="{{ $logoBase64 }}" style="height:28px;max-width:120px;"><br>
        @else
            <div class="app-name">{{ $appName }}</div>
        @endif
        <div class="sub-title">Individual User Report</div>
        <div class="meta-txt">Backup snapshot · {{ $generatedAt }}</div>
    </td>
    <td align="right" valign="middle" style="font-size:10px;color:#6B7280;">Confidential</td>
</tr>
</table>

{{-- User card --}}
<div class="user-card">
    <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="52" valign="middle">
            @if(!empty($avatarBase64))
                <img src="{{ $avatarBase64 }}" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #C7D2FE;">
            @else
                <div style="width:44px;height:44px;border-radius:50%;background:#4F46E5;text-align:center;padding-top:10px;">
                    <span style="color:#fff;font-size:18px;font-weight:bold;line-height:1;">{{ strtoupper(substr($user->name,0,1)) }}</span>
                </div>
            @endif
        </td>
        <td valign="middle" style="padding-left:12px;">
            <div class="user-name">{{ $user->name }}</div>
            <div class="user-meta">{{ $user->email }}
                @if($user->job_title) &middot; {{ $user->job_title }} @endif
                @if($user->nationality) &middot; {{ $user->nationality }} @endif
            </div>
            <div style="margin-top:5px;">
                <span class="badge badge-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
                &nbsp;<span class="badge badge-{{ $user->status }}">{{ ucfirst($user->status) }}</span>
                @if($user->last_seen_at)
                &nbsp;<span style="font-size:9px;color:#9CA3AF;">Last active: {{ $user->last_seen_at->format('d M Y') }}</span>
                @endif
            </div>
        </td>
    </tr>
    </table>
</div>

{{-- KPI boxes --}}
<table width="100%" cellpadding="0" cellspacing="6" style="margin-bottom:14px;">
<tr>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #4F46E5;">
        <div class="kpi-val">{{ $totalTasks }}</div><div class="kpi-lbl">Total Assigned</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #10B981;">
        <div class="kpi-val">{{ $completedTasks }}</div><div class="kpi-lbl">Completed</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #F59E0B;">
        <div class="kpi-val">{{ $pendingTasks }}</div><div class="kpi-lbl">In Progress</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #8B5CF6;">
        <div class="kpi-val">{{ $inReviewTasks }}</div><div class="kpi-lbl">In Review</div>
    </div></td>
    <td width="20%"><div class="kpi-box" style="border-top:3px solid #3B82F6;">
        <div class="kpi-val">{{ $completionRate }}%</div><div class="kpi-lbl">Completion Rate</div>
    </div></td>
</tr>
</table>

{{-- Progress bar --}}
@if($totalTasks > 0)
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:4px;">
<tr>
    <td style="font-size:9px;color:#6B7280;">Completion progress</td>
    <td align="right" style="font-size:9px;color:#4F46E5;font-weight:bold;">{{ $completionRate }}%</td>
</tr>
</table>
<div class="bar-bg" style="margin-bottom:16px;">
    <div class="bar-fill" style="width:{{ $completionRate }}%;background:{{ $completionRate>=80?'#10B981':($completionRate>=50?'#F59E0B':'#EF4444') }};"></div>
</div>
@endif

{{-- Task list --}}
<div class="section-title">Task List ({{ $totalTasks }} tasks)</div>
@if($tasks->isEmpty())
    <p style="text-align:center;padding:20px;color:#9CA3AF;">No tasks assigned.</p>
@else
<table class="data" width="100%">
    <thead>
    <tr>
        <th width="24">#</th>
        <th>Task Title</th>
        <th>Project</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Deadline</th>
        <th>Created</th>
    </tr>
    </thead>
    <tbody>
    @foreach($tasks as $i => $task)
    @php
        $isOverdue = $task->deadline
            && \Carbon\Carbon::parse($task->deadline)->isPast()
            && !in_array($task->status, ['approved','delivered']);
    @endphp
    <tr>
        <td style="color:#9CA3AF;">{{ $i + 1 }}</td>
        <td>{{ $task->title }}</td>
        <td style="color:#6B7280;">{{ ($task->project && !$task->project->is_quick) ? $task->project->name : '—' }}</td>
        <td><span class="st st-{{ $task->status }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span></td>
        <td>
            @if($task->priority)
                <span class="dot dot-{{ $task->priority }}"></span>{{ ucfirst($task->priority) }}
            @else —
            @endif
        </td>
        <td style="color:{{ $isOverdue ? '#DC2626' : '#374151' }};white-space:nowrap;">
            {{ $task->deadline ? \Carbon\Carbon::parse($task->deadline)->format('d M Y') : '—' }}
        </td>
        <td style="color:#9CA3AF;white-space:nowrap;">{{ $task->created_at->format('d M Y') }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

{{-- Footer --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E5E7EB;padding-top:10px;margin-top:20px;">
<tr>
    <td class="footer-txt">{{ $appName }} — {{ $user->name }} · Backup Report · Confidential</td>
    <td class="footer-txt" align="right">Generated on {{ $generatedAt }}</td>
</tr>
</table>

</div>
</body>
</html>

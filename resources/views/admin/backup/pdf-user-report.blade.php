<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; background: #fff; }

.page { padding: 32px 36px; }

/* Header */
.header { border-bottom: 3px solid #4F46E5; padding-bottom: 16px; margin-bottom: 22px; display: table; width: 100%; }
.header-left  { display: table-cell; vertical-align: middle; }
.header-right { display: table-cell; text-align: right; vertical-align: middle; }
.app-name    { font-size: 13px; font-weight: bold; color: #4F46E5; }
.report-title{ font-size: 11px; color: #6B7280; margin-top: 2px; }
.meta        { font-size: 9px; color: #9CA3AF; margin-top: 2px; }

/* User card */
.user-card { background: #F0F4FF; border: 1px solid #C7D2FE; border-radius: 10px; padding: 14px 16px; margin-bottom: 20px; display: table; width: 100%; }
.user-avatar { display: table-cell; width: 48px; vertical-align: middle; }
.avatar-circle { width: 44px; height: 44px; border-radius: 50%; background: #4F46E5; display: table-cell; text-align: center; vertical-align: middle; }
.avatar-letter { color: #fff; font-size: 20px; font-weight: bold; }
.user-info { display: table-cell; vertical-align: middle; padding-left: 12px; }
.user-name  { font-size: 16px; font-weight: bold; color: #111827; }
.user-meta  { font-size: 10px; color: #6B7280; margin-top: 3px; }
.user-badges { display: table-cell; vertical-align: middle; text-align: right; }
.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 9px; font-weight: bold; text-transform: capitalize; }
.badge-admin   { background: #FEE2E2; color: #991B1B; }
.badge-manager { background: #E0E7FF; color: #3730A3; }
.badge-user    { background: #D1FAE5; color: #065F46; }
.badge-active  { background: #D1FAE5; color: #065F46; }
.badge-held    { background: #FEF3C7; color: #92400E; }

/* KPI row */
.kpi-row { display: table; width: 100%; margin-bottom: 22px; }
.kpi-cell { display: table-cell; padding-right: 8px; }
.kpi-cell:last-child { padding-right: 0; }
.kpi-box { border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 10px; text-align: center; background: #FAFAFA; }
.kpi-box.indigo { border-top: 3px solid #4F46E5; }
.kpi-box.green  { border-top: 3px solid #10B981; }
.kpi-box.amber  { border-top: 3px solid #F59E0B; }
.kpi-box.violet { border-top: 3px solid #8B5CF6; }
.kpi-box.blue   { border-top: 3px solid #3B82F6; }
.kpi-val  { font-size: 22px; font-weight: bold; color: #111827; }
.kpi-lbl  { font-size: 8px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; }

/* Progress */
.progress-row { margin-bottom: 20px; }
.bar-wrap { background: #E5E7EB; border-radius: 4px; height: 8px; width: 100%; }
.bar-fill { border-radius: 4px; height: 8px; }

/* Section */
.section-title { font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase; letter-spacing: 0.8px; border-bottom: 1px solid #E5E7EB; padding-bottom: 5px; margin-bottom: 10px; }

/* Task table */
table { width: 100%; border-collapse: collapse; font-size: 10px; }
th { background: #F3F4F6; padding: 7px 8px; text-align: left; font-size: 9px; font-weight: bold; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #E5E7EB; }
td { padding: 7px 8px; border-bottom: 1px solid #F3F4F6; color: #374151; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:nth-child(even) td { background: #FAFAFA; }

/* Status badges */
.st { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: 8px; font-weight: bold; white-space: nowrap; }
.st-delivered         { background: #D1FAE5; color: #065F46; }
.st-approved          { background: #E0E7FF; color: #3730A3; }
.st-submitted         { background: #FEF3C7; color: #92400E; }
.st-in_progress       { background: #DBEAFE; color: #1E40AF; }
.st-revision_requested{ background: #FEE2E2; color: #991B1B; }
.st-assigned          { background: #F3F4F6; color: #374151; }
.st-viewed            { background: #F3F4F6; color: #374151; }
.st-draft             { background: #F3F4F6; color: #9CA3AF; }
.st-paused            { background: #FEF3C7; color: #92400E; }

/* Priority dot */
.dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 4px; vertical-align: middle; }
.dot-high   { background: #EF4444; }
.dot-medium { background: #F59E0B; }
.dot-low    { background: #10B981; }
.dot-urgent { background: #7C3AED; }

/* Footer */
.footer { border-top: 1px solid #E5E7EB; padding-top: 10px; margin-top: 24px; display: table; width: 100%; }
.footer-left  { display: table-cell; font-size: 8px; color: #9CA3AF; }
.footer-right { display: table-cell; text-align: right; font-size: 8px; color: #9CA3AF; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            @if(!empty($logoBase64))
                <img src="{{ $logoBase64 }}" style="height:28px;max-width:120px;object-fit:contain;display:block;margin-bottom:4px;">
            @else
                <div class="app-name">{{ $appName }}</div>
            @endif
            <div class="report-title">Individual User Report</div>
            <div class="meta">Backup snapshot · {{ $generatedAt }}</div>
        </div>
        <div class="header-right">
            <div style="font-size:10px;color:#6B7280;">Confidential</div>
        </div>
    </div>

    {{-- User card --}}
    <div class="user-card">
        <div class="user-avatar">
            @if(!empty($avatarBase64))
                <img src="{{ $avatarBase64 }}" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #C7D2FE;">
            @else
                <div style="width:44px;height:44px;border-radius:50%;background:#4F46E5;display:table-cell;text-align:center;vertical-align:middle;">
                    <span style="color:#fff;font-size:20px;font-weight:bold;">{{ strtoupper(substr($user->name,0,1)) }}</span>
                </div>
            @endif
        </div>
        <div class="user-info">
            <div class="user-name">{{ $user->name }}</div>
            <div class="user-meta">{{ $user->email }}
                @if($user->job_title) · {{ $user->job_title }}@endif
                @if($user->nationality) · {{ $user->nationality }}@endif
            </div>
            <div style="margin-top:5px;">
                <span class="badge badge-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
                &nbsp;
                <span class="badge badge-{{ $user->status }}">{{ ucfirst($user->status) }}</span>
                @if($user->last_seen_at)
                &nbsp;<span style="font-size:9px;color:#9CA3AF;">Last active: {{ $user->last_seen_at->format('d M Y') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- KPI boxes --}}
    <div class="kpi-row">
        <div class="kpi-cell"><div class="kpi-box indigo">
            <div class="kpi-val">{{ $totalTasks }}</div>
            <div class="kpi-lbl">Total Assigned</div>
        </div></div>
        <div class="kpi-cell"><div class="kpi-box green">
            <div class="kpi-val">{{ $completedTasks }}</div>
            <div class="kpi-lbl">Completed</div>
        </div></div>
        <div class="kpi-cell"><div class="kpi-box amber">
            <div class="kpi-val">{{ $pendingTasks }}</div>
            <div class="kpi-lbl">In Progress</div>
        </div></div>
        <div class="kpi-cell"><div class="kpi-box violet">
            <div class="kpi-val">{{ $inReviewTasks }}</div>
            <div class="kpi-lbl">In Review</div>
        </div></div>
        <div class="kpi-cell"><div class="kpi-box blue">
            <div class="kpi-val">{{ $completionRate }}%</div>
            <div class="kpi-lbl">Completion Rate</div>
        </div></div>
    </div>

    {{-- Progress bar --}}
    @if($totalTasks > 0)
    <div class="progress-row">
        <div style="display:table;width:100%;margin-bottom:4px;">
            <div style="display:table-cell;font-size:9px;color:#6B7280;">Completion progress</div>
            <div style="display:table-cell;text-align:right;font-size:9px;color:#4F46E5;font-weight:bold;">{{ $completionRate }}%</div>
        </div>
        <div class="bar-wrap">
            <div class="bar-fill" style="width:{{ $completionRate }}%;background:{{ $completionRate >= 80 ? '#10B981' : ($completionRate >= 50 ? '#F59E0B' : '#EF4444') }};"></div>
        </div>
    </div>
    @endif

    {{-- Task list --}}
    <div class="section-title">Task List ({{ $totalTasks }} tasks)</div>
    @if($tasks->isEmpty())
    <div style="text-align:center;padding:20px;color:#9CA3AF;font-size:11px;">No tasks assigned to this user.</div>
    @else
    <table>
        <thead>
            <tr>
                <th>#</th>
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
            <tr>
                <td style="color:#9CA3AF;width:24px;">{{ $i + 1 }}</td>
                <td style="max-width:180px;">{{ $task->title }}</td>
                <td style="color:#6B7280;max-width:100px;">{{ ($task->project && !$task->project->is_quick) ? $task->project->name : '—' }}</td>
                <td>
                    <span class="st st-{{ str_replace(' ','_',$task->status) }}">
                        {{ ucfirst(str_replace('_',' ',$task->status)) }}
                    </span>
                </td>
                <td>
                    @if($task->priority)
                    <span class="dot dot-{{ $task->priority }}"></span>{{ ucfirst($task->priority) }}
                    @else —
                    @endif
                </td>
                <td style="white-space:nowrap;color:{{ $task->deadline && \Carbon\Carbon::parse($task->deadline)->isPast() && !in_array($task->status,['approved','delivered']) ? '#DC2626' : '#374151' }};">
                    {{ $task->deadline ? \Carbon\Carbon::parse($task->deadline)->format('d M Y') : '—' }}
                </td>
                <td style="color:#9CA3AF;white-space:nowrap;">{{ $task->created_at->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">{{ $appName }} — {{ $user->name }} · Backup Report · Confidential</div>
        <div class="footer-right">Generated on {{ $generatedAt }}</div>
    </div>

</div>
</body>
</html>

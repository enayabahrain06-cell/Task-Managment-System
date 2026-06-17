<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Task Report — {{ $user->name }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #111827; background: #fff; }

    /* ── Print layout ──────────────────────────────── */
    @media print {
        body { font-size: 11px; }
        .no-print { display: none !important; }
        .page-break { page-break-before: always; }
        table { page-break-inside: avoid; }
    }
    @page { margin: 16mm 14mm; size: A4 portrait; }

    /* ── Screen layout ─────────────────────────────── */
    .page { max-width: 760px; margin: 0 auto; padding: 32px 28px; }

    /* Header */
    .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #4F46E5; padding-bottom: 14px; margin-bottom: 24px; }
    .header-title { font-size: 20px; font-weight: 700; color: #4F46E5; }
    .header-meta { text-align: right; font-size: 11px; color: #6B7280; }
    .header-meta strong { color: #111827; font-size: 13px; display: block; }

    /* Stats grid */
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 28px; }
    .stat-card { border: 1px solid #E5E7EB; border-radius: 10px; padding: 14px 16px; }
    .stat-label { font-size: 11px; color: #6B7280; margin-bottom: 4px; }
    .stat-value { font-size: 26px; font-weight: 700; line-height: 1; }
    .stat-value.blue   { color: #2563EB; }
    .stat-value.green  { color: #16A34A; }
    .stat-value.red    { color: #DC2626; }
    .stat-value.yellow { color: #D97706; }
    .stat-value.indigo { color: #4F46E5; }
    .stat-value.gray   { color: #374151; }

    /* Section */
    .section { margin-bottom: 24px; }
    .section-title { font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid #E5E7EB; display: flex; align-items: center; gap: 6px; }
    .section-empty { font-size: 12px; color: #9CA3AF; font-style: italic; padding: 6px 0; }

    /* Task table */
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th { text-align: left; font-size: 10px; font-weight: 600; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; padding: 6px 8px; border-bottom: 1px solid #E5E7EB; }
    td { padding: 7px 8px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #F9FAFB; }

    .badge { display: inline-block; font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 999px; }
    .badge-red    { background: #FEE2E2; color: #B91C1C; }
    .badge-orange { background: #FFEDD5; color: #C2410C; }
    .badge-yellow { background: #FEF9C3; color: #A16207; }
    .badge-indigo { background: #EEF2FF; color: #4338CA; }
    .badge-blue   { background: #DBEAFE; color: #1D4ED8; }
    .badge-green  { background: #DCFCE7; color: #15803D; }
    .badge-gray   { background: #F3F4F6; color: #374151; }
    .overdue-meta { color: #DC2626; font-size: 11px; }
    .meta-gray    { color: #9CA3AF; font-size: 11px; }

    /* Print button */
    .print-bar { background: #4F46E5; color: #fff; text-align: center; padding: 12px; position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: center; gap: 12px; }
    .print-bar button { background: #fff; color: #4F46E5; border: none; font-weight: 700; font-size: 13px; padding: 7px 20px; border-radius: 7px; cursor: pointer; display: flex; align-items: center; gap: 6px; }
    .print-bar button:hover { background: #EEF2FF; }
    .print-bar span { font-size: 13px; opacity: .9; }

    /* Footer */
    .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #E5E7EB; font-size: 11px; color: #9CA3AF; display: flex; justify-content: space-between; }
</style>
</head>
<body>

{{-- Print bar (screen only) --}}
<div class="print-bar no-print">
    <span>📄 Task Report ready</span>
    <button onclick="window.print()">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
        Print / Save as PDF
    </button>
    <button onclick="window.close()" style="background:#EF4444;color:#fff;border:none;font-weight:600;font-size:12px;padding:7px 14px;border-radius:7px;cursor:pointer;">✕ Close</button>
</div>

<div class="page">

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="header-title">📋 Task Report</div>
            <div style="font-size:12px;color:#6B7280;margin-top:3px;">{{ now()->format('F j, Y') }}</div>
        </div>
        <div class="header-meta">
            <strong>{{ $user->name }}</strong>
            {{ ucfirst($user->role) }}
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Tasks</div>
            <div class="stat-value gray">{{ $stats['total'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Completed</div>
            <div class="stat-value green">{{ $stats['completed'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Overdue</div>
            <div class="stat-value {{ $stats['overdue'] > 0 ? 'red' : 'green' }}">{{ $stats['overdue'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">In Progress</div>
            <div class="stat-value indigo">{{ $stats['inProgress'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">In Review</div>
            <div class="stat-value yellow">{{ $stats['submitted'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Completion Rate</div>
            <div class="stat-value {{ $stats['rate'] >= 70 ? 'green' : ($stats['rate'] >= 40 ? 'yellow' : 'red') }}">{{ $stats['rate'] }}%</div>
        </div>
    </div>

    {{-- Overdue Tasks --}}
    <div class="section">
        <div class="section-title">
            🔴 Overdue Tasks
            <span style="font-size:11px;font-weight:400;color:#6B7280;">({{ $overdueTasks->count() }})</span>
        </div>
        @if($overdueTasks->isEmpty())
            <div class="section-empty">No overdue tasks — great work!</div>
        @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Task</th>
                    <th>Project</th>
                    <th>Status</th>
                    <th>Deadline</th>
                </tr>
            </thead>
            <tbody>
                @foreach($overdueTasks as $task)
                <tr>
                    <td style="color:#9CA3AF;width:36px;">{{ $task->id }}</td>
                    <td style="font-weight:500;">{{ $task->title }}</td>
                    <td class="meta-gray">{{ $task->project?->name ?? '—' }}</td>
                    <td>
                        @php $sc = match($task->status) { 'in_progress'=>'indigo','assigned'=>'blue','viewed'=>'gray','paused'=>'yellow','submitted'=>'orange','revision_requested'=>'red',default=>'gray' }; @endphp
                        <span class="badge badge-{{ $sc }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                    </td>
                    <td class="overdue-meta">⚠️ {{ \Carbon\Carbon::parse($task->deadline)->format('M j, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- In Progress --}}
    <div class="section">
        <div class="section-title">
            🔵 In Progress
            <span style="font-size:11px;font-weight:400;color:#6B7280;">({{ $inProgressTasks->count() }})</span>
        </div>
        @if($inProgressTasks->isEmpty())
            <div class="section-empty">No tasks currently in progress.</div>
        @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Task</th>
                    <th>Project</th>
                    <th>Status</th>
                    <th>Deadline</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inProgressTasks as $task)
                <tr>
                    <td style="color:#9CA3AF;width:36px;">{{ $task->id }}</td>
                    <td style="font-weight:500;">{{ $task->title }}</td>
                    <td class="meta-gray">{{ $task->project?->name ?? '—' }}</td>
                    <td><span class="badge badge-indigo">{{ $task->status === 'paused' ? 'Paused' : 'In Progress' }}</span></td>
                    <td class="{{ $task->deadline && \Carbon\Carbon::parse($task->deadline)->isPast() ? 'overdue-meta' : 'meta-gray' }}">
                        {{ $task->deadline ? \Carbon\Carbon::parse($task->deadline)->format('M j, Y') : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Upcoming --}}
    <div class="section">
        <div class="section-title">
            📅 Due in the Next 14 Days
            <span style="font-size:11px;font-weight:400;color:#6B7280;">({{ $upcomingTasks->count() }})</span>
        </div>
        @if($upcomingTasks->isEmpty())
            <div class="section-empty">No tasks due in the next 14 days.</div>
        @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Task</th>
                    <th>Project</th>
                    <th>Status</th>
                    <th>Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach($upcomingTasks as $task)
                @php $sc = match($task->status) { 'in_progress'=>'indigo','assigned'=>'blue','viewed'=>'gray','paused'=>'yellow','submitted'=>'orange','revision_requested'=>'red',default=>'gray' }; @endphp
                <tr>
                    <td style="color:#9CA3AF;width:36px;">{{ $task->id }}</td>
                    <td style="font-weight:500;">{{ $task->title }}</td>
                    <td class="meta-gray">{{ $task->project?->name ?? '—' }}</td>
                    <td><span class="badge badge-{{ $sc }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span></td>
                    <td class="meta-gray">{{ \Carbon\Carbon::parse($task->deadline)->format('M j, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Footer --}}
    <div class="footer">
        <span>Generated by Task Assistant · {{ now()->format('M j, Y \a\t g:i A') }}</span>
        <span>{{ $user->name }} · {{ ucfirst($user->role) }}</span>
    </div>

</div>

<script class="no-print">
    // Auto-print after fonts load
    window.addEventListener('load', function() {
        setTimeout(function() { window.print(); }, 400);
    });
</script>
</body>
</html>

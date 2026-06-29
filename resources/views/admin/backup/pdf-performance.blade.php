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

/* Table */
table { width: 100%; border-collapse: collapse; font-size: 11px; }
th { background: #F3F4F6; padding: 9px 10px; text-align: left; font-size: 10px; font-weight: bold; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #E5E7EB; }
td { padding: 9px 10px; border-bottom: 1px solid #F3F4F6; color: #374151; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:nth-child(even) td { background: #FAFAFA; }

/* Role badge */
.role { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: bold; text-transform: capitalize; }
.role-admin   { background: #FEE2E2; color: #991B1B; }
.role-manager { background: #E0E7FF; color: #3730A3; }
.role-user    { background: #D1FAE5; color: #065F46; }

/* Progress bar */
.bar-wrap { background: #E5E7EB; border-radius: 4px; height: 6px; width: 70px; display: inline-block; vertical-align: middle; }
.bar-fill { border-radius: 4px; height: 6px; }

/* Rate text */
.rate-high { color: #059669; font-weight: bold; }
.rate-mid  { color: #D97706; font-weight: bold; }
.rate-low  { color: #DC2626; font-weight: bold; }

/* Summary row */
.summary-box { background: #F0F4FF; border: 1px solid #C7D2FE; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px; display: table; width: 100%; }
.sum-cell { display: table-cell; text-align: center; }
.sum-val { font-size: 20px; font-weight: bold; color: #4F46E5; }
.sum-lbl { font-size: 9px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

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
                <div class="app-name">{{ $appName }}</div>
                <div class="report-title">User Performance Report</div>
                <div class="meta">Generated: {{ $generatedAt }}</div>
            </div>
            <div class="header-right">
                <div style="font-size:11px;color:#6B7280;">Backup Snapshot</div>
                <div style="font-size:10px;color:#9CA3AF;margin-top:2px;">{{ $generatedAt }}</div>
            </div>
        </div>
    </div>

    {{-- Summary strip --}}
    <div class="summary-box">
        <div class="sum-cell">
            <div class="sum-val">{{ $totalUsers }}</div>
            <div class="sum-lbl">Total Users</div>
        </div>
        <div class="sum-cell">
            <div class="sum-val">{{ $activeUsers }}</div>
            <div class="sum-lbl">Active</div>
        </div>
        <div class="sum-cell">
            <div class="sum-val">{{ $totalAssigned }}</div>
            <div class="sum-lbl">Tasks Assigned</div>
        </div>
        <div class="sum-cell">
            <div class="sum-val">{{ $totalCompleted }}</div>
            <div class="sum-lbl">Tasks Completed</div>
        </div>
        <div class="sum-cell">
            <div class="sum-val">{{ $overallRate }}%</div>
            <div class="sum-lbl">Overall Rate</div>
        </div>
    </div>

    {{-- Performance table --}}
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Role</th>
                <th>Assigned</th>
                <th>Completed</th>
                <th>Pending</th>
                <th>In Review</th>
                <th>Rate</th>
                <th>Progress</th>
                <th>Last Active</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $i => $u)
            @php
                $rate = $u->total > 0 ? round($u->completed / $u->total * 100) : 0;
                $rateClass = $rate >= 80 ? 'rate-high' : ($rate >= 50 ? 'rate-mid' : 'rate-low');
                $barColor = $rate >= 80 ? '#10B981' : ($rate >= 50 ? '#F59E0B' : '#EF4444');
            @endphp
            <tr>
                <td style="color:#9CA3AF;">{{ $i + 1 }}</td>
                <td><strong>{{ $u->name }}</strong></td>
                <td><span class="role role-{{ $u->role }}">{{ ucfirst($u->role) }}</span></td>
                <td>{{ $u->total }}</td>
                <td style="color:#059669;font-weight:600;">{{ $u->completed }}</td>
                <td style="color:#D97706;">{{ $u->pending }}</td>
                <td style="color:#6366F1;">{{ $u->in_review }}</td>
                <td><span class="{{ $rateClass }}">{{ $rate }}%</span></td>
                <td>
                    <div class="bar-wrap">
                        <div class="bar-fill" style="width:{{ $rate }}%;background:{{ $barColor }};"></div>
                    </div>
                </td>
                <td style="color:#9CA3AF;font-size:10px;">{{ $u->last_seen }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">{{ $appName }} — Backup Report · Confidential</div>
        <div class="footer-right">Generated on {{ $generatedAt }}</div>
    </div>

</div>
</body>
</html>

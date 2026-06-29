<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
.page { padding: 28px 32px; }

.app-name  { font-size: 20px; font-weight: bold; color: #4F46E5; }
.sub-title { font-size: 12px; color: #6B7280; margin-top: 2px; }
.meta-txt  { font-size: 10px; color: #9CA3AF; margin-top: 2px; }

.sum-box { background: #F0F4FF; border: 1px solid #C7D2FE; border-radius: 8px; padding: 12px 0; margin-bottom: 20px; }
.sum-val { font-size: 20px; font-weight: bold; color: #4F46E5; text-align: center; }
.sum-lbl { font-size: 9px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; text-align: center; margin-top: 2px; }

table.data { width: 100%; border-collapse: collapse; font-size: 10px; }
table.data th { background: #F3F4F6; padding: 8px 9px; text-align: left; font-size: 9px;
    font-weight: bold; color: #6B7280; text-transform: uppercase; border-bottom: 2px solid #E5E7EB; }
table.data td { padding: 8px 9px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
table.data tr:last-child td { border-bottom: none; }
table.data tr:nth-child(even) td { background: #FAFAFA; }

.role { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: 9px; font-weight: bold; }
.role-admin   { background: #FEE2E2; color: #991B1B; }
.role-manager { background: #E0E7FF; color: #3730A3; }
.role-user    { background: #D1FAE5; color: #065F46; }

.bar-bg   { background: #E5E7EB; border-radius: 4px; height: 6px; width: 60px; }
.bar-fill { border-radius: 4px; height: 6px; }
.footer-txt { font-size: 9px; color: #9CA3AF; }
</style>
</head>
<body>
<div class="page">

{{-- Header --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:3px solid #4F46E5;padding-bottom:14px;margin-bottom:20px;">
<tr>
    <td>
        @if(!empty($logoBase64))
            <img src="{{ $logoBase64 }}" style="height:52px;max-width:200px;"><br>
        @else
            <div class="app-name">{{ $appName }}</div>
        @endif
        <div class="sub-title">User Performance Report</div>
        <div class="meta-txt">Generated: {{ $generatedAt }}</div>
    </td>
    <td align="right" valign="middle">
        <div style="font-size:11px;color:#6B7280;">Backup Snapshot</div>
        <div style="font-size:10px;color:#9CA3AF;margin-top:2px;">{{ $generatedAt }}</div>
    </td>
</tr>
</table>

{{-- Summary strip --}}
<table width="100%" cellpadding="0" cellspacing="0" class="sum-box" style="margin-bottom:20px;">
<tr>
    <td width="20%"><div class="sum-val">{{ $totalUsers }}</div><div class="sum-lbl">Total Users</div></td>
    <td width="20%"><div class="sum-val">{{ $activeUsers }}</div><div class="sum-lbl">Active</div></td>
    <td width="20%"><div class="sum-val">{{ $totalAssigned }}</div><div class="sum-lbl">Assigned</div></td>
    <td width="20%"><div class="sum-val">{{ $totalCompleted }}</div><div class="sum-lbl">Completed</div></td>
    <td width="20%"><div class="sum-val">{{ $overallRate }}%</div><div class="sum-lbl">Overall Rate</div></td>
</tr>
</table>

{{-- Performance table --}}
<table class="data" width="100%">
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
        $rateColor = $rate >= 80 ? '#059669' : ($rate >= 50 ? '#D97706' : '#DC2626');
        $barColor  = $rate >= 80 ? '#10B981' : ($rate >= 50 ? '#F59E0B' : '#EF4444');
    @endphp
    <tr>
        <td style="color:#9CA3AF;">{{ $i + 1 }}</td>
        <td><strong>{{ $u->name }}</strong></td>
        <td><span class="role role-{{ $u->role }}">{{ ucfirst($u->role) }}</span></td>
        <td>{{ $u->total }}</td>
        <td style="color:#059669;font-weight:600;">{{ $u->completed }}</td>
        <td style="color:#D97706;">{{ $u->pending }}</td>
        <td style="color:#6366F1;">{{ $u->in_review }}</td>
        <td style="color:{{ $rateColor }};font-weight:bold;">{{ $rate }}%</td>
        <td><div class="bar-bg"><div class="bar-fill" style="width:{{ $rate }}%;background:{{ $barColor }};"></div></div></td>
        <td style="color:#9CA3AF;">{{ $u->last_seen }}</td>
    </tr>
    @endforeach
    </tbody>
</table>

{{-- Footer --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E5E7EB;padding-top:10px;margin-top:24px;">
<tr>
    <td class="footer-txt">{{ $appName }} — Backup Report · Confidential</td>
    <td class="footer-txt" align="right">Generated on {{ $generatedAt }}</td>
</tr>
</table>

</div>
</body>
</html>

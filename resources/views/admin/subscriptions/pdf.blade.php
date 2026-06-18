<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size:10px; color:#111827; background:#fff; }

/* ── Page header ── */
.page-header { background:linear-gradient(135deg,#4F46E5 0%,#7C3AED 100%); color:#fff; padding:20px 24px 16px; border-radius:10px; margin-bottom:18px; }
.page-header h1 { font-size:18px; font-weight:700; margin:0 0 3px; letter-spacing:-.2px; }
.page-header .sub { font-size:10px; opacity:.8; }
.page-header .meta { font-size:9px; opacity:.65; margin-top:6px; }

/* ── Summary cards row ── */
.cards { display:table; width:100%; border-collapse:separate; border-spacing:8px 0; margin-bottom:18px; }
.card  { display:table-cell; background:#F9FAFB; border:1.5px solid #E5E7EB; border-radius:8px; padding:11px 14px; vertical-align:top; }
.card.indigo { background:#EEF2FF; border-color:#C7D2FE; }
.card.green  { background:#ECFDF5; border-color:#A7F3D0; }
.card.amber  { background:#FFFBEB; border-color:#FDE68A; }
.card.red    { background:#FEF2F2; border-color:#FECACA; }
.card-label { font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6B7280; margin-bottom:4px; }
.card-value { font-size:20px; font-weight:800; line-height:1; }
.card.indigo .card-value { color:#4F46E5; }
.card.green  .card-value { color:#16A34A; }
.card.amber  .card-value { color:#D97706; }
.card.red    .card-value { color:#DC2626; }
.card-sub { font-size:8.5px; color:#6B7280; margin-top:3px; }

/* ── Two-column summary layout ── */
.summary-row { display:table; width:100%; border-collapse:separate; border-spacing:14px 0; margin-bottom:18px; }
.summary-col { display:table-cell; vertical-align:top; }

/* ── Cost summary box ── */
.cost-box { background:#EEF2FF; border:1.5px solid #C7D2FE; border-radius:8px; padding:13px 16px; }
.cost-box h3 { font-size:10px; font-weight:700; color:#4F46E5; margin-bottom:10px; text-transform:uppercase; letter-spacing:.05em; }
.cost-row { display:table; width:100%; margin-bottom:6px; }
.cost-row td { display:table-cell; }
.cost-row .lbl { font-size:9px; color:#6B7280; }
.cost-row .val { font-size:13px; font-weight:800; color:#111827; text-align:right; }
.cost-row .val span { font-size:9px; font-weight:500; color:#6B7280; }
.cost-divider { border:none; border-top:1px solid #C7D2FE; margin:8px 0; }

/* ── Category table ── */
.cat-box { background:#F9FAFB; border:1.5px solid #E5E7EB; border-radius:8px; padding:13px 16px; }
.cat-box h3 { font-size:10px; font-weight:700; color:#374151; margin-bottom:10px; text-transform:uppercase; letter-spacing:.05em; }
.cat-bar-row { margin-bottom:7px; }
.cat-bar-label { font-size:9px; color:#374151; margin-bottom:2px; display:table; width:100%; }
.cat-bar-label .name { display:table-cell; }
.cat-bar-label .amt  { display:table-cell; text-align:right; font-weight:700; color:#4F46E5; }
.cat-bar-track { background:#E5E7EB; border-radius:4px; height:5px; }
.cat-bar-fill  { background:linear-gradient(90deg,#6366F1,#4F46E5); height:5px; border-radius:4px; }

/* ── Main table ── */
.section-title { font-size:11px; font-weight:700; color:#111827; margin-bottom:8px; padding-bottom:6px; border-bottom:2px solid #4F46E5; }
table.sub-table { width:100%; border-collapse:collapse; }
table.sub-table thead tr { background:#4F46E5; }
table.sub-table thead th { color:#fff; font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding:7px 9px; text-align:left; }
table.sub-table tbody tr { border-bottom:1px solid #F3F4F6; }
table.sub-table tbody tr:nth-child(even) { background:#FAFAFA; }
table.sub-table tbody td { padding:7px 9px; font-size:9.5px; color:#374151; vertical-align:middle; }
.badge { display:inline-block; padding:2px 7px; border-radius:10px; font-size:8px; font-weight:700; }
.badge-active        { background:#ECFDF5; color:#16A34A; }
.badge-expiring_soon { background:#FFFBEB; color:#D97706; }
.badge-expired       { background:#FEE2E2; color:#DC2626; }
.badge-cat           { background:#EEF2FF; color:#4F46E5; }
.cost-cell { font-weight:700; color:#111827; }
.cost-cell .cycle { font-size:8px; color:#9CA3AF; font-weight:400; }
.users-cell { color:#6B7280; }

/* ── Footer ── */
.page-footer { margin-top:18px; padding-top:10px; border-top:1px solid #E5E7EB; display:table; width:100%; }
.page-footer .left  { display:table-cell; font-size:8.5px; color:#9CA3AF; }
.page-footer .right { display:table-cell; text-align:right; font-size:8.5px; color:#9CA3AF; }
</style>
</head>
<body>

{{-- ── Header ── --}}
<div class="page-header">
    <h1>Subscriptions &amp; Licenses Report</h1>
    <div class="sub">Complete overview of all software subscriptions and recurring costs</div>
    <div class="meta">Generated on {{ $summary['generated_at'] }}</div>
</div>

{{-- ── Stat cards ── --}}
<div class="cards">
    <div class="card indigo">
        <div class="card-label">Total</div>
        <div class="card-value">{{ $summary['total'] }}</div>
        <div class="card-sub">Subscriptions</div>
    </div>
    <div class="card green">
        <div class="card-label">Active</div>
        <div class="card-value">{{ $summary['active'] }}</div>
        <div class="card-sub">Running normally</div>
    </div>
    <div class="card amber">
        <div class="card-label">Expiring Soon</div>
        <div class="card-value">{{ $summary['expiring_soon'] }}</div>
        <div class="card-sub">Within 30 days</div>
    </div>
    <div class="card red">
        <div class="card-label">Expired</div>
        <div class="card-value">{{ $summary['expired'] }}</div>
        <div class="card-sub">Action required</div>
    </div>
    <div class="card indigo">
        <div class="card-label">Monthly Spend</div>
        <div class="card-value" style="font-size:14px;">BHD {{ number_format($summary['monthly_total'], 3) }}</div>
        <div class="card-sub">All subscriptions</div>
    </div>
    <div class="card indigo">
        <div class="card-label">Annual Spend</div>
        <div class="card-value" style="font-size:14px;">BHD {{ number_format($summary['annual_total'], 3) }}</div>
        <div class="card-sub">Projected yearly</div>
    </div>
</div>

{{-- ── Summary: Cost breakdown + Category ── --}}
<div class="summary-row">
    {{-- Cost breakdown --}}
    <div class="summary-col" style="width:38%;">
        <div class="cost-box">
            <h3>Cost Breakdown</h3>
            @php
                $monthly  = $all->filter(fn($s) => $s->billing_cycle === 'monthly');
                $annual   = $all->filter(fn($s) => $s->billing_cycle === 'annual');
                $quarterly= $all->filter(fn($s) => $s->billing_cycle === 'quarterly');
                $onetime  = $all->filter(fn($s) => $s->billing_cycle === 'one_time');
            @endphp
            @foreach([['Monthly','monthly',$monthly],['Annual','annual',$annual],['Quarterly','quarterly',$quarterly],['One-time','one_time',$onetime]] as [$label,$key,$grp])
            @if($grp->count())
            <table class="cost-row"><tr>
                <td class="lbl">{{ $label }} ({{ $grp->count() }})</td>
                <td class="val">BHD {{ number_format($grp->sum(fn($s)=>$s->annual_cost), 3) }} <span>/ yr</span></td>
            </tr></table>
            @endif
            @endforeach
            <hr class="cost-divider">
            <table class="cost-row"><tr>
                <td class="lbl" style="font-weight:700;color:#374151;">Total Annual</td>
                <td class="val" style="font-size:15px;color:#4F46E5;">BHD {{ number_format($summary['annual_total'], 3) }}</td>
            </tr></table>
            <table class="cost-row" style="margin-top:4px;"><tr>
                <td class="lbl" style="color:#374151;">Monthly Equivalent</td>
                <td class="val">BHD {{ number_format($summary['monthly_total'], 3) }}</td>
            </tr></table>
        </div>
    </div>

    {{-- Category breakdown --}}
    <div class="summary-col">
        <div class="cat-box">
            <h3>By Category</h3>
            @php $maxAnnual = $summary['by_category']->max('annual') ?: 1; @endphp
            @foreach($summary['by_category'] as $cat)
            <div class="cat-bar-row">
                <div class="cat-bar-label">
                    <span class="name">{{ $cat['label'] }} ({{ $cat['count'] }})</span>
                    <span class="amt">BHD {{ number_format($cat['annual'], 3) }}/yr</span>
                </div>
                <div class="cat-bar-track">
                    <div class="cat-bar-fill" style="width:{{ round($cat['annual'] / $maxAnnual * 100) }}%;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Subscriptions table ── --}}
<div class="section-title">All Subscriptions</div>
<table class="sub-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Vendor</th>
            <th>Category</th>
            <th>Type</th>
            <th>Cost</th>
            <th>Annual Cost</th>
            <th>Renewal Date</th>
            <th>Status</th>
            <th>Seats</th>
        </tr>
    </thead>
    <tbody>
        @foreach($all as $i => $sub)
        @php
            $renewalLabel = $sub->renewal_date ? $sub->renewal_date->format('d M Y') : '—';
            $days = $sub->days_until_renewal;
            $daysLabel = $sub->renewal_date
                ? ($days < 0 ? abs($days).'d overdue' : ($days === 0 ? 'Today' : $days.'d left'))
                : '';
        @endphp
        <tr>
            <td style="color:#9CA3AF;">{{ $i + 1 }}</td>
            <td style="font-weight:600;color:#111827;">
                {{ $sub->name }}
                @if($sub->vendor)<br><span style="font-size:8px;color:#9CA3AF;">{{ $sub->vendor }}</span>@endif
            </td>
            <td>{{ $sub->vendor ?? '—' }}</td>
            <td><span class="badge badge-cat">{{ $catNames[$sub->category] ?? $sub->category }}</span></td>
            <td>
                @if($sub->type === 'per_seat') Per Seat
                @elseif($sub->type === 'site_license') Site License
                @else Shared @endif
            </td>
            <td class="cost-cell">
                {{ $sub->currency }} {{ number_format($sub->cost, 3) }}
                <br><span class="cycle">{{ ucfirst($sub->billing_cycle) }}</span>
            </td>
            <td class="cost-cell">{{ $sub->currency }} {{ number_format($sub->annual_cost, 3) }}</td>
            <td>
                {{ $renewalLabel }}
                @if($daysLabel)<br><span style="font-size:8px;color:{{ $sub->status==='expired'?'#DC2626':($sub->status==='expiring_soon'?'#D97706':'#6B7280') }};">{{ $daysLabel }}</span>@endif
            </td>
            <td><span class="badge badge-{{ $sub->status }}">
                @if($sub->status==='active') Active
                @elseif($sub->status==='expiring_soon') Expiring
                @else Expired @endif
            </span></td>
            <td class="users-cell">
                {{ $sub->users_count }}
                @if($sub->max_seats) / {{ $sub->max_seats }} @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ── Footer ── --}}
<div class="page-footer">
    <div class="left">Subscriptions &amp; Licenses — Confidential</div>
    <div class="right">Generated {{ $summary['generated_at'] }} · Total {{ $summary['total'] }} subscription{{ $summary['total'] !== 1 ? 's' : '' }}</div>
</div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
@page { size: A4 portrait; margin: 0; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size:9.5px; color:#1F2937; background:#fff; }

/* ── Accent bar (top) ── */
.accent-top { height:5px; background:#4F46E5; }

/* ── Header (light, matches reference) ── */
.header {
    background:#fff;
    padding:18px 28px 16px;
    display:table;
    width:100%;
    border-bottom:1px solid #E5E7EB;
}
.header-left  { display:table-cell; vertical-align:middle; }
.header-right { display:table-cell; vertical-align:middle; text-align:right; }

/* Logo + company name row */
.brand-row     { display:table; }
.brand-logo    { display:table-cell; vertical-align:middle; padding-right:12px; }
.brand-logo img{ height:44px; max-width:60px; object-fit:contain; }
.brand-text    { display:table-cell; vertical-align:middle; }
.brand-name    { font-size:16px; font-weight:800; color:#1E1B4B; letter-spacing:-.3px; }
.brand-sub     { font-size:9px; color:#9CA3AF; margin-top:2px; }

/* Report title on right */
.report-title { font-size:22px; font-weight:800; color:#4F46E5; letter-spacing:-.4px; }
.report-conf  { font-size:8.5px; color:#9CA3AF; margin-top:4px; }

/* ── Meta strip ── */
.meta-strip { display:table; width:100%; border-bottom:1px solid #E5E7EB; background:#FAFAFA; }
.meta-cell  { display:table-cell; padding:9px 28px; vertical-align:middle; border-right:1px solid #E5E7EB; }
.meta-cell:last-child { border-right:none; }
.meta-label { font-size:7px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:#9CA3AF; margin-bottom:3px; }
.meta-value { font-size:9.5px; font-weight:600; color:#111827; }

/* ── Body ── */
.body-wrap { padding:18px 28px; }

/* ── Section headers ── */
.section-row   { display:table; width:100%; margin-bottom:10px; }
.section-left  { display:table-cell; vertical-align:bottom; }
.section-right { display:table-cell; vertical-align:bottom; text-align:right; }
.section-head  { padding-left:10px; border-left:3px solid #4F46E5; }
.section-label { font-size:7.5px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#6366F1; margin-bottom:1px; }
.section-title { font-size:11px; font-weight:800; color:#1E1B4B; }

/* ── Count pill ── */
.count-pill {
    display:inline-block; background:#EEF2FF; color:#4338CA;
    border-radius:20px; padding:2px 10px; font-size:8px; font-weight:700;
}

/* ── Stat cards ── */
.cards-grid { display:table; width:100%; border-collapse:separate; border-spacing:0; margin-bottom:18px; }
.cards-row  { display:table-row; }
.card-cell  { display:table-cell; padding:4px; width:33.33%; }
.card {
    border-radius:10px; padding:14px 16px;
    border-left:4px solid transparent; vertical-align:top;
}
.card.indigo { background:#EEF2FF; border-color:#4F46E5; }
.card.green  { background:#ECFDF5; border-color:#16A34A; }
.card.amber  { background:#FFFBEB; border-color:#D97706; }
.card.red    { background:#FEF2F2; border-color:#DC2626; }
.card.blue   { background:#EFF6FF; border-color:#2563EB; }
.card.violet { background:#F5F3FF; border-color:#7C3AED; }
.card-label { font-size:7.5px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#6B7280; margin-bottom:6px; }
.card-value { font-size:24px; font-weight:800; line-height:1; }
.card.indigo .card-value { color:#4F46E5; }
.card.green  .card-value { color:#16A34A; }
.card.amber  .card-value { color:#D97706; }
.card.red    .card-value { color:#DC2626; }
.card.blue   .card-value { color:#2563EB; }
.card.violet .card-value { color:#7C3AED; }
.card-sub { font-size:7.5px; color:#9CA3AF; margin-top:5px; }

/* ── Cost + Category two-col ── */
.summary-row { display:table; width:100%; border-collapse:separate; border-spacing:10px 0; margin-bottom:18px; }
.summary-col { display:table-cell; vertical-align:top; }
.box { border:1px solid #E5E7EB; border-radius:10px; padding:14px 16px; background:#fff; }
.box-title { font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#6366F1; margin-bottom:12px; padding-bottom:8px; border-bottom:2px solid #EEF2FF; }

/* cycle rows */
.cycle-item { display:table; width:100%; margin-bottom:9px; }
.cycle-dot-cell { display:table-cell; width:8px; vertical-align:middle; padding-right:7px; }
.cycle-dot { width:8px; height:8px; border-radius:50%; }
.cycle-name-cell { display:table-cell; vertical-align:middle; font-size:8.5px; color:#374151; }
.cycle-count { font-size:7px; color:#9CA3AF; margin-left:3px; }
.cycle-cost-cell { display:table-cell; vertical-align:middle; text-align:right; font-size:10px; font-weight:700; color:#111827; white-space:nowrap; }
.cycle-cost-sub { font-size:7px; font-weight:400; color:#9CA3AF; }

/* dompdf-safe bar: table with filled left cell */
.bar-table { width:100%; border-collapse:collapse; margin-top:4px; }
.bar-filled { height:5px; border-radius:3px; }
.bar-empty  { height:5px; background:#F3F4F6; border-radius:3px; }

/* totals highlight */
.totals-box { background:#F5F3FF; border-radius:8px; padding:10px 12px; margin-top:10px; display:table; width:100%; }
.totals-box td { display:table-cell; vertical-align:middle; }
.totals-lbl { font-size:8px; color:#6B7280; }
.totals-val { font-size:13px; font-weight:800; color:#4F46E5; text-align:right; }
.totals-sub-lbl { font-size:7.5px; color:#9CA3AF; margin-top:2px; }
.totals-sub-val { font-size:9px; font-weight:600; color:#374151; text-align:right; margin-top:2px; }

/* category rows */
.cat-item { margin-bottom:10px; }
.cat-header { display:table; width:100%; margin-bottom:4px; }
.cat-name { display:table-cell; font-size:8.5px; font-weight:600; color:#374151; }
.cat-count { font-size:7px; color:#9CA3AF; margin-left:3px; font-weight:400; }
.cat-amt  { display:table-cell; text-align:right; font-size:8.5px; font-weight:700; color:#4F46E5; white-space:nowrap; }

/* ── Subscriptions table ── */
table.sub-table { width:100%; border-collapse:collapse; }
table.sub-table thead tr { background:#1E1B4B; }
table.sub-table thead th {
    color:rgba(255,255,255,.9); font-size:7.5px; font-weight:700;
    text-transform:uppercase; letter-spacing:.06em; padding:7px 9px; text-align:left;
}
table.sub-table tbody tr { border-bottom:1px solid #F3F4F6; }
table.sub-table tbody tr:nth-child(even) { background:#FAFAFA; }
table.sub-table tbody td { padding:7px 9px; font-size:9px; color:#374151; vertical-align:middle; }
.badge { display:inline-block; padding:2px 7px; border-radius:20px; font-size:7.5px; font-weight:700; }
.badge-active        { background:#DCFCE7; color:#15803D; }
.badge-expiring_soon { background:#FEF9C3; color:#A16207; }
.badge-expired       { background:#FEE2E2; color:#B91C1C; }
.badge-cat           { background:#EEF2FF; color:#4338CA; }
.name-cell   { font-weight:600; color:#111827; }
.vendor-cell { font-size:7.5px; color:#9CA3AF; }
.cost-cell   { font-weight:700; color:#111827; }
.cycle-tag   { font-size:7px; color:#9CA3AF; font-weight:400; }
.num-cell    { color:#9CA3AF; font-size:8px; }

/* ── Footer ── */
.footer { margin-top:18px; padding-top:10px; border-top:1px solid #E5E7EB; display:table; width:100%; }
.footer-left  { display:table-cell; font-size:8px; font-weight:700; color:#4F46E5; }
.footer-mid   { display:table-cell; text-align:center; font-size:7.5px; color:#9CA3AF; }
.footer-right { display:table-cell; text-align:right; font-size:7px; color:#D1D5DB; font-style:italic; }
</style>
</head>
<body>

{{-- ── Top accent bar ── --}}
<div class="accent-top"></div>

{{-- ── Header ── --}}
<div class="header">
    <div class="header-left">
        <div class="brand-row">
            @if($logoPath)
                <div class="brand-logo"><img src="{{ $logoPath }}" alt="{{ $appName }}"></div>
            @endif
            <div class="brand-text">
                <div class="brand-name">{{ $settings['company_name'] ?? $appName }}</div>
                <div class="brand-sub">{{ $settings['department_name'] ?? 'Management Portal' }}</div>
            </div>
        </div>
    </div>
    <div class="header-right">
        <div class="report-title">Subscriptions Report</div>
        <div class="report-conf">Confidential &mdash; Internal Use Only</div>
    </div>
</div>

{{-- ── Meta strip ── --}}
<div class="meta-strip">
    <div class="meta-cell">
        <div class="meta-label">Generated</div>
        <div class="meta-value">{{ $summary['generated_at'] }}</div>
    </div>
    <div class="meta-cell">
        <div class="meta-label">Total Records</div>
        <div class="meta-value">{{ $summary['total'] }} subscription{{ $summary['total'] !== 1 ? 's' : '' }}</div>
    </div>
    <div class="meta-cell">
        <div class="meta-label">Prepared By</div>
        <div class="meta-value">{{ auth()->user()->name }}</div>
    </div>
    <div class="meta-cell">
        <div class="meta-label">Department</div>
        <div class="meta-value">{{ $settings['department_name'] ?? ($settings['company_name'] ?? $appName) }}</div>
    </div>
</div>

<div class="body-wrap">

{{-- ── Overview section ── --}}
<div class="section-row">
    <div class="section-left">
        <div class="section-head">
            <div class="section-label">Overview</div>
            <div class="section-title">Subscriptions Summary</div>
        </div>
    </div>
</div>

<div class="cards-grid">
    <div class="cards-row">
        <div class="card-cell">
            <div class="card indigo">
                <div class="card-label">Total</div>
                <div class="card-value">{{ $summary['total'] }}</div>
                <div class="card-sub">All subscriptions</div>
            </div>
        </div>
        <div class="card-cell">
            <div class="card green">
                <div class="card-label">Active</div>
                <div class="card-value">{{ $summary['active'] }}</div>
                <div class="card-sub">Currently running</div>
            </div>
        </div>
        <div class="card-cell">
            <div class="card amber">
                <div class="card-label">Expiring Soon</div>
                <div class="card-value">{{ $summary['expiring_soon'] }}</div>
                <div class="card-sub">Within 30 days</div>
            </div>
        </div>
    </div>
    <div class="cards-row">
        <div class="card-cell">
            <div class="card red">
                <div class="card-label">Expired</div>
                <div class="card-value">{{ $summary['expired'] }}</div>
                <div class="card-sub">Action needed</div>
            </div>
        </div>
        <div class="card-cell">
            <div class="card blue">
                <div class="card-label">Monthly Spend</div>
                <div class="card-value" style="font-size:15px;">BHD {{ number_format($summary['monthly_total'], 3) }}</div>
                <div class="card-sub">All billing cycles</div>
            </div>
        </div>
        <div class="card-cell">
            <div class="card violet">
                <div class="card-label">Annual Spend</div>
                <div class="card-value" style="font-size:15px;">BHD {{ number_format($summary['annual_total'], 3) }}</div>
                <div class="card-sub">Projected yearly</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Financials section ── --}}
<div class="section-row">
    <div class="section-left">
        <div class="section-head">
            <div class="section-label">Financials</div>
            <div class="section-title">Cost Breakdown &amp; Category Distribution</div>
        </div>
    </div>
</div>

@php
    $cycleGroups = [
        ['label'=>'Monthly',  'key'=>'monthly',   'color'=>'#6366F1', 'bg'=>'#EEF2FF', 'col'=>$all->filter(fn($s)=>$s->billing_cycle==='monthly')],
        ['label'=>'Annual',   'key'=>'annual',    'color'=>'#16A34A', 'bg'=>'#ECFDF5', 'col'=>$all->filter(fn($s)=>$s->billing_cycle==='annual')],
        ['label'=>'Quarterly','key'=>'quarterly', 'color'=>'#D97706', 'bg'=>'#FFFBEB', 'col'=>$all->filter(fn($s)=>$s->billing_cycle==='quarterly')],
        ['label'=>'One-time', 'key'=>'one_time',  'color'=>'#0EA5E9', 'bg'=>'#F0F9FF', 'col'=>$all->filter(fn($s)=>$s->billing_cycle==='one_time')],
    ];
    $maxCycleAnnual = collect($cycleGroups)->map(fn($g)=>$g['col']->sum(fn($s)=>$s->annual_cost))->max() ?: 1;
    $maxCatAnnual   = $summary['by_category']->max('annual') ?: 1;
    $catColors = ['#4F46E5','#16A34A','#D97706','#DC2626','#0EA5E9','#7C3AED','#EC4899'];
@endphp

<div class="summary-row">

    {{-- ── By Billing Cycle ── --}}
    <div class="summary-col" style="width:44%;">
        <div class="box">
            <div class="box-title">By Billing Cycle</div>

            @foreach($cycleGroups as $grp)
            @if($grp['col']->count())
            @php $grpAnnual = $grp['col']->sum(fn($s)=>$s->annual_cost); $pct = max(4, round($grpAnnual/$maxCycleAnnual*100)); @endphp
            <div class="cycle-item">
                <div class="cycle-dot-cell"><div class="cycle-dot" style="background:{{ $grp['color'] }};"></div></div>
                <div class="cycle-name-cell">
                    {{ $grp['label'] }}<span class="cycle-count">({{ $grp['col']->count() }})</span>
                </div>
                <div class="cycle-cost-cell">
                    BHD {{ number_format($grpAnnual, 3) }} <span class="cycle-cost-sub">/yr</span>
                </div>
            </div>
            {{-- Dompdf-safe bar using table cell widths --}}
            <table class="bar-table" style="margin-bottom:3px;"><tr>
                <td class="bar-filled" style="width:{{ $pct }}%;background:{{ $grp['color'] }};opacity:.7;"></td>
                <td class="bar-empty"  style="width:{{ 100-$pct }}%;"></td>
            </tr></table>
            @endif
            @endforeach

            {{-- Totals highlight --}}
            <table class="totals-box" style="margin-top:12px;">
                <tr>
                    <td>
                        <div class="totals-lbl">Total Annual</div>
                        <div class="totals-sub-lbl" style="margin-top:2px;">Monthly equivalent</div>
                    </td>
                    <td style="text-align:right;">
                        <div class="totals-val">BHD {{ number_format($summary['annual_total'], 3) }}</div>
                        <div class="totals-sub-val">BHD {{ number_format($summary['monthly_total'], 3) }}/mo</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ── By Category ── --}}
    <div class="summary-col">
        <div class="box">
            <div class="box-title">By Category</div>

            @foreach($summary['by_category'] as $ci => $cat)
            @php
                $catColor = $catColors[$ci % count($catColors)];
                $catPct   = max(4, round($cat['annual'] / $maxCatAnnual * 100));
            @endphp
            <div class="cat-item">
                <div class="cat-header">
                    <div class="cat-name">
                        {{ $cat['label'] }}<span class="cat-count">({{ $cat['count'] }})</span>
                    </div>
                    <div class="cat-amt">BHD {{ number_format($cat['annual'], 3) }}<span style="font-size:7px;font-weight:400;color:#9CA3AF;">/yr</span></div>
                </div>
                <table class="bar-table"><tr>
                    <td class="bar-filled" style="width:{{ $catPct }}%;background:{{ $catColor }};"></td>
                    <td class="bar-empty"  style="width:{{ 100-$catPct }}%;"></td>
                </tr></table>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ── Directory section ── --}}
<div class="section-row">
    <div class="section-left">
        <div class="section-head">
            <div class="section-label">Directory</div>
            <div class="section-title">All Subscriptions</div>
        </div>
    </div>
    <div class="section-right">
        <span class="count-pill">{{ $summary['total'] }} record{{ $summary['total'] !== 1 ? 's' : '' }}</span>
    </div>
</div>

<table class="sub-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Category</th>
            <th>Type</th>
            <th>Cost</th>
            <th>Annual (BHD)</th>
            <th>Renewal</th>
            <th>Status</th>
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
            <td class="num-cell">{{ $i + 1 }}</td>
            <td>
                <span class="name-cell">{{ $sub->name }}</span>
                @if($sub->vendor)<br><span class="vendor-cell">{{ $sub->vendor }}</span>@endif
            </td>
            <td><span class="badge badge-cat">{{ $catNames[$sub->category] ?? $sub->category }}</span></td>
            <td style="font-size:8px;color:#6B7280;">
                @if($sub->type === 'per_seat') Per Seat
                @elseif($sub->type === 'site_license') Site License
                @else Shared @endif
            </td>
            <td class="cost-cell">
                {{ $sub->currency }} {{ number_format($sub->cost, 3) }}<br>
                <span class="cycle-tag">{{ ucfirst(str_replace('_',' ',$sub->billing_cycle)) }}</span>
            </td>
            <td class="cost-cell">{{ number_format($sub->annual_cost, 3) }}</td>
            <td style="font-size:8.5px;">
                {{ $renewalLabel }}
                @if($daysLabel)<br><span style="font-size:7.5px;color:{{ $sub->status==='expired'?'#DC2626':($sub->status==='expiring_soon'?'#D97706':'#9CA3AF') }};">{{ $daysLabel }}</span>@endif
            </td>
            <td><span class="badge badge-{{ $sub->status }}">
                @if($sub->status==='active') Active
                @elseif($sub->status==='expiring_soon') Expiring
                @else Expired @endif
            </span></td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ── Signature ── --}}
<div style="margin-top:24px; display:table; width:100%; border-collapse:separate; border-spacing:20px 0;">
    <div style="display:table-cell; vertical-align:bottom; width:33%;">
        <div style="height:28px;"></div>
        <div style="border-bottom:1.5px solid #374151; margin-bottom:5px;"></div>
        <div style="font-size:7.5px; color:#6B7280;">Prepared by</div>
        <div style="font-size:8px; font-weight:700; color:#111827; margin-top:2px;">{{ auth()->user()->name }}</div>
    </div>
    <div style="display:table-cell; vertical-align:bottom; width:33%;">
        <div style="height:28px;"></div>
        <div style="border-bottom:1.5px solid #374151; margin-bottom:5px;"></div>
        <div style="font-size:7.5px; color:#6B7280;">Reviewed by</div>
        <div style="font-size:8px; font-weight:700; color:#111827; margin-top:2px;">&nbsp;</div>
    </div>
    <div style="display:table-cell; vertical-align:bottom; width:33%;">
        <div style="height:28px;"></div>
        <div style="border-bottom:1.5px solid #374151; margin-bottom:5px;"></div>
        <div style="font-size:7.5px; color:#6B7280;">Approved by</div>
        <div style="font-size:8px; font-weight:700; color:#111827; margin-top:2px;">&nbsp;</div>
    </div>
</div>

{{-- ── Footer ── --}}
<div class="footer">
    <div class="footer-left">{{ $appName }} &mdash; Subscriptions &amp; Licenses</div>
    <div class="footer-mid">Generated {{ $summary['generated_at'] }} &bull; {{ auth()->user()->name }}</div>
    <div class="footer-right">Confidential</div>
</div>

</div>{{-- /body-wrap --}}
</body>
</html>

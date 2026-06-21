<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
@page { size: A4 portrait; margin: 10mm 12mm 10mm 12mm; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size:9px; color:#1F2937; background:#fff; }

/* ── Top accent ── */
.accent-top { height:5px; background:#4F46E5; width:100%; }

/* ── Header ── */
.header { display:table; width:100%; padding:12px 0 10px; border-bottom:1px solid #E5E7EB; }
.header-left  { display:table-cell; vertical-align:middle; }
.header-right { display:table-cell; vertical-align:middle; text-align:right; width:45%; }
.brand-row  { display:table; }
.brand-logo { display:table-cell; vertical-align:middle; padding-right:10px; }
.brand-logo img { height:38px; max-width:52px; object-fit:contain; }
.brand-text { display:table-cell; vertical-align:middle; }
.brand-name { font-size:14px; font-weight:800; color:#1E1B4B; }
.brand-sub  { font-size:8px; color:#9CA3AF; margin-top:1px; }
.rep-title  { font-size:18px; font-weight:800; color:#4F46E5; }
.rep-conf   { font-size:7.5px; color:#9CA3AF; margin-top:3px; }

/* ── Meta strip ── */
.meta-strip { display:table; width:100%; background:#FAFAFA; border-bottom:1px solid #E5E7EB; margin-bottom:12px; }
.meta-cell  { display:table-cell; padding:7px 12px 7px 0; vertical-align:middle; border-right:1px solid #E5E7EB; }
.meta-cell:first-child { padding-left:0; }
.meta-cell:last-child  { border-right:none; }
.meta-lbl { font-size:6.5px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#9CA3AF; margin-bottom:2px; }
.meta-val { font-size:9px; font-weight:600; color:#111827; }

/* ── Section header ── */
.sec-row   { display:table; width:100%; margin-bottom:8px; }
.sec-left  { display:table-cell; vertical-align:bottom; }
.sec-right { display:table-cell; vertical-align:bottom; text-align:right; }
.sec-head  { padding-left:8px; border-left:3px solid #4F46E5; }
.sec-lbl   { font-size:7px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#6366F1; }
.sec-title { font-size:10px; font-weight:800; color:#1E1B4B; margin-top:1px; }
.count-pill { display:inline-block; background:#EEF2FF; color:#4338CA; border-radius:20px; padding:2px 9px; font-size:7.5px; font-weight:700; }

/* ── Stat cards (3-col grid) ── */
.cards-grid { display:table; width:100%; border-collapse:separate; border-spacing:0; margin-bottom:10px; }
.cards-row  { display:table-row; }
.card-cell  { display:table-cell; padding:3px; width:33.33%; }
.card { border-radius:8px; padding:10px 12px; border-left:4px solid transparent; }
.card.indigo { background:#EEF2FF; border-color:#4F46E5; }
.card.green  { background:#ECFDF5; border-color:#16A34A; }
.card.amber  { background:#FFFBEB; border-color:#D97706; }
.card.red    { background:#FEF2F2; border-color:#DC2626; }
.card.blue   { background:#EFF6FF; border-color:#2563EB; }
.card.violet { background:#F5F3FF; border-color:#7C3AED; }
.card-lbl { font-size:7px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#6B7280; margin-bottom:4px; }
.card-val { font-size:20px; font-weight:800; line-height:1; }
.card.indigo .card-val { color:#4F46E5; }
.card.green  .card-val { color:#16A34A; }
.card.amber  .card-val { color:#D97706; }
.card.red    .card-val { color:#DC2626; }
.card.blue   .card-val { color:#2563EB; }
.card.violet .card-val { color:#7C3AED; }
.card-sub { font-size:7px; color:#9CA3AF; margin-top:4px; }

/* ── Financials two-col
   Key: style the table-cell (.fin-col) directly so both cells
   share the same height automatically — no inner div height issue ── */
.fin-row { display:table; width:100%; border-collapse:separate; border-spacing:8px 0; margin-bottom:10px; }
.fin-col {
    display:table-cell; vertical-align:top; width:50%;
    border:1px solid #E5E7EB; border-radius:8px; padding:12px 14px; background:#fff;
}
.fin-col-title { font-size:7.5px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#6366F1; margin-bottom:10px; padding-bottom:7px; border-bottom:2px solid #EEF2FF; }

/* cycle rows */
.cy-row  { display:table; width:100%; margin-bottom:7px; }
.cy-dot  { display:table-cell; width:10px; vertical-align:middle; padding-right:6px; }
.cy-dot span { display:block; width:8px; height:8px; border-radius:50%; }
.cy-name { display:table-cell; vertical-align:middle; font-size:8px; color:#374151; }
.cy-cnt  { font-size:6.5px; color:#9CA3AF; margin-left:2px; }
.cy-cost { display:table-cell; vertical-align:middle; text-align:right; font-size:9.5px; font-weight:700; color:#111827; white-space:nowrap; }
.cy-sub  { font-size:6.5px; font-weight:400; color:#9CA3AF; }
.bar-t { width:100%; border-collapse:collapse; margin-top:3px; margin-bottom:2px; }
.bar-f { height:5px; }
.bar-e { height:5px; background:#F3F4F6; }

/* totals */
.totals { background:#F5F3FF; border-radius:6px; padding:8px 10px; margin-top:8px; display:table; width:100%; }
.totals td { display:table-cell; vertical-align:middle; }
.tot-lbl { font-size:7.5px; color:#6B7280; }
.tot-val { font-size:12px; font-weight:800; color:#4F46E5; text-align:right; }
.tot-sub-lbl { font-size:7px; color:#9CA3AF; margin-top:2px; }
.tot-sub-val { font-size:8px; font-weight:600; color:#374151; text-align:right; margin-top:2px; }

/* cat rows */
.cat-row { margin-bottom:8px; }
.cat-hdr { display:table; width:100%; margin-bottom:3px; }
.cat-nm  { display:table-cell; font-size:8px; font-weight:600; color:#374151; }
.cat-cnt { font-size:6.5px; color:#9CA3AF; margin-left:2px; font-weight:400; }
.cat-am  { display:table-cell; text-align:right; font-size:8px; font-weight:700; color:#4F46E5; white-space:nowrap; }

/* ── Main table ── */
table.sub-tbl { width:100%; border-collapse:collapse; }
table.sub-tbl thead tr { background:#1E1B4B; }
table.sub-tbl thead th { color:rgba(255,255,255,.9); font-size:7px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding:6px 8px; text-align:left; }
table.sub-tbl tbody tr { border-bottom:1px solid #F3F4F6; }
table.sub-tbl tbody tr:nth-child(even) { background:#FAFAFA; }
table.sub-tbl tbody td { padding:6px 8px; font-size:8.5px; color:#374151; vertical-align:middle; }
.badge { display:inline-block; padding:2px 6px; border-radius:20px; font-size:7px; font-weight:700; }
.badge-active        { background:#DCFCE7; color:#15803D; }
.badge-expiring_soon { background:#FEF9C3; color:#A16207; }
.badge-expired       { background:#FEE2E2; color:#B91C1C; }
.badge-cat           { background:#EEF2FF; color:#4338CA; }
.nm-cell  { font-weight:600; color:#111827; }
.vnd-cell { font-size:7px; color:#9CA3AF; }
.cst-cell { font-weight:700; color:#111827; }
.cyc-tag  { font-size:6.5px; color:#9CA3AF; font-weight:400; }
.num-cell { color:#9CA3AF; font-size:7.5px; }

/* ── Signature ── */
.sig-wrap { margin-top:20px; display:table; width:100%; border-collapse:separate; border-spacing:16px 0; }
.sig-col  { display:table-cell; vertical-align:bottom; width:33%; }
.sig-line { height:24px; }
.sig-rule { border-bottom:1.5px solid #374151; margin-bottom:4px; }
.sig-lbl  { font-size:7px; color:#6B7280; }
.sig-name { font-size:7.5px; font-weight:700; color:#111827; margin-top:2px; }

/* ── Footer ── */
.footer { margin-top:12px; padding-top:8px; border-top:1px solid #E5E7EB; display:table; width:100%; }
.ft-l   { display:table-cell; font-size:7.5px; font-weight:700; color:#4F46E5; }
.ft-m   { display:table-cell; text-align:center; font-size:7px; color:#9CA3AF; }
.ft-r   { display:table-cell; text-align:right; font-size:7px; color:#D1D5DB; font-style:italic; }
</style>
</head>
<body>

<div class="accent-top"></div>

{{-- Header --}}
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
        <div class="rep-title">Subscriptions Report</div>
        <div class="rep-conf">Confidential &mdash; Internal Use Only</div>
    </div>
</div>

{{-- Meta strip --}}
<div class="meta-strip">
    <div class="meta-cell">
        <div class="meta-lbl">Generated</div>
        <div class="meta-val">{{ $summary['generated_at'] }}</div>
    </div>
    <div class="meta-cell">
        <div class="meta-lbl">Total Records</div>
        <div class="meta-val">{{ $summary['total'] }} subscription{{ $summary['total'] !== 1 ? 's' : '' }}</div>
    </div>
    <div class="meta-cell">
        <div class="meta-lbl">Prepared By</div>
        <div class="meta-val">{{ auth()->user()->name }}</div>
    </div>
    <div class="meta-cell">
        <div class="meta-lbl">Department</div>
        <div class="meta-val">{{ $settings['department_name'] ?? ($settings['company_name'] ?? $appName) }}</div>
    </div>
</div>

{{-- Overview --}}
<div class="sec-row">
    <div class="sec-left"><div class="sec-head"><div class="sec-lbl">Overview</div><div class="sec-title">Subscriptions Summary</div></div></div>
</div>
<div class="cards-grid">
    <div class="cards-row">
        <div class="card-cell"><div class="card indigo"><div class="card-lbl">Total</div><div class="card-val">{{ $summary['total'] }}</div><div class="card-sub">All subscriptions</div></div></div>
        <div class="card-cell"><div class="card green"><div class="card-lbl">Active</div><div class="card-val">{{ $summary['active'] }}</div><div class="card-sub">Currently running</div></div></div>
        <div class="card-cell"><div class="card amber"><div class="card-lbl">Expiring Soon</div><div class="card-val">{{ $summary['expiring_soon'] }}</div><div class="card-sub">Within 30 days</div></div></div>
    </div>
    <div class="cards-row">
        <div class="card-cell"><div class="card red"><div class="card-lbl">Expired</div><div class="card-val">{{ $summary['expired'] }}</div><div class="card-sub">Action needed</div></div></div>
        <div class="card-cell"><div class="card blue"><div class="card-lbl">Monthly Spend</div><div class="card-val" style="font-size:13px;">BHD {{ number_format($summary['monthly_total'], 3) }}</div><div class="card-sub">All billing cycles</div></div></div>
        <div class="card-cell"><div class="card violet"><div class="card-lbl">Annual Spend</div><div class="card-val" style="font-size:13px;">BHD {{ number_format($summary['annual_total'], 3) }}</div><div class="card-sub">Projected yearly</div></div></div>
    </div>
</div>

{{-- Financials --}}
<div class="sec-row">
    <div class="sec-left"><div class="sec-head"><div class="sec-lbl">Financials</div><div class="sec-title">Cost Breakdown &amp; Category Distribution</div></div></div>
</div>

@php
    $cycleGroups = [
        ['label'=>'Monthly',  'color'=>'#6366F1', 'col'=>$all->filter(fn($s)=>$s->billing_cycle==='monthly')],
        ['label'=>'Annual',   'color'=>'#16A34A', 'col'=>$all->filter(fn($s)=>$s->billing_cycle==='annual')],
        ['label'=>'Quarterly','color'=>'#D97706', 'col'=>$all->filter(fn($s)=>$s->billing_cycle==='quarterly')],
        ['label'=>'One-time', 'color'=>'#0EA5E9', 'col'=>$all->filter(fn($s)=>$s->billing_cycle==='one_time')],
    ];
    $maxCy  = collect($cycleGroups)->map(fn($g)=>$g['col']->sum(fn($s)=>$s->annual_cost))->max() ?: 1;
    $maxCat = $summary['by_category']->max('annual') ?: 1;
    $catPalette = ['#4F46E5','#16A34A','#D97706','#DC2626','#0EA5E9','#7C3AED','#EC4899'];
@endphp

<div class="fin-row">
    {{-- By Billing Cycle --}}
    <div class="fin-col">
        <div class="fin-col-title">By Billing Cycle</div>
        @foreach($cycleGroups as $grp)
        @if($grp['col']->count())
        @php $amt = $grp['col']->sum(fn($s)=>$s->annual_cost); $pct = max(5,round($amt/$maxCy*100)); @endphp
        <div class="cy-row">
            <div class="cy-dot"><span style="background:{{ $grp['color'] }};"></span></div>
            <div class="cy-name">{{ $grp['label'] }}<span class="cy-cnt">({{ $grp['col']->count() }})</span></div>
            <div class="cy-cost">BHD {{ number_format($amt,3) }} <span class="cy-sub">/yr</span></div>
        </div>
        <table class="bar-t"><tr>
            <td class="bar-f" style="width:{{ $pct }}%;background:{{ $grp['color'] }};opacity:.65;"></td>
            <td class="bar-e" style="width:{{ 100-$pct }}%;"></td>
        </tr></table>
        @endif
        @endforeach
        <table class="totals"><tr>
            <td><div class="tot-lbl">Total Annual</div><div class="tot-sub-lbl">Monthly equivalent</div></td>
            <td><div class="tot-val">BHD {{ number_format($summary['annual_total'],3) }}</div><div class="tot-sub-val">BHD {{ number_format($summary['monthly_total'],3) }}/mo</div></td>
        </tr></table>
    </div>

    {{-- By Category --}}
    <div class="fin-col">
        <div class="fin-col-title">By Category</div>
        @foreach($summary['by_category'] as $ci => $cat)
        @php $cc = $catPalette[$ci % count($catPalette)]; $cp = max(5,round($cat['annual']/$maxCat*100)); @endphp
        <div class="cat-row">
            <div class="cat-hdr">
                <div class="cat-nm">{{ $cat['label'] }}<span class="cat-cnt">({{ $cat['count'] }})</span></div>
                <div class="cat-am">BHD {{ number_format($cat['annual'],3) }}<span style="font-size:6.5px;font-weight:400;color:#9CA3AF;">/yr</span></div>
            </div>
            <table class="bar-t"><tr>
                <td class="bar-f" style="width:{{ $cp }}%;background:{{ $cc }};"></td>
                <td class="bar-e" style="width:{{ 100-$cp }}%;"></td>
            </tr></table>
        </div>
        @endforeach
    </div>
</div>

{{-- Directory --}}
<div class="sec-row">
    <div class="sec-left"><div class="sec-head"><div class="sec-lbl">Directory</div><div class="sec-title">All Subscriptions</div></div></div>
    <div class="sec-right"><span class="count-pill">{{ $summary['total'] }} record{{ $summary['total'] !== 1 ? 's' : '' }}</span></div>
</div>

<table class="sub-tbl">
    <thead>
        <tr>
            <th>#</th><th>Name</th><th>Category</th><th>Type</th>
            <th>Cost</th><th>Annual (BHD)</th><th>Renewal</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($all as $i => $sub)
        @php
            $rl = $sub->renewal_date ? $sub->renewal_date->format('d M Y') : '—';
            $dy = $sub->days_until_renewal;
            $dl = $sub->renewal_date ? ($dy < 0 ? abs($dy).'d overdue' : ($dy === 0 ? 'Today' : $dy.'d left')) : '';
        @endphp
        <tr>
            <td class="num-cell">{{ $i+1 }}</td>
            <td><span class="nm-cell">{{ $sub->name }}</span>@if($sub->vendor)<br><span class="vnd-cell">{{ $sub->vendor }}</span>@endif</td>
            <td><span class="badge badge-cat">{{ $catNames[$sub->category] ?? $sub->category }}</span></td>
            <td style="font-size:7.5px;color:#6B7280;">
                @if($sub->type==='per_seat') Per Seat
                @elseif($sub->type==='site_license') Site License
                @else Shared @endif
            </td>
            <td class="cst-cell">{{ $sub->currency }} {{ number_format($sub->cost,3) }}<br><span class="cyc-tag">{{ ucfirst(str_replace('_',' ',$sub->billing_cycle)) }}</span></td>
            <td class="cst-cell">{{ number_format($sub->annual_cost,3) }}</td>
            <td style="font-size:8px;">{{ $rl }}@if($dl)<br><span style="font-size:7px;color:{{ $sub->status==='expired'?'#DC2626':($sub->status==='expiring_soon'?'#D97706':'#9CA3AF') }};">{{ $dl }}</span>@endif</td>
            <td><span class="badge badge-{{ $sub->status }}">
                @if($sub->status==='active') Active
                @elseif($sub->status==='expiring_soon') Expiring
                @else Expired @endif
            </span></td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Signature --}}
<div class="sig-wrap">
    <div class="sig-col"><div class="sig-line"></div><div class="sig-rule"></div><div class="sig-lbl">Prepared by</div><div class="sig-name">{{ auth()->user()->name }}</div></div>
    <div class="sig-col"><div class="sig-line"></div><div class="sig-rule"></div><div class="sig-lbl">Reviewed by</div><div class="sig-name">&nbsp;</div></div>
    <div class="sig-col"><div class="sig-line"></div><div class="sig-rule"></div><div class="sig-lbl">Approved by</div><div class="sig-name">&nbsp;</div></div>
</div>

{{-- Footer --}}
<div class="footer">
    <div class="ft-l">{{ $appName }} &mdash; Subscriptions &amp; Licenses</div>
    <div class="ft-m">Generated {{ $summary['generated_at'] }} &bull; {{ auth()->user()->name }}</div>
    <div class="ft-r">Confidential</div>
</div>

</body>
</html>

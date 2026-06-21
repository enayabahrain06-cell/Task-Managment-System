<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
@page { size: A4 portrait; margin: 0; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size:8.5px; color:#1F2937; background:#fff; }

/* ── Top accent bar ── */
.accent-top { height:5px; background:#4F46E5; }

/* ── Header (light, matches Reports style) ── */
.header {
    background:#fff;
    padding:16px 28px 14px;
    display:table;
    width:100%;
    border-bottom:1px solid #E5E7EB;
}
.header-left  { display:table-cell; vertical-align:middle; }
.header-right { display:table-cell; vertical-align:middle; text-align:right; }
.brand-row    { display:table; }
.brand-logo   { display:table-cell; vertical-align:middle; padding-right:11px; }
.brand-logo img { height:40px; max-width:56px; object-fit:contain; }
.brand-text   { display:table-cell; vertical-align:middle; }
.brand-name   { font-size:15px; font-weight:800; color:#1E1B4B; letter-spacing:-.3px; }
.brand-sub    { font-size:8.5px; color:#9CA3AF; margin-top:2px; }
.report-title { font-size:20px; font-weight:800; color:#4F46E5; letter-spacing:-.4px; }
.report-conf  { font-size:8px; color:#9CA3AF; margin-top:4px; }

/* ── Meta strip ── */
.meta-strip { display:table; width:100%; border-bottom:1px solid #E5E7EB; background:#FAFAFA; }
.meta-cell  { display:table-cell; padding:8px 28px; vertical-align:middle; border-right:1px solid #E5E7EB; }
.meta-cell:last-child { border-right:none; }
.meta-label { font-size:6.5px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:#9CA3AF; margin-bottom:3px; }
.meta-value { font-size:9px; font-weight:600; color:#111827; }

/* ── Body ── */
.body-wrap { padding:14px 28px; }

/* ── Section headers ── */
.section-row   { display:table; width:100%; margin-bottom:9px; }
.section-left  { display:table-cell; vertical-align:bottom; }
.section-right { display:table-cell; vertical-align:bottom; text-align:right; }
.section-head  { padding-left:9px; border-left:3px solid #4F46E5; }
.section-label { font-size:7px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#6366F1; margin-bottom:1px; }
.section-title { font-size:10.5px; font-weight:800; color:#1E1B4B; }
.count-pill {
    display:inline-block; background:#EEF2FF; color:#4338CA;
    border-radius:20px; padding:2px 9px; font-size:7.5px; font-weight:700;
}

/* ── Stat cards — 5 across (landscape) ── */
.cards-grid { display:table; width:100%; border-collapse:separate; border-spacing:0; margin-bottom:14px; }
.cards-row  { display:table-row; }
.card-cell  { display:table-cell; padding:3px; width:20%; }
.card {
    border-radius:9px; padding:12px 14px;
    border-left:4px solid transparent; vertical-align:top;
}
.card.indigo { background:#EEF2FF; border-color:#4F46E5; }
.card.green  { background:#ECFDF5; border-color:#16A34A; }
.card.amber  { background:#FFFBEB; border-color:#D97706; }
.card.red    { background:#FEF2F2; border-color:#DC2626; }
.card.blue   { background:#EFF6FF; border-color:#2563EB; }
.card-label { font-size:7px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#6B7280; margin-bottom:5px; }
.card-value { font-size:22px; font-weight:800; line-height:1; }
.card.indigo .card-value { color:#4F46E5; }
.card.green  .card-value { color:#16A34A; }
.card.amber  .card-value { color:#D97706; }
.card.red    .card-value { color:#DC2626; }
.card.blue   .card-value { color:#2563EB; }
.card-sub { font-size:7px; color:#9CA3AF; margin-top:4px; }

/* ── Distribution two-col ── */
.dist-row { display:table; width:100%; border-collapse:separate; border-spacing:10px 0; margin-bottom:14px; }
.dist-col { display:table-cell; vertical-align:top; }
.box { border:1px solid #E5E7EB; border-radius:9px; padding:12px 14px; }
.box-title { font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#374151; margin-bottom:8px; padding-bottom:6px; border-bottom:1px solid #F3F4F6; }

/* dist table inside box */
.dist-table { width:100%; border-collapse:collapse; }
.dist-table td { padding:4px 0; font-size:8.5px; color:#374151; border-bottom:1px solid #F9FAFB; vertical-align:middle; }
.dist-table td:last-child { text-align:right; font-weight:700; color:#4F46E5; }
.dist-table tr:last-child td { border-bottom:none; }
.bar-track { background:#F3F4F6; border-radius:3px; height:4px; margin-top:3px; }
.bar-fill  { background:#4F46E5; height:4px; border-radius:3px; }

/* ── Main table ── */
table.main-table { width:100%; border-collapse:collapse; }
table.main-table thead tr { background:#1E1B4B; }
table.main-table thead th {
    color:rgba(255,255,255,.9); font-size:6.5px; font-weight:700;
    text-transform:uppercase; letter-spacing:.05em; padding:6px 6px; text-align:left;
}
table.main-table tbody tr { border-bottom:1px solid #F3F4F6; }
table.main-table tbody tr:nth-child(even) { background:#FAFAFA; }
table.main-table tbody td { padding:5px 6px; font-size:8px; color:#374151; vertical-align:middle; }
.badge { display:inline-block; padding:2px 6px; border-radius:20px; font-size:7px; font-weight:700; }
.badge-active        { background:#DCFCE7; color:#15803D; }
.badge-expiring_soon { background:#FEF9C3; color:#A16207; }
.badge-expired       { background:#FEE2E2; color:#B91C1C; }
.domain-name { font-weight:700; color:#111827; }
.host-sub    { font-size:7.5px; color:#9CA3AF; }
.num-cell    { color:#9CA3AF; font-size:7.5px; }
.check-yes   { color:#16A34A; font-weight:700; }
.check-no    { color:#D1D5DB; }

/* ── Footer ── */
.footer { margin-top:14px; padding-top:9px; border-top:1px solid #E5E7EB; display:table; width:100%; }
.footer-left  { display:table-cell; font-size:7.5px; font-weight:700; color:#4F46E5; }
.footer-mid   { display:table-cell; text-align:center; font-size:7px; color:#9CA3AF; }
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
        <div class="report-title">Domain Register</div>
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
        <div class="meta-value">{{ $summary['total'] }} domain{{ $summary['total'] !== 1 ? 's' : '' }}</div>
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

{{-- ── Overview stat cards ── --}}
<div class="section-row">
    <div class="section-left">
        <div class="section-head">
            <div class="section-label">Overview</div>
            <div class="section-title">Domain Summary</div>
        </div>
    </div>
</div>

<div class="cards-grid">
    <div class="cards-row">
        <div class="card-cell">
            <div class="card indigo">
                <div class="card-label">Total</div>
                <div class="card-value">{{ $summary['total'] }}</div>
                <div class="card-sub">All domains</div>
            </div>
        </div>
        <div class="card-cell">
            <div class="card green">
                <div class="card-label">Active</div>
                <div class="card-value">{{ $summary['active'] }}</div>
                <div class="card-sub">Currently live</div>
            </div>
        </div>
        <div class="card-cell">
            <div class="card amber">
                <div class="card-label">Expiring Soon</div>
                <div class="card-value">{{ $summary['expiring_soon'] }}</div>
                <div class="card-sub">Within 30 days</div>
            </div>
        </div>
        <div class="card-cell">
            <div class="card red">
                <div class="card-label">Expired</div>
                <div class="card-value">{{ $summary['expired'] }}</div>
                <div class="card-sub">Action needed</div>
            </div>
        </div>
        <div class="card-cell">
            <div class="card blue">
                <div class="card-label">Annual Spend</div>
                <div class="card-value" style="font-size:14px;">BHD {{ number_format($summary['annual_total'], 3) }}</div>
                <div class="card-sub">Total yearly cost</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Distribution: By Registrar + By Customer ── --}}
@if($summary['by_registrar']->count() || $summary['by_customer']->count())
<div class="section-row">
    <div class="section-left">
        <div class="section-head">
            <div class="section-label">Distribution</div>
            <div class="section-title">By Registrar &amp; Customer</div>
        </div>
    </div>
</div>

<div class="dist-row">
    @if($summary['by_registrar']->count())
    <div class="dist-col">
        <div class="box">
            <div class="box-title">By Registrar</div>
            @php $maxReg = $summary['by_registrar']->max('count') ?: 1; @endphp
            <table class="dist-table">
                @foreach($summary['by_registrar'] as $row)
                <tr>
                    <td>
                        {{ $row['label'] }}
                        <div class="bar-track"><div class="bar-fill" style="width:{{ round($row['count']/$maxReg*100) }}%;"></div></div>
                    </td>
                    <td>{{ $row['count'] }}</td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
    @endif

    @if($summary['by_customer']->count())
    <div class="dist-col">
        <div class="box">
            <div class="box-title">By Customer</div>
            @php $maxCust = $summary['by_customer']->max('count') ?: 1; @endphp
            <table class="dist-table">
                @foreach($summary['by_customer'] as $row)
                <tr>
                    <td>
                        {{ $row['label'] }}
                        <div class="bar-track"><div class="bar-fill" style="width:{{ round($row['count']/$maxCust*100) }}%;"></div></div>
                    </td>
                    <td>{{ $row['count'] }}</td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
    @endif
</div>
@endif

{{-- ── All Domains table ── --}}
<div class="section-row">
    <div class="section-left">
        <div class="section-head">
            <div class="section-label">Directory</div>
            <div class="section-title">All Domains</div>
        </div>
    </div>
    <div class="section-right">
        <span class="count-pill">{{ $summary['total'] }} record{{ $summary['total'] !== 1 ? 's' : '' }}</span>
    </div>
</div>

<table class="main-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Domain</th>
            <th>Customer</th>
            <th>Registrar</th>
            <th>Responsible</th>
            <th>Bill To</th>
            <th>Expires</th>
            <th>Cost (BHD/yr)</th>
            <th>Auto Renew</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($all as $i => $d)
        @php
            $days = $d->days_until_expiry;
            $daysLabel = $d->expires_at
                ? ($days < 0 ? abs($days).'d overdue' : ($days === 0 ? 'Today' : $days.'d left'))
                : '';
        @endphp
        <tr>
            <td class="num-cell">{{ $i + 1 }}</td>
            <td>
                <span class="domain-name">{{ $d->domain }}</span>
                @if($d->hosting_provider)<br><span class="host-sub">{{ $d->hosting_provider }}</span>@endif
            </td>
            <td>{{ $d->customer?->name ?? '—' }}</td>
            <td>{{ $d->registrar ?? '—' }}</td>
            <td>{{ $d->responsibleUser?->name ?? '—' }}</td>
            <td>{{ $d->billing_to ?? '—' }}</td>
            <td style="font-size:8px;">
                {{ $d->expires_at ? $d->expires_at->format('d M Y') : '—' }}
                @if($daysLabel)<br><span style="font-size:7px;color:{{ $d->status==='expired'?'#DC2626':($d->status==='expiring_soon'?'#D97706':'#9CA3AF') }};">{{ $daysLabel }}</span>@endif
            </td>
            <td style="font-weight:700;color:#111827;">{{ number_format($d->annual_cost, 3) }}</td>
            <td class="{{ $d->auto_renew ? 'check-yes' : 'check-no' }}">{{ $d->auto_renew ? '✓' : '—' }}</td>
            <td><span class="badge badge-{{ $d->status }}">{{ ucfirst(str_replace('_',' ',$d->status)) }}</span></td>
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
    <div class="footer-left">{{ $appName }} &mdash; Domain Register</div>
    <div class="footer-mid">Generated {{ $summary['generated_at'] }} &bull; {{ auth()->user()->name }}</div>
    <div class="footer-right">Confidential</div>
</div>

</div>{{-- /body-wrap --}}
</body>
</html>

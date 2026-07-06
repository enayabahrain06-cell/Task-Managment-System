<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
@php
    $primaryColor = $settings['primary_color'] ?? '#4F46E5';
    $hex  = ltrim($primaryColor,'#');
    $pr   = hexdec(substr($hex,0,2));
    $pg   = hexdec(substr($hex,2,2));
    $pb   = hexdec(substr($hex,4,2));
    $primaryLight = sprintf('#%02x%02x%02x',(int)($pr+(255-$pr)*.9),(int)($pg+(255-$pg)*.9),(int)($pb+(255-$pb)*.9));
    $primaryMid   = sprintf('#%02x%02x%02x',(int)($pr+(255-$pr)*.72),(int)($pg+(255-$pg)*.72),(int)($pb+(255-$pb)*.72));
    $primaryDark  = sprintf('#%02x%02x%02x',(int)($pr*.78),(int)($pg*.78),(int)($pb*.78));
    $primaryDeep  = sprintf('#%02x%02x%02x',(int)($pr*.60),(int)($pg*.60),(int)($pb*.60));
@endphp
<style>
@page { size:A4 portrait; margin:0; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:DejaVu Sans,Arial,sans-serif; font-size:9px; color:#1F2937; background:#fff; }

/* ── Accent bar ── */
.accent-bar { height:5px; background:linear-gradient(90deg,{{ $primaryColor }},{{ $primaryMid }},{{ $primaryLight }}); }

/* ── White header ── */
.hdr-white   { background:#fff; padding:14px 22px 12px; display:table; width:100%; }
.hdr-white-l { display:table-cell; vertical-align:middle; }
.hdr-white-r { display:table-cell; vertical-align:middle; text-align:right; width:52%; }
.brand-row   { display:table; }
.brand-logo  { display:table-cell; vertical-align:middle; padding-right:10px; }
.brand-logo img { height:40px; max-width:56px; object-fit:contain; }
.brand-text  { display:table-cell; vertical-align:middle; }
.brand-name  { font-size:15px; font-weight:800; color:#111827; line-height:1.2; }
.brand-sub   { font-size:7.5px; color:#9CA3AF; margin-top:2px; }
.rep-title   { font-size:20px; font-weight:800; color:{{ $primaryColor }}; line-height:1.2; }
.rep-conf    { font-size:7px; color:#9CA3AF; margin-top:3px; }

/* ── Divider ── */
.hdr-divider { border:none; border-top:1.5px solid #E5E7EB; margin:0 22px; }

/* ── Meta strip ── */
.meta-strip  { display:table; width:100%; padding:10px 22px; }
.meta-cell   { display:table-cell; padding:0 28px 0 0; vertical-align:middle; }
.meta-cell:last-child { padding-right:0; }
.meta-lbl    { font-size:7px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#9CA3AF; margin-bottom:3px; }
.meta-val    { font-size:10px; font-weight:600; color:#374151; }

/* ── Body ── */
.body-wrap { padding:14px 22px; }

/* ── Section header ── */
.sec-hdr   { display:table; width:100%; margin-bottom:9px; margin-top:2px; }
.sec-hdr-l { display:table-cell; vertical-align:middle; }
.sec-hdr-r { display:table-cell; vertical-align:middle; text-align:right; }
.sec-tag   { display:inline-block; background:{{ $primaryLight }}; color:{{ $primaryColor }}; border-radius:3px; padding:1px 6px; font-size:6px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; margin-bottom:3px; }
.sec-title { font-size:11px; font-weight:800; color:#0F172A; }

/* ── Hero strip ── */
.hero-tbl  { width:100%; border-collapse:collapse; margin-bottom:6px; }
.hero-left { background:{{ $primaryColor }}; padding:18px 18px 16px; vertical-align:middle; width:36%; }
.hero-right{ background:{{ $primaryDark }};  padding:16px 10px; vertical-align:middle; }
.hero-lbl  { font-size:6.5px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:rgba(255,255,255,.55); margin-bottom:8px; }
.hero-num  { font-size:42px; font-weight:800; color:#fff; line-height:1; margin-bottom:10px; }
.hero-pill { display:inline-block; border-radius:20px; padding:2px 9px; font-size:6.5px; font-weight:700; margin-right:4px; }
.stat-cell { text-align:center; padding:0 12px; vertical-align:middle; border-right:1px solid rgba(255,255,255,.12); }
.stat-cell:last-child { border-right:none; }
.stat-lbl  { font-size:6px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:rgba(255,255,255,.5); margin-bottom:5px; }
.stat-num  { font-size:26px; font-weight:800; color:#fff; line-height:1; margin-bottom:4px; }
.stat-sub  { font-size:6.5px; color:rgba(255,255,255,.4); }

/* ── Financial cards ── */
.spend-tbl  { width:100%; border-collapse:separate; border-spacing:6px 0; margin-bottom:14px; }
.spend-card { background:#fff; border:1px solid #E2E8F0; border-radius:10px; padding:14px 16px; }
.spend-lbl  { font-size:6.5px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#64748B; margin-bottom:8px; }
.spend-val  { font-size:18px; font-weight:800; line-height:1; margin-bottom:5px; }
.spend-sub  { font-size:7px; color:#94A3B8; }

/* ── Distribution panels ── */
.fin-wrap   { display:table; width:100%; border-collapse:separate; border-spacing:6px 0; margin-bottom:14px; }
.fin-panel  { display:table-cell; vertical-align:top; width:50%; background:#fff; border:1px solid #E2E8F0; border-radius:10px; padding:13px 14px; }
.fin-title  { font-size:7px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:{{ $primaryColor }}; margin-bottom:11px; padding-bottom:8px; border-bottom:1.5px solid {{ $primaryLight }}; }

/* Donut layout */
.donut-layout { display:table; width:100%; }
.donut-left   { display:table-cell; vertical-align:middle; width:88px; }
.donut-right  { display:table-cell; vertical-align:middle; padding-left:10px; }
.cy-item    { display:table; width:100%; margin-bottom:7px; }
.cy-dot-c   { display:table-cell; width:9px; vertical-align:middle; padding-right:5px; }
.cy-dot-c span { display:block; width:7px; height:7px; border-radius:50%; }
.cy-lbl-c   { display:table-cell; vertical-align:middle; font-size:8px; color:#334155; }
.cy-cnt-c   { font-size:6px; color:#94A3B8; margin-left:2px; }
.cy-amt-c   { display:table-cell; vertical-align:middle; text-align:right; font-size:8.5px; font-weight:700; color:#0F172A; white-space:nowrap; }
.cy-sub-c   { font-size:6px; font-weight:400; color:#94A3B8; }
.cy-divider { border:none; border-top:1px solid #F1F5F9; margin:5px 0; }
.total-row  { display:table; width:100%; background:{{ $primaryLight }}; border-radius:6px; padding:7px 9px; border:1px solid {{ $primaryMid }}; }
.total-row td { display:table-cell; vertical-align:middle; }
.tot-l  { font-size:7.5px; font-weight:700; color:#334155; }
.tot-sl { font-size:6.5px; color:#94A3B8; margin-top:2px; }
.tot-r  { text-align:right; }
.tot-rv { font-size:12px; font-weight:800; color:{{ $primaryColor }}; }
.tot-rs { font-size:7px; font-weight:600; color:{{ $primaryColor }}; opacity:.65; margin-top:2px; }

/* Bar chart */
.cat-item  { margin-bottom:9px; }
.cat-top   { display:table; width:100%; margin-bottom:4px; }
.cat-name  { display:table-cell; font-size:8px; font-weight:600; color:#334155; }
.cat-cnt-s { font-size:6px; color:#94A3B8; margin-left:2px; }
.cat-val   { display:table-cell; text-align:right; font-size:8px; font-weight:700; white-space:nowrap; }
.bar-wrap  { width:100%; border-collapse:collapse; }
.bar-fill  { height:6px; border-radius:3px; }
.bar-empty { height:6px; background:#F1F5F9; border-radius:3px; }

/* ── Directory table ── */
.dir-tbl   { width:100%; border-collapse:collapse; }
.dir-tbl thead tr { background:{{ $primaryDeep }}; }
.dir-tbl thead th { color:rgba(255,255,255,.85); font-size:6.5px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:8px 7px; text-align:left; }
.dir-tbl tbody tr { border-bottom:1px solid #F1F5F9; }
.dir-tbl tbody tr:nth-child(odd)  { background:#fff; }
.dir-tbl tbody tr:nth-child(even) { background:#F8FAFC; }
.dir-tbl tbody td { padding:6px 7px; font-size:8px; color:#334155; vertical-align:middle; }
.nm-main  { font-weight:700; color:#0F172A; font-size:9px; }
.nm-sub   { font-size:6.5px; color:#94A3B8; margin-top:1px; }
.badge    { display:inline-block; padding:2px 7px; border-radius:20px; font-size:6.5px; font-weight:700; }
.b-active   { background:#DCFCE7; color:#166534; border:1px solid #86EFAC; }
.b-expiring { background:#FEF9C3; color:#854D0E; border:1px solid #FDE047; }
.b-expired  { background:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; }
.num-c    { color:#CBD5E1; font-size:8px; font-weight:600; }
.cst-main { font-weight:700; color:#0F172A; }
.chk-yes  { color:#15803D; font-weight:700; font-size:10px; }
.chk-no   { color:#CBD5E1; font-size:10px; }

/* ── Signature ── */
.sig-tbl { width:100%; border-collapse:separate; border-spacing:14px 0; margin-top:18px; }
.sig-tbl td { vertical-align:bottom; width:33%; }
.sig-blank { height:26px; }
.sig-rule  { border-bottom:1px solid #CBD5E1; margin-bottom:5px; }
.sig-role  { font-size:7px; color:#94A3B8; text-transform:uppercase; letter-spacing:.06em; }
.sig-name  { font-size:8px; font-weight:700; color:#0F172A; margin-top:2px; }

/* ── Footer ── */
.footer-strip { display:table; width:100%; margin-top:16px; padding:10px 22px 0; border-top:1.5px solid #E5E7EB; }
.ft-l { display:table-cell; font-size:8px; font-weight:700; color:{{ $primaryColor }}; }
.ft-m { display:table-cell; text-align:center; font-size:7.5px; color:#9CA3AF; }
.ft-r { display:table-cell; text-align:right; font-size:7px; color:#CBD5E1; font-style:italic; }
</style>
</head>
<body>

{{-- ═══ ACCENT BAR ═══ --}}
<div class="accent-bar"></div>

{{-- ═══ WHITE HEADER ═══ --}}
<div class="hdr-white">
    <div class="hdr-white-l">
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
    <div class="hdr-white-r">
        <div class="rep-title">Domain Register</div>
        <div class="rep-conf">Confidential &ndash; Internal Use Only</div>
    </div>
</div>

{{-- ═══ DIVIDER ═══ --}}
<hr class="hdr-divider">

{{-- ═══ META STRIP ═══ --}}
<div class="meta-strip">
    <div class="meta-cell"><div class="meta-lbl">Generated</div><div class="meta-val">{{ $summary['generated_at'] }}</div></div>
    <div class="meta-cell"><div class="meta-lbl">Total Records</div><div class="meta-val">{{ $summary['total'] }} domain{{ $summary['total']!==1?'s':'' }}</div></div>
    <div class="meta-cell"><div class="meta-lbl">Prepared By</div><div class="meta-val">{{ auth()->user()->name }}</div></div>
    <div class="meta-cell"><div class="meta-lbl">Department</div><div class="meta-val">{{ $settings['department_name'] ?? ($settings['company_name'] ?? $appName) }}</div></div>
</div>

{{-- ═══ DIVIDER ═══ --}}
<hr class="hdr-divider" style="margin-bottom:0;">

<div class="body-wrap">

{{-- ═══ OVERVIEW ═══ --}}
<div class="sec-hdr">
    <div class="sec-hdr-l">
        <div class="sec-tag">Overview</div>
        <div class="sec-title">Domain Summary</div>
    </div>
</div>

{{-- ── Hero status strip ── --}}
<table class="hero-tbl" style="margin-bottom:6px;">
    <tr>
        {{-- LEFT: Total --}}
        <td class="hero-left">
            <div class="hero-lbl">Total Domains</div>
            <div class="hero-num">{{ $summary['total'] }}</div>
            <span class="hero-pill" style="background:rgba(255,255,255,.18);color:rgba(255,255,255,.9);">{{ $summary['active'] }} active</span>
            <span class="hero-pill" style="background:rgba(255,255,255,.13);color:rgba(255,255,255,.75);">{{ $summary['expiring_soon'] }} expiring</span>
            <span class="hero-pill" style="background:rgba(255,255,255,.10);color:rgba(255,255,255,.6);">{{ $summary['expired'] }} expired</span>
        </td>
        {{-- RIGHT: 3 status stats --}}
        <td class="hero-right">
            <table width="100%" style="border-collapse:collapse;">
                <tr>
                    <td class="stat-cell">
                        <div class="stat-lbl">Active</div>
                        <div class="stat-num">{{ $summary['active'] }}</div>
                        <div class="stat-sub">Currently live</div>
                    </td>
                    <td class="stat-cell">
                        <div class="stat-lbl">Expiring Soon</div>
                        <div class="stat-num">{{ $summary['expiring_soon'] }}</div>
                        <div class="stat-sub">Within 30 days</div>
                    </td>
                    <td class="stat-cell">
                        <div class="stat-lbl">Expired</div>
                        <div class="stat-num">{{ $summary['expired'] }}</div>
                        <div class="stat-sub">Action needed</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ── Financial + auto-renew cards ── --}}
<table class="spend-tbl">
    <tr>
        <td width="50%" style="padding:0; vertical-align:top;">
            <div class="spend-card" style="border-left:4px solid {{ $primaryColor }};">
                <div class="spend-lbl">Annual Spend</div>
                @forelse($summary['annual_total_by_currency'] as $currency => $amount)
                <div class="spend-val" style="color:{{ $primaryColor }};font-size:{{ $summary['annual_total_by_currency']->count() > 1 ? '13px' : '18px' }};">{{ format_money($amount, $currency) }}</div>
                @empty
                <div class="spend-val" style="color:{{ $primaryColor }};">BHD&nbsp;0.000</div>
                @endforelse
                <div class="spend-sub">Total yearly cost</div>
            </div>
        </td>
        <td width="50%" style="padding:0; vertical-align:top;">
            <div class="spend-card" style="border-left:4px solid #059669;">
                <div class="spend-lbl">Auto-Renew Enabled</div>
                <div class="spend-val" style="color:#059669;">{{ $summary['auto_renew_count'] }} <span style="font-size:10px;font-weight:600;color:#94A3B8;">/ {{ $summary['total'] }}</span></div>
                <div class="spend-sub">Domains with auto-renewal on</div>
            </div>
        </td>
    </tr>
</table>

{{-- ═══ DISTRIBUTION ═══ --}}
@php
    $cycleColors = [
        'annual'    => $primaryColor,
        'biennial'  => '#059669',
        'triennial' => '#D97706',
        'one_time'  => '#0EA5E9',
    ];
    $regPalette  = [$primaryColor,'#059669','#D97706','#DC2626','#0EA5E9','#7C3AED','#EC4899'];
    $custPalette = ['#059669',$primaryColor,'#D97706','#DC2626','#0EA5E9','#7C3AED','#EC4899'];
    $maxReg  = $summary['by_registrar']->max('count') ?: 1;
    $maxCust = $summary['by_customer']->max('count') ?: 1;

    /* SVG donut for billing cycle */
    $activeCycles   = $summary['by_billing_cycle']->filter(fn($g)=>$g['count']>0);
    $totalCycleAmt  = $activeCycles->sum('annual') ?: 1;
    $polar = fn($cx,$cy,$r,$deg) => [$cx+$r*cos(deg2rad($deg)), $cy+$r*sin(deg2rad($deg))];
    $donutPath = function($cx,$cy,$ro,$ri,$a1,$a2) use($polar) {
        if(abs($a2-$a1)>=360) $a2=$a1+359.99;
        $lg=($a2-$a1)>180?1:0;
        [$x1,$y1]=$polar($cx,$cy,$ro,$a1); [$x2,$y2]=$polar($cx,$cy,$ro,$a2);
        [$x3,$y3]=$polar($cx,$cy,$ri,$a2); [$x4,$y4]=$polar($cx,$cy,$ri,$a1);
        return sprintf('M%.2f %.2f A%.2f %.2f 0 %d 1 %.2f %.2f L%.2f %.2f A%.2f %.2f 0 %d 0 %.2f %.2f Z',
            $x1,$y1,$ro,$ro,$lg,$x2,$y2,$x3,$y3,$ri,$ri,$lg,$x4,$y4);
    };
    $donutSegs=[]; $angle=-90;
    foreach($activeCycles as $g){
        $cycleKey = strtolower(str_replace(' ','_',$g['label']));
        $color = $cycleColors[$cycleKey] ?? $primaryColor;
        $amt = max($g['annual'], 1);
        $sw = ($amt/$totalCycleAmt)*360;
        if($totalCycleAmt <= 1) $sw = ($g['count'] / max($activeCycles->sum('count'),1)) * 360;
        $donutSegs[]=['path'=>$donutPath(44,44,38,24,$angle,$angle+$sw),'color'=>$color,'label'=>$g['label'],'count'=>$g['count'],'amt'=>$g['annual'],'amt_by_currency'=>$g['annual_by_currency']];
        $angle+=$sw;
    }
@endphp

@if($summary['by_billing_cycle']->count() || $summary['by_registrar']->count())
<div class="sec-hdr">
    <div class="sec-hdr-l">
        <div class="sec-tag">Distribution</div>
        <div class="sec-title">Billing Cycle &amp; Registrar Breakdown</div>
    </div>
</div>

<div class="fin-wrap">
    {{-- BY BILLING CYCLE (donut) --}}
    <div class="fin-panel">
        <div class="fin-title">By Billing Cycle</div>
        <div class="donut-layout">
            <div class="donut-left">
                <svg width="88" height="88" viewBox="0 0 88 88" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="44" cy="44" r="38" fill="none" stroke="#F1F5F9" stroke-width="14"/>
                    @if(count($donutSegs)===1)
                        <circle cx="44" cy="44" r="38" fill="{{ $donutSegs[0]['color'] }}" />
                        <circle cx="44" cy="44" r="24" fill="#fff" />
                    @elseif(count($donutSegs)>1)
                        @foreach($donutSegs as $seg)
                            <path d="{{ $seg['path'] }}" fill="{{ $seg['color'] }}" />
                        @endforeach
                        <circle cx="44" cy="44" r="23" fill="#fff" />
                    @else
                        <circle cx="44" cy="44" r="38" fill="{{ $primaryLight }}" />
                        <circle cx="44" cy="44" r="24" fill="#fff" />
                    @endif
                    <text x="44" y="40" text-anchor="middle" font-size="11" font-weight="bold" fill="#0F172A" font-family="DejaVu Sans,Arial,sans-serif">{{ $summary['total'] }}</text>
                    <text x="44" y="50" text-anchor="middle" font-size="6" fill="#94A3B8" font-family="DejaVu Sans,Arial,sans-serif">domains</text>
                </svg>
            </div>
            <div class="donut-right">
                @forelse($donutSegs as $i => $seg)
                    @if($i>0)<hr class="cy-divider">@endif
                    <div class="cy-item">
                        <div class="cy-dot-c"><span style="background:{{ $seg['color'] }};"></span></div>
                        <div class="cy-lbl-c">{{ $seg['label'] }}<span class="cy-cnt-c">({{ $seg['count'] }})</span></div>
                        <div class="cy-amt-c">{{ $seg['amt_by_currency']->map(fn($a,$c) => format_money($a,$c))->implode(' + ') }} <span class="cy-sub-c">/yr</span></div>
                    </div>
                @empty
                    <div style="font-size:8px;color:#94A3B8;">No data</div>
                @endforelse
                <table class="total-row" style="margin-top:8px;"><tr>
                    <td><div class="tot-l">Total Annual</div><div class="tot-sl">Monthly equiv.</div></td>
                    <td class="tot-r"><div class="tot-rv">{{ $summary['annual_total_by_currency']->map(fn($a,$c) => format_money($a,$c))->implode(' + ') ?: 'BHD 0.000' }}</div><div class="tot-rs">{{ $summary['monthly_total_by_currency']->map(fn($a,$c) => format_money($a,$c))->implode(' + ') ?: 'BHD 0.000' }}/mo</div></td>
                </tr></table>
            </div>
        </div>
    </div>

    {{-- BY REGISTRAR (bars) --}}
    <div class="fin-panel">
        <div class="fin-title">By Registrar</div>
        @forelse($summary['by_registrar'] as $ri => $row)
        @php $rc = $regPalette[$ri % count($regPalette)]; $rp = max(4, round($row['count']/$maxReg*100)); @endphp
        <div class="cat-item">
            <div class="cat-top">
                <div class="cat-name">{{ $row['label'] }}<span class="cat-cnt-s">({{ $row['count'] }})</span></div>
                <div class="cat-val" style="color:{{ $rc }};">{{ $row['count'] }} domain{{ $row['count']!==1?'s':'' }}</div>
            </div>
            <table class="bar-wrap"><tr>
                <td class="bar-fill" style="width:{{ $rp }}%;background:{{ $rc }};"></td>
                <td class="bar-empty" style="width:{{ 100-$rp }}%;"></td>
            </tr></table>
        </div>
        @empty
            <div style="font-size:8px;color:#94A3B8;">No registrar data</div>
        @endforelse
    </div>
</div>
@endif

{{-- ═══ DIRECTORY ═══ --}}
<div class="sec-hdr">
    <div class="sec-hdr-l">
        <div class="sec-tag">Directory</div>
        <div class="sec-title">All Domains</div>
    </div>
    <div class="sec-hdr-r">
        <span style="background:{{ $primaryLight }};color:{{ $primaryColor }};border:1px solid {{ $primaryMid }};border-radius:20px;padding:3px 10px;font-size:7.5px;font-weight:700;">
            {{ $summary['total'] }} record{{ $summary['total']!==1?'s':'' }}
        </span>
    </div>
</div>

<table class="dir-tbl">
    <thead>
        <tr>
            <th style="width:20px;">#</th>
            <th>Domain</th>
            <th>Customer</th>
            <th>Registrar</th>
            <th>Responsible</th>
            <th>Expires</th>
            <th>Annual Cost</th>
            <th>Auto</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($all as $i => $d)
        @php
            $days = $d->days_until_expiry;
            $daysLabel = $d->expires_at
                ? ($days<0 ? abs($days).'d overdue' : ($days===0 ? 'Today' : $days.'d left'))
                : '';
            $dlColor = $d->status==='expired' ? '#DC2626' : ($d->status==='expiring_soon' ? '#D97706' : '#94A3B8');
        @endphp
        <tr>
            <td class="num-c">{{ $i+1 }}</td>
            <td>
                <div class="nm-main">{{ $d->domain }}</div>
                @if($d->hosting_provider)<div class="nm-sub">{{ $d->hosting_provider }}</div>@endif
            </td>
            <td style="font-size:8px;">{{ $d->customer?->name ?? '—' }}</td>
            <td style="font-size:8px;">{{ $d->registrar ?? '—' }}</td>
            <td style="font-size:8px;">{{ $d->responsibleUsers->pluck('name')->implode(', ') ?: '—' }}</td>
            <td style="font-size:8px;">
                {{ $d->expires_at ? $d->expires_at->format('d M Y') : '—' }}
                @if($daysLabel)<div style="font-size:6.5px;color:{{ $dlColor }};margin-top:1px;">{{ $daysLabel }}</div>@endif
            </td>
            <td class="cst-main">{{ format_money($d->annual_cost, $d->currency) }}</td>
            <td class="{{ $d->auto_renew ? 'chk-yes' : 'chk-no' }}">{{ $d->auto_renew ? '&#10003;' : '&mdash;' }}</td>
            <td>
                @if($d->status==='active')<span class="badge b-active">Active</span>
                @elseif($d->status==='expiring_soon')<span class="badge b-expiring">Expiring</span>
                @else<span class="badge b-expired">Expired</span>@endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ═══ SIGNATURE ═══ --}}
<table class="sig-tbl">
    <tr>
        <td><div class="sig-blank"></div><div class="sig-rule"></div><div class="sig-role">Prepared by</div><div class="sig-name">{{ auth()->user()->name }}</div></td>
        <td><div class="sig-blank"></div><div class="sig-rule"></div><div class="sig-role">Reviewed by</div><div class="sig-name">&nbsp;</div></td>
        <td><div class="sig-blank"></div><div class="sig-rule"></div><div class="sig-role">Approved by</div><div class="sig-name">{{ auth()->user()->name }}</div></td>
    </tr>
</table>

</div>{{-- /body-wrap --}}

{{-- ═══ FOOTER BAND ═══ --}}
<div class="footer-strip">
    <div class="ft-l">{{ $appName }} &mdash; Domain Register</div>
    <div class="ft-m">Generated {{ $summary['generated_at'] }} &bull; {{ auth()->user()->name }}</div>
    <div class="ft-r">Confidential</div>
</div>

</body>
</html>

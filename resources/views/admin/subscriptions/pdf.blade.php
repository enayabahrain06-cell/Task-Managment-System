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
    $primaryLight  = sprintf('#%02x%02x%02x',(int)($pr+(255-$pr)*.9),(int)($pg+(255-$pg)*.9),(int)($pb+(255-$pb)*.9));
    $primaryMid    = sprintf('#%02x%02x%02x',(int)($pr+(255-$pr)*.72),(int)($pg+(255-$pg)*.72),(int)($pb+(255-$pb)*.72));
    $primaryDark   = sprintf('#%02x%02x%02x',(int)($pr*.78),(int)($pg*.78),(int)($pb*.78));
    $primaryDeep   = sprintf('#%02x%02x%02x',(int)($pr*.60),(int)($pg*.60),(int)($pb*.60));
@endphp
<style>
@page { size:A4 portrait; margin:0; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:DejaVu Sans,Arial,sans-serif; font-size:9px; color:#1F2937; background:#F1F5F9; }

/* ── Page wrapper (inner margin) ── */
.page { padding:0; }

/* ── White top header ── */
.hdr-white { background:#fff; padding:12px 18px 11px; display:table; width:100%; border-bottom:1px solid #E2E8F0; }
.hdr-white-l { display:table-cell; vertical-align:middle; }
.hdr-white-r { display:table-cell; vertical-align:middle; text-align:right; width:52%; }
.brand-row   { display:table; }
.brand-logo  { display:table-cell; vertical-align:middle; padding-right:10px; }
.brand-logo img { height:38px; max-width:52px; object-fit:contain; }
.brand-text  { display:table-cell; vertical-align:middle; }
.brand-name  { font-size:14px; font-weight:800; color:#0F172A; }
.brand-sub   { font-size:7.5px; color:#94A3B8; margin-top:2px; }
.rep-title   { font-size:21px; font-weight:800; color:#0F172A; line-height:1.1; }
.rep-conf    { font-size:7px; color:#94A3B8; margin-top:3px; }

/* ── Colored title band ── */
.hdr-band { background:{{ $primaryDeep }}; padding:7px 18px; display:table; width:100%; }
.hdr-band-l { display:table-cell; vertical-align:middle; }
.hdr-band-r { display:table-cell; vertical-align:middle; text-align:right; }
.rep-badge     { display:inline-block; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); border-radius:3px; padding:1px 7px; }
.rep-badge-txt { font-size:6px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:rgba(255,255,255,.75); }
.hdr-band-date { font-size:7.5px; color:rgba(255,255,255,.55); }

/* ── Meta strip ── */
.meta-strip { display:table; width:100%; background:#F8FAFC; border-bottom:2px solid {{ $primaryColor }}; }
.meta-cell  { display:table-cell; padding:8px 16px; vertical-align:middle; border-right:1px solid #E2E8F0; }
.meta-cell:last-child { border-right:none; }
.meta-lbl { font-size:6px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#94A3B8; margin-bottom:3px; }
.meta-val { font-size:9.5px; font-weight:700; color:#0F172A; }

/* ── Body padding wrapper ── */
.body-wrap { padding:14px 18px; }

/* ── Section header ── */
.sec-hdr { display:table; width:100%; margin-bottom:9px; margin-top:2px; }
.sec-hdr-l { display:table-cell; vertical-align:middle; }
.sec-hdr-r { display:table-cell; vertical-align:middle; text-align:right; }
.sec-tag   { display:inline-block; background:{{ $primaryLight }}; color:{{ $primaryColor }}; border-radius:3px; padding:1px 6px; font-size:6px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; margin-bottom:3px; }
.sec-title { font-size:11px; font-weight:800; color:#0F172A; }

/* ── Stat cards ── */
.cards-tbl { width:100%; border-collapse:separate; border-spacing:5px; margin-bottom:14px; }
.cards-tbl td { padding:0; }
.card { border-radius:10px; padding:12px 13px; border:1px solid #E2E8F0; background:#fff; }
.card-top { display:table; width:100%; margin-bottom:6px; }
.card-lbl { display:table-cell; font-size:6.5px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#64748B; vertical-align:middle; }
.card-ico  { display:table-cell; vertical-align:middle; text-align:right; }
.card-ico-box { display:inline-block; width:22px; height:22px; border-radius:6px; text-align:center; line-height:22px; font-size:11px; }
.card-val  { font-size:22px; font-weight:800; line-height:1; color:#0F172A; margin-bottom:3px; }
.card-sub  { font-size:7px; color:#94A3B8; }
.card-bar  { height:3px; border-radius:2px; margin-top:8px; background:#E2E8F0; }

/* Colored card variants */
.card-active { background:{{ $primaryColor }}; border-color:{{ $primaryColor }}; }
.card-active .card-lbl { color:rgba(255,255,255,.65); }
.card-active .card-val { color:#fff; }
.card-active .card-sub { color:rgba(255,255,255,.5); }
.card-active .card-bar { background:rgba(255,255,255,.25); }

.card-expiring { background:#FFFBEB; border-color:#FDE68A; }
.card-expiring .card-lbl { color:#92400E; }
.card-expiring .card-val { color:#B45309; }
.card-expiring .card-sub { color:#D97706; opacity:.7; }

.card-expired  { background:#FFF1F2; border-color:#FECDD3; }
.card-expired .card-lbl  { color:#9F1239; }
.card-expired .card-val  { color:#BE123C; }
.card-expired .card-sub  { color:#E11D48; opacity:.6; }

.card-spend { background:#F8FAFF; border-color:#C7D2FE; }
.card-spend .card-val { color:{{ $primaryColor }}; }
.card-spend .card-sub { color:#6366F1; opacity:.6; }

/* ── Financials panels ── */
.fin-wrap { display:table; width:100%; border-collapse:separate; border-spacing:6px 0; margin-bottom:14px; }
.fin-panel { display:table-cell; vertical-align:top; width:50%; background:#fff; border:1px solid #E2E8F0; border-radius:10px; padding:13px 14px; }
.fin-title { font-size:7px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:{{ $primaryColor }}; margin-bottom:11px; padding-bottom:8px; border-bottom:1.5px solid {{ $primaryLight }}; }

/* Donut layout */
.donut-layout { display:table; width:100%; }
.donut-left   { display:table-cell; vertical-align:middle; width:88px; }
.donut-right  { display:table-cell; vertical-align:middle; padding-left:10px; }
.cy-item  { display:table; width:100%; margin-bottom:7px; }
.cy-dot-c { display:table-cell; width:9px; vertical-align:middle; padding-right:5px; }
.cy-dot-c span { display:block; width:7px; height:7px; border-radius:50%; }
.cy-lbl-c { display:table-cell; vertical-align:middle; font-size:8px; color:#334155; }
.cy-cnt-c { font-size:6px; color:#94A3B8; margin-left:2px; }
.cy-amt-c { display:table-cell; vertical-align:middle; text-align:right; font-size:9px; font-weight:700; color:#0F172A; white-space:nowrap; }
.cy-sub-c { font-size:6px; font-weight:400; color:#94A3B8; }
.cy-divider { border:none; border-top:1px solid #F1F5F9; margin:6px 0; }
.total-row { display:table; width:100%; background:{{ $primaryLight }}; border-radius:6px; padding:7px 9px; border:1px solid {{ $primaryMid }}; }
.total-row td { display:table-cell; vertical-align:middle; }
.tot-l { font-size:7.5px; font-weight:700; color:#334155; }
.tot-sl { font-size:6.5px; color:#94A3B8; margin-top:2px; }
.tot-r { text-align:right; }
.tot-rv { font-size:12px; font-weight:800; color:{{ $primaryColor }}; }
.tot-rs { font-size:7px; font-weight:600; color:{{ $primaryColor }}; opacity:.65; margin-top:2px; }

/* Category bars */
.cat-item { margin-bottom:9px; }
.cat-top  { display:table; width:100%; margin-bottom:4px; }
.cat-name { display:table-cell; font-size:8px; font-weight:600; color:#334155; }
.cat-cnt-s { font-size:6px; color:#94A3B8; margin-left:2px; }
.cat-val  { display:table-cell; text-align:right; font-size:8.5px; font-weight:700; white-space:nowrap; }
.bar-wrap  { width:100%; border-collapse:collapse; }
.bar-fill  { height:6px; border-radius:3px; }
.bar-empty { height:6px; background:#F1F5F9; border-radius:3px; }

/* ── Directory table ── */
.dir-tbl { width:100%; border-collapse:collapse; border-radius:10px; overflow:hidden; }
.dir-tbl thead tr { background:{{ $primaryDeep }}; }
.dir-tbl thead th { color:rgba(255,255,255,.85); font-size:6.5px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:8px 9px; text-align:left; }
.dir-tbl tbody tr { border-bottom:1px solid #F1F5F9; }
.dir-tbl tbody tr:nth-child(odd)  { background:#fff; }
.dir-tbl tbody tr:nth-child(even) { background:#F8FAFC; }
.dir-tbl tbody td { padding:7px 9px; font-size:8.5px; color:#334155; vertical-align:middle; }
.nm-main { font-weight:700; color:#0F172A; font-size:9px; }
.nm-sub  { font-size:6.5px; color:#94A3B8; margin-top:1px; }
.badge   { display:inline-block; padding:2px 8px; border-radius:20px; font-size:6.5px; font-weight:700; }
.b-active  { background:#DCFCE7; color:#166534; border:1px solid #86EFAC; }
.b-expiring{ background:#FEF9C3; color:#854D0E; border:1px solid #FDE047; }
.b-expired { background:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; }
.b-cat     { border:1px solid {{ $primaryMid }}; }
.cst-main  { font-weight:700; color:#0F172A; font-size:9px; }
.cst-sub   { font-size:6.5px; color:#94A3B8; }
.num-c     { color:#CBD5E1; font-size:8px; font-weight:600; }

/* ── Signature ── */
.sig-tbl { width:100%; border-collapse:separate; border-spacing:14px 0; margin-top:18px; }
.sig-tbl td { vertical-align:bottom; width:33%; }
.sig-blank { height:26px; }
.sig-rule  { border-bottom:1px solid #CBD5E1; margin-bottom:5px; }
.sig-role  { font-size:7px; color:#94A3B8; text-transform:uppercase; letter-spacing:.06em; }
.sig-name  { font-size:8px; font-weight:700; color:#0F172A; margin-top:2px; }

/* ── Footer ── */
.footer-strip { background:{{ $primaryDeep }}; padding:7px 18px; display:table; width:100%; margin-top:14px; }
.ft-l { display:table-cell; font-size:7.5px; font-weight:700; color:rgba(255,255,255,.8); }
.ft-m { display:table-cell; text-align:center; font-size:7px; color:rgba(255,255,255,.45); }
.ft-r { display:table-cell; text-align:right; font-size:7px; color:rgba(255,255,255,.35); font-style:italic; }
</style>
</head>
<body>
<div class="page">

{{-- ═══ WHITE HEADER (logo safe zone) ═══ --}}
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
        <div class="rep-title">Subscriptions Report</div>
        <div class="rep-conf">Confidential &ndash; Internal Use Only</div>
    </div>
</div>
{{-- ═══ COLORED BAND ═══ --}}
<div class="hdr-band">
    <div class="hdr-band-l">
        <span class="rep-badge"><span class="rep-badge-txt">Official Report</span></span>
    </div>
    <div class="hdr-band-r">
        <span class="hdr-band-date">Generated {{ $summary['generated_at'] }} &bull; Prepared by {{ auth()->user()->name }}</span>
    </div>
</div>

{{-- ═══ META STRIP ═══ --}}
<div class="meta-strip">
    <div class="meta-cell"><div class="meta-lbl">Generated</div><div class="meta-val">{{ $summary['generated_at'] }}</div></div>
    <div class="meta-cell"><div class="meta-lbl">Total Records</div><div class="meta-val">{{ $summary['total'] }} subscription{{ $summary['total']!==1?'s':'' }}</div></div>
    <div class="meta-cell"><div class="meta-lbl">Prepared By</div><div class="meta-val">{{ auth()->user()->name }}</div></div>
    <div class="meta-cell"><div class="meta-lbl">Department</div><div class="meta-val">{{ $settings['department_name'] ?? ($settings['company_name'] ?? $appName) }}</div></div>
</div>

<div class="body-wrap">

{{-- ═══ OVERVIEW ═══ --}}
<div class="sec-hdr">
    <div class="sec-hdr-l">
        <div class="sec-tag">Overview</div>
        <div class="sec-title">Subscriptions Summary</div>
    </div>
</div>

<table class="cards-tbl">
    <tr>
        {{-- TOTAL --}}
        <td width="33%">
            <div class="card">
                <div class="card-top">
                    <div class="card-lbl">Total</div>
                    <div class="card-ico"><div class="card-ico-box" style="background:{{ $primaryLight }};color:{{ $primaryColor }};">&#8734;</div></div>
                </div>
                <div class="card-val">{{ $summary['total'] }}</div>
                <div class="card-sub">All subscriptions</div>
                <div class="card-bar"><div style="height:3px;border-radius:2px;background:{{ $primaryMid }};width:100%;"></div></div>
            </div>
        </td>
        {{-- ACTIVE --}}
        <td width="33%">
            <div class="card card-active">
                <div class="card-top">
                    <div class="card-lbl">Active</div>
                    <div class="card-ico"><div class="card-ico-box" style="background:rgba(255,255,255,.18);color:#fff;">&#10003;</div></div>
                </div>
                <div class="card-val">{{ $summary['active'] }}</div>
                <div class="card-sub">Currently running</div>
                <div class="card-bar"></div>
            </div>
        </td>
        {{-- EXPIRING SOON --}}
        <td width="33%">
            <div class="card card-expiring">
                <div class="card-top">
                    <div class="card-lbl">Expiring Soon</div>
                    <div class="card-ico"><div class="card-ico-box" style="background:#FDE68A;color:#B45309;">&#9651;</div></div>
                </div>
                <div class="card-val">{{ $summary['expiring_soon'] }}</div>
                <div class="card-sub">Within 30 days</div>
                <div class="card-bar"><div style="height:3px;border-radius:2px;background:#FCD34D;width:{{ $summary['expiring_soon'] ? '60' : '0' }}%;"></div></div>
            </div>
        </td>
    </tr>
    <tr>
        {{-- EXPIRED --}}
        <td width="33%">
            <div class="card card-expired">
                <div class="card-top">
                    <div class="card-lbl">Expired</div>
                    <div class="card-ico"><div class="card-ico-box" style="background:#FFE4E6;color:#BE123C;">&#8856;</div></div>
                </div>
                <div class="card-val">{{ $summary['expired'] }}</div>
                <div class="card-sub">Action needed</div>
                <div class="card-bar"><div style="height:3px;border-radius:2px;background:#FDA4AF;width:{{ $summary['expired'] ? '60' : '0' }}%;"></div></div>
            </div>
        </td>
        {{-- MONTHLY SPEND --}}
        <td width="33%">
            <div class="card card-spend">
                <div class="card-top">
                    <div class="card-lbl">Monthly Spend</div>
                    <div class="card-ico"><div class="card-ico-box" style="background:#E0E7FF;color:{{ $primaryColor }};">&#36;</div></div>
                </div>
                <div class="card-val" style="font-size:13px;">BHD&nbsp;{{ number_format($summary['monthly_total'],3) }}</div>
                <div class="card-sub">All billing cycles</div>
                <div class="card-bar"><div style="height:3px;border-radius:2px;background:{{ $primaryMid }};width:45%;"></div></div>
            </div>
        </td>
        {{-- ANNUAL SPEND --}}
        <td width="33%">
            <div class="card card-spend">
                <div class="card-top">
                    <div class="card-lbl">Annual Spend</div>
                    <div class="card-ico"><div class="card-ico-box" style="background:#E0E7FF;color:{{ $primaryColor }};">&#8776;</div></div>
                </div>
                <div class="card-val" style="font-size:13px;">BHD&nbsp;{{ number_format($summary['annual_total'],3) }}</div>
                <div class="card-sub">Projected yearly</div>
                <div class="card-bar"><div style="height:3px;border-radius:2px;background:{{ $primaryMid }};width:100%;"></div></div>
            </div>
        </td>
    </tr>
</table>

{{-- ═══ FINANCIALS ═══ --}}
<div class="sec-hdr">
    <div class="sec-hdr-l">
        <div class="sec-tag">Financials</div>
        <div class="sec-title">Cost Breakdown &amp; Category Distribution</div>
    </div>
</div>

@php
    $cycleGroups = [
        ['label'=>'Monthly',   'color'=>$primaryColor, 'col'=>$all->filter(fn($s)=>$s->billing_cycle==='monthly')],
        ['label'=>'Annual',    'color'=>'#059669',     'col'=>$all->filter(fn($s)=>$s->billing_cycle==='annual')],
        ['label'=>'Quarterly', 'color'=>'#D97706',     'col'=>$all->filter(fn($s)=>$s->billing_cycle==='quarterly')],
        ['label'=>'One-time',  'color'=>'#0EA5E9',     'col'=>$all->filter(fn($s)=>$s->billing_cycle==='one_time')],
    ];
    $activeCycles   = collect($cycleGroups)->filter(fn($g)=>$g['col']->count()>0);
    $totalCycleAmt  = $activeCycles->sum(fn($g)=>$g['col']->sum(fn($s)=>$s->annual_cost)) ?: 1;
    $maxCat         = $summary['by_category']->max('annual') ?: 1;
    $catPalette     = [$primaryColor,'#059669','#D97706','#DC2626','#0EA5E9','#7C3AED','#EC4899'];

    /* SVG donut helpers */
    $polar = fn($cx,$cy,$r,$deg) => [$cx+$r*cos(deg2rad($deg)), $cy+$r*sin(deg2rad($deg))];
    $donutPath = function($cx,$cy,$ro,$ri,$a1,$a2) use($polar) {
        if(abs($a2-$a1)>=360) $a2=$a1+359.99;
        $lg=($a2-$a1)>180?1:0;
        [$x1,$y1]=$polar($cx,$cy,$ro,$a1); [$x2,$y2]=$polar($cx,$cy,$ro,$a2);
        [$x3,$y3]=$polar($cx,$cy,$ri,$a2); [$x4,$y4]=$polar($cx,$cy,$ri,$a1);
        return sprintf('M%.2f %.2f A%.2f %.2f 0 %d 1 %.2f %.2f L%.2f %.2f A%.2f %.2f 0 %d 0 %.2f %.2f Z',
            $x1,$y1,$ro,$ro,$lg,$x2,$y2,$x3,$y3,$ri,$ri,$lg,$x4,$y4);
    };
    $segs=[]; $angle=-90;
    foreach($activeCycles as $g){
        $amt=$g['col']->sum(fn($s)=>$s->annual_cost);
        $sw=($amt/$totalCycleAmt)*360;
        $segs[]=['path'=>$donutPath(44,44,38,24,$angle,$angle+$sw),'color'=>$g['color'],'label'=>$g['label'],'count'=>$g['col']->count(),'amt'=>$amt];
        $angle+=$sw;
    }
@endphp

<div class="fin-wrap">
    {{-- BY BILLING CYCLE --}}
    <div class="fin-panel">
        <div class="fin-title">By Billing Cycle</div>
        <div class="donut-layout">
            <div class="donut-left">
                <svg width="88" height="88" viewBox="0 0 88 88" xmlns="http://www.w3.org/2000/svg">
                    {{-- Background ring --}}
                    <circle cx="44" cy="44" r="38" fill="none" stroke="#F1F5F9" stroke-width="14"/>
                    @if(count($segs)===1)
                        <circle cx="44" cy="44" r="38" fill="{{ $segs[0]['color'] }}" />
                        <circle cx="44" cy="44" r="24" fill="#fff" />
                    @else
                        @foreach($segs as $seg)
                            <path d="{{ $seg['path'] }}" fill="{{ $seg['color'] }}" />
                        @endforeach
                        <circle cx="44" cy="44" r="23" fill="#fff" />
                    @endif
                    <text x="44" y="40" text-anchor="middle" font-size="11" font-weight="bold" fill="#0F172A" font-family="DejaVu Sans,Arial,sans-serif">{{ $summary['total'] }}</text>
                    <text x="44" y="50" text-anchor="middle" font-size="6" fill="#94A3B8" font-family="DejaVu Sans,Arial,sans-serif">subscriptions</text>
                </svg>
            </div>
            <div class="donut-right">
                @foreach($segs as $i => $seg)
                    @if($i>0)<hr class="cy-divider">@endif
                    <div class="cy-item">
                        <div class="cy-dot-c"><span style="background:{{ $seg['color'] }};"></span></div>
                        <div class="cy-lbl-c">{{ $seg['label'] }}<span class="cy-cnt-c">({{ $seg['count'] }})</span></div>
                        <div class="cy-amt-c">BHD {{ number_format($seg['amt'],3) }} <span class="cy-sub-c">/yr</span></div>
                    </div>
                @endforeach
                <table class="total-row" style="margin-top:8px;"><tr>
                    <td><div class="tot-l">Total Annual</div><div class="tot-sl">Monthly equiv.</div></td>
                    <td class="tot-r"><div class="tot-rv">BHD {{ number_format($summary['annual_total'],3) }}</div><div class="tot-rs">BHD {{ number_format($summary['monthly_total'],3) }}/mo</div></td>
                </tr></table>
            </div>
        </div>
    </div>

    {{-- BY CATEGORY --}}
    <div class="fin-panel">
        <div class="fin-title">By Category</div>
        @foreach($summary['by_category'] as $ci => $cat)
        @php $cc=$catPalette[$ci%count($catPalette)]; $cp=max(4,round($cat['annual']/$maxCat*100)); @endphp
        <div class="cat-item">
            <div class="cat-top">
                <div class="cat-name">{{ $cat['label'] }}<span class="cat-cnt-s">({{ $cat['count'] }})</span></div>
                <div class="cat-val" style="color:{{ $cc }};">BHD {{ number_format($cat['annual'],3) }}<span style="font-size:6px;font-weight:400;color:#94A3B8;"> /yr</span></div>
            </div>
            <table class="bar-wrap"><tr>
                <td class="bar-fill" style="width:{{ $cp }}%;background:{{ $cc }};"></td>
                <td class="bar-empty" style="width:{{ 100-$cp }}%;"></td>
            </tr></table>
        </div>
        @endforeach
    </div>
</div>

{{-- ═══ DIRECTORY ═══ --}}
<div class="sec-hdr">
    <div class="sec-hdr-l">
        <div class="sec-tag">Directory</div>
        <div class="sec-title">All Subscriptions</div>
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
            <th style="width:22px;">#</th>
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
            $rl = $sub->renewal_date ? $sub->renewal_date->format('d M Y') : '—';
            $dy = $sub->days_until_renewal;
            $dl = $sub->renewal_date ? ($dy<0?abs($dy).'d overdue':($dy===0?'Today':$dy.'d left')) : '';
            $dlColor = $sub->status==='expired'?'#DC2626':($sub->status==='expiring_soon'?'#D97706':'#94A3B8');
        @endphp
        <tr>
            <td class="num-c">{{ $i+1 }}</td>
            <td>
                <div class="nm-main">{{ $sub->name }}</div>
                @if($sub->vendor)<div class="nm-sub">{{ $sub->vendor }}</div>@endif
            </td>
            <td>
                <span class="badge b-cat" style="background:{{ $primaryLight }};color:{{ $primaryColor }};">{{ $catNames[$sub->category] ?? $sub->category }}</span>
            </td>
            <td style="font-size:7.5px;color:#64748B;">
                @if($sub->type==='per_seat') Per Seat
                @elseif($sub->type==='site_license') Site License
                @else Shared @endif
            </td>
            <td>
                <div class="cst-main">{{ $sub->currency }}&nbsp;{{ number_format($sub->cost,3) }}</div>
                <div class="cst-sub">{{ ucfirst(str_replace('_',' ',$sub->billing_cycle)) }}</div>
            </td>
            <td class="cst-main">{{ number_format($sub->annual_cost,3) }}</td>
            <td style="font-size:8px;color:#334155;">
                {{ $rl }}
                @if($dl)<div style="font-size:6.5px;color:{{ $dlColor }};margin-top:1px;">{{ $dl }}</div>@endif
            </td>
            <td>
                @if($sub->status==='active')<span class="badge b-active">Active</span>
                @elseif($sub->status==='expiring_soon')<span class="badge b-expiring">Expiring</span>
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
    <div class="ft-l">{{ $appName }} &mdash; Subscriptions &amp; Licenses</div>
    <div class="ft-m">Generated {{ $summary['generated_at'] }} &bull; {{ auth()->user()->name }}</div>
    <div class="ft-r">Confidential</div>
</div>

</div>{{-- /page --}}
</body>
</html>

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
body { font-family:DejaVu Sans,Arial,sans-serif; font-size:9px; color:#1F2937; background:#F1F5F9; }

/* ── White header ── */
.hdr-white   { background:#fff; padding:12px 18px 11px; display:table; width:100%; border-bottom:1px solid #E2E8F0; }
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

/* ── Colored band ── */
.hdr-band    { background:{{ $primaryDeep }}; padding:7px 18px; display:table; width:100%; }
.hdr-band-l  { display:table-cell; vertical-align:middle; }
.hdr-band-r  { display:table-cell; vertical-align:middle; text-align:right; }
.rep-badge   { display:inline-block; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); border-radius:3px; padding:1px 7px; }
.rep-badge-txt { font-size:6px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:rgba(255,255,255,.75); }
.hdr-band-date { font-size:7.5px; color:rgba(255,255,255,.55); }

/* ── Meta strip ── */
.meta-strip  { display:table; width:100%; background:#F8FAFC; border-bottom:2px solid {{ $primaryColor }}; }
.meta-cell   { display:table-cell; padding:8px 16px; vertical-align:middle; border-right:1px solid #E2E8F0; }
.meta-cell:last-child { border-right:none; }
.meta-lbl    { font-size:6px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#94A3B8; margin-bottom:3px; }
.meta-val    { font-size:9.5px; font-weight:700; color:#0F172A; }

/* ── Body ── */
.body-wrap { padding:14px 18px; }

/* ── Section header ── */
.sec-hdr   { display:table; width:100%; margin-bottom:9px; margin-top:2px; }
.sec-hdr-l { display:table-cell; vertical-align:middle; }
.sec-hdr-r { display:table-cell; vertical-align:middle; text-align:right; }
.sec-tag   { display:inline-block; background:{{ $primaryLight }}; color:{{ $primaryColor }}; border-radius:3px; padding:1px 6px; font-size:6px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; margin-bottom:3px; }
.sec-title { font-size:11px; font-weight:800; color:#0F172A; }

/* ── Stat cards ── */
.cards-tbl { width:100%; border-collapse:separate; border-spacing:5px; margin-bottom:14px; }
.cards-tbl td { padding:0; }
.card     { border-radius:10px; padding:12px 13px; border:1px solid #E2E8F0; background:#fff; }
.card-top { display:table; width:100%; margin-bottom:6px; }
.card-lbl { display:table-cell; font-size:6.5px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#64748B; vertical-align:middle; }
.card-ico { display:table-cell; vertical-align:middle; text-align:right; }
.card-ico-box { display:inline-block; width:22px; height:22px; border-radius:6px; text-align:center; line-height:22px; font-size:11px; }
.card-val { font-size:22px; font-weight:800; line-height:1; color:#0F172A; margin-bottom:3px; }
.card-sub { font-size:7px; color:#94A3B8; }
.card-bar { height:3px; border-radius:2px; margin-top:8px; background:#E2E8F0; }

.card-active   { background:{{ $primaryColor }}; border-color:{{ $primaryColor }}; }
.card-active .card-lbl { color:rgba(255,255,255,.65); }
.card-active .card-val { color:#fff; }
.card-active .card-sub { color:rgba(255,255,255,.5); }

.card-inactive { background:#F8FAFC; border-color:#CBD5E1; }
.card-inactive .card-lbl { color:#475569; }
.card-inactive .card-val { color:#475569; }
.card-inactive .card-sub { color:#94A3B8; }

.card-suspended { background:#FFF1F2; border-color:#FECDD3; }
.card-suspended .card-lbl { color:#9F1239; }
.card-suspended .card-val { color:#BE123C; }
.card-suspended .card-sub { color:#E11D48; opacity:.6; }

.card-plat { background:#F8FAFF; border-color:#C7D2FE; }
.card-plat .card-val { color:{{ $primaryColor }}; }

/* ── Distribution panels ── */
.fin-wrap   { display:table; width:100%; border-collapse:separate; border-spacing:6px 0; margin-bottom:14px; }
.fin-panel  { display:table-cell; vertical-align:top; width:50%; background:#fff; border:1px solid #E2E8F0; border-radius:10px; padding:13px 14px; }
.fin-title  { font-size:7px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:{{ $primaryColor }}; margin-bottom:11px; padding-bottom:8px; border-bottom:1.5px solid {{ $primaryLight }}; }

/* Donut layout */
.donut-layout { display:table; width:100%; }
.donut-left   { display:table-cell; vertical-align:middle; width:88px; }
.donut-right  { display:table-cell; vertical-align:middle; padding-left:10px; }
.cy-item    { display:table; width:100%; margin-bottom:6px; }
.cy-dot-c   { display:table-cell; width:9px; vertical-align:middle; padding-right:5px; }
.cy-dot-c span { display:block; width:7px; height:7px; border-radius:50%; }
.cy-lbl-c   { display:table-cell; vertical-align:middle; font-size:8px; color:#334155; }
.cy-cnt-c   { font-size:6px; color:#94A3B8; margin-left:2px; }
.cy-amt-c   { display:table-cell; vertical-align:middle; text-align:right; font-size:8px; font-weight:700; color:#0F172A; white-space:nowrap; }
.cy-divider { border:none; border-top:1px solid #F1F5F9; margin:4px 0; }
.total-row  { display:table; width:100%; background:{{ $primaryLight }}; border-radius:6px; padding:7px 9px; border:1px solid {{ $primaryMid }}; margin-top:8px; }
.total-row td { display:table-cell; vertical-align:middle; }
.tot-l  { font-size:7.5px; font-weight:700; color:#334155; }
.tot-r  { text-align:right; }
.tot-rv { font-size:12px; font-weight:800; color:{{ $primaryColor }}; }

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
.b-active    { background:#DCFCE7; color:#166534; border:1px solid #86EFAC; }
.b-inactive  { background:#F1F5F9; color:#475569; border:1px solid #CBD5E1; }
.b-suspended { background:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; }
.plat-badge  { display:inline-block; padding:2px 7px; border-radius:20px; font-size:6.5px; font-weight:700; }
.num-c    { color:#CBD5E1; font-size:8px; font-weight:600; }

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
        <div class="rep-title">Social Accounts Report</div>
        <div class="rep-conf">Confidential &ndash; Internal Use Only</div>
    </div>
</div>

{{-- ═══ COLORED BAND ═══ --}}
<div class="hdr-band">
    <div class="hdr-band-l"><span class="rep-badge"><span class="rep-badge-txt">Official Report</span></span></div>
    <div class="hdr-band-r"><span class="hdr-band-date">Generated {{ $summary['generated_at'] }} &bull; Prepared by {{ auth()->user()->name }}</span></div>
</div>

{{-- ═══ META STRIP ═══ --}}
<div class="meta-strip">
    <div class="meta-cell"><div class="meta-lbl">Generated</div><div class="meta-val">{{ $summary['generated_at'] }}</div></div>
    <div class="meta-cell"><div class="meta-lbl">Total Records</div><div class="meta-val">{{ $summary['total'] }} account{{ $summary['total']!==1?'s':'' }}</div></div>
    <div class="meta-cell"><div class="meta-lbl">Prepared By</div><div class="meta-val">{{ auth()->user()->name }}</div></div>
    <div class="meta-cell"><div class="meta-lbl">Department</div><div class="meta-val">{{ $settings['department_name'] ?? ($settings['company_name'] ?? $appName) }}</div></div>
</div>

<div class="body-wrap">

{{-- ═══ OVERVIEW ═══ --}}
<div class="sec-hdr">
    <div class="sec-hdr-l">
        <div class="sec-tag">Overview</div>
        <div class="sec-title">Accounts Summary</div>
    </div>
</div>

<table class="cards-tbl">
    <tr>
        {{-- Total --}}
        <td width="25%">
            <div class="card">
                <div class="card-top">
                    <div class="card-lbl">Total</div>
                    <div class="card-ico"><div class="card-ico-box" style="background:{{ $primaryLight }};color:{{ $primaryColor }};">&#8734;</div></div>
                </div>
                <div class="card-val">{{ $summary['total'] }}</div>
                <div class="card-sub">All accounts</div>
                <div class="card-bar"><div style="height:3px;border-radius:2px;background:{{ $primaryMid }};width:100%;"></div></div>
            </div>
        </td>
        {{-- Active --}}
        <td width="25%">
            <div class="card card-active">
                <div class="card-top">
                    <div class="card-lbl">Active</div>
                    <div class="card-ico"><div class="card-ico-box" style="background:rgba(255,255,255,.18);color:#fff;">&#10003;</div></div>
                </div>
                <div class="card-val">{{ $summary['active'] }}</div>
                <div class="card-sub">Currently active</div>
                <div class="card-bar"></div>
            </div>
        </td>
        {{-- Inactive --}}
        <td width="25%">
            <div class="card card-inactive">
                <div class="card-top">
                    <div class="card-lbl">Inactive</div>
                    <div class="card-ico"><div class="card-ico-box" style="background:#E2E8F0;color:#475569;">&#8212;</div></div>
                </div>
                <div class="card-val">{{ $summary['inactive'] }}</div>
                <div class="card-sub">Not in use</div>
                <div class="card-bar"><div style="height:3px;border-radius:2px;background:#94A3B8;width:{{ $summary['total'] ? round($summary['inactive']/$summary['total']*100) : 0 }}%;"></div></div>
            </div>
        </td>
        {{-- Suspended --}}
        <td width="25%">
            <div class="card card-suspended">
                <div class="card-top">
                    <div class="card-lbl">Suspended</div>
                    <div class="card-ico"><div class="card-ico-box" style="background:#FFE4E6;color:#BE123C;">&#8856;</div></div>
                </div>
                <div class="card-val">{{ $summary['suspended'] }}</div>
                <div class="card-sub">Action needed</div>
                <div class="card-bar"><div style="height:3px;border-radius:2px;background:#FDA4AF;width:{{ $summary['suspended'] ? '60' : '0' }}%;"></div></div>
            </div>
        </td>
    </tr>
</table>

{{-- ═══ DISTRIBUTION ═══ --}}
@php
    /* Donut for platforms */
    $totalPlatCount = $summary['by_platform']->sum('count') ?: 1;
    $polar = fn($cx,$cy,$r,$deg) => [$cx+$r*cos(deg2rad($deg)), $cy+$r*sin(deg2rad($deg))];
    $donutPath = function($cx,$cy,$ro,$ri,$a1,$a2) use($polar) {
        if(abs($a2-$a1)>=360) $a2=$a1+359.99;
        $lg=($a2-$a1)>180?1:0;
        [$x1,$y1]=$polar($cx,$cy,$ro,$a1); [$x2,$y2]=$polar($cx,$cy,$ro,$a2);
        [$x3,$y3]=$polar($cx,$cy,$ri,$a2); [$x4,$y4]=$polar($cx,$cy,$ri,$a1);
        return sprintf('M%.2f %.2f A%.2f %.2f 0 %d 1 %.2f %.2f L%.2f %.2f A%.2f %.2f 0 %d 0 %.2f %.2f Z',
            $x1,$y1,$ro,$ro,$lg,$x2,$y2,$x3,$y3,$ri,$ri,$lg,$x4,$y4);
    };
    $platSegs=[]; $angle=-90;
    foreach($summary['by_platform'] as $g){
        $sw=($g['count']/$totalPlatCount)*360;
        $platSegs[]=['path'=>$donutPath(44,44,38,24,$angle,$angle+$sw),'color'=>$g['color'],'label'=>$g['label'],'count'=>$g['count'],'active'=>$g['active']];
        $angle+=$sw;
    }
    $maxCust = $summary['by_customer']->max('count') ?: 1;
    $custPalette = [$primaryColor,'#059669','#D97706','#DC2626','#0EA5E9','#7C3AED','#EC4899'];
@endphp

<div class="sec-hdr">
    <div class="sec-hdr-l">
        <div class="sec-tag">Distribution</div>
        <div class="sec-title">By Platform &amp; Customer</div>
    </div>
</div>

<div class="fin-wrap">
    {{-- BY PLATFORM (donut) --}}
    <div class="fin-panel">
        <div class="fin-title">By Platform</div>
        <div class="donut-layout">
            <div class="donut-left">
                <svg width="88" height="88" viewBox="0 0 88 88" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="44" cy="44" r="38" fill="none" stroke="#F1F5F9" stroke-width="14"/>
                    @if(count($platSegs)===1)
                        <circle cx="44" cy="44" r="38" fill="{{ $platSegs[0]['color'] }}" />
                        <circle cx="44" cy="44" r="24" fill="#fff" />
                    @elseif(count($platSegs)>1)
                        @foreach($platSegs as $seg)
                            <path d="{{ $seg['path'] }}" fill="{{ $seg['color'] }}" />
                        @endforeach
                        <circle cx="44" cy="44" r="23" fill="#fff" />
                    @else
                        <circle cx="44" cy="44" r="38" fill="{{ $primaryLight }}" />
                        <circle cx="44" cy="44" r="24" fill="#fff" />
                    @endif
                    <text x="44" y="40" text-anchor="middle" font-size="11" font-weight="bold" fill="#0F172A" font-family="DejaVu Sans,Arial,sans-serif">{{ $summary['total'] }}</text>
                    <text x="44" y="50" text-anchor="middle" font-size="6" fill="#94A3B8" font-family="DejaVu Sans,Arial,sans-serif">accounts</text>
                </svg>
            </div>
            <div class="donut-right">
                @forelse($platSegs as $i => $seg)
                    @if($i>0)<hr class="cy-divider">@endif
                    <div class="cy-item">
                        <div class="cy-dot-c"><span style="background:{{ $seg['color'] }};"></span></div>
                        <div class="cy-lbl-c">{{ $seg['label'] }}<span class="cy-cnt-c">({{ $seg['count'] }})</span></div>
                        <div class="cy-amt-c" style="color:{{ $seg['color'] }};">{{ $seg['active'] }} active</div>
                    </div>
                @empty
                    <div style="font-size:8px;color:#94A3B8;">No data</div>
                @endforelse
                <table class="total-row"><tr>
                    <td><div class="tot-l">Total Accounts</div></td>
                    <td class="tot-r"><div class="tot-rv">{{ $summary['total'] }}</div></td>
                </tr></table>
            </div>
        </div>
    </div>

    {{-- BY CUSTOMER (bars) --}}
    <div class="fin-panel">
        <div class="fin-title">By Customer</div>
        @forelse($summary['by_customer'] as $ci => $row)
        @php $cc = $custPalette[$ci % count($custPalette)]; $cp = max(4, round($row['count']/$maxCust*100)); @endphp
        <div class="cat-item">
            <div class="cat-top">
                <div class="cat-name">{{ $row['label'] }}<span class="cat-cnt-s">({{ $row['count'] }})</span></div>
                <div class="cat-val" style="color:{{ $cc }};">{{ $row['count'] }} account{{ $row['count']!==1?'s':'' }}</div>
            </div>
            <table class="bar-wrap"><tr>
                <td class="bar-fill" style="width:{{ $cp }}%;background:{{ $cc }};"></td>
                <td class="bar-empty" style="width:{{ 100-$cp }}%;"></td>
            </tr></table>
        </div>
        @empty
            <div style="font-size:8px;color:#94A3B8;">No customer data</div>
        @endforelse
    </div>
</div>

{{-- ═══ DIRECTORY ═══ --}}
<div class="sec-hdr">
    <div class="sec-hdr-l">
        <div class="sec-tag">Directory</div>
        <div class="sec-title">All Social Accounts</div>
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
            <th>Account Name</th>
            <th>Platform</th>
            <th>Customer</th>
            <th>Username / Email</th>
            <th>Assigned To</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($all as $i => $acc)
        @php
            $pInfo  = $platforms[$acc->platform] ?? ['label'=>ucfirst($acc->platform),'color'=>'#6B7280','bg'=>'#F3F4F6'];
            $sBg    = $acc->status==='active' ? '#DCFCE7' : ($acc->status==='suspended' ? '#FEE2E2' : '#F1F5F9');
            $sColor = $acc->status==='active' ? '#166534' : ($acc->status==='suspended' ? '#991B1B' : '#475569');
        @endphp
        <tr>
            <td class="num-c">{{ $i+1 }}</td>
            <td>
                <div class="nm-main">{{ $acc->name }}</div>
                @if($acc->account_id)<div class="nm-sub">ID: {{ $acc->account_id }}</div>@endif
            </td>
            <td>
                <span class="plat-badge" style="background:{{ $pInfo['bg'] }};color:{{ $pInfo['color'] }};border:1px solid {{ $pInfo['color'] }}20;">{{ $pInfo['label'] }}</span>
            </td>
            <td style="font-size:8px;">{{ $acc->customer?->name ?? '—' }}</td>
            <td>
                @if($acc->username)<div style="font-size:8px;font-weight:600;color:#0F172A;">{{ $acc->username }}</div>@endif
                @if($acc->email)<div style="font-size:7px;color:#94A3B8;">{{ $acc->email }}</div>@endif
                @if(!$acc->username && !$acc->email)<span style="color:#CBD5E1;">—</span>@endif
            </td>
            <td style="font-size:8px;">
                @forelse($acc->users->take(2) as $u)
                    <div>{{ $u->name }}</div>
                @empty
                    <span style="color:#CBD5E1;">—</span>
                @endforelse
                @if($acc->users->count() > 2)
                    <div style="font-size:7px;color:#94A3B8;">+{{ $acc->users->count()-2 }} more</div>
                @endif
            </td>
            <td>
                <span class="badge" style="background:{{ $sBg }};color:{{ $sColor }};border:1px solid {{ $sColor }}30;">
                    {{ ucfirst($acc->status) }}
                </span>
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
    <div class="ft-l">{{ $appName }} &mdash; Social Accounts Register</div>
    <div class="ft-m">Generated {{ $summary['generated_at'] }} &bull; {{ auth()->user()->name }}</div>
    <div class="ft-r">Confidential</div>
</div>

</body>
</html>

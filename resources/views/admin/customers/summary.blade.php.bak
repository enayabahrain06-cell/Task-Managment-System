@extends('layouts.app')
@section('title', 'Customers Summary')

@push('styles')
<style>
.sum-card { background:#fff; border-radius:14px; box-shadow:0 1px 4px rgba(0,0,0,.07); padding:20px; }
.bar-track { background:#f3f4f6; border-radius:999px; height:6px; overflow:hidden; }
.bar-fill  { height:6px; border-radius:999px; transition:width .6s ease; }
.print-only { display:none; }

@media print {
    @page {
        size: A4 landscape;
        margin: 8mm 10mm;
    }

    /* suppress browser URL / date header & footer */
    @page :first { margin-top: 8mm; }
    html { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    a::after, a[href]::after { content: none !important; display: none !important; }

    body { background:#fff !important; font-size:11px; }
    .no-print { display:none !important; }
    .print-only { display:block !important; }
    .print-header-bar { display:flex !important; }

    /* break the app-shell viewport so all content renders */
    html, body { height:auto !important; overflow:visible !important; }
    .app-shell   { height:auto !important; overflow:visible !important; display:block !important; }
    .app-main    { height:auto !important; overflow:visible !important; display:block !important; }
    .app-content {
        height:auto !important;
        max-height:none !important;
        overflow:visible !important;
        flex:none !important;
        padding:0 !important;
        animation:none !important;
        position:static !important;
    }
    .app-sidebar, .app-topbar { display:none !important; }

    /* cards */
    .sum-card {
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 8px !important;
        break-inside: avoid;
    }

    /* KPI grid: tighten for landscape A4 */
    [style*="repeat(6,1fr)"] { gap: 8px !important; }

    /* main 2-col grid: stack for print */
    [style*="2fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>
@endpush

@section('content')
@php
    $overallRate = $summaryTotals['tasks'] > 0
        ? round($summaryTotals['delivered'] / $summaryTotals['tasks'] * 100) : 0;
    $maxTasks = $summaryList->max('tasks_count') ?: 1;
    $palette  = ['#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6'];
    $hasFilter   = !empty($dateFrom) || !empty($dateTo);
    $periodLabel = $hasFilter
        ? (($dateFrom ? $dateFrom->format('M j, Y') : '…') . ' → ' . ($dateTo ? $dateTo->format('M j, Y') : '…'))
        : 'All Time';
@endphp

<div style="padding-bottom:40px;">

    {{-- Header --}}
    <div class="no-print" style="background:linear-gradient(135deg,#4f46e5,#6366f1);border-radius:16px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <a href="{{ route('admin.customers.index') }}" style="color:#c7d2fe;display:flex;align-items:center;gap:6px;font-size:13px;text-decoration:none;flex-shrink:0;">
            <i class="fas fa-arrow-left"></i> Back to Customers
        </a>
        <div style="flex:1;min-width:0;">
            <div style="color:#c7d2fe;font-size:12px;text-transform:uppercase;letter-spacing:.07em;font-weight:600;">All Customers</div>
            <div style="color:#fff;font-size:1.2rem;font-weight:700;margin-top:2px;">Customers Summary</div>
            <div style="color:#a5b4fc;font-size:12px;margin-top:2px;">
                {{ $summaryTotals['customers'] }} customers · {{ $periodLabel }} · Generated {{ now()->format('M j, Y') }}
            </div>
        </div>
        <div style="position:relative;flex-shrink:0;" x-data="{ open: false }" @keydown.escape.window="open=false" @click.outside="open=false">
            <button @click="open=!open"
                    style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:8px 14px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:7px;transition:background .15s;"
                    onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                <i class="fas fa-ellipsis" style="font-size:13px;"></i>
                <i class="fas fa-chevron-down" :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform .2s;font-size:11px;"></i>
            </button>
            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 style="position:absolute;right:0;top:calc(100% + 8px);background:#fff;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.14);border:1px solid #e5e7eb;min-width:170px;z-index:50;overflow:hidden;padding:4px 0;"
                 @click="open=false">
                <button onclick="printSummaryReport()"
                        style="width:100%;text-align:left;padding:9px 14px;background:none;border:none;cursor:pointer;font-size:13px;color:#374151;display:flex;align-items:center;gap:10px;transition:background .1s;"
                        onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                    <i class="fas fa-print" style="color:#6b7280;width:14px;text-align:center;"></i> Print
                </button>
                <button onclick="exportSummaryPDF()"
                        style="width:100%;text-align:left;padding:9px 14px;background:none;border:none;cursor:pointer;font-size:13px;color:#374151;display:flex;align-items:center;gap:10px;transition:background .1s;"
                        onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                    <i class="fas fa-file-pdf" style="color:#ef4444;width:14px;text-align:center;"></i> Export PDF
                </button>
            </div>
        </div>
    </div>

    {{-- Print header --}}
    <div class="print-only print-header-bar" style="align-items:center;justify-content:space-between;background:#4f46e5;color:#fff;border-radius:8px;padding:12px 16px;margin-bottom:16px;">
        <div>
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:.07em;opacity:.8;">Customers Summary · {{ $periodLabel }}</div>
            <div style="font-size:1rem;font-weight:700;">All Customers — {{ $summaryTotals['customers'] }} clients</div>
        </div>
        <div style="text-align:right;font-size:11px;opacity:.8;">Generated {{ now()->format('M j, Y') }}</div>
    </div>

    {{-- KPI row --}}
    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:20px;">
        @foreach([
            ['label'=>'Customers',  'value'=>$summaryTotals['customers'], 'color'=>'#4f46e5', 'bg'=>'#eef2ff', 'icon'=>'fa-building'],
            ['label'=>'Projects',   'value'=>$summaryTotals['projects'],  'color'=>'#0ea5e9', 'bg'=>'#f0f9ff', 'icon'=>'fa-folder-open'],
            ['label'=>'Total Tasks','value'=>$summaryTotals['tasks'],     'color'=>'#6b7280', 'bg'=>'#f9fafb', 'icon'=>'fa-layer-group'],
            ['label'=>'Delivered',  'value'=>$summaryTotals['delivered'], 'color'=>'#10b981', 'bg'=>'#ecfdf5', 'icon'=>'fa-circle-check'],
            ['label'=>'Active',     'value'=>$summaryTotals['active'],    'color'=>'#0284c7', 'bg'=>'#eff6ff', 'icon'=>'fa-spinner'],
            ['label'=>'Overdue',    'value'=>$summaryTotals['overdue'],   'color'=>'#dc2626', 'bg'=>'#fff5f5', 'icon'=>'fa-triangle-exclamation'],
        ] as $kpi)
        <div class="sum-card" style="border-left:4px solid {{ $kpi['color'] }};padding:16px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">{{ $kpi['label'] }}</div>
                <div style="width:28px;height:28px;border-radius:7px;background:{{ $kpi['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas {{ $kpi['icon'] }}" style="color:{{ $kpi['color'] }};font-size:11px;"></i>
                </div>
            </div>
            <div style="font-size:2rem;font-weight:800;color:{{ $kpi['color'] }};line-height:1;">{{ $kpi['value'] }}</div>
            @if($kpi['label'] === 'Delivered' && $summaryTotals['tasks'] > 0)
            <div style="margin-top:6px;font-size:11px;color:#9ca3af;">{{ $overallRate }}% completion rate</div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Main table + bar chart --}}
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:20px;">

        {{-- Customer table --}}
        <div class="sum-card" style="padding:0;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;">
                <div style="font-weight:700;font-size:15px;color:#111827;">
                    <i class="fas fa-building" style="color:#6366f1;margin-right:6px;"></i>All Customers
                </div>
                <span style="font-size:12px;color:#9ca3af;">Sorted by task volume</span>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#f9fafb;border-bottom:1px solid #f3f4f6;">
                            <th style="padding:10px 16px;text-align:left;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">#</th>
                            <th style="padding:10px 16px;text-align:left;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Customer</th>
                            <th style="padding:10px 16px;text-align:center;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Projects</th>
                            <th style="padding:10px 16px;text-align:center;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Tasks</th>
                            <th style="padding:10px 16px;text-align:center;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Delivered</th>
                            <th style="padding:10px 16px;text-align:center;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Rate</th>
                            <th style="padding:10px 16px;text-align:center;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Overdue</th>
                            <th style="padding:10px 16px;text-align:right;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;" class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summaryList as $i => $sc)
                        @php
                            $rate = $sc->tasks_count > 0 ? round($sc->delivered_count / $sc->tasks_count * 100) : 0;
                            $rateColor = $rate >= 80 ? '#059669' : ($rate >= 50 ? '#d97706' : '#dc2626');
                        @endphp
                        <tr style="border-bottom:1px solid #f9fafb;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                            <td style="padding:10px 16px;color:#9ca3af;font-size:12px;">{{ $i + 1 }}</td>
                            <td style="padding:10px 16px;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    @if($sc->logo)
                                    <img src="{{ Storage::url($sc->logo) }}" style="width:30px;height:30px;border-radius:8px;object-fit:contain;background:#fff;border:1px solid #e5e7eb;flex-shrink:0;">
                                    @else
                                    <div style="width:30px;height:30px;border-radius:8px;background:{{ $palette[$i % count($palette)] }};display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                                        {{ strtoupper(substr($sc->name, 0, 1)) }}
                                    </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.customers.show', $sc) }}" style="font-size:13px;font-weight:600;color:#111827;text-decoration:none;">{{ $sc->name }}</a>
                                        @if($sc->company)<div style="font-size:11px;color:#9ca3af;">{{ $sc->company }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding:10px 16px;text-align:center;">
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:7px;background:#eef2ff;color:#4f46e5;font-size:12px;font-weight:700;">{{ $sc->projects_count }}</span>
                            </td>
                            <td style="padding:10px 16px;text-align:center;font-size:13px;font-weight:700;color:#111827;">{{ $sc->tasks_count }}</td>
                            <td style="padding:10px 16px;text-align:center;">
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:7px;background:#ecfdf5;color:#059669;font-size:12px;font-weight:700;">{{ $sc->delivered_count }}</span>
                            </td>
                            <td style="padding:10px 16px;text-align:center;">
                                @if($sc->tasks_count > 0)
                                <span style="font-size:12px;font-weight:700;color:{{ $rateColor }};">{{ $rate }}%</span>
                                @else
                                <span style="color:#d1d5db;font-size:12px;">—</span>
                                @endif
                            </td>
                            <td style="padding:10px 16px;text-align:center;">
                                @if($sc->overdue_count > 0)
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:7px;background:#fff5f5;color:#dc2626;font-size:12px;font-weight:700;">{{ $sc->overdue_count }}</span>
                                @else
                                <span style="color:#d1d5db;font-size:12px;">—</span>
                                @endif
                            </td>
                            <td style="padding:10px 16px;text-align:right;" class="no-print">
                                <div style="display:flex;align-items:center;justify-content:flex-end;gap:5px;">
                                    <a href="{{ route('admin.customers.show', $sc) }}" style="padding:4px 9px;border-radius:6px;background:#f3f4f6;color:#374151;font-size:11px;font-weight:600;text-decoration:none;" title="View"><i class="fas fa-eye" style="font-size:10px;"></i></a>
                                    <a href="{{ route('admin.customers.report', $sc) }}" style="padding:4px 9px;border-radius:6px;background:#eef2ff;color:#4f46e5;font-size:11px;font-weight:600;text-decoration:none;" title="Report"><i class="fas fa-chart-line" style="font-size:10px;"></i></a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Task share bar chart --}}
        <div class="sum-card">
            <div style="font-weight:700;font-size:15px;color:#111827;margin-bottom:4px;">
                <i class="fas fa-chart-bar" style="color:#6366f1;margin-right:6px;"></i>Task Share
            </div>
            <div style="color:#9ca3af;font-size:12px;margin-bottom:16px;">% of total tasks per customer</div>
            @foreach($summaryList->take(10) as $i => $sc)
            @if($sc->tasks_count > 0)
            @php $pct = $summaryTotals['tasks'] > 0 ? round($sc->tasks_count / $summaryTotals['tasks'] * 100) : 0; @endphp
            <div style="margin-bottom:10px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
                    <span style="font-size:12px;font-weight:500;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:65%;">{{ $sc->name }}</span>
                    <span style="font-size:11px;font-weight:700;color:#111827;flex-shrink:0;">{{ $pct }}%</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="background:{{ $palette[$i % count($palette)] }};width:{{ $pct }}%;"></div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>

    {{-- Completion rate ranking --}}
    <div class="sum-card" style="margin-bottom:20px;">
        <div style="font-weight:700;font-size:15px;color:#111827;margin-bottom:16px;">
            <i class="fas fa-trophy" style="color:#f59e0b;margin-right:6px;"></i>Completion Rate Ranking
            <span style="font-size:12px;font-weight:400;color:#9ca3af;margin-left:8px;">(customers with tasks only)</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
            @foreach($summaryList->filter(fn($s) => $s->tasks_count > 0)->sortByDesc(fn($s) => $s->tasks_count > 0 ? $s->delivered_count / $s->tasks_count : 0)->values() as $i => $sc)
            @php
                $rate = round($sc->delivered_count / $sc->tasks_count * 100);
                $rateColor = $rate >= 80 ? '#059669' : ($rate >= 50 ? '#d97706' : '#dc2626');
                $rateBg    = $rate >= 80 ? '#ecfdf5' : ($rate >= 50 ? '#fffbeb' : '#fff5f5');
            @endphp
            <div style="background:#fafafa;border-radius:10px;padding:12px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div style="width:22px;height:22px;border-radius:5px;background:{{ $palette[$i % count($palette)] }};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0;">{{ $i+1 }}</div>
                    <span style="font-size:12px;font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $sc->name }}</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-size:11px;color:#9ca3af;">{{ $sc->delivered_count }}/{{ $sc->tasks_count }}</span>
                    <span style="font-size:13px;font-weight:800;color:{{ $rateColor }};">{{ $rate }}%</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="background:{{ $rateColor }};width:{{ $rate }}%;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

@php
$summaryLogo = '';
if (!empty($appSettings['logo_path'])) {
    $lp = Storage::disk('public')->path($appSettings['logo_path']);
    if (file_exists($lp)) {
        $mime = mime_content_type($lp) ?: 'image/jpeg';
        $summaryLogo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($lp));
    }
}
@endphp

@push('scripts')
<script src="/js/html2pdf.bundle.min.js"></script>
<script>
/* ══════════════════════════════════════════════════════════
   CUSTOMERS SUMMARY — Branded Print & Export PDF
══════════════════════════════════════════════════════════ */
function buildSummaryHTML(immediate) {
    var company   = '{{ addslashes($appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name')) }}';
    var dept      = '{{ addslashes($appSettings['department_name'] ?? 'Operations') }}';
    var logoSrc   = '{{ $summaryLogo ?? '' }}';
    var mono      = company.charAt(0).toUpperCase();
    var dateStr   = '{{ now()->format(config('app.date_format','M d, Y')) }}';
    var timeStr   = '{{ now()->format('H:i') }}';
    var user      = '{{ auth()->user()->name }}';
    var overallRate = {{ $overallRate }};

    var periodFrom  = '{{ $dateFrom ? $dateFrom->format('M j, Y') : '' }}';
    var periodTo    = '{{ $dateTo   ? $dateTo->format('M j, Y')   : '' }}';
    var periodMonth = '{{ $dateFrom ? $dateFrom->format('F Y') . ($dateTo && $dateTo->format('F Y') !== $dateFrom->format('F Y') ? ' – ' . $dateTo->format('F Y') : '') : '' }}';
    var periodLabel = (periodFrom && periodTo)
        ? periodFrom + ' → ' + periodTo
        : (periodFrom ? 'From ' + periodFrom : (periodTo ? 'Until ' + periodTo : 'All Time'));

    var kpis = [
        { label:'Customers',   value:{{ $summaryTotals['customers'] }}, color:'#4F46E5', bg:'#EEF2FF', sub:'Total clients' },
        { label:'Projects',    value:{{ $summaryTotals['projects'] }},  color:'#0EA5E9', bg:'#F0F9FF', sub:'Across all customers' },
        { label:'Total Tasks', value:{{ $summaryTotals['tasks'] }},     color:'#6B7280', bg:'#F9FAFB', sub:periodLabel },
        { label:'Delivered',   value:{{ $summaryTotals['delivered'] }}, color:'#10B981', bg:'#ECFDF5', sub:overallRate + '% — ' + periodLabel },
        { label:'Active',      value:{{ $summaryTotals['active'] }},    color:'#0284C7', bg:'#EFF6FF', sub:'In progress' },
        { label:'Overdue',     value:{{ $summaryTotals['overdue'] }},   color:'#DC2626', bg:'#FEF2F2', sub:'Need attention' },
    ];

    var customers = [
        @foreach($summaryList as $i => $sc)
        @php $scRate = $sc->tasks_count > 0 ? round($sc->delivered_count / $sc->tasks_count * 100) : 0; @endphp
        { name:'{{ addslashes($sc->name) }}', co:'{{ addslashes($sc->company ?? '') }}', projects:{{ $sc->projects_count }}, tasks:{{ $sc->tasks_count }}, delivered:{{ $sc->delivered_count }}, active:{{ $sc->active_count }}, overdue:{{ $sc->overdue_count }}, rate:{{ $scRate }} },
        @endforeach
    ];

    // ── CSS ───────────────────────────────────────────────
    var css =
      '* { box-sizing:border-box; margin:0; padding:0; }'
    + 'body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif; font-size:12px; color:#111827; background:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.pbar { position:sticky; top:0; z-index:100; background:linear-gradient(135deg,#4338CA,#7C3AED); padding:11px 28px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 3px 14px rgba(79,70,229,.35); }'
    + '.pbar-l h2 { font-size:14px; font-weight:700; color:#fff; margin:0; }'
    + '.pbar-l p  { font-size:10.5px; color:rgba(255,255,255,.72); margin:3px 0 0; }'
    + '.pbar-btn  { display:flex; align-items:center; gap:7px; padding:9px 22px; background:#fff; color:#4F46E5; border:none; border-radius:9px; font-size:12px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,.14); }'
    + '.pbar-btn:hover { background:#EEF2FF; }'
    + '.doc  { max-width:980px; margin:0 auto; background:#fff; }'
    + '.accent { height:6px; background:linear-gradient(90deg,#4F46E5 0%,#7C3AED 45%,#06B6D4 100%); -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.dh { padding:20px 32px 16px; display:flex; justify-content:space-between; align-items:center; gap:16px; }'
    + '.logo-area { display:flex; align-items:center; gap:14px; }'
    + '.logo-img  { height:48px; width:auto; max-width:160px; object-fit:contain; }'
    + '.logo-mono { width:48px; height:48px; background:linear-gradient(135deg,#4F46E5,#7C3AED); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:25px; font-weight:900; flex-shrink:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.co-name { font-size:17px; font-weight:800; color:#111827; line-height:1.2; }'
    + '.co-sub  { font-size:10px; color:#9CA3AF; margin-top:3px; }'
    + '.rt { text-align:right; }'
    + '.rt-name { font-size:20px; font-weight:900; color:#4F46E5; letter-spacing:-.4px; line-height:1.15; }'
    + '.rt-badge { display:inline-flex; align-items:center; gap:5px; margin-top:5px; padding:3px 12px; background:#EEF2FF; border-radius:20px; font-size:10px; font-weight:600; color:#6366F1; border:1px solid #C7D2FE; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.hr { height:1px; background:#E5E7EB; }'
    + '.meta { display:flex; background:#F8FAFC; padding:12px 32px; border-bottom:1px solid #E5E7EB; }'
    + '.mi { flex:1; min-width:0; padding-right:20px; border-right:1px solid #E5E7EB; margin-right:20px; }'
    + '.mi:last-child { border-right:none; padding-right:0; margin-right:0; }'
    + '.mi-lbl { font-size:8px; font-weight:700; color:#9CA3AF; text-transform:uppercase; letter-spacing:.09em; }'
    + '.mi-val { font-size:11.5px; font-weight:600; color:#374151; margin-top:3px; }'
    + '.content { padding:22px 32px 26px; }'
    + '.sh { display:flex; align-items:center; gap:10px; margin:22px 0 13px; padding-bottom:9px; border-bottom:1.5px solid #F3F4F6; }'
    + '.sh:first-child { margin-top:0; }'
    + '.sh-bar  { width:4px; height:18px; border-radius:4px; flex-shrink:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.sh-text { font-size:10px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.08em; }'
    + '.sh-pill { margin-left:auto; font-size:9.5px; font-weight:600; color:#6B7280; background:#F3F4F6; padding:2px 9px; border-radius:20px; }'
    + '.kgrid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:4px; }'
    + '.kcard { border-radius:10px; padding:14px 14px 14px 18px; border:1px solid rgba(0,0,0,.06); position:relative; overflow:hidden; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.kacc  { position:absolute; top:0; left:0; width:4px; height:100%; border-radius:10px 0 0 10px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.k-lbl { font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; margin-bottom:7px; }'
    + '.k-val { font-size:30px; font-weight:900; color:#111827; line-height:1; letter-spacing:-1px; }'
    + '.k-sub { font-size:9.5px; color:#9CA3AF; margin-top:5px; }'
    + '.twrap { border-radius:10px; border:1px solid #E5E7EB; overflow:hidden; margin-bottom:4px; }'
    + 'table  { width:100%; border-collapse:collapse; }'
    + 'thead tr { background:linear-gradient(90deg,#4F46E5,#6366F1); -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + 'th { font-size:9.5px; font-weight:700; color:#fff; text-align:left; padding:9px 12px; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }'
    + 'td { padding:9px 12px; border-bottom:1px solid #F3F4F6; font-size:12px; color:#374151; vertical-align:middle; }'
    + 'tbody tr:last-child td { border-bottom:none; }'
    + 'tbody tr:nth-child(even) td { background:#FAFAFA; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.td-n  { font-weight:600; color:#111827; }'
    + '.td-sm { font-size:10px; color:#9CA3AF; margin-top:1px; }'
    + '.td-c  { text-align:center; font-weight:700; }'
    + '.bt    { height:7px; background:#F3F4F6; border-radius:99px; overflow:hidden; min-width:80px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.bf    { height:7px; border-radius:99px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.rb    { display:inline-block; padding:3px 10px; border-radius:20px; font-size:10px; font-weight:700; white-space:nowrap; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.cav   { width:28px; height:28px; border-radius:7px; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff; flex-shrink:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.foot  { margin:4px 32px 0; padding:13px 0; border-top:2px solid #4F46E5; display:flex; justify-content:space-between; align-items:center; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.foot-brand { font-size:10px; font-weight:700; color:#4F46E5; }'
    + '.foot-mid   { font-size:10px; color:#9CA3AF; }'
    + '.foot-conf  { font-size:10px; color:#9CA3AF; font-style:italic; }'
    + 'tr { page-break-inside: avoid; -webkit-column-break-inside: avoid; }'
    + '@@media print { .pbar{display:none !important;} body{background:#fff !important;} .doc{max-width:none;} *{-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important;} @@page{size:A4 landscape;margin:0;} }'
    ;

    // ── Palette for customer avatars ─────────────────────
    var palette = ['#6366F1','#0EA5E9','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6'];

    // ── Logo ─────────────────────────────────────────────
    var logoEl = logoSrc
        ? '<img class="logo-img" src="' + logoSrc + '" alt="' + company + '">'
        : '<div class="logo-mono">' + mono + '</div>';

    // ── Print icon SVG ────────────────────────────────────
    var printIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>';

    // ── KPI grid ─────────────────────────────────────────
    var kHtml = '<div class="kgrid">';
    kpis.forEach(function(k) {
        kHtml += '<div class="kcard" style="background:' + k.bg + ';">'
              + '<div class="kacc" style="background:' + k.color + ';"></div>'
              + '<div class="k-lbl" style="color:' + k.color + ';">' + k.label + '</div>'
              + '<div class="k-val">' + k.value + '</div>'
              + '<div class="k-sub">' + k.sub + '</div>'
              + '</div>';
    });
    kHtml += '</div>';

    // ── Customer table ────────────────────────────────────
    var tHtml = '<div class="sh"><div class="sh-bar" style="background:#4F46E5;"></div><div class="sh-text">All Customers</div><span class="sh-pill">' + customers.length + ' clients · ' + periodLabel + '</span></div>';
    tHtml += '<div class="twrap"><table><thead><tr>'
          + '<th style="width:28px;">#</th>'
          + '<th>Customer</th>'
          + '<th style="text-align:center;">Projects</th>'
          + '<th style="text-align:center;">Tasks</th>'
          + '<th style="text-align:center;">Delivered</th>'
          + '<th style="text-align:center;">Active</th>'
          + '<th style="text-align:center;">Overdue</th>'
          + '<th style="min-width:120px;">Progress</th>'
          + '<th style="text-align:right;">Rate</th>'
          + '</tr></thead><tbody>';

    customers.forEach(function(c, i) {
        var col  = c.rate >= 80 ? '#10B981' : (c.rate >= 50 ? '#D97706' : '#DC2626');
        var rbg  = c.rate >= 80 ? '#D1FAE5' : (c.rate >= 50 ? '#FEF3C7' : '#FEE2E2');
        var pct  = palette[i % palette.length];
        var init = c.name.charAt(0).toUpperCase();
        tHtml += '<tr>'
              + '<td style="color:#9CA3AF;font-size:10px;">' + (i+1) + '</td>'
              + '<td><div style="display:flex;align-items:center;gap:9px;">'
              +   '<div class="cav" style="background:' + pct + ';">' + init + '</div>'
              +   '<div><div class="td-n">' + c.name + '</div>' + (c.co ? '<div class="td-sm">' + c.co + '</div>' : '') + '</div>'
              + '</div></td>'
              + '<td class="td-c" style="color:#4F46E5;">' + c.projects + '</td>'
              + '<td class="td-c">' + c.tasks + '</td>'
              + '<td class="td-c" style="color:#10B981;">' + c.delivered + '</td>'
              + '<td class="td-c" style="color:#0284C7;">' + c.active + '</td>'
              + '<td class="td-c" style="color:' + (c.overdue > 0 ? '#DC2626' : '#9CA3AF') + ';">' + (c.overdue > 0 ? c.overdue : '—') + '</td>'
              + '<td>' + (c.tasks > 0 ? '<div class="bt"><div class="bf" style="width:' + c.rate + '%;background:' + col + ';"></div></div>' : '<span style="color:#D1D5DB;">—</span>') + '</td>'
              + '<td style="text-align:right;">' + (c.tasks > 0 ? '<span class="rb" style="background:' + rbg + ';color:' + col + ';">' + c.rate + '%</span>' : '<span style="color:#D1D5DB;">—</span>') + '</td>'
              + '</tr>';
    });
    tHtml += '</tbody></table></div>';

    // ── Preview bar ───────────────────────────────────────
    var pbar = immediate ? '' :
        '<div class="pbar">'
      + '<div class="pbar-l"><h2>Customers Summary &mdash; ' + company + '</h2>'
      + '<p>' + customers.length + ' customers &bull; ' + periodLabel + ' &bull; Generated ' + dateStr + '</p></div>'
      + '<button class="pbar-btn" onclick="window.print()">' + printIcon + ' Print / Save as PDF</button>'
      + '</div>';

    var printOnLoad = immediate ? '<sc'+'ript>window.onload=function(){window.print();}<\/'+'script>' : '';

    return '<!DOCTYPE html><html lang="en"><head>'
         + '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
         + '<title>Customers Summary &mdash; ' + company + '</title>'
         + '<style>' + css + '</style>'
         + printOnLoad
         + '</head><body>'
         + pbar
         + '<div class="doc">'

         /* Accent bar */
         + '<div class="accent"></div>'

         /* Header */
         + '<div class="dh">'
         +   '<div class="logo-area">' + logoEl
         +     '<div><div class="co-name">' + company + '</div><div class="co-sub">' + dept + '</div></div>'
         +   '</div>'
         +   '<div class="rt">'
         +     '<div class="rt-name">Customers Summary</div>'
         +     ''
         +   '</div>'
         + '</div>'
         + '<div class="hr"></div>'

         /* Meta strip */
         + '<div class="meta">'
         +   '<div class="mi"><div class="mi-lbl">Generated</div><div class="mi-val">' + dateStr + ' at ' + timeStr + '</div></div>'
         +   '<div class="mi"><div class="mi-lbl">Period</div><div class="mi-val">' + periodLabel + (periodMonth ? '<div style="font-size:9px;color:#9CA3AF;margin-top:2px;">' + periodMonth + '</div>' : '') + '</div></div>'
         +   '<div class="mi"><div class="mi-lbl">Customers</div><div class="mi-val">' + customers.length + ' clients</div></div>'
         +   '<div class="mi"><div class="mi-lbl">Prepared By</div><div class="mi-val">' + user + '</div></div>'
         +   '<div class="mi"><div class="mi-lbl">Department</div><div class="mi-val">' + dept + '</div></div>'
         + '</div>'

         /* Content */
         + '<div class="content">'
         +   '<div class="sh"><div class="sh-bar" style="background:#4F46E5;"></div><div class="sh-text">Portfolio Overview</div></div>'
         +   kHtml
         +   tHtml
         + '</div>'

         /* Footer */
         + '<div class="foot">'
         +   '<div class="foot-brand">' + company + ' &mdash; Customers Summary</div>'
         +   '<div class="foot-mid">Period: ' + periodLabel + ' &bull; Generated ' + dateStr + ' &bull; ' + user + '</div>'
         +   ''
         + '</div>'

         + '</div></body></html>';
}

function printSummaryReport() {
    var win = window.open('', '_blank');
    if (win) { win.document.write(buildSummaryHTML(true)); win.document.close(); }
    else { alert('Pop-up blocked — please allow pop-ups for this site.'); }
}

function exportSummaryPDF() {
    _pdfDownload(buildSummaryHTML, 'customers-summary.pdf');
}

function _pdfDownload(buildFn, filename) {
    if (typeof html2pdf === 'undefined') { alert('PDF library not loaded yet — please try again in a moment.'); return; }
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(17,24,39,.55);z-index:99999;display:flex;align-items:center;justify-content:center;';
    overlay.innerHTML = '<div style="background:#fff;border-radius:14px;padding:22px 32px;font-size:14px;font-weight:600;color:#374151;display:flex;align-items:center;gap:12px;box-shadow:0 8px 32px rgba(0,0,0,.2);"><div style="width:20px;height:20px;border:3px solid #4F46E5;border-top-color:transparent;border-radius:50%;animation:_pdf_spin .7s linear infinite;flex-shrink:0;"></div>Generating PDF…</div><style>@@keyframes _pdf_spin{to{transform:rotate(360deg)}}</style>';
    document.body.appendChild(overlay);
    var htmlStr = buildFn(false);
    var parser  = new DOMParser();
    var parsed  = parser.parseFromString(htmlStr, 'text/html');
    var cssText = Array.from(parsed.querySelectorAll('style')).map(function(s){ return s.textContent; }).join('\n');
    var docNode = parsed.querySelector('.doc');
    if (!docNode) { document.body.removeChild(overlay); return; }
    var content = '<style>' + cssText + '</style>' + docNode.outerHTML;
    html2pdf().set({
        margin: 0,
        filename: filename,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, allowTaint: true, backgroundColor: '#ffffff', logging: false, windowWidth: 1100 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
        pagebreak: { mode: ['css', 'legacy'], avoid: ['tr', '.kcard'] }
    }).from(content, 'string').save().then(function() {
        document.body.removeChild(overlay);
    }).catch(function(e) {
        document.body.removeChild(overlay);
        alert('PDF generation failed — ' + (e && e.message ? e.message : 'please try again.'));
    });
}

// Auto-trigger when opened with ?export=1: replace page with clean standalone HTML then print
if (new URLSearchParams(window.location.search).get('export') === '1') {
    window.addEventListener('load', function() {
        setTimeout(function() {
            var html = buildSummaryHTML(true);
            document.open();
            document.write(html);
            document.close();
        }, 400);
    });
}
</script>
@endpush

@endsection

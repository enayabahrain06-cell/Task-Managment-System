@extends('layouts.app')
@section('title', 'Reports & Analytics')

@push('head_scripts')
<script src="/js/chart.umd.min.js"></script>
@endpush
@section('content')

{{-- ══ Print CSS ══ --}}
<style>
/* ── Screen layout helpers ── */
.rpt-grid-4  { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:10px; }
.rpt-grid-5  { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin-bottom:10px; }
.rpt-grid-2  { display:grid; grid-template-columns:1fr 1fr;       gap:10px; margin-bottom:10px; }
.rpt-grid-3  { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:10px; }
@media(max-width:1100px){ .rpt-grid-4,.rpt-grid-5 { grid-template-columns:repeat(2,1fr); } }
@media(max-width:900px) { .rpt-grid-2,.rpt-grid-3 { grid-template-columns:1fr; } }
@media(max-width:600px) { .rpt-grid-4,.rpt-grid-5 { grid-template-columns:1fr; } }
/* Inline sub-grids inside cards */
.rpt-inline-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:10px; min-width:0; }
.rpt-inline-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:14px; min-width:0; }
.rpt-inline-3 > *, .rpt-inline-4 > * { min-width:0; }

/* ── KPI rows: desktop swipe-strip cards vs. real 2-col mobile grid ── */
.rpt-kpi-mobile { display:none; }
@media(max-width:768px) {
    .rpt-kpi-desktop { display:none !important; }
    .rpt-kpi-mobile  { display:block !important; margin-bottom:10px; }
}

/* ── Mobile filter bar: segmented range control + chip-style filters + sticky
   bottom action bar (Summarize / Export). Hidden on desktop; shown <=768px. ── */
.rpt-mobile-filters, .rpt-m-actionbar { display:none; }
@media(max-width:768px) {
    .rpt-mobile-filters { display:block !important; margin-bottom:14px; }

    .rpt-m-seg { display:flex; gap:2px; background:#F3F4F6; border-radius:11px; padding:3px; }
    .rpt-m-seg-btn {
        flex:1; min-height:44px; border:0; border-radius:8px; background:transparent;
        font-family:'Inter',sans-serif; font-size:12.5px; font-weight:600; color:#6B7280; cursor:pointer;
    }
    .rpt-m-seg-btn.is-on {
        background:#fff; color:var(--mob-brand,#4F46E5); font-weight:700;
        box-shadow:0 1px 4px rgba(17,24,39,.08);
    }

    .rpt-m-chiprow {
        display:flex; gap:7px; overflow-x:auto; -webkit-overflow-scrolling:touch;
        padding:12px 2px 4px; scrollbar-width:none;
    }
    .rpt-m-chiprow::-webkit-scrollbar { display:none; }
    .rpt-m-chip {
        position:relative; flex:0 0 auto; min-height:44px; display:inline-flex; align-items:center; gap:7px;
        padding:0 12px; border:1px solid #E1E4EA; border-radius:10px; background:#fff;
        font-size:12.5px; font-weight:600; color:#374151; white-space:nowrap; max-width:60vw;
    }
    .rpt-m-chip span { overflow:hidden; text-overflow:ellipsis; }
    .rpt-m-chip.is-active { border-color:#C7D2FE; background:#EEF2FF; color:#4F46E5; }
    .rpt-m-chip i { font-size:9px; color:#9CA3AF; flex-shrink:0; }
    /* the native select covers the chip so the OS picker opens on tap */
    .rpt-m-chip select { position:absolute; inset:0; width:100%; height:100%; opacity:0; border:0; }

    /* Sticky action bar — sits above the global bottom tab bar (58px + safe-area) */
    .rpt-m-actionbar {
        display:flex !important; gap:9px; position:fixed; left:0; right:0;
        bottom:calc(58px + env(safe-area-inset-bottom)); z-index:20;
        padding:10px 16px; background:rgba(255,255,255,.94); backdrop-filter:blur(18px);
        border-top:1px solid #E9EBF0;
    }
    .rpt-m-btn-ghost {
        flex:0 0 auto; display:flex; align-items:center; gap:7px; padding:0 18px; min-height:48px;
        border:1px solid #C7D2FE; border-radius:14px; background:#fff; color:var(--mob-brand,#4F46E5);
        font-family:'Inter',sans-serif; font-size:14px; font-weight:700; cursor:pointer;
    }
    .rpt-m-btn-primary {
        width:100%; display:flex; align-items:center; justify-content:center; gap:7px; min-height:48px;
        border:0; border-radius:14px; background:var(--mob-brand-grad, linear-gradient(135deg,#4F46E5,#6366F1));
        color:#fff; font-family:'Inter',sans-serif; font-size:14.5px; font-weight:700; cursor:pointer;
        box-shadow:0 10px 20px -10px rgba(79,70,229,.8);
    }
    .rpt-m-export-menu {
        position:absolute; bottom:calc(100% + 8px); right:0; background:#fff; border:1px solid #E5E7EB;
        border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,.12); min-width:200px; overflow:hidden;
    }
    .rpt-m-export-menu button {
        display:flex; align-items:center; gap:9px; width:100%; min-height:44px; padding:0 14px;
        font-size:13px; font-weight:600; color:#374151; border:none; background:transparent; cursor:pointer; text-align:left;
    }
}

/* ── Mobile ── */
@media(max-width:768px) {
    /* Filter bar: title stays, the dense desktop actions-bar (range/selects/summarize/export)
       is replaced below by .rpt-mobile-filters + .rpt-m-actionbar (segmented control,
       chip-style selects, sticky bottom action bar) — see the "Mobile filter bar" block. */
    #rpt-filter-bar { flex-direction:column !important; align-items:stretch !important; gap:10px !important; }
    #rpt-filter-bar > div:first-child { width:100% !important; }
    #rpt-actions-bar { display:none !important; }
    /* Card grids */
    .rpt-inline-3 { grid-template-columns:repeat(3,1fr) !important; }
    .rpt-inline-4 { grid-template-columns:repeat(2,1fr) !important; }
    /* Room for the new sticky mobile action bar (72px) above the global bottom tab bar */
    .app-content { padding-bottom: 110px !important; }
}

/* ══ Premium mobile pass (reuses shared .mob-* tokens/classes from
   resources/views/layouts/app.blade.php — see "Shared mobile design tokens") ══ */
@media(max-width:768px) {
    /* KPI rows (Row 1 + Row 2 only) → horizontally swipeable strip.
       Scoped to the .mob-kpi-row marker class so the unrelated 3-col/2-col
       card grids further down the page (status/priority/trend, projects)
       keep their normal stacked layout. */
    .rpt-grid-5.mob-kpi-row, .rpt-grid-4.mob-kpi-row,
    .rpt-grid-3.mob-kpi-row, .rpt-grid-2.mob-kpi-row {
        display:flex !important; overflow-x:auto !important; -webkit-overflow-scrolling:touch;
        scroll-snap-type:x mandatory; padding:2px 2px 10px !important; margin-bottom:10px !important;
    }
    .mob-kpi-row::-webkit-scrollbar { display:none; }
    .mob-kpi-row > .rpt-card { flex:0 0 auto !important; min-width:150px !important; scroll-snap-align:start; }

    /* Table → card conversion (reuses the shared .mob-table-cards recipe).
       Higher-specificity selectors so this page's own .rpt-table rules
       (min-width / padding / font-size) can never win the cascade. */
    .mob-table-cards table,
    .mob-table-cards .rpt-table { min-width:0 !important; width:100% !important; }
    .mob-table-cards .rpt-table td,
    .mob-table-cards .rpt-table th { padding:9px 4px !important; font-size:13px !important; }
    .mob-table-cards .rpt-table tr { margin-bottom:var(--mob-sp-2, 10px) !important; }
    /* Title/name cell shown as the card heading — no label, full text, left-aligned */
    .mob-table-cards td.rpt-td-title {
        display:block !important; text-align:left !important;
        padding:4px 4px 10px !important; border-bottom:1px solid #F3F4F6 !important;
    }
    .mob-table-cards td.rpt-td-title::before { content:none !important; }
    .mob-table-cards td.rpt-td-title a,
    .mob-table-cards td.rpt-td-title span {
        max-width:none !important; white-space:normal !important;
        overflow:visible !important; text-overflow:clip !important;
    }
    /* Purely decorative "→" cell (reassigned-tasks table): no label, centered */
    .mob-table-cards td.rpt-td-arrow { justify-content:center !important; }
    .mob-table-cards td.rpt-td-arrow::before { content:none !important; }
    /* Let the Project Performance card list breathe before it needs to scroll */
    .rpt-scroll-wrap { max-height:340px; }

    /* Summarize modal renders its tables via JS (kept untouched) — just make
       sure a wide table scrolls inside its own box instead of forcing the
       whole page to scroll horizontally. */
    #rpt-summ-body { overflow-x:hidden; }
    #rpt-summarize-modal .twrap { overflow-x:auto !important; -webkit-overflow-scrolling:touch; }
    #rpt-summarize-modal .twrap table { min-width:460px; }
    #rpt-summarize-modal .kgrid { grid-template-columns:repeat(2,1fr) !important; }

    /* Modal header icon-buttons (Export PDF, print dropdown trigger, Print Now/Cancel, etc.)
       — real touch target on mobile without touching desktop sizing */
    button[style*="height:30px"][style*="border-radius:8px"] { min-height:44px !important; }
    /* Circular "×" close buttons in stat-detail modals (rpt-summarize, social-posts,
       total-tasks, completed-tasks, ontime-tasks, …) */
    button[style*="width:30px;height:30px;border-radius:50%"] { width:44px !important; height:44px !important; }

    /* Summarize modal: Period filter chips + date inputs need a real touch target */
    #rpt-summarize-modal button, #rpt-summarize-modal input[type=date] { min-height:44px !important; }

    /* Modal search inputs (e.g. Social Posts search) */
    #social-posts-search { min-height:44px !important; }

    /* Tap targets: mini action links/badges under 44px on mobile only (desktop
       keeps its compact inline sizing — these are additive min-height rules) */
    .rpt-pg-btn { min-height:44px !important; display:inline-flex !important; align-items:center !important; justify-content:center !important; box-sizing:border-box; }
    .rpt-manage-link, .rpt-fullpage-link { min-height:44px; }
    .mob-table-cards td[data-label="Review"] a { min-height:44px; }
    .mob-table-cards td.rpt-td-title a { min-height:44px; display:flex; align-items:center; }

    /* Completion-rate bar: flat color at .85 opacity on mobile, matching
       .uds-track-fill — desktop keeps its gradient (background-image is only
       stripped here, revealing the flat background-color set inline). */
    .rpt-rate-fill { background-image:none !important; opacity:.85; }
}

@media(max-width:480px) {
    .rpt-grid-2  { grid-template-columns:1fr !important; }
    .rpt-table th, .rpt-table td { padding:5px 7px !important; font-size:11px !important; }
    #rpt-capture-zone { padding:0 !important; }
    .rpt-inline-3 { grid-template-columns:1fr 1fr !important; }
    .rpt-inline-4 { grid-template-columns:1fr 1fr !important; }
    /* Card padding tighter on phones */
    .rpt-card { padding:10px 8px !important; }
    /* Completion rate bar wraps on small phones */
    .rpt-rate-bar-row { flex-wrap:wrap !important; gap:6px !important; }
    .rpt-rate-bar-row .rpt-rate-avg { display:none !important; }
    /* KPI strip / summarize modal KPI grid: one column on very small phones */
    .mob-kpi-row > .rpt-card { min-width:135px !important; }
    #rpt-summarize-modal .kgrid { grid-template-columns:1fr !important; }
}
@media(max-width:380px) {
    .rpt-inline-4 { grid-template-columns:1fr 1fr !important; }
    .rpt-rate-bar-row { flex-direction:column !important; }
    .rpt-rate-bar-row > div { width:100% !important; }
}

/* Grid items must not overflow their track */
.rpt-grid-2 > *, .rpt-grid-3 > *,
.rpt-grid-4 > *, .rpt-grid-5 > * { min-width:0; }
.rpt-card {
    background:#fff; border-radius:12px;
    border:1px solid #E5E7EB;
    box-shadow:0 1px 3px rgba(0,0,0,.04);
    padding:14px;
    min-width:0;
}
.rpt-section-title {
    font-size:12px; font-weight:700; color:#374151;
    text-transform:uppercase; letter-spacing:.06em;
    margin:0 0 10px; display:flex; align-items:center; gap:7px;
}
.rpt-scroll-wrap { overflow-x:auto; overflow-y:auto; max-height:220px; -webkit-overflow-scrolling:touch; display:block; width:100%; }
.rpt-table { width:100%; border-collapse:collapse; font-size:13px; min-width:560px; }
/* Per-table min-widths based on column count */
#proj-table            { min-width:600px; }
#customer-table        { min-width:660px; }
#approval-speed-table  { min-width:700px; }
#overdue-table         { min-width:680px; }
#reopened-table        { min-width:580px; }
#billing-user-table    { min-width:600px; }
#billing-customer-table{ min-width:520px; }
#ad-budget-table       { min-width:720px; }
.rpt-table th {
    text-align:left; padding:7px 10px;
    font-size:10px; font-weight:700; color:#6B7280;
    text-transform:uppercase; letter-spacing:.05em;
    background:#F9FAFB; border-bottom:1px solid #E5E7EB;
    position:sticky; top:0; z-index:1;
}
.rpt-table td { padding:7px 10px; border-bottom:1px solid #F3F4F6; color:#374151; font-size:12px; }
.rpt-table tr:last-child td { border-bottom:none; }
.rpt-table tr:hover td { background:#FAFAFA; }
.rpt-bar-track { height:6px; background:#F3F4F6; border-radius:4px; overflow:hidden; margin-top:4px; }
.rpt-bar-fill  { height:6px; border-radius:4px; transition:width .3s; }
.rpt-badge { display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600; }
.rpt-rate-circle { position:relative; display:inline-flex; align-items:center; justify-content:center; }

/* ── Priority chips ── */
.chip-low    { background:#D1FAE5;color:#059669; }
.chip-medium { background:#FEF3C7;color:#D97706; }
.chip-high   { background:#FFE4E6;color:#E11D48; }

/* ══ PRINT STYLES ══════════════════════════════════════ */
@media print {
    /* ── Hide app chrome ── */
    .app-sidebar, .app-topbar,
    #rpt-filter-bar, .no-print { display:none !important; }

    /* ── Full-page overflow for printing ── */
    html, body { height:auto !important; overflow:visible !important; background:#fff !important; }
    .app-shell   { display:block !important; height:auto !important; overflow:visible !important; }
    .app-main    { display:block !important; height:auto !important; overflow:visible !important; }
    .app-content { height:auto !important; overflow:visible !important; padding:0 !important; animation:none !important; background:#fff !important; }
    .rpt-scroll-wrap { max-height:none !important; overflow-y:visible !important; }

    /* ── Report area ── */
    /* @page margin:0 removes browser-injected URL/date/title; content fills full A4 */
    #rpt-capture-zone { padding: 0 !important; background:#fff !important; }
    #rpt-main-content { margin:0 !important; padding:0 !important; }
    #rpt-print-header { display:block !important; margin-bottom:24px !important; }

    /* ── Cards: border only, NO break-inside avoid (avoidance causes the page gaps) ── */
    .rpt-card {
        border:1px solid #D1D5DB !important;
        box-shadow:none !important;
    }
    .rpt-grid-4 { grid-template-columns:repeat(4,1fr) !important; }
    .rpt-grid-5 { grid-template-columns:repeat(5,1fr) !important; }
    .rpt-grid-2 { grid-template-columns:1fr 1fr !important; }
    .rpt-grid-3 { grid-template-columns:repeat(3,1fr) !important; }

    /* ── Avoid splitting individual table rows across pages ── */
    .rpt-table tr { break-inside:avoid; page-break-inside:avoid; }

    /* ── Progress bars, badges & gradients print in color ── */
    .rpt-bar-fill  { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rpt-badge     { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    * { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }

    /* Proper margins so content doesn't touch the paper edge */
    @page { size: A4 portrait; margin: 12mm 8mm; }

    /* Hide pagination controls — all rows are expanded during print */
    [id$="-pg"] { display:none !important; }
}

/* Print header (hidden on screen, shown when printing/PDF) */
#rpt-print-header { display:none; }
</style>

{{-- ══ Capture zone: wraps everything html2canvas captures ══ --}}
<div id="rpt-capture-zone" style="background:#F8FAFC;">

{{-- ══ Print / PDF Header (hidden on screen, shown when printing or exporting PDF) ══ --}}
<div id="rpt-print-header" style="margin-bottom:28px;">

    {{-- Top accent bar --}}
    <div style="height:5px;background:linear-gradient(90deg,#4F46E5,#6366F1,#818CF8);border-radius:3px;margin-bottom:20px;"></div>

    {{-- Logo + company block --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        {{-- Left: logo + company name --}}
        <div style="display:flex;align-items:center;gap:14px;">
            @if(!empty($appSettings['logo_path']))
                <img src="{{ Storage::url($appSettings['logo_path']) }}"
                     alt="{{ $appSettings['company_name'] ?? $appSettings['app_name'] ?? 'Logo' }}"
                     style="height:48px;width:auto;max-width:160px;object-fit:contain;"
                     crossorigin="anonymous">
            @else
                <div style="width:44px;height:44px;background:#4F46E5;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <span style="color:#fff;font-size:20px;font-weight:800;line-height:1;">
                        {{ strtoupper(substr($appSettings['company_name'] ?? $appSettings['app_name'] ?? 'D', 0, 1)) }}
                    </span>
                </div>
            @endif
            <div>
                <div style="font-size:18px;font-weight:800;color:#111827;line-height:1.2;">
                    {{ $appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name') }}
                </div>
                @if(!empty($appSettings['app_tagline']))
                <div style="font-size:11px;color:#9CA3AF;margin-top:2px;">{{ $appSettings['app_tagline'] }}</div>
                @endif
            </div>
        </div>
        {{-- Right: report label --}}
        <div style="text-align:right;">
            <div style="font-size:20px;font-weight:800;color:#4F46E5;line-height:1.2;">Reports & Analytics</div>
            <div style="font-size:11px;color:#9CA3AF;margin-top:3px;">Confidential — Internal Use Only</div>
        </div>
    </div>

    {{-- Divider --}}
    <div style="border-top:1.5px solid #E5E7EB;margin-bottom:14px;"></div>

    {{-- Meta row --}}
    <div style="display:flex;gap:32px;flex-wrap:wrap;">
        <div>
            <span style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Generated</span>
            <div style="font-size:12px;font-weight:600;color:#374151;margin-top:2px;">{{ now()->format(config('app.date_format', 'M d, Y')) }} at {{ now()->format('H:i') }}</div>
        </div>
        <div>
            <span style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Period</span>
            <div style="font-size:12px;font-weight:600;color:#374151;margin-top:2px;">
                {{ $from ? $from->format(config('app.date_format', 'M d, Y')).' – '.now()->format(config('app.date_format', 'M d, Y')) : 'All Time' }}
            </div>
        </div>
        <div>
            <span style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Prepared By</span>
            <div style="font-size:12px;font-weight:600;color:#374151;margin-top:2px;">{{ auth()->user()->name }}</div>
        </div>
        <div>
            <span style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Department</span>
            <div style="font-size:12px;font-weight:600;color:#374151;margin-top:2px;">{{ $appSettings['department_name'] ?? 'Operations' }}</div>
        </div>
    </div>

    {{-- Bottom divider --}}
    <div style="border-top:1.5px solid #E5E7EB;margin-top:14px;"></div>
</div>

<div id="rpt-main-content">

{{-- ══ Filter / Action Bar ══ --}}
<div id="rpt-filter-bar" class="no-print" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:10px;width:100%;box-sizing:border-box;">
    <div>
        <h1 style="font-size:18px;font-weight:700;color:#111827;margin:0;">
            @if($selectedUser) {{ $selectedUser->name }} — Employee Report
            @else Reports & Analytics
            @endif
        </h1>
        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">
            {{ $from ? 'From '.$from->format(config('app.date_format', 'M d, Y')).' to '.now()->format(config('app.date_format', 'M d, Y')) : 'All time data' }}
            @if($selectedUser) · {{ ucfirst($selectedUser->role) }}@endif
        </p>
    </div>
    <div id="rpt-actions-bar" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;min-width:0;">

        {{-- Range selector --}}
        <form method="GET" action="{{ route('admin.reports.index') }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            {{-- Hidden range input — set by buttons below, preserved when other filters change --}}
            <input type="hidden" id="rpt-range-input" name="range" value="{{ $range }}">

            <div style="display:flex;align-items:center;gap:2px;background:#F3F4F6;border-radius:9px;padding:3px;">
                @foreach(['7'=>'7D','30'=>'30D','90'=>'90D','365'=>'1Y','all'=>'All'] as $val=>$label)
                <button type="button"
                        onclick="document.getElementById('rpt-range-input').value='{{ $val }}';this.closest('form').submit();"
                        style="padding:5px 13px;font-size:12px;font-weight:600;border:none;border-radius:7px;cursor:pointer;transition:all .15s;{{ $range===$val ? 'background:#fff;color:var(--mob-brand,#4F46E5);box-shadow:0 1px 3px rgba(0,0,0,.1);' : 'background:none;color:#6B7280;' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>

            {{-- Project filter --}}
            <select name="project_id" onchange="this.form.submit()"
                    style="font-size:12px;border:1px solid #E5E7EB;border-radius:8px;padding:7px 28px 7px 10px;background:#fff;color:#374151;outline:none;-webkit-appearance:none;appearance:none;background-image:url(data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E);background-repeat:no-repeat;background-position:right 10px center;">
                <option value="">All Projects</option>
                @foreach($allProjects as $p)
                <option value="{{ $p->id }}" {{ $projectId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>

            {{-- Customer filter --}}
            @if($allCustomers->isNotEmpty())
            <select name="customer_id" onchange="this.form.submit()"
                    style="font-size:12px;border:1px solid #E5E7EB;border-radius:8px;padding:7px 28px 7px 10px;background:#fff;color:#374151;outline:none;-webkit-appearance:none;appearance:none;background-image:url(data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E);background-repeat:no-repeat;background-position:right 10px center;">
                <option value="">All Customers</option>
                @foreach($allCustomers as $c)
                <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @endif

            {{-- User filter --}}
            <select name="user_id" onchange="this.form.submit()"
                    style="font-size:12px;border:1px solid {{ $userId ? '#A5B4FC' : '#E5E7EB' }};border-radius:8px;padding:7px 28px 7px 10px;background:{{ $userId ? '#EEF2FF' : '#fff' }};color:{{ $userId ? '#4F46E5' : '#374151' }};font-weight:{{ $userId ? '600' : 'normal' }};outline:none;-webkit-appearance:none;appearance:none;background-image:url(data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E);background-repeat:no-repeat;background-position:right 10px center;">
                <option value="">All Members</option>
                @foreach($allUsers->groupBy('role') as $role => $members)
                <optgroup label="{{ ucfirst($role) }}s">
                    @foreach($members as $u)
                    <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </form>

        {{-- Summarize button --}}
        <button onclick="openRptSummarize()"
                style="display:inline-flex;align-items:center;gap:7px;padding:7px 14px;background:#fff;color:#4F46E5;border:1.5px solid #C7D2FE;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap;"
                onmouseover="this.style.background='#EEF2FF'" onmouseout="this.style.background='#fff'">
            <i class="fas fa-chart-pie" style="font-size:11px;"></i> Summarize
        </button>

        {{-- Export dropdown --}}
        <div x-data="{ exportOpen: false }" style="position:relative;" @click.outside="exportOpen=false">
            <button @click="exportOpen=!exportOpen"
                    style="display:flex;align-items:center;gap:7px;padding:7px 14px;background:var(--mob-brand,#4F46E5);color:#fff;font-size:12px;font-weight:600;border:none;border-radius:8px;cursor:pointer;transition:background .15s;white-space:nowrap;"
                    onmouseover="this.style.background='#4338CA'" onmouseout="this.style.background='var(--mob-brand,#4F46E5)'">
                <i class="fas fa-file-export" style="font-size:11px;"></i>
                Export
                <i class="fas fa-chevron-down" style="font-size:9px;transition:transform .15s;"
                   :style="exportOpen ? 'transform:rotate(180deg)' : ''"></i>
            </button>
            <div x-show="exportOpen" x-transition
                 style="position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #E5E7EB;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:190px;z-index:200;overflow:hidden;">

                <button onclick="printReport()" @click="exportOpen=false"
                        style="display:flex;align-items:center;gap:9px;padding:10px 14px;font-size:13px;color:#374151;width:100%;border:none;background:transparent;cursor:pointer;text-align:left;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-print" style="font-size:12px;color:#6B7280;width:14px;text-align:center;"></i>
                    Print
                </button>

                <div style="height:1px;background:#F3F4F6;"></div>

                <button id="pdf-btn" onclick="exportPDF()" @click="exportOpen=false"
                        style="display:flex;align-items:center;gap:9px;padding:10px 14px;font-size:13px;color:#374151;width:100%;border:none;background:transparent;cursor:pointer;text-align:left;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-file-pdf" style="font-size:12px;color:#DC2626;width:14px;text-align:center;"></i>
                    Export as PDF
                </button>

                <button id="excel-btn" onclick="exportExcel()" @click="exportOpen=false"
                        style="display:flex;align-items:center;gap:9px;padding:10px 14px;font-size:13px;color:#374151;width:100%;border:none;background:transparent;cursor:pointer;text-align:left;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-file-excel" style="font-size:12px;color:#16A34A;width:14px;text-align:center;"></i>
                    Export as Excel
                </button>

                <div style="height:1px;background:#F3F4F6;"></div>

                <button onclick="openUserExport()" @click="exportOpen=false"
                        style="display:flex;align-items:center;gap:9px;padding:10px 14px;font-size:13px;color:#374151;width:100%;border:none;background:transparent;cursor:pointer;text-align:left;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-users" style="font-size:12px;color:#4F46E5;width:14px;text-align:center;"></i>
                    User Performance
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══ Mobile filter bar (<=768px only) — segmented range control + chip-style
     filters + sticky bottom action bar. Replaces #rpt-actions-bar on mobile;
     posts to the same route/fields so range + filters stay in sync. ══ --}}
<div class="rpt-mobile-filters no-print">
    <form method="GET" action="{{ route('admin.reports.index') }}" id="rpt-m-filter-form">
        <input type="hidden" id="rpt-m-range" name="range" value="{{ $range }}">

        @php
        $rptRangeOptions = [];
        foreach (['7'=>'7D','30'=>'30D','90'=>'90D','365'=>'1Y','all'=>'All'] as $val => $label) {
            $rptRangeOptions[] = [
                'key' => $val,
                'label' => $label,
                'onclick' => "document.getElementById('rpt-m-range').value='{$val}';this.closest('form').submit();",
            ];
        }
        @endphp
        <x-mobile.segmented :options="$rptRangeOptions" :active="(string) $range" />

        <div class="uds-chiprow" style="margin-top:12px;">
            <x-mobile.filter-chip :label="optional($allProjects->firstWhere('id', $projectId))->name ?? 'All projects'" :active="(bool) $projectId">
                <select name="project_id" onchange="this.form.submit()">
                    <option value="">All Projects</option>
                    @foreach($allProjects as $p)
                    <option value="{{ $p->id }}" {{ $projectId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </x-mobile.filter-chip>

            @if($allCustomers->isNotEmpty())
            <x-mobile.filter-chip :label="optional($allCustomers->firstWhere('id', $customerId))->name ?? 'All customers'" :active="(bool) $customerId">
                <select name="customer_id" onchange="this.form.submit()">
                    <option value="">All Customers</option>
                    @foreach($allCustomers as $c)
                    <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </x-mobile.filter-chip>
            @endif

            <x-mobile.filter-chip :label="optional($allUsers->firstWhere('id', $userId))->name ?? 'All members'" :active="(bool) $userId">
                <select name="user_id" onchange="this.form.submit()">
                    <option value="">All Members</option>
                    @foreach($allUsers->groupBy('role') as $role => $members)
                    <optgroup label="{{ ucfirst($role) }}s">
                        @foreach($members as $u)
                        <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </x-mobile.filter-chip>
        </div>
    </form>
</div>

<x-mobile.action-bar class="rpt-m-actionbar">
    <button type="button" onclick="openRptSummarize()" class="uds-btn-ghost">
        <i class="fas fa-chart-pie" style="font-size:12px;"></i> Summarize
    </button>
    <div x-data="{ exportOpen: false }" style="position:relative;flex:1;" @click.outside="exportOpen=false">
        <button type="button" @click="exportOpen=!exportOpen" class="uds-btn-primary" style="width:100%;">
            <i class="fas fa-file-export" style="font-size:12px;"></i> Export
            <i class="fas fa-chevron-up" style="font-size:9px;transition:transform .15s;" :style="exportOpen ? 'transform:rotate(180deg)' : ''"></i>
        </button>
        <div x-show="exportOpen" x-transition x-cloak class="rpt-m-export-menu">
            <button type="button" onclick="printReport()" @click="exportOpen=false">
                <i class="fas fa-print" style="font-size:12px;color:#6B7280;width:14px;text-align:center;"></i> Print
            </button>
            <div style="height:1px;background:#F3F4F6;"></div>
            <button type="button" onclick="exportPDF()" @click="exportOpen=false">
                <i class="fas fa-file-pdf" style="font-size:12px;color:#DC2626;width:14px;text-align:center;"></i> Export as PDF
            </button>
            <button type="button" onclick="exportExcel()" @click="exportOpen=false">
                <i class="fas fa-file-excel" style="font-size:12px;color:#16A34A;width:14px;text-align:center;"></i> Export as Excel
            </button>
            <div style="height:1px;background:#F3F4F6;"></div>
            <button type="button" onclick="openUserExport()" @click="exportOpen=false">
                <i class="fas fa-users" style="font-size:12px;color:#4F46E5;width:14px;text-align:center;"></i> User Performance
            </button>
        </div>
    </div>
</x-mobile.action-bar>

{{-- ══ Summarize Modal ══ --}}
<div id="rpt-summarize-modal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.45);"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:18px;width:100%;max-width:720px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden;">

        {{-- Header --}}
        <div style="padding:18px 22px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-chart-pie" style="color:#4F46E5;font-size:14px;"></i>
                </div>
                <div>
                    <p style="font-size:15px;font-weight:700;color:#111827;margin:0;">Reports Summary</p>
                    <p id="rpt-summ-subtitle" style="font-size:11px;color:#9CA3AF;margin:0;">Loading…</p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                {{-- Print dropdown --}}
                <div style="position:relative;" id="rpt-print-wrap">
                    <button onclick="document.getElementById('rpt-print-menu').style.display=document.getElementById('rpt-print-menu').style.display==='block'?'none':'block'"
                            style="height:30px;padding:0 12px;border-radius:8px;background:#F3F4F6;border:1px solid #E5E7EB;cursor:pointer;font-size:11px;font-weight:600;color:#374151;display:flex;align-items:center;gap:5px;"
                            onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                        <i class="fas fa-print" style="font-size:10px;"></i> Print <i class="fas fa-chevron-down" style="font-size:8px;margin-left:2px;"></i>
                    </button>
                    <div id="rpt-print-menu" style="display:none;position:absolute;top:34px;right:0;background:#fff;border:1px solid #E5E7EB;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:190px;z-index:100;overflow:hidden;">
                        <button onclick="printSummarize();document.getElementById('rpt-print-menu').style.display='none'"
                                style="width:100%;text-align:left;padding:10px 14px;border:none;background:none;cursor:pointer;font-size:12px;font-weight:600;color:#374151;display:flex;align-items:center;gap:8px;"
                                onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='none'">
                            <i class="fas fa-file-alt" style="color:#6366F1;width:14px;"></i> Full Report
                        </button>
                        <div style="height:1px;background:#F3F4F6;margin:0 10px;"></div>
                        <button onclick="openRptSectionPicker();document.getElementById('rpt-print-menu').style.display='none'"
                                style="width:100%;text-align:left;padding:10px 14px;border:none;background:none;cursor:pointer;font-size:12px;font-weight:600;color:#374151;display:flex;align-items:center;gap:8px;"
                                onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='none'">
                            <i class="fas fa-check-square" style="color:#10B981;width:14px;"></i> Print Selection…
                        </button>
                    </div>
                </div>
                <button onclick="exportSummarizePDF()"
                        style="height:30px;padding:0 12px;border-radius:8px;background:#FEF2F2;border:1px solid #FECACA;cursor:pointer;font-size:11px;font-weight:600;color:#DC2626;display:flex;align-items:center;gap:5px;"
                        onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                    <i class="fas fa-file-pdf" style="font-size:10px;"></i> Export PDF
                </button>
                <button onclick="document.getElementById('rpt-summarize-modal').style.display='none';document.getElementById('rpt-print-menu').style.display='none'"
                        style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;font-size:16px;color:#6B7280;display:flex;align-items:center;justify-content:center;">×</button>
            </div>
        </div>

        {{-- Filter bar --}}
        <div style="padding:10px 22px;border-bottom:1px solid #F3F4F6;background:#FAFAFA;display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex-shrink:0;">
            <span style="font-size:11px;font-weight:600;color:#6B7280;white-space:nowrap;">Period:</span>
            <button id="rpt-chip-all"    onclick="setRptSummPreset('all')"     style="height:26px;padding:0 11px;border-radius:20px;border:1.5px solid #E5E7EB;background:#fff;font-size:11px;font-weight:600;cursor:pointer;color:#374151;">All Time</button>
            <button id="rpt-chip-month"  onclick="setRptSummPreset('month')"   style="height:26px;padding:0 11px;border-radius:20px;border:1.5px solid #E5E7EB;background:#fff;font-size:11px;font-weight:600;cursor:pointer;color:#374151;">This Month</button>
            <button id="rpt-chip-last"   onclick="setRptSummPreset('last')"    style="height:26px;padding:0 11px;border-radius:20px;border:1.5px solid #E5E7EB;background:#fff;font-size:11px;font-weight:600;cursor:pointer;color:#374151;">Last Month</button>
            <button id="rpt-chip-3m"     onclick="setRptSummPreset('3m')"      style="height:26px;padding:0 11px;border-radius:20px;border:1.5px solid #E5E7EB;background:#fff;font-size:11px;font-weight:600;cursor:pointer;color:#374151;">Last 3 Months</button>
            <div style="display:flex;align-items:center;gap:5px;margin-left:2px;">
                <input id="rpt-summ-from" type="date" style="height:26px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:11px;padding:0 6px;color:#374151;background:#fff;cursor:pointer;">
                <span style="font-size:11px;color:#9CA3AF;">→</span>
                <input id="rpt-summ-to" type="date" style="height:26px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:11px;padding:0 6px;color:#374151;background:#fff;cursor:pointer;">
                <button onclick="applyRptSummFilter()"
                        style="height:26px;padding:0 12px;border-radius:8px;background:#4F46E5;border:none;font-size:11px;font-weight:600;color:#fff;cursor:pointer;white-space:nowrap;">Apply</button>
            </div>
        </div>

        {{-- Section picker panel (hidden by default) --}}
        <div id="rpt-section-picker" style="display:none;padding:14px 22px;border-bottom:1px solid #E5E7EB;background:#F0FDF4;flex-shrink:0;">
            <p style="font-size:11px;font-weight:700;color:#374151;margin:0 0 10px;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-check-square" style="color:#10B981;"></i> Select sections to print:
            </p>
            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;background:#fff;border:1.5px solid #D1FAE5;border-radius:8px;padding:6px 12px;">
                    <input type="checkbox" id="rpt-sec-kpi1" checked style="accent-color:#10B981;width:14px;height:14px;cursor:pointer;">
                    <i class="fas fa-chart-bar" style="color:#6366F1;font-size:11px;"></i> KPI Stats
                </label>
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;background:#fff;border:1.5px solid #D1FAE5;border-radius:8px;padding:6px 12px;">
                    <input type="checkbox" id="rpt-sec-kpi2" checked style="accent-color:#10B981;width:14px;height:14px;cursor:pointer;">
                    <i class="fas fa-layer-group" style="color:#DC2626;font-size:11px;"></i> Overdue / Projects / Review
                </label>
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;background:#fff;border:1.5px solid #D1FAE5;border-radius:8px;padding:6px 12px;">
                    <input type="checkbox" id="rpt-sec-team" checked style="accent-color:#10B981;width:14px;height:14px;cursor:pointer;">
                    <i class="fas fa-users" style="color:#7C3AED;font-size:11px;"></i> Team Performance
                </label>
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;background:#fff;border:1.5px solid #D1FAE5;border-radius:8px;padding:6px 12px;">
                    <input type="checkbox" id="rpt-sec-customers" checked style="accent-color:#10B981;width:14px;height:14px;cursor:pointer;">
                    <i class="fas fa-building" style="color:#4F46E5;font-size:11px;"></i> Top Customers
                </label>
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;background:#fff;border:1.5px solid #D1FAE5;border-radius:8px;padding:6px 12px;">
                    <input type="checkbox" id="rpt-sec-task-dist" style="accent-color:#10B981;width:14px;height:14px;cursor:pointer;">
                    <i class="fas fa-percent" style="color:#F59E0B;font-size:11px;"></i> Task % by Customer
                </label>
                <label id="rpt-task-dist-pct-wrap" style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;background:#fff;border:1.5px solid #FEF3C7;border-radius:8px;padding:6px 12px;opacity:.4;pointer-events:none;" title="Enable 'Task % by Customer' first">
                    <input type="checkbox" id="rpt-sec-task-as-pct" style="accent-color:#F59E0B;width:14px;height:14px;cursor:pointer;">
                    <i class="fas fa-hashtag" style="color:#F59E0B;font-size:11px;"></i> Show tasks as %
                </label>
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;background:#fff;border:1.5px solid #D1FAE5;border-radius:8px;padding:6px 12px;">
                    <input type="checkbox" id="rpt-sec-task-list" style="accent-color:#10B981;width:14px;height:14px;cursor:pointer;">
                    <i class="fas fa-list" style="color:#0EA5E9;font-size:11px;"></i> Task List (this period)
                </label>
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <div style="flex:1;height:1px;background:#E5E7EB;"></div>
                <span style="font-size:10px;font-weight:600;color:#9CA3AF;white-space:nowrap;">Document sections</span>
                <div style="flex:1;height:1px;background:#E5E7EB;"></div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;background:#fff;border:1.5px solid #EEF2FF;border-radius:8px;padding:6px 12px;">
                    <input type="checkbox" id="rpt-sec-narrative" checked style="accent-color:#4F46E5;width:14px;height:14px;cursor:pointer;">
                    <i class="fas fa-align-left" style="color:#4F46E5;font-size:11px;"></i> Report Summary
                </label>
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;background:#fff;border:1.5px solid #EEF2FF;border-radius:8px;padding:6px 12px;">
                    <input type="checkbox" id="rpt-sec-notes" checked style="accent-color:#4F46E5;width:14px;height:14px;cursor:pointer;">
                    <i class="fas fa-pencil-alt" style="color:#7C3AED;font-size:11px;"></i> Notes &amp; Remarks
                </label>
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;background:#fff;border:1.5px solid #EEF2FF;border-radius:8px;padding:6px 12px;">
                    <input type="checkbox" id="rpt-sec-signature" checked style="accent-color:#4F46E5;width:14px;height:14px;cursor:pointer;">
                    <i class="fas fa-signature" style="color:#0891B2;font-size:11px;"></i> Signature Block
                </label>
            </div>
            <div style="display:flex;gap:8px;">
                <button onclick="doRptSummPrint()"
                        style="height:30px;padding:0 18px;border-radius:8px;background:#10B981;border:none;font-size:12px;font-weight:700;color:#fff;cursor:pointer;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-print" style="font-size:11px;"></i> Print Now
                </button>
                <button onclick="document.getElementById('rpt-section-picker').style.display='none'"
                        style="height:30px;padding:0 14px;border-radius:8px;background:#fff;border:1.5px solid #E5E7EB;font-size:12px;font-weight:600;color:#6B7280;cursor:pointer;">
                    Cancel
                </button>
            </div>
        </div>

        {{-- Scrollable body --}}
        <div style="overflow-y:auto;flex:1;padding:20px 22px;">
            <div id="rpt-summ-body">
                <div style="display:flex;align-items:center;justify-content:center;height:160px;color:#9CA3AF;font-size:13px;gap:10px;">
                    <div style="width:18px;height:18px;border:2.5px solid #6366F1;border-top-color:transparent;border-radius:50%;animation:rptSummSpin .7s linear infinite;"></div>
                    Loading…
                </div>
            </div>
        </div>
    </div>
</div>
<style>@keyframes rptSummSpin{to{transform:rotate(360deg)}}</style>

{{-- ══ User Performance Export Modal ══ --}}
<div id="user-export-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;width:90%;max-width:520px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;">

        {{-- Header --}}
        <div style="padding:16px 20px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <p style="font-size:15px;font-weight:700;color:#111827;margin:0;">Export HR Performance Report</p>
                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Full HR-ready CSV: profile, project history, workload &amp; task details per employee</p>
            </div>
            <button onclick="closeUserExport()" style="background:none;border:none;cursor:pointer;color:#9CA3AF;font-size:18px;line-height:1;padding:2px 4px;">&times;</button>
        </div>

        {{-- Body --}}
        <form id="user-export-form" method="GET" action="{{ route('admin.reports.export-users') }}" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
            <input type="hidden" name="range" value="{{ $range }}">

            {{-- Select all + search --}}
            <div style="padding:12px 20px 8px;border-bottom:1px solid #F3F4F6;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;">
                        <input type="checkbox" id="select-all-users" onchange="toggleAllUsers(this.checked)"
                               style="width:14px;height:14px;accent-color:#4F46E5;cursor:pointer;">
                        Select All ({{ $teamMembers->count() }} users)
                    </label>
                    <span id="export-selected-count" style="font-size:11px;color:#9CA3AF;">0 selected</span>
                </div>
            </div>

            {{-- User list --}}
            <div style="flex:1;overflow-y:auto;padding:8px 20px;">
                @foreach($teamMembers->sortBy('name') as $member)
                <label style="display:flex;align-items:center;gap:10px;padding:8px 6px;border-radius:8px;cursor:pointer;transition:background .1s;"
                       onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                    <input type="checkbox" name="user_ids[]" value="{{ $member['id'] }}"
                           class="user-export-cb"
                           onchange="updateExportCount()"
                           style="width:14px;height:14px;accent-color:#4F46E5;cursor:pointer;flex-shrink:0;">
                    <div style="flex:1;min-width:0;">
                        <p style="margin:0;font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $member['name'] }}</p>
                        <p style="margin:0;font-size:11px;color:#9CA3AF;">{{ $member['role'] }}</p>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <p style="margin:0;font-size:12px;font-weight:700;color:{{ $member['rate'] >= 80 ? '#10B981' : ($member['rate'] >= 40 ? '#F59E0B' : '#EF4444') }};">{{ $member['rate'] }}%</p>
                        <p style="margin:0;font-size:10px;color:#9CA3AF;">{{ $member['total'] }} tasks</p>
                    </div>
                </label>
                @endforeach
            </div>

            {{-- Footer --}}
            <div style="padding:12px 20px;border-top:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <div style="font-size:11px;color:#9CA3AF;">
                    Period: <strong style="color:#374151;">{{ $from ? $from->format(config('app.date_format', 'M d, Y')).' – '.now()->format(config('app.date_format', 'M d, Y')) : 'All Time' }}</strong>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button" onclick="closeUserExport()"
                            style="padding:7px 16px;font-size:12px;font-weight:600;color:#374151;background:#F3F4F6;border:none;border-radius:8px;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="button" id="user-pdf-btn" onclick="exportUsersPDF()"
                            style="padding:7px 16px;font-size:12px;font-weight:600;color:#DC2626;background:#FEF2F2;border:1.5px solid #FECACA;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:5px;opacity:.5;"
                            disabled>
                        <i class="fas fa-file-pdf" style="font-size:11px;"></i>
                        <span>Export PDF</span>
                    </button>
                    <button type="submit" id="user-export-submit"
                            style="padding:7px 16px;font-size:12px;font-weight:600;color:#fff;background:#4F46E5;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:5px;opacity:.5;"
                            disabled>
                        <i class="fas fa-download" style="font-size:11px;"></i>
                        <span>Download CSV</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Hidden area used for PDF rendering --}}
<div id="user-perf-pdf-area" style="position:fixed;left:-9999px;top:0;width:900px;background:#fff;padding:32px;font-family:Inter,system-ui,sans-serif;"></div>

{{-- ══ KPI Summary ══ --}}
@php
$empName       = $selectedUser ? $selectedUser->name : null;
$platformIcons = ['facebook'=>['fab fa-facebook','#1877F2'],'instagram'=>['fab fa-instagram','#E1306C'],'twitter'=>['fab fa-x-twitter','#000'],'tiktok'=>['fab fa-tiktok','#010101'],'youtube'=>['fab fa-youtube','#FF0000'],'snapchat'=>['fab fa-snapchat-ghost','#F7CA00'],'linkedin'=>['fab fa-linkedin','#0A66C2'],'other'=>['fas fa-share-nodes','#6366F1']];

// Row 1 — core performance: scope → output → efficiency → quality → risk
$kpisRow1 = [
    ['label' => $empName ? 'Assigned Tasks' : 'Total Tasks',
     'value' => $totalTasks,         'icon'=>'fa-list-check',   'color'=>'#6366F1','bg'=>'#EEF2FF',
     'sub'   => $empName ? 'Assigned to '.$empName : 'In selected period',
     'modal' => 'total-tasks-modal'],
    ['label'=>'Completed',        'value'=>$completedTasks,    'icon'=>'fa-circle-check', 'color'=>'#10B981','bg'=>'#D1FAE5', 'sub'=>'Approved + Delivered',
     'modal' => 'completed-tasks-modal'],
    ['label'=>'Completion Rate',  'value'=>$completionRate.'%','icon'=>'fa-chart-pie',     'color'=>'#F59E0B','bg'=>'#FEF3C7', 'sub'=>'Of all tasks done',
     'modal' => 'completed-tasks-modal'],
    ['label'=>'On-time Rate',     'value'=>$onTimeRate.'%',    'icon'=>'fa-clock',         'color'=>'#8B5CF6','bg'=>'#EDE9FE', 'sub'=>'Before deadline',
     'modal' => 'ontime-tasks-modal'],
    ['label'=>'Overdue',          'value'=>$overdueTasks,      'icon'=>'fa-triangle-exclamation','color'=>'#EF4444','bg'=>'#FEE2E2', 'sub'=>'Need attention'],
];

// Row 2 — context & resources (count varies by filter)
$kpisRow2 = [
    ...($empName ? [] : [
        ['label'=>'Active Projects','value'=>$activeProjects,'icon'=>'fa-diagram-project','color'=>'#3B82F6','bg'=>'#DBEAFE','sub'=>'Currently running'],
    ]),
    ['label' => $empName ? 'Submitted Tasks' : 'Pending Review',
     'value' => $pendingReview,
     'icon'  => 'fa-gavel', 'color'=>'#7C3AED','bg'=>'#EDE9FE',
     'sub'   => 'Awaiting approval'],
    ...($empName ? [] : [
        ['label'=>'Team Members','value'=>$teamMemberCount,'icon'=>'fa-users','color'=>'#059669','bg'=>'#ECFDF5','sub'=>'Active contributors'],
    ]),
    ['label'     => 'Social Posts',
     'value'     => $socialPostsCount,
     'icon'      => 'fa-share-nodes',
     'color'     => '#EC4899','bg'=>'#FCE7F3',
     'sub'       => $socialPendingCount > 0 ? $socialPendingCount.' pending' : 'All posted',
     'sub_color' => $socialPendingCount > 0 ? '#D97706' : '#10B981',
     'platforms' => $socialByPlatform],
    ['label'     => 'Ad Budget',
     'value'     => $adBudgetNumericTotal > 0 ? number_format($adBudgetNumericTotal).' BHD' : '—',
     'icon'      => 'fa-coins',
     'color'     => '#2563EB','bg'=>'#DBEAFE',
     'sub'       => $adBudgetTasks->count().' campaigns',
     'sub_color' => '#6B7280'],
];

$row2Count = count($kpisRow2);
$row2Class = $row2Count === 5 ? 'rpt-grid-5' : ($row2Count === 3 ? 'rpt-grid-3' : ($row2Count === 2 ? 'rpt-grid-2' : 'rpt-grid-4'));
@endphp

{{-- Desktop KPI rows (unchanged) — hidden on mobile in favor of the real
     2-col <x-mobile.kpi-grid> below (see "Mobile KPI grid" block). --}}
<div class="rpt-kpi-desktop">

{{-- Row 1: Core performance (always 5) --}}
<div class="rpt-grid-5 mob-kpi-row">
    @foreach($kpisRow1 as $kpi)
    @php $modal1 = $kpi['modal'] ?? null; @endphp
    <div class="rpt-card" style="padding:10px 12px;{{ $modal1 ? 'cursor:pointer;transition:box-shadow .15s;' : '' }}"
         @if($modal1) onclick="document.getElementById('{{ $modal1 }}').style.display='flex'" onmouseover="this.style.boxShadow='0 4px 16px rgba(99,102,241,.1)'" onmouseout="this.style.boxShadow=''" @endif>
        <div style="display:flex;align-items:center;gap:9px;margin-bottom:6px;">
            <div style="width:28px;height:28px;border-radius:8px;background:{{ $kpi['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas {{ $kpi['icon'] }}" style="color:{{ $kpi['color'] }};font-size:11px;"></i>
            </div>
            <span style="font-size:10px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.04em;line-height:1.2;">{{ $kpi['label'] }}</span>
        </div>
        <p style="font-size:22px;font-weight:800;color:#111827;margin:0;line-height:1;">{{ $kpi['value'] }}</p>
        <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">{{ $kpi['sub'] }}</p>
    </div>
    @endforeach
</div>

{{-- Row 2: Context metrics (5 cols without filter, 3 cols with user filter) --}}
<div class="{{ $row2Class }} mob-kpi-row">
    @foreach($kpisRow2 as $kpi)
    @php $isSocialCard = ($kpi['label'] === 'Social Posts'); @endphp
    <div class="rpt-card" style="padding:10px 12px;{{ $isSocialCard ? 'cursor:pointer;transition:box-shadow .15s;' : '' }}"
         @if($isSocialCard) onclick="document.getElementById('social-posts-modal').style.display='flex';spClearSearch()" onmouseover="this.style.boxShadow='0 4px 16px rgba(236,72,153,.13)'" onmouseout="this.style.boxShadow=''" @endif>
        <div style="display:flex;align-items:center;gap:9px;margin-bottom:6px;">
            <div style="width:28px;height:28px;border-radius:8px;background:{{ $kpi['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas {{ $kpi['icon'] }}" style="color:{{ $kpi['color'] }};font-size:11px;"></i>
            </div>
            <span style="font-size:10px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.04em;line-height:1.2;">{{ $kpi['label'] }}</span>
        </div>
        <p style="font-size:22px;font-weight:800;color:#111827;margin:0;line-height:1;">{{ $kpi['value'] }}</p>
        <p style="font-size:10px;margin:3px 0 0;color:{{ $kpi['sub_color'] ?? '#9CA3AF' }};">{{ $kpi['sub'] }}</p>
        @if(!empty($kpi['platforms']) && $kpi['platforms']->isNotEmpty())
        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:7px;padding-top:7px;border-top:1px solid #F3F4F6;">
            @foreach($kpi['platforms'] as $pl)
            @php $pIco = $platformIcons[$pl->platform] ?? $platformIcons['other']; @endphp
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:600;color:#374151;background:#F9FAFB;border:1px solid #F3F4F6;border-radius:6px;padding:2px 6px;">
                <i class="{{ $pIco[0] }}" style="color:{{ $pIco[1] }};font-size:10px;"></i>
                {{ ucfirst($pl->platform) }} <span style="color:#9CA3AF;font-weight:400;">· {{ $pl->cnt }}</span>
            </span>
            @endforeach
        </div>
        @endif
    </div>
    @endforeach
</div>

</div>{{-- /.rpt-kpi-desktop --}}

{{-- Mobile KPI grid — real 2-col <x-mobile.kpi-grid>, hidden on desktop --}}
<div class="rpt-kpi-mobile">
    <x-mobile.kpi-grid>
        @foreach($kpisRow1 as $kpi)
        <x-mobile.kpi-tile :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub']"
            @if(!empty($kpi['modal'])) onclick="document.getElementById('{{ $kpi['modal'] }}').style.display='flex'" style="cursor:pointer;" @endif />
        @endforeach
        @foreach($kpisRow2 as $kpi)
        @php $isSocialCardM = ($kpi['label'] === 'Social Posts'); @endphp
        <x-mobile.kpi-tile :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub']" :money="$kpi['label'] === 'Ad Budget'"
            @if($isSocialCardM) onclick="document.getElementById('social-posts-modal').style.display='flex';spClearSearch()" style="cursor:pointer;" @endif />
        @endforeach
    </x-mobile.kpi-grid>
</div>

{{-- ══ Social Posts Modal ══ --}}
<div id="social-posts-modal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.45);" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:18px;width:100%;max-width:780px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #F3F4F6;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:9px;background:#FCE7F3;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-share-nodes" style="color:#EC4899;font-size:13px;"></i>
                </div>
                <div>
                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Social Posts</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $socialPostsCount }} published</p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <button onclick="exportSocialPostsPDF()" style="height:30px;padding:0 12px;border-radius:8px;background:#FDF4FF;border:1px solid #E9D5FF;cursor:pointer;font-size:11px;font-weight:600;color:#7C3AED;display:flex;align-items:center;gap:5px;">
                    <i class="fas fa-file-pdf" style="font-size:10px;"></i> Export PDF
                </button>
                <button onclick="document.getElementById('social-posts-modal').style.display='none'" style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;font-size:16px;color:#6B7280;display:flex;align-items:center;justify-content:center;">×</button>
            </div>
        </div>
        {{-- Search bar --}}
        <div style="padding:10px 22px;border-bottom:1px solid #F3F4F6;flex-shrink:0;background:#FAFAFA;">
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:11px;pointer-events:none;"></i>
                <input id="social-posts-search" type="text" placeholder="Search task, customer, platform, poster…"
                    style="width:100%;padding:7px 12px 7px 30px;border:1px solid #E5E7EB;border-radius:8px;font-size:12px;outline:none;box-sizing:border-box;background:#fff;"
                    oninput="spSearch()" onfocus="this.style.borderColor='#EC4899'" onblur="this.style.borderColor='#E5E7EB'">
            </div>
        </div>
        <div style="overflow-y:auto;flex:1;padding:0 22px 22px;">
            @if($socialPostsList->isEmpty())
            <div style="text-align:center;padding:40px 20px;">
                <i class="fas fa-share-nodes" style="font-size:28px;color:#FCE7F3;margin-bottom:10px;"></i>
                <p style="font-size:13px;color:#9CA3AF;margin:0;">No social posts in this period.</p>
            </div>
            @else
            <div style="overflow-x:auto;margin-top:16px;" class="mob-table-cards">
            <table class="rpt-table" id="social-posts-modal-table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Customer</th>
                        <th style="text-align:center;">Platform</th>
                        <th>Posted By</th>
                        <th style="text-align:right;">Date</th>
                        <th style="text-align:center;">Link</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($socialPostsList as $sp)
                @php $spIco = $platformIcons[$sp['platform']] ?? $platformIcons['other']; @endphp
                <tr>
                    <td style="max-width:200px;" class="rpt-td-title" data-label="Task">
                        <a href="{{ route('admin.tasks.show', $sp['task_id']) }}" style="font-weight:600;color:#111827;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" onmouseover="this.style.color='#EC4899'" onmouseout="this.style.color='#111827'" title="{{ $sp['task'] }}">
                            {{ Str::limit($sp['task'], 40) }}
                        </a>
                    </td>
                    <td style="color:#374151;font-size:12px;" data-label="Customer">{{ $sp['customer'] }}</td>
                    <td style="text-align:center;" data-label="Platform">
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#374151;background:#F9FAFB;border:1px solid #F3F4F6;border-radius:6px;padding:2px 8px;">
                            <i class="{{ $spIco[0] }}" style="color:{{ $spIco[1] }};font-size:10px;"></i>
                            {{ ucfirst($sp['platform']) }}
                        </span>
                    </td>
                    <td style="color:#6B7280;font-size:12px;" data-label="Posted By">{{ $sp['poster'] }}</td>
                    <td style="color:#6B7280;font-size:12px;text-align:right;white-space:nowrap;" data-label="Date">{{ $sp['date'] }}</td>
                    <td style="text-align:center;" data-label="Link">
                        @if($sp['url'])
                        <a href="{{ $sp['url'] }}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:3px;padding:3px 8px;background:#FCE7F3;border-radius:6px;font-size:11px;font-weight:600;color:#EC4899;text-decoration:none;" onmouseover="this.style.background='#FBCFE8'" onmouseout="this.style.background='#FCE7F3'">
                            <i class="fas fa-arrow-up-right-from-square" style="font-size:9px;"></i> View
                        </a>
                        @else
                        <span style="font-size:11px;color:#D1D5DB;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            @endif
        </div>
    </div>
</div>

@php
$statusBgMap    = collect(\App\Support\TaskStatusColors::MAP)->map(fn($c) => $c['bg'])->all();
$statusColorMap = collect(\App\Support\TaskStatusColors::MAP)->map(fn($c) => $c['text'])->all();
@endphp

{{-- ══ Total Tasks Modal ══ --}}
<div id="total-tasks-modal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.45);" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:18px;width:100%;max-width:820px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #F3F4F6;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:9px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-list-check" style="color:#6366F1;font-size:13px;"></i>
                </div>
                <div>
                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">All Tasks</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $totalTasks }} in selected period</p>
                </div>
            </div>
            <button onclick="document.getElementById('total-tasks-modal').style.display='none'" style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;font-size:16px;color:#6B7280;display:flex;align-items:center;justify-content:center;">×</button>
        </div>
        <div style="overflow-y:auto;flex:1;padding:0 22px 22px;">
            @if($totalTasksList->isEmpty())
            <p style="text-align:center;color:#9CA3AF;font-size:13px;padding:32px 0;">No tasks in this period.</p>
            @else
            <div style="overflow-x:auto;margin-top:16px;" class="mob-table-cards">
            <table class="rpt-table" id="total-tasks-modal-table">
                <thead><tr><th>Task</th><th>Project</th><th>Customer</th><th>Assignee</th><th style="text-align:center;">Status</th><th style="text-align:right;">Deadline</th></tr></thead>
                <tbody>
                @foreach($totalTasksList as $ct)
                <tr>
                    <td style="max-width:200px;" class="rpt-td-title" data-label="Task"><a href="{{ route('admin.tasks.show', $ct['task_id']) }}" style="font-weight:600;color:#111827;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" onmouseover="this.style.color='#6366F1'" onmouseout="this.style.color='#111827'" title="{{ $ct['title'] }}">{{ Str::limit($ct['title'], 40) }}</a></td>
                    <td style="color:#374151;font-size:12px;" data-label="Project">{{ $ct['project'] }}</td>
                    <td style="color:#6B7280;font-size:12px;" data-label="Customer">{{ $ct['customer'] }}</td>
                    <td style="color:#6B7280;font-size:12px;" data-label="Assignee">{{ $ct['assignee'] }}</td>
                    <td style="text-align:center;" data-label="Status"><span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;white-space:nowrap;background:{{ $statusBgMap[$ct['status']] ?? '#F3F4F6' }};color:{{ $statusColorMap[$ct['status']] ?? '#374151' }};">{{ $ct['status_label'] }}</span></td>
                    <td style="text-align:right;font-size:12px;white-space:nowrap;color:{{ $ct['overdue'] ? '#DC2626' : '#6B7280' }};" data-label="Deadline">{{ $ct['deadline'] ?? '—' }}{{ $ct['overdue'] ? ' ' : '' }}@if($ct['overdue'])<i class="fas fa-triangle-exclamation" style="font-size:10px;"></i>@endif</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══ Completed Tasks Modal ══ --}}
<div id="completed-tasks-modal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.45);" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:18px;width:100%;max-width:820px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #F3F4F6;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:9px;background:#D1FAE5;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-circle-check" style="color:#10B981;font-size:13px;"></i>
                </div>
                <div>
                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Completed Tasks</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $completedTasks }} approved or delivered</p>
                </div>
            </div>
            <button onclick="document.getElementById('completed-tasks-modal').style.display='none'" style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;font-size:16px;color:#6B7280;display:flex;align-items:center;justify-content:center;">×</button>
        </div>
        <div style="overflow-y:auto;flex:1;padding:0 22px 22px;">
            @if($completedTasksList->isEmpty())
            <p style="text-align:center;color:#9CA3AF;font-size:13px;padding:32px 0;">No completed tasks in this period.</p>
            @else
            <div style="overflow-x:auto;margin-top:16px;" class="mob-table-cards">
            <table class="rpt-table" id="completed-tasks-modal-table">
                <thead><tr><th>Task</th><th>Project</th><th>Customer</th><th>Assignee</th><th style="text-align:center;">Status</th><th style="text-align:right;">Deadline</th></tr></thead>
                <tbody>
                @foreach($completedTasksList as $ct)
                <tr>
                    <td style="max-width:200px;" class="rpt-td-title" data-label="Task"><a href="{{ route('admin.tasks.show', $ct['task_id']) }}" style="font-weight:600;color:#111827;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" onmouseover="this.style.color='#10B981'" onmouseout="this.style.color='#111827'" title="{{ $ct['title'] }}">{{ Str::limit($ct['title'], 40) }}</a></td>
                    <td style="color:#374151;font-size:12px;" data-label="Project">{{ $ct['project'] }}</td>
                    <td style="color:#6B7280;font-size:12px;" data-label="Customer">{{ $ct['customer'] }}</td>
                    <td style="color:#6B7280;font-size:12px;" data-label="Assignee">{{ $ct['assignee'] }}</td>
                    <td style="text-align:center;" data-label="Status"><span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;white-space:nowrap;background:{{ $statusBgMap[$ct['status']] ?? '#F3F4F6' }};color:{{ $statusColorMap[$ct['status']] ?? '#374151' }};">{{ $ct['status_label'] }}</span></td>
                    <td style="text-align:right;font-size:12px;white-space:nowrap;color:#6B7280;" data-label="Deadline">{{ $ct['deadline'] ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══ On-time Tasks Modal ══ --}}
<div id="ontime-tasks-modal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.45);" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:18px;width:100%;max-width:820px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #F3F4F6;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:9px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-clock" style="color:#8B5CF6;font-size:13px;"></i>
                </div>
                <div>
                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">On-time Tasks</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $onTimeTasksList->count() }} completed before deadline</p>
                </div>
            </div>
            <button onclick="document.getElementById('ontime-tasks-modal').style.display='none'" style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;font-size:16px;color:#6B7280;display:flex;align-items:center;justify-content:center;">×</button>
        </div>
        <div style="overflow-y:auto;flex:1;padding:0 22px 22px;">
            @if($onTimeTasksList->isEmpty())
            <p style="text-align:center;color:#9CA3AF;font-size:13px;padding:32px 0;">No on-time completions in this period.</p>
            @else
            <div style="overflow-x:auto;margin-top:16px;" class="mob-table-cards">
            <table class="rpt-table" id="ontime-tasks-modal-table">
                <thead><tr><th>Task</th><th>Project</th><th>Customer</th><th>Assignee</th><th style="text-align:center;">Status</th><th style="text-align:right;">Deadline</th></tr></thead>
                <tbody>
                @foreach($onTimeTasksList as $ct)
                <tr>
                    <td style="max-width:200px;" class="rpt-td-title" data-label="Task"><a href="{{ route('admin.tasks.show', $ct['task_id']) }}" style="font-weight:600;color:#111827;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" onmouseover="this.style.color='#8B5CF6'" onmouseout="this.style.color='#111827'" title="{{ $ct['title'] }}">{{ Str::limit($ct['title'], 40) }}</a></td>
                    <td style="color:#374151;font-size:12px;" data-label="Project">{{ $ct['project'] }}</td>
                    <td style="color:#6B7280;font-size:12px;" data-label="Customer">{{ $ct['customer'] }}</td>
                    <td style="color:#6B7280;font-size:12px;" data-label="Assignee">{{ $ct['assignee'] }}</td>
                    <td style="text-align:center;" data-label="Status"><span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;white-space:nowrap;background:{{ $statusBgMap[$ct['status']] ?? '#F3F4F6' }};color:{{ $statusColorMap[$ct['status']] ?? '#374151' }};">{{ $ct['status_label'] }}</span></td>
                    <td style="text-align:right;font-size:12px;white-space:nowrap;color:#6B7280;" data-label="Deadline">{{ $ct['deadline'] ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══ Row 2: Status + Priority + Trend (3-col) ══ --}}
<div class="rpt-grid-3">

    {{-- Status Breakdown --}}
    <div class="rpt-card">
        <p class="rpt-section-title">
            <span style="width:22px;height:22px;border-radius:6px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-bars-progress" style="color:#6366F1;font-size:10px;"></i>
            </span>
            @if($selectedUser) {{ $selectedUser->name }}'s Task Status @else Status Breakdown @endif
        </p>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($statusBreakdown as $key => $s)
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="width:7px;height:7px;border-radius:50%;background:{{ $s['color'] }};display:inline-block;flex-shrink:0;"></span>
                        <span style="font-size:12px;font-weight:600;color:#374151;">{{ $s['label'] }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:12px;font-weight:700;color:#111827;">{{ $s['count'] }}</span>
                        <span style="font-size:10px;color:#9CA3AF;min-width:28px;text-align:right;">{{ $s['pct'] }}%</span>
                    </div>
                </div>
                <div class="rpt-bar-track">
                    <div class="rpt-bar-fill" style="width:{{ $s['pct'] }}%;background:{{ $s['color'] }};"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Priority Distribution --}}
    <div class="rpt-card">
        <p class="rpt-section-title">
            <span style="width:22px;height:22px;border-radius:6px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-flag" style="color:#F59E0B;font-size:10px;"></i>
            </span>
            @if($selectedUser) Priority — {{ $selectedUser->name }}'s Tasks @else Priority Distribution @endif
        </p>
        <div class="rpt-inline-3">
            @foreach($priorityBreakdown as $p => $data)
            <div style="background:{{ $data['bg'] }};border-radius:10px;padding:10px 8px;text-align:center;">
                <p style="font-size:22px;font-weight:800;color:{{ $data['color'] }};margin:0;line-height:1;">{{ $data['count'] }}</p>
                <p style="font-size:11px;font-weight:700;color:{{ $data['color'] }};margin:3px 0 1px;">{{ $data['label'] }}</p>
                <p style="font-size:10px;color:#9CA3AF;margin:0;">{{ $data['pct'] }}%</p>
            </div>
            @endforeach
        </div>
        <div style="position:relative;height:90px;">
            <canvas id="priorityChart"></canvas>
        </div>
    </div>

    {{-- 6-Month Trend --}}
    <div class="rpt-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
            <p class="rpt-section-title" style="margin:0;">
                <span style="width:22px;height:22px;border-radius:6px;background:#DBEAFE;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-chart-line" style="color:#3B82F6;font-size:10px;"></i>
                </span>
                6-Month Trend
            </p>
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="display:flex;align-items:center;gap:4px;">
                    <span style="width:10px;height:3px;border-radius:2px;background:#6366F1;display:inline-block;"></span>
                    <span style="font-size:11px;color:#6B7280;">Created</span>
                </div>
                <div style="display:flex;align-items:center;gap:4px;">
                    <span style="width:10px;height:3px;border-radius:2px;background:#10B981;display:inline-block;"></span>
                    <span style="font-size:11px;color:#6B7280;">Completed</span>
                </div>
            </div>
        </div>
        <div style="position:relative;height:148px;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

</div>

{{-- ══ Row 4: Project Performance (full width) ══ --}}
<div class="rpt-grid-2">

    {{-- Project Performance --}}
    <div class="rpt-card">
        <p class="rpt-section-title">
            <span style="width:22px;height:22px;border-radius:6px;background:#D1FAE5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-diagram-project" style="color:#10B981;font-size:10px;"></i>
            </span>
            @if($selectedUser) Projects Involving {{ $selectedUser->name }} @else Project Performance @endif
        </p>
        @if($projects->isEmpty())
        <p style="text-align:center;color:#9CA3AF;font-size:12px;padding:20px 0;">No project data available.</p>
        @else
        <div class="rpt-scroll-wrap mob-table-cards" style="overflow-x:auto;">
            <table class="rpt-table" id="proj-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Tasks</th>
                        <th style="text-align:center;">Done</th>
                        <th style="text-align:center;">OD</th>
                        <th style="min-width:100px;">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($projects as $proj)
                    @php
                        $pc = \App\Support\ProjectStatusColors::for($proj['status']);
                        $statusColor = [$pc['bg'], $pc['text']];
                    @endphp
                    <tr>
                        <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" class="rpt-td-title" data-label="Project">
                            <span style="font-weight:600;color:#111827;">{{ $proj['name'] }}</span>
                        </td>
                        <td style="text-align:center;" data-label="Status">
                            <span class="rpt-badge" style="background:{{ $statusColor[0] }};color:{{ $statusColor[1] }};font-size:10px;">
                                {{ ucfirst($proj['status']) }}
                            </span>
                        </td>
                        <td style="text-align:center;font-weight:600;" data-label="Tasks">{{ $proj['total'] }}</td>
                        <td style="text-align:center;" data-label="Done"><span style="color:#10B981;font-weight:700;">{{ $proj['completed'] }}</span></td>
                        <td style="text-align:center;" data-label="Overdue">
                            <span style="color:{{ $proj['overdue'] > 0 ? '#EF4444' : '#9CA3AF' }};font-weight:700;">{{ $proj['overdue'] }}</span>
                        </td>
                        <td style="min-width:100px;" data-label="Progress">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <div style="flex:1;height:5px;background:#F3F4F6;border-radius:3px;overflow:hidden;">
                                    <div style="height:5px;width:{{ $proj['rate'] }}%;background:{{ $proj['rate'] >= 80 ? '#10B981' : ($proj['rate'] >= 40 ? '#F59E0B' : '#EF4444') }};border-radius:3px;"></div>
                                </div>
                                <span style="font-size:11px;font-weight:700;color:#374151;min-width:26px;">{{ $proj['rate'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Monthly Task Balance Chart --}}
    @php
        $totalCreated12   = array_sum($balanceCreated);
        $totalDone12      = array_sum($balanceDone);
        $netBalance12     = $totalDone12 - $totalCreated12;
        $bestMonthIdx     = array_search(max($balanceDone ?: [0]), $balanceDone ?: [0]);
        $bestMonth        = $balanceLabels[$bestMonthIdx] ?? '—';
        $avgCompletion    = count($balanceDone) > 0 ? round(array_sum($balanceDone) / count($balanceDone), 1) : 0;
        $completionRate12 = $totalCreated12 > 0 ? round($totalDone12 / $totalCreated12 * 100) : 0;
    @endphp
    <div class="rpt-card" style="display:flex;flex-direction:column;">
        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
            <p class="rpt-section-title" style="margin:0;min-width:0;">
                <span style="width:22px;height:22px;border-radius:6px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-chart-line" style="color:#6366F1;font-size:10px;"></i>
                </span>
                @if($selectedUser) {{ $selectedUser->name }}'s Monthly Balance @else Monthly Task Balance @endif
            </p>
            <div style="display:flex;align-items:center;gap:10px;font-size:10px;color:#6B7280;">
                <span><span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:rgba(16,185,129,.85);margin-right:4px;"></span>Completed</span>
                <span><span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:rgba(99,102,241,.85);margin-right:4px;"></span>Created</span>
            </div>
        </div>

        {{-- Stat pills --}}
        <div class="rpt-inline-4">
            <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:10px 12px;">
                <p style="font-size:10px;font-weight:600;color:#6B7280;margin:0 0 4px;text-transform:uppercase;letter-spacing:.04em;">Completed</p>
                <p style="font-size:22px;font-weight:800;color:#059669;margin:0;line-height:1;">{{ $totalDone12 }}</p>
                <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">last 12 months</p>
            </div>
            <div style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:10px;padding:10px 12px;">
                <p style="font-size:10px;font-weight:600;color:#6B7280;margin:0 0 4px;text-transform:uppercase;letter-spacing:.04em;">Created</p>
                <p style="font-size:22px;font-weight:800;color:#4F46E5;margin:0;line-height:1;">{{ $totalCreated12 }}</p>
                <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">last 12 months</p>
            </div>
            <div style="background:{{ $netBalance12 >= 0 ? '#F0FDF4' : '#FEF2F2' }};border:1px solid {{ $netBalance12 >= 0 ? '#BBF7D0' : '#FECACA' }};border-radius:10px;padding:10px 12px;">
                <p style="font-size:10px;font-weight:600;color:#6B7280;margin:0 0 4px;text-transform:uppercase;letter-spacing:.04em;">Net Balance</p>
                <p style="font-size:22px;font-weight:800;color:{{ $netBalance12 >= 0 ? '#059669' : '#DC2626' }};margin:0;line-height:1;">
                    {{ $netBalance12 >= 0 ? '+' : '' }}{{ $netBalance12 }}
                </p>
                <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">{{ $netBalance12 >= 0 ? 'ahead of backlog' : 'backlog growing' }}</p>
            </div>
            <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:10px 12px;">
                <p style="font-size:10px;font-weight:600;color:#6B7280;margin:0 0 4px;text-transform:uppercase;letter-spacing:.04em;">Best Month</p>
                <p style="font-size:16px;font-weight:800;color:#EA580C;margin:0;line-height:1.2;">{{ $bestMonth }}</p>
                <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">{{ max($balanceDone ?: [0]) }} tasks done</p>
            </div>
        </div>

        {{-- Completion rate bar --}}
        <div class="rpt-rate-bar-row" style="display:flex;align-items:center;gap:10px;margin-bottom:14px;padding:10px 12px;background:#F9FAFB;border-radius:10px;border:1px solid #F0F0F0;">
            <span style="font-size:11px;font-weight:600;color:#374151;white-space:nowrap;">12-Month Rate</span>
            <div style="flex:1;min-width:60px;height:7px;background:#E5E7EB;border-radius:99px;overflow:hidden;">
                <div class="rpt-rate-fill" style="height:7px;width:{{ $completionRate12 }}%;background-color:{{ $completionRate12 >= 80 ? '#059669' : ($completionRate12 >= 50 ? '#D97706' : '#DC2626') }};background-image:{{ $completionRate12 >= 80 ? 'linear-gradient(90deg,#059669,#10B981)' : ($completionRate12 >= 50 ? 'linear-gradient(90deg,#D97706,#F59E0B)' : 'linear-gradient(90deg,#DC2626,#EF4444)') }};border-radius:99px;transition:width .6s;"></div>
            </div>
            <span style="font-size:12px;font-weight:700;color:{{ $completionRate12 >= 80 ? '#059669' : ($completionRate12 >= 50 ? '#D97706' : '#DC2626') }};min-width:34px;text-align:right;">{{ $completionRate12 }}%</span>
            <span class="rpt-rate-avg" style="font-size:10px;color:#9CA3AF;white-space:nowrap;">avg {{ $avgCompletion }}/mo</span>
        </div>

        {{-- Chart --}}
        <div style="flex:1;position:relative;min-height:160px;">
            <canvas id="projCompletionChart" style="max-width:100%;"></canvas>
        </div>
    </div>

</div>

{{-- ══ Row 5: Team Productivity / Employee Performance (full width) ══ --}}
<div class="rpt-card" style="margin-bottom:10px;">
        <p class="rpt-section-title">
            <span style="width:22px;height:22px;border-radius:6px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas {{ $selectedUser ? 'fa-user' : 'fa-users' }}" style="color:#7C3AED;font-size:10px;"></i>
            </span>
            @if($selectedUser) {{ $selectedUser->name }} — Performance Summary @else Team Productivity @endif
        </p>
        @if($teamMembers->isEmpty())
        <p style="text-align:center;color:#9CA3AF;font-size:12px;padding:20px 0;">No team data for this period.</p>
        @else
        <div style="font-size:10px;color:#9CA3AF;margin-bottom:8px;">
            <i class="fas fa-circle-info" style="margin-right:3px;"></i>
            Admin/Manager: counted by tasks <strong>created</strong> &amp; tasks <strong>approved</strong>. &nbsp;Users: counted by assigned tasks.
        </div>
        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;display:block;width:100%;" class="mob-table-cards">
            <table style="width:100%;border-collapse:collapse;font-size:12px;min-width:780px;" id="team-table">
                <thead>
                    <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB;">
                        <th style="text-align:left;padding:7px 12px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Member</th>
                        <th style="text-align:center;padding:7px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Created</th>
                        <th style="text-align:center;padding:7px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Done</th>
                        <th style="text-align:center;padding:7px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Active</th>
                        <th style="text-align:center;padding:7px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;" title="Overdue">OD</th>
                        <th style="text-align:center;padding:7px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Projects</th>
                        <th style="text-align:center;padding:7px 10px;font-size:10px;font-weight:700;color:#F59E0B;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;" title="Tasks reopened by this admin/manager">Reopened</th>
                        <th style="text-align:center;padding:7px 10px;font-size:10px;font-weight:700;color:#6366F1;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;" title="Tasks reassigned by this admin/manager">Reassigned</th>
                        <th style="text-align:center;padding:7px 10px;font-size:10px;font-weight:700;color:#EF4444;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;" title="Users: tasks sent back for revision | Admin/Manager: revision requests they sent">Revisions</th>
                        <th style="padding:7px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;min-width:110px;">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teamMembers->sortByDesc('completed') as $member)
                    @php $isAdmin = ($member['member_type'] ?? 'user') === 'admin'; @endphp
                    <tr style="{{ $isAdmin ? 'background:#F5F3FF;' : '' }}border-bottom:1px solid #F3F4F6;">
                        <td style="padding:8px 12px;" class="rpt-td-title" data-label="Member">
                            <p style="font-weight:600;color:#111827;margin:0;font-size:12px;white-space:nowrap;">{{ $member['name'] }}</p>
                            <span style="font-size:10px;color:{{ $isAdmin ? '#7C3AED' : '#9CA3AF' }};">
                                {{ $member['role'] }}
                                @if($isAdmin)
                                <span style="background:#EDE9FE;color:#7C3AED;border-radius:4px;padding:0 4px;font-size:9px;margin-left:3px;">{{ strtolower($member['role']) === 'admin' ? 'Admin' : 'Manager' }}</span>
                                @endif
                            </span>
                        </td>
                        <td style="text-align:center;padding:8px 10px;" data-label="Created">
                            <span style="color:{{ $isAdmin ? '#7C3AED' : '#6B7280' }};font-weight:700;">{{ $member['total'] }}</span>
                        </td>
                        <td style="text-align:center;padding:8px 10px;" data-label="Done">
                            <span style="color:#10B981;font-weight:700;" title="{{ $isAdmin ? 'Tasks Approved' : 'Tasks Completed' }}">{{ $member['completed'] }}</span>
                        </td>
                        <td style="text-align:center;padding:8px 10px;" data-label="Active"><span style="color:#F59E0B;font-weight:700;">{{ $member['in_progress'] }}</span></td>
                        <td style="text-align:center;padding:8px 10px;" data-label="Overdue">
                            <span style="color:{{ $member['overdue'] > 0 ? '#EF4444' : '#9CA3AF' }};font-weight:700;">{{ $member['overdue'] }}</span>
                        </td>
                        <td style="text-align:center;padding:8px 10px;" data-label="Projects">
                            @if($isAdmin && $member['projects_created'] > 0)
                            <span style="color:#4F46E5;font-weight:700;">{{ $member['projects_created'] }}</span>
                            @else
                            <span style="color:#D1D5DB;">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;padding:8px 10px;" data-label="Reopened">
                            @if($isAdmin)
                            <span style="color:{{ $member['tasks_reopened'] > 0 ? '#F59E0B' : '#9CA3AF' }};font-weight:700;">{{ $member['tasks_reopened'] }}</span>
                            @else
                            <span style="color:#D1D5DB;">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;padding:8px 10px;" data-label="Reassigned">
                            @if($isAdmin)
                            <span style="color:{{ $member['tasks_reassigned'] > 0 ? '#6366F1' : '#9CA3AF' }};font-weight:700;">{{ $member['tasks_reassigned'] }}</span>
                            @else
                            <span style="color:#D1D5DB;">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;padding:8px 10px;" data-label="Revisions">
                            @if(is_null($member['revisions'] ?? null))
                            <span style="color:#D1D5DB;">—</span>
                            @elseif($member['revisions'] > 0)
                            <span style="color:#EF4444;font-weight:700;">{{ $member['revisions'] }}</span>
                            @else
                            <span style="color:#9CA3AF;font-weight:700;">0</span>
                            @endif
                        </td>
                        <td style="padding:8px 10px;min-width:110px;" data-label="Rate">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <div style="flex:1;height:5px;background:#F3F4F6;border-radius:3px;overflow:hidden;">
                                    <div style="height:5px;width:{{ $member['rate'] }}%;background:{{ $member['rate'] >= 80 ? '#10B981' : ($member['rate'] >= 40 ? '#F59E0B' : '#EF4444') }};border-radius:3px;"></div>
                                </div>
                                <span style="font-size:11px;font-weight:700;color:#374151;min-width:26px;">{{ $member['rate'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
</div>

{{-- ══ Row 6: Customer Performance ══ --}}
@if($customerStats->isNotEmpty() && !$selectedUser)
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
        <p class="rpt-section-title" style="margin:0;">
            <span style="width:22px;height:22px;border-radius:6px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-building" style="color:#4F46E5;font-size:10px;"></i>
            </span>
            Customer Performance
        </p>
        <span style="font-size:11px;color:#4F46E5;background:#EEF2FF;padding:2px 9px;border-radius:20px;font-weight:600;">{{ $customerStats->count() }} Customers</span>
    </div>
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;display:block;width:100%;" class="mob-table-cards">
        <table class="rpt-table" id="customer-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th style="text-align:center;">Projects</th>
                    <th style="text-align:center;">Tasks</th>
                    <th style="text-align:center;">Done</th>
                    <th style="text-align:center;">Active</th>
                    <th style="text-align:center;" title="Overdue">OD</th>
                    <th style="min-width:110px;">Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customerStats->sortByDesc('total') as $cust)
                <tr>
                    <td class="rpt-td-title" data-label="Customer">
                        <a href="{{ route('admin.customers.show', $cust['id']) }}"
                           style="font-weight:600;color:#4F46E5;text-decoration:none;font-size:12px;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            {{ $cust['name'] }}
                        </a>
                        @if($cust['company'])
                        <p style="margin:1px 0 0;font-size:10px;color:#9CA3AF;">{{ $cust['company'] }}</p>
                        @endif
                    </td>
                    <td style="text-align:center;font-weight:600;color:#374151;" data-label="Projects">{{ $cust['projects'] }}</td>
                    <td style="text-align:center;font-weight:600;color:#374151;" data-label="Tasks">{{ $cust['total'] }}</td>
                    <td style="text-align:center;" data-label="Done"><span style="color:#10B981;font-weight:700;">{{ $cust['completed'] }}</span></td>
                    <td style="text-align:center;" data-label="Active"><span style="color:#F59E0B;font-weight:700;">{{ $cust['active'] }}</span></td>
                    <td style="text-align:center;" data-label="Overdue">
                        <span style="color:{{ $cust['overdue'] > 0 ? '#EF4444' : '#9CA3AF' }};font-weight:700;">{{ $cust['overdue'] }}</span>
                    </td>
                    <td style="min-width:110px;" data-label="Rate">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="flex:1;height:5px;background:#F3F4F6;border-radius:3px;overflow:hidden;">
                                <div style="height:5px;width:{{ $cust['rate'] }}%;background:{{ $cust['rate'] >= 80 ? '#10B981' : ($cust['rate'] >= 40 ? '#F59E0B' : '#EF4444') }};border-radius:3px;"></div>
                            </div>
                            <span style="font-size:11px;font-weight:700;color:#374151;min-width:26px;">{{ $cust['rate'] }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══ Customer Approval Speed ══ --}}
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <p class="rpt-section-title" style="margin:0;display:flex;align-items:center;gap:8px;">
            <span style="width:22px;height:22px;border-radius:6px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-stopwatch" style="font-size:11px;color:#D97706;"></i>
            </span>
            Customer Approval Speed
        </p>
        @if($approvalSpeedTasks->isNotEmpty())
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:6px;padding:5px 12px;background:#F0FDF4;border-radius:20px;border:1px solid #BBF7D0;">
                <i class="fas fa-circle-check" style="font-size:11px;color:#16A34A;"></i>
                <span style="font-size:12px;font-weight:700;color:#16A34A;">{{ $approvedCount }} approved</span>
            </div>
            @if($pendingApproval > 0)
            <div style="display:flex;align-items:center;gap:6px;padding:5px 12px;background:#FFFBEB;border-radius:20px;border:1px solid #FDE68A;">
                <i class="fas fa-hourglass-half" style="font-size:11px;color:#D97706;"></i>
                <span style="font-size:12px;font-weight:700;color:#D97706;">{{ $pendingApproval }} waiting</span>
            </div>
            @endif
            <a href="{{ route('admin.approvals.index') }}?tab=awaiting"
               style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#EEF2FF;border-radius:20px;border:1px solid #C7D2FE;text-decoration:none;">
                <i class="fas fa-arrow-right" style="font-size:11px;color:#4F46E5;"></i>
                <span style="font-size:12px;font-weight:700;color:#4F46E5;">Awaiting Approvals</span>
            </a>
            @if($avgHours !== null)
            <div style="display:flex;align-items:center;gap:6px;padding:5px 12px;background:#EEF2FF;border-radius:20px;border:1px solid #C7D2FE;">
                <i class="fas fa-clock" style="font-size:11px;color:#4F46E5;"></i>
                <span style="font-size:12px;font-weight:700;color:#4F46E5;">Avg {{ $avgHours }}h to approve</span>
            </div>
            @endif
        </div>
        @endif
    </div>

    @if($approvalSpeedTasks->isEmpty())
    <div style="text-align:center;padding:40px 20px;">
        <div style="width:48px;height:48px;border-radius:14px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <i class="fas fa-stopwatch" style="font-size:20px;color:#D97706;"></i>
        </div>
        <p style="font-size:14px;font-weight:700;color:#111827;margin:0 0 6px;">No approval data yet</p>
        <p style="font-size:12px;color:#9CA3AF;margin:0;max-width:360px;margin:0 auto;">Data appears here once a manager marks a task as <strong>"Awaiting Customer Approval"</strong> from the Approvals page. That action records when the design was sent, and the timer starts.</p>
    </div>
    @else
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;display:block;width:100%;" class="mob-table-cards">
    <table class="rpt-table" id="approval-speed-table">
        <thead>
            <tr>
                <th>Task</th>
                <th>Customer</th>
                <th>Assignee</th>
                <th>Design Sent</th>
                <th>Customer Approved</th>
                <th style="text-align:center;">Time to Approve</th>
                <th style="text-align:center;">Review</th>
            </tr>
        </thead>
        <tbody>
        @foreach($approvalSpeedTasks as $row)
        <tr id="approval-row-{{ $row['id'] }}">
            <td class="rpt-td-title" data-label="Task">
                <a href="{{ route('admin.tasks.show', $row['id']) }}" style="font-weight:600;color:#111827;text-decoration:none;" onmouseover="this.style.color='#4F46E5'" onmouseout="this.style.color='#111827'">
                    {{ Str::limit($row['title'], 40) }}
                </a>
            </td>
            <td style="color:#374151;" data-label="Customer">{{ $row['customer'] }}</td>
            <td style="color:#6B7280;" data-label="Assignee">{{ $row['assignee'] }}</td>
            <td data-label="Design Sent">
                <span style="font-size:12px;color:#374151;font-weight:600;">{{ $row['sent_at'] }}</span>
                <span style="font-size:11px;color:#9CA3AF;margin-left:4px;">{{ $row['sent_time'] }}</span>
            </td>
            <td data-label="Customer Approved">
                @if($row['approved'])
                <span style="font-size:12px;color:#16A34A;font-weight:600;">{{ $row['approved_at'] }}</span>
                <span style="font-size:11px;color:#9CA3AF;margin-left:4px;">{{ $row['approved_time'] }}</span>
                @else
                <span style="font-size:12px;color:#D97706;font-style:italic;">Pending...</span>
                @endif
            </td>
            <td id="approval-timer-{{ $row['id'] }}" style="text-align:center;" data-label="Time to Approve">
                @if($row['approved'])
                @php
                    $h = $row['hours'];
                    $timeStr = $h < 1 ? round($h * 60) . 'm' : ($h < 24 ? $h . 'h' : round($row['days'], 1) . 'd');
                    $timeBg  = $h <= 24 ? '#F0FDF4' : ($h <= 72 ? '#FEF3C7' : '#FEF2F2');
                    $timeCo  = $h <= 24 ? '#16A34A' : ($h <= 72 ? '#D97706' : '#DC2626');
                @endphp
                <span style="font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;background:{{ $timeBg }};color:{{ $timeCo }};">{{ $timeStr }}</span>
                @else
                @php
                    $waitSec = abs(now()->diffInSeconds(\Carbon\Carbon::parse($row['sent_time_raw'] ?? now())));
                    $waitH   = round($waitSec / 3600, 1);
                    $waitStr = $waitSec < 3600 ? (int)round($waitSec/60).'m' : ($waitH < 24 ? $waitH.'h' : round($waitH/24,1).'d');
                @endphp
                <span style="font-size:11px;color:#D97706;font-style:italic;">{{ $waitStr }} waiting</span>
                @endif
            </td>
            <td style="text-align:center;white-space:nowrap;" data-label="Review">
                @if($row['approved'])
                <span style="font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:#F0FDF4;color:#16A34A;">
                    <i class="fas fa-circle-check" style="font-size:10px;margin-right:3px;"></i>Approved
                </span>
                @else
                <a href="{{ route('admin.approvals.index') }}?tab=awaiting"
                   style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:8px;font-size:11px;font-weight:600;color:#4F46E5;text-decoration:none;white-space:nowrap;"
                   onmouseover="this.style.background='#E0E7FF'" onmouseout="this.style.background='#EEF2FF'">
                    <i class="fas fa-arrow-right" style="font-size:9px;"></i> Review
                </a>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>

{{-- ══ Social Pending ══ --}}
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <p class="rpt-section-title" style="margin:0;display:flex;align-items:center;gap:8px;">
            <span style="width:22px;height:22px;border-radius:6px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-share-nodes" style="font-size:11px;color:#7C3AED;"></i>
            </span>
            Social Pending
        </p>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:6px;padding:5px 12px;background:#F5F3FF;border-radius:20px;border:1px solid #DDD6FE;">
                <i class="fas fa-clock" style="font-size:11px;color:#7C3AED;"></i>
                <span style="font-size:12px;font-weight:700;color:#7C3AED;">{{ $socialPendingTasks->count() }} pending</span>
            </div>
            <a href="{{ route('admin.approvals.index') }}?tab=social" class="rpt-manage-link"
               style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#EDE9FE;border-radius:20px;border:1px solid #DDD6FE;text-decoration:none;">
                <i class="fas fa-arrow-right" style="font-size:11px;color:#7C3AED;"></i>
                <span style="font-size:12px;font-weight:700;color:#7C3AED;">Manage in Approvals</span>
            </a>
        </div>
    </div>

    @if($socialPendingTasks->isEmpty())
    <div style="text-align:center;padding:32px 20px;">
        <i class="fas fa-share-nodes" style="font-size:28px;color:#DDD6FE;margin-bottom:10px;"></i>
        <p style="font-size:13px;color:#9CA3AF;margin:0;">No social media posts pending.</p>
    </div>
    @else
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;display:block;width:100%;" class="mob-table-cards">
    <table class="rpt-table">
        <thead>
            <tr>
                <th>Task</th>
                <th>Customer</th>
                <th>Social Assignee</th>
                <th>Platforms</th>
                <th>Deadline</th>
                <th style="text-align:center;">Status</th>
            </tr>
        </thead>
        <tbody>
        @foreach($socialPendingTasks as $task)
        @php
            $spCustomer  = $task->customer?->name ?? $task->project?->customer?->name ?? '—';
            $spOverdue   = $task->deadline && $task->deadline->isPast();
            $spPlatforms = is_array($task->social_platforms) ? $task->social_platforms : [];
            $spStatusMap = collect(\App\Support\TaskStatusColors::MAP)->map(fn($c) => [$c['bg'], $c['text'], $c['label']])->all();
            [$spBg, $spCo, $spLbl] = $spStatusMap[$task->status] ?? ['#F3F4F6','#6B7280', ucfirst($task->status)];
        @endphp
        <tr>
            <td style="max-width:200px;" class="rpt-td-title" data-label="Task">
                <a href="{{ route('admin.tasks.show', $task) }}" style="font-weight:600;color:#111827;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" onmouseover="this.style.color='#7C3AED'" onmouseout="this.style.color='#111827'" title="{{ $task->title }}">
                    {{ Str::limit($task->title, 40) }}
                </a>
            </td>
            <td style="color:#374151;" data-label="Customer">{{ $spCustomer }}</td>
            <td data-label="Social Assignee">
                @if($task->socialAssignee)
                <span style="font-size:12px;font-weight:600;color:#7C3AED;">{{ $task->socialAssignee->name }}</span>
                @else
                <span style="font-size:12px;color:#D1D5DB;">Unassigned</span>
                @endif
            </td>
            <td data-label="Platforms">
                @if(count($spPlatforms))
                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                    @foreach($spPlatforms as $p)
                    <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;background:#EDE9FE;color:#7C3AED;white-space:nowrap;">{{ ucfirst($p) }}</span>
                    @endforeach
                </div>
                @else
                <span style="font-size:12px;color:#D1D5DB;">—</span>
                @endif
            </td>
            <td data-label="Deadline">
                @if($task->deadline)
                <span style="font-size:12px;{{ $spOverdue ? 'color:#DC2626;font-weight:600;' : 'color:#6B7280;' }}white-space:nowrap;">
                    @if($spOverdue)<i class="fas fa-triangle-exclamation" style="font-size:10px;"></i>@endif {{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}
                </span>
                @else
                <span style="font-size:12px;color:#D1D5DB;">—</span>
                @endif
            </td>
            <td style="text-align:center;" data-label="Status">
                <span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;background:{{ $spBg }};color:{{ $spCo }};white-space:nowrap;">{{ $spLbl }}</span>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>

{{-- ══ Decide Later ══ --}}
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <p class="rpt-section-title" style="margin:0;display:flex;align-items:center;gap:8px;">
            <span style="width:22px;height:22px;border-radius:6px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-clock" style="font-size:11px;color:#D97706;"></i>
            </span>
            Decide Later
        </p>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:6px;padding:5px 12px;background:#FFFBEB;border-radius:20px;border:1px solid #FDE68A;">
                <i class="fas fa-clock" style="font-size:11px;color:#D97706;"></i>
                <span style="font-size:12px;font-weight:700;color:#D97706;">{{ $decideLaterReportTasks->count() }} pending decision</span>
            </div>
            <a href="{{ route('admin.approvals.index') }}?tab=decide_later" class="rpt-manage-link"
               style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#FFFBEB;border-radius:20px;border:1px solid #FDE68A;text-decoration:none;">
                <i class="fas fa-arrow-right" style="font-size:11px;color:#D97706;"></i>
                <span style="font-size:12px;font-weight:700;color:#D97706;">Manage in Approvals</span>
            </a>
        </div>
    </div>

    @if($decideLaterReportTasks->isEmpty())
    <div style="text-align:center;padding:32px 20px;">
        <i class="fas fa-clock" style="font-size:28px;color:#FDE68A;margin-bottom:10px;"></i>
        <p style="font-size:13px;color:#9CA3AF;margin:0;">No tasks pending a social media decision.</p>
    </div>
    @else
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;display:block;width:100%;" class="mob-table-cards">
    <table class="rpt-table" id="decide-later-table">
        <thead>
            <tr>
                <th>Task</th>
                <th>Customer</th>
                <th>Assignee</th>
                <th>Status</th>
                <th style="text-align:center;">Review</th>
            </tr>
        </thead>
        <tbody>
        @foreach($decideLaterReportTasks as $task)
        @php
            $dlCustomer = $task->customer?->name ?? $task->project?->customer?->name ?? '—';
            $dlSc = \App\Support\TaskStatusColors::for($task->status);
            [$dlBg, $dlCo, $dlLbl] = [$dlSc['bg'], $dlSc['text'], $dlSc['label']];
        @endphp
        <tr>
            <td style="max-width:220px;" class="rpt-td-title" data-label="Task">
                <a href="{{ route('admin.tasks.show', $task) }}" style="font-weight:600;color:#111827;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" onmouseover="this.style.color='#D97706'" onmouseout="this.style.color='#111827'" title="{{ $task->title }}">
                    {{ Str::limit($task->title, 45) }}
                </a>
            </td>
            <td style="color:#374151;" data-label="Customer">{{ $dlCustomer }}</td>
            <td style="color:#6B7280;" data-label="Assignee">{{ $task->assignee?->name ?? '—' }}</td>
            <td data-label="Status">
                <span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;background:{{ $dlBg }};color:{{ $dlCo }};white-space:nowrap;">{{ $dlLbl }}</span>
            </td>
            <td style="text-align:center;" data-label="Review">
                <a href="{{ route('admin.approvals.index') }}?tab=decide_later"
                   style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;font-size:11px;font-weight:600;color:#D97706;text-decoration:none;white-space:nowrap;"
                   onmouseover="this.style.background='#FEF3C7'" onmouseout="this.style.background='#FFFBEB'">
                    <i class="fas fa-arrow-right" style="font-size:9px;"></i> Decide
                </a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>

{{-- ══ Row 7: Overdue Tasks ══ --}}
@if($overdueList->isNotEmpty())
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
        <p class="rpt-section-title" style="margin:0;">
            <span style="width:22px;height:22px;border-radius:6px;background:#FEE2E2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-triangle-exclamation" style="color:#EF4444;font-size:10px;"></i>
            </span>
            @if($selectedUser) {{ $selectedUser->name }}'s Overdue Tasks ({{ $overdueList->count() }}) @else Overdue Tasks ({{ $overdueList->count() }}) @endif
        </p>
        <span style="font-size:11px;color:#EF4444;background:#FEE2E2;padding:2px 9px;border-radius:20px;font-weight:600;">Needs Attention</span>
    </div>
    <div class="rpt-scroll-wrap mob-table-cards" style="overflow-x:auto;">
        <table class="rpt-table" id="overdue-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Project</th>
                    <th>Assignee</th>
                    <th>Deadline</th>
                    <th style="text-align:center;">Late</th>
                    <th style="text-align:center;">Priority</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($overdueList as $task)
                <tr>
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" class="rpt-td-title" data-label="Task">
                        <span style="font-weight:600;color:#111827;">{{ $task['title'] }}</span>
                    </td>
                    <td style="color:#6B7280;font-size:12px;" data-label="Project">{{ $task['project'] }}</td>
                    <td style="color:#6B7280;font-size:12px;" data-label="Assignee">{{ $task['assignee'] }}</td>
                    <td style="color:#EF4444;font-weight:600;font-size:12px;" data-label="Deadline">{{ $task['deadline'] }}</td>
                    <td style="text-align:center;" data-label="Late">
                        <span style="background:#FEE2E2;color:#DC2626;padding:1px 7px;border-radius:20px;font-size:11px;font-weight:700;">+{{ $task['days_late'] }}d</span>
                    </td>
                    <td style="text-align:center;" data-label="Priority">
                        <span class="rpt-badge chip-{{ $task['priority'] }}" style="font-size:10px;">{{ ucfirst($task['priority']) }}</span>
                    </td>
                    <td style="text-align:center;" data-label="Status">
                        <span style="font-size:10px;background:#F3F4F6;color:#6B7280;padding:1px 7px;border-radius:20px;font-weight:600;">
                            {{ ucwords(str_replace('_',' ',$task['status'])) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══ Row 8: Reopened Tasks ══ --}}
@if($reopenedList->isNotEmpty())
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
        <p class="rpt-section-title" style="margin:0;">
            <span style="width:22px;height:22px;border-radius:6px;background:#FFF7ED;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-rotate-right" style="color:#EA580C;font-size:10px;"></i>
            </span>
            @if($selectedUser) Tasks Reopened by {{ $selectedUser->name }} ({{ $reopenedList->count() }}) @else Reopened Tasks ({{ $reopenedList->count() }}) @endif
        </p>
        <span style="font-size:11px;color:#EA580C;background:#FFF7ED;padding:2px 9px;border-radius:20px;font-weight:600;">Needs Attention</span>
    </div>
    <div class="rpt-scroll-wrap mob-table-cards" style="overflow-x:auto;">
        <table class="rpt-table" id="reopened-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Project</th>
                    <th style="text-align:center;">Was</th>
                    <th style="text-align:center;">Reopened By</th>
                    <th style="text-align:center;">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reopenedList as $row)
                <tr>
                    <td style="font-weight:600;color:#111827;font-size:12px;" class="rpt-td-title" data-label="Task">{{ Str::limit($row['task'], 40) }}</td>
                    <td style="font-size:12px;color:#6B7280;" data-label="Project">{{ $row['project'] }}</td>
                    <td style="text-align:center;" data-label="Was">
                        <span style="font-size:11px;background:#F3F4F6;color:#374151;padding:2px 8px;border-radius:6px;font-weight:600;white-space:nowrap;">{{ $row['old_status'] }}</span>
                    </td>
                    <td style="text-align:center;" data-label="Reopened By">
                        <span style="font-size:11px;color:#EA580C;font-weight:600;white-space:nowrap;">{{ $row['by'] }}</span>
                    </td>
                    <td style="text-align:center;white-space:nowrap;" data-label="Date">
                        <span style="font-size:11px;color:#6B7280;">{{ $row['date'] }}</span>
                        <span style="font-size:10px;color:#9CA3AF;display:block;">{{ $row['time'] }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══ Row 9: Reassigned Tasks ══ --}}
@if($reassignedList->isNotEmpty())
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
        <p class="rpt-section-title" style="margin:0;">
            <span style="width:22px;height:22px;border-radius:6px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-arrows-rotate" style="color:#4F46E5;font-size:10px;"></i>
            </span>
            @if($selectedUser) Reassignments Involving {{ $selectedUser->name }} ({{ $reassignedList->count() }}) @else Reassigned Tasks ({{ $reassignedList->count() }}) @endif
        </p>
        <span style="font-size:11px;color:#4F46E5;background:#EEF2FF;padding:2px 9px;border-radius:20px;font-weight:600;">Assignment Changes</span>
    </div>
    <div class="rpt-scroll-wrap mob-table-cards" style="overflow-x:auto;">
        <table class="rpt-table" id="reassigned-bottom-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Project</th>
                    <th style="text-align:center;">From</th>
                    <th style="text-align:center;"><i class="fas fa-arrow-right" style="font-size:9px;"></i></th>
                    <th style="text-align:center;">To</th>
                    <th style="text-align:center;">Reassigned By</th>
                    <th>Reason</th>
                    <th style="text-align:center;">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reassignedList as $row)
                <tr>
                    <td class="rpt-td-title" data-label="Task">
                        <a href="{{ route('admin.tasks.show', $row['task_id']) }}"
                           style="font-weight:600;color:#4F46E5;font-size:12px;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            {{ Str::limit($row['task'], 40) }}
                        </a>
                    </td>
                    <td style="font-size:12px;color:#6B7280;" data-label="Project">{{ $row['project'] }}</td>
                    <td style="text-align:center;" data-label="From">
                        <span style="font-size:11px;background:#FEE2E2;color:#DC2626;padding:2px 8px;border-radius:6px;font-weight:600;white-space:nowrap;">
                            {{ $row['from_user'] }}
                        </span>
                    </td>
                    <td style="text-align:center;" class="rpt-td-arrow" data-label="">
                        <i class="fas fa-arrow-right" style="color:#9CA3AF;font-size:9px;"></i>
                    </td>
                    <td style="text-align:center;" data-label="To">
                        <span style="font-size:11px;background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:6px;font-weight:600;white-space:nowrap;">
                            {{ $row['to_user'] }}
                        </span>
                    </td>
                    <td style="text-align:center;" data-label="Reassigned By">
                        <span style="font-size:11px;font-weight:600;color:#4F46E5;white-space:nowrap;">{{ $row['by'] }}</span>
                    </td>
                    <td style="font-size:11px;color:#374151;max-width:200px;" data-label="Reason">
                        @if($row['reason'])
                            <span style="font-style:italic;">{{ Str::limit($row['reason'], 80) }}</span>
                        @else
                            <span style="color:#D1D5DB;">—</span>
                        @endif
                    </td>
                    <td style="text-align:center;white-space:nowrap;" data-label="Date">
                        <span style="font-size:11px;color:#6B7280;">{{ $row['date'] }}</span>
                        <span style="font-size:10px;color:#9CA3AF;display:block;">{{ $row['time'] }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══ Time Tracking & Billing ══ --}}
@if($billingUsers->isNotEmpty() || $billingCustomers->isNotEmpty())
@php
    $phaseColors = [
        'work'     => ['bg'=>'#FEF3C7','color'=>'#D97706'],
        'revision' => ['bg'=>'#FEE2E2','color'=>'#DC2626'],
        'review'   => ['bg'=>'#EDE9FE','color'=>'#7C3AED'],
        'social'   => ['bg'=>'#E0F2FE','color'=>'#0284C7'],
    ];
@endphp

{{-- Phase legend (shared) --}}
@php
    $phaseLegend = '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">';
    foreach($phaseLabels as $phaseKey => $phaseLabel) {
        $pc = $phaseColors[$phaseKey] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280'];
        $phaseLegend .= '<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:'.$pc['bg'].';color:'.$pc['color'].';"><span style="width:6px;height:6px;border-radius:50%;background:'.$pc['color'].';display:inline-block;"></span>'.$phaseLabel.'</span>';
    }
    $phaseLegend .= '</div>';
@endphp

{{-- Card 1: By Employee --}}
@if($billingUsers->isNotEmpty())
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
        <div style="width:36px;height:36px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa fa-users" style="color:#6366F1;font-size:15px;"></i>
        </div>
        <div>
            <p class="rpt-section-title" style="margin:0;">
                @if($selectedUser) {{ $selectedUser->name }}'s Time by Phase @else Time Tracking — By Employee @endif
            </p>
            <p style="font-size:11px;color:#9CA3AF;margin:0;">
                @if($selectedUser) Tracked hours for {{ $selectedUser->name }} @else Hours logged per employee, broken down by work phase @endif
                · {{ $from ? 'From '.$from->format(config('app.date_format', 'M d, Y')) : 'All time' }}
            </p>
        </div>
    </div>

    {{-- Phase legend --}}
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
        @foreach($phaseLabels as $phaseKey => $phaseLabel)
        @php $pc = $phaseColors[$phaseKey] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280']; @endphp
        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $pc['bg'] }};color:{{ $pc['color'] }};">
            <span style="width:6px;height:6px;border-radius:50%;background:{{ $pc['color'] }};display:inline-block;"></span>
            {{ $phaseLabel }}
        </span>
        @endforeach
        <span style="font-size:11px;color:#9CA3AF;align-self:center;margin-left:4px;">
            Work &amp; Revision = employee timer · Admin Review = auto-tracked · Social = auto-tracked when posted
        </span>
    </div>

    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;display:block;width:100%;" class="mob-table-cards">
    <table class="rpt-table" id="billing-user-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Role</th>
                @foreach($phaseLabels as $phaseKey => $phaseLabel)
                @php $pc = $phaseColors[$phaseKey] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280']; @endphp
                <th style="color:{{ $pc['color'] }};">{{ $phaseLabel }}</th>
                @endforeach
                <th>Total Hours</th>
                @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1')
                <th>Hourly Rate</th>
                <th style="text-align:right;">Est. Pay</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($billingUsers as $bu)
            @php $hasRate = $bu['hourly_rate'] > 0; @endphp
            <tr>
                <td class="rpt-td-title" data-label="Employee"><span style="font-weight:600;color:#111827;">{{ $bu['name'] }}</span></td>
                <td data-label="Role"><span class="rpt-badge" style="background:#EEF2FF;color:#4F46E5;">{{ ucfirst($bu['role']) }}</span></td>
                @foreach(array_keys($phaseLabels) as $phaseKey)
                @php $secs = $bu['phases'][$phaseKey] ?? 0; $pc = $phaseColors[$phaseKey] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280']; @endphp
                <td data-label="{{ $phaseLabels[$phaseKey] }}">
                    @if($secs > 0)<span style="font-size:12px;font-weight:600;color:{{ $pc['color'] }};">{{ round($secs/3600,1) }}h</span>
                    @else<span style="color:#E5E7EB;">—</span>@endif
                </td>
                @endforeach
                <td data-label="Total Hours"><strong>{{ $bu['hours'] }}h</strong></td>
                @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1')
                <td data-label="Hourly Rate">
                    @if($hasRate)<span style="color:#059669;font-weight:600;">${{ number_format($bu['hourly_rate'],2) }}/hr</span>
                    @else<span style="color:#9CA3AF;font-size:11px;">Not set</span>@endif
                </td>
                <td style="text-align:right;" data-label="Est. Pay">
                    @if($bu['estimated_pay'])<span style="font-weight:700;color:#111827;">${{ number_format($bu['estimated_pay'],2) }}</span>
                    @else<span style="color:#D1D5DB;">—</span>@endif
                </td>
                @endif
            </tr>
            @endforeach
            <tr style="background:#F9FAFB;">
                <td colspan="{{ 2 + count($phaseLabels) }}"><strong style="color:#374151;">Total</strong></td>
                <td data-label="Total Hours"><strong>{{ round($billingUsers->sum('total_seconds') / 3600, 1) }}h</strong></td>
                @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1')
                <td data-label="Hourly Rate"></td>
                <td style="text-align:right;" data-label="Est. Pay">
                    @php $totalPay = $billingUsers->whereNotNull('estimated_pay')->sum('estimated_pay'); @endphp
                    @if($totalPay > 0)<strong style="color:#6366F1;">${{ number_format($totalPay,2) }}</strong>
                    @else<span style="color:#D1D5DB;">—</span>@endif
                </td>
                @endif
            </tr>
        </tbody>
    </table>
    </div>

    <p style="font-size:10px;color:#9CA3AF;margin:10px 0 0;font-style:italic;">
        <i class="fa fa-circle-info" style="margin-right:3px;"></i>
        Hourly rates are set per employee in <a href="{{ route('team.index') }}" style="color:#6366F1;text-decoration:none;">Team → edit user</a>.
    </p>
</div>
@endif

{{-- Card 2: By Customer --}}
@if($billingCustomers->isNotEmpty())
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
        <div style="width:36px;height:36px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa fa-building" style="color:#6366F1;font-size:15px;"></i>
        </div>
        <div>
            <p class="rpt-section-title" style="margin:0;">Billing — By Customer</p>
            <p style="font-size:11px;color:#9CA3AF;margin:0;">
                Estimated cost per customer based on hours × hourly rate
                · {{ $from ? 'From '.$from->format(config('app.date_format', 'M d, Y')) : 'All time' }}
            </p>
        </div>
    </div>

    {{-- Phase legend --}}
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
        @foreach($phaseLabels as $phaseKey => $phaseLabel)
        @php $pc = $phaseColors[$phaseKey] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280']; @endphp
        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $pc['bg'] }};color:{{ $pc['color'] }};">
            <span style="width:6px;height:6px;border-radius:50%;background:{{ $pc['color'] }};display:inline-block;"></span>
            {{ $phaseLabel }}
        </span>
        @endforeach
    </div>

    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;display:block;width:100%;" class="mob-table-cards">
    <table class="rpt-table" id="billing-customer-table">
        <thead>
            <tr>
                <th>Customer</th>
                @foreach($phaseLabels as $phaseKey => $phaseLabel)
                @php $pc = $phaseColors[$phaseKey] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280']; @endphp
                <th style="color:{{ $pc['color'] }};">{{ $phaseLabel }}</th>
                @endforeach
                <th>Total Hours</th>
                @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1')
                <th style="text-align:right;">Est. Cost</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($billingCustomers->sortByDesc('total_seconds') as $bc)
            <tr>
                <td class="rpt-td-title" data-label="Customer">
                    <span style="font-weight:600;color:#111827;">{{ $bc['customer_name'] }}</span>
                    <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:4px;">
                        @foreach($bc['by_user'] as $uName => $ud)
                        <span style="font-size:10px;background:#F3F4F6;color:#374151;padding:1px 6px;border-radius:8px;white-space:nowrap;">
                            {{ $uName }} · {{ round($ud['seconds']/3600,1) }}h
                            @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1' && $ud['rate'] > 0)<span style="color:#059669;"> · ${{ $ud['rate'] }}/hr</span>@endif
                        </span>
                        @endforeach
                    </div>
                </td>
                @foreach(array_keys($phaseLabels) as $phaseKey)
                @php $secs = $bc['phases'][$phaseKey] ?? 0; $pc = $phaseColors[$phaseKey] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280']; @endphp
                <td data-label="{{ $phaseLabels[$phaseKey] }}">
                    @if($secs > 0)<span style="font-size:12px;font-weight:600;color:{{ $pc['color'] }};">{{ round($secs/3600,1) }}h</span>
                    @else<span style="color:#E5E7EB;">—</span>@endif
                </td>
                @endforeach
                <td data-label="Total Hours"><strong>{{ $bc['hours'] }}h</strong></td>
                @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1')
                <td style="text-align:right;" data-label="Est. Cost">
                    @if($bc['estimated_cost'])<span style="font-weight:700;color:#111827;">${{ number_format($bc['estimated_cost'],2) }}</span>
                    @else<span style="color:#D1D5DB;">—</span>@endif
                </td>
                @endif
            </tr>
            @endforeach
            <tr style="background:#F9FAFB;">
                <td colspan="{{ 1 + count($phaseLabels) }}"><strong style="color:#374151;">Total</strong></td>
                <td data-label="Total Hours"><strong>{{ round($billingCustomers->sum('total_seconds') / 3600, 1) }}h</strong></td>
                @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1')
                <td style="text-align:right;" data-label="Est. Cost">
                    @php $grandTotal = $billingCustomers->whereNotNull('estimated_cost')->sum('estimated_cost'); @endphp
                    @if($grandTotal > 0)<strong style="color:#6366F1;">${{ number_format($grandTotal,2) }}</strong>
                    @else<span style="color:#D1D5DB;">—</span>@endif
                </td>
                @endif
            </tr>
        </tbody>
    </table>
    </div>

    <p style="font-size:10px;color:#9CA3AF;margin:10px 0 0;font-style:italic;">
        <i class="fa fa-circle-info" style="margin-right:3px;"></i>
        Review &amp; Social times are recorded automatically — no employee action required.
    </p>
</div>
@endif

@endif

{{-- ══ Ad Budget Monitor ══ --}}
@php
    $budgetPosted  = $adBudgetTasks->where('posted', true)->count();
    $budgetPending = $adBudgetTasks->where('posted', false)->count();
    $budgetTotal   = $adBudgetTasks->count();
    $budgetBhdLabel = $adBudgetNumericTotal > 0 ? number_format($adBudgetNumericTotal).' BHD' : null;
@endphp
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:10px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-wallet" style="color:#D97706;font-size:15px;"></i>
            </div>
            <div>
                <p class="rpt-section-title" style="margin:0;">Ad Budget Monitor</p>
                <p style="font-size:11px;color:#9CA3AF;margin:0;">
                    Social media tasks — {{ $budgetPosted }} posted · {{ $budgetPending }} pending
                    · {{ $from ? 'From '.$from->format(config('app.date_format', 'M d, Y')) : 'All time' }}
                </p>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#FEF3C7;color:#D97706;">
                    <i class="fas fa-hashtag" style="font-size:10px;"></i> {{ $budgetTotal }} tasks
                </span>
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#D1FAE5;color:#059669;">
                    <i class="fas fa-circle-check" style="font-size:10px;"></i> {{ $budgetPosted }} posted
                </span>
                @if($budgetBhdLabel)
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#DBEAFE;color:#1D4ED8;">
                    <i class="fas fa-coins" style="font-size:10px;"></i> {{ $budgetBhdLabel }}
                </span>
                @endif
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#FEE2E2;color:#DC2626;">
                    <i class="fas fa-clock" style="font-size:10px;"></i> {{ $budgetPending }} pending
                </span>
            </div>
            <a href="{{ route('admin.social-budget.index') }}" class="rpt-fullpage-link"
               style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:#FEF3C7;color:#D97706;border:1.5px solid #FDE68A;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;"
               onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i> Full page
            </a>
        </div>
    </div>

    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;display:block;width:100%;" class="mob-table-cards">
    <table class="rpt-table" id="ad-budget-table">
        <thead>
            <tr>
                <th>Task</th>
                <th>Project</th>
                <th>Customer</th>
                <th>Assigned To</th>
                <th>Ad Budget</th>
                <th>Caption</th>
                <th>Status</th>
                <th>Posted On</th>
            </tr>
        </thead>
        <tbody>
            @forelse($adBudgetTasks as $at)
            <tr>
                <td class="rpt-td-title" data-label="Task">
                    <a href="{{ route('admin.tasks.show', $at['id']) }}"
                       style="font-weight:600;color:#111827;text-decoration:none;"
                       onmouseover="this.style.color='#4F46E5'" onmouseout="this.style.color='#111827'">
                        {{ $at['title'] }}
                    </a>
                </td>
                <td style="color:#374151;" data-label="Project">{{ $at['project'] }}</td>
                <td style="color:#374151;" data-label="Customer">{{ $at['customer'] }}</td>
                <td data-label="Assigned To">
                    @if($at['social_user'] !== '—')
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:8px;background:#E0F2FE;color:#0284C7;font-size:11px;font-weight:600;">
                            <i class="fas fa-share-alt" style="font-size:9px;"></i>
                            {{ $at['social_user'] }}
                        </span>
                    @else
                        <span style="color:#D1D5DB;">—</span>
                    @endif
                </td>
                <td data-label="Ad Budget">
                    @if(!empty($at['budget']))
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:8px;background:#FEF3C7;color:#D97706;font-size:12px;font-weight:700;">
                            <i class="fas fa-wallet" style="font-size:10px;"></i>
                            {{ $at['budget'] }}
                        </span>
                    @else
                        <span style="color:#D1D5DB;font-size:11px;">—</span>
                    @endif
                </td>
                <td style="max-width:200px;" data-label="Caption">
                    @if(!empty($at['caption']))
                        <span style="display:block;font-size:11px;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;"
                              title="{{ $at['caption'] }}">{{ $at['caption'] }}</span>
                    @else
                        <span style="color:#D1D5DB;">—</span>
                    @endif
                </td>
                <td data-label="Status">
                    @if($at['posted'])
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:8px;background:#D1FAE5;color:#059669;font-size:11px;font-weight:600;">
                            <i class="fas fa-circle-check" style="font-size:9px;"></i> Posted
                        </span>
                    @else
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:8px;background:#FEF3C7;color:#D97706;font-size:11px;font-weight:600;">
                            <i class="fas fa-clock" style="font-size:9px;"></i> Pending
                        </span>
                    @endif
                </td>
                <td style="color:#6B7280;font-size:11px;" data-label="Posted On">{{ $at['posted_at'] ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;padding:32px 16px;">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:8px;">
                        <div style="width:44px;height:44px;border-radius:12px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-wallet" style="color:#D97706;font-size:18px;"></i>
                        </div>
                        <p style="font-size:13px;font-weight:600;color:#374151;margin:0;">No social media tasks yet</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:0;">When a task is approved with "Yes, assign" for social posting, it will appear here.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

{{-- ══ Footer ══ --}}
<div style="text-align:center;padding:8px 0;color:#9CA3AF;font-size:10px;" class="no-print">
    &copy; {{ now()->year }} {{ $appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name') }}. All rights reserved.
</div>

</div>{{-- #rpt-main-content --}}
</div>{{-- #rpt-capture-zone --}}

@endsection

@php
$summarizeLogo = '';
if (!empty($appSettings['logo_path'])) {
    $lp = Storage::disk('public')->path($appSettings['logo_path']);
    if (file_exists($lp)) {
        $mime = mime_content_type($lp) ?: 'image/jpeg';
        $summarizeLogo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($lp));
    }
}
@endphp

@push('scripts')
{{-- ── Export libraries ── --}}
<script src="/js/html2canvas.min.js"></script>
<script src="/js/jspdf.umd.min.js"></script>
<script src="/js/html2pdf.bundle.min.js"></script>
<script src="/js/xlsx.full.min.js"></script>

<script>
/* ══════════════════════════════════════════════════════════
   Charts
══════════════════════════════════════════════════════════ */
Chart.defaults.font = { family: 'Inter, system-ui, sans-serif', size: 12 };
Chart.defaults.color = '#9CA3AF';

new Chart(document.getElementById('priorityChart'), {
    type: 'bar',
    data: {
        labels: ['Low', 'Medium', 'High'],
        datasets: [{
            data: [
                {{ $priorityBreakdown['low']['count'] ?? 0 }},
                {{ $priorityBreakdown['medium']['count'] ?? 0 }},
                {{ $priorityBreakdown['high']['count'] ?? 0 }}
            ],
            backgroundColor: ['rgba(16,185,129,0.82)','rgba(245,158,11,0.82)','rgba(239,68,68,0.82)'],
            borderRadius: 6, borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: { grid: { color: '#F3F4F6' }, border: { display: false }, beginAtZero: true, ticks: { stepSize: 1, maxTicksLimit: 5 } }
        }
    }
});

new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: @json($monthLabels),
        datasets: [
            { label: 'Created',   data: @json($monthlyCreated),   backgroundColor: 'rgba(99,102,241,0.8)',  borderRadius: 5, borderSkipped: false, barPercentage: 0.55 },
            { label: 'Completed', data: @json($monthlyCompleted), backgroundColor: 'rgba(16,185,129,0.8)', borderRadius: 5, borderSkipped: false, barPercentage: 0.55 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y } }
        },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: { grid: { color: '#F3F4F6' }, border: { display: false }, beginAtZero: true, ticks: { stepSize: 1, maxTicksLimit: 6 } }
        }
    }
});

new Chart(document.getElementById('projCompletionChart'), {
    type: 'line',
    data: {
        labels: @json($balanceLabels),
        datasets: [
            {
                label: 'Completed',
                data: @json($balanceDone),
                borderColor: 'rgba(16,185,129,1)',
                backgroundColor: 'rgba(16,185,129,0.08)',
                pointBackgroundColor: 'rgba(16,185,129,1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
            },
            {
                label: 'Created',
                data: @json($balanceCreated),
                borderColor: 'rgba(99,102,241,1)',
                backgroundColor: 'rgba(99,102,241,0.08)',
                pointBackgroundColor: 'rgba(99,102,241,1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1F2937',
                titleColor: '#F9FAFB',
                bodyColor: '#D1D5DB',
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y}`
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                border: { display: false },
                ticks: { font: { size: 11 } }
            },
            y: {
                grid: { color: '#F3F4F6' },
                border: { display: false },
                beginAtZero: true,
                ticks: { stepSize: 1, maxTicksLimit: 6 }
            }
        }
    }
});

/* ══════════════════════════════════════════════════════════
   Shared helpers: show/hide UI for export capture
══════════════════════════════════════════════════════════ */
let _hiddenPaginatedRows = [];

function prepareCapture() {
    document.getElementById('rpt-print-header').style.display = 'block';
    document.getElementById('rpt-filter-bar').style.display   = 'none';
    // Force grid columns regardless of viewport width (overrides responsive breakpoints)
    const s = document.createElement('style');
    s.id = '__rpt-grid-override';
    s.textContent = '.rpt-grid-4{grid-template-columns:repeat(4,1fr)!important;}' +
                    '.rpt-grid-5{grid-template-columns:repeat(5,1fr)!important;}' +
                    '.rpt-grid-2{grid-template-columns:1fr 1fr!important;}' +
                    '.rpt-grid-3{grid-template-columns:repeat(3,1fr)!important;}';
    document.head.appendChild(s);

    // Expand all paginated tables so every row prints
    _hiddenPaginatedRows = [];
    document.querySelectorAll('#rpt-capture-zone .rpt-table tbody tr').forEach(function(r) {
        if (r.style.display === 'none') {
            _hiddenPaginatedRows.push(r);
            r.style.display = '';
        }
    });
}
function restoreCapture() {
    document.getElementById('rpt-print-header').style.display = 'none';
    document.getElementById('rpt-filter-bar').style.display   = '';
    document.getElementById('__rpt-grid-override')?.remove();

    // Restore hidden rows to their paginated state
    _hiddenPaginatedRows.forEach(function(r) { r.style.display = 'none'; });
    _hiddenPaginatedRows = [];
}

/* Convert <canvas> elements to <img> so browser/html2canvas renders them */
let _savedCanvases = [];
function canvasToImages() {
    _savedCanvases = [];
    document.querySelectorAll('#rpt-capture-zone canvas').forEach(cv => {
        const img = document.createElement('img');
        img.src = cv.toDataURL('image/png');
        img.style.width  = cv.offsetWidth  + 'px';
        img.style.height = cv.offsetHeight + 'px';
        img.style.display = 'block';
        cv.parentNode.insertBefore(img, cv);
        cv.style.display = 'none';
        _savedCanvases.push({ cv, img });
    });
}
function restoreCanvases() {
    _savedCanvases.forEach(({ cv, img }) => { cv.style.display = ''; img.remove(); });
    _savedCanvases = [];
}

/* ══════════════════════════════════════════════════════════
   PRINT  — single page A4 portrait, fills the page
══════════════════════════════════════════════════════════ */
const TARGET_W = 794; // A4 portrait width at 96 dpi (210mm)

// Ctrl+P / browser print — minimal setup (no zoom, no width override)
let _printingViaBtn = false;
let _prevZoneStyle  = null;

window.addEventListener('beforeprint', () => {
    if (_printingViaBtn) return; // printReport() already handled this
    prepareCapture();
    canvasToImages();
});

window.addEventListener('afterprint', () => {
    restoreCapture();
    restoreCanvases();
    document.getElementById('__rpt-print-fit')?.remove();
    if (_prevZoneStyle !== null) {
        const z = document.getElementById('rpt-capture-zone');
        if (z) z.setAttribute('style', _prevZoneStyle);
        _prevZoneStyle = null;
    }
    _printingViaBtn = false;
});

async function printReport() {
    _printingViaBtn = true;

    const zone = document.getElementById('rpt-capture-zone');

    prepareCapture();
    canvasToImages();

    // TARGET_W = 794px matches A4 portrait width at 96dpi exactly — no zoom needed,
    // browser paginates naturally across as many pages as the content requires.
    _prevZoneStyle = zone.getAttribute('style') || '';
    zone.style.cssText = `width:${TARGET_W}px !important; min-width:${TARGET_W}px !important; max-width:${TARGET_W}px !important;`;

    await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
    await new Promise(r => setTimeout(r, 80));

    window.print();
    // afterprint listener restores everything
}

/* ══════════════════════════════════════════════════════════
   EXPORT PDF  — single page A4 landscape, fills the page
══════════════════════════════════════════════════════════ */
async function exportPDF() {
    const btn = document.getElementById('pdf-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;font-size:12px;"></i>Generating…';
    btn.disabled  = true;

    const zone = document.getElementById('rpt-capture-zone');

    prepareCapture();
    canvasToImages();

    // Force the zone to TARGET_W (970px) — maps to A4 landscape content area at 96 dpi
    const prevStyle = zone.getAttribute('style') || '';
    zone.style.width    = TARGET_W + 'px';
    zone.style.minWidth = TARGET_W + 'px';
    zone.style.maxWidth = TARGET_W + 'px';

    await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
    await new Promise(r => setTimeout(r, 200));

    let canvas;
    try {
        canvas = await html2canvas(zone, {
            scale:           2,
            useCORS:         true,
            allowTaint:      false,
            backgroundColor: '#ffffff',
            logging:         false,
            scrollX:         0,
            scrollY:         -window.scrollY,
            windowWidth:     TARGET_W,
            width:           TARGET_W,
            imageTimeout:    15000,
        });
    } catch (e) {
        alert('PDF generation failed: ' + e.message);
        zone.setAttribute('style', prevStyle);
        restoreCapture();
        restoreCanvases();
        btn.innerHTML = '<i class="fas fa-file-pdf" style="margin-right:6px;font-size:12px;"></i>Export PDF';
        btn.disabled  = false;
        return;
    }

    // Restore zone width
    zone.setAttribute('style', prevStyle);
    restoreCapture();
    restoreCanvases();

    const { jsPDF } = window.jspdf;
    const pdf    = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const pageW  = pdf.internal.pageSize.getWidth();   // 210 mm
    const pageH  = pdf.internal.pageSize.getHeight();  // 297 mm
    const footerH = 5;
    const contentH = pageH - footerH;  // 292 mm per page

    const imgW = canvas.width;
    const imgH = canvas.height;

    // Scale so content fills the FULL page width — no side gaps
    const scaleX    = pageW / imgW;          // mm per canvas-pixel
    const pxPerPage = Math.floor(contentH / scaleX); // canvas pixels that fit per page
    const numPages  = Math.ceil(imgH / pxPerPage);

    const companyName = '{{ addslashes($appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name')) }}';
    const dateStr     = '{{ now()->format(config('app.date_format', 'M d, Y')) }}';

    const addFooter = (pageNum) => {
        const footerY = pageH - footerH;
        pdf.setDrawColor(99, 102, 241);
        pdf.setLineWidth(0.3);
        pdf.line(0, footerY, pageW, footerY);
        pdf.setFontSize(6.5);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(79, 70, 229);
        pdf.text(companyName + '  —  Reports & Analytics', 2, footerY + 3.5);
        pdf.setFont('helvetica', 'normal');
        pdf.setTextColor(156, 163, 175);
        pdf.text('Generated ' + dateStr, pageW / 2, footerY + 3.5, { align: 'center' });
        const pageLabel = numPages > 1 ? '{{ auth()->user()->name }}  |  Page ' + pageNum + ' of ' + numPages : '{{ auth()->user()->name }}';
        pdf.text(pageLabel, pageW - 2, footerY + 3.5, { align: 'right' });
    };

    for (let p = 0; p < numPages; p++) {
        if (p > 0) pdf.addPage();

        // Slice the canvas vertically for this page
        const startPx  = p * pxPerPage;
        const sliceH   = Math.min(pxPerPage, imgH - startPx);
        const slice    = document.createElement('canvas');
        slice.width    = imgW;
        slice.height   = sliceH;
        slice.getContext('2d').drawImage(canvas, 0, startPx, imgW, sliceH, 0, 0, imgW, sliceH);

        const drawH = sliceH * scaleX;
        pdf.addImage(slice.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, pageW, drawH);
        addFooter(p + 1);
    }

    pdf.setProperties({
        title:   'Reports & Analytics — ' + companyName,
        subject: 'Analytics Report — ' + dateStr,
        author:  '{{ auth()->user()->name }}',
        creator: companyName,
    });

    pdf.save('report-{{ now()->format('Y-m-d') }}.pdf');

    btn.innerHTML = '<i class="fas fa-file-pdf" style="margin-right:6px;font-size:12px;"></i>Export PDF';
    btn.disabled  = false;
}

/* ══════════════════════════════════════════════════════════
   EXPORT EXCEL  — SheetJS, 4 sheets
══════════════════════════════════════════════════════════ */
function exportExcel() {
    const btn = document.getElementById('excel-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;font-size:12px;"></i>Exporting…';
    btn.disabled  = true;

    try {
        const wb = XLSX.utils.book_new();

        /* ── Sheet 1: Summary ── */
        const rows = [
            ['Reports & Analytics'],
            ['Generated', '{{ now()->format('F d, Y H:i') }}'],
            ['Period',    '{{ $from ? $from->format(config('app.date_format', 'M d, Y')).' – '.now()->format(config('app.date_format', 'M d, Y')) : 'All Time' }}'],
            [],
            ['KEY PERFORMANCE INDICATORS'],
            ['Metric', 'Value', 'Notes'],
            ['Total Tasks',     {{ $totalTasks }},            'In selected period'],
            ['Completed Tasks', {{ $completedTasks }},        'Approved + Delivered'],
            ['Completion Rate', '{{ $completionRate }}%',     ''],
            ['On-time Rate',    '{{ $onTimeRate }}%',         'Finished before deadline'],
            ['Overdue Tasks',   {{ $overdueTasks }},          'Need attention'],
            ['Active Projects', {{ $activeProjects }},        ''],
            ['Pending Review',  {{ $pendingReview }},         'Awaiting approval'],
            ['Team Members',    {{ $teamMemberCount }},        'Active contributors'],
            [],
            ['TASK STATUS BREAKDOWN'],
            ['Status', 'Count', 'Percentage'],
            @foreach($statusBreakdown as $s)
            ['{{ $s['label'] }}', {{ $s['count'] }}, '{{ $s['pct'] }}%'],
            @endforeach
            [],
            ['PRIORITY BREAKDOWN'],
            ['Priority', 'Count', 'Percentage'],
            @foreach($priorityBreakdown as $data)
            ['{{ $data['label'] }}', {{ $data['count'] }}, '{{ $data['pct'] }}%'],
            @endforeach
            [],
            ['MONTHLY TREND (last 6 months)'],
            ['Month', 'Created', 'Completed'],
            @foreach($monthLabels as $i => $label)
            ['{{ $label }}', {{ $monthlyCreated[$i] ?? 0 }}, {{ $monthlyCompleted[$i] ?? 0 }}],
            @endforeach
        ];
        const ws1 = XLSX.utils.aoa_to_sheet(rows);
        ws1['!cols'] = [{wch:32}, {wch:16}, {wch:28}];
        XLSX.utils.book_append_sheet(wb, ws1, 'Summary');

        /* ── Sheet 2: Project Performance ── */
        const projTbl = document.getElementById('proj-table');
        if (projTbl) {
            const ws2 = XLSX.utils.table_to_sheet(projTbl, { raw: false });
            XLSX.utils.book_append_sheet(wb, ws2, 'Projects');
        }

        /* ── Sheet 3: Team Productivity ── */
        const teamTbl = document.getElementById('team-table');
        if (teamTbl) {
            const ws3 = XLSX.utils.table_to_sheet(teamTbl, { raw: false });
            XLSX.utils.book_append_sheet(wb, ws3, 'Team Productivity');
        }

        /* ── Sheet 4: Overdue Tasks ── */
        const overdueTbl = document.getElementById('overdue-table');
        if (overdueTbl) {
            const ws4 = XLSX.utils.table_to_sheet(overdueTbl, { raw: false });
            XLSX.utils.book_append_sheet(wb, ws4, 'Overdue Tasks');
        }

        /* ── Sheet 5: Reassigned Tasks ── */
        const reassignedTbl = document.getElementById('reassigned-table');
        if (reassignedTbl) {
            const ws5 = XLSX.utils.table_to_sheet(reassignedTbl, { raw: false });
            XLSX.utils.book_append_sheet(wb, ws5, 'Reassigned Tasks');
        }

        /* ── Sheet 6: Reopened Tasks ── */
        const reopenedTbl = document.getElementById('reopened-table');
        if (reopenedTbl) {
            const ws6 = XLSX.utils.table_to_sheet(reopenedTbl, { raw: false });
            XLSX.utils.book_append_sheet(wb, ws6, 'Reopened Tasks');
        }

        XLSX.writeFile(wb, 'report-{{ now()->format('Y-m-d') }}.xlsx');
    } catch (e) {
        alert('Excel export failed: ' + e.message);
    }

    btn.innerHTML = '<i class="fas fa-file-excel" style="margin-right:6px;font-size:12px;"></i>Export Excel';
    btn.disabled  = false;
}

/* ══════════════════════════════════════════════════════════
   USER PERFORMANCE EXPORT MODAL
══════════════════════════════════════════════════════════ */
const teamMembersData = @json($teamMembers);

function openUserExport() {
    document.getElementById('user-export-modal').style.display = 'flex';
    updateExportCount();
}
function closeUserExport() {
    document.getElementById('user-export-modal').style.display = 'none';
}
function toggleAllUsers(checked) {
    document.querySelectorAll('.user-export-cb').forEach(cb => cb.checked = checked);
    updateExportCount();
}
function updateExportCount() {
    const total    = document.querySelectorAll('.user-export-cb').length;
    const selected = document.querySelectorAll('.user-export-cb:checked').length;
    document.getElementById('export-selected-count').textContent = selected + ' selected';
    document.getElementById('select-all-users').checked = selected === total && total > 0;
    document.getElementById('select-all-users').indeterminate = selected > 0 && selected < total;

    const csvBtn = document.getElementById('user-export-submit');
    csvBtn.querySelector('span').textContent = selected > 0 ? 'Download CSV (' + selected + ')' : 'Download CSV';
    csvBtn.disabled  = selected === 0;
    csvBtn.style.opacity = selected === 0 ? '0.5' : '1';

    const pdfBtn = document.getElementById('user-pdf-btn');
    pdfBtn.querySelector('span').textContent = selected > 0 ? 'Export PDF (' + selected + ')' : 'Export PDF';
    pdfBtn.disabled  = selected === 0;
    pdfBtn.style.opacity = selected === 0 ? '0.5' : '1';
}
document.getElementById('user-export-modal').addEventListener('click', function(e) {
    if (e.target === this) closeUserExport();
});
document.getElementById('user-export-form').addEventListener('submit', function(e) {
    const selected = document.querySelectorAll('.user-export-cb:checked').length;
    if (selected === 0) { e.preventDefault(); return; }
    const btn = document.getElementById('user-export-submit');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:11px;"></i><span> Downloading…</span>';
    btn.disabled = true;
    setTimeout(() => closeUserExport(), 1500);
});

/* ══════════════════════════════════════════════════════════
   USER PERFORMANCE — PDF EXPORT
══════════════════════════════════════════════════════════ */
async function exportUsersPDF() {
    const selectedIds = [...document.querySelectorAll('.user-export-cb:checked')].map(cb => parseInt(cb.value));
    if (selectedIds.length === 0) return;

    const pdfBtn  = document.getElementById('user-pdf-btn');
    pdfBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:11px;"></i><span> Fetching data…</span>';
    pdfBtn.disabled  = true;

    const period  = '{{ $from ? $from->format(config('app.date_format', 'M d, Y')).' – '.now()->format(config('app.date_format', 'M d, Y')) : 'All Time' }}';
    const company = '{{ addslashes($appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name')) }}';
    const dateStr = '{{ now()->format(config('app.date_format', 'M d, Y')) }}';
    const range   = '{{ $range }}';

    // Fetch rich per-user detail from backend
    let users;
    try {
        const qs = selectedIds.map(id => 'user_ids[]=' + id).join('&') + '&range=' + range;
        const r  = await fetch('{{ route('admin.reports.user-detail') }}?' + qs, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        users = await r.json();
    } catch (e) {
        alert('Could not fetch user data: ' + e.message);
        pdfBtn.innerHTML = '<i class="fas fa-file-pdf" style="font-size:11px;"></i><span>Export PDF (' + selectedIds.length + ')</span>';
        pdfBtn.disabled = false;
        return;
    }

    pdfBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:11px;"></i><span> Generating PDF…</span>';

    // ── helpers ─────────────────────────────────────────────────────────────
    function rateColor(r)    { return r >= 80 ? '#065F46' : r >= 40 ? '#92400E' : '#991B1B'; }
    function rateBg(r)       { return r >= 80 ? '#D1FAE5' : r >= 40 ? '#FEF3C7' : '#FEE2E2'; }
    function statusColor(s) {
        const map = @json(collect(\App\Support\TaskStatusColors::MAP)->map(fn($c) => $c['text']));
        return map[s] || '#6B7280';
    }
    function priorityColor(p) {
        return p === 'high' ? '#EF4444' : p === 'low' ? '#10B981' : '#F59E0B';
    }
    function barRow(label, color, count, total) {
        const pct = total > 0 ? Math.round(count / total * 100) : 0;
        return `<div style="margin-bottom:7px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:7px;height:7px;border-radius:50%;background:${color};display:inline-block;flex-shrink:0;"></span>
                    <span style="font-size:11px;font-weight:600;color:#374151;">${label}</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="font-size:11px;font-weight:700;color:#111827;">${count}</span>
                    <span style="font-size:9px;color:#9CA3AF;min-width:26px;text-align:right;">${pct}%</span>
                </div>
            </div>
            <div style="height:5px;background:#F3F4F6;border-radius:3px;overflow:hidden;">
                <div style="height:5px;width:${pct}%;background:${color};border-radius:3px;"></div>
            </div>
        </div>`;
    }

    // ── SVG mini-bar chart for 6-month trend ────────────────────────────────
    function svgTrend(labels, created, completed) {
        const tw = 260, th2 = 100, pad = 20, btmPad = 18;
        const maxVal = Math.max(...created, ...completed, 1);
        const n = labels.length;
        const slotW = (tw - pad * 2) / n;
        const bw = slotW * 0.32;
        let bars = '';
        labels.forEach((lbl, i) => {
            const x0 = pad + i * slotW + slotW * 0.1;
            const cH  = Math.round((created[i]   / maxVal) * (th2 - btmPad - 4));
            const dH  = Math.round((completed[i] / maxVal) * (th2 - btmPad - 4));
            const yBase = th2 - btmPad;
            bars += `<rect x="${x0}"           y="${yBase - cH}" width="${bw}" height="${Math.max(cH,1)}" fill="#6366F1" rx="2" opacity=".75"/>`;
            bars += `<rect x="${x0 + bw + 1}"  y="${yBase - dH}" width="${bw}" height="${Math.max(dH,1)}" fill="#10B981" rx="2" opacity=".75"/>`;
            bars += `<text x="${x0 + bw}"      y="${th2 - 3}" text-anchor="middle" font-size="6" fill="#9CA3AF" font-family="Inter,sans-serif">${lbl.slice(0,3)}</text>`;
        });
        // Legend
        const leg = `<rect x="${pad}" y="4" width="6" height="6" fill="#6366F1" rx="1" opacity=".75"/>
            <text x="${pad+8}" y="10" font-size="6.5" fill="#6B7280" font-family="Inter,sans-serif">Created</text>
            <rect x="${pad+44}" y="4" width="6" height="6" fill="#10B981" rx="1" opacity=".75"/>
            <text x="${pad+52}" y="10" font-size="6.5" fill="#6B7280" font-family="Inter,sans-serif">Completed</text>`;
        return `<svg xmlns="http://www.w3.org/2000/svg" width="${tw}" height="${th2}" viewBox="0 0 ${tw} ${th2}" style="width:100%;height:auto;">${leg}${bars}</svg>`;
    }

    // ── build one HTML block per user ────────────────────────────────────────
    function buildUserBlock(u, isFirst) {
        const t      = u.totals;
        const isPage = !isFirst;

        // helpers
        const th = (label, color='#6B7280') =>
            `<th style="padding:7px 8px;text-align:left;font-size:9px;font-weight:700;color:${color};text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #E5E7EB;background:#F9FAFB;">${label}</th>`;
        const thC = (label, color='#6B7280') =>
            `<th style="padding:7px 8px;text-align:center;font-size:9px;font-weight:700;color:${color};text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #E5E7EB;background:#F9FAFB;">${label}</th>`;

        const sectionTitle = (icon, label, iconColor='#4F46E5') =>
            `<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <div style="width:22px;height:22px;border-radius:6px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <span style="font-size:10px;font-weight:900;color:${iconColor};">${icon}</span>
                </div>
                <span style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em;">${label}</span>
            </div>`;

        // 4 main KPI cards (matching page 2 style)
        const kpiRows = [
            [
                { label:'ASSIGNED TASKS',  value: t.total,              sub:'Tasks assigned to '+u.name.split(' ')[0], color:'#4F46E5', iconBg:'#EEF2FF', icon:'≡' },
                { label:'COMPLETED',        value: t.done,               sub:'Approved + Delivered',                    color:'#059669', iconBg:'#D1FAE5', icon:'✓' },
                { label:'COMPLETION RATE',  value: u.rate+'%',           sub:'Of all tasks done',                       color: rateColor(u.rate), iconBg: rateBg(u.rate), icon:'%' },
                { label:'ON-TIME RATE',     value: u.on_time_rate+'%',   sub:'Before deadline',                         color:'#7C3AED', iconBg:'#F5F3FF', icon:'O' },
            ],
            [
                { label:'OVERDUE',          value: t.overdue,            sub:'Need attention',                          color: t.overdue>0?'#DC2626':'#9CA3AF', iconBg: t.overdue>0?'#FEF2F2':'#F8FAFC', icon:'!' },
                { label:'SUBMITTED TASKS',  value: t.in_review,          sub:'Awaiting admin approval',                 color:'#7C3AED', iconBg:'#F5F3FF', icon:'S' },
            ],
        ];

        const kpiRowHtml = kpiRows.map((row, ri) => {
            const cols = row.map(k => `
                <div style="flex:1;background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span style="font-size:9px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">${k.label}</span>
                        <div style="width:28px;height:28px;border-radius:8px;background:${k.iconBg};display:flex;align-items:center;justify-content:center;">
                            <span style="font-size:12px;font-weight:900;color:${k.color};">${k.icon}</span>
                        </div>
                    </div>
                    <div style="font-size:${ri===0?'28':'22'}px;font-weight:800;color:#111827;line-height:1;margin-bottom:4px;">${k.value}</div>
                    <div style="font-size:10px;color:#9CA3AF;">${k.sub}</div>
                </div>`).join('');
            return `<div style="display:flex;gap:12px;margin-bottom:12px;">${cols}</div>`;
        }).join('');

        // Status breakdown bars
        const bars = [
            barRow('Pending',     '#6B7280', t.pending,   t.total),
            barRow('In Progress', '#F59E0B', t.in_prog,   t.total),
            barRow('In Review',   '#8B5CF6', t.in_review, t.total),
            barRow('Completed',   '#10B981', t.approved,  t.total),
            barRow('Delivered',   '#047857', t.delivered, t.total),
            barRow('Overdue',     '#EF4444', t.overdue,   t.total),
        ].join('');

        // Priority bars + colored boxes
        const priorityBars = [
            barRow('Low',    '#10B981', t.p_low,    t.total),
            barRow('Medium', '#F59E0B', t.p_medium, t.total),
            barRow('High',   '#EF4444', t.p_high,   t.total),
        ].join('');
        const priorityBoxes = [
            { label:'Low',    color:'#059669', bg:'#D1FAE5', val: t.p_low    },
            { label:'Medium', color:'#D97706', bg:'#FEF3C7', val: t.p_medium },
            { label:'High',   color:'#DC2626', bg:'#FEE2E2', val: t.p_high   },
        ].map(b => `<div style="flex:1;background:${b.bg};border-radius:10px;padding:10px;text-align:center;">
            <div style="font-size:20px;font-weight:800;color:${b.color};line-height:1;">${b.val}</div>
            <div style="font-size:9px;font-weight:600;color:${b.color};margin-top:3px;opacity:.8;">${b.label}</div>
        </div>`).join('');

        // 6-month SVG trend
        const trendSvg = svgTrend(u.monthly_labels, u.monthly_created, u.monthly_completed);

        // Monthly balance boxes
        const totalCreated   = u.monthly_created.reduce((a,b)=>a+b,0);
        const totalCompleted = u.monthly_completed.reduce((a,b)=>a+b,0);
        const netBalance     = totalCompleted - totalCreated;
        const balanceBoxes = [
            { label:'COMPLETED', sub:'last 12 months', val: totalCompleted, color:'#059669', bg:'#D1FAE5' },
            { label:'CREATED',   sub:'last 12 months', val: totalCreated,   color:'#4F46E5', bg:'#EEF2FF' },
            { label:'NET BALANCE', sub: netBalance >= 0 ? 'ahead of backlog' : 'behind backlog', val: (netBalance >= 0 ? '+' : '') + netBalance, color: netBalance >= 0 ? '#059669' : '#DC2626', bg: netBalance >= 0 ? '#F0FDF4' : '#FEF2F2' },
            { label:'BEST MONTH', sub: u.best_month_count + ' tasks done', val: u.best_month, color:'#D97706', bg:'#FEF3C7' },
        ].map(b => `<div style="flex:1;background:${b.bg};border-radius:12px;padding:14px;text-align:center;">
            <div style="font-size:10px;font-weight:700;color:${b.color};text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">${b.label}</div>
            <div style="font-size:22px;font-weight:800;color:#111827;line-height:1;margin-bottom:3px;">${b.val}</div>
            <div style="font-size:10px;color:#6B7280;">${b.sub}</div>
        </div>`).join('');

        // Projects table rows
        const projRows = u.projects.length === 0
            ? `<tr><td colspan="8" style="padding:14px;text-align:center;color:#9CA3AF;font-size:11px;">No projects in this period</td></tr>`
            : u.projects.map(p => {
                const activeTag = p.is_active
                    ? `<span style="background:#D1FAE5;color:#065F46;font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;margin-left:4px;">ACTIVE</span>`
                    : `<span style="background:#F3F4F6;color:#9CA3AF;font-size:9px;padding:1px 5px;border-radius:4px;margin-left:4px;">${p.proj_status}</span>`;
                return `<tr style="background:${p.is_active ? '#FAFFFE' : '#fff'}">
                    <td style="padding:7px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;font-weight:600;color:#111827;">${p.name}${activeTag}</td>
                    <td style="padding:7px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;color:#6B7280;">${p.customer}</td>
                    <td style="padding:7px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;color:#374151;white-space:nowrap;">${p.first_date}</td>
                    <td style="padding:7px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;color:#4F46E5;font-weight:600;text-align:center;">${p.days_active}d</td>
                    <td style="padding:7px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;text-align:center;color:#374151;">${p.total}</td>
                    <td style="padding:7px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;text-align:center;color:#10B981;font-weight:700;">${p.done}</td>
                    <td style="padding:7px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;text-align:center;">
                        <span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:10px;font-weight:700;background:${rateBg(p.rate)};color:${rateColor(p.rate)};">${p.rate}%</span>
                    </td>
                    <td style="padding:7px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;color:#8B5CF6;text-align:center;">${p.time_spent}</td>
                </tr>`;
            }).join('');

        // Active tasks rows
        const taskRows = u.active_tasks.length === 0
            ? `<tr><td colspan="6" style="padding:14px;text-align:center;color:#9CA3AF;font-size:11px;">No active tasks in this period</td></tr>`
            : u.active_tasks.map(t2 => {
                const dl      = t2.days_left;
                const dlColor = dl === null ? '#9CA3AF' : dl < 0 ? '#EF4444' : dl <= 3 ? '#F59E0B' : '#10B981';
                const dlText  = dl === null ? '—' : dl < 0 ? Math.abs(dl)+'d overdue' : dl+'d left';
                return `<tr>
                    <td style="padding:6px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;font-weight:600;color:#111827;">${t2.title}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;color:#6B7280;">${t2.project}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;color:#6B7280;">${t2.customer}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;text-align:center;">
                        <span style="background:${statusColor(t2.status)}22;color:${statusColor(t2.status)};font-size:9px;font-weight:700;padding:2px 6px;border-radius:4px;">${t2.status.replace(/_/g,' ')}</span>
                    </td>
                    <td style="padding:6px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;text-align:center;">
                        <span style="color:${priorityColor(t2.priority)};font-weight:700;font-size:10px;">${t2.priority||'—'}</span>
                    </td>
                    <td style="padding:6px 8px;border-bottom:1px solid #F3F4F6;font-size:11px;color:${dlColor};font-weight:700;text-align:center;">${dlText}</td>
                </tr>`;
            }).join('');

        return `
        <div style="font-family:Inter,system-ui,sans-serif;${isPage ? 'margin-top:48px;padding-top:32px;border-top:2px solid #E5E7EB;' : ''}">

        ${!isPage ? `
        ${/* ── accent bar */ ''}
        <div style="height:5px;background:linear-gradient(90deg,#4F46E5,#6366F1,#818CF8);border-radius:3px;margin-bottom:20px;"></div>

        ${/* ── logo + title header */ ''}
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div>
                <div style="font-size:20px;font-weight:800;color:#111827;line-height:1.2;">${company}</div>
                <div style="font-size:11px;color:#9CA3AF;margin-top:2px;">Task Management System</div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:18px;font-weight:800;color:#4F46E5;line-height:1.2;">Reports &amp; Analytics</div>
                <div style="font-size:10px;color:#9CA3AF;margin-top:3px;">Confidential &mdash; Internal Use Only</div>
            </div>
        </div>

        ${/* ── meta row */ ''}
        <div style="border-top:1.5px solid #E5E7EB;border-bottom:1.5px solid #E5E7EB;padding:10px 0;display:flex;gap:32px;margin-bottom:20px;">
            <div>
                <div style="font-size:9px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Generated</div>
                <div style="font-size:11px;font-weight:600;color:#374151;margin-top:2px;">${dateStr} at {{ now()->format('H:i') }}</div>
            </div>
            <div>
                <div style="font-size:9px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Period</div>
                <div style="font-size:11px;font-weight:600;color:#374151;margin-top:2px;">${period}</div>
            </div>
            <div>
                <div style="font-size:9px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Prepared By</div>
                <div style="font-size:11px;font-weight:600;color:#374151;margin-top:2px;">{{ auth()->user()->name }}</div>
            </div>
            <div>
                <div style="font-size:9px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Department</div>
                <div style="font-size:11px;font-weight:600;color:#374151;margin-top:2px;">{{ $appSettings['company_name'] ?? config('app.name') }}</div>
            </div>
        </div>` : ''}

        ${/* ── employee name card */ ''}
        <div style="background:linear-gradient(135deg,#EEF2FF,#F5F3FF);border:1.5px solid #E0E7FF;border-radius:14px;padding:18px 22px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#4F46E5,#7C3AED);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;flex-shrink:0;">
                    ${u.name.charAt(0).toUpperCase()}
                </div>
                <div>
                    <div style="font-size:17px;font-weight:800;color:#111827;">${u.name}</div>
                    <div style="font-size:12px;color:#6B7280;margin-top:2px;">${u.role}${u.job_title?' &middot; '+u.job_title:''}</div>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:1px;">${u.email}</div>
                </div>
            </div>
            <div style="display:flex;gap:24px;text-align:center;">
                <div>
                    <div style="font-size:10px;color:#9CA3AF;margin-bottom:3px;">Member Since</div>
                    <div style="font-size:13px;font-weight:700;color:#374151;">${u.member_since}</div>
                </div>
                <div>
                    <div style="font-size:10px;color:#9CA3AF;margin-bottom:3px;">Active Time Logged</div>
                    <div style="font-size:13px;font-weight:700;color:#4F46E5;">${u.time_spent}</div>
                </div>
            </div>
        </div>

        ${/* ── KPI rows */ ''}
        ${kpiRowHtml}

        ${/* ── 3-col: status | priority | trend */ ''}
        <div style="display:flex;gap:12px;margin-bottom:14px;">

            ${/* status breakdown */ ''}
            <div style="flex:1;background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:14px 16px;">
                ${sectionTitle('=', u.name.split(' ')[0].toUpperCase()+"'S TASK STATUS",'#6366F1')}
                ${bars}
            </div>

            ${/* priority breakdown */ ''}
            <div style="flex:1;background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:14px 16px;">
                ${sectionTitle('P', 'PRIORITY — '+u.name.split(' ')[0].toUpperCase()+"'S TASKS",'#F59E0B')}
                ${priorityBars}
                <div style="display:flex;gap:8px;margin-top:10px;">${priorityBoxes}</div>
            </div>

            ${/* 6-month trend */ ''}
            <div style="flex:1;background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:14px 16px;">
                ${sectionTitle('~', '6-MONTH TREND','#4F46E5')}
                ${trendSvg}
            </div>
        </div>

        ${/* ── projects table */ ''}
        <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:14px 16px;margin-bottom:14px;">
            ${sectionTitle('>', 'PROJECTS INVOLVING '+u.name.split(' ')[0].toUpperCase(),'#8B5CF6')}
            <div style="overflow:hidden;border-radius:8px;border:1px solid #F3F4F6;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr>${th('Project')}${th('Customer')}${th('Started')}${thC('Days','#4F46E5')}${thC('Tasks')}${thC('Done','#10B981')}${thC('Rate')}${thC('Time','#8B5CF6')}</tr></thead>
                    <tbody>${projRows}</tbody>
                </table>
            </div>
        </div>

        ${/* ── monthly balance */ ''}
        <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:14px 16px;margin-bottom:14px;">
            ${sectionTitle('~', u.name.split(' ')[0].toUpperCase()+"'S MONTHLY BALANCE",'#10B981')}
            <div style="display:flex;gap:10px;margin-bottom:14px;">${balanceBoxes}</div>
        </div>

        ${/* ── active workload */ ''}
        <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:14px 16px;">
            ${sectionTitle('!', 'CURRENT ACTIVE WORKLOAD ('+u.active_tasks.length+' tasks)','#EF4444')}
            <div style="overflow:hidden;border-radius:8px;border:1px solid #F3F4F6;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr style="background:#F9FAFB;">${th('Task')}${th('Project')}${th('Customer')}${thC('Status')}${thC('Priority')}${thC('Deadline','#EF4444')}</tr></thead>
                    <tbody>${taskRows}</tbody>
                </table>
            </div>
        </div>

        </div>`;
    }

    const area = document.getElementById('user-perf-pdf-area');
    area.innerHTML = users.map((u, i) => buildUserBlock(u, i === 0)).join('');

    await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
    await new Promise(r => setTimeout(r, 200));

    let canvas;
    try {
        canvas = await html2canvas(area, {
            scale: 2, useCORS: true, allowTaint: false,
            backgroundColor: '#ffffff', logging: false,
            scrollX: 0, scrollY: 0,
            windowWidth: 900, width: 900,
        });
    } catch (e) {
        alert('PDF generation failed: ' + e.message);
        pdfBtn.innerHTML = '<i class="fas fa-file-pdf" style="font-size:11px;"></i><span>Export PDF (' + selectedIds.length + ')</span>';
        pdfBtn.disabled = false;
        return;
    }

    area.innerHTML = '';

    const { jsPDF } = window.jspdf;
    const pdf   = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const pageW = pdf.internal.pageSize.getWidth();
    const pageH = pdf.internal.pageSize.getHeight();
    const footH = 7;
    const contentH = pageH - footH;

    const imgW = canvas.width, imgH = canvas.height;
    const scaleX    = pageW / imgW;
    const pxPerPage = Math.floor(contentH / scaleX);
    const numPages  = Math.ceil(imgH / pxPerPage);

    for (let p = 0; p < numPages; p++) {
        if (p > 0) pdf.addPage();
        const startPx = p * pxPerPage;
        const sliceH  = Math.min(pxPerPage, imgH - startPx);
        const slice   = document.createElement('canvas');
        slice.width   = imgW; slice.height = sliceH;
        slice.getContext('2d').drawImage(canvas, 0, startPx, imgW, sliceH, 0, 0, imgW, sliceH);
        pdf.addImage(slice.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, pageW, sliceH * scaleX);

        const footerY = pageH - footH;
        pdf.setDrawColor(99, 102, 241); pdf.setLineWidth(0.3);
        pdf.line(0, footerY, pageW, footerY);
        pdf.setFontSize(6.5); pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(79, 70, 229);
        pdf.text(company + '  —  Employee Performance Report', 2, footerY + 4);
        pdf.setFont('helvetica', 'normal'); pdf.setTextColor(156, 163, 175);
        pdf.text('Generated ' + dateStr + '  ·  Period: ' + period, pageW / 2, footerY + 4, { align: 'center' });
        pdf.text('Page ' + (p + 1) + ' of ' + numPages, pageW - 2, footerY + 4, { align: 'right' });
    }

    pdf.save('employee-performance-{{ now()->format('Y-m-d') }}.pdf');
    pdfBtn.innerHTML = '<i class="fas fa-file-pdf" style="font-size:11px;"></i><span>Export PDF (' + selectedIds.length + ')</span>';
    pdfBtn.disabled = false;
}

/* ── Client-side table paginator ── */
function rptPaginate(tableId, perPage) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var tbody = table.querySelector('tbody');
    if (!tbody) return;
    var rows = Array.from(tbody.querySelectorAll('tr'));
    if (rows.length <= perPage) return; // no pagination needed

    var totalPages = Math.ceil(rows.length / perPage);
    var current = 1;

    // Create pagination container
    var wrap = document.createElement('div');
    wrap.id = tableId + '-pg';
    wrap.style.cssText = 'margin-top:12px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;';
    table.closest('.rpt-scroll-wrap, div') && table.parentNode.insertBefore && table.parentNode.parentNode
        ? table.parentNode.parentNode.insertBefore(wrap, table.parentNode.nextSibling)
        : table.parentNode.insertBefore(wrap, table.nextSibling);

    function render(page) {
        current = page;
        var start = (page - 1) * perPage;
        rows.forEach(function(r, i) {
            r.style.display = (i >= start && i < start + perPage) ? '' : 'none';
        });
        var from = start + 1, to = Math.min(start + perPage, rows.length);
        wrap.innerHTML = '';

        // Count label
        var lbl = document.createElement('span');
        lbl.style.cssText = 'font-size:12px;color:#6B7280;';
        lbl.textContent = 'Showing ' + from + '–' + to + ' of ' + rows.length + ' results';
        wrap.appendChild(lbl);

        // Buttons
        var btns = document.createElement('div');
        btns.style.cssText = 'display:flex;gap:4px;align-items:center;flex-wrap:wrap;';

        function btn(label, page, active, disabled) {
            var el = document.createElement(disabled ? 'span' : 'button');
            el.textContent = label;
            el.className = 'rpt-pg-btn';
            el.style.cssText = 'padding:5px 11px;border-radius:8px;font-size:12px;font-weight:' + (active ? '700' : '600') + ';border:none;cursor:' + (disabled ? 'default' : 'pointer') + ';min-width:32px;text-align:center;background:' + (active ? '#4F46E5' : '#F3F4F6') + ';color:' + (active ? '#fff' : (disabled ? '#D1D5DB' : '#374151')) + ';';
            if (!disabled && !active) el.addEventListener('click', function() { render(page); });
            return el;
        }

        btns.appendChild(btn('‹ Prev', current - 1, false, current === 1));
        for (var p = 1; p <= totalPages; p++) btns.appendChild(btn(p, p, p === current, false));
        btns.appendChild(btn('Next ›', current + 1, false, current === totalPages));
        wrap.appendChild(btns);
    }
    render(1);
}

// Init all report tables
document.addEventListener('DOMContentLoaded', function() {
    ['proj-table','team-table','customer-table','approval-speed-table',
     'decide-later-table',
     'overdue-table','reopened-table','reassigned-bottom-table',
     'billing-user-table','billing-customer-table','ad-budget-table'
    ].forEach(function(id) { rptPaginate(id, 7); });
    initSocialPostsModal();
    rptPaginate('total-tasks-modal-table', 10);
    rptPaginate('completed-tasks-modal-table', 10);
    rptPaginate('ontime-tasks-modal-table', 10);
});

// ── Social Posts Modal: search + paginate + PDF export ──────────────────────
var _spAllRows = [], _spFiltered = [], _spPage = 1, _spPer = 10;

function initSocialPostsModal() {
    var tbody = document.querySelector('#social-posts-modal-table tbody');
    if (!tbody) return;
    _spAllRows = Array.from(tbody.querySelectorAll('tr'));
    _spFiltered = _spAllRows.slice();
    _spRender();
}

function spClearSearch() {
    var inp = document.getElementById('social-posts-search');
    if (inp) { inp.value = ''; }
    _spFiltered = _spAllRows.slice();
    _spPage = 1;
    _spRender();
}

function spSearch() {
    var term = (document.getElementById('social-posts-search').value || '').toLowerCase().trim();
    _spFiltered = term
        ? _spAllRows.filter(function(r) { return r.textContent.toLowerCase().indexOf(term) !== -1; })
        : _spAllRows.slice();
    _spPage = 1;
    _spRender();
}

function _spRender() {
    var total = _spFiltered.length;
    var totalPages = Math.ceil(total / _spPer) || 1;
    _spAllRows.forEach(function(r) { r.style.display = 'none'; });
    var start = (_spPage - 1) * _spPer;
    _spFiltered.slice(start, start + _spPer).forEach(function(r) { r.style.display = ''; });

    var wrap = document.getElementById('social-posts-modal-table-pg');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'social-posts-modal-table-pg';
        wrap.style.cssText = 'margin-top:12px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;';
        var tbl = document.getElementById('social-posts-modal-table');
        tbl.parentNode.insertBefore(wrap, tbl.nextSibling);
    }
    var from = total ? start + 1 : 0, to = Math.min(start + _spPer, total);
    wrap.innerHTML = '';

    // "no results" message
    var noRes = document.getElementById('sp-no-results');
    if (!noRes) {
        noRes = document.createElement('p');
        noRes.id = 'sp-no-results';
        noRes.style.cssText = 'text-align:center;color:#9CA3AF;font-size:13px;padding:24px 0;margin:0;display:none;';
        noRes.textContent = 'No posts match your search.';
        var tbl2 = document.getElementById('social-posts-modal-table');
        tbl2.parentNode.insertBefore(noRes, tbl2.nextSibling);
    }
    noRes.style.display = total === 0 ? '' : 'none';
    document.getElementById('social-posts-modal-table').style.display = total === 0 ? 'none' : '';

    if (total === 0) return;

    var lbl = document.createElement('span');
    lbl.style.cssText = 'font-size:12px;color:#6B7280;';
    lbl.textContent = 'Showing ' + from + '–' + to + ' of ' + total + ' results';
    wrap.appendChild(lbl);

    var btns = document.createElement('div');
    btns.style.cssText = 'display:flex;gap:4px;align-items:center;flex-wrap:wrap;';

    function mkBtn(label, pg, active, disabled) {
        var el = document.createElement(disabled ? 'span' : 'button');
        el.textContent = label;
        el.style.cssText = 'padding:5px 11px;border-radius:8px;font-size:12px;font-weight:' + (active ? '700' : '600') + ';border:none;cursor:' + (disabled ? 'default' : 'pointer') + ';min-width:32px;text-align:center;background:' + (active ? '#4F46E5' : '#F3F4F6') + ';color:' + (active ? '#fff' : (disabled ? '#D1D5DB' : '#374151')) + ';';
        if (!disabled && !active) el.addEventListener('click', function() { _spPage = pg; _spRender(); });
        return el;
    }

    btns.appendChild(mkBtn('‹ Prev', _spPage - 1, false, _spPage === 1));
    if (totalPages <= 8) {
        for (var p = 1; p <= totalPages; p++) btns.appendChild(mkBtn(p, p, p === _spPage, false));
    } else {
        btns.appendChild(mkBtn(1, 1, _spPage === 1, false));
        if (_spPage > 3) { var d1 = document.createElement('span'); d1.textContent = '…'; d1.style.cssText = 'padding:5px 4px;font-size:12px;color:#9CA3AF;'; btns.appendChild(d1); }
        for (var p2 = Math.max(2, _spPage - 1); p2 <= Math.min(totalPages - 1, _spPage + 1); p2++) btns.appendChild(mkBtn(p2, p2, p2 === _spPage, false));
        if (_spPage < totalPages - 2) { var d2 = document.createElement('span'); d2.textContent = '…'; d2.style.cssText = 'padding:5px 4px;font-size:12px;color:#9CA3AF;'; btns.appendChild(d2); }
        btns.appendChild(mkBtn(totalPages, totalPages, _spPage === totalPages, false));
    }
    btns.appendChild(mkBtn('Next ›', _spPage + 1, false, _spPage >= totalPages));
    wrap.appendChild(btns);
}

/* ══════════════════════════════════════════════════════════
   SUMMARIZE MODAL — Print & Export PDF  (branded layout)
══════════════════════════════════════════════════════════ */
function buildSummarizeHTML(immediate) {
    var d        = (window._rptSummState && window._rptSummState.data) ? window._rptSummState.data : null;
    var company  = '{{ addslashes($appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name')) }}';
    var dept     = '{{ addslashes($appSettings['department_name'] ?? 'Operations') }}';
    var period   = d ? d.periodLabel : '{{ $from ? $from->format(config('app.date_format','M d, Y')).' – '.now()->format(config('app.date_format','M d, Y')) : 'All Time' }}';
    var dateStr  = '{{ now()->format(config('app.date_format','M d, Y')) }}';
    var timeStr  = '{{ now()->format('H:i') }}';
    var user     = '{{ auth()->user()->name }}';
    var logoSrc  = '{{ $summarizeLogo ?? '' }}';
    var mono     = company.charAt(0).toUpperCase();

    @if($selectedUser)
    var filteredBy = '{{ addslashes($selectedUser->name) }}';
    @else
    var filteredBy = '';
    @endif

    // KPI data — use dynamic data if available, else fall back to PHP-rendered values
    var kpis = d ? [
        { label:'Total Tasks',     value: d.totalTasks,          color:'#4F46E5', bg:'#EEF2FF', sub:'In selected period' },
        { label:'Completed',       value: d.completedTasks,      color:'#059669', bg:'#D1FAE5', sub:'Approved + Delivered' },
        { label:'Completion Rate', value: d.completionRate + '%',color:'#EA580C', bg:'#FFF7ED', sub:'Of all tasks' },
        { label:'On-Time Rate',    value: d.onTimeRate + '%',    color:'#10B981', bg:'#F0FDF4', sub:'Before deadline' },
        { label:'Overdue',         value: d.overdueTasks,        color:'#DC2626', bg:'#FEF2F2', sub:'Need attention' },
        { label:'Active Projects', value: d.activeProjects,      color:'#2563EB', bg:'#EFF6FF', sub:'Currently running' },
    ] : [
        { label:'Total Tasks',     value:'{{ $totalTasks }}',        color:'#4F46E5', bg:'#EEF2FF', sub:'In selected period' },
        { label:'Completed',       value:'{{ $completedTasks }}',    color:'#059669', bg:'#D1FAE5', sub:'Approved + Delivered' },
        { label:'Completion Rate', value:'{{ $completionRate }}%',   color:'#EA580C', bg:'#FFF7ED', sub:'Of all tasks' },
        { label:'On-Time Rate',    value:'{{ $onTimeRate }}%',       color:'#10B981', bg:'#F0FDF4', sub:'Before deadline' },
        { label:'Overdue',         value:'{{ $overdueTasks }}',      color:'#DC2626', bg:'#FEF2F2', sub:'Need attention' },
        { label:'Active Projects', value:'{{ $activeProjects }}',    color:'#2563EB', bg:'#EFF6FF', sub:'Currently running' },
    ];

    // Team rows
    var teamRows = d ? d.teamMembers.map(function(m){ return { name:m.name, role:'User', total:m.total, done:Math.round(m.total * m.rate / 100), rate:m.rate }; }) : [
        @foreach($teamMembers->sortByDesc('rate')->take(8) as $m)
        { name:'{{ addslashes($m['name']) }}', role:'{{ addslashes($m['role']) }}', total:{{ $m['total'] }}, done:{{ $m['completed'] }}, rate:{{ $m['rate'] }} },
        @endforeach
    ];

    // Customer rows
    var custRows = d ? (d.hasUser ? [] : d.customerStats.map(function(c){ return { name:c.name, total:c.total, done:Math.round(c.total * c.rate / 100), rate:c.rate }; })) : (
        @if($customerStats->isNotEmpty() && !$selectedUser)
        [
            @foreach($customerStats->sortByDesc('total')->take(8) as $c)
            { name:'{{ addslashes($c['name']) }}', total:{{ $c['total'] }}, done:{{ $c['completed'] }}, rate:{{ $c['rate'] }} },
            @endforeach
        ]
        @else
        []
        @endif
    );

    // ── CSS ───────────────────────────────────────────────
    var css =
      '* { box-sizing:border-box; margin:0; padding:0; }'
    + 'body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif; font-size:12px; color:#111827; background:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'

    /* Preview bar */
    + '.pbar { position:sticky; top:0; z-index:100; background:linear-gradient(135deg,#4338CA,#7C3AED); padding:11px 28px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 3px 14px rgba(79,70,229,.35); }'
    + '.pbar-l h2 { font-size:14px; font-weight:700; color:#fff; margin:0; }'
    + '.pbar-l p  { font-size:10.5px; color:rgba(255,255,255,.72); margin:3px 0 0; }'
    + '.pbar-btn  { display:flex; align-items:center; gap:7px; padding:9px 22px; background:#fff; color:#4F46E5; border:none; border-radius:9px; font-size:12px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,.14); letter-spacing:.01em; }'
    + '.pbar-btn:hover { background:#EEF2FF; }'
    + '.pbar-btn svg { flex-shrink:0; }'

    /* Document shell */
    + '.doc { max-width:860px; margin:0 auto; background:#fff; }'

    /* Gradient top bar */
    + '.accent { height:6px; background:linear-gradient(90deg,#4F46E5 0%,#7C3AED 45%,#06B6D4 100%); -webkit-print-color-adjust:exact; print-color-adjust:exact; }'

    /* Document header */
    + '.dh { padding:20px 32px 16px; display:flex; justify-content:space-between; align-items:center; gap:16px; }'
    + '.logo-area { display:flex; align-items:center; gap:14px; }'
    + '.logo-img  { height:48px; width:auto; max-width:160px; object-fit:contain; }'
    + '.logo-mono { width:48px; height:48px; background:linear-gradient(135deg,#4F46E5,#7C3AED); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:25px; font-weight:900; flex-shrink:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.co-name { font-size:17px; font-weight:800; color:#111827; line-height:1.2; }'
    + '.co-sub  { font-size:10px; color:#9CA3AF; margin-top:3px; }'
    + '.rt { text-align:right; }'
    + '.rt-name { font-size:20px; font-weight:900; color:#4F46E5; letter-spacing:-.4px; line-height:1.15; }'
    + '.rt-badge { display:inline-flex; align-items:center; gap:5px; margin-top:6px; padding:3px 12px; background:#EEF2FF; border-radius:20px; font-size:10px; font-weight:600; color:#6366F1; border:1px solid #C7D2FE; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'

    /* Divider */
    + '.hr { height:1px; background:#E5E7EB; }'

    /* Meta strip */
    + '.meta { display:flex; background:#F8FAFC; padding:12px 32px; border-bottom:1px solid #E5E7EB; gap:0; }'
    + '.mi { flex:1; min-width:0; padding-right:20px; border-right:1px solid #E5E7EB; margin-right:20px; }'
    + '.mi:last-child { border-right:none; padding-right:0; margin-right:0; }'
    + '.mi-lbl { font-size:8px; font-weight:700; color:#9CA3AF; text-transform:uppercase; letter-spacing:.09em; }'
    + '.mi-val { font-size:11.5px; font-weight:600; color:#374151; margin-top:3px; }'

    /* Content */
    + '.content { padding:22px 32px 26px; }'

    /* Section heading */
    + '.sh { display:flex; align-items:center; gap:10px; margin:22px 0 13px; padding-bottom:9px; border-bottom:1.5px solid #F3F4F6; }'
    + '.sh:first-child { margin-top:0; }'
    + '.sh-bar  { width:4px; height:18px; border-radius:4px; flex-shrink:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.sh-text { font-size:10px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.08em; }'
    + '.sh-pill { margin-left:auto; font-size:9.5px; font-weight:600; color:#6B7280; background:#F3F4F6; padding:2px 9px; border-radius:20px; }'

    /* KPI grid */
    + '.kgrid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:4px; }'
    + '.kcard  { border-radius:10px; padding:14px 14px 14px 18px; border:1px solid rgba(0,0,0,.06); position:relative; overflow:hidden; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.kacc   { position:absolute; top:0; left:0; width:4px; height:100%; border-radius:10px 0 0 10px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.k-lbl  { font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; margin-bottom:7px; }'
    + '.k-val  { font-size:30px; font-weight:900; color:#111827; line-height:1; letter-spacing:-1px; }'
    + '.k-sub  { font-size:9.5px; color:#9CA3AF; margin-top:5px; }'

    /* Tables */
    + '.twrap { border-radius:10px; border:1px solid #E5E7EB; overflow:hidden; margin-bottom:4px; }'
    + 'table   { width:100%; border-collapse:collapse; }'
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

    /* Footer */
    + '.foot { margin:4px 32px 0; padding:13px 0; border-top:2px solid #4F46E5; display:flex; justify-content:space-between; align-items:center; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.foot-brand { font-size:10px; font-weight:700; color:#4F46E5; }'
    + '.foot-mid   { font-size:10px; color:#9CA3AF; }'
    + '.foot-conf  { font-size:10px; color:#9CA3AF; font-style:italic; }'
    + 'tr { page-break-inside: avoid; -webkit-column-break-inside: avoid; }'

    /* Print */
    + '@@media print { .pbar{display:none !important;} body{background:#fff !important;} .doc{max-width:none;} *{-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important;} @@page{size:A4 portrait;margin:0;} }'
    ;

    // ── Logo element ─────────────────────────────────────
    var logoEl = logoSrc
        ? '<img class="logo-img" src="' + logoSrc + '" alt="' + company + '">'
        : '<div class="logo-mono">' + mono + '</div>';

    // ── Print SVG icon ────────────────────────────────────
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

    // ── Team table ───────────────────────────────────────
    var tHtml = '';
    if (teamRows.length) {
        tHtml += '<div class="sh"><div class="sh-bar" style="background:#7C3AED;"></div><div class="sh-text">Team Productivity</div><span class="sh-pill">' + teamRows.length + ' members</span></div>';
        tHtml += '<div class="twrap"><table><thead><tr>'
              + '<th>Member</th><th>Role</th>'
              + '<th style="text-align:center;">Total</th>'
              + '<th style="text-align:center;">Done</th>'
              + '<th style="min-width:110px;">Progress</th>'
              + '<th style="text-align:right;">Rate</th>'
              + '</tr></thead><tbody>';
        teamRows.forEach(function(m) {
            var c   = m.rate >= 80 ? '#10B981' : (m.rate >= 40 ? '#F59E0B' : '#EF4444');
            var rbg = m.rate >= 80 ? '#D1FAE5' : (m.rate >= 40 ? '#FEF3C7' : '#FEE2E2');
            tHtml += '<tr>'
                  + '<td class="td-n">' + m.name + '</td>'
                  + '<td style="font-size:10px;color:#9CA3AF;">' + m.role + '</td>'
                  + '<td class="td-c">' + m.total + '</td>'
                  + '<td class="td-c" style="color:#10B981;">' + m.done + '</td>'
                  + '<td><div class="bt"><div class="bf" style="width:' + m.rate + '%;background:' + c + ';"></div></div></td>'
                  + '<td style="text-align:right;"><span class="rb" style="background:' + rbg + ';color:' + c + ';">' + m.rate + '%</span></td>'
                  + '</tr>';
        });
        tHtml += '</tbody></table></div>';
    }

    // ── Customer table ───────────────────────────────────
    var cHtml = '';
    if (custRows.length) {
        cHtml += '<div class="sh"><div class="sh-bar" style="background:#2563EB;"></div><div class="sh-text">Customer Performance</div><span class="sh-pill">' + custRows.length + ' customers</span></div>';
        cHtml += '<div class="twrap"><table><thead><tr>'
              + '<th>Customer</th>'
              + '<th style="text-align:center;">Total Tasks</th>'
              + '<th style="text-align:center;">Completed</th>'
              + '<th style="min-width:110px;">Progress</th>'
              + '<th style="text-align:right;">Delivery Rate</th>'
              + '</tr></thead><tbody>';
        custRows.forEach(function(c) {
            var col = c.rate >= 80 ? '#10B981' : (c.rate >= 40 ? '#F59E0B' : '#EF4444');
            var rbg = c.rate >= 80 ? '#D1FAE5' : (c.rate >= 40 ? '#FEF3C7' : '#FEE2E2');
            cHtml += '<tr>'
                  + '<td class="td-n">' + c.name + '</td>'
                  + '<td class="td-c">' + c.total + '</td>'
                  + '<td class="td-c" style="color:#10B981;">' + c.done + '</td>'
                  + '<td><div class="bt"><div class="bf" style="width:' + c.rate + '%;background:' + col + ';"></div></div></td>'
                  + '<td style="text-align:right;"><span class="rb" style="background:' + rbg + ';color:' + col + ';">' + c.rate + '%</span></td>'
                  + '</tr>';
        });
        cHtml += '</tbody></table></div>';
    }

    // ── Preview toolbar ──────────────────────────────────
    var pbar = immediate ? '' :
        '<div class="pbar">'
      + '<div class="pbar-l"><h2>Reports Summary — ' + company + '</h2>'
      + '<p>' + period + (filteredBy ? ' · Employee: ' + filteredBy : '') + '</p></div>'
      + '<button class="pbar-btn" onclick="window.print()">' + printIcon + ' Print / Save as PDF</button>'
      + '</div>';

    // ── Assemble HTML ────────────────────────────────────
    var printOnLoad = immediate ? '<sc'+'ript>window.onload=function(){window.print();}<\/'+'script>' : '';

    return '<!DOCTYPE html><html lang="en"><head>'
         + '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
         + '<title>Reports Summary — ' + company + '</title>'
         + '<style>' + css + '</style>'
         + printOnLoad
         + '</head><body>'
         + pbar
         + '<div class="doc">'

         /* ── Accent bar ── */
         + '<div class="accent"></div>'

         /* ── Header ── */
         + '<div class="dh">'
         +   '<div class="logo-area">' + logoEl
         +     '<div><div class="co-name">' + company + '</div>'
         +     '<div class="co-sub">' + dept + '</div></div>'
         +   '</div>'
         +   '<div class="rt">'
         +     '<div class="rt-name">Reports &amp; Analytics</div>'
         +     '<div class="rt-badge">&#128274;&ensp;Confidential &mdash; Internal Use</div>'
         +   '</div>'
         + '</div>'

         /* ── Divider ── */
         + '<div class="hr"></div>'

         /* ── Meta strip ── */
         + '<div class="meta">'
         +   '<div class="mi"><div class="mi-lbl">Generated</div><div class="mi-val">' + dateStr + ' at ' + timeStr + '</div></div>'
         +   '<div class="mi"><div class="mi-lbl">Period</div><div class="mi-val">' + period + '</div></div>'
         +   '<div class="mi"><div class="mi-lbl">Prepared By</div><div class="mi-val">' + user + '</div></div>'
         +   '<div class="mi"><div class="mi-lbl">Department</div><div class="mi-val">' + dept + '</div></div>'
         + '</div>'

         /* ── Content ── */
         + '<div class="content">'
         +   '<div class="sh"><div class="sh-bar" style="background:#4F46E5;"></div><div class="sh-text">Performance Overview</div></div>'
         +   kHtml
         +   tHtml
         +   cHtml
         + '</div>'

         /* ── Footer ── */
         + '<div class="foot">'
         +   '<div class="foot-brand">' + company + ' &mdash; Reports &amp; Analytics</div>'
         +   '<div class="foot-mid">Generated ' + dateStr + ' &bull; ' + user + '</div>'
         +   '<div class="foot-conf">Confidential</div>'
         + '</div>'

         + '</div>'  /* .doc */
         + '</body></html>';
}

// ── Reports Summary Modal Filter State ────────────────────────────────────
window._rptSummState = { data: null, loading: false };

function _rptDateStr(d) {
    var y = d.getFullYear(), m = String(d.getMonth()+1).padStart(2,'0'), day = String(d.getDate()).padStart(2,'0');
    return y + '-' + m + '-' + day;
}

function openRptSummarize() {
    document.getElementById('rpt-summarize-modal').style.display = 'flex';
    // Initialize to 1 month back → today if not already set
    var fromEl = document.getElementById('rpt-summ-from');
    var toEl   = document.getElementById('rpt-summ-to');
    if (!fromEl.value) {
        var today  = new Date();
        var oneAgo = new Date(today); oneAgo.setMonth(oneAgo.getMonth() - 1);
        fromEl.value = _rptDateStr(oneAgo);
        toEl.value   = _rptDateStr(today);
    }
    _highlightRptChip('custom');
    fetchRptSummary();
}

function setRptSummPreset(preset) {
    var today = new Date();
    var from, to;
    if (preset === 'all') {
        from = ''; to = '';
    } else if (preset === 'month') {
        var s = new Date(today.getFullYear(), today.getMonth(), 1);
        var e = new Date(today.getFullYear(), today.getMonth()+1, 0);
        from = _rptDateStr(s); to = _rptDateStr(e);
    } else if (preset === 'last') {
        var s = new Date(today.getFullYear(), today.getMonth()-1, 1);
        var e = new Date(today.getFullYear(), today.getMonth(), 0);
        from = _rptDateStr(s); to = _rptDateStr(e);
    } else if (preset === '3m') {
        var s = new Date(today); s.setMonth(s.getMonth()-3); s.setDate(1);
        var e = new Date(today.getFullYear(), today.getMonth()+1, 0);
        from = _rptDateStr(s); to = _rptDateStr(e);
    }
    document.getElementById('rpt-summ-from').value = from || '';
    document.getElementById('rpt-summ-to').value   = to   || '';
    _highlightRptChip(preset);
    fetchRptSummary();
}

function applyRptSummFilter() {
    _highlightRptChip('custom');
    fetchRptSummary();
}

function _highlightRptChip(active) {
    var chips = { all:'rpt-chip-all', month:'rpt-chip-month', last:'rpt-chip-last', '3m':'rpt-chip-3m' };
    Object.keys(chips).forEach(function(k) {
        var el = document.getElementById(chips[k]);
        if (!el) return;
        if (k === active) {
            el.style.background = '#4F46E5'; el.style.color = '#fff'; el.style.borderColor = '#4F46E5';
        } else {
            el.style.background = '#fff'; el.style.color = '#374151'; el.style.borderColor = '#E5E7EB';
        }
    });
}

function fetchRptSummary() {
    var fromVal = document.getElementById('rpt-summ-from').value;
    var toVal   = document.getElementById('rpt-summ-to').value;

    var bodyEl = document.getElementById('rpt-summ-body');
    bodyEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:160px;color:#9CA3AF;font-size:13px;gap:10px;"><div style="width:18px;height:18px;border:2.5px solid #6366F1;border-top-color:transparent;border-radius:50%;animation:rptSummSpin .7s linear infinite;"></div>Loading…</div>';

    var url = new URL('{{ route('admin.reports.summary-data') }}', window.location.origin);
    if (fromVal) url.searchParams.set('date_from', fromVal);
    if (toVal)   url.searchParams.set('date_to',   toVal);
    // Pass current page filters
    var pageParams = new URLSearchParams(window.location.search);
    ['project_id','customer_id','user_id'].forEach(function(k) {
        if (pageParams.get(k)) url.searchParams.set(k, pageParams.get(k));
    });

    fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            window._rptSummState.data = data;
            // Update subtitle
            var sub = document.getElementById('rpt-summ-subtitle');
            if (sub) sub.textContent = data.periodLabel + (data.hasUser ? '' : '');
            renderRptSummBody(data);
        })
        .catch(function() {
            bodyEl.innerHTML = '<div style="text-align:center;padding:30px;color:#DC2626;font-size:13px;">Failed to load data. Please try again.</div>';
        });
}

function renderRptSummBody(d) {
    function rateColor(r) { return r >= 80 ? '#10B981' : (r >= 40 ? '#F59E0B' : '#EF4444'); }
    function rateBg(r)    { return r >= 80 ? '#D1FAE5' : (r >= 40 ? '#FEF3C7' : '#FEE2E2'); }

    var cols2 = d.adBudgetTotal > 0 ? 4 : 3;
    var adBudgetBox = d.adBudgetTotal > 0
        ? '<div style="background:#EFF6FF;border-radius:12px;padding:14px;">'
          + '<p style="font-size:10px;font-weight:700;color:#2563EB;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Ad Budget</p>'
          + '<p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + Math.round(d.adBudgetTotal).toLocaleString() + '</p>'
          + '<p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Total BHD · ' + d.adBudgetCount + ' campaigns</p>'
          + '</div>'
        : '';

    var teamHtml = '';
    if (d.teamMembers && d.teamMembers.length) {
        teamHtml = '<div style="margin-bottom:18px;">'
            + '<p style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;display:flex;align-items:center;gap:7px;">'
            + '<span style="width:20px;height:20px;border-radius:5px;background:#EDE9FE;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-users" style="color:#7C3AED;font-size:9px;"></i></span>'
            + 'Team Performance (Top ' + d.teamMembers.length + ')</p>'
            + '<div style="display:flex;flex-direction:column;gap:7px;">';
        d.teamMembers.forEach(function(m) {
            teamHtml += '<div style="display:flex;align-items:center;gap:10px;">'
                + '<span style="font-size:12px;font-weight:600;color:#111827;min-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="' + m.name + '">' + m.name + '</span>'
                + '<span style="font-size:10px;color:#9CA3AF;min-width:50px;">' + m.total + ' tasks</span>'
                + '<div style="flex:1;height:6px;background:#F3F4F6;border-radius:99px;overflow:hidden;">'
                + '<div style="height:6px;width:' + m.rate + '%;background:' + rateColor(m.rate) + ';border-radius:99px;"></div></div>'
                + '<span style="font-size:12px;font-weight:700;min-width:34px;text-align:right;color:' + rateColor(m.rate) + ';">' + m.rate + '%</span>'
                + '</div>';
        });
        teamHtml += '</div></div>';
    }

    var custHtml = '';
    if (!d.hasUser && d.customerStats && d.customerStats.length) {
        custHtml = '<div>'
            + '<p style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;display:flex;align-items:center;gap:7px;">'
            + '<span style="width:20px;height:20px;border-radius:5px;background:#EEF2FF;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-building" style="color:#4F46E5;font-size:9px;"></i></span>'
            + 'Top Customers (by task volume)</p>'
            + '<div style="display:flex;flex-direction:column;gap:7px;">';
        d.customerStats.forEach(function(c) {
            custHtml += '<div style="display:flex;align-items:center;gap:10px;">'
                + '<span style="font-size:12px;font-weight:600;color:#4F46E5;min-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="' + c.name + '">' + c.name + '</span>'
                + '<span style="font-size:10px;color:#9CA3AF;min-width:50px;">' + c.total + ' tasks</span>'
                + '<div style="flex:1;height:6px;background:#F3F4F6;border-radius:99px;overflow:hidden;">'
                + '<div style="height:6px;width:' + c.rate + '%;background:' + rateColor(c.rate) + ';border-radius:99px;"></div></div>'
                + '<span style="font-size:12px;font-weight:700;min-width:34px;text-align:right;color:' + rateColor(c.rate) + ';">' + c.rate + '%</span>'
                + '</div>';
        });
        custHtml += '</div></div>';
    }

    var html = ''
        + '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:10px;">'
        +   '<div style="background:#EEF2FF;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#6366F1;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Total Tasks</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + d.totalTasks + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">In selected period</p></div>'
        +   '<div style="background:#D1FAE5;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Completed</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + d.completedTasks + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Approved + Delivered</p></div>'
        +   '<div style="background:#FEF3C7;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#D97706;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Completion Rate</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + d.completionRate + '%</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Of all tasks</p></div>'
        +   '<div style="background:#EDE9FE;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#7C3AED;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">On-Time Rate</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + d.onTimeRate + '%</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Before deadline</p></div>'
        + '</div>'
        + '<div style="display:grid;grid-template-columns:repeat(' + cols2 + ',1fr);gap:10px;margin-bottom:18px;">'
        +   '<div style="background:#FEE2E2;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#DC2626;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Overdue</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + d.overdueTasks + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Need attention</p></div>'
        +   '<div style="background:#DBEAFE;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#1D4ED8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Active Projects</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + d.activeProjects + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Currently running</p></div>'
        +   '<div style="background:#EDE9FE;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#7C3AED;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Pending Review</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + d.pendingReview + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Awaiting approval</p></div>'
        +   adBudgetBox
        + '</div>'
        + teamHtml
        + custHtml;

    document.getElementById('rpt-summ-body').innerHTML = html;
}

function printSummarize() {
    var win = window.open('', '_blank');
    if (win) { win.document.write(buildSummarizeHTML(true)); win.document.close(); }
    else { alert('Pop-up blocked — please allow pop-ups for this site and try again.'); }
}

function exportSummarizePDF() {
    var win = window.open('', '_blank');
    if (!win) { alert('Pop-up blocked — please allow pop-ups for this site and try again.'); return; }
    var html = buildSummarizeHTML(false);
    /* Inject a small script that auto-opens print dialog after fonts/images settle */
    html = html.replace('</body>', '<sc'+'ript>setTimeout(function(){window.print();},600);<\/sc'+'ript></body>');
    win.document.write(html);
    win.document.close();
}

function openRptSectionPicker() {
    var picker = document.getElementById('rpt-section-picker');
    if (!picker) return;
    picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
}

// Enable/disable "Show tasks as %" based on task-dist checkbox
(function() {
    function syncTaskPctToggle() {
        var distChk = document.getElementById('rpt-sec-task-dist');
        var wrap    = document.getElementById('rpt-task-dist-pct-wrap');
        if (!distChk || !wrap) return;
        var on = distChk.checked;
        wrap.style.opacity       = on ? '1'    : '.4';
        wrap.style.pointerEvents = on ? 'auto' : 'none';
        if (!on) { var pctChk = document.getElementById('rpt-sec-task-as-pct'); if (pctChk) pctChk.checked = false; }
    }
    document.addEventListener('DOMContentLoaded', function() {
        var distChk = document.getElementById('rpt-sec-task-dist');
        if (distChk) { distChk.addEventListener('change', syncTaskPctToggle); syncTaskPctToggle(); }
    });
})();

function doRptSummPrint() {
    function chk(id, def) { var el = document.getElementById(id); return el ? el.checked : def; }
    var sections = {
        kpi1:       chk('rpt-sec-kpi1',       true),
        kpi2:       chk('rpt-sec-kpi2',       true),
        team:       chk('rpt-sec-team',       true),
        customers:  chk('rpt-sec-customers',  true),
        taskDist:   chk('rpt-sec-task-dist',  false),
        taskAsPct:  chk('rpt-sec-task-as-pct',false),
        taskList:   chk('rpt-sec-task-list',  false),
        narrative:  chk('rpt-sec-narrative',  true),
        notes:      chk('rpt-sec-notes',      true),
        signature:  chk('rpt-sec-signature',  true),
    };
    document.getElementById('rpt-section-picker').style.display = 'none';
    printRptSummSelection(sections);
}

function printRptSummSelection(sections) {
    var d        = window._rptSummState && window._rptSummState.data;
    var period   = (document.getElementById('rpt-summ-subtitle') || {}).textContent || '';
    var company  = '{{ addslashes($appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name')) }}';
    var dept     = '{{ addslashes($appSettings['department_name'] ?? 'Operations') }}';
    var dateStr  = '{{ now()->format(config('app.date_format','M d, Y')) }}';
    var timeStr  = '{{ now()->format('H:i') }}';
    var user     = '{{ auth()->user()->name }}';
    var logoSrc  = '{{ $summarizeLogo ?? '' }}';
    var mono     = company.charAt(0).toUpperCase();

    function rateColor(r) { return r >= 80 ? '#10B981' : (r >= 40 ? '#F59E0B' : '#EF4444'); }
    function rateBg(r)    { return r >= 80 ? '#D1FAE5' : (r >= 40 ? '#FEF3C7' : '#FEE2E2'); }

    var bodyHtml = '';

    // Row 1: KPI stats
    if (!sections || sections.kpi1) {
        var tt  = d ? d.totalTasks     : '—';
        var ct  = d ? d.completedTasks : '—';
        var cr  = d ? d.completionRate + '%' : '—';
        var otr = d ? d.onTimeRate + '%'     : '—';
        bodyHtml += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:10px;">'
            + '<div style="background:#EEF2FF;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#6366F1;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Total Tasks</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + tt + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">In selected period</p></div>'
            + '<div style="background:#D1FAE5;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Completed</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + ct + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Approved + Delivered</p></div>'
            + '<div style="background:#FEF3C7;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#D97706;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Completion Rate</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + cr + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Of all tasks</p></div>'
            + '<div style="background:#EDE9FE;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#7C3AED;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">On-Time Rate</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + otr + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Before deadline</p></div>'
            + '</div>';
    }

    // Row 2: Overdue / Projects / Review / Ad Budget
    if (!sections || sections.kpi2) {
        var ov  = d ? d.overdueTasks   : '—';
        var ap  = d ? d.activeProjects : '—';
        var pr  = d ? d.pendingReview  : '—';
        var cols2 = (d && d.adBudgetTotal > 0) ? 4 : 3;
        var adBox = (d && d.adBudgetTotal > 0)
            ? '<div style="background:#EFF6FF;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#2563EB;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Ad Budget</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + Math.round(d.adBudgetTotal).toLocaleString() + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Total BHD · ' + d.adBudgetCount + ' campaigns</p></div>'
            : '';
        bodyHtml += '<div style="display:grid;grid-template-columns:repeat(' + cols2 + ',1fr);gap:10px;margin-bottom:18px;">'
            + '<div style="background:#FEE2E2;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#DC2626;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Overdue</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + ov + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Need attention</p></div>'
            + '<div style="background:#DBEAFE;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#1D4ED8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Active Projects</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + ap + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Currently running</p></div>'
            + '<div style="background:#EDE9FE;border-radius:12px;padding:14px;"><p style="font-size:10px;font-weight:700;color:#7C3AED;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Pending Review</p><p style="font-size:28px;font-weight:800;color:#111827;margin:0;line-height:1;">' + pr + '</p><p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Awaiting approval</p></div>'
            + adBox
            + '</div>';
    }

    // Team Performance
    if ((!sections || sections.team) && d && d.teamMembers && d.teamMembers.length) {
        bodyHtml += '<div style="margin-bottom:18px;">'
            + '<p style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;">Team Performance (Top ' + d.teamMembers.length + ')</p>'
            + '<div style="display:flex;flex-direction:column;gap:7px;">';
        d.teamMembers.forEach(function(m) {
            bodyHtml += '<div style="display:flex;align-items:center;gap:10px;">'
                + '<span style="font-size:12px;font-weight:600;color:#111827;min-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + m.name + '</span>'
                + '<span style="font-size:10px;color:#9CA3AF;min-width:50px;">' + m.total + ' tasks</span>'
                + '<div style="flex:1;height:6px;background:#F3F4F6;border-radius:99px;overflow:hidden;">'
                + '<div style="height:6px;width:' + m.rate + '%;background:' + rateColor(m.rate) + ';border-radius:99px;"></div></div>'
                + '<span style="font-size:12px;font-weight:700;min-width:34px;text-align:right;color:' + rateColor(m.rate) + ';">' + m.rate + '%</span>'
                + '</div>';
        });
        bodyHtml += '</div></div>';
    }

    // Top Customers
    if ((!sections || sections.customers) && d && !d.hasUser && d.customerStats && d.customerStats.length) {
        bodyHtml += '<div style="margin-bottom:18px;">'
            + '<p style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;">Top Customers (by task volume)</p>'
            + '<div style="display:flex;flex-direction:column;gap:7px;">';
        d.customerStats.forEach(function(c) {
            bodyHtml += '<div style="display:flex;align-items:center;gap:10px;">'
                + '<span style="font-size:12px;font-weight:600;color:#4F46E5;min-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + c.name + '</span>'
                + '<span style="font-size:10px;color:#9CA3AF;min-width:50px;">' + c.total + ' tasks</span>'
                + '<div style="flex:1;height:6px;background:#F3F4F6;border-radius:99px;overflow:hidden;">'
                + '<div style="height:6px;width:' + c.rate + '%;background:' + rateColor(c.rate) + ';border-radius:99px;"></div></div>'
                + '<span style="font-size:12px;font-weight:700;min-width:34px;text-align:right;color:' + rateColor(c.rate) + ';">' + c.rate + '%</span>'
                + '</div>';
        });
        bodyHtml += '</div></div>';
    }

    // Task % by Customer — one table per month (last 6 calendar months)
    if (sections && sections.taskDist && d && !d.hasUser && d.customerStatsMonthly && d.customerStatsMonthly.length) {
        var thStyle = 'text-align:center;padding:8px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #E5E7EB;';
        var thLeft  = 'text-align:left;padding:8px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #E5E7EB;';
        var thRight = 'text-align:right;padding:8px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #E5E7EB;';

        bodyHtml += '<div>'
            + '<p style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em;margin:18px 0 4px;padding-bottom:8px;border-bottom:2px solid #4F46E5;">Work Distribution by Customer — Monthly</p>';

        d.customerStatsMonthly.forEach(function(month) {
            var distRows = month.stats || [];
            var grandTasks    = distRows.reduce(function(sum, c) { return sum + c.total; }, 0);
            var grandProjects = distRows.reduce(function(sum, c) { return sum + (c.projects||0); }, 0);
            var grandWorkload = grandTasks + grandProjects;

            bodyHtml += '<div style="margin-top:14px;">'
                + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">'
                + '<p style="font-size:12px;font-weight:700;color:#111827;margin:0;">' + month.label + '</p>'
                + '<p style="font-size:10px;color:#9CA3AF;margin:0;">' + grandTasks + ' tasks &nbsp;+&nbsp; ' + grandProjects + ' project' + (grandProjects !== 1 ? 's' : '') + ' &nbsp;=&nbsp; ' + grandWorkload + ' work units</p>'
                + '</div>';

            if (!distRows.length) {
                bodyHtml += '<p style="text-align:center;padding:10px;color:#9CA3AF;font-size:11px;background:#FAFAFA;border-radius:8px;">No task activity this month.</p></div>';
                return;
            }

            bodyHtml += '<table style="width:100%;border-collapse:collapse;">'
                + '<thead><tr style="background:#F8FAFC;">'
                + '<th style="' + thLeft  + 'width:28px;">#</th>'
                + '<th style="' + thLeft  + '">Customer</th>'
                + '<th style="' + thStyle + '">' + (sections.taskAsPct ? '% Share' : 'Tasks') + '</th>'
                + '<th style="' + thStyle + '">' + (sections.taskAsPct ? '' : 'Projects') + '</th>'
                + '<th style="' + thStyle + 'min-width:110px;">Share</th>'
                + (sections.taskAsPct ? '' : '<th style="' + thRight + '">% of Total</th>')
                + '</tr></thead><tbody>';

            distRows.forEach(function(c, i) {
                var pctInt = c.share_pct || 0;
                var pctStr = pctInt + '%';
                var rowBg  = i % 2 === 0 ? '#fff' : '#FAFAFA';
                var td = 'padding:9px 10px;border-bottom:1px solid #F3F4F6;';
                bodyHtml += '<tr style="background:' + rowBg + ';">'
                    + '<td style="' + td + 'font-size:11px;color:#9CA3AF;">' + (i + 1) + '</td>'
                    + '<td style="' + td + 'font-size:13px;font-weight:600;color:#111827;">' + c.name + '</td>'
                    + (sections.taskAsPct
                        ? '<td style="' + td + 'font-size:13px;font-weight:800;color:#4F46E5;text-align:center;">' + pctStr + '</td>'
                        : '<td style="' + td + 'font-size:13px;font-weight:700;color:#4F46E5;text-align:center;">' + c.total + '</td>')
                    + (sections.taskAsPct
                        ? '<td></td>'
                        : '<td style="' + td + 'font-size:13px;font-weight:700;color:#7C3AED;text-align:center;">' + (c.projects||0) + '</td>')
                    + '<td style="' + td + '">'
                    +   '<div style="flex:1;height:7px;background:#EEF2FF;border-radius:99px;overflow:hidden;">'
                    +   '<div style="height:7px;width:' + pctInt + '%;background:#4F46E5;border-radius:99px;"></div>'
                    +   '</div></td>'
                    + (sections.taskAsPct ? '' : '<td style="' + td + 'font-size:13px;font-weight:800;color:#4F46E5;text-align:right;">' + pctStr + '</td>')
                    + '</tr>';
            });

            // Totals row
            bodyHtml += '<tr style="background:#EEF2FF;">'
                + '<td colspan="2" style="padding:10px 10px;font-size:12px;font-weight:700;color:#374151;">Total</td>'
                + (sections.taskAsPct
                    ? '<td style="padding:10px;font-size:13px;font-weight:800;color:#4F46E5;text-align:center;">100%</td><td></td>'
                    : '<td style="padding:10px;font-size:13px;font-weight:800;color:#4F46E5;text-align:center;">' + grandTasks + '</td>'
                    + '<td style="padding:10px;font-size:13px;font-weight:800;color:#7C3AED;text-align:center;">' + grandProjects + '</td>')
                + '<td></td>'
                + (sections.taskAsPct ? '' : '<td style="padding:10px;font-size:13px;font-weight:800;color:#4F46E5;text-align:right;">100%</td>')
                + '</tr>';

            bodyHtml += '</tbody></table></div>';
        });

        bodyHtml += '</div>';
    }

    // Task List (this period)
    if (sections && sections.taskList && d && d.taskList && d.taskList.length) {
        function escHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
                return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
            });
        }
        function taskStatusColor(s) {
            var map = @json(collect(\App\Support\TaskStatusColors::MAP)->map(fn($c) => $c['text']));
            return map[s] || '#6B7280';
        }
        function taskStatusLabel(s) {
            var map = @json(collect(\App\Support\TaskStatusColors::MAP)->map(fn($c) => $c['label']));
            return map[s] || (s || '').replace(/_/g, ' ');
        }
        var thLeftTL  = 'text-align:left;padding:8px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #E5E7EB;';
        var thCtrTL   = 'text-align:center;padding:8px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #E5E7EB;';

        // Group tasks by customer — one table per customer
        var tlGroups = {};
        var tlOrder  = [];
        d.taskList.forEach(function(t) {
            var key = t.customer || 'No Customer';
            if (!tlGroups[key]) { tlGroups[key] = []; tlOrder.push(key); }
            tlGroups[key].push(t);
        });
        tlOrder.sort(function(a, b) {
            if (a === 'No Customer') return 1;
            if (b === 'No Customer') return -1;
            return tlGroups[b].length - tlGroups[a].length;
        });

        bodyHtml += '<div style="margin-top:18px;">'
            + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #0EA5E9;">'
            + '<div>'
            + '<p style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em;margin:0;">Task List</p>'
            + '<p style="font-size:11px;color:#6B7280;margin:3px 0 0;">' + period + '</p>'
            + '</div>'
            + '<div style="text-align:right;">'
            + '<p style="font-size:11px;color:#9CA3AF;margin:0;">Tasks Listed</p>'
            + '<p style="font-size:28px;font-weight:900;color:#0EA5E9;margin:0;line-height:1.1;">' + d.taskList.length + '</p>'
            + '</div>'
            + '</div>';

        tlOrder.forEach(function(custName, gi) {
            var rows = tlGroups[custName];
            bodyHtml += '<div style="margin-top:' + (gi === 0 ? '0' : '20px') + ';page-break-inside:avoid;break-inside:avoid;">'
                + '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;page-break-after:avoid;break-after:avoid;">'
                + '<i class="fas fa-building" style="color:#4F46E5;font-size:11px;"></i>'
                + '<p style="font-size:12.5px;font-weight:700;color:#111827;margin:0;">' + escHtml(custName) + '</p>'
                + '<span style="font-size:10px;font-weight:600;color:#4F46E5;background:#EEF2FF;padding:2px 9px;border-radius:20px;">' + rows.length + ' task' + (rows.length === 1 ? '' : 's') + '</span>'
                + '</div>'
                + '<table style="width:100%;border-collapse:collapse;table-layout:fixed;">'
                + '<colgroup><col style="width:5%;"><col style="width:27%;"><col style="width:20%;"><col style="width:17%;"><col style="width:16%;"><col style="width:15%;"></colgroup>'
                + '<thead><tr style="background:#F8FAFC;">'
                + '<th style="' + thLeftTL + '">#</th>'
                + '<th style="' + thLeftTL + '">Task</th>'
                + '<th style="' + thLeftTL + '">Project</th>'
                + '<th style="' + thLeftTL + '">Assignee</th>'
                + '<th style="' + thCtrTL  + '">Status</th>'
                + '<th style="' + thCtrTL  + '">Deadline</th>'
                + '</tr></thead><tbody>';

            rows.forEach(function(t, i) {
                var rowBg  = i % 2 === 0 ? '#fff' : '#FAFAFA';
                var td     = 'padding:9px 10px;border-bottom:1px solid #F3F4F6;overflow-wrap:break-word;word-break:break-word;';
                var scLbl  = escHtml(taskStatusLabel(t.status));
                var scCol  = taskStatusColor(t.status);
                bodyHtml += '<tr style="background:' + rowBg + ';">'
                    + '<td style="' + td + 'font-size:11px;color:#9CA3AF;">' + (i + 1) + '</td>'
                    + '<td style="' + td + 'font-size:12.5px;font-weight:600;color:#111827;">' + escHtml(t.title) + '</td>'
                    + '<td style="' + td + 'font-size:11.5px;color:#4B5563;">' + escHtml(t.project || '—') + '</td>'
                    + '<td style="' + td + 'font-size:11.5px;color:#4B5563;">' + escHtml(t.assignee || '—') + '</td>'
                    + '<td style="' + td + 'text-align:center;"><span style="display:inline-block;min-width:74px;background:' + scCol + '22;color:' + scCol + ';font-size:9px;font-weight:700;padding:2px 8px;border-radius:99px;text-transform:capitalize;">' + scLbl + '</span></td>'
                    + '<td style="' + td + 'font-size:11.5px;color:#4B5563;text-align:center;">' + escHtml(t.deadline || '—') + '</td>'
                    + '</tr>';
            });

            bodyHtml += '</tbody></table></div>';
        });

        bodyHtml += '</div>';
    }

    var logoEl = logoSrc
        ? '<img class="logo-img" src="' + logoSrc + '" alt="' + company + '">'
        : '<div class="logo-mono">' + mono + '</div>';

    var css =
      '* { box-sizing:border-box; margin:0; padding:0; }'
    + 'body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif; font-size:12px; color:#111827; background:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.doc { max-width:860px; margin:0 auto; background:#fff; }'
    + '.accent { height:6px; background:linear-gradient(90deg,#4F46E5 0%,#7C3AED 45%,#06B6D4 100%); -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.dh { padding:20px 32px 16px; display:flex; justify-content:space-between; align-items:center; gap:16px; }'
    + '.logo-area { display:flex; align-items:center; gap:14px; }'
    + '.logo-img  { height:48px; width:auto; max-width:160px; object-fit:contain; }'
    + '.logo-mono { width:48px; height:48px; background:linear-gradient(135deg,#4F46E5,#7C3AED); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:25px; font-weight:900; flex-shrink:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.co-name { font-size:17px; font-weight:800; color:#111827; line-height:1.2; }'
    + '.co-sub  { font-size:10px; color:#9CA3AF; margin-top:3px; }'
    + '.rt { text-align:right; }'
    + '.rt-name { font-size:20px; font-weight:900; color:#4F46E5; letter-spacing:-.4px; line-height:1.15; }'
    + '.rt-badge { display:inline-flex; align-items:center; gap:5px; margin-top:6px; padding:3px 12px; background:#EEF2FF; border-radius:20px; font-size:10px; font-weight:600; color:#6366F1; border:1px solid #C7D2FE; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.hr { height:1px; background:#E5E7EB; }'
    + '.meta { display:flex; background:#F8FAFC; padding:12px 32px; border-bottom:1px solid #E5E7EB; gap:0; }'
    + '.mi { flex:1; min-width:0; padding-right:20px; border-right:1px solid #E5E7EB; margin-right:20px; }'
    + '.mi:last-child { border-right:none; padding-right:0; margin-right:0; }'
    + '.mi-lbl { font-size:8px; font-weight:700; color:#9CA3AF; text-transform:uppercase; letter-spacing:.09em; }'
    + '.mi-val { font-size:11.5px; font-weight:600; color:#374151; margin-top:3px; }'
    + '.content { padding:22px 32px 26px; }'
    + '.foot { margin:4px 32px 0; padding:13px 0; border-top:2px solid #4F46E5; display:flex; justify-content:space-between; align-items:center; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.foot-brand { font-size:10px; font-weight:700; color:#4F46E5; }'
    + '.foot-mid   { font-size:10px; color:#9CA3AF; }'
    + '.foot-conf  { font-size:10px; color:#9CA3AF; font-style:italic; }'
    + '@media print { body{background:#fff !important;} .doc{max-width:none;} *{-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important;} tr{page-break-inside:avoid;} @page{size:A4 portrait;margin:0;} }';

    // ── Auto-generated narrative ─────────────────────────────────────────────
    var narrativeHtml = '';
    if ((!sections || sections.narrative !== false) && d) {
        // Use customer stats as the source of truth so narrative matches the table
        var _hasCusts  = !d.hasUser && d.customerStats && d.customerStats.length > 0;
        var _custSum   = _hasCusts ? d.customerStats.reduce(function(s,c){return s+c.total+(c.projects||0);},0) : 0;
        var _custDone  = _hasCusts ? d.customerStats.reduce(function(s,c){return s+(c.completed||0);},0) : 0;
        var narTotal   = _hasCusts ? _custSum   : d.totalTasks;
        var narDone    = _hasCusts ? _custDone  : d.completedTasks;
        var narRate    = narTotal > 0 ? Math.round(narDone / narTotal * 100) : 0;

        var sentences = [];
        sentences.push('This report covers the period <strong>' + period + '</strong>, prepared by <strong>' + user + '</strong>.');
        sentences.push('During this period, a total of <strong>' + narTotal + ' task' + (narTotal !== 1 ? 's' : '') + '</strong> were recorded across <strong>' + d.activeProjects + ' active project' + (d.activeProjects !== 1 ? 's' : '') + '</strong>.');
        sentences.push('Of these, <strong>' + narDone + '</strong> were successfully completed, yielding an overall delivery rate of <strong>' + narRate + '%</strong>.');
        if (d.onTimeRate > 0) {
            sentences.push('<strong>' + d.onTimeRate + '%</strong> of completed tasks were delivered before their deadline.');
        }
        if (d.overdueTasks > 0) {
            sentences.push('<strong>' + d.overdueTasks + ' task' + (d.overdueTasks !== 1 ? 's' : '') + '</strong> remain' + (d.overdueTasks === 1 ? 's' : '') + ' overdue and require immediate attention.');
        }
        if (d.pendingReview > 0) {
            sentences.push('<strong>' + d.pendingReview + ' task' + (d.pendingReview !== 1 ? 's' : '') + '</strong> ' + (d.pendingReview === 1 ? 'is' : 'are') + ' awaiting approval.');
        }
        if (_hasCusts) {
            var topC       = d.customerStats.slice().sort(function(a,b){return (b.total+(b.projects||0))-(a.total+(a.projects||0));})[0];
            var topWorkload = topC.total + (topC.projects||0);
            var topCpct    = _custSum > 0 ? Math.round(topWorkload / _custSum * 100) : 0;
            var topDesc    = topC.total + ' task' + (topC.total !== 1 ? 's' : '') + ((topC.projects||0) > 0 ? ' + ' + topC.projects + ' project' + (topC.projects !== 1 ? 's' : '') : '');
            sentences.push('<strong>' + topC.name + '</strong> had the highest workload: <strong>' + topDesc + '</strong> (' + topCpct + '% of total).');
        }
        narrativeHtml =
            '<div style="margin-top:24px;padding:16px 20px;background:#F8FAFC;border-left:4px solid #4F46E5;border-radius:0 8px 8px 0;page-break-inside:avoid;">'
          + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">'
          +   '<p style="font-size:9.5px;font-weight:700;color:#4F46E5;text-transform:uppercase;letter-spacing:.09em;margin:0;">Report Summary</p>'
          +   '<span class="edit-hint" style="font-size:9px;color:#9CA3AF;font-style:italic;">Click text to edit</span>'
          + '</div>'
          + '<div contenteditable="true" spellcheck="false" style="font-size:12px;color:#374151;line-height:1.75;outline:none;min-height:1.75em;border-radius:4px;padding:2px 4px;cursor:text;" onfocus="this.style.background=\'#fff\';this.style.boxShadow=\'0 0 0 2px #C7D2FE\';" onblur="this.style.background=\'\';this.style.boxShadow=\'\';">'
          + sentences.join(' ')
          + '</div>'
          + '<style>@media print{.edit-hint{display:none!important;}[contenteditable]{outline:none!important;box-shadow:none!important;background:transparent!important;}}</style>'
          + '</div>';
    }

    // ── Notes / Remarks area ─────────────────────────────────────────────────
    var notesHtml = (!sections || sections.notes !== false)
      ? '<div style="margin-top:22px;page-break-inside:avoid;">'
      + '<p style="font-size:9.5px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.09em;margin:0 0 10px;">Notes &amp; Remarks</p>'
      + '<div contenteditable="true" spellcheck="false"'
      +   ' style="border:1px solid #E5E7EB;border-radius:8px;padding:12px 14px;min-height:90px;font-size:12px;color:#374151;line-height:1.8;outline:none;cursor:text;white-space:pre-wrap;"'
      +   ' onfocus="this.style.borderColor=\'#6366F1\';this.style.boxShadow=\'0 0 0 3px rgba(99,102,241,.15)\';"'
      +   ' onblur="this.style.borderColor=\'#E5E7EB\';this.style.boxShadow=\'none\';"'
      +   ' data-placeholder="Write your notes here…">'
      + '</div>'
      + '<style>'
      + '[contenteditable][data-placeholder]:empty:before{content:attr(data-placeholder);color:#D1D5DB;pointer-events:none;}'
      + '@media print{[contenteditable]{border:1px solid #E5E7EB!important;box-shadow:none!important;outline:none!important;}}'
      + '</style>'
      + '</div>'
      : '';

    // ── Signature block ──────────────────────────────────────────────────────
    var signHtml = (!sections || sections.signature !== false)
      ? '<div style="margin-top:28px;display:grid;grid-template-columns:repeat(3,1fr);gap:28px;page-break-inside:avoid;">'
      + '<div>'
      +   '<p style="font-size:9px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.09em;margin:0 0 24px;">Prepared By</p>'
      +   '<div style="border-bottom:1.5px solid #374151;margin-bottom:8px;"></div>'
      +   '<p style="font-size:11px;font-weight:700;color:#111827;margin:0 0 2px;">' + user + '</p>'
      +   '<p style="font-size:10px;color:#9CA3AF;margin:0;">' + dateStr + '</p>'
      + '</div>'
      + '<div>'
      +   '<p style="font-size:9px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.09em;margin:0 0 24px;">Reviewed By</p>'
      +   '<div style="border-bottom:1.5px solid #374151;margin-bottom:8px;"></div>'
      +   '<p style="font-size:11px;color:#D1D5DB;margin:0 0 2px;">Name &amp; Signature</p>'
      +   '<p style="font-size:10px;color:#E5E7EB;margin:0;">Date</p>'
      + '</div>'
      + '<div>'
      +   '<p style="font-size:9px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.09em;margin:0 0 24px;">Approved By</p>'
      +   '<div style="border-bottom:1.5px solid #374151;margin-bottom:8px;"></div>'
      +   '<p style="font-size:11px;color:#D1D5DB;margin:0 0 2px;">Name &amp; Signature</p>'
      +   '<p style="font-size:10px;color:#E5E7EB;margin:0;">Date</p>'
      + '</div>'
      + '</div>'
      : '';

    var html = '<!DOCTYPE html><html lang="en"><head>'
        + '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        + '<title>Reports Summary — ' + company + '</title>'
        + '<style>' + css + '</style>'
        + '<script>window.onload=function(){window.print();}<\/script>'
        + '</head><body>'
        + '<div class="doc">'
        + '<div class="accent"></div>'
        + '<div class="dh">'
        +   '<div class="logo-area">' + logoEl
        +     '<div><div class="co-name">' + company + '</div>'
        +     '<div class="co-sub">' + dept + '</div></div>'
        +   '</div>'
        +   '<div class="rt">'
        +     '<div class="rt-name">Reports &amp; Analytics</div>'
        +   '</div>'
        + '</div>'
        + '<div class="hr"></div>'
        + '<div class="meta">'
        +   '<div class="mi"><div class="mi-lbl">Generated</div><div class="mi-val">' + dateStr + ' at ' + timeStr + '</div></div>'
        +   '<div class="mi"><div class="mi-lbl">Period</div><div class="mi-val">' + period + '</div></div>'
        +   '<div class="mi"><div class="mi-lbl">Prepared By</div><div class="mi-val">' + user + '</div></div>'
        +   '<div class="mi"><div class="mi-lbl">Department</div><div class="mi-val">' + dept + '</div></div>'
        + '</div>'
        + '<div class="content">' + bodyHtml + narrativeHtml + notesHtml + signHtml + '</div>'
        + '<div class="foot">'
        +   '<div class="foot-brand">' + company + ' &mdash; Reports &amp; Analytics</div>'
        +   '<div class="foot-mid">Generated ' + dateStr + ' &bull; ' + user + '</div>'
        +   '<div class="foot-conf"></div>'
        + '</div>'
        + '</div>'
        + '</body></html>';

    var win = window.open('', '_blank');
    if (win) { win.document.write(html); win.document.close(); }
    else { alert('Pop-up blocked — please allow pop-ups for this site and try again.'); }
}

// Close print dropdown when clicking outside
document.addEventListener('click', function(e) {
    var wrap = document.getElementById('rpt-print-wrap');
    var menu = document.getElementById('rpt-print-menu');
    if (wrap && menu && !wrap.contains(e.target)) menu.style.display = 'none';
});

function _pdfDownload(buildFn, filename) {
    if (typeof html2pdf === 'undefined') { alert('PDF library not loaded yet — please try again in a moment.'); return; }
    /* Loading overlay — keeps wrapper invisible to user while html2canvas renders it */
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

function exportSocialPostsPDF() {
    var rows = _spFiltered.length ? _spFiltered : _spAllRows;

    var css = [
        'body{font-family:Arial,sans-serif;font-size:11px;color:#111;margin:0;background:#F9FAFB;}',
        '.preview-bar{position:sticky;top:0;z-index:10;background:#fff;border-bottom:1px solid #E5E7EB;padding:14px 28px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 4px rgba(0,0,0,.06);}',
        '.preview-bar h2{margin:0;font-size:14px;color:#111827;}',
        '.preview-bar p{margin:0;font-size:11px;color:#6B7280;}',
        '.preview-bar .print-btn{padding:8px 20px;background:#7C3AED;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;}',
        '.preview-bar .print-btn:hover{background:#6D28D9;}',
        '.table-wrap{padding:24px 28px;}',
        'table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);}',
        'thead tr{background:#F3F4F6;}',
        'th{font-size:10px;font-weight:700;color:#374151;text-align:left;padding:8px 10px;border-bottom:2px solid #E5E7EB;}',
        'td{padding:7px 10px;border-bottom:1px solid #F3F4F6;font-size:10px;color:#374151;vertical-align:middle;}',
        'tbody tr:nth-child(even) td{background:#FAFAFA;}',
        'a{color:#4F46E5;text-decoration:none;}',
        '@@media print{',
        '  .preview-bar{display:none !important;}',
        '  body{background:#fff;}',
        '  .table-wrap{padding:0;}',
        '  table{box-shadow:none;border-radius:0;}',
        '  thead{display:table-header-group;}',
        '  tr{page-break-inside:avoid;}',
        '  @@page{margin:12mm;size:A4 landscape;}',
        '}'
    ].join('');

    var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Social Posts Report</title><style>' + css + '</style></head><body>'
        + '<div class="preview-bar">'
        +   '<div><h2>Social Posts Report</h2><p>' + rows.length + ' record' + (rows.length !== 1 ? 's' : '') + ' — scroll to review, then click Print</p></div>'
        +   '<button class="print-btn" onclick="window.print()">&#128438; Print / Save as PDF</button>'
        + '</div>'
        + '<div class="table-wrap">'
        + '<table><thead><tr>'
        + '<th>#</th><th>Task</th><th>Customer</th><th>Platform</th><th>Posted By</th><th>Date</th><th>Link</th>'
        + '</tr></thead><tbody>';

    rows.forEach(function(r, i) {
        var cells = r.querySelectorAll('td');
        if (!cells.length) return;
        var taskA  = r.querySelector('td:first-child a');
        var task   = taskA ? (taskA.getAttribute('title') || taskA.textContent.trim()) : '';
        var href   = taskA ? taskA.href : '';
        var cust   = cells[1] ? cells[1].textContent.trim() : '';
        var plat   = cells[2] ? cells[2].textContent.trim() : '';
        var poster = cells[3] ? cells[3].textContent.trim() : '';
        var date   = cells[4] ? cells[4].textContent.trim() : '';
        var linkA  = cells[5] ? cells[5].querySelector('a') : null;
        var url    = linkA ? linkA.href : '';
        html += '<tr>'
            + '<td style="color:#9CA3AF;width:30px;">' + (i + 1) + '</td>'
            + '<td style="max-width:220px;">' + (href ? '<a href="' + href + '">' + task + '</a>' : task) + '</td>'
            + '<td>' + cust + '</td>'
            + '<td>' + plat + '</td>'
            + '<td>' + poster + '</td>'
            + '<td style="white-space:nowrap;">' + date + '</td>'
            + '<td>' + (url ? '<a href="' + url + '">View</a>' : '—') + '</td>'
            + '</tr>';
    });

    html += '</tbody></table></div></body></html>';

    var win = window.open('', '_blank');
    if (win) { win.document.write(html); win.document.close(); }
    else { alert('Pop-up blocked — please allow pop-ups for this site and try again.'); }
}

</script>
@endpush

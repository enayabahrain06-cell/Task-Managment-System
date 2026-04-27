@extends('layouts.app')
@section('title', 'Reports & Analytics')

@section('content')

{{-- ══ Print CSS ══ --}}
<style>
/* ── Screen layout helpers ── */
.rpt-grid-4  { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:10px; }
.rpt-grid-2  { display:grid; grid-template-columns:1fr 1fr;       gap:10px; margin-bottom:10px; }
.rpt-grid-3  { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:10px; }
@media(max-width:1100px){ .rpt-grid-4 { grid-template-columns:repeat(2,1fr); } }
@media(max-width:900px) { .rpt-grid-2,.rpt-grid-3 { grid-template-columns:1fr; } }
@media(max-width:600px) { .rpt-grid-4 { grid-template-columns:1fr; } }

.rpt-card {
    background:#fff; border-radius:12px;
    border:1px solid #E5E7EB;
    box-shadow:0 1px 3px rgba(0,0,0,.04);
    padding:14px;
}
.rpt-section-title {
    font-size:12px; font-weight:700; color:#374151;
    text-transform:uppercase; letter-spacing:.06em;
    margin:0 0 10px; display:flex; align-items:center; gap:7px;
}
.rpt-scroll-wrap { overflow-y:auto; max-height:190px; }
.rpt-table { width:100%; border-collapse:collapse; font-size:13px; }
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
.chip-high   { background:#FEE2E2;color:#DC2626; }

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

    /* ── Cards & grids preserve layout ── */
    .rpt-card {
        border:1px solid #D1D5DB !important;
        box-shadow:none !important;
        break-inside:avoid;
        -webkit-column-break-inside:avoid;
        page-break-inside:avoid;
    }
    .rpt-grid-4 { grid-template-columns:repeat(4,1fr) !important; }
    .rpt-grid-2 { grid-template-columns:1fr 1fr !important; }
    .rpt-grid-3 { grid-template-columns:repeat(3,1fr) !important; }

    /* ── Progress bars, badges & gradients print in color ── */
    .rpt-bar-fill  { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rpt-badge     { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    * { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }

    @page { size: A4 portrait; margin: 0; }

    /* Allow natural multi-page flow; only avoid breaks inside cards */
    .rpt-card { page-break-inside: avoid; break-inside: avoid; }
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
            <div style="font-size:12px;font-weight:600;color:#374151;margin-top:2px;">{{ now()->format('F d, Y') }} at {{ now()->format('H:i') }}</div>
        </div>
        <div>
            <span style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;">Period</span>
            <div style="font-size:12px;font-weight:600;color:#374151;margin-top:2px;">
                {{ $from ? $from->format('M d, Y').' – '.now()->format('M d, Y') : 'All Time' }}
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
<div id="rpt-filter-bar" class="no-print" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
    <div>
        <h1 style="font-size:18px;font-weight:700;color:#111827;margin:0;">Reports & Analytics</h1>
        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">
            {{ $from ? 'From '.$from->format('M d, Y').' to '.now()->format('M d, Y') : 'All time data' }}
        </p>
    </div>
    <div id="rpt-actions-bar" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

        {{-- Range selector --}}
        <form method="GET" action="{{ route('admin.reports.index') }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            {{-- Hidden range input — set by buttons below, preserved when project filter changes --}}
            <input type="hidden" id="rpt-range-input" name="range" value="{{ $range }}">

            <div style="display:flex;align-items:center;gap:2px;background:#F3F4F6;border-radius:9px;padding:3px;">
                @foreach(['7'=>'7D','30'=>'30D','90'=>'90D','365'=>'1Y','all'=>'All'] as $val=>$label)
                <button type="button"
                        onclick="document.getElementById('rpt-range-input').value='{{ $val }}';this.closest('form').submit();"
                        style="padding:5px 13px;font-size:12px;font-weight:600;border:none;border-radius:7px;cursor:pointer;transition:all .15s;{{ $range===$val ? 'background:#fff;color:#4F46E5;box-shadow:0 1px 3px rgba(0,0,0,.1);' : 'background:none;color:#6B7280;' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>

            {{-- Project filter --}}
            <select name="project_id" onchange="this.form.submit()"
                    style="font-size:12px;border:1px solid #E5E7EB;border-radius:8px;padding:7px 28px 7px 10px;background:#fff;color:#374151;outline:none;-webkit-appearance:none;appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 10px center;">
                <option value="">All Projects</option>
                @foreach($allProjects as $p)
                <option value="{{ $p->id }}" {{ $projectId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </form>

        {{-- Export dropdown --}}
        <div x-data="{ exportOpen: false }" style="position:relative;" @click.outside="exportOpen=false">
            <button @click="exportOpen=!exportOpen"
                    style="display:flex;align-items:center;gap:7px;padding:7px 14px;background:#4F46E5;color:#fff;font-size:12px;font-weight:600;border:none;border-radius:8px;cursor:pointer;transition:background .15s;white-space:nowrap;"
                    onmouseover="this.style.background='#4338CA'" onmouseout="this.style.background='#4F46E5'">
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

{{-- ══ User Performance Export Modal ══ --}}
<div id="user-export-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;width:90%;max-width:520px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;">

        {{-- Header --}}
        <div style="padding:16px 20px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <p style="font-size:15px;font-weight:700;color:#111827;margin:0;">Export User Performance</p>
                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Export a summary PDF or a detailed CSV for each selected user</p>
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
                    Period: <strong style="color:#374151;">{{ $from ? $from->format('M d, Y').' – '.now()->format('M d, Y') : 'All Time' }}</strong>
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

{{-- ══ KPI Summary Row ══ --}}
<div class="rpt-grid-4">
    @php
    $kpis = [
        ['label'=>'Total Tasks',      'value'=>$totalTasks,        'icon'=>'fa-list-check',          'color'=>'#6366F1','bg'=>'#EEF2FF', 'sub'=>'In selected period'],
        ['label'=>'Completed',        'value'=>$completedTasks,    'icon'=>'fa-circle-check',        'color'=>'#10B981','bg'=>'#D1FAE5', 'sub'=>'Approved + Delivered'],
        ['label'=>'Completion Rate',  'value'=>$completionRate.'%','icon'=>'fa-chart-pie',            'color'=>'#F59E0B','bg'=>'#FEF3C7', 'sub'=>'Of all tasks done'],
        ['label'=>'On-time Rate',     'value'=>$onTimeRate.'%',    'icon'=>'fa-clock',               'color'=>'#8B5CF6','bg'=>'#EDE9FE', 'sub'=>'Before deadline'],
        ['label'=>'Overdue',          'value'=>$overdueTasks,      'icon'=>'fa-triangle-exclamation','color'=>'#EF4444','bg'=>'#FEE2E2', 'sub'=>'Need attention'],
        ['label'=>'Active Projects',  'value'=>$activeProjects,    'icon'=>'fa-diagram-project',     'color'=>'#3B82F6','bg'=>'#DBEAFE', 'sub'=>'Currently running'],
        ['label'=>'Pending Review',   'value'=>$pendingReview,     'icon'=>'fa-gavel',               'color'=>'#7C3AED','bg'=>'#EDE9FE', 'sub'=>'Awaiting approval'],
        ['label'=>'Team Members',     'value'=>$teamMemberCount,   'icon'=>'fa-users',               'color'=>'#059669','bg'=>'#ECFDF5', 'sub'=>'Active contributors'],
    ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="rpt-card" style="padding:10px 12px;">
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

{{-- ══ Row 2: Status + Priority + Trend (3-col) ══ --}}
<div class="rpt-grid-3">

    {{-- Status Breakdown --}}
    <div class="rpt-card">
        <p class="rpt-section-title">
            <span style="width:22px;height:22px;border-radius:6px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-bars-progress" style="color:#6366F1;font-size:10px;"></i>
            </span>
            Status Breakdown
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
            Priority Distribution
        </p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px;">
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

{{-- ══ Row 4+5: Project Performance + Team Productivity (2-col) ══ --}}
<div class="rpt-grid-2">

    {{-- Project Performance --}}
    <div class="rpt-card">
        <p class="rpt-section-title">
            <span style="width:22px;height:22px;border-radius:6px;background:#D1FAE5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-diagram-project" style="color:#10B981;font-size:10px;"></i>
            </span>
            Project Performance
        </p>
        @if($projects->isEmpty())
        <p style="text-align:center;color:#9CA3AF;font-size:12px;padding:20px 0;">No project data available.</p>
        @else
        <div class="rpt-scroll-wrap" style="overflow-x:auto;">
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
                        $statusColor = match($proj['status']) {
                            'active'    => ['#DCFCE7','#16A34A'],
                            'completed' => ['#DBEAFE','#1D4ED8'],
                            default     => ['#F3F4F6','#6B7280'],
                        };
                    @endphp
                    <tr>
                        <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <span style="font-weight:600;color:#111827;">{{ $proj['name'] }}</span>
                        </td>
                        <td style="text-align:center;">
                            <span class="rpt-badge" style="background:{{ $statusColor[0] }};color:{{ $statusColor[1] }};font-size:10px;">
                                {{ ucfirst($proj['status']) }}
                            </span>
                        </td>
                        <td style="text-align:center;font-weight:600;">{{ $proj['total'] }}</td>
                        <td style="text-align:center;"><span style="color:#10B981;font-weight:700;">{{ $proj['completed'] }}</span></td>
                        <td style="text-align:center;">
                            <span style="color:{{ $proj['overdue'] > 0 ? '#EF4444' : '#9CA3AF' }};font-weight:700;">{{ $proj['overdue'] }}</span>
                        </td>
                        <td style="min-width:100px;">
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

    {{-- Team Productivity --}}
    <div class="rpt-card">
        <p class="rpt-section-title">
            <span style="width:22px;height:22px;border-radius:6px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-users" style="color:#7C3AED;font-size:10px;"></i>
            </span>
            Team Productivity
        </p>
        @if($teamMembers->isEmpty())
        <p style="text-align:center;color:#9CA3AF;font-size:12px;padding:20px 0;">No team data for this period.</p>
        @else
        <div style="font-size:10px;color:#9CA3AF;margin-bottom:8px;">
            <i class="fas fa-circle-info" style="margin-right:3px;"></i>
            Admin/Manager: counted by tasks <strong>created</strong> &amp; tasks <strong>approved</strong>. &nbsp;Users: counted by assigned tasks.
        </div>
        <div class="rpt-scroll-wrap" style="overflow-x:auto;">
            <table class="rpt-table" id="team-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th style="text-align:center;">Created</th>
                        <th style="text-align:center;">Done</th>
                        <th style="text-align:center;">Active</th>
                        <th style="text-align:center;">OD</th>
                        <th style="text-align:center;">Projects</th>
                        <th style="min-width:90px;">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teamMembers->sortByDesc('completed') as $member)
                    @php $isAdmin = ($member['member_type'] ?? 'user') === 'admin'; @endphp
                    <tr style="{{ $isAdmin ? 'background:#F5F3FF;' : '' }}">
                        <td>
                            <p style="font-weight:600;color:#111827;margin:0;font-size:12px;">{{ $member['name'] }}</p>
                            <span style="font-size:10px;color:{{ $isAdmin ? '#7C3AED' : '#9CA3AF' }};">
                                {{ $member['role'] }}
                                @if($isAdmin)
                                <span style="background:#EDE9FE;color:#7C3AED;border-radius:4px;padding:0 4px;font-size:9px;margin-left:3px;">{{ strtolower($member['role']) === 'admin' ? 'Admin' : 'Manager' }}</span>
                                @endif
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <span style="color:{{ $isAdmin ? '#7C3AED' : '#6B7280' }};font-weight:700;">
                                {{ $isAdmin ? $member['total'] : $member['total'] }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <span style="color:#10B981;font-weight:700;" title="{{ $isAdmin ? 'Tasks Approved' : 'Tasks Completed' }}">{{ $member['completed'] }}</span>
                        </td>
                        <td style="text-align:center;"><span style="color:#F59E0B;font-weight:700;">{{ $member['in_progress'] }}</span></td>
                        <td style="text-align:center;">
                            <span style="color:{{ $member['overdue'] > 0 ? '#EF4444' : '#9CA3AF' }};font-weight:700;">{{ $member['overdue'] }}</span>
                        </td>
                        <td style="text-align:center;">
                            @if($isAdmin && $member['projects_created'] > 0)
                            <span style="color:#4F46E5;font-weight:700;">{{ $member['projects_created'] }}</span>
                            @else
                            <span style="color:#D1D5DB;">—</span>
                            @endif
                        </td>
                        <td>
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

</div>

{{-- ══ Row 6: Overdue Tasks ══ --}}
@if($overdueList->isNotEmpty())
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
        <p class="rpt-section-title" style="margin:0;">
            <span style="width:22px;height:22px;border-radius:6px;background:#FEE2E2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-triangle-exclamation" style="color:#EF4444;font-size:10px;"></i>
            </span>
            Overdue Tasks ({{ $overdueList->count() }})
        </p>
        <span style="font-size:11px;color:#EF4444;background:#FEE2E2;padding:2px 9px;border-radius:20px;font-weight:600;">Needs Attention</span>
    </div>
    <div class="rpt-scroll-wrap" style="overflow-x:auto;">
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
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <span style="font-weight:600;color:#111827;">{{ $task['title'] }}</span>
                    </td>
                    <td style="color:#6B7280;font-size:12px;">{{ $task['project'] }}</td>
                    <td style="color:#6B7280;font-size:12px;">{{ $task['assignee'] }}</td>
                    <td style="color:#EF4444;font-weight:600;font-size:12px;">{{ $task['deadline'] }}</td>
                    <td style="text-align:center;">
                        <span style="background:#FEE2E2;color:#DC2626;padding:1px 7px;border-radius:20px;font-size:11px;font-weight:700;">+{{ $task['days_late'] }}d</span>
                    </td>
                    <td style="text-align:center;">
                        <span class="rpt-badge chip-{{ $task['priority'] }}" style="font-size:10px;">{{ ucfirst($task['priority']) }}</span>
                    </td>
                    <td style="text-align:center;">
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

{{-- ══ Row 7: Reassigned Tasks ══ --}}
@if($reassignedList->isNotEmpty())
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
        <p class="rpt-section-title" style="margin:0;">
            <span style="width:22px;height:22px;border-radius:6px;background:#F0FDF4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-arrows-rotate" style="color:#16A34A;font-size:10px;"></i>
            </span>
            Reassigned Tasks ({{ $reassignedList->count() }})
        </p>
        <span style="font-size:11px;color:#16A34A;background:#F0FDF4;padding:2px 9px;border-radius:20px;font-weight:600;">Assignment History</span>
    </div>
    <div class="rpt-scroll-wrap" style="overflow-x:auto;">
        <table class="rpt-table" id="reassigned-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Project</th>
                    <th style="text-align:center;">From</th>
                    <th style="text-align:center;">To</th>
                    <th style="text-align:center;">By</th>
                    <th>Reason</th>
                    <th style="text-align:center;">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reassignedList as $row)
                <tr>
                    <td style="font-weight:600;color:#111827;font-size:12px;">{{ Str::limit($row['task'], 40) }}</td>
                    <td style="font-size:12px;color:#6B7280;">{{ $row['project'] }}</td>
                    <td style="text-align:center;">
                        <span style="font-size:11px;background:#FEE2E2;color:#DC2626;padding:2px 8px;border-radius:6px;font-weight:600;white-space:nowrap;">{{ $row['from_user'] }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-size:11px;background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:6px;font-weight:600;white-space:nowrap;">{{ $row['to_user'] }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-size:11px;color:#6B7280;white-space:nowrap;">{{ $row['by'] }}</span>
                    </td>
                    <td style="font-size:11px;color:#374151;max-width:200px;">
                        @if($row['reason'])
                            <span style="font-style:italic;">{{ Str::limit($row['reason'], 80) }}</span>
                        @else
                            <span style="color:#D1D5DB;">—</span>
                        @endif
                    </td>
                    <td style="text-align:center;white-space:nowrap;">
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

{{-- ══ Row 8: Reopened Tasks ══ --}}
@if($reopenedList->isNotEmpty())
<div class="rpt-card" style="margin-bottom:10px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
        <p class="rpt-section-title" style="margin:0;">
            <span style="width:22px;height:22px;border-radius:6px;background:#FFF7ED;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-rotate-right" style="color:#EA580C;font-size:10px;"></i>
            </span>
            Reopened Tasks ({{ $reopenedList->count() }})
        </p>
        <span style="font-size:11px;color:#EA580C;background:#FFF7ED;padding:2px 9px;border-radius:20px;font-weight:600;">Needs Attention</span>
    </div>
    <div class="rpt-scroll-wrap" style="overflow-x:auto;">
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
                    <td style="font-weight:600;color:#111827;font-size:12px;">{{ Str::limit($row['task'], 40) }}</td>
                    <td style="font-size:12px;color:#6B7280;">{{ $row['project'] }}</td>
                    <td style="text-align:center;">
                        <span style="font-size:11px;background:#F3F4F6;color:#374151;padding:2px 8px;border-radius:6px;font-weight:600;white-space:nowrap;">{{ $row['old_status'] }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-size:11px;color:#EA580C;font-weight:600;white-space:nowrap;">{{ $row['by'] }}</span>
                    </td>
                    <td style="text-align:center;white-space:nowrap;">
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

{{-- ══ Footer ══ --}}
<div style="text-align:center;padding:8px 0;color:#9CA3AF;font-size:10px;" class="no-print">
    &copy; {{ now()->year }} {{ $appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name') }}. All rights reserved.
</div>

</div>{{-- #rpt-main-content --}}
</div>{{-- #rpt-capture-zone --}}

@endsection

@push('scripts')
{{-- ── Export libraries ── --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

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

/* ══════════════════════════════════════════════════════════
   Shared helpers: show/hide UI for export capture
══════════════════════════════════════════════════════════ */
function prepareCapture() {
    document.getElementById('rpt-print-header').style.display = 'block';
    document.getElementById('rpt-filter-bar').style.display   = 'none';
    // Force grid columns regardless of viewport width (overrides responsive breakpoints)
    const s = document.createElement('style');
    s.id = '__rpt-grid-override';
    s.textContent = '.rpt-grid-4{grid-template-columns:repeat(4,1fr)!important;}' +
                    '.rpt-grid-2{grid-template-columns:1fr 1fr!important;}' +
                    '.rpt-grid-3{grid-template-columns:repeat(3,1fr)!important;}';
    document.head.appendChild(s);
}
function restoreCapture() {
    document.getElementById('rpt-print-header').style.display = 'none';
    document.getElementById('rpt-filter-bar').style.display   = '';
    document.getElementById('__rpt-grid-override')?.remove();
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
    const dateStr     = '{{ now()->format('F d, Y') }}';

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
            ['Period',    '{{ $from ? $from->format('M d, Y').' – '.now()->format('M d, Y') : 'All Time' }}'],
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

    const pdfBtn = document.getElementById('user-pdf-btn');
    pdfBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:11px;"></i><span> Generating…</span>';
    pdfBtn.disabled = true;

    const users   = teamMembersData.filter(m => selectedIds.includes(m.id));
    const period  = '{{ $from ? $from->format('M d, Y').' – '.now()->format('M d, Y') : 'All Time' }}';
    const company = '{{ addslashes($appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name')) }}';
    const dateStr = '{{ now()->format('F d, Y') }}';

    /* ── Build styled HTML ── */
    let rows = users.map(u => {
        const isAdm = u.member_type === 'admin';
        const rowBg = isAdm ? '#F5F3FF' : '#fff';
        const projCell = isAdm
            ? `<td style="padding:9px 10px;border-bottom:1px solid #F3F4F6;text-align:center;color:#4F46E5;font-weight:700;">${u.projects_created}</td>`
            : `<td style="padding:9px 10px;border-bottom:1px solid #F3F4F6;text-align:center;color:#D1D5DB;">—</td>`;
        return `
        <tr style="background:${rowBg}">
            <td style="padding:9px 10px;border-bottom:1px solid #F3F4F6;font-weight:600;color:#111827;">${u.name}</td>
            <td style="padding:9px 10px;border-bottom:1px solid #F3F4F6;color:${isAdm ? '#7C3AED' : '#6B7280'};">${u.role}</td>
            <td style="padding:9px 10px;border-bottom:1px solid #F3F4F6;text-align:center;font-weight:700;color:${isAdm ? '#7C3AED' : '#374151'};" title="${isAdm ? 'Tasks Created' : 'Total Assigned'}">${u.total}</td>
            <td style="padding:9px 10px;border-bottom:1px solid #F3F4F6;text-align:center;color:#10B981;font-weight:700;" title="${isAdm ? 'Tasks Approved' : 'Tasks Completed'}">${u.completed}</td>
            <td style="padding:9px 10px;border-bottom:1px solid #F3F4F6;text-align:center;color:#F59E0B;font-weight:700;">${u.in_progress}</td>
            <td style="padding:9px 10px;border-bottom:1px solid #F3F4F6;text-align:center;color:#8B5CF6;font-weight:700;">${u.in_review}</td>
            <td style="padding:9px 10px;border-bottom:1px solid #F3F4F6;text-align:center;color:${u.overdue > 0 ? '#EF4444' : '#9CA3AF'};font-weight:700;">${u.overdue}</td>
            ${projCell}
            <td style="padding:9px 10px;border-bottom:1px solid #F3F4F6;text-align:center;">
                <span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700;
                    background:${u.rate >= 80 ? '#D1FAE5' : u.rate >= 40 ? '#FEF3C7' : '#FEE2E2'};
                    color:${u.rate >= 80 ? '#065F46' : u.rate >= 40 ? '#92400E' : '#991B1B'};">
                    ${u.rate}%
                </span>
            </td>
        </tr>`;
    }).join('');

    const area = document.getElementById('user-perf-pdf-area');
    area.innerHTML = `
        <div style="margin-bottom:22px;">
            <div style="height:4px;background:linear-gradient(90deg,#4F46E5,#818CF8);border-radius:3px;margin-bottom:18px;"></div>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <div style="font-size:20px;font-weight:800;color:#111827;line-height:1.2;">${company}</div>
                    <div style="font-size:13px;font-weight:700;color:#4F46E5;margin-top:4px;">User Performance Report</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:11px;color:#9CA3AF;">Generated ${dateStr}</div>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:2px;">Period: <strong style="color:#374151;">${period}</strong></div>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:2px;">Prepared by: <strong style="color:#374151;">{{ auth()->user()->name }}</strong></div>
                </div>
            </div>
            <div style="border-top:1.5px solid #E5E7EB;margin-top:14px;"></div>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;font-family:Inter,system-ui,sans-serif;">
            <thead>
                <tr style="background:#F9FAFB;">
                    <th style="padding:9px 10px;text-align:left;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #E5E7EB;">Member</th>
                    <th style="padding:9px 10px;text-align:left;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #E5E7EB;">Role</th>
                    <th style="padding:9px 10px;text-align:center;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #E5E7EB;">Created/Total</th>
                    <th style="padding:9px 10px;text-align:center;font-size:10px;font-weight:700;color:#10B981;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #E5E7EB;">Done/Approved</th>
                    <th style="padding:9px 10px;text-align:center;font-size:10px;font-weight:700;color:#F59E0B;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #E5E7EB;">Active</th>
                    <th style="padding:9px 10px;text-align:center;font-size:10px;font-weight:700;color:#8B5CF6;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #E5E7EB;">Review</th>
                    <th style="padding:9px 10px;text-align:center;font-size:10px;font-weight:700;color:#EF4444;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #E5E7EB;">Overdue</th>
                    <th style="padding:9px 10px;text-align:center;font-size:10px;font-weight:700;color:#4F46E5;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #E5E7EB;">Projects</th>
                    <th style="padding:9px 10px;text-align:center;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #E5E7EB;">Rate</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
        <div style="margin-top:24px;padding-top:10px;border-top:1px solid #E5E7EB;display:flex;justify-content:space-between;">
            <span style="font-size:10px;color:#9CA3AF;">${company} — Confidential, Internal Use Only</span>
            <span style="font-size:10px;color:#9CA3AF;">${users.length} user${users.length !== 1 ? 's' : ''} included</span>
        </div>`;

    await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
    await new Promise(r => setTimeout(r, 150));

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
        pdfBtn.innerHTML = '<i class="fas fa-file-pdf" style="font-size:11px;"></i><span>Export PDF</span>';
        pdfBtn.disabled = false;
        return;
    }

    area.innerHTML = '';

    const { jsPDF } = window.jspdf;
    const pdf   = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const pageW = pdf.internal.pageSize.getWidth();
    const pageH = pdf.internal.pageSize.getHeight();
    const footH = 6;
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
        pdf.text(company + '  —  User Performance', 2, footerY + 4);
        pdf.setFont('helvetica', 'normal'); pdf.setTextColor(156, 163, 175);
        pdf.text('Generated ' + dateStr, pageW / 2, footerY + 4, { align: 'center' });
        pdf.text('Page ' + (p + 1) + (numPages > 1 ? ' of ' + numPages : ''), pageW - 2, footerY + 4, { align: 'right' });
    }

    pdf.save('user-performance-{{ now()->format('Y-m-d') }}.pdf');

    pdfBtn.innerHTML = '<i class="fas fa-file-pdf" style="font-size:11px;"></i><span>Export PDF (' + selectedIds.length + ')</span>';
    pdfBtn.disabled = false;
}
</script>
@endpush

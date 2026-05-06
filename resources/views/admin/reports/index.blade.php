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
<div id="rpt-filter-bar" class="no-print" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
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
    <div id="rpt-actions-bar" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

        {{-- Range selector --}}
        <form method="GET" action="{{ route('admin.reports.index') }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            {{-- Hidden range input — set by buttons below, preserved when other filters change --}}
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

            {{-- Customer filter --}}
            @if($allCustomers->isNotEmpty())
            <select name="customer_id" onchange="this.form.submit()"
                    style="font-size:12px;border:1px solid #E5E7EB;border-radius:8px;padding:7px 28px 7px 10px;background:#fff;color:#374151;outline:none;-webkit-appearance:none;appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 10px center;">
                <option value="">All Customers</option>
                @foreach($allCustomers as $c)
                <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @endif

            {{-- User filter --}}
            <select name="user_id" onchange="this.form.submit()"
                    style="font-size:12px;border:1px solid {{ $userId ? '#A5B4FC' : '#E5E7EB' }};border-radius:8px;padding:7px 28px 7px 10px;background:{{ $userId ? '#EEF2FF' : '#fff' }};color:{{ $userId ? '#4F46E5' : '#374151' }};font-weight:{{ $userId ? '600' : 'normal' }};outline:none;-webkit-appearance:none;appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 10px center;">
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

{{-- ══ KPI Summary Row ══ --}}
<div class="rpt-grid-4">
    @php
    $empName = $selectedUser ? $selectedUser->name : null;
    $kpis = [
        ['label' => $empName ? 'Assigned Tasks'   : 'Total Tasks',
         'value' => $totalTasks,        'icon'=>'fa-list-check',          'color'=>'#6366F1','bg'=>'#EEF2FF',
         'sub'   => $empName ? 'Tasks assigned to '.$empName : 'In selected period'],
        ['label'=>'Completed',        'value'=>$completedTasks,    'icon'=>'fa-circle-check',        'color'=>'#10B981','bg'=>'#D1FAE5', 'sub'=>'Approved + Delivered'],
        ['label'=>'Completion Rate',  'value'=>$completionRate.'%','icon'=>'fa-chart-pie',            'color'=>'#F59E0B','bg'=>'#FEF3C7', 'sub'=>'Of all tasks done'],
        ['label'=>'On-time Rate',     'value'=>$onTimeRate.'%',    'icon'=>'fa-clock',               'color'=>'#8B5CF6','bg'=>'#EDE9FE', 'sub'=>'Before deadline'],
        ['label'=>'Overdue',          'value'=>$overdueTasks,      'icon'=>'fa-triangle-exclamation','color'=>'#EF4444','bg'=>'#FEE2E2', 'sub'=>'Need attention'],
        ...($empName ? [] : [
            ['label'=>'Active Projects','value'=>$activeProjects,  'icon'=>'fa-diagram-project',     'color'=>'#3B82F6','bg'=>'#DBEAFE', 'sub'=>'Currently running'],
        ]),
        ['label' => $empName ? 'Submitted Tasks'  : 'Pending Review',
         'value' => $pendingReview,     'icon'=>'fa-gavel',               'color'=>'#7C3AED','bg'=>'#EDE9FE',
         'sub'   => $empName ? 'Awaiting admin approval' : 'Awaiting approval'],
        ...($empName ? [] : [
            ['label'=>'Team Members',  'value'=>$teamMemberCount,  'icon'=>'fa-users',               'color'=>'#059669','bg'=>'#ECFDF5', 'sub'=>'Active contributors'],
        ]),
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
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <p class="rpt-section-title" style="margin:0;">
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
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px;">
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
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;padding:10px 12px;background:#F9FAFB;border-radius:10px;border:1px solid #F0F0F0;">
            <span style="font-size:11px;font-weight:600;color:#374151;white-space:nowrap;">12-Month Rate</span>
            <div style="flex:1;height:7px;background:#E5E7EB;border-radius:99px;overflow:hidden;">
                <div style="height:7px;width:{{ $completionRate12 }}%;background:{{ $completionRate12 >= 80 ? 'linear-gradient(90deg,#059669,#10B981)' : ($completionRate12 >= 50 ? 'linear-gradient(90deg,#D97706,#F59E0B)' : 'linear-gradient(90deg,#DC2626,#EF4444)') }};border-radius:99px;transition:width .6s;"></div>
            </div>
            <span style="font-size:12px;font-weight:700;color:{{ $completionRate12 >= 80 ? '#059669' : ($completionRate12 >= 50 ? '#D97706' : '#DC2626') }};min-width:34px;text-align:right;">{{ $completionRate12 }}%</span>
            <span style="font-size:10px;color:#9CA3AF;">avg {{ $avgCompletion }}/mo</span>
        </div>

        {{-- Chart --}}
        <div style="flex:1;position:relative;min-height:180px;">
            <canvas id="projCompletionChart"></canvas>
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
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;" id="team-table">
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
                        <th style="padding:7px 10px;font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;min-width:110px;">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teamMembers->sortByDesc('completed') as $member)
                    @php $isAdmin = ($member['member_type'] ?? 'user') === 'admin'; @endphp
                    <tr style="{{ $isAdmin ? 'background:#F5F3FF;' : '' }}border-bottom:1px solid #F3F4F6;">
                        <td style="padding:8px 12px;">
                            <p style="font-weight:600;color:#111827;margin:0;font-size:12px;white-space:nowrap;">{{ $member['name'] }}</p>
                            <span style="font-size:10px;color:{{ $isAdmin ? '#7C3AED' : '#9CA3AF' }};">
                                {{ $member['role'] }}
                                @if($isAdmin)
                                <span style="background:#EDE9FE;color:#7C3AED;border-radius:4px;padding:0 4px;font-size:9px;margin-left:3px;">{{ strtolower($member['role']) === 'admin' ? 'Admin' : 'Manager' }}</span>
                                @endif
                            </span>
                        </td>
                        <td style="text-align:center;padding:8px 10px;">
                            <span style="color:{{ $isAdmin ? '#7C3AED' : '#6B7280' }};font-weight:700;">{{ $member['total'] }}</span>
                        </td>
                        <td style="text-align:center;padding:8px 10px;">
                            <span style="color:#10B981;font-weight:700;" title="{{ $isAdmin ? 'Tasks Approved' : 'Tasks Completed' }}">{{ $member['completed'] }}</span>
                        </td>
                        <td style="text-align:center;padding:8px 10px;"><span style="color:#F59E0B;font-weight:700;">{{ $member['in_progress'] }}</span></td>
                        <td style="text-align:center;padding:8px 10px;">
                            <span style="color:{{ $member['overdue'] > 0 ? '#EF4444' : '#9CA3AF' }};font-weight:700;">{{ $member['overdue'] }}</span>
                        </td>
                        <td style="text-align:center;padding:8px 10px;">
                            @if($isAdmin && $member['projects_created'] > 0)
                            <span style="color:#4F46E5;font-weight:700;">{{ $member['projects_created'] }}</span>
                            @else
                            <span style="color:#D1D5DB;">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;padding:8px 10px;">
                            @if($isAdmin)
                            <span style="color:{{ $member['tasks_reopened'] > 0 ? '#F59E0B' : '#9CA3AF' }};font-weight:700;">{{ $member['tasks_reopened'] }}</span>
                            @else
                            <span style="color:#D1D5DB;">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;padding:8px 10px;">
                            @if($isAdmin)
                            <span style="color:{{ $member['tasks_reassigned'] > 0 ? '#6366F1' : '#9CA3AF' }};font-weight:700;">{{ $member['tasks_reassigned'] }}</span>
                            @else
                            <span style="color:#D1D5DB;">—</span>
                            @endif
                        </td>
                        <td style="padding:8px 10px;min-width:110px;">
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
    <div style="overflow-x:auto;">
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
                    <td>
                        <a href="{{ route('admin.customers.show', $cust['id']) }}"
                           style="font-weight:600;color:#4F46E5;text-decoration:none;font-size:12px;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            {{ $cust['name'] }}
                        </a>
                        @if($cust['company'])
                        <p style="margin:1px 0 0;font-size:10px;color:#9CA3AF;">{{ $cust['company'] }}</p>
                        @endif
                    </td>
                    <td style="text-align:center;font-weight:600;color:#374151;">{{ $cust['projects'] }}</td>
                    <td style="text-align:center;font-weight:600;color:#374151;">{{ $cust['total'] }}</td>
                    <td style="text-align:center;"><span style="color:#10B981;font-weight:700;">{{ $cust['completed'] }}</span></td>
                    <td style="text-align:center;"><span style="color:#F59E0B;font-weight:700;">{{ $cust['active'] }}</span></td>
                    <td style="text-align:center;">
                        <span style="color:{{ $cust['overdue'] > 0 ? '#EF4444' : '#9CA3AF' }};font-weight:700;">{{ $cust['overdue'] }}</span>
                    </td>
                    <td style="min-width:110px;">
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
    <div class="rpt-scroll-wrap" style="overflow-x:auto;">
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
                    <td>
                        <a href="{{ route('admin.tasks.show', $row['task_id']) }}"
                           style="font-weight:600;color:#4F46E5;font-size:12px;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            {{ Str::limit($row['task'], 40) }}
                        </a>
                    </td>
                    <td style="font-size:12px;color:#6B7280;">{{ $row['project'] }}</td>
                    <td style="text-align:center;">
                        <span style="font-size:11px;background:#FEE2E2;color:#DC2626;padding:2px 8px;border-radius:6px;font-weight:600;white-space:nowrap;">
                            {{ $row['from_user'] }}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <i class="fas fa-arrow-right" style="color:#9CA3AF;font-size:9px;"></i>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-size:11px;background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:6px;font-weight:600;white-space:nowrap;">
                            {{ $row['to_user'] }}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-size:11px;font-weight:600;color:#4F46E5;white-space:nowrap;">{{ $row['by'] }}</span>
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

    <div style="overflow-x:auto;">
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
                <td><span style="font-weight:600;color:#111827;">{{ $bu['name'] }}</span></td>
                <td><span class="rpt-badge" style="background:#EEF2FF;color:#4F46E5;">{{ ucfirst($bu['role']) }}</span></td>
                @foreach(array_keys($phaseLabels) as $phaseKey)
                @php $secs = $bu['phases'][$phaseKey] ?? 0; $pc = $phaseColors[$phaseKey] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280']; @endphp
                <td>
                    @if($secs > 0)<span style="font-size:12px;font-weight:600;color:{{ $pc['color'] }};">{{ round($secs/3600,1) }}h</span>
                    @else<span style="color:#E5E7EB;">—</span>@endif
                </td>
                @endforeach
                <td><strong>{{ $bu['hours'] }}h</strong></td>
                @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1')
                <td>
                    @if($hasRate)<span style="color:#059669;font-weight:600;">${{ number_format($bu['hourly_rate'],2) }}/hr</span>
                    @else<span style="color:#9CA3AF;font-size:11px;">Not set</span>@endif
                </td>
                <td style="text-align:right;">
                    @if($bu['estimated_pay'])<span style="font-weight:700;color:#111827;">${{ number_format($bu['estimated_pay'],2) }}</span>
                    @else<span style="color:#D1D5DB;">—</span>@endif
                </td>
                @endif
            </tr>
            @endforeach
            <tr style="background:#F9FAFB;">
                <td colspan="{{ 2 + count($phaseLabels) }}"><strong style="color:#374151;">Total</strong></td>
                <td><strong>{{ round($billingUsers->sum('total_seconds') / 3600, 1) }}h</strong></td>
                @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1')
                <td></td>
                <td style="text-align:right;">
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

    <div style="overflow-x:auto;">
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
                <td>
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
                <td>
                    @if($secs > 0)<span style="font-size:12px;font-weight:600;color:{{ $pc['color'] }};">{{ round($secs/3600,1) }}h</span>
                    @else<span style="color:#E5E7EB;">—</span>@endif
                </td>
                @endforeach
                <td><strong>{{ $bc['hours'] }}h</strong></td>
                @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1')
                <td style="text-align:right;">
                    @if($bc['estimated_cost'])<span style="font-weight:700;color:#111827;">${{ number_format($bc['estimated_cost'],2) }}</span>
                    @else<span style="color:#D1D5DB;">—</span>@endif
                </td>
                @endif
            </tr>
            @endforeach
            <tr style="background:#F9FAFB;">
                <td colspan="{{ 1 + count($phaseLabels) }}"><strong style="color:#374151;">Total</strong></td>
                <td><strong>{{ round($billingCustomers->sum('total_seconds') / 3600, 1) }}h</strong></td>
                @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1')
                <td style="text-align:right;">
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
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#FEE2E2;color:#DC2626;">
                    <i class="fas fa-clock" style="font-size:10px;"></i> {{ $budgetPending }} pending
                </span>
            </div>
            <a href="{{ route('admin.social-budget.index') }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:#FEF3C7;color:#D97706;border:1.5px solid #FDE68A;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;"
               onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i> Full page
            </a>
        </div>
    </div>

    <div style="overflow-x:auto;">
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
                <td>
                    <a href="{{ route('admin.tasks.show', $at['id']) }}"
                       style="font-weight:600;color:#111827;text-decoration:none;"
                       onmouseover="this.style.color='#4F46E5'" onmouseout="this.style.color='#111827'">
                        {{ $at['title'] }}
                    </a>
                </td>
                <td style="color:#374151;">{{ $at['project'] }}</td>
                <td style="color:#374151;">{{ $at['customer'] }}</td>
                <td>
                    @if($at['social_user'] !== '—')
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:8px;background:#E0F2FE;color:#0284C7;font-size:11px;font-weight:600;">
                            <i class="fas fa-share-nodes" style="font-size:9px;"></i>
                            {{ $at['social_user'] }}
                        </span>
                    @else
                        <span style="color:#D1D5DB;">—</span>
                    @endif
                </td>
                <td>
                    @if(!empty($at['budget']))
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:8px;background:#FEF3C7;color:#D97706;font-size:12px;font-weight:700;">
                            <i class="fas fa-wallet" style="font-size:10px;"></i>
                            {{ $at['budget'] }}
                        </span>
                    @else
                        <span style="color:#D1D5DB;font-size:11px;">—</span>
                    @endif
                </td>
                <td style="max-width:200px;">
                    @if(!empty($at['caption']))
                        <span style="display:block;font-size:11px;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;"
                              title="{{ $at['caption'] }}">{{ $at['caption'] }}</span>
                    @else
                        <span style="color:#D1D5DB;">—</span>
                    @endif
                </td>
                <td>
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
                <td style="color:#6B7280;font-size:11px;">{{ $at['posted_at'] ?? '—' }}</td>
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
        const map = { in_progress:'#F59E0B', submitted:'#8B5CF6', revision_requested:'#8B5CF6',
                      approved:'#10B981', delivered:'#047857', archived:'#047857',
                      assigned:'#6B7280', viewed:'#6B7280', draft:'#6B7280' };
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
</script>
@endpush

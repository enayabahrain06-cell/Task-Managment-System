@extends('layouts.app')

@section('title', 'Customer Report — ' . $customer->name)

@push('styles')
<style>
.report-card { background:#fff; border-radius:14px; box-shadow:0 1px 4px rgba(0,0,0,.07); padding:24px; }
.kpi-value { font-size:2rem; font-weight:700; line-height:1; }
.bar-track { background:#f3f4f6; border-radius:999px; height:8px; overflow:hidden; }
.bar-fill  { height:8px; border-radius:999px; transition:width .6s ease; }
.insight-pill { display:flex; align-items:flex-start; gap:12px; padding:12px 16px; border-radius:10px; }
canvas { max-width:100% !important; }
.print-only { display:none; }
[x-cloak] { display:none !important; }

/* ── Sort controls ── */
.sort-th { cursor:pointer; user-select:none; white-space:nowrap; }
.sort-th:hover { color:#4f46e5 !important; }
.sort-icon { margin-left:4px; font-size:9px; opacity:.5; }
.sort-th.active .sort-icon { opacity:1; color:#4f46e5; }

/* ── Print ─────────────────────────────────────────────── */
@media print {
    @page { size: A4 portrait; margin: 12mm 14mm; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

    body { background: #fff !important; font-size:11px; }

    .no-print  { display: none !important; }
    .print-only { display: block !important; }
    .print-header-bar { display: flex !important; }

    /* reset outer layout */
    #app-layout-wrapper,
    .sidebar, nav, header, footer { display:none !important; }
    .main-content { margin:0 !important; padding:0 !important; }

    .report-card {
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 8px !important;
        break-inside: avoid;
        margin-bottom: 14px !important;
        padding: 14px !important;
    }

    /* Stack 2-col grid to 1-col for paper */
    [style*="grid-template-columns:1fr 1fr"] {
        display: block !important;
    }
    [style*="grid-template-columns:1fr 1fr"] > * {
        margin-bottom: 14px !important;
    }

    /* KPI rows: keep 3 cols, tighten gap */
    [style*="repeat(3,1fr)"] {
        gap: 10px !important;
    }

    .kpi-value { font-size: 1.4rem !important; }

    /* Charts: set fixed height for print */
    #activityChart { height:160px !important; }

    /* Table: no scroll, full width */
    .task-table-wrap { overflow: visible !important; }
    .task-table-scroll { overflow: visible !important; max-height: none !important; }
    table { page-break-inside: auto; font-size: 10px !important; }
    tr { page-break-inside: avoid; }
    thead { display: table-header-group; }

    /* Gradient backgrounds */
    [style*="linear-gradient"] { background: #f8f4ff !important; }

    .print-header-bar {
        background: #4f46e5 !important;
        color: #fff !important;
        border-radius: 8px !important;
        padding: 12px 16px !important;
        margin-bottom: 14px !important;
    }

    /* ── Break out of app-shell viewport constraints ── */
    .app-shell   { height: auto !important; overflow: visible !important; display: block !important; }
    .app-main    { overflow: visible !important; height: auto !important; display: block !important; }
    .app-content { overflow: visible !important; height: auto !important; flex: none !important; padding: 0 !important; animation: none !important; }
    .app-sidebar, .app-topbar { display: none !important; }

    /* Show the Alpine task-table card even if x-cloak hasn't resolved */
    [x-cloak] { display: block !important; }

    /* Suppress browser link-URL annotations */
    a::after, a[href]::after { content:none !important; display:none !important; }
}
</style>
@endpush

@section('content')
<div style="padding-bottom:40px;">

    {{-- ── Print-only header ────────────────────────────────────────────────── --}}
    <div class="print-only print-header-bar" style="align-items:center;justify-content:space-between;">
        <div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.07em;opacity:.8;">Customer Report</div>
            <div style="font-size:1.1rem;font-weight:700;">{{ $customer->name }}@if($customer->company) · {{ $customer->company }}@endif</div>
            @if($firstTaskAt)
            <div style="font-size:11px;opacity:.8;margin-top:2px;">{{ $firstTaskAt->format('M j, Y') }} — {{ $lastTaskAt->format('M j, Y') }} · {{ $workDays }} days</div>
            @endif
        </div>
        <div style="text-align:right;font-size:11px;opacity:.8;">
            Generated {{ now()->format('M j, Y') }}<br>
            {{ $total }} tasks · {{ $completionRate }}% completion
        </div>
    </div>

    {{-- ── Page header ──────────────────────────────────────────────────────── --}}
    <div class="page-header no-print" style="background:linear-gradient(135deg,#4f46e5,#6366f1);border-radius:16px;padding:20px 24px 20px;margin-bottom:24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <a href="{{ route('admin.customers.show', $customer) }}" style="color:#c7d2fe;display:flex;align-items:center;gap:6px;font-size:13px;text-decoration:none;flex-shrink:0;">
            <i class="fas fa-arrow-left"></i> Back to Customer
        </a>
        <div style="flex:1;min-width:0;">
            <div style="color:#c7d2fe;font-size:12px;text-transform:uppercase;letter-spacing:.07em;font-weight:600;">Customer Report</div>
            <div style="color:#fff;font-size:1.2rem;font-weight:700;margin-top:2px;">{{ $customer->name }}</div>
            @if($firstTaskAt)
            <div style="color:#a5b4fc;font-size:12px;margin-top:2px;">
                {{ $firstTaskAt->format('M j, Y') }} — {{ $lastTaskAt->format('M j, Y') }}
                <span style="margin-left:8px;background:rgba(255,255,255,.15);border-radius:20px;padding:2px 10px;">{{ $workDays }} calendar days</span>
            </div>
            @endif
        </div>
        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button onclick="printCustomerReport()" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:8px 14px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-print"></i> Print
            </button>
            <button onclick="exportCustomerReportPDF()" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:8px 14px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <span style="background:rgba(255,255,255,.1);color:#c7d2fe;border-radius:8px;padding:8px 14px;font-size:12px;">
                Generated {{ now()->format('M j, Y') }}
            </span>
        </div>
    </div>

    {{-- ── Customer Info Bar ──────────────────────────────────────────────────── --}}
    <div class="report-card no-print" style="margin-bottom:20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap;padding:16px 20px;">
        <div style="width:48px;height:48px;border-radius:12px;background:#6366f1;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:800;color:#fff;flex-shrink:0;">
            {{ strtoupper(substr($customer->name, 0, 1)) }}
        </div>
        <div style="flex:1;min-width:160px;">
            <div style="font-size:1rem;font-weight:700;color:#111827;">{{ $customer->name }}</div>
            @if($customer->company)<div style="font-size:12px;color:#6b7280;margin-top:1px;">{{ $customer->company }}</div>@endif
        </div>
        @if($customer->email)
        <div style="display:flex;align-items:center;gap:7px;color:#374151;font-size:13px;"><i class="fas fa-envelope" style="color:#9ca3af;font-size:11px;"></i>{{ $customer->email }}</div>
        @endif
        @if($customer->phone)
        <div style="display:flex;align-items:center;gap:7px;color:#374151;font-size:13px;"><i class="fas fa-phone" style="color:#9ca3af;font-size:11px;"></i>{{ $customer->phone }}</div>
        @endif
        <div style="width:1px;height:36px;background:#f3f4f6;flex-shrink:0;"></div>
        <div style="display:flex;gap:24px;">
            <div style="text-align:center;"><div style="font-size:1.15rem;font-weight:700;color:#111827;">{{ $total }}</div><div style="font-size:11px;color:#9ca3af;margin-top:1px;">Total Tasks</div></div>
            <div style="text-align:center;"><div style="font-size:1.15rem;font-weight:700;color:#059669;">{{ $completionRate }}%</div><div style="font-size:11px;color:#9ca3af;margin-top:1px;">Completion</div></div>
            <div style="text-align:center;"><div style="font-size:1.15rem;font-weight:700;color:#0284c7;">{{ $workDays }}</div><div style="font-size:11px;color:#9ca3af;margin-top:1px;">Days</div></div>
            @if($overdue > 0)
            <div style="text-align:center;"><div style="font-size:1.15rem;font-weight:700;color:#dc2626;">{{ $overdue }}</div><div style="font-size:11px;color:#9ca3af;margin-top:1px;">Overdue</div></div>
            @endif
        </div>
    </div>

    {{-- ── KPI Cards ─────────────────────────────────────────────────────────── --}}
    @php
        $avgDays = $avgCompletionHours > 0 ? round($avgCompletionHours / 24, 1) : 0;
        $avgCompletionLabel = $avgDays < 1 ? round($avgCompletionHours, 1).'h' : $avgDays.'d';
        $onTimePct = $completed > 0 ? round($deliveredOnTime / $completed * 100) : 0;
    @endphp

    {{-- All 6 KPIs in one row --}}
    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:20px;">

        {{-- Total Tasks --}}
        <div class="report-card" style="border-left:4px solid #6366f1;padding:20px 20px 18px;display:flex;flex-direction:column;gap:0;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
                <div style="color:#6b7280;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Total Tasks</div>
                <div style="width:32px;height:32px;border-radius:8px;background:#eef2ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-layer-group" style="color:#6366f1;font-size:13px;"></i>
                </div>
            </div>
            <div style="font-size:2.4rem;font-weight:800;color:#1f2937;line-height:1;">{{ $total }}</div>
            <div style="margin-top:10px;display:flex;align-items:center;gap:10px;">
                <span style="display:inline-flex;align-items:center;gap:4px;background:#eff6ff;color:#1d4ed8;border-radius:6px;padding:3px 8px;font-size:11px;font-weight:600;">
                    <span style="width:6px;height:6px;border-radius:50%;background:#3b82f6;display:inline-block;"></span>
                    {{ $active }} active
                </span>
                <span style="display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;color:#15803d;border-radius:6px;padding:3px 8px;font-size:11px;font-weight:600;">
                    <span style="width:6px;height:6px;border-radius:50%;background:#22c55e;display:inline-block;"></span>
                    {{ $completed }} done
                </span>
                @if($overdue > 0)
                <span style="display:inline-flex;align-items:center;gap:4px;background:#fff5f5;color:#dc2626;border-radius:6px;padding:3px 8px;font-size:11px;font-weight:600;">
                    ⚠ {{ $overdue }} late
                </span>
                @endif
            </div>
        </div>

        {{-- Completion Rate --}}
        <div class="report-card" style="border-left:4px solid #10b981;padding:20px 20px 18px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
                <div style="color:#6b7280;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Completion Rate</div>
                <div style="width:32px;height:32px;border-radius:8px;background:#ecfdf5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-circle-check" style="color:#10b981;font-size:13px;"></i>
                </div>
            </div>
            <div style="font-size:2.4rem;font-weight:800;color:#059669;line-height:1;">{{ $completionRate }}<span style="font-size:1.2rem;font-weight:600;">%</span></div>
            <div style="margin-top:10px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                    <span style="font-size:11px;color:#6b7280;">{{ $completed }} of {{ $total }} delivered</span>
                    <span style="font-size:11px;font-weight:600;color:#059669;">{{ $completionRate }}%</span>
                </div>
                <div style="background:#f3f4f6;border-radius:999px;height:6px;overflow:hidden;">
                    <div style="height:6px;border-radius:999px;background:linear-gradient(90deg,#10b981,#34d399);width:{{ $completionRate }}%;transition:width .8s ease;"></div>
                </div>
                @if($deliveredOnTime > 0)
                <div style="margin-top:6px;font-size:11px;color:#9ca3af;">
                    {{ $deliveredOnTime }} on-time ({{ $onTimePct }}%)
                </div>
                @endif
            </div>
        </div>

        {{-- Overdue / Health --}}
        <div class="report-card" style="border-left:4px solid {{ $overdue > 0 ? '#ef4444' : '#f59e0b' }};padding:20px 20px 18px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
                <div style="color:#6b7280;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Avg Completion</div>
                <div style="width:32px;height:32px;border-radius:8px;background:#fffbeb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-gauge-high" style="color:#f59e0b;font-size:13px;"></i>
                </div>
            </div>
            <div style="font-size:2.4rem;font-weight:800;color:#d97706;line-height:1;">{{ $avgCompletionLabel }}</div>
            <div style="margin-top:10px;font-size:12px;color:#9ca3af;">from creation to delivery</div>
            @if($completionBuckets['same_day'] > 0)
            @php $sameDayPct = $completed > 0 ? round($completionBuckets['same_day'] / $completed * 100) : 0; @endphp
            <div style="margin-top:8px;display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;color:#15803d;border-radius:6px;padding:3px 9px;font-size:11px;font-weight:600;">
                ⚡ {{ $sameDayPct }}% same-day
            </div>
            @endif
        </div>

        {{-- Revisions --}}
        <div class="report-card" style="border-left:4px solid #ef4444;padding:20px 20px 18px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
                <div style="color:#6b7280;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Revisions</div>
                <div style="width:32px;height:32px;border-radius:8px;background:#fff5f5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-rotate-left" style="color:#ef4444;font-size:13px;"></i>
                </div>
            </div>
            <div style="font-size:2.4rem;font-weight:800;color:#dc2626;line-height:1;">{{ $totalRevisions }}</div>
            <div style="margin-top:10px;font-size:12px;color:#9ca3af;">
                {{ $revisionRate }}% revision rate
                @if($topRevisionTasks->count() > 0)
                · top: <span style="color:#4b5563;font-weight:500;">{{ Str::limit($topRevisionTasks->first()['title'], 22) }}</span>
                @endif
            </div>
        </div>

        {{-- Avg Approval --}}
        @if($approvalItems->count() > 0)
        <div class="report-card" style="border-left:4px solid #8b5cf6;padding:20px 20px 18px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
                <div style="color:#6b7280;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Avg Approval</div>
                <div style="width:32px;height:32px;border-radius:8px;background:#f5f3ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-user-clock" style="color:#8b5cf6;font-size:13px;"></i>
                </div>
            </div>
            <div style="font-size:2.4rem;font-weight:800;color:#7c3aed;line-height:1;">
                {{ $avgApprovalHours < 24 ? $avgApprovalHours.'h' : round($avgApprovalHours/24,1).'d' }}
            </div>
            <div style="margin-top:10px;font-size:12px;color:#9ca3af;">customer response time</div>
            @if($approvalBuckets['under_24'] > 0)
            @php $fast24 = round($approvalBuckets['under_24'] / $approvalItems->count() * 100); @endphp
            <div style="margin-top:8px;display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;color:#15803d;border-radius:6px;padding:3px 9px;font-size:11px;font-weight:600;">
                ✓ {{ $fast24 }}% under 24h
            </div>
            @endif
        </div>
        @else
        <div class="report-card" style="border-left:4px solid #8b5cf6;padding:20px 20px 18px;opacity:.5;">
            <div style="color:#6b7280;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">Avg Approval</div>
            <div style="font-size:13px;color:#9ca3af;">No approval data yet</div>
        </div>
        @endif

        {{-- Work Period --}}
        <div class="report-card" style="border-left:4px solid #0ea5e9;padding:20px 20px 18px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
                <div style="color:#6b7280;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Work Period</div>
                <div style="width:32px;height:32px;border-radius:8px;background:#f0f9ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-calendar-days" style="color:#0ea5e9;font-size:13px;"></i>
                </div>
            </div>
            <div style="font-size:2.4rem;font-weight:800;color:#0284c7;line-height:1;">
                {{ $workDays }}<span style="font-size:1rem;font-weight:600;color:#9ca3af;margin-left:4px;">days</span>
            </div>
            @if($firstTaskAt)
            <div style="margin-top:10px;font-size:12px;color:#9ca3af;">
                {{ $firstTaskAt->format('M j') }} – {{ $lastTaskAt->format('M j, Y') }}
            </div>
            @if($peakLabel)
            <div style="margin-top:6px;display:inline-flex;align-items:center;gap:5px;background:#eff6ff;color:#1d4ed8;border-radius:6px;padding:3px 9px;font-size:11px;font-weight:600;">
                🔥 Peak: {{ $peakLabel }}
            </div>
            @endif
            @endif
        </div>
    </div>{{-- end KPI grid --}}

    {{-- ── Status Breakdown + Projects ─────────────────────────────────────── --}}
    @php
        $statusGroups = $allTasks->groupBy('status')->map->count();
        $statusConfig = [
            'draft'              => ['label'=>'Draft',       'bg'=>'#f3f4f6','color'=>'#6b7280','border'=>'#e5e7eb'],
            'assigned'           => ['label'=>'Assigned',    'bg'=>'#eff6ff','color'=>'#1d4ed8','border'=>'#bfdbfe'],
            'viewed'             => ['label'=>'Viewed',      'bg'=>'#f3f4f6','color'=>'#4b5563','border'=>'#d1d5db'],
            'in_progress'        => ['label'=>'In Progress', 'bg'=>'#fff7ed','color'=>'#ea580c','border'=>'#fed7aa'],
            'submitted'          => ['label'=>'Submitted',   'bg'=>'#fef3c7','color'=>'#d97706','border'=>'#fde68a'],
            'revision_requested' => ['label'=>'Revision',    'bg'=>'#fee2e2','color'=>'#dc2626','border'=>'#fecaca'],
            'approved'           => ['label'=>'Approved',    'bg'=>'#d1fae5','color'=>'#059669','border'=>'#a7f3d0'],
            'delivered'          => ['label'=>'Delivered',   'bg'=>'#dcfce7','color'=>'#16a34a','border'=>'#bbf7d0'],
            'archived'           => ['label'=>'Archived',    'bg'=>'#f3f4f6','color'=>'#9ca3af','border'=>'#e5e7eb'],
        ];
        $projectsBreakdown = $allTasks
            ->groupBy(fn($t) => $t->project?->name ?? 'No Project')
            ->map(fn($g, $name) => [
                'name'      => $name,
                'total'     => $g->count(),
                'delivered' => $g->whereIn('status', ['delivered','approved'])->count(),
                'active'    => $g->whereNotIn('status', ['delivered','approved','archived'])->count(),
            ])
            ->sortByDesc('total')
            ->values();
        $maxProj = $projectsBreakdown->max('total') ?: 1;
    @endphp

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
        {{-- Status Breakdown --}}
        <div class="report-card">
            <div style="font-weight:700;font-size:15px;color:#111827;margin-bottom:16px;">
                <i class="fas fa-chart-pie" style="color:#6366f1;margin-right:6px;"></i>Status Breakdown
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach($statusConfig as $key => $cfg)
                @php $cnt = $statusGroups[$key] ?? 0; @endphp
                @if($cnt > 0)
                <div style="display:flex;align-items:center;gap:8px;background:{{ $cfg['bg'] }};border:1px solid {{ $cfg['border'] }};border-radius:10px;padding:9px 14px;">
                    <span style="font-size:1rem;font-weight:700;color:{{ $cfg['color'] }};">{{ $cnt }}</span>
                    <span style="font-size:12px;font-weight:500;color:{{ $cfg['color'] }};">{{ $cfg['label'] }}</span>
                </div>
                @endif
                @endforeach
            </div>
        </div>

        {{-- Projects Breakdown --}}
        <div class="report-card">
            <div style="font-weight:700;font-size:15px;color:#111827;margin-bottom:16px;">
                <i class="fas fa-folder-open" style="color:#0ea5e9;margin-right:6px;"></i>Projects
                <span style="font-size:12px;font-weight:400;color:#9ca3af;margin-left:6px;">{{ $projectsBreakdown->count() }} project{{ $projectsBreakdown->count() !== 1 ? 's' : '' }}</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($projectsBreakdown as $proj)
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                        <span style="font-size:13px;font-weight:500;color:#1f2937;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:70%;">{{ $proj['name'] }}</span>
                        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                            <span style="font-size:11px;color:#16a34a;">✓ {{ $proj['delivered'] }}</span>
                            @if($proj['active'] > 0)<span style="font-size:11px;color:#0284c7;">⏳ {{ $proj['active'] }}</span>@endif
                            <span style="font-size:13px;font-weight:700;color:#1f2937;">{{ $proj['total'] }}</span>
                        </div>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="background:#6366f1;width:{{ round($proj['total']/$maxProj*100) }}%;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Monthly Activity Chart ─────────────────────────────────────────── --}}
    <div class="report-card" style="margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:8px;">
            <div>
                <div style="font-weight:700;font-size:15px;color:#111827;">Monthly Activity</div>
                <div style="color:#9ca3af;font-size:12px;margin-top:2px;">Tasks created vs delivered per month</div>
            </div>
            @if($peakLabel)
            <div style="background:#eff6ff;color:#1d4ed8;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;">
                <i class="fas fa-fire-flame-curved" style="color:#f59e0b;margin-right:4px;"></i>
                Peak: {{ $peakLabel }} · {{ $peakCount }} tasks
            </div>
            @endif
        </div>
        <div style="position:relative;height:260px;">
            <canvas id="activityChart"></canvas>
        </div>
    </div>

    {{-- ── Two-column: Approval + Workload ─────────────────────────────────── --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">

        {{-- Approval Performance --}}
        <div class="report-card">
            <div style="font-weight:700;font-size:15px;color:#111827;margin-bottom:4px;">
                <i class="fas fa-clock" style="color:#8b5cf6;margin-right:6px;"></i>Customer Approval Speed
            </div>
            <div style="color:#9ca3af;font-size:12px;margin-bottom:16px;">How fast the customer responds after design is sent</div>

            @if($approvalItems->count() === 0)
                <div style="color:#9ca3af;text-align:center;padding:24px 0;font-size:13px;">No approval tracking data yet</div>
            @else
                {{-- Doughnut --}}
                <div style="display:flex;align-items:center;gap:20px;margin-bottom:16px;">
                    <div style="position:relative;width:110px;height:110px;flex-shrink:0;">
                        <canvas id="approvalDonut" width="110" height="110"></canvas>
                        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                            <div style="font-size:1.1rem;font-weight:700;color:#1f2937;line-height:1;">
                                {{ $avgApprovalHours < 24 ? $avgApprovalHours.'h' : round($avgApprovalHours/24,1).'d' }}
                            </div>
                            <div style="font-size:10px;color:#9ca3af;">avg</div>
                        </div>
                    </div>
                    <div style="flex:1;space-y:8px;">
                        @php $total_a = $approvalItems->count(); @endphp
                        @foreach([['label'=>'< 24 hours','count'=>$approvalBuckets['under_24'],'color'=>'#10b981'],['label'=>'24–72 hours','count'=>$approvalBuckets['under_72'],'color'=>'#f59e0b'],['label' => '> 72 hours','count'=>$approvalBuckets['over_72'],'color'=>'#ef4444']] as $bucket)
                        <div style="margin-bottom:8px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                                <span style="font-size:11px;color:#4b5563;">{{ $bucket['label'] }}</span>
                                <span style="font-size:11px;font-weight:600;color:#1f2937;">{{ $bucket['count'] }}</span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill" style="background:{{ $bucket['color'] }};width:{{ $total_a > 0 ? round($bucket['count']/$total_a*100) : 0 }}%;"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Best / worst --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    @php $fastest = $approvalItems->first(); $slowest = $approvalItems->last(); @endphp
                    <div style="background:#f0fdf4;border-radius:8px;padding:10px;">
                        <div style="font-size:10px;color:#16a34a;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Fastest ⚡</div>
                        <div style="font-size:13px;font-weight:700;color:#15803d;margin-top:2px;">
                            {{ $fastest['hours'] < 1 ? round($fastest['hours']*60).'min' : ($fastest['hours'] < 24 ? $fastest['hours'].'h' : round($fastest['hours']/24,1).'d') }}
                        </div>
                        <div style="font-size:11px;color:#4b5563;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Str::limit($fastest['title'], 28) }}</div>
                    </div>
                    <div style="background:#fff5f5;border-radius:8px;padding:10px;">
                        <div style="font-size:10px;color:#dc2626;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Slowest 🐢</div>
                        <div style="font-size:13px;font-weight:700;color:#b91c1c;margin-top:2px;">
                            {{ $slowest['hours'] < 24 ? $slowest['hours'].'h' : round($slowest['hours']/24,1).'d' }}
                        </div>
                        <div style="font-size:11px;color:#4b5563;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Str::limit($slowest['title'], 28) }}</div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Team Workload --}}
        <div class="report-card">
            <div style="font-weight:700;font-size:15px;color:#111827;margin-bottom:4px;">
                <i class="fas fa-users" style="color:#0ea5e9;margin-right:6px;"></i>Team Workload
            </div>
            <div style="color:#9ca3af;font-size:12px;margin-bottom:16px;">Tasks per team member for this customer</div>

            @php $maxLoad = $workload->max('total') ?: 1; $palette = ['#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899']; @endphp
            <div style="space-y:10px;">
                @foreach($workload as $i => $member)
                <div style="margin-bottom:12px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0;background:{{ $palette[$i % count($palette)] }}">
                                {{ strtoupper(substr($member['name'], 0, 1)) }}
                            </div>
                            <span style="font-size:13px;font-weight:500;color:#1f2937;">{{ $member['name'] }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:11px;color:#9ca3af;">{{ $member['delivered'] }}/{{ $member['total'] }} done</span>
                            <span style="font-size:13px;font-weight:700;color:#1f2937;">{{ $member['total'] }}</span>
                        </div>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="background:{{ $palette[$i % count($palette)] }};width:{{ round($member['total']/$maxLoad*100) }}%;"></div>
                    </div>
                </div>
                @endforeach
            </div>

            @if($workload->count() > 0)
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f3f4f6;display:flex;gap:16px;font-size:12px;">
                <div><span style="color:#9ca3af;">Top contributor:</span> <strong>{{ $workload->first()['name'] }}</strong> ({{ round($workload->first()['total']/$total*100) }}% of tasks)</div>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Completion Time Distribution ─────────────────────────────────────── --}}
    @if($completionHours->count() > 0)
    <div class="report-card" style="margin-bottom:24px;">
        <div style="font-weight:700;font-size:15px;color:#111827;margin-bottom:4px;">
            <i class="fas fa-gauge-high" style="color:#f59e0b;margin-right:6px;"></i>Delivery Speed
        </div>
        <div style="color:#9ca3af;font-size:12px;margin-bottom:20px;">How quickly tasks were completed from creation to delivery</div>
        @php
            $bucketDefs = [
                ['label' => 'Same day  (< 24h)',  'count' => $completionBuckets['same_day'],    'color' => '#10b981', 'icon' => '⚡'],
                ['label' => '1 – 3 days',         'count' => $completionBuckets['one_three'],   'color' => '#6366f1', 'icon' => '✅'],
                ['label' => '3 – 7 days',         'count' => $completionBuckets['three_seven'], 'color' => '#f59e0b', 'icon' => '⏳'],
                ['label' => 'Over a week',        'count' => $completionBuckets['over_week'],   'color' => '#ef4444', 'icon' => '🐢'],
            ];
            $totalDel = $completionHours->count();
        @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            @foreach($bucketDefs as $b)
            <div style="background:#fafafa;border-radius:10px;padding:14px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:13px;color:#4b5563;">{{ $b['icon'] }} {{ $b['label'] }}</span>
                    <span style="font-size:1rem;font-weight:700;color:#1f2937;">{{ $b['count'] }}</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="background:{{ $b['color'] }};width:{{ $totalDel > 0 ? round($b['count']/$totalDel*100) : 0 }}%;"></div>
                </div>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">{{ $totalDel > 0 ? round($b['count']/$totalDel*100) : 0 }}% of deliveries</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Revisions ────────────────────────────────────────────────────────── --}}
    @if($totalRevisions > 0)
    <div class="report-card" style="margin-bottom:24px;">
        <div style="font-weight:700;font-size:15px;color:#111827;margin-bottom:4px;">
            <i class="fas fa-rotate-left" style="color:#ef4444;margin-right:6px;"></i>Revision Analysis
        </div>
        <div style="color:#9ca3af;font-size:12px;margin-bottom:16px;">Tasks that required rework after initial submission</div>
        <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
            <div style="text-align:center;padding:16px 20px;background:#fff5f5;border-radius:12px;min-width:100px;">
                <div style="font-size:2rem;font-weight:700;color:#dc2626;line-height:1;">{{ $totalRevisions }}</div>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">total revisions</div>
            </div>
            <div style="text-align:center;padding:16px 20px;background:#fff5f5;border-radius:12px;min-width:100px;">
                <div style="font-size:2rem;font-weight:700;color:#dc2626;line-height:1;">{{ $revisionRate }}%</div>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">revision rate</div>
            </div>
            @if($topRevisionTasks->count() > 0)
            <div style="flex:1;min-width:200px;">
                <div style="font-size:12px;font-weight:600;color:#4b5563;margin-bottom:8px;">Most Revised Tasks</div>
                @foreach($topRevisionTasks as $rt)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f3f4f6;">
                    <span style="font-size:12px;color:#1f2937;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $rt['title'] }}</span>
                    <span style="background:#fee2e2;color:#dc2626;border-radius:20px;padding:2px 8px;font-size:11px;font-weight:600;margin-left:8px;flex-shrink:0;">{{ $rt['count'] }}×</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ── Smart Insights ────────────────────────────────────────────────────── --}}
    <div class="report-card" style="margin-bottom:24px;background:linear-gradient(135deg,#faf5ff,#eff6ff);">
        <div style="font-weight:700;font-size:15px;color:#111827;margin-bottom:16px;">
            <i class="fas fa-lightbulb" style="color:#f59e0b;margin-right:6px;"></i>Smart Insights
        </div>
        @php
            $insights = [];

            if ($peakLabel) {
                $pct = $total > 0 ? round($peakCount / $total * 100) : 0;
                $insights[] = ['icon' => '📅', 'color' => '#eff6ff', 'border' => '#bfdbfe', 'text' =>
                    "Peak activity was <strong>{$peakLabel}</strong> with <strong>{$peakCount} tasks</strong> ({$pct}% of all work for this customer).",
                ];
            }

            if ($completionHours->count() > 0) {
                $avgLabel = $avgCompletionHours < 24 ? round($avgCompletionHours, 1).'h' : round($avgCompletionHours / 24, 1).' days';
                $insights[] = ['icon' => '⚡', 'color' => '#f0fdf4', 'border' => '#bbf7d0', 'text' =>
                    "Average task completion time is <strong>{$avgLabel}</strong> from creation to delivery.",
                ];
            }

            if ($totalRevisions > 0) {
                $insights[] = ['icon' => '🔄', 'color' => '#fff5f5', 'border' => '#fecaca', 'text' =>
                    "<strong>{$totalRevisions} revision</strong> request".($totalRevisions > 1 ? 's were' : ' was')." made across {$total} tasks — a <strong>{$revisionRate}%</strong> revision rate.",
                ];
            }

            if ($workload->count() > 0) {
                $top = $workload->first();
                $topPct = $total > 0 ? round($top['total'] / $total * 100) : 0;
                $insights[] = ['icon' => '🏆', 'color' => '#fafaf9', 'border' => '#d4d4aa', 'text' =>
                    "Top contributor: <strong>{$top['name']}</strong> handled <strong>{$top['total']} tasks</strong> ({$topPct}% of workload) for this customer.",
                ];
            }

            if ($approvalItems->count() > 0) {
                $fastest = $approvalItems->first();
                $slowest = $approvalItems->last();
                $fastLabel = $fastest['hours'] < 1 ? round($fastest['hours'] * 60).' min' : ($fastest['hours'] < 24 ? $fastest['hours'].'h' : round($fastest['hours']/24, 1).'d');
                $slowLabel = $slowest['hours'] < 24 ? $slowest['hours'].'h' : round($slowest['hours']/24, 1).'d';
                $insights[] = ['icon' => '⚡', 'color' => '#f0fdf4', 'border' => '#bbf7d0', 'text' =>
                    "Fastest customer approval: <strong>{$fastLabel}</strong> (<em>".e($fastest['title'])."</em>). Slowest: <strong>{$slowLabel}</strong> (<em>".e($slowest['title'])."</em>).",
                ];
                if ($approvalBuckets['under_24'] > 0) {
                    $pct24 = round($approvalBuckets['under_24'] / $approvalItems->count() * 100);
                    $insights[] = ['icon' => '✅', 'color' => '#f0fdf4', 'border' => '#bbf7d0', 'text' =>
                        "<strong>{$pct24}%</strong> of design approvals were completed within 24 hours.",
                    ];
                }
                if ($approvalBuckets['over_72'] > 0) {
                    $insights[] = ['icon' => '⚠️', 'color' => '#fffbeb', 'border' => '#fde68a', 'text' =>
                        "<strong>{$approvalBuckets['over_72']}</strong> design".($approvalBuckets['over_72'] > 1 ? 's took' : ' took')." over 72 hours for customer approval — consider following up sooner.",
                    ];
                }
            }

            if ($completionBuckets['same_day'] > 0 && $total > 0) {
                $pct = round($completionBuckets['same_day'] / $total * 100);
                if ($pct >= 50) {
                    $insights[] = ['icon' => '🚀', 'color' => '#f0fdf4', 'border' => '#bbf7d0', 'text' =>
                        "<strong>{$pct}%</strong> of tasks were delivered on the same day they were created — excellent turnaround!",
                    ];
                }
            }
        @endphp

        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($insights as $ins)
            <div class="insight-pill" style="background:{{ $ins['color'] }};border:1px solid {{ $ins['border'] }};">
                <span style="font-size:18px;flex-shrink:0;">{{ $ins['icon'] }}</span>
                <span style="font-size:13px;color:#374151;line-height:1.5;">{!! $ins['text'] !!}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Task Summary Table ───────────────────────────────────────────────── --}}
    @php
    $taskRows = $allTasks->map(function($task) {
        $isOverdue = $task->deadline && \Carbon\Carbon::parse($task->deadline)->isPast()
            && !in_array($task->status, ['delivered','approved','archived']);
        return [
            'id'       => $task->id,
            'title'    => $task->title,
            'url'      => route('admin.tasks.show', $task),
            'assignee' => $task->assignee?->name ?? '—',
            'project'  => $task->project?->name ?? '',
            'status'   => $task->status,
            'created'  => $task->created_at->format('M j, Y'),
            'created_ts'=> $task->created_at->timestamp,
            'deadline' => $task->deadline ? \Carbon\Carbon::parse($task->deadline)->format('M j, Y') : '',
            'deadline_ts'=> $task->deadline ? \Carbon\Carbon::parse($task->deadline)->timestamp : 0,
            'overdue'  => $isOverdue,
        ];
    })->values();
    @endphp
    <div class="report-card" x-data="taskTable" x-cloak>
        {{-- Header row --}}
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
            <div>
                <div style="font-weight:700;font-size:15px;color:#111827;">
                    <i class="fas fa-table-list" style="color:#6366f1;margin-right:6px;"></i>All Tasks
                </div>
                <div style="color:#9ca3af;font-size:12px;margin-top:2px;">
                    Showing <span x-text="visibleCount"></span> of {{ $total }} tasks
                </div>
            </div>
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:11px;pointer-events:none;"></i>
                <input type="text" x-model="search" placeholder="Search tasks…"
                       style="padding:6px 10px 6px 28px;border:1px solid #e5e7eb;border-radius:8px;font-size:12px;width:180px;outline:none;color:#374151;"
                       @focus="$el.style.borderColor='#6366f1'" @blur="$el.style.borderColor='#e5e7eb'">
            </div>
        </div>

        {{-- Hardcoded tabs (no x-for — avoids template/rendered-element confusion) --}}
        <div style="display:flex;gap:2px;background:#F3F4F6;border-radius:12px;padding:4px;margin-bottom:22px;width:fit-content;" class="no-print">
            @php
            $tabDefs = [
                ['key'=>'all',      'label'=>'All'],
                ['key'=>'active',   'label'=>'Active'],
                ['key'=>'done',     'label'=>'Delivered'],
                ['key'=>'overdue',  'label'=>'Overdue'],
                ['key'=>'revision', 'label'=>'Revision'],
            ];
            @endphp
            @foreach($tabDefs as $tab)
            <button @click="activeTab = '{{ $tab['key'] }}'"
                    :style="activeTab === '{{ $tab['key'] }}'
                        ? 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);'
                        : 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;'"
                    style="display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;{{ $tab['key'] === 'all' ? 'background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);' : 'background:transparent;color:#6B7280;' }}">
                {{ $tab['label'] }}
                <span x-text="countFor('{{ $tab['key'] }}')"
                      :style="activeTab === '{{ $tab['key'] }}' ? 'background:rgba(79,70,229,.12);color:#4F46E5;' : 'background:#E5E7EB;color:#6B7280;'"
                      style="border-radius:20px;padding:1px 7px;font-size:11px;font-weight:700;min-width:20px;text-align:center;{{ $tab['key'] === 'all' ? 'background:rgba(79,70,229,.12);color:#4F46E5;' : 'background:#E5E7EB;color:#6B7280;' }}"></span>
            </button>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="task-table-wrap" style="overflow-x:auto;">
            <div class="task-table-scroll" style="max-height:500px;overflow-y:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead style="position:sticky;top:0;z-index:2;background:#fff;box-shadow:0 1px 0 #f3f4f6;">
                        <tr>
                            @php
                            $cols = [
                                ['key'=>'title',       'label'=>'Task'],
                                ['key'=>'assignee',    'label'=>'Assignee'],
                                ['key'=>'status',      'label'=>'Status'],
                                ['key'=>'created_ts',  'label'=>'Created'],
                                ['key'=>'deadline_ts', 'label'=>'Deadline'],
                            ];
                            @endphp
                            @foreach($cols as $col)
                            <th class="sort-th" :class="sortCol === '{{ $col['key'] }}' ? 'active' : ''"
                                @click="toggleSort('{{ $col['key'] }}')"
                                style="text-align:left;padding:9px 12px;color:#6b7280;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">
                                {{ $col['label'] }}
                                <i class="fas sort-icon"
                                   :class="sortCol === '{{ $col['key'] }}'
                                       ? (sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down')
                                       : 'fa-sort'"></i>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="t in filteredRows" :key="t.id">
                            <tr style="border-bottom:1px solid #f9fafb;transition:background .1s;"
                                @mouseover="$el.style.background='#f9fafb'" @mouseout="$el.style.background=''">
                                <td style="padding:10px 12px;max-width:280px;">
                                    <a :href="t.url" target="_blank"
                                       style="color:#111827;font-weight:500;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                       :title="t.title" x-text="t.title"></a>
                                    <span x-show="t.project" style="font-size:11px;color:#9ca3af;" x-text="t.project"></span>
                                </td>
                                <td style="padding:10px 12px;color:#4b5563;white-space:nowrap;" x-text="t.assignee"></td>
                                <td style="padding:10px 12px;">
                                    <span :style="statusStyle(t.status)"
                                          style="border-radius:20px;padding:3px 10px;font-size:11px;font-weight:600;white-space:nowrap;"
                                          x-text="statusLabel(t.status)"></span>
                                </td>
                                <td style="padding:10px 12px;color:#6b7280;white-space:nowrap;" x-text="t.created"></td>
                                <td style="padding:10px 12px;white-space:nowrap;">
                                    <template x-if="t.deadline">
                                        <span :style="t.overdue ? 'color:#dc2626;font-weight:600' : 'color:#6b7280'"
                                              x-text="t.deadline + (t.overdue ? ' ⚠️' : '')"></span>
                                    </template>
                                    <template x-if="!t.deadline">
                                        <span style="color:#d1d5db;">—</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <template x-if="filteredRows.length === 0">
                            <tr>
                                <td colspan="5" style="text-align:center;padding:32px;color:#9ca3af;font-size:13px;">
                                    No tasks match the current filter.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@php
$reportLogo = '';
if (!empty($appSettings['logo_path'])) {
    $lp = Storage::disk('public')->path($appSettings['logo_path']);
    if (file_exists($lp)) {
        $mime = mime_content_type($lp) ?: 'image/jpeg';
        $reportLogo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($lp));
    }
}
@endphp

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
window._reportTasks    = @json($taskRows);
window._reportWorkload = @json($workload);
document.addEventListener('alpine:init', () => {
    Alpine.data('taskTable', () => ({
        all:          window._reportTasks || [],
        search:       '',
        activeTab:    'all',
        sortCol:      'created_ts',
        sortDir:      'asc',
        _printTab:    null,
        _printSearch: null,

        init() {
            window.addEventListener('beforeprint', () => {
                this._printTab    = this.activeTab;
                this._printSearch = this.search;
                this.activeTab    = 'all';
                this.search       = '';
            });
            window.addEventListener('afterprint', () => {
                if (this._printTab !== null) this.activeTab = this._printTab;
                if (this._printSearch !== null) this.search = this._printSearch;
                this._printTab    = null;
                this._printSearch = null;
            });
        },

        get visibleCount() { return this.filteredRows.length; },

        countFor(key) {
            return this.all.filter(t => this.matchTab(t, key)).length;
        },

        matchTab(t, key) {
            if (key === 'all')      return true;
            if (key === 'active')   return !['delivered','approved','archived'].includes(t.status);
            if (key === 'done')     return ['delivered','approved'].includes(t.status);
            if (key === 'overdue')  return !!t.overdue;
            if (key === 'revision') return t.status === 'revision_requested';
            return true;
        },

        get filteredRows() {
            const q   = this.search.toLowerCase().trim();
            const tab = this.activeTab;
            const col = this.sortCol;
            const dir = this.sortDir === 'asc' ? 1 : -1;

            let list = this.all.filter(t => {
                if (!this.matchTab(t, tab)) return false;
                if (q) {
                    return t.title.toLowerCase().includes(q)
                        || t.assignee.toLowerCase().includes(q)
                        || t.project.toLowerCase().includes(q);
                }
                return true;
            });

            list.sort((a, b) => {
                const av = a[col] ?? '';
                const bv = b[col] ?? '';
                if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
                return String(av).localeCompare(String(bv)) * dir;
            });

            return list;
        },

        toggleSort(col) {
            if (this.sortCol === col) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortCol = col;
                this.sortDir = 'asc';
            }
        },

        statusLabel(s) {
            const map = {
                in_progress: 'In Progress', submitted: 'Submitted',
                revision_requested: 'Revision', approved: 'Approved',
                delivered: 'Delivered', assigned: 'Assigned', viewed: 'Viewed',
                draft: 'Draft', archived: 'Archived',
            };
            return map[s] || s.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
        },

        statusStyle(s) {
            const map = {
                in_progress:        'background:#ede9fe;color:#7c3aed',
                submitted:          'background:#fef3c7;color:#d97706',
                revision_requested: 'background:#fee2e2;color:#dc2626',
                approved:           'background:#d1fae5;color:#059669',
                delivered:          'background:#dcfce7;color:#16a34a',
                assigned:           'background:#e0f2fe;color:#0284c7',
                viewed:             'background:#f3f4f6;color:#4b5563',
                draft:              'background:#f3f4f6;color:#6b7280',
                archived:           'background:#f3f4f6;color:#9ca3af',
            };
            return map[s] || 'background:#f3f4f6;color:#6b7280';
        },
    }));
});
</script>

<script>
/* ══════════════════════════════════════════════════════════
   CUSTOMER REPORT — Branded Print & Export PDF
══════════════════════════════════════════════════════════ */
function buildCustomerReportHTML(immediate) {
    var company  = '{{ addslashes($appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name')) }}';
    var dept     = '{{ addslashes($appSettings['department_name'] ?? 'Operations') }}';
    var logoSrc  = '{{ $reportLogo ?? '' }}';
    var mono     = company.charAt(0).toUpperCase();
    var custName = '{{ addslashes($customer->name) }}';
    var custCo   = '{{ addslashes($customer->company ?? '') }}';
    var custEmail= '{{ addslashes($customer->email ?? '') }}';
    var custPhone= '{{ addslashes($customer->phone ?? '') }}';
    var dateStr  = '{{ now()->format(config('app.date_format','M d, Y')) }}';
    var timeStr  = '{{ now()->format('H:i') }}';
    var user     = '{{ auth()->user()->name }}';
    @if($firstTaskAt)
    var period   = '{{ $firstTaskAt->format(config('app.date_format','M d, Y')) }} &mdash; {{ $lastTaskAt->format(config('app.date_format','M d, Y')) }}';
    var workDays = '{{ $workDays }} calendar days';
    @else
    var period   = 'All Time';
    var workDays = '';
    @endif

    var kpis = [
        { label:'Total Tasks',     value:'{{ $total }}',             color:'#4F46E5', bg:'#EEF2FF', sub:'All tasks' },
        { label:'Completed',       value:'{{ $completed }}',         color:'#059669', bg:'#D1FAE5', sub:'Delivered + Approved' },
        { label:'Completion Rate', value:'{{ $completionRate }}%',   color:'#EA580C', bg:'#FFF7ED', sub:'Of all tasks' },
        { label:'Overdue',         value:'{{ $overdue }}',           color:'#DC2626', bg:'#FEF2F2', sub:'Needs attention' },
        { label:'Avg Completion',  value:'{{ $avgCompletionLabel }}',color:'#2563EB', bg:'#EFF6FF', sub:'Created to delivery' },
        { label:'Revisions',       value:'{{ $totalRevisions }}',    color:'#7C3AED', bg:'#EDE9FE', sub:'Revision requests' },
    ];

    var workload = window._reportWorkload || [];
    var tasks    = window._reportTasks    || [];

    // ── CSS ───────────────────────────────────────────────
    var css =
      '* { box-sizing:border-box; margin:0; padding:0; }'
    + 'body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif; font-size:12px; color:#111827; background:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.pbar { position:sticky; top:0; z-index:100; background:linear-gradient(135deg,#4338CA,#7C3AED); padding:11px 28px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 3px 14px rgba(79,70,229,.35); }'
    + '.pbar-l h2 { font-size:14px; font-weight:700; color:#fff; margin:0; }'
    + '.pbar-l p  { font-size:10.5px; color:rgba(255,255,255,.72); margin:3px 0 0; }'
    + '.pbar-btn  { display:flex; align-items:center; gap:7px; padding:9px 22px; background:#fff; color:#4F46E5; border:none; border-radius:9px; font-size:12px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,.14); }'
    + '.pbar-btn:hover { background:#EEF2FF; }'
    + '.doc { max-width:900px; margin:0 auto; background:#fff; }'
    + '.accent { height:6px; background:linear-gradient(90deg,#4F46E5 0%,#7C3AED 45%,#06B6D4 100%); -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.dh { padding:20px 32px 16px; display:flex; justify-content:space-between; align-items:center; gap:16px; }'
    + '.logo-area { display:flex; align-items:center; gap:14px; }'
    + '.logo-img  { height:48px; width:auto; max-width:160px; object-fit:contain; }'
    + '.logo-mono { width:48px; height:48px; background:linear-gradient(135deg,#4F46E5,#7C3AED); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:25px; font-weight:900; flex-shrink:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.co-name { font-size:17px; font-weight:800; color:#111827; line-height:1.2; }'
    + '.co-sub  { font-size:10px; color:#9CA3AF; margin-top:3px; }'
    + '.rt { text-align:right; }'
    + '.rt-name { font-size:20px; font-weight:900; color:#4F46E5; letter-spacing:-.4px; line-height:1.15; }'
    + '.rt-sub  { font-size:12px; color:#6B7280; margin-top:4px; }'
    + '.rt-badge { display:inline-flex; align-items:center; gap:5px; margin-top:5px; padding:3px 12px; background:#EEF2FF; border-radius:20px; font-size:10px; font-weight:600; color:#6366F1; border:1px solid #C7D2FE; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.hr { height:1px; background:#E5E7EB; }'
    + '.cust-strip { display:flex; align-items:center; gap:14px; padding:14px 32px; background:#F8FAFC; border-bottom:1px solid #E5E7EB; }'
    + '.cust-avatar { width:44px; height:44px; border-radius:11px; background:linear-gradient(135deg,#6366F1,#4F46E5); display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; font-weight:800; flex-shrink:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.cust-name  { font-size:15px; font-weight:800; color:#111827; }'
    + '.cust-co    { font-size:11px; color:#9CA3AF; margin-top:1px; }'
    + '.cust-facts { display:flex; gap:20px; margin-left:auto; }'
    + '.cf { text-align:center; }'
    + '.cf-val { font-size:16px; font-weight:800; color:#111827; line-height:1; }'
    + '.cf-lbl { font-size:9px; color:#9CA3AF; text-transform:uppercase; letter-spacing:.06em; margin-top:2px; }'
    + '.meta { display:flex; background:#F9FAFB; padding:12px 32px; border-bottom:1px solid #E5E7EB; }'
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
    + '.kcard  { border-radius:10px; padding:14px 14px 14px 18px; border:1px solid rgba(0,0,0,.06); position:relative; overflow:hidden; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.kacc   { position:absolute; top:0; left:0; width:4px; height:100%; border-radius:10px 0 0 10px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.k-lbl  { font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; margin-bottom:7px; }'
    + '.k-val  { font-size:30px; font-weight:900; color:#111827; line-height:1; letter-spacing:-1px; }'
    + '.k-sub  { font-size:9.5px; color:#9CA3AF; margin-top:5px; }'
    + '.twrap { border-radius:10px; border:1px solid #E5E7EB; overflow:hidden; margin-bottom:4px; }'
    + 'table  { width:100%; border-collapse:collapse; }'
    + 'thead tr { background:linear-gradient(90deg,#4F46E5,#6366F1); -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + 'th { font-size:9.5px; font-weight:700; color:#fff; text-align:left; padding:9px 12px; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }'
    + 'td { padding:9px 12px; border-bottom:1px solid #F3F4F6; font-size:11.5px; color:#374151; vertical-align:middle; }'
    + 'tbody tr:last-child td { border-bottom:none; }'
    + 'tbody tr:nth-child(even) td { background:#FAFAFA; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.td-n  { font-weight:600; color:#111827; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }'
    + '.td-c  { text-align:center; font-weight:700; }'
    + '.bt    { height:7px; background:#F3F4F6; border-radius:99px; overflow:hidden; min-width:80px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.bf    { height:7px; border-radius:99px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.rb    { display:inline-block; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:600; white-space:nowrap; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.sb    { display:inline-block; padding:2px 9px; border-radius:20px; font-size:10px; font-weight:600; white-space:nowrap; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.foot  { margin:4px 32px 0; padding:13px 0; border-top:2px solid #4F46E5; display:flex; justify-content:space-between; align-items:center; -webkit-print-color-adjust:exact; print-color-adjust:exact; }'
    + '.foot-brand { font-size:10px; font-weight:700; color:#4F46E5; }'
    + '.foot-mid   { font-size:10px; color:#9CA3AF; }'
    + '.foot-conf  { font-size:10px; color:#9CA3AF; font-style:italic; }'
    + '@@media print { .pbar{display:none !important;} body{background:#fff !important;} .doc{max-width:none;} *{-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important;} tr{page-break-inside:avoid;} @@page{size:A4 portrait;margin:0;} }'
    ;

    // ── Status badge map ─────────────────────────────────
    var statusMap = {
        in_progress:        { label:'In Progress', bg:'#EDE9FE', c:'#7C3AED' },
        submitted:          { label:'Submitted',   bg:'#FEF3C7', c:'#D97706' },
        revision_requested: { label:'Revision',    bg:'#FEE2E2', c:'#DC2626' },
        approved:           { label:'Approved',    bg:'#D1FAE5', c:'#059669' },
        delivered:          { label:'Delivered',   bg:'#DCFCE7', c:'#16A34A' },
        assigned:           { label:'Assigned',    bg:'#E0F2FE', c:'#0284C7' },
        viewed:             { label:'Viewed',      bg:'#F3F4F6', c:'#4B5563' },
        draft:              { label:'Draft',       bg:'#F3F4F6', c:'#6B7280' },
        archived:           { label:'Archived',    bg:'#F3F4F6', c:'#9CA3AF' },
        paused:             { label:'Paused',      bg:'#FEF3C7', c:'#D97706' },
    };

    // ── Logo ─────────────────────────────────────────────
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

    // ── Team workload table ───────────────────────────────
    var wHtml = '';
    if (workload.length) {
        wHtml += '<div class="sh"><div class="sh-bar" style="background:#7C3AED;"></div><div class="sh-text">Team Workload</div><span class="sh-pill">' + workload.length + ' members</span></div>';
        wHtml += '<div class="twrap"><table><thead><tr>'
              + '<th>Team Member</th><th style="text-align:center;">Total</th><th style="text-align:center;">Delivered</th><th style="min-width:110px;">Progress</th><th style="text-align:right;">Rate</th>'
              + '</tr></thead><tbody>';
        workload.forEach(function(m) {
            var rate = m.total > 0 ? Math.round(m.delivered / m.total * 100) : 0;
            var c    = rate >= 80 ? '#10B981' : (rate >= 40 ? '#F59E0B' : '#EF4444');
            var rbg  = rate >= 80 ? '#D1FAE5' : (rate >= 40 ? '#FEF3C7' : '#FEE2E2');
            wHtml += '<tr>'
                  + '<td class="td-n">' + m.name + '</td>'
                  + '<td class="td-c">' + m.total + '</td>'
                  + '<td class="td-c" style="color:#10B981;">' + m.delivered + '</td>'
                  + '<td><div class="bt"><div class="bf" style="width:' + rate + '%;background:' + c + ';"></div></div></td>'
                  + '<td style="text-align:right;"><span class="rb" style="background:' + rbg + ';color:' + c + ';">' + rate + '%</span></td>'
                  + '</tr>';
        });
        wHtml += '</tbody></table></div>';
    }

    // ── Task list table ───────────────────────────────────
    var tHtml = '';
    if (tasks.length) {
        tHtml += '<div class="sh"><div class="sh-bar" style="background:#2563EB;"></div><div class="sh-text">All Tasks</div><span class="sh-pill">' + tasks.length + ' tasks</span></div>';
        tHtml += '<div class="twrap"><table><thead><tr>'
              + '<th style="width:28px;">#</th><th>Task</th><th>Project</th><th>Assignee</th><th style="text-align:center;">Status</th><th style="text-align:right;white-space:nowrap;">Deadline</th>'
              + '</tr></thead><tbody>';
        tasks.forEach(function(t, i) {
            var st  = statusMap[t.status] || { label: t.status, bg:'#F3F4F6', c:'#6B7280' };
            var dl  = t.deadline || '—';
            var dlStyle = t.overdue ? 'color:#DC2626;font-weight:600;' : 'color:#6B7280;';
            tHtml += '<tr>'
                  + '<td style="color:#9CA3AF;font-size:10px;">' + (i+1) + '</td>'
                  + '<td class="td-n" style="max-width:200px;">' + t.title + '</td>'
                  + '<td style="font-size:10px;color:#6B7280;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + (t.project||'—') + '</td>'
                  + '<td style="font-size:11px;color:#374151;">' + t.assignee + '</td>'
                  + '<td style="text-align:center;"><span class="sb" style="background:' + st.bg + ';color:' + st.c + ';">' + st.label + '</span></td>'
                  + '<td style="text-align:right;white-space:nowrap;font-size:11px;' + dlStyle + '">' + dl + (t.overdue ? ' ⚠' : '') + '</td>'
                  + '</tr>';
        });
        tHtml += '</tbody></table></div>';
    }

    // ── Preview bar ───────────────────────────────────────
    var pbar = immediate ? '' :
        '<div class="pbar">'
      + '<div class="pbar-l"><h2>Customer Report &mdash; ' + custName + '</h2>'
      + '<p>' + period + (workDays ? ' &bull; ' + workDays : '') + '</p></div>'
      + '<button class="pbar-btn" onclick="window.print()">' + printIcon + ' Print / Save as PDF</button>'
      + '</div>';

    // ── Customer facts strip ──────────────────────────────
    var custInitial = custName.charAt(0).toUpperCase();
    var custFacts   =
        '<div class="cf"><div class="cf-val">{{ $total }}</div><div class="cf-lbl">Tasks</div></div>'
      + '<div class="cf"><div class="cf-val" style="color:#059669;">{{ $completionRate }}%</div><div class="cf-lbl">Done</div></div>'
      + '<div class="cf"><div class="cf-val" style="color:#2563EB;">{{ $workDays }}</div><div class="cf-lbl">Days</div></div>'
      + ({{ $overdue }} > 0 ? '<div class="cf"><div class="cf-val" style="color:#DC2626;">{{ $overdue }}</div><div class="cf-lbl">Overdue</div></div>' : '');

    var custContact = '';
    if (custEmail) custContact += ' &bull; ' + custEmail;
    if (custPhone) custContact += ' &bull; ' + custPhone;

    var printOnLoad = immediate ? '<sc'+'ript>window.onload=function(){window.print();}<\/'+'script>' : '';

    return '<!DOCTYPE html><html lang="en"><head>'
         + '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
         + '<title>Customer Report &mdash; ' + custName + ' | ' + company + '</title>'
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
         +     '<div class="rt-name">Customer Report</div>'
         +     '<div class="rt-badge">&#128274;&ensp;Confidential &mdash; Internal Use</div>'
         +   '</div>'
         + '</div>'
         + '<div class="hr"></div>'

         /* Customer strip */
         + '<div class="cust-strip">'
         +   '<div class="cust-avatar">' + custInitial + '</div>'
         +   '<div>'
         +     '<div class="cust-name">' + custName + '</div>'
         +     '<div class="cust-co">' + (custCo || '') + (custContact || '') + '</div>'
         +   '</div>'
         +   '<div class="cust-facts">' + custFacts + '</div>'
         + '</div>'

         /* Meta strip */
         + '<div class="meta">'
         +   '<div class="mi"><div class="mi-lbl">Generated</div><div class="mi-val">' + dateStr + ' at ' + timeStr + '</div></div>'
         +   '<div class="mi"><div class="mi-lbl">Work Period</div><div class="mi-val">' + period + '</div></div>'
         +   '<div class="mi"><div class="mi-lbl">Prepared By</div><div class="mi-val">' + user + '</div></div>'
         +   '<div class="mi"><div class="mi-lbl">Department</div><div class="mi-val">' + dept + '</div></div>'
         + '</div>'

         /* Content */
         + '<div class="content">'
         +   '<div class="sh"><div class="sh-bar" style="background:#4F46E5;"></div><div class="sh-text">Performance Overview</div></div>'
         +   kHtml
         +   wHtml
         +   tHtml
         + '</div>'

         /* Footer */
         + '<div class="foot">'
         +   '<div class="foot-brand">' + company + ' &mdash; Customer Report: ' + custName + '</div>'
         +   '<div class="foot-mid">Generated ' + dateStr + ' &bull; ' + user + '</div>'
         +   '<div class="foot-conf">Confidential</div>'
         + '</div>'

         + '</div></body></html>';
}

function printCustomerReport() {
    var win = window.open('', '_blank');
    if (win) { win.document.write(buildCustomerReportHTML(true)); win.document.close(); }
    else { alert('Pop-up blocked — please allow pop-ups for this site and try again.'); }
}

function exportCustomerReportPDF() {
    _pdfDownload(buildCustomerReportHTML, '{{ Str::slug($customer->name) }}-report.pdf');
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
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
    }).from(content, 'string').save().then(function() {
        document.body.removeChild(overlay);
    }).catch(function(e) {
        document.body.removeChild(overlay);
        alert('PDF generation failed — ' + (e && e.message ? e.message : 'please try again.'));
    });
}
</script>
@endpush

</div>

{{-- Chart.js initialization --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const months    = @json($allMonths->values());
    const createdMap  = {!! json_encode($monthlyCreated) !!};
    const deliveredMap = {!! json_encode($monthlyDelivered) !!};
    const created   = months.map(m => createdMap[m]   || 0);
    const delivered = months.map(m => deliveredMap[m] || 0);

    const labels = months.map(m => {
        const [y, mo] = m.split('-');
        return new Date(y, mo - 1).toLocaleString('default', { month: 'short', year: 'numeric' });
    });

    // ── Monthly Activity Bar Chart ──────────────────────────────────────────
    new Chart(document.getElementById('activityChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Tasks Created',
                    data: created,
                    backgroundColor: 'rgba(99,102,241,0.85)',
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Tasks Delivered',
                    data: delivered,
                    backgroundColor: 'rgba(16,185,129,0.85)',
                    borderRadius: 6,
                    borderSkipped: false,
                },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { usePointStyle: true, font: { size: 12 } } } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f3f4f6' } },
                x: { ticks: { font: { size: 11 } }, grid: { display: false } },
            },
        },
    });

    @if($approvalItems->count() > 0)
    // ── Approval Donut ───────────────────────────────────────────────────────
    new Chart(document.getElementById('approvalDonut'), {
        type: 'doughnut',
        data: {
            labels: ['< 24h', '24–72h', '> 72h'],
            datasets: [{
                data: [
                    {{ $approvalBuckets['under_24'] }},
                    {{ $approvalBuckets['under_72'] }},
                    {{ $approvalBuckets['over_72'] }},
                ],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 4,
            }],
        },
        options: {
            cutout: '70%',
            responsive: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: {
                label: ctx => ` ${ctx.label}: ${ctx.raw} task${ctx.raw !== 1 ? 's' : ''}`,
            }}},
        },
    });
    @endif
});
</script>
@endsection

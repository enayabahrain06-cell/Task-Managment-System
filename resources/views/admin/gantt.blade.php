@extends('layouts.app')
@section('title', 'Gantt Chart')

@push('styles')
<style>
/* ── Gantt layout ── */
.gantt-wrap       { display:flex; flex-direction:column; height:calc(100vh - 110px); min-height:400px; background:#fff; border:1px solid #E5E7EB; border-radius:14px; overflow:hidden; }
.gantt-toolbar    { display:flex; align-items:center; gap:10px; padding:10px 16px; border-bottom:1px solid #F3F4F6; flex-wrap:wrap; flex-shrink:0; }
.gantt-body       { display:flex; flex:1; overflow:hidden; }
.gantt-left       { width:260px; min-width:260px; border-right:1px solid #E5E7EB; overflow-y:auto; overflow-x:hidden; flex-shrink:0; background:#FAFAFA; }
.gantt-right      { flex:1; overflow:auto; position:relative; }

/* ── Sync scroll ── */
.gantt-left-header  { height:56px; border-bottom:1px solid #E5E7EB; background:#F8F9FA; display:flex; align-items:center; padding:0 14px; flex-shrink:0; position:sticky; top:0; z-index:3; }

/* ── Timeline header ── */
.gantt-timeline-header { position:sticky; top:0; z-index:4; background:#F8F9FA; border-bottom:2px solid #E5E7EB; }

/* ── Row cells ── */
.gantt-row        { display:flex; align-items:center; height:44px; border-bottom:1px solid #F3F4F6; transition:background .12s; }
.gantt-row:last-child { border-bottom:none; }
.gantt-row.project-row { height:36px; background:#F5F3FF; border-bottom:1px solid #E5E7EB; cursor:pointer; user-select:none; }
.gantt-row.project-row:hover { background:#EDE9FE; }
.gantt-row.project-row .row-label { font-size:11px; font-weight:700; color:#4F46E5; text-transform:uppercase; letter-spacing:.05em; padding:0 14px; display:flex; align-items:center; gap:7px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; width:100%; }
.gantt-row.task-row   .row-label  { font-size:12px; font-weight:500; color:#374151; padding:0 14px 0 26px; display:flex; align-items:center; gap:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.gantt-row.task-row.collapsed { display:none; }

/* ── Canvas cell ── */
.gantt-canvas-row     { display:flex; align-items:center; height:44px; border-bottom:1px solid #F3F4F6; position:relative; }
.gantt-canvas-row.project-row { height:36px; background:#F5F3FF; border-bottom:1px solid #E5E7EB; cursor:pointer; }
.gantt-canvas-row.task-row.collapsed { display:none; }

/* ── Day/week column backgrounds ── */
.gantt-col-cell   { height:100%; flex-shrink:0; }
.gantt-col-cell.weekend { background:#F9FAFB; }
.gantt-col-cell.today   { background:#FFFBEB; border-left:1px solid #FDE68A; border-right:1px solid #FDE68A; }

/* ── Header cells ── */
.header-month     { font-size:11px; font-weight:700; color:#4F46E5; text-transform:uppercase; letter-spacing:.05em; padding:6px 10px 2px; border-right:1px solid #E5E7EB; white-space:nowrap; overflow:hidden; }
.header-week      { display:flex; border-bottom:1px solid #F3F4F6; }
.header-day       { font-size:10px; color:#9CA3AF; font-weight:500; text-align:center; flex-shrink:0; line-height:20px; border-right:1px solid #F3F4F6; box-sizing:border-box; }
.header-day.weekend { background:#F9FAFB; }
.header-day.today   { background:#FEFCE8; color:#D97706; font-weight:700; }

/* ── Task bar ── */
.gantt-bar {
    position:absolute;
    height:26px;
    border-radius:7px;
    display:flex;
    align-items:center;
    overflow:hidden;
    cursor:pointer;
    transition:filter .15s, transform .1s, box-shadow .15s;
    z-index:2;
    text-decoration:none;
    box-shadow:0 1px 3px rgba(0,0,0,.08);
}
.gantt-bar:hover  { filter:brightness(.93); transform:scaleY(1.07); z-index:5; box-shadow:0 4px 12px rgba(0,0,0,.15); }
.gantt-bar.overdue { box-shadow:0 0 0 2px #EF4444, 0 1px 3px rgba(0,0,0,.1) !important; }
.gantt-bar.overdue:hover { box-shadow:0 0 0 2px #EF4444, 0 4px 12px rgba(239,68,68,.3) !important; }

/* ── Progress fill inside bar ── */
.gantt-bar-progress { position:absolute; top:0; left:0; height:100%; opacity:.3; pointer-events:none; border-radius:7px; }
.gantt-bar-label  { position:relative; z-index:1; font-size:10px; font-weight:600; padding:0 6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; pointer-events:none; flex:1; min-width:0; }

/* ── Assignee badge ── */
.gantt-bar-assignee { position:relative; z-index:1; width:18px; height:18px; border-radius:50%; background:rgba(0,0,0,.15); display:flex; align-items:center; justify-content:center; font-size:8px; font-weight:700; color:#fff; flex-shrink:0; margin-right:4px; pointer-events:none; }

/* ── Overdue badge on bar ── */
.gantt-bar-overdue-tag { position:relative; z-index:1; background:#EF4444; color:#fff; font-size:8px; font-weight:700; padding:1px 4px; border-radius:3px; margin-right:4px; flex-shrink:0; pointer-events:none; }

/* ── Today line ── */
.today-line { position:absolute; top:0; bottom:0; width:2px; background:#F59E0B; z-index:6; pointer-events:none; }
.today-line::before { content:'Today'; position:absolute; top:4px; left:4px; font-size:9px; color:#D97706; font-weight:700; white-space:nowrap; }

/* ── Toolbar ── */
.gantt-select { padding:5px 10px; border:1px solid #E5E7EB; border-radius:8px; font-size:12px; color:#374151; background:#fff; cursor:pointer; }
.gantt-btn    { padding:5px 12px; border:1px solid #E5E7EB; border-radius:8px; font-size:12px; color:#6B7280; background:#fff; cursor:pointer; transition:all .15s; font-weight:500; }
.gantt-btn:hover { background:#F3F4F6; color:#111827; }
.gantt-btn.active { background:#EEF2FF; color:#4F46E5; border-color:#C7D2FE; }

/* ── Legend ── */
.legend-dot { width:10px; height:10px; border-radius:3px; flex-shrink:0; }

/* ── Project row badges ── */
.proj-badge { font-size:10px; font-weight:600; padding:1px 6px; border-radius:10px; background:#E0E7FF; color:#4338CA; flex-shrink:0; }
.proj-badge.has-overdue { background:#FEE2E2; color:#991B1B; }
.proj-collapse-icon { margin-left:auto; font-size:9px; color:#7C3AED; flex-shrink:0; transition:transform .2s; }
.proj-collapse-icon.collapsed { transform:rotate(-90deg); }

/* ── Empty state ── */
.gantt-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:300px; color:#9CA3AF; }

/* ── Tooltip card ── */
#ganttTooltip {
    position:fixed;
    z-index:9999;
    background:#fff;
    border:1px solid #E5E7EB;
    border-radius:12px;
    box-shadow:0 8px 30px rgba(0,0,0,.15);
    padding:12px 14px;
    min-width:220px;
    max-width:280px;
    pointer-events:none;
    opacity:0;
    transition:opacity .15s;
    font-size:12px;
}
#ganttTooltip.visible { opacity:1; }
.tt-title   { font-weight:700; color:#111827; font-size:13px; margin-bottom:6px; line-height:1.3; }
.tt-row     { display:flex; align-items:center; gap:6px; margin-bottom:4px; color:#6B7280; }
.tt-badge   { display:inline-flex; align-items:center; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:600; }
.tt-overdue { color:#EF4444; font-weight:700; font-size:10px; }

/* ── Scrollbar ── */
.gantt-right::-webkit-scrollbar { width:6px; height:6px; }
.gantt-right::-webkit-scrollbar-track { background:#F9FAFB; }
.gantt-right::-webkit-scrollbar-thumb { background:#D1D5DB; border-radius:3px; }
.gantt-left::-webkit-scrollbar  { width:3px; }
.gantt-left::-webkit-scrollbar-thumb { background:#E5E7EB; border-radius:3px; }

/* ── Export dropdown ── */
.export-dropdown  { position:relative; display:inline-block; }
.export-menu      { position:absolute; right:0; top:calc(100% + 8px); background:#fff; border:1px solid #E5E7EB; border-radius:14px; box-shadow:0 12px 32px rgba(0,0,0,.14); width:240px; z-index:200; overflow:hidden; display:none; }
.export-menu.open { display:block; }
.export-menu-header { padding:10px 14px 8px; border-bottom:1px solid #F3F4F6; font-size:10px; font-weight:700; color:#9CA3AF; text-transform:uppercase; letter-spacing:.06em; }
.export-menu-item { display:flex; align-items:center; gap:12px; padding:10px 14px; cursor:pointer; transition:background .12s; border:none; background:none; width:100%; text-align:left; border-bottom:1px solid #F9FAFB; }
.export-menu-item:last-child { border-bottom:none; }
.export-menu-item:hover { background:#F5F3FF; }
.export-menu-item:hover .emi-icon { color:#fff; }
.export-menu-item:hover .emi-title { color:#4F46E5; }
.emi-icon  { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; transition:filter .12s; }
.emi-text  { display:flex; flex-direction:column; gap:1px; }
.emi-title { font-size:12px; font-weight:600; color:#111827; }
.emi-desc  { font-size:10px; color:#9CA3AF; line-height:1.3; }

/* ── Print styles ── */
@media print {
    body * { visibility:hidden !important; }
    #ganttPrintArea, #ganttPrintArea * { visibility:visible !important; }
    #ganttPrintArea { position:fixed; top:0; left:0; width:100%; z-index:99999; background:#fff; padding:16px; }
    .gantt-right { overflow:visible !important; }
    #ganttCanvas  { width:auto !important; }
    .gantt-wrap   { height:auto !important; border:none !important; }
    .gantt-toolbar { display:none !important; }
}
</style>
@endpush

@section('content')
<div style="padding:20px 24px 4px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0 0 2px;">Gantt Chart</h1>
            <p style="font-size:13px;color:#6B7280;margin:0;">Timeline view of tasks across all projects</p>
        </div>
        <a href="{{ route('admin.tasks.index') }}"
           style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#F3F4F6;border-radius:9px;font-size:12px;font-weight:600;color:#374151;text-decoration:none;">
            <i class="fas fa-list-check"></i> Task List
        </a>
    </div>
</div>

<div style="padding:0 24px 10px;">
    {{-- Info panel --}}
    <div id="ganttInfoPanel" style="background:#F0F4FF;border:1px solid #C7D2FE;border-radius:12px;overflow:hidden;margin-bottom:14px;">
        {{-- Header (always visible, click to toggle) --}}
        <div onclick="toggleInfo()" style="display:flex;align-items:center;gap:10px;padding:10px 16px;cursor:pointer;user-select:none;">
            <span style="width:28px;height:28px;background:#4F46E5;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-circle-info" style="color:#fff;font-size:13px;"></i>
            </span>
            <div style="flex:1;">
                <span style="font-size:13px;font-weight:700;color:#3730A3;">What is the Gantt Chart?</span>
                <span style="font-size:11px;color:#6366F1;margin-left:8px;">Click to expand</span>
            </div>
            <i class="fas fa-chevron-down" id="ganttInfoChevron" style="color:#6366F1;font-size:11px;transition:transform .2s;"></i>
        </div>

        {{-- Body (collapsed by default) --}}
        <div id="ganttInfoBody" style="display:none;padding:0 16px 14px;">
            <hr style="border:none;border-top:1px solid #C7D2FE;margin:0 0 14px;">

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">

                {{-- What is it --}}
                <div style="background:#fff;border-radius:10px;padding:12px 14px;border:1px solid #E0E7FF;">
                    <div style="font-size:11px;font-weight:700;color:#4F46E5;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">
                        <i class="fas fa-diagram-project" style="margin-right:5px;"></i>What is it?
                    </div>
                    <p style="font-size:12px;color:#374151;margin:0;line-height:1.6;">
                        A <strong>Gantt Chart</strong> is a timeline view that shows every task across all your projects as horizontal bars.
                        Each bar stretches from the task's <strong>start date</strong> to its <strong>deadline</strong>, letting you see how work is
                        distributed over time and spot scheduling conflicts at a glance.
                    </p>
                </div>

                {{-- How to read it --}}
                <div style="background:#fff;border-radius:10px;padding:12px 14px;border:1px solid #E0E7FF;">
                    <div style="font-size:11px;font-weight:700;color:#4F46E5;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">
                        <i class="fas fa-book-open" style="margin-right:5px;"></i>How to read it
                    </div>
                    <ul style="font-size:12px;color:#374151;margin:0;padding-left:16px;line-height:1.8;">
                        <li>Each <strong>purple row</strong> is a project — click it to collapse/expand its tasks</li>
                        <li>Each <strong>colored bar</strong> is a task — color = current status</li>
                        <li>The <strong>fill level</strong> inside the bar shows progress (Draft → Delivered)</li>
                        <li>The <strong>initials circle</strong> on the right of a bar shows who is assigned</li>
                        <li>The <strong>amber vertical line</strong> marks today</li>
                        <li>A <strong>red border</strong> on a bar means the task is overdue</li>
                    </ul>
                </div>

                {{-- Controls --}}
                <div style="background:#fff;border-radius:10px;padding:12px 14px;border:1px solid #E0E7FF;">
                    <div style="font-size:11px;font-weight:700;color:#4F46E5;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">
                        <i class="fas fa-sliders" style="margin-right:5px;"></i>Controls
                    </div>
                    <ul style="font-size:12px;color:#374151;margin:0;padding-left:16px;line-height:1.8;">
                        <li><strong>Project filter</strong> — narrow to one project</li>
                        <li><strong>Status filter</strong> — show only tasks in a specific status, or <em>Overdue Only</em></li>
                        <li><strong>Day / Week / Month</strong> — zoom the timeline in or out</li>
                        <li><strong>Today</strong> — scroll the timeline back to today's position</li>
                        <li><strong>Hover</strong> a bar — see full task details in a popup card</li>
                        <li><strong>Click</strong> a bar — open the task detail page</li>
                    </ul>
                </div>

                {{-- Export --}}
                <div style="background:#fff;border-radius:10px;padding:12px 14px;border:1px solid #E0E7FF;">
                    <div style="font-size:11px;font-weight:700;color:#4F46E5;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">
                        <i class="fas fa-download" style="margin-right:5px;"></i>Export options
                    </div>
                    <ul style="font-size:12px;color:#374151;margin:0;padding-left:16px;line-height:1.8;">
                        <li><strong>PNG</strong> — saves a screenshot of the chart exactly as you see it</li>
                        <li><strong>PDF</strong> — opens the print dialog; choose "Save as PDF" for a printable report</li>
                        <li><strong>CSV</strong> — downloads all task data (project, status, assignee, dates) as a spreadsheet you can open in Excel</li>
                    </ul>
                </div>

            </div>

            <p style="font-size:11px;color:#6366F1;margin:12px 0 0;text-align:right;">
                <i class="fas fa-lightbulb" style="margin-right:4px;"></i>
                Tip: only tasks with a deadline are shown. Go to <a href="{{ route('admin.tasks.index') }}" style="color:#4F46E5;font-weight:600;">Task List</a> to add deadlines.
            </p>
        </div>
    </div>
</div>

<div style="padding:0 24px 24px;">

@if(count($chartData) === 0)
<div class="gantt-empty" style="background:#fff;border:1px solid #E5E7EB;border-radius:14px;">
    <i class="fas fa-bars-progress" style="font-size:40px;margin-bottom:12px;color:#D1D5DB;"></i>
    <p style="font-weight:700;font-size:15px;color:#374151;margin:0 0 6px;">No tasks with deadlines found</p>
    <p style="font-size:12px;color:#9CA3AF;margin:0;">Assign deadlines to tasks to see them on the Gantt chart.</p>
</div>
@else

<div id="ganttPrintArea">
<div class="gantt-wrap" id="ganttWrap">

    {{-- Toolbar --}}
    <div class="gantt-toolbar">
        {{-- Project filter --}}
        <select class="gantt-select" id="projectFilter" onchange="filterProject(this.value)">
            <option value="">All Projects</option>
            @foreach($projects as $project)
            <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>

        {{-- Status filter --}}
        <select class="gantt-select" id="statusFilter" onchange="filterStatus(this.value)">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="assigned">Assigned</option>
            <option value="viewed">Viewed</option>
            <option value="in_progress">In Progress</option>
            <option value="submitted">Submitted</option>
            <option value="approved">Approved</option>
            <option value="revision_requested">Revision</option>
            <option value="delivered">Delivered</option>
            <option value="archived">Archived</option>
            <option value="overdue">Overdue Only</option>
        </select>

        {{-- Zoom --}}
        <div style="display:flex;gap:4px;margin-left:auto;">
            <button class="gantt-btn" id="zoomOut" onclick="changeZoom(-8)" title="Zoom out"><i class="fas fa-magnifying-glass-minus"></i></button>
            <button class="gantt-btn active" id="zoomDay" onclick="setZoom(28)" title="Day view">Day</button>
            <button class="gantt-btn" id="zoomWeek" onclick="setZoom(14)" title="Week view">Week</button>
            <button class="gantt-btn" id="zoomMonth" onclick="setZoom(6)" title="Month view">Month</button>
            <button class="gantt-btn" id="zoomIn" onclick="changeZoom(8)" title="Zoom in"><i class="fas fa-magnifying-glass-plus"></i></button>
        </div>

        {{-- Today button --}}
        <button class="gantt-btn" onclick="scrollToToday()" title="Jump to today">
            <i class="fas fa-crosshairs" style="margin-right:4px;"></i>Today
        </button>

        {{-- Export dropdown --}}
        <div class="export-dropdown" id="exportDropdown">
            <button class="gantt-btn" onclick="toggleExportMenu(event)" title="Export">
                <i class="fas fa-download" style="margin-right:4px;"></i>Export
                <i class="fas fa-chevron-down" id="exportChevron" style="font-size:9px;margin-left:4px;transition:transform .2s;"></i>
            </button>
            <div class="export-menu" id="exportMenu">
                <div class="export-menu-header">Download as</div>
                <button class="export-menu-item" onclick="exportPNG()">
                    <span class="emi-icon" style="background:#EEF2FF;color:#4F46E5;"><i class="fas fa-image"></i></span>
                    <span class="emi-text">
                        <span class="emi-title">PNG Image (A4)</span>
                        <span class="emi-desc">Chart snapshot scaled to A4 landscape (1754×1240 px)</span>
                    </span>
                </button>
                <button class="export-menu-item" onclick="exportPDF()">
                    <span class="emi-icon" style="background:#FEF2F2;color:#EF4444;"><i class="fas fa-file-pdf"></i></span>
                    <span class="emi-text">
                        <span class="emi-title">PDF (Print)</span>
                        <span class="emi-desc">Open print dialog &amp; save as PDF</span>
                    </span>
                </button>
                <button class="export-menu-item" onclick="exportCSV()">
                    <span class="emi-icon" style="background:#F0FDF4;color:#16A34A;"><i class="fas fa-table"></i></span>
                    <span class="emi-text">
                        <span class="emi-title">Excel Spreadsheet</span>
                        <span class="emi-desc">Formatted .xlsx with colors &amp; status badges</span>
                    </span>
                </button>
            </div>
        </div>

        {{-- Legend --}}
        <div style="display:flex;align-items:center;gap:10px;padding-left:8px;border-left:1px solid #F3F4F6;flex-wrap:wrap;">
            @foreach([
                ['#DBEAFE','#1D4ED8','Assigned'],
                ['#EDE9FE','#6D28D9','In Progress'],
                ['#FEF3C7','#B45309','Submitted'],
                ['#D1FAE5','#065F46','Approved'],
                ['#ECFDF5','#047857','Delivered'],
                ['#FEE2E2','#991B1B','Revision'],
            ] as [$bg,$txt,$lbl])
            <div style="display:flex;align-items:center;gap:4px;">
                <span class="legend-dot" style="background:{{ $bg }};border:1px solid {{ $txt }}33;"></span>
                <span style="font-size:10px;color:#6B7280;">{{ $lbl }}</span>
            </div>
            @endforeach
            <div style="display:flex;align-items:center;gap:4px;">
                <span style="width:10px;height:10px;border-radius:3px;background:#FEE2E2;border:2px solid #EF4444;display:inline-block;flex-shrink:0;"></span>
                <span style="font-size:10px;color:#EF4444;font-weight:600;">Overdue</span>
            </div>
        </div>
    </div>

    {{-- Body --}}
    <div class="gantt-body" id="ganttBody">
        <div class="gantt-left" id="ganttLeft">
            <div class="gantt-left-header">
                <span style="font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Project / Task</span>
            </div>
            <div class="gantt-left-content" id="ganttLeftContent"></div>
        </div>
        <div class="gantt-right" id="ganttRight">
            <div id="ganttCanvas" style="position:relative;"></div>
        </div>
    </div>
</div>

@endif
</div>{{-- /ganttPrintArea --}}
</div>

{{-- Floating tooltip --}}
<div id="ganttTooltip"></div>

<script>
// ── Data ──────────────────────────────────────────────────────────────
const CHART_DATA  = @json($chartData);
const RANGE_START = new Date('{{ $rangeStart }}');
const RANGE_END   = new Date('{{ $rangeEnd }}');
const TODAY       = new Date();
TODAY.setHours(0,0,0,0);

// ── Status progress map (0-100) ───────────────────────────────────────
const STATUS_PROGRESS = {
    draft:              0,
    assigned:          20,
    viewed:            30,
    in_progress:       50,
    submitted:         70,
    approved:          85,
    revision_requested:40,
    delivered:        100,
    archived:         100,
};

// ── State ─────────────────────────────────────────────────────────────
let pixelsPerDay  = 28;
let activeProject = '';
let activeStatus  = '';
let collapsed     = {};   // { projectId: true }

// ── Helpers ───────────────────────────────────────────────────────────
function daysBetween(a, b) { return Math.round((b - a) / 86400000); }
function addDays(d, n) { const r = new Date(d); r.setDate(r.getDate() + n); return r; }
function isWeekend(d) { return d.getDay() === 0 || d.getDay() === 6; }
function isToday(d)   { return d.toDateString() === TODAY.toDateString(); }
function isOverdue(task) {
    const end = new Date(task.end);
    return end < TODAY && task.status !== 'delivered' && task.status !== 'archived';
}
function formatDate(d) { return new Date(d).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'}); }
function monthLabel(d) { return d.toLocaleDateString('en-GB', {month:'short', year:'numeric'}); }
function initials(name) {
    if (!name || name === '—') return '?';
    return name.split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase();
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function buildDays() {
    const days = [];
    let cur = new Date(RANGE_START);
    while (cur <= RANGE_END) { days.push(new Date(cur)); cur.setDate(cur.getDate() + 1); }
    return days;
}

// ── Tooltip ───────────────────────────────────────────────────────────
const tooltip = document.getElementById('ganttTooltip');
let tooltipTimer;

function showTooltip(e, task) {
    clearTimeout(tooltipTimer);
    const overdue  = isOverdue(task);
    const progress = STATUS_PROGRESS[task.status] ?? 0;
    const statusLabel = task.color.label;

    tooltip.innerHTML = `
        <div class="tt-title">${escHtml(task.title)}</div>
        <div class="tt-row">
            <span class="tt-badge" style="background:${task.color.bg};color:${task.color.text};border:1px solid ${task.color.text}33;">${statusLabel}</span>
            ${overdue ? '<span class="tt-overdue"><i class="fas fa-triangle-exclamation"></i> Overdue</span>' : ''}
        </div>
        <div style="margin:6px 0;background:#F3F4F6;border-radius:4px;height:5px;overflow:hidden;">
            <div style="width:${progress}%;height:100%;background:${task.color.text};border-radius:4px;"></div>
        </div>
        <div class="tt-row"><i class="fas fa-user" style="width:12px;color:#9CA3AF;"></i> ${escHtml(task.assignee)}</div>
        <div class="tt-row"><i class="fas fa-calendar-day" style="width:12px;color:#9CA3AF;"></i> ${formatDate(task.start)} → ${formatDate(task.end)}</div>
        <div class="tt-row" style="margin-bottom:0;"><i class="fas fa-diagram-project" style="width:12px;color:#9CA3AF;"></i> ${escHtml(task.project)}</div>
    `;
    positionTooltip(e);
    tooltip.classList.add('visible');
}

function positionTooltip(e) {
    const tw = 240, th = 150;
    let x = e.clientX + 14, y = e.clientY + 14;
    if (x + tw > window.innerWidth  - 16) x = e.clientX - tw - 14;
    if (y + th > window.innerHeight - 16) y = e.clientY - th - 14;
    tooltip.style.left = x + 'px';
    tooltip.style.top  = y + 'px';
}

function hideTooltip() {
    tooltipTimer = setTimeout(() => tooltip.classList.remove('visible'), 80);
}

document.addEventListener('mousemove', e => {
    if (tooltip.classList.contains('visible')) positionTooltip(e);
});

// ── Render ─────────────────────────────────────────────────────────────
function render() {
    const days   = buildDays();
    const totalW = days.length * pixelsPerDay;
    const ROW_H  = 44;
    const PROJ_H = 36;

    let filtered = CHART_DATA;
    if (activeProject) filtered = filtered.filter(p => p.id == activeProject);
    if (activeStatus) {
        filtered = filtered.map(p => ({
            ...p,
            tasks: p.tasks.filter(t => {
                if (activeStatus === 'overdue') return isOverdue(t);
                return t.status === activeStatus;
            })
        })).filter(p => p.tasks.length > 0);
    }
    if (!filtered.length) {
        document.getElementById('ganttLeftContent').innerHTML =
            '<div style="padding:20px 14px;font-size:12px;color:#9CA3AF;text-align:center;">No tasks match the filter.</div>';
        document.getElementById('ganttCanvas').innerHTML = '';
        return;
    }

    // ── Left sidebar ──
    const leftHtml = filtered.map(proj => {
        const overdueCount = proj.tasks.filter(isOverdue).length;
        const badgeCls     = overdueCount > 0 ? 'proj-badge has-overdue' : 'proj-badge';
        const badgeLabel   = overdueCount > 0
            ? `${proj.tasks.length} tasks · <i class='fas fa-triangle-exclamation'></i> ${overdueCount}`
            : `${proj.tasks.length} tasks`;
        const isCollapsed  = !!collapsed[proj.id];
        const iconCls      = isCollapsed ? 'proj-collapse-icon collapsed' : 'proj-collapse-icon';

        const taskRows = proj.tasks.map(t => {
            const over = isOverdue(t);
            return `<div class="gantt-row task-row${isCollapsed ? ' collapsed' : ''}" data-task="${t.id}" data-proj="${proj.id}" style="height:${ROW_H}px;">
                <div class="row-label" title="${escHtml(t.title)}">
                    <span class="legend-dot" style="background:${t.color.bg};border:1.5px solid ${t.color.text}55;flex-shrink:0;${over ? 'box-shadow:0 0 0 1.5px #EF4444;' : ''}"></span>
                    <span style="overflow:hidden;text-overflow:ellipsis;${over ? 'color:#EF4444;font-weight:600;' : ''}">${escHtml(t.title)}</span>
                    ${over ? '<i class="fas fa-triangle-exclamation" style="color:#EF4444;font-size:9px;flex-shrink:0;margin-left:auto;"></i>' : ''}
                </div>
            </div>`;
        }).join('');

        return `<div class="gantt-row project-row" data-project="${proj.id}" onclick="toggleCollapse(${proj.id})">
                    <div class="row-label">
                        <i class="fas fa-diagram-project" style="font-size:10px;flex-shrink:0;"></i>
                        <span style="overflow:hidden;text-overflow:ellipsis;">${escHtml(proj.name)}</span>
                        <span class="${badgeCls}" style="margin-left:6px;flex-shrink:0;">${badgeLabel}</span>
                        <i class="fas fa-chevron-down ${iconCls}" data-proj-icon="${proj.id}"></i>
                    </div>
                </div>${taskRows}`;
    }).join('');
    document.getElementById('ganttLeftContent').innerHTML = leftHtml;

    // ── Timeline header ──
    let monthGroupHtml = '<div style="display:flex;height:26px;border-bottom:1px solid #E5E7EB;">';
    let curMonth = null, monthCount = 0;
    days.forEach((day) => {
        const m = monthLabel(day);
        if (m !== curMonth) {
            if (curMonth !== null) monthGroupHtml += `<div class="header-month" style="width:${monthCount * pixelsPerDay}px;min-width:0;">${curMonth}</div>`;
            curMonth = m; monthCount = 1;
        } else { monthCount++; }
    });
    if (curMonth) monthGroupHtml += `<div class="header-month" style="width:${monthCount * pixelsPerDay}px;min-width:0;">${curMonth}</div>`;
    monthGroupHtml += '</div>';

    let dayHtml = '<div style="display:flex;height:22px;">';
    days.forEach(day => {
        const cls = isToday(day) ? 'today' : (isWeekend(day) ? 'weekend' : '');
        const show = pixelsPerDay >= 14 || day.getDate() === 1;
        dayHtml += `<div class="header-day ${cls}" style="width:${pixelsPerDay}px;${!show ? 'color:transparent;' : ''}">${day.getDate()}</div>`;
    });
    dayHtml += '</div>';

    const headerHtml = `<div class="gantt-timeline-header" style="width:${totalW}px;">${monthGroupHtml}${dayHtml}</div>`;

    // ── Canvas rows ──
    let canvasHtml = '';
    const todayOffset = daysBetween(RANGE_START, TODAY);

    filtered.forEach(proj => {
        const isCollapsed = !!collapsed[proj.id];
        canvasHtml += `<div class="gantt-canvas-row project-row" style="width:${totalW}px;height:${PROJ_H}px;" onclick="toggleCollapse(${proj.id})">`;
        days.forEach(day => {
            const cls = isToday(day) ? 'today' : (isWeekend(day) ? 'weekend' : '');
            canvasHtml += `<div class="gantt-col-cell ${cls}" style="width:${pixelsPerDay}px;height:${PROJ_H}px;float:left;"></div>`;
        });
        canvasHtml += '</div>';

        proj.tasks.forEach(task => {
            const startDate = new Date(task.start);
            const endDate   = new Date(task.end);
            const leftDays  = daysBetween(RANGE_START, startDate);
            const widthDays = Math.max(1, daysBetween(startDate, endDate) + 1);
            const leftPx    = leftDays * pixelsPerDay;
            const widthPx   = Math.max(pixelsPerDay, widthDays * pixelsPerDay);
            const over      = isOverdue(task);
            const progress  = STATUS_PROGRESS[task.status] ?? 0;
            const showLabel = widthPx >= 50;
            const showInit  = widthPx >= 38;
            const overCls   = over ? ' overdue' : '';
            const collCls   = isCollapsed ? ' collapsed' : '';

            canvasHtml += `<div class="gantt-canvas-row task-row${collCls}" data-proj="${proj.id}" style="width:${totalW}px;height:${ROW_H}px;">`;
            days.forEach(day => {
                const cls = isToday(day) ? 'today' : (isWeekend(day) ? 'weekend' : '');
                canvasHtml += `<div class="gantt-col-cell ${cls}" style="width:${pixelsPerDay}px;height:${ROW_H}px;float:left;"></div>`;
            });
            canvasHtml += `<a href="${task.url}" class="gantt-bar${overCls}"
                data-task-id="${task.id}"
                style="left:${leftPx}px;width:${widthPx}px;background:${task.color.bg};border:1.5px solid ${task.color.text}55;top:9px;">
                <div class="gantt-bar-progress" style="width:${progress}%;background:${task.color.text};"></div>
                ${showLabel ? `<span class="gantt-bar-label" style="color:${task.color.text};">${escHtml(task.title)}</span>` : ''}
                ${over ? '<span class="gantt-bar-overdue-tag">!</span>' : ''}
                ${showInit ? `<span class="gantt-bar-assignee" title="${escHtml(task.assignee)}">${escHtml(initials(task.assignee))}</span>` : ''}
            </a>`;
            canvasHtml += '</div>';
        });
    });

    if (todayOffset >= 0 && todayOffset <= days.length) {
        canvasHtml += `<div class="today-line" style="left:${todayOffset * pixelsPerDay}px;"></div>`;
    }

    const canvas = document.getElementById('ganttCanvas');
    canvas.style.width = totalW + 'px';
    canvas.innerHTML   = headerHtml + canvasHtml;

    // Attach tooltip events to bars
    canvas.querySelectorAll('.gantt-bar').forEach(bar => {
        const tid  = bar.dataset.taskId;
        const task = findTask(tid);
        if (!task) return;
        bar.addEventListener('mouseenter', e => showTooltip(e, task));
        bar.addEventListener('mouseleave', hideTooltip);
        bar.addEventListener('click', e => { hideTooltip(); });
    });

    syncScroll();
    updateZoomButtons();
}

// ── Find task in data ─────────────────────────────────────────────────
function findTask(id) {
    for (const proj of CHART_DATA) {
        const t = proj.tasks.find(t => String(t.id) === String(id));
        if (t) return { ...t, project: proj.name };
    }
    return null;
}

// ── Sync vertical scroll ──────────────────────────────────────────────
function syncScroll() {
    const right = document.getElementById('ganttRight');
    const left  = document.getElementById('ganttLeft');
    right.onscroll = () => { left.scrollTop = right.scrollTop; };
}

// ── Collapse / expand project ─────────────────────────────────────────
function toggleCollapse(projId) {
    collapsed[projId] = !collapsed[projId];
    // Toggle task rows in left sidebar
    document.querySelectorAll(`#ganttLeftContent .task-row[data-proj="${projId}"]`).forEach(r => {
        r.classList.toggle('collapsed', !!collapsed[projId]);
    });
    // Toggle task rows in canvas
    document.querySelectorAll(`#ganttCanvas .task-row[data-proj="${projId}"]`).forEach(r => {
        r.classList.toggle('collapsed', !!collapsed[projId]);
    });
    // Rotate chevron icon
    const icon = document.querySelector(`[data-proj-icon="${projId}"]`);
    if (icon) icon.classList.toggle('collapsed', !!collapsed[projId]);
    // Re-sync scroll after DOM change
    syncScroll();
}

// ── Zoom ──────────────────────────────────────────────────────────────
function changeZoom(delta) { pixelsPerDay = Math.min(60, Math.max(4, pixelsPerDay + delta)); render(); }
function setZoom(v)        { pixelsPerDay = v; render(); }
function updateZoomButtons() {
    document.querySelectorAll('[id^=zoom]').forEach(b => b.classList.remove('active'));
    if      (pixelsPerDay >= 24) document.getElementById('zoomDay')?.classList.add('active');
    else if (pixelsPerDay >= 12) document.getElementById('zoomWeek')?.classList.add('active');
    else                          document.getElementById('zoomMonth')?.classList.add('active');
}

// ── Filters ───────────────────────────────────────────────────────────
function filterProject(v) { activeProject = v; render(); }
function filterStatus(v)  { activeStatus  = v; render(); }

// ── Scroll to today ───────────────────────────────────────────────────
function scrollToToday() {
    const todayOffset = daysBetween(RANGE_START, TODAY);
    const right       = document.getElementById('ganttRight');
    right.scrollLeft  = Math.max(0, todayOffset * pixelsPerDay - right.clientWidth / 2);
}

// ── Info panel toggle ─────────────────────────────────────────────────
function toggleInfo() {
    const body    = document.getElementById('ganttInfoBody');
    const chevron = document.getElementById('ganttInfoChevron');
    const panel   = document.getElementById('ganttInfoPanel');
    const open    = body.style.display === 'none';
    body.style.display    = open ? 'block' : 'none';
    chevron.style.transform = open ? 'rotate(180deg)' : '';
    panel.querySelector('span.click-label') && (panel.querySelector('span.click-label').textContent = open ? 'Click to collapse' : 'Click to expand');
    // persist preference
    try { localStorage.setItem('ganttInfoOpen', open ? '1' : '0'); } catch(e){}
}

// ── Export menu ───────────────────────────────────────────────────────
function toggleExportMenu(e) {
    e.stopPropagation();
    const menu    = document.getElementById('exportMenu');
    const chevron = document.getElementById('exportChevron');
    const open    = menu.classList.toggle('open');
    chevron.style.transform = open ? 'rotate(180deg)' : '';
}
document.addEventListener('click', () => {
    document.getElementById('exportMenu')?.classList.remove('open');
    const ch = document.getElementById('exportChevron');
    if (ch) ch.style.transform = '';
});

// ── Export: PNG (canvas redraw — no DOM snapshot) ─────────────────────
function exportPNG() {
    document.getElementById('exportMenu').classList.remove('open');

    const days    = buildDays();
    const PPD     = pixelsPerDay;
    const ROW_H   = 44;
    const PROJ_H  = 36;
    const HDR_H   = 48;   // month row + day row
    const SIDE_W  = 260;

    let filtered = CHART_DATA;
    if (activeProject) filtered = filtered.filter(p => p.id == activeProject);
    if (activeStatus)  filtered = filtered.map(p => ({...p, tasks: p.tasks.filter(t => activeStatus === 'overdue' ? isOverdue(t) : t.status === activeStatus)})).filter(p => p.tasks.length);

    // Calculate total canvas height
    let totalRows = 0;
    filtered.forEach(p => { totalRows += 1 + (collapsed[p.id] ? 0 : p.tasks.length); });
    const totalH = HDR_H + totalRows * ROW_H;
    const totalW = SIDE_W + days.length * PPD;

    const cvs = document.createElement('canvas');
    cvs.width  = totalW;
    cvs.height = totalH;
    const ctx  = cvs.getContext('2d');
    ctx.imageSmoothingEnabled = true;

    // ── helpers ──
    function rect(x, y, w, h, fill, stroke, radius) {
        ctx.beginPath();
        if (radius) {
            ctx.roundRect(x, y, w, h, radius);
        } else {
            ctx.rect(x, y, w, h);
        }
        if (fill)   { ctx.fillStyle = fill;     ctx.fill(); }
        if (stroke) { ctx.strokeStyle = stroke; ctx.lineWidth = 1.5; ctx.stroke(); }
    }
    function text(str, x, y, font, color, maxW) {
        ctx.font      = font;
        ctx.fillStyle = color;
        ctx.fillText(str, x, y, maxW);
    }
    function line(x1, y1, x2, y2, color, width) {
        ctx.beginPath();
        ctx.strokeStyle = color;
        ctx.lineWidth   = width || 1;
        ctx.moveTo(x1, y1);
        ctx.lineTo(x2, y2);
        ctx.stroke();
    }

    // ── background ──
    rect(0, 0, totalW, totalH, '#ffffff');

    // ── sidebar background ──
    rect(0, 0, SIDE_W, totalH, '#FAFAFA');
    line(SIDE_W, 0, SIDE_W, totalH, '#E5E7EB', 1);

    // ── header row ──
    rect(0, 0, totalW, HDR_H, '#F8F9FA');
    line(0, HDR_H, totalW, HDR_H, '#E5E7EB', 2);

    // Sidebar header label
    rect(0, 0, SIDE_W, HDR_H, '#F8F9FA');
    text('PROJECT / TASK', 14, 30, '600 10px sans-serif', '#9CA3AF');

    // Month labels
    let curMonth = null, monthStartX = SIDE_W, monthCount = 0;
    days.forEach((day, i) => {
        const m = monthLabel(day);
        if (m !== curMonth) {
            if (curMonth !== null) {
                text(curMonth.toUpperCase(), monthStartX + 8, 18, '700 10px sans-serif', '#4F46E5', monthCount * PPD - 10);
                line(monthStartX, 0, monthStartX, HDR_H, '#E5E7EB');
            }
            curMonth = m; monthStartX = SIDE_W + i * PPD; monthCount = 1;
        } else { monthCount++; }
    });
    if (curMonth) text(curMonth.toUpperCase(), monthStartX + 8, 18, '700 10px sans-serif', '#4F46E5', monthCount * PPD - 10);

    // Day labels
    days.forEach((day, i) => {
        const x   = SIDE_W + i * PPD;
        const tod = isToday(day);
        const wkd = isWeekend(day);
        if (tod)       rect(x, 0, PPD, totalH, '#FFFBEB');
        else if (wkd)  rect(x, 26, PPD, HDR_H - 26, '#F9FAFB');
        if (PPD >= 14) {
            text(String(day.getDate()), x + PPD/2 - 4, HDR_H - 8, (tod ? '700' : '500') + ' 10px sans-serif', tod ? '#D97706' : '#9CA3AF');
        }
        line(x, 26, x, HDR_H, '#F3F4F6');
    });

    // ── rows ──
    let rowY = HDR_H;
    filtered.forEach(proj => {
        const overdueCount = proj.tasks.filter(isOverdue).length;

        // Project row
        rect(0, rowY, totalW, PROJ_H, '#F5F3FF');
        line(0, rowY + PROJ_H, totalW, rowY + PROJ_H, '#E5E7EB');
        text(proj.name.toUpperCase(), 30, rowY + 23, '700 11px sans-serif', '#4F46E5', SIDE_W - 90);
        if (overdueCount > 0) {
            const badge = `${proj.tasks.length} tasks · ⚠ ${overdueCount}`;
            text(badge, SIDE_W - 130, rowY + 23, '600 10px sans-serif', '#991B1B', 120);
        } else {
            text(`${proj.tasks.length} tasks`, SIDE_W - 80, rowY + 23, '600 10px sans-serif', '#4338CA', 70);
        }
        rowY += PROJ_H;

        if (collapsed[proj.id]) return;

        proj.tasks.forEach(task => {
            const over = isOverdue(task);

            // Row background
            if (over) rect(0, rowY, totalW, ROW_H, '#FFF5F5');
            line(0, rowY + ROW_H, totalW, rowY + ROW_H, '#F3F4F6');

            // Sidebar: color dot + title
            rect(26, rowY + ROW_H/2 - 5, 10, 10, task.color.bg, task.color.text + '55', 2);
            text(task.title, 44, rowY + ROW_H/2 + 5, (over ? '600' : '500') + ' 12px sans-serif', over ? '#EF4444' : '#374151', SIDE_W - 55);

            // Bar
            const startDate = new Date(task.start);
            const endDate   = new Date(task.end);
            const leftDays  = daysBetween(RANGE_START, startDate);
            const widthDays = Math.max(1, daysBetween(startDate, endDate) + 1);
            const barX      = SIDE_W + leftDays * PPD;
            const barW      = Math.max(PPD, widthDays * PPD);
            const barY      = rowY + 9;
            const barH      = 26;

            // Bar background
            rect(barX, barY, barW, barH, task.color.bg, over ? '#EF4444' : (task.color.text + '55'), 6);

            // Progress fill
            const progress = (STATUS_PROGRESS[task.status] ?? 0) / 100;
            if (progress > 0) {
                ctx.save();
                ctx.beginPath();
                ctx.roundRect(barX, barY, barW, barH, 6);
                ctx.clip();
                rect(barX, barY, barW * progress, barH, task.color.text + '44');
                ctx.restore();
            }

            // Bar label
            if (barW >= 50) {
                text(task.title, barX + 8, barY + 17, '600 10px sans-serif', task.color.text, barW - 36);
            }

            // Assignee initials circle
            if (barW >= 36) {
                const cx = barX + barW - 14;
                const cy = barY + barH / 2;
                ctx.beginPath();
                ctx.arc(cx, cy, 9, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(0,0,0,0.15)';
                ctx.fill();
                text(initials(task.assignee), cx - 5, cy + 4, '700 8px sans-serif', '#fff');
            }

            rowY += ROW_H;
        });
    });

    // ── Today line ──
    const todayX = SIDE_W + daysBetween(RANGE_START, TODAY) * PPD;
    line(todayX, HDR_H, todayX, totalH, '#F59E0B', 2);
    text('TODAY', todayX + 4, HDR_H + 14, '700 8px sans-serif', '#D97706');

    // ── Scale to A4 landscape at 150 DPI (1754 × 1240 px) ──────────────
    const A4_W = 1754;
    const A4_H = 1240;
    const a4   = document.createElement('canvas');
    a4.width   = A4_W;
    a4.height  = A4_H;
    const a4ctx = a4.getContext('2d');
    a4ctx.fillStyle = '#ffffff';
    a4ctx.fillRect(0, 0, A4_W, A4_H);

    // Fit the drawn chart into A4, maintaining aspect ratio, centred
    const scale   = Math.min(A4_W / totalW, A4_H / totalH);
    const dstW    = Math.round(totalW * scale);
    const dstH    = Math.round(totalH * scale);
    const offsetX = Math.round((A4_W - dstW) / 2);
    const offsetY = Math.round((A4_H - dstH) / 2);
    a4ctx.drawImage(cvs, offsetX, offsetY, dstW, dstH);

    // ── Download ──
    const today2  = new Date();
    const dateStr = today2.getFullYear() + '-' + String(today2.getMonth()+1).padStart(2,'0') + '-' + String(today2.getDate()).padStart(2,'0');
    const link    = document.createElement('a');
    link.download = `gantt-chart-${dateStr}.png`;
    link.href     = a4.toDataURL('image/png');
    link.click();
}

// ── Export: PDF ──────────────────────────────────────────────────────
function exportPDF() {
    document.getElementById('exportMenu').classList.remove('open');
    window.location.href = '{{ route('admin.gantt.export.pdf') }}';
}

// ── Export: CSV ───────────────────────────────────────────────────────
function exportCSV() {
    document.getElementById('exportMenu').classList.remove('open');
    window.location.href = '{{ route('admin.gantt.export.csv') }}';
}

// ── Init ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Restore info panel state
    try {
        if (localStorage.getItem('ganttInfoOpen') === '1') {
            document.getElementById('ganttInfoBody').style.display = 'block';
            document.getElementById('ganttInfoChevron').style.transform = 'rotate(180deg)';
        }
    } catch(e){}
    render();
    setTimeout(scrollToToday, 80);
});
</script>
@endsection

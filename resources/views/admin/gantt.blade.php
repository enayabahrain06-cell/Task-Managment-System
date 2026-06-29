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

/* ── Sync scroll (left tracks right vertically) ── */
.gantt-left-header  { height:56px; border-bottom:1px solid #E5E7EB; background:#F8F9FA; display:flex; align-items:center; padding:0 14px; flex-shrink:0; position:sticky; top:0; z-index:3; }
.gantt-left-content { }

/* ── Timeline header ── */
.gantt-timeline-header { position:sticky; top:0; z-index:4; background:#F8F9FA; border-bottom:2px solid #E5E7EB; }
.gantt-timeline-row    { position:relative; display:flex; }

/* ── Row cells ── */
.gantt-row        { display:flex; align-items:center; height:44px; border-bottom:1px solid #F3F4F6; }
.gantt-row:last-child { border-bottom:none; }
.gantt-row.project-row { height:34px; background:#F5F3FF; border-bottom:1px solid #E5E7EB; }
.gantt-row.project-row .row-label { font-size:11px; font-weight:700; color:#4F46E5; text-transform:uppercase; letter-spacing:.05em; padding:0 14px; display:flex; align-items:center; gap:7px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.gantt-row.task-row   .row-label  { font-size:12px; font-weight:500; color:#374151; padding:0 14px 0 26px; display:flex; align-items:center; gap:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* ── Canvas cell = one row on the right ── */
.gantt-canvas-row     { display:flex; align-items:center; height:44px; border-bottom:1px solid #F3F4F6; position:relative; }
.gantt-canvas-row.project-row { height:34px; background:#F5F3FF; border-bottom:1px solid #E5E7EB; }

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
    height:24px;
    border-radius:6px;
    display:flex;
    align-items:center;
    overflow:hidden;
    cursor:pointer;
    transition:filter .15s, transform .1s;
    z-index:2;
    text-decoration:none;
    box-shadow:0 1px 3px rgba(0,0,0,.08);
}
.gantt-bar:hover  { filter:brightness(.93); transform:scaleY(1.05); z-index:5; }
.gantt-bar-label  { font-size:10px; font-weight:600; padding:0 8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; pointer-events:none; }

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

/* ── Empty state ── */
.gantt-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:300px; color:#9CA3AF; }

/* ── Scrollbar ── */
.gantt-right::-webkit-scrollbar { width:6px; height:6px; }
.gantt-right::-webkit-scrollbar-track { background:#F9FAFB; }
.gantt-right::-webkit-scrollbar-thumb { background:#D1D5DB; border-radius:3px; }
.gantt-left::-webkit-scrollbar  { width:3px; }
.gantt-left::-webkit-scrollbar-thumb { background:#E5E7EB; border-radius:3px; }
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

<div style="padding:0 24px 24px;">

@if(count($chartData) === 0)
<div class="gantt-empty" style="background:#fff;border:1px solid #E5E7EB;border-radius:14px;">
    <i class="fas fa-bars-progress" style="font-size:40px;margin-bottom:12px;color:#D1D5DB;"></i>
    <p style="font-weight:700;font-size:15px;color:#374151;margin:0 0 6px;">No tasks with deadlines found</p>
    <p style="font-size:12px;color:#9CA3AF;margin:0;">Assign deadlines to tasks to see them on the Gantt chart.</p>
</div>
@else

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
        </div>
    </div>

    {{-- Body --}}
    <div class="gantt-body" id="ganttBody">

        {{-- Left sidebar --}}
        <div class="gantt-left" id="ganttLeft">
            <div class="gantt-left-header">
                <span style="font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Project / Task</span>
            </div>
            <div class="gantt-left-content" id="ganttLeftContent">
                {{-- Rows injected by JS --}}
            </div>
        </div>

        {{-- Right scrollable timeline --}}
        <div class="gantt-right" id="ganttRight">
            <div id="ganttCanvas" style="position:relative;">
                {{-- Timeline header + rows rendered by JS --}}
            </div>
        </div>

    </div>
</div>

@endif
</div>

<script>
// ── Data ──────────────────────────────────────────────────────────────
const CHART_DATA    = @json($chartData);
const RANGE_START   = new Date('{{ $rangeStart }}');
const RANGE_END     = new Date('{{ $rangeEnd }}');
const TODAY         = new Date();
TODAY.setHours(0,0,0,0);

// ── State ─────────────────────────────────────────────────────────────
let pixelsPerDay    = 28;    // default: day view
let activeProject   = '';

// ── Helpers ───────────────────────────────────────────────────────────
function daysBetween(a, b) {
    return Math.round((b - a) / 86400000);
}
function addDays(d, n) {
    const r = new Date(d); r.setDate(r.getDate() + n); return r;
}
function isWeekend(d) { return d.getDay() === 0 || d.getDay() === 6; }
function isToday(d)   { return d.toDateString() === TODAY.toDateString(); }

function formatDate(d) {
    return d.toLocaleDateString('en-GB', {day:'2-digit',month:'short'});
}
function monthLabel(d) {
    return d.toLocaleDateString('en-GB', {month:'short', year:'numeric'});
}

// ── Build column index ─────────────────────────────────────────────────
function buildDays() {
    const days = [];
    let cur = new Date(RANGE_START);
    while (cur <= RANGE_END) {
        days.push(new Date(cur));
        cur.setDate(cur.getDate() + 1);
    }
    return days;
}

// ── Render ─────────────────────────────────────────────────────────────
function render() {
    const days     = buildDays();
    const totalW   = days.length * pixelsPerDay;
    const ROW_H    = 44;
    const PROJ_H   = 34;

    const filtered = activeProject
        ? CHART_DATA.filter(p => p.id == activeProject)
        : CHART_DATA;

    if (!filtered.length) return;

    // ── Left sidebar ──
    const leftHtml = filtered.map(proj => {
        const taskRows = proj.tasks.map(t =>
            `<div class="gantt-row task-row" data-task="${t.id}" style="height:${ROW_H}px;">
                <div class="row-label" title="${escHtml(t.title)}">
                    <span class="legend-dot" style="background:${t.color.bg};border:1px solid ${t.color.text}44;flex-shrink:0;"></span>
                    <span style="overflow:hidden;text-overflow:ellipsis;">${escHtml(t.title)}</span>
                </div>
            </div>`
        ).join('');
        return `<div class="gantt-row project-row" data-project="${proj.id}">
                    <div class="row-label"><i class="fas fa-diagram-project" style="font-size:10px;"></i>${escHtml(proj.name)}</div>
                </div>${taskRows}`;
    }).join('');
    document.getElementById('ganttLeftContent').innerHTML = leftHtml;

    // ── Timeline header ──
    // Month groups
    let monthGroupHtml = '<div style="display:flex;height:26px;border-bottom:1px solid #E5E7EB;">';
    let curMonth = null, monthStart = 0, monthCount = 0;
    days.forEach((day, i) => {
        const m = day.getMonth() + '-' + day.getFullYear();
        if (m !== curMonth) {
            if (curMonth !== null) {
                monthGroupHtml += `<div class="header-month" style="width:${monthCount * pixelsPerDay}px;min-width:0;">${curMonth.replace('-', ' ').replace('-',' ')}</div>`;
            }
            curMonth = monthLabel(day);
            monthStart = i;
            monthCount = 1;
        } else {
            monthCount++;
        }
    });
    if (curMonth) monthGroupHtml += `<div class="header-month" style="width:${monthCount * pixelsPerDay}px;min-width:0;">${curMonth}</div>`;
    monthGroupHtml += '</div>';

    // Day labels
    let dayHtml = '<div style="display:flex;height:22px;">';
    days.forEach(day => {
        const cls = isToday(day) ? 'today' : (isWeekend(day) ? 'weekend' : '');
        const label = pixelsPerDay >= 14
            ? `<div class="header-day ${cls}" style="width:${pixelsPerDay}px;">${day.getDate()}</div>`
            : (day.getDate() === 1
                ? `<div class="header-day ${cls}" style="width:${pixelsPerDay}px;font-size:9px;">${day.getDate()}</div>`
                : `<div class="header-day ${cls}" style="width:${pixelsPerDay}px;"></div>`);
        dayHtml += label;
    });
    dayHtml += '</div>';

    const headerHtml = `<div class="gantt-timeline-header" style="width:${totalW}px;">${monthGroupHtml}${dayHtml}</div>`;

    // ── Canvas rows ──
    let canvasHtml = '';
    const todayOffset = daysBetween(RANGE_START, TODAY);

    filtered.forEach(proj => {
        // Project row (spacer)
        canvasHtml += `<div class="gantt-canvas-row project-row" style="width:${totalW}px;height:${PROJ_H}px;">`;
        // Background grid cells
        days.forEach((day, i) => {
            const cls = isToday(day) ? 'today' : (isWeekend(day) ? 'weekend' : '');
            canvasHtml += `<div class="gantt-col-cell ${cls}" style="width:${pixelsPerDay}px;height:${PROJ_H}px;float:left;"></div>`;
        });
        canvasHtml += '</div>';

        proj.tasks.forEach(task => {
            const startDate  = new Date(task.start);
            const endDate    = new Date(task.end);
            const leftDays   = daysBetween(RANGE_START, startDate);
            const widthDays  = Math.max(1, daysBetween(startDate, endDate) + 1);
            const leftPx     = leftDays * pixelsPerDay;
            const widthPx    = Math.max(pixelsPerDay, widthDays * pixelsPerDay);

            canvasHtml += `<div class="gantt-canvas-row task-row" style="width:${totalW}px;height:${ROW_H}px;">`;
            // Background columns
            days.forEach(day => {
                const cls = isToday(day) ? 'today' : (isWeekend(day) ? 'weekend' : '');
                canvasHtml += `<div class="gantt-col-cell ${cls}" style="width:${pixelsPerDay}px;height:${ROW_H}px;float:left;"></div>`;
            });
            // Task bar
            const barLabel = widthPx >= 60 ? escHtml(task.title) : '';
            canvasHtml += `<a href="${task.url}" class="gantt-bar"
                style="left:${leftPx}px;width:${widthPx}px;background:${task.color.bg};border:1.5px solid ${task.color.text}55;top:10px;"
                title="${escHtml(task.title)} · ${task.status} · ${task.assignee}&#10;${formatDate(startDate)} → ${formatDate(endDate)}">
                <span class="gantt-bar-label" style="color:${task.color.text};">${barLabel}</span>
            </a>`;
            canvasHtml += '</div>';
        });
    });

    // Today line
    if (todayOffset >= 0 && todayOffset <= days.length) {
        canvasHtml += `<div class="today-line" style="left:${todayOffset * pixelsPerDay}px;"></div>`;
    }

    const canvas = document.getElementById('ganttCanvas');
    canvas.style.width  = totalW + 'px';
    canvas.innerHTML    = headerHtml + canvasHtml;

    // Re-sync vertical scroll
    syncScroll();

    // Highlight zoom button
    document.querySelectorAll('[id^=zoom]').forEach(b => b.classList.remove('active'));
    if      (pixelsPerDay >= 24) document.getElementById('zoomDay')?.classList.add('active');
    else if (pixelsPerDay >= 12) document.getElementById('zoomWeek')?.classList.add('active');
    else                          document.getElementById('zoomMonth')?.classList.add('active');
}

// ── Escape HTML ─────────────────────────────────────────────────────────
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Sync left sidebar scroll with right panel ──────────────────────────
function syncScroll() {
    const right = document.getElementById('ganttRight');
    const left  = document.getElementById('ganttLeft');
    right.addEventListener('scroll', () => {
        left.scrollTop = right.scrollTop;
    }, { passive: true });
}

// ── Zoom ──────────────────────────────────────────────────────────────
function changeZoom(delta) {
    pixelsPerDay = Math.min(60, Math.max(4, pixelsPerDay + delta));
    render();
}
function setZoom(v) {
    pixelsPerDay = v;
    render();
}

// ── Filter ────────────────────────────────────────────────────────────
function filterProject(v) {
    activeProject = v;
    render();
}

// ── Scroll to today ───────────────────────────────────────────────────
function scrollToToday() {
    const days       = buildDays();
    const todayOffset = daysBetween(RANGE_START, TODAY);
    const right       = document.getElementById('ganttRight');
    const viewW       = right.clientWidth;
    right.scrollLeft  = Math.max(0, todayOffset * pixelsPerDay - viewW / 2);
}

// ── Init ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    render();
    // Auto-scroll so today is visible after a tiny delay (layout settled)
    setTimeout(scrollToToday, 80);
});
</script>
@endsection

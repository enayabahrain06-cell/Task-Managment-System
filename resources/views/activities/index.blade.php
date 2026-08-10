@extends('layouts.app')

@section('title', 'Activities')

@section('content')
<style>
#activityFeed::-webkit-scrollbar { width: 4px; }
#activityFeed::-webkit-scrollbar-track { background: transparent; }
#activityFeed::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 99px; }
#activityFeed::-webkit-scrollbar-thumb:hover { background: #C7D2FE; }

/* ══ Mobile (320–768px) premium pass — desktop untouched ══ */
@media (max-width: 768px) {
    .act-release-btn { display: none !important; }
    .act-search-form { flex: 1 1 100% !important; max-width: none !important; order: -1; }
    .act-filter-pill, .act-action-btn, .act-sort-btn, .act-clear-btn { min-height: 40px; padding-top: 8px !important; padding-bottom: 8px !important; }
    .act-team-sidebar { order: 2; }
    .act-timeline-col { order: 1; }
    .act-react-btn, .act-reply-btn, .act-view-btn { min-height: 40px; }
    .act-emoji-picker { left: 8px !important; right: 8px !important; width: auto !important; max-width: calc(100vw - 16px); }
    .act-modal-input { min-height: 46px; }
    .act-modal-cancel-btn, .act-modal-submit-btn { min-height: 46px; flex: 1; justify-content: center; }
    .act-task-title-link { max-width: 140px !important; }

    /* ── Card design-system pass: unify radius/shadow/spacing across activity cards ── */
    .act-filterbar,
    .act-team-sidebar,
    .act-content-card,
    .act-release-card {
        border-radius: var(--mob-r-lg) !important;
        box-shadow: var(--mob-shadow-1) !important;
        border-color: #F3F4F6 !important;
    }
    .act-filterbar { padding: var(--mob-sp-1) var(--mob-sp-2) !important; }
    .act-team-sidebar > .p-3 { padding: var(--mob-sp-2) !important; }
    .act-content-card { margin-bottom: 2px; }
    .act-timeline-dot { box-shadow: var(--mob-shadow-1) !important; }
    .act-reaction-pill { min-height: 26px; padding: 4px 10px !important; }

    /* Empty state — box it like every other card */
    .act-empty-card {
        background: #fff;
        border-radius: var(--mob-r-lg);
        box-shadow: var(--mob-shadow-1);
        margin: 0 2px;
        padding-left: var(--mob-sp-2);
        padding-right: var(--mob-sp-2);
    }
}
@media (max-width: 480px) {
    #activityFeed { max-height: calc(100vh - 320px) !important; }
}

/* ══════════════════════════════════════════════════════════════
   Mobile "Activity Mobile" design-system pass (<=768px only).
   Restyles the real feed to the agency-app spec — same backend
   data/actions, new indigo visual language. Desktop untouched.
   ══════════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
    .act-m-root { --am-indigo:#5B4BE1; --am-indigo-tint:#EEEDFD; --am-indigo-dark:#4C3FCB;
        --am-bg:#F6F7FB; --am-border:#EDEFF5; --am-text:#1B2130; --am-text2:#5C6478;
        --am-muted:#8A93A6; --am-faint:#A8AFC0; }

    /* Header */
    .act-m-root .act-page-title { font-size:23px !important; font-weight:800 !important; letter-spacing:-0.5px !important; color:var(--am-text) !important; }
    .act-m-root .act-page-subtitle { font-size:12.5px !important; font-weight:500 !important; color:var(--am-muted) !important; }

    /* Search + filter trigger */
    .act-m-root .act-filterbar { background:transparent !important; border:none !important; box-shadow:none !important; padding:0 !important; gap:10px !important; }
    .act-m-root .act-search-wrap { flex:1 1 auto; display:flex; align-items:center; height:42px; border-radius:13px !important; background:#fff !important; border:1px solid var(--am-border) !important; }
    .act-m-root .act-search-wrap i { left:14px !important; }
    .act-m-root .act-search-wrap input { font-size:14px !important; font-weight:600 !important; color:var(--am-text) !important; background:transparent !important; border:none !important; box-shadow:none !important; padding:0 30px 0 34px !important; }
    .act-m-root .act-search-wrap input::placeholder { color:#98A0B4; font-weight:500; }
    .act-m-root .act-filter-trigger { flex:0 0 auto; width:42px; height:42px; border-radius:13px; background:var(--am-indigo-tint); color:var(--am-indigo); display:flex !important; align-items:center; justify-content:center; position:relative; }
    .act-m-root .act-filter-trigger .act-filter-badge { position:absolute; top:-4px; right:-4px; min-width:16px; height:16px; padding:0 4px; border-radius:99px; background:#F04438; color:#fff; font-size:9.5px; font-weight:800; display:flex; align-items:center; justify-content:center; border:1.5px solid #fff; }
    .act-m-root .act-desktop-only,
    .act-m-root .act-filterbar > .w-px { display:none !important; }

    /* Date chip row */
    .act-m-root .act-chip-row { display:flex; gap:7px; overflow-x:auto; padding:2px 2px 2px; margin:0; -webkit-overflow-scrolling:touch; }
    .act-m-root .act-chip-row::-webkit-scrollbar { display:none; }
    .act-m-root .act-chip { flex:0 0 auto; height:33px; padding:0 14px; border-radius:99px; display:flex; align-items:center; font-size:12.5px; font-weight:700; background:#fff; color:var(--am-text2); border:1px solid var(--am-border); }
    .act-m-root .act-chip.is-active { background:var(--am-indigo); color:#fff; border-color:var(--am-indigo); }

    /* Count + sort row */
    .act-m-root .act-count-row { display:flex !important; align-items:center; justify-content:space-between; padding:15px 1px 4px; }
    .act-m-root .act-count-label { font-size:11px; font-weight:800; letter-spacing:0.5px; color:var(--am-faint); text-transform:uppercase; }
    .act-m-root .act-sort-link { display:flex; align-items:center; gap:5px; font-size:12px; font-weight:700; color:var(--am-indigo); }

    /* Day divider */
    .act-m-root .act-day-divider { display:flex !important; align-items:center; gap:10px; padding:14px 1px 8px; }
    .act-m-root .act-day-divider .act-day-label { font-size:12px; font-weight:800; color:var(--am-text); }
    .act-m-root .act-day-divider .act-day-rule { flex:1; height:1px; background:#E7EAF2; }
    .act-m-root .act-day-divider .act-day-count { font-size:11px; font-weight:700; color:var(--am-faint); }

    /* Cards + timeline rail recolor */
    .act-m-root .act-content-card,
    .act-m-root .act-release-card { border-radius:15px !important; box-shadow:0 1px 2px rgba(27,33,48,.04) !important; border-color:var(--am-border) !important; }
    .act-m-root .act-timeline-dot { background:var(--m-bg) !important; box-shadow:none !important; }
    .act-m-root .act-timeline-dot i { color:var(--m-fg) !important; }
    .act-m-root .act-badge { background:var(--m-bg) !important; color:var(--m-fg) !important; }
    .act-m-root .act-avatar { background:var(--m-avatar) !important; }
    .act-m-root .act-time-col { display:flex !important; }
    .act-m-root .act-task-title-link { color:var(--am-indigo) !important; }

    /* Footer actions */
    .act-m-root .act-heart-btn { display:flex !important; align-items:center; gap:5px; }
    .act-m-root .act-heart-btn.is-liked svg { fill:#EF4444; stroke:#EF4444; }
    .act-m-root .act-heart-count { font-size:12px; font-weight:700; color:var(--am-muted); }
    .act-m-root .act-view-btn { color:var(--am-indigo) !important; }

    /* Floating Release FAB */
    .act-m-root .act-fab { display:flex !important; position:fixed; right:18px; bottom:calc(78px + env(safe-area-inset-bottom, 0px) + 16px);
        height:50px; padding:0 20px; border-radius:16px; background:var(--am-indigo); color:#fff;
        align-items:center; gap:8px; font-size:14px; font-weight:800; box-shadow:0 10px 24px rgba(91,75,225,.36); z-index:46; }

    /* Bottom sheet */
    .act-m-root .act-sheet-backdrop { position:fixed; inset:0; background:rgba(17,24,39,.45); z-index:9998; display:flex; align-items:flex-end; }
    .act-m-root .act-sheet { width:100%; background:#fff; border-radius:26px 26px 0 0; padding:12px 20px calc(22px + env(safe-area-inset-bottom, 0px)); max-height:80vh; overflow-y:auto; animation:actSheetUp .26s cubic-bezier(.32,.72,0,1) both; }
    @keyframes actSheetUp { from { transform:translateY(100%); } to { transform:translateY(0); } }
    .act-m-root .act-sheet-handle { width:36px; height:4px; background:#E5E7EB; border-radius:99px; margin:0 auto 14px; }
    .act-m-root .act-sheet-label { font-size:11px; font-weight:800; letter-spacing:0.5px; color:var(--am-faint); text-transform:uppercase; margin:16px 0 8px; }
    .act-m-root .act-sheet-chip { padding:9px 14px; border-radius:99px; font-size:12.5px; font-weight:700; background:#F4F5FA; color:var(--am-text2); border:1px solid var(--am-border); }
    .act-m-root .act-sheet-chip.is-active { background:var(--am-indigo); color:#fff; border-color:var(--am-indigo); }
    .act-m-root .act-sheet-reset { flex:1; height:46px; border-radius:13px; background:#F4F5FA; color:var(--am-text2); font-size:13.5px; font-weight:700; display:flex; align-items:center; justify-content:center; }
    .act-m-root .act-sheet-submit { flex:2; height:46px; border-radius:13px; background:var(--am-indigo); color:#fff; font-size:13.5px; font-weight:700; display:flex; align-items:center; justify-content:center; border:none; }
}
</style>

@php
    $currentAction    = request('action', '');
    $currentDateRange = request('date_range', '');
    $currentSort      = request('sort', 'newest');
    $searchQuery      = request('search', '');
    $hasFilters       = $currentAction || $currentDateRange || $currentSort !== 'newest' || $searchQuery;
    $activeFilterCount = ($currentAction ? 1 : 0) + ($currentDateRange ? 1 : 0);

    function actUrl($params) {
        $base = array_merge(request()->only(['user_id','action','date_range','sort','search']), $params);
        $base = array_filter($base, fn($v) => $v !== '' && $v !== null);
        return route('activities.index', $base);
    }

    // Mobile-only recolor: groups the ~30 TaskLog action keys into the
    // 6 tint families used by the Activity Mobile spec. Desktop keeps
    // TaskLog::actionStyle()'s original per-action colors untouched.
    function mobileActionTint($action) {
        return match(true) {
            str_contains($action, 'comment')                                                       => ['#FEF2E6', '#B4661A'],
            str_contains($action, 'revision') || str_contains($action, 'deadline')
                || str_contains($action, 'transferred') || str_contains($action, 'deleted')         => ['#FDECEC', '#C3372B'],
            str_contains($action, 'paused') || str_contains($action, 'archived')
                || str_contains($action, 'draft') || $action === 'task_updated'                     => ['#F1F3F8', '#5C6478'],
            str_contains($action, 'deliver') || str_contains($action, 'timer_started')
                || str_contains($action, 'completed') || str_contains($action, 'posted')             => ['#E4F7EE', '#0E8B5F'],
            str_contains($action, 'view') || str_contains($action, 'assigned')
                || str_contains($action, 'notified') || str_contains($action, 'customer')            => ['#E8F4FE', '#0E7FBF'],
            default                                                                                  => ['#EEEDFD', '#4C3FCB'], // approved / released / created / etc.
        };
    }
    $mobileAvatarPalette = ['#12A87A', '#F09022', '#5B4BE1', '#E0457B', '#0E8FD1'];

    // Day-group counts for the mobile "Today ── N events" dividers (current page only).
    $mobileDayCounts = [];
    foreach ($activities as $l) {
        $d = $l->created_at->isToday() ? 'Today' : ($l->created_at->isYesterday() ? 'Yesterday' : $l->created_at->format(config('app.date_format', 'M d, Y')));
        $mobileDayCounts[$d] = ($mobileDayCounts[$d] ?? 0) + 1;
    }
@endphp

<div class="act-m-root" x-data="{ releaseOpen: false, filterOpen: false, selAction: '{{ $currentAction }}', selRange: '{{ $currentDateRange }}' }">

{{-- ══ Page Header ══ --}}
<div class="flex items-start justify-between mb-5 gap-4">
    <div>
        <h1 class="act-page-title text-2xl font-bold text-gray-900">Activity Feed</h1>
        <p class="act-page-subtitle text-sm text-gray-500 mt-0.5">
            {{ $isPrivileged ? 'All team activity across every task and project' : 'Your personal activity timeline' }}
        </p>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        <div class="hidden sm:flex items-center gap-1.5 bg-white border border-gray-200 rounded-xl px-3.5 py-2 shadow-sm">
            <i class="fa fa-bolt text-indigo-500 text-xs"></i>
            <span class="text-sm font-bold text-gray-900">{{ number_format($activities->total()) }}</span>
            <span class="text-xs text-gray-400">{{ $hasFilters ? 'found' : 'activities' }}</span>
        </div>
        @if($isPrivileged)
        <button @click="releaseOpen=true"
                class="act-release-btn flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-sm font-semibold px-4 py-2 rounded-xl transition shadow-sm">
            <i class="fa fa-rocket text-xs"></i> Release
        </button>
        {{-- Mobile floating Release FAB (hidden on desktop; opens the same release modal) --}}
        <button @click="releaseOpen=true" class="act-fab hidden">
            <i class="fa fa-rocket" style="font-size:15px;"></i> Release
        </button>
        @endif
    </div>
</div>

{{-- ══ Filter Bar ══ --}}
<div class="act-filterbar bg-white border border-gray-200 rounded-2xl shadow-sm p-3 mb-5 flex flex-wrap items-center gap-2">

    {{-- Search --}}
    <form method="GET" action="{{ route('activities.index') }}" id="actSearchForm" class="act-search-form flex-1 min-w-44 max-w-64">
        @foreach(request()->only(['user_id','action','date_range','sort']) as $k => $v)
            @if($v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
        <div class="relative act-search-wrap">
            <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Search activity"
                   class="w-full pl-8 pr-7 py-1.5 text-sm bg-gray-50 border border-gray-200 rounded-lg outline-none focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition"
                   oninput="clearTimeout(window._actSt); window._actSt = setTimeout(() => document.getElementById('actSearchForm').submit(), 450)">
            @if($searchQuery)
            <a href="{{ actUrl(['search'=>'']) }}" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 transition">
                <i class="fa fa-times-circle text-xs"></i>
            </a>
            @endif
        </div>
    </form>

    {{-- Mobile: filter icon opens the Filters bottom sheet (Action + Date Range) --}}
    <button type="button" @click="filterOpen=true" class="act-filter-trigger hidden">
        <i class="fa fa-sliders" style="font-size:15px;"></i>
        @if($activeFilterCount > 0)
        <span class="act-filter-badge">{{ $activeFilterCount }}</span>
        @endif
    </button>

    <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>

    {{-- Date range pills --}}
    <div class="act-chip-row flex items-center gap-1 flex-wrap">
        @foreach([''=>'All','today'=>'Today','yesterday'=>'Yesterday','week'=>'Last 7 Days','month'=>'This month'] as $val => $lbl)
        <a href="{{ actUrl(['date_range' => $val]) }}"
           class="act-filter-pill act-chip px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentDateRange === $val ? 'bg-indigo-600 text-white shadow-sm is-active' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800' }}">
            {{ $lbl }}
        </a>
        @endforeach
    </div>

    <div class="w-px h-6 bg-gray-200 hidden sm:block act-desktop-only"></div>

    {{-- Action type dropdown (desktop) --}}
    <div x-data="{ open: false }" class="relative act-desktop-only">
        <button @click="open=!open" @click.outside="open=false"
                class="act-action-btn flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentAction ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800' }}">
            <i class="fa fa-tag text-[10px]"></i>
            {{ $currentAction ? ucwords(str_replace('_', ' ', $currentAction)) : 'Action' }}
            <i class="fa fa-chevron-down text-[9px] opacity-50 transition-transform" :style="open && 'transform:rotate(180deg)'"></i>
        </button>
        <div x-show="open" x-cloak @click.outside="open=false"
             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="absolute left-0 top-full mt-1.5 w-52 bg-white border border-gray-200 rounded-xl shadow-xl z-50 py-1 overflow-auto max-h-64">
            <a href="{{ actUrl(['action'=>'']) }}"
               class="flex items-center gap-2 px-3 py-2 text-xs font-medium transition hover:bg-indigo-50 {{ $currentAction==='' ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700' }}">
                <i class="fa fa-layer-group text-[10px] text-gray-400 w-3"></i> All Actions
            </a>
            @foreach($actionTypes as $action)
            <a href="{{ actUrl(['action'=>$action]) }}"
               class="flex items-center gap-2 px-3 py-2 text-xs font-medium transition hover:bg-indigo-50 {{ $currentAction===$action ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700' }}">
                <i class="fa fa-circle text-[5px] text-gray-300 w-3"></i> {{ ucwords(str_replace('_', ' ', $action)) }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- Sort toggle (desktop; duplicated compactly in the mobile count row below) --}}
    <a href="{{ actUrl(['sort' => $currentSort === 'newest' ? 'oldest' : 'newest']) }}"
       class="act-sort-btn act-desktop-only flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition">
        <i class="fa {{ $currentSort === 'oldest' ? 'fa-arrow-up-wide-short' : 'fa-arrow-down-wide-short' }} text-[10px]"></i>
        {{ $currentSort === 'oldest' ? 'Oldest first' : 'Newest first' }}
    </a>

    @if($hasFilters)
    <a href="{{ route('activities.index', array_filter(['user_id' => request('user_id')])) }}"
       class="act-clear-btn act-desktop-only ml-auto flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-500 hover:bg-red-50 border border-red-200 transition">
        <i class="fa fa-times text-[10px]"></i> Clear
    </a>
    @endif
</div>

{{-- Mobile: N activities · sort toggle row --}}
<div class="act-count-row hidden">
    <span class="act-count-label">{{ number_format($activities->total()) }} {{ Str::plural('activity', $activities->total()) }}</span>
    <a href="{{ actUrl(['sort' => $currentSort === 'newest' ? 'oldest' : 'newest']) }}" class="act-sort-link">
        <i class="fa {{ $currentSort === 'oldest' ? 'fa-arrow-up-wide-short' : 'fa-arrow-down-wide-short' }}" style="font-size:12px;"></i>
        {{ $currentSort === 'oldest' ? 'Oldest first' : 'Newest first' }}
    </a>
</div>

{{-- Mobile: Filters bottom sheet (Action type + Date range) — real GET filters, no backend change --}}
<div x-show="filterOpen" x-cloak class="act-sheet-backdrop hidden" @click.self="filterOpen=false" x-transition.opacity>
    <form method="GET" action="{{ route('activities.index') }}" class="act-sheet">
        @foreach(request()->only(['user_id','search','sort']) as $k => $v)
            @if($v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
        <input type="hidden" name="action" :value="selAction">
        <input type="hidden" name="date_range" :value="selRange">

        <div class="act-sheet-handle"></div>
        <div class="flex items-center justify-between">
            <span style="font-size:16px;font-weight:800;color:#1B2130;">Filters</span>
            <button type="button" @click="filterOpen=false" style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;color:#6B7280;">
                <i class="fa fa-times"></i>
            </button>
        </div>

        <div class="act-sheet-label">Action Type</div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <button type="button" @click="selAction=''" class="act-sheet-chip" :class="selAction==='' && 'is-active'">All</button>
            @foreach($actionTypes as $action)
            <button type="button" @click="selAction='{{ $action }}'" class="act-sheet-chip" :class="selAction==='{{ $action }}' && 'is-active'">{{ ucwords(str_replace('_', ' ', $action)) }}</button>
            @endforeach
        </div>

        <div class="act-sheet-label">Date Range</div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @foreach([''=>'All','today'=>'Today','yesterday'=>'Yesterday','week'=>'Last 7 days','month'=>'This month'] as $val => $lbl)
            <button type="button" @click="selRange='{{ $val }}'" class="act-sheet-chip" :class="selRange==='{{ $val }}' && 'is-active'">{{ $lbl }}</button>
            @endforeach
        </div>

        <div style="display:flex;gap:10px;margin-top:22px;">
            <a href="{{ actUrl(['action'=>'','date_range'=>'']) }}" class="act-sheet-reset">Reset</a>
            <button type="submit" class="act-sheet-submit">Show results</button>
        </div>
    </form>
</div>

{{-- ══ Release Modal ══ --}}
@if($isPrivileged)
<div x-show="releaseOpen" x-cloak style="position:fixed;inset:0;z-index:9999;">
    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;">
        <div @click="releaseOpen=false" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(4px);"></div>
        <div style="position:relative;width:100%;max-width:500px;background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,.2);overflow:hidden;">
            <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;">
                        <i class="fa fa-rocket" style="color:#6366F1;font-size:15px;"></i>
                    </div>
                    <div>
                        <h2 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Publish Release</h2>
                        <p style="font-size:11px;color:#9CA3AF;margin:0;">Announce a new release to the team</p>
                    </div>
                </div>
                <button @click="releaseOpen=false" style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('activities.release') }}" style="padding:20px 24px 24px;">
                @csrf
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Release Title <span style="color:#EF4444;">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Website Redesign Launch"
                           class="act-modal-input"
                           style="width:100%;padding:9px 12px;font-size:13px;border:1.5px solid #E5E7EB;border-radius:9px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Version <span style="font-weight:400;color:#9CA3AF;">(optional)</span></label>
                    <input type="text" name="version" placeholder="e.g. v2.1.0"
                           class="act-modal-input"
                           style="width:100%;padding:9px 12px;font-size:13px;border:1.5px solid #E5E7EB;border-radius:9px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Release Notes <span style="font-weight:400;color:#9CA3AF;">(optional)</span></label>
                    <textarea name="description" rows="4" placeholder="Describe what's included in this release…"
                              style="width:100%;padding:9px 12px;font-size:13px;border:1.5px solid #E5E7EB;border-radius:9px;outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;"
                              onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" @click="releaseOpen=false" class="act-modal-cancel-btn"
                            style="padding:9px 18px;font-size:13px;font-weight:600;border:1.5px solid #E5E7EB;border-radius:9px;background:#fff;color:#374151;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit" class="act-modal-submit-btn"
                            style="padding:9px 20px;font-size:13px;font-weight:600;border:none;border-radius:9px;background:#6366F1;color:#fff;cursor:pointer;display:flex;align-items:center;gap:7px;">
                        <i class="fa fa-rocket" style="font-size:11px;"></i> Publish Release
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ══ Main Grid ══ --}}
<div class="{{ $isPrivileged ? 'grid grid-cols-1 lg:grid-cols-4' : 'grid grid-cols-1' }} gap-5">

    {{-- ── Team Sidebar (privileged only) ── --}}
    @if($isPrivileged)
    <div class="act-team-sidebar bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden max-md:rounded-[18px] max-md:shadow-sm max-md:ring-1 max-md:ring-black/5">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-900 text-sm max-md:text-sm max-md:font-semibold">Team</h3>
            <span class="text-xs bg-indigo-100 text-indigo-600 font-semibold px-2 py-0.5 rounded-full max-md:text-[11px] max-md:font-medium max-md:px-2 max-md:py-0.5">
                {{ $teams->flatten()->count() }} members
            </span>
        </div>

        @if($selectedUser)
        <div class="px-4 pt-3 pb-0">
            <a href="{{ route('activities.index') }}"
               class="inline-flex items-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-800 font-semibold transition">
                <i class="fa fa-arrow-left text-[10px]"></i> Show all members
            </a>
        </div>
        @endif

        <div class="p-3 space-y-3">
            @php $teamColors = ['manager' => '#6366F1', 'user' => '#10B981']; @endphp
            @foreach($teams as $role => $members)
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 px-1">{{ ucfirst($role) }}s</p>
                <div class="space-y-0.5">
                    @foreach($members as $member)
                    @php $isActive = $selectedUser && $selectedUser->id === $member->id; @endphp
                    <a href="{{ route('activities.index', ['user_id' => $member->id]) }}"
                       class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl transition max-md:gap-3 max-md:px-4 max-md:py-3 {{ $isActive ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50' }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-sm max-md:w-10 max-md:h-10"
                             style="background:{{ $teamColors[$role] ?? '#6366F1' }}">
                            {{ strtoupper(substr($member->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold {{ $isActive ? 'text-indigo-700' : 'text-gray-800' }} truncate max-md:text-sm max-md:font-semibold">{{ $member->name }}</p>
                            <p class="text-[10px] text-gray-400 max-md:text-xs max-md:text-gray-500">{{ $member->tasks_count }} tasks</p>
                        </div>
                        <span class="w-2 h-2 bg-emerald-400 rounded-full flex-shrink-0"></span>
                    </a>
                    @endforeach
                </div>
            </div>
            @endforeach

            @if($teams->flatten()->count() === 0)
            <p class="text-xs text-gray-400 text-center py-6">No team members</p>
            @endif
        </div>
    </div>
    @endif

    {{-- ── Activity Timeline ── --}}
    <div class="act-timeline-col {{ $isPrivileged ? 'lg:col-span-3' : '' }}">

        {{-- Results info (desktop; mobile shows the count/sort row above the list instead) --}}
        @if($activities->total() > 0)
        <div class="act-desktop-only flex items-center justify-between mb-4 px-1">
            <span class="text-xs text-gray-400">
                Showing <span class="font-semibold text-gray-600">{{ $activities->firstItem() }}–{{ $activities->lastItem() }}</span>
                of <span class="font-semibold text-gray-600">{{ number_format($activities->total()) }}</span> activities
            </span>
            @if($activities->lastPage() > 1)
            <span class="text-xs text-gray-400">Page {{ $activities->currentPage() }} / {{ $activities->lastPage() }}</span>
            @endif
        </div>
        @endif

        {{-- Timeline --}}
        <div class="relative overflow-y-auto pr-1" style="max-height:calc(100vh - 260px);scroll-behavior:smooth;" id="activityFeed">

            {{-- Vertical guide line --}}
            @if($activities->count() > 0)
            <div class="absolute left-[19px] top-8 bottom-4 w-0.5 bg-gradient-to-b from-indigo-200 via-gray-100 to-transparent pointer-events-none"></div>
            @endif

            @php
                $lastDate = null;
                $colors   = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6'];
            @endphp

            @forelse($activities as $log)
            @php
                $dateStr     = $log->created_at->isToday()
                    ? 'Today'
                    : ($log->created_at->isYesterday() ? 'Yesterday' : $log->created_at->format(config('app.date_format', 'M d, Y')));
                $color       = $colors[$log->user_id % count($colors)];
                $actionLabel = $log->actionLabel();
                [$actIcon, $actFg, $actBg] = $log->actionStyle();
                $releaseData = ($log->action === 'release_published' && $log->note)
                    ? json_decode($log->note, true) : null;
                $showNote = $log->note
                    && !$releaseData
                    && !str_starts_with($log->note, 'Task details updated by')
                    && !str_starts_with($log->note, 'Task "');
                [$mBg, $mFg] = mobileActionTint($log->action);
                $mAvatar = $mobileAvatarPalette[$log->user_id % count($mobileAvatarPalette)];
            @endphp

            @php $isNewDay = $lastDate !== $dateStr; if ($isNewDay) $lastDate = $dateStr; @endphp

            {{-- Mobile-only day divider --}}
            @if($isNewDay)
            <div class="act-day-divider hidden">
                <span class="act-day-label">{{ $dateStr }}</span>
                <span class="act-day-rule"></span>
                <span class="act-day-count">{{ $mobileDayCounts[$dateStr] ?? 0 }} {{ Str::plural('event', $mobileDayCounts[$dateStr] ?? 0) }}</span>
            </div>
            @endif

            {{-- ── Release card ── --}}
            @if($releaseData)
            <div class="relative flex items-start gap-3 mb-3">
                <div class="w-10 flex-shrink-0 flex justify-center z-10 mt-1">
                    <div class="act-timeline-dot w-8 h-8 rounded-xl flex items-center justify-center shadow-sm ring-4 ring-gray-50 max-md:w-9 max-md:h-9" style="background:#EEF2FF;--m-bg:#EEEDFD;--m-fg:#4C3FCB;">
                        <i class="fa fa-rocket" style="color:#6366F1;font-size:12px;"></i>
                    </div>
                </div>
                <div class="act-release-card flex-1 rounded-2xl border border-indigo-100 shadow-sm overflow-hidden max-md:rounded-[18px] max-md:shadow-sm max-md:ring-1 max-md:ring-black/5">
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-4 py-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-gray-900 text-sm">{{ $log->user->name ?? 'Unknown' }}</span>
                            <span class="text-xs text-gray-500 max-md:text-xs max-md:text-gray-500">published a release</span>
                            @if(!empty($releaseData['version']))
                            <span class="text-xs font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded-full max-md:text-[11px] max-md:font-medium max-md:px-2 max-md:py-0.5">{{ $releaseData['version'] }}</span>
                            @endif
                            <span class="text-xs text-gray-400 ml-auto">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm font-bold text-indigo-800 mt-1.5">🚀 {{ $releaseData['title'] }}</p>
                        @if(!empty($releaseData['description']))
                        <p class="text-xs text-gray-600 mt-1 whitespace-pre-line leading-relaxed">{{ $releaseData['description'] }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @continue
            @endif

            @php
                $initReactions = $log->reactions->groupBy('emoji')->map(fn($g) => $g->count());
                $myReactions   = $log->reactions->where('user_id', auth()->id())->pluck('emoji')->toArray();
                $initReplies   = $log->replies->map(fn($r) => [
                    'id'         => $r->id,
                    'body'       => $r->body,
                    'user'       => $r->user->name ?? '?',
                    'initial'    => strtoupper(substr($r->user->name ?? '?', 0, 1)),
                    'time'       => $r->created_at->diffForHumans(),
                    'mine'       => $r->user_id === auth()->id(),
                    'delete_url' => route('activities.reply.delete', $r),
                ]);
                $taskUrl = null;
                if ($log->task) {
                    $taskUrl = in_array(auth()->user()->role, ['admin','manager'])
                        ? route('admin.tasks.show', $log->task)
                        : route('user.tasks.show', $log->task);
                }
            @endphp

            {{-- ── Activity item ── --}}
            <div class="relative flex items-start gap-3 mb-2.5"
                 x-data="{
                    showPicker:  false,
                    showReply:   false,
                    replyText:   '',
                    submitting:  false,
                    reactions:   {{ Js::from($initReactions) }},
                    myReactions: {{ Js::from($myReactions) }},
                    replies:     {{ Js::from($initReplies) }},
                    emojis: ['👍','👎','❤️','🧡','💛','💚','💙','💜','😄','😂','😮','😢','😡','🤩','🥳','🎉','🔥','✅','💯','🚀','👀','💪','🙏','⭐','😎','🤝','💎','🎯'],
                    async toggleReact(emoji) {
                        this.showPicker = false;
                        const res = await fetch('{{ route('activities.react', $log) }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ emoji })
                        });
                        const d = await res.json();
                        this.reactions = d.counts;
                        if (d.reacted) { if (!this.myReactions.includes(emoji)) this.myReactions.push(emoji); }
                        else { this.myReactions = this.myReactions.filter(e => e !== emoji); }
                    },
                    async submitReply() {
                        if (!this.replyText.trim() || this.submitting) return;
                        this.submitting = true;
                        const res = await fetch('{{ route('activities.reply', $log) }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ body: this.replyText.trim() })
                        });
                        const d = await res.json();
                        this.replies.push(d);
                        this.replyText = '';
                        this.submitting = false;
                    },
                    async deleteReply(r) {
                        await fetch(r.delete_url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                        this.replies = this.replies.filter(x => x.id !== r.id);
                    }
                 }">

                {{-- Timeline icon dot --}}
                <div class="w-10 flex-shrink-0 flex justify-center z-10 mt-0.5">
                    <div class="act-timeline-dot w-8 h-8 rounded-xl flex items-center justify-center shadow-sm ring-4 ring-gray-50 flex-shrink-0 max-md:w-9 max-md:h-9"
                         style="background:{{ $actBg }};--m-bg:{{ $mBg }};--m-fg:{{ $mFg }};">
                        <i class="fa {{ $actIcon }}" style="color:{{ $actFg }};font-size:12px;"></i>
                    </div>
                </div>

                {{-- Content card --}}
                <div class="act-content-card flex-1 min-w-0 bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all duration-150 overflow-hidden max-md:rounded-[18px] max-md:shadow-sm max-md:ring-1 max-md:ring-black/5 {{ $taskUrl ? 'cursor-pointer' : '' }}"
                     @if($taskUrl) @click="if(!$event.target.closest('[data-no-nav]')) window.location='{{ $taskUrl }}'" @endif>

                    <div class="px-4 pt-3 pb-2">
                        {{-- Main content line --}}
                        <div class="flex items-start gap-2">
                            {{-- User avatar --}}
                            <div class="act-avatar w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mt-0.5 shadow-sm max-md:w-10 max-md:h-10"
                                 style="background:{{ $color }};--m-avatar:{{ $mAvatar }};">
                                {{ strtoupper(substr($log->user->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0 pt-0.5">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-bold text-gray-900 text-sm">{{ $log->user->name ?? 'Unknown' }}</span>
                                    <span class="act-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold leading-none max-md:text-[11px] max-md:font-medium max-md:px-2 max-md:py-0.5"
                                          style="background:{{ $actBg }};color:{{ $actFg }};--m-bg:{{ $mBg }};--m-fg:{{ $mFg }};">
                                        <i class="fa {{ $actIcon }}" style="font-size:8px;"></i>{{ $actionLabel }}
                                    </span>
                                    @if($log->task)
                                        @if($taskUrl)
                                        <a href="{{ $taskUrl }}" data-no-nav
                                           class="act-task-title-link font-semibold text-indigo-600 hover:text-indigo-800 hover:underline text-sm truncate max-w-[200px]">{{ $log->task->title }}</a>
                                        @else
                                        <span class="act-task-title-link font-semibold text-indigo-600 text-sm truncate max-w-[200px]">{{ $log->task->title }}</span>
                                        @endif
                                        @if($log->task->project && !$log->task->project->is_quick)
                                        <span class="hidden sm:inline text-xs text-gray-400">in
                                            <span class="text-gray-600 font-medium">{{ $log->task->project->name }}</span>
                                        </span>
                                        @endif
                                    @elseif($log->task_id)
                                    <span class="italic text-xs text-gray-400">(deleted task)</span>
                                    @endif
                                </div>

                                @if($showNote)
                                <div class="mt-2 px-3 py-2 bg-gray-50 rounded-xl border border-gray-100 text-xs text-gray-600 whitespace-pre-line leading-relaxed">{{ strip_tags($log->note) }}</div>
                                @endif
                            </div>
                            {{-- Time --}}
                            <div class="hidden sm:flex act-time-col flex-col items-end gap-0.5 flex-shrink-0 mt-0.5">
                                @if($isNewDay)
                                <span class="act-desktop-only text-[10px] font-bold text-indigo-500 bg-indigo-50 px-1.5 py-0.5 rounded-md leading-none">{{ $dateStr }}</span>
                                @endif
                                <span class="text-xs text-gray-400 font-medium tabular-nums">{{ $log->created_at->format('g:i A') }}</span>
                            </div>
                        </div>

                        {{-- Reactions --}}
                        <div class="flex flex-wrap gap-1 mt-2 ml-9" x-show="Object.keys(reactions).length > 0" data-no-nav>
                            <template x-for="(count, emoji) in reactions" :key="emoji">
                                <button @click="toggleReact(emoji)"
                                        :class="myReactions.includes(emoji)
                                            ? 'bg-indigo-100 text-indigo-700 border-indigo-300'
                                            : 'bg-gray-50 text-gray-600 border-gray-200 hover:border-indigo-200 hover:bg-indigo-50'"
                                        class="act-reaction-pill inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-xs font-medium transition-all hover:scale-105 active:scale-95">
                                    <span x-text="emoji"></span><span x-text="count" class="font-bold"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Action bar --}}
                    <div class="flex items-center gap-1 px-4 py-2 border-t border-gray-50 bg-gray-50/50" data-no-nav>

                        {{-- React (desktop: full emoji picker) --}}
                        <div class="relative act-desktop-only">
                            <button @click="showPicker=!showPicker"
                                    class="act-react-btn flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition">
                                <i class="fa fa-smile text-sm"></i>
                            </button>
                            <div x-show="showPicker" @click.outside="showPicker=false" x-transition
                                 class="act-emoji-picker"
                                 style="position:fixed;background:#fff;border:1px solid #E5E7EB;border-radius:14px;padding:10px;box-shadow:0 12px 32px rgba(0,0,0,.14);z-index:9999;display:grid;grid-template-columns:repeat(5,1fr);gap:4px;width:200px;"
                                 x-init="$watch('showPicker', v => { if(v) { const r = $el.previousElementSibling.getBoundingClientRect(); $el.style.left=r.left+'px'; $el.style.top=(r.bottom+6)+'px'; } })">
                                <template x-for="e in emojis" :key="e">
                                    <button @click="toggleReact(e)"
                                            :class="myReactions.includes(e) ? 'bg-indigo-100 scale-110' : 'hover:bg-gray-100'"
                                            class="text-lg rounded-lg p-1 transition-transform hover:scale-125 active:scale-90" x-text="e"></button>
                                </template>
                            </div>
                        </div>

                        {{-- Reply --}}
                        <button @click="showReply=!showReply"
                                :class="showReply ? 'text-indigo-600 bg-indigo-50' : 'text-gray-400 hover:text-indigo-600 hover:bg-indigo-50'"
                                class="act-reply-btn flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold transition">
                            <i class="fa fa-reply"></i>
                            <span x-text="replies.length > 0 ? replies.length + (replies.length === 1 ? ' reply' : ' replies') : 'Reply'"></span>
                        </button>

                        {{-- Heart quick-toggle (mobile) --}}
                        <button type="button" @click="toggleReact('❤️')"
                                class="act-heart-btn hidden px-2.5 py-1 rounded-lg" :class="myReactions.includes('❤️') && 'is-liked'">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A93A6" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20s-7-4.5-7-9.5A4 4 0 0112 8a4 4 0 017 2.5c0 5-7 9.5-7 9.5z"></path>
                            </svg>
                            <span class="act-heart-count" x-text="reactions['❤️'] || 0"></span>
                        </button>

                        <span class="act-desktop-only flex items-center gap-1 text-xs text-gray-400 ml-1">
                            <i class="fa fa-clock text-[10px]"></i>
                            <span class="hidden sm:inline">{{ $log->created_at->diffForHumans() }}</span>
                            <span class="sm:hidden">{{ $log->created_at->format('g:i A') }}</span>
                        </span>

                        @if($taskUrl)
                        <a href="{{ $taskUrl }}" data-no-nav
                           class="act-view-btn ml-auto flex items-center gap-1.5 text-xs font-semibold text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 px-2.5 py-1 rounded-lg transition">
                            View <i class="fa fa-arrow-right text-[9px]"></i>
                        </a>
                        @endif
                    </div>

                    {{-- Replies --}}
                    <div x-show="showReply || replies.length > 0" x-cloak x-transition class="px-4 pb-3 space-y-2" data-no-nav>
                        <template x-for="r in replies" :key="r.id">
                            <div class="flex items-start gap-2 group/reply mt-2">
                                <div class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0 mt-0.5 shadow-sm"
                                     x-text="r.initial"></div>
                                <div class="flex-1 bg-gray-50 rounded-xl px-3 py-2 border border-gray-100">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-bold text-gray-700" x-text="r.user"></span>
                                        <div class="flex items-center gap-2 text-[11px] text-gray-400">
                                            <span x-text="r.time"></span>
                                            <button x-show="r.mine" @click="deleteReply(r)"
                                                    class="opacity-0 group-hover/reply:opacity-100 text-red-400 hover:text-red-600 transition-opacity">
                                                <i class="fa fa-trash-alt text-[10px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-0.5 leading-relaxed" x-text="r.body"></p>
                                </div>
                            </div>
                        </template>

                        {{-- Reply input --}}
                        <div x-show="showReply" x-transition class="flex items-center gap-2 mt-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0 shadow-sm"
                                 style="background:#6366F1">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                            <div class="flex-1 flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-1.5 focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-100 transition shadow-sm">
                                <input x-model="replyText"
                                       @keydown.enter.prevent="submitReply()"
                                       @keydown.escape="showReply=false; replyText=''"
                                       type="text" placeholder="Reply… (Enter to send)"
                                       class="flex-1 bg-transparent text-xs text-gray-700 outline-none placeholder-gray-400">
                                <button @click="submitReply()" :disabled="submitting || !replyText.trim()"
                                        class="text-indigo-500 hover:text-indigo-700 disabled:opacity-30 transition">
                                    <i class="fa fa-paper-plane text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            @empty
            <div class="act-empty-card py-20 text-center max-md:py-8">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4 max-md:w-10 max-md:h-10">
                    <i class="fa fa-bolt text-3xl text-gray-300 max-md:text-xl"></i>
                </div>
                <p class="text-gray-600 font-semibold">No activities found</p>
                <p class="text-sm text-gray-400 mt-1 max-md:text-sm max-md:text-gray-500">
                    {{ $hasFilters ? 'Try adjusting your filters or clearing the search.' : 'Activity will appear here when team members update tasks.' }}
                </p>
                @if($hasFilters)
                <a href="{{ route('activities.index', array_filter(['user_id' => request('user_id')])) }}"
                   class="mt-4 inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 font-semibold transition">
                    <i class="fa fa-times-circle text-xs"></i> Clear filters
                </a>
                @endif
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($activities->hasPages())
        <div class="mt-5">
            <x-pagination :paginator="$activities" mt="0" />
        </div>
        @endif

    </div>{{-- /timeline col --}}

</div>{{-- /main grid --}}

</div>{{-- /x-data releaseOpen --}}
@endsection

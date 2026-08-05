@extends('layouts.app')

@section('title', 'Task History')

@section('content')
<style>
.utask-filter-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:24px; }
@media(max-width:640px) { .utask-filter-grid { grid-template-columns:repeat(2,1fr); gap:10px; } }
@media(max-width:360px) { .utask-filter-grid { grid-template-columns:1fr 1fr; gap:8px; } }

/* Task list rows — hide deadline on very small phones */
@media(max-width:400px) {
    .utask-deadline-col { display:none !important; }
}

/* Mobile: prevent task-list rows from overflowing the card */
@media(max-width:480px) {
    .utask-filter-grid { grid-template-columns:repeat(2,1fr) !important; gap:8px !important; }
}
.utask-task-row { overflow:hidden; }

/* ══════════════════════════════════════════════════════════
   Premium mobile redesign (uses shared tokens from layouts.app)
   Desktop (>768px) is completely untouched below this point.
   ══════════════════════════════════════════════════════════ */
@media (max-width:768px) {
    .utask-page-header { margin-bottom: var(--mob-sp-2) !important; }
    .utask-page-header h1 { font-size:19px !important; }

    /* Paused-tasks reminder as a soft premium card */
    .utask-paused-banner {
        border-radius: var(--mob-r-md) !important;
        box-shadow: var(--mob-shadow-1);
    }

    /* Filter/stat cards: shared radius + shadow tokens, bigger tap feedback */
    .utask-filter-grid { gap: var(--mob-sp-1) !important; margin-bottom: var(--mob-sp-3) !important; }
    .utask-filter-card {
        border-radius: var(--mob-r-md) !important;
        box-shadow: var(--mob-shadow-1) !important;
    }
    .utask-filter-card.is-active { box-shadow: var(--mob-shadow-2) !important; }
    .utask-filter-card:active { transform: scale(.97); }

    /* Drop the desktop "single card" chrome — each task row becomes its own card below */
    .utask-list-wrap {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        overflow: visible !important;
    }
    .utask-filter-banner {
        border-radius: var(--mob-r-md) !important;
        margin-bottom: var(--mob-sp-2) !important;
    }
    .utask-rows { display: block; }
    .utask-rows > a.utask-row { border-top: none !important; }

    /* Task row -> stacked premium card: title/status prominent, full tap target */
    .utask-row {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        column-gap: 10px !important;
        row-gap: 6px !important;
        border-radius: var(--mob-r-md) !important;
        background: #fff !important;
        border: 1px solid #F3F4F6 !important;
        box-shadow: var(--mob-shadow-1) !important;
        margin: 0 0 var(--mob-sp-2) !important;
        padding: var(--mob-sp-2) !important;
        min-height: 64px;
    }
    .utask-row:active { transform: scale(.98); }

    .utask-row .utask-dot { order: 1; margin-top: 2px; }
    .utask-row .utask-main { order: 2; flex: 1 1 calc(100% - 34px) !important; min-width: 0; }
    .utask-row .utask-main p:first-child { font-size: 14.5px !important; white-space: normal !important; }

    /* Meta chips wrap to their own row beneath the title, most important (status) first */
    .utask-row .utask-status-badge { order: 3; flex: 0 0 auto; font-size: 11.5px !important; }
    .utask-row .utask-priority     { order: 4; flex: 0 0 auto; }
    .utask-row .utask-deadline-col { order: 5; flex: 0 0 auto; font-size: 11px !important; }
    .utask-row .utask-chevron      { order: 6; margin-left: auto; }

    /* Chips: slightly taller pill padding for a premium feel */
    .utask-status-badge, .utask-priority { padding-top: 4px !important; padding-bottom: 4px !important; }

    /* Empty state — its own soft card instead of floating in the transparent wrap */
    .utask-empty-card {
        border-radius: var(--mob-r-md) !important;
        background: #fff !important;
        border: 1px solid #F3F4F6 !important;
        box-shadow: var(--mob-shadow-1) !important;
    }
}

/* ── Mobile-only tasks list: pill filters + rail/chip rows ── */
.utask-mobile-only { display: none; }
.utask-pill-row { align-items:center; gap:8px; overflow-x:auto; -webkit-overflow-scrolling:touch; scrollbar-width:none; padding:2px 2px 4px; }
.utask-pill-row::-webkit-scrollbar { display:none; }
.utask-pill { flex-shrink:0; padding:9px 15px; border-radius:11px; font-size:12.5px; font-weight:600; white-space:nowrap; text-decoration:none; border:1px solid #E1E4EA; background:#fff; color:#4B5563; }
.utask-pill.is-active { background:var(--mob-brand); border-color:var(--mob-brand); color:#fff; }
.utask-mobile-row { align-items:center; gap:12px; background:#fff; padding:13px 14px; text-decoration:none; border-bottom:1px solid #EDEFF3; }
.utask-mobile-row:last-child { border-bottom:none; }
@media(max-width:768px){
    .utask-mobile-only { display: block !important; }
    .utask-pill-row, .utask-mobile-row { display: flex !important; }
    .utask-filter-grid,
    .utask-filter-banner,
    .utask-rows,
    .utask-paused-banner {
        display: none !important;
    }
}
</style>
<div class="flex items-center justify-between mb-6 utask-page-header">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Task History</h1>
        <p class="text-sm text-gray-500 mt-0.5">All tasks assigned to you</p>
    </div>
</div>

@php
    $pausedCount = auth()->user()->tasks()->where('status', 'paused')->count();
@endphp

{{-- Paused tasks reminder --}}
@if($pausedCount > 0)
<div class="utask-paused-banner" style="background:#FFFBEB;border:1.5px solid #FDE68A;border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:14px;">
    <div style="width:38px;height:38px;border-radius:10px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="fa fa-circle-pause" style="color:#D97706;font-size:16px;"></i>
    </div>
    <div style="flex:1;">
        <p style="font-size:13px;font-weight:700;color:#92400E;margin:0 0 2px;">{{ $pausedCount }} {{ Str::plural('task', $pausedCount) }} paused</p>
        <p style="font-size:12px;color:#B45309;margin:0;">{{ $pausedCount === 1 ? 'A task was' : 'Tasks were' }} paused — open {{ $pausedCount === 1 ? 'it' : 'them' }} to resume your timer when you're ready.</p>
    </div>
    <i class="fa fa-chevron-right" style="color:#D97706;font-size:12px;flex-shrink:0;"></i>
</div>
@endif

{{-- Filter Cards --}}
@php
    $filterCards = [
        ['all',         'All Tasks',    'fa-list-check',           '#EEF2FF', '#4F46E5', $counts['all']],
        ['pending',     'Pending',      'fa-clock',                '#FFFBEB', '#D97706', $counts['pending']],
        ['in_progress', 'In Progress',  'fa-spinner',              '#F5F3FF', '#7C3AED', $counts['in_progress']],
        ['overdue',     'Overdue',      'fa-triangle-exclamation', '#FEF2F2', '#DC2626', $counts['overdue']],
        ['completed',   'Completed',    'fa-circle-check',         '#F0FDF4', '#16A34A', $counts['completed']],
    ];
@endphp

{{-- Mobile-only pill filter row + rail/chip row list --}}
<div class="utask-mobile-only">
    <div class="utask-pill-row" style="margin-bottom:14px;">
        @foreach($filterCards as [$fKey, $fLabel, $fIcon, $fBg, $fColor, $fCount])
        <a href="{{ route('user.tasks.index', ['filter' => $fKey]) }}" class="utask-pill {{ $filter === $fKey ? 'is-active' : '' }}">{{ $fLabel }} · {{ $fCount }}</a>
        @endforeach
    </div>
    <div style="background:#fff;border:1px solid #EDEFF3;border-radius:16px;overflow:hidden;margin-bottom:16px;">
        @php $mDoneStatuses = ['approved', 'delivered', 'archived']; @endphp
        @forelse($tasks as $mt)
        @php
            $mtOverdue = $mt->deadline && $mt->deadline->isPast() && !in_array($mt->status, $mDoneStatuses);
            $mtSocialOnly = $mt->social_assigned_to == auth()->id() && $mt->assigned_to != auth()->id();
            $mtUrl = $mtSocialOnly ? route('social.show', $mt) : route('user.tasks.show', $mt);
        @endphp
        <a href="{{ $mtUrl }}" class="utask-mobile-row">
            <x-status-rail :status="$mt->status" />
            <span style="flex:1;min-width:0;">
                <span style="display:block;font-size:14.5px;font-weight:600;color:#111827;letter-spacing:-.012em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $mt->title }}</span>
                <span style="display:flex;align-items:center;gap:8px;margin-top:6px;">
                    <x-status-chip :status="$mt->status" />
                    <span style="font-size:11.5px;font-weight:500;color:#9CA3AF;max-width:13ch;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $mt->project->name ?? 'Quick Tasks' }}</span>
                </span>
            </span>
            @if($mt->deadline)
            <span style="font-size:11.5px;font-weight:700;font-variant-numeric:tabular-nums;color:{{ $mtOverdue ? '#DC2626' : '#9CA3AF' }};flex-shrink:0;">{{ $mt->deadline->format('M d') }}</span>
            @endif
        </a>
        @empty
        <div style="padding:32px 16px;text-align:center;">
            <p style="font-size:13.5px;font-weight:600;color:#111827;margin:0;">Nothing here</p>
            <p style="font-size:12px;color:#9CA3AF;margin:4px 0 0;">No {{ $filter === 'all' ? '' : str_replace('_', ' ', $filter).' ' }}tasks found.</p>
        </div>
        @endforelse
    </div>
</div>

<div class="utask-filter-grid">
    @foreach($filterCards as [$key, $label, $icon, $bg, $color, $count])
    @php $isActive = $filter === $key; @endphp
    <a href="{{ route('user.tasks.index', ['filter' => $key]) }}"
       class="utask-filter-card {{ $isActive ? 'is-active' : '' }}"
       style="display:block;background:{{ $isActive ? $color : '#fff' }};border:2px solid {{ $isActive ? $color : '#E5E7EB' }};border-radius:14px;padding:16px 18px;text-decoration:none;transition:all .15s;box-shadow:{{ $isActive ? '0 4px 14px rgba(0,0,0,.12)' : '0 1px 3px rgba(0,0,0,.05)' }};"
       onmouseover="if({{ $isActive ? 'false' : 'true' }}) { this.style.borderColor='{{ $color }}'; this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'; }"
       onmouseout="if({{ $isActive ? 'false' : 'true' }}) { this.style.borderColor='#E5E7EB'; this.style.boxShadow='0 1px 3px rgba(0,0,0,.05)'; }">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <div style="width:34px;height:34px;border-radius:9px;background:{{ $isActive ? 'rgba(255,255,255,.22)' : $bg }};display:flex;align-items:center;justify-content:center;">
                <i class="fas {{ $icon }}" style="font-size:14px;color:{{ $isActive ? '#fff' : $color }};"></i>
            </div>
            @if($isActive)
            <i class="fas fa-check-circle" style="font-size:13px;color:rgba(255,255,255,.7);"></i>
            @endif
        </div>
        <p style="font-size:24px;font-weight:800;color:{{ $isActive ? '#fff' : '#111827' }};margin:0 0 2px;line-height:1;">{{ $count }}</p>
        <p style="font-size:12px;font-weight:600;color:{{ $isActive ? 'rgba(255,255,255,.8)' : '#6B7280' }};margin:0;">{{ $label }}</p>
    </a>
    @endforeach
</div>

{{-- Task List --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden utask-list-wrap">
    @if($filter !== 'all')
    <div class="utask-filter-banner" style="padding:12px 20px;border-bottom:1px solid #F3F4F6;background:#F9FAFB;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:12px;font-weight:600;color:#6B7280;">
            Showing: <span style="color:#111827;">{{ ucwords(str_replace('_', ' ', $filter)) }}</span> tasks
        </span>
        <a href="{{ route('user.tasks.index') }}" style="font-size:12px;color:#6366F1;text-decoration:none;font-weight:600;">
            <i class="fas fa-times" style="font-size:10px;margin-right:3px;"></i> Clear filter
        </a>
    </div>
    @endif

    <div class="divide-y divide-gray-50 utask-rows">
        @forelse($tasks as $task)
        @php
            $isDone     = in_array($task->status, ['approved','delivered','archived']);
            $isPaused   = $task->status === 'paused';
            $isRevision = $task->status === 'revision_requested';
            $isOverdue  = $task->deadline && $task->deadline < now() && !$isDone && !$isPaused;

        @endphp
        @php
            $isSocialOnlyTask = $task->social_assigned_to == auth()->id() && $task->assigned_to != auth()->id();
            $taskUrl = $isSocialOnlyTask ? route('social.show', $task) : route('user.tasks.show', $task);
        @endphp
        <a href="{{ $taskUrl }}"
           class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50/70 transition group utask-row
               {{ $isPaused   ? 'bg-amber-50/40 border-l-2 border-amber-300'  : '' }}
               {{ $isRevision ? 'bg-red-50/40 border-l-2 border-red-300'      : '' }}
               {{ $isOverdue && !$isPaused && !$isRevision ? 'bg-red-50/30'  : '' }}
               {{ $isSocialOnlyTask ? 'bg-indigo-50/30' : '' }}">
            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0 utask-dot
                {{ $isDone ? 'bg-emerald-400' : ($task->status === 'in_progress' ? 'bg-amber-400' : ($isPaused ? 'bg-gray-400' : ($isOverdue ? 'bg-red-400' : 'bg-gray-300'))) }}">
            </div>
            <div class="flex-1 min-w-0 utask-main">
                <p class="text-sm font-medium truncate group-hover:text-indigo-600 transition max-md:text-sm max-md:font-semibold
                    {{ $isDone ? 'line-through text-gray-400' : ($isPaused ? 'text-gray-500' : 'text-gray-900') }}">
                    {{ $task->title }}
                    @if($isPaused)
                    <span class="inline-flex items-center gap-1 ml-1 text-xs font-normal text-amber-600">
                        <i class="fa fa-circle-pause" style="font-size:9px;"></i> paused
                    </span>
                    @endif
                    @if($isSocialOnlyTask)
                    <span class="inline-flex items-center gap-1 ml-1 text-xs font-semibold text-indigo-600" style="font-size:10px;">
                        <i class="fas fa-share-alt" style="font-size:9px;"></i> Post Pending
                    </span>
                    @endif
                </p>
                <p class="text-xs text-gray-400 mt-0.5 max-md:text-gray-500">{{ $task->project->name ?? '—' }}</p>
            </div>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0 utask-priority max-md:text-[11px] max-md:px-2 max-md:py-0.5
                {{ $task->priority === 'high' ? 'bg-red-100 text-red-600' : ($task->priority === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                {{ ucfirst($task->priority ?? 'low') }}
            </span>
            @if($task->deadline)
            <span class="utask-deadline-col text-xs flex-shrink-0 max-md:text-[11px] {{ $isOverdue ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                {{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}
            </span>
            @else
            <span class="utask-deadline-col text-xs text-gray-300 flex-shrink-0 max-md:text-[11px]">No deadline</span>
            @endif
            <x-status-chip :status="$task->status" class="flex-shrink-0 utask-status-badge max-md:text-[11px] max-md:px-2 max-md:py-0.5" />
            <i class="fa fa-chevron-right text-gray-300 text-xs flex-shrink-0 group-hover:text-indigo-400 transition utask-chevron"></i>
        </a>
        @empty
        <div class="px-5 py-16 text-center max-md:py-8 utask-empty-card">
            <i class="fa fa-clipboard-list text-5xl text-gray-200 mb-3 max-md:text-4xl max-md:text-gray-300"></i>
            <p class="text-gray-500 font-medium mb-1 max-md:text-sm">No {{ $filter === 'all' ? '' : str_replace('_', ' ', $filter) . ' ' }}tasks found</p>
            @if($filter !== 'all')
            <a href="{{ route('user.tasks.index') }}" class="text-sm text-indigo-500 hover:underline max-md:text-xs max-md:font-medium max-md:text-gray-400">View all tasks</a>
            @endif
        </div>
        @endforelse
    </div>

    <x-pagination :paginator="$tasks" mt="12px" />
</div>
@endsection

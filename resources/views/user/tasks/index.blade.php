@extends('layouts.app')

@section('title', 'Task History')

@section('content')
<div class="flex items-center justify-between mb-6">
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
<div style="background:#FFFBEB;border:1.5px solid #FDE68A;border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:14px;">
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
        ['completed',   'Completed',    'fa-circle-check',         '#F0FDF4', '#16A34A', $counts['completed']],
    ];
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;">
    @foreach($filterCards as [$key, $label, $icon, $bg, $color, $count])
    @php $isActive = $filter === $key; @endphp
    <a href="{{ route('user.tasks.index', ['filter' => $key]) }}"
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
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    @if($filter !== 'all')
    <div style="padding:12px 20px;border-bottom:1px solid #F3F4F6;background:#F9FAFB;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:12px;font-weight:600;color:#6B7280;">
            Showing: <span style="color:#111827;">{{ ucwords(str_replace('_', ' ', $filter)) }}</span> tasks
        </span>
        <a href="{{ route('user.tasks.index') }}" style="font-size:12px;color:#6366F1;text-decoration:none;font-weight:600;">
            <i class="fas fa-times" style="font-size:10px;margin-right:3px;"></i> Clear filter
        </a>
    </div>
    @endif

    <div class="divide-y divide-gray-50">
        @forelse($tasks as $task)
        @php
            $isDone     = in_array($task->status, ['approved','delivered','archived']);
            $isPaused   = $task->status === 'paused';
            $isRevision = $task->status === 'revision_requested';
            $isOverdue  = $task->deadline && $task->deadline < now() && !$isDone && !$isPaused;

            $statusMeta = match($task->status) {
                'approved'           => ['Approved',    'bg-emerald-100 text-emerald-700'],
                'delivered'          => ['Delivered',   'bg-emerald-100 text-emerald-700'],
                'archived'           => ['Archived',    'bg-gray-100 text-gray-500'],
                'in_progress'        => ['In Progress', 'bg-amber-100 text-amber-700'],
                'paused'             => ['Paused',      'bg-gray-100 text-gray-500'],
                'submitted'          => ['In Review',   'bg-purple-100 text-purple-700'],
                'revision_requested' => ['Revision',    'bg-red-100 text-red-600'],
                'viewed'             => ['Viewed',      'bg-blue-100 text-blue-600'],
                'assigned'           => ['Assigned',    'bg-indigo-100 text-indigo-600'],
                default              => [ucfirst($task->status), 'bg-gray-100 text-gray-600'],
            };
        @endphp
        <a href="{{ route('user.tasks.show', $task) }}"
           class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50/70 transition group
               {{ $isPaused   ? 'bg-amber-50/40 border-l-2 border-amber-300'  : '' }}
               {{ $isRevision ? 'bg-red-50/40 border-l-2 border-red-300'      : '' }}
               {{ $isOverdue && !$isPaused && !$isRevision ? 'bg-red-50/30'  : '' }}">
            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0
                {{ $isDone ? 'bg-emerald-400' : ($task->status === 'in_progress' ? 'bg-amber-400' : ($isPaused ? 'bg-gray-400' : ($isOverdue ? 'bg-red-400' : 'bg-gray-300'))) }}">
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate group-hover:text-indigo-600 transition
                    {{ $isDone ? 'line-through text-gray-400' : ($isPaused ? 'text-gray-500' : 'text-gray-900') }}">
                    {{ $task->title }}
                    @if($isPaused)
                    <span class="inline-flex items-center gap-1 ml-1 text-xs font-normal text-amber-600">
                        <i class="fa fa-circle-pause" style="font-size:9px;"></i> paused
                    </span>
                    @endif
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $task->project->name ?? '—' }}</p>
            </div>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0
                {{ $task->priority === 'high' ? 'bg-red-100 text-red-600' : ($task->priority === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                {{ ucfirst($task->priority ?? 'low') }}
            </span>
            @if($task->deadline)
            <span class="text-xs flex-shrink-0 {{ $isOverdue ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                {{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}
            </span>
            @else
            <span class="text-xs text-gray-300 flex-shrink-0">No deadline</span>
            @endif
            <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0 {{ $statusMeta[1] }}">
                {{ $statusMeta[0] }}
            </span>
            <i class="fa fa-chevron-right text-gray-300 text-xs flex-shrink-0 group-hover:text-indigo-400 transition"></i>
        </a>
        @empty
        <div class="px-5 py-16 text-center">
            <i class="fa fa-clipboard-list text-5xl text-gray-200 mb-3"></i>
            <p class="text-gray-500 font-medium mb-1">No {{ $filter === 'all' ? '' : str_replace('_', ' ', $filter) . ' ' }}tasks found</p>
            @if($filter !== 'all')
            <a href="{{ route('user.tasks.index') }}" class="text-sm text-indigo-500 hover:underline">View all tasks</a>
            @endif
        </div>
        @endforelse
    </div>

    <x-pagination :paginator="$tasks" mt="12px" />
</div>
@endsection

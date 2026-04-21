@extends('layouts.app')
@section('title', 'All Tasks')

@section('content')
@php
$statusMeta = [
    'draft'              => ['label'=>'Draft',       'bg'=>'#F3F4F6','color'=>'#6B7280','dot'=>'#9CA3AF', 'pct'=>5],
    'assigned'           => ['label'=>'Assigned',    'bg'=>'#EEF2FF','color'=>'#4F46E5','dot'=>'#6366F1', 'pct'=>15],
    'viewed'             => ['label'=>'Viewed',      'bg'=>'#F0F9FF','color'=>'#0369A1','dot'=>'#0EA5E9', 'pct'=>25],
    'in_progress'        => ['label'=>'In Progress', 'bg'=>'#FFFBEB','color'=>'#D97706','dot'=>'#F59E0B', 'pct'=>55],
    'submitted'          => ['label'=>'In Review',   'bg'=>'#F5F3FF','color'=>'#7C3AED','dot'=>'#8B5CF6', 'pct'=>75],
    'revision_requested' => ['label'=>'Revision',   'bg'=>'#FFF7ED','color'=>'#C2410C','dot'=>'#F97316', 'pct'=>60],
    'approved'           => ['label'=>'Approved',    'bg'=>'#F0FDF4','color'=>'#15803D','dot'=>'#22C55E', 'pct'=>90],
    'delivered'          => ['label'=>'Delivered',   'bg'=>'#F0FDF4','color'=>'#166534','dot'=>'#16A34A', 'pct'=>100],
    'archived'           => ['label'=>'Archived',    'bg'=>'#F3F4F6','color'=>'#6B7280','dot'=>'#9CA3AF', 'pct'=>100],
];
$priorityMeta = [
    'high'   => ['color'=>'#EF4444','bg'=>'#FEF2F2','label'=>'High'],
    'medium' => ['color'=>'#F59E0B','bg'=>'#FFFBEB','label'=>'Medium'],
    'low'    => ['color'=>'#10B981','bg'=>'#F0FDF4','label'=>'Low'],
];
$avatarColors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4'];
@endphp

{{-- ── Page Header ── --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Tasks</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $stats['total'] }} total tasks</p>
    </div>
    <a href="{{ route('admin.projects.index') }}"
       class="flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition border border-gray-200 shadow-sm"
       style="text-decoration:none;">
        <i class="fa fa-diagram-project text-indigo-500"></i> View Projects
    </a>
</div>

{{-- ── Stats bar ── --}}
<style>
.proj-stat-card { border-radius:14px; padding:18px 20px; position:relative; overflow:hidden; color:#fff; cursor:default; transition:transform .15s,box-shadow .15s; }
.proj-stat-card:hover { transform:translateY(-3px); }
.proj-stat-card-blob { position:absolute; top:-20px; right:-20px; width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.12); }
.proj-stat-card-label { font-size:12px; font-weight:500; color:rgba(255,255,255,0.75); margin:0 0 8px; }
.proj-stat-card-value { font-size:34px; font-weight:700; line-height:1; margin:0; }
.proj-stat-card-sub   { font-size:11px; color:rgba(255,255,255,0.6); margin:6px 0 0; }
.proj-stat-card-menu  { position:absolute; top:14px; right:14px; background:rgba(255,255,255,0.15); border:none; border-radius:6px; width:26px; height:26px; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#fff; font-size:11px; }
</style>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <a href="{{ route('admin.tasks.index') }}" style="text-decoration:none;display:flex;">
        <div class="proj-stat-card" style="flex:1;background:linear-gradient(135deg,#4F46E5,#6366F1);" onmouseover="this.style.boxShadow='0 8px 24px rgba(79,70,229,.4)'" onmouseout="this.style.boxShadow=''">
            <div class="proj-stat-card-blob"></div>
            <p class="proj-stat-card-label">All Tasks</p>
            <p class="proj-stat-card-value">{{ $stats['total'] }}</p>
            <p class="proj-stat-card-sub">Total Tasks</p>
        </div>
    </a>
    <a href="{{ route('admin.tasks.index', ['status'=>'in_progress']) }}" style="text-decoration:none;display:flex;">
        <div class="proj-stat-card" style="flex:1;background:linear-gradient(135deg,#D97706,#F59E0B);" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(217,119,6,.4)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="proj-stat-card-blob"></div>
            <p class="proj-stat-card-label">In Progress</p>
            <p class="proj-stat-card-value">{{ $stats['in_progress'] }}</p>
            <p class="proj-stat-card-sub">Active Tasks</p>
        </div>
    </a>
    <a href="{{ route('admin.tasks.index', ['status'=>'approved']) }}" style="text-decoration:none;display:flex;">
        <div class="proj-stat-card" style="flex:1;background:linear-gradient(135deg,#059669,#10B981);" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(5,150,105,.4)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="proj-stat-card-blob"></div>
            <p class="proj-stat-card-label">Completed</p>
            <p class="proj-stat-card-value">{{ $stats['done'] }}</p>
            <p class="proj-stat-card-sub">Approved / Delivered</p>
        </div>
    </a>
    <a href="{{ route('admin.tasks.index') }}?overdue=1" style="text-decoration:none;display:flex;">
        <div class="proj-stat-card" style="flex:1;background:linear-gradient(135deg,#DC2626,#EF4444);" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(220,38,38,.4)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="proj-stat-card-blob"></div>
            <p class="proj-stat-card-label">Overdue</p>
            <p class="proj-stat-card-value">{{ $stats['overdue'] }}</p>
            <p class="proj-stat-card-sub">Past Deadline</p>
        </div>
    </a>
</div>

{{-- ── Filters ── --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 mb-5 flex flex-wrap items-center gap-3">
    <form method="GET" action="{{ route('admin.tasks.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
        <div class="relative flex-1 min-w-48">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tasks…"
                   class="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-gray-50">
        </div>
        <select name="status" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-indigo-400">
            <option value="">All Statuses</option>
            @foreach($statusMeta as $key => $s)
            <option value="{{ $key }}" {{ request('status')===$key?'selected':'' }}>{{ $s['label'] }}</option>
            @endforeach
        </select>
        <select name="priority" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-indigo-400">
            <option value="">All Priorities</option>
            <option value="high"   {{ request('priority')==='high'  ?'selected':'' }}>High</option>
            <option value="medium" {{ request('priority')==='medium'?'selected':'' }}>Medium</option>
            <option value="low"    {{ request('priority')==='low'   ?'selected':'' }}>Low</option>
        </select>
        <select name="project" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-indigo-400">
            <option value="">All Projects</option>
            @foreach($projects as $proj)
            <option value="{{ $proj->id }}" {{ request('project')==$proj->id?'selected':'' }}>{{ $proj->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['search','status','priority','project']))
        <a href="{{ route('admin.tasks.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
    <span class="text-xs text-gray-400 font-medium whitespace-nowrap">{{ $tasks->total() }} {{ Str::plural('task',$tasks->total()) }}</span>
</div>

{{-- ── Task Cards Grid ── --}}
@if($tasks->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm py-24 text-center">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-list-check text-2xl text-gray-300"></i>
    </div>
    <p class="text-gray-500 font-semibold">No tasks found</p>
    @if(request()->hasAny(['search','status','priority','project']))
    <a href="{{ route('admin.tasks.index') }}" class="mt-3 inline-block text-sm text-indigo-500 hover:underline">Clear filters</a>
    @endif
</div>
@else
@php $doneStatuses = ['approved','delivered','archived']; @endphp
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-5">
@foreach($tasks as $task)
    @php
        $sm        = $statusMeta[$task->status] ?? $statusMeta['draft'];
        $pct       = $sm['pct'];
        $isOverdue = $task->deadline && $task->deadline->isPast() && !in_array($task->status, $doneStatuses);
        $daysLeft  = $task->deadline ? now()->diffInDays($task->deadline, false) : null;
        $isDone    = in_array($task->status, $doneStatuses);
        $priorityDot = ['high'=>'#EF4444','medium'=>'#F59E0B','low'=>'#10B981'][$task->priority] ?? '#D1D5DB';
        $stages    = ['draft'=>0,'assigned'=>1,'viewed'=>1,'in_progress'=>2,'submitted'=>3,'revision_requested'=>2,'approved'=>4,'delivered'=>5,'archived'=>5];
        $stageNum  = $stages[$task->status] ?? 0;
    @endphp

    <a href="{{ route('admin.tasks.show', $task) }}"
       class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-100 transition group flex flex-col overflow-hidden"
       style="text-decoration:none;">

        <div class="p-5 flex flex-col gap-3 flex-1">

            {{-- Top row: project + priority dot --}}
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-medium text-gray-400 truncate">
                    <i class="fas fa-diagram-project mr-1" style="font-size:10px;"></i>{{ $task->project?->name ?? 'No Project' }}
                </span>
                <div class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $priorityDot }};" title="{{ ucfirst($task->priority ?? 'none') }} priority"></div>
            </div>

            {{-- Title --}}
            <h3 class="text-sm font-semibold text-gray-800 leading-snug group-hover:text-indigo-600 transition line-clamp-2">
                {{ $task->title }}
            </h3>

            {{-- Progress bar --}}
            <div>
                <div class="flex justify-between text-xs text-gray-400 mb-1.5">
                    <span>Progress</span>
                    <span class="font-semibold text-gray-600">{{ $pct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                    <div class="h-1.5 rounded-full transition-all {{ $isDone ? 'bg-emerald-400' : 'bg-indigo-400' }}"
                         style="width:{{ $pct }}%;"></div>
                </div>
            </div>

            {{-- Stage pipeline --}}
            @php
                $pipeline = ['Assigned','Working','Review','Approved','Done'];
            @endphp
            <div class="flex gap-1">
                @foreach($pipeline as $i => $label)
                @php
                    $reached   = $stageNum >= ($i + 1);
                    $isCurrent = $stageNum === ($i + 1);
                @endphp
                <div class="flex-1 flex flex-col gap-1">
                    <div class="h-1.5 rounded-sm transition-all
                        {{ $isCurrent  ? ($isDone ? 'bg-emerald-500' : 'bg-indigo-500') : '' }}
                        {{ $reached && !$isCurrent ? ($isDone ? 'bg-emerald-300' : 'bg-indigo-200') : '' }}
                        {{ !$reached   ? 'bg-gray-100' : '' }}">
                    </div>
                    <span class="leading-none text-center block {{ $isCurrent ? ($isDone ? 'text-emerald-600 font-semibold' : 'text-indigo-600 font-semibold') : 'text-gray-300' }}"
                          style="font-size:9px;">{{ $label }}</span>
                </div>
                @endforeach
            </div>

            {{-- Assignee --}}
            <div class="flex items-center gap-2 mt-auto">
                @if($task->assignee)
                <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr($task->assignee->name,0,1)) }}
                </div>
                <span class="text-xs text-gray-500 truncate">{{ $task->assignee->name }}</span>
                @else
                <i class="fas fa-user-slash text-gray-300 text-xs"></i>
                <span class="text-xs text-gray-300">Unassigned</span>
                @endif
            </div>

            {{-- Deadline --}}
            <div class="flex items-center gap-1.5 pt-2.5 border-t border-gray-50">
                @if($isOverdue)
                <i class="fas fa-triangle-exclamation text-red-400 text-xs"></i>
                <span class="text-xs font-semibold text-red-500">Overdue · {{ $task->deadline->format('M d') }}</span>
                @elseif($task->deadline)
                <i class="fas fa-calendar-days text-gray-300 text-xs"></i>
                <span class="text-xs text-gray-400">
                    {{ $daysLeft == 0 ? 'Due today' : ($daysLeft == 1 ? 'Due tomorrow' : 'Due '.$task->deadline->format('M d, Y')) }}
                </span>
                @else
                <span class="text-xs text-gray-200">No deadline</span>
                @endif
            </div>

        </div>
    </a>
@endforeach
</div>

{{-- Pagination --}}
@if($tasks->hasPages())
<div class="mt-4">{{ $tasks->links() }}</div>
@endif
@endif

@endsection

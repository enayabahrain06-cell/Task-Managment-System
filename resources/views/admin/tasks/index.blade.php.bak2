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
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.tasks.trash') }}"
           class="flex items-center gap-2 bg-white hover:bg-red-50 text-red-500 text-sm font-medium px-4 py-2 rounded-lg transition border border-red-100 shadow-sm"
           style="text-decoration:none;">
            <i class="fa fa-trash-can"></i> Recycle Bin
        </a>
        <a href="{{ route('admin.projects.index') }}"
           class="flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition border border-gray-200 shadow-sm"
           style="text-decoration:none;">
            <i class="fa fa-diagram-project text-indigo-500"></i> View Projects
        </a>
    </div>
</div>

@if(session('success'))
<div style="background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border:1px solid #A7F3D0;border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#065F46;font-size:14px;display:flex;gap:10px;align-items:center;">
    <i class="fa fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#991B1B;font-size:14px;display:flex;gap:10px;align-items:center;">
    <i class="fa fa-circle-exclamation"></i> {{ session('error') }}
</div>
@endif

{{-- ── Stats bar ── --}}
<style>
.proj-stat-card { border-radius:14px; padding:18px 20px; position:relative; overflow:hidden; color:#fff; cursor:default; transition:transform .15s,box-shadow .15s; }
.proj-stat-card:hover { transform:translateY(-3px); }
.proj-stat-card-blob { position:absolute; top:-20px; right:-20px; width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.12); }
.proj-stat-card-label { font-size:12px; font-weight:500; color:rgba(255,255,255,0.75); margin:0 0 8px; }
.proj-stat-card-value { font-size:34px; font-weight:700; line-height:1; margin:0; }
.proj-stat-card-sub   { font-size:11px; color:rgba(255,255,255,0.6); margin:6px 0 0; }
</style>

@php $isDoneTab = (request('tab') === 'done'); @endphp

{{-- Tab Bar --}}
<div style="display:flex;align-items:center;gap:3px;background:#F3F4F6;border-radius:12px;padding:4px;width:fit-content;margin-bottom:20px;">
    <a href="{{ route('admin.tasks.index') }}"
       style="display:flex;align-items:center;gap:8px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;{{ !$isDoneTab ? 'background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);' : 'background:transparent;color:#6B7280;' }}">
        <i class="fa fa-circle-play" style="font-size:11px;"></i>
        Active
        <span style="font-size:11px;font-weight:700;padding:1px 8px;border-radius:20px;{{ !$isDoneTab ? 'background:#EEF2FF;color:#4F46E5;' : 'background:#E5E7EB;color:#9CA3AF;' }}">
            {{ $stats['active'] }}
        </span>
    </a>
    <a href="{{ route('admin.tasks.index', ['tab'=>'done']) }}"
       style="display:flex;align-items:center;gap:8px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;{{ $isDoneTab ? 'background:#fff;color:#059669;box-shadow:0 1px 4px rgba(0,0,0,.08);' : 'background:transparent;color:#6B7280;' }}">
        <i class="fa fa-circle-check" style="font-size:11px;"></i>
        Completed
        <span style="font-size:11px;font-weight:700;padding:1px 8px;border-radius:20px;{{ $isDoneTab ? 'background:#DCFCE7;color:#059669;' : 'background:#E5E7EB;color:#9CA3AF;' }}">
            {{ $stats['done'] }}
        </span>
    </a>
</div>

@if(!$isDoneTab)
{{-- Active tab: 3 stat cards --}}
@php
$activeStatDefs = [
    ['label'=>'All Active',  'value'=>$stats['active'],      'sub'=>'Non-completed',  'grad'=>'linear-gradient(135deg,#4F46E5,#6366F1)', 'shadow'=>'rgba(79,70,229,.4)',   'url'=> route('admin.tasks.index'),                           'active'=> !request('status') && !request('overdue') && !request('filter')],
    ['label'=>'In Progress', 'value'=>$stats['in_progress'], 'sub'=>'Working Now',    'grad'=>'linear-gradient(135deg,#D97706,#F59E0B)', 'shadow'=>'rgba(217,119,6,.4)',   'url'=> route('admin.tasks.index', ['status'=>'in_progress']), 'active'=> request('status')==='in_progress'],
    ['label'=>'Overdue',     'value'=>$stats['overdue'],     'sub'=>'Past Deadline',  'grad'=>'linear-gradient(135deg,#DC2626,#EF4444)', 'shadow'=>'rgba(220,38,38,.4)',   'url'=> route('admin.tasks.index') . '?overdue=1',            'active'=> request()->boolean('overdue')],
    ['label'=>'Completed',   'value'=>$stats['done'],        'sub'=>'All time done',  'grad'=>'linear-gradient(135deg,#7C3AED,#8B5CF6)', 'shadow'=>'rgba(124,58,237,.4)',  'url'=> route('admin.tasks.index', ['tab'=>'done']),           'active'=> false],
];
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    @foreach($activeStatDefs as $card)
    @php $isActive = $card['active']; @endphp
    <a href="{{ $card['url'] }}" style="text-decoration:none;display:flex;">
        <div class="proj-stat-card"
             style="flex:1;background:{{ $card['grad'] }};{{ $isActive ? 'transform:translateY(-3px);box-shadow:0 8px 24px '.$card['shadow'].';outline:3px solid rgba(255,255,255,0.4);' : '' }}"
             onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px {{ $card['shadow'] }}'"
             onmouseout="this.style.transform='{{ $isActive ? 'translateY(-3px)' : '' }}';this.style.boxShadow='{{ $isActive ? '0 8px 24px '.$card['shadow'] : '' }}'">
            <div class="proj-stat-card-blob"></div>
            <p class="proj-stat-card-label">{{ $card['label'] }}</p>
            <p class="proj-stat-card-value">{{ $card['value'] }}</p>
            <p class="proj-stat-card-sub">{{ $card['sub'] }}</p>
        </div>
    </a>
    @endforeach
</div>
@else
{{-- Done tab: summary banner with breakdown --}}
<div style="display:flex;align-items:center;gap:14px;padding:16px 20px;background:linear-gradient(135deg,#F0FDF4,#DCFCE7);border-radius:14px;border:1px solid #BBF7D0;margin-bottom:24px;">
    <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,#059669,#10B981);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(5,150,105,.3);">
        <i class="fa fa-circle-check" style="color:#fff;font-size:18px;"></i>
    </div>
    <div style="flex:1;">
        <p style="font-size:15px;font-weight:700;color:#14532D;margin:0;">{{ $stats['done'] }} Completed Task{{ $stats['done'] !== 1 ? 's' : '' }}</p>
        <div style="display:flex;align-items:center;gap:12px;margin-top:4px;">
            <span style="font-size:12px;color:#15803D;"><i class="fa fa-circle-check" style="font-size:10px;margin-right:3px;color:#22C55E;"></i>{{ $stats['approved'] }} approved</span>
            <span style="font-size:12px;color:#15803D;"><i class="fa fa-truck" style="font-size:10px;margin-right:3px;color:#16A34A;"></i>{{ $stats['delivered'] }} delivered</span>
            <span style="font-size:12px;color:#6B7280;"><i class="fa fa-box-archive" style="font-size:10px;margin-right:3px;color:#9CA3AF;"></i>{{ $stats['archived'] }} archived</span>
        </div>
    </div>
    <a href="{{ route('admin.tasks.index') }}"
       style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#15803D;text-decoration:none;background:rgba(255,255,255,.6);border:1px solid #86EFAC;padding:7px 14px;border-radius:8px;">
        <i class="fa fa-arrow-left" style="font-size:10px;"></i> Back to Active
    </a>
</div>
@endif

{{-- ── Filters ── --}}
<div class="mb-6">
    <form method="GET" action="{{ route('admin.tasks.index') }}">
        @if($isDoneTab)<input type="hidden" name="tab" value="done">@endif
        <div class="bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl shadow-sm p-4">
            <div class="flex flex-wrap items-center gap-3">

                {{-- Search --}}
                <div class="relative flex-1 min-w-56">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                        <i class="fas fa-search text-indigo-400 text-xs"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tasks…"
                           class="w-full pl-9 pr-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl
                                  placeholder-gray-400 text-gray-700
                                  focus:outline-none focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100
                                  transition-all duration-200">
                </div>

                {{-- Divider --}}
                <div class="hidden sm:block w-px h-8 bg-gray-200"></div>

                {{-- Status --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-layer-group text-gray-400 text-xs"></i>
                    </div>
                    <select name="status"
                            class="pl-8 pr-8 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl text-gray-700
                                   appearance-none cursor-pointer
                                   focus:outline-none focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100
                                   transition-all duration-200">
                        <option value="">All Statuses</option>
                        @foreach($statusMeta as $key => $s)
                        <option value="{{ $key }}" {{ request('status')===$key?'selected':'' }}>{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-[10px]"></i>
                    </div>
                </div>

                {{-- Priority --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-flag text-gray-400 text-xs"></i>
                    </div>
                    <select name="priority"
                            class="pl-8 pr-8 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl text-gray-700
                                   appearance-none cursor-pointer
                                   focus:outline-none focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100
                                   transition-all duration-200">
                        <option value="">All Priorities</option>
                        <option value="high"   {{ request('priority')==='high'  ?'selected':'' }}>High</option>
                        <option value="medium" {{ request('priority')==='medium'?'selected':'' }}>Medium</option>
                        <option value="low"    {{ request('priority')==='low'   ?'selected':'' }}>Low</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-[10px]"></i>
                    </div>
                </div>

                {{-- Project --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-folder text-gray-400 text-xs"></i>
                    </div>
                    <select name="project"
                            class="pl-8 pr-8 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl text-gray-700
                                   appearance-none cursor-pointer
                                   focus:outline-none focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100
                                   transition-all duration-200">
                        <option value="">All Projects</option>
                        @foreach($projects as $proj)
                        <option value="{{ $proj->id }}" {{ request('project')==$proj->id?'selected':'' }}>{{ $proj->name }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-[10px]"></i>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="hidden sm:block w-px h-8 bg-gray-200"></div>

                {{-- Actions --}}
                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700
                                   text-white text-sm font-semibold rounded-xl
                                   shadow-sm shadow-indigo-200 hover:shadow-indigo-300
                                   transition-all duration-200 active:scale-95">
                        <i class="fas fa-sliders-h text-xs"></i>
                        Apply
                    </button>
                    @if(request()->hasAny(['search','status','priority','project','overdue','filter']))
                    <a href="{{ $isDoneTab ? route('admin.tasks.index', ['tab'=>'done']) : route('admin.tasks.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200
                              text-gray-600 text-sm font-semibold rounded-xl
                              transition-all duration-200 active:scale-95">
                        <i class="fas fa-times text-xs"></i>
                        Clear
                    </a>
                    @endif
                </div>

            </div>
        </div>
    </form>
</div>

{{-- ── View Toggle ── --}}
<style>
/* List view table */
.task-list-table { width:100%; border-collapse:collapse; }
.task-list-table th { font-size:11px; font-weight:700; color:#9CA3AF; text-transform:uppercase; letter-spacing:.05em; padding:10px 14px; border-bottom:2px solid #F3F4F6; background:#FAFAFA; text-align:left; white-space:nowrap; }
.task-list-table th:first-child { border-radius:12px 0 0 0; }
.task-list-table th:last-child  { border-radius:0 12px 0 0; }
.task-list-table td { padding:11px 14px; border-bottom:1px solid #F9FAFB; vertical-align:middle; }
.task-list-table tr:last-child td { border-bottom:none; }
.task-list-table tbody tr:hover td { background:#FAFBFF; }
</style>

@if($tasks->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm py-24 text-center">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-list-check text-2xl text-gray-300"></i>
    </div>
    <p class="text-gray-500 font-semibold">No tasks found</p>
    @if(request()->hasAny(['search','status','priority','project','overdue','filter']))
    <a href="{{ $isDoneTab ? route('admin.tasks.index', ['tab'=>'done']) : route('admin.tasks.index') }}" class="mt-3 inline-block text-sm text-indigo-500 hover:underline">Clear filters</a>
    @endif
</div>
@else
@php $doneStatuses = ['approved','delivered','archived']; @endphp

{{-- ── View toggle (Alpine) ── --}}
<div x-data="taskViewToggle()" style="margin-bottom:22px;">
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
    <div style="display:flex;gap:2px;background:#F3F4F6;border-radius:12px;padding:4px;width:fit-content;">
        <button @click="setView('table')"
                :style="view==='table'
                    ? 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);'
                    : 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;'"
                style="display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <i class="fa fa-table-list" style="font-size:11px;"></i> Table
        </button>
        <button @click="setView('cards')"
                :style="view==='cards'
                    ? 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);'
                    : 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;'"
                style="display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;">
            <i class="fa fa-grip" style="font-size:11px;"></i> Cards
        </button>
    </div>
    <span style="font-size:12px;color:#9CA3AF;">{{ $tasks->total() }} {{ Str::plural('task',$tasks->total()) }}</span>
</div>

{{-- ── CARD VIEW ── --}}
<div x-show="view==='cards'">
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

    @php
    $cardEditData = [
        'id'          => $task->id,
        'title'       => $task->title,
        'project_id'  => $task->project_id,
        'customer_id' => $task->customer_id,
        'assigned_to' => $task->assigned_to,
        'priority'    => $task->priority ?? 'medium',
        'deadline_raw'=> $task->deadline?->format('Y-m-d'),
        'description' => $task->description,
        'update_url'  => route('admin.tasks.update', $task),
    ];
    @endphp
    <div class="relative group/card">

    {{-- Action buttons (top-right, visible on hover) --}}
    <div style="position:absolute;top:10px;right:10px;z-index:10;display:flex;gap:4px;">
        <button type="button"
                @click.prevent="openEdit(@js($cardEditData))"
                class="opacity-0 group-hover/card:opacity-100 transition-opacity"
                style="width:28px;height:28px;border-radius:8px;background:rgba(99,102,241,.1);color:#6366F1;border:1px solid rgba(99,102,241,.2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;"
                onmouseover="this.style.background='rgba(99,102,241,.2)'" onmouseout="this.style.background='rgba(99,102,241,.1)'"
                title="Edit task">
            <i class="fas fa-pen"></i>
        </button>
        @if(in_array($task->status, ['approved','delivered','archived']))
        <form method="POST" action="{{ route('admin.tasks.reopen', $task) }}" style="margin:0;">
            @csrf
            <button type="submit"
                    class="opacity-0 group-hover/card:opacity-100 transition-opacity"
                    style="width:28px;height:28px;border-radius:8px;background:rgba(217,119,6,.1);color:#D97706;border:1px solid rgba(217,119,6,.2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;"
                    onmouseover="this.style.background='rgba(217,119,6,.2)'" onmouseout="this.style.background='rgba(217,119,6,.1)'"
                    title="Reopen task">
                <i class="fas fa-rotate-right"></i>
            </button>
        </form>
        @else
        <form method="POST" action="{{ route('admin.tasks.archive', $task) }}" style="margin:0;"
              onsubmit="return confirm('Archive &quot;{{ addslashes($task->title) }}&quot;?')">
            @csrf
            <button type="submit"
                    class="opacity-0 group-hover/card:opacity-100 transition-opacity"
                    style="width:28px;height:28px;border-radius:8px;background:rgba(22,163,74,.1);color:#16A34A;border:1px solid rgba(22,163,74,.2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;"
                    onmouseover="this.style.background='rgba(22,163,74,.2)'" onmouseout="this.style.background='rgba(22,163,74,.1)'"
                    title="Archive (close) task">
                <i class="fas fa-check"></i>
            </button>
        </form>
        @endif
        <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}"
              onsubmit="return confirm('Move &quot;{{ addslashes($task->title) }}&quot; to the Recycle Bin?')"
              style="margin:0;">
            @csrf @method('DELETE')
            <button type="submit"
                    class="opacity-0 group-hover/card:opacity-100 transition-opacity"
                    style="width:28px;height:28px;border-radius:8px;background:rgba(239,68,68,.1);color:#EF4444;border:1px solid rgba(239,68,68,.2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;"
                    onmouseover="this.style.background='rgba(239,68,68,.2)'" onmouseout="this.style.background='rgba(239,68,68,.1)'"
                    title="Move to Recycle Bin">
                <i class="fas fa-trash-can"></i>
            </button>
        </form>
    </div>

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
            <div class="flex items-center gap-2 mt-auto flex-wrap">
                @if($task->assignee)
                <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr($task->assignee->name,0,1)) }}
                </div>
                <span class="text-xs text-gray-500 truncate">{{ $task->assignee->name }}</span>
                @else
                <i class="fas fa-user-slash text-gray-300 text-xs"></i>
                <span class="text-xs text-gray-300">Unassigned</span>
                @endif
                @if($task->socialAssignee && $task->socialAssignee->id !== $task->assigned_to)
                <div class="flex items-center gap-1 ml-1" title="Social: {{ $task->socialAssignee->name }}">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:#8B5CF6;">
                        {{ strtoupper(substr($task->socialAssignee->name,0,1)) }}
                    </div>
                    <i class="fab fa-share-alt" style="font-size:9px;color:#8B5CF6;"></i>
                </div>
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
                    {{ $daysLeft == 0 ? 'Due today' : ($daysLeft == 1 ? 'Due tomorrow' : 'Due '.$task->deadline->format(config('app.date_format', 'M d, Y'))) }}
                </span>
                @else
                <span class="text-xs text-gray-200">No deadline</span>
                @endif
            </div>

        </div>
    </a>

    {{-- Social Media Row (done tab only) --}}
    @if($isDoneTab && $task->social_required)
    @php
        $platformIcons = ['facebook'=>['fab fa-facebook','#1877F2'],'instagram'=>['fab fa-instagram','#E1306C'],'twitter'=>['fab fa-x-twitter','#000'],'tiktok'=>['fab fa-tiktok','#010101'],'youtube'=>['fab fa-youtube','#FF0000'],'snapchat'=>['fab fa-snapchat-ghost','#F7CA00'],'linkedin'=>['fab fa-linkedin','#0A66C2'],'other'=>['fas fa-share-nodes','#6366F1']];
        $socialPost = $task->socialPosts->first();
        $isPosted = $socialPost !== null;
        $platform = $socialPost?->platform ?? null;
        $pIcon = $platform ? ($platformIcons[$platform] ?? $platformIcons['other']) : null;
    @endphp
    <div style="margin-top:6px;border-radius:10px;overflow:hidden;border:1px solid {{ $isPosted ? '#BBF7D0' : '#FDE68A' }};background:{{ $isPosted ? '#F0FDF4' : '#FFFBEB' }};">
        <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;">
            {{-- Platform icon --}}
            @if($pIcon)
            <div style="width:26px;height:26px;border-radius:7px;background:{{ $isPosted ? '#DCFCE7' : '#FEF3C7' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="{{ $pIcon[0] }}" style="font-size:13px;color:{{ $pIcon[1] }};"></i>
            </div>
            @else
            <div style="width:26px;height:26px;border-radius:7px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fab fa-share-nodes" style="font-size:12px;color:#8B5CF6;"></i>
            </div>
            @endif

            {{-- Info --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                    <span style="font-size:10px;font-weight:700;color:{{ $isPosted ? '#15803D' : '#92400E' }};text-transform:uppercase;letter-spacing:.05em;">
                        {{ $platform ? ucfirst($platform) : 'Social' }}
                    </span>
                    @if($task->socialAssignee)
                    <span style="font-size:10px;color:#6B7280;">· {{ $task->socialAssignee->name }}</span>
                    @endif
                </div>
                @if($isPosted && $socialPost->note)
                <p style="font-size:10px;color:#6B7280;margin:1px 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $socialPost->note }}</p>
                @endif
            </div>

            {{-- Status badge --}}
            @if($isPosted)
            <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                @if($socialPost->post_url)
                <a href="{{ $socialPost->post_url }}" target="_blank" rel="noopener"
                   style="font-size:10px;color:#4F46E5;text-decoration:none;background:#EEF2FF;padding:2px 7px;border-radius:6px;font-weight:600;"
                   onmouseover="this.style.background='#E0E7FF'" onmouseout="this.style.background='#EEF2FF'">
                    <i class="fas fa-arrow-up-right-from-square" style="font-size:8px;"></i> View
                </a>
                @endif
                <span style="font-size:10px;font-weight:700;color:#15803D;background:#DCFCE7;padding:2px 7px;border-radius:6px;white-space:nowrap;">
                    <i class="fas fa-circle-check" style="font-size:9px;"></i> {{ $socialPost->created_at->format('M d') }}
                </span>
            </div>
            @else
            <span style="font-size:10px;font-weight:700;color:#92400E;background:#FEF3C7;padding:2px 7px;border-radius:6px;white-space:nowrap;flex-shrink:0;">
                <i class="fas fa-hourglass-half" style="font-size:9px;"></i> Pending
            </span>
            @endif
        </div>

        {{-- Extra social posts (multiple platforms) --}}
        @foreach($task->socialPosts->skip(1) as $sp)
        @php $spIcon = $platformIcons[$sp->platform] ?? $platformIcons['other']; @endphp
        <div style="display:flex;align-items:center;gap:8px;padding:5px 12px 5px 46px;border-top:1px solid {{ $isPosted ? '#BBF7D0' : '#FDE68A' }};">
            <i class="{{ $spIcon[0] }}" style="font-size:12px;color:{{ $spIcon[1] }};flex-shrink:0;"></i>
            <span style="font-size:10px;color:#6B7280;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ ucfirst($sp->platform) }}{{ $sp->note ? ' · '.$sp->note : '' }}</span>
            @if($sp->post_url)
            <a href="{{ $sp->post_url }}" target="_blank" rel="noopener" style="font-size:10px;color:#4F46E5;text-decoration:none;background:#EEF2FF;padding:2px 7px;border-radius:6px;font-weight:600;flex-shrink:0;">
                <i class="fas fa-arrow-up-right-from-square" style="font-size:8px;"></i> View
            </a>
            @endif
            <span style="font-size:10px;font-weight:600;color:#15803D;white-space:nowrap;flex-shrink:0;">
                <i class="fas fa-circle-check" style="font-size:9px;"></i> {{ $sp->created_at->format('M d') }}
            </span>
        </div>
        @endforeach
    </div>
    @endif

    </div>{{-- /relative group/card --}}
@endforeach
</div>
</div>{{-- end cards view --}}

{{-- ── LIST / TABLE VIEW ── --}}
<div x-show="view==='table'" x-cloak>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-5">
<table class="task-list-table">
    <thead>
        <tr>
            <th>Task</th>
            <th>Project</th>
            <th>Assignee</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Deadline</th>
            <th style="text-align:right;">Actions</th>
        </tr>
    </thead>
    <tbody>
    @foreach($tasks as $task)
    @php
        $sm        = $statusMeta[$task->status] ?? $statusMeta['draft'];
        $isOverdue = $task->deadline && $task->deadline->isPast() && !in_array($task->status, $doneStatuses);
        $pm        = $priorityMeta[$task->priority] ?? ['color'=>'#9CA3AF','bg'=>'#F3F4F6','label'=>ucfirst($task->priority)];
        $rowEditData = [
            'id'          => $task->id,
            'title'       => $task->title,
            'project_id'  => $task->project_id,
            'customer_id' => $task->customer_id,
            'assigned_to' => $task->assigned_to,
            'priority'    => $task->priority ?? 'medium',
            'deadline_raw'=> $task->deadline?->format('Y-m-d'),
            'description' => $task->description,
            'update_url'  => route('admin.tasks.update', $task),
        ];
    @endphp
    <tr>
        {{-- Task title + status badge --}}
        <td style="max-width:260px;">
            <a href="{{ route('admin.tasks.show', $task) }}"
               style="font-size:13px;font-weight:600;color:#111827;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:240px;"
               onmouseover="this.style.color='#4F46E5'" onmouseout="this.style.color='#111827'"
               title="{{ $task->title }}">
                {{ $task->title }}
            </a>
        </td>
        {{-- Project --}}
        <td>
            <span style="font-size:12px;color:#6B7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;display:block;">
                <i class="fas fa-diagram-project" style="font-size:10px;margin-right:4px;color:#C4B5FD;"></i>
                {{ $task->project?->name ?? '—' }}
            </span>
        </td>
        {{-- Assignee --}}
        <td>
            @if($task->assignee)
            <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <div style="width:26px;height:26px;border-radius:50%;background:#6366F1;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:700;flex-shrink:0;">
                        {{ strtoupper(substr($task->assignee->name,0,1)) }}
                    </div>
                    <span style="font-size:12px;color:#374151;white-space:nowrap;">{{ $task->assignee->name }}</span>
                </div>
                @if($task->socialAssignee && $task->socialAssignee->id !== $task->assigned_to)
                <div style="display:flex;align-items:center;gap:4px;" title="Social: {{ $task->socialAssignee->name }}">
                    <div style="width:22px;height:22px;border-radius:50%;background:#8B5CF6;display:flex;align-items:center;justify-content:center;color:#fff;font-size:9px;font-weight:700;flex-shrink:0;">
                        {{ strtoupper(substr($task->socialAssignee->name,0,1)) }}
                    </div>
                    <span style="font-size:11px;color:#8B5CF6;white-space:nowrap;">{{ $task->socialAssignee->name }}</span>
                    <i class="fab fa-share-alt" style="font-size:9px;color:#8B5CF6;"></i>
                </div>
                @endif
            </div>
            @else
            <span style="font-size:12px;color:#D1D5DB;">Unassigned</span>
            @endif
        </td>
        {{-- Priority --}}
        <td>
            <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:{{ $pm['bg'] }};color:{{ $pm['color'] }};white-space:nowrap;">
                {{ $pm['label'] }}
            </span>
        </td>
        {{-- Status --}}
        <td>
            <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:{{ $sm['bg'] }};color:{{ $sm['color'] }};white-space:nowrap;">
                {{ $sm['label'] }}
            </span>
        </td>
        {{-- Deadline --}}
        <td style="white-space:nowrap;">
            @if($isOverdue)
                <span style="font-size:12px;font-weight:600;color:#EF4444;">
                    <i class="fas fa-triangle-exclamation" style="font-size:10px;margin-right:3px;"></i>{{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}
                </span>
            @elseif($task->deadline)
                <span style="font-size:12px;color:#6B7280;">{{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}</span>
            @else
                <span style="font-size:12px;color:#D1D5DB;">—</span>
            @endif
        </td>
        {{-- Actions --}}
        <td style="text-align:right;white-space:nowrap;">
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                <a href="{{ route('admin.tasks.show', $task) }}"
                   style="width:30px;height:30px;border-radius:8px;background:#EEF2FF;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6366F1;font-size:12px;text-decoration:none;transition:background .15s;"
                   onmouseover="this.style.background='#E0E7FF'" onmouseout="this.style.background='#EEF2FF'"
                   title="View task">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
                <button type="button"
                        @click="openEdit(@js($rowEditData))"
                        style="width:30px;height:30px;border-radius:8px;background:#F5F3FF;border:1px solid #DDD6FE;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#7C3AED;font-size:12px;transition:background .15s;"
                        onmouseover="this.style.background='#EDE9FE'" onmouseout="this.style.background='#F5F3FF'"
                        title="Edit task">
                    <i class="fas fa-pen"></i>
                </button>
                @if(in_array($task->status, ['approved','delivered','archived']))
                <form method="POST" action="{{ route('admin.tasks.reopen', $task) }}" style="margin:0;">
                    @csrf
                    <button type="submit"
                            style="width:30px;height:30px;border-radius:8px;background:#FFFBEB;border:1px solid #FDE68A;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#D97706;font-size:12px;transition:background .15s;"
                            onmouseover="this.style.background='#FEF3C7'" onmouseout="this.style.background='#FFFBEB'"
                            title="Reopen task">
                        <i class="fas fa-rotate-right"></i>
                    </button>
                </form>
                @else
                <form method="POST" action="{{ route('admin.tasks.archive', $task) }}" style="margin:0;"
                      onsubmit="return confirm('Archive &quot;{{ addslashes($task->title) }}&quot;?')">
                    @csrf
                    <button type="submit"
                            style="width:30px;height:30px;border-radius:8px;background:#F0FDF4;border:1px solid #BBF7D0;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#16A34A;font-size:12px;transition:background .15s;"
                            onmouseover="this.style.background='#DCFCE7'" onmouseout="this.style.background='#F0FDF4'"
                            title="Archive (close) task">
                        <i class="fas fa-check"></i>
                    </button>
                </form>
                @endif
                <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}"
                      onsubmit="return confirm('Move &quot;{{ addslashes($task->title) }}&quot; to the Recycle Bin?')"
                      style="margin:0;">
                    @csrf @method('DELETE')
                    <button type="submit"
                            style="width:30px;height:30px;border-radius:8px;background:#FEF2F2;border:1px solid #FECACA;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#EF4444;font-size:12px;transition:background .15s;"
                            onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'"
                            title="Move to Recycle Bin">
                        <i class="fas fa-trash-can"></i>
                    </button>
                </form>
            </div>
        </td>
    </tr>
    {{-- Social sub-row (done tab only) --}}
    @if($isDoneTab && $task->social_required)
    @php
        $platformIcons = $platformIcons ?? ['facebook'=>['fab fa-facebook','#1877F2'],'instagram'=>['fab fa-instagram','#E1306C'],'twitter'=>['fab fa-x-twitter','#000'],'tiktok'=>['fab fa-tiktok','#010101'],'youtube'=>['fab fa-youtube','#FF0000'],'snapchat'=>['fab fa-snapchat-ghost','#F7CA00'],'linkedin'=>['fab fa-linkedin','#0A66C2'],'other'=>['fas fa-share-nodes','#6366F1']];
        $socialPost = $task->socialPosts->first();
        $isPosted = $socialPost !== null;
        $platform = $socialPost?->platform ?? null;
        $pIcon = $platform ? ($platformIcons[$platform] ?? $platformIcons['other']) : $platformIcons['other'];
    @endphp
    <tr style="background:{{ $isPosted ? '#F0FDF4' : '#FFFBEB' }};">
        <td colspan="6" style="padding:0 10px 8px 28px;">
            <div style="display:flex;align-items:flex-start;gap:6px;flex-wrap:wrap;">
                {{-- Each social post --}}
                @forelse($task->socialPosts as $sp)
                @php $spIcon = $platformIcons[$sp->platform] ?? $platformIcons['other']; @endphp
                <div style="display:inline-flex;align-items:center;gap:6px;background:{{ $isPosted ? '#DCFCE7' : '#FEF3C7' }};border:1px solid {{ $isPosted ? '#BBF7D0' : '#FDE68A' }};border-radius:8px;padding:5px 10px;">
                    <i class="{{ $spIcon[0] }}" style="font-size:13px;color:{{ $spIcon[1] }};flex-shrink:0;"></i>
                    <span style="font-size:11px;font-weight:600;color:{{ $isPosted ? '#15803D' : '#92400E' }};">{{ ucfirst($sp->platform) }}</span>
                    @if($task->socialAssignee)
                    <span style="font-size:10px;color:#6B7280;">· {{ $task->socialAssignee->name }}</span>
                    @endif
                    @if($sp->note)
                    <span style="font-size:10px;color:#6B7280;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $sp->note }}">· {{ $sp->note }}</span>
                    @endif
                    @if($sp->post_url)
                    <a href="{{ $sp->post_url }}" target="_blank" rel="noopener"
                       style="font-size:10px;color:#4F46E5;text-decoration:none;background:#EEF2FF;padding:2px 7px;border-radius:5px;font-weight:600;flex-shrink:0;"
                       onmouseover="this.style.background='#E0E7FF'" onmouseout="this.style.background='#EEF2FF'">
                        <i class="fas fa-arrow-up-right-from-square" style="font-size:8px;"></i> View
                    </a>
                    @endif
                    <span style="font-size:10px;font-weight:700;color:#15803D;white-space:nowrap;flex-shrink:0;">
                        <i class="fas fa-circle-check" style="font-size:9px;"></i> {{ $sp->created_at->format('M d') }}
                    </span>
                </div>
                @empty
                <div style="display:inline-flex;align-items:center;gap:6px;background:#FEF3C7;border:1px solid #FDE68A;border-radius:8px;padding:5px 10px;">
                    <i class="fab fa-share-nodes" style="font-size:12px;color:#8B5CF6;"></i>
                    @if($task->socialAssignee)
                    <span style="font-size:11px;color:#6B7280;">{{ $task->socialAssignee->name }}</span>
                    @endif
                    <span style="font-size:10px;font-weight:700;color:#92400E;white-space:nowrap;">
                        <i class="fas fa-hourglass-half" style="font-size:9px;"></i> Pending post
                    </span>
                </div>
                @endforelse
            </div>
        </td>
    </tr>
    @endif
    @endforeach
    </tbody>
</table>
</div>
</div>{{-- end table view --}}

{{-- ── Edit Task Modal ── --}}
<template x-teleport="body">
<div x-show="editModal" x-cloak
     @keydown.escape.window="closeEdit()"
     style="position:fixed;inset:0;z-index:10000;">
    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;">
    <div @click="closeEdit()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);"></div>
    <div
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="position:relative;width:100%;max-width:560px;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,.25);">
        <template x-if="editTask">
        <form :action="editTask.update_url" method="POST">
            @csrf
            @method('PATCH')
            {{-- Header --}}
            <div style="padding:20px 24px;background:linear-gradient(135deg,#4F46E5,#6366F1);display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa fa-pen" style="color:#fff;font-size:14px;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:14px;font-weight:700;color:#fff;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="editTask.title"></p>
                    <p style="font-size:11px;color:rgba(255,255,255,.7);margin:2px 0 0;">Edit task details</p>
                </div>
                <button type="button" @click="closeEdit()"
                        style="width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,.15);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;"
                        onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                    <i class="fa fa-xmark"></i>
                </button>
            </div>
            {{-- Body --}}
            <div style="padding:24px;display:flex;flex-direction:column;gap:16px;max-height:70vh;overflow-y:auto;">

                {{-- Title --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                        Task Title <span style="color:#EF4444;">*</span>
                    </label>
                    <input type="text" name="title" x-model="editForm.title" required
                           style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;outline:none;transition:border-color .15s;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                </div>

                {{-- Project + Customer row --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Project</label>
                        <div style="position:relative;">
                            <select name="project_id" x-model="editForm.project_id"
                                    style="width:100%;padding:9px 32px 9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;appearance:none;background:#fff;cursor:pointer;outline:none;box-sizing:border-box;"
                                    onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                                <option value="">No project</option>
                                @foreach($projects as $proj)
                                <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                                @endforeach
                            </select>
                            <div style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9CA3AF;font-size:10px;">
                                <i class="fa fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Customer</label>
                        <div style="position:relative;">
                            <select name="customer_id" x-model="editForm.customer_id"
                                    style="width:100%;padding:9px 32px 9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;appearance:none;background:#fff;cursor:pointer;outline:none;box-sizing:border-box;"
                                    onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                                <option value="">No customer</option>
                                @foreach($customers as $cust)
                                <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                                @endforeach
                            </select>
                            <div style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9CA3AF;font-size:10px;">
                                <i class="fa fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Assignee + Priority row --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Assignee</label>
                        <div style="position:relative;">
                            <select name="assigned_to" x-model="editForm.assigned_to"
                                    style="width:100%;padding:9px 32px 9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;appearance:none;background:#fff;cursor:pointer;outline:none;box-sizing:border-box;"
                                    onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                                <option value="">Unassigned</option>
                                @foreach($assignableUsers as $usr)
                                <option value="{{ $usr->id }}">{{ $usr->name }}</option>
                                @endforeach
                            </select>
                            <div style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9CA3AF;font-size:10px;">
                                <i class="fa fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Priority</label>
                        <div style="position:relative;">
                            <select name="priority" x-model="editForm.priority"
                                    style="width:100%;padding:9px 32px 9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;appearance:none;background:#fff;cursor:pointer;outline:none;box-sizing:border-box;"
                                    onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                            <div style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9CA3AF;font-size:10px;">
                                <i class="fa fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Deadline --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Deadline</label>
                    <input type="date" name="deadline" x-model="editForm.deadline"
                           style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;outline:none;transition:border-color .15s;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                </div>

                {{-- Description --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Description</label>
                    <textarea name="description" x-model="editForm.description" rows="3"
                              style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;outline:none;resize:vertical;transition:border-color .15s;box-sizing:border-box;"
                              onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"
                              placeholder="Optional description…"></textarea>
                </div>

            </div>
            {{-- Footer --}}
            <div style="padding:16px 24px;border-top:1px solid #F3F4F6;display:flex;align-items:center;justify-content:flex-end;gap:10px;background:#FAFAFA;">
                <button type="button" @click="closeEdit()"
                        style="padding:9px 20px;border-radius:10px;border:1.5px solid #E5E7EB;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='#fff'">
                    Cancel
                </button>
                <button type="submit"
                        style="padding:9px 22px;border-radius:10px;border:none;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(79,70,229,.4);transition:opacity .15s;"
                        onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                    <i class="fa fa-check" style="margin-right:6px;font-size:11px;"></i>Save Changes
                </button>
            </div>
        </form>
        </template>
    </div>
    </div>
</div>
</template>

</div>{{-- end x-data taskViewToggle --}}

<x-pagination :paginator="$tasks" />
@endif

<script>
function taskViewToggle() {
    var saved = null;
    try { saved = localStorage.getItem('adminTaskView'); } catch(e) {}
    return {
        view: saved || 'table',
        setView(v) {
            this.view = v;
            try { localStorage.setItem('adminTaskView', v); } catch(e) {}
        },
        editModal: false,
        editTask: null,
        editForm: { title:'', project_id:'', customer_id:'', assigned_to:'', priority:'', deadline:'', description:'' },
        openEdit(task) {
            this.editTask = task;
            this.editForm = {
                title:       task.title,
                project_id:  task.project_id  ? String(task.project_id)  : '',
                customer_id: task.customer_id ? String(task.customer_id) : '',
                assigned_to: task.assigned_to ? String(task.assigned_to) : '',
                priority:    task.priority    || 'medium',
                deadline:    task.deadline_raw || '',
                description: task.description || '',
            };
            this.editModal = true;
        },
        closeEdit() { this.editModal = false; this.editTask = null; },
    };
}
</script>

@endsection

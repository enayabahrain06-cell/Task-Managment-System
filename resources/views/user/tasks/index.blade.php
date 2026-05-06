@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">My Tasks</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $tasks->total() }} total tasks assigned to you</p>
    </div>
</div>

@php
    $pausedCount = auth()->user()->tasks()->where('status', 'paused')->count();
@endphp

{{-- Next-day reminder for paused tasks ─────────────────────────────── --}}
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

{{-- Mini Stats --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-gray-600">{{ auth()->user()->tasks()->whereIn('status',['draft','assigned','viewed','revision_requested'])->count() }}</p>
        <p class="text-xs text-gray-400 mt-1">Pending</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-amber-500">{{ auth()->user()->tasks()->whereIn('status',['in_progress','paused','submitted'])->count() }}</p>
        <p class="text-xs text-gray-400 mt-1">In Progress</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-emerald-500">{{ auth()->user()->tasks()->whereIn('status',['approved','delivered','archived'])->count() }}</p>
        <p class="text-xs text-gray-400 mt-1">Completed</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="divide-y divide-gray-50">
        @forelse($tasks as $task)
        @php
            $isDone     = in_array($task->status, ['approved','delivered','archived']);
            $isPaused   = $task->status === 'paused';
            $isRevision = $task->status === 'revision_requested';
        @endphp
        <a href="{{ route('user.tasks.show', $task) }}"
           class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50/70 transition group
               {{ $isPaused ? 'bg-amber-50/40 border-l-2 border-amber-300' : '' }}
               {{ $isRevision ? 'bg-red-50/40 border-l-2 border-red-300' : '' }}
               {{ $task->deadline && $task->deadline < now() && !$isDone && !$isPaused && !$isRevision ? 'bg-red-50/30' : '' }}">
            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0
                {{ $isDone ? 'bg-emerald-400' : ($task->status === 'in_progress' ? 'bg-amber-400' : ($isPaused ? 'bg-gray-400' : ($task->deadline && $task->deadline < now() ? 'bg-red-400' : 'bg-gray-300'))) }}">
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
                <p class="text-xs text-gray-400 mt-0.5">{{ $task->project->name }}</p>
            </div>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0
                {{ $task->priority === 'high' ? 'bg-red-100 text-red-600' : ($task->priority === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                {{ ucfirst($task->priority) }}
            </span>
            <span class="text-xs flex-shrink-0 {{ $task->deadline && $task->deadline < now() && !$isDone ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                {{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}
            </span>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0
                {{ $isDone ? 'bg-emerald-100 text-emerald-700' : ($task->status === 'in_progress' ? 'bg-amber-100 text-amber-700' : ($isPaused ? 'bg-gray-100 text-gray-500' : ($task->status === 'submitted' ? 'bg-purple-100 text-purple-700' : ($isRevision ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600')))) }}">
                {{ $isPaused ? 'Paused' : str_replace('_', ' ', ucfirst($task->status)) }}
            </span>
            <i class="fa fa-chevron-right text-gray-300 text-xs flex-shrink-0 group-hover:text-indigo-400 transition"></i>
        </a>
        @empty
        <div class="px-5 py-16 text-center">
            <i class="fa fa-clipboard-list text-5xl text-gray-200 mb-3"></i>
            <p class="text-gray-400">No tasks assigned to you yet</p>
        </div>
        @endforelse
    </div>
    @if($tasks->hasPages())
    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
        {{ $tasks->links() }}
    </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">My Tasks</h1>
        <p class="text-sm text-gray-500 mt-0.5">Welcome back, {{ auth()->user()->name }}!</p>
    </div>
    <a href="{{ route('user.tasks.index') }}" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm shadow-indigo-200">
        <i class="fa fa-list"></i> All Tasks
    </a>
</div>

{{-- Stats --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-indigo-600 rounded-xl p-5 text-white shadow-lg shadow-indigo-200 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-20 h-20 bg-indigo-500/30 rounded-full -translate-y-4 translate-x-4"></div>
        <p class="text-indigo-200 text-sm font-medium mb-2">Total Tasks</p>
        <p class="text-4xl font-bold">{{ $tasks->total() }}</p>
        <p class="text-indigo-200 text-xs mt-1">Assigned to you</p>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <p class="text-gray-500 text-sm font-medium mb-2">Overdue</p>
        <p class="text-4xl font-bold text-red-500">{{ $overdueTasks }}</p>
        <p class="text-gray-400 text-xs mt-1">Past deadline</p>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <p class="text-gray-500 text-sm font-medium mb-2">Completed</p>
        <p class="text-4xl font-bold text-emerald-500">{{ auth()->user()->tasks()->where('status','completed')->count() }}</p>
        <p class="text-gray-400 text-xs mt-1">Done</p>
    </div>
</div>

{{-- Task List --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-900">Recent Tasks</h3>
        <a href="{{ route('user.tasks.index') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
    </div>
    <div class="divide-y divide-gray-50">
        @forelse($tasks as $task)
        <a href="{{ route('user.tasks.show', $task) }}"
           class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50/70 transition group">
            {{-- Status dot --}}
            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0 mt-0.5
                {{ $task->status === 'completed' ? 'bg-emerald-400' : ($task->status === 'in_progress' ? 'bg-amber-400' : 'bg-gray-300') }}">
            </div>
            {{-- Title + Project --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate group-hover:text-indigo-600 transition {{ $task->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                    {{ $task->title }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $task->project->name }}</p>
            </div>
            {{-- Priority badge --}}
            <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0
                {{ $task->priority === 'high' ? 'bg-red-100 text-red-600' : ($task->priority === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                {{ ucfirst($task->priority) }}
            </span>
            {{-- Deadline --}}
            <span class="text-xs flex-shrink-0 {{ $task->deadline < now() && $task->status !== 'completed' ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                {{ $task->deadline->format('M d') }}
            </span>
            {{-- Status badge --}}
            <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0
                {{ $task->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($task->status === 'in_progress' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                {{ str_replace('_', ' ', ucfirst($task->status)) }}
            </span>
        </a>
        @empty
        <div class="px-5 py-12 text-center">
            <i class="fa fa-clipboard-list text-4xl text-gray-200 mb-3"></i>
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

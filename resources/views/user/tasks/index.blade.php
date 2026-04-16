@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">My Tasks</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $tasks->total() }} total tasks assigned to you</p>
    </div>
</div>

{{-- Mini Stats --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-gray-600">{{ auth()->user()->tasks()->where('status','pending')->count() }}</p>
        <p class="text-xs text-gray-400 mt-1">Pending</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-amber-500">{{ auth()->user()->tasks()->where('status','in_progress')->count() }}</p>
        <p class="text-xs text-gray-400 mt-1">In Progress</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-emerald-500">{{ auth()->user()->tasks()->where('status','completed')->count() }}</p>
        <p class="text-xs text-gray-400 mt-1">Completed</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="divide-y divide-gray-50">
        @forelse($tasks as $task)
        <a href="{{ route('user.tasks.show', $task) }}"
           class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50/70 transition group {{ $task->deadline < now() && $task->status !== 'completed' ? 'bg-red-50/30' : '' }}">
            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0
                {{ $task->status === 'completed' ? 'bg-emerald-400' : ($task->status === 'in_progress' ? 'bg-amber-400' : ($task->deadline < now() ? 'bg-red-400' : 'bg-gray-300')) }}">
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate group-hover:text-indigo-600 transition {{ $task->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                    {{ $task->title }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $task->project->name }}</p>
            </div>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0
                {{ $task->priority === 'high' ? 'bg-red-100 text-red-600' : ($task->priority === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                {{ ucfirst($task->priority) }}
            </span>
            <span class="text-xs flex-shrink-0 {{ $task->deadline < now() && $task->status !== 'completed' ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                {{ $task->deadline->format('M d, Y') }}
            </span>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0
                {{ $task->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($task->status === 'in_progress' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                {{ str_replace('_', ' ', ucfirst($task->status)) }}
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

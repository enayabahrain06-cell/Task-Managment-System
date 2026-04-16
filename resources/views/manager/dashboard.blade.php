@extends('layouts.app')

@section('title', 'Overview')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Overview</h1>
        <p class="text-sm text-gray-500 mt-0.5">Welcome back, {{ auth()->user()->name }}!</p>
    </div>
    <button class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm shadow-indigo-200">
        <i class="fa fa-download"></i> Export
    </button>
</div>

{{-- Stats --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-indigo-600 rounded-xl p-5 text-white shadow-lg shadow-indigo-200 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-20 h-20 bg-indigo-500/30 rounded-full -translate-y-4 translate-x-4"></div>
        <p class="text-indigo-200 text-sm font-medium mb-2">Team Members</p>
        <p class="text-4xl font-bold">{{ $teamUsers->count() }}</p>
        <p class="text-indigo-200 text-xs mt-1">Active users</p>
    </div>
    <div class="bg-indigo-600 rounded-xl p-5 text-white shadow-lg shadow-indigo-200 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-20 h-20 bg-indigo-500/30 rounded-full -translate-y-4 translate-x-4"></div>
        <p class="text-indigo-200 text-sm font-medium mb-2">Projects</p>
        <p class="text-4xl font-bold">{{ $projects->count() }}</p>
        <p class="text-indigo-200 text-xs mt-1">Total projects</p>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <p class="text-gray-500 text-sm font-medium mb-2">Overdue Tasks</p>
        <p class="text-4xl font-bold text-red-500">{{ $overdueTasks->count() }}</p>
        <p class="text-gray-400 text-xs mt-1">Need attention</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    {{-- Team Workload --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Team Workload</h3>
            <a href="{{ route('team.index') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
        </div>
        @if($teamUsers->count() > 0)
        <div class="space-y-3">
            @foreach($teamUsers as $user)
            @php $count = $workload[$user->id] ?? 0; @endphp
            <div>
                <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold"
                             style="background: {{ ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6'][$loop->index % 5] }}">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <span class="text-sm font-medium text-gray-800">{{ $user->name }}</span>
                    </div>
                    <span class="text-sm font-bold {{ $count > 5 ? 'text-red-500' : 'text-emerald-600' }}">{{ $count }} tasks</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full {{ $count > 5 ? 'bg-red-400' : 'bg-emerald-400' }}"
                         style="width: {{ min(100, ($count / 10) * 100) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-400 text-center py-8">No team members yet</p>
        @endif
    </div>

    {{-- Overdue Tasks --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Overdue Tasks</h3>
            <span class="text-xs bg-red-100 text-red-600 font-medium px-2 py-0.5 rounded-full">{{ $overdueTasks->count() }}</span>
        </div>
        <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
            @forelse($overdueTasks as $task)
            <div class="flex items-start gap-3 p-3 bg-red-50 rounded-lg border border-red-100">
                <div class="w-2 h-2 rounded-full bg-red-400 mt-1.5 flex-shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $task->title }}</p>
                    <p class="text-xs text-gray-500">{{ $task->project->name }}</p>
                    <p class="text-xs text-red-500 font-medium mt-0.5">
                        <i class="fa fa-clock"></i> Due {{ $task->deadline->format('M d, Y') }}
                    </p>
                </div>
            </div>
            @empty
            <div class="text-center py-8">
                <i class="fa fa-check-circle text-3xl text-emerald-300 mb-2"></i>
                <p class="text-sm text-gray-400">No overdue tasks</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

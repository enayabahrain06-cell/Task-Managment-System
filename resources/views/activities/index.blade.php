@extends('layouts.app')

@section('title', 'Activities')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Activities</h1>
        <p class="text-sm text-gray-500 mt-0.5">Latest activity feed across all teams</p>
    </div>
    <div class="flex items-center gap-2">
        <button class="flex items-center gap-2 text-sm border border-gray-200 bg-white rounded-lg px-3 py-2 hover:border-indigo-300 transition">
            <i class="fa fa-filter text-gray-400"></i> Filters
        </button>
        <button class="flex items-center gap-2 text-sm border border-gray-200 bg-white rounded-lg px-3 py-2 hover:border-indigo-300 transition">
            <i class="fa fa-sort text-gray-400"></i> Most important first
        </button>
        <button class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <i class="fa fa-rocket"></i> Release
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

    {{-- Left: Teams --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Teams</h3>
            @php $totalNotif = 0; @endphp
            <span class="text-xs bg-indigo-100 text-indigo-600 font-medium px-2 py-0.5 rounded-full">
                {{ $teams->flatten()->count() }} members
            </span>
        </div>

        @if($selectedUser)
        <div class="mb-3 px-1">
            <a href="{{ route('activities.index') }}" class="inline-flex items-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">
                <i class="fa fa-times-circle"></i> Clear filter
            </a>
        </div>
        @endif

        <div class="space-y-2">
            @php $teamColors = ['manager' => '#6366F1', 'user' => '#10B981']; @endphp
            @foreach($teams as $role => $members)
            <div class="mb-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-1">{{ ucfirst($role) }} Team</p>
                @foreach($members as $member)
                @php $isActive = $selectedUser && $selectedUser->id === $member->id; @endphp
                <a href="{{ route('activities.index', ['user_id' => $member->id]) }}"
                   class="flex items-center gap-2 p-2 rounded-lg transition {{ $isActive ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                         style="background:{{ $teamColors[$role] ?? '#6366F1' }}">
                        {{ strtoupper(substr($member->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium {{ $isActive ? 'text-indigo-700' : 'text-gray-800' }} truncate">{{ $member->name }}</p>
                        <p class="text-xs text-gray-400">{{ $member->tasks_count }} tasks</p>
                    </div>
                    <span class="w-2 h-2 bg-emerald-400 rounded-full flex-shrink-0"></span>
                </a>
                @endforeach
            </div>
            @endforeach

            @if($teams->flatten()->count() === 0)
            <p class="text-sm text-gray-400 text-center py-6">No team members</p>
            @endif
        </div>
    </div>

    {{-- Right: Activity Feed --}}
    <div class="lg:col-span-3 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h3 class="font-semibold text-gray-900">Latest Activity Feed</h3>
                @if($selectedUser)
                <span class="inline-flex items-center gap-1.5 text-xs bg-indigo-100 text-indigo-700 font-medium px-2.5 py-1 rounded-full">
                    <span class="w-5 h-5 rounded-full bg-indigo-500 text-white flex items-center justify-center text-[10px] font-bold">
                        {{ strtoupper(substr($selectedUser->name, 0, 1)) }}
                    </span>
                    {{ $selectedUser->name }}
                </span>
                @endif
            </div>
            <span class="text-xs text-gray-400">{{ $activities->total() }} {{ $selectedUser ? 'activities' : 'total activities' }}</span>
        </div>

        @php
            $lastDate = null;
            $actionIcons = [
                'status_updated_completed' => ['icon' => 'fa-check-circle', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50'],
                'status_updated_in_progress' => ['icon' => 'fa-spinner', 'color' => 'text-amber-500', 'bg' => 'bg-amber-50'],
                'status_updated_pending' => ['icon' => 'fa-clock', 'color' => 'text-gray-400', 'bg' => 'bg-gray-50'],
            ];
            $colors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6'];
        @endphp

        <div class="divide-y divide-gray-50 max-h-[600px] overflow-y-auto">
            @forelse($activities as $i => $log)
            @php
                $dateStr = $log->created_at->isToday() ? 'Today' : ($log->created_at->isYesterday() ? 'Yesterday' : $log->created_at->format('M d, Y'));
                $style   = $actionIcons[$log->action] ?? ['icon' => 'fa-bolt', 'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50'];
                $color   = $colors[$log->user_id % count($colors)];
                $actionText = match($log->action) {
                    'status_updated_completed' => 'marked task as completed',
                    'status_updated_in_progress' => 'started working on task',
                    'status_updated_pending' => 'moved task to pending',
                    default => str_replace(['status_updated_', '_'], ['updated status to ', ' '], $log->action),
                };
            @endphp

            @if($lastDate !== $dateStr)
            @php $lastDate = $dateStr; @endphp
            <div class="px-5 py-2 bg-gray-50">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $dateStr }}</p>
            </div>
            @endif

            <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50/70 transition group">
                {{-- Time --}}
                <span class="text-xs text-gray-400 w-10 flex-shrink-0 mt-0.5 pt-1">{{ $log->created_at->format('H:i') }}</span>

                {{-- Avatar --}}
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                     style="background:{{ $color }}">
                    {{ strtoupper(substr($log->user->name ?? 'U', 0, 1)) }}
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800">
                        <span class="font-semibold text-gray-900">{{ $log->user->name ?? 'Unknown' }}</span>
                        {{ $actionText }}
                        @if($log->task)
                        <span class="font-medium text-indigo-600">#{{ $log->task->title }}</span>
                        @if($log->task->project)
                        in team <span class="font-medium text-gray-700">{{ $log->task->project->name }}</span>
                        @endif
                        @endif
                    </p>

                    @if($log->note)
                    <div class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-100 text-sm text-gray-600">
                        {{ $log->note }}
                    </div>
                    @endif

                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                        <button class="hover:text-indigo-600 transition flex items-center gap-1">
                            <i class="fa fa-smile"></i> React
                        </button>
                        <button class="hover:text-indigo-600 transition flex items-center gap-1">
                            <i class="fa fa-reply"></i> Reply
                        </button>
                        <span class="flex items-center gap-1">
                            <i class="fa fa-clock"></i> {{ $log->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                {{-- Activity icon --}}
                <div class="w-8 h-8 {{ $style['bg'] }} rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fa {{ $style['icon'] }} {{ $style['color'] }} text-sm"></i>
                </div>
            </div>
            @empty
            <div class="px-5 py-16 text-center">
                <i class="fa fa-bolt text-5xl text-gray-200 mb-3"></i>
                <p class="text-gray-400">No activity recorded yet</p>
                <p class="text-xs text-gray-400 mt-1">Activity will appear here when team members update tasks</p>
            </div>
            @endforelse
        </div>

        @if($activities->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
            {{ $activities->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

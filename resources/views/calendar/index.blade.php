@extends('layouts.app')

@section('title', 'Calendar')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Calendar & Meetings</h1>
        <p class="text-sm text-gray-500 mt-0.5">Task deadlines and scheduled events</p>
    </div>
    <div class="flex items-center gap-2">
        <div class="flex items-center gap-2 text-xs font-medium">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Completed</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> In Progress</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> High Priority</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span> Normal</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Main Calendar --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div id="calendar" data-events="{{ $events->toJson() }}"></div>
    </div>

    {{-- Sidebar: Today + Upcoming --}}
    <div class="space-y-4">

        {{-- Today's Tasks --}}
        <div class="bg-gray-900 rounded-xl p-5 text-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold">Today's Tasks</h3>
                <span class="text-xs bg-white/10 rounded-full px-2 py-0.5">{{ now()->format('M d, Y') }}</span>
            </div>
            <div class="space-y-3">
                @forelse($todayTasks as $task)
                @php
                    $routeName = auth()->user()->role === 'user' ? 'user.tasks.show' : null;
                @endphp
                <div class="flex items-start gap-3 p-3 bg-white/10 rounded-lg">
                    <div class="w-1.5 rounded-full flex-shrink-0 mt-0.5 self-stretch
                        {{ $task->status === 'completed' ? 'bg-emerald-400' : ($task->priority === 'high' ? 'bg-red-400' : 'bg-indigo-400') }}">
                    </div>
                    <div class="flex-1 min-w-0">
                        @if($routeName)
                        <a href="{{ route($routeName, $task) }}" class="text-sm font-medium text-white hover:text-indigo-200 transition truncate block">
                            {{ $task->title }}
                        </a>
                        @else
                        <p class="text-sm font-medium text-white truncate">{{ $task->title }}</p>
                        @endif
                        <p class="text-xs text-white/60 mt-0.5">{{ $task->project->name }}</p>
                    </div>
                    <span class="text-xs px-1.5 py-0.5 rounded bg-white/10 font-medium flex-shrink-0
                        {{ $task->status === 'completed' ? 'text-emerald-300' : ($task->status === 'in_progress' ? 'text-amber-300' : 'text-gray-300') }}">
                        {{ str_replace('_', ' ', ucfirst($task->status)) }}
                    </span>
                </div>
                @empty
                <div class="text-center py-4">
                    <i class="fa fa-calendar-check text-3xl text-white/20 mb-2"></i>
                    <p class="text-sm text-white/60">No tasks due today</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Upcoming Tasks --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Upcoming</h3>
                <span class="text-xs text-indigo-600">Next deadlines</span>
            </div>
            <div class="space-y-3">
                @forelse($upcomingTasks as $task)
                @php $daysLeft = now()->diffInDays($task->deadline, false); @endphp
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-indigo-50 transition cursor-pointer">
                    <div class="w-10 h-10 rounded-lg flex flex-col items-center justify-center flex-shrink-0
                        {{ $daysLeft <= 2 ? 'bg-red-100' : ($daysLeft <= 7 ? 'bg-amber-100' : 'bg-indigo-100') }}">
                        <span class="text-xs font-bold {{ $daysLeft <= 2 ? 'text-red-600' : ($daysLeft <= 7 ? 'text-amber-600' : 'text-indigo-600') }}">
                            {{ $task->deadline->format('d') }}
                        </span>
                        <span class="text-xs {{ $daysLeft <= 2 ? 'text-red-400' : ($daysLeft <= 7 ? 'text-amber-400' : 'text-indigo-400') }}">
                            {{ $task->deadline->format('M') }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $task->title }}</p>
                        <p class="text-xs text-gray-400">{{ $task->project->name }}</p>
                    </div>
                    <span class="text-xs text-gray-400 flex-shrink-0">
                        {{ $daysLeft === 1 ? 'Tomorrow' : "in {$daysLeft}d" }}
                    </span>
                </div>
                @empty
                <div class="text-center py-4">
                    <i class="fa fa-calendar text-3xl text-gray-200 mb-2"></i>
                    <p class="text-sm text-gray-400">No upcoming tasks</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const events = JSON.parse(calendarEl.dataset.events || '[]');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 500,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },
        buttonText: { today: 'Today', month: 'Month', list: 'List' },
        events: events,
        eventClick: function(info) {
            const props = info.event.extendedProps;
            if (props.type === 'task') {
                @if(auth()->user()->role === 'user')
                window.location.href = `/user/tasks/${props.id}`;
                @endif
            }
        },
        eventDidMount: function(info) {
            info.el.title = `${info.event.title}\nProject: ${info.event.extendedProps.project}\nStatus: ${info.event.extendedProps.status}`;
        },
        dayMaxEvents: 3,
    });

    calendar.render();
});
</script>
@endpush

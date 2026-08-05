@extends('layouts.app')

@section('title', 'Overview')

@section('content')
<style>
@media (max-width: 768px) {
    .mgr-dash-header { flex-wrap: wrap; gap: 10px; }
    .mgr-dash-header button { width: 100%; justify-content: center; min-height: 44px; }
    .mgr-dash-stats { grid-template-columns: 1fr !important; gap: var(--mob-sp-2) !important; }
    .mgr-dash-header h1 { font-size: 1.375rem; }
    .mgr-view-all { display: inline-flex; align-items: center; min-height: 44px; padding: 0 4px; }

    /* KPI stat tiles */
    .mgr-dash-stats > div {
        border-radius: var(--mob-r-md) !important;
        box-shadow: var(--mob-shadow-1) !important;
        padding: var(--mob-sp-2) !important;
    }
    .mgr-dash-stats > div.mgr-kpi-emphasis { box-shadow: var(--mob-shadow-2) !important; }
    .mgr-kpi-label { margin-bottom: 4px !important; }
    .mgr-kpi-value { font-size: 26px !important; line-height: 1.15 !important; }
    .mgr-kpi-sub { margin-top: 2px !important; }

    /* Panel cards (Team Workload / Overdue Tasks) */
    .mgr-panel { border-radius: var(--mob-r-lg) !important; box-shadow: var(--mob-shadow-1) !important; padding: var(--mob-sp-2) !important; }

    /* Team Workload rows */
    .mgr-team-row { padding: 8px 0; border-bottom: 1px solid #F9FAFB; }
    .mgr-team-row:last-child { border-bottom: none; }
    .mgr-team-name {
        max-width: 130px; overflow: hidden; text-overflow: ellipsis;
        white-space: nowrap; display: inline-block; vertical-align: middle;
    }

    /* Overdue count badge -> pill */
    .mgr-badge {
        font-size: 11px !important; font-weight: 700 !important;
        padding: 4px 10px !important; border-radius: 999px !important;
    }

    /* Overdue task rows */
    .mgr-overdue-row { border-radius: var(--mob-r-md) !important; padding: 10px var(--mob-sp-2) !important; }
    .mgr-task-sub { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* Empty states */
    .mgr-empty-icon { font-size: 22px !important; }
}
</style>

<div class="mgr-dash-header flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Overview</h1>
        <p class="text-sm text-gray-500 mt-0.5">Welcome back, {{ auth()->user()->name }}!</p>
    </div>
    <button class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm shadow-indigo-200">
        <i class="fa fa-download"></i> Export
    </button>
</div>

{{-- Stats --}}
<div class="mgr-dash-stats grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 max-md:grid-cols-2 max-md:gap-3">
    <div class="mgr-kpi-emphasis bg-indigo-600 rounded-xl p-5 text-white shadow-lg shadow-indigo-200 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-20 h-20 bg-indigo-500/30 rounded-full -translate-y-4 translate-x-4"></div>
        <p class="mgr-kpi-label text-indigo-200 text-sm font-medium mb-2 max-md:text-[11px] max-md:font-semibold max-md:uppercase max-md:tracking-wide">Team Members</p>
        <p class="mgr-kpi-value text-4xl font-bold max-md:text-xl max-md:leading-tight">{{ $teamUsers->count() }}</p>
        <p class="mgr-kpi-sub text-indigo-200 text-xs mt-1 max-md:text-[11px]">Active users</p>
    </div>
    <div class="mgr-kpi-emphasis bg-indigo-600 rounded-xl p-5 text-white shadow-lg shadow-indigo-200 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-20 h-20 bg-indigo-500/30 rounded-full -translate-y-4 translate-x-4"></div>
        <p class="mgr-kpi-label text-indigo-200 text-sm font-medium mb-2 max-md:text-[11px] max-md:font-semibold max-md:uppercase max-md:tracking-wide">Projects</p>
        <p class="mgr-kpi-value text-4xl font-bold max-md:text-xl max-md:leading-tight">{{ $projects->count() }}</p>
        <p class="mgr-kpi-sub text-indigo-200 text-xs mt-1 max-md:text-[11px]">Total projects</p>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <p class="mgr-kpi-label text-gray-500 text-sm font-medium mb-2 max-md:text-[11px] max-md:font-semibold max-md:uppercase max-md:tracking-wide">Overdue Tasks</p>
        <p class="mgr-kpi-value text-4xl font-bold text-red-500 max-md:text-xl max-md:leading-tight">{{ $overdueTasks->count() }}</p>
        <p class="mgr-kpi-sub text-gray-400 text-xs mt-1 max-md:text-[11px]">Need attention</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    {{-- Team Workload --}}
    <div class="mgr-panel bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 max-md:text-sm">Team Workload</h3>
            <a href="{{ route('team.index') }}" class="mgr-view-all text-xs text-indigo-600 hover:underline max-md:font-medium max-md:text-gray-400">View all</a>
        </div>
        @if($teamUsers->count() > 0)
        <div class="space-y-3">
            @foreach($teamUsers as $user)
            @php $count = $workload[$user->id] ?? 0; @endphp
            <div class="mgr-team-row">
                <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                             style="background: {{ ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6'][$loop->index % 5] }}">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <span class="mgr-team-name text-sm font-medium text-gray-800">{{ $user->name }}</span>
                    </div>
                    <span class="text-sm font-bold flex-shrink-0 {{ $count > 5 ? 'text-red-500' : 'text-emerald-600' }}">{{ $count }} tasks</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full {{ $count > 5 ? 'bg-red-400' : 'bg-emerald-400' }}"
                         style="width: {{ min(100, ($count / 10) * 100) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8">
            <i class="fa fa-users mgr-empty-icon hidden max-md:block text-gray-300 mb-2"></i>
            <p class="text-sm text-gray-400">No team members yet</p>
        </div>
        @endif
    </div>

    {{-- Overdue Tasks --}}
    <div class="mgr-panel bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 max-md:text-sm">Overdue Tasks</h3>
            <span class="mgr-badge text-xs bg-red-100 text-red-600 font-medium px-2 py-0.5 rounded-full max-md:text-[11px] max-md:px-2 max-md:py-0.5">{{ $overdueTasks->count() }}</span>
        </div>
        <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
            @forelse($overdueTasks as $task)
            <div class="mgr-overdue-row flex items-start gap-3 p-3 bg-red-50 rounded-lg border border-red-100">
                <div class="w-2 h-2 rounded-full bg-red-400 mt-1.5 flex-shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate max-md:text-sm">{{ $task->title }}</p>
                    <p class="mgr-task-sub text-xs text-gray-500 max-md:text-xs">{{ $task->project->name }}</p>
                    <p class="text-xs text-red-500 font-medium mt-0.5 max-md:text-[11px]">
                        <i class="fa fa-clock"></i> Due {{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}
                    </p>
                </div>
            </div>
            @empty
            <div class="text-center py-8 max-md:py-8">
                <i class="fa fa-check-circle mgr-empty-icon text-3xl text-emerald-300 mb-2 max-md:text-2xl"></i>
                <p class="text-sm text-gray-400 max-md:text-sm">No overdue tasks</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

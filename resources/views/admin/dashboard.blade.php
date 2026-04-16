@extends('layouts.app')

@section('title', 'Overview')

@section('content')

<style>
/* ── Responsive grid helpers ── */
.stats-grid   { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px; }
.charts-grid  { display:grid; grid-template-columns:2fr 1fr;        gap:16px; margin-bottom:16px; }
.bottom-grid  { display:grid; grid-template-columns:1fr 2fr;        gap:16px; margin-bottom:0; }

@media(max-width:1100px){
    .charts-grid, .bottom-grid { grid-template-columns:1fr; }
}
@media(max-width:700px){
    .stats-grid { grid-template-columns:repeat(2,1fr); }
}
@media(max-width:420px){
    .stats-grid { grid-template-columns:1fr; }
}

/* ── Card base ── */
.dash-card { background:#fff; border-radius:14px; border:1px solid #F0F0F0; box-shadow:0 1px 4px rgba(0,0,0,0.05); padding:20px; }

/* ── Stat cards ── */
.stat-card { border-radius:14px; padding:18px 20px; position:relative; overflow:hidden; color:#fff; }
.stat-card-blob { position:absolute; top:-20px; right:-20px; width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.12); }
.stat-card-label { font-size:12px; font-weight:500; color:rgba(255,255,255,0.75); margin:0 0 8px; }
.stat-card-value { font-size:34px; font-weight:700; line-height:1; margin:0; }
.stat-card-sub   { font-size:11px; color:rgba(255,255,255,0.6); margin:6px 0 0; }
.stat-card-menu  { position:absolute; top:14px; right:14px; background:rgba(255,255,255,0.15); border:none; border-radius:6px; width:26px; height:26px; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#fff; font-size:11px; }

/* ── Calendar dark card ── */
.cal-card { background:linear-gradient(135deg,#1a2040 0%,#1e2756 100%); border-radius:14px; padding:20px; color:#fff; }

/* ── Task Modal ── */
.task-modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:200; display:flex; align-items:center; justify-content:center; padding:16px; }
.task-modal { background:#fff; border-radius:20px; width:100%; max-width:560px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
.task-modal-header { display:flex; align-items:center; justify-content:space-between; padding:22px 24px 0; }
.task-modal-body   { padding:20px 24px 24px; }
.form-label { display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-input { width:100%; padding:9px 12px; font-size:13px; border:1.5px solid #E5E7EB; border-radius:9px; color:#111827; outline:none; font-family:'Inter',sans-serif; transition:border-color 0.15s; background:#fff; }
.form-input:focus { border-color:#6366F1; box-shadow:0 0 0 3px rgba(99,102,241,0.12); }
.form-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:32px; }
.priority-btn { flex:1; padding:8px; font-size:12px; font-weight:600; border-radius:8px; border:1.5px solid #E5E7EB; cursor:pointer; transition:all 0.15s; text-align:center; background:#fff; color:#6B7280; }
.priority-btn.active-low    { background:#F0FDF4; border-color:#10B981; color:#059669; }
.priority-btn.active-medium { background:#FFFBEB; border-color:#F59E0B; color:#D97706; }
.priority-btn.active-high   { background:#FFF1F2; border-color:#EF4444; color:#DC2626; }
</style>

{{-- ══ Page Title + Quick Create Modal ══ --}}
<div x-data="taskModal()" style="margin-bottom:22px;">

    {{-- Title row --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Overview</h1>
            <p  style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">Welcome back, {{ auth()->user()->name }}!</p>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;background:#fff;border:1px solid #E5E7EB;border-radius:8px;padding:7px 12px;cursor:pointer;">
                <i class="fas fa-calendar-days" style="color:#9CA3AF;font-size:11px;"></i>
                <span>Last Week</span>
                <i class="fas fa-chevron-down" style="color:#9CA3AF;font-size:10px;"></i>
            </div>
            {{-- NEW TASK BUTTON --}}
            <button @click="open = true"
                    style="display:flex;align-items:center;gap:7px;background:#4F46E5;color:#fff;font-size:13px;font-weight:600;padding:9px 18px;border-radius:9px;border:none;cursor:pointer;box-shadow:0 2px 10px rgba(79,70,229,0.4);">
                <i class="fas fa-plus" style="font-size:11px;"></i> New Task
            </button>
            <button style="width:36px;height:36px;background:#fff;border:1px solid #E5E7EB;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#9CA3AF;">
                <i class="fas fa-ellipsis-h"></i>
            </button>
        </div>
    </div>

    {{-- ══ Create Task Modal ══ --}}
    <div x-show="open" x-cloak class="task-modal-backdrop" @click.self="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="task-modal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             @click.stop>

            {{-- Modal Header --}}
            <div class="task-modal-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;background:#EEF2FF;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-square-check" style="color:#6366F1;font-size:15px;"></i>
                    </div>
                    <div>
                        <h2 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Create New Task</h2>
                        <p  style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Assign and track instantly</p>
                    </div>
                </div>
                <button @click="open = false"
                        style="width:32px;height:32px;border-radius:8px;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:13px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="task-modal-body">
                <form method="POST" action="{{ route('admin.tasks.quick') }}" @submit="submitting = true">
                    @csrf

                    {{-- Title --}}
                    <div style="margin-bottom:16px;">
                        <label class="form-label">Task Title <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="title" class="form-input" placeholder="e.g. Redesign the login page" required
                               value="{{ old('title') }}">
                    </div>

                    {{-- Description --}}
                    <div style="margin-bottom:16px;">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input" rows="3"
                                  placeholder="What needs to be done? (optional)" style="resize:vertical;">{{ old('description') }}</textarea>
                    </div>

                    {{-- Project + Assign To (2 cols) --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                        <div>
                            <label class="form-label">Project <span style="color:#EF4444;">*</span></label>
                            <select name="project_id" class="form-input form-select" required>
                                <option value="">— Select Project —</option>
                                @foreach($allProjects as $proj)
                                <option value="{{ $proj->id }}" {{ old('project_id') == $proj->id ? 'selected' : '' }}>
                                    {{ $proj->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Assign To <span style="color:#EF4444;">*</span></label>
                            <select name="assigned_to" class="form-input form-select" required>
                                <option value="">— Select Member —</option>
                                @foreach($allUsers as $member)
                                <option value="{{ $member->id }}" {{ old('assigned_to') == $member->id ? 'selected' : '' }}>
                                    {{ $member->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Priority --}}
                    <div style="margin-bottom:16px;">
                        <label class="form-label">Priority <span style="color:#EF4444;">*</span></label>
                        <div style="display:flex;gap:8px;">
                            <button type="button" @click="priority = 'low'"
                                    :class="priority === 'low' ? 'priority-btn active-low' : 'priority-btn'">
                                <i class="fas fa-circle-minus" style="margin-right:4px;font-size:10px;"></i> Low
                            </button>
                            <button type="button" @click="priority = 'medium'"
                                    :class="priority === 'medium' ? 'priority-btn active-medium' : 'priority-btn'">
                                <i class="fas fa-circle" style="margin-right:4px;font-size:10px;"></i> Medium
                            </button>
                            <button type="button" @click="priority = 'high'"
                                    :class="priority === 'high' ? 'priority-btn active-high' : 'priority-btn'">
                                <i class="fas fa-circle-exclamation" style="margin-right:4px;font-size:10px;"></i> High
                            </button>
                        </div>
                        <input type="hidden" name="priority" :value="priority">
                    </div>

                    {{-- Deadline --}}
                    <div style="margin-bottom:24px;">
                        <label class="form-label">Deadline <span style="color:#EF4444;">*</span></label>
                        <input type="date" name="deadline" class="form-input" required
                               min="{{ date('Y-m-d') }}" value="{{ old('deadline') }}">
                    </div>

                    {{-- Actions --}}
                    <div style="display:flex;align-items:center;gap:10px;justify-content:flex-end;">
                        <button type="button" @click="open = false"
                                style="padding:9px 20px;font-size:13px;font-weight:500;background:#F3F4F6;border:none;border-radius:9px;cursor:pointer;color:#374151;">
                            Cancel
                        </button>
                        <button type="submit" :disabled="submitting"
                                style="padding:9px 24px;font-size:13px;font-weight:600;background:#4F46E5;color:#fff;border:none;border-radius:9px;cursor:pointer;box-shadow:0 2px 8px rgba(79,70,229,0.35);display:flex;align-items:center;gap:7px;"
                                :style="submitting ? 'opacity:0.7;cursor:not-allowed;' : ''">
                            <i class="fas fa-plus" style="font-size:11px;" x-show="!submitting"></i>
                            <i class="fas fa-spinner fa-spin" style="font-size:11px;" x-show="submitting"></i>
                            <span x-text="submitting ? 'Creating…' : 'Create Task'"></span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function taskModal() {
    return {
        open:      {{ $errors->any() ? 'true' : 'false' }},
        priority:  '{{ old('priority', 'medium') }}',
        submitting: false,
    };
}
</script>
@endpush

{{-- ══ Stats Row ══ --}}
<div class="stats-grid">

    {{-- Tasks --}}
    <div class="stat-card" style="background:linear-gradient(135deg,#4F46E5,#6366F1);">
        <div class="stat-card-blob"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p class="stat-card-label">Tasks</p>
            <button class="stat-card-menu"><i class="fas fa-ellipsis-h"></i></button>
        </div>
        <p class="stat-card-value">{{ $totalTasks }}</p>
        <p class="stat-card-sub">Open Tasks</p>
    </div>

    {{-- Projects --}}
    <div class="stat-card" style="background:linear-gradient(135deg,#4F46E5,#6366F1);">
        <div class="stat-card-blob"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p class="stat-card-label">Projects</p>
            <button class="stat-card-menu"><i class="fas fa-ellipsis-h"></i></button>
        </div>
        <p class="stat-card-value">{{ $activeProjects }}</p>
        <p class="stat-card-sub">Active Projects</p>
    </div>

    {{-- Meetings --}}
    <div class="stat-card" style="background:linear-gradient(135deg,#4F46E5,#6366F1);">
        <div class="stat-card-blob"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p class="stat-card-label">Meetings</p>
            <button class="stat-card-menu"><i class="fas fa-ellipsis-h"></i></button>
        </div>
        <p class="stat-card-value">{{ $scheduledMeetings }}</p>
        <p class="stat-card-sub">Scheduled Meetings</p>
    </div>

    {{-- Member Status --}}
    <div class="dash-card" style="padding:18px 20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <p style="font-size:12px;font-weight:500;color:#6B7280;margin:0;">Member Status</p>
            <a href="{{ route('team.index') }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:500;">View more</a>
        </div>
        <p style="font-size:32px;font-weight:700;color:#111827;margin:0 0 2px;line-height:1;">{{ $totalMembers }}</p>
        <p style="font-size:11px;color:#9CA3AF;margin:0 0 10px;">Total Members</p>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:5px;">
                <span style="width:24px;height:24px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#4F46E5;">{{ $activeMembers }}</span>
                <span style="font-size:11px;color:#9CA3AF;">Active</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;">
                <span style="width:24px;height:24px;border-radius:8px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#D97706;">{{ $managerCount }}</span>
                <span style="font-size:11px;color:#9CA3AF;">Mgr</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;">
                <span style="width:24px;height:24px;border-radius:8px;background:#F0FDF4;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#16A34A;">{{ $userCount }}</span>
                <span style="font-size:11px;color:#9CA3AF;">Users</span>
            </div>
        </div>
    </div>
</div>

{{-- ══ Charts Row ══ --}}
<div class="charts-grid">

    {{-- Working Hours Line Chart --}}
    <div class="dash-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
            <div>
                <h3 style="font-size:14px;font-weight:600;color:#111827;margin:0;">Working Hours Statistics</h3>
                <p  style="font-size:12px;color:#9CA3AF;margin:3px 0 0;">Task activity over the past 7 days</p>
            </div>
            <div style="display:flex;align-items:center;gap:2px;background:#F3F4F6;border-radius:8px;padding:3px;">
                <button style="padding:4px 12px;font-size:12px;font-weight:500;background:#fff;border:none;border-radius:6px;cursor:pointer;color:#374151;box-shadow:0 1px 2px rgba(0,0,0,0.06);">Week</button>
                <button style="padding:4px 12px;font-size:12px;font-weight:500;background:none;border:none;border-radius:6px;cursor:pointer;color:#9CA3AF;">Month</button>
                <button style="padding:4px 12px;font-size:12px;font-weight:500;background:none;border:none;border-radius:6px;cursor:pointer;color:#9CA3AF;">Year</button>
            </div>
        </div>
        <canvas id="workingHoursChart" height="85"></canvas>
    </div>

    {{-- Project Statistics Donut + Project List --}}
    <div class="dash-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <h3 style="font-size:14px;font-weight:600;color:#111827;margin:0;">Project Statistics</h3>
            <a href="{{ route('admin.projects.index') }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:500;">View more</a>
        </div>

        {{-- Donut --}}
        <div style="display:flex;justify-content:center;margin-bottom:14px;">
            <div style="position:relative;width:120px;height:120px;">
                <canvas id="projectStatsChart"></canvas>
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                    <p style="font-size:24px;font-weight:700;color:#111827;margin:0;line-height:1;">{{ $activeProjects }}</p>
                    <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;">Active</p>
                </div>
            </div>
        </div>

        {{-- Stat rows --}}
        <div style="display:flex;justify-content:center;gap:16px;margin-bottom:14px;">
            <div style="text-align:center;">
                <p style="font-size:16px;font-weight:700;color:#10B981;margin:0;">{{ $taskStats['completed'] }}</p>
                <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;">Success</p>
            </div>
            <div style="text-align:center;">
                <p style="font-size:16px;font-weight:700;color:#F59E0B;margin:0;">{{ $taskStats['pending'] }}</p>
                <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;">Pending</p>
            </div>
            <div style="text-align:center;">
                <p style="font-size:16px;font-weight:700;color:#6366F1;margin:0;">{{ $taskStats['in_progress'] }}</p>
                <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;">On-Going</p>
            </div>
        </div>

        {{-- Project list --}}
        <div style="display:flex;flex-direction:column;gap:8px;">
            @forelse($projects->take(3) as $proj)
            @php
                $pColors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6'];
                $c = $pColors[$loop->index % 5];
            @endphp
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $c }};flex-shrink:0;"></span>
                <span style="flex:1;font-size:12px;color:#374151;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->name }}</span>
                <button style="background:none;border:none;cursor:pointer;color:#D1D5DB;font-size:11px;padding:0;"><i class="fas fa-ellipsis-h"></i></button>
            </div>
            @empty
            <p style="font-size:12px;color:#9CA3AF;text-align:center;">No projects yet</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ══ Bottom Row: Workload + Calendar ══ --}}
<div class="bottom-grid">

    {{-- Task Workload Bar Chart --}}
    <div class="dash-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:14px;font-weight:600;color:#111827;margin:0;">Task Workload</h3>
            <a href="{{ route('team.index') }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:500;">View more</a>
        </div>
        @if(count($workloadLabels) > 0)
            <canvas id="workloadChart" height="130"></canvas>
        @else
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:130px;color:#D1D5DB;">
                <i class="fas fa-chart-bar" style="font-size:28px;margin-bottom:8px;"></i>
                <p style="font-size:12px;margin:0;">No data yet</p>
            </div>
        @endif
    </div>

    {{-- Calendar and Meetings dark card --}}
    @php
        $avatarColors  = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4'];
        $meetingColors = ['#1B4D3E','#1E3A5F','#3B1F5E','#4A1942','#1A3A4A'];
        $allMeetings   = $todayMeetings->count() ? $todayMeetings : $todayTaskEvents;
        $showMore      = max(0, $allMeetings->count() - 2);
    @endphp

    <div class="cal-card">

        {{-- Header --}}
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <div style="width:32px;height:32px;background:rgba(99,102,241,0.3);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-calendar-days" style="color:#A5B4FC;font-size:13px;"></i>
            </div>
            <span style="font-size:15px;font-weight:700;color:#fff;">Calendar and Meetings</span>
        </div>

        <div style="display:flex;gap:20px;flex-wrap:wrap;">

            {{-- Mini Calendar --}}
            <div style="min-width:220px;flex:0 0 220px;">

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:13px;font-weight:600;color:#E0E7FF;">{{ $calMonthLabel }}</span>
                        <button style="width:20px;height:20px;border-radius:50%;background:rgba(255,255,255,0.1);border:none;color:#A5B4FC;cursor:pointer;font-size:9px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button style="width:20px;height:20px;border-radius:50%;background:rgba(255,255,255,0.1);border:none;color:#A5B4FC;cursor:pointer;font-size:9px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <span style="font-size:10px;color:#6B7CB9;background:rgba(255,255,255,0.07);padding:2px 8px;border-radius:20px;">{{ $calWeekCount }} weeks</span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:1px;margin-bottom:5px;">
                    @foreach(['MO','TU','WE','TH','FR','SA','SU'] as $dh)
                    <div style="text-align:center;font-size:9px;font-weight:600;color:#6B7CB9;">{{ $dh }}</div>
                    @endforeach
                </div>

                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:1px;">
                    @foreach($calWeeks as $week)
                    @foreach($week as $day)
                    @php
                        $dk      = $day->format('Y-m-d');
                        $isToday = $dk === $calTodayKey;
                        $inMonth = $day->month === $firstOfMonth->month;
                        $dots    = $taskDotMap[$dk] ?? [];
                    @endphp
                    <div style="text-align:center;padding:1px 0;">
                        <div style="width:22px;height:22px;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:{{ $isToday ? '700' : '400' }};{{ $isToday ? 'background:#6366F1;color:#fff;' : ($inMonth ? 'color:#C7D2FE;' : 'color:#3A4570;') }}">{{ $day->format('j') }}</div>
                        @if(count($dots))
                        <div style="display:flex;justify-content:center;gap:1px;margin-top:1px;">
                            @foreach(array_slice($dots,0,3) as $dot)
                            <span style="width:3px;height:3px;border-radius:50%;background:{{ $dot }};display:inline-block;"></span>
                            @endforeach
                        </div>
                        @else
                        <div style="height:4px;"></div>
                        @endif
                    </div>
                    @endforeach
                    @endforeach
                </div>
            </div>

            {{-- Today Meetings --}}
            <div style="flex:1;min-width:0;">
                <div style="font-size:11px;font-weight:700;color:#6B7CB9;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:10px;">Today meetings</div>

                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;align-items:start;">

                    @forelse($allMeetings->take(2) as $i => $event)
                    @php
                        $bg      = $meetingColors[$i % count($meetingColors)];
                        $isEvent = $event instanceof \App\Models\CalendarEvent;
                        $title   = $event->title;
                        $desc    = $isEvent ? ($event->description ?? '') : ('Project: '.($event->project->name ?? ''));
                        $time    = $isEvent ? $event->start_date->format('H:i') : now()->format('H:i');
                        $person  = $isEvent ? $event->user : ($event->assignee ?? null);
                    @endphp
                    <div style="background:{{ $bg }};border-radius:10px;padding:12px;min-height:100px;display:flex;flex-direction:column;gap:6px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:11px;color:rgba(255,255,255,0.6);">{{ $time }}</span>
                            @if($person)
                            <div style="width:20px;height:20px;border-radius:50%;background:{{ $avatarColors[$i % count($avatarColors)] }};display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;color:#fff;">
                                {{ strtoupper(substr($person->name ?? 'U',0,1)) }}
                            </div>
                            @endif
                        </div>
                        <p style="font-size:13px;font-weight:700;color:#fff;margin:0;line-height:1.3;">{{ $title }}</p>
                        @if($desc)
                        <p style="font-size:10px;color:rgba(255,255,255,0.5);margin:0;line-height:1.4;">{{ Str::limit($desc,45) }}</p>
                        @endif
                    </div>
                    @empty
                    <div style="background:rgba(255,255,255,0.05);border-radius:10px;padding:14px;text-align:center;color:rgba(255,255,255,0.35);font-size:11px;grid-column:1/-1;">
                        <i class="fas fa-calendar-check" style="font-size:20px;margin-bottom:6px;display:block;"></i>
                        No meetings today
                    </div>
                    @endforelse

                    {{-- X More --}}
                    @if($showMore > 0)
                    <div style="background:rgba(255,255,255,0.07);border-radius:10px;padding:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;min-height:80px;cursor:pointer;"
                         onclick="window.location='{{ route('calendar.index') }}'">
                        <span style="font-size:18px;font-weight:700;color:#E0E7FF;">{{ $showMore }}</span>
                        <span style="font-size:10px;color:rgba(255,255,255,0.4);">More</span>
                    </div>
                    @endif

                    {{-- Add New Meeting --}}
                    <a href="{{ route('calendar.index') }}"
                       style="background:rgba(255,255,255,0.07);border-radius:10px;padding:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;min-height:80px;text-decoration:none;transition:background 0.15s;"
                       onmouseover="this.style.background='rgba(99,102,241,0.3)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.07)'">
                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-plus" style="color:#A5B4FC;font-size:12px;"></i>
                        </div>
                        <span style="font-size:10px;color:rgba(255,255,255,0.45);text-align:center;line-height:1.3;">Add new<br>meeting</span>
                    </a>

                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
Chart.defaults.font = { family: 'Inter, sans-serif', size: 12 };
Chart.defaults.color = '#9CA3AF';

// Line Chart
new Chart(document.getElementById('workingHoursChart'), {
    type: 'line',
    data: {
        labels: @json($weekLabels),
        datasets: [{
            label: 'Activity',
            data: @json($weekData),
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99,102,241,0.08)',
            borderWidth: 2.5,
            pointRadius: 4,
            pointBackgroundColor: '#6366F1',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            tension: 0.4,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: { grid: { color: '#F3F4F6' }, border: { display: false }, beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Donut Chart
new Chart(document.getElementById('projectStatsChart'), {
    type: 'doughnut',
    data: {
        labels: ['Completed','In Progress','Pending','Overdue'],
        datasets: [{
            data: [
                {{ $taskStats['completed'] }},
                {{ $taskStats['in_progress'] }},
                {{ $taskStats['pending'] }},
                {{ $taskStats['overdue'] }}
            ],
            backgroundColor: ['#10B981','#F59E0B','#60A5FA','#EF4444'],
            borderWidth: 0,
            hoverOffset: 4,
        }]
    },
    options: {
        responsive: true,
        cutout: '72%',
        plugins: { legend: { display: false }, tooltip: { callbacks: {
            label: ctx => ` ${ctx.label}: ${ctx.parsed}`
        }}}
    }
});

// Bar Chart
@if(count($workloadLabels) > 0)
new Chart(document.getElementById('workloadChart'), {
    type: 'bar',
    data: {
        labels: @json($workloadLabels),
        datasets: [{
            label: 'Open Tasks',
            data: @json($workloadData),
            backgroundColor: [
                'rgba(99,102,241,0.85)',
                'rgba(16,185,129,0.85)',
                'rgba(245,158,11,0.85)',
                'rgba(239,68,68,0.85)',
                'rgba(139,92,246,0.85)',
                'rgba(59,130,246,0.85)',
            ],
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: { grid: { color: '#F3F4F6' }, border: { display: false }, beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
@endif
</script>
@endpush

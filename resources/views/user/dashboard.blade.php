@extends('layouts.app')
@section('title', isset($previewUser) ? $previewUser->name . ' — Dashboard Preview' : 'My Dashboard')

@section('content')
@php
    $user      = $previewUser ?? auth()->user();
    $isPreview = isset($previewUser);
    $hour      = now()->hour;
    $greeting  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

@isset($previewUser)
<div style="background:#FEF3C7;border:1.5px solid #FCD34D;border-radius:12px;padding:10px 18px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <i class="fa fa-eye" style="color:#D97706;font-size:14px;"></i>
        <span style="font-size:13px;font-weight:600;color:#92400E;">Previewing dashboard as <strong>{{ $previewUser->name }}</strong></span>
    </div>
    <a href="{{ url()->previous() }}"
       style="font-size:12px;font-weight:600;color:#D97706;background:rgba(217,119,6,0.1);padding:5px 14px;border-radius:8px;text-decoration:none;">
        <i class="fa fa-arrow-left" style="margin-right:4px;"></i> Back to Team
    </a>
</div>
@endisset

{{-- ═══════════════════════════════
     HEADER
════════════════════════════════ --}}
<div style="background:linear-gradient(135deg,#4F46E5 0%,#7C3AED 100%);border-radius:16px;padding:24px 28px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:16px;">
        @if($user->avatarUrl())
            <img src="{{ $user->avatarUrl() }}" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.3);" alt="">
        @else
            <div style="width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;border:3px solid rgba(255,255,255,.3);flex-shrink:0;">
                {{ strtoupper(substr($user->name,0,1)) }}
            </div>
        @endif
        <div>
            <p style="font-size:13px;color:rgba(255,255,255,.7);margin:0 0 3px;">{{ $greeting }} 👋</p>
            <h1 style="font-size:22px;font-weight:800;color:#fff;margin:0;line-height:1.2;">{{ $user->name }}</h1>
            @if($user->job_title)
            <p style="font-size:12px;color:rgba(255,255,255,.65);margin:4px 0 0;">{{ $user->job_title }} · {{ now()->format('l, M j') }}</p>
            @else
            <p style="font-size:12px;color:rgba(255,255,255,.65);margin:4px 0 0;">{{ now()->format('l, F j, Y') }}</p>
            @endif
        </div>
    </div>
    @unless($isPreview)
    <button onclick="document.getElementById('report-modal').style.display='flex'"
            style="display:flex;align-items:center;gap:8px;padding:10px 20px;background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3);border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;backdrop-filter:blur(4px);transition:background .15s;flex-shrink:0;"
            onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
        <i class="fas fa-paper-plane" style="font-size:12px;"></i> Submit Report
    </button>
    @endunless
</div>

{{-- ═══════════════════════════════
     STATS  (single row: 5 cards)
════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px;">
    @foreach([
        ['Total Tasks',  $total,           'fa-list-check',              '#EEF2FF', '#4F46E5', 'Assigned to you',  'F0F0F0'],
        ['Completed',    $completed,       'fa-circle-check',            '#F0FDF4', '#16A34A', $rate.'% rate',     'F0F0F0'],
        ['In Progress',  $inProgress,      'fa-spinner',                 '#FFFBEB', '#D97706', 'Active tasks',     'F0F0F0'],
        ['In Review',    $pendingApproval, 'fa-hourglass-half',          '#F5F3FF', '#7C3AED', 'Awaiting approval','F0F0F0'],
        ['Overdue',      $overdue,         'fa-triangle-exclamation',    $overdue>0?'#FEF2F2':'#F8FAFC', $overdue>0?'#DC2626':'#9CA3AF', $overdue>0?'Needs attention':'All on time', $overdue>0?'FECACA':'F0F0F0'],
    ] as [$label,$val,$icon,$bg,$ic,$sub,$border])
    <div style="background:#fff;border-radius:14px;border:1px solid #{{ $border }};padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,.04);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <span style="font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">{{ $label }}</span>
            <div style="width:34px;height:34px;border-radius:10px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;">
                <i class="fas {{ $icon }}" style="font-size:14px;color:{{ $ic }};"></i>
            </div>
        </div>
        <p style="font-size:30px;font-weight:800;color:#111827;margin:0 0 2px;line-height:1;">{{ $val }}</p>
        <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $sub }}</p>
    </div>
    @endforeach
</div>

{{-- ═══════════════════════════════
     TABS
════════════════════════════════ --}}
<div x-data="{ tab: 'my-tasks' }">

    {{-- Tab bar --}}
    <div class="flex gap-1 bg-gray-100 p-1 rounded-xl mb-6" style="flex-wrap:wrap;">
        @foreach([['my-tasks','fa-square-check','My Tasks'],['team-tasks','fa-users','Team Tasks'],['my-projects','fa-diagram-project','My Projects']] as [$id,$icon,$label])
        <button @click="tab='{{ $id }}'"
                :class="tab==='{{ $id }}'
                    ? 'bg-white text-indigo-600 shadow-sm'
                    : 'bg-transparent text-gray-500 hover:text-gray-700'"
                class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition border-none cursor-pointer font-sans">
            <i class="fas {{ $icon }} text-xs"></i> {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- ══ MY TASKS ══ --}}
    <div x-show="tab==='my-tasks'">
        <div style="display:grid;grid-template-columns:10fr 2fr;gap:18px;align-items:start;">

            {{-- Left: task list + activity --}}
            <div style="display:flex;flex-direction:column;gap:18px;">

                {{-- Tasks --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden;">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #F3F4F6;">
                        <div>
                            <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">My Tasks</h3>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Sorted by urgency</p>
                        </div>
                        @unless($isPreview)
                        <a href="{{ route('user.tasks.index') }}" style="font-size:11px;font-weight:600;color:#6366F1;text-decoration:none;background:#EEF2FF;padding:5px 12px;border-radius:7px;">View all</a>
                        @endunless
                    </div>
                    @forelse($tasks->take(8) as $task)
                    @php
                        $isOv = $task->deadline && $task->deadline->isPast() && !in_array($task->status,['completed','submitted']);
                        $dl   = $task->deadline ? now()->diffInDays($task->deadline, false) : 0;
                        $sm   = ['completed'=>['#F0FDF4','#16A34A','Completed'],'in_progress'=>['#FFFBEB','#D97706','In Progress'],'pending'=>['#F8FAFC','#64748B','Pending'],'submitted'=>['#F5F3FF','#7C3AED','In Review']];
                        [$sbg,$sco,$slbl] = $sm[$task->status] ?? ['#F8FAFC','#9CA3AF','Unknown'];
                        $pco = ['high'=>'#DC2626','medium'=>'#D97706','low'=>'#16A34A'][$task->priority] ?? '#9CA3AF';
                    @endphp
                    <a href="{{ $isPreview ? route('admin.tasks.show',$task) : route('user.tasks.show',$task) }}"
                       style="display:flex;align-items:center;gap:12px;padding:11px 20px;border-bottom:1px solid #F9FAFB;text-decoration:none;background:{{ $isOv?'#FFF8F8':($task->status==='submitted'?'#FAFBFF':'#fff') }};transition:background .1s;"
                       onmouseover="this.style.background='#F5F5FF'" onmouseout="this.style.background='{{ $isOv?'#FFF8F8':'#fff' }}'">
                        <div style="width:8px;height:8px;border-radius:50%;background:{{ $pco }};flex-shrink:0;margin-top:1px;"></div>
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:13px;font-weight:600;color:{{ $task->status==='completed'?'#9CA3AF':'#111827' }};margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;{{ $task->status==='completed'?'text-decoration:line-through;':'' }}">{{ $task->title }}</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $task->project->name }}</p>
                        </div>
                        <span style="font-size:10px;font-weight:600;padding:3px 9px;border-radius:20px;background:{{ $sbg }};color:{{ $sco }};flex-shrink:0;">{{ $slbl }}</span>
                        <span style="font-size:11px;font-weight:{{ $isOv?'700':'500' }};color:{{ $isOv?'#DC2626':($dl<=3?'#D97706':'#9CA3AF') }};flex-shrink:0;white-space:nowrap;min-width:60px;text-align:right;">
                            @if($isOv)<i class="fas fa-triangle-exclamation" style="font-size:9px;margin-right:2px;"></i>Overdue
                            @elseif($task->status==='completed')<i class="fas fa-check" style="font-size:9px;margin-right:2px;color:#16A34A;"></i>Done
                            @elseif($task->status==='submitted')<i class="fas fa-hourglass-half" style="font-size:9px;margin-right:2px;color:#7C3AED;"></i>Review
                            @elseif(!$task->deadline)—
                            @elseif($dl===0)Today
                            @elseif($dl===1)Tomorrow
                            @else{{ $task->deadline->format('M d') }}
                            @endif
                        </span>
                    </a>
                    @empty
                    <div style="text-align:center;padding:48px 20px;">
                        <i class="fas fa-clipboard-list" style="color:#E5E7EB;font-size:36px;display:block;margin-bottom:12px;"></i>
                        <p style="font-size:13px;color:#9CA3AF;margin:0;">No tasks assigned yet</p>
                    </div>
                    @endforelse
                </div>

                {{-- Activity --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden;">
                    <div style="padding:16px 20px;border-bottom:1px solid #F3F4F6;">
                        <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">Recent Activity</h3>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Your latest updates</p>
                    </div>
                    @forelse($recentActivity as $log)
                    @php
                        $am=['status_updated_completed'=>['fa-circle-check','#16A34A','#F0FDF4','Completed'],'status_updated_in_progress'=>['fa-spinner','#D97706','#FFFBEB','Started'],'status_updated_pending'=>['fa-clock','#64748B','#F8FAFC','Set pending'],'status_updated_submitted'=>['fa-hourglass-half','#7C3AED','#F5F3FF','Submitted']];
                        [$ai,$ac,$ab,$al]=$am[$log->action]??['fa-circle-dot','#6366F1','#EEF2FF',ucfirst(str_replace('_',' ',$log->action))];
                    @endphp
                    <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 20px;border-bottom:1px solid #F9FAFB;">
                        <div style="width:30px;height:30px;border-radius:9px;background:{{ $ab }};display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                            <i class="fas {{ $ai }}" style="font-size:11px;color:{{ $ac }};"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:12px;font-weight:600;color:#111827;margin:0;">{{ $al }}
                                @if($log->task)<span style="font-weight:400;color:#6B7280;">— {{ Str::limit($log->task->title,32) }}</span>@endif
                            </p>
                            @if($log->note)<p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;font-style:italic;">"{{ Str::limit($log->note,50) }}"</p>@endif
                            <p style="font-size:10px;color:#C4C9D4;margin:2px 0 0;">{{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @empty
                    <div style="text-align:center;padding:28px;color:#9CA3AF;font-size:12px;">No activity yet</div>
                    @endforelse
                </div>

            </div>{{-- /left --}}

            {{-- Right sidebar --}}
            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Performance donut --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
                    <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0 0 2px;">Performance</h3>
                    <p style="font-size:11px;color:#9CA3AF;margin:0 0 16px;">Task status breakdown</p>
                    <div style="position:relative;width:160px;height:160px;margin:0 auto 14px;">
                        <canvas id="perfChart" width="160" height="160" style="display:block;"></canvas>
                        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                            <p style="font-size:26px;font-weight:800;color:#111827;margin:0;line-height:1;">{{ $rate }}<span style="font-size:13px;font-weight:600;color:#9CA3AF;">%</span></p>
                            <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;">Done</p>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        @foreach([['Completed','#10B981',$completed],['In Progress','#F59E0B',$inProgress],['In Review','#8B5CF6',$pendingApproval],['Overdue','#EF4444',$overdue]] as [$lbl,$lco,$lv])
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div style="display:flex;align-items:center;gap:7px;">
                                <div style="width:9px;height:9px;border-radius:3px;background:{{ $lco }};flex-shrink:0;"></div>
                                <span style="font-size:12px;color:#6B7280;">{{ $lbl }}</span>
                            </div>
                            <span style="font-size:12px;font-weight:700;color:#111827;">{{ $lv }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Weekly bar chart --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
                    <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0 0 2px;">Weekly Activity</h3>
                    <p style="font-size:11px;color:#9CA3AF;margin:0 0 14px;">Updates in last 7 days</p>
                    <div style="height:85px;position:relative;">
                        <canvas id="weekChart"></canvas>
                    </div>
                </div>

                {{-- Upcoming deadlines --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden;">
                    <div style="padding:14px 18px 12px;border-bottom:1px solid #F3F4F6;">
                        <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">Upcoming Deadlines</h3>
                    </div>
                    <div style="padding:6px 0;">
                        @forelse($upcomingTasks as $ut)
                        @php $dl2 = $ut->deadline ? (int)now()->diffInDays($ut->deadline,false) : 0; $urg=$dl2<=2; @endphp
                        <a href="{{ $isPreview ? route('admin.tasks.show',$ut) : route('user.tasks.show',$ut) }}"
                           style="display:flex;align-items:center;gap:12px;padding:9px 18px;text-decoration:none;transition:background .1s;"
                           onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='#fff'">
                            <div style="width:34px;height:34px;border-radius:9px;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;background:{{ $urg?'#FEF2F2':'#EEF2FF' }};">
                                <span style="font-size:12px;font-weight:800;line-height:1;color:{{ $urg?'#DC2626':'#4F46E5' }};">{{ $ut->deadline?->format('d') ?? '—' }}</span>
                                <span style="font-size:9px;color:{{ $urg?'#EF4444':'#818CF8' }};">{{ $ut->deadline?->format('M') ?? '' }}</span>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $ut->title }}</p>
                                <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;">{{ $dl2===0?'Due today':($dl2===1?'Tomorrow':"in {$dl2} days") }}</p>
                            </div>
                            @if($urg)<i class="fas fa-fire" style="color:#EF4444;font-size:11px;flex-shrink:0;"></i>@endif
                        </a>
                        @empty
                        <div style="text-align:center;padding:18px;font-size:12px;color:#9CA3AF;">No upcoming deadlines!</div>
                        @endforelse
                    </div>
                </div>

            </div>{{-- /right --}}
        </div>{{-- /grid --}}
    </div>{{-- /my-tasks --}}

    {{-- ══ TEAM TASKS ══ --}}
    <div x-show="tab==='team-tasks'" x-cloak>
        <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">Team Tasks</h3>
                    <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Tasks in your projects assigned to teammates</p>
                </div>
                <span style="font-size:11px;font-weight:600;padding:4px 12px;border-radius:20px;background:#EEF2FF;color:#4F46E5;">{{ $teamTasks->count() }} tasks</span>
            </div>
            @forelse($teamTasks as $task)
            @php
                $isOv2=$task->deadline&&$task->deadline->isPast()&&!in_array($task->status,['completed','submitted']);
                $sm2=['completed'=>['#F0FDF4','#16A34A','Completed'],'in_progress'=>['#FFFBEB','#D97706','In Progress'],'pending'=>['#F8FAFC','#64748B','Pending'],'submitted'=>['#F5F3FF','#7C3AED','In Review']];
                [$sb,$sc,$sl]=$sm2[$task->status]??['#F8FAFC','#9CA3AF','Unknown'];
                $pc2=['high'=>'#DC2626','medium'=>'#D97706','low'=>'#16A34A'][$task->priority]??'#9CA3AF';
            @endphp
            <div style="display:flex;align-items:center;gap:14px;padding:11px 20px;border-bottom:1px solid #F9FAFB;background:{{ $isOv2?'#FFF8F8':'#fff' }};">
                <div style="width:8px;height:8px;border-radius:50%;background:{{ $pc2 }};flex-shrink:0;"></div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $task->title }}</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $task->project->name }}</p>
                </div>
                @if($task->assignee)
                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                    <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;">
                        {{ strtoupper(substr($task->assignee->name,0,1)) }}
                    </div>
                    <span style="font-size:12px;color:#374151;font-weight:500;">{{ explode(' ',$task->assignee->name)[0] }}</span>
                </div>
                @endif
                <span style="font-size:10px;font-weight:600;padding:3px 9px;border-radius:20px;background:{{ $sb }};color:{{ $sc }};flex-shrink:0;">{{ $sl }}</span>
                <span style="font-size:11px;color:{{ $isOv2?'#DC2626':'#9CA3AF' }};font-weight:{{ $isOv2?'700':'400' }};flex-shrink:0;white-space:nowrap;">{{ $task->deadline?->format('M d') ?? '—' }}</span>
            </div>
            @empty
            <div style="text-align:center;padding:56px 20px;">
                <div style="width:56px;height:56px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                    <i class="fas fa-users" style="color:#D1D5DB;font-size:22px;"></i>
                </div>
                <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">No team tasks yet</p>
                <p style="font-size:12px;color:#9CA3AF;margin:0;">You'll see teammates' tasks once you're added to a project.</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ══ MY PROJECTS ══ --}}
    <div x-show="tab==='my-projects'" x-cloak>
        @if($myProjects->isEmpty())
        <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;padding:56px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <i class="fas fa-diagram-project" style="color:#D1D5DB;font-size:22px;"></i>
            </div>
            <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">No projects yet</p>
            <p style="font-size:12px;color:#9CA3AF;margin:0;">An admin will add you to projects when they're created.</p>
        </div>
        @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
            @foreach($myProjects as $proj)
            @php
                $pr2=$proj->tasks_count>0?round($proj->completed_count/$proj->tasks_count*100):0;
                $sc2=['active'=>['#EEF2FF','#4F46E5'],'completed'=>['#F0FDF4','#16A34A'],'overdue'=>['#FEF2F2','#DC2626']];
                [$pcbg,$pcco]=$sc2[$proj->status]??['#F3F4F6','#6B7280'];
            @endphp
            <a href="{{ $isPreview ? route('admin.projects.show',$proj) : route('user.projects.show',$proj) }}"
               style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;text-decoration:none;display:block;transition:box-shadow .15s,transform .15s;"
               onmouseover="this.style.boxShadow='0 6px 20px rgba(99,102,241,.14)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.04)';this.style.transform='translateY(0)'">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;">
                    <div style="width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-diagram-project" style="color:#fff;font-size:16px;"></i>
                    </div>
                    <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:10px;background:{{ $pcbg }};color:{{ $pcco }};">{{ ucfirst($proj->status) }}</span>
                </div>
                <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0 0 3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->name }}</h3>
                <p style="font-size:11px;color:#9CA3AF;margin:0 0 14px;">{{ $proj->deadline ? 'Due '.$proj->deadline->format('M d, Y') : 'No deadline' }}</p>
                <div style="margin-bottom:6px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                        <span style="font-size:11px;color:#6B7280;">Progress</span>
                        <span style="font-size:11px;font-weight:700;color:#4F46E5;">{{ $pr2 }}%</span>
                    </div>
                    <div style="height:6px;background:#F0F0F0;border-radius:999px;overflow:hidden;">
                        <div style="height:100%;width:{{ $pr2 }}%;background:linear-gradient(90deg,#6366F1,#8B5CF6);border-radius:999px;"></div>
                    </div>
                </div>
                <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $proj->completed_count }}/{{ $proj->tasks_count }} completed</p>
            </a>
            @endforeach
        </div>
        @if($myProjects->count()>=6)
        <div style="text-align:center;margin-top:16px;">
            <a href="{{ $isPreview ? route('admin.projects.index') : route('user.projects.index') }}" style="font-size:13px;font-weight:600;color:#6366F1;text-decoration:none;">View all projects →</a>
        </div>
        @endif
        @endif
    </div>

</div>{{-- /x-data --}}

{{-- ═══════════════════════════════
     SUBMIT REPORT MODAL
════════════════════════════════ --}}
@unless($isPreview)
<div id="report-modal"
     style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.5);"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:20px;box-shadow:0 28px 60px rgba(0,0,0,.18);width:100%;max-width:460px;overflow:hidden;" onclick="event.stopPropagation()">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 22px 16px;border-bottom:1px solid #F3F4F6;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-paper-plane" style="color:#4F46E5;font-size:15px;"></i>
                </div>
                <div>
                    <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0;">Submit Progress Report</h3>
                    <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Sent directly to the admin</p>
                </div>
            </div>
            <button onclick="document.getElementById('report-modal').style.display='none'"
                    style="width:32px;height:32px;border-radius:8px;border:none;background:#F3F4F6;cursor:pointer;color:#6B7280;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('user.report') }}" style="padding:20px 22px;">
            @csrf
            <div style="background:#F8FAFC;border-radius:12px;padding:14px;margin-bottom:16px;border:1px solid #F0F0F0;">
                <p style="font-size:10px;font-weight:700;color:#9CA3AF;margin:0 0 10px;text-transform:uppercase;letter-spacing:.06em;">Your Stats Today</p>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:4px;">
                    <div style="text-align:center;background:#fff;border-radius:8px;padding:10px 6px;border:1px solid #F0F0F0;">
                        <p style="font-size:20px;font-weight:800;color:#4F46E5;margin:0;line-height:1;">{{ $completed }}</p>
                        <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Completed</p>
                    </div>
                    <div style="text-align:center;background:#fff;border-radius:8px;padding:10px 6px;border:1px solid #F0F0F0;">
                        <p style="font-size:20px;font-weight:800;color:#D97706;margin:0;line-height:1;">{{ $inProgress }}</p>
                        <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">In Progress</p>
                    </div>
                    <div style="text-align:center;background:#fff;border-radius:8px;padding:10px 6px;border:1px solid #F0F0F0;">
                        <p style="font-size:20px;font-weight:800;color:#111827;margin:0;line-height:1;">{{ $rate }}%</p>
                        <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Rate</p>
                    </div>
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                    What did you work on? <span style="color:#EF4444;">*</span>
                </label>
                <textarea name="report" rows="4" required minlength="10" maxlength="1000"
                          placeholder="Describe your progress, blockers, and next steps..."
                          style="width:100%;padding:10px 13px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;color:#111827;resize:none;outline:none;box-sizing:border-box;line-height:1.6;transition:border-color .15s;"
                          onfocus="this.style.borderColor='#6366F1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,.1)'"
                          onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none'">{{ old('report') }}</textarea>
                @error('report')<p style="font-size:11px;color:#DC2626;margin:4px 0 0;">{{ $message }}</p>@enderror
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="document.getElementById('report-modal').style.display='none'"
                        style="flex:1;padding:10px;font-size:13px;font-weight:600;background:#F3F4F6;color:#374151;border:none;border-radius:10px;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit"
                        style="flex:2;padding:10px;font-size:13px;font-weight:600;background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;border:none;border-radius:10px;cursor:pointer;box-shadow:0 4px 12px rgba(79,70,229,.3);">
                    <i class="fas fa-paper-plane" style="font-size:11px;margin-right:5px;"></i>Send Report
                </button>
            </div>
        </form>
    </div>
</div>
@endunless

@endsection

@push('scripts')
@php
    $perfTotal  = $completed + $inProgress + $pendingApproval + $overdue;
    $perfData   = $perfTotal > 0 ? [$completed, $inProgress, $pendingApproval, $overdue] : [1];
    $perfColors = $perfTotal > 0 ? ['#10B981','#F59E0B','#8B5CF6','#EF4444'] : ['#E5E7EB'];
@endphp
<script>
(function () {
    var ctx = document.getElementById('perfChart');
    if (!ctx) return;
    new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Completed','In Progress','In Review','Overdue'],
            datasets: [{
                data: @json($perfData),
                backgroundColor: @json($perfColors),
                borderWidth: 0,
                hoverOffset: {{ $perfTotal > 0 ? 4 : 0 }}
            }]
        },
        options: {
            responsive: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: {{ $perfTotal > 0 ? 'true' : 'false' }} }
            },
            animation: { duration: 500 }
        }
    });
}());

new Chart(document.getElementById('weekChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: @json($weekActivity->pluck('label')),
        datasets: [{ data: @json($weekActivity->pluck('count')),
            backgroundColor: '#818CF8', borderRadius: 5, borderSkipped: false }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9CA3AF' } },
            y: { grid: { color: '#F3F4F6' }, ticks: { font: { size: 10 }, color: '#9CA3AF', stepSize: 1, precision: 0 }, beginAtZero: true }
        },
        animation: { duration: 600 }
    }
});

@if($errors->has('report'))
document.getElementById('report-modal').style.display = 'flex';
@endif
</script>
@endpush

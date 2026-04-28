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
     STATS  (single row: 6 cards)
════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:20px;">
    @foreach([
        ['Total Tasks',   $total + $pendingSocialPosts, 'fa-list-check', '#EEF2FF', '#4F46E5', 'Assigned to you', 'F0F0F0', 'total', '#4F46E5'],
        ['Completed',     $completed + $completedSocialPosts, 'fa-circle-check', '#F0FDF4', '#16A34A', $rate.'% rate'.($completedSocialPosts>0?' · '.$completedSocialPosts.' post'.($completedSocialPosts>1?'s':'').' done':''), 'F0F0F0', 'completed', '#16A34A'],
        ['In Progress',   $inProgress,      'fa-spinner',              '#FFFBEB', '#D97706', 'Active tasks',      'F0F0F0', 'in_progress', '#D97706'],
        ['In Review',     $pendingApproval, 'fa-hourglass-half',       '#F5F3FF', '#7C3AED', 'Awaiting approval', 'F0F0F0', 'in_review', '#7C3AED'],
        ['Overdue',       $overdue,         'fa-triangle-exclamation', $overdue>0?'#FEF2F2':'#F8FAFC', $overdue>0?'#DC2626':'#9CA3AF', $overdue>0?'Needs attention':'All on time', $overdue>0?'FECACA':'F0F0F0', 'overdue', '#EF4444'],
        ['Pending Posts', $pendingSocialPosts, 'fa-share-nodes',       $pendingSocialPosts>0?'#FFF7ED':'#F8FAFC', $pendingSocialPosts>0?'#EA580C':'#9CA3AF', $pendingSocialPosts>0?'Needs posting':($completedSocialPosts>0?$completedSocialPosts.' completed':'No pending posts'), $pendingSocialPosts>0?'FED7AA':'F0F0F0', 'social', '#EA580C'],
    ] as $i => [$label,$val,$icon,$bg,$ic,$sub,$border,$filter,$color])
    <div onclick="openUserStatsModal('{{ $filter }}','{{ $label }}','{{ $color }}','{{ $bg }}','{{ $icon }}')"
         onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)';this.style.transform='translateY(-1px)'"
         onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.04)';this.style.transform='translateY(0)'"
         style="background:#fff;border-radius:14px;border:1px solid #{{ $border }};padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,.04);cursor:pointer;transition:box-shadow .15s,transform .15s;">
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
     STATS MODAL
════════════════════════════════ --}}
<div id="userStatsModal" style="display:none;position:fixed;inset:0;z-index:9999;overflow-y:auto;">
    <div onclick="closeUserStatsModal()" style="position:fixed;inset:0;background:rgba(15,23,42,0.55);backdrop-filter:blur(3px);"></div>
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:center;min-height:100%;padding:40px 16px;">
        <div style="background:#fff;border-radius:20px;width:100%;max-width:620px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,0.22);animation:usmSlideUp .22s ease;">

            {{-- Header --}}
            <div id="usmHeader" style="padding:20px 24px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div id="usmIcon" style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0;"></div>
                    <div>
                        <h2 id="usmTitle" style="font-size:17px;font-weight:700;color:#fff;margin:0;"></h2>
                        <p id="usmSub" style="font-size:12px;color:rgba(255,255,255,.75);margin:3px 0 0;">Your tasks</p>
                    </div>
                </div>
                <button onclick="closeUserStatsModal()" style="background:rgba(255,255,255,.15);border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-times" style="color:#fff;font-size:13px;"></i>
                </button>
            </div>

            {{-- Loading --}}
            <div id="usmLoading" style="display:none;padding:48px 24px;text-align:center;">
                <div style="width:32px;height:32px;border:3px solid #E5E7EB;border-top-color:#6366F1;border-radius:50%;animation:usmSpin .7s linear infinite;display:inline-block;"></div>
                <p style="margin:12px 0 0;font-size:13px;color:#9CA3AF;">Loading tasks…</p>
            </div>

            {{-- Empty --}}
            <div id="usmEmpty" style="display:none;padding:48px 24px;text-align:center;">
                <i class="fas fa-check-circle" style="font-size:36px;color:#D1FAE5;"></i>
                <p style="margin:10px 0 0;font-size:14px;color:#6B7280;">No tasks in this category.</p>
            </div>

            {{-- List --}}
            <div id="usmList" style="display:none;padding:16px 20px 20px;">
                <p id="usmCount" style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin:0 0 12px;"></p>
                <div id="usmItems" style="max-height:420px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;"></div>
                <div style="padding-top:14px;border-top:1px solid #F3F4F6;margin-top:12px;">
                    <a href="{{ route('user.tasks.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#6366F1;text-decoration:none;">
                        View all tasks <i class="fas fa-arrow-right" style="font-size:10px;"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
@keyframes usmSlideUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
@keyframes usmSpin    { to { transform:rotate(360deg); } }
</style>

<script>
var _usmUrl = '{{ route('user.tasks.modal') }}';

function openUserStatsModal(filter, label, color, bg, icon, date) {
    var modal   = document.getElementById('userStatsModal');
    var header  = document.getElementById('usmHeader');
    var iconEl  = document.getElementById('usmIcon');
    var titleEl = document.getElementById('usmTitle');
    var loading = document.getElementById('usmLoading');
    var empty   = document.getElementById('usmEmpty');
    var list    = document.getElementById('usmList');
    var countEl = document.getElementById('usmCount');
    var items   = document.getElementById('usmItems');

    header.style.background = 'linear-gradient(135deg,' + color + 'dd,' + color + ')';
    iconEl.innerHTML = '<i class="fas ' + _usmEsc(icon) + '"></i>';
    titleEl.textContent = label;

    loading.style.display = 'block';
    empty.style.display   = 'none';
    list.style.display    = 'none';
    items.innerHTML       = '';
    countEl.textContent   = '';

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';

    var _usmQuery = '?filter=' + encodeURIComponent(filter) + (date ? '&date=' + encodeURIComponent(date) : '');
    fetch(_usmUrl + _usmQuery, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        loading.style.display = 'none';
        var tasks = data.tasks || [];
        if (!tasks.length) {
            empty.style.display = 'block';
            return;
        }
        countEl.textContent = tasks.length + ' task' + (tasks.length !== 1 ? 's' : '');
        items.innerHTML = tasks.map(function(t) {
            var deadlineHtml = '';
            if (t.deadline) {
                var dStyle = t.isOverdue ? 'color:#EF4444;font-weight:600;' : 'color:#9CA3AF;';
                deadlineHtml = '<span style="' + dStyle + 'font-size:11px;display:flex;align-items:center;gap:3px;">'
                    + (t.isOverdue ? '<i class="fas fa-exclamation-circle"></i> ' : '<i class="far fa-calendar"></i> ')
                    + _usmEsc(t.deadline) + '</span>';
            }
            var priorityBgs = { high: '#FEF2F2', medium: '#FFFBEB', low: '#ECFDF5' };
            var priorityHtml = '';
            if (t.priority && t.priorityMeta) {
                priorityHtml = '<span style="font-size:10px;font-weight:700;color:' + _usmEsc(t.priorityMeta.color) + ';background:' + _usmEsc(priorityBgs[t.priority] || '#F3F4F6') + ';padding:2px 7px;border-radius:20px;">'
                    + _usmEsc(t.priorityMeta.label) + '</span>';
            }
            var projectHtml = t.project
                ? '<span style="font-size:11px;color:#9CA3AF;"><i class="fas fa-folder" style="font-size:9px;margin-right:3px;"></i>' + _usmEsc(t.project) + '</span>'
                : '';
            return '<a href="' + _usmEsc(t.url) + '" style="display:block;text-decoration:none;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:12px 14px;transition:box-shadow .15s;" '
                + 'onmouseover="this.style.boxShadow=\'0 4px 12px rgba(0,0,0,0.08)\'" onmouseout="this.style.boxShadow=\'none\'">'
                + '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">'
                +   '<p style="font-size:13px;font-weight:600;color:#111827;margin:0 0 6px;line-height:1.4;">' + _usmEsc(t.title) + '</p>'
                +   '<span style="font-size:10px;font-weight:700;color:' + _usmEsc(t.statusColor) + ';background:' + _usmEsc(t.statusBg) + ';padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0;">' + _usmEsc(t.statusLabel) + '</span>'
                + '</div>'
                + '<div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;">'
                + priorityHtml + projectHtml + deadlineHtml
                + '</div>'
                + '</a>';
        }).join('');
        list.style.display = 'block';
    })
    .catch(function() {
        loading.style.display = 'none';
        empty.style.display   = 'block';
    });
}

function closeUserStatsModal() {
    document.getElementById('userStatsModal').style.display = 'none';
    document.body.style.overflow = '';
}

function _usmEsc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeUserStatsModal();
});
</script>

{{-- ═══════════════════════════════
     TABS
════════════════════════════════ --}}
<div x-data="{ tab: 'my-tasks' }">

    {{-- Tab bar --}}
    <div id="dashboard-tabs" class="flex gap-1 bg-gray-100 p-1 rounded-xl mb-6" style="flex-wrap:wrap;">
        @foreach([['my-tasks','fa-square-check','My Tasks'],['team-tasks','fa-users','Team Tasks'],['my-projects','fa-diagram-project','My Projects']] as [$id,$icon,$label])
        <button id="{{ $id === 'my-projects' ? 'proj-tab-btn' : ($id === 'my-tasks' ? 'my-tasks-tab-btn' : '') }}" @click="tab='{{ $id }}'"
                :class="tab==='{{ $id }}'
                    ? 'bg-white text-indigo-600 shadow-sm'
                    : 'bg-transparent text-gray-500 hover:text-gray-700'"
                class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition border-none cursor-pointer font-sans">
            <i class="fas {{ $icon }} text-xs"></i> {{ $label }}
        </button>
        @endforeach
        <button id="social-tab-btn" @click="tab='social'"
                :class="tab==='social'
                    ? 'bg-white text-indigo-600 shadow-sm'
                    : 'bg-transparent text-gray-500 hover:text-gray-700'"
                class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition border-none cursor-pointer font-sans">
            <i class="fas fa-share-nodes text-xs"></i> Social Posts
            @if($socialTasks->where('social_posted_at', null)->count() > 0)
            <span style="font-size:10px;font-weight:700;background:#EF4444;color:#fff;padding:1px 6px;border-radius:20px;line-height:1.6;">
                {{ $socialTasks->whereNull('social_posted_at')->count() }}
            </span>
            @endif
        </button>
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
                        $doneStatuses = ['approved','delivered','archived'];
                        $isDone = in_array($task->status, $doneStatuses);
                        $isOv = $task->deadline && $task->deadline->isPast() && !$isDone && $task->status !== 'submitted';
                        $dl   = $task->deadline ? now()->diffInDays($task->deadline, false) : 0;
                        $sm   = [
                            'draft'              => ['#F3F4F6','#6B7280','Draft'],
                            'assigned'           => ['#EEF2FF','#4F46E5','Assigned'],
                            'viewed'             => ['#F0F9FF','#0369A1','Viewed'],
                            'in_progress'        => ['#FFFBEB','#D97706','In Progress'],
                            'submitted'          => ['#F5F3FF','#7C3AED','In Review'],
                            'revision_requested' => ['#FFF7ED','#C2410C','Revision'],
                            'approved'           => ['#F0FDF4','#16A34A','Approved'],
                            'delivered'          => ['#ECFDF5','#047857','Delivered'],
                            'archived'           => ['#F3F4F6','#6B7280','Archived'],
                        ];
                        [$sbg,$sco,$slbl] = $sm[$task->status] ?? ['#F8FAFC','#9CA3AF','Unknown'];
                        $pco = ['high'=>'#DC2626','medium'=>'#D97706','low'=>'#16A34A'][$task->priority] ?? '#9CA3AF';
                    @endphp
                    @if($task->is_social)
                    <a href="{{ route('social.show', $task) }}"
                       style="display:flex;align-items:center;gap:12px;padding:11px 20px;border-bottom:1px solid #F9FAFB;text-decoration:none;background:#FAFBFF;transition:background .1s;"
                       onmouseover="this.style.background='#F0F4FF'" onmouseout="this.style.background='#FAFBFF'">
                        <div style="width:8px;height:8px;border-radius:50%;background:#6366F1;flex-shrink:0;margin-top:1px;"></div>
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $task->title }}</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $task->project?->name ?? 'Social Post' }}</p>
                        </div>
                        <span style="font-size:10px;font-weight:600;padding:3px 9px;border-radius:20px;background:#EEF2FF;color:#4F46E5;flex-shrink:0;display:flex;align-items:center;gap:4px;">
                            <i class="fas fa-share-nodes" style="font-size:9px;"></i> Post Pending
                        </span>
                        <span style="font-size:11px;font-weight:500;color:#D97706;flex-shrink:0;white-space:nowrap;min-width:60px;text-align:right;">
                            @if($task->deadline){{ now()->diffInDays($task->deadline,false) === 0 ? 'Today' : $task->deadline->format('M d') }}@else—@endif
                        </span>
                    </a>
                    @else
                    <a href="{{ $isPreview ? route('admin.tasks.show',$task) : route('user.tasks.show',$task) }}"
                       style="display:flex;align-items:center;gap:12px;padding:11px 20px;border-bottom:1px solid #F9FAFB;text-decoration:none;background:{{ $isOv?'#FFF8F8':($task->status==='submitted'?'#FAFBFF':'#fff') }};transition:background .1s;"
                       onmouseover="this.style.background='#F5F5FF'" onmouseout="this.style.background='{{ $isOv?'#FFF8F8':'#fff' }}'">
                        <div style="width:8px;height:8px;border-radius:50%;background:{{ $pco }};flex-shrink:0;margin-top:1px;"></div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <p style="font-size:13px;font-weight:600;color:{{ $isDone?'#9CA3AF':'#111827' }};margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;{{ $isDone?'text-decoration:line-through;':'' }}">{{ $task->title }}</p>
                                @if(!empty($task->is_received))
                                <span style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:5px;background:#FEF9C3;color:#854D0E;flex-shrink:0;white-space:nowrap;border:1px solid #FDE68A;">
                                    <i class="fas fa-arrows-rotate" style="font-size:8px;margin-right:2px;"></i>{{ $task->from_user ? 'from '.$task->from_user : 'Reassigned' }}
                                </span>
                                @endif
                            </div>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $task->project?->name }}</p>
                        </div>
                        <span style="font-size:10px;font-weight:600;padding:3px 9px;border-radius:20px;background:{{ $sbg }};color:{{ $sco }};flex-shrink:0;">{{ $slbl }}</span>
                        <span style="font-size:11px;font-weight:{{ $isOv?'700':'500' }};color:{{ $isOv?'#DC2626':($dl<=3?'#D97706':'#9CA3AF') }};flex-shrink:0;white-space:nowrap;min-width:60px;text-align:right;">
                            @if($isOv)<i class="fas fa-triangle-exclamation" style="font-size:9px;margin-right:2px;"></i>Overdue
                            @elseif($isDone)<i class="fas fa-check" style="font-size:9px;margin-right:2px;color:#16A34A;"></i>Done
                            @elseif($task->status==='submitted')<i class="fas fa-hourglass-half" style="font-size:9px;margin-right:2px;color:#7C3AED;"></i>Review
                            @elseif(!$task->deadline)—
                            @elseif($dl===0)Today
                            @elseif($dl===1)Tomorrow
                            @else{{ $task->deadline->format('M d') }}
                            @endif
                        </span>
                    </a>
                    @endif
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

                {{-- Combined Performance & Project Statistics card --}}
                @php
                    $perfCompleted = $completed + $completedSocialPosts;
                    $perfTotal     = $perfCompleted + $inProgress + $pendingApproval + $overdue;
                    $perfRate      = $perfTotal > 0 ? round($perfCompleted / $perfTotal * 100) : $rate;
                @endphp
                <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:20px;transition:box-shadow .2s,transform .2s;"
                     onmouseover="this.style.boxShadow='0 6px 24px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.05)'">

                    {{-- Header --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <div>
                            <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">Performance & Projects</h3>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Task completion &amp; project overview</p>
                        </div>
                        <button onclick="document.getElementById('proj-tab-btn').click(); setTimeout(()=>document.getElementById('dashboard-tabs').scrollIntoView({behavior:'smooth'}),50);"
                                style="font-size:11px;color:#4F46E5;background:#EEF2FF;border:none;cursor:pointer;font-weight:600;padding:5px 12px;border-radius:7px;">View Projects</button>
                    </div>

                    {{-- Task Performance: donut + stat grid --}}
                    <div style="display:flex;align-items:center;gap:20px;margin-bottom:16px;">
                        <div style="flex-shrink:0;display:flex;flex-direction:column;align-items:center;gap:5px;">
                            <div style="position:relative;width:130px;height:130px;">
                                <canvas id="perfChart" style="display:block;width:130px!important;height:130px!important;cursor:pointer;"></canvas>
                                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                                    <p style="font-size:22px;font-weight:800;color:#111827;margin:0;line-height:1;">{{ $perfRate }}<span style="font-size:11px;font-weight:600;color:#9CA3AF;">%</span></p>
                                    <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;">Tasks Done</p>
                                </div>
                            </div>
                            <p style="font-size:10px;color:#C4B5FD;margin:0;text-align:center;white-space:nowrap;">
                                <i class="fas fa-hand-pointer" style="font-size:9px;margin-right:3px;"></i>Click to view tasks
                            </p>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;">
                                @foreach([['Completed','#10B981',$completed + $completedSocialPosts],['In Progress','#F59E0B',$inProgress],['In Review','#8B5CF6',$pendingApproval],['Overdue','#EF4444',$overdue]] as [$lbl,$lco,$lv])
                                <div style="background:#F9FAFB;border-radius:8px;padding:8px 10px;border:1px solid #F0F0F0;">
                                    <p style="font-size:18px;font-weight:800;color:{{ $lco }};margin:0;line-height:1;">{{ $lv }}</p>
                                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">{{ $lbl }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div style="border-top:1px solid #F3F4F6;margin-bottom:14px;"></div>

                    {{-- Project Statistics: donut + stats + legend --}}
                    <div style="display:flex;align-items:center;gap:20px;margin-bottom:14px;">
                        <div style="flex-shrink:0;display:flex;flex-direction:column;align-items:center;gap:5px;">
                            <div style="position:relative;width:130px;height:130px;">
                                <canvas id="myProjChart" style="display:block;width:130px!important;height:130px!important;cursor:pointer;"></canvas>
                                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                                    <p style="font-size:22px;font-weight:700;color:#111827;margin:0;line-height:1;">{{ $myProjectStats['active'] }}</p>
                                    <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;">Active</p>
                                </div>
                            </div>
                            <p style="font-size:10px;color:#C4B5FD;margin:0;text-align:center;white-space:nowrap;">
                                <i class="fas fa-hand-pointer" style="font-size:9px;margin-right:3px;"></i>Click to view tasks
                            </p>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-bottom:10px;">
                                <div style="background:#F9FAFB;border-radius:8px;padding:8px 10px;border:1px solid #F0F0F0;">
                                    <p style="font-size:18px;font-weight:800;color:#06B6D4;margin:0;line-height:1;">{{ $myProjectStats['completed'] }}</p>
                                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Completed</p>
                                </div>
                                <div style="background:#F9FAFB;border-radius:8px;padding:8px 10px;border:1px solid #F0F0F0;">
                                    <p style="font-size:18px;font-weight:800;color:#3B82F6;margin:0;line-height:1;">{{ $myProjectStats['active'] }}</p>
                                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Active</p>
                                </div>
                                <div style="background:#F9FAFB;border-radius:8px;padding:8px 10px;border:1px solid #F0F0F0;">
                                    <p style="font-size:18px;font-weight:800;color:#F97316;margin:0;line-height:1;">{{ $myProjectStats['overdue'] }}</p>
                                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Overdue</p>
                                </div>
                                <div style="background:#F9FAFB;border-radius:8px;padding:8px 10px;border:1px solid #F0F0F0;">
                                    <p style="font-size:18px;font-weight:800;color:#111827;margin:0;line-height:1;">{{ $myProjectStats['total'] }}</p>
                                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Total</p>
                                </div>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:5px;">
                                @foreach([['Completed','#06B6D4'],['Active','#3B82F6'],['Overdue','#F97316']] as [$lbl,$lc])
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $lc }};display:inline-block;flex-shrink:0;"></span>
                                    <span style="font-size:11px;color:#6B7280;">{{ $lbl }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Project list --}}
                    @if($myProjects->isNotEmpty())
                    <div style="border-top:1px solid #F3F4F6;padding-top:12px;display:flex;flex-direction:column;gap:7px;">
                        @foreach($myProjects->take(4) as $proj)
                        @php $pColors=['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6']; $pc=$pColors[$loop->index % 5]; @endphp
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $pc }};flex-shrink:0;display:inline-block;"></span>
                            <span style="flex:1;font-size:12px;color:#374151;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->name }}</span>
                            <span style="font-size:10px;color:#9CA3AF;">{{ $proj->tasks_count }} tasks</span>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Social Media mini section --}}
                    @if($pendingSocialPosts > 0 || $completedSocialPosts > 0)
                    <div style="border-top:1px solid #F3F4F6;padding-top:12px;margin-top:12px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                            <span style="font-size:12px;font-weight:600;color:#374151;display:flex;align-items:center;gap:5px;">
                                <i class="fas fa-share-nodes" style="font-size:10px;color:#6366F1;"></i> Social Posts
                            </span>
                            <button onclick="document.getElementById('social-tab-btn').click(); setTimeout(()=>document.getElementById('dashboard-tabs').scrollIntoView({behavior:'smooth'}),50);"
                                    style="font-size:10px;color:#6366F1;background:none;border:none;cursor:pointer;font-weight:600;padding:0;">View all</button>
                        </div>
                        <div style="display:flex;gap:10px;">
                            <div style="flex:1;background:#F0FDF4;border-radius:8px;padding:8px 10px;text-align:center;">
                                <p style="font-size:16px;font-weight:800;color:#16A34A;margin:0;line-height:1;">{{ $completedSocialPosts }}</p>
                                <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Posted</p>
                            </div>
                            <div style="flex:1;background:{{ $pendingSocialPosts > 0 ? '#FFF7ED' : '#F8FAFC' }};border-radius:8px;padding:8px 10px;text-align:center;">
                                <p style="font-size:16px;font-weight:800;color:{{ $pendingSocialPosts > 0 ? '#EA580C' : '#9CA3AF' }};margin:0;line-height:1;">{{ $pendingSocialPosts }}</p>
                                <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Pending</p>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>

            </div>{{-- /left --}}

            {{-- Right sidebar --}}
            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Work Contribution Breakdown (credibility card) --}}
                @if($receivedTotal > 0)
                <div style="background:#fff;border-radius:14px;border:1.5px solid #FDE68A;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:18px;transition:box-shadow .2s,transform .2s;"
                     onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.08)';this.style.transform='translateY(-2px)'"
                     onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.04)';this.style.transform='translateY(0)'">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                        <div style="width:28px;height:28px;border-radius:8px;background:#FEF9C3;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-star" style="font-size:11px;color:#CA8A04;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:13px;font-weight:700;color:#111827;margin:0;">Work Contribution</h3>
                            <p style="font-size:10px;color:#9CA3AF;margin:0;">Performance breakdown for management</p>
                        </div>
                    </div>

                    {{-- Own tasks row --}}
                    <div style="margin-bottom:10px;cursor:pointer;border-radius:8px;padding:6px 8px;margin-left:-8px;margin-right:-8px;transition:background .15s;"
                         onclick="openUserStatsModal('total','My Own Tasks','#6366F1','#EEF2FF','fa-list-check')"
                         onmouseover="this.style.background='#F5F3FF'" onmouseout="this.style.background='transparent'">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                            <span style="font-size:11px;font-weight:600;color:#374151;">My Own Tasks</span>
                            <span style="font-size:11px;font-weight:700;color:#374151;">{{ $nativeCompleted }}<span style="color:#9CA3AF;font-weight:400;">/{{ $nativeTotal }}</span></span>
                        </div>
                        <div style="height:5px;background:#F3F4F6;border-radius:3px;overflow:hidden;">
                            <div style="height:5px;width:{{ $nativeTotal > 0 ? round($nativeCompleted/$nativeTotal*100) : 0 }}%;background:#6366F1;border-radius:3px;"></div>
                        </div>
                        <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">{{ $nativeTotal > 0 ? round($nativeCompleted/$nativeTotal*100) : 0 }}% completion rate &mdash; click to view</p>
                    </div>

                    {{-- Received from team row --}}
                    <div style="margin-bottom:12px;cursor:pointer;border-radius:8px;padding:6px 8px;margin-left:-8px;margin-right:-8px;transition:background .15s;"
                         onclick="openUserStatsModal('received','Received from Team','#CA8A04','#FEF9C3','fa-arrows-rotate')"
                         onmouseover="this.style.background='#FEFCE8'" onmouseout="this.style.background='transparent'">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                            <span style="font-size:11px;font-weight:600;color:#374151;">
                                <i class="fas fa-arrows-rotate" style="font-size:9px;color:#CA8A04;margin-right:4px;"></i>Received from Team
                            </span>
                            <span style="font-size:11px;font-weight:700;color:#374151;">{{ $receivedCompleted }}<span style="color:#9CA3AF;font-weight:400;">/{{ $receivedTotal }}</span></span>
                        </div>
                        <div style="height:5px;background:#F3F4F6;border-radius:3px;overflow:hidden;">
                            <div style="height:5px;width:{{ $receivedTotal > 0 ? round($receivedCompleted/$receivedTotal*100) : 0 }}%;background:#CA8A04;border-radius:3px;"></div>
                        </div>
                        <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Tasks reassigned by management &mdash; click to view</p>
                    </div>

                    {{-- Combined total --}}
                    <div style="border-top:1px solid #FEF3C7;padding-top:10px;cursor:pointer;border-radius:8px;transition:background .15s;"
                         onclick="openUserStatsModal('total','All My Tasks','#4F46E5','#EEF2FF','fa-chart-pie')"
                         onmouseover="this.style.background='#F5F3FF'" onmouseout="this.style.background='transparent'">
                        @php
                            $allDone  = $nativeCompleted + $receivedCompleted;
                            $allTotal = $nativeTotal + $receivedTotal;
                            $allRate  = $allTotal > 0 ? round($allDone/$allTotal*100) : 0;
                        @endphp
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:12px;font-weight:700;color:#111827;">Total Contribution</span>
                            <span style="font-size:13px;font-weight:800;color:{{ $allRate >= 80 ? '#16A34A' : ($allRate >= 50 ? '#D97706' : '#DC2626') }};">{{ $allRate }}%</span>
                        </div>
                        <p style="font-size:10px;color:#9CA3AF;margin:4px 0 0;">{{ $allDone }} of {{ $allTotal }} tasks completed &mdash; click to view all</p>
                    </div>
                </div>
                @endif

                {{-- Weekly bar chart --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
                    <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0 0 2px;">Weekly Activity</h3>
                    <p style="font-size:11px;color:#9CA3AF;margin:0 0 10px;">Updates in last 7 days</p>
                    <div style="height:85px;position:relative;">
                        <canvas id="weekChart" style="cursor:pointer;"></canvas>
                    </div>
                    <p style="font-size:10px;color:#C4B5FD;margin:8px 0 0;text-align:center;">
                        <i class="fas fa-hand-pointer" style="font-size:9px;margin-right:3px;"></i>Click a bar to view tasks
                    </p>
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
                $isDone2=in_array($task->status,['approved','delivered','archived']);
                $isOv2=$task->deadline&&$task->deadline->isPast()&&!$isDone2&&$task->status!=='submitted';
                $sm2=['draft'=>['#F3F4F6','#6B7280','Draft'],'assigned'=>['#EEF2FF','#4F46E5','Assigned'],'viewed'=>['#F0F9FF','#0369A1','Viewed'],'in_progress'=>['#FFFBEB','#D97706','In Progress'],'submitted'=>['#F5F3FF','#7C3AED','In Review'],'revision_requested'=>['#FFF7ED','#C2410C','Revision'],'approved'=>['#F0FDF4','#16A34A','Approved'],'delivered'=>['#ECFDF5','#047857','Delivered'],'archived'=>['#F3F4F6','#6B7280','Archived']];
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

    {{-- ══ SOCIAL POSTS ══ --}}
    <div x-show="tab==='social'" x-cloak>
        @php
        $socialPosted   = $socialTasks->whereNotNull('social_posted_at');
        $socialPending  = $socialTasks->whereNull('social_posted_at');
        $pMetaDash = [
            'facebook'  => ['Facebook',   'fa-facebook',   '#1877F2','#EBF3FF'],
            'instagram' => ['Instagram',  'fa-instagram',  '#E1306C','#FFF0F5'],
            'twitter'   => ['Twitter / X','fa-x-twitter',  '#000000','#F5F5F5'],
            'linkedin'  => ['LinkedIn',   'fa-linkedin',   '#0A66C2','#EAF2FB'],
            'tiktok'    => ['TikTok',     'fa-tiktok',     '#010101','#F5F5F5'],
            'youtube'   => ['YouTube',    'fa-youtube',    '#FF0000','#FFF0F0'],
            'snapchat'  => ['Snapchat',   'fa-snapchat',   '#F7CA00','#FFFDE7'],
            'other'     => ['Other',      'fa-share-nodes','#6366F1','#EEF2FF'],
        ];
        @endphp

        @if($socialTasks->isEmpty())
        <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:56px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <i class="fas fa-share-nodes" style="color:#D1D5DB;font-size:22px;"></i>
            </div>
            <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">No social media tasks assigned</p>
            <p style="font-size:12px;color:#9CA3AF;margin:0;">When an admin assigns you to post content, it will appear here.</p>
        </div>
        @else
        <div style="display:flex;flex-direction:column;gap:14px;">

            {{-- Pending tasks --}}
            @if($socialPending->isNotEmpty())
            <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden;">
                <div style="padding:14px 20px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:8px;height:8px;border-radius:50%;background:#EF4444;"></div>
                        <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">Pending Posts</h3>
                        <span style="font-size:11px;font-weight:700;background:#FEF2F2;color:#DC2626;padding:2px 9px;border-radius:20px;">{{ $socialPending->count() }} pending</span>
                    </div>
                </div>
                @foreach($socialPending as $st)
                <a href="{{ route('social.show', $st) }}"
                   style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid #F9FAFB;text-decoration:none;background:#fff;transition:background .1s;"
                   onmouseover="this.style.background='#FFF8F8'" onmouseout="this.style.background='#fff'">
                    <div style="width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-share-nodes" style="color:#fff;font-size:15px;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $st->title }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">
                            @if($st->project)
                            <i class="fas fa-folder" style="font-size:9px;margin-right:3px;color:#A5B4FC;"></i>{{ $st->project->name }} ·
                            @endif
                            @if($st->deadline)
                            Due {{ $st->deadline->format('M d, Y') }}
                            @else
                            No deadline
                            @endif
                        </p>
                    </div>
                    <div style="flex-shrink:0;text-align:right;">
                        @if($st->deadline && $st->deadline->isPast())
                        <span style="font-size:11px;font-weight:700;background:#FEF2F2;color:#DC2626;padding:3px 9px;border-radius:20px;">Overdue</span>
                        @else
                        <span style="font-size:11px;font-weight:600;background:#FEF3C7;color:#D97706;padding:3px 9px;border-radius:20px;">Needs Posting</span>
                        @endif
                        <p style="font-size:10px;color:#A5B4FC;margin:4px 0 0;font-weight:600;">Tap to post &rarr;</p>
                    </div>
                </a>
                @endforeach
            </div>
            @endif

            {{-- Completed / posted tasks --}}
            @if($socialPosted->isNotEmpty())
            <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden;">
                <div style="padding:14px 20px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:8px;">
                    <div style="width:8px;height:8px;border-radius:50%;background:#10B981;"></div>
                    <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">Posted</h3>
                    <span style="font-size:11px;font-weight:700;background:#ECFDF5;color:#059669;padding:2px 9px;border-radius:20px;">{{ $socialPosted->count() }} completed</span>
                </div>
                @foreach($socialPosted as $st)
                <a href="{{ route('social.show', $st) }}"
                   style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid #F9FAFB;text-decoration:none;background:#fff;transition:background .1s;"
                   onmouseover="this.style.background='#F6FFF9'" onmouseout="this.style.background='#fff'">
                    <div style="width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,#059669,#10B981);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-circle-check" style="color:#fff;font-size:15px;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:13px;font-weight:600;color:#374151;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $st->title }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">
                            @if($st->project)<i class="fas fa-folder" style="font-size:9px;margin-right:3px;color:#A5B4FC;"></i>{{ $st->project->name }} ·@endif
                            Posted {{ $st->social_posted_at->format('M d, Y') }}
                        </p>
                    </div>
                    <div style="flex-shrink:0;display:flex;align-items:center;gap:5px;flex-wrap:wrap;justify-content:flex-end;">
                        @foreach($st->socialPosts->take(3) as $sp)
                        @php [$spLabel,$spIcon,$spColor,$spBg] = $pMetaDash[$sp->platform] ?? $pMetaDash['other']; @endphp
                        <div title="{{ $spLabel }}" style="width:26px;height:26px;border-radius:8px;background:{{ $spBg }};display:flex;align-items:center;justify-content:center;border:1px solid rgba(0,0,0,.06);">
                            <i class="fab {{ $spIcon }}" style="color:{{ $spColor }};font-size:12px;"></i>
                        </div>
                        @endforeach
                        @if($st->socialPosts->count() > 3)
                        <span style="font-size:10px;color:#9CA3AF;font-weight:600;">+{{ $st->socialPosts->count() - 3 }}</span>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
            @endif

        </div>
        @endif
    </div>

</div>{{-- /x-data --}}

{{-- ═══════════════════════════════
     EDIT PROFILE MODAL
════════════════════════════════ --}}
@unless($isPreview)
<div id="profile-modal"
     style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.5);"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,.2);width:100%;max-width:460px;overflow:hidden;">

        {{-- Header --}}
        <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;">
                    <i class="fa fa-user-pen" style="color:#6366F1;font-size:14px;"></i>
                </div>
                <div>
                    <p style="font-size:15px;font-weight:700;color:#111827;margin:0;">Edit Profile</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:0;">Update your photo, email or password</p>
                </div>
            </div>
            <button onclick="document.getElementById('profile-modal').style.display='none'"
                    style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;">
                <i class="fa fa-times text-sm"></i>
            </button>
        </div>

        @if(session('profile_success'))
        <div style="margin:16px 24px 0;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:10px 14px;font-size:12px;color:#166534;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-circle-check"></i> {{ session('profile_success') }}
        </div>
        @endif

        @if($errors->any())
        <div style="margin:16px 24px 0;background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;font-size:12px;color:#DC2626;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-exclamation-circle"></i> {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data">
            @csrf
            <div style="padding:20px 24px;display:flex;flex-direction:column;gap:18px;">

                {{-- Profile Picture --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:10px;">Profile Picture</label>
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div style="position:relative;width:64px;height:64px;flex-shrink:0;" id="avatar-preview-wrap">
                            @if(auth()->user()->avatarUrl())
                            <img id="avatar-preview" src="{{ auth()->user()->avatarUrl() }}"
                                 style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #E5E7EB;">
                            @else
                            <div id="avatar-preview-initials"
                                 style="width:64px;height:64px;border-radius:50%;background:#6366F1;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;border:2px solid #E5E7EB;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            @endif
                        </div>
                        <div>
                            <label for="avatar-input"
                                   style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#EEF2FF;color:#4F46E5;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid #C7D2FE;">
                                <i class="fa fa-upload" style="font-size:10px;"></i> Choose Photo
                            </label>
                            <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display:none;"
                                   onchange="previewAvatar(this)">
                            <p style="font-size:11px;color:#9CA3AF;margin:5px 0 0;">JPG, PNG or WebP · max 2MB</p>
                        </div>
                    </div>
                </div>

                {{-- Email --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Email Address</label>
                    <div style="position:relative;">
                        <i class="fa fa-envelope" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                               placeholder="{{ auth()->user()->email }}"
                               style="width:100%;padding:10px 10px 10px 32px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                    </div>
                </div>

                {{-- New Password --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">New Password <span style="font-weight:400;color:#9CA3AF;">(leave blank to keep current)</span></label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div x-data="{show:false}" style="position:relative;">
                            <i class="fa fa-lock" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;z-index:1;"></i>
                            <input :type="show?'text':'password'" name="password" placeholder="New password"
                                   style="width:100%;padding:10px 32px 10px 32px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                            <button type="button" @click="show=!show" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;padding:0;">
                                <i :class="show?'fa fa-eye-slash':'fa fa-eye'" style="font-size:12px;"></i>
                            </button>
                        </div>
                        <div x-data="{show:false}" style="position:relative;">
                            <i class="fa fa-lock" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;z-index:1;"></i>
                            <input :type="show?'text':'password'" name="password_confirmation" placeholder="Confirm"
                                   style="width:100%;padding:10px 32px 10px 32px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                            <button type="button" @click="show=!show" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;padding:0;">
                                <i :class="show?'fa fa-eye-slash':'fa fa-eye'" style="font-size:12px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <div style="padding:16px 24px;border-top:1px solid #F3F4F6;display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" onclick="document.getElementById('profile-modal').style.display='none'"
                        style="padding:9px 20px;border-radius:10px;font-size:13px;font-weight:600;background:#F3F4F6;color:#374151;border:none;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit"
                        style="padding:9px 20px;border-radius:10px;font-size:13px;font-weight:600;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 12px rgba(79,70,229,.3);">
                    <i class="fa fa-check" style="font-size:11px;margin-right:5px;"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endunless

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const wrap = document.getElementById('avatar-preview-wrap');
            wrap.innerHTML = `<img id="avatar-preview" src="${e.target.result}" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #E5E7EB;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
@if(session('profile_success') || $errors->any())
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('profile-modal').style.display = 'flex';
});
@endif
</script>

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
    $perfData   = $perfTotal > 0 ? [$perfCompleted, $inProgress, $pendingApproval, $overdue] : [1];
    $perfColors = $perfTotal > 0 ? ['#10B981','#F59E0B','#8B5CF6','#EF4444'] : ['#E5E7EB'];
@endphp
<script>
(function () {
    var ctx = document.getElementById('perfChart');
    if (!ctx) return;
    var _perfEnabled = {{ $perfTotal > 0 ? 'true' : 'false' }};
    var _perfMap = [
        { filter:'completed',   label:'Completed',   color:'#10B981', bg:'#D1FAE5', icon:'fa-circle-check'        },
        { filter:'in_progress', label:'In Progress', color:'#F59E0B', bg:'#FEF3C7', icon:'fa-spinner'             },
        { filter:'in_review',   label:'In Review',   color:'#8B5CF6', bg:'#EDE9FE', icon:'fa-hourglass-half'      },
        { filter:'overdue',     label:'Overdue',     color:'#EF4444', bg:'#FEE2E2', icon:'fa-triangle-exclamation'},
    ];
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
                tooltip: { enabled: _perfEnabled }
            },
            animation: { duration: 500 },
            onClick: function (evt, elements) {
                if (!_perfEnabled || !elements.length) return;
                var m = _perfMap[elements[0].index];
                if (m) openUserStatsModal(m.filter, m.label, m.color, m.bg, m.icon);
            },
            onHover: function (evt, elements) {
                evt.native.target.style.cursor = (_perfEnabled && elements.length) ? 'pointer' : 'default';
            }
        }
    });
}());

@php
$weekDates = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->toDateString())->values();
@endphp
var _weekDates = @json($weekDates);
var _weekCounts = @json($weekActivity->pluck('count'));
new Chart(document.getElementById('weekChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: @json($weekActivity->pluck('label')),
        datasets: [{ data: _weekCounts,
            backgroundColor: '#818CF8', borderRadius: 5, borderSkipped: false,
            hoverBackgroundColor: '#6366F1' }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return ' ' + ctx.parsed.y + ' update' + (ctx.parsed.y !== 1 ? 's' : ''); } } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9CA3AF' } },
            y: { grid: { color: '#F3F4F6' }, ticks: { font: { size: 10 }, color: '#9CA3AF', stepSize: 1, precision: 0 }, beginAtZero: true }
        },
        animation: { duration: 600 },
        onClick: function (evt, elements) {
            if (!elements.length) return;
            var idx  = elements[0].index;
            var date = _weekDates[idx];
            var cnt  = _weekCounts[idx];
            if (!cnt) return;
            var label = @json($weekActivity->pluck('label'))[idx];
            openUserStatsModal('date', label + ' Activity', '#6366F1', '#EEF2FF', 'fa-calendar-day', date);
        },
        onHover: function (evt, elements) {
            var idx = elements.length ? elements[0].index : -1;
            evt.native.target.style.cursor = (idx >= 0 && _weekCounts[idx]) ? 'pointer' : 'default';
        }
    }
});

// Project Statistics donut
(function () {
    var ctx = document.getElementById('myProjChart');
    if (!ctx) return;
    @php
        $pActive    = $myProjectStats['active'];
        $pCompleted = $myProjectStats['completed'];
        $pOverdue   = $myProjectStats['overdue'];
        $pTotal     = $pActive + $pCompleted + $pOverdue;
        $pData      = $pTotal > 0 ? [$pActive, $pCompleted, $pOverdue] : [1];
        $pColors    = $pTotal > 0 ? ['#3B82F6','#06B6D4','#F97316'] : ['#E5E7EB'];
    @endphp
    var _projEnabled = {{ $pTotal > 0 ? 'true' : 'false' }};
    var _projMap = [
        { filter:'total',     label:'Active Tasks',    color:'#3B82F6', bg:'#EFF6FF', icon:'fa-diagram-project'       },
        { filter:'completed', label:'Completed Tasks', color:'#06B6D4', bg:'#ECFEFF', icon:'fa-circle-check'          },
        { filter:'overdue',   label:'Overdue Tasks',   color:'#F97316', bg:'#FFF7ED', icon:'fa-triangle-exclamation'  },
    ];
    new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Active','Completed','Overdue'],
            datasets: [{
                data: @json($pData),
                backgroundColor: @json($pColors),
                borderWidth: 0,
                hoverOffset: {{ $pTotal > 0 ? 4 : 0 }}
            }]
        },
        options: {
            responsive: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: _projEnabled }
            },
            animation: { duration: 500 },
            onClick: function (evt, elements) {
                if (!_projEnabled || !elements.length) return;
                var m = _projMap[elements[0].index];
                if (m) openUserStatsModal(m.filter, m.label, m.color, m.bg, m.icon);
            },
            onHover: function (evt, elements) {
                evt.native.target.style.cursor = (_projEnabled && elements.length) ? 'pointer' : 'default';
            }
        }
    });
}());

@if($errors->has('report'))
document.getElementById('report-modal').style.display = 'flex';
@endif
</script>
@endpush

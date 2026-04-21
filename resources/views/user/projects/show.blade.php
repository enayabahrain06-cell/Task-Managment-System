@extends('layouts.app')
@section('title', $project->name)

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
    <a href="{{ route('user.projects.index') }}"
       style="width:36px;height:36px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;flex-shrink:0;">
        <i class="fa fa-arrow-left" style="font-size:13px;"></i>
    </a>
    <div>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">{{ $project->name }}</h1>
        @if($project->description)
        <p style="font-size:13px;color:#9CA3AF;margin:2px 0 0;">{{ $project->description }}</p>
        @endif
    </div>
    @php $statusColors = ['active'=>['#EEF2FF','#4F46E5'],'completed'=>['#F0FDF4','#16A34A'],'overdue'=>['#FEF2F2','#DC2626']]; [$scbg,$scco] = $statusColors[$project->status] ?? ['#F3F4F6','#6B7280']; @endphp
    <span style="margin-left:auto;font-size:12px;font-weight:600;padding:5px 14px;border-radius:20px;background:{{ $scbg }};color:{{ $scco }};">{{ ucfirst($project->status) }}</span>
</div>

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;">
@foreach([['Total','fa-list-check','#EEF2FF','#4F46E5',$stats['total']],['Completed','fa-circle-check','#F0FDF4','#16A34A',$stats['completed']],['In Progress','fa-spinner','#FFFBEB','#D97706',$stats['in_progress']],['In Review','fa-hourglass-half','#F5F3FF','#7C3AED',$stats['submitted']],['Pending','fa-clock','#F8FAFC','#64748B',$stats['pending']]] as [$lbl,$ico,$bg,$ic,$val])
<div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.04);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
        <span style="font-size:10px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">{{ $lbl }}</span>
        <div style="width:28px;height:28px;border-radius:8px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;">
            <i class="fas {{ $ico }}" style="font-size:11px;color:{{ $ic }};"></i>
        </div>
    </div>
    <p style="font-size:24px;font-weight:800;color:#111827;margin:0;">{{ $val }}</p>
</div>
@endforeach
</div>

{{-- Progress bar --}}
<div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;padding:20px;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.04);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <span style="font-size:13px;font-weight:600;color:#374151;">Overall Progress</span>
        <span style="font-size:20px;font-weight:800;color:#4F46E5;">{{ $stats['rate'] }}%</span>
    </div>
    <div style="height:10px;background:#F0F0F0;border-radius:999px;overflow:hidden;">
        <div style="height:100%;width:{{ $stats['rate'] }}%;background:linear-gradient(90deg,#6366F1,#8B5CF6);border-radius:999px;transition:width .6s;"></div>
    </div>
    <p style="font-size:12px;color:#9CA3AF;margin:8px 0 0;">Due {{ $project->deadline->format('l, F j, Y') }} · {{ $project->deadline->diffForHumans() }}</p>
</div>

<div style="display:grid;grid-template-columns:1fr 260px;gap:20px;align-items:start;">

    {{-- All tasks --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.04);">
        <div style="padding:16px 20px 14px;border-bottom:1px solid #F3F4F6;">
            <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">All Tasks</h3>
            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">{{ $project->tasks->count() }} total</p>
        </div>
        @forelse($project->tasks->sortBy(fn($t)=>match($t->status){'submitted'=>0,'in_progress'=>1,'pending'=>2,'completed'=>3,default=>4}) as $task)
        @php
            $isMe = $task->assigned_to === auth()->id();
            $sMap=['completed'=>['#F0FDF4','#16A34A','Completed'],'in_progress'=>['#FFFBEB','#D97706','In Progress'],'pending'=>['#F8FAFC','#64748B','Pending'],'submitted'=>['#EDE9FE','#7C3AED','In Review']];
            [$sbg,$sco,$slbl] = $sMap[$task->status] ?? ['#F8FAFC','#9CA3AF','Unknown'];
            $pco = ['high'=>'#DC2626','medium'=>'#D97706','low'=>'#16A34A'][$task->priority] ?? '#9CA3AF';
        @endphp
        @if($isMe)
        <a href="{{ route('user.tasks.show', $task) }}" style="text-decoration:none;">
        @endif
        <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #F9FAFB;background:{{ $isMe ? '#FAFBFF' : '#fff' }};{{ $isMe ? 'cursor:pointer;' : '' }}transition:background .1s;"
             @if($isMe) onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#FAFBFF'" @endif>
            <div style="width:8px;height:8px;border-radius:50%;background:{{ $pco }};flex-shrink:0;"></div>
            <div style="flex:1;min-width:0;">
                <p style="font-size:13px;font-weight:{{ $isMe ? '700' : '500' }};color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    {{ $task->title }}
                    @if($isMe)<span style="font-size:10px;font-weight:600;background:#EEF2FF;color:#4F46E5;padding:1px 7px;border-radius:8px;margin-left:4px;">You</span>@endif
                </p>
                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $task->assignee?->name ?? 'Unassigned' }}</p>
            </div>
            <span style="font-size:10px;font-weight:600;padding:3px 9px;border-radius:999px;background:{{ $sbg }};color:{{ $sco }};flex-shrink:0;">{{ $slbl }}</span>
            <span style="font-size:11px;color:#9CA3AF;flex-shrink:0;">{{ $task->deadline->format('M d') }}</span>
        </div>
        @if($isMe)
        </a>
        @endif
        @empty
        <div style="padding:40px;text-align:center;color:#9CA3AF;font-size:13px;">No tasks yet.</div>
        @endforelse
    </div>

    {{-- Members --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.04);">
        <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:.04em;">Team Members</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($project->members as $member)
            @php
                $memberTasks    = $project->tasks->where('assigned_to', $member->id);
                $memberDone     = $memberTasks->where('status','completed')->count();
                $memberTotal    = $memberTasks->count();
                $memberRate     = $memberTotal > 0 ? round($memberDone/$memberTotal*100) : 0;
                $isCurrentUser  = $member->id === auth()->id();
            @endphp
            <div style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;background:{{ $isCurrentUser ? '#EEF2FF' : '#FAFAFA' }};">
                <div style="width:36px;height:36px;border-radius:50%;background:{{ $isCurrentUser ? 'linear-gradient(135deg,#6366F1,#4F46E5)' : 'linear-gradient(135deg,#9CA3AF,#6B7280)' }};display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">
                    {{ strtoupper(substr($member->name,0,1)) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $member->name }}@if($isCurrentUser)<span style="font-size:10px;color:#6366F1;margin-left:4px;">(you)</span>@endif
                    </p>
                    <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;">{{ $memberDone }}/{{ $memberTotal }} tasks · {{ $memberRate }}%</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection

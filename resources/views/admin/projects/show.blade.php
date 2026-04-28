@extends('layouts.app')
@section('title', $project->name)

@section('content')
@php
    $statusMap = [
        'pending'          => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Pending'],
        'in_progress'      => ['bg'=>'#FEF3C7','color'=>'#D97706','label'=>'In Progress'],
        'submitted'        => ['bg'=>'#EDE9FE','color'=>'#7C3AED','label'=>'In Review'],
        'completed'        => ['bg'=>'#D1FAE5','color'=>'#059669','label'=>'Completed'],
        'delivered'        => ['bg'=>'#ECFDF5','color'=>'#047857','label'=>'Delivered'],
    ];
    $priorityMap = ['low'=>['#D1FAE5','#059669'],'medium'=>['#FEF3C7','#D97706'],'high'=>['#FEE2E2','#DC2626']];
    $users = \App\Models\User::whereIn('role',['user','manager'])->orderBy('name')->get();
@endphp

{{-- Page header --}}
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
    <a href="{{ route('admin.projects.index') }}"
       style="width:36px;height:36px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;flex-shrink:0;">
        <i class="fa fa-arrow-left" style="font-size:13px;"></i>
    </a>
    <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">{{ $project->name }}</h1>
            @if($project->customer)
            <a href="{{ route('admin.customers.show', $project->customer) }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;background:#EEF2FF;color:#4F46E5;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;">
                <i class="fas fa-building" style="font-size:10px;"></i>
                {{ $project->customer->name }}
                @if($project->customer->company)
                <span style="color:#818CF8;">· {{ $project->customer->company }}</span>
                @endif
            </a>
            @endif
        </div>
        @if($project->description)
        <p style="font-size:13px;color:#9CA3AF;margin:4px 0 0;">{{ $project->description }}</p>
        @endif
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        @php $pStatus = ['active'=>['#D1FAE5','#059669'],'completed'=>['#F3F4F6','#6B7280'],'overdue'=>['#FEE2E2','#DC2626']][$project->status] ?? ['#F3F4F6','#6B7280']; @endphp
        <span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $pStatus[0] }};color:{{ $pStatus[1] }};">{{ ucfirst($project->status) }}</span>
        <span style="font-size:13px;color:#9CA3AF;">Due {{ $project->deadline->format('M d, Y') }}</span>
        @if($pendingApprovalCount > 0)
        <a href="{{ route('admin.approvals.index') }}"
           style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#EDE9FE;color:#7C3AED;text-decoration:none;">
            {{ $pendingApprovalCount }} pending {{ Str::plural('review', $pendingApprovalCount) }}
        </a>
        @endif
        <a href="{{ route('admin.projects.tasks.create', $project) }}"
           style="padding:8px 16px;background:#6366F1;color:#fff;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="fa fa-plus"></i> Add Task
        </a>
        <a href="{{ route('admin.projects.edit', $project) }}"
           style="padding:8px 14px;background:#F3F4F6;color:#374151;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="fa fa-pen"></i> Edit
        </a>
    </div>
</div>

@if(session('success'))
<div style="background:#D1FAE5;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#065F46;font-size:14px;display:flex;gap:10px;align-items:center;">
    <i class="fa fa-circle-check"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#FEE2E2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#991B1B;font-size:14px;display:flex;gap:10px;align-items:center;">
    <i class="fa fa-circle-exclamation"></i> {{ session('error') }}
</div>
@endif

{{-- Stats row --}}
@php
    $total    = $project->tasks->count();
    $done     = $project->tasks->whereIn('status', ['completed','delivered'])->count();
    $inReview = $project->tasks->where('status','submitted')->count();
    $active   = $project->tasks->whereIn('status', ['pending','in_progress'])->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;">
    @foreach([['Tasks','fa-list-check','#EEF2FF','#6366F1',$total],['In Progress','fa-circle-play','#FEF3C7','#D97706',$active],['In Review','fa-hourglass-half','#EDE9FE','#7C3AED',$inReview],['Completed','fa-circle-check','#D1FAE5','#059669',$done]] as [$label,$icon,$bg,$col,$val])
    <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,.04);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <div style="width:36px;height:36px;border-radius:10px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;">
                <i class="fa {{ $icon }}" style="color:{{ $col }};font-size:14px;"></i>
            </div>
            <span style="font-size:22px;font-weight:700;color:#111827;">{{ $val }}</span>
        </div>
        <p style="font-size:12px;font-weight:500;color:#9CA3AF;margin:0;">{{ $label }}</p>
    </div>
    @endforeach
</div>

{{-- Task list --}}
<div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden;">
    <div style="padding:18px 20px 14px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
        <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0;">Tasks <span style="font-size:13px;font-weight:500;color:#9CA3AF;">({{ $total }})</span></h2>
    </div>

    @forelse($project->tasks as $task)
    @php
        $s = $statusMap[$task->status] ?? $statusMap['pending'];
        [$pbg,$pco] = $priorityMap[$task->priority] ?? ['#F3F4F6','#6B7280'];
        $isOverdue = $task->deadline->isPast() && !in_array($task->status, ['completed','delivered','submitted']);
    @endphp
    <div x-data="{ reassignOpen: false }" style="border-bottom:1px solid #F9FAFB;padding:16px 20px;">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">

            {{-- Avatar --}}
            <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;">
                {{ strtoupper(substr($task->assignee->name ?? 'U', 0, 1)) }}
            </div>

            {{-- Task info --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
                    <a href="{{ route('admin.tasks.show', $task) }}"
                       style="font-size:14px;font-weight:600;color:#111827;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                       onmouseover="this.style.color='#6366F1'" onmouseout="this.style.color='#111827'">{{ $task->title }}</a>
                    <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:10px;background:{{ $s['bg'] }};color:{{ $s['color'] }};">{{ $s['label'] }}</span>
                    <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:10px;background:{{ $pbg }};color:{{ $pco }};">{{ ucfirst($task->priority) }}</span>
                    @if($isOverdue)
                    <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:10px;background:#FEE2E2;color:#DC2626;"><i class="fa fa-clock" style="margin-right:3px;"></i>Overdue</span>
                    @endif
                </div>
                <p style="font-size:12px;color:#9CA3AF;margin:0;">
                    <i class="fa fa-user" style="margin-right:4px;"></i>{{ $task->assignee->name ?? '—' }}
                    &nbsp;·&nbsp;<i class="fa fa-calendar" style="margin-right:4px;"></i>Due {{ $task->deadline->format('M d, Y') }}
                </p>
            </div>

            {{-- Actions --}}
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                {{-- View task --}}
                <a href="{{ route('admin.tasks.show', $task) }}"
                   style="padding:6px 12px;background:#F3F4F6;color:#374151;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:4px;"
                   onmouseover="this.style.background='#EEF2FF';this.style.color='#4F46E5'" onmouseout="this.style.background='#F3F4F6';this.style.color='#374151'">
                    <i class="fa fa-eye" style="font-size:11px;"></i> View
                </a>

                {{-- Deliver (completed only) --}}
                @if($task->status === 'completed')
                <form method="POST" action="{{ route('admin.tasks.deliver', $task) }}">
                    @csrf
                    <button type="submit"
                            style="padding:6px 12px;background:#D1FAE5;color:#047857;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;"
                            onmouseover="this.style.background='#A7F3D0'" onmouseout="this.style.background='#D1FAE5'">
                        <i class="fa fa-truck" style="font-size:11px;"></i> Deliver
                    </button>
                </form>
                @endif

                {{-- Reassign toggle --}}
                <button @click="reassignOpen = !reassignOpen"
                        style="padding:6px 12px;background:#F3F4F6;color:#374151;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;"
                        onmouseover="this.style.background='#FEF3C7';this.style.color='#D97706'" onmouseout="this.style.background='#F3F4F6';this.style.color='#374151'">
                    <i class="fa fa-arrows-rotate" style="font-size:11px;"></i> Reassign
                </button>
            </div>
        </div>

        {{-- Inline reassign form --}}
        <div x-show="reassignOpen" x-cloak style="margin-top:12px;padding:14px;background:#FAFAFA;border-radius:10px;border:1px solid #F3F4F6;">
            <form method="POST" action="{{ route('admin.tasks.reassign', $task) }}" style="display:flex;gap:10px;align-items:center;">
                @csrf
                <label style="font-size:12px;font-weight:600;color:#6B7280;white-space:nowrap;">Reassign to:</label>
                <select name="assigned_to" required
                        style="flex:1;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#111827;background:#fff;outline:none;">
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ $u->id == $task->assigned_to ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
                <button type="submit"
                        style="padding:8px 16px;background:#6366F1;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;">
                    Save
                </button>
                <button type="button" @click="reassignOpen = false"
                        style="padding:8px 14px;background:#F3F4F6;color:#6B7280;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
            </form>
        </div>
    </div>
    @empty
    <div style="padding:48px;text-align:center;color:#9CA3AF;">
        <div style="width:52px;height:52px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <i class="fa fa-list-check" style="color:#D1D5DB;font-size:22px;"></i>
        </div>
        <p style="font-size:14px;font-weight:500;color:#374151;margin:0 0 6px;">No tasks yet</p>
        <a href="{{ route('admin.projects.tasks.create', $project) }}"
           style="font-size:13px;color:#6366F1;text-decoration:none;font-weight:500;">Add the first task →</a>
    </div>
    @endforelse
</div>

{{-- Members --}}
@if($project->members->count())
<div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;margin-top:20px;">
    <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 14px;display:flex;align-items:center;gap:8px;">
        <i class="fa fa-users" style="color:#6366F1;"></i> Team Members
    </h2>
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
        @foreach($project->members as $member)
        <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;background:#F9FAFB;border-radius:10px;border:1px solid #F3F4F6;">
            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                {{ strtoupper(substr($member->name, 0, 1)) }}
            </div>
            <div>
                <p style="font-size:13px;font-weight:600;color:#111827;margin:0;">{{ $member->name }}</p>
                <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ ucfirst($member->role) }}</p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection

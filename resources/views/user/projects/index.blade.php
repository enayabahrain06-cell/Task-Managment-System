@extends('layouts.app')
@section('title', 'My Projects')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">My Projects</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">Projects you're a member of</p>
    </div>
</div>

@if($projects->isEmpty())
<div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;padding:60px;text-align:center;">
    <i class="fas fa-diagram-project" style="color:#D1D5DB;font-size:40px;display:block;margin-bottom:16px;"></i>
    <p style="font-size:15px;font-weight:600;color:#374151;margin:0 0 6px;">No projects yet</p>
    <p style="font-size:13px;color:#9CA3AF;margin:0;">An admin will add you to projects when they're created.</p>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px;">
    @foreach($projects as $proj)
    @php
        $total     = $proj->tasks_count;
        $done      = $proj->completed_tasks_count;
        $rate      = $total > 0 ? round($done / $total * 100) : 0;
        $statusColors = ['active'=>['#EEF2FF','#4F46E5'],'completed'=>['#F0FDF4','#16A34A'],'overdue'=>['#FEF2F2','#DC2626']];
        [$scbg,$scco] = $statusColors[$proj->status] ?? ['#F3F4F6','#6B7280'];
    @endphp
    <a href="{{ route('user.projects.show', $proj) }}"
       style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:22px;text-decoration:none;display:block;transition:box-shadow .15s;"
       onmouseover="this.style.boxShadow='0 4px 20px rgba(99,102,241,.12)'" onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.05)'">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-diagram-project" style="color:#fff;font-size:18px;"></i>
            </div>
            <span style="font-size:11px;font-weight:600;padding:4px 10px;border-radius:10px;background:{{ $scbg }};color:{{ $scco }};">{{ ucfirst($proj->status) }}</span>
        </div>
        <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->name }}</h3>
        @if($proj->description)
        <p style="font-size:12px;color:#9CA3AF;margin:0 0 14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->description }}</p>
        @else
        <p style="font-size:12px;color:#9CA3AF;margin:0 0 14px;">Due {{ $proj->deadline->format('M d, Y') }}</p>
        @endif
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="font-size:12px;color:#6B7280;">Progress</span>
                <span style="font-size:12px;font-weight:700;color:#4F46E5;">{{ $rate }}%</span>
            </div>
            <div style="height:7px;background:#F0F0F0;border-radius:999px;overflow:hidden;">
                <div style="height:100%;width:{{ $rate }}%;background:linear-gradient(90deg,#6366F1,#8B5CF6);border-radius:999px;"></div>
            </div>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:12px;color:#9CA3AF;">{{ $done }}/{{ $total }} tasks done</span>
            <span style="font-size:12px;color:#9CA3AF;">Due {{ $proj->deadline->format('M d') }}</span>
        </div>
    </a>
    @endforeach
</div>
@endif
@endsection

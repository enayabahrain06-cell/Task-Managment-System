@extends('layouts.app')

@section('title', 'Team Members')

@section('content')

<style>
*, *::before, *::after { box-sizing: border-box; }
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
@media(max-width:700px){ .stats-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:420px){ .stats-grid { grid-template-columns:1fr; } }
.stat-card { border-radius:14px; padding:18px 20px; position:relative; overflow:hidden; color:#fff; }
.stat-card-blob { position:absolute; top:-20px; right:-20px; width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.12); }
.stat-card-label { font-size:12px; font-weight:500; color:rgba(255,255,255,0.75); margin:0 0 8px; }
.stat-card-value { font-size:34px; font-weight:700; line-height:1; margin:0; }
.stat-card-sub   { font-size:11px; color:rgba(255,255,255,0.6); margin:6px 0 0; }
.stat-card-menu  { position:absolute; top:14px; right:14px; background:rgba(255,255,255,0.15); border:none; border-radius:6px; width:26px; height:26px; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#fff; font-size:11px; }
@keyframes fadeInUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }
.anim-card { animation: fadeInUp 0.45s cubic-bezier(0.22,1,0.36,1) both; }
.anim-d1 { animation-delay:0.04s; }
.anim-d2 { animation-delay:0.10s; }
.anim-d3 { animation-delay:0.16s; }
.anim-d4 { animation-delay:0.22s; }
</style>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Team Members</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $totalMembers }} total members across all teams</p>
    </div>
    @if(auth()->user()->role === 'admin')
    <a href="{{ route('admin.users.create') }}" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
        <i class="fa fa-user-plus"></i> Add Member
    </a>
    @endif
</div>

{{-- Stats --}}
<div class="stats-grid" style="margin-bottom:24px;">

    <a href="{{ route('team.index') }}" style="text-decoration:none;display:flex;">
    <div class="stat-card anim-card anim-d1" style="flex:1 1 0%;background:linear-gradient(135deg,#4F46E5,#6366F1);cursor:pointer;transition:transform 0.15s,box-shadow 0.15s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(79,70,229,.4)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''"
         onmousedown="this.style.transform='translateY(-1px)'"
         onmouseup="this.style.transform='translateY(-3px)'">
        <div class="stat-card-blob"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p class="stat-card-label">Team</p>
            <button class="stat-card-menu" onclick="event.preventDefault()"><i class="fas fa-ellipsis-h"></i></button>
        </div>
        <p class="stat-card-value">{{ $totalMembers }}</p>
        <p class="stat-card-sub">Total Members</p>
    </div>
    </a>

    <a href="{{ route('team.index') }}" style="text-decoration:none;display:flex;">
    <div class="stat-card anim-card anim-d2" style="flex:1 1 0%;background:linear-gradient(135deg,#059669,#10B981);cursor:pointer;transition:transform 0.15s,box-shadow 0.15s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(5,150,105,.4)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''"
         onmousedown="this.style.transform='translateY(-1px)'"
         onmouseup="this.style.transform='translateY(-3px)'">
        <div class="stat-card-blob"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p class="stat-card-label">Active</p>
            <button class="stat-card-menu" onclick="event.preventDefault()"><i class="fas fa-ellipsis-h"></i></button>
        </div>
        <p class="stat-card-value">{{ $activeMembers }}</p>
        <p class="stat-card-sub">Active Users</p>
    </div>
    </a>

    <a href="{{ route('team.index') }}" style="text-decoration:none;display:flex;">
    <div class="stat-card anim-card anim-d3" style="flex:1 1 0%;background:linear-gradient(135deg,#7C3AED,#8B5CF6);cursor:pointer;transition:transform 0.15s,box-shadow 0.15s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(124,58,237,.4)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''"
         onmousedown="this.style.transform='translateY(-1px)'"
         onmouseup="this.style.transform='translateY(-3px)'">
        <div class="stat-card-blob"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p class="stat-card-label">Open</p>
            <button class="stat-card-menu" onclick="event.preventDefault()"><i class="fas fa-ellipsis-h"></i></button>
        </div>
        <p class="stat-card-value">{{ $totalPending }}</p>
        <p class="stat-card-sub">Open Tasks</p>
    </div>
    </a>

    <a href="{{ route('team.index') }}" style="text-decoration:none;display:flex;">
    <div class="stat-card anim-card anim-d4" style="flex:1 1 0%;background:linear-gradient(135deg,#0E7490,#0891B2);cursor:pointer;transition:transform 0.15s,box-shadow 0.15s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(8,145,178,.4)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''"
         onmousedown="this.style.transform='translateY(-1px)'"
         onmouseup="this.style.transform='translateY(-3px)'">
        <div class="stat-card-blob"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p class="stat-card-label">Done</p>
            <button class="stat-card-menu" onclick="event.preventDefault()"><i class="fas fa-ellipsis-h"></i></button>
        </div>
        <p class="stat-card-value">{{ $totalCompleted }}</p>
        <p class="stat-card-sub">Tasks Completed</p>
    </div>
    </a>

</div>

{{-- Members Grid --}}
@php
    $roleColors = ['admin' => '#EF4444', 'manager' => '#6366F1', 'user' => '#10B981'];
    $roleBg     = ['admin' => 'bg-red-100 text-red-600', 'manager' => 'bg-indigo-100 text-indigo-600', 'user' => 'bg-emerald-100 text-emerald-700'];
    $avatarColors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4'];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @forelse($members as $member)
    @php
        $color     = $avatarColors[$loop->index % count($avatarColors)];
        $progress  = $member->total_tasks > 0 ? round(($member->completed_tasks / $member->total_tasks) * 100) : 0;
    @endphp
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md hover:border-indigo-200 transition group">
        {{-- Avatar + Name --}}
        <div class="flex flex-col items-center text-center mb-4">
            <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold mb-3 group-hover:scale-105 transition"
                 style="background: {{ $color }}">
                {{ strtoupper(substr($member->name, 0, 1)) }}
            </div>
            <h3 class="font-semibold text-gray-900 text-sm">{{ $member->name }}</h3>
            <p class="text-xs text-gray-400 mt-0.5 truncate w-full">{{ $member->email }}</p>
            <span class="mt-2 text-xs px-2.5 py-0.5 rounded-full font-medium {{ $roleBg[$member->role] ?? 'bg-gray-100 text-gray-600' }}">
                {{ ucfirst($member->role) }}
            </span>
        </div>

        {{-- Task Stats --}}
        <div class="space-y-2">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Task Progress</span>
                <span class="font-semibold text-gray-700">{{ $progress }}%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="h-1.5 rounded-full bg-indigo-500 transition-all"
                     style="width: {{ $progress }}%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 pt-1">
                <span><span class="font-semibold text-gray-700">{{ $member->completed_tasks }}</span> done</span>
                <span><span class="font-semibold text-gray-700">{{ $member->pending_tasks }}</span> pending</span>
            </div>
        </div>

        {{-- Actions (admin only) --}}
        @if(auth()->user()->role === 'admin')
        <div class="flex gap-2 mt-4">
            <a href="{{ route('admin.users.edit', $member) }}"
               class="flex-1 text-center text-xs bg-gray-100 hover:bg-indigo-100 text-gray-600 hover:text-indigo-600 py-1.5 rounded-lg transition font-medium">
                Edit
            </a>
            <form action="{{ route('admin.users.destroy', $member) }}" method="POST" class="flex-1"
                  onsubmit="return confirm('Remove {{ addslashes($member->name) }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full text-xs bg-gray-100 hover:bg-red-100 text-gray-600 hover:text-red-600 py-1.5 rounded-lg transition font-medium">
                    Remove
                </button>
            </form>
        </div>
        @endif
    </div>
    @empty
    <div class="col-span-full text-center py-16">
        <i class="fa fa-users text-5xl text-gray-200 mb-3"></i>
        <p class="text-gray-400">No team members yet</p>
    </div>
    @endforelse
</div>
@endsection

@extends('layouts.app')

@section('title', 'Team Members')

@section('content')

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
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-3xl font-bold text-indigo-600">{{ $totalMembers }}</p>
        <p class="text-xs text-gray-400 mt-1">Total Members</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-3xl font-bold text-emerald-600">{{ $activeMembers }}</p>
        <p class="text-xs text-gray-400 mt-1">Active Users</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-3xl font-bold text-amber-500">{{ $totalPending }}</p>
        <p class="text-xs text-gray-400 mt-1">Open Tasks</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-3xl font-bold text-gray-700">{{ $totalCompleted }}</p>
        <p class="text-xs text-gray-400 mt-1">Tasks Completed</p>
    </div>
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

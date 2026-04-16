@extends('layouts.app')
@section('title', 'Manage Users')

@section('content')
@php
$roleColors = ['admin'=>'bg-red-100 text-red-600','manager'=>'bg-amber-100 text-amber-700','user'=>'bg-emerald-100 text-emerald-700'];
$avatarBg   = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6'];
@endphp

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Manage Users</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $stats['total'] }} total members</p>
    </div>
    <a href="{{ route('admin.users.create') }}"
       class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
        <i class="fa fa-user-plus"></i> Add User
    </a>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
    @foreach([['label'=>'Total','value'=>$stats['total'],'icon'=>'fa-users','color'=>'#6366F1'],['label'=>'Active','value'=>$stats['active'],'icon'=>'fa-circle-check','color'=>'#10B981'],['label'=>'Inactive','value'=>$stats['inactive'],'icon'=>'fa-circle-xmark','color'=>'#EF4444'],['label'=>'Admins','value'=>$stats['admins'],'icon'=>'fa-user-shield','color'=>'#8B5CF6'],['label'=>'Managers','value'=>$stats['managers'],'icon'=>'fa-user-tie','color'=>'#F59E0B']] as $s)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3 flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background:{{ $s['color'] }}18;">
            <i class="fa {{ $s['icon'] }} text-sm" style="color:{{ $s['color'] }};"></i>
        </div>
        <div>
            <p class="text-lg font-bold text-gray-900 leading-none">{{ $s['value'] }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $s['label'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Search & Filters --}}
<form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-3 mb-4">
    <div class="relative flex-1 min-w-[200px]">
        <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email…"
               class="w-full pl-8 pr-3 py-2 text-sm bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300">
    </div>
    <select name="role" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
        <option value="">All Roles</option>
        <option value="admin"   {{ request('role')==='admin'   ? 'selected':'' }}>Admin</option>
        <option value="manager" {{ request('role')==='manager' ? 'selected':'' }}>Manager</option>
        <option value="user"    {{ request('role')==='user'    ? 'selected':'' }}>User</option>
    </select>
    <select name="status" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
        <option value="">All Status</option>
        <option value="active"   {{ request('status')==='active'   ? 'selected':'' }}>Active</option>
        <option value="inactive" {{ request('status')==='inactive' ? 'selected':'' }}>Inactive</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">Filter</button>
    @if(request()->hasAny(['search','role','status']))
    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition">Clear</a>
    @endif
</form>

{{-- Table --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50/60">
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Contact</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Status</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Tasks</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Joined</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50/60 transition">
                {{-- User --}}
                <td class="px-5 py-3.5">
                    <div class="flex items-center gap-3">
                        @if($user->avatarUrl())
                            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}"
                                 class="w-10 h-10 rounded-full object-cover flex-shrink-0 border-2 border-white shadow-sm">
                        @else
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0 shadow-sm"
                                 style="background:{{ $avatarBg[$loop->index % count($avatarBg)] }}">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                            @if($user->job_title)
                            <p class="text-xs text-gray-400">{{ $user->job_title }}</p>
                            @endif
                        </div>
                    </div>
                </td>
                {{-- Contact --}}
                <td class="px-5 py-3.5 hidden sm:table-cell">
                    <p class="text-sm text-gray-600">{{ $user->email }}</p>
                    @if($user->phone)
                    <p class="text-xs text-gray-400 mt-0.5"><i class="fa fa-phone text-xs mr-1"></i>{{ $user->phone }}</p>
                    @endif
                </td>
                {{-- Role --}}
                <td class="px-5 py-3.5">
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $roleColors[$user->role] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </td>
                {{-- Status --}}
                <td class="px-5 py-3.5 hidden md:table-cell">
                    @if($user->status === 'active')
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-emerald-100 text-emerald-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-gray-100 text-gray-500">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                    </span>
                    @endif
                </td>
                {{-- Tasks --}}
                <td class="px-5 py-3.5 hidden lg:table-cell">
                    <span class="text-sm font-medium text-gray-700">{{ $user->tasks_count }}</span>
                    <span class="text-xs text-gray-400 ml-1">tasks</span>
                </td>
                {{-- Joined --}}
                <td class="px-5 py-3.5 hidden lg:table-cell text-sm text-gray-400">
                    {{ $user->created_at->format('M d, Y') }}
                </td>
                {{-- Actions --}}
                <td class="px-5 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.users.edit', $user) }}"
                           class="w-8 h-8 flex items-center justify-center rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 transition" title="Edit">
                            <i class="fa fa-pen text-xs"></i>
                        </a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                              onsubmit="return confirm('Delete {{ addslashes($user->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 hover:bg-red-100 text-red-500 transition" title="Delete">
                                <i class="fa fa-trash text-xs"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-5 py-16 text-center">
                    <i class="fa fa-users text-5xl text-gray-200 mb-3 block"></i>
                    <p class="text-sm text-gray-400">No users found</p>
                    @if(request()->hasAny(['search','role','status']))
                    <a href="{{ route('admin.users.index') }}" class="mt-3 inline-block text-sm text-indigo-500 hover:underline">Clear filters</a>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($users->hasPages())
    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection

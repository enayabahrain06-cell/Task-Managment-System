@extends('layouts.app')

@section('title', $view === 'manage' ? 'Manage Users' : 'Team Members')

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
.perm-toggle{position:relative;width:44px;height:24px;border-radius:12px;border:2px solid #D1D5DB;background:#fff;transition:background .2s,border-color .2s;cursor:pointer;flex-shrink:0;outline:none;}
.perm-toggle.is-on{background:#6366F1;border-color:#6366F1;}
.perm-toggle-knob{position:absolute;top:2px;left:2px;width:16px;height:16px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;display:block;}
.perm-toggle.is-on .perm-toggle-knob{transform:translateX(20px);}
.all-access-toggle{position:relative;width:48px;height:26px;border-radius:13px;border:2px solid #D1D5DB;background:#fff;transition:background .2s,border-color .2s;cursor:pointer;outline:none;}
.all-access-toggle.is-on{background:#10B981;border-color:#10B981;}
.all-access-toggle-knob{position:absolute;top:2px;left:2px;width:18px;height:18px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;display:block;}
.all-access-toggle.is-on .all-access-toggle-knob{transform:translateX(22px);}
.perms-user-row{width:100%;text-align:left;padding:11px 16px;border:none;border-bottom:1px solid #F3F4F6;cursor:pointer;transition:background .12s;display:flex;align-items:center;gap:10px;background:transparent;}
.perms-user-row:hover{background:#F9FAFB;}
.perms-user-row.active{background:#EEF2FF;}
.perm-item-row{padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;transition:background .1s;}
.perm-item-row:hover{background:#FAFAFA;}
.role-perm-toggle{position:relative;width:36px;height:20px;border-radius:10px;border:2px solid #D1D5DB;background:#fff;transition:background .18s,border-color .18s;cursor:pointer;flex-shrink:0;outline:none;display:inline-block;}
.role-perm-toggle.is-on{background:#6366F1;border-color:#6366F1;}
.role-perm-toggle-knob{position:absolute;top:2px;left:2px;width:12px;height:12px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .18s;display:block;}
.role-perm-toggle.is-on .role-perm-toggle-knob{transform:translateX(16px);}
</style>

{{-- ═══════ Page Header ═══════ --}}
<div class="flex items-center justify-between mb-5">
    <div>
        @if($view === 'manage')
        <h1 class="text-2xl font-bold text-gray-900">Manage Users</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $stats['total'] ?? 0 }} total members</p>
        @else
        <h1 class="text-2xl font-bold text-gray-900">Team Members</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $totalMembers }} total members across all teams</p>
        @endif
    </div>
    @if(auth()->user()->role === 'admin')
    <button type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-add-user'))"
            class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
        <i class="fa fa-user-plus"></i> {{ $view === 'manage' ? 'Add User' : 'Add Member' }}
    </button>
    @endif
</div>

{{-- ═══════ Outer Tabs (admin only) ═══════ --}}
@if(auth()->user()->role === 'admin')
<div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit mb-6">
    <a href="{{ route('team.index') }}"
       class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition {{ $view === 'team' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
        <i class="fa fa-users text-xs"></i> Team
    </a>
    <a href="{{ route('team.index', ['view' => 'manage']) }}"
       class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition {{ $view === 'manage' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
        <i class="fa fa-user-shield text-xs"></i> Manage Users
    </a>
</div>
@endif

{{-- ══════════════════════════════════════════════════════
     TEAM VIEW
══════════════════════════════════════════════════════ --}}
@if($view === 'team')

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
            <button type="button"
                    onclick='window.dispatchEvent(new CustomEvent("open-edit-user",{detail:{{ json_encode(['id'=>$member->id,'name'=>$member->name,'email'=>$member->email,'phone'=>$member->phone??'','job_title'=>$member->job_title??'','role'=>$member->role,'status'=>$member->status,'avatar'=>$member->avatarUrl()??'']) }}}))'
                    class="flex-1 text-center text-xs bg-gray-100 hover:bg-indigo-100 text-gray-600 hover:text-indigo-600 py-1.5 rounded-lg transition font-medium">
                Edit
            </button>
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

@endif {{-- team view --}}

{{-- ══════════════════════════════════════════════════════
     MANAGE VIEW (admin only)
══════════════════════════════════════════════════════ --}}
@if($view === 'manage' && auth()->user()->role === 'admin')

@php
$roleColorMap = ['admin'=>'bg-red-100 text-red-600','manager'=>'bg-amber-100 text-amber-700','user'=>'bg-emerald-100 text-emerald-700'];
$avatarBg     = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6'];
@endphp

@if(session('success'))
<div class="mb-4 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-xl">
    <i class="fa fa-circle-check"></i> {{ session('success') }}
</div>
@endif

{{-- Inner Tabs --}}
<div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit mb-6">
    <a href="{{ route('team.index', ['view' => 'manage', 'tab' => 'users']) }}"
       class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition {{ request('tab', 'users') === 'users' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
        <i class="fa fa-users text-xs"></i> Users
    </a>
    <a href="{{ route('team.index', ['view' => 'manage', 'tab' => 'permissions']) }}"
       class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition {{ request('tab') === 'permissions' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
        <i class="fa fa-shield-halved text-xs"></i> Permissions
    </a>
    <a href="{{ route('team.index', ['view' => 'manage', 'tab' => 'roles']) }}"
       class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition {{ request('tab') === 'roles' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
        <i class="fa fa-tag text-xs"></i> Roles
        <span class="text-xs font-bold px-1.5 py-0.5 rounded-full {{ request('tab') === 'roles' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-200 text-gray-500' }}">{{ $allRoles->count() }}</span>
    </a>
</div>

{{-- ─── Users Sub-Tab ─── --}}
@if(request('tab', 'users') === 'users')

{{-- Mini stats --}}
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
<form method="GET" action="{{ route('team.index') }}" class="flex flex-wrap gap-3 mb-4">
    <input type="hidden" name="view" value="manage">
    <input type="hidden" name="tab" value="users">
    <div class="relative flex-1 min-w-[200px]">
        <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email…"
               class="w-full pl-8 pr-3 py-2 text-sm bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300">
    </div>
    <select name="role" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
        <option value="">All Roles</option>
        @foreach($allRoles as $r)
        <option value="{{ $r->name }}" {{ request('role') === $r->name ? 'selected' : '' }}>{{ $r->label }}</option>
        @endforeach
    </select>
    <select name="status" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
        <option value="">All Status</option>
        <option value="active"   {{ request('status')==='active'   ? 'selected':'' }}>Active</option>
        <option value="inactive" {{ request('status')==='inactive' ? 'selected':'' }}>Inactive</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">Filter</button>
    @if(request()->hasAny(['search','role','status']))
    <a href="{{ route('team.index', ['view' => 'manage']) }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition">Clear</a>
    @endif
</form>

{{-- Users Table --}}
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
            @forelse($users as $u)
            <tr class="hover:bg-gray-50/60 transition">
                <td class="px-5 py-3.5">
                    <div class="flex items-center gap-3">
                        @if($u->avatarUrl())
                            <img src="{{ $u->avatarUrl() }}" alt="{{ $u->name }}"
                                 class="w-10 h-10 rounded-full object-cover flex-shrink-0 border-2 border-white shadow-sm">
                        @else
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0 shadow-sm"
                                 style="background:{{ $avatarBg[$loop->index % count($avatarBg)] }}">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $u->name }}</p>
                            @if($u->job_title)
                            <p class="text-xs text-gray-400">{{ $u->job_title }}</p>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-5 py-3.5 hidden sm:table-cell">
                    <p class="text-sm text-gray-600">{{ $u->email }}</p>
                    @if($u->phone)
                    <p class="text-xs text-gray-400 mt-0.5"><i class="fa fa-phone text-xs mr-1"></i>{{ $u->phone }}</p>
                    @endif
                </td>
                <td class="px-5 py-3.5">
                    @php
                        $roleObj   = $allRoles->firstWhere('name', $u->role);
                        $roleLabel = $roleObj ? $roleObj->label : ucfirst($u->role);
                        $roleStyle = $roleObj ? "background:{$roleObj->color}18;color:{$roleObj->color};" : '';
                    @endphp
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $roleColorMap[$u->role] ?? '' }}"
                          @if(!isset($roleColorMap[$u->role])) style="{{ $roleStyle }}" @endif>
                        {{ $roleLabel }}
                    </span>
                </td>
                <td class="px-5 py-3.5 hidden md:table-cell">
                    @if($u->status === 'active')
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-emerald-100 text-emerald-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-gray-100 text-gray-500">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                    </span>
                    @endif
                </td>
                <td class="px-5 py-3.5 hidden lg:table-cell">
                    <span class="text-sm font-medium text-gray-700">{{ $u->tasks_count }}</span>
                    <span class="text-xs text-gray-400 ml-1">tasks</span>
                </td>
                <td class="px-5 py-3.5 hidden lg:table-cell text-sm text-gray-400">
                    {{ $u->created_at->format('M d, Y') }}
                </td>
                <td class="px-5 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button type="button"
                                onclick='window.dispatchEvent(new CustomEvent("open-edit-user",{detail:{{ json_encode(['id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'phone'=>$u->phone??'','job_title'=>$u->job_title??'','role'=>$u->role,'status'=>$u->status,'avatar'=>$u->avatarUrl()??'']) }}}))'
                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 transition" title="Edit">
                            <i class="fa fa-pen text-xs"></i>
                        </button>
                        <form action="{{ route('admin.users.destroy', $u) }}" method="POST"
                              onsubmit="return confirm('Delete {{ addslashes($u->name) }}?')">
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
                    <a href="{{ route('team.index', ['view' => 'manage']) }}" class="mt-3 inline-block text-sm text-indigo-500 hover:underline">Clear filters</a>
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
@endif {{-- users sub-tab --}}

{{-- ─── Permissions Sub-Tab ─── --}}
@if(request('tab') === 'permissions')
@php
    $permUsers     = \App\Models\User::whereNotIn('role', ['admin', 'manager'])->orderBy('name')->get();
    $allPerms      = \App\Models\User::ALL_PERMISSIONS;
    $allPermKeys   = array_keys($allPerms);
    $avatarBgList  = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6'];

    $usersData = $permUsers->values()->map(function($u, $i) use ($allPermKeys, $avatarBgList) {
        return [
            'id'           => $u->id,
            'name'         => $u->name,
            'email'        => $u->email,
            'job_title'    => $u->job_title ?? '',
            'avatar'       => $u->avatarUrl(),
            'initials'     => strtoupper(substr($u->name, 0, 1)),
            'color'        => $avatarBgList[$i % count($avatarBgList)],
            'unrestricted' => is_null($u->permissions),
            'perms'        => $u->permissions ?? $allPermKeys,
            'perm_count'   => is_null($u->permissions) ? count($allPermKeys) : count($u->permissions ?? []),
            'route'        => route('admin.users.permissions', $u),
        ];
    })->toArray();

    $permGroups = [
        'Tasks & Work' => [
            'icon' => 'fa-square-check', 'color' => '#6366F1', 'bg' => '#EEF2FF',
            'perms' => [
                'view_tasks'           => ['icon' => 'fa-list-check',          'label' => 'View Tasks',           'desc' => 'View & manage own tasks'],
                'submit_work'          => ['icon' => 'fa-paper-plane',         'label' => 'Submit Work',          'desc' => 'Submit deliverables for review'],
                'manage_tasks'         => ['icon' => 'fa-pen-to-square',       'label' => 'Create & Assign Tasks','desc' => 'Create, edit & assign tasks'],
                'approve_tasks'        => ['icon' => 'fa-clipboard-check',     'label' => 'Approve Submissions',  'desc' => 'Approve or reject submissions'],
                'view_activity_log'    => ['icon' => 'fa-bolt',                'label' => 'Task Activity Log',    'desc' => 'Task history & change log'],
                'view_version_history' => ['icon' => 'fa-clock-rotate-left',   'label' => 'Version History',      'desc' => 'Submitted version history'],
                'view_comments'        => ['icon' => 'fa-comments',            'label' => 'Comments & Updates',   'desc' => 'Read & write task comments'],
            ],
        ],
        'Projects & Team' => [
            'icon' => 'fa-diagram-project', 'color' => '#10B981', 'bg' => '#ECFDF5',
            'perms' => [
                'view_projects'   => ['icon' => 'fa-folder-open',      'label' => 'View Projects',          'desc' => 'Browse own projects'],
                'manage_projects' => ['icon' => 'fa-folder-plus',      'label' => 'Manage Projects',        'desc' => 'Create & manage projects'],
                'view_team_tasks' => ['icon' => 'fa-users-viewfinder', 'label' => 'View Team Tasks',        'desc' => 'View tasks of teammates'],
                'view_team'       => ['icon' => 'fa-users',            'label' => 'Team Directory',         'desc' => 'Team member directory'],
            ],
        ],
        'Communication' => [
            'icon' => 'fa-comment-dots', 'color' => '#3B82F6', 'bg' => '#EFF6FF',
            'perms' => [
                'view_messages' => ['icon' => 'fa-envelope',      'label' => 'Messages',          'desc' => 'Direct messaging'],
                'view_calendar' => ['icon' => 'fa-calendar-days', 'label' => 'Calendar',          'desc' => 'Calendar & schedule'],
            ],
        ],
        'Reports & Data' => [
            'icon' => 'fa-chart-bar', 'color' => '#F59E0B', 'bg' => '#FFFBEB',
            'perms' => [
                'view_reports'   => ['icon' => 'fa-chart-column',  'label' => 'Reports & Analytics', 'desc' => 'Reports & analytics page'],
                'export_data'    => ['icon' => 'fa-file-export',   'label' => 'Export Data',         'desc' => 'Export & download data'],
                'view_audit_log' => ['icon' => 'fa-shield-halved', 'label' => 'Audit Log',           'desc' => 'View audit log entries'],
            ],
        ],
        'Administration' => [
            'icon' => 'fa-gear', 'color' => '#EF4444', 'bg' => '#FEF2F2',
            'perms' => [
                'manage_users'    => ['icon' => 'fa-user-shield', 'label' => 'Manage Users',    'desc' => 'Create & manage users'],
                'manage_roles'    => ['icon' => 'fa-tag',         'label' => 'Manage Roles',    'desc' => 'Roles & permissions config'],
                'manage_settings' => ['icon' => 'fa-sliders',     'label' => 'System Settings', 'desc' => 'System settings & config'],
                'view_approvals'  => ['icon' => 'fa-stamp',       'label' => 'Task Approvals',  'desc' => 'Task approvals queue'],
            ],
        ],
    ];
@endphp

@if($permUsers->isEmpty())
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
    <i class="fa fa-users text-4xl text-gray-200 mb-3 block"></i>
    <p class="text-sm text-gray-400">No regular users found to manage permissions for.</p>
</div>
@else

<div x-data="permsApp()" x-init="init()" style="display:flex;gap:20px;height:calc(100vh - 280px);min-height:540px;">

    {{-- Left panel: user list --}}
    <div style="width:272px;flex-shrink:0;background:#fff;border-radius:16px;border:1px solid #E5E7EB;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="padding:14px;border-bottom:1px solid #F3F4F6;">
            <div style="position:relative;">
                <i class="fa fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:11px;pointer-events:none;"></i>
                <input x-model="search" type="text" placeholder="Search users…"
                    style="width:100%;padding:8px 10px 8px 30px;border:1px solid #E5E7EB;border-radius:8px;font-size:13px;outline:none;background:#F9FAFB;box-sizing:border-box;color:#374151;transition:border .15s,background .15s;"
                    @focus="$el.style.borderColor='#6366F1';$el.style.background='#fff'"
                    @blur="$el.style.borderColor='#E5E7EB';$el.style.background='#F9FAFB'">
            </div>
        </div>
        <div style="padding:5px 16px;background:#F9FAFB;border-bottom:1px solid #F3F4F6;">
            <span style="font-size:10.5px;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.06em;"
                x-text="`${filteredUsers.length} member${filteredUsers.length !== 1 ? 's' : ''}`"></span>
        </div>
        <div style="flex:1;overflow-y:auto;">
            <template x-for="u in filteredUsers" :key="u.id">
                <button type="button" @click="select(u)"
                    :class="selectedId === u.id ? 'perms-user-row active' : 'perms-user-row'">
                    <template x-if="u.avatar">
                        <img :src="u.avatar" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    </template>
                    <template x-if="!u.avatar">
                        <div :style="`background:${u.color}`" style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;flex-shrink:0;" x-text="u.initials"></div>
                    </template>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="u.name"></p>
                        <template x-if="u.unrestricted">
                            <span style="display:inline-flex;align-items:center;gap:3px;font-size:10px;padding:1px 7px;border-radius:20px;background:#D1FAE5;color:#059669;font-weight:700;margin-top:2px;">
                                <i class="fa fa-infinity" style="font-size:8px;"></i> Full Access
                            </span>
                        </template>
                        <template x-if="!u.unrestricted">
                            <p style="font-size:11px;color:#9CA3AF;margin-top:2px;" x-text="`${u.perm_count} / ${allKeys.length} enabled`"></p>
                        </template>
                    </div>
                    <i x-show="selectedId === u.id" class="fa fa-chevron-right" style="color:#6366F1;font-size:10px;flex-shrink:0;"></i>
                </button>
            </template>
            <template x-if="filteredUsers.length === 0">
                <div style="padding:36px 16px;text-align:center;">
                    <i class="fa fa-magnifying-glass" style="font-size:24px;color:#E5E7EB;display:block;margin-bottom:8px;"></i>
                    <p style="font-size:13px;color:#9CA3AF;">No users match</p>
                </div>
            </template>
        </div>
    </div>

    {{-- Right panel: permission editor --}}
    <div style="flex:1;min-width:0;overflow-y:auto;display:flex;flex-direction:column;gap:14px;">

        <template x-if="!activeUser">
            <div style="flex:1;background:#fff;border-radius:16px;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:10px;min-height:300px;">
                <div style="width:56px;height:56px;border-radius:14px;background:#F5F3FF;display:flex;align-items:center;justify-content:center;">
                    <i class="fa fa-shield-halved" style="font-size:22px;color:#8B5CF6;"></i>
                </div>
                <p style="font-size:14px;font-weight:600;color:#374151;">Select a user</p>
                <p style="font-size:13px;color:#9CA3AF;">Choose a member from the list to manage their access</p>
            </div>
        </template>

        <template x-if="activeUser">
            <div style="display:flex;flex-direction:column;gap:14px;">

                {{-- User header --}}
                <div style="background:#fff;border-radius:16px;border:1px solid #E5E7EB;padding:18px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;box-shadow:0 1px 4px rgba(0,0,0,.04);">
                    <div style="display:flex;align-items:center;gap:13px;">
                        <template x-if="activeUser.avatar">
                            <img :src="activeUser.avatar" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid #E5E7EB;flex-shrink:0;">
                        </template>
                        <template x-if="!activeUser.avatar">
                            <div :style="`background:${activeUser.color}`" style="width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;font-weight:700;flex-shrink:0;border:2px solid #E5E7EB;" x-text="activeUser.initials"></div>
                        </template>
                        <div>
                            <p style="font-size:15px;font-weight:700;color:#111827;" x-text="activeUser.name"></p>
                            <p style="font-size:12px;color:#9CA3AF;margin-top:2px;" x-text="activeUser.email"></p>
                        </div>
                    </div>
                    <div style="flex-shrink:0;">
                        <template x-if="saving">
                            <span style="font-size:12px;color:#9CA3AF;display:flex;align-items:center;gap:5px;">
                                <i class="fa fa-spinner fa-spin" style="color:#6366F1;"></i> Saving…
                            </span>
                        </template>
                        <template x-if="saved && !saving">
                            <span style="font-size:12px;color:#10B981;display:flex;align-items:center;gap:5px;">
                                <i class="fa fa-circle-check"></i> Saved
                            </span>
                        </template>
                        <template x-if="!saving && !saved">
                            <span style="font-size:12px;color:#D1D5DB;display:flex;align-items:center;gap:5px;">
                                <i class="fa fa-bolt" style="color:#FCD34D;"></i> Auto-save on
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Full Access toggle --}}
                <div style="background:#fff;border-radius:16px;border:1px solid #E5E7EB;padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,.04);">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:#ECFDF5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa fa-infinity" style="color:#10B981;font-size:15px;"></i>
                            </div>
                            <div>
                                <p style="font-size:14px;font-weight:600;color:#111827;">Full Access</p>
                                <p style="font-size:12px;color:#9CA3AF;margin-top:1px;">Grant unrestricted access to everything</p>
                            </div>
                        </div>
                        <button type="button" @click="setAll(!activeUser.unrestricted)"
                            :class="activeUser.unrestricted ? 'all-access-toggle is-on' : 'all-access-toggle'">
                            <span class="all-access-toggle-knob"></span>
                        </button>
                    </div>
                    <div x-show="activeUser.unrestricted"
                         style="margin-top:12px;padding:10px 14px;background:#ECFDF5;border-radius:8px;border:1px solid #A7F3D0;">
                        <p style="font-size:12px;color:#059669;">
                            <i class="fa fa-circle-check" style="margin-right:5px;"></i>
                            This user has full unrestricted access to all features and pages.
                        </p>
                    </div>
                </div>

                {{-- Permission groups --}}
                @foreach($permGroups as $groupName => $group)
                @php $groupKeys = array_keys($group['perms']); @endphp
                <div style="background:#fff;border-radius:16px;border:1px solid #E5E7EB;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.04);"
                     :style="activeUser.unrestricted ? 'opacity:0.5;pointer-events:none;' : ''">
                    <div style="padding:12px 20px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:10px;background:#FAFAFA;">
                        <div style="width:28px;height:28px;border-radius:7px;background:{{ $group['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa {{ $group['icon'] }}" style="font-size:12px;color:{{ $group['color'] }};"></i>
                        </div>
                        <span style="font-size:13px;font-weight:600;color:#374151;flex:1;">{{ $groupName }}</span>
                        <span style="font-size:11px;color:#9CA3AF;background:#F3F4F6;padding:2px 8px;border-radius:20px;"
                            x-text="`${[{{ implode(',', array_map(fn($k) => "'{$k}'", $groupKeys)) }}].filter(k => hasPermission(k)).length} / {{ count($group['perms']) }}`"></span>
                    </div>
                    @foreach($group['perms'] as $key => $perm)
                    <div class="perm-item-row" style="{{ !$loop->last ? 'border-bottom:1px solid #F9FAFB;' : '' }}">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:34px;height:34px;border-radius:8px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa {{ $perm['icon'] }}" style="font-size:13px;color:#6B7280;"></i>
                            </div>
                            <div>
                                <p style="font-size:13px;font-weight:600;color:#374151;">{{ $perm['label'] }}</p>
                                <p style="font-size:11px;color:#9CA3AF;margin-top:1px;">{{ $perm['desc'] }}</p>
                            </div>
                        </div>
                        <button type="button"
                            @click="toggle('{{ $key }}')"
                            :class="hasPermission('{{ $key }}') ? 'perm-toggle is-on' : 'perm-toggle'">
                            <span class="perm-toggle-knob"></span>
                        </button>
                    </div>
                    @endforeach
                </div>
                @endforeach

            </div>
        </template>

    </div>
</div>

<script>
function permsApp() {
    return {
        users:      @json(array_values($usersData)),
        allKeys:    @json($allPermKeys),
        selectedId: null,
        search:     '',
        saving:     false,
        saved:      false,

        init() {
            if (this.users.length) this.select(this.users[0]);
        },

        get filteredUsers() {
            const q = this.search.toLowerCase().trim();
            if (!q) return this.users;
            return this.users.filter(u =>
                u.name.toLowerCase().includes(q) ||
                u.email.toLowerCase().includes(q)
            );
        },

        get activeUser() {
            return this.users.find(u => u.id === this.selectedId) || null;
        },

        select(u) {
            this.selectedId = u.id;
            this.saved = false;
        },

        hasPermission(key) {
            const u = this.activeUser;
            if (!u) return false;
            return u.unrestricted || u.perms.includes(key);
        },

        toggle(key) {
            const u = this.activeUser;
            if (!u || u.unrestricted) return;
            const idx = u.perms.indexOf(key);
            if (idx >= 0) u.perms.splice(idx, 1);
            else u.perms.push(key);
            u.perm_count = u.perms.length;
            this.save();
        },

        setAll(val) {
            const u = this.activeUser;
            if (!u) return;
            u.unrestricted = val;
            if (val) { u.perms = [...this.allKeys]; u.perm_count = this.allKeys.length; }
            this.save();
        },

        async save() {
            const u = this.activeUser;
            if (!u) return;
            this.saving = true;
            this.saved  = false;
            try {
                await fetch(u.route, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type':  'application/json',
                        'X-CSRF-TOKEN':  document.querySelector('meta[name=csrf-token]').content,
                        'Accept':        'application/json',
                    },
                    body: JSON.stringify({ unrestricted: u.unrestricted, permissions: u.perms }),
                });
            } catch(e) {}
            this.saving = false;
            this.saved  = true;
            setTimeout(() => this.saved = false, 2000);
        },
    };
}
</script>
@endif {{-- permUsers check --}}

@endif {{-- permissions sub-tab --}}

{{-- ─── Roles Sub-Tab ─── --}}
@if(request('tab') === 'roles')

@if(session('role_success'))
<div class="mb-4 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-xl">
    <i class="fa fa-circle-check"></i> {{ session('role_success') }}
</div>
@endif
@if(session('role_error'))
<div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-600 text-sm px-4 py-3 rounded-xl">
    <i class="fa fa-circle-exclamation"></i> {{ session('role_error') }}
</div>
@endif

<div x-data="rolesTab()" x-init="init()">

    <div class="flex items-center justify-between mb-5">
        <p class="text-sm text-gray-500">{{ $allRoles->count() }} roles defined &mdash; <span class="text-indigo-600 font-medium">{{ $allRoles->where('is_system', false)->count() }} custom</span></p>
        <button @click="openCreate()"
                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition shadow-sm">
            <i class="fa fa-plus text-xs"></i> New Role
        </button>
    </div>

    {{-- Role Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
        @foreach($allRoles as $role)
        @php $userCount = \App\Models\User::where('role', $role->name)->count(); @endphp
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col transition hover:shadow-md">
            <div class="h-1.5 w-full" style="background:{{ $role->color }};"></div>
            <div class="p-5 flex flex-col gap-3 flex-1">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                             style="background:{{ $role->color }}18;">
                            <i class="fa {{ $role->is_system ? 'fa-shield-halved' : 'fa-tag' }} text-sm"
                               style="color:{{ $role->color }};"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ $role->label }}</p>
                            <span class="text-xs font-mono text-gray-400">{{ $role->name }}</span>
                        </div>
                    </div>
                    @if($role->is_system)
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">System</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 leading-relaxed flex-1 min-h-[36px]">
                    {{ $role->description ?: 'No description.' }}
                </p>
                @php
                    $totalPerms = count(\App\Models\User::ALL_PERMISSIONS);
                    $rolePermsCount = is_null($role->permissions) ? $totalPerms : count($role->permissions);
                    $isFullAccess = is_null($role->permissions);
                @endphp
                <div class="flex items-center gap-1.5 text-xs py-1.5">
                    @if(in_array($role->name, ['admin','manager']))
                        <i class="fa fa-shield-halved text-purple-400 text-xs"></i>
                        <span class="text-gray-400">Bypasses all permission checks</span>
                    @elseif($isFullAccess)
                        <i class="fa fa-infinity text-emerald-500 text-xs"></i>
                        <span class="font-semibold text-emerald-600">Full Access</span>
                    @else
                        <i class="fa fa-lock text-amber-400 text-xs"></i>
                        <span class="text-gray-500"><strong class="text-gray-700">{{ $rolePermsCount }}</strong> / {{ $totalPerms }} permissions</span>
                    @endif
                </div>
                <div class="flex items-center gap-2 pt-2 border-t border-gray-50">
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <i class="fa fa-users text-gray-400 text-xs"></i>
                        <span><strong class="text-gray-700">{{ $userCount }}</strong> {{ Str::plural('user', $userCount) }}</span>
                    </div>
                    @if($userCount > 0)
                    <a href="{{ route('team.index', ['view' => 'manage', 'tab' => 'users', 'role' => $role->name]) }}"
                       class="ml-auto text-xs text-indigo-500 hover:text-indigo-700 font-medium">View →</a>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button @click="openEdit({{ $role->id }}, '{{ addslashes($role->label) }}', '{{ $role->color }}', '{{ addslashes($role->description ?? '') }}', {{ json_encode(is_null($role->permissions)) }}, {{ json_encode($role->permissions ?? []) }})"
                            class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-600 transition">
                        <i class="fa fa-pen text-xs"></i> Edit
                    </button>
                    @if(!$role->is_system)
                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST"
                          onsubmit="return confirm('Delete role \'{{ addslashes($role->label) }}\'? Users with this role will be moved to User.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-xs bg-red-50 hover:bg-red-100 text-red-500 transition">
                            <i class="fa fa-trash"></i>
                        </button>
                    </form>
                    @else
                    <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-50 text-gray-300 cursor-not-allowed" title="System roles cannot be deleted">
                        <i class="fa fa-lock text-xs"></i>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Create/Edit Modal --}}
    <div x-show="modalOpen" x-cloak style="position:fixed;inset:0;z-index:9999;">
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;">
            <div @click="modalOpen=false" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);-webkit-backdrop-filter:blur(3px);backdrop-filter:blur(3px);"></div>
            <div style="position:relative;width:100%;max-width:520px;background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,0.2);display:flex;flex-direction:column;max-height:calc(100vh - 40px);">
                <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;">
                            <i class="fa fa-tag" style="color:#6366F1;font-size:15px;"></i>
                        </div>
                        <div>
                            <h2 style="font-size:16px;font-weight:700;color:#111827;margin:0;" x-text="editId ? 'Edit Role' : 'New Role'"></h2>
                            <p style="font-size:11px;color:#9CA3AF;margin:0;" x-text="editId ? 'Update label, color, description or permissions' : 'Define a new custom role with permissions'"></p>
                        </div>
                    </div>
                    <button @click="modalOpen=false"
                            style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;">
                        <i class="fa fa-times text-sm"></i>
                    </button>
                </div>
                <div style="overflow-y:auto;flex:1;min-height:0;">

                @php
                    $modalPermGroups = [
                        'Tasks & Work' => ['icon' => 'fa-square-check', 'color' => '#6366F1', 'bg' => '#EEF2FF', 'perms' => ['view_tasks' => ['icon' => 'fa-list-check', 'desc' => 'View & manage own tasks'], 'submit_work' => ['icon' => 'fa-paper-plane', 'desc' => 'Submit deliverables for review'], 'manage_tasks' => ['icon' => 'fa-pen-to-square', 'desc' => 'Create, edit & assign tasks'], 'approve_tasks' => ['icon' => 'fa-clipboard-check', 'desc' => 'Approve or reject submissions'], 'view_activity_log' => ['icon' => 'fa-bolt', 'desc' => 'Task history & change log'], 'view_version_history' => ['icon' => 'fa-clock-rotate-left', 'desc' => 'Submitted version history'], 'view_comments' => ['icon' => 'fa-comments', 'desc' => 'Read & write task comments']]],
                        'Projects & Team' => ['icon' => 'fa-diagram-project', 'color' => '#10B981', 'bg' => '#ECFDF5', 'perms' => ['view_projects' => ['icon' => 'fa-folder-open', 'desc' => 'Browse own projects'], 'manage_projects' => ['icon' => 'fa-folder-plus', 'desc' => 'Create & manage projects'], 'view_team_tasks' => ['icon' => 'fa-users-viewfinder', 'desc' => 'View tasks of teammates'], 'view_team' => ['icon' => 'fa-users', 'desc' => 'Team member directory']]],
                        'Communication' => ['icon' => 'fa-comment-dots', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'perms' => ['view_messages' => ['icon' => 'fa-envelope', 'desc' => 'Direct messaging'], 'view_calendar' => ['icon' => 'fa-calendar-days', 'desc' => 'Calendar & schedule']]],
                        'Reports & Data' => ['icon' => 'fa-chart-bar', 'color' => '#F59E0B', 'bg' => '#FFFBEB', 'perms' => ['view_reports' => ['icon' => 'fa-chart-column', 'desc' => 'Reports & analytics page'], 'export_data' => ['icon' => 'fa-file-export', 'desc' => 'Export & download data'], 'view_audit_log' => ['icon' => 'fa-shield-halved', 'desc' => 'View audit log entries']]],
                        'Administration' => ['icon' => 'fa-gear', 'color' => '#EF4444', 'bg' => '#FEF2F2', 'perms' => ['manage_users' => ['icon' => 'fa-user-shield', 'desc' => 'Create & manage users'], 'manage_roles' => ['icon' => 'fa-tag', 'desc' => 'Manage roles & permissions'], 'manage_settings' => ['icon' => 'fa-sliders', 'desc' => 'System settings & config'], 'view_approvals' => ['icon' => 'fa-stamp', 'desc' => 'Task approvals queue']]],
                    ];
                @endphp

                {{-- Create form --}}
                <template x-if="!editId">
                    <form method="POST" action="{{ route('admin.roles.store') }}" style="padding:20px 24px 24px;">
                        @csrf
                        <div style="margin-bottom:14px;">
                            <label class="form-label">Role Name <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="label" x-model="form.label" class="form-input"
                                   placeholder="e.g. Designer, Developer, QA Tester" required maxlength="80">
                            <p style="font-size:11px;color:#9CA3AF;margin:4px 0 0;">A slug will be auto-generated from the name.</p>
                        </div>
                        <div style="margin-bottom:14px;">
                            <label class="form-label">Badge Color <span style="color:#EF4444;">*</span></label>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                @foreach(['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4','#F97316','#14B8A6'] as $pc)
                                <button type="button" @click="form.color='{{ $pc }}'"
                                        :style="form.color === '{{ $pc }}' ? 'width:28px;height:28px;border-radius:50%;background:{{ $pc }};border:3px solid #111827;flex-shrink:0;cursor:pointer;' : 'width:26px;height:26px;border-radius:50%;background:{{ $pc }};border:2px solid transparent;flex-shrink:0;cursor:pointer;'">
                                </button>
                                @endforeach
                                <input type="color" x-model="form.color" style="width:36px;height:28px;border:1.5px solid #E5E7EB;border-radius:6px;padding:2px;cursor:pointer;background:#fff;">
                                <input type="hidden" name="color" :value="form.color">
                            </div>
                            <div style="margin-top:8px;display:flex;align-items:center;gap:6px;">
                                <span style="font-size:12px;color:#6B7280;">Preview:</span>
                                <span :style="`font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;background:${form.color}18;color:${form.color};`"
                                      x-text="form.label || 'Role Name'"></span>
                            </div>
                        </div>
                        <div style="margin-bottom:14px;">
                            <label class="form-label">Description <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— optional</span></label>
                            <textarea name="description" x-model="form.description" class="form-input" rows="2"
                                      placeholder="What does this role do?" maxlength="200" style="resize:none;"></textarea>
                        </div>
                        <div style="margin-bottom:20px;">
                            <label class="form-label" style="margin-bottom:8px;">Default Permissions</label>
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;margin-bottom:10px;background:#F9FAFB;cursor:pointer;"
                                 @click="form.unrestricted = !form.unrestricted">
                                <div style="display:flex;align-items:center;gap:8px;pointer-events:none;">
                                    <i class="fa fa-infinity" style="color:#10B981;font-size:13px;"></i>
                                    <span style="font-size:13px;font-weight:600;color:#374151;">Full Access</span>
                                    <span style="font-size:11px;color:#9CA3AF;margin-left:5px;">— no restrictions for this role</span>
                                </div>
                                <button type="button" :class="form.unrestricted ? 'role-perm-toggle is-on' : 'role-perm-toggle'" style="pointer-events:none;flex-shrink:0;">
                                    <span class="role-perm-toggle-knob"></span>
                                </button>
                            </div>
                            <div x-show="!form.unrestricted" style="border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;">
                                @foreach($modalPermGroups as $grpName => $grp)
                                <div style="{{ !$loop->first ? 'border-top:1px solid #F3F4F6;' : '' }}">
                                    <div style="padding:8px 14px;background:{{ $grp['bg'] }};display:flex;align-items:center;gap:7px;">
                                        <i class="fa {{ $grp['icon'] }}" style="font-size:11px;color:{{ $grp['color'] }};"></i>
                                        <span style="font-size:11px;font-weight:700;color:{{ $grp['color'] }};text-transform:uppercase;letter-spacing:.05em;">{{ $grpName }}</span>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">
                                        @foreach($grp['perms'] as $pk => $pinfo)
                                        <label style="display:flex;align-items:center;gap:8px;padding:9px 12px;cursor:pointer;border-top:1px solid #F9FAFB;{{ $loop->index % 2 === 1 ? 'border-left:1px solid #F9FAFB;' : '' }}transition:background .1s;"
                                               onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                                            <button type="button" @click.prevent="togglePerm('{{ $pk }}')"
                                                    :class="hasPerm('{{ $pk }}') ? 'role-perm-toggle is-on' : 'role-perm-toggle'"
                                                    style="flex-shrink:0;">
                                                <span class="role-perm-toggle-knob"></span>
                                            </button>
                                            <div style="min-width:0;">
                                                <div style="display:flex;align-items:center;gap:4px;">
                                                    <i class="fa {{ $pinfo['icon'] }}" style="font-size:10px;color:#9CA3AF;flex-shrink:0;"></i>
                                                    <span style="font-size:11.5px;color:#374151;font-weight:600;line-height:1.2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ \App\Models\User::ALL_PERMISSIONS[$pk] }}</span>
                                                </div>
                                                <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $pinfo['desc'] }}</p>
                                            </div>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="unrestricted" :value="form.unrestricted ? '1' : '0'">
                        <input type="hidden" name="permissions_json" :value="JSON.stringify(form.unrestricted ? [] : form.permissions)">
                        <button type="submit"
                                style="width:100%;padding:11px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 12px rgba(99,102,241,.3);">
                            <i class="fa fa-plus"></i> Create Role
                        </button>
                    </form>
                </template>

                {{-- Edit form --}}
                <template x-if="editId">
                    <form method="POST" :action="`{{ url('admin/roles') }}/${editId}`" style="padding:20px 24px 24px;">
                        @csrf @method('PUT')
                        <div style="margin-bottom:14px;">
                            <label class="form-label">Role Name <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="label" x-model="form.label" class="form-input" required maxlength="80">
                        </div>
                        <div style="margin-bottom:14px;">
                            <label class="form-label">Badge Color <span style="color:#EF4444;">*</span></label>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                @foreach(['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4','#F97316','#14B8A6'] as $pc)
                                <button type="button" @click="form.color='{{ $pc }}'"
                                        :style="form.color === '{{ $pc }}' ? 'width:28px;height:28px;border-radius:50%;background:{{ $pc }};border:3px solid #111827;flex-shrink:0;cursor:pointer;' : 'width:26px;height:26px;border-radius:50%;background:{{ $pc }};border:2px solid transparent;flex-shrink:0;cursor:pointer;'">
                                </button>
                                @endforeach
                                <input type="color" x-model="form.color" style="width:36px;height:28px;border:1.5px solid #E5E7EB;border-radius:6px;padding:2px;cursor:pointer;background:#fff;">
                                <input type="hidden" name="color" :value="form.color">
                            </div>
                            <div style="margin-top:8px;display:flex;align-items:center;gap:6px;">
                                <span style="font-size:12px;color:#6B7280;">Preview:</span>
                                <span :style="`font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;background:${form.color}18;color:${form.color};`"
                                      x-text="form.label || 'Role Name'"></span>
                            </div>
                        </div>
                        <div style="margin-bottom:14px;">
                            <label class="form-label">Description</label>
                            <textarea name="description" x-model="form.description" class="form-input" rows="2"
                                      maxlength="200" style="resize:none;"></textarea>
                        </div>
                        <div style="margin-bottom:20px;">
                            <label class="form-label" style="margin-bottom:8px;">Default Permissions</label>
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;margin-bottom:10px;background:#F9FAFB;cursor:pointer;"
                                 @click="form.unrestricted = !form.unrestricted">
                                <div style="display:flex;align-items:center;gap:8px;pointer-events:none;">
                                    <i class="fa fa-infinity" style="color:#10B981;font-size:13px;"></i>
                                    <span style="font-size:13px;font-weight:600;color:#374151;">Full Access</span>
                                    <span style="font-size:11px;color:#9CA3AF;margin-left:5px;">— no restrictions for this role</span>
                                </div>
                                <button type="button" :class="form.unrestricted ? 'role-perm-toggle is-on' : 'role-perm-toggle'" style="pointer-events:none;flex-shrink:0;">
                                    <span class="role-perm-toggle-knob"></span>
                                </button>
                            </div>
                            <div x-show="!form.unrestricted" style="border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;">
                                @foreach($modalPermGroups as $grpName => $grp)
                                <div style="{{ !$loop->first ? 'border-top:1px solid #F3F4F6;' : '' }}">
                                    <div style="padding:8px 14px;background:{{ $grp['bg'] }};display:flex;align-items:center;gap:7px;">
                                        <i class="fa {{ $grp['icon'] }}" style="font-size:11px;color:{{ $grp['color'] }};"></i>
                                        <span style="font-size:11px;font-weight:700;color:{{ $grp['color'] }};text-transform:uppercase;letter-spacing:.05em;">{{ $grpName }}</span>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">
                                        @foreach($grp['perms'] as $pk => $pinfo)
                                        <label style="display:flex;align-items:center;gap:8px;padding:9px 12px;cursor:pointer;border-top:1px solid #F9FAFB;{{ $loop->index % 2 === 1 ? 'border-left:1px solid #F9FAFB;' : '' }}transition:background .1s;"
                                               onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                                            <button type="button" @click.prevent="togglePerm('{{ $pk }}')"
                                                    :class="hasPerm('{{ $pk }}') ? 'role-perm-toggle is-on' : 'role-perm-toggle'"
                                                    style="flex-shrink:0;">
                                                <span class="role-perm-toggle-knob"></span>
                                            </button>
                                            <div style="min-width:0;">
                                                <div style="display:flex;align-items:center;gap:4px;">
                                                    <i class="fa {{ $pinfo['icon'] }}" style="font-size:10px;color:#9CA3AF;flex-shrink:0;"></i>
                                                    <span style="font-size:11.5px;color:#374151;font-weight:600;line-height:1.2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ \App\Models\User::ALL_PERMISSIONS[$pk] }}</span>
                                                </div>
                                                <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $pinfo['desc'] }}</p>
                                            </div>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="unrestricted" :value="form.unrestricted ? '1' : '0'">
                        <input type="hidden" name="permissions_json" :value="JSON.stringify(form.unrestricted ? [] : form.permissions)">
                        <button type="submit"
                                style="width:100%;padding:11px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 12px rgba(99,102,241,.3);">
                            <i class="fa fa-check"></i> Save Changes
                        </button>
                    </form>
                </template>

                </div>
            </div>
        </div>
    </div>

</div>

<script>
function rolesTab() {
    return {
        modalOpen: false,
        editId:    null,
        form: { label: '', color: '#6366F1', description: '', unrestricted: true, permissions: [] },

        init() {},

        openCreate() {
            this.editId = null;
            this.form   = { label: '', color: '#6366F1', description: '', unrestricted: true, permissions: [] };
            this.modalOpen = true;
        },

        openEdit(id, label, color, description, unrestricted, permissions) {
            this.editId = id;
            this.form   = { label, color, description, unrestricted: !!unrestricted, permissions: permissions || [] };
            this.modalOpen = true;
        },

        togglePerm(key) {
            if (this.form.unrestricted) return;
            const idx = this.form.permissions.indexOf(key);
            if (idx >= 0) this.form.permissions.splice(idx, 1);
            else this.form.permissions.push(key);
        },

        hasPerm(key) {
            return this.form.unrestricted || this.form.permissions.includes(key);
        },
    };
}
</script>
@endif {{-- roles sub-tab --}}

@endif {{-- manage view --}}

{{-- ══════════════════════════════════════════════════════
     EDIT USER MODAL
══════════════════════════════════════════════════════ --}}
<div x-data="editUserModal()"
     x-on:open-edit-user.window="open($event.detail)"
     x-cloak
     :style="show ? 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;' : 'display:none;'">

    {{-- Backdrop --}}
    <div @click="close()" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);"></div>

    {{-- Panel --}}
    <div style="position:relative;width:100%;max-width:600px;background:#fff;border-radius:20px;box-shadow:0 32px 80px rgba(0,0,0,0.22);display:flex;flex-direction:column;max-height:calc(100vh - 32px);overflow:hidden;">

        {{-- Header --}}
        <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="position:relative;flex-shrink:0;">
                    <template x-if="avatarPreview">
                        <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;border:2px solid #E5E7EB;">
                            <img :src="avatarPreview" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                    </template>
                    <template x-if="!avatarPreview">
                        <div style="width:44px;height:44px;border-radius:50%;background:#6366F1;display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;font-weight:700;border:2px solid #E5E7EB;">
                            <span x-text="name ? name[0].toUpperCase() : '?'"></span>
                        </div>
                    </template>
                </div>
                <div>
                    <h2 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Edit User</h2>
                    <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;" x-text="name"></p>
                </div>
            </div>
            <button @click="close()" style="width:32px;height:32px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;flex-shrink:0;">
                <i class="fa fa-times"></i>
            </button>
        </div>

        {{-- Scrollable body --}}
        <div style="overflow-y:auto;flex:1;padding:20px 24px;">
            <form id="edit-user-form" method="POST" :action="'/admin/users/' + userId"
                  enctype="multipart/form-data" @submit="saving = true">
                @csrf
                @method('PUT')

                {{-- Avatar upload --}}
                <div style="display:flex;align-items:center;gap:16px;padding:16px;background:#F9FAFB;border-radius:12px;border:1px solid #F3F4F6;margin-bottom:16px;">
                    <div style="position:relative;flex-shrink:0;">
                        <template x-if="avatarPreview">
                            <div style="width:64px;height:64px;border-radius:50%;overflow:hidden;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.12);">
                                <img :src="avatarPreview" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        </template>
                        <template x-if="!avatarPreview">
                            <div style="width:64px;height:64px;border-radius:50%;background:#6366F1;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.12);">
                                <span x-text="name ? name[0].toUpperCase() : '?'"></span>
                            </div>
                        </template>
                        <label style="position:absolute;bottom:-2px;right:-2px;width:24px;height:24px;background:#6366F1;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid #fff;">
                            <i class="fa fa-camera" style="color:#fff;font-size:9px;"></i>
                            <input type="file" name="avatar" accept="image/*" class="hidden"
                                   x-on:change="avatarPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : avatarPreview">
                        </label>
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#374151;margin:0;">Profile Photo</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">JPG, PNG or WebP · Max 2MB. Leave blank to keep current.</p>
                    </div>
                </div>

                {{-- Basic Info grid --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">

                    <div style="grid-column:span 2;">
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Full Name <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="name" x-model="name" required maxlength="255"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                    </div>

                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Email Address <span style="color:#EF4444;">*</span></label>
                        <input type="email" name="email" x-model="email" required maxlength="255"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                    </div>

                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Phone Number</label>
                        <input type="tel" name="phone" x-model="phone" maxlength="30"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                    </div>

                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Job Title</label>
                        <input type="text" name="job_title" x-model="jobTitle" maxlength="80"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                    </div>

                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Role <span style="color:#EF4444;">*</span></label>
                        <select name="role" x-model="role" required
                                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;cursor:pointer;">
                            @foreach($allRoles as $r)
                            <option value="{{ $r->name }}">{{ $r->label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Status</label>
                        <select name="status" x-model="status"
                                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;cursor:pointer;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                </div>

                {{-- Password section --}}
                <div style="border:1.5px solid #F3F4F6;border-radius:12px;overflow:hidden;margin-bottom:20px;">
                    <div style="padding:10px 14px;background:#F9FAFB;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:7px;">
                        <i class="fa fa-lock" style="font-size:11px;color:#9CA3AF;"></i>
                        <span style="font-size:12px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Change Password</span>
                        <span style="font-size:11px;color:#9CA3AF;font-weight:400;text-transform:none;letter-spacing:0;">— leave blank to keep current</span>
                    </div>
                    <div style="padding:14px;display:grid;grid-template-columns:1fr 1fr;gap:12px;" x-data="{s1:false,s2:false}">
                        <div style="position:relative;">
                            <input :type="s1?'text':'password'" name="password" placeholder="New password…"
                                   style="width:100%;padding:9px 36px 9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                            <button type="button" x-on:click="s1=!s1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;">
                                <i :class="s1?'fa fa-eye-slash':'fa fa-eye'" style="font-size:13px;"></i>
                            </button>
                        </div>
                        <div style="position:relative;">
                            <input :type="s2?'text':'password'" name="password_confirmation" placeholder="Confirm password"
                                   style="width:100%;padding:9px 36px 9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                            <button type="button" x-on:click="s2=!s2" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;">
                                <i :class="s2?'fa fa-eye-slash':'fa fa-eye'" style="font-size:13px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>

        {{-- Footer --}}
        <div style="padding:16px 24px;border-top:1px solid #F3F4F6;display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-shrink:0;background:#FAFAFA;">
            <button type="button" @click="close()"
                    style="padding:9px 20px;background:#F3F4F6;border:none;border-radius:10px;font-size:13px;font-weight:600;color:#6B7280;cursor:pointer;">
                Cancel
            </button>
            <button type="submit" form="edit-user-form" :disabled="saving"
                    style="padding:9px 24px;background:linear-gradient(135deg,#6366F1,#4F46E5);border:none;border-radius:10px;font-size:13px;font-weight:600;color:#fff;cursor:pointer;display:flex;align-items:center;gap:7px;box-shadow:0 4px 12px rgba(99,102,241,.3);"
                    :style="saving ? 'opacity:0.7;cursor:not-allowed;' : ''">
                <i x-show="saving" class="fa fa-spinner fa-spin text-xs"></i>
                <i x-show="!saving" class="fa fa-check text-xs"></i>
                <span x-text="saving ? 'Saving…' : 'Save Changes'"></span>
            </button>
        </div>

    </div>
</div>

<script>
function editUserModal() {
    return {
        show:         false,
        saving:       false,
        userId:       null,
        name:         '',
        email:        '',
        phone:        '',
        jobTitle:     '',
        role:         '',
        status:       '',
        avatarPreview: null,

        open(u) {
            this.userId        = u.id;
            this.name          = u.name;
            this.email         = u.email;
            this.phone         = u.phone || '';
            this.jobTitle      = u.job_title || '';
            this.role          = u.role;
            this.status        = u.status;
            this.avatarPreview = u.avatar || null;
            this.saving        = false;
            this.show          = true;
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.show = false;
            document.body.style.overflow = '';
        },
    };
}
</script>
{{-- ══════════════════════════════════════════════════════
     ADD USER MODAL
══════════════════════════════════════════════════════ --}}
@php
    $addPermKeys = array_keys(\App\Models\User::ALL_PERMISSIONS);
    $addPermGroups = [
        'Tasks & Work'    => ['icon'=>'fa-square-check','color'=>'#6366F1','bg'=>'#EEF2FF','perms'=>['view_tasks'=>'View Tasks','submit_work'=>'Submit Work','manage_tasks'=>'Manage Tasks','approve_tasks'=>'Approve Submissions','view_activity_log'=>'Activity Log','view_version_history'=>'Version History','view_comments'=>'Comments']],
        'Projects & Team' => ['icon'=>'fa-diagram-project','color'=>'#10B981','bg'=>'#ECFDF5','perms'=>['view_projects'=>'View Projects','manage_projects'=>'Manage Projects','view_team_tasks'=>'Team Tasks','view_team'=>'Team Directory']],
        'Communication'   => ['icon'=>'fa-comment-dots','color'=>'#3B82F6','bg'=>'#EFF6FF','perms'=>['view_messages'=>'Messages','view_calendar'=>'Calendar']],
        'Reports'         => ['icon'=>'fa-chart-bar','color'=>'#F59E0B','bg'=>'#FFFBEB','perms'=>['view_reports'=>'Reports','export_data'=>'Export Data','view_audit_log'=>'Audit Log']],
        'Admin'           => ['icon'=>'fa-gear','color'=>'#EF4444','bg'=>'#FEF2F2','perms'=>['manage_users'=>'Manage Users','manage_roles'=>'Manage Roles','manage_settings'=>'Settings','view_approvals'=>'Approvals']],
    ];
@endphp

<div x-data="addUserModal()"
     x-on:open-add-user.window="open()"
     x-cloak
     :style="show ? 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;' : 'display:none;'">

    {{-- Backdrop --}}
    <div @click="close()" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);"></div>

    {{-- Panel --}}
    <div style="position:relative;width:100%;max-width:620px;background:#fff;border-radius:20px;box-shadow:0 32px 80px rgba(0,0,0,0.22);display:flex;flex-direction:column;max-height:calc(100vh - 32px);overflow:hidden;">

        {{-- Header --}}
        <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:12px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa fa-user-plus" style="color:#6366F1;font-size:16px;"></i>
                </div>
                <div>
                    <h2 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Add New User</h2>
                    <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Add a new member to the team</p>
                </div>
            </div>
            <button @click="close()" style="width:32px;height:32px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;flex-shrink:0;">
                <i class="fa fa-times"></i>
            </button>
        </div>

        {{-- Scrollable body --}}
        <div style="overflow-y:auto;flex:1;padding:20px 24px;">
            <form id="add-user-form" method="POST" action="{{ route('admin.users.store') }}"
                  enctype="multipart/form-data" @submit="saving = true">
                @csrf

                {{-- Avatar --}}
                <div style="display:flex;align-items:center;gap:16px;padding:16px;background:#F9FAFB;border-radius:12px;border:1px solid #F3F4F6;margin-bottom:16px;">
                    <div style="position:relative;flex-shrink:0;">
                        <template x-if="avatarPreview">
                            <div style="width:64px;height:64px;border-radius:50%;overflow:hidden;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.12);">
                                <img :src="avatarPreview" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        </template>
                        <template x-if="!avatarPreview">
                            <div style="width:64px;height:64px;border-radius:50%;background:#E0E7FF;display:flex;align-items:center;justify-content:center;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.12);">
                                <i class="fa fa-user" style="font-size:22px;color:#6366F1;"></i>
                            </div>
                        </template>
                        <label style="position:absolute;bottom:-2px;right:-2px;width:24px;height:24px;background:#6366F1;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid #fff;">
                            <i class="fa fa-camera" style="color:#fff;font-size:9px;"></i>
                            <input type="file" name="avatar" accept="image/*" class="hidden"
                                   x-on:change="avatarPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                        </label>
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#374151;margin:0;">Profile Photo</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">JPG, PNG or WebP · Max 2MB · Optional</p>
                    </div>
                </div>

                {{-- Basic Info --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                    <div style="grid-column:span 2;">
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Full Name <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="name" required placeholder="John Doe" maxlength="255"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Email Address <span style="color:#EF4444;">*</span></label>
                        <input type="email" name="email" required placeholder="user@company.com" maxlength="255"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Phone Number</label>
                        <input type="tel" name="phone" placeholder="+1 555 000 0000" maxlength="30"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Job Title</label>
                        <input type="text" name="job_title" placeholder="e.g. Frontend Developer" maxlength="80"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Role <span style="color:#EF4444;">*</span></label>
                        <select name="role" x-model="role" required
                                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;cursor:pointer;">
                            <option value="">Select role…</option>
                            @foreach($allRoles as $r)
                            <option value="{{ $r->name }}">{{ $r->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Status</label>
                        <select name="status"
                                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;cursor:pointer;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                {{-- Password --}}
                <div style="border:1.5px solid #F3F4F6;border-radius:12px;overflow:hidden;margin-bottom:16px;">
                    <div style="padding:10px 14px;background:#F9FAFB;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:7px;">
                        <i class="fa fa-lock" style="font-size:11px;color:#9CA3AF;"></i>
                        <span style="font-size:12px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Password</span>
                        <span style="font-size:11px;color:#9CA3AF;font-weight:400;text-transform:none;letter-spacing:0;">— required</span>
                    </div>
                    <div style="padding:14px;display:grid;grid-template-columns:1fr 1fr;gap:12px;" x-data="{s1:false,s2:false}">
                        <div style="position:relative;">
                            <input :type="s1?'text':'password'" name="password" required placeholder="Min. 8 characters"
                                   style="width:100%;padding:9px 36px 9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                            <button type="button" x-on:click="s1=!s1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;">
                                <i :class="s1?'fa fa-eye-slash':'fa fa-eye'" style="font-size:13px;"></i>
                            </button>
                        </div>
                        <div style="position:relative;">
                            <input :type="s2?'text':'password'" name="password_confirmation" required placeholder="Confirm password"
                                   style="width:100%;padding:9px 36px 9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;">
                            <button type="button" x-on:click="s2=!s2" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;">
                                <i :class="s2?'fa fa-eye-slash':'fa fa-eye'" style="font-size:13px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Permissions (user role only) --}}
                <div x-show="role === 'user'" style="border:1.5px solid #F3F4F6;border-radius:12px;overflow:hidden;margin-bottom:4px;">
                    <input type="hidden" name="_perms_sent" value="1">

                    {{-- Full access toggle --}}
                    <div style="padding:12px 16px;background:#FAFAFA;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <p style="font-size:13px;font-weight:600;color:#374151;margin:0;">Permissions</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Control what this user can access</p>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:11px;font-weight:600;" :style="allOn ? 'color:#10B981;' : 'color:#9CA3AF;'" x-text="allOn ? 'Full Access' : 'Custom'"></span>
                            <button type="button" @click="allOn = !allOn"
                                    :style="allOn ? 'background:#10B981;border-color:#10B981;' : 'background:#fff;border-color:#D1D5DB;'"
                                    style="position:relative;width:44px;height:24px;border-radius:12px;border:2px solid;cursor:pointer;outline:none;flex-shrink:0;transition:background .2s,border-color .2s;">
                                <span :style="allOn ? 'transform:translateX(20px);' : 'transform:translateX(0);'"
                                      style="position:absolute;top:2px;left:2px;width:16px;height:16px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;display:block;"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="!allOn">
                        @foreach($addPermGroups as $grpName => $grp)
                        <div style="{{ !$loop->first ? 'border-top:1px solid #F3F4F6;' : '' }}">
                            <div style="padding:7px 16px;background:{{ $grp['bg'] }};display:flex;align-items:center;gap:6px;">
                                <i class="fa {{ $grp['icon'] }}" style="font-size:10px;color:{{ $grp['color'] }};"></i>
                                <span style="font-size:10px;font-weight:700;color:{{ $grp['color'] }};text-transform:uppercase;letter-spacing:.05em;">{{ $grpName }}</span>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;">
                                @foreach($grp['perms'] as $pk => $plabel)
                                <label style="display:flex;align-items:center;gap:8px;padding:9px 14px;cursor:pointer;border-top:1px solid #F9FAFB;{{ $loop->index % 2 === 1 ? 'border-left:1px solid #F9FAFB;' : '' }}"
                                       onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                                    <input type="checkbox" name="permissions[]" value="{{ $pk }}"
                                           :checked="allOn || perms.includes('{{ $pk }}')"
                                           x-on:change="togglePerm('{{ $pk }}')"
                                           style="width:14px;height:14px;border-radius:4px;accent-color:#6366F1;flex-shrink:0;">
                                    <span style="font-size:11.5px;color:#374151;font-weight:500;">{{ $plabel }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div x-show="allOn" style="padding:10px 16px;background:#ECFDF5;border-top:1px solid #A7F3D0;">
                        <p style="font-size:12px;color:#059669;"><i class="fa fa-circle-check" style="margin-right:5px;"></i>User will have unrestricted access to all features.</p>
                    </div>
                </div>

            </form>
        </div>

        {{-- Footer --}}
        <div style="padding:16px 24px;border-top:1px solid #F3F4F6;display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-shrink:0;background:#FAFAFA;">
            <button type="button" @click="close()"
                    style="padding:9px 20px;background:#F3F4F6;border:none;border-radius:10px;font-size:13px;font-weight:600;color:#6B7280;cursor:pointer;">
                Cancel
            </button>
            <button type="submit" form="add-user-form" :disabled="saving"
                    style="padding:9px 24px;background:linear-gradient(135deg,#6366F1,#4F46E5);border:none;border-radius:10px;font-size:13px;font-weight:600;color:#fff;cursor:pointer;display:flex;align-items:center;gap:7px;box-shadow:0 4px 12px rgba(99,102,241,.3);"
                    :style="saving ? 'opacity:0.7;cursor:not-allowed;' : ''">
                <i x-show="saving" class="fa fa-spinner fa-spin text-xs"></i>
                <i x-show="!saving" class="fa fa-user-plus text-xs"></i>
                <span x-text="saving ? 'Creating…' : 'Create User'"></span>
            </button>
        </div>

    </div>
</div>

<script>
function addUserModal() {
    const defaultPerms = ['view_tasks','submit_work','view_comments','view_activity_log','view_version_history','view_projects','view_team','view_messages','view_calendar'];
    return {
        show:         false,
        saving:       false,
        role:         '',
        allOn:        false,
        perms:        [...defaultPerms],
        avatarPreview: null,

        open() {
            this.show          = true;
            this.saving        = false;
            this.role          = '';
            this.allOn         = false;
            this.perms         = [...defaultPerms];
            this.avatarPreview = null;
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.show = false;
            document.body.style.overflow = '';
        },

        togglePerm(key) {
            const idx = this.perms.indexOf(key);
            if (idx >= 0) this.perms.splice(idx, 1);
            else this.perms.push(key);
        },
    };
}
</script>
@endsection

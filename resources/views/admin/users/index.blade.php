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

{{-- Tabs --}}
<div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit mb-6">
    <a href="{{ route('admin.users.index') }}?tab=users"
       class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition {{ request('tab','users') === 'users' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
        <i class="fa fa-users text-xs"></i> Users
    </a>
    <a href="{{ route('admin.users.index') }}?tab=permissions"
       class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition {{ request('tab') === 'permissions' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
        <i class="fa fa-shield-halved text-xs"></i> Permissions
    </a>
</div>

@if(request('tab','users') === 'users')

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
@endif {{-- users tab --}}

{{-- ═══════════ PERMISSIONS TAB ═══════════ --}}
@if(request('tab') === 'permissions')
@php
    $permUsers     = \App\Models\User::where('role', 'user')->orderBy('name')->get();
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
        'Content & Tasks' => [
            'icon' => 'fa-file-lines', 'color' => '#6366F1', 'bg' => '#EEF2FF',
            'perms' => [
                'view_activity_log'    => ['icon' => 'fa-bolt',               'label' => 'Activity Log',       'desc' => 'Task history and change log'],
                'view_version_history' => ['icon' => 'fa-clock-rotate-left',  'label' => 'Version History',    'desc' => 'Submitted versions and review history'],
                'view_comments'        => ['icon' => 'fa-comments',           'label' => 'Comments & Updates', 'desc' => 'Read and write task comments'],
                'submit_work'          => ['icon' => 'fa-paper-plane',        'label' => 'Submit Work',        'desc' => 'Submit deliverables for manager review'],
            ],
        ],
        'Navigation & Pages' => [
            'icon' => 'fa-compass', 'color' => '#8B5CF6', 'bg' => '#F5F3FF',
            'perms' => [
                'view_messages'   => ['icon' => 'fa-comment-dots',    'label' => 'Messages',       'desc' => 'Direct messaging with teammates'],
                'view_team'       => ['icon' => 'fa-users',           'label' => 'Team Page',      'desc' => 'Browse team member profiles'],
                'view_calendar'   => ['icon' => 'fa-calendar-days',   'label' => 'Calendar',       'desc' => 'View task deadlines and schedule'],
                'view_projects'   => ['icon' => 'fa-diagram-project', 'label' => 'Projects',       'desc' => 'Personal projects section'],
                'view_team_tasks' => ['icon' => 'fa-list-check',      'label' => 'Team Tasks Tab', 'desc' => 'See tasks assigned to teammates'],
            ],
        ],
    ];
@endphp

<style>
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
</style>

@if($permUsers->isEmpty())
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
    <i class="fa fa-users text-4xl text-gray-200 mb-3 block"></i>
    <p class="text-sm text-gray-400">No regular users found to manage permissions for.</p>
</div>
@else

<div x-data="permsApp()" x-init="init()" style="display:flex;gap:20px;height:calc(100vh - 230px);min-height:540px;">

    {{-- ── Left panel: user list ── --}}
    <div style="width:272px;flex-shrink:0;background:#fff;border-radius:16px;border:1px solid #E5E7EB;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 1px 4px rgba(0,0,0,.05);">

        {{-- Search --}}
        <div style="padding:14px;border-bottom:1px solid #F3F4F6;">
            <div style="position:relative;">
                <i class="fa fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:11px;pointer-events:none;"></i>
                <input x-model="search" type="text" placeholder="Search users…"
                    style="width:100%;padding:8px 10px 8px 30px;border:1px solid #E5E7EB;border-radius:8px;font-size:13px;outline:none;background:#F9FAFB;box-sizing:border-box;color:#374151;transition:border .15s,background .15s;"
                    @focus="$el.style.borderColor='#6366F1';$el.style.background='#fff'"
                    @blur="$el.style.borderColor='#E5E7EB';$el.style.background='#F9FAFB'">
            </div>
        </div>

        {{-- Count bar --}}
        <div style="padding:5px 16px;background:#F9FAFB;border-bottom:1px solid #F3F4F6;">
            <span style="font-size:10.5px;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.06em;"
                x-text="`${filteredUsers.length} member${filteredUsers.length !== 1 ? 's' : ''}`"></span>
        </div>

        {{-- User rows --}}
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

    {{-- ── Right panel: permission editor ── --}}
    <div style="flex:1;min-width:0;overflow-y:auto;display:flex;flex-direction:column;gap:14px;">

        {{-- Empty state --}}
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

                {{-- Full Access --}}
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

                {{-- Permission groups (server-rendered, Alpine-controlled) --}}
                @foreach($permGroups as $groupName => $group)
                @php $groupKeys = array_keys($group['perms']); @endphp
                <div style="background:#fff;border-radius:16px;border:1px solid #E5E7EB;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.04);"
                     :style="activeUser.unrestricted ? 'opacity:0.5;pointer-events:none;' : ''">

                    {{-- Group header --}}
                    <div style="padding:12px 20px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:10px;background:#FAFAFA;">
                        <div style="width:28px;height:28px;border-radius:7px;background:{{ $group['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa {{ $group['icon'] }}" style="font-size:12px;color:{{ $group['color'] }};"></i>
                        </div>
                        <span style="font-size:13px;font-weight:600;color:#374151;flex:1;">{{ $groupName }}</span>
                        <span style="font-size:11px;color:#9CA3AF;background:#F3F4F6;padding:2px 8px;border-radius:20px;"
                            x-text="`${[{{ implode(',', array_map(fn($k) => "'{$k}'", $groupKeys)) }}].filter(k => hasPermission(k)).length} / {{ count($group['perms']) }}`"></span>
                    </div>

                    {{-- Permission rows --}}
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

@endif {{-- permissions tab --}}

@endsection

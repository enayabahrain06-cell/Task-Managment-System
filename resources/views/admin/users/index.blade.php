@extends('layouts.app')
@section('title', 'Manage Users')

@section('content')
@php
$roleColorMap = ['admin'=>'bg-red-100 text-red-600','manager'=>'bg-amber-100 text-amber-700','user'=>'bg-emerald-100 text-emerald-700'];
$avatarBg     = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6'];
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
    <a href="{{ route('admin.users.index') }}?tab=roles"
       class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition {{ request('tab') === 'roles' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
        <i class="fa fa-tag text-xs"></i> Roles
        <span class="text-xs font-bold px-1.5 py-0.5 rounded-full {{ request('tab') === 'roles' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-200 text-gray-500' }}">{{ $allRoles->count() }}</span>
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
                    @php
                        $roleObj = $allRoles->firstWhere('name', $user->role);
                        $roleLabel = $roleObj ? $roleObj->label : ucfirst($user->role);
                        $roleStyle = $roleObj ? "background:{$roleObj->color}18;color:{$roleObj->color};" : '';
                    @endphp
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $roleColorMap[$user->role] ?? '' }}"
                          @if(!isset($roleColorMap[$user->role])) style="{{ $roleStyle }}" @endif>
                        {{ $roleLabel }}
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

{{-- ═══════════ ROLES TAB ═══════════ --}}
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

    {{-- ── Header ── --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="text-sm text-gray-500">{{ $allRoles->count() }} roles defined &mdash; <span class="text-indigo-600 font-medium">{{ $allRoles->where('is_system', false)->count() }} custom</span></p>
        </div>
        <button @click="openCreate()"
                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition shadow-sm">
            <i class="fa fa-plus text-xs"></i> New Role
        </button>
    </div>

    {{-- ── Role Cards Grid ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
        @foreach($allRoles as $role)
        @php $userCount = \App\Models\User::where('role', $role->name)->count(); @endphp
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col transition hover:shadow-md">

            {{-- Colored top bar --}}
            <div class="h-1.5 w-full" style="background:{{ $role->color }};"></div>

            <div class="p-5 flex flex-col gap-3 flex-1">
                {{-- Role icon + name --}}
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

                {{-- Description --}}
                <p class="text-xs text-gray-500 leading-relaxed flex-1 min-h-[36px]">
                    {{ $role->description ?: 'No description.' }}
                </p>

                {{-- Permissions preview --}}
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

                {{-- User count --}}
                <div class="flex items-center gap-2 pt-2 border-t border-gray-50">
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <i class="fa fa-users text-gray-400 text-xs"></i>
                        <span><strong class="text-gray-700">{{ $userCount }}</strong> {{ Str::plural('user', $userCount) }}</span>
                    </div>
                    @if($userCount > 0)
                    <a href="{{ route('admin.users.index') }}?role={{ $role->name }}&tab=users"
                       class="ml-auto text-xs text-indigo-500 hover:text-indigo-700 font-medium">View →</a>
                    @endif
                </div>

                {{-- Actions --}}
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

    {{-- ══ Create / Edit Modal ══ --}}
    <div x-show="modalOpen" x-cloak style="position:fixed;inset:0;z-index:9999;">
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;">

            <div @click="modalOpen=false" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);-webkit-backdrop-filter:blur(3px);backdrop-filter:blur(3px);"></div>

            <div style="position:relative;width:100%;max-width:520px;background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,0.2);display:flex;flex-direction:column;max-height:calc(100vh - 40px);">

                {{-- Modal header (fixed) --}}
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

                {{-- Scrollable body --}}
                <div style="overflow-y:auto;flex:1;min-height:0;">

                @php
                    $modalPermGroups = [
                        'Tasks & Work' => [
                            'icon' => 'fa-square-check', 'color' => '#6366F1', 'bg' => '#EEF2FF',
                            'perms' => [
                                'view_tasks'           => ['icon' => 'fa-list-check',          'desc' => 'View & manage own tasks'],
                                'submit_work'          => ['icon' => 'fa-paper-plane',          'desc' => 'Submit deliverables for review'],
                                'manage_tasks'         => ['icon' => 'fa-pen-to-square',        'desc' => 'Create, edit & assign tasks'],
                                'approve_tasks'        => ['icon' => 'fa-clipboard-check',      'desc' => 'Approve or reject submissions'],
                                'view_activity_log'    => ['icon' => 'fa-bolt',                 'desc' => 'Task history & change log'],
                                'view_version_history' => ['icon' => 'fa-clock-rotate-left',    'desc' => 'Submitted version history'],
                                'view_comments'        => ['icon' => 'fa-comments',             'desc' => 'Read & write task comments'],
                            ],
                        ],
                        'Projects & Team' => [
                            'icon' => 'fa-diagram-project', 'color' => '#10B981', 'bg' => '#ECFDF5',
                            'perms' => [
                                'view_projects'   => ['icon' => 'fa-folder-open',    'desc' => 'Browse own projects'],
                                'manage_projects' => ['icon' => 'fa-folder-plus',    'desc' => 'Create & manage projects'],
                                'view_team_tasks' => ['icon' => 'fa-users-viewfinder','desc' => 'View tasks of teammates'],
                                'view_team'       => ['icon' => 'fa-users',           'desc' => 'Team member directory'],
                            ],
                        ],
                        'Communication' => [
                            'icon' => 'fa-comment-dots', 'color' => '#3B82F6', 'bg' => '#EFF6FF',
                            'perms' => [
                                'view_messages' => ['icon' => 'fa-envelope',       'desc' => 'Direct messaging'],
                                'view_calendar' => ['icon' => 'fa-calendar-days',  'desc' => 'Calendar & schedule'],
                            ],
                        ],
                        'Reports & Data' => [
                            'icon' => 'fa-chart-bar', 'color' => '#F59E0B', 'bg' => '#FFFBEB',
                            'perms' => [
                                'view_reports'  => ['icon' => 'fa-chart-column',    'desc' => 'Reports & analytics page'],
                                'export_data'   => ['icon' => 'fa-file-export',     'desc' => 'Export & download data'],
                                'view_audit_log'=> ['icon' => 'fa-shield-halved',   'desc' => 'View audit log entries'],
                            ],
                        ],
                        'Administration' => [
                            'icon' => 'fa-gear', 'color' => '#EF4444', 'bg' => '#FEF2F2',
                            'perms' => [
                                'manage_users'    => ['icon' => 'fa-user-shield',  'desc' => 'Create & manage users'],
                                'manage_roles'    => ['icon' => 'fa-tag',          'desc' => 'Manage roles & permissions'],
                                'manage_settings' => ['icon' => 'fa-sliders',      'desc' => 'System settings & config'],
                                'view_approvals'  => ['icon' => 'fa-stamp',        'desc' => 'Task approvals queue'],
                            ],
                        ],
                    ];
                @endphp

                {{-- Shared field partial (rendered twice for create/edit) --}}
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
                                <button type="button"
                                        @click="form.color='{{ $pc }}'"
                                        :style="form.color === '{{ $pc }}' ? 'width:28px;height:28px;border-radius:50%;background:{{ $pc }};border:3px solid #111827;flex-shrink:0;cursor:pointer;' : 'width:26px;height:26px;border-radius:50%;background:{{ $pc }};border:2px solid transparent;flex-shrink:0;cursor:pointer;'">
                                </button>
                                @endforeach
                                <input type="color" x-model="form.color"
                                       style="width:36px;height:28px;border:1.5px solid #E5E7EB;border-radius:6px;padding:2px;cursor:pointer;background:#fff;">
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

                        {{-- ── Permissions ── --}}
                        <div style="margin-bottom:20px;">
                            <label class="form-label" style="margin-bottom:8px;">Default Permissions</label>

                            {{-- Full Access row --}}
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;margin-bottom:10px;background:#F9FAFB;cursor:pointer;"
                                 @click="form.unrestricted = !form.unrestricted">
                                <div style="display:flex;align-items:center;gap:8px;pointer-events:none;">
                                    <i class="fa fa-infinity" style="color:#10B981;font-size:13px;"></i>
                                    <div>
                                        <span style="font-size:13px;font-weight:600;color:#374151;">Full Access</span>
                                        <span style="font-size:11px;color:#9CA3AF;margin-left:5px;">— no restrictions for this role</span>
                                    </div>
                                </div>
                                <button type="button" :class="form.unrestricted ? 'role-perm-toggle is-on' : 'role-perm-toggle'" style="pointer-events:none;flex-shrink:0;">
                                    <span class="role-perm-toggle-knob"></span>
                                </button>
                            </div>

                            {{-- Permission groups (shown when not full access) --}}
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
                                            <button type="button"
                                                    @click.prevent="togglePerm('{{ $pk }}')"
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

                        {{-- Hidden permission inputs --}}
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
                                <button type="button"
                                        @click="form.color='{{ $pc }}'"
                                        :style="form.color === '{{ $pc }}' ? 'width:28px;height:28px;border-radius:50%;background:{{ $pc }};border:3px solid #111827;flex-shrink:0;cursor:pointer;' : 'width:26px;height:26px;border-radius:50%;background:{{ $pc }};border:2px solid transparent;flex-shrink:0;cursor:pointer;'">
                                </button>
                                @endforeach
                                <input type="color" x-model="form.color"
                                       style="width:36px;height:28px;border:1.5px solid #E5E7EB;border-radius:6px;padding:2px;cursor:pointer;background:#fff;">
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

                        {{-- ── Permissions ── --}}
                        <div style="margin-bottom:20px;">
                            <label class="form-label" style="margin-bottom:8px;">Default Permissions</label>

                            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;margin-bottom:10px;background:#F9FAFB;cursor:pointer;"
                                 @click="form.unrestricted = !form.unrestricted">
                                <div style="display:flex;align-items:center;gap:8px;pointer-events:none;">
                                    <i class="fa fa-infinity" style="color:#10B981;font-size:13px;"></i>
                                    <div>
                                        <span style="font-size:13px;font-weight:600;color:#374151;">Full Access</span>
                                        <span style="font-size:11px;color:#9CA3AF;margin-left:5px;">— no restrictions for this role</span>
                                    </div>
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
                                            <button type="button"
                                                    @click.prevent="togglePerm('{{ $pk }}')"
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

                </div>{{-- end scrollable body --}}
            </div>
        </div>
    </div>

</div>

<style>
.role-perm-toggle{position:relative;width:36px;height:20px;border-radius:10px;border:2px solid #D1D5DB;background:#fff;transition:background .18s,border-color .18s;cursor:pointer;flex-shrink:0;outline:none;display:inline-block;}
.role-perm-toggle.is-on{background:#6366F1;border-color:#6366F1;}
.role-perm-toggle-knob{position:absolute;top:2px;left:2px;width:12px;height:12px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .18s;display:block;}
.role-perm-toggle.is-on .role-perm-toggle-knob{transform:translateX(16px);}
</style>

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
@endif {{-- roles tab --}}

@endsection

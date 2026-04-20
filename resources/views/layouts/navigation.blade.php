@php
    $role              = auth()->user()->role ?? 'user';
    $taskCount         = auth()->check() ? auth()->user()->tasks()->where('status','!=','completed')->count() : 0;
    $dashRoute         = $role === 'admin' ? 'admin.dashboard' : ($role === 'manager' ? 'manager.dashboard' : 'user.dashboard');
    $tasksRoute        = $role === 'admin' ? 'admin.dashboard' : ($role === 'manager' ? 'manager.dashboard' : 'user.dashboard');
    $approvalCount     = ($role === 'admin' && auth()->check()) ? \App\Models\Task::where('status','pending_approval')->count() : 0;
    $userProjectCount  = ($role === 'user'  && auth()->check()) ? auth()->user()->projects()->count() : 0;
@endphp

<div style="display:flex;flex-direction:column;height:100%;">

    {{-- Brand --}}
    <div class="sidebar-brand" style="display:flex;flex-direction:column;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <div class="sidebar-logo" style="margin-bottom:0;">
                @if(!empty($appSettings['logo_path']))
                    <img src="{{ Storage::url($appSettings['logo_path']) }}"
                         alt="{{ $appSettings['app_name'] ?? 'Logo' }}"
                         style="height:32px;width:auto;max-width:120px;object-fit:contain;border-radius:6px;">
                @else
                    <div class="sidebar-logo-icon"><i class="fas fa-bolt"></i></div>
                    <span class="sidebar-logo-text">{{ strtolower($appSettings['app_name'] ?? 'dash') }}</span>
                @endif
            </div>
            {{-- Mobile close button --}}
            <button @click="sidebarOpen = false"
                    style="display:none;width:28px;height:28px;border-radius:6px;background:#F3F4F6;border:none;cursor:pointer;align-items:center;justify-content:center;color:#6B7280;font-size:12px;flex-shrink:0;"
                    id="sidebar-close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <button class="sidebar-company">
            <span>{{ $appSettings['company_name'] ?? 'Product Co.' }}</span>
            <i class="fas fa-chevron-down" style="font-size:10px;color:#9CA3AF;"></i>
        </button>
    </div>
    <style>
    @media (max-width: 768px) {
        #sidebar-close-btn { display: flex !important; }
    }
    .recent-proj-link { display:flex;align-items:center;gap:8px;padding:6px 10px;font-size:13px;color:#6B7280;text-decoration:none;border-radius:6px;transition:color 0.15s,background 0.15s;margin-bottom:2px; }
    .recent-proj-link:hover { color:#4F46E5; background:#F5F3FF; }
    .logout-btn-wrap { width:100%;background:none;border:none;cursor:pointer;text-align:left;padding:0; }
    .logout-btn-wrap .nav-item { transition:background 0.15s,color 0.15s; }
    .logout-btn-wrap:hover .nav-item { background:#FEF2F2 !important; color:#DC2626 !important; }
    .logout-btn-wrap:hover .nav-icon { color:#DC2626 !important; }
    </style>

    {{-- Nav --}}
    <nav class="sidebar-nav">

        {{-- My Tasks --}}
        <a href="{{ route($tasksRoute) }}"
           class="nav-item {{ request()->routeIs($tasksRoute) ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-square-check nav-icon"></i>
                My Tasks
            </div>
            @if($taskCount > 0)
                <span class="nav-badge nav-badge-blue">{{ $taskCount }}</span>
            @endif
        </a>

        {{-- Activities --}}
        <a href="{{ route('activities.index') }}"
           class="nav-item {{ request()->routeIs('activities.*') ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-bolt nav-icon"></i>
                Activities
            </div>
        </a>

        {{-- MENU section --}}
        <div class="sidebar-section">Menu</div>

        {{-- Overview (admin/manager only — users have My Tasks as their dashboard) --}}
        @if($role !== 'user')
        <a href="{{ route($dashRoute) }}"
           class="nav-item {{ request()->routeIs($dashRoute) ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-table-cells-large nav-icon"></i>
                Overview
            </div>
        </a>
        @endif

        {{-- Messages --}}
        @if(auth()->user()->hasPermission('view_messages'))
        <a href="{{ route('messages.index') }}"
           class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-comment-dots nav-icon"></i>
                Messages
            </div>
            <span class="nav-badge nav-badge-red">8</span>
        </a>
        @endif

        {{-- Team Members --}}
        @if(auth()->user()->hasPermission('view_team'))
        <a href="{{ route('team.index') }}"
           class="nav-item {{ request()->routeIs('team.*') ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-users nav-icon"></i>
                Team Members
            </div>
        </a>
        @endif

        {{-- Calendar --}}
        @if(auth()->user()->hasPermission('view_calendar'))
        <a href="{{ route('calendar.index') }}"
           class="nav-item {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-calendar-days nav-icon"></i>
                Calendar
            </div>
        </a>
        @endif

        {{-- User-only: My Projects --}}
        @if($role === 'user' && auth()->user()->hasPermission('view_projects'))
        <a href="{{ route('user.projects.index') }}"
           class="nav-item {{ request()->routeIs('user.projects.*') ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-diagram-project nav-icon"></i>
                My Projects
            </div>
            @if($userProjectCount > 0)
            <span class="nav-badge nav-badge-blue">{{ $userProjectCount }}</span>
            @endif
        </a>
        @endif

        {{-- Admin-only --}}
        @if($role === 'admin')
        <div class="sidebar-section">Admin</div>

        <a href="{{ route('admin.users.index') }}"
           class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-user-shield nav-icon"></i>
                Manage Users
            </div>
        </a>

        <a href="{{ route('admin.projects.index') }}"
           class="nav-item {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-diagram-project nav-icon"></i>
                Projects
            </div>
        </a>

        <a href="{{ route('admin.approvals.index') }}"
           class="nav-item {{ request()->routeIs('admin.approvals.*') ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-clipboard-check nav-icon"></i>
                Approvals
            </div>
            @if($approvalCount > 0)
            <span class="nav-badge nav-badge-red">{{ $approvalCount }}</span>
            @endif
        </a>

        <a href="{{ route('admin.audit.index') }}"
           class="nav-item {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-shield-halved nav-icon"></i>
                Audit Log
            </div>
        </a>

        <a href="{{ route('admin.reports.index') }}"
           class="nav-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-chart-bar nav-icon"></i>
                Reports
            </div>
        </a>
        @endif

        {{-- Recent Projects (from View Composer) --}}
        @if(isset($recentProjects) && $recentProjects->count())
        <div class="sidebar-section">Recent Project</div>
        @foreach($recentProjects as $rp)
        @php $dotColors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6']; @endphp
        <a href="{{ $role === 'admin' ? route('admin.projects.show', $rp) : '#' }}"
           class="recent-proj-link">
            <span style="width:8px;height:8px;border-radius:50%;background:{{ $dotColors[$rp->id % 5] }};flex-shrink:0;"></span>
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $rp->name }}</span>
        </a>
        @endforeach
        @endif

    </nav>

    {{-- Footer --}}
    <div class="sidebar-footer">
        @if($role === 'admin')
        <a href="{{ route('admin.settings.index') }}"
           class="nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <div class="nav-left">
                <i class="fas fa-gear nav-icon"></i>
                Settings
            </div>
        </a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn-wrap">
                <span class="nav-item" style="display:flex;">
                    <span class="nav-left">
                        <i class="fas fa-right-from-bracket nav-icon"></i>
                        Logout
                    </span>
                </span>
            </button>
        </form>
    </div>

</div>

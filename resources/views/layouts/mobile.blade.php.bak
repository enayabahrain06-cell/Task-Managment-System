<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $appSettings['app_name'] ?? config('app.name', 'Dash') }} — @yield('title', 'Dashboard')</title>

    @if(!empty($appSettings['favicon_path']))
    <link rel="icon" type="image/png" href="{{ Storage::url($appSettings['favicon_path']) }}">
    @endif

    <link rel="stylesheet" href="/css/inter.css">
    <link rel="stylesheet" href="/css/fa-all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        :root {
            --mob-r-lg: 20px;
            --mob-r-md: 16px;
            --mob-r-sm: 12px;
            --mob-shadow-1: 0 2px 10px rgba(17,24,39,.05);
            --mob-shadow-2: 0 10px 30px rgba(17,24,39,.10);
            --mob-sp-1: 8px;
            --mob-sp-2: 16px;
            --mob-sp-3: 24px;
            --mob-brand: {{ $appSettings['primary_color'] ?? '#4F46E5' }};
            --mob-brand-grad: linear-gradient(135deg, {{ $appSettings['primary_color'] ?? '#4F46E5' }} 0%, #6366F1 100%);
            --mob-brand-accent: {{ $appSettings['primary_color'] ?? '#4F46E5' }};
            --mob-ink: #111827;
            --mob-ink-2: #4B5563;
            --mob-ink-3: #6B7280;
            --mob-line: #EDEFF3;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: #F8FAFC; color: var(--mob-ink); font-size: 15px; }

        .mob-topbar {
            position: sticky; top: 0; z-index: 40; height: 52px; background: #fff;
            border-bottom: 1px solid var(--mob-line); display: flex; align-items: center;
            justify-content: space-between; padding: 0 16px;
        }
        .mob-topbar-title { font-size: 16px; font-weight: 700; letter-spacing: -.01em; color: var(--mob-ink); }
        .mob-topbar-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .mob-back-btn {
            flex-shrink: 0; width: 32px; height: 32px; margin-left: -6px; border: 0; background: none;
            color: var(--mob-ink); font-size: 17px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; border-radius: 99px;
        }
        .mob-main { padding: 16px 0 96px; min-height: calc(100vh - 52px); }

        .mobile-bottom-nav {
            display: flex; position: fixed; left: 0; right: 0; bottom: 0; height: 58px;
            background: #fff; border-top: 1px solid var(--mob-line); z-index: 45;
            padding-bottom: env(safe-area-inset-bottom);
            box-shadow: 0 -4px 20px rgba(17,24,39,.06);
        }
        .mbn-item {
            flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 3px; text-decoration: none; color: var(--mob-ink-3); font-size: 10px; font-weight: 600;
            min-height: 44px; position: relative; background: none; border: none; cursor: pointer;
            font-family: 'Inter', sans-serif;
        }
        .mbn-item i { font-size: 17px; transition: transform .15s ease; }
        .mbn-item.active { color: var(--mob-brand); }
        .mbn-item.active i { transform: translateY(-1px) scale(1.08); }
        .mbn-badge {
            position: absolute; top: 2px; right: calc(50% - 20px); min-width: 15px; height: 15px;
            background: #EF4444; border-radius: 999px; color: #fff; font-size: 9px; font-weight: 700;
            display: flex; align-items: center; justify-content: center; padding: 0 3px; border: 2px solid #fff;
            line-height: 1;
        }
    </style>
    @stack('head_scripts')
</head>
<body>

    <header class="mob-topbar">
        <span class="mob-topbar-left">
            <button type="button" class="mob-back-btn" onclick="history.back()" aria-label="Back">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span class="mob-topbar-title">@yield('title', 'Dashboard')</span>
        </span>
        <a href="{{ route('alerts.index') }}" style="position:relative;width:36px;height:36px;border-radius:99px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;flex-shrink:0;">
            <i class="fas fa-bell" style="font-size:14px;"></i>
            @if(($notificationCount ?? 0) > 0)
            <span style="position:absolute;top:-2px;right:-2px;width:9px;height:9px;border-radius:99px;background:#EF4444;border:2px solid #fff;"></span>
            @endif
        </a>
    </header>

    <main class="mob-main">
        @yield('mobile-content')
    </main>

    @php
        $mbnRole         = auth()->user()->role ?? 'user';
        $mbnDashRoute    = $mbnRole === 'admin' ? 'admin.dashboard' : ($mbnRole === 'manager' ? 'manager.dashboard' : 'user.dashboard');
        $mbnTasksRoute   = $mbnRole === 'user' ? 'user.tasks.index' : 'admin.tasks.index';
        $mbnTasksPattern = $mbnRole === 'user' ? 'user.tasks.*' : 'admin.tasks.*';
        $mbnCanTasks     = $mbnRole === 'user' || auth()->user()->hasPermission('manage_tasks');
        $mbnCanTeam      = auth()->user()->hasPermission('view_team');
    @endphp
    <nav class="mobile-bottom-nav" aria-label="Primary">
        <a href="{{ route($mbnDashRoute) }}" class="mbn-item {{ request()->routeIs($mbnDashRoute) ? 'active' : '' }}"
           aria-current="{{ request()->routeIs($mbnDashRoute) ? 'page' : 'false' }}">
            <i class="fas fa-table-cells-large" aria-hidden="true"></i>
            <span>Home</span>
        </a>
        @if($mbnCanTasks)
        <a href="{{ route($mbnTasksRoute) }}" class="mbn-item {{ request()->routeIs($mbnTasksPattern) ? 'active' : '' }}"
           aria-current="{{ request()->routeIs($mbnTasksPattern) ? 'page' : 'false' }}">
            <i class="fas fa-list-check" aria-hidden="true"></i>
            <span>Tasks</span>
        </a>
        @endif
        @if($mbnCanTeam)
        <a href="{{ route('team.index') }}" class="mbn-item {{ request()->routeIs('team.*') ? 'active' : '' }}"
           aria-current="{{ request()->routeIs('team.*') ? 'page' : 'false' }}">
            <i class="fas fa-users" aria-hidden="true"></i>
            <span>Team</span>
        </a>
        @endif
        <a href="{{ route('alerts.index') }}" class="mbn-item {{ request()->routeIs('alerts.*') ? 'active' : '' }}"
           aria-current="{{ request()->routeIs('alerts.*') ? 'page' : 'false' }}">
            <i class="fas fa-bell" aria-hidden="true"></i>
            <span>Alerts</span>
            @if(($notificationCount ?? 0) > 0)
            <span class="mbn-badge">{{ $notificationCount > 9 ? '9+' : $notificationCount }}</span>
            @endif
        </a>
    </nav>

    @stack('modals')
    @stack('scripts')
</body>
</html>

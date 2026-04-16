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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: #F8FAFC; color: #111827; }

        /* Layout */
        .app-shell   { display: flex; height: 100vh; overflow: hidden; }
        .app-sidebar { width: 240px; min-width: 240px; background: #fff; border-right: 1px solid #E5E7EB; display: flex; flex-direction: column; overflow-y: auto; transition: transform 0.25s ease; z-index: 50; }
        .app-main    { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }
        .app-topbar  { height: 56px; background: #fff; border-bottom: 1px solid #E5E7EB; display: flex; align-items: center; justify-content: space-between; padding: 0 16px; flex-shrink: 0; }
        .app-content { flex: 1; overflow-y: auto; padding: 16px; }

        /* Mobile sidebar overlay */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 40; }
        .hamburger-btn   { display: none; width: 36px; height: 36px; border-radius: 8px; background: #F3F4F6; border: none; cursor: pointer; align-items: center; justify-content: center; color: #6B7280; font-size: 14px; flex-shrink: 0; margin-right: 8px; }

        @media (max-width: 768px) {
            .app-sidebar  { position: fixed; top: 0; left: 0; height: 100%; transform: translateX(-100%); }
            .app-sidebar.sidebar-open { transform: translateX(0); }
            .sidebar-overlay.overlay-open { display: block; }
            .hamburger-btn { display: flex; }
            .app-content { padding: 12px; }
            .topbar-search { display: none !important; }
        }

        @media (min-width: 769px) {
            .app-content { padding: 24px; }
        }

        /* Sidebar brand */
        .sidebar-brand { padding: 20px 20px 16px; border-bottom: 1px solid #F3F4F6; }
        .sidebar-logo  { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .sidebar-logo-icon { width: 32px; height: 32px; background: #4F46E5; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; flex-shrink: 0; }
        .sidebar-logo-text { font-size: 20px; font-weight: 700; color: #111827; }
        .sidebar-company   { display: flex; align-items: center; justify-content: space-between; width: 100%; font-size: 13px; font-weight: 500; color: #374151; cursor: pointer; background: none; border: none; padding: 0; }

        /* Sidebar nav */
        .sidebar-nav { flex: 1; padding: 12px; }
        .sidebar-section { font-size: 10px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.08em; padding: 16px 8px 4px; }
        .nav-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; border-radius: 8px; font-size: 13px; font-weight: 500; color: #6B7280; text-decoration: none; transition: background 0.15s, color 0.15s; margin-bottom: 2px; }
        .nav-item:hover { background: #F9FAFB; color: #111827; }
        .nav-item.active { background: #EEF2FF; color: #4F46E5; }
        .nav-item .nav-icon { width: 16px; text-align: center; margin-right: 10px; font-size: 13px; color: #9CA3AF; flex-shrink: 0; }
        .nav-item.active .nav-icon { color: #6366F1; }
        .nav-item:hover .nav-icon { color: #6B7280; }
        .nav-left { display: flex; align-items: center; }
        .nav-badge { font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 20px; }
        .nav-badge-blue { background: #EEF2FF; color: #4F46E5; }
        .nav-badge-red  { background: #FEE2E2; color: #EF4444; }

        /* Sidebar footer */
        .sidebar-footer { padding: 12px; border-top: 1px solid #F3F4F6; }
        .sidebar-footer .nav-item { margin-bottom: 2px; }

        /* Top bar */
        .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #9CA3AF; }
        .breadcrumb a { color: #9CA3AF; text-decoration: none; }
        .breadcrumb a:hover { color: #374151; }
        .breadcrumb-current { color: #111827; font-weight: 500; }
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .topbar-search { position: relative; }
        .topbar-search input { padding: 6px 12px 6px 32px; font-size: 12px; border: 1px solid #E5E7EB; border-radius: 8px; background: #F9FAFB; color: #374151; outline: none; width: 200px; font-family: 'Inter', sans-serif; }
        .topbar-search input:focus { border-color: #A5B4FC; box-shadow: 0 0 0 3px rgba(165,180,252,0.2); }
        .topbar-search i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9CA3AF; font-size: 11px; }
        .icon-btn { width: 32px; height: 32px; border-radius: 8px; background: #F3F4F6; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #6B7280; font-size: 13px; transition: background 0.15s; }
        .icon-btn:hover { background: #E5E7EB; }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background: #6366F1; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 13px; font-weight: 600; flex-shrink: 0; }
        .user-info p { margin: 0; }
        .user-name  { font-size: 12px; font-weight: 600; color: #111827; line-height: 1.3; }
        .user-email { font-size: 11px; color: #9CA3AF; line-height: 1.3; }

        /* Alpine cloak */
        [x-cloak] { display: none !important; }

        /* Alert */
        .alert { display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; }
        .alert-success { background: #F0FDF4; border: 1px solid #BBF7D0; color: #15803D; }
        .alert-error   { background: #FEF2F2; border: 1px solid #FECACA; color: #DC2626; }
        .alert-close   { margin-left: auto; background: none; border: none; cursor: pointer; color: inherit; opacity: 0.6; font-size: 12px; }
        .alert-close:hover { opacity: 1; }
    </style>
</head>
<body>

<div class="app-shell" x-data="{ sidebarOpen: false }">

    {{-- Mobile overlay backdrop --}}
    <div class="sidebar-overlay" :class="sidebarOpen ? 'overlay-open' : ''" @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    <aside class="app-sidebar" :class="sidebarOpen ? 'sidebar-open' : ''">
        @include('layouts.navigation')
    </aside>

    {{-- Main --}}
    <div class="app-main">

        {{-- Top Header --}}
        <header class="app-topbar">
            <div style="display:flex;align-items:center;gap:4px;">
                <button class="hamburger-btn" @click="sidebarOpen = !sidebarOpen" title="Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumb">
                    <a href="{{ url('/') }}">Home</a>
                    <span>/</span>
                    <span class="breadcrumb-current">@yield('title', 'Dashboard')</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-search" style="display:none" id="topbar-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="What are you looking for?">
                </div>
                <button class="icon-btn" title="Search" onclick="document.getElementById('topbar-search-wrap').style.display='block';this.style.display='none'">
                    <i class="fas fa-search"></i>
                </button>
                <button class="icon-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                </button>
                <button class="icon-btn" title="Toggle theme" style="display:none" id="theme-btn">
                    <i class="fas fa-moon"></i>
                </button>
                <div style="display:flex;align-items:center;gap:8px;margin-left:4px;">
                    @if(auth()->user()?->avatarUrl())
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}"
                             style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    @else
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                    <div class="user-info" style="display:none" id="user-info-block">
                        <p class="user-name">{{ auth()->user()->name ?? '' }}</p>
                        <p class="user-email">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main class="app-content">
            @if (session('success'))
                <div class="alert alert-success" x-data="{ show: true }" x-show="show" x-init="setTimeout(()=>show=false,4000)">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                    <button class="alert-close" @click="show=false"><i class="fas fa-times"></i></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-error" x-data="{ show: true }" x-show="show">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                    <button class="alert-close" @click="show=false"><i class="fas fa-times"></i></button>
                </div>
            @endif
            @yield('content')
        </main>

    </div>
</div>

<script>
if (window.innerWidth >= 1024) {
    var el = document.getElementById('user-info-block');
    if (el) el.style.display = 'block';
    var sw = document.getElementById('topbar-search-wrap');
    if (sw) sw.style.display = 'block';
    var themeBtn = document.getElementById('theme-btn');
    if (themeBtn) themeBtn.style.display = 'flex';
}
// Close sidebar when a nav link is clicked on mobile
document.querySelectorAll('.app-sidebar .nav-item').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            // trigger Alpine close
            var shell = document.querySelector('[x-data]');
            if (shell && shell.__x) { shell.__x.$data.sidebarOpen = false; }
        }
    });
});
</script>

@stack('scripts')
</body>
</html>

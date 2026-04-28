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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/fa-all.min.css">
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
            .topbar-search { display: block !important; }
            #user-info-block { display: block !important; }
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

        /* Page content fade-in */
        .app-content { animation: pageFadeIn 0.3s ease both; }
        @keyframes pageFadeIn { from { opacity:0; } to { opacity:1; } }

        /* Topbar search hidden on mobile, shown on desktop via media query */
        .topbar-search { display: none; }

        /* User info hidden on mobile */
        #user-info-block { display: none; }

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
                @php $headerHidden = json_decode($appSettings['nav_hidden'] ?? '[]', true) ?: []; @endphp
                @if(!in_array('nav_search', $headerHidden))
                <div class="topbar-search" id="topbar-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="What are you looking for?">
                </div>
                @endif
                {{-- Page History --}}
                @if(!in_array('nav_history', $headerHidden))
                <div x-data="navHistory()" x-init="init()" @click.outside="open=false" style="position:relative;">
                    <button @click="open=!open; if(open) load()" class="icon-btn" title="Recently viewed" style="position:relative;">
                        <i class="fas fa-clock-rotate-left"></i>
                        <span x-show="items.length>0 && !open"
                              style="position:absolute;top:-4px;right:-4px;min-width:16px;height:16px;background:#6366F1;border-radius:999px;font-size:9px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center;padding:0 3px;border:2px solid #fff;line-height:1;"
                              x-text="items.length>9?'9+':items.length"></span>
                    </button>
                    <div x-show="open" x-cloak
                         style="position:absolute;right:0;top:calc(100% + 8px);width:320px;background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.12);border:1px solid #F0F0F0;z-index:200;overflow:hidden;">
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #F3F4F6;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-size:14px;font-weight:700;color:#111827;">Recently Viewed</span>
                                <span x-show="items.length>0" style="background:#EEF2FF;color:#4F46E5;font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;" x-text="items.length"></span>
                            </div>
                            <button x-show="items.length>0" @click.stop="clear()"
                                    style="font-size:11px;color:#9CA3AF;font-weight:500;background:none;border:none;cursor:pointer;padding:0;"
                                    onmouseover="this.style.color='#EF4444'" onmouseout="this.style.color='#9CA3AF'">Clear all</button>
                        </div>
                        <div style="max-height:380px;overflow-y:auto;">
                            <template x-for="item in items" :key="item.url">
                                <a :href="item.url" @click="open=false"
                                   style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid #F9FAFB;text-decoration:none;background:#fff;transition:background .1s;"
                                   onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='#fff'">
                                    <div :style="'width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:'+iconBg(item.url)">
                                        <i :class="'fas '+pageIcon(item.url)" :style="'font-size:13px;color:'+iconColor(item.url)"></i>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="item.title"></p>
                                        <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;" x-text="timeAgo(item.at)"></p>
                                    </div>
                                    <i class="fas fa-arrow-up-right-from-square" style="color:#D1D5DB;font-size:10px;flex-shrink:0;"></i>
                                </a>
                            </template>
                            <div x-show="items.length===0" style="text-align:center;padding:32px 16px;">
                                <div style="width:44px;height:44px;background:#F3F4F6;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                                    <i class="fas fa-clock-rotate-left" style="color:#D1D5DB;font-size:18px;"></i>
                                </div>
                                <p style="font-size:12px;font-weight:600;color:#9CA3AF;margin:0;">No history yet</p>
                                <p style="font-size:11px;color:#C4C4C4;margin:4px 0 0;">Pages you visit will appear here</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Notification Bell --}}
                @if(!in_array('nav_notifications', $headerHidden))
                <div x-data="notifBell()" x-init="init()" @click.outside="open = false" style="position:relative;">

                    {{-- Bell button --}}
                    <button @click="open = !open" class="icon-btn" title="Notifications" style="position:relative;">
                        <i class="fas fa-bell" :class="count > 0 && !open ? 'fa-shake' : ''" style="animation-duration:2s;"></i>
                        <span x-show="count > 0"
                              style="position:absolute;top:-4px;right:-4px;min-width:16px;height:16px;background:#EF4444;border-radius:999px;font-size:9px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center;padding:0 3px;border:2px solid #fff;line-height:1;"
                              x-text="count > 9 ? '9+' : count">
                        </span>
                    </button>

                    {{-- Dropdown --}}
                    <div x-show="open" x-cloak
                         style="position:absolute;right:0;top:calc(100% + 8px);width:340px;background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,0.12);border:1px solid #F0F0F0;z-index:200;overflow:hidden;">

                        {{-- Header --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #F3F4F6;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-size:14px;font-weight:700;color:#111827;">Notifications</span>
                                <span x-show="count > 0"
                                      style="background:#EEF2FF;color:#4F46E5;font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;"
                                      x-text="count + ' new'">
                                </span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                {{-- Sound toggle --}}
                                <button @click.stop="toggleSound()"
                                        :title="soundEnabled ? 'Mute notification sound' : 'Enable notification sound'"
                                        style="width:28px;height:28px;border-radius:7px;border:1.5px solid #E5E7EB;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:12px;transition:all .15s;"
                                        :style="soundEnabled ? 'border-color:#6366F1;color:#6366F1;background:#EEF2FF;' : 'border-color:#E5E7EB;color:#9CA3AF;'">
                                    <i :class="soundEnabled ? 'fas fa-volume-high' : 'fas fa-volume-xmark'"></i>
                                </button>
                                {{-- Mark all read --}}
                                @if($notificationCount > 0)
                                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                                    @csrf
                                    <button type="submit" style="font-size:11px;color:#6366F1;font-weight:600;background:none;border:none;cursor:pointer;padding:0;">Mark all read</button>
                                </form>
                                @endif
                            </div>
                        </div>

                        {{-- List --}}
                        <div style="max-height:360px;overflow-y:auto;">
                            @forelse($notifications as $n)
                            @php
                                $nData  = $n->data;
                                $isRead = !is_null($n->read_at);
                                $palettes = ['indigo'=>['#EEF2FF','#4F46E5'],'green'=>['#F0FDF4','#16A34A'],'red'=>['#FEF2F2','#DC2626'],'amber'=>['#FFFBEB','#D97706']];
                                [$cbg,$cico] = $palettes[$nData['color'] ?? 'indigo'] ?? $palettes['indigo'];
                            @endphp
                            <a href="{{ route('notifications.read', $n->id) }}"
                               style="display:flex;align-items:flex-start;gap:11px;padding:11px 16px;border-bottom:1px solid #F9FAFB;text-decoration:none;background:{{ $isRead ? '#fff' : '#F8F8FF' }};"
                               onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='{{ $isRead ? '#fff' : '#F8F8FF' }}'">
                                <div style="width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:{{ $cbg }};">
                                    <i class="fas {{ $nData['icon'] ?? 'fa-bell' }}" style="font-size:13px;color:{{ $cico }};"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <p style="font-size:12px;font-weight:{{ $isRead ? '500' : '700' }};color:#111827;margin:0 0 2px;">{{ $nData['title'] ?? '' }}</p>
                                    <p style="font-size:11px;color:#6B7280;margin:0 0 3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $nData['message'] ?? '' }}</p>
                                    <p style="font-size:10px;color:#9CA3AF;margin:0;">{{ $n->created_at->diffForHumans() }}</p>
                                </div>
                                @unless($isRead)
                                <div style="width:7px;height:7px;border-radius:50%;background:#6366F1;flex-shrink:0;margin-top:5px;"></div>
                                @endunless
                            </a>
                            @empty
                            <div style="text-align:center;padding:32px 16px;">
                                <div style="width:44px;height:44px;background:#F3F4F6;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                                    <i class="fas fa-bell-slash" style="color:#D1D5DB;font-size:18px;"></i>
                                </div>
                                <p style="font-size:12px;color:#9CA3AF;margin:0;">You're all caught up!</p>
                            </div>
                            @endforelse
                        </div>

                        {{-- Sound status footer --}}
                        <div style="padding:8px 16px;border-top:1px solid #F3F4F6;display:flex;align-items:center;gap:6px;">
                            <i :class="soundEnabled ? 'fas fa-volume-high' : 'fas fa-volume-xmark'"
                               :style="soundEnabled ? 'color:#6366F1;' : 'color:#9CA3AF;'"
                               style="font-size:11px;"></i>
                            <span style="font-size:11px;color:#9CA3AF;" x-text="soundEnabled ? 'Sound alerts on' : 'Sound alerts off'"></span>
                            <button @click.stop="toggleSound()"
                                    style="margin-left:auto;font-size:11px;font-weight:600;background:none;border:none;cursor:pointer;padding:0;"
                                    :style="soundEnabled ? 'color:#DC2626;' : 'color:#6366F1;'"
                                    x-text="soundEnabled ? 'Mute' : 'Unmute'">
                            </button>
                        </div>

                    </div>
                </div>
                @endif
                {{-- Dev Mode pill (admin only) --}}
                @if(auth()->check() && auth()->user()->role === 'admin')
                @php $devOn = ($appSettings['developer_mode'] ?? '0') === '1'; @endphp
                <button id="global-dev-mode-btn" onclick="typeof toggleGlobalDevMode!=='undefined'&&toggleGlobalDevMode(this)"
                        title="{{ $devOn ? 'Developer Mode ON — click to disable' : 'Developer Mode OFF — click to enable' }}"
                        style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1.5px solid {{ $devOn ? '#C7D2FE' : '#E5E7EB' }};cursor:pointer;transition:all .2s;flex-shrink:0;{{ $devOn ? 'background:#EEF2FF;color:#4F46E5;' : 'background:#F9FAFB;color:#9CA3AF;' }}">
                    <i class="fas fa-code" style="font-size:9px;"></i>
                    <span id="global-dev-label">{{ $devOn ? 'Dev On' : 'Dev' }}</span>
                    <span id="global-dev-dot" style="width:6px;height:6px;border-radius:50%;flex-shrink:0;background:{{ $devOn ? '#4F46E5' : '#D1D5DB' }};"></span>
                </button>
                @endif

                {{-- Who's Online (admin/manager only) --}}
                @if(in_array(auth()->user()?->role, ['admin','manager']) && !in_array('nav_online_users', $headerHidden))
                <div x-data="onlineUsers()" x-init="init()" @click.outside="open=false" style="position:relative;">
                    <button @click="open=!open" class="icon-btn" title="Who's online" style="position:relative;">
                        <i class="fas fa-circle-dot" style="color:#10B981;font-size:13px;"></i>
                        <span x-show="count>0"
                              style="position:absolute;top:-4px;right:-4px;min-width:16px;height:16px;background:#10B981;border-radius:999px;font-size:9px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center;padding:0 3px;border:2px solid #fff;line-height:1;"
                              x-text="count>9?'9+':count"></span>
                    </button>
                    <div x-show="open" x-cloak
                         style="position:absolute;right:0;top:calc(100% + 8px);width:280px;background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.12);border:1px solid #F0F0F0;z-index:200;overflow:hidden;">
                        <div style="padding:12px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:14px;font-weight:700;color:#111827;">Who's Online</span>
                            <span x-show="count>0" style="background:#ECFDF5;color:#059669;font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;" x-text="count+' online'"></span>
                            <span x-show="count===0" style="font-size:11px;color:#9CA3AF;">No one online</span>
                        </div>
                        <div style="max-height:300px;overflow-y:auto;">
                            <template x-for="u in users" :key="u.id">
                                <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid #F9FAFB;">
                                    <div style="position:relative;flex-shrink:0;">
                                        <template x-if="u.avatar">
                                            <img :src="u.avatar" :alt="u.name" style="width:34px;height:34px;border-radius:50%;object-fit:cover;">
                                        </template>
                                        <template x-if="!u.avatar">
                                            <div style="width:34px;height:34px;border-radius:50%;background:#6366F1;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:600;" x-text="u.initials"></div>
                                        </template>
                                        <span :style="'position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;border:2px solid #fff;background:'+u.dot_color"></span>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="u.name"></p>
                                        <p style="font-size:11px;color:#9CA3AF;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="u.job_title||u.role"></p>
                                    </div>
                                    <span :style="'font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px;background:'+statusBg(u.presence_status)+';color:'+u.dot_color"
                                          x-text="u.presence_status.charAt(0).toUpperCase()+u.presence_status.slice(1)"></span>
                                    <a :href="'/messages?user='+u.id"
                                       style="width:28px;height:28px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;text-decoration:none;transition:background .15s;"
                                       onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='#EEF2FF'"
                                       title="Send message">
                                        <i class="fas fa-comment-dots" style="font-size:11px;color:#6366F1;"></i>
                                    </a>
                                </div>
                            </template>
                            <div x-show="users.length===0" style="text-align:center;padding:24px 16px;">
                                <i class="fas fa-moon" style="color:#D1D5DB;font-size:22px;"></i>
                                <p style="font-size:12px;color:#9CA3AF;margin:8px 0 0;">Everyone is offline</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- User avatar + status dot --}}
                <div x-data="statusPicker()" @click.outside="open=false" style="display:flex;align-items:center;gap:8px;margin-left:4px;position:relative;">
                    <div style="position:relative;cursor:pointer;" @click="open=!open" title="Change your status">
                        @if(auth()->user()?->avatarUrl())
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}"
                                 style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                        @else
                            <div class="user-avatar">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                        <span :style="'position:absolute;bottom:-1px;right:-1px;width:11px;height:11px;border-radius:50%;border:2px solid #fff;background:'+dotColor(current)"></span>
                    </div>
                    <div class="user-info" id="user-info-block" style="cursor:pointer;" @click="open=!open">
                        <p class="user-name">{{ auth()->user()->name ?? '' }}</p>
                        <p class="user-email" :style="'color:'+dotColor(current)+';font-weight:600;'" x-text="label(current)"></p>
                    </div>
                    {{-- Status dropdown --}}
                    <div x-show="open" x-cloak
                         style="position:absolute;top:calc(100% + 8px);right:0;width:190px;background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid #F0F0F0;z-index:300;overflow:hidden;">
                        <div style="padding:8px 12px;border-bottom:1px solid #F3F4F6;">
                            <p style="font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0;">Set status</p>
                        </div>
                        <template x-for="opt in options" :key="opt.value">
                            <button @click="setStatus(opt.value)"
                                    style="width:100%;display:flex;align-items:center;gap:10px;padding:9px 14px;background:none;border:none;cursor:pointer;text-align:left;transition:background .12s;"
                                    onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='none'"
                                    :style="current===opt.value?'background:#F5F3FF;':''">
                                <span :style="'width:10px;height:10px;border-radius:50%;flex-shrink:0;background:'+opt.color"></span>
                                <span style="font-size:13px;color:#374151;" x-text="opt.label"></span>
                                <i x-show="current===opt.value" class="fas fa-check" style="margin-left:auto;color:#6366F1;font-size:11px;"></i>
                            </button>
                        </template>
                        <div style="height:1px;background:#F3F4F6;margin:2px 0;"></div>
                        <button @click="open=false; document.getElementById('global-profile-modal').style.display='flex'"
                                style="width:100%;display:flex;align-items:center;gap:10px;padding:9px 14px;background:none;border:none;cursor:pointer;text-align:left;transition:background .12s;"
                                onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='none'">
                            <i class="fa fa-user-pen" style="font-size:12px;color:#6366F1;width:10px;text-align:center;"></i>
                            <span style="font-size:13px;color:#374151;">Edit Profile</span>
                        </button>
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

        @php $copyright = \App\Models\Setting::get('copyright', ''); @endphp
        @if($copyright)
        <div style="flex-shrink:0;text-align:center;padding:10px 16px;font-size:11.5px;color:#B0B7C3;border-top:1px solid #F3F4F6;background:#fff;">
            {{ $copyright }}
        </div>
        @endif

    </div>
</div>

{{-- ═══════════ GLOBAL EDIT PROFILE MODAL ═══════════ --}}
@php $profileUser = auth()->user(); @endphp
<div id="global-profile-modal"
     style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.5);"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,.2);width:100%;max-width:460px;overflow:hidden;">

        <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;">
                    <i class="fa fa-user-pen" style="color:#6366F1;font-size:14px;"></i>
                </div>
                <div>
                    <p style="font-size:15px;font-weight:700;color:#111827;margin:0;">Edit Profile</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:0;">Update your photo, email or password</p>
                </div>
            </div>
            <button onclick="document.getElementById('global-profile-modal').style.display='none'"
                    style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;">
                <i class="fa fa-times"></i>
            </button>
        </div>

        @if(session('profile_success'))
        <div style="margin:16px 24px 0;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:10px 14px;font-size:12px;color:#166534;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-circle-check"></i> {{ session('profile_success') }}
        </div>
        @endif
        @if($errors->has('current_password') || $errors->has('email') || $errors->has('password') || $errors->has('avatar'))
        <div style="margin:16px 24px 0;background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;font-size:12px;color:#DC2626;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-exclamation-circle"></i>
            {{ $errors->first('current_password') ?: ($errors->first('email') ?: ($errors->first('password') ?: $errors->first('avatar'))) }}
        </div>
        @endif

        <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data">
            @csrf
            <div style="padding:20px 24px;display:flex;flex-direction:column;gap:16px;">

                {{-- Avatar --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:10px;">Profile Picture</label>
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div style="width:64px;height:64px;flex-shrink:0;" id="gp-preview-wrap">
                            @if($profileUser->avatarUrl())
                                <img src="{{ $profileUser->avatarUrl() }}" id="gp-preview"
                                     style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #E5E7EB;">
                            @else
                                <div style="width:64px;height:64px;border-radius:50%;background:#6366F1;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;border:2px solid #E5E7EB;">
                                    {{ strtoupper(substr($profileUser->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <label for="gp-avatar-input"
                                   style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#EEF2FF;color:#4F46E5;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid #C7D2FE;">
                                <i class="fa fa-upload" style="font-size:10px;"></i> Choose Photo
                            </label>
                            <input type="file" id="gp-avatar-input" name="avatar" accept="image/*" style="display:none;"
                                   onchange="gpPreviewAvatar(this)">
                            <p style="font-size:11px;color:#9CA3AF;margin:5px 0 0;">JPG, PNG or WebP · max 2MB</p>
                        </div>
                    </div>
                </div>

                {{-- Email --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Email Address</label>
                    <div style="position:relative;">
                        <i class="fa fa-envelope" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
                        <input type="email" name="email" value="{{ old('email', $profileUser->email) }}"
                               style="width:100%;padding:10px 10px 10px 32px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                    </div>
                </div>

                {{-- Current Password --}}
                <div x-data="{show:false}">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Current Password <span style="color:#EF4444;">*</span></label>
                    <div style="position:relative;">
                        <i class="fa fa-lock" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;z-index:1;"></i>
                        <input :type="show?'text':'password'" name="current_password" required placeholder="Enter current password"
                               style="width:100%;padding:10px 32px 10px 32px;border:1.5px solid {{ $errors->has('current_password') ? '#FCA5A5' : '#E5E7EB' }};border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                        <button type="button" @click="show=!show" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;padding:0;">
                            <i :class="show?'fa fa-eye-slash':'fa fa-eye'" style="font-size:12px;"></i>
                        </button>
                    </div>
                </div>

                {{-- New Password --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">New Password <span style="font-weight:400;color:#9CA3AF;">(leave blank to keep current)</span></label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div x-data="{show:false}" style="position:relative;">
                            <i class="fa fa-lock" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;z-index:1;"></i>
                            <input :type="show?'text':'password'" name="password" placeholder="New password"
                                   style="width:100%;padding:10px 32px 10px 32px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                            <button type="button" @click="show=!show" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;padding:0;">
                                <i :class="show?'fa fa-eye-slash':'fa fa-eye'" style="font-size:12px;"></i>
                            </button>
                        </div>
                        <div x-data="{show:false}" style="position:relative;">
                            <i class="fa fa-lock" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;z-index:1;"></i>
                            <input :type="show?'text':'password'" name="password_confirmation" placeholder="Confirm"
                                   style="width:100%;padding:10px 32px 10px 32px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                            <button type="button" @click="show=!show" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;padding:0;">
                                <i :class="show?'fa fa-eye-slash':'fa fa-eye'" style="font-size:12px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <div style="padding:16px 24px;border-top:1px solid #F3F4F6;display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" onclick="document.getElementById('global-profile-modal').style.display='none'"
                        style="padding:9px 20px;border-radius:10px;font-size:13px;font-weight:600;background:#F3F4F6;color:#374151;border:none;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit"
                        style="padding:9px 20px;border-radius:10px;font-size:13px;font-weight:600;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 12px rgba(79,70,229,.3);">
                    <i class="fa fa-check" style="font-size:11px;margin-right:5px;"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function gpPreviewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('gp-preview-wrap').innerHTML =
                `<img src="${e.target.result}" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #E5E7EB;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
@if(session('profile_success') || $errors->has('current_password') || $errors->has('email') || $errors->has('password') || $errors->has('avatar'))
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('global-profile-modal').style.display = 'flex';
});
@endif
</script>

<script>
// Close sidebar when a nav link is clicked on mobile (Alpine v3 compatible)
document.querySelectorAll('.app-sidebar .nav-item').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            var overlay = document.querySelector('.sidebar-overlay.overlay-open');
            if (overlay) overlay.click();
        }
    });
});
</script>

<script>
function notifBell() {
    return {
        open:         false,
        count:        {{ $notificationCount }},
        soundEnabled: localStorage.getItem('notif_sound') !== 'false',
        _timer:       null,

        init() {
            this._timer = setInterval(() => this._poll(), 3000);

            // Immediate poll when the user returns to this tab
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this._poll();
            });
        },

        async _poll() {
            try {
                const res  = await fetch('{{ route("notifications.count") }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.count > this.count && this.soundEnabled) {
                    this.playSound();
                }
                this.count = data.count;
            } catch (e) { /* network error — silently skip */ }
        },

        playSound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                [[784, 0], [988, 0.18]].forEach(([freq, delay]) => {
                    const osc  = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    const t = ctx.currentTime + delay;
                    gain.gain.setValueAtTime(0, t);
                    gain.gain.linearRampToValueAtTime(0.28, t + 0.015);
                    gain.gain.exponentialRampToValueAtTime(0.001, t + 0.55);
                    osc.start(t);
                    osc.stop(t + 0.55);
                });
            } catch (e) { /* AudioContext not supported */ }
        },

        toggleSound() {
            this.soundEnabled = !this.soundEnabled;
            localStorage.setItem('notif_sound', this.soundEnabled);
            if (this.soundEnabled) this.playSound();
        },
    };
}
</script>

<script>
function statusPicker() {
    return {
        open:    false,
        current: '{{ auth()->user()?->presence_status ?? "offline" }}',
        options: [
            { value: 'online',  label: 'Online',  color: '#10B981' },
            { value: 'away',    label: 'Away',    color: '#F59E0B' },
            { value: 'busy',    label: 'Busy',    color: '#EF4444' },
            { value: 'offline', label: 'Offline', color: '#9CA3AF' },
        ],
        dotColor(s) {
            return { online:'#10B981', away:'#F59E0B', busy:'#EF4444', offline:'#9CA3AF' }[s] || '#9CA3AF';
        },
        label(s) {
            return s.charAt(0).toUpperCase() + s.slice(1);
        },
        async setStatus(val) {
            this.current = val;
            this.open    = false;
            await fetch('{{ route("user.presence") }}', {
                method:  'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' },
                body:    JSON.stringify({ status: val }),
            });
        },
    };
}

function navHistory() {
    return {
        open:  false,
        items: [],
        init() {
            // Record this page visit
            const KEY = '_navHist', MAX = 20;
            const hist  = JSON.parse(localStorage.getItem(KEY) || '[]');
            const title = document.title.replace(/\s*[\-–|].*$/, '').trim() || document.title;
            const entry = { url: location.href, title, at: Date.now() };
            const deduped = hist.filter(h => h.url !== entry.url);
            deduped.unshift(entry);
            localStorage.setItem(KEY, JSON.stringify(deduped.slice(0, MAX)));
            this.load();
        },
        load() {
            const raw = JSON.parse(localStorage.getItem('_navHist') || '[]');
            // Exclude current page from the visible list
            this.items = raw.filter(h => h.url !== location.href);
        },
        clear() {
            const raw     = JSON.parse(localStorage.getItem('_navHist') || '[]');
            const current = raw.find(h => h.url === location.href);
            localStorage.setItem('_navHist', JSON.stringify(current ? [current] : []));
            this.items = [];
        },
        timeAgo(ts) {
            const s = Math.floor((Date.now() - ts) / 1000);
            if (s < 60)   return 'just now';
            const m = Math.floor(s / 60);
            if (m < 60)   return m + 'm ago';
            const h = Math.floor(m / 60);
            if (h < 24)   return h + 'h ago';
            return Math.floor(h / 24) + 'd ago';
        },
        pageIcon(url) {
            if (/\/tasks\/\d/.test(url))    return 'fa-list-check';
            if (/\/projects\/\d/.test(url)) return 'fa-folder';
            if (/\/messages/.test(url))     return 'fa-comment-dots';
            if (/\/reports/.test(url))      return 'fa-chart-bar';
            if (/\/users\/\d/.test(url))    return 'fa-user';
            if (/\/settings/.test(url))     return 'fa-gear';
            if (/\/calendar/.test(url))     return 'fa-calendar';
            if (/\/activities/.test(url))   return 'fa-timeline';
            return 'fa-house';
        },
        iconBg(url) {
            if (/\/tasks\/\d/.test(url))    return '#EEF2FF';
            if (/\/projects\/\d/.test(url)) return '#FEF3C7';
            if (/\/messages/.test(url))     return '#ECFDF5';
            if (/\/reports/.test(url))      return '#F0F9FF';
            return '#F3F4F6';
        },
        iconColor(url) {
            if (/\/tasks\/\d/.test(url))    return '#6366F1';
            if (/\/projects\/\d/.test(url)) return '#D97706';
            if (/\/messages/.test(url))     return '#059669';
            if (/\/reports/.test(url))      return '#0EA5E9';
            return '#6B7280';
        },
    };
}

function onlineUsers() {
    return {
        open:  false,
        count: 0,
        users: [],
        _timer: null,
        init() {
            this._fetch();
            this._timer = setInterval(() => this._fetch(), 15000);
        },
        async _fetch() {
            try {
                const res  = await fetch('{{ route("online.users") }}', { headers:{ 'X-Requested-With':'XMLHttpRequest' } });
                if (!res.ok) return;
                this.users = await res.json();
                this.count = this.users.length;
            } catch(e) {}
        },
        statusBg(s) {
            return { online:'#ECFDF5', away:'#FFFBEB', busy:'#FEF2F2', offline:'#F3F4F6' }[s] || '#F3F4F6';
        },
    };
}
</script>

@stack('scripts')

@auth
@if(auth()->user()->role === 'admin')
<style>@keyframes devpulse { 0%,100%{opacity:1} 50%{opacity:.3} }</style>
<script>
(function () {
    window.DEV_MODE = {{ ($appSettings['developer_mode'] ?? '0') === '1' ? 'true' : 'false' }};
    const _DEV_TOGGLE_URL   = '{{ route('admin.settings.dev-mode') }}';
    const _DEV_EL_URL       = '{{ route('admin.settings.elements.toggle') }}';
    const _DEV_SETTINGS_URL = '{{ route('admin.settings.index') }}?tab=developer';
    const _DEV_CSRF         = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    let _devFooter = null;

    function _devToast(msg) {
        var t = document.createElement('div');
        t.innerHTML = '<i class="fas fa-eye-slash" style="margin-right:6px;font-size:11px;"></i>' + msg;
        t.style.cssText = 'position:fixed;bottom:52px;right:24px;z-index:99999;background:#1E1B4B;color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;box-shadow:0 6px 24px rgba(0,0,0,.2);opacity:0;transform:translateY(8px);transition:opacity .2s,transform .2s;';
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.style.opacity='1'; t.style.transform='translateY(0)'; });
        setTimeout(function () { t.style.opacity='0'; t.style.transform='translateY(8px)'; setTimeout(function () { t.remove(); }, 220); }, 2500);
    }

    function _devInitOverlays() {
        document.querySelectorAll('[data-dev-key]').forEach(function (el) {
            if (el.closest('.dev-ow')) return;
            var key     = el.getAttribute('data-dev-key');
            var label   = el.getAttribute('data-dev-label') || key;
            var isExtra = el.getAttribute('data-dev-type') === 'extra';

            var wrap = document.createElement('div');
            wrap.className = 'dev-ow';
            wrap.style.cssText = 'position:relative;';
            el.parentNode.insertBefore(wrap, el);
            wrap.appendChild(el);

            var ov = document.createElement('div');
            ov.style.cssText = 'position:absolute;inset:0;z-index:50;border-radius:inherit;cursor:pointer;transition:background .15s,outline .15s;outline:2px dashed transparent;';

            var badge = document.createElement('div');
            badge.innerHTML = '<i class="fas fa-eye-slash" style="font-size:10px;margin-right:5px;"></i>' + label;
            badge.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(79,70,229,.92);color:#fff;padding:7px 16px;border-radius:8px;font-size:12px;font-weight:700;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .15s;box-shadow:0 4px 16px rgba(79,70,229,.35);';
            ov.appendChild(badge);
            wrap.appendChild(ov);

            ov.addEventListener('mouseenter', function () { ov.style.background='rgba(79,70,229,.08)'; ov.style.outline='2px dashed #6366F1'; badge.style.opacity='1'; });
            ov.addEventListener('mouseleave', function () { ov.style.background=''; ov.style.outline='2px dashed transparent'; badge.style.opacity='0'; });
            ov.addEventListener('click', function () {
                ov.style.pointerEvents='none';
                badge.innerHTML='<i class="fas fa-spinner fa-spin" style="font-size:10px;margin-right:5px;"></i>Hiding…';
                badge.style.opacity='1';
                fetch(_DEV_EL_URL, { method:'POST', headers:{'X-CSRF-TOKEN':_DEV_CSRF,'Content-Type':'application/json'}, body:JSON.stringify({key:key, action:isExtra?'remove':'hide'}) })
                    .then(function(r){return r.json();})
                    .then(function(){
                        wrap.style.transition='opacity .3s,transform .3s'; wrap.style.opacity='0'; wrap.style.transform='scale(.97)';
                        setTimeout(function(){ wrap.remove(); _devToast(label+' hidden'); }, 320);
                    })
                    .catch(function(){ ov.style.pointerEvents=''; badge.style.opacity='0'; });
            });
        });
    }

    function _devDestroyOverlays() {
        document.querySelectorAll('.dev-ow').forEach(function (wrap) {
            var el = wrap.querySelector('[data-dev-key]');
            if (el) { wrap.replaceWith(el); } else { wrap.remove(); }
        });
    }

    function _devShowFooter() {
        if (document.getElementById('dev-mode-global-footer')) return;
        _devFooter = document.createElement('div');
        _devFooter.id = 'dev-mode-global-footer';
        _devFooter.style.cssText = 'position:fixed;bottom:0;left:0;right:0;background:#1E1B4B;color:#C7D2FE;font-size:12px;font-weight:600;padding:8px 20px;z-index:88888;display:flex;align-items:center;justify-content:space-between;gap:12px;';
        _devFooter.innerHTML =
            '<div style="display:flex;align-items:center;gap:10px;"><span style="width:8px;height:8px;border-radius:50%;background:#6366F1;display:inline-block;animation:devpulse 1.5s infinite;"></span><span>Developer Mode Active — hover over any dashboard section to hide it</span></div>' +
            '<a href="'+_DEV_SETTINGS_URL+'" style="display:flex;align-items:center;gap:6px;padding:5px 14px;background:rgba(99,102,241,.3);color:#A5B4FC;border-radius:7px;font-size:11px;text-decoration:none;border:1px solid rgba(99,102,241,.4);"><i class="fas fa-gear"></i> Manage Sections</a>';
        document.body.appendChild(_devFooter);
    }

    function _devHideFooter() {
        var f = document.getElementById('dev-mode-global-footer');
        if (f) { f.remove(); _devFooter = null; }
    }

    function _devActivate() {
        _devShowFooter();
        _devInitOverlays();
        var banner = document.getElementById('dev-mode-banner');
        if (banner) banner.style.display = 'flex';
    }

    function _devDeactivate() {
        _devHideFooter();
        _devDestroyOverlays();
        var banner = document.getElementById('dev-mode-banner');
        if (banner) banner.style.display = 'none';
    }

    function _devUpdatePill(on) {
        var btn = document.getElementById('global-dev-mode-btn');
        if (!btn) return;
        btn.style.background  = on ? '#EEF2FF' : '#F9FAFB';
        btn.style.color       = on ? '#4F46E5' : '#9CA3AF';
        btn.style.borderColor = on ? '#C7D2FE' : '#E5E7EB';
        btn.title = on ? 'Developer Mode ON — click to disable' : 'Developer Mode OFF — click to enable';
        var dot = document.getElementById('global-dev-dot');
        if (dot) dot.style.background = on ? '#4F46E5' : '#D1D5DB';
        var lbl = document.getElementById('global-dev-label');
        if (lbl) lbl.textContent = on ? 'Dev On' : 'Dev';
    }

    window.toggleGlobalDevMode = function (btn) {
        btn.disabled = true;
        fetch(_DEV_TOGGLE_URL, { method:'POST', headers:{'X-CSRF-TOKEN':_DEV_CSRF,'Content-Type':'application/json'} })
            .then(function(r){return r.json();})
            .then(function(d){
                btn.disabled = false;
                window.DEV_MODE = d.developer_mode;
                _devUpdatePill(d.developer_mode);
                window.dispatchEvent(new CustomEvent('devmode-changed', { detail: { on: d.developer_mode } }));
                try { localStorage.setItem('dev_mode', d.developer_mode ? '1' : '0'); } catch(e){}
                if (d.developer_mode) _devActivate(); else _devDeactivate();
            })
            .catch(function(){ btn.disabled = false; });
    };

    window._devModeChanged = function (on) {
        window.DEV_MODE = on;
        _devUpdatePill(on);
        if (on) _devActivate(); else _devDeactivate();
    };

    window.addEventListener('storage', function (e) {
        if (e.key !== 'dev_mode') return;
        var on = e.newValue === '1';
        if (on === window.DEV_MODE) return;
        window.DEV_MODE = on;
        _devUpdatePill(on);
        window.dispatchEvent(new CustomEvent('devmode-changed', { detail: { on: on } }));
        if (on) _devActivate(); else _devDeactivate();
    });

    function _onReady() { if (window.DEV_MODE) _devActivate(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', _onReady);
    else _onReady();
})();
</script>
@endif
@endauth

</body>
</html>

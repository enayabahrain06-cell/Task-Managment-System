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
                <div class="topbar-search" id="topbar-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="What are you looking for?">
                </div>
                {{-- Notification Bell --}}
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
                <div style="display:flex;align-items:center;gap:8px;margin-left:4px;">
                    @if(auth()->user()?->avatarUrl())
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}"
                             style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    @else
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                    <div class="user-info" id="user-info-block">
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

        @php $copyright = \App\Models\Setting::get('copyright', ''); @endphp
        @if($copyright)
        <div style="flex-shrink:0;text-align:center;padding:10px 16px;font-size:11.5px;color:#B0B7C3;border-top:1px solid #F3F4F6;background:#fff;">
            {{ $copyright }}
        </div>
        @endif

    </div>
</div>

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
        _es:          null,
        _fallback:    null,

        init() {
            this._connect();
            // Re-poll immediately when the user returns to this tab
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this._poll();
            });
        },

        _connect() {
            if (!window.EventSource) { this._startFallback(); return; }

            this._es = new EventSource('{{ route("notifications.stream") }}');

            this._es.onmessage = (e) => {
                const data = JSON.parse(e.data);
                if (data.reconnect) {
                    // Server asked us to reconnect — EventSource does this automatically
                    return;
                }
                if (data.count > this.count && this.soundEnabled) {
                    this.playSound();
                }
                this.count = data.count;
            };

            this._es.onerror = () => {
                // SSE failed — close and fall back to polling
                this._es.close();
                this._es = null;
                this._startFallback();
            };
        },

        _startFallback() {
            if (this._fallback) return;
            this._fallback = setInterval(() => this._poll(), 5000);
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

@stack('scripts')
</body>
</html>

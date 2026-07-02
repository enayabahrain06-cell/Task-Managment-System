<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $appSettings['app_name'] ?? config('app.name', 'Dash') }}</title>
    @if(!empty($appSettings['favicon_path']))
    <link rel="icon" type="image/png" href="{{ Storage::url($appSettings['favicon_path']) }}">
    @endif
    <link rel="stylesheet" href="/css/inter.css">
    <link rel="stylesheet" href="/css/fa-all.min.css">
    <script defer src="/js/alpine.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
        $loginBgType   = $appSettings['login_bg_type'] ?? 'gradient';
        $showTeamLayout = request()->routeIs('login')
            && ($appSettings['login_show_doodles'] ?? '1') === '1';

        /* Pull up to 4 active users that have a real profile photo */
        $teamFrameUsers = $showTeamLayout
            ? \App\Models\User::where('status', 'active')
                ->whereNotNull('avatar')->where('avatar', '!=', '')
                ->take(4)->get(['id','name','avatar','job_title'])
            : collect();

        $frameMeta = [
            ['icon' => 'fa-briefcase',    'title' => 'Project Lead',     'desc' => 'Leads with vision',  'color' => '#6366F1'],
            ['icon' => 'fa-palette',      'title' => 'Creative Designer','desc' => 'Designs the future', 'color' => '#8B5CF6'],
            ['icon' => 'fa-code',         'title' => 'Developer',        'desc' => 'Builds with code',   'color' => '#6366F1'],
            ['icon' => 'fa-chart-simple', 'title' => 'Strategist',       'desc' => 'Plans for success',  'color' => '#8B5CF6'],
        ];
        $teamName = strtoupper($appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name','Our'));
    @endphp

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0; padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #fce4ec 0%, #ede6f8 40%, #dce8fb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            position: relative;
            overflow: hidden;
        }

        @if($showTeamLayout)
        body { background: linear-gradient(135deg,#F5F3FE 0%,#ECE9FB 45%,#E8ECFC 100%) !important; padding: 96px 16px 96px !important; }
        @endif

        /* ── Orbit animations (login deco column) ── */
        @keyframes orbit1 { 0%{transform:rotate(0deg) translateX(90px) rotate(0deg)} 100%{transform:rotate(360deg) translateX(90px) rotate(-360deg)} }
        @keyframes orbit2 { 0%{transform:rotate(60deg) translateX(110px) rotate(-60deg)} 100%{transform:rotate(420deg) translateX(110px) rotate(-420deg)} }
        @keyframes orbit3 { 0%{transform:rotate(120deg) translateX(75px) rotate(-120deg)} 100%{transform:rotate(480deg) translateX(75px) rotate(-480deg)} }
        @keyframes orbit4 { 0%{transform:rotate(200deg) translateX(100px) rotate(-200deg)} 100%{transform:rotate(560deg) translateX(100px) rotate(-560deg)} }
        @keyframes orbit5 { 0%{transform:rotate(280deg) translateX(120px) rotate(-280deg)} 100%{transform:rotate(640deg) translateX(120px) rotate(-640deg)} }
        @keyframes float  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
        @keyframes pulse-ring { 0%{transform:scale(1);opacity:0.4} 100%{transform:scale(1.6);opacity:0} }

        .orbit-icon { position:absolute; top:50%; left:50%; width:38px; height:38px; margin:-19px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 15px rgba(0,0,0,0.15); }
        .o1 { animation:orbit1 8s linear infinite; }
        .o2 { animation:orbit2 12s linear infinite; }
        .o3 { animation:orbit3 9s linear infinite reverse; }
        .o4 { animation:orbit4 11s linear infinite; }
        .o5 { animation:orbit5 10s linear infinite reverse; }

        .pulse-ring { position:absolute; width:100%; height:100%; border-radius:50%; border:2px solid rgba(255,255,255,0.3); animation: pulse-ring 2.5s ease-out infinite; }
        .pulse-ring2 { animation-delay:0.8s; }
        .pulse-ring3 { animation-delay:1.6s; }

        @if($showTeamLayout)
        /* ── Animation keyframes ── */
        @keyframes bannerSlide {
            from { opacity:0; transform:translate(-50%,-22px); }
            to   { opacity:1; transform:translate(-50%,0); }
        }
        @keyframes frameFloat0 {
            0%,100% { transform:rotate(-5deg) translateY(0px); }
            50%      { transform:rotate(-5deg) translateY(-10px); }
        }
        @keyframes frameFloat1 {
            0%,100% { transform:rotate(5deg) translateY(0px); }
            50%      { transform:rotate(5deg) translateY(-10px); }
        }
        @keyframes frameFloat2 {
            0%,100% { transform:rotate(5deg) translateY(0px); }
            50%      { transform:rotate(5deg) translateY(-10px); }
        }
        @keyframes frameFloat3 {
            0%,100% { transform:rotate(-5deg) translateY(0px); }
            50%      { transform:rotate(-5deg) translateY(-10px); }
        }
        @keyframes blobPulse {
            0%,100% { transform:translate(-50%,-50%) scale(1);   opacity:0.65; }
            50%      { transform:translate(-50%,-50%) scale(1.18); opacity:0.45; }
        }
        @keyframes popIn {
            0%   { opacity:0; transform:scale(0.4); }
            70%  { transform:scale(1.08); }
            100% { opacity:1; transform:scale(1); }
        }
        @keyframes popInCentered {
            0%   { opacity:0; transform:translateX(-50%) scale(0.4); }
            70%  { transform:translateX(-50%) scale(1.08); }
            100% { opacity:1; transform:translateX(-50%) scale(1); }
        }
        @keyframes ringPulse {
            0%,100% { transform:scale(1);   opacity:0.5; }
            50%      { transform:scale(1.25); opacity:0.2; }
        }

        /* ── Background decoration (blobs / dot-grids / rings) ── */
        .bg-deco { position:fixed; z-index:1; pointer-events:none; }
        .bg-blob-tl  { top:-90px; left:-90px; width:280px; height:280px; border-radius:50%; background:radial-gradient(circle,rgba(129,140,248,0.38),rgba(129,140,248,0) 70%); }
        .bg-blob-br1 { bottom:-100px; right:-60px; width:260px; height:260px; border-radius:50%; background:radial-gradient(circle,rgba(167,139,250,0.35),rgba(167,139,250,0) 70%); }
        .bg-blob-br2 { bottom:60px; right:150px; width:150px; height:150px; border-radius:50%; background:radial-gradient(circle,rgba(196,181,253,0.4),rgba(196,181,253,0) 70%); }
        .bg-dots-tr  { top:16px; right:210px; width:112px; height:88px; background-image:radial-gradient(circle,rgba(99,102,241,0.4) 1.6px,transparent 1.6px); background-size:14px 14px; }
        .bg-dots-bl  { bottom:330px; left:56px; width:88px; height:70px; background-image:radial-gradient(circle,rgba(99,102,241,0.32) 1.6px,transparent 1.6px); background-size:13px 13px; }
        .bg-ring     { border-radius:50%; border:2px solid rgba(99,102,241,0.35); animation:ringPulse 3.5s ease-in-out infinite; }
        .bg-ring-1   { top:80px; left:370px; width:30px; height:30px; }
        .bg-ring-2   { bottom:330px; right:60px; width:22px; height:22px; animation-delay:1.2s; }

        @media (max-width: 1160px) { .bg-blob-br2, .bg-dots-tr, .bg-dots-bl, .bg-ring-1 { display:none !important; } }

        /* ── Hero heading ── */
        .team-hero {
            position: fixed;
            top: 26px; left: 50%;
            display: flex; flex-direction: column; align-items: center;
            z-index: 200;
            pointer-events: none;
            padding: 22px 36px 20px;
            max-width: calc(100vw - 40px);
            animation: bannerSlide 0.7s cubic-bezier(.22,.68,0,1.2) both;
        }
        .team-hero-clickable {
            pointer-events: auto;
            cursor: pointer;
            border-radius: 24px;
            transition: transform 0.15s, background 0.15s;
        }

        /* ── Team artwork popup ── */
        [x-cloak] { display: none !important; }
        .team-artwork-backdrop {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(17,24,39,0.6);
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }
        .team-artwork-card {
            position: relative;
            background: #fff;
            border-radius: 20px;
            padding: 16px;
            max-width: 680px; width: 100%; max-height: 90vh;
            overflow: auto;
            box-shadow: 0 32px 80px rgba(0,0,0,0.3);
        }
        .team-artwork-close {
            position: absolute; top: 12px; right: 12px;
            width: 32px; height: 32px; border-radius: 50%;
            background: #F3F4F6; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: #374151; z-index: 2;
        }
        .team-artwork-img {
            display: block; border-radius: 12px; margin: 0 auto;
            max-width: 100%; max-height: 60vh; width: auto; height: auto;
        }
        .team-artwork-stage {
            position: relative;
            min-height: 200px; max-height: 60vh;
            display: flex; align-items: center; justify-content: center;
        }
        .team-artwork-stage.is-multi { cursor: pointer; }
        .team-artwork-stage.is-multi .team-artwork-img { user-select: none; -webkit-user-drag: none; }
        .team-artwork-video { background: #111827; }
        .team-artwork-nav {
            position: absolute; top: 50%; transform: translateY(-50%);
            width: 38px; height: 38px; border-radius: 50%;
            background: rgba(17,24,39,0.55); border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px; z-index: 1;
            transition: background 0.15s;
        }
        .team-artwork-nav:hover { background: rgba(17,24,39,0.8); }
        .team-artwork-nav.prev { left: 10px; }
        .team-artwork-nav.next { right: 10px; }
        .team-artwork-counter {
            background: #F3F4F6; color: #6B7280;
            font-size: 11px; font-weight: 600; padding: 3px 10px;
            border-radius: 999px; letter-spacing: .02em;
        }
        .team-artwork-dots { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 12px; }
        .team-artwork-dotrow { display: flex; justify-content: center; gap: 6px; }
        .team-artwork-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #D1D5DB; border: none; cursor: pointer; padding: 0;
            transition: background 0.15s, transform 0.15s;
        }
        .team-artwork-dot.active { background: #6366F1; transform: scale(1.25); }
        .team-hero-icon {
            width: 52px; height: 52px; border-radius: 16px;
            background: linear-gradient(135deg,#A5B4FC,#6366F1);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
            animation: iconGlowPulse 3.2s ease-in-out infinite;
        }
        @keyframes iconGlowPulse {
            0%,100% { box-shadow: 0 10px 24px rgba(99,102,241,0.35), 0 0 0 0 rgba(165,180,252,0.5); }
            50%      { box-shadow: 0 10px 24px rgba(99,102,241,0.35), 0 0 0 8px rgba(165,180,252,0.22); }
        }
        .team-hero-title {
            margin: 0;
            font-size: clamp(24px, 3.6vw, 46px);
            font-weight: 800;
            letter-spacing: 0.4px;
            color: #1E1B4B;
            text-align: center;
            white-space: nowrap;
        }
        .team-hero-title .accent { color: #6366F1; }
        .team-hero-tagline { font-size: 13.5px; color: #6B7280; margin: 8px 0 0; text-align: center; }
        .team-hero-rule {
            width: 52px; height: 3px; border-radius: 2px; margin-top: 12px;
            background: linear-gradient(90deg,#6366F1,#C7D2FE,#6366F1);
            background-size: 200% 100%;
            transform-origin: center;
            animation: ruleGrow 0.5s 0.55s cubic-bezier(.22,.68,0,1.2) both,
                       ruleShimmer 2.4s linear 1.1s infinite;
        }
        .team-hero-about-link {
            display: flex; align-items: center; gap: 6px;
            margin-top: 12px;
            pointer-events: auto;
            font-size: 11.5px; font-weight: 700; letter-spacing: 0.2px;
            color: #fff; text-decoration: none;
            background: linear-gradient(135deg,#818CF8,#6366F1);
            padding: 7px 16px; border-radius: 999px;
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .team-hero-about-link:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(99,102,241,0.4); }
        .team-hero-about-link i { font-size: 10px; }
        @keyframes ruleGrow {
            from { transform: scaleX(0); opacity: 0; }
            to   { transform: scaleX(1); opacity: 1; }
        }
        @keyframes ruleShimmer {
            from { background-position: 0% 0; }
            to   { background-position: 200% 0; }
        }

        /* ── Team frames ── */
        .team-frame {
            position: fixed;
            z-index: 100;
            pointer-events: none;
        }
        /* Inner wrapper handles the one-shot pop-in entrance so it doesn't fight the
           outer element's continuous float/tilt animation over the shared `transform` property
           (two animations on one element targeting the same property: the later one — with
           fill-mode both — permanently wins and silently kills the other after it finishes). */
        .team-frame-pop {
            width: 100%; height: 100%; position: relative;
        }
        .team-frame-blob {
            position: absolute;
            border-radius: 50%;
            z-index: 0;
            animation: blobPulse 4s ease-in-out infinite;
        }
        .team-frame-inner {
            position: relative; z-index: 1;
            border: 3px solid #111;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 5px 5px 0 #111;
            background: #fff;
            width: 100%; height: 100%;
        }
        .team-frame img {
            width: 100%; height: 100%;
            object-fit: cover; display: block;
        }
        .team-frame-initials {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-size: 40px; font-weight: 800; color: #fff;
        }
        .tf-0 { top:60px;    left:36px;  width:150px; height:186px; animation: frameFloat0 4.2s ease-in-out infinite; }
        .tf-1 { top:60px;    right:36px; width:150px; height:186px; animation: frameFloat1 4.2s ease-in-out infinite; }
        .tf-2 { bottom:104px; left:36px;  width:150px; height:186px; animation: frameFloat2 4.2s ease-in-out infinite; }
        .tf-3 { bottom:104px; right:36px; width:150px; height:186px; animation: frameFloat3 4.2s ease-in-out infinite; }

        /* ── Role cards (below/above each frame) ── */
        .team-role-card {
            position: fixed;
            z-index: 100;
            width: 186px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 14px 34px rgba(79,70,229,0.16);
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            pointer-events: none;
        }
        .team-role-icon {
            width: 34px; height: 34px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 13px; flex-shrink: 0;
        }
        .team-role-title { font-size: 12.5px; font-weight: 700; color: #111827; margin: 0; line-height: 1.25; }
        .team-role-desc  { font-size: 10.5px; color: #9CA3AF; margin: 1px 0 0; line-height: 1.2; }
        .team-role-0 { top:260px;  left:36px; }
        .team-role-1 { top:260px;  right:36px; }
        .team-role-2 { bottom:26px; left:36px; }
        .team-role-3 { bottom:26px; right:36px; }

        /* ── Bottom pill — fixed footer bar, centered horizontally ── */
        .team-bottom-pill {
            position: fixed;
            bottom: 18px; left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border-radius: 30px;
            padding: 10px 20px;
            display: flex; align-items: center; gap: 8px;
            box-shadow: 0 12px 28px rgba(0,0,0,0.10);
            font-size: 13px; color: #374151;
            z-index: 150;
            pointer-events: none;
            white-space: nowrap;
            max-width: calc(100vw - 40px);
            animation: popInCentered 0.6s 1s cubic-bezier(.22,.68,0,1.15) both;
        }
        .team-bottom-pill i { color: #F59E0B; flex-shrink: 0; }
        .team-bottom-pill .accent { color: #6366F1; font-weight: 700; }

        @media (max-width: 1160px) { .team-frame, .team-role-card { display:none !important; } }
        @media (max-height: 760px)  { .team-bottom-pill { display:none !important; } }
        @media (max-width: 600px)   {
            .team-hero       { padding:16px 22px 15px; border-radius:20px; }
            .team-hero-title { font-size:20px; letter-spacing:0; white-space:normal; }
            .team-bottom-pill { white-space:normal; text-align:center; }
        }
        @endif
    </style>

    {{-- ── Background override from branding settings ── --}}
    @if($loginBgType === 'color')
    <style>body { background: {!! e($appSettings['login_bg_color'] ?? '#e8eaf6') !!} !important; }</style>
    @elseif($loginBgType === 'image' && !empty($appSettings['login_bg_image']))
    @php
        $bgUrl     = \Illuminate\Support\Facades\Storage::url($appSettings['login_bg_image']);
        $bgPos     = $appSettings['login_bg_position']   ?? 'center center';
        $bgSize    = $appSettings['login_bg_size']        ?? 'cover';
        $bgAttach  = $appSettings['login_bg_attachment']  ?? 'fixed';
        $bgOverlay = max(0, min(80, (int)($appSettings['login_bg_overlay'] ?? 0)));
        $overlayAlpha = number_format($bgOverlay / 100, 2);
        $isVideoBg = (bool) preg_match('/\.(mp4|webm|mov|m4v)$/i', $appSettings['login_bg_image']);
        $bgObjectFit = match ($bgSize) { 'contain' => 'contain', 'auto' => 'none', default => 'cover' };
    @endphp
    @if($isVideoBg)
    <style>
        body { background: #0f172a !important; }
        .login-bg-video {
            position: {{ $bgAttach === 'scroll' ? 'absolute' : 'fixed' }};
            inset: 0; width: 100%; height: 100%; z-index: -2;
            object-fit: {{ $bgObjectFit }}; object-position: {{ e($bgPos) }};
        }
        .login-bg-video-overlay {
            position: {{ $bgAttach === 'scroll' ? 'absolute' : 'fixed' }};
            inset: 0; z-index: -1; pointer-events: none;
            background: rgba(0,0,0,{{ $overlayAlpha }});
        }
    </style>
    @else
    <style>
    body {
        background-image: linear-gradient(rgba(0,0,0,{{ $overlayAlpha }}),rgba(0,0,0,{{ $overlayAlpha }})), url('{!! e($bgUrl) !!}') !important;
        background-size: {{ e($bgSize) }}, {{ e($bgSize) }} !important;
        background-position: {{ e($bgPos) }}, {{ e($bgPos) }} !important;
        background-attachment: {{ e($bgAttach) }}, {{ e($bgAttach) }} !important;
        background-repeat: no-repeat, no-repeat !important;
    }
    </style>
    @endif
    @endif
</head>
<body>

@if($loginBgType === 'image' && !empty($appSettings['login_bg_image']) && ($isVideoBg ?? false))
<video class="login-bg-video" src="{{ e($bgUrl) }}" autoplay muted loop playsinline></video>
<div class="login-bg-video-overlay"></div>
@endif

@if($showTeamLayout)

{{-- ── Background decoration ── --}}
<div class="bg-deco bg-blob-tl"></div>
<div class="bg-deco bg-blob-br1"></div>
<div class="bg-deco bg-blob-br2"></div>
<div class="bg-deco bg-dots-tr"></div>
<div class="bg-deco bg-dots-bl"></div>
<div class="bg-deco bg-ring bg-ring-1"></div>
<div class="bg-deco bg-ring bg-ring-2"></div>

{{-- ── Hero heading — clickable to reveal team artwork, if any is uploaded ── --}}
@php
    $artworkUrls = collect($appSettings['login_team_artwork'] ?? [])
        ->map(fn($path) => Storage::url($path))
        ->values();
    $hasArtwork = $artworkUrls->isNotEmpty();
@endphp
<div @if($hasArtwork) x-data="{
        artworkOpen: false, artworkIndex: 0, artworkTimer: null,
        artworkImgs: {{ json_encode($artworkUrls) }},
        artworkNext() { this.artworkIndex = (this.artworkIndex + 1) % this.artworkImgs.length },
        artworkPrev() { this.artworkIndex = (this.artworkIndex - 1 + this.artworkImgs.length) % this.artworkImgs.length },
        artworkGoto(i) { this.artworkIndex = i },
        artworkIsVideo(url) { return /\.(mp4|webm|mov|m4v)(\?.*)?$/i.test(url) },
        artworkRestart() {
            clearInterval(this.artworkTimer);
            if (this.artworkImgs.length > 1 && !this.artworkIsVideo(this.artworkImgs[this.artworkIndex])) {
                this.artworkTimer = setInterval(() => this.artworkNext(), 4000);
            }
        },
        artworkStop() { clearInterval(this.artworkTimer) }
    }" @endif>
    <div class="team-hero">
        <div class="team-hero-icon"><i class="fa fa-users" style="color:#fff;font-size:20px;"></i></div>
        <h1 class="team-hero-title">THE <span class="accent">{{ $teamName }}</span> TEAM</h1>
        <p class="team-hero-tagline">{{ $appSettings['login_hero_tagline'] ?? 'Together we build. Together we achieve.' }}</p>
        <div class="team-hero-rule"></div>
        @if(($appSettings['about_page_enabled'] ?? '1') === '1')
        <a href="{{ route('about') }}" class="team-hero-about-link" target="_blank" rel="noopener">
            Meet the full team <i class="fa fa-arrow-right"></i>
        </a>
        @endif
    </div>

    @if($hasArtwork)
    <div x-show="artworkOpen" x-cloak class="team-artwork-backdrop" @click.self="artworkOpen = false; artworkStop()"
         @keydown.window.escape="artworkOpen = false; artworkStop()"
         @keydown.window.arrow-left="if (artworkOpen) { artworkPrev(); artworkRestart() }"
         @keydown.window.arrow-right="if (artworkOpen) { artworkNext(); artworkRestart() }">
        <div class="team-artwork-card">
            <button type="button" class="team-artwork-close" @click="artworkOpen = false; artworkStop()">
                <i class="fa fa-times"></i>
            </button>
            <div class="team-artwork-stage" :class="{ 'is-multi': artworkImgs.length > 1 && !artworkIsVideo(artworkImgs[artworkIndex]) }"
                 @mouseenter="artworkStop()" @mouseleave="artworkRestart()"
                 @click="if (artworkImgs.length > 1 && !artworkIsVideo(artworkImgs[artworkIndex])) { ($event.offsetX > $event.currentTarget.offsetWidth / 2) ? artworkNext() : artworkPrev(); artworkRestart() }">
                <template x-for="(img, i) in artworkImgs" :key="i">
                    <img :src="img" x-show="artworkIndex === i && !artworkIsVideo(img)" alt="Team artwork" class="team-artwork-img">
                </template>
                <template x-for="(img, i) in artworkImgs" :key="'v'+i">
                    <video :src="img" x-show="artworkIndex === i && artworkIsVideo(img)"
                           controls playsinline preload="metadata" class="team-artwork-img team-artwork-video"></video>
                </template>

                <template x-if="artworkImgs.length > 1">
                    <button type="button" class="team-artwork-nav prev"
                            @click.stop="artworkPrev(); artworkRestart()">
                        <i class="fa fa-chevron-left"></i>
                    </button>
                </template>
                <template x-if="artworkImgs.length > 1">
                    <button type="button" class="team-artwork-nav next"
                            @click.stop="artworkNext(); artworkRestart()">
                        <i class="fa fa-chevron-right"></i>
                    </button>
                </template>
            </div>

            <template x-if="artworkImgs.length > 1">
                <div class="team-artwork-dots">
                    <span class="team-artwork-counter" x-text="(artworkIndex + 1) + ' / ' + artworkImgs.length"></span>
                    <div class="team-artwork-dotrow">
                        <template x-for="(img, i) in artworkImgs" :key="'dot'+i">
                            <button type="button" class="team-artwork-dot" :class="{ active: artworkIndex === i }"
                                    @click="artworkGoto(i); artworkRestart()"></button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
    @endif
</div>

{{-- ── Team member frames + role cards (4 corners) ── --}}
@php
    $posClasses = ['tf-0','tf-1','tf-2','tf-3'];
    $popDelays  = [0.2, 0.4, 0.3, 0.5];
@endphp
@foreach($teamFrameUsers as $fi => $fuser)
@php $meta = $frameMeta[$fi] ?? $frameMeta[0]; @endphp
<div class="team-frame {{ $posClasses[$fi] ?? '' }}">
    {{-- Inner wrapper: one-shot pop-in entrance, kept off the outer element so it
         doesn't clobber the outer's continuous float/tilt animation --}}
    <div class="team-frame-pop"
         style="animation:popIn 0.6s {{ $popDelays[$fi] ?? 0.2 }}s cubic-bezier(.22,.68,0,1.15) both;">
        {{-- Colored blob behind --}}
        <div class="team-frame-blob"
             style="width:200px;height:200px;
                    top:50%;left:50%;
                    background:{{ $meta['color'] }};
                    animation-delay:{{ $fi * 0.7 }}s;">
        </div>
        {{-- Photo or initials --}}
        <div class="team-frame-inner">
            @if($fuser->avatar)
                <img src="{{ Storage::url($fuser->avatar) }}" alt="{{ $fuser->name }}">
            @else
                <div class="team-frame-initials" style="background:{{ $meta['color'] }};">
                    {{ mb_strtoupper(mb_substr($fuser->name, 0, 1)) }}
                </div>
            @endif
        </div>
    </div>
</div>

<div class="team-role-card team-role-{{ $fi }}"
     style="animation:popIn 0.6s {{ 0.4 + $fi * 0.15 }}s cubic-bezier(.22,.68,0,1.15) both;">
    <div class="team-role-icon" style="background:{{ $meta['color'] }};">
        <i class="fa {{ $meta['icon'] }}"></i>
    </div>
    <div>
        <p class="team-role-title">{{ $fuser->job_title ?: $meta['title'] }}</p>
        <p class="team-role-desc">{{ $meta['desc'] }}</p>
    </div>
</div>
@endforeach

{{-- ── Bottom pill — fixed footer bar ── --}}
<div class="team-bottom-pill">
    <i class="fa fa-star"></i> {{ $appSettings['login_pill_text'] ?? 'One Team. One Goal.' }} <span class="accent">{{ $appSettings['login_pill_accent'] ?? 'Unlimited Impact.' }}</span>
</div>

@endif {{-- end showTeamLayout --}}

@yield('content')

</body>
</html>

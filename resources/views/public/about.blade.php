<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $metaCompanyName = $appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name','Our Team');
        $metaTitle = $metaCompanyName . ' — About';
        $metaDescription = trim(strip_tags($appSettings['about_page_intro'] ?: ($appSettings['login_hero_tagline'] ?? 'Together we build. Together we achieve.')));
        $metaDescription = \Illuminate\Support\Str::limit($metaDescription, 155);
        $metaImagePath = $appSettings['logo_path'] ?? $appSettings['favicon_path'] ?? null;
        $metaImageUrl = $metaImagePath ? url(Storage::url($metaImagePath)) : null;
    @endphp

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($metaImageUrl)
    <meta property="og:image" content="{{ $metaImageUrl }}">
    @endif
    <meta name="twitter:card" content="{{ $metaImageUrl ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @if($metaImageUrl)
    <meta name="twitter:image" content="{{ $metaImageUrl }}">
    @endif

    @if(!empty($appSettings['favicon_path']))
    <link rel="icon" type="image/png" href="{{ Storage::url($appSettings['favicon_path']) }}">
    @endif
    <link rel="stylesheet" href="/css/inter.css">
    <link rel="stylesheet" href="/css/fa-all.min.css">
    <script defer src="/js/alpine.min.js"></script>

    @php
        $teamName = strtoupper($appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name','Our'));
        $artworkUrls = collect($appSettings['login_team_artwork'] ?? [])
            ->map(fn($path) => Storage::url($path))
            ->values();
        $hasArtwork = $artworkUrls->isNotEmpty();

        // Wave-arc team row — real members arranged in a smile-curve, tallest/biggest in the middle
        $wavePalette = [
            ['#FBCFE8','#DB2777'], ['#BFDBFE','#2563EB'], ['#C7D2FE','#4F46E5'],
            ['#BBF7D0','#059669'], ['#FDE68A','#D97706'], ['#A5F3FC','#0891B2'], ['#DDD6FE','#7C3AED'],
        ];
        $wavePeople = $teamMembers->take(7)->values();
        $waveCount  = max($wavePeople->count(), 1);
        $waveCards  = $wavePeople->map(function ($person, $i) use ($waveCount, $wavePalette) {
            $t     = $waveCount > 1 ? $i / ($waveCount - 1) : 0.5;
            $curve = sin($t * M_PI); // 0 at the ends, 1 in the middle
            return [
                'person' => $person,
                'lift'   => round($curve * 46),
                'w'      => round(104 * (1 + $curve * 0.34)),
                'h'      => round(158 * (1 + $curve * 0.34)),
                'colors' => $wavePalette[$i % count($wavePalette)],
            ];
        });

        // The grid below only earns its place if it shows people the wave row couldn't fit —
        // otherwise it's the same names twice. On mobile the wave row is hidden, so the grid always shows there.
        $showFullGridOnDesktop = $teamMembers->count() > $waveCards->count();
    @endphp

    <style>
        * { box-sizing: border-box; }
        a:focus-visible, button:focus-visible {
            outline: 2.5px solid #6366F1; outline-offset: 3px; border-radius: 6px;
        }
        .team-artwork-dot:focus-visible {
            outline-offset: 2px;
        }
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            min-height: 100vh;
            background: #F6F5FC;
            color: #16132B;
            position: relative;
            overflow-x: hidden;
        }

        /* ── Background atmosphere ── */
        .bg-deco { position: fixed; z-index: 0; pointer-events: none; }
        .bg-blob-tl  { top:-90px; left:-90px; width:280px; height:280px; border-radius:50%; background:radial-gradient(circle,rgba(129,140,248,0.30),rgba(129,140,248,0) 70%); }
        .bg-blob-br  { bottom:-100px; right:-60px; width:320px; height:320px; border-radius:50%; background:radial-gradient(circle,rgba(167,139,250,0.28),rgba(167,139,250,0) 70%); }

        .about-hero-stage {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(60% 50% at 15% 10%, rgba(99,102,241,0.16), transparent 70%),
                radial-gradient(50% 45% at 88% 8%,  rgba(6,182,212,0.14), transparent 70%),
                radial-gradient(55% 50% at 50% 95%, rgba(219,39,119,0.10), transparent 70%),
                linear-gradient(180deg,#FBFAFF 0%,#F3F1FC 100%);
        }
        .about-hero-stage::before {
            content: ''; position: absolute; inset: 0; pointer-events: none; opacity: 0.5; mix-blend-mode: multiply;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='90' height='90'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.05'/%3E%3C/svg%3E");
        }

        /* ── Top bar ── */
        .about-nav {
            position: relative; z-index: 3;
            display: flex; align-items: center; justify-content: space-between;
            padding: 26px 40px;
            max-width: 1180px; margin: 0 auto;
        }
        .about-brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 17px; letter-spacing: -0.01em; }
        .about-brand-icon {
            width: 36px; height: 36px; border-radius: 11px;
            background: linear-gradient(135deg,#A5B4FC,#6366F1);
            display: flex; align-items: center; justify-content: center; color: #fff;
        }
        .about-login-btn {
            padding: 10px 22px; border-radius: 999px;
            background: #16132B; color: #fff; font-weight: 700; font-size: 13.5px;
            text-decoration: none; box-shadow: 0 4px 14px rgba(22,19,43,0.25);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .about-login-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(22,19,43,0.32); }

        /* ── Hero ── */
        .about-hero {
            position: relative; z-index: 2; text-align: center;
            padding: 64px 20px 24px;
            max-width: 640px; margin: 0 auto;
            animation: heroRise 0.7s cubic-bezier(.22,.68,0,1.1) both;
        }
        @keyframes heroRise { from { opacity:0; transform: translateY(14px); } to { opacity:1; transform: translateY(0); } }
        .about-eyebrow {
            display: inline-flex; align-items: center; gap: 7px;
            background: #fff; border-radius: 999px; padding: 7px 16px;
            font-size: 12px; font-weight: 700; color: #6366F1;
            box-shadow: 0 4px 14px rgba(99,102,241,0.14);
            margin-bottom: 22px;
        }
        .about-eyebrow i { color: #F59E0B; }
        .about-hero h1 {
            font-size: clamp(34px, 6vw, 58px); font-weight: 800; margin: 0 0 16px;
            line-height: 1.04; letter-spacing: -0.03em;
        }
        .about-hero h1 .line2 {
            display: block;
            background: linear-gradient(100deg,#DB2777 0%,#6366F1 55%,#0891B2 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .about-hero p { font-size: 16px; color: #6B7280; margin: 0 auto; max-width: 480px; line-height: 1.6; }
        .about-hero-actions { margin-top: 28px; display: flex; align-items: center; justify-content: center; gap: 20px; flex-wrap: wrap; }
        .about-cta-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 14px 30px; border-radius: 999px; border: none; cursor: pointer;
            background: #16132B; color: #fff; font-weight: 700; font-size: 14px;
            text-decoration: none;
            box-shadow: 0 8px 22px rgba(22,19,43,0.28);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .about-cta-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 26px rgba(22,19,43,0.35); }
        .about-artwork-btn {
            display: inline-flex; align-items: center; gap: 7px;
            border: none; background: none; cursor: pointer;
            color: #16132B; font-weight: 700; font-size: 14px;
            padding: 14px 4px;
            transition: gap 0.15s, color 0.15s;
        }
        .about-artwork-btn i.fa-image { color: #6366F1; }
        .about-artwork-btn:hover { gap: 11px; color: #6366F1; }

        /* ── Wave-arc team row ── */
        .about-wave-row {
            position: relative; z-index: 2;
            display: flex; align-items: flex-end; justify-content: center;
            gap: 26px; max-width: 1100px; margin: 0 auto;
            padding: 64px 24px 60px; flex-wrap: wrap;
        }
        .about-wave-card {
            position: relative;
            display: flex; flex-direction: column; align-items: center;
            animation: wavePop 0.55s cubic-bezier(.22,.68,0,1.15) both;
        }
        @keyframes wavePop { from { opacity: 0; transform: translateY(26px) scale(0.85); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .about-wave-label {
            position: absolute; top: -50px; left: 50%; transform: translateX(-50%);
            background: #fff; border-radius: 12px; padding: 6px 13px;
            text-align: center; white-space: nowrap;
            box-shadow: 0 6px 16px rgba(22,19,43,0.12);
        }
        .wave-name { font-size: 12.5px; font-weight: 800; margin: 0; color: #16132B; }
        .wave-title { font-size: 10.5px; font-weight: 600; margin: 1px 0 0; color: #6366F1; }
        .about-wave-portrait {
            border-radius: 50% / 38%; overflow: hidden;
            box-shadow: 0 20px 36px -12px rgba(22,19,43,0.32);
        }
        .about-wave-portrait img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .about-wave-portrait .initials {
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 1.7em;
        }
        @media (max-width: 900px) { .about-wave-row { display: none; } }

        /* ── Services ── */
        .about-services-section { position: relative; z-index: 2; max-width: 940px; margin: 0 auto; padding: 0 24px 20px; }
        .about-services-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .about-service-card {
            background: #fff; border-radius: 22px; padding: 30px 22px; text-align: center;
            box-shadow: 0 14px 34px -12px rgba(22,19,43,0.14);
            border: 1px solid rgba(99,102,241,0.06);
            transition: transform 0.18s, box-shadow 0.18s;
        }
        .about-service-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -12px rgba(22,19,43,0.2); }
        .about-service-icon {
            width: 54px; height: 54px; border-radius: 50%; margin: 0 auto 18px;
            display: flex; align-items: center; justify-content: center; color: #fff; font-size: 19px;
            background: linear-gradient(135deg,#A5B4FC,#6366F1);
            box-shadow: 0 10px 20px -6px rgba(99,102,241,0.55), 0 0 0 6px rgba(99,102,241,0.08);
        }
        .about-service-title { font-weight: 800; font-size: 16px; margin: 0 0 6px; letter-spacing: -0.01em; }
        .about-service-desc { font-size: 13px; color: #6B7280; margin: 0; line-height: 1.6; }

        /* ── Team grid ── */
        .about-team-section { position: relative; z-index: 2; max-width: 1000px; margin: 0 auto; padding: 70px 24px 70px; }
        .about-team-heading { text-align: center; font-size: 13px; font-weight: 700; letter-spacing: 0.08em; color: #6366F1; text-transform: uppercase; margin-bottom: 28px; }
        .about-team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 20px; }
        .about-team-card {
            background: #fff; border-radius: 18px; padding: 22px 16px; text-align: center;
            box-shadow: 0 4px 20px rgba(99,102,241,0.08);
            border: 1px solid rgba(99,102,241,0.06);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .about-team-card:hover { transform: translateY(-3px); box-shadow: 0 10px 26px rgba(99,102,241,0.16); }
        .about-team-avatar {
            width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 14px;
            object-fit: cover; box-shadow: 0 4px 14px rgba(0,0,0,0.1);
        }
        .about-team-initials {
            width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 14px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 24px; color: #fff;
            background: linear-gradient(135deg,#A5B4FC,#6366F1);
        }
        .about-team-name { font-weight: 700; font-size: 14.5px; margin: 0 0 2px; }
        .about-team-role { font-size: 12px; color: #8B5CF6; font-weight: 600; text-transform: capitalize; margin: 0; }
        .about-team-empty { text-align: center; color: #9CA3AF; font-size: 14px; padding: 30px; }
        @media (min-width: 900px) { .about-team-section.wave-covers-everyone { display: none; } }
        @media (max-width: 899px) { .about-team-section { padding-top: 24px; } }

        /* ── Gallery (real artwork, shown inline instead of only in the popup) ── */
        .about-gallery-section { position: relative; z-index: 2; max-width: 1000px; margin: 0 auto; padding: 20px 24px 70px; }
        .about-gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .about-gallery-tile {
            position: relative; aspect-ratio: 4 / 3; border-radius: 18px; overflow: hidden;
            cursor: pointer; border: none; padding: 0; background: #E5E7EB;
            box-shadow: 0 10px 26px -10px rgba(22,19,43,0.25);
            transition: transform 0.2s;
        }
        .about-gallery-tile:hover { transform: translateY(-3px) scale(1.015); }
        .about-gallery-tile img, .about-gallery-tile video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .about-gallery-play {
            position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
            background: rgba(17,24,39,0.25);
        }
        .about-gallery-play i {
            width: 46px; height: 46px; border-radius: 50%; background: rgba(255,255,255,0.92);
            color: #16132B; display: flex; align-items: center; justify-content: center; font-size: 16px;
        }

        /* ── Promo / CTA banner ── */
        .about-cta-banner {
            position: relative; z-index: 2; margin: 0 24px 70px; max-width: 1000px; margin-left: auto; margin-right: auto;
            background: linear-gradient(135deg,#1E1B4B 0%,#16132B 100%);
            border-radius: 28px; padding: 56px 32px; text-align: center;
            box-shadow: 0 24px 50px -18px rgba(22,19,43,0.5);
            overflow: hidden;
        }
        .about-cta-banner::before {
            content: ''; position: absolute; inset: 0; opacity: 0.5;
            background: radial-gradient(50% 80% at 15% 20%, rgba(99,102,241,0.35), transparent 70%),
                        radial-gradient(50% 80% at 85% 80%, rgba(219,39,119,0.28), transparent 70%);
        }
        .about-cta-banner h2 { position: relative; color: #fff; font-size: clamp(24px,3.4vw,34px); font-weight: 800; margin: 0 0 22px; letter-spacing: -0.02em; }
        .about-cta-banner .about-cta-btn { position: relative; background: #fff; color: #16132B; box-shadow: 0 10px 24px -8px rgba(0,0,0,0.4); }
        .about-cta-banner .about-cta-btn:hover { box-shadow: 0 14px 28px -8px rgba(0,0,0,0.5); }

        /* ── Footer ── */
        .about-footer {
            position: relative; z-index: 2; text-align: center;
            padding: 30px 24px; font-size: 12.5px; color: #9CA3AF;
            border-top: 1px solid rgba(99,102,241,0.08);
        }
        .about-footer-links { display: flex; align-items: center; justify-content: center; gap: 18px; margin-bottom: 10px; flex-wrap: wrap; }
        .about-footer-links a { color: #6366F1; font-weight: 700; text-decoration: none; font-size: 13px; }
        .about-footer-links a:hover { text-decoration: underline; }

        /* ── Artwork popup (shared pattern with login page) ── */
        [x-cloak] { display: none !important; }
        .team-artwork-backdrop {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(17,24,39,0.6);
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }
        .team-artwork-card {
            position: relative; background: #fff; border-radius: 20px; padding: 16px;
            max-width: 680px; width: 100%; max-height: 90vh; overflow: auto;
            box-shadow: 0 32px 80px rgba(0,0,0,0.3);
        }
        .team-artwork-close {
            position: absolute; top: 12px; right: 12px;
            width: 32px; height: 32px; border-radius: 50%;
            background: #F3F4F6; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: #374151; z-index: 2;
        }
        .team-artwork-img { display: block; border-radius: 12px; margin: 0 auto; max-width: 100%; max-height: 60vh; width: auto; height: auto; }
        .team-artwork-video { background: #111827; }
        .team-artwork-stage { position: relative; min-height: 200px; max-height: 60vh; display: flex; align-items: center; justify-content: center; }
        .team-artwork-stage.is-multi { cursor: pointer; }
        .team-artwork-nav {
            position: absolute; top: 50%; transform: translateY(-50%);
            width: 38px; height: 38px; border-radius: 50%;
            background: rgba(17,24,39,0.55); border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px; z-index: 1; transition: background 0.15s;
        }
        .team-artwork-nav:hover { background: rgba(17,24,39,0.8); }
        .team-artwork-nav.prev { left: 10px; }
        .team-artwork-nav.next { right: 10px; }
        .team-artwork-dots { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 12px; }
        .team-artwork-counter { background: #F3F4F6; color: #6B7280; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; letter-spacing: .02em; }
        .team-artwork-dotrow { display: flex; gap: 6px; }
        .team-artwork-dot { width: 7px; height: 7px; border-radius: 50%; border: none; background: #E5E7EB; cursor: pointer; padding: 0; }
        .team-artwork-dot.active { background: #6366F1; width: 18px; border-radius: 4px; }

        @media (max-width: 600px) {
            .about-nav { padding: 18px 20px; }
            .about-hero { padding: 40px 16px 40px; }
        }
    </style>
</head>
<body>

<div class="bg-deco bg-blob-tl"></div>
<div class="bg-deco bg-blob-br"></div>

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

    <div class="about-hero-stage">
        <header class="about-nav">
            <div class="about-brand">
                @if(!empty($appSettings['logo_path']))
                    <img src="{{ Storage::url($appSettings['logo_path']) }}" alt="Logo" style="height:32px;">
                @else
                    <div class="about-brand-icon"><i class="fa fa-users"></i></div>
                @endif
                {{ $appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name') }}
            </div>
            <div style="display:flex; align-items:center; gap:20px;">
                @if(($appSettings['about_page_cta_enabled'] ?? '1') === '1' && !empty($appSettings['about_page_cta_link']))
                <a href="{{ $appSettings['about_page_cta_link'] }}" style="font-weight:700; font-size:13.5px; color:#16132B; text-decoration:none;"
                   @if(str_starts_with($appSettings['about_page_cta_link'], 'http')) target="_blank" rel="noopener" @endif>
                    {{ $appSettings['about_page_cta_text'] ?: 'Get in Touch' }}
                </a>
                @endif
                <a href="{{ route('login') }}" class="about-login-btn">Login <i class="fa fa-arrow-right" style="margin-left:4px;"></i></a>
            </div>
        </header>

        <section class="about-hero">
            <div class="about-eyebrow"><i class="fa fa-star"></i> {{ $appSettings['login_pill_text'] ?? 'One Team. One Goal.' }}</div>
            <h1>We Are<span class="line2">{{ $teamName }}</span></h1>
            <p>{{ $appSettings['about_page_intro'] ?: ($appSettings['login_hero_tagline'] ?? 'Together we build. Together we achieve.') }}</p>

            <div class="about-hero-actions">
                @if(($appSettings['about_page_cta_enabled'] ?? '1') === '1' && !empty($appSettings['about_page_cta_link']))
                <a href="{{ $appSettings['about_page_cta_link'] }}" class="about-cta-btn"
                   @if(str_starts_with($appSettings['about_page_cta_link'], 'http')) target="_blank" rel="noopener" @endif>
                    {{ $appSettings['about_page_cta_text'] ?: 'Get in Touch' }} <i class="fa fa-arrow-right"></i>
                </a>
                @endif
                @if($hasArtwork)
                <button type="button" class="about-artwork-btn" @click="artworkOpen = true; artworkIndex = 0; artworkRestart()">
                    View our work <i class="fa fa-image"></i>
                </button>
                @endif
            </div>
        </section>

        @if($waveCards->isNotEmpty())
        <div class="about-wave-row">
            @foreach($waveCards as $i => $card)
            @php $person = $card['person']; @endphp
            <div class="about-wave-card" style="margin-top:-{{ $card['lift'] }}px; animation-delay:{{ 0.08 * $i }}s;">
                <div class="about-wave-label">
                    <p class="wave-name">{{ $person->name }}</p>
                    <p class="wave-title">{{ $person->job_title ?: ucfirst($person->role) }}</p>
                </div>
                <div class="about-wave-portrait"
                     style="width:{{ $card['w'] }}px; height:{{ $card['h'] }}px; background:linear-gradient(160deg,{{ $card['colors'][0] }},{{ $card['colors'][1] }});">
                    @if($person->avatar)
                        <img src="{{ Storage::url($person->avatar) }}" alt="{{ $person->name }}">
                    @else
                        <div class="initials">{{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    @php
        $services = collect([1, 2, 3])
            ->map(fn ($i) => [
                'title' => $appSettings["about_page_service{$i}_title"] ?? '',
                'desc'  => $appSettings["about_page_service{$i}_desc"] ?? '',
            ])
            ->filter(fn ($s) => $s['title'] !== '')
            ->values();
        $serviceIcons = ['fa-diagram-project', 'fa-bullhorn', 'fa-lightbulb'];
    @endphp
    @if($services->isNotEmpty())
    <section class="about-services-section">
        <div class="about-services-grid">
            @foreach($services as $i => $service)
            <div class="about-service-card">
                <div class="about-service-icon"><i class="fa {{ $serviceIcons[$i] ?? 'fa-star' }}"></i></div>
                <p class="about-service-title">{{ $service['title'] }}</p>
                @if($service['desc'])
                <p class="about-service-desc">{{ $service['desc'] }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </section>
    @endif

    @if($hasArtwork)
    <section class="about-gallery-section">
        <p class="about-team-heading">Gallery</p>
        <div class="about-gallery-grid">
            @foreach($artworkUrls as $i => $url)
            <button type="button" class="about-gallery-tile" @click="artworkOpen = true; artworkIndex = {{ $i }}; artworkRestart()">
                @if(preg_match('/\.(mp4|webm|mov|m4v)(\?.*)?$/i', $url))
                    <video src="{{ $url }}#t=0.1" preload="metadata" muted></video>
                    <div class="about-gallery-play"><i class="fa fa-play"></i></div>
                @else
                    <img src="{{ $url }}" alt="Gallery image {{ $i + 1 }}">
                @endif
            </button>
            @endforeach
        </div>
    </section>
    @endif

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
                    <button type="button" class="team-artwork-nav prev" @click.stop="artworkPrev(); artworkRestart()">
                        <i class="fa fa-chevron-left"></i>
                    </button>
                </template>
                <template x-if="artworkImgs.length > 1">
                    <button type="button" class="team-artwork-nav next" @click.stop="artworkNext(); artworkRestart()">
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

<section class="about-team-section {{ $showFullGridOnDesktop ? '' : 'wave-covers-everyone' }}">
    <p class="about-team-heading">Meet the team</p>

    @if($teamMembers->isEmpty())
        <p class="about-team-empty">No team members to show yet.</p>
    @else
        <div class="about-team-grid">
            @foreach($teamMembers as $member)
            <div class="about-team-card">
                @if($member->avatar)
                    <img src="{{ Storage::url($member->avatar) }}" alt="{{ $member->name }}" class="about-team-avatar">
                @else
                    <div class="about-team-initials">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</div>
                @endif
                <p class="about-team-name">{{ $member->name }}</p>
                <p class="about-team-role">{{ $member->job_title ?: $member->role }}</p>
            </div>
            @endforeach
        </div>
    @endif
</section>

@if(($appSettings['about_page_cta_enabled'] ?? '1') === '1' && !empty($appSettings['about_page_cta_link']))
<section class="about-cta-banner">
    <h2>Let's talk.</h2>
    <a href="{{ $appSettings['about_page_cta_link'] }}" class="about-cta-btn"
       @if(str_starts_with($appSettings['about_page_cta_link'], 'http')) target="_blank" rel="noopener" @endif>
        {{ $appSettings['about_page_cta_text'] ?: 'Get in Touch' }} <i class="fa fa-arrow-right"></i>
    </a>
</section>
@endif

<footer class="about-footer">
    <div class="about-footer-links">
        <a href="{{ route('login') }}">Login</a>
        @if(($appSettings['about_page_cta_enabled'] ?? '1') === '1' && !empty($appSettings['about_page_cta_link']))
        <a href="{{ $appSettings['about_page_cta_link'] }}"
           @if(str_starts_with($appSettings['about_page_cta_link'], 'http')) target="_blank" rel="noopener" @endif>
            {{ $appSettings['about_page_cta_text'] ?: 'Get in Touch' }}
        </a>
        @endif
    </div>
    {{ $appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name') }}
    @if(!empty($appSettings['copyright']))
        · {{ $appSettings['copyright'] }}
    @else
        · © {{ date('Y') }}
    @endif
</footer>

</body>
</html>

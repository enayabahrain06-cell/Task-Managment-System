<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if($devEditOn)
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @endif
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
    @vite(['resources/css/app.css'])

    @php
        // Brand tokens — driven entirely by the admin-configured system colors, not hardcoded hex.
        $brand   = $appSettings['primary_color'] ?? '#4F46E5';
        $brand2  = \App\Models\Setting::get('accent_color', '#9163aa');
        $teamName = strtoupper($metaCompanyName);

        $artworkUrls = collect($appSettings['login_team_artwork'] ?? [])
            ->map(fn($path) => Storage::url($path))
            ->values();
        $hasArtwork = $artworkUrls->isNotEmpty();

        // Wave-arc team row — real members arranged in a smile-curve, tallest/biggest in the middle
        $wavePalette = [
            ['#FBCFE8','#DB2777'], ['#BFDBFE','#2563EB'], ['#C7D2FE', $brand],
            ['#BBF7D0','#059669'], ['#FDE68A','#D97706'], ['#A5F3FC','#0891B2'], ['#DDD6FE', $brand2],
        ];
        // $teamMembers is already in the admin's chosen display order (see PublicController::about),
        // so whoever is in the middle of the first 7 lands in the wave's tallest, centered slot.
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
        $showFullGridOnDesktop = $teamMembers->count() > $waveCards->count();

        // Mission / Vision — admin-editable, graceful defaults when empty
        $mission = $appSettings['about_page_mission'] ?: "To empower teams everywhere with tools that make ambitious work feel effortless — pairing thoughtful design with reliable engineering.";
        $vision  = $appSettings['about_page_vision']  ?: "A future where every team, regardless of size, has access to tools that adapt to how they actually work — not the other way around.";
        $whoText = $appSettings['about_page_who_text'] ?: ($metaCompanyName." is a team of builders, designers, and strategists dedicated to turning ambitious ideas into products people love. We combine deep technical craft with a genuine care for the people we work with — clients and teammates alike.");
        $quoteText = $appSettings['about_page_quote_text'] ?: "Great work isn't an accident — it's a team that genuinely cares, showing up every day.";
        $introText = $appSettings['about_page_intro'] ?: ($appSettings['login_hero_tagline'] ?? 'Together we build. Together we achieve.');

        // Raw (un-fallback) values — used as the editable content in Developer Mode so an empty
        // field shows the CSS placeholder instead of the resolved default baked into the text.
        $introRaw  = $appSettings['about_page_intro'] ?? '';
        $missionRaw = $appSettings['about_page_mission'] ?? '';
        $visionRaw  = $appSettings['about_page_vision'] ?? '';
        $whoTextRaw = $appSettings['about_page_who_text'] ?? '';
        $quoteTextRaw = $appSettings['about_page_quote_text'] ?? '';
        $servicesHeadingRaw = $appSettings['about_page_services_heading'] ?? '';
        $ctaTextRaw = $appSettings['about_page_cta_text'] ?? '';

        // Hero background — optional admin-uploaded image/video, falls back to the brand-color gradient blend
        $heroBgPath = $appSettings['about_page_bg_image'] ?? '';
        $hasHeroBg = $heroBgPath !== '';
        $heroBgUrl = $hasHeroBg ? Storage::url($heroBgPath) : null;
        $heroBgIsVideo = $hasHeroBg && preg_match('/\.(mp4|webm|mov|m4v)$/i', $heroBgPath);
        $heroBgOverlay = max(0, min(80, (int) ($appSettings['about_page_bg_overlay'] ?? 0)));

        // Core Values — admin-editable per slot, sensible defaults fill any empty slot, hidden slots dropped
        $valueDefaults = [
            1 => ['title' => 'Innovation',        'desc' => "We embrace new ideas and aren't afraid to challenge the status quo.", 'icon' => 'fa-lightbulb'],
            2 => ['title' => 'Integrity',         'desc' => 'We do what\'s right, even when no one is watching.', 'icon' => 'fa-shield-halved'],
            3 => ['title' => 'Collaboration',     'desc' => 'Great work happens when diverse minds build together.', 'icon' => 'fa-people-group'],
            4 => ['title' => 'Excellence',        'desc' => 'We sweat the details that others skip.', 'icon' => 'fa-medal'],
            5 => ['title' => 'Customer Success',  'desc' => 'Our success is measured by the success of the people we serve.', 'icon' => 'fa-heart'],
            6 => ['title' => 'Growth',            'desc' => 'We invest in learning, iterating, and getting better every single day.', 'icon' => 'fa-seedling'],
        ];
        $values = collect($valueDefaults)
            ->map(function ($default, $i) use ($appSettings) {
                return [
                    'slot'      => $i,
                    'title'     => $appSettings["about_page_value{$i}_title"] ?: $default['title'],
                    'desc'      => $appSettings["about_page_value{$i}_desc"]  ?: $default['desc'],
                    'title_raw' => $appSettings["about_page_value{$i}_title"] ?? '',
                    'desc_raw'  => $appSettings["about_page_value{$i}_desc"] ?? '',
                    'icon'      => $default['icon'],
                    'hidden'    => ($appSettings["about_page_value{$i}_hidden"] ?? '0') === '1',
                ];
            })
            ->when(! $devEditOn, fn ($c) => $c->reject(fn ($v) => $v['hidden']))
            ->values();

        // Products & Services — admin-editable per slot, agency-style defaults fill any empty slot, hidden slots dropped
        $serviceDefaults = [
            1 => ['title' => 'Brand Strategy',        'desc' => 'Positioning and messaging that make your brand impossible to ignore.', 'icon' => 'fa-diagram-project'],
            2 => ['title' => 'Creative Design',       'desc' => 'Visual identity and design systems built to scale.', 'icon' => 'fa-palette'],
            3 => ['title' => 'Digital Marketing',     'desc' => 'Campaigns that reach the right people, at the right moment.', 'icon' => 'fa-bullhorn'],
            4 => ['title' => 'Web & App Development', 'desc' => 'Fast, reliable products built with modern engineering.', 'icon' => 'fa-laptop-code'],
            5 => ['title' => 'Content Production',    'desc' => 'Photo, video, and copy that tells your story well.', 'icon' => 'fa-video'],
            6 => ['title' => 'Project Management',    'desc' => 'Clear timelines and transparent communication — work that ships on time.', 'icon' => 'fa-list-check'],
        ];
        $services = collect($serviceDefaults)
            ->map(function ($default, $i) use ($appSettings) {
                return [
                    'slot'      => $i,
                    'title'     => $appSettings["about_page_service{$i}_title"] ?: $default['title'],
                    'desc'      => $appSettings["about_page_service{$i}_desc"]  ?: $default['desc'],
                    'title_raw' => $appSettings["about_page_service{$i}_title"] ?? '',
                    'desc_raw'  => $appSettings["about_page_service{$i}_desc"] ?? '',
                    'icon'      => $default['icon'],
                    'hidden'    => ($appSettings["about_page_service{$i}_hidden"] ?? '0') === '1',
                ];
            })
            ->when(! $devEditOn, fn ($c) => $c->reject(fn ($s) => $s['hidden']))
            ->values();

        // Our Journey — narrative milestones (intentionally undated placeholder copy, not fabricated history), hidden slots dropped
        $journeyDefaults = [
            1 => ['title' => 'The Beginning',       'desc' => "It started with a small team and a big idea: work shouldn't feel this hard.", 'icon' => 'fa-flag'],
            2 => ['title' => 'Building the Team',   'desc' => 'We brought together builders, designers, and strategists who share the same obsession with quality.', 'icon' => 'fa-people-group'],
            3 => ['title' => 'Expanding Our Craft', 'desc' => 'New services, new challenges, and a growing list of projects we\'re proud of.', 'icon' => 'fa-arrow-trend-up'],
            4 => ['title' => 'Today',               'desc' => 'We keep shipping, learning, and raising the bar for ourselves — every single day.', 'icon' => 'fa-star'],
        ];
        $journey = collect($journeyDefaults)
            ->map(fn ($stop, $i) => array_merge($stop, [
                'slot'   => $i,
                'hidden' => ($appSettings["about_page_journey{$i}_hidden"] ?? '0') === '1',
            ]))
            ->when(! $devEditOn, fn ($c) => $c->reject(fn ($s) => $s['hidden']))
            ->values();

        // Statistics — 1 & 2 are always real (computed from the DB); 3 & 4 have no automatic
        // source (years in business, satisfaction survey), so they only appear if an admin
        // explicitly enters a real number in Settings — never a made-up default.
        $statDefaults = [
            1 => ['value' => $completedProjectCount, 'suffix' => '+', 'label' => 'Projects Completed', 'icon' => 'fa-diagram-project', 'editable' => false],
            2 => ['value' => $activeMemberCount,     'suffix' => '+', 'label' => 'Team Members',       'icon' => 'fa-people-group',    'editable' => false],
        ];
        $stat3Set = is_numeric($appSettings['about_page_stat3_value'] ?? '');
        $stat4Set = is_numeric($appSettings['about_page_stat4_value'] ?? '');
        if ($stat3Set || $devEditOn) {
            $statDefaults[3] = ['value' => $stat3Set ? (int) $appSettings['about_page_stat3_value'] : null, 'suffix' => '+', 'label' => 'Years of Experience', 'icon' => 'fa-award', 'editable' => true];
        }
        if ($stat4Set || $devEditOn) {
            $statDefaults[4] = ['value' => $stat4Set ? (int) $appSettings['about_page_stat4_value'] : null, 'suffix' => '%', 'label' => 'Client Satisfaction', 'icon' => 'fa-face-smile', 'editable' => true];
        }
        $stats = collect($statDefaults)
            ->map(fn ($stat, $i) => array_merge($stat, [
                'slot'   => $i,
                'hidden' => ($appSettings["about_page_stat{$i}_hidden"] ?? '0') === '1',
            ]))
            ->when(! $devEditOn, fn ($c) => $c->reject(fn ($s) => $s['hidden'] || $s['value'] === null))
            ->values();

        $ctaEnabled = ($appSettings['about_page_cta_enabled'] ?? '1') === '1' && !empty($appSettings['about_page_cta_link']);
        $ctaLink = $appSettings['about_page_cta_link'] ?? '';
        $ctaText = $appSettings['about_page_cta_text'] ?: 'Get in Touch';
        $ctaExternal = str_starts_with($ctaLink, 'http');

        // Section-level visibility toggles
        $showWho           = ($appSettings['about_page_show_who'] ?? '1') === '1';
        $showStats         = ($appSettings['about_page_show_stats'] ?? '1') === '1' && $stats->isNotEmpty();
        $showMission       = ($appSettings['about_page_mission_hidden'] ?? '0') !== '1';
        $showVision        = ($appSettings['about_page_vision_hidden'] ?? '0') !== '1';
        $showMissionVision = ($appSettings['about_page_show_mission_vision'] ?? '1') === '1' && ($showMission || $showVision);
        $showValues        = ($appSettings['about_page_show_values'] ?? '1') === '1' && $values->isNotEmpty();
        $showTeam          = ($appSettings['about_page_show_team'] ?? '1') === '1';
        $showJourney       = ($appSettings['about_page_show_journey'] ?? '1') === '1' && $journey->isNotEmpty();
        $showServices      = ($appSettings['about_page_show_services'] ?? '1') === '1' && $services->isNotEmpty();
        $showGallery       = ($appSettings['about_page_show_gallery'] ?? '1') === '1';

        // In Developer Mode, keep every section rendered (dimmed via CSS if actually off) so an
        // admin can flip it back on from the live page instead of a section just vanishing.
        $sectionOn = [
            'who' => $showWho, 'stats' => $showStats, 'mission_vision' => $showMissionVision,
            'values' => $showValues, 'team' => $showTeam, 'journey' => $showJourney,
            'services' => $showServices, 'gallery' => $showGallery,
            'mission' => $showMission, 'vision' => $showVision,
        ];
        if ($devEditOn) {
            $showWho = $showStats = $showMissionVision = $showValues = $showTeam = $showJourney = $showServices = $showGallery = true;
            $showMission = $showVision = true;
        }
    @endphp

    <style>
        :root {
            --brand: {{ $brand }};
            --brand-2: {{ $brand2 }};
            --brand-ink: color-mix(in srgb, var(--brand) 80%, black);
            --brand-soft: color-mix(in srgb, var(--brand) 9%, white);
            --brand-soft2: color-mix(in srgb, var(--brand) 16%, white);
            --brand-line: color-mix(in srgb, var(--brand) 22%, white);
            /* Mobile design tokens — only referenced inside the @media (max-width) blocks below */
            --mobile-radius: 18px;
            --mobile-shadow: 0 2px 10px rgba(17,24,39,.05);
            --mobile-space-sm: 8px;
            --mobile-space-md: 16px;
            --mobile-space-lg: 24px;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        a:focus-visible, button:focus-visible { outline: 2.5px solid var(--brand); outline-offset: 3px; border-radius: 6px; }
        body {
            font-family: 'Inter', sans-serif;
            margin: 0; min-height: 100vh; background: #FBFAFF; color: #16132B;
            position: relative; overflow-x: hidden;
        }
        [x-cloak] { display: none !important; }

        /* ── Ambient background atmosphere ── */
        .bg-deco { position: fixed; z-index: 0; pointer-events: none; }
        .bg-blob-tl { top: -110px; left: -110px; width: 340px; height: 340px; border-radius: 50%; background: radial-gradient(circle, color-mix(in srgb, var(--brand) 30%, transparent), transparent 70%); }
        .bg-blob-br { bottom: -120px; right: -80px; width: 380px; height: 380px; border-radius: 50%; background: radial-gradient(circle, color-mix(in srgb, var(--brand-2) 26%, transparent), transparent 70%); }
        .grain::before {
            content: ''; position: absolute; inset: 0; pointer-events: none; opacity: 0.5; mix-blend-mode: multiply;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='90' height='90'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.05'/%3E%3C/svg%3E");
        }

        .gradient-text {
            background: linear-gradient(100deg, #DB2777 0%, var(--brand) 55%, #0891B2 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .brand-grad { background: linear-gradient(135deg, var(--brand), var(--brand-2)); }
        .glass { background: rgba(255,255,255,0.72); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }

        /* ── Scroll reveal ── */
        .reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s cubic-bezier(.16,.84,.44,1), transform .7s cubic-bezier(.16,.84,.44,1); }
        .reveal.is-visible { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1; transform: none; transition: none; } }

        /* ── Hero ambient background blobs ── */
        .hero-blob { position: absolute; z-index: 0; border-radius: 50%; filter: blur(70px); pointer-events: none; }
        .hero-blob-1 { width: 46%; height: 55%; left: -8%; top: -14%; background: color-mix(in srgb, var(--brand) 30%, transparent); animation: heroBlobDrift1 22s ease-in-out infinite alternate; }
        .hero-blob-2 { width: 40%; height: 45%; right: -6%; top: -10%; background: rgba(6,182,212,0.22); animation: heroBlobDrift2 26s ease-in-out infinite alternate; }
        .hero-blob-3 { width: 50%; height: 50%; left: 25%; bottom: -20%; background: rgba(219,39,119,0.16); animation: heroBlobDrift3 30s ease-in-out infinite alternate; }
        @keyframes heroBlobDrift1 { from { transform: translate(0,0) scale(1); } to { transform: translate(4%,3%) scale(1.08); } }
        @keyframes heroBlobDrift2 { from { transform: translate(0,0) scale(1); } to { transform: translate(-5%,4%) scale(0.94); } }
        @keyframes heroBlobDrift3 { from { transform: translate(0,0) scale(1); } to { transform: translate(3%,-4%) scale(1.06); } }
        @media (prefers-reduced-motion: reduce) { .hero-blob { animation: none; } }

        /* ── Wave-arc team row (hero signature element) ── */
        @keyframes wavePop { from { opacity: 0; transform: translateY(26px) scale(0.85); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .wave-card { animation: wavePop 0.55s cubic-bezier(.22,.68,0,1.15) both; }
        .wave-label { box-shadow: 0 6px 16px rgba(22,19,43,0.12); }
        .wave-portrait { border-radius: 50% / 38%; box-shadow: 0 20px 36px -12px rgba(22,19,43,0.32); }
        @media (max-width: 900px) { .wave-row { display: none !important; } }

        /* ── Journey timeline connector ── */
        .journey-line { background: linear-gradient(180deg, transparent, var(--brand-line) 10%, var(--brand-line) 90%, transparent); }
        .journey-dot { box-shadow: 0 0 0 6px var(--brand-soft2); }

        /* ── Tech marquee ── */
        @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .tech-track { animation: marquee 26s linear infinite; }
        .tech-track:hover { animation-play-state: paused; }

        /* ── Artwork lightbox (shared pattern with login page) ── */
        .team-artwork-backdrop { position: fixed; inset: 0; z-index: 9999; background: rgba(17,24,39,0.6); display: flex; align-items: center; justify-content: center; padding: 20px; }
        .team-artwork-card { position: relative; background: #fff; border-radius: 20px; padding: 16px; max-width: 680px; width: 100%; max-height: 90vh; overflow: auto; box-shadow: 0 32px 80px rgba(0,0,0,0.3); }
        .team-artwork-close { position: absolute; top: 12px; right: 12px; width: 32px; height: 32px; border-radius: 50%; background: #F3F4F6; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #374151; z-index: 2; }
        .team-artwork-img { display: block; border-radius: 12px; margin: 0 auto; max-width: 100%; max-height: 60vh; width: auto; height: auto; }
        .team-artwork-video { background: #111827; }
        .team-artwork-stage { position: relative; min-height: 200px; max-height: 60vh; display: flex; align-items: center; justify-content: center; }
        .team-artwork-stage.is-multi { cursor: pointer; }
        .team-artwork-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 38px; height: 38px; border-radius: 50%; background: rgba(17,24,39,0.55); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; z-index: 1; transition: background 0.15s; }
        .team-artwork-nav:hover { background: rgba(17,24,39,0.8); }
        .team-artwork-nav.prev { left: 10px; }
        .team-artwork-nav.next { right: 10px; }
        .team-artwork-dots { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 12px; }
        .team-artwork-counter { background: #F3F4F6; color: #6B7280; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; letter-spacing: .02em; }
        .team-artwork-dotrow { display: flex; gap: 6px; }
        .team-artwork-dot { width: 7px; height: 7px; border-radius: 50%; border: none; background: #E5E7EB; cursor: pointer; padding: 0; }
        .team-artwork-dot.active { background: var(--brand); width: 18px; border-radius: 4px; }

        /* ── Mobile-only premium pass — every rule is scoped inside @media (max-width: 768px)/(max-width: 480px)
             and only touches classes added below; desktop (>768px / md:/lg:) styling is untouched.
             Note: the team-card drag-reorder JS (data-member-id listeners) is untouched — only grid/sizing here. ── */
        @media (max-width: 768px) {
            .mobile-team-grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: var(--mobile-space-md) !important; }
            .mobile-team-card { padding: var(--mobile-space-md) !important; border-radius: var(--mobile-radius) !important; box-shadow: var(--mobile-shadow) !important; }
            .mobile-stat-card { padding: var(--mobile-space-md) !important; }
            .mobile-journey-card { padding: var(--mobile-space-md) !important; }
            .mobile-nav-login {
                min-height: 44px !important;
                padding-top: 0 !important;
                padding-bottom: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
            }
            .mobile-hero-title { font-size: clamp(26px, 8vw, 32px) !important; line-height: 1.18 !important; }
            .mobile-hero-intro { font-size: 14.5px !important; line-height: 1.65 !important; }
        }

        @media (max-width: 480px) {
            .mobile-team-grid { grid-template-columns: minmax(0, 1fr) !important; }
            .mobile-hero-title { font-size: 24px !important; }
        }

        /* ── Developer Mode live editing ── */
        @if($devEditOn)
        body { padding-top: 44px; }
        .dev-bar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            background: linear-gradient(90deg,#4F46E5,#7C3AED); color: #fff;
            padding: 9px 20px; display: flex; align-items: center; justify-content: center; gap: 14px;
            font-size: 12.5px; font-weight: 600; flex-wrap: wrap; text-align: center;
        }
        .dev-bar a { color: #fff; text-decoration: underline; }
        .dev-editable { outline: none; cursor: text; border-radius: 4px; transition: box-shadow .15s; }
        .dev-editable:hover { box-shadow: 0 0 0 2px rgba(99,102,241,0.35); }
        .dev-editable:focus { box-shadow: 0 0 0 2px rgba(99,102,241,0.8); }
        .dev-editable:empty:before { content: attr(data-placeholder); opacity: 0.55; }
        .dev-editable-on-brand:hover { box-shadow: 0 0 0 2px rgba(255,255,255,0.4); }
        .dev-editable-on-brand:focus { box-shadow: 0 0 0 2px rgba(255,255,255,0.85); }
        .dev-hideable { position: relative; transition: opacity .15s; }
        .dev-hideable[data-hidden="1"] { opacity: 0.4; }
        .dev-eye-btn {
            position: absolute; top: 10px; right: 10px; z-index: 20;
            width: 26px; height: 26px; border-radius: 50%; border: none; cursor: pointer;
            background: rgba(17,24,39,0.55); color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 11px;
        }
        .dev-eye-btn:hover { background: rgba(17,24,39,0.8); }
        section.dev-hideable > .dev-eye-btn { top: 16px; right: 16px; }
        .dev-draggable-card { cursor: grab; transition: box-shadow .15s, opacity .15s; }
        .dev-draggable-card:active { cursor: grabbing; }
        .dev-drag-dragging { opacity: 0.4; }
        .dev-drag-over { box-shadow: 0 0 0 3px var(--brand) !important; }
        @endif
    </style>
</head>
<body>

@if($devEditOn)
<div class="dev-bar">
    <span><i class="fa-solid fa-pen"></i> Developer Mode — click any text to edit it, use the eye icons to show/hide sections and cards. Changes save instantly.</span>
    <a href="{{ route('admin.settings.index') }}#about_page">Open full editor in Settings</a>
</div>
@endif

<div class="bg-deco bg-blob-tl"></div>
<div class="bg-deco bg-blob-br"></div>

<div class="relative z-10 grain"
     @if($hasArtwork) x-data="{
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

    {{-- ═══════════ NAV ═══════════ --}}
    <header class="sticky top-0 z-40 glass border-b border-black/5">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5 font-extrabold text-[17px] tracking-tight">
                @if(!empty($appSettings['logo_path']))
                    <img src="{{ Storage::url($appSettings['logo_path']) }}" alt="Logo" class="h-8">
                @else
                    <div class="w-9 h-9 rounded-xl brand-grad flex items-center justify-center text-white"><i class="fa-solid fa-users"></i></div>
                @endif
                {{ $metaCompanyName }}
            </div>
            <div class="flex items-center gap-5">
                @if($ctaEnabled)
                <a href="{{ $ctaLink }}" @if($ctaExternal) target="_blank" rel="noopener" @endif
                   class="hidden sm:inline text-[13.5px] font-bold text-[#16132B] hover:text-[color:var(--brand)] transition">{{ $ctaText }}</a>
                @endif
                <a href="{{ route('login') }}" class="mobile-nav-login inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#16132B] text-white text-[13.5px] font-bold shadow-[0_4px_14px_rgba(22,19,43,0.25)] hover:-translate-y-0.5 hover:shadow-[0_8px_20px_rgba(22,19,43,0.32)] transition">
                    Login <i class="fa-solid fa-arrow-right text-[11px]"></i>
                </a>
            </div>
        </div>
    </header>

    {{-- ═══════════ HERO ═══════════ --}}
    <section id="devHeroSection" class="relative overflow-hidden"
         @if($hasHeroBg)
         style="background:#0F0B24;"
         @else
         style="background: linear-gradient(180deg, #FBFAFF 0%, #F3F1FC 100%);"
         @endif>
        @if(!$hasHeroBg)
        <div class="hero-blob hero-blob-1"></div>
        <div class="hero-blob hero-blob-2"></div>
        <div class="hero-blob hero-blob-3"></div>
        @endif
        @if($hasHeroBg)
            @if($heroBgIsVideo)
            <video src="{{ $heroBgUrl }}" autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover"></video>
            @else
            <img src="{{ $heroBgUrl }}" alt="" class="absolute inset-0 w-full h-full object-cover">
            @endif
            <div class="absolute inset-0" style="background: rgba(15,11,36,{{ number_format(max($heroBgOverlay, 40) / 100, 2) }});"></div>
        @endif

        @if($devEditOn)
        <div id="devHeroDropOverlay" style="display:none;position:absolute;inset:0;z-index:35;background:rgba(99,102,241,0.25);border:3px dashed #fff;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;">
            Drop image or video to set as hero background
        </div>
        <div style="position:absolute;top:14px;left:14px;z-index:36;display:flex;align-items:center;gap:8px;flex-wrap:wrap;background:rgba(17,24,39,0.62);backdrop-filter:blur(6px);padding:8px 12px;border-radius:12px;color:#fff;font-size:11.5px;font-weight:600;">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                <i class="fa-solid fa-image"></i> {{ $hasHeroBg ? 'Change background' : 'Add background' }}
                <input type="file" id="devHeroBgInput" accept="image/png,image/jpeg,image/webp,video/mp4,video/webm,video/quicktime" style="display:none;">
            </label>
            @if($hasHeroBg)
            <button type="button" id="devHeroBgRemove" title="Remove background" style="background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:8px;padding:4px 9px;cursor:pointer;">
                <i class="fa-solid fa-trash"></i>
            </button>
            <span style="display:flex;align-items:center;gap:5px;padding-left:6px;border-left:1px solid rgba(255,255,255,0.25);">
                <i class="fa-solid fa-circle-half-stroke" title="Overlay darkness"></i>
                <input type="range" id="devHeroOverlayRange" min="0" max="80" step="5" value="{{ $heroBgOverlay }}" style="width:80px;accent-color:#fff;">
            </span>
            @endif
        </div>
        @endif

        <div class="relative z-10 text-center max-w-2xl mx-auto px-6 pt-16 pb-8 md:pt-24">
            <h1 class="mobile-hero-title font-extrabold tracking-tight leading-[1.04] mb-4 {{ $hasHeroBg ? 'text-white' : '' }}" style="font-size: clamp(34px, 6vw, 58px); letter-spacing: -0.03em;">
                About <span class="gradient-text block">{{ $teamName }}</span>
            </h1>
            <p class="mobile-hero-intro text-base leading-relaxed max-w-md mx-auto {{ $hasHeroBg ? 'text-white/85' : 'text-gray-500' }} {{ $devEditOn ? 'dev-editable' : '' }}"
               @if($devEditOn) contenteditable="true" data-key="about_page_intro" data-placeholder="{{ e($introText) }}" @endif>{{ $devEditOn ? $introRaw : $introText }}</p>

            <div class="mt-7 flex items-center justify-center gap-5 flex-wrap">
                @if($ctaEnabled)
                <a href="{{ $ctaLink }}" @if($ctaExternal) target="_blank" rel="noopener" @endif
                   class="inline-flex items-center gap-2 px-8 py-3.5 rounded-full bg-[#16132B] text-white font-bold text-sm shadow-[0_8px_22px_rgba(22,19,43,0.28)] hover:-translate-y-0.5 hover:shadow-[0_10px_26px_rgba(22,19,43,0.35)] transition">
                    {{ $ctaText }} <i class="fa-solid fa-arrow-right"></i>
                </a>
                @endif
            </div>

            @if($devEditOn)
            <div class="dev-hideable" data-hidden="{{ $ctaEnabled ? '0' : '1' }}"
                 style="display:inline-flex;align-items:center;gap:8px;background:#F3F4F6;border-radius:999px;padding:6px 6px 6px 14px;margin-top:14px;font-size:12.5px;color:#374151;">
                <span>CTA button:</span>
                <span class="dev-editable" contenteditable="true" data-key="about_page_cta_text" data-single-line="1"
                      data-placeholder="Get in Touch" style="font-weight:700;">{{ $ctaTextRaw }}</span>
                <button type="button" class="dev-cta-link-btn" data-current="{{ $ctaLink }}" title="Edit link"
                        style="width:24px;height:24px;border-radius:50%;border:none;background:#E5E7EB;color:#374151;cursor:pointer;">
                    <i class="fa-solid fa-link" style="font-size:10px;"></i>
                </button>
                <button type="button" class="dev-eye-btn" data-key="about_page_cta_enabled"
                        style="position:static;background:#E5E7EB;color:#374151;width:24px;height:24px;">
                    <i class="fa-solid {{ $ctaEnabled ? 'fa-eye' : 'fa-eye-slash' }}" style="font-size:10px;"></i>
                </button>
            </div>
            @endif
        </div>

        @if($waveCards->isNotEmpty())
        <div class="wave-row relative z-10 flex items-end justify-center gap-6 max-w-5xl mx-auto px-6 pt-16 pb-14 flex-wrap">
            @foreach($waveCards as $i => $card)
            @php $person = $card['person']; @endphp
            <div class="wave-card relative flex flex-col items-center {{ $devEditOn ? 'dev-draggable-card' : '' }}" style="margin-top:-{{ $card['lift'] }}px; animation-delay:{{ 0.08 * $i }}s;"
                 @if($devEditOn) draggable="true" data-member-id="{{ $person->id }}" @endif>
                <div class="wave-label absolute -top-[50px] left-1/2 -translate-x-1/2 bg-white rounded-xl px-3.5 py-1.5 text-center whitespace-nowrap">
                    <p class="text-[12.5px] font-extrabold m-0 text-[#16132B]">{{ $person->name }}</p>
                    <p class="text-[10.5px] font-semibold m-0 mt-0.5 text-[color:var(--brand)]">{{ $person->job_title ?: ucfirst($person->role) }}</p>
                </div>
                <div class="wave-portrait overflow-hidden" style="width:{{ $card['w'] }}px; height:{{ $card['h'] }}px; background:linear-gradient(160deg,{{ $card['colors'][0] }},{{ $card['colors'][1] }});">
                    @if($person->avatar)
                        <img src="{{ Storage::url($person->avatar) }}" alt="{{ $person->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-white font-extrabold text-2xl">{{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="relative z-10 flex justify-center pb-8">
            <a href="#who-we-are" class="w-9 h-9 rounded-full bg-white shadow-[0_4px_14px_rgba(22,19,43,0.14)] flex items-center justify-center text-[color:var(--brand)] animate-bounce">
                <i class="fa-solid fa-chevron-down text-xs"></i>
            </a>
        </div>
    </section>

    {{-- ═══════════ WHO WE ARE ═══════════ --}}
    @if($showWho)
    <section id="who-we-are" class="relative max-w-5xl mx-auto px-6 py-20 md:py-28 {{ $devEditOn ? 'dev-hideable' : '' }}" @if($devEditOn) data-hidden="{{ $sectionOn['who'] ? '0' : '1' }}" @endif>
        @if($devEditOn)
        <button type="button" class="dev-eye-btn" data-key="about_page_show_who"><i class="fa-solid {{ $sectionOn['who'] ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
        @endif
        <div class="reveal grid grid-cols-1 md:grid-cols-[1.1fr_0.9fr] gap-12 items-center">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[color:var(--brand)] mb-3">Our Story</p>
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight mb-5" style="letter-spacing:-0.02em;">Who We Are</h2>
                <p class="text-gray-500 leading-relaxed text-[15px] {{ $devEditOn ? 'dev-editable' : '' }}"
                   @if($devEditOn) contenteditable="true" data-key="about_page_who_text" data-placeholder="{{ e($whoText) }}" @endif>{{ $devEditOn ? $whoTextRaw : $whoText }}</p>
            </div>
            <div class="relative">
                <div class="glass rounded-[28px] p-8 shadow-[0_20px_60px_-20px_rgba(79,70,229,0.3)] border border-white/60">
                    <div class="flex -space-x-3 mb-5">
                        @foreach($teamMembers->take(5) as $person)
                            @if($person->avatar)
                                <img src="{{ Storage::url($person->avatar) }}" alt="{{ $person->name }}" class="w-11 h-11 rounded-full object-cover border-2 border-white shadow-sm">
                            @else
                                <div class="w-11 h-11 rounded-full border-2 border-white shadow-sm flex items-center justify-center text-white text-sm font-bold brand-grad">{{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}</div>
                            @endif
                        @endforeach
                    </div>
                    <p class="text-lg font-bold text-[#16132B] leading-snug">"<span class="{{ $devEditOn ? 'dev-editable' : '' }}"
                       @if($devEditOn) contenteditable="true" data-key="about_page_quote_text" data-placeholder="{{ e($quoteText) }}" @endif>{{ $devEditOn ? $quoteTextRaw : $quoteText }}</span>"</p>
                    <p class="text-xs font-semibold text-gray-400 mt-3 uppercase tracking-wide">— The {{ $metaCompanyName }} Team</p>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════ STATISTICS ═══════════ --}}
    @if($showStats)
    <section class="relative max-w-5xl mx-auto px-6 pb-20 md:pb-28 {{ $devEditOn ? 'dev-hideable' : '' }}" @if($devEditOn) data-hidden="{{ $sectionOn['stats'] ? '0' : '1' }}" @endif>
        @if($devEditOn)
        <button type="button" class="dev-eye-btn" data-key="about_page_show_stats"><i class="fa-solid {{ $sectionOn['stats'] ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
        @endif
        <div class="reveal grid grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($stats as $i => $stat)
            <div class="mobile-stat-card bg-white rounded-3xl p-6 text-center shadow-[0_10px_30px_-14px_rgba(22,19,43,0.15)] border border-black/[0.03] hover:-translate-y-1 hover:shadow-[0_16px_36px_-14px_rgba(22,19,43,0.22)] transition {{ $devEditOn ? 'dev-hideable' : '' }}"
                 @if($devEditOn) data-hidden="{{ $stat['hidden'] ? '1' : '0' }}" @endif>
                @if($devEditOn)
                <button type="button" class="dev-eye-btn" data-key="about_page_stat{{ $stat['slot'] }}_hidden"><i class="fa-solid {{ $stat['hidden'] ? 'fa-eye-slash' : 'fa-eye' }}"></i></button>
                @endif
                <div class="w-11 h-11 rounded-2xl mx-auto mb-3 flex items-center justify-center text-white brand-grad">
                    <i class="fa-solid {{ $stat['icon'] }}"></i>
                </div>
                <p class="text-3xl md:text-4xl font-extrabold tracking-tight text-[#16132B]" style="font-variant-numeric: tabular-nums;">
                    @if($devEditOn && !empty($stat['editable']))
                    <span class="dev-editable" contenteditable="true" data-key="about_page_stat{{ $stat['slot'] }}_value"
                          data-single-line="1" data-placeholder="0">{{ $stat['value'] }}</span>{{ $stat['suffix'] }}
                    @else
                    <span class="stat-num" data-target="{{ $stat['value'] ?? 0 }}">0</span>{{ $stat['suffix'] }}
                    @endif
                </p>
                <p class="text-[12.5px] font-semibold text-gray-400 mt-1">{{ $stat['label'] }}</p>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ═══════════ MISSION & VISION ═══════════ --}}
    @if($showMissionVision)
    @php $missionVisionBoth = $showMission && $showVision; @endphp
    <section class="relative max-w-5xl mx-auto px-6 pb-20 md:pb-28 {{ $devEditOn ? 'dev-hideable' : '' }}" @if($devEditOn) data-hidden="{{ $sectionOn['mission_vision'] ? '0' : '1' }}" @endif>
        @if($devEditOn)
        <button type="button" class="dev-eye-btn" data-key="about_page_show_mission_vision"><i class="fa-solid {{ $sectionOn['mission_vision'] ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
        @endif
        <div class="reveal grid grid-cols-1 {{ $missionVisionBoth ? 'md:grid-cols-2 gap-6' : 'gap-6 max-w-lg mx-auto' }}">
            @if($showMission)
            <div class="rounded-[28px] p-9 text-white shadow-[0_20px_50px_-18px_rgba(79,70,229,0.45)] {{ $devEditOn ? 'dev-hideable' : '' }}" style="background: linear-gradient(150deg, var(--brand), color-mix(in srgb, var(--brand) 60%, black));"
                 @if($devEditOn) data-hidden="{{ $sectionOn['mission'] ? '0' : '1' }}" @endif>
                @if($devEditOn)
                <button type="button" class="dev-eye-btn" data-key="about_page_mission_hidden" style="background:rgba(255,255,255,0.2);"><i class="fa-solid {{ $sectionOn['mission'] ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
                @endif
                <div class="w-12 h-12 rounded-2xl bg-white/15 flex items-center justify-center mb-5"><i class="fa-solid fa-bullseye text-lg"></i></div>
                <h3 class="text-xl font-extrabold mb-3 tracking-tight">Our Mission</h3>
                <p class="text-white/85 leading-relaxed text-[14.5px] {{ $devEditOn ? 'dev-editable dev-editable-on-brand' : '' }}"
                   @if($devEditOn) contenteditable="true" data-key="about_page_mission" data-placeholder="{{ e($mission) }}" @endif>{{ $devEditOn ? $missionRaw : $mission }}</p>
            </div>
            @endif
            @if($showVision)
            <div class="rounded-[28px] p-9 text-white shadow-[0_20px_50px_-18px_rgba(145,99,170,0.45)] {{ $devEditOn ? 'dev-hideable' : '' }}" style="background: linear-gradient(150deg, var(--brand-2), color-mix(in srgb, var(--brand-2) 55%, black));"
                 @if($devEditOn) data-hidden="{{ $sectionOn['vision'] ? '0' : '1' }}" @endif>
                @if($devEditOn)
                <button type="button" class="dev-eye-btn" data-key="about_page_vision_hidden" style="background:rgba(255,255,255,0.2);"><i class="fa-solid {{ $sectionOn['vision'] ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
                @endif
                <div class="w-12 h-12 rounded-2xl bg-white/15 flex items-center justify-center mb-5"><i class="fa-solid fa-binoculars text-lg"></i></div>
                <h3 class="text-xl font-extrabold mb-3 tracking-tight">Our Vision</h3>
                <p class="text-white/85 leading-relaxed text-[14.5px] {{ $devEditOn ? 'dev-editable dev-editable-on-brand' : '' }}"
                   @if($devEditOn) contenteditable="true" data-key="about_page_vision" data-placeholder="{{ e($vision) }}" @endif>{{ $devEditOn ? $visionRaw : $vision }}</p>
            </div>
            @endif
        </div>
    </section>
    @endif

    {{-- ═══════════ CORE VALUES ═══════════ --}}
    @if($showValues)
    <section class="relative max-w-5xl mx-auto px-6 pb-20 md:pb-28 {{ $devEditOn ? 'dev-hideable' : '' }}" @if($devEditOn) data-hidden="{{ $sectionOn['values'] ? '0' : '1' }}" @endif>
        @if($devEditOn)
        <button type="button" class="dev-eye-btn" data-key="about_page_show_values"><i class="fa-solid {{ $sectionOn['values'] ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
        @endif
        <div class="reveal text-center mb-12">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[color:var(--brand)] mb-3">What Drives Us</p>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight" style="letter-spacing:-0.02em;">Core Values</h2>
        </div>
        <div class="reveal grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($values as $i => $value)
            <div class="bg-white rounded-3xl p-7 shadow-[0_10px_30px_-16px_rgba(22,19,43,0.15)] border border-black/[0.03] hover:-translate-y-1 transition {{ $devEditOn ? 'dev-hideable' : '' }}" style="transition-delay:{{ $i * 40 }}ms;"
                 @if($devEditOn) data-hidden="{{ $value['hidden'] ? '1' : '0' }}" @endif>
                @if($devEditOn)
                <button type="button" class="dev-eye-btn" data-key="about_page_value{{ $value['slot'] }}_hidden"><i class="fa-solid {{ $value['hidden'] ? 'fa-eye-slash' : 'fa-eye' }}"></i></button>
                @endif
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4 text-[color:var(--brand)]" style="background: var(--brand-soft);">
                    <i class="fa-solid {{ $value['icon'] }} text-lg"></i>
                </div>
                <h4 class="font-extrabold text-[16px] mb-1.5 tracking-tight {{ $devEditOn ? 'dev-editable' : '' }}"
                    @if($devEditOn) contenteditable="true" data-key="about_page_value{{ $value['slot'] }}_title" data-single-line="1" data-placeholder="{{ e($value['title']) }}" @endif>{{ $devEditOn ? $value['title_raw'] : $value['title'] }}</h4>
                <p class="text-[13px] text-gray-500 leading-relaxed {{ $devEditOn ? 'dev-editable' : '' }}"
                   @if($devEditOn) contenteditable="true" data-key="about_page_value{{ $value['slot'] }}_desc" data-placeholder="{{ e($value['desc']) }}" @endif>{{ $devEditOn ? $value['desc_raw'] : $value['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ═══════════ MEET OUR TEAM ═══════════ --}}
    @if($showTeam)
    <section class="relative max-w-5xl mx-auto px-6 pb-20 md:pb-28 {{ $showFullGridOnDesktop ? '' : 'lg:hidden' }} {{ $devEditOn ? 'dev-hideable' : '' }}" @if($devEditOn) data-hidden="{{ $sectionOn['team'] ? '0' : '1' }}" @endif>
        @if($devEditOn)
        <button type="button" class="dev-eye-btn" data-key="about_page_show_team"><i class="fa-solid {{ $sectionOn['team'] ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
        @endif
        <div class="reveal text-center mb-12">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[color:var(--brand)] mb-3">The People</p>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight" style="letter-spacing:-0.02em;">Meet Our Team</h2>
        </div>

        @if($teamMembers->isEmpty())
            <p class="text-center text-gray-400 text-sm py-10">No team members to show yet.</p>
        @else
            <div class="mobile-team-grid reveal grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                @foreach($teamMembers as $i => $member)
                <div class="mobile-team-card bg-white rounded-[22px] p-6 text-center shadow-[0_4px_20px_rgba(99,102,241,0.08)] border border-black/[0.03] hover:-translate-y-1 hover:shadow-[0_12px_28px_rgba(99,102,241,0.16)] transition {{ $devEditOn ? 'dev-draggable-card' : '' }}" style="transition-delay:{{ $i * 30 }}ms;"
                     @if($devEditOn) draggable="true" data-member-id="{{ $member->id }}" @endif>
                    @if($member->avatar)
                        <img src="{{ Storage::url($member->avatar) }}" alt="{{ $member->name }}" class="w-[72px] h-[72px] rounded-full mx-auto mb-3.5 object-cover shadow-[0_4px_14px_rgba(0,0,0,0.1)]">
                    @else
                        <div class="w-[72px] h-[72px] rounded-full mx-auto mb-3.5 flex items-center justify-center font-extrabold text-2xl text-white brand-grad">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</div>
                    @endif
                    <p class="font-bold text-[14.5px] mb-0.5">{{ $member->name }}</p>
                    <p class="text-xs font-semibold text-[color:var(--brand)] capitalize">{{ $member->job_title ?: $member->role }}</p>
                </div>
                @endforeach
            </div>
        @endif
    </section>
    @endif

    {{-- ═══════════ OUR JOURNEY ═══════════ --}}
    @if($showJourney)
    <section class="relative max-w-3xl mx-auto px-6 pb-20 md:pb-28 {{ $devEditOn ? 'dev-hideable' : '' }}" @if($devEditOn) data-hidden="{{ $sectionOn['journey'] ? '0' : '1' }}" @endif>
        @if($devEditOn)
        <button type="button" class="dev-eye-btn" data-key="about_page_show_journey"><i class="fa-solid {{ $sectionOn['journey'] ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
        @endif
        <div class="reveal text-center mb-14">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[color:var(--brand)] mb-3">How Far We've Come</p>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight" style="letter-spacing:-0.02em;">Our Journey</h2>
        </div>
        <div class="relative">
            <div class="journey-line absolute left-[19px] md:left-1/2 top-0 bottom-0 w-[2px] md:-translate-x-1/2"></div>
            <div class="space-y-10">
                @foreach($journey as $i => $stop)
                <div class="reveal relative flex md:justify-center" style="transition-delay:{{ $i * 90 }}ms;">
                    <div class="flex md:contents">
                        <div class="journey-dot relative z-10 w-10 h-10 rounded-full brand-grad flex items-center justify-center text-white shrink-0 md:absolute md:left-1/2 md:-translate-x-1/2">
                            <i class="fa-solid {{ $stop['icon'] }} text-[13px]"></i>
                        </div>
                        <div class="ml-5 md:ml-0 md:w-[calc(50%-40px)] {{ $i % 2 === 0 ? 'md:mr-auto md:text-right md:pr-[56px]' : 'md:ml-auto md:pl-[56px]' }}">
                            <div class="mobile-journey-card bg-white rounded-2xl p-5 shadow-[0_10px_30px_-16px_rgba(22,19,43,0.18)] border border-black/[0.03] inline-block text-left {{ $devEditOn ? 'dev-hideable' : '' }}"
                                 @if($devEditOn) data-hidden="{{ $stop['hidden'] ? '1' : '0' }}" @endif>
                                @if($devEditOn)
                                <button type="button" class="dev-eye-btn" data-key="about_page_journey{{ $stop['slot'] }}_hidden"><i class="fa-solid {{ $stop['hidden'] ? 'fa-eye-slash' : 'fa-eye' }}"></i></button>
                                @endif
                                <h4 class="font-extrabold text-[15px] mb-1 tracking-tight">{{ $stop['title'] }}</h4>
                                <p class="text-[13px] text-gray-500 leading-relaxed m-0">{{ $stop['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════ PRODUCTS & SERVICES ═══════════ --}}
    @if($showServices)
    <section class="relative max-w-5xl mx-auto px-6 pb-20 md:pb-28 {{ $devEditOn ? 'dev-hideable' : '' }}" @if($devEditOn) data-hidden="{{ $sectionOn['services'] ? '0' : '1' }}" @endif>
        @if($devEditOn)
        <button type="button" class="dev-eye-btn" data-key="about_page_show_services"><i class="fa-solid {{ $sectionOn['services'] ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
        @endif
        <div class="reveal text-center mb-12">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[color:var(--brand)] mb-3">What We Offer</p>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight {{ $devEditOn ? 'dev-editable' : '' }}"
                @if($devEditOn) contenteditable="true" data-key="about_page_services_heading" data-single-line="1" data-placeholder="What We Do" @endif
                style="letter-spacing:-0.02em;">{{ $devEditOn ? $servicesHeadingRaw : ($appSettings['about_page_services_heading'] ?: 'What We Do') }}</h2>
        </div>
        <div class="reveal grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($services as $i => $service)
            <div class="bg-white rounded-3xl p-7 text-center shadow-[0_14px_34px_-14px_rgba(22,19,43,0.16)] border border-black/[0.03] hover:-translate-y-1 hover:shadow-[0_20px_40px_-14px_rgba(22,19,43,0.22)] transition {{ $devEditOn ? 'dev-hideable' : '' }}" style="transition-delay:{{ $i * 40 }}ms;"
                 @if($devEditOn) data-hidden="{{ $service['hidden'] ? '1' : '0' }}" @endif>
                @if($devEditOn)
                <button type="button" class="dev-eye-btn" data-key="about_page_service{{ $service['slot'] }}_hidden"><i class="fa-solid {{ $service['hidden'] ? 'fa-eye-slash' : 'fa-eye' }}"></i></button>
                @endif
                <div class="w-14 h-14 rounded-full mx-auto mb-4 flex items-center justify-center text-white brand-grad text-lg shadow-[0_10px_20px_-6px_rgba(79,70,229,0.5)]">
                    <i class="fa-solid {{ $service['icon'] }}"></i>
                </div>
                <h4 class="font-extrabold text-[16px] mb-1.5 tracking-tight {{ $devEditOn ? 'dev-editable' : '' }}"
                    @if($devEditOn) contenteditable="true" data-key="about_page_service{{ $service['slot'] }}_title" data-single-line="1" data-placeholder="{{ e($service['title']) }}" @endif>{{ $devEditOn ? $service['title_raw'] : $service['title'] }}</h4>
                <p class="text-[13px] text-gray-500 leading-relaxed {{ $devEditOn ? 'dev-editable' : '' }}"
                   @if($devEditOn) contenteditable="true" data-key="about_page_service{{ $service['slot'] }}_desc" data-placeholder="{{ e($service['desc']) }}" @endif>{{ $devEditOn ? $service['desc_raw'] : $service['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ═══════════ LIFE AT [COMPANY] (GALLERY) ═══════════ --}}
    @if($showGallery)
    <section class="relative max-w-5xl mx-auto px-6 pb-20 md:pb-28 {{ $devEditOn ? 'dev-hideable' : '' }}" @if($devEditOn) data-hidden="{{ $sectionOn['gallery'] ? '0' : '1' }}" @endif>
        @if($devEditOn)
        <button type="button" class="dev-eye-btn" data-key="about_page_show_gallery"><i class="fa-solid {{ $sectionOn['gallery'] ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
        @endif
        <div class="reveal text-center mb-12">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[color:var(--brand)] mb-3">Behind The Scenes</p>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight" style="letter-spacing:-0.02em;">Life at {{ $metaCompanyName }}</h2>
        </div>

        @if($hasArtwork)
        <div class="reveal columns-2 md:columns-3 gap-4 [column-fill:balance]">
            @foreach($artworkUrls as $i => $url)
            <button type="button" @click="artworkOpen = true; artworkIndex = {{ $i }}; artworkRestart()"
                    class="relative block w-full mb-4 rounded-[18px] overflow-hidden border-0 p-0 cursor-pointer bg-gray-200 shadow-[0_10px_26px_-10px_rgba(22,19,43,0.25)] hover:-translate-y-1 hover:scale-[1.01] transition break-inside-avoid"
                    style="aspect-ratio: {{ [4,3,1][$i % 3] === 1 ? '1/1' : ($i % 3 === 1 ? '3/4' : '4/3') }};">
                @if(preg_match('/\.(mp4|webm|mov|m4v)(\?.*)?$/i', $url))
                    <video src="{{ $url }}#t=0.1" preload="metadata" muted class="w-full h-full object-cover block"></video>
                    <div class="absolute inset-0 flex items-center justify-center bg-black/25">
                        <i class="fa-solid fa-play w-[46px] h-[46px] rounded-full bg-white/92 text-[#16132B] flex items-center justify-center text-base"></i>
                    </div>
                @else
                    <img src="{{ $url }}" alt="Gallery image {{ $i + 1 }}" class="w-full h-full object-cover block">
                @endif
            </button>
            @endforeach
        </div>
        @else
        <div class="reveal bg-white rounded-[28px] p-16 text-center border border-dashed border-black/10">
            <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center text-[color:var(--brand)]" style="background: var(--brand-soft);">
                <i class="fa-solid fa-images text-xl"></i>
            </div>
            <p class="text-gray-400 text-sm font-semibold">Photos and videos from the team are coming soon.</p>
        </div>
        @endif
    </section>
    @endif

    {{-- ═══════════ ARTWORK LIGHTBOX ═══════════ --}}
    @if($hasArtwork && $showGallery)
    <div x-show="artworkOpen" x-cloak class="team-artwork-backdrop" @click.self="artworkOpen = false; artworkStop()"
         @keydown.window.escape="artworkOpen = false; artworkStop()"
         @keydown.window.arrow-left="if (artworkOpen) { artworkPrev(); artworkRestart() }"
         @keydown.window.arrow-right="if (artworkOpen) { artworkNext(); artworkRestart() }">
        <div class="team-artwork-card">
            <button type="button" class="team-artwork-close" @click="artworkOpen = false; artworkStop()">
                <i class="fa-solid fa-times"></i>
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
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                </template>
                <template x-if="artworkImgs.length > 1">
                    <button type="button" class="team-artwork-nav next" @click.stop="artworkNext(); artworkRestart()">
                        <i class="fa-solid fa-chevron-right"></i>
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

    {{-- ═══════════ FINAL CTA ═══════════ --}}
    <section class="relative max-w-5xl mx-auto px-6 pb-20">
        <div class="reveal relative overflow-hidden rounded-[28px] px-8 py-16 text-center shadow-[0_24px_50px_-18px_rgba(22,19,43,0.5)]" style="background: linear-gradient(135deg, #1E1B4B 0%, #16132B 100%);">
            <div class="absolute inset-0 opacity-50" style="background: radial-gradient(50% 80% at 15% 20%, color-mix(in srgb, var(--brand) 40%, transparent), transparent 70%), radial-gradient(50% 80% at 85% 80%, rgba(219,39,119,0.28), transparent 70%);"></div>
            <div class="relative">
                <h2 class="text-white font-extrabold mb-6" style="font-size: clamp(24px,3.4vw,34px); letter-spacing:-0.02em;">Let's build something great.</h2>
                <div class="flex items-center justify-center gap-4 flex-wrap">
                    @if($ctaEnabled)
                    <a href="{{ $ctaLink }}" @if($ctaExternal) target="_blank" rel="noopener" @endif
                       class="inline-flex items-center gap-2 px-8 py-3.5 rounded-full bg-white text-[#16132B] font-bold text-sm shadow-[0_10px_24px_-8px_rgba(0,0,0,0.4)] hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-8px_rgba(0,0,0,0.5)] transition">
                        {{ $ctaText }} <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    @endif
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-full border border-white/25 text-white font-bold text-sm hover:bg-white/10 transition">
                        Explore the platform
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════ FOOTER (reused, not redesigned) ═══════════ --}}
    <footer class="relative z-10 text-center px-6 py-8 text-[12.5px] text-gray-400 border-t border-black/5">
        @if($ctaEnabled)
        <div class="flex items-center justify-center gap-5 flex-wrap mb-2.5">
            <a href="{{ $ctaLink }}" @if($ctaExternal) target="_blank" rel="noopener" @endif
               class="text-[color:var(--brand)] font-bold no-underline hover:underline text-[13px]">{{ $ctaText }}</a>
        </div>
        @endif
        {{ $metaCompanyName }}
        @if(!empty($appSettings['copyright']))
            · {{ $appSettings['copyright'] }}
        @else
            · © {{ date('Y') }}
        @endif
    </footer>
</div>

<script>
    (function () {
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Scroll reveal
        var revealEls = document.querySelectorAll('.reveal');
        if (reduceMotion || !('IntersectionObserver' in window)) {
            revealEls.forEach(function (el) { el.classList.add('is-visible'); });
        } else {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            revealEls.forEach(function (el) { io.observe(el); });
        }

        // Stat count-up
        var statEls = document.querySelectorAll('.stat-num');
        if (reduceMotion || !('IntersectionObserver' in window)) {
            statEls.forEach(function (el) { el.textContent = el.dataset.target; });
        } else {
            var statIo = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var el = entry.target;
                    var target = parseInt(el.dataset.target, 10) || 0;
                    var start = null;
                    var duration = 1200;
                    function step(ts) {
                        if (!start) start = ts;
                        var progress = Math.min((ts - start) / duration, 1);
                        var eased = 1 - Math.pow(1 - progress, 3);
                        el.textContent = Math.round(eased * target);
                        if (progress < 1) requestAnimationFrame(step);
                    }
                    requestAnimationFrame(step);
                    statIo.unobserve(el);
                });
            }, { threshold: 0.5 });
            statEls.forEach(function (el) { statIo.observe(el); });
        }
    })();
</script>

@if($devEditOn)
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var saveUrl = @json(route('admin.settings.about-page.field'));

    function saveField(key, value) {
        fetch(saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ key: key, value: value }),
        });
    }

    // Editable text — save on blur, only if it actually changed
    document.querySelectorAll('.dev-editable[data-key]').forEach(function (el) {
        var original = el.innerText;
        el.addEventListener('blur', function () {
            var current = el.innerText.trim();
            if (current === original) return;
            original = current;
            saveField(el.dataset.key, current);
        });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && el.dataset.singleLine === '1') { e.preventDefault(); el.blur(); }
        });
    });

    // Eye-icon hide/show toggles — flip immediately, save in background
    document.querySelectorAll('.dev-eye-btn[data-key]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var card = btn.closest('.dev-hideable');
            var nowHidden = card.dataset.hidden !== '1';
            card.dataset.hidden = nowHidden ? '1' : '0';
            var icon = btn.querySelector('i');
            icon.className = 'fa-solid ' + (nowHidden ? 'fa-eye-slash' : 'fa-eye');
            saveField(btn.dataset.key, nowHidden);
        });
    });

    // CTA link — no visible text to click, use a small prompt
    document.querySelectorAll('.dev-cta-link-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var next = prompt('Button link (mailto:, https://, or a path):', btn.dataset.current || '');
            if (next === null) return;
            btn.dataset.current = next;
            saveField('about_page_cta_link', next);
        });
    });

    // Hero background — upload (click or drag-and-drop) and remove
    var heroSection = document.getElementById('devHeroSection');
    var heroBgUrl = @json(route('admin.settings.about-page.hero-bg'));

    function uploadHeroBg(file) {
        var form = new FormData();
        form.append('about_page_bg_image', file);
        fetch(heroBgUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            body: form,
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data.success) location.reload();
            else alert(data.message || 'Upload failed.');
        });
    }

    var heroInput = document.getElementById('devHeroBgInput');
    if (heroInput) {
        heroInput.addEventListener('change', function () {
            if (heroInput.files[0]) uploadHeroBg(heroInput.files[0]);
        });
    }

    var heroRemoveBtn = document.getElementById('devHeroBgRemove');
    if (heroRemoveBtn) {
        heroRemoveBtn.addEventListener('click', function () {
            if (!confirm('Remove the hero background?')) return;
            var form = new FormData();
            form.append('remove', '1');
            fetch(heroBgUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                body: form,
            }).then(function () { location.reload(); });
        });
    }

    var heroOverlayRange = document.getElementById('devHeroOverlayRange');
    if (heroOverlayRange) {
        heroOverlayRange.addEventListener('change', function () {
            saveField('about_page_bg_overlay', parseInt(heroOverlayRange.value, 10));
        });
    }

    if (heroSection) {
        var heroDropOverlay = document.getElementById('devHeroDropOverlay');
        heroSection.addEventListener('dragover', function (e) {
            e.preventDefault();
            // Only show the background drop overlay for real OS file drags — not for wave-card reordering below
            if (heroDropOverlay && e.dataTransfer.types.includes('Files')) heroDropOverlay.style.display = 'flex';
        });
        heroSection.addEventListener('dragleave', function (e) {
            if (e.target === heroSection && heroDropOverlay) heroDropOverlay.style.display = 'none';
        });
        heroSection.addEventListener('drop', function (e) {
            e.preventDefault();
            if (heroDropOverlay) heroDropOverlay.style.display = 'none';
            var file = e.dataTransfer.files[0];
            if (file) uploadHeroBg(file);
        });
    }

    // Team card reordering — drag a wave-row or "Meet Our Team" card onto another to swap their positions.
    // Both sections render the same underlying order, so one array (synced with the server's current order)
    // drives the save regardless of which section the drag happened in.
    var teamOrder = @json($teamMembers->pluck('id'));
    var teamOrderUrl = @json(route('admin.settings.about-page.team-order'));
    var draggedMemberId = null;

    document.querySelectorAll('[data-member-id]').forEach(function (card) {
        card.addEventListener('dragstart', function (e) {
            draggedMemberId = parseInt(card.dataset.memberId, 10);
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(draggedMemberId));
            card.classList.add('dev-drag-dragging');
        });
        card.addEventListener('dragend', function () {
            card.classList.remove('dev-drag-dragging');
            document.querySelectorAll('.dev-drag-over').forEach(function (el) { el.classList.remove('dev-drag-over'); });
        });
        card.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (draggedMemberId !== null && parseInt(card.dataset.memberId, 10) !== draggedMemberId) {
                card.classList.add('dev-drag-over');
            }
        });
        card.addEventListener('dragleave', function () {
            card.classList.remove('dev-drag-over');
        });
        card.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            card.classList.remove('dev-drag-over');
            var targetId = parseInt(card.dataset.memberId, 10);
            if (draggedMemberId === null || targetId === draggedMemberId) return;

            var from = teamOrder.indexOf(draggedMemberId);
            var to = teamOrder.indexOf(targetId);
            if (from === -1 || to === -1) return;
            teamOrder.splice(to, 0, teamOrder.splice(from, 1)[0]);

            fetch(teamOrderUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ order: teamOrder }),
            }).then(function () { location.reload(); });
        });
    });
})();
</script>
@endif

</body>
</html>

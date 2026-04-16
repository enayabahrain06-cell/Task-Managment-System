<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $appSettings['app_name'] ?? config('app.name', 'Dash') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; min-height: 100vh;
               background: linear-gradient(135deg, #fce4ec 0%, #ede6f8 40%, #dce8fb 100%);
               display: flex; align-items: center; justify-content: center; padding: 16px; }

        /* Floating orbit icons */
        @keyframes orbit1 { 0%{transform:rotate(0deg) translateX(90px) rotate(0deg)} 100%{transform:rotate(360deg) translateX(90px) rotate(-360deg)} }
        @keyframes orbit2 { 0%{transform:rotate(60deg) translateX(110px) rotate(-60deg)} 100%{transform:rotate(420deg) translateX(110px) rotate(-420deg)} }
        @keyframes orbit3 { 0%{transform:rotate(120deg) translateX(75px) rotate(-120deg)} 100%{transform:rotate(480deg) translateX(75px) rotate(-480deg)} }
        @keyframes orbit4 { 0%{transform:rotate(200deg) translateX(100px) rotate(-200deg)} 100%{transform:rotate(560deg) translateX(100px) rotate(-560deg)} }
        @keyframes orbit5 { 0%{transform:rotate(280deg) translateX(120px) rotate(-280deg)} 100%{transform:rotate(640deg) translateX(120px) rotate(-640deg)} }
        @keyframes orbit6 { 0%{transform:rotate(330deg) translateX(85px) rotate(-330deg)} 100%{transform:rotate(690deg) translateX(85px) rotate(-690deg)} }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
        @keyframes pulse-ring { 0%{transform:scale(1);opacity:0.4} 100%{transform:scale(1.6);opacity:0} }

        .orbit-icon { position:absolute; top:50%; left:50%; width:38px; height:38px; margin:-19px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 15px rgba(0,0,0,0.15); }
        .o1 { animation:orbit1 8s linear infinite; }
        .o2 { animation:orbit2 12s linear infinite; }
        .o3 { animation:orbit3 9s linear infinite reverse; }
        .o4 { animation:orbit4 11s linear infinite; }
        .o5 { animation:orbit5 10s linear infinite reverse; }
        .o6 { animation:orbit6 7s linear infinite; }

        .pulse-ring { position:absolute; width:100%; height:100%; border-radius:50%; border:2px solid rgba(255,255,255,0.3); animation: pulse-ring 2.5s ease-out infinite; }
        .pulse-ring2 { animation-delay:0.8s; }
        .pulse-ring3 { animation-delay:1.6s; }
    </style>
    {{-- Dynamic background override from branding settings --}}
    @php $loginBgType = $appSettings['login_bg_type'] ?? 'gradient'; @endphp
    @if($loginBgType === 'color')
    <style>body { background: {!! e($appSettings['login_bg_color'] ?? '#e8eaf6') !!} !important; }</style>
    @elseif($loginBgType === 'image' && !empty($appSettings['login_bg_image']))
    <style>body { background: url('{!! e(\Illuminate\Support\Facades\Storage::url($appSettings['login_bg_image'])) !!}') center/cover no-repeat fixed !important; }</style>
    @endif
</head>
<body>
    @yield('content')
</body>
</html>

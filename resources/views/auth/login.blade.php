@extends('layouts.auth')

@section('content')
<style>
.login-card { width:100%;max-width:880px;background:#fff;border-radius:20px;box-shadow:0 24px 60px rgba(0,0,0,0.18),0 4px 16px rgba(0,0,0,0.08);overflow:hidden;display:flex;min-height:540px; }
.login-form-col { flex:1;padding:48px 44px;display:flex;flex-direction:column;justify-content:center;min-width:0; }
.login-deco-col  { width:380px;flex-shrink:0; }
@media(min-width:1160px) {
    /* Scale down to ~35-40% of a wide hero so the corner team frames have room to frame it */
    .login-card { width:clamp(680px,38vw,900px); max-width:900px; }
}
@media(max-width:640px) {
    .login-card      { flex-direction:column; min-height:unset; border-radius:16px; }
    .login-form-col  { padding:32px 24px; }
    .login-deco-col  { display:none !important; }
}
@media(max-width:400px) {
    .login-form-col  { padding:28px 16px; }
}

/* ── Mobile-only premium polish (adds to, never replaces, the rules above) ── */
@media(max-width:768px) {
    .login-card    { max-width: 92vw; box-shadow: var(--amob-shadow-1, 0 10px 30px rgba(17,24,39,0.14)); }
    .amob-logo-row { margin-bottom: 24px !important; }
    .amob-h1       { font-size: 20px !important; line-height: 1.25 !important; }
    .amob-help     { font-size: 13px !important; }
    .amob-field    { min-height: 46px !important; font-size: 15px !important; }
    .amob-btn      { min-height: 48px !important; font-size: 15px !important; }
    .amob-social-btn { min-height: 44px !important; }
}
@media(max-width:480px) {
    .login-card { border-radius: var(--amob-r-md, 16px); }
    .amob-h1    { font-size: 19px !important; }
}

/* This desktop card is swapped for the full-bleed mobile canvas (.lg-wrap) below 769px */
@media(max-width:768px) {
    .auth-desktop-view { display:none !important; }
    /* Let the mobile canvas run edge-to-edge instead of sitting inside layouts.auth's centered/padded body */
    body { padding:0 !important; }
    /* layouts.auth normally keeps the "meet the team" hero/pill stacked above the card on
       mobile (see its own max-width:768px block) — the full-bleed canvas replaces that
       treatment entirely on mobile, so suppress it here. */
    .team-hero, .team-frame-group, .team-bottom-pill { display:none !important; }
}
</style>

<div class="auth-desktop-view">
{{-- Main card --}}
<div class="login-card">

    {{-- ── Left: Form ── --}}
    <div class="login-form-col">

        {{-- Logo --}}
        <div class="amob-logo-row" style="display:flex;align-items:center;gap:8px;margin-bottom:36px;">
            @if(!empty($appSettings['logo_path']))
                <img src="{{ Storage::url($appSettings['logo_path']) }}"
                     alt="{{ $appSettings['app_name'] ?? 'Logo' }}"
                     style="height:32px;width:auto;max-width:120px;object-fit:contain;border-radius:6px;">
            @else
                <div style="width:34px;height:34px;background:#4F46E5;border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(79,70,229,0.35);">
                    <i class="fa fa-bolt" style="color:#fff;font-size:14px;"></i>
                </div>
            @endif
            <span style="font-size:15px;font-weight:700;color:#111827;">{{ $appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name','Dash') }}</span>
        </div>

        {{-- Heading --}}
        <h1 class="amob-h1" style="font-size:22px;font-weight:800;color:#111827;margin:0 0 6px;">Login to your account!</h1>
        <p class="amob-help" style="font-size:13px;color:#9CA3AF;margin:0 0 28px;">Enter your registered email address and password to login</p>

        {{-- Status / info message (e.g. registration closed redirect) --}}
        @if (session('status'))
        <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:12px;color:#1D4ED8;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-info-circle"></i>
            {{ session('status') }}
        </div>
        @endif

        {{-- Errors --}}
        @if ($errors->any())
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:12px;color:#DC2626;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-exclamation-circle"></i>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- Email --}}
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Email or Username</label>
                <div style="position:relative;">
                    <i class="fa fa-user" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
                    <input type="text" name="email" value="{{ old('email') }}" required
                           placeholder="Email or username" class="amob-field"
                           style="width:100%;padding:10px 12px 10px 36px;border:1.5px solid {{ $errors->has('email') ? '#FCA5A5' : '#E5E7EB' }};border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;background:{{ $errors->has('email') ? '#FEF2F2' : '#F9FAFB' }};color:#111827;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#6366F1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)'"
                           onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none'">
                </div>
            </div>

            {{-- Password --}}
            <div style="margin-bottom:16px;" x-data="{show:false}">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Password</label>
                <div style="position:relative;">
                    <i class="fa fa-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
                    <input :type="show?'text':'password'" name="password" required
                           placeholder="••••••••••••" class="amob-field"
                           style="width:100%;padding:10px 40px 10px 36px;border:1.5px solid {{ $errors->has('password') ? '#FCA5A5' : '#E5E7EB' }};border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;background:{{ $errors->has('password') ? '#FEF2F2' : '#F9FAFB' }};color:#111827;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#6366F1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)'"
                           onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none'">
                    <button type="button" @click="show=!show"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;padding:0;">
                        <i :class="show?'fa fa-eye-slash':'fa fa-eye'" style="font-size:13px;"></i>
                    </button>
                </div>
            </div>

            {{-- Remember + Forgot --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                <label style="display:flex;align-items:center;gap:7px;font-size:12px;color:#6B7280;cursor:pointer;">
                    <input type="checkbox" name="remember"
                           style="width:14px;height:14px;accent-color:#4F46E5;border-radius:4px;">
                    Remember me
                </label>
                <a href="#" style="font-size:12px;color:#4F46E5;font-weight:500;text-decoration:none;">Forgot Password ?</a>
            </div>

            {{-- Submit --}}
            <button type="submit" class="amob-btn"
                    style="width:100%;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;font-size:14px;font-weight:600;padding:12px;border:none;border-radius:12px;cursor:pointer;box-shadow:0 6px 20px rgba(79,70,229,0.35);transition:opacity 0.15s;font-family:'Inter',sans-serif;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                Login
            </button>
        </form>

        {{-- Divider --}}
        <div style="display:flex;align-items:center;gap:12px;margin:22px 0;">
            <div style="flex:1;height:1px;background:#F3F4F6;"></div>
            <span style="font-size:11px;color:#9CA3AF;white-space:nowrap;">Or login with</span>
            <div style="flex:1;height:1px;background:#F3F4F6;"></div>
        </div>

        {{-- Social buttons --}}
        <div style="display:flex;gap:10px;justify-content:center;">
            @foreach([['fa-google','#EA4335'],['fa-apple','#111827'],['fa-windows','#00A4EF']] as [$icon,$color])
            <button type="button" class="amob-social-btn"
                    style="flex:1;padding:10px;background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:border-color 0.15s;"
                    onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                <i class="fab {{ $icon }}" style="font-size:16px;color:{{ $color }};"></i>
            </button>
            @endforeach
        </div>

    </div>

    {{-- ── Right: Illustration ── --}}
    @php
        /* Build deco column gradient from the saved panel colour */
        $dh = ltrim($appSettings['login_deco_color'] ?? '#4F46E5', '#');
        if (strlen($dh) !== 6) $dh = '4F46E5';
        $dr = hexdec(substr($dh,0,2)); $dg = hexdec(substr($dh,2,2)); $db = hexdec(substr($dh,4,2));
        $lightDeco = sprintf('#%02x%02x%02x', min(255,round($dr+(255-$dr)*0.58)), min(255,round($dg+(255-$dg)*0.58)), min(255,round($db+(255-$db)*0.58)));
        $darkDeco  = sprintf('#%02x%02x%02x', round($dr*0.55), round($dg*0.55), round($db*0.55));
        $decoGradient = "linear-gradient(145deg,{$lightDeco} 0%,#{$dh} 42%,{$darkDeco} 100%)";

    @endphp
    <div class="login-deco-col" style="background:{!! $decoGradient !!};display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 32px;position:relative;overflow:hidden;">

        {{-- Background blobs --}}
        <div style="position:absolute;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,0.06);top:-80px;right:-80px;"></div>
        <div style="position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,0.06);bottom:-60px;left:-60px;"></div>

        <h2 style="color:#fff;font-size:22px;font-weight:800;text-align:center;margin:0 0 8px;position:relative;z-index:1;line-height:1.3;">
            Manage Tasks<br><span style="color:#BAE6FD;">Everywhere</span>
        </h2>
        <p style="color:rgba(255,255,255,0.65);font-size:12px;text-align:center;margin:0 0 48px;position:relative;z-index:1;">All your projects, tasks and team<br>in one place.</p>

        {{-- Orbit illustration --}}
        <div style="position:relative;width:220px;height:220px;flex-shrink:0;">

            {{-- Orbit rings --}}
            <div style="position:absolute;inset:0;border-radius:50%;border:1px solid rgba(255,255,255,0.15);"></div>
            <div style="position:absolute;inset:20px;border-radius:50%;border:1px solid rgba(255,255,255,0.10);"></div>

            {{-- Pulse rings --}}
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">
                <div class="pulse-ring"></div>
                <div class="pulse-ring pulse-ring2"></div>
                <div class="pulse-ring pulse-ring3"></div>
            </div>

            {{-- Center icon --}}
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:54px;height:54px;background:linear-gradient(135deg,#818CF8,#4F46E5);border-radius:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 30px rgba(0,0,0,0.25);z-index:2;">
                <i class="fa fa-diagram-project" style="color:#fff;font-size:22px;"></i>
            </div>

            {{-- Orbiting feature icons --}}
            <div class="orbit-icon o1" style="background:#fff;">
                <i class="fa fa-check-circle" style="color:#10B981;font-size:16px;"></i>
            </div>
            <div class="orbit-icon o2" style="background:#EFF6FF;">
                <i class="fa fa-calendar" style="color:#3B82F6;font-size:15px;"></i>
            </div>
            <div class="orbit-icon o3" style="background:#FFF7ED;">
                <i class="fa fa-comment-dots" style="color:#F59E0B;font-size:15px;"></i>
            </div>
            <div class="orbit-icon o4" style="background:#F0FDF4;">
                <i class="fa fa-users" style="color:#10B981;font-size:14px;"></i>
            </div>
            <div class="orbit-icon o5" style="background:#FDF4FF;">
                <i class="fa fa-chart-bar" style="color:#8B5CF6;font-size:15px;"></i>
            </div>
            <div class="orbit-icon o6" style="background:#FEF2F2;">
                <i class="fa fa-bell" style="color:#EF4444;font-size:14px;"></i>
            </div>
        </div>

        <p style="color:rgba(255,255,255,0.6);font-size:11px;text-align:center;margin-top:36px;line-height:1.6;position:relative;z-index:1;">
            Compatible with <strong style="color:rgba(255,255,255,0.9);">Tasks, Projects, Calendar</strong><br>and your entire team workflow.
        </p>

        {{-- Copyright inside right panel --}}
        @if(!empty($appSettings['copyright']))
        <p style="position:absolute;bottom:16px;right:20px;font-size:10px;color:rgba(255,255,255,0.4);margin:0;z-index:1;">
            {{ $appSettings['copyright'] }}
        </p>
        @endif
    </div>

</div>
</div>

{{--
  Mobile login canvas — full-bleed indigo canvas + white form sheet, entrance animation.
  Shown only below 769px; the card layout above (.auth-desktop-view) covers desktop.
--}}
@php
    $ssoRoutable = \Illuminate\Support\Facades\Route::has('auth.redirect');
    $brandName = $appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name', 'Dash');

    /* Derive the mobile canvas palette from the same primary_color setting that drives
       --mob-brand app-wide, instead of a hardcoded indigo, so it stays in sync if the
       admin changes the brand color. */
    $lgPrimary = $appSettings['primary_color'] ?? '#4F46E5';
    $lph = ltrim($lgPrimary, '#');
    if (strlen($lph) !== 6) $lph = '4F46E5';
    $lpr = hexdec(substr($lph,0,2)); $lpg = hexdec(substr($lph,2,2)); $lpb = hexdec(substr($lph,4,2));
    $lgBrand2 = sprintf('#%02x%02x%02x', min(255,round($lpr+(255-$lpr)*0.18)), min(255,round($lpg+(255-$lpg)*0.18)), min(255,round($lpb+(255-$lpb)*0.18)));
    $lgCanvas = sprintf('#%02x%02x%02x', round($lpr*0.55), round($lpg*0.55), round($lpb*0.62));
@endphp
<div class="lg-wrap" x-data="{
        pwShown: false, focus: null,
        mode: 'password',
        waAvailable: false, waBusy: false, waMsg: null,
        async waInit() {
            if (!window.PublicKeyCredential || !PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable) return;
            try {
                this.waAvailable = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            } catch (e) { this.waAvailable = false; }
        },
        waBufToBase64url(buf) {
            let str = '';
            new Uint8Array(buf).forEach(b => { str += String.fromCharCode(b); });
            return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
        },
        waBase64urlToBuf(b64url) {
            const b64 = b64url.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - b64url.length % 4) % 4);
            const str = atob(b64);
            const buf = new Uint8Array(str.length);
            for (let i = 0; i < str.length; i++) buf[i] = str.charCodeAt(i);
            return buf.buffer;
        },
        async waLogin() {
            this.waBusy = true; this.waMsg = null;
            try {
                const optRes = await fetch('{{ route('login.webauthn.options') }}', { headers: { 'Accept': 'application/json' } });
                if (!optRes.ok) throw new Error('options');
                const options = await optRes.json();
                options.publicKey.challenge = this.waBase64urlToBuf(options.publicKey.challenge);

                const assertion = await navigator.credentials.get({ publicKey: options.publicKey });

                const payload = {
                    id: this.waBufToBase64url(assertion.rawId),
                    clientDataJSON: this.waBufToBase64url(assertion.response.clientDataJSON),
                    authenticatorData: this.waBufToBase64url(assertion.response.authenticatorData),
                    signature: this.waBufToBase64url(assertion.response.signature),
                };

                const res = await fetch('{{ route('login.webauthn') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.state === 'ok' && data.redirect) {
                    window.location = data.redirect;
                    return;
                }
                this.waMsg = data.message || 'Could not sign you in this way.';
            } catch (e) {
                if (!(e && e.name === 'NotAllowedError')) {
                    this.waMsg = 'Could not sign you in this way. Use password or Shift PIN below.';
                }
            }
            this.waBusy = false;
        },
        staff: [], staffLoaded: false, staffLoading: false, staffError: null,
        selected: null, digits: '', padError: null, padHint: null, padBusy: false, shake: false,
        padLocked: false, padFatal: false, lockRemaining: 0, lockTimer: null,
        openPinMode() { this.mode = 'staff'; this.loadStaff(); },
        async loadStaff() {
            if (this.staffLoaded || this.staffLoading) return;
            this.staffLoading = true; this.staffError = null;
            try {
                const res = await fetch('{{ route('login.shift-pin.staff') }}', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('bad response');
                const data = await res.json();
                this.staff = data.users || [];
                this.staffLoaded = true;
            } catch (e) {
                this.staffError = 'Could not load staff list.';
            }
            this.staffLoading = false;
        },
        resetPad() {
            clearInterval(this.lockTimer); this.lockTimer = null;
            this.digits = ''; this.padError = null; this.padHint = null;
            this.padLocked = false; this.padFatal = false; this.lockRemaining = 0;
        },
        pickStaff(u) { this.selected = u; this.mode = 'pad'; this.resetPad(); },
        backToStaff() { this.mode = 'staff'; this.selected = null; this.resetPad(); },
        useCredentials() { this.mode = 'password'; this.resetPad(); },
        startLock(seconds) {
            this.padLocked = true;
            this.lockRemaining = seconds;
            clearInterval(this.lockTimer);
            this.lockTimer = setInterval(() => {
                this.lockRemaining--;
                if (this.lockRemaining <= 0) {
                    clearInterval(this.lockTimer); this.lockTimer = null;
                    this.padLocked = false; this.padError = null;
                }
            }, 1000);
        },
        lockLabel() {
            const m = Math.floor(this.lockRemaining / 60), s = this.lockRemaining % 60;
            return m + ':' + String(s).padStart(2, '0');
        },
        tapDigit(d) {
            if (this.padBusy || this.padLocked || this.padFatal || this.digits.length >= 4) return;
            this.digits += String(d);
            this.padError = null; this.padHint = null;
            if (this.digits.length === 4) this.submitPin();
        },
        backspace() {
            if (this.padBusy || this.padLocked || this.padFatal) return;
            this.digits = this.digits.slice(0, -1);
            this.padError = null; this.padHint = null;
        },
        async submitPin() {
            this.padBusy = true;
            try {
                const res = await fetch('{{ route('login.shift-pin') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ user_id: this.selected.id, pin: this.digits }),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.state === 'ok' && data.redirect) {
                    window.location = data.redirect;
                    return;
                }
                this.digits = '';
                if (data.state === 'locked') {
                    this.padError = data.message || 'Too many attempts.';
                    this.startLock(data.retry_after || 60);
                } else if (data.state === 'inactive' || data.state === 'unavailable') {
                    this.padError = data.message || 'This account can no longer sign in this way.';
                    this.padFatal = true;
                } else {
                    this.padError = data.message || 'Incorrect PIN.';
                    if (typeof data.attempts_remaining === 'number' && data.attempts_remaining <= 2) {
                        this.padHint = data.attempts_remaining === 0
                            ? 'Last attempt before a temporary lock.'
                            : data.attempts_remaining + ' attempt' + (data.attempts_remaining === 1 ? '' : 's') + ' left before a temporary lock.';
                    }
                    this.shake = true;
                    setTimeout(() => { this.shake = false; }, 400);
                }
            } catch (e) {
                this.digits = '';
                this.padError = 'Network error. Please try again.';
            }
            this.padBusy = false;
        },
     }" x-init="waInit()">

    {{-- drifting glows --}}
    <div class="lg-glow lg-glow-1"></div>
    <div class="lg-glow lg-glow-2"></div>

    {{-- brand --}}
    <div class="lg-brand">
        @if(!empty($appSettings['logo_path']))
            <img src="{{ Storage::url($appSettings['logo_path']) }}" alt="{{ $brandName }}" class="lg-logo-img">
        @else
            <span class="lg-logo">{{ \Illuminate\Support\Str::of($brandName)->substr(0, 2)->upper() }}</span>
        @endif
        <span class="lg-brand-text">
            <span class="lg-brand-name">{{ $brandName }}</span>
            <span class="lg-brand-sub">Task Management System</span>
        </span>
        <span class="lg-live">
            <span class="lg-live-dot"></span>
            <span class="lg-live-label">LIVE</span>
        </span>
    </div>

    {{-- headline --}}
    <div class="lg-hero">
        <h1 class="lg-title">Welcome back</h1>
        <p class="lg-tagline">One team. One goal. Unlimited impact.</p>
    </div>

    {{-- form sheet --}}
    <div class="lg-sheet">

        {{-- Face ID / Touch ID / fingerprint — fastest path on a personal device --}}
        <button type="button" class="lg-bio-btn" x-show="waAvailable" x-cloak @click="waLogin()" :disabled="waBusy">
            <i class="fas fa-fingerprint"></i>
            <span x-text="waBusy ? 'Checking…' : 'Sign in with Face ID / Touch ID'"></span>
        </button>
        <p class="lg-bio-msg" x-show="waMsg" x-text="waMsg" x-cloak></p>
        <div class="lg-bio-divider" x-show="waAvailable" x-cloak><span></span><span>or</span><span></span></div>

        {{-- Password / Shift PIN mode switch --}}
        <div class="lg-tabs">
            <button type="button" class="lg-tab" :class="mode === 'password' && 'is-active'" @click="mode = 'password'">
                <i class="fas fa-lock"></i> Password
            </button>
            <button type="button" class="lg-tab" :class="mode !== 'password' && 'is-active'" @click="openPinMode()">
                <i class="fas fa-hashtag"></i> Shift PIN
            </button>
        </div>

        <div x-show="mode === 'password'" x-cloak>
        <form method="POST" action="{{ route('login') }}" class="lg-form">
            @csrf

            @if (session('status'))
                <div class="lg-error" style="background:#EFF6FF;color:#1D4ED8;">
                    <i class="fas fa-circle-info"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="lg-error">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <label class="lg-field">
                <span class="lg-label">Email or username</span>
                <span class="lg-input" :class="focus === 'email' && 'is-focus'">
                    <i class="fas fa-user"></i>
                    <input type="text" name="email" value="{{ old('email') }}"
                           placeholder="you@promoseven.com" autocomplete="username"
                           required autofocus
                           @focus="focus = 'email'" @blur="focus = null">
                </span>
            </label>

            <label class="lg-field">
                <span class="lg-label">Password</span>
                <span class="lg-input" :class="focus === 'pw' && 'is-focus'">
                    <i class="fas fa-lock"></i>
                    <input :type="pwShown ? 'text' : 'password'" name="password"
                           placeholder="••••••••" autocomplete="current-password" required
                           @focus="focus = 'pw'" @blur="focus = null">
                    <button type="button" @click="pwShown = !pwShown" class="lg-eye"
                            :aria-label="pwShown ? 'Hide password' : 'Show password'">
                        <i class="fas" :class="pwShown ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </span>
            </label>

            <div class="lg-row">
                <label class="lg-remember">
                    <input type="checkbox" name="remember" @checked(old('remember'))>
                    <span class="lg-box"><i class="fas fa-check"></i></span>
                    <span class="lg-remember-text">Remember me</span>
                </label>
                <a href="#" class="lg-forgot">Forgot password?</a>
            </div>

            <button type="submit" class="lg-submit">Sign in</button>
        </form>

        <div class="lg-divider">
            <span></span><span class="lg-divider-text">or continue with</span><span></span>
        </div>

        <div class="lg-sso">
            @foreach ([
                ['google', 'fa-google',  'Google',    '#EA4335', 0],
                ['apple',  'fa-apple',   'Apple',     '#111827', 1],
                ['azure',  'fa-windows', 'Microsoft', '#0078D4', 2],
            ] as [$provider, $iconClass, $label, $color, $i])
                <a href="{{ $ssoRoutable ? route('auth.redirect', $provider) : '#' }}" class="lg-sso-btn"
                   aria-label="{{ $label }}" title="{{ $label }}"
                   style="animation-delay:{{ 0.34 + $i * 0.06 }}s">
                    <i class="fab {{ $iconClass }}" style="color:{{ $color }}"></i>
                </a>
            @endforeach
        </div>
        </div>{{-- /mode:password --}}

        {{-- Shift PIN: staff picker --}}
        <div x-show="mode === 'staff'" x-cloak class="lg-pin-staff">
            <p class="lg-pin-title">Who's signing in?</p>
            <p class="lg-pin-sub">Tap your name, then enter your Shift PIN.</p>

            <div class="lg-staff-grid" x-show="staff.length">
                <template x-for="u in staff" :key="u.id">
                    <button type="button" class="lg-staff-btn" @click="pickStaff(u)">
                        <span class="lg-staff-avatar">
                            <template x-if="u.avatar"><img :src="u.avatar" :alt="u.name"></template>
                            <template x-if="!u.avatar"><span x-text="u.name.slice(0,1).toUpperCase()"></span></template>
                        </span>
                        <span class="lg-staff-name" x-text="u.name"></span>
                    </button>
                </template>
            </div>

            <div class="lg-pin-empty" x-show="staffLoading">
                <i class="fas fa-circle-notch fa-spin"></i> Loading staff…
            </div>
            <div class="lg-pin-empty" x-show="staffLoaded && !staffLoading && !staff.length && !staffError">
                No one on this device has a Shift PIN set up yet.
            </div>
            <div class="lg-pin-empty" x-show="staffError" x-text="staffError"></div>
        </div>

        {{-- Shift PIN: keypad --}}
        <div x-show="mode === 'pad'" x-cloak class="lg-pin-pad">
            <button type="button" class="lg-pin-back" @click="backToStaff()">
                <i class="fas fa-arrow-left-long"></i> Not you?
            </button>

            <div class="lg-pin-who" x-show="selected">
                <span class="lg-staff-avatar lg-staff-avatar-lg">
                    <template x-if="selected && selected.avatar"><img :src="selected.avatar" :alt="selected.name"></template>
                    <template x-if="selected && !selected.avatar"><span x-text="selected ? selected.name.slice(0,1).toUpperCase() : ''"></span></template>
                </span>
                <span class="lg-pin-who-name" x-text="selected ? selected.name : ''"></span>
            </div>

            <div class="lg-pin-dots" :class="shake && 'lg-pin-dots-shake'" x-show="!padFatal">
                <span class="lg-pin-dot" :class="digits.length > 0 && 'is-filled'"></span>
                <span class="lg-pin-dot" :class="digits.length > 1 && 'is-filled'"></span>
                <span class="lg-pin-dot" :class="digits.length > 2 && 'is-filled'"></span>
                <span class="lg-pin-dot" :class="digits.length > 3 && 'is-filled'"></span>
            </div>

            <p class="lg-pin-msg lg-pin-msg-error" x-show="padError" x-text="padError"></p>
            <p class="lg-pin-msg lg-pin-msg-lock" x-show="padLocked" x-text="'Locked — try again in ' + lockLabel()"></p>
            <p class="lg-pin-msg lg-pin-msg-hint" x-show="padHint && !padLocked && !padFatal" x-text="padHint"></p>
            <p class="lg-pin-msg" x-show="padBusy && !padError">Signing in…</p>

            <div class="lg-keypad" x-show="!padFatal">
                <template x-for="n in [1,2,3,4,5,6,7,8,9]" :key="n">
                    <button type="button" class="lg-key" @click="tapDigit(n)" :disabled="padBusy || padLocked">
                        <span x-text="n"></span>
                    </button>
                </template>
                <button type="button" class="lg-key lg-key-ghost" tabindex="-1" disabled></button>
                <button type="button" class="lg-key" @click="tapDigit(0)" :disabled="padBusy || padLocked">
                    <span>0</span>
                </button>
                <button type="button" class="lg-key lg-key-back" @click="backspace()" :disabled="padBusy || padLocked || !digits.length" aria-label="Delete digit">
                    <i class="fas fa-delete-left"></i>
                </button>
            </div>

            <button type="button" class="lg-pin-use-pw" x-show="padLocked || padFatal" @click="useCredentials()">
                Sign in with password instead
            </button>
        </div>

        <div class="lg-foot">
            <span>New to the team?</span>
            <a href="{{ route('team.index') }}">Meet everyone</a>
        </div>
    </div>
</div>

<style>
    :root {
        --lg-canvas: {{ $lgCanvas }};
        --lg-brand: {{ $lgPrimary }};
        --lg-brand-2: {{ $lgBrand2 }};
        --lg-teal: #2DD4BF;
    }

    @keyframes lgRise  { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
    @keyframes lgSheet { from { transform: translateY(52px); }            to { transform: translateY(0); } }
    @keyframes lgDrift {
        0%   { transform: translate(0, 0) scale(1); }
        50%  { transform: translate(18px, -14px) scale(1.12); }
        100% { transform: translate(0, 0) scale(1); }
    }
    @keyframes lgPulse {
        0%   { box-shadow: 0 0 0 0 rgba(45, 212, 191, .55); }
        70%  { box-shadow: 0 0 0 7px rgba(45, 212, 191, 0); }
        100% { box-shadow: 0 0 0 0 rgba(45, 212, 191, 0); }
    }

    /* Hidden on desktop — .auth-desktop-view is the card layout there */
    .lg-wrap { display: none; }

    @media(max-width:768px) {
        .lg-wrap {
            position: relative;
            min-height: 100vh; min-height: 100dvh;
            background: var(--lg-canvas);
            display: flex; flex-direction: column;
            overflow: hidden;
            font-family: 'Inter', system-ui, sans-serif;
        }
    }

    .lg-glow { position: absolute; border-radius: 50%; pointer-events: none; }
    .lg-glow-1 {
        top: -70px; right: -60px; width: 240px; height: 240px;
        background: radial-gradient(circle, rgba(99,102,241,.55) 0%, rgba(49,46,129,0) 70%);
        animation: lgDrift 11s ease-in-out infinite;
    }
    .lg-glow-2 {
        top: 110px; left: -80px; width: 200px; height: 200px;
        background: radial-gradient(circle, rgba(45,212,191,.28) 0%, rgba(49,46,129,0) 70%);
        animation: lgDrift 14s ease-in-out infinite reverse;
    }

    /* ── brand ─────────────────────────────── */
    .lg-brand {
        position: relative; flex: none;
        padding: 32px 24px 22px;
        display: flex; align-items: center; gap: 11px;
        animation: lgRise .5s cubic-bezier(.22,1,.36,1) both;
    }
    .lg-logo, .lg-logo-img {
        width: 34px; height: 34px; flex: none; border-radius: 10px;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2);
        color: #fff; font-size: 13px; font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .lg-logo-img { object-fit: contain; background: #fff; }
    .lg-brand-text { flex: 1; min-width: 0; }
    .lg-brand-name { display: block; font-size: 15px; font-weight: 700; color: #fff; letter-spacing: -.018em; line-height: 1.1; }
    .lg-brand-sub  { display: block; font-size: 10.5px; font-weight: 500; color: #A5B4FC; margin-top: 2px; }
    .lg-live {
        flex: none; display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 10px; border-radius: 20px;
        background: rgba(45,212,191,.14); border: 1px solid rgba(45,212,191,.28);
    }
    .lg-live-dot { width: 5px; height: 5px; border-radius: 99px; background: var(--lg-teal); animation: lgPulse 2.4s ease-out infinite; }
    .lg-live-label { font-size: 10px; font-weight: 700; color: #5EEAD4; letter-spacing: .02em; }

    /* ── headline ──────────────────────────── */
    .lg-hero { position: relative; flex: none; padding: 0 24px 26px; }
    .lg-title {
        margin: 0; font-size: 30px; font-weight: 700; color: #fff;
        letter-spacing: -.034em; line-height: 1.08; white-space: nowrap;
        animation: lgRise .58s cubic-bezier(.22,1,.36,1) .07s both;
    }
    .lg-tagline {
        margin: 10px 0 0; font-size: 13px; font-weight: 500; color: #A5B4FC; line-height: 1.5;
        animation: lgRise .58s cubic-bezier(.22,1,.36,1) .14s both;
    }

    /* ── sheet ─────────────────────────────── */
    .lg-sheet {
        position: relative; flex: 1;
        background: #fff; border-radius: 28px 28px 0 0;
        padding: 24px 24px calc(30px + env(safe-area-inset-bottom));
        display: flex; flex-direction: column;
        animation: lgSheet .62s cubic-bezier(.22,1,.36,1) .1s both;
    }
    .lg-form {
        display: flex; flex-direction: column; gap: 16px;
        animation: lgRise .5s cubic-bezier(.22,1,.36,1) .26s both;
    }

    /* ── Face ID / Touch ID / fingerprint quick sign-in ─── */
    .lg-bio-btn {
        display: flex; align-items: center; justify-content: center; gap: 9px;
        width: 100%; min-height: 52px; margin-bottom: 14px;
        border: 0; border-radius: 14px; cursor: pointer; font-family: inherit;
        font-size: 14.5px; font-weight: 700; letter-spacing: -.01em; color: #fff;
        background: linear-gradient(135deg, var(--lg-brand) 0%, var(--lg-brand-2) 100%);
        box-shadow: 0 12px 24px -12px rgba(79,70,229,.85);
        transition: transform .18s cubic-bezier(.22,1,.36,1), box-shadow .18s ease;
        animation: lgRise .5s cubic-bezier(.22,1,.36,1) .16s both;
    }
    .lg-bio-btn i { font-size: 17px; }
    .lg-bio-btn:hover { transform: translateY(-2px); }
    .lg-bio-btn:disabled { opacity: .7; cursor: default; transform: none; }
    .lg-bio-msg { margin: -6px 0 12px; font-size: 12px; font-weight: 600; color: #DC2626; text-align: center; }
    .lg-bio-divider {
        display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
        font-size: 11px; font-weight: 600; color: #C7CBDC; text-transform: uppercase; letter-spacing: .04em;
    }
    .lg-bio-divider span:not(:nth-child(2)) { flex: 1; height: 1px; background: #EDEFF3; }

    /* ── mode switch (Password / Shift PIN) ─────────── */
    .lg-tabs {
        display: flex; gap: 4px; padding: 4px; margin-bottom: 20px;
        background: #F4F5FB; border-radius: 12px;
        animation: lgRise .5s cubic-bezier(.22,1,.36,1) .2s both;
    }
    .lg-tab {
        flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        min-height: 40px; border: 0; border-radius: 9px; background: none; cursor: pointer;
        font-family: inherit; font-size: 12.5px; font-weight: 600; color: #6B7280;
        transition: background .15s ease, color .15s ease, box-shadow .15s ease;
    }
    .lg-tab i { font-size: 11px; }
    .lg-tab.is-active { background: #fff; color: var(--lg-brand); box-shadow: 0 2px 8px rgba(17,24,39,.08); }

    .lg-error {
        display: flex; align-items: center; gap: 9px;
        padding: 12px 13px; border-radius: 12px; background: #FEE2E2;
        font-size: 12.5px; font-weight: 600; color: #DC2626;
    }
    .lg-error i { font-size: 13px; }

    .lg-field { display: block; }
    .lg-label { display: block; font-size: 12px; font-weight: 600; color: #374151; letter-spacing: -.005em; }
    .lg-input {
        display: flex; align-items: center; gap: 10px;
        margin-top: 7px; padding: 0 13px; min-height: 52px;
        border-radius: 13px; background: #F4F5FB; border: 1px solid #EDEFF3;
        transition: border-color .18s ease, box-shadow .2s ease, transform .2s cubic-bezier(.22,1,.36,1);
    }
    .lg-input.is-focus {
        border-color: #C7D2FE;
        box-shadow: 0 6px 16px -6px rgba(79,70,229,.35), 0 0 0 3px #EEF2FF;
        transform: translateY(-2px);
    }
    .lg-input > i { font-size: 14px; color: #9CA3AF; flex: none; width: 16px; text-align: center; transition: color .18s ease; }
    .lg-input.is-focus > i { color: var(--lg-brand); animation: lgIconPop .32s cubic-bezier(.34,1.56,.64,1); }
    @keyframes lgIconPop {
        0%   { transform: scale(1); }
        50%  { transform: scale(1.25); }
        100% { transform: scale(1); }
    }
    .lg-input input {
        flex: 1; min-width: 0; border: 0; background: none; outline: none;
        font-family: inherit; font-size: 16px; font-weight: 500; color: #111827; /* 16px = no iOS zoom */
    }
    .lg-input input::placeholder { color: #9CA3AF; font-weight: 400; }
    .lg-eye {
        flex: none; width: 34px; height: 34px; margin: 0 -6px 0 0;
        border: 0; border-radius: 9px; background: none; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .lg-eye i { font-size: 15px; color: #9CA3AF; }

    .lg-row { display: flex; align-items: center; gap: 12px; min-height: 44px; }
    .lg-remember { flex: 1; display: inline-flex; align-items: center; gap: 9px; min-height: 44px; cursor: pointer; }
    .lg-remember input { position: absolute; opacity: 0; pointer-events: none; }
    .lg-box {
        width: 20px; height: 20px; flex: none; border-radius: 6px;
        border: 1.5px solid #D1D5DB; background: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        transition: background .15s ease, border-color .15s ease;
    }
    .lg-box i { font-size: 10px; color: #fff; opacity: 0; transition: opacity .15s ease; }
    .lg-remember input:checked + .lg-box { background: var(--lg-brand); border-color: var(--lg-brand); }
    .lg-remember input:checked + .lg-box i { opacity: 1; }
    .lg-remember-text { font-size: 13px; font-weight: 500; color: #4B5563; }
    .lg-forgot {
        flex: none; display: inline-flex; align-items: center; min-height: 44px; padding: 0 2px;
        color: var(--lg-brand); font-size: 13px; font-weight: 600; text-decoration: none;
    }

    .lg-submit {
        width: 100%; min-height: 52px; border: 0; border-radius: 14px;
        background: linear-gradient(135deg, var(--lg-brand) 0%, var(--lg-brand-2) 100%);
        color: #fff; font-family: inherit; font-size: 15.5px; font-weight: 700;
        letter-spacing: -.012em; cursor: pointer;
        box-shadow: 0 12px 24px -12px rgba(79,70,229,.85);
        transition: transform .18s cubic-bezier(.22,1,.36,1), box-shadow .18s ease;
    }
    .lg-submit:hover { transform: translateY(-2px); box-shadow: 0 16px 30px -12px rgba(79,70,229,.95); }
    .lg-submit:active { transform: translateY(1px) scale(.99); }

    .lg-divider {
        display: flex; align-items: center; gap: 12px; padding: 20px 0 16px;
        animation: lgRise .5s cubic-bezier(.22,1,.36,1) .3s both;
    }
    .lg-divider > span:not(.lg-divider-text) { flex: 1; height: 1px; background: #EDEFF3; }
    .lg-divider-text { font-size: 11.5px; font-weight: 500; color: #9CA3AF; }

    .lg-sso { display: flex; gap: 9px; }
    .lg-sso-btn {
        flex: 1; min-height: 50px; border: 1px solid #E5E7EB; border-radius: 12px;
        background: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none;
        font-size: 16px;
        transition: border-color .18s ease, background .18s ease;
        animation: lgRise .5s cubic-bezier(.22,1,.36,1) both;
    }
    .lg-sso-btn:hover { border-color: #C7D2FE; background: #FBFBFE; }

    .lg-foot {
        margin-top: auto; padding-top: 22px;
        display: flex; align-items: center; justify-content: center; gap: 7px;
        font-size: 12px; font-weight: 500; color: #9CA3AF;
        animation: lgRise .5s cubic-bezier(.22,1,.36,1) .5s both;
    }
    .lg-foot a { color: var(--lg-brand); font-weight: 700; text-decoration: none; }

    /* ── Shift PIN: staff picker ─────────────────────── */
    .lg-pin-staff { animation: lgRise .5s cubic-bezier(.22,1,.36,1) .1s both; }
    .lg-pin-title { margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #111827; }
    .lg-pin-sub { margin: 0 0 18px; font-size: 12.5px; color: #9CA3AF; }
    .lg-staff-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
    }
    .lg-staff-btn {
        display: flex; flex-direction: column; align-items: center; gap: 8px;
        padding: 14px 6px; border: 1px solid #EDEFF3; border-radius: 14px; background: #F9FAFB;
        cursor: pointer; transition: border-color .15s ease, background .15s ease, transform .12s ease;
    }
    .lg-staff-btn:active { transform: scale(.96); }
    .lg-staff-btn:hover { border-color: #C7D2FE; background: #F4F5FB; }
    .lg-staff-avatar {
        width: 46px; height: 46px; border-radius: 50%; flex: none; overflow: hidden;
        background: linear-gradient(135deg, var(--lg-brand) 0%, var(--lg-brand-2) 100%);
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-size: 16px; font-weight: 700;
    }
    .lg-staff-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .lg-staff-name {
        font-size: 12px; font-weight: 600; color: #374151; text-align: center;
        max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .lg-pin-empty {
        padding: 22px 4px; text-align: center; font-size: 12.5px; font-weight: 500; color: #9CA3AF;
    }
    .lg-pin-empty i { margin-right: 6px; }

    /* ── Shift PIN: keypad ────────────────────────────── */
    .lg-pin-pad {
        display: flex; flex-direction: column; align-items: center;
        animation: lgRise .5s cubic-bezier(.22,1,.36,1) .1s both;
    }
    .lg-pin-back {
        align-self: flex-start; display: inline-flex; align-items: center; gap: 7px;
        border: 0; background: none; cursor: pointer; padding: 0 0 18px;
        font-family: inherit; font-size: 12.5px; font-weight: 600; color: #9CA3AF;
    }
    .lg-pin-back i { font-size: 11px; }
    .lg-pin-who { display: flex; flex-direction: column; align-items: center; gap: 10px; margin-bottom: 22px; }
    .lg-staff-avatar-lg { width: 62px; height: 62px; font-size: 22px; }
    .lg-pin-who-name { font-size: 14.5px; font-weight: 700; color: #111827; }

    .lg-pin-dots { display: flex; gap: 16px; margin-bottom: 14px; }
    .lg-pin-dot {
        width: 14px; height: 14px; border-radius: 50%; border: 1.5px solid #D1D5DB; background: #fff;
        transition: background .15s ease, border-color .15s ease, transform .15s ease;
    }
    .lg-pin-dot.is-filled { background: var(--lg-brand); border-color: var(--lg-brand); transform: scale(1.1); }
    @keyframes lgShake {
        10%, 90% { transform: translateX(-1px); }
        20%, 80% { transform: translateX(2px); }
        30%, 50%, 70% { transform: translateX(-4px); }
        40%, 60% { transform: translateX(4px); }
    }
    .lg-pin-dots-shake { animation: lgShake .4s both; }
    .lg-pin-dots-shake .lg-pin-dot { border-color: #FCA5A5; }

    .lg-pin-msg {
        min-height: 16px; margin: 0 0 14px; font-size: 12px; font-weight: 600; color: #9CA3AF; text-align: center;
    }
    .lg-pin-msg-error { color: #DC2626; }
    .lg-pin-msg-lock { color: #B45309; font-weight: 700; }
    .lg-pin-msg-hint { color: #D97706; }

    .lg-pin-use-pw {
        margin-top: 18px; border: 0; background: none; cursor: pointer;
        font-family: inherit; font-size: 12.5px; font-weight: 700; color: var(--lg-brand);
    }

    .lg-keypad {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; width: 100%; max-width: 260px;
    }
    .lg-key {
        min-height: 56px; border: 1px solid #EDEFF3; border-radius: 14px; background: #F9FAFB;
        font-family: inherit; font-size: 19px; font-weight: 600; color: #111827; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background .12s ease, transform .1s ease;
    }
    .lg-key:active { transform: scale(.94); background: #F4F5FB; }
    .lg-key:disabled { opacity: .5; cursor: default; }
    .lg-key-ghost { border: 0; background: none; pointer-events: none; }
    .lg-key-back { color: #9CA3AF; font-size: 16px; }

    @media (prefers-reduced-motion: reduce) {
        .lg-glow, .lg-live-dot { animation: none; }
        .lg-brand, .lg-title, .lg-tagline, .lg-sheet, .lg-form, .lg-tabs, .lg-bio-btn,
        .lg-divider, .lg-sso-btn, .lg-foot, .lg-pin-staff, .lg-pin-pad { animation: none; opacity: 1; transform: none; }
        .lg-pin-dots-shake { animation: none; }
        .lg-input.is-focus { transform: none; }
        .lg-input.is-focus > i { animation: none; }
    }
</style>
@endsection

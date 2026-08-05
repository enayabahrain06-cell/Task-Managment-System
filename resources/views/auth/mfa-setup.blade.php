@extends('layouts.auth')

@section('content')
<style>
.mfa-card {
    width: 100%;
    max-width: 900px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(79,70,229,0.15);
    overflow: hidden;
    display: flex;
    min-height: 560px;
}
.mfa-form-col {
    flex: 1;
    padding: 44px 40px;
    display: flex;
    flex-direction: column;
    min-width: 0;
    overflow-y: auto;
}
.mfa-deco-col {
    width: 320px;
    flex-shrink: 0;
    background: linear-gradient(145deg, #4338CA 0%, #4F46E5 50%, #6366F1 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 28px;
    position: relative;
    overflow: hidden;
}
.setup-step {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 20px;
}
.setup-step-num {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #EEF2FF;
    color: #4F46E5;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}
.app-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    color: #374151;
    background: #F9FAFB;
    cursor: default;
    transition: border-color .15s, background .15s;
}
.app-badge:hover { border-color: #6366F1; background: #EEF2FF; color: #4F46E5; }
.otp-box {
    width: 44px;
    height: 52px;
    border: 1.5px solid #E5E7EB;
    border-radius: 11px;
    text-align: center;
    font-size: 22px;
    font-weight: 700;
    font-family: monospace;
    color: #111827;
    background: #F9FAFB;
    outline: none;
    transition: border-color .15s, box-shadow .15s, background .15s;
    caret-color: transparent;
}
.otp-box:focus { border-color: #6366F1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); background: #fff; }
.otp-box.filled { border-color: #6366F1; background: #F5F3FF; color: #4F46E5; }
.step-divider { height: 1px; background: #F3F4F6; margin: 4px 0 20px 40px; }
[x-cloak] { display: none !important; }

@media (max-width: 640px) {
    .mfa-card { flex-direction: column; min-height: unset; border-radius: 16px; }
    .mfa-form-col { padding: 28px 22px; }
    .mfa-deco-col { display: none !important; }
}

/* ── Mobile-only premium polish (additive; desktop >768px untouched) ── */
@media (max-width: 768px) {
    .mfa-card    { max-width: 92vw; box-shadow: var(--amob-shadow-1, 0 10px 30px rgba(17,24,39,0.14)); }
    .amob-h1     { font-size: 18px !important; }
    .amob-help   { font-size: 12px !important; }
    .amob-btn    { min-height: 48px !important; font-size: 15px !important; }
    .amob-copy-btn { min-height: 44px !important; padding: 10px 14px !important; }
    /* QR code must never overflow the viewport width on narrow phones */
    .amob-qr-wrap     { max-width: 100%; }
    .amob-qr-wrap svg { width: 100% !important; height: auto !important; max-width: 180px; display: block; }
}
@media (max-width: 480px) {
    .mfa-form-col     { padding: 20px 16px !important; }
    #enableBoxes      { gap: 4px !important; }
    .otp-box          { width: 40px !important; height: 48px !important; font-size: 19px !important; }
    .amob-otp-divider { width: 10px !important; }
    .amob-qr-wrap svg { max-width: 150px; }
}
</style>

@php $authUser = auth()->user(); @endphp

<div class="mfa-card">

    {{-- ── Left: Setup steps ── --}}
    <div class="mfa-form-col">

        {{-- Logo --}}
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:24px;">
            @if(!empty($appSettings['logo_path']))
                <img src="{{ Storage::url($appSettings['logo_path']) }}" alt="{{ $appSettings['app_name'] ?? 'Logo' }}" style="height:30px;width:auto;max-width:120px;object-fit:contain;border-radius:6px;">
            @else
                <div style="width:32px;height:32px;background:linear-gradient(135deg,#4F46E5,#6366F1);border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(79,70,229,.35);">
                    <i class="fas fa-shield-halved" style="color:#fff;font-size:13px;"></i>
                </div>
            @endif
            <span style="font-size:14px;font-weight:700;color:#111827;">{{ $appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name','Dash') }}</span>
        </div>

        {{-- User identity card --}}
        <div style="display:flex;align-items:center;gap:12px;background:#F8FAFF;border:1px solid #E0E7FF;border-radius:14px;padding:12px 16px;margin-bottom:22px;">
            @if($authUser->avatar)
                <img src="{{ Storage::url($authUser->avatar) }}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #C7D2FE;">
            @else
                <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#818CF8);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:2px solid #C7D2FE;">
                    <span style="color:#fff;font-size:15px;font-weight:700;">{{ strtoupper(substr($authUser->name,0,1)) }}</span>
                </div>
            @endif
            <div style="min-width:0;flex:1;">
                <p style="margin:0;font-size:13px;font-weight:700;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $authUser->name }}</p>
                <p style="margin:2px 0 0;font-size:11px;color:#9CA3AF;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $authUser->email }}</p>
            </div>
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:600;color:#4F46E5;background:#EEF2FF;padding:3px 8px;border-radius:20px;flex-shrink:0;">
                <i class="fas fa-shield-halved" style="font-size:9px;"></i> Setup
            </span>
        </div>

        {{-- Heading --}}
        <div style="margin-bottom:22px;">
            <h1 class="amob-h1" style="font-size:18px;font-weight:800;color:#111827;margin:0 0 4px;">Set Up Two-Factor Authentication</h1>
            <p class="amob-help" style="font-size:12px;color:#9CA3AF;margin:0;">Follow three quick steps to secure your account.</p>
        </div>

        {{-- Error --}}
        @if($errors->any())
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:12px;color:#DC2626;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-exclamation-circle"></i> {{ $errors->first() }}
        </div>
        @endif

        {{-- Step 1: Install app --}}
        <div class="setup-step">
            <div class="setup-step-num">1</div>
            <div style="flex:1;">
                <p style="font-size:12px;font-weight:700;color:#374151;margin:0 0 4px;">Install an authenticator app</p>
                <p style="font-size:11px;color:#9CA3AF;margin:0 0 10px;">Download one of these free apps on your phone:</p>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    <span class="app-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                            <path d="M3.18 23.76c.3.17.64.24.98.22l12.38-12.38L13.06 8.12 3.18 23.76z" fill="#EA4335"/>
                            <path d="M20.96 10.22L17.54 8.12l-3.08 3.08 3.08 3.08 3.44-1.94c.98-.55.98-1.62.02-2.12h-.04z" fill="#FBBC04"/>
                            <path d="M4.16.47C3.82.65 3.56 1.02 3.56 1.52v21c0 .5.26.87.6 1.05l12.38-12.19L4.16.47z" fill="#4285F4"/>
                            <path d="M4.16 23.57l12.38-12.19-3.48-3.48L4.16.47v23.1z" fill="#34A853"/>
                        </svg>
                        Google Authenticator
                    </span>
                    <span class="app-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24"><circle cx="12" cy="12" r="12" fill="#EF4444"/><path d="M12 6l1.5 4.5H18l-3.75 2.7 1.5 4.5L12 15l-3.75 2.7 1.5-4.5L6 10.5h4.5z" fill="#fff"/></svg>
                        Authy
                    </span>
                    <span class="app-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24"><rect width="24" height="24" rx="4" fill="#00A4EF"/><path d="M7 7h4v4H7zm6 0h4v4h-4zM7 13h4v4H7zm6 0h4v4h-4z" fill="#fff"/></svg>
                        Microsoft Auth
                    </span>
                </div>
            </div>
        </div>

        <div class="step-divider"></div>

        {{-- Step 2: QR Code --}}
        <div class="setup-step">
            <div class="setup-step-num">2</div>
            <div style="flex:1;">
                <p style="font-size:12px;font-weight:700;color:#374151;margin:0 0 10px;">Scan this QR code with your app</p>
                <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                    <div class="amob-qr-wrap" style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:12px;display:inline-flex;flex-shrink:0;">
                        {!! $qrSvg !!}
                    </div>
                    <div style="flex:1;min-width:160px;">
                        <p style="font-size:11px;color:#9CA3AF;margin:0 0 8px;">Can't scan? Enter this key manually:</p>
                        <code id="mfaSecret" style="font-size:11px;font-weight:700;color:#374151;letter-spacing:2px;font-family:monospace;background:#F3F4F6;padding:8px 10px;border-radius:8px;display:block;word-break:break-all;line-height:1.8;margin-bottom:8px;">{{ chunk_split($secret, 4, ' ') }}</code>
                        <button type="button" onclick="copySecret()" id="copyBtn" class="amob-copy-btn"
                                style="display:inline-flex;align-items:center;gap:5px;padding:7px 12px;background:#EEF2FF;color:#4F46E5;border:1.5px solid #C7D2FE;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;transition:background .15s;"
                                onmouseover="this.style.background='#E0E7FF'" onmouseout="if(!window._copied)this.style.background='#EEF2FF'">
                            <i class="fas fa-copy" style="font-size:11px;"></i> Copy Key
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="step-divider"></div>

        {{-- Step 3: Verify --}}
        <div class="setup-step">
            <div class="setup-step-num">3</div>
            <div style="flex:1;">
                <p style="font-size:12px;font-weight:700;color:#374151;margin:0 0 10px;">Enter the 6-digit code to confirm</p>
                <form method="POST" action="{{ route('mfa.enable') }}" id="enableForm">
                    @csrf
                    <input type="hidden" name="code" id="enableCode">

                    <label style="display:block;font-size:10px;font-weight:600;color:#9CA3AF;margin-bottom:10px;letter-spacing:1px;text-transform:uppercase;text-align:center;">Authentication Code</label>
                    <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:16px;" id="enableBoxes">
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code">
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                        <div class="amob-otp-divider" style="width:12px;height:2px;background:#D1D5DB;border-radius:2px;flex-shrink:0;"></div>
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                    </div>

                    <button type="submit" class="amob-btn"
                            style="width:100%;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;font-size:14px;font-weight:600;padding:13px;border:none;border-radius:12px;cursor:pointer;box-shadow:0 6px 20px rgba(79,70,229,.35);transition:opacity .15s;"
                            onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                        <i class="fas fa-check" style="margin-right:6px;font-size:11px;"></i> Enable Two-Factor Auth
                    </button>
                </form>
            </div>
        </div>

        {{-- Footer --}}
        @php $forceMfa = (\App\Models\Setting::get('force_mfa','0') === '1'); @endphp
        <div style="margin-top:auto;padding-top:18px;border-top:1px solid #F3F4F6;text-align:center;">
            @unless($forceMfa)
                <a href="{{ match(auth()->user()->role) { 'admin' => route('admin.dashboard'), 'manager' => route('manager.dashboard'), default => route('user.dashboard') } }}"
                   style="font-size:12px;color:#9CA3AF;text-decoration:none;display:inline-flex;align-items:center;gap:5px;"
                   onmouseover="this.style.color='#6B7280'" onmouseout="this.style.color='#9CA3AF'">
                    <i class="fas fa-clock" style="font-size:10px;"></i> Skip for now
                </a>
            @else
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" style="font-size:12px;color:#9CA3AF;background:none;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:5px;"
                            onmouseover="this.style.color='#6B7280'" onmouseout="this.style.color='#9CA3AF'">
                        <i class="fa fa-arrow-left" style="font-size:10px;"></i> Back to login
                    </button>
                </form>
            @endunless
        </div>

    </div>

    {{-- ── Right: Decorative ── --}}
    <div class="mfa-deco-col">
        {{-- Background orbs --}}
        <div style="position:absolute;width:260px;height:260px;border-radius:50%;background:rgba(255,255,255,0.05);top:-80px;right:-80px;pointer-events:none;"></div>
        <div style="position:absolute;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,0.05);bottom:-50px;left:-50px;pointer-events:none;"></div>

        <h2 style="color:#fff;font-size:20px;font-weight:800;text-align:center;margin:0 0 6px;position:relative;z-index:1;line-height:1.3;">One-Time Setup,<br><span style="color:#BAE6FD;">Lifetime Protection</span></h2>
        <p style="color:rgba(255,255,255,0.6);font-size:12px;text-align:center;margin:0 0 32px;position:relative;z-index:1;">Two-factor authentication keeps your account safe even if your password is compromised.</p>

        {{-- Orbit illustration --}}
        <div style="position:relative;width:190px;height:190px;flex-shrink:0;">
            <div style="position:absolute;inset:0;border-radius:50%;border:1px solid rgba(255,255,255,0.15);"></div>
            <div style="position:absolute;inset:18px;border-radius:50%;border:1px solid rgba(255,255,255,0.10);"></div>
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">
                <div class="pulse-ring"></div>
                <div class="pulse-ring pulse-ring2"></div>
                <div class="pulse-ring pulse-ring3"></div>
            </div>
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:52px;height:52px;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);border-radius:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 30px rgba(0,0,0,0.25);z-index:2;">
                <i class="fas fa-mobile-screen-button" style="color:#fff;font-size:22px;"></i>
            </div>
            <div class="orbit-icon o1" style="background:#fff;"><i class="fas fa-lock" style="color:#4F46E5;font-size:14px;"></i></div>
            <div class="orbit-icon o2" style="background:#EFF6FF;"><i class="fas fa-shield-halved" style="color:#3B82F6;font-size:14px;"></i></div>
            <div class="orbit-icon o3" style="background:#F0FDF4;"><i class="fas fa-check-circle" style="color:#10B981;font-size:14px;"></i></div>
            <div class="orbit-icon o4" style="background:#FFF7ED;"><i class="fas fa-key" style="color:#F59E0B;font-size:13px;"></i></div>
            <div class="orbit-icon o5" style="background:#FDF4FF;"><i class="fas fa-fingerprint" style="color:#8B5CF6;font-size:14px;"></i></div>
            <div class="orbit-icon o6" style="background:#FEF2F2;"><i class="fas fa-qrcode" style="color:#EF4444;font-size:13px;"></i></div>
        </div>

        {{-- Tips --}}
        <div style="margin-top:28px;position:relative;z-index:1;width:100%;display:flex;flex-direction:column;gap:10px;">
            <div style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:12px;padding:12px 14px;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px;">
                    <i class="fas fa-circle-info" style="color:rgba(255,255,255,0.6);font-size:11px;"></i>
                    <span style="color:rgba(255,255,255,0.6);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;">Tip</span>
                </div>
                <p style="color:rgba(255,255,255,0.55);font-size:11px;margin:0;line-height:1.6;">Codes rotate every 30 seconds. Enter the current code shown in your app — never share it with anyone.</p>
            </div>
            <div style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:12px;padding:12px 14px;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px;">
                    <i class="fas fa-life-ring" style="color:rgba(255,255,255,0.6);font-size:11px;"></i>
                    <span style="color:rgba(255,255,255,0.6);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;">Recovery</span>
                </div>
                <p style="color:rgba(255,255,255,0.55);font-size:11px;margin:0;line-height:1.6;">After enabling MFA you'll receive recovery codes. Store them in a safe place in case you lose your phone.</p>
            </div>
        </div>

        @if(!empty($appSettings['copyright']))
        <p style="position:absolute;bottom:14px;font-size:10px;color:rgba(255,255,255,0.3);margin:0;z-index:1;text-align:center;">{{ $appSettings['copyright'] }}</p>
        @endif
    </div>

</div>

<script>
(function () {
    const container = document.getElementById('enableBoxes');
    if (!container) return;
    const inputs = Array.from(container.querySelectorAll('input.otp-box'));
    const hidden  = document.getElementById('enableCode');
    const form    = document.getElementById('enableForm');

    function sync() {
        hidden.value = inputs.map(i => i.value).join('');
        inputs.forEach(i => i.classList.toggle('filled', !!i.value));
    }

    inputs.forEach((inp, idx) => {
        inp.addEventListener('focus', () => inp.select());
        inp.addEventListener('input', () => {
            const v = inp.value.replace(/\D/g, '');
            inp.value = v ? v[v.length - 1] : '';
            sync();
            if (inp.value && idx < inputs.length - 1) inputs[idx + 1].focus();
            if (hidden.value.length === 6) form.submit();
        });
        inp.addEventListener('keydown', e => {
            if (e.key === 'Backspace') {
                if (!inp.value && idx > 0) { inputs[idx - 1].focus(); inputs[idx - 1].value = ''; sync(); }
            } else if (e.key === 'ArrowLeft'  && idx > 0) inputs[idx - 1].focus();
              else if (e.key === 'ArrowRight' && idx < inputs.length - 1) inputs[idx + 1].focus();
        });
        inp.addEventListener('paste', e => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            inputs.forEach((inp2, j) => { inp2.value = paste[j] || ''; });
            sync();
            if (paste.length === 6) { form.submit(); }
            else inputs[Math.min(paste.length, inputs.length - 1)].focus();
        });
    });

    inputs[0] && inputs[0].focus();
})();

// Copy secret key
window._copied = false;
function copySecret() {
    const text = document.getElementById('mfaSecret').textContent.replace(/\s/g, '');
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById('copyBtn');
        window._copied = true;
        btn.innerHTML = '<i class="fas fa-check" style="font-size:11px;"></i> Copied!';
        btn.style.background = '#F0FDF4';
        btn.style.color = '#16A34A';
        btn.style.borderColor = '#BBF7D0';
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-copy" style="font-size:11px;"></i> Copy Key';
            btn.style.background = '#EEF2FF';
            btn.style.color = '#4F46E5';
            btn.style.borderColor = '#C7D2FE';
            window._copied = false;
        }, 2000);
    });
}
</script>
@endsection

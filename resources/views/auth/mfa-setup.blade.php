@extends('layouts.auth')

@section('content')
<style>
.mfa-setup-card {
    width: 100%;
    max-width: 480px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(79,70,229,0.15);
    overflow: hidden;
}
.mfa-step {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 20px;
}
.mfa-step-num {
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
    padding: 6px 10px;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    color: #374151;
    background: #F9FAFB;
    text-decoration: none;
    transition: border-color 0.15s, background 0.15s;
    cursor: default;
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
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    caret-color: transparent;
}
.otp-box:focus { border-color: #6366F1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); background: #fff; }
.otp-box.filled { border-color: #6366F1; background: #F5F3FF; color: #4F46E5; }
</style>

<div class="mfa-setup-card">
    <div style="padding:36px 36px 28px;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <div style="width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,#4F46E5,#6366F1);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(79,70,229,0.35);flex-shrink:0;">
                <i class="fas fa-shield-halved" style="color:#fff;font-size:18px;"></i>
            </div>
            <div>
                <h1 style="font-size:17px;font-weight:800;color:#111827;margin:0;">Set Up Two-Factor Authentication</h1>
                <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Secure your account with an authenticator app</p>
            </div>
        </div>

        @if($errors->any())
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:12px;color:#DC2626;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-exclamation-circle"></i> {{ $errors->first() }}
        </div>
        @endif

        {{-- Step 1: Install app --}}
        <div class="mfa-step">
            <div class="mfa-step-num">1</div>
            <div style="flex:1;">
                <p style="font-size:12px;font-weight:700;color:#374151;margin:0 0 6px;">Install an authenticator app</p>
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
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="#EF4444">
                            <circle cx="12" cy="12" r="12" fill="#EF4444"/>
                            <path d="M12 6l1.5 4.5H18l-3.75 2.7 1.5 4.5L12 15l-3.75 2.7 1.5-4.5L6 10.5h4.5z" fill="#fff"/>
                        </svg>
                        Authy
                    </span>
                    <span class="app-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                            <rect width="24" height="24" rx="4" fill="#00A4EF"/>
                            <path d="M7 7h4v4H7zm6 0h4v4h-4zM7 13h4v4H7zm6 0h4v4h-4z" fill="#fff"/>
                        </svg>
                        Microsoft Auth
                    </span>
                </div>
            </div>
        </div>

        {{-- Divider --}}
        <div style="height:1px;background:#F3F4F6;margin:4px 0 20px 40px;"></div>

        {{-- Step 2: QR Code --}}
        <div class="mfa-step">
            <div class="mfa-step-num">2</div>
            <div style="flex:1;">
                <p style="font-size:12px;font-weight:700;color:#374151;margin:0 0 8px;">Scan this QR code</p>
                <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:14px;display:inline-block;margin-bottom:10px;">
                    {!! $qrSvg !!}
                </div>
                <p style="font-size:11px;color:#9CA3AF;margin:0 0 6px;">Can't scan? Enter this key manually:</p>
                <div style="display:flex;align-items:center;gap:6px;">
                    <code id="mfaSecret" style="font-size:12px;font-weight:700;color:#374151;letter-spacing:2px;font-family:monospace;background:#F3F4F6;padding:7px 10px;border-radius:8px;display:inline-block;flex:1;">{{ chunk_split($secret, 4, ' ') }}</code>
                    <button type="button" onclick="copySecret()" id="copyBtn"
                            style="display:inline-flex;align-items:center;gap:5px;padding:7px 10px;background:#EEF2FF;color:#4F46E5;border:1.5px solid #C7D2FE;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap;transition:background 0.15s;"
                            onmouseover="this.style.background='#E0E7FF'" onmouseout="if(!window._copied)this.style.background='#EEF2FF'">
                        <i class="fas fa-copy" style="font-size:11px;"></i> Copy
                    </button>
                </div>
            </div>
        </div>

        {{-- Divider --}}
        <div style="height:1px;background:#F3F4F6;margin:4px 0 20px 40px;"></div>

        {{-- Step 3: Verify code --}}
        <div class="mfa-step">
            <div class="mfa-step-num">3</div>
            <div style="flex:1;width:100%;">
                <p style="font-size:12px;font-weight:700;color:#374151;margin:0 0 10px;">Enter the 6-digit code to confirm</p>
                <form method="POST" action="{{ route('mfa.enable') }}" id="enableForm">
                    @csrf
                    <input type="hidden" name="code" id="enableCode">

                    <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:16px;" id="enableBoxes">
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code">
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                        <div style="width:12px;height:2px;background:#D1D5DB;border-radius:2px;flex-shrink:0;"></div>
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                        <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                    </div>

                    <button type="submit"
                            style="width:100%;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;font-size:14px;font-weight:600;padding:13px;border:none;border-radius:12px;cursor:pointer;box-shadow:0 6px 20px rgba(79,70,229,0.35);transition:opacity 0.15s;"
                            onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        <i class="fas fa-check" style="margin-right:6px;font-size:11px;"></i> Enable Two-Factor Auth
                    </button>
                </form>
            </div>
        </div>

        {{-- Footer actions --}}
        @php $forceMfa = (\App\Models\Setting::get('force_mfa','0') === '1'); @endphp
        <div style="margin-top:4px;padding-top:16px;border-top:1px solid #F3F4F6;text-align:center;">
            @unless($forceMfa)
                <a href="{{ match(auth()->user()->role) { 'admin' => route('admin.dashboard'), 'manager' => route('manager.dashboard'), default => route('user.dashboard') } }}"
                   style="font-size:12px;color:#9CA3AF;text-decoration:none;">
                    <i class="fas fa-clock" style="margin-right:4px;font-size:10px;"></i> Skip for now
                </a>
            @else
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" style="font-size:12px;color:#9CA3AF;background:none;border:none;cursor:pointer;">
                        <i class="fa fa-arrow-left" style="margin-right:4px;"></i> Back to login
                    </button>
                </form>
            @endunless
        </div>

    </div>
</div>

<script>
// OTP boxes — setup page (no auto-submit, user clicks button)
(function () {
    const container = document.getElementById('enableBoxes');
    if (!container) return;
    const inputs = Array.from(container.querySelectorAll('input.otp-box'));
    const hidden  = document.getElementById('enableCode');

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
            inputs[Math.min(paste.length, inputs.length - 1)].focus();
        });
    });
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
            btn.innerHTML = '<i class="fas fa-copy" style="font-size:11px;"></i> Copy';
            btn.style.background = '#EEF2FF';
            btn.style.color = '#4F46E5';
            btn.style.borderColor = '#C7D2FE';
            window._copied = false;
        }, 2000);
    });
}
</script>
@endsection

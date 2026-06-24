@extends('layouts.auth')

@section('content')
<style>
.mfa-card { width:100%;max-width:440px;background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(79,70,229,0.15);overflow:hidden; }
.otp-box { width:44px;height:52px;border:1.5px solid #E5E7EB;border-radius:11px;text-align:center;font-size:22px;font-weight:700;font-family:monospace;color:#111827;background:#F9FAFB;outline:none;transition:border-color 0.15s,box-shadow 0.15s,background 0.15s;caret-color:transparent; }
.otp-box:focus { border-color:#6366F1;box-shadow:0 0 0 3px rgba(99,102,241,0.12);background:#fff; }
.otp-box.filled { border-color:#6366F1;background:#F5F3FF;color:#4F46E5; }
[x-cloak] { display:none !important; }
</style>

<div class="mfa-card" x-data="{ codeSent: {{ $sent ? 'true' : 'false' }} }">
    <div style="padding:36px 36px 28px;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <div style="width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,#0EA5E9,#6366F1);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(99,102,241,0.35);flex-shrink:0;">
                <i class="fas fa-envelope-open-text" style="color:#fff;font-size:18px;"></i>
            </div>
            <div>
                <h1 style="font-size:17px;font-weight:800;color:#111827;margin:0;" x-text="codeSent ? 'Check Your Email' : 'Email Recovery'"></h1>
                <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;" x-text="codeSent ? 'Enter the 6-digit code we sent you' : 'We\'ll send a one-time code to your email'"></p>
            </div>
        </div>

        {{-- Errors --}}
        @if($errors->any())
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:12px;color:#DC2626;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-exclamation-circle"></i> {{ $errors->first() }}
        </div>
        @endif

        @if(session('code_sent'))
        <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:12px;color:#16A34A;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-circle-check"></i> A recovery code was sent to your email. Check your inbox.
        </div>
        @endif

        {{-- Step 1: Send code --}}
        <div x-show="!codeSent">
            <div style="background:#F8FAFF;border:1px solid #E0E7FF;border-radius:12px;padding:16px;margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-envelope" style="color:#6366F1;font-size:14px;"></i>
                    </div>
                    <div>
                        <p style="margin:0;font-size:11px;color:#9CA3AF;">Sending code to</p>
                        <p style="margin:2px 0 0;font-size:14px;font-weight:700;color:#111827;font-family:monospace;">{{ $masked }}</p>
                    </div>
                </div>
            </div>

            <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:10px 14px;margin-bottom:20px;font-size:11px;color:#92400E;display:flex;gap:8px;">
                <i class="fas fa-triangle-exclamation" style="margin-top:1px;flex-shrink:0;"></i>
                <span>The code will expire in <strong>10 minutes</strong>. You can request up to 3 codes per session.</span>
            </div>

            <form method="POST" action="{{ route('mfa.send-email-code') }}">
                @csrf
                <button type="submit"
                        style="width:100%;background:linear-gradient(135deg,#0EA5E9,#6366F1);color:#fff;font-size:14px;font-weight:600;padding:13px;border:none;border-radius:12px;cursor:pointer;box-shadow:0 6px 20px rgba(99,102,241,0.3);transition:opacity 0.15s;"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-paper-plane" style="margin-right:6px;font-size:12px;"></i> Send Recovery Code
                </button>
            </form>
        </div>

        {{-- Step 2: Enter code --}}
        <div x-show="codeSent" x-cloak>
            <form method="POST" action="{{ route('mfa.verify-email') }}" id="emailOtpForm">
                @csrf
                <input type="hidden" name="code" id="emailOtpCode">

                <label style="display:block;font-size:10px;font-weight:600;color:#9CA3AF;margin-bottom:10px;text-align:center;letter-spacing:1px;text-transform:uppercase;">Recovery Code</label>
                <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:20px;" id="emailOtpBoxes">
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                    <div style="width:12px;height:2px;background:#D1D5DB;border-radius:2px;flex-shrink:0;"></div>
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                </div>

                <button type="submit"
                        style="width:100%;background:linear-gradient(135deg,#0EA5E9,#6366F1);color:#fff;font-size:14px;font-weight:600;padding:13px;border:none;border-radius:12px;cursor:pointer;box-shadow:0 6px 20px rgba(99,102,241,0.3);transition:opacity 0.15s;margin-bottom:12px;"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-arrow-right" style="margin-right:6px;font-size:12px;"></i> Verify Code
                </button>
            </form>

            {{-- Resend --}}
            <form method="POST" action="{{ route('mfa.send-email-code') }}" style="text-align:center;">
                @csrf
                <button type="submit" style="font-size:12px;color:#6366F1;background:none;border:none;cursor:pointer;font-weight:500;">
                    <i class="fas fa-rotate-right" style="font-size:10px;margin-right:3px;"></i> Resend code
                </button>
            </form>
        </div>

        {{-- Back links --}}
        <div style="margin-top:18px;padding-top:14px;border-top:1px solid #F3F4F6;text-align:center;display:flex;align-items:center;justify-content:center;gap:16px;">
            <a href="{{ route('mfa.challenge') }}" style="font-size:12px;color:#9CA3AF;text-decoration:none;">
                <i class="fas fa-shield-halved" style="font-size:10px;margin-right:3px;"></i> Use authenticator app
            </a>
            <span style="color:#E5E7EB;">|</span>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" style="font-size:12px;color:#9CA3AF;background:none;border:none;cursor:pointer;">
                    <i class="fa fa-arrow-left" style="margin-right:3px;"></i> Back to login
                </button>
            </form>
        </div>

    </div>
</div>

<script>
(function () {
    const container = document.getElementById('emailOtpBoxes');
    if (!container) return;
    const inputs = Array.from(container.querySelectorAll('input.otp-box'));
    const hidden  = document.getElementById('emailOtpCode');
    const form    = document.getElementById('emailOtpForm');

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
            if (paste.length === 6) form.submit();
            else inputs[Math.min(paste.length, inputs.length - 1)].focus();
        });
    });

    // Auto-focus first box
    inputs[0] && setTimeout(() => inputs[0].focus(), 100);
})();
</script>
@endsection

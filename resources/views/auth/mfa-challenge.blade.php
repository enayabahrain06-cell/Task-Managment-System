@extends('layouts.auth')

@section('content')
<style>
.mfa-card { width:100%;max-width:860px;background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(79,70,229,0.15);overflow:hidden;display:flex;min-height:480px; }
.mfa-form-col { flex:1;padding:48px 44px;display:flex;flex-direction:column;justify-content:center;min-width:0; }
.mfa-deco-col { width:340px;flex-shrink:0; }
.otp-box { width:46px;height:54px;border:1.5px solid #E5E7EB;border-radius:12px;text-align:center;font-size:24px;font-weight:700;font-family:monospace;color:#111827;background:#F9FAFB;outline:none;transition:border-color 0.15s,box-shadow 0.15s,background 0.15s;caret-color:transparent; }
.otp-box:focus { border-color:#6366F1;box-shadow:0 0 0 3px rgba(99,102,241,0.12);background:#fff; }
.otp-box.filled { border-color:#6366F1;background:#F5F3FF;color:#4F46E5; }
@media(max-width:640px) {
    .mfa-card { flex-direction:column;min-height:unset;border-radius:16px; }
    .mfa-form-col { padding:32px 24px; }
    .mfa-deco-col { display:none !important; }
}
[x-cloak] { display:none !important; }
</style>

@php $authUser = auth()->user(); @endphp

<div class="mfa-card" x-data="{useRecovery: false}">

    {{-- ── Left: Form ── --}}
    <div class="mfa-form-col">

        {{-- Logo --}}
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:28px;">
            @if(!empty($appSettings['logo_path']))
                <img src="{{ Storage::url($appSettings['logo_path']) }}" alt="{{ $appSettings['app_name'] ?? 'Logo' }}" style="height:30px;width:auto;max-width:120px;object-fit:contain;border-radius:6px;">
            @else
                <div style="width:32px;height:32px;background:linear-gradient(135deg,#4F46E5,#6366F1);border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(79,70,229,0.35);">
                    <i class="fas fa-shield-halved" style="color:#fff;font-size:13px;"></i>
                </div>
            @endif
            <span style="font-size:14px;font-weight:700;color:#111827;">{{ $appSettings['company_name'] ?? $appSettings['app_name'] ?? config('app.name','Dash') }}</span>
        </div>

        {{-- User identity card --}}
        <div style="display:flex;align-items:center;gap:12px;background:#F8FAFF;border:1px solid #E0E7FF;border-radius:14px;padding:12px 16px;margin-bottom:24px;">
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
                <i class="fas fa-lock" style="font-size:9px;"></i> MFA
            </span>
        </div>

        {{-- Heading --}}
        <div style="margin-bottom:22px;">
            <h1 style="font-size:19px;font-weight:800;color:#111827;margin:0 0 4px;" x-text="useRecovery ? 'Enter Recovery Code' : 'Two-Factor Authentication'"></h1>
            <p style="font-size:12px;color:#9CA3AF;margin:0;" x-text="useRecovery ? 'Enter one of your saved recovery codes' : 'Enter the 6-digit code from your authenticator app'"></p>
        </div>

        {{-- Error --}}
        @if($errors->any())
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:12px;color:#DC2626;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-exclamation-circle"></i> {{ $errors->first() }}
        </div>
        @endif

        {{-- TOTP Form --}}
        <div x-show="!useRecovery">
            <form method="POST" action="{{ route('mfa.verify') }}" id="totpForm">
                @csrf
                <input type="hidden" name="code" id="totpCode">

                <label style="display:block;font-size:10px;font-weight:600;color:#9CA3AF;margin-bottom:10px;text-align:center;letter-spacing:1px;text-transform:uppercase;">Authentication Code</label>
                <div style="display:flex;align-items:center;justify-content:center;gap:7px;margin-bottom:22px;" id="totpBoxes">
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code">
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                    <div style="width:14px;height:2px;background:#D1D5DB;border-radius:2px;flex-shrink:0;"></div>
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="off">
                </div>

                <button type="submit" id="totpSubmitBtn"
                        style="width:100%;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;font-size:14px;font-weight:600;padding:13px;border:none;border-radius:12px;cursor:pointer;box-shadow:0 6px 20px rgba(79,70,229,0.35);transition:opacity 0.15s;"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-arrow-right" style="margin-right:6px;font-size:12px;"></i> Verify
                </button>
            </form>
        </div>

        {{-- Recovery Code Form --}}
        <div x-show="useRecovery" x-cloak>
            <form method="POST" action="{{ route('mfa.verify') }}">
                @csrf
                <label style="display:block;font-size:10px;font-weight:600;color:#9CA3AF;margin-bottom:8px;text-align:center;letter-spacing:1px;text-transform:uppercase;">Recovery Code</label>
                <input type="text" name="code" inputmode="text" autocomplete="off" placeholder="XXXXX-XXXXX" maxlength="11"
                       style="width:100%;padding:14px;border:1.5px solid #E5E7EB;border-radius:12px;font-size:18px;font-weight:700;text-align:center;letter-spacing:4px;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;font-family:monospace;margin-bottom:20px;"
                       onfocus="this.style.borderColor='#6366F1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)'"
                       onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none'">
                <button type="submit"
                        style="width:100%;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;font-size:14px;font-weight:600;padding:13px;border:none;border-radius:12px;cursor:pointer;box-shadow:0 6px 20px rgba(79,70,229,0.35);transition:opacity 0.15s;"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-arrow-right" style="margin-right:6px;font-size:12px;"></i> Verify
                </button>
            </form>
        </div>

        {{-- Toggle --}}
        <div style="margin-top:14px;text-align:center;">
            <button type="button" @click="useRecovery=!useRecovery"
                    style="font-size:12px;color:#6366F1;background:none;border:none;cursor:pointer;font-weight:500;"
                    x-text="useRecovery ? '← Use authenticator app' : 'Use recovery code instead'"></button>
        </div>

        {{-- Back to login --}}
        <div style="margin-top:18px;padding-top:14px;border-top:1px solid #F3F4F6;text-align:center;">
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" style="font-size:12px;color:#9CA3AF;background:none;border:none;cursor:pointer;">
                    <i class="fa fa-arrow-left" style="margin-right:4px;"></i> Back to login
                </button>
            </form>
        </div>

    </div>

    {{-- ── Right: Decorative ── --}}
    <div class="mfa-deco-col" style="background:linear-gradient(145deg,#4338CA 0%,#4F46E5 50%,#6366F1 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 28px;position:relative;overflow:hidden;">

        <div style="position:absolute;width:260px;height:260px;border-radius:50%;background:rgba(255,255,255,0.05);top:-80px;right:-80px;pointer-events:none;"></div>
        <div style="position:absolute;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,0.05);bottom:-50px;left:-50px;pointer-events:none;"></div>

        <h2 style="color:#fff;font-size:20px;font-weight:800;text-align:center;margin:0 0 6px;position:relative;z-index:1;line-height:1.3;">Your Account<br><span style="color:#BAE6FD;">is Protected</span></h2>
        <p style="color:rgba(255,255,255,0.6);font-size:12px;text-align:center;margin:0 0 36px;position:relative;z-index:1;">Two-factor authentication adds<br>an extra layer of security.</p>

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
                <i class="fas fa-shield-halved" style="color:#fff;font-size:22px;"></i>
            </div>
            <div class="orbit-icon o1" style="background:#fff;"><i class="fas fa-lock" style="color:#4F46E5;font-size:14px;"></i></div>
            <div class="orbit-icon o2" style="background:#EFF6FF;"><i class="fas fa-mobile-screen" style="color:#3B82F6;font-size:14px;"></i></div>
            <div class="orbit-icon o3" style="background:#F0FDF4;"><i class="fas fa-check-circle" style="color:#10B981;font-size:14px;"></i></div>
            <div class="orbit-icon o4" style="background:#FFF7ED;"><i class="fas fa-key" style="color:#F59E0B;font-size:13px;"></i></div>
            <div class="orbit-icon o5" style="background:#FDF4FF;"><i class="fas fa-fingerprint" style="color:#8B5CF6;font-size:14px;"></i></div>
            <div class="orbit-icon o6" style="background:#FEF2F2;"><i class="fas fa-user-shield" style="color:#EF4444;font-size:13px;"></i></div>
        </div>

        {{-- Security tip card --}}
        <div style="margin-top:28px;position:relative;z-index:1;width:100%;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:12px;padding:14px 16px;">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                <i class="fas fa-circle-info" style="color:rgba(255,255,255,0.6);font-size:11px;"></i>
                <span style="color:rgba(255,255,255,0.6);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;">Security Tip</span>
            </div>
            <p style="color:rgba(255,255,255,0.55);font-size:11px;margin:0;line-height:1.6;">Never share your 6-digit code. Codes rotate every 30 seconds and can only be used once.</p>
        </div>

        @if(!empty($appSettings['copyright']))
        <p style="position:absolute;bottom:14px;font-size:10px;color:rgba(255,255,255,0.3);margin:0;z-index:1;">{{ $appSettings['copyright'] }}</p>
        @endif
    </div>

</div>

<script>
(function () {
    const container = document.getElementById('totpBoxes');
    if (!container) return;
    const inputs = Array.from(container.querySelectorAll('input.otp-box'));
    const hidden  = document.getElementById('totpCode');
    const form    = document.getElementById('totpForm');

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
</script>
@endsection

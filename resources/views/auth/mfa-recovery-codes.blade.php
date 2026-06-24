@extends('layouts.auth')

@section('content')
<style>
.mfa-card { width:100%;max-width:440px;background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(79,70,229,0.15);overflow:hidden; }
.recovery-grid { display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:16px 0; }
.recovery-code { background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:10px 12px;font-size:13px;font-weight:700;font-family:monospace;color:#374151;text-align:center;letter-spacing:2px; }
</style>

<div class="mfa-card">
    <div style="padding:36px 36px 28px;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <div style="width:44px;height:44px;border-radius:14px;background:#F0FDF4;border:1.5px solid #BBF7D0;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-check-circle" style="color:#16A34A;font-size:20px;"></i>
            </div>
            <div>
                <h1 style="font-size:18px;font-weight:800;color:#111827;margin:0;">
                    {{ isset($regenerated) && $regenerated ? 'New Recovery Codes' : 'MFA Enabled!' }}
                </h1>
                <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Save these recovery codes in a safe place</p>
            </div>
        </div>

        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:12px;color:#92400E;display:flex;gap:8px;">
            <i class="fas fa-triangle-exclamation" style="margin-top:1px;flex-shrink:0;"></i>
            <span>These codes are shown <strong>only once</strong>. Each code can be used once to access your account if you lose your authenticator app.</span>
        </div>

        <div class="recovery-grid">
            @foreach($codes as $code)
            <div class="recovery-code">{{ $code }}</div>
            @endforeach
        </div>

        {{-- Copy all button --}}
        <button type="button" onclick="copyAllCodes()"
                style="width:100%;padding:10px;background:#EEF2FF;color:#4F46E5;border:1.5px solid #C7D2FE;border-radius:10px;font-size:12px;font-weight:600;cursor:pointer;margin-bottom:14px;display:flex;align-items:center;justify-content:center;gap:8px;"
                onmouseover="this.style.background='#E0E7FF'" onmouseout="this.style.background='#EEF2FF'">
            <i class="fas fa-copy"></i> Copy All Codes
        </button>

        {{-- Continue --}}
        <a href="{{ match(auth()->user()->role) { 'admin' => route('admin.dashboard'), 'manager' => route('manager.dashboard'), default => route('user.dashboard') } }}"
           style="display:block;width:100%;text-align:center;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;font-size:14px;font-weight:600;padding:13px;border-radius:12px;text-decoration:none;box-shadow:0 6px 20px rgba(79,70,229,0.35);box-sizing:border-box;">
            <i class="fas fa-arrow-right" style="margin-right:6px;font-size:12px;"></i> Continue to Dashboard
        </a>

    </div>
</div>

<script>
function copyAllCodes() {
    const codes = @json($codes);
    navigator.clipboard.writeText(codes.join('\n')).then(() => {
        const btn = event.target.closest('button');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.style.background = '#F0FDF4';
        btn.style.color = '#16A34A';
        btn.style.borderColor = '#BBF7D0';
        setTimeout(() => { btn.innerHTML = orig; btn.style.background=''; btn.style.color=''; btn.style.borderColor=''; }, 2000);
    });
}
</script>
@endsection

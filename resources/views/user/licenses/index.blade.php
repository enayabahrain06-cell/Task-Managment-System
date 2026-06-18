@extends('layouts.app')
@section('title', 'My Licenses')

@section('content')
<style>
.lic-card { background:#fff; border:1.5px solid #E5E7EB; border-radius:16px; overflow:hidden; display:flex; flex-direction:column; transition:box-shadow .2s, transform .2s; }
.lic-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.1); transform:translateY(-2px); }
.lic-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
@media(max-width:900px) { .lic-grid { grid-template-columns:repeat(2,1fr) !important; } }
@media(max-width:560px) { .lic-grid { grid-template-columns:1fr !important; } }
.cred-val { font-size:13px; font-weight:600; color:#111827; font-family:monospace; background:#F3F4F6; border-radius:7px; padding:6px 10px; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cred-copy { border:none; background:#EEF2FF; color:#4F46E5; border-radius:7px; padding:6px 10px; cursor:pointer; font-size:11px; font-weight:700; flex-shrink:0; transition:background .15s; }
.cred-copy:hover { background:#C7D2FE; }
.cred-reveal { border:none; background:#F3F4F6; color:#6B7280; border-radius:7px; padding:6px 10px; cursor:pointer; font-size:11px; flex-shrink:0; transition:background .15s; }
.cred-reveal:hover { background:#E5E7EB; }
.attach-row { display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; background:#F9FAFB; text-decoration:none; transition:background .15s; }
.attach-row:hover { background:#EEF2FF; }
</style>

@php
    $catColors = \App\Models\Subscription::categoryColors();
    $catLabels = \App\Models\Subscription::categoryOptions();
    $catIcons  = [
        'design'        => 'pen-nib',
        'development'   => 'code',
        'communication' => 'comment-dots',
        'marketing'     => 'bullhorn',
        'security'      => 'shield-halved',
        'finance'       => 'chart-line',
        'productivity'  => 'bolt',
        'other'         => 'layer-group',
    ];
@endphp

{{-- Header --}}
<div style="display:flex;align-items:center;gap:16px;margin-bottom:28px;">
    <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#6366F1,#4F46E5);display:flex;align-items:center;justify-content:center;">
        <i class="fas fa-layer-group" style="color:#fff;font-size:20px;"></i>
    </div>
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0;">My Licenses</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">
            {{ $licenses->count() }} tool{{ $licenses->count() !== 1 ? 's' : '' }} assigned to you
        </p>
    </div>
</div>

@if($licenses->isEmpty())
{{-- Empty state --}}
<div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:16px;padding:72px 24px;text-align:center;">
    <div style="width:72px;height:72px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
        <i class="fas fa-layer-group" style="font-size:28px;color:#D1D5DB;"></i>
    </div>
    <h2 style="font-size:18px;font-weight:700;color:#111827;margin:0 0 8px;">No licenses assigned yet</h2>
    <p style="font-size:14px;color:#9CA3AF;margin:0;">Your administrator will assign software licenses to you here.</p>
</div>

@else

{{-- Expiring alert --}}
@php $expiring = $licenses->filter(fn($l) => $l->days_until_renewal !== null && $l->days_until_renewal >= 0 && $l->days_until_renewal <= 7); @endphp
@if($expiring->count())
<div style="background:#FFF7ED;border:1.5px solid #FED7AA;border-radius:12px;padding:14px 20px;margin-bottom:24px;display:flex;align-items:flex-start;gap:12px;">
    <i class="fas fa-triangle-exclamation" style="color:#EA580C;margin-top:2px;flex-shrink:0;"></i>
    <div>
        <div style="font-size:13px;font-weight:700;color:#C2410C;margin-bottom:4px;">
            {{ $expiring->count() }} license{{ $expiring->count()>1?'s':'' }} expiring within 7 days
        </div>
        <div style="font-size:12px;color:#9A3412;">
            These subscriptions may be interrupted soon. Contact your administrator if needed.
        </div>
    </div>
</div>
@endif

{{-- License Cards Grid --}}
<div class="lic-grid">
    @foreach($licenses as $lic)
    @php
        $cc          = $catColors[$lic->category] ?? $catColors['other'];
        $icon        = $catIcons[$lic->category] ?? 'layer-group';
        $status      = $lic->status;
        $days        = $lic->days_until_renewal;
        $statusBg    = $status === 'expired' ? '#FEE2E2' : ($status === 'expiring_soon' ? '#FEF3C7' : '#ECFDF5');
        $statusColor = $status === 'expired' ? '#DC2626' : ($status === 'expiring_soon' ? '#D97706' : '#16A34A');
        $statusLabel = $status === 'expired' ? 'Expired' : ($status === 'expiring_soon' ? 'Expiring Soon' : 'Active');
        $statusIcon  = $status === 'expired' ? 'fa-triangle-exclamation' : ($status === 'expiring_soon' ? 'fa-clock' : 'fa-circle-check');
        $hasCredentials = $lic->username || $lic->password;
        $decryptedPw = $lic->decrypted_password;
    @endphp
    <div class="lic-card">

        {{-- Card Header --}}
        <div style="padding:20px 20px 16px;display:flex;align-items:flex-start;gap:14px;">
            {{-- Logo or icon --}}
            <div style="width:48px;height:48px;border-radius:14px;background:{{ $cc['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                @if($lic->logo_url)
                    <img src="{{ $lic->logo_url }}" alt="{{ $lic->name }}" style="width:100%;height:100%;object-fit:contain;padding:6px;">
                @else
                    <i class="fas fa-{{ $icon }}" style="font-size:20px;color:{{ $cc['color'] }};"></i>
                @endif
            </div>
            <div style="flex:1;min-width:0;">
                <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $lic->name }}</h3>
                @if($lic->vendor)
                <p style="font-size:12px;color:#9CA3AF;margin:0;">{{ $lic->vendor }}</p>
                @endif
                <span style="display:inline-flex;align-items:center;gap:4px;margin-top:6px;background:{{ $cc['bg'] }};color:{{ $cc['color'] }};padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;">
                    {{ $catLabels[$lic->category] ?? $lic->category }}
                </span>
            </div>
            <span style="display:inline-flex;align-items:center;gap:4px;background:{{ $statusBg }};color:{{ $statusColor }};padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;flex-shrink:0;">
                <i class="fas {{ $statusIcon }}" style="font-size:9px;"></i> {{ $statusLabel }}
            </span>
        </div>

        {{-- Renewal Countdown --}}
        @if($lic->renewal_date)
        <div style="margin:0 20px 16px;padding:12px 16px;border-radius:12px;background:{{ $status==='expired'?'#FFF5F5':($status==='expiring_soon'?'#FFFBEB':'#F0FDF4') }};border:1px solid {{ $status==='expired'?'#FCA5A5':($status==='expiring_soon'?'#FDE68A':'#A7F3D0') }};display:flex;align-items:center;justify-content:space-between;">
            <div>
                <p style="font-size:10px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;margin:0 0 2px;">
                    {{ $status === 'expired' ? 'Expired' : 'Renews' }}
                </p>
                <p style="font-size:13px;font-weight:700;color:#374151;margin:0;">{{ $lic->renewal_date->format('d M Y') }}</p>
            </div>
            <div style="text-align:right;">
                <p style="font-size:26px;font-weight:900;color:{{ $statusColor }};margin:0;line-height:1;">{{ $days !== null ? abs($days) : '—' }}</p>
                <p style="font-size:10px;color:#9CA3AF;margin:0;">{{ $status === 'expired' ? 'days ago' : 'days left' }}</p>
            </div>
        </div>
        @endif

        {{-- Details row --}}
        <div style="padding:0 20px 16px;display:flex;gap:8px;">
            <div style="flex:1;background:#F9FAFB;border-radius:10px;padding:10px 12px;text-align:center;">
                <p style="font-size:11px;color:#9CA3AF;margin:0 0 3px;font-weight:500;">Type</p>
                <p style="font-size:12px;font-weight:700;color:#374151;margin:0;">
                    {{ $lic->type === 'per_seat' ? 'Per Seat' : ($lic->type === 'site_license' ? 'Site License' : 'Shared') }}
                </p>
            </div>
            @if($lic->purchase_date)
            <div style="flex:1;background:#F9FAFB;border-radius:10px;padding:10px 12px;text-align:center;">
                <p style="font-size:11px;color:#9CA3AF;margin:0 0 3px;font-weight:500;">Since</p>
                <p style="font-size:12px;font-weight:700;color:#374151;margin:0;">{{ $lic->purchase_date->format('M Y') }}</p>
            </div>
            @endif
            @if($lic->website)
            <div style="flex:1;">
                <a href="{{ $lic->website }}" target="_blank"
                   style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;background:#EEF2FF;border-radius:10px;padding:10px 12px;text-decoration:none;transition:background .15s;"
                   onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='#EEF2FF'">
                    <i class="fas fa-arrow-up-right-from-square" style="font-size:14px;color:#4F46E5;margin-bottom:4px;"></i>
                    <span style="font-size:11px;font-weight:700;color:#4F46E5;">Open Tool</span>
                </a>
            </div>
            @endif
        </div>

        {{-- Credentials --}}
        @if($hasCredentials)
        <div style="margin:0 20px 16px;padding:14px;background:#F8FAFF;border:1px solid #E0E7FF;border-radius:12px;">
            <p style="font-size:11px;font-weight:700;color:#4F46E5;margin:0 0 10px;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:5px;">
                <i class="fas fa-key" style="font-size:10px;"></i> Login Credentials
            </p>
            @if($lic->username)
            <div style="margin-bottom:8px;">
                <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;font-weight:500;">Username / Email</p>
                <div style="display:flex;gap:6px;align-items:center;">
                    <span class="cred-val">{{ $lic->username }}</span>
                    <button class="cred-copy" onclick="licCopy(this, '{{ addslashes($lic->username) }}')" title="Copy">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            @endif
            @if($decryptedPw)
            <div>
                <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;font-weight:500;">Password</p>
                <div style="display:flex;gap:6px;align-items:center;">
                    <span class="cred-val" id="pw-{{ $lic->id }}" data-pw="{{ $decryptedPw }}" style="letter-spacing:.12em;">••••••••</span>
                    <button class="cred-reveal" onclick="licReveal({{ $lic->id }})" id="reveal-{{ $lic->id }}" title="Show/hide">
                        <i class="fas fa-eye" id="reveal-icon-{{ $lic->id }}"></i>
                    </button>
                    <button class="cred-copy" onclick="licCopy(this, document.getElementById('pw-{{ $lic->id }}').dataset.pw)" title="Copy">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Notes --}}
        @if($lic->notes)
        <div style="margin:0 20px 16px;padding:12px 14px;background:#F8FAFC;border-radius:10px;border-left:3px solid {{ $cc['color'] }};">
            <p style="font-size:11px;font-weight:600;color:#6B7280;margin:0 0 5px;text-transform:uppercase;letter-spacing:.04em;">
                <i class="fas fa-note-sticky" style="margin-right:4px;font-size:10px;"></i> Notes from Admin
            </p>
            <p style="font-size:12px;color:#374151;margin:0;line-height:1.6;white-space:pre-wrap;">{{ $lic->notes }}</p>
        </div>
        @endif

        {{-- Attachments --}}
        @if($lic->attachments->count())
        <div style="margin:0 20px 20px;">
            <p style="font-size:11px;font-weight:700;color:#6B7280;margin:0 0 8px;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:5px;">
                <i class="fas fa-paperclip" style="font-size:10px;"></i> Files ({{ $lic->attachments->count() }})
            </p>
            <div style="display:flex;flex-direction:column;gap:6px;">
                @foreach($lic->attachments as $att)
                <a href="{{ $att->url }}" target="_blank" class="attach-row">
                    <i class="fas {{ $att->icon_class }}" style="font-size:15px;color:#4F46E5;flex-shrink:0;width:18px;text-align:center;"></i>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $att->filename }}</p>
                        @if($att->comment)
                        <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $att->comment }}</p>
                        @endif
                    </div>
                    <span style="font-size:10px;color:#9CA3AF;flex-shrink:0;">{{ $att->formatted_size }}</span>
                    <i class="fas fa-download" style="font-size:11px;color:#4F46E5;flex-shrink:0;"></i>
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
    @endforeach
</div>
@endif

<script>
function licCopy(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        const icon = btn.querySelector('i');
        icon.className = 'fas fa-check';
        btn.style.background = '#D1FAE5';
        btn.style.color = '#059669';
        setTimeout(() => {
            icon.className = 'fas fa-copy';
            btn.style.background = '';
            btn.style.color = '';
        }, 1800);
    });
}

function licReveal(id) {
    const el   = document.getElementById('pw-' + id);
    const icon = document.getElementById('reveal-icon-' + id);
    const pw   = el.dataset.pw;
    if (el.textContent.includes('•')) {
        el.textContent = pw;
        icon.className = 'fas fa-eye-slash';
    } else {
        el.textContent = '••••••••';
        icon.className = 'fas fa-eye';
    }
}
</script>
@endsection

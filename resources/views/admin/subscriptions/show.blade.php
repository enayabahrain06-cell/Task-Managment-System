@extends('layouts.app')
@section('title', $subscription->name)

@section('content')
<style>
.info-card  { background:#fff; border:1.5px solid #E5E7EB; border-radius:14px; padding:20px; }
.info-label { font-size:11px; font-weight:600; color:#9CA3AF; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
.info-value { font-size:15px; font-weight:600; color:#111827; }
.sub-status-active        { background:#ECFDF5; color:#16A34A; }
.sub-status-expiring_soon { background:#FEF3C7; color:#D97706; }
.sub-status-expired       { background:#FEE2E2; color:#DC2626; }
@media (max-width:900px) {
    .show-grid   { grid-template-columns:1fr !important; }
    .stats-grid  { grid-template-columns:1fr 1fr !important; }
}

/* ── Drop zone animations ── */
@keyframes dz-float {
    0%, 100% { transform: translateY(0);    }
    50%       { transform: translateY(-6px); }
}
@keyframes dz-pulse-ring {
    0%   { transform: scale(.85); opacity:.6; }
    70%  { transform: scale(1.25); opacity:0; }
    100% { transform: scale(1.25); opacity:0; }
}
@keyframes dz-spin-in {
    0%   { transform: rotate(-20deg) scale(.7); opacity:0; }
    100% { transform: rotate(0deg)  scale(1);   opacity:1; }
}
@keyframes dz-dash {
    to { stroke-dashoffset: -24; }
}
@keyframes dz-shake {
    0%,100% { transform: translateX(0); }
    20%     { transform: translateX(-5px) rotate(-3deg); }
    40%     { transform: translateX( 5px) rotate( 3deg); }
    60%     { transform: translateX(-3px) rotate(-2deg); }
    80%     { transform: translateX( 3px) rotate( 2deg); }
}
@keyframes dz-glow-pulse {
    0%, 100% { box-shadow: 0 0 0   0  rgba(99,102,241,.0); }
    50%       { box-shadow: 0 0 22px 4px rgba(99,102,241,.25); }
}

.dz-zone {
    border: 2px dashed #D1D5DB;
    border-radius: 12px;
    padding: 28px 16px;
    text-align: center;
    cursor: pointer;
    background: #FAFAFA;
    transition: border-color .25s, background .25s, box-shadow .25s, transform .2s;
    position: relative;
    overflow: hidden;
}
.dz-zone:hover {
    border-color: #A5B4FC;
    background: #F5F3FF;
    box-shadow: 0 0 0 4px rgba(99,102,241,.08);
    transform: translateY(-1px);
}
.dz-zone.is-dragging {
    border-color: #6366F1;
    background: #EEF2FF;
    animation: dz-glow-pulse .9s ease-in-out infinite;
    transform: scale(1.015);
}
.dz-zone.is-dragging .dz-icon-wrap { animation: dz-shake .5s ease; }

.dz-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px; height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
    margin: 0 auto 12px;
    position: relative;
}
.dz-icon-wrap .dz-ring {
    position: absolute;
    inset: 0;
    border-radius: 16px;
    border: 2px solid #6366F1;
    opacity: 0;
    animation: dz-pulse-ring 2s ease-out infinite;
}
.dz-icon-wrap i {
    font-size: 22px;
    color: #6366F1;
    animation: dz-float 3s ease-in-out infinite, dz-spin-in .4s ease both;
}
.dz-zone.is-dragging .dz-icon-wrap i { animation: dz-shake .5s ease; }

.dz-particles span {
    position: absolute;
    width: 5px; height: 5px;
    border-radius: 50%;
    background: #818CF8;
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s;
}
.dz-zone.is-dragging .dz-particles span {
    animation: dz-pulse-ring .8s ease-out infinite;
}
.dz-zone.is-dragging .dz-particles span:nth-child(1) { top:15%; left:12%; animation-delay:.0s; background:#818CF8; }
.dz-zone.is-dragging .dz-particles span:nth-child(2) { top:20%; right:14%; animation-delay:.2s; background:#A5B4FC; }
.dz-zone.is-dragging .dz-particles span:nth-child(3) { bottom:18%; left:18%; animation-delay:.35s; background:#C7D2FE; }
.dz-zone.is-dragging .dz-particles span:nth-child(4) { bottom:22%; right:10%; animation-delay:.1s; background:#818CF8; }
</style>

@php
    $catColors  = \App\Models\Subscription::categoryColors();
    $cc         = $catColors[$subscription->category] ?? $catColors['other'];
    $status     = $subscription->status;
    $days       = $subscription->days_until_renewal;
    $usedSeats  = $subscription->users->count();
    $catIcon    = match($subscription->category) {
        'design'        => 'pen-nib',
        'development'   => 'code',
        'communication' => 'comment-dots',
        'marketing'     => 'bullhorn',
        'security'      => 'shield-halved',
        'finance'       => 'chart-line',
        default         => 'layer-group',
    };
@endphp

<div x-data="{
    editModal: false,
    deleteModal: false,
    assignModal: false,
    removeModal: false,
    removeUserId: null,
    removeUserName: '',
    showPassword: false,
    openRemove(id, name) { this.removeUserId = id; this.removeUserName = name; this.removeModal = true; },
}">

    {{-- Flash --}}
    @if(session('success'))
    <div style="background:#ECFDF5;border:1px solid #A7F3D0;color:#16A34A;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#FEE2E2;border:1px solid #FCA5A5;color:#DC2626;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
    </div>
    @endif

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:14px;">
        <div style="display:flex;align-items:center;gap:16px;">
            <a href="{{ route('admin.subscriptions.index') }}"
               style="width:36px;height:36px;border-radius:10px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;flex-shrink:0;"
               title="Back to Subscriptions">
                <i class="fas fa-arrow-left" style="font-size:13px;"></i>
            </a>
            <div style="width:56px;height:56px;border-radius:16px;background:{{ $cc['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;border:1.5px solid {{ $cc['bg'] }};">
                @if($subscription->logo_url)
                <img src="{{ $subscription->logo_url }}" alt="{{ $subscription->name }}" style="width:100%;height:100%;object-fit:contain;padding:6px;">
                @else
                <i class="fas fa-{{ $catIcon }}" style="color:{{ $cc['color'] }};font-size:22px;"></i>
                @endif
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0;">{{ $subscription->name }}</h1>
                    <span class="sub-status-{{ $status }}"
                          style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;">
                        @if($status === 'active')<i class="fas fa-circle" style="font-size:6px;"></i> Active
                        @elseif($status === 'expiring_soon')<i class="fas fa-clock" style="font-size:10px;"></i> Expiring Soon
                        @else<i class="fas fa-triangle-exclamation" style="font-size:10px;"></i> Expired
                        @endif
                    </span>
                    <span style="background:{{ $cc['bg'] }};color:{{ $cc['color'] }};padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:600;">
                        {{ \App\Models\Subscription::categoryOptions()[$subscription->category] ?? $subscription->category }}
                    </span>
                </div>
                @if($subscription->vendor)
                <p style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">{{ $subscription->vendor }}</p>
                @endif
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <button @click="editModal = true"
                    style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="fas fa-pen" style="font-size:11px;"></i> Edit
            </button>
            <button @click="deleteModal = true"
                    style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#FEF2F2;color:#DC2626;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="fas fa-trash" style="font-size:11px;"></i> Delete
            </button>
        </div>
    </div>

    {{-- Renewal Banner --}}
    @if($subscription->renewal_date)
    @php
        $bannerBg    = $status === 'expired' ? '#FEE2E2' : ($status === 'expiring_soon' ? '#FEF3C7' : '#EEF2FF');
        $bannerBdr   = $status === 'expired' ? '#FCA5A5' : ($status === 'expiring_soon' ? '#FDE68A' : '#C7D2FE');
        $bannerColor = $status === 'expired' ? '#DC2626' : ($status === 'expiring_soon' ? '#D97706' : '#4F46E5');
        $bannerIcon  = $status === 'expired' ? 'fa-triangle-exclamation' : ($status === 'expiring_soon' ? 'fa-clock' : 'fa-rotate');
    @endphp
    <div style="background:{{ $bannerBg }};border:1.5px solid {{ $bannerBdr }};border-radius:14px;padding:18px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:46px;height:46px;border-radius:12px;background:rgba(255,255,255,.6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas {{ $bannerIcon }}" style="color:{{ $bannerColor }};font-size:20px;"></i>
            </div>
            <div>
                <div style="font-size:13px;font-weight:700;color:{{ $bannerColor }};margin-bottom:2px;">
                    @if($status === 'expired') Subscription expired {{ abs($days) }} day{{ abs($days) === 1 ? '' : 's' }} ago
                    @elseif($days === 0) ⚡ Subscription renews today!
                    @elseif($days === 1) Subscription renews tomorrow
                    @else Subscription renews in {{ $days }} days
                    @endif
                </div>
                <div style="font-size:12px;color:#6B7280;">{{ $subscription->renewal_date->format('l, d F Y') }}</div>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:38px;font-weight:900;color:{{ $bannerColor }};line-height:1;">{{ $status === 'expired' ? abs($days) : ($days ?? '∞') }}</div>
            <div style="font-size:11px;color:#6B7280;font-weight:500;">{{ $status === 'expired' ? 'days overdue' : 'days left' }}</div>
        </div>
    </div>
    @endif

    {{-- Stats Row --}}
    <div class="stats-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
        <div class="info-card">
            <div class="info-label">Cost</div>
            <div class="info-value" style="color:#4F46E5;">{{ $subscription->currency }} {{ number_format($subscription->cost, 3) }}</div>
            <div style="font-size:12px;color:#9CA3AF;margin-top:2px;">per {{ $subscription->billing_cycle }}</div>
        </div>
        <div class="info-card">
            <div class="info-label">Monthly Equivalent</div>
            <div class="info-value">{{ $subscription->currency }} {{ number_format($subscription->monthly_cost, 3) }}</div>
            <div style="font-size:12px;color:#9CA3AF;margin-top:2px;">{{ $subscription->currency }} {{ number_format($subscription->annual_cost, 3) }} / year</div>
        </div>
        <div class="info-card">
            <div class="info-label">Seats</div>
            @if($subscription->max_seats)
            <div class="info-value" style="color:{{ $usedSeats >= $subscription->max_seats ? '#DC2626' : '#111827' }};">
                {{ $usedSeats }} / {{ $subscription->max_seats }}
            </div>
            <div style="margin-top:6px;background:#F3F4F6;border-radius:6px;height:6px;overflow:hidden;">
                <div style="height:100%;width:{{ min(100, $usedSeats / $subscription->max_seats * 100) }}%;background:{{ $usedSeats >= $subscription->max_seats ? '#EF4444' : '#4F46E5' }};border-radius:6px;"></div>
            </div>
            @else
            <div class="info-value">{{ $usedSeats }} <span style="font-size:13px;font-weight:400;color:#9CA3AF;">assigned</span></div>
            <div style="font-size:12px;color:#9CA3AF;margin-top:2px;">Unlimited seats</div>
            @endif
        </div>
    </div>

    {{-- Main Content Grid: Left (details) | Right (users + attachments) --}}
    <div class="show-grid" style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start;">

        {{-- ── Left Column ── --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Details --}}
            <div class="info-card">
                <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0 0 16px;">
                    <i class="fas fa-circle-info" style="color:#6366F1;margin-right:6px;font-size:13px;"></i>Details
                </h3>
                <div style="display:flex;flex-direction:column;gap:13px;">
                    <div>
                        <div class="info-label">License Type</div>
                        <div style="font-size:13px;color:#374151;">
                            @if($subscription->type === 'per_seat') Per Seat
                            @elseif($subscription->type === 'site_license') Site License
                            @else Shared @endif
                        </div>
                    </div>
                    <div>
                        <div class="info-label">Billing Cycle</div>
                        <div style="font-size:13px;color:#374151;">{{ ucfirst(str_replace('_',' ',$subscription->billing_cycle)) }}</div>
                    </div>
                    @if($subscription->purchase_date)
                    <div>
                        <div class="info-label">Purchase Date</div>
                        <div style="font-size:13px;color:#374151;">{{ $subscription->purchase_date->format('d M Y') }}</div>
                    </div>
                    @endif
                    @if($subscription->renewal_date)
                    <div>
                        <div class="info-label">Renewal Date</div>
                        <div style="font-size:13px;color:#374151;">{{ $subscription->renewal_date->format('d M Y') }}</div>
                    </div>
                    @endif
                    @if($subscription->website)
                    <div>
                        <div class="info-label">Website</div>
                        <a href="{{ $subscription->website }}" target="_blank"
                           style="font-size:13px;color:#4F46E5;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                            <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                            {{ parse_url($subscription->website, PHP_URL_HOST) ?? $subscription->website }}
                        </a>
                    </div>
                    @endif
                    <div>
                        <div class="info-label">Added By</div>
                        <div style="font-size:13px;color:#374151;">{{ $subscription->creator->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="info-label">Renewal Reminders</div>
                        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:4px;">
                            @foreach($subscription->notify_days as $d)
                            <span style="background:#EEF2FF;color:#4F46E5;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;">{{ $d }}d before</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Credentials --}}
            @if($subscription->username || $subscription->password)
            <div class="info-card" style="border-color:#E0E7FF;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-key" style="font-size:12px;color:#4F46E5;"></i>
                    </div>
                    <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">Credentials</h3>
                </div>
                @if($subscription->username)
                <div style="margin-bottom:12px;">
                    <div class="info-label">Username / Email</div>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                        <code style="font-size:13px;color:#374151;background:#F3F4F6;padding:5px 10px;border-radius:6px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $subscription->username }}</code>
                        <button onclick="copyToClipboard('{{ addslashes($subscription->username) }}', this)"
                                title="Copy" style="width:28px;height:28px;border:none;border-radius:6px;background:#F3F4F6;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;flex-shrink:0;">
                            <i class="fas fa-copy" style="font-size:11px;"></i>
                        </button>
                    </div>
                </div>
                @endif
                @if($subscription->password)
                <div>
                    <div class="info-label">Password / License Key</div>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                        <code id="sub-pwd-display" style="font-size:13px;color:#374151;background:#F3F4F6;padding:5px 10px;border-radius:6px;flex:1;letter-spacing:.15em;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">••••••••••••</code>
                        <button onclick="openRevealModal()" title="Reveal password"
                                style="width:28px;height:28px;border:none;border-radius:6px;background:#FEF3C7;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#D97706;flex-shrink:0;" title="Requires account password">
                            <i class="fas fa-lock" style="font-size:11px;"></i>
                        </button>
                        <button id="sub-pwd-copy" onclick="copyPwd(this)" title="Copy" style="display:none;width:28px;height:28px;border:none;border-radius:6px;background:#F3F4F6;cursor:pointer;display:none;align-items:center;justify-content:center;color:#6B7280;flex-shrink:0;">
                            <i class="fas fa-copy" style="font-size:11px;"></i>
                        </button>
                    </div>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:4px;display:flex;align-items:center;gap:4px;">
                        <i class="fas fa-shield-halved" style="font-size:9px;color:#D97706;"></i>
                        Account password required to reveal · Access is logged
                    </div>
                </div>

                {{-- Reveal password modal --}}
                <div id="reveal-pwd-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:none;align-items:center;justify-content:center;">
                    <div style="background:#fff;border-radius:16px;padding:28px;width:380px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.2);">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-lock" style="color:#D97706;font-size:16px;"></i>
                            </div>
                            <div>
                                <div style="font-size:15px;font-weight:700;color:#111827;">Reveal Password</div>
                                <div style="font-size:12px;color:#6B7280;">Enter your account password to continue</div>
                            </div>
                        </div>
                        <input type="password" id="reveal-pwd-input" placeholder="Your account password"
                               style="width:100%;padding:10px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;margin-bottom:6px;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"
                               onkeydown="if(event.key==='Enter')submitReveal()">
                        <div id="reveal-pwd-error" style="display:none;font-size:12px;color:#DC2626;margin-bottom:8px;"></div>
                        <div style="display:flex;gap:8px;margin-top:14px;">
                            <button onclick="closeRevealModal()" style="flex:1;padding:9px;border:1.5px solid #E5E7EB;border-radius:8px;background:#fff;font-size:13px;cursor:pointer;color:#374151;">Cancel</button>
                            <button onclick="submitReveal()" id="reveal-pwd-btn" style="flex:1;padding:9px;border:none;border-radius:8px;background:#4F46E5;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
                                <i class="fas fa-unlock" style="font-size:11px;margin-right:4px;"></i> Reveal
                            </button>
                        </div>
                    </div>
                </div>
                <script>
                var _revealTimer = null;
                function openRevealModal() {
                    document.getElementById('reveal-pwd-input').value = '';
                    document.getElementById('reveal-pwd-error').style.display = 'none';
                    document.getElementById('reveal-pwd-modal').style.display = 'flex';
                    setTimeout(() => document.getElementById('reveal-pwd-input').focus(), 50);
                }
                function closeRevealModal() {
                    document.getElementById('reveal-pwd-modal').style.display = 'none';
                }
                function submitReveal() {
                    var pwd = document.getElementById('reveal-pwd-input').value;
                    var btn = document.getElementById('reveal-pwd-btn');
                    var err = document.getElementById('reveal-pwd-error');
                    if (!pwd) { err.textContent = 'Please enter your password.'; err.style.display='block'; return; }
                    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:11px;margin-right:4px;"></i> Checking...';
                    fetch('{{ route('admin.subscriptions.reveal-password', $subscription->id) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ password: pwd })
                    }).then(r => r.json()).then(data => {
                        btn.disabled = false; btn.innerHTML = '<i class="fas fa-unlock" style="font-size:11px;margin-right:4px;"></i> Reveal';
                        if (data.error) { err.textContent = data.error; err.style.display='block'; return; }
                        closeRevealModal();
                        var display = document.getElementById('sub-pwd-display');
                        var copyBtn = document.getElementById('sub-pwd-copy');
                        display.textContent = data.secret;
                        display.style.letterSpacing = 'normal';
                        copyBtn.style.display = 'flex';
                        copyBtn._secret = data.secret;
                        if (_revealTimer) clearTimeout(_revealTimer);
                        _revealTimer = setTimeout(() => {
                            display.textContent = '••••••••••••';
                            display.style.letterSpacing = '.15em';
                            copyBtn.style.display = 'none';
                        }, 30000);
                    }).catch(() => {
                        btn.disabled = false; btn.innerHTML = '<i class="fas fa-unlock" style="font-size:11px;margin-right:4px;"></i> Reveal';
                        err.textContent = 'Request failed. Try again.'; err.style.display='block';
                    });
                }
                function copyPwd(btn) {
                    navigator.clipboard.writeText(btn._secret).then(() => {
                        var icon = btn.querySelector('i');
                        icon.className = 'fas fa-check'; icon.style.color = '#16A34A';
                        setTimeout(() => { icon.className = 'fas fa-copy'; icon.style.color = ''; }, 1500);
                    });
                }
                document.getElementById('reveal-pwd-modal').addEventListener('click', function(e) {
                    if (e.target === this) closeRevealModal();
                });
                </script>
                @endif
            </div>
            @endif

            {{-- Notes --}}
            @if($subscription->notes)
            <div class="info-card">
                <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0 0 10px;">
                    <i class="fas fa-note-sticky" style="color:#6366F1;margin-right:6px;font-size:13px;"></i>Notes
                </h3>
                <p style="font-size:13px;color:#374151;line-height:1.6;margin:0;white-space:pre-wrap;">{{ $subscription->notes }}</p>
            </div>
            @endif

        </div>{{-- end left column --}}

        {{-- ── Right Column ── --}}
        <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- Assigned Users --}}
            <div class="info-card" style="padding:0;overflow:hidden;">
                <div style="padding:16px 20px;border-bottom:1.5px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">
                            <i class="fas fa-users" style="color:#6366F1;margin-right:6px;font-size:13px;"></i>Assigned Users
                        </h3>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">{{ $usedSeats }} user{{ $usedSeats !== 1 ? 's' : '' }} with access</p>
                    </div>
                    @if($availableUsers->count())
                    <button @click="assignModal = true"
                            style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-user-plus" style="font-size:11px;"></i> Assign User
                    </button>
                    @endif
                </div>

                @if($subscription->users->isEmpty())
                <div style="padding:32px 24px;text-align:center;color:#9CA3AF;">
                    <i class="fas fa-users" style="font-size:28px;margin-bottom:10px;opacity:.3;display:block;"></i>
                    <p style="font-size:13px;font-weight:500;margin:0;">No users assigned yet</p>
                    <p style="font-size:12px;margin:4px 0 0;">Click "Assign User" to grant access</p>
                </div>
                @else
                <div>
                    @foreach($subscription->users as $user)
                    @php $avatarColors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6']; @endphp
                    <div style="padding:12px 20px;display:flex;align-items:center;justify-content:space-between;{{ !$loop->last ? 'border-bottom:1px solid #F9FAFB;' : '' }}">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:50%;background:{{ $avatarColors[$loop->index % 5] }};display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;flex-shrink:0;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:#111827;">{{ $user->name }}</div>
                                <div style="font-size:11.5px;color:#9CA3AF;">{{ $user->email }}</div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="text-align:right;">
                                <span style="background:#F3F4F6;color:#6B7280;padding:3px 8px;border-radius:20px;font-size:11px;font-weight:500;">{{ ucfirst($user->role) }}</span>
                                @if($user->pivot->assigned_at)
                                <div style="font-size:10.5px;color:#9CA3AF;margin-top:3px;">Since {{ \Carbon\Carbon::parse($user->pivot->assigned_at)->format('d M Y') }}</div>
                                @endif
                            </div>
                            <button @click="openRemove({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                    style="width:30px;height:30px;border-radius:8px;background:#FEF2F2;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#DC2626;"
                                    title="Remove" onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                                <i class="fas fa-user-minus" style="font-size:11px;"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Billing Attachments --}}
            <div class="info-card" style="padding:0;overflow:hidden;"
                 x-data="{
                     dragging: false,
                     files: [],
                     addFiles(fileList) { for (let f of fileList) this.files.push(f); },
                     removeFile(i) { this.files.splice(i, 1); },
                     formatSize(b) {
                         if (b >= 1048576) return (b/1048576).toFixed(1)+' MB';
                         if (b >= 1024)    return (b/1024).toFixed(1)+' KB';
                         return b+' B';
                     },
                     fileIcon(mime) {
                         if (!mime) return 'fa-file';
                         if (mime.includes('pdf'))   return 'fa-file-pdf';
                         if (mime.includes('image')) return 'fa-file-image';
                         if (mime.includes('word') || mime.includes('document')) return 'fa-file-word';
                         if (mime.includes('excel') || mime.includes('spreadsheet')) return 'fa-file-excel';
                         if (mime.includes('zip') || mime.includes('compressed')) return 'fa-file-zipper';
                         return 'fa-file';
                     },
                     submit() {
                         if (!this.files.length) return;
                         const dt = new DataTransfer();
                         this.files.forEach(f => dt.items.add(f));
                         this.$refs.fileInput.files = dt.files;
                         this.$refs.uploadForm.submit();
                     }
                 }">

                <div style="padding:16px 20px;border-bottom:1.5px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">
                            <i class="fas fa-paperclip" style="color:#6366F1;margin-right:6px;font-size:13px;"></i>Billing Attachments
                        </h3>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Invoices, receipts, and billing documents</p>
                    </div>
                    <span style="background:#EEF2FF;color:#4F46E5;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;">
                        {{ $subscription->attachments->count() }} file{{ $subscription->attachments->count() !== 1 ? 's' : '' }}
                    </span>
                </div>

                <div style="padding:18px 20px;">
                    {{-- Upload form --}}
                    <form x-ref="uploadForm" method="POST"
                          action="{{ route('admin.subscriptions.attachments.upload', $subscription) }}"
                          enctype="multipart/form-data">
                        @csrf
                        <input type="file" x-ref="fileInput" name="files[]" multiple
                               accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip"
                               style="display:none;" @change="addFiles($event.target.files); $event.target.value=''">

                        {{-- Drop zone --}}
                        <div class="dz-zone"
                             :class="dragging ? 'is-dragging' : ''"
                             @dragover.prevent="dragging=true"
                             @dragleave.prevent="dragging=false"
                             @drop.prevent="dragging=false; addFiles($event.dataTransfer.files)"
                             @click="$refs.fileInput.click()">

                            {{-- floating particles (visible while dragging) --}}
                            <div class="dz-particles" aria-hidden="true">
                                <span></span><span></span><span></span><span></span>
                            </div>

                            <div style="pointer-events:none;position:relative;z-index:1;">
                                <div class="dz-icon-wrap">
                                    <div class="dz-ring"></div>
                                    <i class="fas fa-cloud-arrow-up"></i>
                                </div>
                                <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 4px;transition:color .2s;"
                                   :style="dragging ? 'color:#4F46E5;' : ''">
                                    <span x-show="!dragging">Drop files here or click to browse</span>
                                    <span x-show="dragging" x-cloak>Release to upload</span>
                                </p>
                                <p style="font-size:11.5px;color:#9CA3AF;margin:0;">PDF, images, Word, Excel, ZIP — no size limit</p>
                            </div>
                        </div>

                        {{-- Staged files --}}
                        <template x-if="files.length">
                            <div style="margin-top:12px;display:flex;flex-direction:column;gap:7px;">
                                <template x-for="(f, i) in files" :key="i">
                                    <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:8px;">
                                        <div style="width:30px;height:30px;border-radius:7px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i :class="'fas '+fileIcon(f.type)" style="font-size:12px;color:#6366F1;"></i>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <div style="font-size:12.5px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="f.name"></div>
                                            <div style="font-size:11px;color:#9CA3AF;" x-text="formatSize(f.size)"></div>
                                        </div>
                                        <button type="button" @click.stop="removeFile(i)"
                                                style="width:22px;height:22px;border-radius:5px;background:#FEE2E2;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="fas fa-times" style="font-size:9px;color:#DC2626;"></i>
                                        </button>
                                    </div>
                                </template>

                                {{-- Comment --}}
                                <div>
                                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">
                                        <i class="fas fa-comment-lines" style="color:#6366F1;margin-right:4px;"></i>Comment
                                        <span style="font-weight:400;color:#9CA3AF;">(optional)</span>
                                    </label>
                                    <textarea name="comment" rows="2"
                                              placeholder="e.g. Q2 invoice, annual renewal receipt..."
                                              style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;"
                                              onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                                </div>

                                <button type="button" @click="submit()"
                                        style="width:100%;padding:9px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;">
                                    <i class="fas fa-cloud-arrow-up"></i>
                                    Upload <span x-text="files.length"></span> file<span x-show="files.length > 1">s</span>
                                </button>
                            </div>
                        </template>
                    </form>

                    {{-- Uploaded files list --}}
                    @if($subscription->attachments->isNotEmpty())
                    <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px;">
                        <div style="font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Uploaded Files</div>
                        @foreach($subscription->attachments as $att)
                        <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 14px;background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:10px;">
                            <div style="width:34px;height:34px;border-radius:9px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                                <i class="fas {{ $att->icon_class }}" style="font-size:14px;color:#6366F1;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $att->filename }}</div>
                                <div style="font-size:11px;color:#9CA3AF;margin-top:2px;">
                                    {{ $att->formatted_size }}
                                    &nbsp;·&nbsp;{{ $att->created_at->format('d M Y, H:i') }}
                                    @if($att->uploader) &nbsp;·&nbsp;{{ $att->uploader->name }} @endif
                                </div>
                                @if($att->comment)
                                <div style="font-size:12px;color:#374151;margin-top:5px;padding:5px 9px;background:#F3F4F6;border-radius:6px;line-height:1.4;">
                                    <i class="fas fa-comment-lines" style="font-size:10px;color:#9CA3AF;margin-right:4px;"></i>{{ $att->comment }}
                                </div>
                                @endif
                            </div>
                            <div style="display:flex;gap:5px;flex-shrink:0;">
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($att->path) }}" target="_blank"
                                   style="width:28px;height:28px;border-radius:7px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;color:#6366F1;text-decoration:none;"
                                   title="Open">
                                    <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                                </a>
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($att->path) }}" download="{{ $att->filename }}"
                                   style="width:28px;height:28px;border-radius:7px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;color:#6366F1;text-decoration:none;"
                                   title="Download">
                                    <i class="fas fa-download" style="font-size:10px;"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.subscriptions.attachments.delete', [$subscription, $att]) }}"
                                      onsubmit="return confirm('Delete {{ addslashes($att->filename) }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            style="width:28px;height:28px;border-radius:7px;background:#FEF2F2;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#DC2626;"
                                            title="Delete">
                                        <i class="fas fa-trash" style="font-size:10px;"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>{{-- end attachments --}}

        </div>{{-- end right column --}}
    </div>{{-- end main grid --}}

    {{-- ════════════════ MODALS ════════════════ --}}

    {{-- Assign User --}}
    <div x-show="assignModal" x-cloak style="position:fixed;inset:0;z-index:9999;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;overflow-y:auto;">
            <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);" @click="assignModal = false"></div>
            <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:420px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
                <h3 style="font-size:17px;font-weight:700;color:#111827;margin:0 0 6px;">Assign User</h3>
                <p style="font-size:13px;color:#9CA3AF;margin:0 0 20px;">Grant access to {{ $subscription->name }}</p>
                <form method="POST" action="{{ route('admin.subscriptions.assign-user', $subscription->id) }}">
                    @csrf
                    <select name="user_id" required
                            style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;background:#fff;margin-bottom:16px;box-sizing:border-box;">
                        <option value="">Select a user…</option>
                        @foreach($availableUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }} ({{ ucfirst($u->role) }})</option>
                        @endforeach
                    </select>
                    <div style="display:flex;gap:10px;">
                        <button type="submit"
                                style="flex:1;padding:10px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                            Assign
                        </button>
                        <button type="button" @click="assignModal = false"
                                style="padding:10px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Remove User --}}
    <div x-show="removeModal" x-cloak style="position:fixed;inset:0;z-index:9999;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;overflow-y:auto;">
            <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);" @click="removeModal = false"></div>
            <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:380px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.2);text-align:center;">
                <div style="width:52px;height:52px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                    <i class="fas fa-user-minus" style="color:#DC2626;font-size:18px;"></i>
                </div>
                <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">Remove User?</h3>
                <p style="font-size:13px;color:#6B7280;margin:0 0 20px;">Remove <strong x-text="removeUserName" style="color:#111827;"></strong> from this subscription? They will be notified.</p>
                <div style="display:flex;gap:10px;">
                    <button @click="removeModal = false"
                            style="flex:1;padding:10px;background:#F3F4F6;color:#374151;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                        Cancel
                    </button>
                    <form :action="'/admin/subscriptions/{{ $subscription->id }}/remove-user/'+removeUserId" method="POST" style="flex:1;">
                        @csrf @method('DELETE')
                        <button type="submit"
                                style="width:100%;padding:10px;background:#DC2626;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                            Remove
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Subscription --}}
    <div x-show="editModal" x-cloak style="position:fixed;inset:0;z-index:9999;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;overflow-y:auto;">
            <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);" @click="editModal = false"></div>
            <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:640px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);margin:auto;">

                <div style="padding:20px 24px;border-bottom:1.5px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;">
                    <div>
                        <h2 style="font-size:17px;font-weight:700;color:#111827;margin:0;">Edit Subscription</h2>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">{{ $subscription->name }}</p>
                    </div>
                    <button @click="editModal = false"
                            style="width:32px;height:32px;border-radius:8px;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.subscriptions.update', $subscription->id) }}"
                      enctype="multipart/form-data" style="padding:20px 24px 24px;">
                    @csrf @method('PUT')
                    @php
                        $catOpts    = \App\Models\Subscription::categoryOptions();
                        $cycleOpts  = ['monthly'=>'Monthly','annual'=>'Annual','quarterly'=>'Quarterly','one_time'=>'One-time'];
                        $typeOpts   = ['per_seat'=>'Per Seat','site_license'=>'Site License','shared'=>'Shared'];
                        $currencies = ['BHD','USD','EUR','GBP','SAR','AED','KWD'];
                    @endphp

                    {{-- Logo --}}
                    <div style="text-align:center;padding-bottom:20px;margin-bottom:20px;border-bottom:1.5px solid #F3F4F6;"
                         x-data="{
                             preview: '{{ $subscription->logo_url }}',
                             hasExisting: {{ $subscription->logo_url ? 'true' : 'false' }},
                             pick() { this.$refs.logoInput.click(); },
                             onPick(e) {
                                 const f = e.target.files[0]; if (!f) return;
                                 const r = new FileReader();
                                 r.onload = ev => this.preview = ev.target.result;
                                 r.readAsDataURL(f);
                             }
                         }">
                        <div style="position:relative;display:inline-block;">
                            <div style="width:88px;height:88px;border-radius:50%;background:linear-gradient(135deg,#818CF8,#4F46E5);padding:2.5px;box-shadow:0 0 0 4px rgba(99,102,241,.12),0 6px 20px rgba(99,102,241,.2);">
                                <div @click="pick()"
                                     style="width:100%;height:100%;border-radius:50%;overflow:hidden;cursor:pointer;background:#fff;position:relative;">
                                    <template x-if="preview">
                                        <img :src="preview" style="width:100%;height:100%;object-fit:contain;padding:8px;box-sizing:border-box;">
                                    </template>
                                    <template x-if="!preview">
                                        <div style="width:100%;height:100%;background:linear-gradient(135deg,#818CF8,#4F46E5);display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-layer-group" style="color:rgba(255,255,255,.85);font-size:26px;"></i>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div @click="pick()"
                                 style="position:absolute;bottom:2px;right:2px;width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#818CF8,#4F46E5);border:2.5px solid #fff;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(99,102,241,.5);">
                                <i class="fas fa-camera" style="font-size:10px;color:#fff;"></i>
                            </div>
                        </div>
                        <div style="margin-top:8px;font-size:12px;color:#9CA3AF;">Click to change logo</div>
                        <input type="file" x-ref="logoInput" name="logo" accept="image/*" style="display:none;" @change="onPick($event)">
                        @if($subscription->logo_url)
                        <div style="margin-top:6px;">
                            <label style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#DC2626;cursor:pointer;">
                                <input type="checkbox" name="remove_logo" value="1"
                                       @change="if($event.target.checked){preview='';hasExisting=false}"> Remove logo
                            </label>
                        </div>
                        @endif
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div style="grid-column:1/-1;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Name *</label>
                            <input type="text" name="name" value="{{ old('name', $subscription->name) }}" required
                                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Vendor</label>
                            <input type="text" name="vendor" value="{{ old('vendor', $subscription->vendor) }}"
                                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Category *</label>
                            <select name="category" style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                                @foreach($catOpts as $k=>$v)<option value="{{ $k }}" {{ old('category',$subscription->category)===$k?'selected':'' }}>{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">License Type *</label>
                            <select name="type" style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                                @foreach($typeOpts as $k=>$v)<option value="{{ $k }}" {{ old('type',$subscription->type)===$k?'selected':'' }}>{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Billing Cycle *</label>
                            <select name="billing_cycle" style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                                @foreach($cycleOpts as $k=>$v)<option value="{{ $k }}" {{ old('billing_cycle',$subscription->billing_cycle)===$k?'selected':'' }}>{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Cost *</label>
                            <div style="display:flex;gap:6px;">
                                <select name="currency" style="padding:9px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;">
                                    @foreach($currencies as $c)<option value="{{ $c }}" {{ old('currency',$subscription->currency)===$c?'selected':'' }}>{{ $c }}</option>@endforeach
                                </select>
                                <input type="number" name="cost" value="{{ old('cost', $subscription->cost) }}" step="0.001" min="0" required
                                       style="flex:1;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                                       onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                            </div>
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Max Seats</label>
                            <input type="number" name="max_seats" value="{{ old('max_seats', $subscription->max_seats) }}" min="1"
                                   placeholder="Unlimited if blank"
                                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Purchase Date</label>
                            <input type="date" name="purchase_date" value="{{ old('purchase_date', $subscription->purchase_date?->format('Y-m-d')) }}"
                                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Renewal Date</label>
                            <input type="date" name="renewal_date" value="{{ old('renewal_date', $subscription->renewal_date?->format('Y-m-d')) }}"
                                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;">
                        </div>
                        <div style="grid-column:1/-1;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Website</label>
                            <input type="url" name="website" value="{{ old('website', $subscription->website) }}"
                                   placeholder="https://..."
                                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Username / Email</label>
                            <input type="text" name="username" value="{{ old('username', $subscription->username) }}"
                                   placeholder="login@example.com" autocomplete="off"
                                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                        </div>
                        <div x-data="{show:false}">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                Password / License Key
                                @if($subscription->password)<span style="font-size:11px;font-weight:400;color:#9CA3AF;"> — leave blank to keep</span>@endif
                            </label>
                            <div style="position:relative;">
                                <input :type="show?'text':'password'" name="password"
                                       placeholder="{{ $subscription->password ? 'Leave blank to keep current' : 'Enter password or key' }}"
                                       autocomplete="new-password"
                                       style="width:100%;padding:9px 40px 9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                                       onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                                <button type="button" @click="show=!show"
                                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:#9CA3AF;">
                                    <i :class="show?'fas fa-eye-slash':'fas fa-eye'" style="font-size:13px;"></i>
                                </button>
                            </div>
                        </div>
                        <div style="grid-column:1/-1;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;">Renewal Reminders</label>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                @foreach([1,7,14,30,60] as $d)
                                @php $checked = in_array($d, old('notify_days', $subscription->notify_days)); @endphp
                                <label style="display:inline-flex;align-items:center;gap:5px;font-size:13px;color:#374151;cursor:pointer;padding:5px 10px;background:{{ $checked?'#EEF2FF':'#F3F4F6' }};border:1.5px solid {{ $checked?'#C7D2FE':'#E5E7EB' }};border-radius:8px;">
                                    <input type="checkbox" name="notify_days[]" value="{{ $d }}" {{ $checked?'checked':'' }}
                                           onchange="this.closest('label').style.background=this.checked?'#EEF2FF':'#F3F4F6';this.closest('label').style.borderColor=this.checked?'#C7D2FE':'#E5E7EB';"
                                           style="accent-color:#4F46E5;"> {{ $d }}d before
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <div style="grid-column:1/-1;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Notes</label>
                            <textarea name="notes" rows="3"
                                      style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;"
                                      onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">{{ old('notes', $subscription->notes) }}</textarea>
                        </div>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:20px;">
                        <button type="submit"
                                style="flex:1;padding:11px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                            <i class="fas fa-save" style="margin-right:6px;"></i> Save Changes
                        </button>
                        <button type="button" @click="editModal = false"
                                style="padding:11px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete --}}
    <div x-show="deleteModal" x-cloak style="position:fixed;inset:0;z-index:9999;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;overflow-y:auto;">
            <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);" @click="deleteModal = false"></div>
            <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:380px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.2);text-align:center;">
                <div style="width:52px;height:52px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                    <i class="fas fa-trash" style="color:#DC2626;font-size:18px;"></i>
                </div>
                <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">Delete Subscription?</h3>
                <p style="font-size:13px;color:#6B7280;margin:0 0 20px;">This will permanently remove <strong style="color:#111827;">{{ $subscription->name }}</strong> and all associated data.</p>
                <div style="display:flex;gap:10px;">
                    <button @click="deleteModal = false"
                            style="flex:1;padding:10px;background:#F3F4F6;color:#374151;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                        Cancel
                    </button>
                    <form action="{{ route('admin.subscriptions.destroy', $subscription->id) }}" method="POST" style="flex:1;">
                        @csrf @method('DELETE')
                        <button type="submit"
                                style="width:100%;padding:10px;background:#DC2626;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

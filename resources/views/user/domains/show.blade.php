@extends('layouts.app')
@section('title', $domain->domain)

@section('content')
<style>
.info-card  { background:#fff; border:1.5px solid #E5E7EB; border-radius:14px; padding:20px; }
.info-label { font-size:11px; font-weight:600; color:#9CA3AF; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
.info-value { font-size:15px; font-weight:600; color:#111827; }
.dom-status-active        { background:#ECFDF5; color:#16A34A; }
.dom-status-expiring_soon { background:#FEF3C7; color:#D97706; }
.dom-status-expired       { background:#FEE2E2; color:#DC2626; }
@media (max-width:900px) { .show-grid { grid-template-columns:1fr !important; } }
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
    $billingCycleLabel = $billingCycles[$domain->billing_cycle] ?? $domain->billing_cycle;
@endphp

<div>

    {{-- Back --}}
    <a href="{{ route('user.domains.index') }}"
       style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#6B7280;text-decoration:none;margin-bottom:20px;"
       onmouseover="this.style.color='#374151'" onmouseout="this.style.color='#6B7280'">
        <i class="fas fa-arrow-left" style="font-size:11px;"></i> Back to My Domains
    </a>

    {{-- Flash --}}
    @if(session('success'))
    <div style="background:#ECFDF5;border:1px solid #A7F3D0;color:#16A34A;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Header --}}
    <div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:16px;padding:24px;margin-bottom:20px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);border-radius:16px;display:flex;align-items:center;justify-content:center;border:1.5px solid #C7D2FE;flex-shrink:0;">
                    <i class="fas fa-globe" style="font-size:22px;color:#6366F1;"></i>
                </div>
                <div>
                    <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 6px;">{{ $domain->domain }}</h1>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;"
                              class="dom-status-{{ $status }}">
                            @if($status==='active') <i class="fas fa-circle" style="font-size:7px;"></i> Active
                            @elseif($status==='expiring_soon') <i class="fas fa-clock" style="font-size:9px;"></i> Expiring Soon
                            @else <i class="fas fa-triangle-exclamation" style="font-size:9px;"></i> Expired
                            @endif
                        </span>
                        @if($domain->customer)
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#EEF2FF;color:#4F46E5;">
                            <i class="fas fa-building" style="font-size:9px;"></i>
                            {{ $domain->customer->name }}
                        </span>
                        @endif
                        @if($domain->registrar)
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#F3F4F6;color:#374151;">
                            <i class="fas fa-server" style="font-size:9px;"></i>
                            {{ $domain->registrar }}
                        </span>
                        @endif
                        @if($domain->auto_renew)
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#ECFDF5;color:#16A34A;">
                            <i class="fas fa-rotate" style="font-size:9px;"></i> Auto-renew ON
                        </span>
                        @else
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#FEF3C7;color:#D97706;">
                            <i class="fas fa-hand" style="font-size:9px;"></i> Manual Renewal
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Expiry Banner --}}
    @if($domain->expires_at)
    @php
        $bannerBg    = $status==='expired'      ? '#FEE2E2' : ($status==='expiring_soon' ? '#FFF7ED' : '#F0FDF4');
        $bannerBrd   = $status==='expired'      ? '#FCA5A5' : ($status==='expiring_soon' ? '#FED7AA' : '#BBF7D0');
        $bannerColor = $status==='expired'      ? '#DC2626' : ($status==='expiring_soon' ? '#EA580C' : '#16A34A');
        $bannerIcon  = $status==='expired'      ? 'fa-triangle-exclamation' : ($status==='expiring_soon' ? 'fa-clock' : 'fa-circle-check');
        $bannerMsg   = $status==='expired'
            ? 'This domain expired ' . abs($days) . ' day' . (abs($days)!==1?'s':'') . ' ago — contact your admin to renew'
            : ($days === 0 ? 'This domain expires TODAY' : ($days === 1 ? 'This domain expires tomorrow' : 'This domain expires in ' . $days . ' day' . ($days!==1?'s':'')));
    @endphp
    <div style="background:{{ $bannerBg }};border:1.5px solid {{ $bannerBrd }};border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px;">
        <i class="fas {{ $bannerIcon }}" style="color:{{ $bannerColor }};font-size:18px;flex-shrink:0;"></i>
        <div>
            <span style="font-size:14px;font-weight:700;color:{{ $bannerColor }};">{{ $bannerMsg }}</span>
            <span style="font-size:13px;color:{{ $bannerColor }};opacity:.8;margin-left:8px;">{{ $domain->expires_at->format('d M Y') }}</span>
        </div>
    </div>
    @endif

    {{-- Stats Row --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
        <div class="info-card" style="text-align:center;">
            <div style="font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;margin-bottom:4px;">Annual Cost</div>
            @if($domain->cost > 0)
            <div style="font-size:26px;font-weight:800;color:#4F46E5;">{{ number_format($domain->annual_cost, 3) }}</div>
            <div style="font-size:12px;color:#9CA3AF;">{{ $domain->currency }} / year</div>
            @if($domain->billing_cycle !== 'annual')
            <div style="font-size:11px;color:#9CA3AF;margin-top:2px;">{{ number_format($domain->cost, 3) }} {{ $domain->currency }} / {{ $billingCycleLabel }}</div>
            @endif
            @else
            <div style="font-size:18px;font-weight:700;color:#9CA3AF;">—</div>
            @endif
        </div>
        <div class="info-card" style="text-align:center;">
            <div style="font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;margin-bottom:4px;">Days Until Expiry</div>
            @if($days !== null)
            <div style="font-size:26px;font-weight:800;color:{{ $status==='expired' ? '#DC2626' : ($status==='expiring_soon' ? '#D97706' : '#16A34A') }};">
                {{ $days < 0 ? abs($days) : $days }}
            </div>
            <div style="font-size:12px;color:#9CA3AF;">{{ $days < 0 ? 'days overdue' : 'days remaining' }}</div>
            @else
            <div style="font-size:18px;font-weight:700;color:#9CA3AF;">—</div>
            <div style="font-size:12px;color:#9CA3AF;">No expiry date set</div>
            @endif
        </div>
        <div class="info-card" style="text-align:center;">
            <div style="font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;margin-bottom:4px;">Renewal Type</div>
            @if($domain->auto_renew)
            <div style="font-size:20px;font-weight:800;color:#16A34A;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;">
                <i class="fas fa-rotate" style="font-size:18px;"></i> Auto
            </div>
            <div style="font-size:12px;color:#9CA3AF;">Renews automatically</div>
            @else
            <div style="font-size:20px;font-weight:800;color:#D97706;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;">
                <i class="fas fa-hand" style="font-size:16px;"></i> Manual
            </div>
            <div style="font-size:12px;color:#9CA3AF;">Requires manual action</div>
            @endif
        </div>
    </div>

    {{-- Main Layout --}}
    <div class="show-grid" style="display:grid;grid-template-columns:1.1fr .9fr;gap:20px;">

        {{-- Left Column --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Domain Details --}}
            <div class="info-card">
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-circle-info" style="color:#6366F1;font-size:13px;"></i> Domain Details
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <div class="info-label">Customer</div>
                        @if($domain->customer)
                        <div class="info-value">{{ $domain->customer->name }}</div>
                        @if($domain->customer->company)
                        <div style="font-size:12px;color:#9CA3AF;">{{ $domain->customer->company }}</div>
                        @endif
                        @else
                        <div class="info-value" style="color:#9CA3AF;">Not assigned</div>
                        @endif
                    </div>
                    <div>
                        <div class="info-label">Registrar</div>
                        <div class="info-value">{{ $domain->registrar ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="info-label">Registered On</div>
                        <div class="info-value">{{ $domain->registered_at ? $domain->registered_at->format('d M Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="info-label">Expires On</div>
                        <div class="info-value" style="color:{{ $status==='expired' ? '#DC2626' : ($status==='expiring_soon' ? '#D97706' : '#111827') }};">
                            {{ $domain->expires_at ? $domain->expires_at->format('d M Y') : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="info-label">Billing Cycle</div>
                        <div class="info-value">{{ $billingCycleLabel }}</div>
                    </div>
                    <div>
                        <div class="info-label">Hosting Provider</div>
                        <div class="info-value">{{ $domain->hosting_provider ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="info-label">Bill To</div>
                        <div class="info-value">{{ $domain->billing_to ?: '—' }}</div>
                    </div>
                    @php $coResponsible = $domain->responsibleUsers->where('id', '!=', auth()->id()); @endphp
                    @if($coResponsible->isNotEmpty())
                    <div>
                        <div class="info-label">Also Responsible</div>
                        <div class="info-value">{{ $coResponsible->pluck('name')->implode(', ') }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Nameservers --}}
            @if($domain->nameservers && count($domain->nameservers))
            <div class="info-card">
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-network-wired" style="color:#6366F1;font-size:13px;"></i> Nameservers
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($domain->nameservers as $ns)
                    <div style="display:flex;align-items:center;gap:10px;padding:9px 14px;background:#F9FAFB;border-radius:8px;border:1px solid #E5E7EB;">
                        <i class="fas fa-server" style="color:#9CA3AF;font-size:12px;flex-shrink:0;"></i>
                        <code style="font-size:13px;font-weight:600;color:#374151;font-family:monospace;">{{ $ns }}</code>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Notes --}}
            @if($domain->notes)
            <div class="info-card">
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-sticky-note" style="color:#6366F1;font-size:13px;"></i> Notes
                </div>
                <div style="font-size:14px;color:#374151;line-height:1.7;white-space:pre-line;">{{ $domain->notes }}</div>
            </div>
            @endif
        </div>

        {{-- Right Column --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Cost Card --}}
            <div class="info-card" style="background:linear-gradient(135deg,#EEF2FF,#F5F3FF);border-color:#C7D2FE;">
                <div style="font-size:13px;font-weight:700;color:#4F46E5;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-coins" style="font-size:13px;"></i> Billing Info
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="background:#fff;border-radius:10px;padding:12px;border:1px solid #E0E7FF;">
                        <div style="font-size:11px;color:#6B7280;font-weight:600;text-transform:uppercase;margin-bottom:4px;">Total Cost</div>
                        <div style="font-size:20px;font-weight:800;color:#4F46E5;">{{ number_format($domain->cost, 3) }}</div>
                        <div style="font-size:11px;color:#6B7280;">{{ $domain->currency }} / {{ $billingCycleLabel }}</div>
                    </div>
                    <div style="background:#fff;border-radius:10px;padding:12px;border:1px solid #E0E7FF;">
                        <div style="font-size:11px;color:#6B7280;font-weight:600;text-transform:uppercase;margin-bottom:4px;">Annual Equivalent</div>
                        <div style="font-size:20px;font-weight:800;color:#4F46E5;">{{ number_format($domain->annual_cost, 3) }}</div>
                        <div style="font-size:11px;color:#6B7280;">{{ $domain->currency }} / year</div>
                    </div>
                </div>
                @if($domain->billing_to)
                <div style="margin-top:12px;padding:10px 14px;background:#fff;border-radius:10px;border:1px solid #E0E7FF;">
                    <div style="font-size:11px;color:#6B7280;font-weight:600;text-transform:uppercase;margin-bottom:3px;">Invoice / Bill To</div>
                    <div style="font-size:14px;font-weight:700;color:#374151;">{{ $domain->billing_to }}</div>
                </div>
                @endif
            </div>

            {{-- Credentials --}}
            @if($domain->login_url || $domain->username || $domain->password)
            <div class="info-card">
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-key" style="color:#6366F1;font-size:13px;"></i> Registrar Credentials
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    @if($domain->login_url)
                    <div>
                        <div class="info-label">Login URL</div>
                        <a href="{{ $domain->login_url }}" target="_blank" rel="noopener"
                           style="font-size:13px;font-weight:600;color:#4F46E5;text-decoration:none;word-break:break-all;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            <i class="fas fa-external-link" style="font-size:10px;margin-right:4px;"></i>{{ $domain->login_url }}
                        </a>
                    </div>
                    @endif
                    @if($domain->username)
                    <div>
                        <div class="info-label">Username</div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <code style="font-size:13px;font-weight:600;color:#374151;font-family:monospace;background:#F9FAFB;padding:4px 10px;border-radius:6px;border:1px solid #E5E7EB;">{{ $domain->username }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ addslashes($domain->username) }}').then(() => this.innerHTML = '<i class=\'fas fa-check\' style=\'color:#16A34A;\'></i>')"
                                    style="padding:5px 10px;background:#F3F4F6;border:none;border-radius:6px;cursor:pointer;font-size:11px;color:#6B7280;"
                                    title="Copy">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    @endif
                    @if($domain->password)
                    <div>
                        <div class="info-label">Password</div>
                        <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                            <code id="dom-pwd-display" style="font-size:13px;font-weight:600;color:#374151;font-family:monospace;background:#F9FAFB;padding:4px 10px;border-radius:6px;border:1px solid #E5E7EB;flex:1;letter-spacing:.15em;word-break:break-all;">••••••••••</code>
                            <button onclick="openDomRevealModal()" title="Reveal password"
                                    style="width:28px;height:28px;background:#FEF3C7;border:none;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#D97706;flex-shrink:0;">
                                <i class="fas fa-lock" style="font-size:11px;"></i>
                            </button>
                            <button id="dom-pwd-copy" onclick="domCopyPwd(this)" title="Copy"
                                    style="display:none;width:28px;height:28px;background:#F3F4F6;border:none;border-radius:6px;cursor:pointer;align-items:center;justify-content:center;color:#6B7280;flex-shrink:0;">
                                <i class="fas fa-copy" style="font-size:11px;"></i>
                            </button>
                        </div>
                        <div style="font-size:11px;color:#9CA3AF;margin-top:4px;display:flex;align-items:center;gap:4px;">
                            <i class="fas fa-shield-halved" style="font-size:9px;color:#D97706;"></i>
                            Account password required to reveal &middot; Access is logged
                        </div>
                    </div>

                    {{-- Reveal Password Modal --}}
                    <div id="dom-reveal-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
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
                            <input type="password" id="dom-reveal-input" placeholder="Your account password"
                                   style="width:100%;padding:10px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;margin-bottom:6px;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"
                                   onkeydown="if(event.key==='Enter')domSubmitReveal()">
                            <div id="dom-reveal-error" style="display:none;font-size:12px;color:#DC2626;margin-bottom:8px;"></div>
                            <div style="display:flex;gap:8px;margin-top:14px;">
                                <button onclick="closeDomRevealModal()" style="flex:1;padding:9px;border:1.5px solid #E5E7EB;border-radius:8px;background:#fff;font-size:13px;cursor:pointer;color:#374151;">Cancel</button>
                                <button onclick="domSubmitReveal()" id="dom-reveal-btn" style="flex:1;padding:9px;border:none;border-radius:8px;background:#4F46E5;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
                                    <i class="fas fa-unlock" style="font-size:11px;margin-right:4px;"></i> Reveal
                                </button>
                            </div>
                        </div>
                    </div>
                    <script>
                    (function(){
                        var _domTimer = null;
                        window.openDomRevealModal = function() {
                            document.getElementById('dom-reveal-input').value = '';
                            document.getElementById('dom-reveal-error').style.display = 'none';
                            document.getElementById('dom-reveal-modal').style.display = 'flex';
                            setTimeout(function(){ document.getElementById('dom-reveal-input').focus(); }, 50);
                        };
                        window.closeDomRevealModal = function() {
                            document.getElementById('dom-reveal-modal').style.display = 'none';
                        };
                        window.domSubmitReveal = function() {
                            var pwd = document.getElementById('dom-reveal-input').value;
                            var btn = document.getElementById('dom-reveal-btn');
                            var err = document.getElementById('dom-reveal-error');
                            if (!pwd) { err.textContent = 'Please enter your password.'; err.style.display='block'; return; }
                            btn.disabled = true;
                            btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:11px;margin-right:4px;"></i> Checking...';
                            fetch('{{ route('user.domains.reveal-password', $domain) }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ password: pwd })
                            }).then(function(r){ return r.json(); }).then(function(data){
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fas fa-unlock" style="font-size:11px;margin-right:4px;"></i> Reveal';
                                if (data.error) { err.textContent = data.error; err.style.display='block'; return; }
                                closeDomRevealModal();
                                var display = document.getElementById('dom-pwd-display');
                                var copyBtn = document.getElementById('dom-pwd-copy');
                                display.textContent = data.secret;
                                display.style.letterSpacing = 'normal';
                                copyBtn.style.display = 'flex';
                                copyBtn._secret = data.secret;
                                if (_domTimer) clearTimeout(_domTimer);
                                _domTimer = setTimeout(function(){
                                    display.textContent = '••••••••••';
                                    display.style.letterSpacing = '.15em';
                                    copyBtn.style.display = 'none';
                                }, 30000);
                            }).catch(function(){
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fas fa-unlock" style="font-size:11px;margin-right:4px;"></i> Reveal';
                                err.textContent = 'Request failed. Try again.'; err.style.display='block';
                            });
                        };
                        window.domCopyPwd = function(btn) {
                            navigator.clipboard.writeText(btn._secret).then(function(){
                                var icon = btn.querySelector('i');
                                icon.className = 'fas fa-check'; icon.style.color = '#16A34A';
                                setTimeout(function(){ icon.className = 'fas fa-copy'; icon.style.color = ''; }, 1500);
                            });
                        };
                        document.getElementById('dom-reveal-modal').addEventListener('click', function(e){
                            if (e.target === this) closeDomRevealModal();
                        });
                    })();
                    </script>
                    @endif
                </div>
            </div>
            @endif

            {{-- Quick Actions --}}
            @if($domain->login_url)
            <div class="info-card">
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-bolt" style="color:#6366F1;font-size:13px;"></i> Quick Actions
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <a href="{{ $domain->login_url }}" target="_blank" rel="noopener"
                       style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:10px;text-decoration:none;color:#374151;font-size:13px;font-weight:600;"
                       onmouseover="this.style.background='#EEF2FF';this.style.borderColor='#C7D2FE';this.style.color='#4F46E5'" onmouseout="this.style.background='#F9FAFB';this.style.borderColor='#E5E7EB';this.style.color='#374151'">
                        <i class="fas fa-arrow-up-right-from-square" style="font-size:12px;"></i>
                        Open Registrar Panel
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Attachments --}}
    <div class="info-card" style="margin-top:20px;" x-data="domainAttach()">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:700;color:#374151;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-paperclip" style="color:#6366F1;font-size:13px;"></i>
                Billing Attachments
                @if($domain->attachments->isNotEmpty())
                <span style="background:#EEF2FF;color:#4F46E5;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;">{{ $domain->attachments->count() }}</span>
                @endif
            </div>
        </div>

        {{-- Existing files --}}
        @if($domain->attachments->isNotEmpty())
        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
            @foreach($domain->attachments as $att)
            <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;">
                <div style="width:36px;height:36px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas {{ $att->icon_class }}" style="color:#6366F1;font-size:15px;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $att->original_name }}</div>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:1px;">
                        {{ $att->formatted_size }} &bull; {{ $att->created_at->format('d M Y') }}
                        @if($att->uploader) &bull; {{ $att->uploader->name }} @endif
                    </div>
                </div>
                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($att->path) }}" target="_blank" rel="noopener noreferrer"
                   style="padding:6px 12px;background:#F3F4F6;color:#374151;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;display:flex;align-items:center;gap:5px;"
                   onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                    <i class="fas fa-eye" style="font-size:10px;"></i> View
                </a>
                <a href="{{ route('user.domains.attachments.download', [$domain, $att]) }}"
                   style="padding:6px 12px;background:#EEF2FF;color:#4F46E5;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;display:flex;align-items:center;gap:5px;"
                   onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='#EEF2FF'">
                    <i class="fas fa-download" style="font-size:10px;"></i> Download
                </a>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Upload form --}}
        <form method="POST" action="{{ route('user.domains.attachments.store', $domain) }}" enctype="multipart/form-data" id="attForm">
            @csrf
            <input type="file" id="attFileInput" name="files[]" multiple style="display:none;" @change="onFiles($event.target.files)">
            <div class="dz-zone"
                 :class="dragover ? 'is-dragging' : ''"
                 @dragover.prevent="dragover=true"
                 @dragleave.prevent="dragover=false"
                 @drop.prevent="onDrop($event);dragover=false"
                 @click="document.getElementById('attFileInput').click()">

                <div class="dz-particles" aria-hidden="true">
                    <span></span><span></span><span></span><span></span>
                </div>

                <div style="pointer-events:none;position:relative;z-index:1;">
                    <div class="dz-icon-wrap">
                        <div class="dz-ring"></div>
                        <i class="fas fa-cloud-arrow-up"></i>
                    </div>
                    <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 4px;transition:color .2s;"
                       :style="dragover ? 'color:#4F46E5;' : ''">
                        <span x-show="!dragover">Drop files here or click to browse</span>
                        <span x-show="dragover" x-cloak>Release to upload</span>
                    </p>
                    <p style="font-size:11.5px;color:#9CA3AF;margin:0;">PDF, images, Word, Excel — max 20 MB each</p>
                </div>
            </div>

            {{-- Staged chips --}}
            <div x-show="staged.length" x-cloak style="margin-top:12px;flex-wrap:wrap;gap:8px;" :style="staged.length ? 'display:flex' : 'display:none'">
                <template x-for="(f,i) in staged" :key="i">
                    <div style="display:flex;align-items:center;gap:7px;padding:5px 10px 5px 8px;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:20px;">
                        <i class="fas fa-file" style="color:#6366F1;font-size:11px;"></i>
                        <span style="font-size:12px;font-weight:600;color:#374151;" x-text="f.name"></span>
                        <span style="font-size:10px;color:#9CA3AF;" x-text="fmtSize(f.size)"></span>
                        <button type="button" @click="remove(i)" style="width:16px;height:16px;border-radius:50%;background:#C7D2FE;border:none;cursor:pointer;font-size:10px;color:#4F46E5;display:flex;align-items:center;justify-content:center;padding:0;line-height:1;">×</button>
                    </div>
                </template>
            </div>

            <div x-show="staged.length" x-cloak style="margin-top:12px;justify-content:flex-end;" :style="staged.length ? 'display:flex' : 'display:none'">
                <button type="submit" style="padding:9px 22px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px;">
                    <i class="fas fa-upload" style="font-size:12px;"></i>
                    Upload <span x-text="staged.length + ' file' + (staged.length>1?'s':'')"></span>
                </button>
            </div>
        </form>
    </div>

</div>
<script>
function domainAttach() {
    return {
        staged: [],
        dragover: false,
        onFiles(files) {
            Array.from(files).forEach(f => this.staged.push(f));
            this.syncInput();
        },
        onDrop(e) {
            this.onFiles(e.dataTransfer.files);
        },
        remove(i) {
            this.staged.splice(i, 1);
            this.syncInput();
        },
        syncInput() {
            const dt = new DataTransfer();
            this.staged.forEach(f => dt.items.add(f));
            document.getElementById('attFileInput').files = dt.files;
        },
        fmtSize(b) {
            if (b >= 1048576) return (b/1048576).toFixed(1)+' MB';
            if (b >= 1024)    return (b/1024).toFixed(1)+' KB';
            return b+' B';
        }
    };
}
</script>
@endsection

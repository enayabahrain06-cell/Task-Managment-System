@extends('layouts.app')
@section('title', 'My Domains')

@section('content')
<style>
.dom-header { background:linear-gradient(135deg,#4F46E5 0%,#6366F1 50%,#818CF8 100%); border-radius:20px; padding:28px 32px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; gap:24px; flex-wrap:wrap; box-shadow:0 8px 32px rgba(79,70,229,.25); }
.dom-stat-chip { display:flex; align-items:center; gap:10px; padding:11px 18px; background:#fff; border:1.5px solid #E5E7EB; border-radius:12px; flex:1; min-width:110px; }
.dom-stat { background:#fff; border-radius:12px; border:1px solid #F0F0F0; box-shadow:0 1px 4px rgba(0,0,0,.04); padding:16px 20px; display:flex; align-items:center; gap:14px; }
.dom-stat-icon { width:40px; height:40px; border-radius:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:16px; }
@media (max-width:900px) { .dom-stats-grid { grid-template-columns:repeat(2,1fr) !important; } }
@media (max-width:500px)  { .dom-stats-grid { gap:8px !important; } }
.dom-search { border:1.5px solid #E5E7EB; border-radius:10px; padding:8px 14px 8px 36px; font-size:13px; color:#111827; outline:none; width:220px; transition:border-color .15s; background:#fff; }
.dom-search:focus { border-color:#4F46E5; }
.dom-table { width:100%; border-collapse:separate; border-spacing:0; min-width:700px; }
.dom-table th { background:#F9FAFB; font-size:11px; font-weight:700; color:#6B7280; text-transform:uppercase; letter-spacing:.05em; padding:10px 16px; border-bottom:1.5px solid #E5E7EB; text-align:left; white-space:nowrap; }
.dom-table th:first-child { border-radius:10px 0 0 0; }
.dom-table th:last-child  { border-radius:0 10px 0 0; }
.dom-table td { padding:13px 16px; border-bottom:1px solid #F3F4F6; font-size:13px; color:#374151; vertical-align:middle; background:#fff; transition:background .12s; }
.dom-table tr:last-child td { border-bottom:none; }
.dom-table tr:last-child td:first-child { border-radius:0 0 0 10px; }
.dom-table tr:last-child td:last-child  { border-radius:0 0 10px 0; }
.dom-table tbody tr { cursor:pointer; }
.dom-table tbody tr:hover td { background:#F5F3FF; }
.dom-table tbody tr.row-expired td { background:#FFF8F8; }
.dom-table tbody tr.row-expired:hover td { background:#FEF2F2; }
.dom-table tbody tr.row-expiring td { background:#FFFDF0; }
.dom-table tbody tr.row-expiring:hover td { background:#FFFBEB; }
.status-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.badge-active   { background:#D1FAE5; color:#065F46; }
.badge-expiring { background:#FEF3C7; color:#92400E; }
.badge-expired  { background:#FEE2E2; color:#991B1B; }
.badge-none     { background:#F3F4F6; color:#6B7280; }
/* Detail modal */
.dom-detail-overlay { position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px; }
.dom-detail-panel { background:#fff;border-radius:20px;width:560px;max-width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.18);display:flex;flex-direction:column; }
.dom-detail-header { background:linear-gradient(135deg,#4F46E5 0%,#6366F1 100%);border-radius:20px 20px 0 0;padding:22px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0; }
.dom-detail-body { padding:24px;overflow-y:auto; }
.dom-detail-row { display:flex;gap:8px;padding:10px 0;border-bottom:1px solid #F3F4F6;align-items:flex-start; }
.dom-detail-row:last-child { border-bottom:none; }
.dom-detail-label { font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;width:130px;flex-shrink:0;padding-top:1px; }
.dom-detail-val { font-size:13px;color:#111827;font-weight:500;flex:1;word-break:break-word; }
.dom-expiring-row:hover { background:#FFFBEB; }

/* ── Mobile-only premium pass (≤768px) — desktop styles above are untouched ── */
@media (max-width: 768px) {
    .dom-header { padding:20px; border-radius:var(--mob-r-lg,20px); flex-direction:column; align-items:flex-start; gap:16px; }
    .dom-header > div:last-child { width:100%; }
    .dom-header > div:last-child button { width:100%; justify-content:center; padding:12px 18px; }

    .dom-stats-grid { grid-template-columns:repeat(2,1fr) !important; gap:10px !important; }
    .dom-stat { border-radius:18px; box-shadow:0 1px 2px rgba(16,24,40,.06); border:1px solid rgba(0,0,0,.05); padding:16px; }
    .dom-stat-icon { width:36px !important; height:36px !important; border-radius:12px !important; }
    .dom-stat-icon i { font-size:14px !important; }
    .dom-stat-label { font-size:11px !important; font-weight:600 !important; text-transform:uppercase !important; letter-spacing:.05em !important; color:#6B7280 !important; }
    .dom-stat-value { font-size:20px !important; line-height:1.2 !important; }

    /* Empty state */
    .dom-empty-card { border-radius:18px !important; padding:32px 20px !important; }
    .dom-empty-icon-box { width:40px !important; height:40px !important; border-radius:12px !important; }
    .dom-empty-icon { font-size:16px !important; color:#D1D5DB !important; }
    .dom-empty-title { font-size:14px !important; }

    /* Toolbar: full-width, stacked search + filter */
    .dom-toolbar-mobile { flex-direction:column; align-items:stretch !important; border-radius:var(--mob-r-md,16px) !important; }
    .dom-toolbar-mobile > div, .dom-toolbar-mobile select, .dom-toolbar-mobile #dom-count { width:100%; box-sizing:border-box; }
    .dom-toolbar-mobile select { padding:11px 14px; }
    .dom-search { width:100%; min-height:44px; box-sizing:border-box; }

    /* Table → stacked cards, no horizontal scroll */
    .dom-table { min-width:0 !important; }
    .mob-table-cards { overflow-x:visible !important; }
    .mob-table-cards tr.row-expired  { border-left:4px solid #DC2626; }
    .mob-table-cards tr.row-expiring { border-left:4px solid #D97706; }
    .mob-table-cards td { font-size:13px; }
    .mob-table-cards td[data-label="Domain"] { flex-direction:row; }

    /* Add-domain modal */
    #userAddDomainModal > div > div { padding:20px; border-radius:var(--mob-r-lg,20px); }
}
</style>

<div style="padding:0 0 32px;">

    {{-- Flash --}}
    @if(session('success'))
    <div style="background:#ECFDF5;border:1px solid #A7F3D0;color:#16A34A;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Header --}}
    <div class="dom-header">
        <div>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
                <div style="width:44px;height:44px;background:rgba(255,255,255,.18);border-radius:12px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);border:1.5px solid rgba(255,255,255,.3);">
                    <i class="fas fa-globe" style="font-size:20px;color:#fff;"></i>
                </div>
                <div>
                    <h1 style="font-size:22px;font-weight:800;color:#fff;margin:0;">My Domains</h1>
                    <p style="font-size:13px;color:rgba(255,255,255,.75);margin:0;">Domains you are responsible for</p>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <button onclick="document.getElementById('userAddDomainModal').style.display='block'"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;background:#fff;color:#4F46E5;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.1);">
                <i class="fas fa-plus" style="font-size:11px;"></i> Add Domain
            </button>
        </div>
    </div>

    {{-- Expiring Alert --}}
    @if($expiringThisWeek->count())
    <div style="background:#FFF7ED;border:1.5px solid #FED7AA;border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px;">
        <i class="fas fa-triangle-exclamation" style="color:#EA580C;margin-top:2px;"></i>
        <div>
            <div style="font-size:13px;font-weight:700;color:#C2410C;margin-bottom:4px;">
                ⚡ {{ $expiringThisWeek->count() }} domain{{ $expiringThisWeek->count()>1?'s':'' }} expiring within 7 days
            </div>
            <div style="font-size:12px;color:#9A3412;display:flex;flex-wrap:wrap;gap:6px;">
                @foreach($expiringThisWeek as $d)
                <a href="{{ route('user.domains.show', $d->id) }}"
                   style="background:#FEE7D0;padding:3px 10px;border-radius:20px;color:#C2410C;font-weight:600;text-decoration:none;font-size:11.5px;">
                    {{ $d->domain }}
                    @if($d->days_until_expiry === 0) — Today!
                    @elseif($d->days_until_expiry === 1) — Tomorrow
                    @else — {{ $d->days_until_expiry }}d
                    @endif
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Stats --}}
    <div class="dom-stats-grid" style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:24px;">
        <div class="dom-stat">
            <div class="dom-stat-icon" style="background:#EEF2FF;">
                <i class="fas fa-globe" style="color:#4F46E5;"></i>
            </div>
            <div>
                <p class="dom-stat-value" style="font-size:22px;font-weight:800;color:#111827;margin:0;">{{ $totalCount }}</p>
                <p class="dom-stat-label" style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Total Domains</p>
            </div>
        </div>
        <div class="dom-stat">
            <div class="dom-stat-icon" style="background:#D1FAE5;">
                <i class="fas fa-circle-check" style="color:#059669;"></i>
            </div>
            <div>
                <p class="dom-stat-value" style="font-size:22px;font-weight:800;color:#059669;margin:0;">{{ $activeCount }}</p>
                <p class="dom-stat-label" style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Active</p>
            </div>
        </div>
        <div class="dom-stat">
            <div class="dom-stat-icon" style="background:#FEF3C7;">
                <i class="fas fa-clock" style="color:#D97706;"></i>
            </div>
            <div>
                <p class="dom-stat-value" style="font-size:22px;font-weight:800;color:#D97706;margin:0;">{{ $expiringSoonCount }}</p>
                <p class="dom-stat-label" style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Expiring Soon <strong style="color:#EA580C;">· {{ $weekCount }} in 7d</strong></p>
            </div>
        </div>
        <div class="dom-stat">
            <div class="dom-stat-icon" style="background:#FEE2E2;">
                <i class="fas fa-circle-xmark" style="color:#DC2626;"></i>
            </div>
            <div>
                <p class="dom-stat-value" style="font-size:22px;font-weight:800;color:#DC2626;margin:0;">{{ $expiredCount }}</p>
                <p class="dom-stat-label" style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Expired</p>
            </div>
        </div>
        <div class="dom-stat">
            <div class="dom-stat-icon" style="background:#EFF6FF;">
                <i class="fas fa-coins" style="color:#2563EB;"></i>
            </div>
            <div>
                @forelse($annualTotalsByCurrency as $currency => $amount)
                <p class="dom-stat-value" style="font-size:{{ $annualTotalsByCurrency->count() > 1 ? '16px' : '22px' }};font-weight:800;color:#2563EB;margin:0;">{{ format_money($amount, $currency) }}</p>
                @empty
                <p class="dom-stat-value" style="font-size:22px;font-weight:800;color:#2563EB;margin:0;">BHD 0.000</p>
                @endforelse
                <p class="dom-stat-label" style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Annual Spend</p>
            </div>
        </div>
    </div>

    @if($domains->isEmpty())
    <div class="dom-empty-card" style="background:#fff;border:1.5px solid #E5E7EB;border-radius:16px;padding:60px 20px;text-align:center;">
        <div class="dom-empty-icon-box" style="width:72px;height:72px;background:#EEF2FF;border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-globe dom-empty-icon" style="font-size:28px;color:#4F46E5;"></i>
        </div>
        <p class="dom-empty-title" style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">No domains assigned</p>
        <p style="font-size:13px;color:#9CA3AF;margin:0 0 20px;">You have not been assigned as responsible person for any domain yet.</p>
        <button onclick="document.getElementById('userAddDomainModal').style.display='block'"
                style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
            <i class="fas fa-plus" style="font-size:11px;"></i> Add Domain
        </button>
    </div>
    @else

    {{-- Search / filter toolbar --}}
    <div class="dom-toolbar-mobile" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;padding:10px 14px;box-shadow:0 1px 6px rgba(0,0,0,.04);">
        <div style="position:relative;flex:1;min-width:180px;">
            <i class="fas fa-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
            <input type="text" id="dom-search" class="dom-search" placeholder="Search domain or registrar…" oninput="filterDomains()" style="width:100%;box-sizing:border-box;">
        </div>
        <select id="dom-status-filter" onchange="filterDomains()" style="border:1.5px solid #E5E7EB;border-radius:10px;padding:8px 14px;font-size:13px;color:#374151;outline:none;background:#fff;cursor:pointer;">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="expiring_soon">Expiring Soon</option>
            <option value="expired">Expired</option>
        </select>
        <span style="font-size:12px;color:#9CA3AF;" id="dom-count">{{ $domains->count() }} domain{{ $domains->count() !== 1 ? 's' : '' }}</span>
    </div>

    {{-- Table --}}
    <div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:16px;overflow:clip;box-shadow:0 1px 6px rgba(0,0,0,.04);">
        <div style="overflow-x:auto;" class="mob-table-cards">
        <table class="dom-table" id="domains-table">
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Registrar</th>
                    <th>Customer</th>
                    <th>Expiry Date</th>
                    <th>Days Left</th>
                    <th>Auto Renew</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($domains as $domain)
                @php
                    $rowClass = match($domain->status) {
                        'expired'       => 'row-expired',
                        'expiring_soon' => 'row-expiring',
                        default         => '',
                    };
                @endphp
                <tr class="{{ $rowClass }}"
                    data-domain="{{ strtolower($domain->domain) }}"
                    data-registrar="{{ strtolower($domain->registrar ?? '') }}"
                    data-status="{{ $domain->status }}"
                    onclick="window.location='{{ route('user.domains.show', $domain) }}'">
                    <td data-label="Domain">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:9px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-globe" style="font-size:13px;color:#4F46E5;"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;color:#111827;">{{ $domain->domain }}</div>
                                @if($domain->registrar)
                                <div style="font-size:11px;color:#9CA3AF;">{{ $domain->registrar }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td data-label="Registrar" style="color:#374151;">{{ $domain->registrar ?: '—' }}</td>
                    <td data-label="Customer" style="color:#374151;">{{ $domain->customer?->company ?: $domain->customer?->name ?: '—' }}</td>
                    <td data-label="Expiry Date">
                        @if($domain->expires_at)
                            <span style="font-weight:600;color:{{ $domain->status === 'expired' ? '#DC2626' : ($domain->status === 'expiring_soon' ? '#D97706' : '#111827') }};">
                                {{ $domain->expires_at->format('d M Y') }}
                            </span>
                        @else
                            <span style="color:#9CA3AF;">—</span>
                        @endif
                    </td>
                    <td data-label="Days Left">
                        @if($domain->days_until_expiry !== null)
                            @if($domain->days_until_expiry < 0)
                                <span style="font-weight:700;color:#DC2626;">Expired {{ abs($domain->days_until_expiry) }}d ago</span>
                            @elseif($domain->days_until_expiry === 0)
                                <span style="font-weight:700;color:#DC2626;">Today</span>
                            @else
                                <span style="font-weight:600;color:{{ $domain->days_until_expiry <= 30 ? '#D97706' : '#374151' }};">
                                    {{ $domain->days_until_expiry }} days
                                </span>
                            @endif
                        @else
                            <span style="color:#9CA3AF;">—</span>
                        @endif
                    </td>
                    <td data-label="Auto Renew">
                        @if($domain->auto_renew)
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#059669;">
                                <i class="fas fa-rotate" style="font-size:10px;"></i> Yes
                            </span>
                        @else
                            <span style="font-size:12px;color:#9CA3AF;">No</span>
                        @endif
                    </td>
                    <td data-label="Status">
                        @if($domain->status === 'active')
                            <span class="status-badge badge-active"><i class="fas fa-circle" style="font-size:7px;"></i> Active</span>
                        @elseif($domain->status === 'expiring_soon')
                            <span class="status-badge badge-expiring"><i class="fas fa-triangle-exclamation" style="font-size:9px;"></i> Expiring</span>
                        @elseif($domain->status === 'expired')
                            <span class="status-badge badge-expired"><i class="fas fa-circle-xmark" style="font-size:9px;"></i> Expired</span>
                        @else
                            <span class="status-badge badge-none">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    @endif

</div>

{{-- Add Domain Modal --}}
<div id="userAddDomainModal" style="display:none;position:fixed;inset:0;z-index:9500;overflow-y:auto;background:rgba(0,0,0,.45);" onclick="if(event.target===this)document.getElementById('userAddDomainModal').style.display='none'">
    <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:24px 16px;">
        <div style="position:relative;background:#fff;border-radius:16px;padding:28px;width:640px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);" onclick="event.stopPropagation()">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
                <h3 style="font-size:18px;font-weight:700;color:#111827;margin:0;">Add Domain</h3>
                <button onclick="document.getElementById('userAddDomainModal').style.display='none'" style="width:32px;height:32px;background:#F3F4F6;border:none;border-radius:8px;cursor:pointer;font-size:16px;color:#6B7280;">✕</button>
            </div>
            <p style="font-size:12.5px;color:#9CA3AF;margin:-14px 0 20px;">You'll automatically be set as a responsible person for this domain.</p>
            <form method="POST" action="{{ route('user.domains.store') }}">
                @csrf
                @include('admin.domains._form', ['domain' => null])
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px;">
                    <button type="button" onclick="document.getElementById('userAddDomainModal').style.display='none'" style="padding:10px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:10px 24px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-plus" style="margin-right:6px;"></i>Add Domain
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Expiring Soon Modal --}}
<div id="dom-expiring-overlay" class="dom-detail-overlay" style="display:{{ $showExpiringPopup ? 'flex' : 'none' }};z-index:10000;" onclick="if(event.target===this)closeExpiringSummary()">
    <div class="dom-detail-panel" style="width:480px;">
        <div class="dom-detail-header" style="background:linear-gradient(135deg,#DC2626 0%,#EF4444 100%);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;background:rgba(255,255,255,.2);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-triangle-exclamation" style="font-size:18px;color:#fff;"></i>
                </div>
                <div>
                    <div style="font-size:17px;font-weight:800;color:#fff;">Domains Expiring Soon</div>
                    <div style="font-size:12px;color:rgba(255,255,255,.8);margin-top:2px;">{{ $expiringDomains->count() }} of your domain{{ $expiringDomains->count() !== 1 ? 's' : '' }} within 30 days</div>
                </div>
            </div>
            <button onclick="closeExpiringSummary()" style="width:32px;height:32px;background:rgba(255,255,255,.15);border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
                <i class="fas fa-times" style="font-size:13px;"></i>
            </button>
        </div>
        <div class="dom-detail-body" style="padding:12px;">
            @foreach($expiringDomains as $ed)
            <a href="{{ route('user.domains.show', $ed) }}"
                 class="dom-expiring-row"
                 style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px;border-radius:10px;cursor:pointer;margin-bottom:4px;text-decoration:none;color:inherit;">
                <div style="min-width:0;">
                    <div style="font-weight:700;color:#111827;font-size:13.5px;">{{ $ed->domain }}</div>
                    <div style="font-size:11.5px;color:#9CA3AF;margin-top:1px;">{{ $ed->registrar ?: 'No registrar' }}</div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-size:12.5px;font-weight:700;color:{{ $ed->days_until_expiry <= 7 ? '#DC2626' : '#D97706' }};">
                        {{ $ed->days_until_expiry }} day{{ $ed->days_until_expiry !== 1 ? 's' : '' }} left
                    </div>
                    <div style="font-size:11px;color:#9CA3AF;">{{ $ed->expires_at->format('d M Y') }}</div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>

<script>
function closeExpiringSummary() {
    document.getElementById('dom-expiring-overlay').style.display = 'none';
    document.body.style.overflow = '';
    fetch('{{ route('domains.expiring.dismiss') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
    });
}

if (document.getElementById('dom-expiring-overlay').style.display === 'flex') {
    document.body.style.overflow = 'hidden';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeExpiringSummary(); }
});

function filterDomains() {
    const search = document.getElementById('dom-search').value.toLowerCase().trim();
    const status = document.getElementById('dom-status-filter').value;
    const rows   = document.querySelectorAll('#domains-table tbody tr');
    let visible  = 0;

    rows.forEach(row => {
        const matchSearch = !search ||
            row.dataset.domain.includes(search) ||
            row.dataset.registrar.includes(search);
        const matchStatus = !status || row.dataset.status === status;
        const show = matchSearch && matchStatus;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const countEl = document.getElementById('dom-count');
    if (countEl) countEl.textContent = visible + ' domain' + (visible !== 1 ? 's' : '');
}
</script>
@endsection

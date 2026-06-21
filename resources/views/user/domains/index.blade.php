@extends('layouts.app')
@section('title', 'My Domains')

@section('content')
<style>
.dom-header { background:linear-gradient(135deg,#059669 0%,#10B981 50%,#34D399 100%); border-radius:20px; padding:28px 32px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; gap:24px; flex-wrap:wrap; box-shadow:0 8px 32px rgba(5,150,105,.25); }
.dom-stat-chip { display:flex; align-items:center; gap:10px; padding:11px 18px; background:#fff; border:1.5px solid #E5E7EB; border-radius:12px; flex:1; min-width:110px; }
.dom-search { border:1.5px solid #E5E7EB; border-radius:10px; padding:8px 14px 8px 36px; font-size:13px; color:#111827; outline:none; width:220px; transition:border-color .15s; background:#fff; }
.dom-search:focus { border-color:#10B981; }
.dom-table { width:100%; border-collapse:separate; border-spacing:0; }
.dom-table th { background:#F9FAFB; font-size:11px; font-weight:700; color:#6B7280; text-transform:uppercase; letter-spacing:.05em; padding:10px 16px; border-bottom:1.5px solid #E5E7EB; text-align:left; white-space:nowrap; }
.dom-table th:first-child { border-radius:10px 0 0 0; }
.dom-table th:last-child  { border-radius:0 10px 0 0; }
.dom-table td { padding:13px 16px; border-bottom:1px solid #F3F4F6; font-size:13px; color:#374151; vertical-align:middle; background:#fff; }
.dom-table tr:last-child td { border-bottom:none; }
.dom-table tr:last-child td:first-child { border-radius:0 0 0 10px; }
.dom-table tr:last-child td:last-child  { border-radius:0 0 10px 0; }
.dom-table tbody tr:hover td { background:#F0FDF4; }
.dom-table tbody tr.row-expired td { background:#FFF8F8; }
.dom-table tbody tr.row-expired:hover td { background:#FEF2F2; }
.dom-table tbody tr.row-expiring td { background:#FFFDF0; }
.dom-table tbody tr.row-expiring:hover td { background:#FFFBEB; }
.status-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.badge-active   { background:#D1FAE5; color:#065F46; }
.badge-expiring { background:#FEF3C7; color:#92400E; }
.badge-expired  { background:#FEE2E2; color:#991B1B; }
.badge-none     { background:#F3F4F6; color:#6B7280; }
.dom-empty { text-align:center; padding:60px 20px; }
</style>

<div style="padding:0 0 32px;">

    {{-- Header --}}
    <div class="dom-header">
        <div>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
                <div style="width:44px;height:44px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
                    <i class="fas fa-globe" style="font-size:20px;color:#fff;"></i>
                </div>
                <div>
                    <h1 style="font-size:22px;font-weight:800;color:#fff;margin:0;">My Domains</h1>
                    <p style="font-size:13px;color:rgba(255,255,255,.75);margin:0;">Domains you are responsible for</p>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @php
                $activeCount   = $domains->filter(fn($d) => $d->status === 'active')->count();
                $expiringCount = $domains->filter(fn($d) => $d->status === 'expiring_soon')->count();
                $expiredCount  = $domains->filter(fn($d) => $d->status === 'expired')->count();
            @endphp
            <div class="dom-stat-chip">
                <span style="width:30px;height:30px;background:#D1FAE5;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-circle-check" style="font-size:13px;color:#059669;"></i>
                </span>
                <div>
                    <div style="font-size:18px;font-weight:800;color:#111827;">{{ $activeCount }}</div>
                    <div style="font-size:10px;color:#6B7280;font-weight:600;text-transform:uppercase;">Active</div>
                </div>
            </div>
            @if($expiringCount > 0)
            <div class="dom-stat-chip" style="border-color:#FCD34D;">
                <span style="width:30px;height:30px;background:#FEF3C7;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-triangle-exclamation" style="font-size:13px;color:#D97706;"></i>
                </span>
                <div>
                    <div style="font-size:18px;font-weight:800;color:#92400E;">{{ $expiringCount }}</div>
                    <div style="font-size:10px;color:#6B7280;font-weight:600;text-transform:uppercase;">Expiring Soon</div>
                </div>
            </div>
            @endif
            @if($expiredCount > 0)
            <div class="dom-stat-chip" style="border-color:#FCA5A5;">
                <span style="width:30px;height:30px;background:#FEE2E2;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-circle-xmark" style="font-size:13px;color:#DC2626;"></i>
                </span>
                <div>
                    <div style="font-size:18px;font-weight:800;color:#991B1B;">{{ $expiredCount }}</div>
                    <div style="font-size:10px;color:#6B7280;font-weight:600;text-transform:uppercase;">Expired</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($domains->isEmpty())
    <div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:16px;padding:60px 20px;text-align:center;">
        <div style="width:72px;height:72px;background:#D1FAE5;border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-globe" style="font-size:28px;color:#10B981;"></i>
        </div>
        <p style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">No domains assigned</p>
        <p style="font-size:13px;color:#9CA3AF;margin:0;">You have not been assigned as responsible person for any domain yet.</p>
    </div>
    @else

    {{-- Search / filter toolbar --}}
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;padding:10px 14px;box-shadow:0 1px 6px rgba(0,0,0,.04);">
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
    <div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:16px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.04);">
        <div style="overflow-x:auto;">
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
                    data-status="{{ $domain->status }}">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:9px;background:#D1FAE5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-globe" style="font-size:13px;color:#059669;"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;color:#111827;">{{ $domain->domain }}</div>
                                @if($domain->login_url)
                                <a href="{{ $domain->login_url }}" target="_blank" rel="noopener noreferrer"
                                   style="font-size:11px;color:#6B7280;text-decoration:none;"
                                   onclick="event.stopPropagation()">
                                    <i class="fas fa-external-link-alt" style="font-size:9px;"></i> Registrar Panel
                                </a>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="color:#374151;">{{ $domain->registrar ?: '—' }}</td>
                    <td style="color:#374151;">{{ $domain->customer?->company ?: $domain->customer?->name ?: '—' }}</td>
                    <td>
                        @if($domain->expires_at)
                            <span style="font-weight:600;color:{{ $domain->status === 'expired' ? '#DC2626' : ($domain->status === 'expiring_soon' ? '#D97706' : '#111827') }};">
                                {{ $domain->expires_at->format('d M Y') }}
                            </span>
                        @else
                            <span style="color:#9CA3AF;">—</span>
                        @endif
                    </td>
                    <td>
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
                    <td>
                        @if($domain->auto_renew)
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#059669;">
                                <i class="fas fa-rotate" style="font-size:10px;"></i> Yes
                            </span>
                        @else
                            <span style="font-size:12px;color:#9CA3AF;">No</span>
                        @endif
                    </td>
                    <td>
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

<script>
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

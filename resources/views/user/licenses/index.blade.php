@extends('layouts.app')
@section('title', 'My Vault')

@section('content')
<style>
/* ── Preserved classes (referenced by JS) ── */
.cred-val { font-size:13px; font-weight:600; color:#111827; font-family:monospace; background:#F3F4F6; border-radius:7px; padding:6px 10px; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cred-copy { border:none; background:#EEF2FF; color:#4F46E5; border-radius:7px; padding:6px 10px; cursor:pointer; font-size:11px; font-weight:700; flex-shrink:0; transition:background .15s; }
.cred-copy:hover { background:#C7D2FE; }
.cred-reveal { border:none; background:#F3F4F6; color:#6B7280; border-radius:7px; padding:6px 10px; cursor:pointer; font-size:11px; flex-shrink:0; transition:background .15s; }
.cred-reveal:hover { background:#E5E7EB; }
.attach-row { display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; background:#F9FAFB; text-decoration:none; transition:background .15s; }
.attach-row:hover { background:#EEF2FF; }
.lic-stat { background:#fff; border:1.5px solid #E5E7EB; border-radius:14px; padding:16px 20px; display:flex; align-items:center; gap:14px; cursor:pointer; transition:box-shadow .15s,border-color .15s; }
.lic-stat:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); }
.lic-stat.active-filter { border-color:#4F46E5; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
.lic-search { border:1.5px solid #E5E7EB; border-radius:10px; padding:8px 14px 8px 36px; font-size:13px; color:#111827; outline:none; width:200px; transition:border-color .15s; background:#fff; }
.lic-search:focus { border-color:#4F46E5; }
.lic-tab { border:none; background:transparent; padding:6px 14px; border-radius:8px; font-size:13px; font-weight:600; color:#6B7280; cursor:pointer; transition:all .15s; white-space:nowrap; }
.lic-tab:hover { background:#F3F4F6; color:#374151; }
.lic-tab.active { background:#EEF2FF; color:#4F46E5; }
.lic-cat-select { border:1.5px solid #E5E7EB; border-radius:10px; padding:7px 32px 7px 12px; font-size:13px; color:#374151; outline:none; appearance:none; background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M2 4l4 4 4-4' stroke='%236B7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 10px center; cursor:pointer; }
.lic-cat-select:focus { border-color:#4F46E5; }
/* Table */
.lic-table { width:100%; border-collapse:separate; border-spacing:0; }
.lic-table th { background:#F9FAFB; font-size:11px; font-weight:700; color:#6B7280; text-transform:uppercase; letter-spacing:.05em; padding:10px 16px; border-bottom:1.5px solid #E5E7EB; text-align:left; white-space:nowrap; }
.lic-table th:first-child { border-radius:10px 0 0 0; }
.lic-table th:last-child  { border-radius:0 10px 0 0; }
.lic-table th.sortable { cursor:pointer; user-select:none; }
.lic-table th.sortable:hover { background:#F3F4F6; color:#111827; }
.lic-table th.sortable.active { color:#4F46E5; background:#EEF2FF; }
.lic-sort-icon { margin-left:5px; font-size:9px; opacity:.4; }
.lic-table th.active .lic-sort-icon { opacity:1; color:#4F46E5; }
.lic-table td { padding:12px 16px; border-bottom:1px solid #F3F4F6; font-size:13px; color:#374151; vertical-align:middle; background:#fff; }
.lic-table tr:last-child td { border-bottom:none; }
.lic-table tr:last-child td:first-child { border-radius:0 0 0 10px; }
.lic-table tr:last-child td:last-child  { border-radius:0 0 10px 0; }
.lic-table tbody tr:hover td { background:#F9FAFB; }
.lic-table tbody tr.row-expired td { background:#FFF8F8; }
.lic-table tbody tr.row-expired:hover td { background:#FEF2F2; }
.lic-table tbody tr.row-expiring td { background:#FFFDF0; }
.lic-table tbody tr.row-expiring:hover td { background:#FFFBEB; }
/* ── New layout classes ── */
.vault-header { background:linear-gradient(135deg,#4F46E5 0%,#6366F1 50%,#818CF8 100%); border-radius:20px; padding:28px 32px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; gap:24px; flex-wrap:wrap; box-shadow:0 8px 32px rgba(79,70,229,.25); }
.vault-seg { display:flex; gap:4px; background:rgba(255,255,255,.15); border-radius:14px; padding:5px; backdrop-filter:blur(8px); }
.vault-seg-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 20px; border-radius:10px; font-size:13px; font-weight:700; border:none; cursor:pointer; transition:all .2s; color:rgba(255,255,255,.7); background:transparent; white-space:nowrap; }
.vault-seg-btn.active { background:#fff; color:#4F46E5; box-shadow:0 2px 8px rgba(0,0,0,.12); }
.stat-strip { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.stat-chip { display:flex; align-items:center; gap:10px; padding:12px 18px; background:#fff; border:1.5px solid #E5E7EB; border-radius:12px; cursor:pointer; transition:all .15s; flex:1; min-width:120px; }
.stat-chip:hover { border-color:#A5B4FC; box-shadow:0 2px 8px rgba(99,102,241,.1); }
.stat-chip.active-filter { border-color:#4F46E5; box-shadow:0 0 0 3px rgba(99,102,241,.12); background:#FAFBFF; }
.toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; background:#fff; border:1.5px solid #E5E7EB; border-radius:14px; padding:10px 14px; box-shadow:0 1px 6px rgba(0,0,0,.04); }
/* Premium cards */
.lic-card { background:#fff; border:1.5px solid #E5E7EB; border-radius:16px; overflow:hidden; display:flex; flex-direction:column; transition:box-shadow .2s,transform .2s; }
.lic-card:hover { box-shadow:0 10px 32px rgba(0,0,0,.11); transform:translateY(-3px); }
.lic-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
@media(max-width:900px) { .lic-grid { grid-template-columns:repeat(2,1fr) !important; } }
@media(max-width:560px) { .lic-grid { grid-template-columns:1fr !important; } .vault-header { flex-direction:column; align-items:flex-start; } }
.card-band { height:80px; display:flex; align-items:center; justify-content:space-between; padding:0 18px; position:relative; }
.card-band-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.25); display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); border:2px solid rgba(255,255,255,.3); }
.view-toggle { display:flex; gap:2px; background:#F3F4F6; border-radius:10px; padding:3px; }
.view-toggle-btn { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:8px; font-size:12px; font-weight:600; border:none; cursor:pointer; transition:all .15s; background:transparent; color:#6B7280; }
.view-toggle-btn.active { background:#fff; color:#4F46E5; box-shadow:0 1px 4px rgba(0,0,0,.08); }
/* Social vault cards */
.sa-vault-card { background:#fff; border:1.5px solid #E5E7EB; border-radius:16px; overflow:hidden; display:flex; flex-direction:column; transition:box-shadow .2s,transform .2s; }
.sa-vault-card:hover { box-shadow:0 10px 32px rgba(0,0,0,.1); transform:translateY(-2px); }
.sa-vault-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:18px; }
[x-cloak] { display:none !important; }
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
    $usedCategories = $licenses->pluck('category')->unique()->values();
    $licSubtitle = $licenses->count().' '.Str::plural('tool', $licenses->count()).' assigned to you';
    $saSubtitle  = $socialAccounts->count().' '.Str::plural('account', $socialAccounts->count()).' you have access to';
@endphp

{{-- LIC_DATA must be defined before Alpine initialises --}}
<script>
@php
$licData = [];
foreach($licenses as $l) {
    $pivot      = $l->users->first()?->pivot;
    $assignedAt = $pivot?->assigned_at ? \Carbon\Carbon::parse($pivot->assigned_at)->format('d M Y') : '';
    $assignedBy = $assigners[$pivot?->assigned_by ?? 0] ?? '';
    $licData[$l->id] = [
        'id'           => $l->id,
        'name'         => $l->name,
        'vendor'       => $l->vendor ?? '',
        'category'     => $l->category,
        'catLabel'     => $catLabels[$l->category] ?? $l->category,
        'catBg'        => $catColors[$l->category]['bg'] ?? '#F3F4F6',
        'catColor'     => $catColors[$l->category]['color'] ?? '#6B7280',
        'catIcon'      => $catIcons[$l->category] ?? 'layer-group',
        'status'       => $l->status,
        'days'         => $l->days_until_renewal,
        'renewalDate'  => $l->renewal_date ? $l->renewal_date->format('d M Y') : '',
        'type'         => $l->type === 'per_seat' ? 'Per Seat' : ($l->type === 'site_license' ? 'Site License' : 'Shared'),
        'since'        => $l->purchase_date ? $l->purchase_date->format('M Y') : '',
        'website'      => $l->website ?? '',
        'username'     => $l->username ?? '',
        'password'     => $l->decrypted_password ?? '',
        'notes'        => $l->notes ?? '',
        'logoUrl'      => $l->logo_url ?? '',
        'cost'         => $l->cost > 0 ? number_format((float)$l->cost, 3).' '.$l->currency : '',
        'billingCycle' => match($l->billing_cycle) { 'monthly'=>'Monthly','annual'=>'Annual','quarterly'=>'Quarterly',default=>ucfirst($l->billing_cycle) },
        'assignedAt'   => $assignedAt,
        'assignedBy'   => $assignedBy,
        'attachments'  => $l->attachments->map(fn($a) => [
            'filename' => $a->filename,
            'url'      => $a->url,
            'size'     => $a->formatted_size,
            'icon'     => $a->icon_class,
            'comment'  => $a->comment ?? '',
        ])->values()->toArray(),
    ];
}
@endphp
const LIC_DATA = @json($licData);
</script>

<div x-data="{
    section: localStorage.getItem('lic_section') || 'licenses',
    view: localStorage.getItem('lic_view') || 'cards',
    search: '',
    statusFilter: 'all',
    categoryFilter: 'all',
    setSection(s) { this.section = s; localStorage.setItem('lic_section', s); },
    setView(v) { this.view = v; localStorage.setItem('lic_view', v); },
    setStatus(s) { this.statusFilter = s; },
    matches(id) {
        const d = LIC_DATA[id];
        if (!d) return true;
        if (this.statusFilter !== 'all' && d.status !== this.statusFilter) return false;
        if (this.categoryFilter !== 'all' && d.category !== this.categoryFilter) return false;
        if (this.search.trim()) {
            const s = this.search.toLowerCase().trim();
            if (!d.name.toLowerCase().includes(s) && !(d.vendor||'').toLowerCase().includes(s)) return false;
        }
        return true;
    },
    get visibleCount() {
        return Object.values(LIC_DATA).filter(d => this.matches(d.id)).length;
    }
}">

{{-- ══════════════════════════════════════════════ --}}
{{-- VAULT HEADER BANNER                            --}}
{{-- ══════════════════════════════════════════════ --}}
<div class="vault-header">
    {{-- Left: icon + title --}}
    <div style="display:flex;align-items:center;gap:18px;">
        <div style="width:60px;height:60px;border-radius:18px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;border:2px solid rgba(255,255,255,.3);flex-shrink:0;">
            <i :class="section==='licenses' ? 'fas fa-layer-group' : 'fas fa-share-nodes'" style="color:#fff;font-size:24px;"></i>
        </div>
        <div>
            <h1 style="font-size:24px;font-weight:800;color:#fff;margin:0;letter-spacing:-.3px;"
                x-text="section==='licenses' ? 'My Licenses' : 'My Social Accounts'"></h1>
            <p style="font-size:13px;color:rgba(255,255,255,.7);margin:4px 0 0;"
               x-text="section==='licenses' ? '{{ $licSubtitle }}' : '{{ $saSubtitle }}'"></p>
        </div>
    </div>

    {{-- Right: segmented tab control --}}
    <div class="vault-seg">
        <button class="vault-seg-btn" :class="section==='licenses' ? 'active' : ''" @click="setSection('licenses')">
            <i class="fas fa-layer-group" style="font-size:12px;"></i>
            Licenses
            <span style="font-size:10px;font-weight:800;padding:2px 7px;border-radius:20px;"
                  :style="section==='licenses' ? 'background:#EEF2FF;color:#4F46E5;' : 'background:rgba(255,255,255,.2);color:#fff;'">
                {{ $licenses->count() }}
            </span>
        </button>
        <button class="vault-seg-btn" :class="section==='social' ? 'active' : ''" @click="setSection('social')">
            <i class="fas fa-share-nodes" style="font-size:12px;"></i>
            Social Accounts
            <span style="font-size:10px;font-weight:800;padding:2px 7px;border-radius:20px;"
                  :style="section==='social' ? 'background:#F0FDF4;color:#16A34A;' : 'background:rgba(255,255,255,.2);color:#fff;'">
                {{ $socialAccounts->count() }}
            </span>
        </button>
    </div>
</div>

{{-- ═══════════════ LICENSES SECTION ═══════════════ --}}
<div x-show="section==='licenses'">

@if($licenses->isEmpty())
<div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:16px;padding:72px 24px;text-align:center;">
    <div style="width:80px;height:80px;border-radius:24px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
        <i class="fas fa-layer-group" style="font-size:32px;color:#818CF8;"></i>
    </div>
    <h2 style="font-size:18px;font-weight:700;color:#111827;margin:0 0 8px;">No licenses assigned yet</h2>
    <p style="font-size:14px;color:#9CA3AF;margin:0;">Your administrator will assign software licenses to you here.</p>
</div>

@else

{{-- ── Stat strip (clickable filters) ── --}}
@php $expiring7 = $licenses->filter(fn($l) => $l->days_until_renewal !== null && $l->days_until_renewal >= 0 && $l->days_until_renewal <= 7); @endphp
<div class="stat-strip">
    <div class="stat-chip" :class="statusFilter==='all' ? 'active-filter' : ''" @click="setStatus('all')">
        <div style="width:36px;height:36px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-layer-group" style="color:#4F46E5;font-size:14px;"></i>
        </div>
        <div>
            <p style="font-size:20px;font-weight:800;color:#111827;margin:0;line-height:1;">{{ $totalCount }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">All Tools</p>
        </div>
    </div>
    <div class="stat-chip" :class="statusFilter==='active' ? 'active-filter' : ''" @click="setStatus('active')">
        <div style="width:36px;height:36px;border-radius:10px;background:#ECFDF5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-circle-check" style="color:#16A34A;font-size:14px;"></i>
        </div>
        <div>
            <p style="font-size:20px;font-weight:800;color:#16A34A;margin:0;line-height:1;">{{ $activeCount }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Active</p>
        </div>
    </div>
    <div class="stat-chip" :class="statusFilter==='expiring_soon' ? 'active-filter' : ''" @click="setStatus('expiring_soon')">
        <div style="width:36px;height:36px;border-radius:10px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-clock" style="color:#D97706;font-size:14px;"></i>
        </div>
        <div>
            <p style="font-size:20px;font-weight:800;color:#D97706;margin:0;line-height:1;">{{ $expiringSoonCount }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Expiring</p>
        </div>
    </div>
    <div class="stat-chip" :class="statusFilter==='expired' ? 'active-filter' : ''" @click="setStatus('expired')">
        <div style="width:36px;height:36px;border-radius:10px;background:#FEE2E2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-triangle-exclamation" style="color:#DC2626;font-size:14px;"></i>
        </div>
        <div>
            <p style="font-size:20px;font-weight:800;color:#DC2626;margin:0;line-height:1;">{{ $expiredCount }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Expired</p>
        </div>
    </div>
</div>

{{-- ── Expiring soon alert ── --}}
@if($expiring7->count())
<div style="background:#FFF7ED;border:1.5px solid #FED7AA;border-radius:12px;padding:14px 20px;margin-bottom:18px;display:flex;align-items:flex-start;gap:12px;">
    <i class="fas fa-triangle-exclamation" style="color:#EA580C;margin-top:2px;flex-shrink:0;"></i>
    <div>
        <div style="font-size:13px;font-weight:700;color:#C2410C;margin-bottom:3px;">
            {{ $expiring7->count() }} {{ Str::plural('license', $expiring7->count()) }} expiring within 7 days
        </div>
        <div style="font-size:12px;color:#9A3412;">These subscriptions may be interrupted soon. Contact your administrator if needed.</div>
    </div>
</div>
@endif

{{-- ── Floating toolbar ── --}}
<div class="toolbar">
    {{-- View toggle (left) --}}
    <div class="view-toggle" style="flex-shrink:0;">
        <button class="view-toggle-btn" :class="view==='table' ? 'active' : ''" @click="setView('table')">
            <i class="fas fa-table-list" style="font-size:11px;"></i> Table
        </button>
        <button class="view-toggle-btn" :class="view==='cards' ? 'active' : ''" @click="setView('cards')">
            <i class="fas fa-grip" style="font-size:11px;"></i> Cards
        </button>
    </div>

    <div style="width:1px;height:26px;background:#E5E7EB;flex-shrink:0;"></div>

    {{-- Status tabs --}}
    <div style="display:flex;gap:3px;flex-wrap:wrap;">
        <button class="lic-tab" :class="statusFilter==='all' ? 'active' : ''" @click="setStatus('all')">All</button>
        <button class="lic-tab" :class="statusFilter==='active' ? 'active' : ''" @click="setStatus('active')">Active</button>
        <button class="lic-tab" :class="statusFilter==='expiring_soon' ? 'active' : ''" @click="setStatus('expiring_soon')">Expiring</button>
        <button class="lic-tab" :class="statusFilter==='expired' ? 'active' : ''" @click="setStatus('expired')">Expired</button>
    </div>

    @if($usedCategories->count() > 1)
    <div style="width:1px;height:26px;background:#E5E7EB;flex-shrink:0;"></div>
    <select x-model="categoryFilter" class="lic-cat-select">
        <option value="all">All Categories</option>
        @foreach($usedCategories as $cat)
        <option value="{{ $cat }}">{{ $catLabels[$cat] ?? ucfirst($cat) }}</option>
        @endforeach
    </select>
    @endif

    {{-- Search (right) --}}
    <div style="position:relative;flex-shrink:0;margin-left:auto;">
        <i class="fas fa-magnifying-glass" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;pointer-events:none;"></i>
        <input x-model="search" type="text" placeholder="Search tools or vendor…" class="lic-search">
    </div>
</div>

{{-- ── CARDS VIEW ── --}}
<div x-show="view==='cards'" x-cloak>
    <div class="lic-grid">
        @foreach($licenses as $lic)
        @php
            $cc             = $catColors[$lic->category] ?? $catColors['other'];
            $icon           = $catIcons[$lic->category] ?? 'layer-group';
            $status         = $lic->status;
            $days           = $lic->days_until_renewal;
            $statusBg       = $status === 'expired' ? '#FEE2E2' : ($status === 'expiring_soon' ? '#FEF3C7' : '#ECFDF5');
            $statusColor    = $status === 'expired' ? '#DC2626' : ($status === 'expiring_soon' ? '#D97706' : '#16A34A');
            $statusLabel    = $status === 'expired' ? 'Expired' : ($status === 'expiring_soon' ? 'Expiring Soon' : 'Active');
            $statusIcon     = $status === 'expired' ? 'fa-triangle-exclamation' : ($status === 'expiring_soon' ? 'fa-clock' : 'fa-circle-check');
            $hasCredentials = $lic->username || $lic->password;
            $decryptedPw    = $lic->decrypted_password;
        @endphp
        <div class="lic-card" x-show="matches({{ $lic->id }})">

            {{-- Colored band with icon ── --}}
            <div class="card-band" style="background:linear-gradient(135deg,{{ $cc['color'] }}22,{{ $cc['color'] }}11);">
                {{-- Logo / Icon --}}
                <div class="card-band-icon" style="background:{{ $cc['bg'] }};border-color:{{ $cc['color'] }}33;">
                    @if($lic->logo_url)
                        <img src="{{ $lic->logo_url }}" alt="{{ $lic->name }}" style="width:36px;height:36px;object-fit:contain;">
                    @else
                        <i class="fas fa-{{ $icon }}" style="font-size:22px;color:{{ $cc['color'] }};"></i>
                    @endif
                </div>
                {{-- Status badge floating top-right --}}
                <span style="display:inline-flex;align-items:center;gap:4px;background:{{ $statusBg }};color:{{ $statusColor }};padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;">
                    <i class="fas {{ $statusIcon }}" style="font-size:8px;"></i> {{ $statusLabel }}
                </span>
            </div>

            {{-- Card body ── --}}
            <div style="padding:14px 18px 0;flex:1;display:flex;flex-direction:column;">
                <h3 style="font-size:15px;font-weight:800;color:#111827;margin:0 0 2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $lic->name }}</h3>
                @if($lic->vendor)
                <p style="font-size:12px;color:#9CA3AF;margin:0 0 8px;">{{ $lic->vendor }}</p>
                @endif
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:14px;flex-wrap:wrap;">
                    <span style="display:inline-flex;align-items:center;gap:4px;background:{{ $cc['bg'] }};color:{{ $cc['color'] }};padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;">
                        <i class="fas fa-{{ $icon }}" style="font-size:9px;"></i>
                        {{ $catLabels[$lic->category] ?? $lic->category }}
                    </span>
                    @if($lic->website)
                    <a href="{{ $lic->website }}" target="_blank"
                       style="display:inline-flex;align-items:center;gap:4px;background:#EEF2FF;color:#4F46E5;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;text-decoration:none;transition:background .15s;"
                       onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='#EEF2FF'">
                        <i class="fas fa-arrow-up-right-from-square" style="font-size:9px;"></i> Open
                    </a>
                    @endif
                </div>

                {{-- Renewal Countdown ── --}}
                @if($lic->renewal_date)
                <div style="margin-bottom:14px;padding:10px 14px;border-radius:10px;background:{{ $status==='expired'?'#FFF5F5':($status==='expiring_soon'?'#FFFBEB':'#F0FDF4') }};border:1px solid {{ $status==='expired'?'#FCA5A5':($status==='expiring_soon'?'#FDE68A':'#A7F3D0') }};display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <p style="font-size:10px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;margin:0 0 2px;">{{ $status === 'expired' ? 'Expired' : 'Renews' }}</p>
                        <p style="font-size:12px;font-weight:700;color:#374151;margin:0;">{{ $lic->renewal_date->format('d M Y') }}</p>
                    </div>
                    <div style="text-align:right;">
                        <p style="font-size:24px;font-weight:900;color:{{ $statusColor }};margin:0;line-height:1;">{{ $days !== null ? abs($days) : '—' }}</p>
                        <p style="font-size:10px;color:#9CA3AF;margin:0;">{{ $status === 'expired' ? 'days ago' : 'days left' }}</p>
                    </div>
                </div>
                @endif

                {{-- Details mini row ── --}}
                <div style="display:flex;gap:6px;margin-bottom:14px;">
                    <div style="flex:1;background:#F9FAFB;border-radius:8px;padding:8px 10px;text-align:center;">
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;font-weight:500;">Type</p>
                        <p style="font-size:11px;font-weight:700;color:#374151;margin:0;">
                            {{ $lic->type === 'per_seat' ? 'Per Seat' : ($lic->type === 'site_license' ? 'Site' : 'Shared') }}
                        </p>
                    </div>
                    @if($lic->purchase_date)
                    <div style="flex:1;background:#F9FAFB;border-radius:8px;padding:8px 10px;text-align:center;">
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;font-weight:500;">Since</p>
                        <p style="font-size:11px;font-weight:700;color:#374151;margin:0;">{{ $lic->purchase_date->format('M Y') }}</p>
                    </div>
                    @endif
                </div>

                {{-- Credentials ── --}}
                @if($hasCredentials)
                <div style="margin-bottom:14px;padding:12px;background:#F8FAFF;border:1px solid #E0E7FF;border-radius:10px;">
                    <p style="font-size:10px;font-weight:700;color:#4F46E5;margin:0 0 8px;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:5px;">
                        <i class="fas fa-key" style="font-size:9px;"></i> Credentials
                    </p>
                    @if($lic->username)
                    <div style="margin-bottom:6px;">
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 3px;font-weight:500;">Username / Email</p>
                        <div style="display:flex;gap:5px;align-items:center;">
                            <span class="cred-val">{{ $lic->username }}</span>
                            <button class="cred-copy" onclick="licCopy(this, '{{ addslashes($lic->username) }}')" title="Copy"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    @endif
                    @if($decryptedPw)
                    <div>
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 3px;font-weight:500;">Password</p>
                        <div style="display:flex;gap:5px;align-items:center;">
                            <span class="cred-val" id="pw-c-{{ $lic->id }}" data-pw="{{ $decryptedPw }}" style="letter-spacing:.12em;">••••••••</span>
                            <button class="cred-reveal" onclick="licReveal('pw-c-{{ $lic->id }}','reveal-icon-c-{{ $lic->id }}')" title="Show/hide"><i class="fas fa-eye" id="reveal-icon-c-{{ $lic->id }}"></i></button>
                            <button class="cred-copy" onclick="licCopy(this, document.getElementById('pw-c-{{ $lic->id }}').dataset.pw)" title="Copy"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Notes ── --}}
                @if($lic->notes)
                <div style="margin-bottom:14px;padding:10px 12px;background:#F8FAFC;border-radius:8px;border-left:3px solid {{ $cc['color'] }};">
                    <p style="font-size:10px;font-weight:600;color:#6B7280;margin:0 0 4px;text-transform:uppercase;letter-spacing:.04em;">
                        <i class="fas fa-note-sticky" style="margin-right:3px;font-size:9px;"></i> Admin Notes
                    </p>
                    <p style="font-size:12px;color:#374151;margin:0;line-height:1.6;white-space:pre-wrap;">{{ $lic->notes }}</p>
                </div>
                @endif

                {{-- Attachments ── --}}
                @if($lic->attachments->count())
                <div style="margin-bottom:14px;">
                    <p style="font-size:10px;font-weight:700;color:#6B7280;margin:0 0 6px;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:4px;">
                        <i class="fas fa-paperclip" style="font-size:9px;"></i> Files ({{ $lic->attachments->count() }})
                    </p>
                    <div style="display:flex;flex-direction:column;gap:5px;">
                        @foreach($lic->attachments as $att)
                        <a href="{{ $att->url }}" target="_blank" class="attach-row">
                            <i class="fas {{ $att->icon_class }}" style="font-size:14px;color:#4F46E5;flex-shrink:0;width:16px;text-align:center;"></i>
                            <div style="flex:1;min-width:0;">
                                <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $att->filename }}</p>
                                @if($att->comment)<p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $att->comment }}</p>@endif
                            </div>
                            <span style="font-size:10px;color:#9CA3AF;flex-shrink:0;">{{ $att->formatted_size }}</span>
                            <i class="fas fa-download" style="font-size:10px;color:#4F46E5;flex-shrink:0;"></i>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Card footer ── --}}
            <div style="padding:10px 18px;border-top:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;background:#FAFAFA;">
                <span style="font-size:11px;color:#9CA3AF;">Added {{ $lic->created_at ? $lic->created_at->diffForHumans() : '' }}</span>
                <button onclick="openLicModal({{ $lic->id }})"
                        style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#EEF2FF;color:#4F46E5;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:background .15s;"
                        onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='#EEF2FF'">
                    <i class="fas fa-expand" style="font-size:10px;"></i> Details
                </button>
            </div>

        </div>
        @endforeach
    </div>

    {{-- No results (cards) --}}
    <div x-show="visibleCount === 0" x-cloak style="background:#fff;border:1.5px solid #E5E7EB;border-radius:16px;padding:56px 24px;text-align:center;">
        <div style="width:56px;height:56px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-filter" style="font-size:22px;color:#D1D5DB;"></i>
        </div>
        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">No licenses match your filter</h3>
        <p style="font-size:13px;color:#9CA3AF;margin:0 0 16px;">Try changing the search or status filter.</p>
        <button onclick="resetLicFilters()" style="border:none;background:#EEF2FF;color:#4F46E5;padding:8px 20px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">Clear filters</button>
    </div>
</div>

{{-- ── TABLE VIEW ── --}}
<div x-show="view==='table'" x-cloak style="background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;overflow:hidden;">
    <table class="lic-table">
        <thead>
            <tr>
                <th class="sortable" onclick="licSort('name',this)">Tool <i class="fas fa-sort lic-sort-icon" id="si-name"></i></th>
                <th class="sortable" onclick="licSort('category',this)">Category <i class="fas fa-sort lic-sort-icon" id="si-category"></i></th>
                <th class="sortable" onclick="licSort('status',this)">Status <i class="fas fa-sort lic-sort-icon" id="si-status"></i></th>
                <th class="sortable" onclick="licSort('days',this)">Renewal <i class="fas fa-sort lic-sort-icon" id="si-days"></i></th>
                <th class="sortable" onclick="licSort('username',this)">Username <i class="fas fa-sort lic-sort-icon" id="si-username"></i></th>
                <th>Password</th>
                <th class="sortable" onclick="licSort('files',this)">Files <i class="fas fa-sort lic-sort-icon" id="si-files"></i></th>
                <th></th>
            </tr>
        </thead>
        <tbody id="lic-table-body">
            @foreach($licenses as $lic)
            @php
                $cc          = $catColors[$lic->category] ?? $catColors['other'];
                $icon        = $catIcons[$lic->category] ?? 'layer-group';
                $status      = $lic->status;
                $days        = $lic->days_until_renewal;
                $statusBg    = $status === 'expired' ? '#FEE2E2' : ($status === 'expiring_soon' ? '#FEF3C7' : '#ECFDF5');
                $statusColor = $status === 'expired' ? '#DC2626' : ($status === 'expiring_soon' ? '#D97706' : '#16A34A');
                $statusLabel = $status === 'expired' ? 'Expired' : ($status === 'expiring_soon' ? 'Expiring Soon' : 'Active');
                $decryptedPw = $lic->decrypted_password;
                $statusOrder = $status === 'expired' ? 2 : ($status === 'expiring_soon' ? 1 : 0);
            @endphp
            <tr data-name="{{ strtolower($lic->name) }}"
                data-category="{{ strtolower($catLabels[$lic->category] ?? $lic->category) }}"
                data-status="{{ $statusOrder }}"
                data-days="{{ $days ?? 99999 }}"
                data-username="{{ strtolower($lic->username ?? '') }}"
                data-files="{{ $lic->attachments->count() }}"
                data-id="{{ $lic->id }}"
                class="{{ $status === 'expired' ? 'row-expired' : ($status === 'expiring_soon' ? 'row-expiring' : '') }}"
                x-show="matches({{ $lic->id }})"
                onclick="openLicModal({{ $lic->id }})"
                style="cursor:pointer;">
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:9px;background:{{ $cc['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                            @if($lic->logo_url)
                                <img src="{{ $lic->logo_url }}" alt="{{ $lic->name }}" style="width:100%;height:100%;object-fit:contain;padding:4px;">
                            @else
                                <i class="fas fa-{{ $icon }}" style="font-size:13px;color:{{ $cc['color'] }};"></i>
                            @endif
                        </div>
                        <div>
                            <p style="font-size:13px;font-weight:700;color:#111827;margin:0;">{{ $lic->name }}</p>
                            @if($lic->vendor)<p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $lic->vendor }}</p>@endif
                        </div>
                    </div>
                </td>
                <td>
                    <span style="background:{{ $cc['bg'] }};color:{{ $cc['color'] }};padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;">
                        {{ $catLabels[$lic->category] ?? $lic->category }}
                    </span>
                </td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:4px;background:{{ $statusBg }};color:{{ $statusColor }};padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;">
                        <i class="fas fa-{{ $status === 'expired' ? 'triangle-exclamation' : ($status === 'expiring_soon' ? 'clock' : 'circle-check') }}" style="font-size:9px;"></i>
                        {{ $statusLabel }}
                    </span>
                </td>
                <td>
                    @if($lic->renewal_date)
                    <p style="font-size:13px;font-weight:600;color:#374151;margin:0;">{{ $lic->renewal_date->format('d M Y') }}</p>
                    @if($days !== null)
                    <p style="font-size:11px;color:{{ $statusColor }};margin:1px 0 0;">
                        {{ $status === 'expired' ? abs($days).' days ago' : abs($days).' days left' }}
                    </p>
                    @endif
                    @else
                    <span style="color:#D1D5DB;">—</span>
                    @endif
                </td>
                <td>
                    @if($lic->username)
                    <div style="display:flex;align-items:center;gap:5px;">
                        <span style="font-size:12px;font-weight:600;color:#111827;font-family:monospace;">{{ $lic->username }}</span>
                        <button class="cred-copy" style="padding:4px 8px;" onclick="event.stopPropagation();licCopy(this,'{{ addslashes($lic->username) }}')" title="Copy"><i class="fas fa-copy"></i></button>
                    </div>
                    @else
                    <span style="color:#D1D5DB;font-size:12px;">—</span>
                    @endif
                </td>
                <td>
                    @if($decryptedPw)
                    <div style="display:flex;align-items:center;gap:5px;">
                        <span id="pw-t-{{ $lic->id }}" data-pw="{{ $decryptedPw }}" style="font-size:12px;font-weight:600;color:#111827;font-family:monospace;letter-spacing:.1em;">••••••••</span>
                        <button class="cred-reveal" style="padding:4px 8px;" onclick="event.stopPropagation();licReveal('pw-t-{{ $lic->id }}','reveal-icon-t-{{ $lic->id }}')" title="Show/hide"><i class="fas fa-eye" id="reveal-icon-t-{{ $lic->id }}"></i></button>
                        <button class="cred-copy" style="padding:4px 8px;" onclick="event.stopPropagation();licCopy(this,document.getElementById('pw-t-{{ $lic->id }}').dataset.pw)" title="Copy"><i class="fas fa-copy"></i></button>
                    </div>
                    @else
                    <span style="color:#D1D5DB;font-size:12px;">—</span>
                    @endif
                </td>
                <td>
                    @if($lic->attachments->count())
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                        @foreach($lic->attachments as $att)
                        <a href="{{ $att->url }}" target="_blank"
                           onclick="event.stopPropagation()"
                           style="display:inline-flex;align-items:center;gap:4px;background:#EEF2FF;color:#4F46E5;padding:3px 9px;border-radius:8px;font-size:11px;font-weight:600;text-decoration:none;transition:background .15s;"
                           onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='#EEF2FF'"
                           title="{{ $att->filename }}">
                            <i class="fas {{ $att->icon_class }}" style="font-size:10px;"></i>
                            <span style="max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $att->filename }}</span>
                        </a>
                        @endforeach
                    </div>
                    @else
                    <span style="color:#D1D5DB;font-size:12px;">—</span>
                    @endif
                </td>
                <td style="text-align:right;" onclick="event.stopPropagation()">
                    @if($lic->website)
                    <a href="{{ $lic->website }}" target="_blank"
                       style="display:inline-flex;align-items:center;gap:5px;background:#EEF2FF;color:#4F46E5;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:background .15s;white-space:nowrap;"
                       onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='#EEF2FF'">
                        <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i> Open
                    </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- No results (table) --}}
    <div x-show="visibleCount === 0" x-cloak style="padding:48px 24px;text-align:center;">
        <i class="fas fa-filter" style="font-size:28px;color:#D1D5DB;margin-bottom:14px;display:block;"></i>
        <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 6px;">No licenses match your filter</h3>
        <p style="font-size:13px;color:#9CA3AF;margin:0 0 14px;">Try changing the search or status filter.</p>
        <button onclick="resetLicFilters()" style="border:none;background:#EEF2FF;color:#4F46E5;padding:8px 20px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">Clear filters</button>
    </div>
</div>

@endif

</div>{{-- /licenses section --}}

{{-- ═══════════════ SOCIAL ACCOUNTS SECTION ═══════════════ --}}
<div x-show="section==='social'" x-cloak>

@if($socialAccounts->isEmpty())
<div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:16px;padding:72px 24px;text-align:center;">
    <div style="width:80px;height:80px;border-radius:24px;background:linear-gradient(135deg,#F0FDF4,#DCFCE7);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
        <i class="fas fa-share-nodes" style="font-size:32px;color:#86EFAC;"></i>
    </div>
    <h2 style="font-size:18px;font-weight:700;color:#111827;margin:0 0 8px;">No social accounts yet</h2>
    <p style="font-size:14px;color:#9CA3AF;margin:0;">Your administrator will grant you access to social accounts here.</p>
</div>
@else

{{-- Stats chips ── --}}
@php
    $saActive    = $socialAccounts->where('status','active')->count();
    $saInactive  = $socialAccounts->where('status','inactive')->count();
    $saSuspended = $socialAccounts->where('status','suspended')->count();
    $saByPlat    = $socialAccounts->groupBy('platform')->count();
@endphp
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:10px;padding:12px 18px;background:#fff;border:1.5px solid #E5E7EB;border-radius:12px;flex:1;min-width:120px;">
        <div style="width:34px;height:34px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-share-nodes" style="color:#4F46E5;font-size:13px;"></i>
        </div>
        <div>
            <p style="font-size:20px;font-weight:800;color:#111827;margin:0;line-height:1;">{{ $socialAccounts->count() }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Total</p>
        </div>
    </div>
    @if($saActive)
    <div style="display:flex;align-items:center;gap:10px;padding:12px 18px;background:#fff;border:1.5px solid #A7F3D0;border-radius:12px;flex:1;min-width:120px;">
        <div style="width:34px;height:34px;border-radius:10px;background:#ECFDF5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-circle-check" style="color:#16A34A;font-size:13px;"></i>
        </div>
        <div>
            <p style="font-size:20px;font-weight:800;color:#16A34A;margin:0;line-height:1;">{{ $saActive }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Active</p>
        </div>
    </div>
    @endif
    @if($saSuspended)
    <div style="display:flex;align-items:center;gap:10px;padding:12px 18px;background:#fff;border:1.5px solid #FECACA;border-radius:12px;flex:1;min-width:120px;">
        <div style="width:34px;height:34px;border-radius:10px;background:#FEE2E2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-triangle-exclamation" style="color:#DC2626;font-size:13px;"></i>
        </div>
        <div>
            <p style="font-size:20px;font-weight:800;color:#DC2626;margin:0;line-height:1;">{{ $saSuspended }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Suspended</p>
        </div>
    </div>
    @endif
    <div style="display:flex;align-items:center;gap:10px;padding:12px 18px;background:#fff;border:1.5px solid #E5E7EB;border-radius:12px;flex:1;min-width:120px;">
        <div style="width:34px;height:34px;border-radius:10px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-layer-group" style="color:#6B7280;font-size:13px;"></i>
        </div>
        <div>
            <p style="font-size:20px;font-weight:800;color:#111827;margin:0;line-height:1;">{{ $saByPlat }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">{{ Str::plural('Platform', $saByPlat) }}</p>
        </div>
    </div>
</div>

{{-- Social Vault Cards ── --}}
<div class="sa-vault-grid">
    @foreach($socialAccounts as $sa)
    @php
        $pi      = $saPlatforms[$sa->platform] ?? ['label'=>ucfirst($sa->platform),'icon'=>'fa-globe','color'=>'#6B7280','bg'=>'#F3F4F6'];
        $stBg    = match($sa->status) { 'active'=>'#DCFCE7','inactive'=>'#F3F4F6','suspended'=>'#FEE2E2', default=>'#F3F4F6' };
        $stColor = match($sa->status) { 'active'=>'#16A34A','inactive'=>'#6B7280','suspended'=>'#DC2626', default=>'#6B7280' };
        $stDot   = match($sa->status) { 'active'=>'#22C55E','inactive'=>'#9CA3AF','suspended'=>'#EF4444', default=>'#9CA3AF' };
        $decPw   = $sa->decrypted_password;
        $hasCreds = $sa->username || $sa->email || $decPw || $sa->account_id;
    @endphp
    <div x-data="{ showPw: false }" class="sa-vault-card">

        {{-- Platform band ── --}}
        <div style="background:linear-gradient(135deg,{{ $pi['color'] }}18,{{ $pi['color'] }}08);padding:18px 20px 14px;display:flex;align-items:flex-start;gap:14px;border-bottom:1.5px solid {{ $pi['color'] }}18;">
            <div style="width:52px;height:52px;border-radius:14px;background:{{ $pi['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 8px {{ $pi['color'] }}22;">
                <i class="fab {{ $pi['icon'] }}" style="font-size:24px;color:{{ $pi['color'] }};"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <h3 style="font-size:15px;font-weight:800;color:#111827;margin:0 0 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $sa->name }}</h3>
                <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                    <span style="width:7px;height:7px;border-radius:50%;background:{{ $stDot }};flex-shrink:0;"></span>
                    <span style="font-size:12px;color:#6B7280;font-weight:500;">{{ $pi['label'] }}</span>
                    @if($sa->customer)
                    <span style="font-size:11px;color:#D1D5DB;">·</span>
                    <i class="fas fa-building" style="font-size:9px;color:#6366F1;"></i>
                    <span style="font-size:11px;color:#6366F1;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:90px;">{{ $sa->customer->name }}</span>
                    @endif
                </div>
            </div>
            <span style="font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;background:{{ $stBg }};color:{{ $stColor }};flex-shrink:0;text-transform:capitalize;">
                {{ $sa->status }}
            </span>
        </div>

        {{-- Vault credentials ── --}}
        @if($hasCreds)
        <div style="padding:14px 20px;display:flex;flex-direction:column;gap:7px;border-bottom:1.5px solid #F3F4F6;">
            <p style="font-size:10px;font-weight:700;color:#4F46E5;text-transform:uppercase;letter-spacing:.05em;margin:0 0 3px;display:flex;align-items:center;gap:5px;">
                <i class="fas fa-vault" style="font-size:9px;"></i> Credentials
            </p>
            @if($sa->username)
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:20px;text-align:center;flex-shrink:0;"><i class="fas fa-at" style="font-size:10px;color:#C4B5FD;"></i></div>
                <span style="font-size:10px;color:#9CA3AF;width:58px;flex-shrink:0;text-transform:uppercase;letter-spacing:.04em;">User</span>
                <span class="cred-val" style="font-size:12px;">{{ $sa->username }}</span>
                <button class="cred-copy" onclick="licCopy(this,'{{ addslashes($sa->username) }}')" title="Copy"><i class="fas fa-copy"></i></button>
            </div>
            @endif
            @if($sa->email)
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:20px;text-align:center;flex-shrink:0;"><i class="fas fa-envelope" style="font-size:10px;color:#93C5FD;"></i></div>
                <span style="font-size:10px;color:#9CA3AF;width:58px;flex-shrink:0;text-transform:uppercase;letter-spacing:.04em;">Email</span>
                <span class="cred-val" style="font-size:12px;">{{ $sa->email }}</span>
                <button class="cred-copy" onclick="licCopy(this,'{{ addslashes($sa->email) }}')" title="Copy"><i class="fas fa-copy"></i></button>
            </div>
            @endif
            @if($decPw)
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:20px;text-align:center;flex-shrink:0;"><i class="fas fa-lock" style="font-size:10px;color:#FCA5A5;"></i></div>
                <span style="font-size:10px;color:#9CA3AF;width:58px;flex-shrink:0;text-transform:uppercase;letter-spacing:.04em;">Password</span>
                <span class="cred-val" style="font-size:12px;letter-spacing:.1em;"
                      x-text="showPw ? '{{ addslashes($decPw) }}' : '••••••••'"></span>
                <button class="cred-reveal" @click="showPw=!showPw" title="Show/hide">
                    <i :class="showPw ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
                <button class="cred-copy" onclick="licCopy(this,'{{ addslashes($decPw) }}')" title="Copy"><i class="fas fa-copy"></i></button>
            </div>
            @endif
            @if($sa->account_id)
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:20px;text-align:center;flex-shrink:0;"><i class="fas fa-fingerprint" style="font-size:10px;color:#6EE7B7;"></i></div>
                <span style="font-size:10px;color:#9CA3AF;width:58px;flex-shrink:0;text-transform:uppercase;letter-spacing:.04em;">Acct ID</span>
                <span class="cred-val" style="font-size:12px;">{{ $sa->account_id }}</span>
                <button class="cred-copy" onclick="licCopy(this,'{{ addslashes($sa->account_id) }}')" title="Copy"><i class="fas fa-copy"></i></button>
            </div>
            @endif
        </div>
        @endif

        {{-- Footer: URL + notes ── --}}
        @if($sa->page_url || $sa->notes)
        <div style="padding:12px 20px;background:#FAFAFA;flex:1;display:flex;flex-direction:column;gap:8px;">
            @if($sa->page_url)
            <a href="{{ $sa->page_url }}" target="_blank" rel="noopener"
               style="display:inline-flex;align-items:center;gap:7px;padding:7px 14px;background:#EEF2FF;color:#4F46E5;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;width:fit-content;transition:background .15s;"
               onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='#EEF2FF'">
                <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i> Open Page
            </a>
            @endif
            @if($sa->notes)
            <p style="font-size:12px;color:#6B7280;margin:0;line-height:1.6;padding:8px 10px;background:#fff;border-radius:8px;border-left:3px solid {{ $pi['color'] }};">{{ $sa->notes }}</p>
            @endif
        </div>
        @endif

    </div>
    @endforeach
</div>

@endif

</div>{{-- /social section --}}

</div>{{-- end x-data --}}

{{-- ══════════════════════════════════════════════ --}}
{{-- DETAIL MODAL                                   --}}
{{-- ══════════════════════════════════════════════ --}}
<div id="lic-modal-overlay"
     onclick="if(event.target===this)closeLicModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:20px;">
    <div id="lic-modal-box"
         style="background:#fff;border-radius:20px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,.2);position:relative;">

        <button onclick="closeLicModal()"
                style="position:absolute;top:16px;right:16px;border:none;background:#F3F4F6;color:#6B7280;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;z-index:1;">
            <i class="fas fa-xmark"></i>
        </button>

        {{-- Header band --}}
        <div style="padding:28px 28px 20px;border-bottom:1px solid #F3F4F6;display:flex;align-items:flex-start;gap:16px;">
            <div id="lm-logo" style="width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"></div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                    <h2 id="lm-name" style="font-size:18px;font-weight:800;color:#111827;margin:0;"></h2>
                    <span id="lm-status-badge" style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;"></span>
                </div>
                <p id="lm-vendor" style="font-size:13px;color:#9CA3AF;margin:0 0 6px;"></p>
                <span id="lm-cat-badge" style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;"></span>
            </div>
        </div>

        <div style="padding:20px 28px;display:flex;flex-direction:column;gap:16px;">

            {{-- Renewal --}}
            <div id="lm-renewal-wrap" style="display:none;border-radius:12px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <p id="lm-renewal-label" style="font-size:10px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;margin:0 0 3px;"></p>
                    <p id="lm-renewal-date" style="font-size:14px;font-weight:700;color:#374151;margin:0;"></p>
                </div>
                <div style="text-align:right;">
                    <p id="lm-days-num" style="font-size:28px;font-weight:900;margin:0;line-height:1;"></p>
                    <p id="lm-days-label" style="font-size:10px;color:#9CA3AF;margin:0;"></p>
                </div>
            </div>

            {{-- Meta pills: Type / Since / Billing / Open --}}
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <div style="background:#F9FAFB;border-radius:10px;padding:10px 16px;text-align:center;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;font-weight:500;">Type</p>
                    <p id="lm-type" style="font-size:13px;font-weight:700;color:#374151;margin:0;"></p>
                </div>
                <div id="lm-since-pill" style="display:none;background:#F9FAFB;border-radius:10px;padding:10px 16px;text-align:center;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;font-weight:500;">Since</p>
                    <p id="lm-since" style="font-size:13px;font-weight:700;color:#374151;margin:0;"></p>
                </div>
                <div id="lm-billing-pill" style="display:none;background:#F9FAFB;border-radius:10px;padding:10px 16px;text-align:center;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;font-weight:500;">Billing</p>
                    <p id="lm-billing" style="font-size:13px;font-weight:700;color:#374151;margin:0;"></p>
                </div>
                <div id="lm-open-pill" style="display:none;flex:1;">
                    <a id="lm-open-link" href="#" target="_blank"
                       style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;background:#EEF2FF;border-radius:10px;padding:10px 16px;text-decoration:none;"
                       onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='#EEF2FF'">
                        <i class="fas fa-arrow-up-right-from-square" style="font-size:14px;color:#4F46E5;margin-bottom:4px;"></i>
                        <span style="font-size:12px;font-weight:700;color:#4F46E5;">Open Tool</span>
                    </a>
                </div>
            </div>

            {{-- Assigned info --}}
            <div id="lm-assigned-wrap" style="display:none;background:#F9FAFB;border-radius:10px;padding:12px 16px;display:flex;gap:20px;flex-wrap:wrap;">
                <div id="lm-assigned-by-wrap" style="display:none;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;font-weight:500;text-transform:uppercase;letter-spacing:.04em;">Assigned by</p>
                    <p id="lm-assigned-by" style="font-size:13px;font-weight:700;color:#374151;margin:0;"></p>
                </div>
                <div id="lm-assigned-at-wrap" style="display:none;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;font-weight:500;text-transform:uppercase;letter-spacing:.04em;">Assigned on</p>
                    <p id="lm-assigned-at" style="font-size:13px;font-weight:700;color:#374151;margin:0;"></p>
                </div>
            </div>

            {{-- Credentials --}}
            <div id="lm-creds-wrap" style="display:none;padding:14px;background:#F8FAFF;border:1px solid #E0E7FF;border-radius:12px;">
                <p style="font-size:11px;font-weight:700;color:#4F46E5;margin:0 0 12px;text-transform:uppercase;letter-spacing:.05em;">
                    <i class="fas fa-key" style="font-size:10px;margin-right:4px;"></i> Login Credentials
                </p>
                <div id="lm-user-wrap" style="display:none;margin-bottom:10px;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;font-weight:500;">Username / Email</p>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <span id="lm-username" class="cred-val"></span>
                        <button class="cred-copy" onclick="licCopy(this,document.getElementById('lm-username').textContent)" title="Copy"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                <div id="lm-pw-wrap" style="display:none;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;font-weight:500;">Password</p>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <span id="lm-pw" class="cred-val" style="letter-spacing:.12em;">••••••••</span>
                        <button class="cred-reveal" onclick="licReveal('lm-pw','lm-pw-icon')" title="Show/hide"><i class="fas fa-eye" id="lm-pw-icon"></i></button>
                        <button class="cred-copy" onclick="licCopy(this,document.getElementById('lm-pw').dataset.pw)" title="Copy"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div id="lm-notes-wrap" style="display:none;padding:12px 14px;background:#F8FAFC;border-radius:10px;">
                <p style="font-size:11px;font-weight:600;color:#6B7280;margin:0 0 6px;text-transform:uppercase;letter-spacing:.04em;">
                    <i class="fas fa-note-sticky" style="margin-right:4px;font-size:10px;"></i> Notes from Admin
                </p>
                <p id="lm-notes" style="font-size:13px;color:#374151;margin:0;line-height:1.6;white-space:pre-wrap;"></p>
            </div>

            {{-- Attachments --}}
            <div id="lm-atts-wrap" style="display:none;">
                <p style="font-size:11px;font-weight:700;color:#6B7280;margin:0 0 8px;text-transform:uppercase;letter-spacing:.05em;">
                    <i class="fas fa-paperclip" style="font-size:10px;margin-right:4px;"></i> Files
                </p>
                <div id="lm-atts-list" style="display:flex;flex-direction:column;gap:6px;"></div>
            </div>

        </div>
    </div>
</div>

<script>
function openLicModal(id) {
    const d = LIC_DATA[id];
    if (!d) return;

    // Logo
    const logoEl = document.getElementById('lm-logo');
    logoEl.style.background = d.catBg;
    logoEl.innerHTML = d.logoUrl
        ? `<img src="${d.logoUrl}" style="width:100%;height:100%;object-fit:contain;padding:8px;">`
        : `<i class="fas fa-${d.catIcon}" style="font-size:22px;color:${d.catColor};"></i>`;

    document.getElementById('lm-name').textContent = d.name;
    const vendorEl = document.getElementById('lm-vendor');
    vendorEl.textContent = d.vendor; vendorEl.style.display = d.vendor ? '' : 'none';

    const sc = { expired:{bg:'#FEE2E2',color:'#DC2626',icon:'fa-triangle-exclamation',label:'Expired'}, expiring_soon:{bg:'#FEF3C7',color:'#D97706',icon:'fa-clock',label:'Expiring Soon'}, active:{bg:'#ECFDF5',color:'#16A34A',icon:'fa-circle-check',label:'Active'} }[d.status] || {bg:'#ECFDF5',color:'#16A34A',icon:'fa-circle-check',label:'Active'};

    const sb = document.getElementById('lm-status-badge');
    sb.style.background = sc.bg; sb.style.color = sc.color;
    sb.innerHTML = `<i class="fas ${sc.icon}" style="font-size:9px;"></i> ${sc.label}`;

    const cb = document.getElementById('lm-cat-badge');
    cb.style.background = d.catBg; cb.style.color = d.catColor;
    cb.innerHTML = `<i class="fas fa-${d.catIcon}" style="font-size:10px;"></i> ${d.catLabel}`;

    // Renewal
    const rw = document.getElementById('lm-renewal-wrap');
    if (d.renewalDate) {
        rw.style.display = 'flex';
        rw.style.background = d.status==='expired'?'#FFF5F5':(d.status==='expiring_soon'?'#FFFBEB':'#F0FDF4');
        rw.style.border     = '1px solid '+(d.status==='expired'?'#FCA5A5':(d.status==='expiring_soon'?'#FDE68A':'#A7F3D0'));
        document.getElementById('lm-renewal-label').textContent = d.status==='expired'?'Expired':'Renews';
        document.getElementById('lm-renewal-date').textContent  = d.renewalDate;
        document.getElementById('lm-days-num').textContent = d.days!==null?Math.abs(d.days):'—';
        document.getElementById('lm-days-num').style.color = sc.color;
        document.getElementById('lm-days-label').textContent = d.status==='expired'?'days ago':'days left';
    } else { rw.style.display = 'none'; }

    // Meta pills
    document.getElementById('lm-type').textContent = d.type;
    const sinceP = document.getElementById('lm-since-pill');
    if (d.since) { sinceP.style.display=''; document.getElementById('lm-since').textContent=d.since; }
    else { sinceP.style.display='none'; }

    const billingP = document.getElementById('lm-billing-pill');
    if (d.billingCycle && d.cost) {
        billingP.style.display='';
        document.getElementById('lm-billing').textContent = d.billingCycle+' · '+d.cost;
    } else if (d.billingCycle) {
        billingP.style.display='';
        document.getElementById('lm-billing').textContent = d.billingCycle;
    } else { billingP.style.display='none'; }

    const openP = document.getElementById('lm-open-pill');
    if (d.website) { openP.style.display=''; document.getElementById('lm-open-link').href=d.website; }
    else { openP.style.display='none'; }

    // Assigned info
    const assignedWrap = document.getElementById('lm-assigned-wrap');
    const assignedByW  = document.getElementById('lm-assigned-by-wrap');
    const assignedAtW  = document.getElementById('lm-assigned-at-wrap');
    if (d.assignedBy || d.assignedAt) {
        assignedWrap.style.display='flex';
        if (d.assignedBy) { assignedByW.style.display=''; document.getElementById('lm-assigned-by').textContent=d.assignedBy; }
        else { assignedByW.style.display='none'; }
        if (d.assignedAt) { assignedAtW.style.display=''; document.getElementById('lm-assigned-at').textContent=d.assignedAt; }
        else { assignedAtW.style.display='none'; }
    } else { assignedWrap.style.display='none'; }

    // Credentials
    const credsWrap=document.getElementById('lm-creds-wrap'), userWrap=document.getElementById('lm-user-wrap'), pwWrap=document.getElementById('lm-pw-wrap');
    if (d.username||d.password) {
        credsWrap.style.display='';
        if (d.username) { userWrap.style.display=''; document.getElementById('lm-username').textContent=d.username; }
        else { userWrap.style.display='none'; }
        if (d.password) {
            pwWrap.style.display='';
            const pwEl=document.getElementById('lm-pw');
            pwEl.dataset.pw=d.password; pwEl.textContent='••••••••';
            document.getElementById('lm-pw-icon').className='fas fa-eye';
        } else { pwWrap.style.display='none'; }
    } else { credsWrap.style.display='none'; }

    // Notes
    const notesWrap=document.getElementById('lm-notes-wrap');
    if (d.notes) {
        notesWrap.style.display='';
        notesWrap.style.borderLeft='3px solid '+d.catColor;
        document.getElementById('lm-notes').textContent=d.notes;
    } else { notesWrap.style.display='none'; }

    // Attachments
    const attsWrap=document.getElementById('lm-atts-wrap'), attsList=document.getElementById('lm-atts-list');
    if (d.attachments&&d.attachments.length) {
        attsWrap.style.display='';
        attsList.innerHTML=d.attachments.map(a=>`
            <a href="${a.url}" target="_blank" class="attach-row">
                <i class="fas ${a.icon}" style="font-size:15px;color:#4F46E5;flex-shrink:0;width:18px;text-align:center;"></i>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${a.filename}</p>
                    ${a.comment?`<p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;">${a.comment}</p>`:''}
                </div>
                <span style="font-size:10px;color:#9CA3AF;flex-shrink:0;">${a.size}</span>
                <i class="fas fa-download" style="font-size:11px;color:#4F46E5;flex-shrink:0;"></i>
            </a>`).join('');
    } else { attsWrap.style.display='none'; }

    document.getElementById('lic-modal-overlay').style.display='flex';
    document.body.style.overflow='hidden';
}

function closeLicModal() {
    document.getElementById('lic-modal-overlay').style.display='none';
    document.body.style.overflow='';
}

function resetLicFilters() {
    const comp = document.querySelector('[x-data]').__x.$data;
    comp.search = '';
    comp.statusFilter = 'all';
    comp.categoryFilter = 'all';
}

document.addEventListener('keydown', e => { if(e.key==='Escape') closeLicModal(); });

let _licSortCol=null, _licSortAsc=true;

function licSort(col, thEl) {
    const tbody=document.getElementById('lic-table-body');
    if (!tbody) return;
    _licSortAsc = (_licSortCol===col) ? !_licSortAsc : true;
    _licSortCol = col;
    const numeric=['days','files','status'];
    const rows=Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a,b)=>{
        const va=a.dataset[col]??'', vb=b.dataset[col]??'';
        let cmp=numeric.includes(col)?parseFloat(va)-parseFloat(vb):va.localeCompare(vb);
        return _licSortAsc?cmp:-cmp;
    });
    rows.forEach(r=>tbody.appendChild(r));
    document.querySelectorAll('.lic-table th.sortable').forEach(th=>th.classList.remove('active'));
    document.querySelectorAll('.lic-sort-icon').forEach(i=>{i.className='fas fa-sort lic-sort-icon';});
    thEl.classList.add('active');
    const icon=thEl.querySelector('.lic-sort-icon');
    if (icon) icon.className='fas fa-sort-'+(_licSortAsc?'up':'down')+' lic-sort-icon';
}

function licCopy(btn, text) {
    navigator.clipboard.writeText(text).then(()=>{
        const icon=btn.querySelector('i');
        icon.className='fas fa-check'; btn.style.background='#D1FAE5'; btn.style.color='#059669';
        setTimeout(()=>{ icon.className='fas fa-copy'; btn.style.background=''; btn.style.color=''; },1800);
    });
}

function licReveal(elId, iconId) {
    const el=document.getElementById(elId), icon=document.getElementById(iconId);
    if (el.textContent.includes('•')) { el.textContent=el.dataset.pw; icon.className='fas fa-eye-slash'; }
    else { el.textContent='••••••••'; icon.className='fas fa-eye'; }
}
</script>
@endsection

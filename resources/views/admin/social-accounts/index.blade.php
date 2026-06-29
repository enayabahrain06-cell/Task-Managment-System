@extends('layouts.app')
@section('title', 'Social Media Accounts')

@section('content')
<style>
/* ── Credential helpers ── */
.sa-cred-val  { font-size:12px; font-weight:600; color:#111827; font-family:monospace; background:#F3F4F6; border-radius:6px; padding:4px 8px; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sa-cred-btn  { border:none; border-radius:6px; padding:4px 8px; cursor:pointer; font-size:10px; flex-shrink:0; transition:background .15s; }
.sa-copy-btn  { background:#EEF2FF; color:#4F46E5; }
.sa-copy-btn:hover  { background:#C7D2FE; }
.sa-reveal-btn { background:#F3F4F6; color:#6B7280; }
.sa-reveal-btn:hover { background:#E5E7EB; }
/* ── Platform filter tabs ── */
.sa-plat-tab { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; cursor:pointer; border:1.5px solid #E5E7EB; background:#fff; transition:all .15s; text-decoration:none; color:#374151; white-space:nowrap; }
.sa-plat-tab.active, .sa-plat-tab:hover { border-color:#6366F1; background:#EEF2FF; color:#4F46E5; }
/* ── Stat cards (matches social-budget page) ── */
.sa-stat { background:#fff; border-radius:12px; border:1px solid #F0F0F0; box-shadow:0 1px 4px rgba(0,0,0,.04); padding:16px 20px; display:flex; align-items:center; gap:14px; cursor:pointer; transition:box-shadow .15s; }
.sa-stat:hover { box-shadow:0 4px 16px rgba(0,0,0,.09); }
.sa-stat.active-filter { box-shadow:0 0 0 3px rgba(99,102,241,.15); border-color:#C7D2FE; }
.sa-stat-icon { width:40px; height:40px; border-radius:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:16px; }
.sa-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px) { .sa-stats-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:500px) { .sa-stats-grid { grid-template-columns:repeat(2,1fr); gap:8px; } }
/* ── View toggle ── */
.sa-view-toggle { display:flex; gap:2px; background:#F3F4F6; border-radius:10px; padding:3px; }
.sa-view-btn { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:8px; font-size:12px; font-weight:600; border:none; cursor:pointer; transition:all .15s; background:transparent; color:#6B7280; }
.sa-view-btn.active { background:#fff; color:#4F46E5; box-shadow:0 1px 4px rgba(0,0,0,.08); }
/* ── Cards — Swiss style ── */
.sa-card { background:#fff; border:1px solid #E8EAED; border-radius:12px; overflow:hidden; display:flex; flex-direction:column; transition:box-shadow .18s, transform .18s; }
.sa-card:hover { box-shadow:0 6px 24px rgba(0,0,0,.1); transform:translateY(-2px); }
.sa-cred-label { font-size:9px; font-weight:700; color:#9CA3AF; text-transform:uppercase; letter-spacing:.1em; width:68px; flex-shrink:0; }
.sa-rule { height:1px; background:#F2F3F5; margin:0 16px; }
/* ── Table ── */
.sa-table { width:100%; border-collapse:separate; border-spacing:0; }
.sa-table th { background:#F9FAFB; font-size:11px; font-weight:700; color:#6B7280; text-transform:uppercase; letter-spacing:.05em; padding:10px 16px; border-bottom:1.5px solid #E5E7EB; text-align:left; white-space:nowrap; }
.sa-table th:first-child { border-radius:10px 0 0 0; }
.sa-table th:last-child  { border-radius:0 10px 0 0; }
.sa-table td { padding:11px 16px; border-bottom:1px solid #F3F4F6; font-size:13px; color:#374151; vertical-align:middle; background:#fff; }
.sa-table tr:last-child td { border-bottom:none; }
.sa-table tbody tr:hover td { background:#F9FAFB; }
/* ── Animations ── */
@keyframes sa-in { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }
.sa-card { animation:sa-in .22s ease both; }
[x-cloak] { display:none !important; }
/* ── Toolbar ── */
.sa-toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; background:#fff; border:1.5px solid #E5E7EB; border-radius:14px; padding:10px 14px; box-shadow:0 1px 6px rgba(0,0,0,.04); }
.sa-search { border:1.5px solid #E5E7EB; border-radius:10px; padding:7px 13px 7px 34px; font-size:13px; color:#111827; outline:none; width:200px; transition:border-color .15s; background:#fff; }
.sa-search:focus { border-color:#4F46E5; }
@media(max-width:700px) { .sa-cards-grid { grid-template-columns:1fr !important; } }
</style>

@php
$_saColors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#0EA5E9','#14B8A6'];
$_customerData = $customers->map(function($c) use ($_saColors) {
    return [
        'id'       => $c->id,
        'name'     => $c->name,
        'company'  => $c->company ?? '',
        'logo'     => $c->logo ? \Illuminate\Support\Facades\Storage::url($c->logo) : null,
        'initials' => strtoupper(substr($c->name, 0, 1)),
        'color'    => $_saColors[$c->id % 8],
    ];
})->values();
@endphp

{{-- JS globals needed by _form.blade.php and edit modal --}}
<script>
const allFormUsers    = @json($allUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->values());
const saFormPlatforms = @json(collect($platforms)->map(fn($p, $k) => array_merge($p, ['key' => $k]))->values());
const saFormCustomers = @json($_customerData);
let formPreselectedUserIds = [];
</script>

<div x-data="{
    createModal: false,
    editModal: false,
    deleteModal: false,
    editData: {},
    deleteId: null,
    deleteName: '',
    view: localStorage.getItem('sa_view') || 'cards',
    search: '',
    statusFilter: 'all',
    setView(v) { this.view = v; localStorage.setItem('sa_view', v); },
    openEdit(d) {
        window.formPreselectedUserIds = d.user_ids ?? [];
        this.editData = d;
        this.editModal = true;
    },
    openDelete(id, name) { this.deleteId = id; this.deleteName = name; this.deleteModal = true; },
    openCreate() { window.formPreselectedUserIds = []; this.createModal = true; },
    matchesCard(name, status) {
        if (this.statusFilter !== 'all' && status !== this.statusFilter) return false;
        if (this.search.trim()) {
            const s = this.search.toLowerCase().trim();
            if (!name.toLowerCase().includes(s)) return false;
        }
        return true;
    }
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

{{-- ══════════════════════ BANNER HEADER ══════════════════════ --}}
<div style="display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:48px;height:48px;border-radius:14px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-share-nodes" style="font-size:20px;color:#4F46E5;"></i>
        </div>
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0;letter-spacing:-.3px;">Social Accounts</h1>
            <p style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">
                {{ $stats['total'] }} {{ Str::plural('account', $stats['total']) }}
                @if($byPlatform->count())
                across {{ $byPlatform->count() }} {{ Str::plural('platform', $byPlatform->count()) }}
                @endif
            </p>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <a href="{{ route('admin.social-accounts.export.pdf') }}"
           style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:#fff;color:#374151;border:1.5px solid #E5E7EB;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:border-color .15s,box-shadow .15s;"
           onmouseover="this.style.borderColor='#4F46E5';this.style.color='#4F46E5'" onmouseout="this.style.borderColor='#E5E7EB';this.style.color='#374151'">
            <i class="fas fa-file-pdf" style="font-size:13px;color:#EF4444;"></i> Export PDF
        </a>
        <button @click="openCreate()"
                style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(99,102,241,.3);transition:box-shadow .15s;"
                onmouseover="this.style.boxShadow='0 4px 16px rgba(99,102,241,.4)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(99,102,241,.3)'">
            <i class="fas fa-plus" style="font-size:11px;"></i> Add Account
        </button>
    </div>
</div>

{{-- ══════════════════════ STAT CARDS ══════════════════════ --}}
<div class="sa-stats-grid">
    <div class="sa-stat" :class="statusFilter==='all' ? 'active-filter' : ''" @click="statusFilter='all'">
        <div class="sa-stat-icon" style="background:#EEF2FF;">
            <i class="fas fa-share-nodes" style="color:#4F46E5;"></i>
        </div>
        <div>
            <p style="font-size:22px;font-weight:800;color:#111827;margin:0;">{{ $stats['total'] }}</p>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Total Accounts</p>
        </div>
    </div>
    <div class="sa-stat" :class="statusFilter==='active' ? 'active-filter' : ''" @click="statusFilter='active'">
        <div class="sa-stat-icon" style="background:#D1FAE5;">
            <i class="fas fa-circle-check" style="color:#059669;"></i>
        </div>
        <div>
            <p style="font-size:22px;font-weight:800;color:#059669;margin:0;">{{ $stats['active'] }}</p>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Active</p>
        </div>
    </div>
    <div class="sa-stat" :class="statusFilter==='inactive' ? 'active-filter' : ''" @click="statusFilter='inactive'">
        <div class="sa-stat-icon" style="background:#F3F4F6;">
            <i class="fas fa-moon" style="color:#6B7280;"></i>
        </div>
        <div>
            <p style="font-size:22px;font-weight:800;color:#6B7280;margin:0;">{{ $stats['inactive'] }}</p>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Inactive</p>
        </div>
    </div>
    <div class="sa-stat" :class="statusFilter==='suspended' ? 'active-filter' : ''" @click="statusFilter='suspended'">
        <div class="sa-stat-icon" style="background:#FEE2E2;">
            <i class="fas fa-triangle-exclamation" style="color:#DC2626;"></i>
        </div>
        <div>
            <p style="font-size:22px;font-weight:800;color:#DC2626;margin:0;">{{ $stats['suspended'] }}</p>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Suspended</p>
        </div>
    </div>
</div>

{{-- ══════════════════════ TOOLBAR ══════════════════════ --}}
<div class="sa-toolbar">
    {{-- View toggle (left) --}}
    <div class="sa-view-toggle" style="flex-shrink:0;">
        <button class="sa-view-btn" :class="view==='table' ? 'active' : ''" @click="setView('table')">
            <i class="fas fa-table-list" style="font-size:11px;"></i> Table
        </button>
        <button class="sa-view-btn" :class="view==='cards' ? 'active' : ''" @click="setView('cards')">
            <i class="fas fa-grip" style="font-size:11px;"></i> Cards
        </button>
    </div>

    <div style="width:1px;height:24px;background:#E5E7EB;flex-shrink:0;"></div>

    {{-- Platform filters ── server-side links ── --}}
    <div style="display:flex;flex-wrap:wrap;gap:5px;flex:1;min-width:0;">
        <a href="{{ route('admin.social-accounts.index', array_filter(['customer' => request('customer')])) }}"
           class="sa-plat-tab {{ !request('platform') ? 'active' : '' }}"
           title="All platforms" style="padding:5px 10px;">
            <i class="fas fa-border-all" style="font-size:12px;"></i>
        </a>
        @foreach($platforms as $key => $p)
        @if(isset($byPlatform[$key]))
        <a href="{{ route('admin.social-accounts.index', array_filter(['platform' => $key, 'customer' => request('customer')])) }}"
           class="sa-plat-tab {{ request('platform') === $key ? 'active' : '' }}"
           title="{{ $p['label'] }} ({{ $byPlatform[$key]->count() }})"
           style="padding:5px 10px;{{ request('platform') === $key ? 'border-color:'.$p['color'].';background:'.$p['bg'].';color:'.$p['color'].';' : '' }}">
            <i class="fab {{ $p['icon'] }}" style="font-size:14px;{{ request('platform') === $key ? 'color:'.$p['color'].';' : '' }}"></i>
        </a>
        @endif
        @endforeach
    </div>

    @if($customers->count())
    {{-- Custom customer picker with logos ── --}}
    <div style="flex-shrink:0;position:relative;"
         x-data="{
             open: false,
             selectedId: {{ request('customer') ?: 'null' }},
             platform: '{{ request('platform') }}',
             get current() { return this.selectedId ? saFormCustomers.find(c => c.id === this.selectedId) ?? null : null; },
             pick(id) {
                 this.selectedId = id;
                 this.open = false;
                 const base = '{{ route('admin.social-accounts.index') }}';
                 const params = new URLSearchParams();
                 if (this.platform) params.set('platform', this.platform);
                 if (id) params.set('customer', id);
                 window.location = base + (params.toString() ? '?' + params.toString() : '');
             }
         }"
         @keydown.escape.window="open=false">

        {{-- Trigger button --}}
        <button type="button" @click="open=!open"
                style="display:inline-flex;align-items:center;gap:8px;padding:6px 10px 6px 7px;border:1.5px solid;border-radius:10px;font-size:12px;font-weight:600;cursor:pointer;background:#fff;transition:all .15s;max-width:180px;"
                :style="{'border-color': current ? '#6366F1' : '#E5E7EB', 'color': current ? '#4F46E5' : '#374151'}"
            {{-- Avatar --}}
            <span style="width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"
                  :style="current ? {'background': current.color} : {'background': '#F3F4F6'}">
                <template x-if="current && current.logo">
                    <img :src="current.logo" style="width:100%;height:100%;object-fit:contain;">
                </template>
                <template x-if="current && !current.logo">
                    <span x-text="current.initials" style="font-size:10px;font-weight:700;color:#fff;"></span>
                </template>
                <template x-if="!current">
                    <i class="fas fa-building" style="font-size:10px;color:#9CA3AF;"></i>
                </template>
            </span>
            <span x-text="current ? current.name : 'All Customers'" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;min-width:0;"></span>
            <i class="fas fa-chevron-down" style="font-size:9px;opacity:.5;transition:transform .15s;" :style="{'transform': open ? 'rotate(180deg)' : 'none'}"></i>
        </button>

        {{-- Dropdown --}}
        <div x-show="open" @click.outside="open=false" x-cloak
             style="position:absolute;top:calc(100% + 6px);left:0;min-width:200px;background:#fff;border:1.5px solid #E5E7EB;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:200;overflow:hidden;">
            {{-- All option --}}
            <button type="button" @click="pick(null)"
                    style="width:100%;display:flex;align-items:center;gap:10px;padding:9px 12px;border:none;cursor:pointer;text-align:left;transition:background .1s;"
                    :style="{'background': !selectedId ? '#EEF2FF' : 'transparent'}"
                    @mouseover="if(selectedId) $el.style.background='#F9FAFB'" @mouseout="$el.style.background=!selectedId?'#EEF2FF':'transparent'">
                <span style="width:26px;height:26px;border-radius:7px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-building" style="font-size:11px;color:#9CA3AF;"></i>
                </span>
                <span style="font-size:12.5px;color:#6B7280;font-style:italic;">All Customers</span>
                <i x-show="!selectedId" class="fas fa-check" style="font-size:10px;color:#4F46E5;margin-left:auto;"></i>
            </button>
            <div style="height:1px;background:#F3F4F6;"></div>
            <div style="max-height:220px;overflow-y:auto;">
                <template x-for="c in saFormCustomers" :key="c.id">
                    <button type="button" @click="pick(c.id)"
                            style="width:100%;display:flex;align-items:center;gap:10px;padding:9px 12px;border:none;cursor:pointer;text-align:left;transition:background .1s;"
                            :style="{'background': selectedId===c.id ? '#EEF2FF' : 'transparent'}"
                            @mouseover="if(selectedId!==c.id) $el.style.background='#F9FAFB'" @mouseout="$el.style.background=selectedId===c.id?'#EEF2FF':'transparent'">
                        <span style="width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"
                              :style="{'background': c.color}">
                            <template x-if="c.logo">
                                <img :src="c.logo" style="width:100%;height:100%;object-fit:contain;">
                            </template>
                            <template x-if="!c.logo">
                                <span x-text="c.initials" style="font-size:11px;font-weight:700;color:#fff;"></span>
                            </template>
                        </span>
                        <div style="flex:1;min-width:0;">
                            <div x-text="c.name" style="font-size:12.5px;font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
                            <div x-show="c.company" x-text="c.company" style="font-size:10px;color:#9CA3AF;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
                        </div>
                        <i x-show="selectedId===c.id" class="fas fa-check" style="font-size:10px;color:#4F46E5;flex-shrink:0;"></i>
                    </button>
                </template>
            </div>
        </div>
    </div>
    @endif

    {{-- Search (right) --}}
    <div style="position:relative;flex-shrink:0;margin-left:auto;">
        <i class="fas fa-magnifying-glass" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:11px;pointer-events:none;"></i>
        <input x-model="search" type="text" placeholder="Search accounts…" class="sa-search">
    </div>
</div>

{{-- ══════════════════════ CONTENT ══════════════════════ --}}
@if($accounts->isEmpty())
<div style="background:#fff;border:1.5px dashed #E5E7EB;border-radius:16px;padding:64px 24px;text-align:center;">
    <div style="width:72px;height:72px;border-radius:22px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
        <i class="fas fa-share-nodes" style="font-size:30px;color:#6366F1;"></i>
    </div>
    <h3 style="font-size:17px;font-weight:700;color:#111827;margin:0 0 6px;">No accounts yet</h3>
    <p style="font-size:13px;color:#9CA3AF;margin:0 0 22px;">Add your first social media account to get started.</p>
    <button @click="openCreate()"
            style="display:inline-flex;align-items:center;gap:7px;padding:10px 22px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:11px;font-size:13px;font-weight:600;cursor:pointer;">
        <i class="fas fa-plus" style="font-size:11px;"></i> Add Account
    </button>
</div>
@else

{{-- ════════════ CARDS VIEW ════════════ --}}
<div x-show="view==='cards'">
    @foreach($platforms as $platformKey => $platformInfo)
    @if(isset($byPlatform[$platformKey]))
    @php $group = $byPlatform[$platformKey]; @endphp

    {{-- Platform section header --}}
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;margin-top:{{ $loop->first ? '0' : '32px' }};">
        <div style="width:32px;height:32px;border-radius:9px;background:{{ $platformInfo['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fab {{ $platformInfo['icon'] }}" style="font-size:16px;color:{{ $platformInfo['color'] }};"></i>
        </div>
        <h2 style="font-size:14px;font-weight:700;color:#111827;margin:0;">{{ $platformInfo['label'] }}</h2>
        <span style="background:#F3F4F6;color:#6B7280;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;">{{ $group->count() }}</span>
        <div style="flex:1;height:1px;background:#F3F4F6;"></div>
    </div>

    <div class="sa-cards-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:14px;margin-bottom:8px;">
        @foreach($group as $idx => $account)
        @php
            $sc            = $account->status_color;
            $pi            = $account->platform_info;
            $assignedUsers = $account->users;
            $customer      = $account->customer;
            $hasCreds      = $account->email || $account->username || $account->password || $account->account_id;
            $stDot         = match($account->status) { 'active'=>'#22C55E','inactive'=>'#9CA3AF','suspended'=>'#EF4444', default=>'#9CA3AF' };
            $stPill        = match($account->status) {
                'active'    => ['bg'=>'#F0FDF4','border'=>'#86EFAC','color'=>'#16A34A'],
                'suspended' => ['bg'=>'#FFF1F2','border'=>'#FCA5A5','color'=>'#EF4444'],
                default     => ['bg'=>'#F9FAFB','border'=>'#E5E7EB','color'=>'#6B7280'],
            };
        @endphp
        <div class="sa-card" style="animation-delay:{{ $idx * 0.035 }}s;"
             x-show="matchesCard('{{ addslashes($account->name) }}', '{{ $account->status }}')"
             x-data="{showPw:false}">

            {{-- ── Colored header zone ── --}}
            <div style="padding:14px 16px 13px;background:{{ $pi['bg'] }};border-bottom:1.5px solid {{ $pi['color'] }}25;display:flex;align-items:center;gap:12px;">
                {{-- Platform icon with shadow --}}
                <div style="width:50px;height:50px;border-radius:12px;background:{{ $pi['color'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px {{ $pi['color'] }}45;">
                    <i class="fab {{ $pi['icon'] }}" style="font-size:22px;color:#fff;"></i>
                </div>
                {{-- Name + badges --}}
                <div style="flex:1;min-width:0;">
                    <p style="font-size:15px;font-weight:800;color:#111827;margin:0 0 5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;letter-spacing:-.2px;">{{ $account->name }}</p>
                    <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                        <span style="font-size:9px;font-weight:700;padding:2px 8px;border-radius:20px;background:{{ $pi['color'] }}18;color:{{ $pi['color'] }};text-transform:uppercase;letter-spacing:.07em;border:1px solid {{ $pi['color'] }}28;">{{ $pi['label'] }}</span>
                        @if($customer)
                        <a href="{{ route('admin.customers.show', $customer->id) }}"
                           style="font-size:10px;color:#6366F1;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:110px;">
                            <i class="fas fa-building" style="font-size:7px;"></i> {{ $customer->name }}
                        </a>
                        @elseif($account->username)
                        <span style="font-size:10px;color:#6B7280;font-family:monospace;">&#64;{{ $account->username }}</span>
                        @endif
                    </div>
                </div>
                {{-- Status pill --}}
                <div style="flex-shrink:0;">
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;background:{{ $stPill['bg'] }};border:1px solid {{ $stPill['border'] }};">
                        <span style="width:5px;height:5px;border-radius:50%;background:{{ $stDot }};flex-shrink:0;"></span>
                        <span style="font-size:9px;font-weight:700;color:{{ $stPill['color'] }};text-transform:uppercase;letter-spacing:.08em;">{{ $account->status }}</span>
                    </span>
                </div>
            </div>

            {{-- ── Credentials ── --}}
            @if($hasCreds)
            <div class="sa-rule"></div>
            <div style="padding:10px 16px;display:flex;flex-direction:column;">
                @if($account->email)
                <div style="display:flex;align-items:center;padding:5px 0;border-bottom:1px solid #F7F8FA;">
                    <span class="sa-cred-label">Email</span>
                    <span style="font-size:12px;color:#111827;font-family:monospace;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $account->email }}</span>
                    <button class="sa-cred-btn sa-copy-btn" style="margin-left:6px;" onclick="copyToClipboard('{{ addslashes($account->email) }}', this)" title="Copy"><i class="fas fa-copy"></i></button>
                </div>
                @endif
                @if($account->username && !$account->email)
                <div style="display:flex;align-items:center;padding:5px 0;border-bottom:1px solid #F7F8FA;">
                    <span class="sa-cred-label">Handle</span>
                    <span style="font-size:12px;color:#111827;font-family:monospace;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">&#64;{{ $account->username }}</span>
                    <button class="sa-cred-btn sa-copy-btn" style="margin-left:6px;" onclick="copyToClipboard('{{ addslashes($account->username) }}', this)" title="Copy"><i class="fas fa-copy"></i></button>
                </div>
                @endif
                @if($account->password)
                <div style="display:flex;align-items:center;padding:5px 0;border-bottom:1px solid #F7F8FA;" x-data="pwReveal({{ $account->id }})">
                    <span class="sa-cred-label">Password</span>
                    <span style="font-size:12px;color:#111827;font-family:monospace;flex:1;letter-spacing:.12em;" x-text="revealed ? pw : '••••••••'"></span>
                    <div style="display:flex;align-items:center;gap:4px;margin-left:6px;flex-shrink:0;">
                        <span x-show="revealed" x-text="seconds+'s'" style="font-size:9px;color:#9CA3AF;font-weight:700;min-width:18px;text-align:right;"></span>
                        <button class="sa-cred-btn sa-reveal-btn" @click="toggle()" :title="revealed ? 'Hide' : 'Reveal'">
                            <i :class="loading ? 'fas fa-spinner fa-spin' : (revealed ? 'fas fa-eye-slash' : 'fas fa-eye')"></i>
                        </button>
                        <button class="sa-cred-btn sa-copy-btn" x-show="revealed" @click="copyPw($el)" title="Copy"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                @endif
                @if($account->account_id)
                <div style="display:flex;align-items:center;padding:5px 0;">
                    <span class="sa-cred-label">Acct ID</span>
                    <span style="font-size:12px;color:#111827;font-family:monospace;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $account->account_id }}</span>
                    <button class="sa-cred-btn sa-copy-btn" style="margin-left:6px;" onclick="copyToClipboard('{{ addslashes($account->account_id) }}', this)" title="Copy"><i class="fas fa-copy"></i></button>
                </div>
                @endif
            </div>
            @endif

            {{-- ── Page URL ── --}}
            @if($account->page_url)
            <div class="sa-rule"></div>
            <div style="padding:8px 16px;">
                <a href="{{ $account->page_url }}" target="_blank"
                   style="display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;color:{{ $pi['color'] }};text-decoration:none;text-transform:uppercase;letter-spacing:.06em;">
                    <i class="fas fa-arrow-up-right-from-square" style="font-size:9px;"></i>
                    {{ parse_url($account->page_url, PHP_URL_HOST) ?? 'Open Page' }}
                </a>
            </div>
            @endif

            {{-- ── Notes ── --}}
            @if($account->notes)
            <div class="sa-rule"></div>
            <div style="padding:8px 16px;">
                <p style="font-size:11.5px;color:#6B7280;margin:0;line-height:1.6;">{{ $account->notes }}</p>
            </div>
            @endif

            {{-- ── Footer: access + actions ── --}}
            <div class="sa-rule" style="margin-top:auto;"></div>
            <div style="padding:10px 16px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                {{-- Stacked avatars --}}
                <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                    @if($assignedUsers->count())
                    <div style="display:flex;align-items:center;">
                        @foreach($assignedUsers->take(4) as $au)
                        @php $auc = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6'][$au->id % 5]; @endphp
                        <span title="{{ $au->name }}"
                              style="width:22px;height:22px;border-radius:50%;border:2px solid #fff;background:{{ $auc }};display:inline-flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;color:#fff;{{ $loop->first ? '' : 'margin-left:-6px;' }}">
                            {{ strtoupper(substr($au->name, 0, 1)) }}
                        </span>
                        @endforeach
                        @if($assignedUsers->count() > 4)
                        <span style="width:22px;height:22px;border-radius:50%;border:2px solid #fff;background:#E5E7EB;display:inline-flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;color:#6B7280;margin-left:-6px;">
                            +{{ $assignedUsers->count() - 4 }}
                        </span>
                        @endif
                    </div>
                    <span style="font-size:10px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:80px;">
                        {{ $assignedUsers->count() === 1 ? $assignedUsers->first()->name : $assignedUsers->count().' users' }}
                    </span>
                    @else
                    <span style="font-size:10px;font-weight:600;color:#D1D5DB;text-transform:uppercase;letter-spacing:.06em;">No access</span>
                    @endif
                </div>
                {{-- Actions --}}
                <div style="display:flex;gap:4px;flex-shrink:0;">
                    <button @click="openEdit({
                                id: {{ $account->id }},
                                name: '{{ addslashes($account->name) }}',
                                platform: '{{ $account->platform }}',
                                customer_id: {{ $account->customer_id ?? 'null' }},
                                username: '{{ addslashes($account->username ?? '') }}',
                                email: '{{ addslashes($account->email ?? '') }}',
                                account_id: '{{ addslashes($account->account_id ?? '') }}',
                                page_url: '{{ addslashes($account->page_url ?? '') }}',
                                status: '{{ $account->status }}',
                                notes: {{ json_encode($account->notes ?? '') }},
                                has_password: {{ $account->password ? 'true' : 'false' }},
                                user_ids: {{ json_encode($assignedUsers->pluck('id')->toArray()) }}
                            })"
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:transparent;color:#374151;border:1px solid #E2E4E9;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;text-transform:uppercase;letter-spacing:.05em;transition:all .15s;"
                            onmouseover="this.style.borderColor='#4F46E5';this.style.color='#4F46E5';" onmouseout="this.style.borderColor='#E2E4E9';this.style.color='#374151';">
                        <i class="fas fa-pen" style="font-size:9px;"></i> Edit
                    </button>
                    <button @click="openDelete({{ $account->id }}, '{{ addslashes($account->name) }}')"
                            style="width:28px;height:28px;background:transparent;color:#9CA3AF;border:1px solid #E2E4E9;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;"
                            onmouseover="this.style.borderColor='#DC2626';this.style.color='#DC2626';" onmouseout="this.style.borderColor='#E2E4E9';this.style.color='#9CA3AF';"
                            title="Delete">
                        <i class="fas fa-trash" style="font-size:9px;"></i>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    @endforeach

    {{-- No results (cards) --}}
    <div x-show="search.trim() !== '' || statusFilter !== 'all'" x-cloak style="display:none;"
         x-init="$watch('search', () => {})">
    </div>
</div>{{-- /cards view --}}

{{-- ════════════ TABLE VIEW ════════════ --}}
<div x-show="view==='table'" x-cloak style="background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;overflow:hidden;">
    <table class="sa-table">
        <thead>
            <tr>
                <th>Account</th>
                <th>Customer</th>
                <th>Login</th>
                <th>Password</th>
                <th>Status</th>
                <th>Access</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($platforms as $platformKey => $platformInfo)
            @if(isset($byPlatform[$platformKey]))
            @foreach($byPlatform[$platformKey] as $account)
            @php
                $sc            = $account->status_color;
                $pi            = $account->platform_info;
                $assignedUsers = $account->users;
                $customer      = $account->customer;
                $stDot         = match($account->status) { 'active'=>'#22C55E','inactive'=>'#9CA3AF','suspended'=>'#EF4444', default=>'#9CA3AF' };
            @endphp
            <tr x-show="matchesCard('{{ addslashes($account->name) }}', '{{ $account->status }}')">

                {{-- Account --}}
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:9px;background:{{ $pi['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fab {{ $pi['icon'] }}" style="font-size:15px;color:{{ $pi['color'] }};"></i>
                        </div>
                        <div>
                            <p style="font-size:13px;font-weight:700;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;">{{ $account->name }}</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">{{ $pi['label'] }}</p>
                        </div>
                    </div>
                </td>

                {{-- Customer --}}
                <td>
                    @if($customer)
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="width:22px;height:22px;border-radius:6px;background:#EEF2FF;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-building" style="font-size:9px;color:#6366F1;"></i>
                        </span>
                        <span style="font-size:12px;font-weight:600;color:#4F46E5;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;">{{ $customer->name }}</span>
                    </div>
                    @else
                    <span style="color:#D1D5DB;font-size:12px;">—</span>
                    @endif
                </td>

                {{-- Login --}}
                <td style="max-width:190px;">
                    @if($account->email)
                    <div style="display:flex;align-items:center;gap:5px;">
                        <span style="font-size:12px;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;min-width:0;">{{ $account->email }}</span>
                        <button onclick="copyToClipboard('{{ addslashes($account->email) }}', this)"
                                style="width:20px;height:20px;border:none;border-radius:5px;background:#EEF2FF;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:#6366F1;flex-shrink:0;">
                            <i class="fas fa-copy" style="font-size:8px;"></i>
                        </button>
                    </div>
                    @elseif($account->username)
                    <span style="font-size:12px;color:#374151;font-family:monospace;">@{{ $account->username }}</span>
                    @else
                    <span style="color:#D1D5DB;font-size:12px;">—</span>
                    @endif
                </td>

                {{-- Password --}}
                <td x-data="pwReveal({{ $account->id }})">
                    @if($account->password)
                    <div style="display:flex;align-items:center;gap:4px;">
                        <span x-show="!revealed" style="font-size:12px;color:#374151;font-family:monospace;letter-spacing:.1em;">••••••</span>
                        <span x-show="revealed" x-text="pw" style="font-size:12px;color:#374151;font-family:monospace;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                        <span x-show="revealed" x-text="seconds+'s'" style="font-size:9px;color:#9CA3AF;font-weight:700;"></span>
                        <button @click="toggle()"
                                style="width:20px;height:20px;border:none;border-radius:5px;background:#F3F4F6;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:#9CA3AF;">
                            <i :class="loading ? 'fas fa-spinner fa-spin' : (revealed ? 'fas fa-eye-slash' : 'fas fa-eye')" style="font-size:8px;pointer-events:none;"></i>
                        </button>
                        <button x-show="revealed" @click="copyPw($el)"
                                style="width:20px;height:20px;border:none;border-radius:5px;background:#EEF2FF;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:#6366F1;">
                            <i class="fas fa-copy" style="font-size:8px;"></i>
                        </button>
                    </div>
                    @else
                    <span style="color:#D1D5DB;font-size:12px;">—</span>
                    @endif
                </td>

                {{-- Status --}}
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                        <span style="width:5px;height:5px;border-radius:50%;background:{{ $stDot }};flex-shrink:0;"></span>
                        {{ ucfirst($account->status) }}
                    </span>
                </td>

                {{-- Access --}}
                <td>
                    @if($assignedUsers->count())
                    <div style="display:flex;align-items:center;gap:5px;">
                        <div style="display:flex;align-items:center;">
                            @foreach($assignedUsers->take(4) as $au)
                            @php $ac = ['#4F46E5','#10B981','#F59E0B','#EF4444','#8B5CF6'][$au->id % 5]; @endphp
                            @if($au->avatar)
                            <img src="{{ Storage::url($au->avatar) }}" title="{{ $au->name }}"
                                 style="width:22px;height:22px;border-radius:50%;border:2px solid #fff;object-fit:cover;{{ $loop->first ? '' : 'margin-left:-6px;' }}">
                            @else
                            <span title="{{ $au->name }}"
                                  style="width:22px;height:22px;border-radius:50%;border:2px solid #fff;background:{{ $ac }};display:inline-flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;color:#fff;{{ $loop->first ? '' : 'margin-left:-6px;' }}">
                                {{ strtoupper(substr($au->name, 0, 1)) }}
                            </span>
                            @endif
                            @endforeach
                            @if($assignedUsers->count() > 4)
                            <span style="width:22px;height:22px;border-radius:50%;border:2px solid #fff;background:#E5E7EB;display:inline-flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;color:#6B7280;margin-left:-6px;"
                                  title="{{ $assignedUsers->skip(4)->pluck('name')->join(', ') }}">
                                +{{ $assignedUsers->count() - 4 }}
                            </span>
                            @endif
                        </div>
                        <span style="font-size:11px;color:#6B7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px;"
                              title="{{ $assignedUsers->pluck('name')->join(', ') }}">
                            {{ $assignedUsers->count() === 1 ? $assignedUsers->first()->name : $assignedUsers->count().' users' }}
                        </span>
                    </div>
                    @else
                    <span style="font-size:12px;color:#D1D5DB;">No access</span>
                    @endif
                </td>

                {{-- Actions --}}
                <td style="text-align:right;">
                    <div style="display:inline-flex;gap:5px;">
                        <button @click="openEdit({
                                    id: {{ $account->id }},
                                    name: '{{ addslashes($account->name) }}',
                                    platform: '{{ $account->platform }}',
                                    customer_id: {{ $account->customer_id ?? 'null' }},
                                    username: '{{ addslashes($account->username ?? '') }}',
                                    email: '{{ addslashes($account->email ?? '') }}',
                                    account_id: '{{ addslashes($account->account_id ?? '') }}',
                                    page_url: '{{ addslashes($account->page_url ?? '') }}',
                                    status: '{{ $account->status }}',
                                    notes: {{ json_encode($account->notes ?? '') }},
                                    has_password: {{ $account->password ? 'true' : 'false' }},
                                    user_ids: {{ json_encode($assignedUsers->pluck('id')->toArray()) }}
                                })"
                                style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:#EEF2FF;color:#4F46E5;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;"
                                onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='#EEF2FF'">
                            <i class="fas fa-pen" style="font-size:10px;"></i> Edit
                        </button>
                        <button @click="openDelete({{ $account->id }}, '{{ addslashes($account->name) }}')"
                                style="width:30px;height:30px;background:#FEF2F2;color:#DC2626;border:none;border-radius:7px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"
                                onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'"
                                title="Delete">
                            <i class="fas fa-trash" style="font-size:10px;"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
            @endif
            @endforeach
        </tbody>
    </table>
</div>{{-- /table view --}}

@endif

{{-- ════ CREATE MODAL ════ --}}
<div x-show="createModal" x-cloak style="position:fixed;inset:0;z-index:9999;"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);" @click="createModal=false"></div>
        <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <div style="padding:20px 24px;border-bottom:1.5px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;border-radius:16px 16px 0 0;">
                <div>
                    <h2 style="font-size:17px;font-weight:700;color:#111827;margin:0;">Add Social Account</h2>
                    <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Credentials are stored encrypted</p>
                </div>
                <button @click="createModal=false" style="width:32px;height:32px;border-radius:8px;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.social-accounts.store') }}" style="padding:20px 24px 24px;">
                @csrf
                @include('admin.social-accounts._form', ['account' => null, 'allUsers' => $allUsers, 'platforms' => $platforms, 'saFormCustomers' => $customers])
                <div style="display:flex;gap:10px;margin-top:20px;">
                    <button type="submit" style="flex:1;padding:11px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-plus" style="margin-right:6px;"></i> Add Account
                    </button>
                    <button type="button" @click="createModal=false" style="padding:11px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ════ EDIT MODAL ════ --}}
<div x-show="editModal" x-cloak style="position:fixed;inset:0;z-index:9999;"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);" @click="editModal=false"></div>
        <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <div style="padding:20px 24px;border-bottom:1.5px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;border-radius:16px 16px 0 0;">
                <div>
                    <h2 style="font-size:17px;font-weight:700;color:#111827;margin:0;">Edit Account</h2>
                    <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;" x-text="editData.name"></p>
                </div>
                <button @click="editModal=false" style="width:32px;height:32px;border-radius:8px;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" :action="'/admin/social-accounts/'+editData.id" style="padding:20px 24px 24px;">
                @csrf @method('PUT')
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

                    {{-- Name --}}
                    <div style="grid-column:1/-1;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Account Name *</label>
                        <input type="text" name="name" :value="editData.name" required
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                    </div>

                    {{-- Customer — logo picker --}}
                    <div style="grid-column:1/-1;position:relative;"
                         x-data="{
                             open: false,
                             get selected() { return editData.customer_id ?? null; },
                             set selected(v) { editData.customer_id = v; },
                             customers: saFormCustomers,
                             get current() { return this.selected ? this.customers.find(c => c.id === this.selected) ?? null : null; },
                             search: '',
                             get filtered() {
                                 const q = this.search.toLowerCase();
                                 return this.customers.filter(c => !q || c.name.toLowerCase().includes(q) || c.company.toLowerCase().includes(q));
                             }
                         }">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                            Customer
                            <span style="font-size:11px;font-weight:400;color:#9CA3AF;"> — which client owns this account</span>
                        </label>
                        <input type="hidden" name="customer_id" :value="selected ?? ''">
                        <button type="button" @click="open=!open; if(open) $nextTick(() => $refs.ecsearch.focus())" @keydown.escape.window="open=false"
                                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;display:flex;align-items:center;gap:10px;cursor:pointer;text-align:left;"
                                :style="{'border-color': open ? '#6366F1' : '#E5E7EB'}">
                            <span style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"
                                  :style="current ? {'background': current.color} : {'background': '#F3F4F6'}">
                                <template x-if="current && current.logo">
                                    <img :src="current.logo" style="width:100%;height:100%;object-fit:contain;">
                                </template>
                                <template x-if="current && !current.logo">
                                    <span x-text="current.initials" style="font-size:12px;font-weight:700;color:#fff;"></span>
                                </template>
                                <template x-if="!current">
                                    <i class="fas fa-building" style="font-size:12px;color:#9CA3AF;"></i>
                                </template>
                            </span>
                            <span style="flex:1;font-size:13px;" :style="{'color': current ? '#111827' : '#9CA3AF'}"
                                  x-text="current ? current.name + (current.company ? ' — '+current.company : '') : '— No customer / Internal —'"></span>
                            <i class="fas fa-chevron-down" style="font-size:10px;color:#9CA3AF;transition:transform .15s;"
                               :style="{'transform': open ? 'rotate(180deg)' : 'none'}"></i>
                        </button>
                        <div x-show="open" @click.outside="open=false" x-cloak
                             style="position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1.5px solid #E5E7EB;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.12);z-index:300;overflow:hidden;">
                            <div style="padding:8px 10px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:7px;background:#FAFAFA;">
                                <i class="fas fa-magnifying-glass" style="font-size:11px;color:#9CA3AF;flex-shrink:0;"></i>
                                <input type="text" x-model="search" x-ref="ecsearch" placeholder="Search customers..."
                                       style="border:none;background:transparent;font-size:12.5px;outline:none;flex:1;color:#374151;">
                            </div>
                            <button type="button" @click="selected=null; open=false"
                                    style="width:100%;padding:9px 12px;display:flex;align-items:center;gap:10px;border:none;cursor:pointer;text-align:left;transition:background .1s;"
                                    :style="{'background': !selected ? '#EEF2FF' : 'transparent'}"
                                    @mouseover="if(selected) $el.style.background='#F9FAFB'" @mouseout="$el.style.background=!selected?'#EEF2FF':'transparent'">
                                <span style="width:28px;height:28px;border-radius:8px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-building" style="font-size:12px;color:#9CA3AF;"></i>
                                </span>
                                <span style="font-size:13px;color:#9CA3AF;font-style:italic;flex:1;">— No customer / Internal —</span>
                                <i x-show="!selected" class="fas fa-check" style="font-size:11px;color:#4F46E5;"></i>
                            </button>
                            <div style="max-height:200px;overflow-y:auto;">
                                <template x-for="c in filtered" :key="c.id">
                                    <button type="button" @click="selected=c.id; open=false"
                                            style="width:100%;padding:9px 12px;display:flex;align-items:center;gap:10px;border:none;cursor:pointer;text-align:left;transition:background .1s;"
                                            :style="{'background': selected===c.id ? '#EEF2FF' : 'transparent'}"
                                            @mouseover="if(selected!==c.id) $el.style.background='#F9FAFB'"
                                            @mouseout="$el.style.background=selected===c.id?'#EEF2FF':'transparent'">
                                        <span style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"
                                              :style="{'background': c.color}">
                                            <template x-if="c.logo">
                                                <img :src="c.logo" style="width:100%;height:100%;object-fit:contain;">
                                            </template>
                                            <template x-if="!c.logo">
                                                <span x-text="c.initials" style="font-size:12px;font-weight:700;color:#fff;"></span>
                                            </template>
                                        </span>
                                        <div style="flex:1;min-width:0;">
                                            <div x-text="c.name" style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                                            <div x-show="c.company" x-text="c.company" style="font-size:11px;color:#9CA3AF;"></div>
                                        </div>
                                        <i x-show="selected===c.id" class="fas fa-check" style="font-size:11px;color:#4F46E5;flex-shrink:0;margin-left:auto;"></i>
                                    </button>
                                </template>
                                <div x-show="filtered.length===0" style="padding:14px;text-align:center;font-size:12px;color:#9CA3AF;">No customers found</div>
                            </div>
                        </div>
                    </div>

                    {{-- Platform --}}
                    <div x-data="{
                            open: false,
                            get selected() { return editData.platform ?? ''; },
                            set selected(v) { editData.platform = v; },
                            platforms: saFormPlatforms,
                            get current() { return this.platforms.find(p => p.key === this.selected) ?? null; }
                         }" style="position:relative;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Platform *</label>
                        <input type="hidden" name="platform" :value="selected">
                        <button type="button" @click="open=!open" @keydown.escape.window="open=false"
                                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;display:flex;align-items:center;gap:8px;cursor:pointer;text-align:left;"
                                :style="{'border-color': open ? '#6366F1' : '#E5E7EB'}">
                            <span style="width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
                                  :style="{'background': current?.bg ?? '#F3F4F6'}">
                                <i :class="current ? 'fab '+current.icon : 'fas fa-share-nodes'"
                                   :style="{'font-size':'12px','color': current?.color ?? '#9CA3AF'}"></i>
                            </span>
                            <span style="flex:1;font-size:13px;" :style="{'color': current ? '#111827' : '#9CA3AF'}"
                                  x-text="current?.label ?? '— Select platform —'"></span>
                            <i class="fas fa-chevron-down" style="font-size:10px;color:#9CA3AF;transition:transform .15s;"
                               :style="{'transform': open ? 'rotate(180deg)' : 'none'}"></i>
                        </button>
                        <div x-show="open" @click.outside="open=false" x-cloak
                             style="position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1.5px solid #E5E7EB;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:200;overflow:hidden;">
                            <template x-for="p in platforms" :key="p.key">
                                <button type="button" @click="selected=p.key; open=false"
                                        style="width:100%;padding:9px 12px;display:flex;align-items:center;gap:10px;border:none;background:transparent;cursor:pointer;text-align:left;transition:background .1s;"
                                        :style="{'background': selected===p.key ? '#EEF2FF' : 'transparent'}"
                                        @mouseover="if(selected!==p.key) $el.style.background='#F9FAFB'"
                                        @mouseout="$el.style.background = selected===p.key ? '#EEF2FF' : 'transparent'">
                                    <span style="width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
                                          :style="{'background': p.bg}">
                                        <i :class="'fab '+p.icon" :style="{'font-size':'13px','color':p.color}"></i>
                                    </span>
                                    <span x-text="p.label" style="font-size:13px;color:#111827;font-weight:500;flex:1;"></span>
                                    <i x-show="selected===p.key" class="fas fa-check" style="font-size:11px;color:#4F46E5;margin-left:auto;"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Status *</label>
                        <select name="status" style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                            <option value="active"    x-bind:selected="editData.status==='active'">Active</option>
                            <option value="inactive"  x-bind:selected="editData.status==='inactive'">Inactive</option>
                            <option value="suspended" x-bind:selected="editData.status==='suspended'">Suspended</option>
                        </select>
                    </div>

                    {{-- Username --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Username / Handle</label>
                        <input type="text" name="username" :value="editData.username" placeholder="@handle"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                    </div>

                    {{-- Email --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Login Email</label>
                        <input type="text" name="email" :value="editData.email" autocomplete="off"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                    </div>

                    {{-- Password --}}
                    <div x-data="{show:false}">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                            Password
                            <span x-show="editData.has_password" style="font-size:11px;font-weight:400;color:#9CA3AF;"> — leave blank to keep</span>
                        </label>
                        <div style="position:relative;">
                            <input :type="show?'text':'password'" name="password" autocomplete="new-password"
                                   :placeholder="editData.has_password ? 'Leave blank to keep' : 'Enter password'"
                                   style="width:100%;padding:9px 40px 9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                            <button type="button" @click="show=!show"
                                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;">
                                <i :class="show?'fas fa-eye-slash':'fas fa-eye'" style="font-size:13px;"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Account ID --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Account / Page ID</label>
                        <input type="text" name="account_id" :value="editData.account_id"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                    </div>

                    {{-- Page URL --}}
                    <div style="grid-column:1/-1;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Page / Profile URL</label>
                        <input type="url" name="page_url" :value="editData.page_url"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                    </div>

                    {{-- Who has access --}}
                    <div style="grid-column:1/-1;"
                         x-data="{
                             userSearch: '',
                             get selectedIds() { return editData.user_ids ?? []; },
                             get filtered() {
                                 const q = this.userSearch.toLowerCase();
                                 return allFormUsers.filter(u => !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
                             },
                             toggle(id) {
                                 const ids = editData.user_ids ?? [];
                                 const i = ids.indexOf(id);
                                 if (i === -1) editData.user_ids = [...ids, id];
                                 else editData.user_ids = ids.filter(x => x !== id);
                             },
                             isSelected(id) { return (editData.user_ids ?? []).includes(id); }
                         }">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                            Who Has Access
                            <span style="font-size:11px;font-weight:400;color:#9CA3AF;"> — team members with credentials access</span>
                        </label>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;min-height:28px;margin-bottom:8px;" x-show="selectedIds.length > 0">
                            <template x-for="id in selectedIds" :key="'chip-'+id">
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px 4px 6px;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:20px;font-size:12px;font-weight:500;color:#4F46E5;">
                                    <span x-text="allFormUsers.find(u=>u.id===id)?.name ?? id" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                                    <button type="button" @click="toggle(id)" style="background:none;border:none;cursor:pointer;padding:0;line-height:1;color:#6366F1;">
                                        <i class="fas fa-times" style="font-size:9px;"></i>
                                    </button>
                                </span>
                            </template>
                        </div>
                        <template x-for="id in selectedIds" :key="'euid-'+id">
                            <input type="hidden" name="user_ids[]" :value="id">
                        </template>
                        <div style="border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;">
                            <div style="padding:8px 10px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:7px;background:#FAFAFA;">
                                <i class="fas fa-magnifying-glass" style="font-size:11px;color:#9CA3AF;"></i>
                                <input type="text" x-model="userSearch" placeholder="Search team members..."
                                       style="border:none;background:transparent;font-size:12.5px;outline:none;flex:1;color:#374151;">
                            </div>
                            <div style="max-height:160px;overflow-y:auto;">
                                <template x-for="u in filtered" :key="u.id">
                                    <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;transition:background .1s;"
                                         :style="{'background': isSelected(u.id) ? '#EEF2FF' : 'transparent'}"
                                         @click="toggle(u.id)"
                                         @mouseover="if(!isSelected(u.id)) $el.style.background='#F9FAFB'"
                                         @mouseout="$el.style.background = isSelected(u.id) ? '#EEF2FF' : 'transparent'">
                                        <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:11px;font-weight:700;color:#fff;"
                                             :style="{'background': ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6'][u.id % 5]}">
                                            <span x-text="u.name.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <div x-text="u.name" style="font-size:12.5px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                                            <div x-text="u.email" style="font-size:11px;color:#9CA3AF;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                                        </div>
                                        <div style="width:18px;height:18px;border-radius:5px;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .1s;"
                                             :style="isSelected(u.id)
                                                 ? {'background':'#4F46E5','border':'none'}
                                                 : {'border':'1.5px solid #D1D5DB','background':'transparent'}">
                                            <i x-show="isSelected(u.id)" class="fas fa-check" style="font-size:9px;color:#fff;pointer-events:none;"></i>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="filtered.length === 0" style="padding:16px;text-align:center;font-size:12px;color:#9CA3AF;">No users found</div>
                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div style="grid-column:1/-1;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Notes</label>
                        <textarea name="notes" rows="3" :value="editData.notes"
                                  style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;"
                                  onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                    </div>

                </div>
                <div style="display:flex;gap:10px;margin-top:20px;">
                    <button type="submit" style="flex:1;padding:11px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-save" style="margin-right:6px;"></i> Save Changes
                    </button>
                    <button type="button" @click="editModal=false" style="padding:11px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ════ DELETE MODAL ════ --}}
<div x-show="deleteModal" x-cloak style="position:fixed;inset:0;z-index:9999;"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);" @click="deleteModal=false"></div>
        <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:380px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.2);text-align:center;">
            <div style="width:52px;height:52px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <i class="fas fa-trash" style="color:#DC2626;font-size:18px;"></i>
            </div>
            <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">Delete Account?</h3>
            <p style="font-size:13px;color:#6B7280;margin:0 0 20px;">
                Permanently delete <strong x-text="deleteName" style="color:#111827;"></strong>? This cannot be undone.
            </p>
            <div style="display:flex;gap:10px;">
                <button @click="deleteModal=false"
                        style="flex:1;padding:10px;background:#F3F4F6;color:#374151;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <form :action="'/admin/social-accounts/'+deleteId" method="POST" style="flex:1;">
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

</div>{{-- /x-data --}}

@push('scripts')
<script>
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const icon = btn.querySelector('i');
        icon.className = 'fas fa-check';
        icon.style.color = '#16A34A';
        setTimeout(() => { icon.className = 'fas fa-copy'; icon.style.color = ''; }, 1800);
    });
}

var _saRevealCallback = null;
var _saRevealAccountId = null;

function openSaRevealModal(accountId, callback) {
    _saRevealAccountId = accountId;
    _saRevealCallback  = callback;
    document.getElementById('sa-reveal-input').value = '';
    document.getElementById('sa-reveal-error').style.display = 'none';
    document.getElementById('sa-reveal-btn').disabled = false;
    document.getElementById('sa-reveal-btn').textContent = 'Reveal';
    document.getElementById('sa-reveal-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('sa-reveal-input').focus(), 50);
}
function closeSaRevealModal() {
    document.getElementById('sa-reveal-modal').style.display = 'none';
    _saRevealCallback  = null;
    _saRevealAccountId = null;
}
async function submitSaReveal() {
    const pwd = document.getElementById('sa-reveal-input').value;
    const btn = document.getElementById('sa-reveal-btn');
    const err = document.getElementById('sa-reveal-error');
    err.style.display = 'none';
    if (!pwd) { err.textContent = 'Please enter your password.'; err.style.display = 'block'; return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:12px;margin-right:5px;"></i>Checking…';
    try {
        const res = await fetch(`/admin/social-accounts/${_saRevealAccountId}/reveal-password`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ password: pwd })
        });
        const data = await res.json();
        if (res.status === 403) { err.textContent = 'Incorrect password.'; err.style.display = 'block'; btn.disabled = false; btn.textContent = 'Reveal'; return; }
        if (!res.ok)            { err.textContent = 'Error. Please try again.'; err.style.display = 'block'; btn.disabled = false; btn.textContent = 'Reveal'; return; }
        closeSaRevealModal();
        if (_saRevealCallback) _saRevealCallback(data.secret);
    } catch (e) {
        err.textContent = 'Network error. Please try again.';
        err.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Reveal';
    }
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSaRevealModal();
    if (e.key === 'Enter' && document.getElementById('sa-reveal-modal')?.style.display === 'flex') submitSaReveal();
});

function pwReveal(accountId) {
    return {
        pw: '',
        revealed: false,
        loading: false,
        seconds: 0,
        _countdown: null,

        toggle() {
            if (this.revealed) { this.hide(); return; }
            // Password already fetched this session — just re-show
            if (this.pw) { this._startCountdown(); this.revealed = true; return; }
            openSaRevealModal(accountId, (secret) => {
                this.pw = secret;
                this._startCountdown();
                this.revealed = true;
            });
        },

        hide() {
            this.revealed = false;
            this.pw = '';          // wipe from memory on manual hide
            this.seconds = 0;
            clearInterval(this._countdown);
        },

        _startCountdown() {
            clearInterval(this._countdown);
            this.seconds = 15;
            this._countdown = setInterval(() => {
                this.seconds--;
                if (this.seconds <= 0) {
                    clearInterval(this._countdown);
                    this.revealed = false;
                    this.pw = '';  // wipe after auto-hide
                }
            }, 1000);
        },

        copyPw(btn) {
            if (!this.pw) return;
            navigator.clipboard.writeText(this.pw).then(() => {
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-check';
                    icon.style.color = '#16A34A';
                    setTimeout(() => { icon.className = 'fas fa-copy'; icon.style.color = ''; }, 1800);
                }
            });
        },
    };
}
</script>
@endpush

{{-- Password-gated reveal modal --}}
<div id="sa-reveal-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)closeSaRevealModal()">
    <div style="background:#fff;border-radius:16px;padding:28px 28px 24px;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
            <div style="width:34px;height:34px;background:#EEF2FF;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-lock" style="color:#4F46E5;font-size:14px;"></i>
            </div>
            <div>
                <div style="font-size:15px;font-weight:700;color:#111827;">Reveal Password</div>
                <div style="font-size:12px;color:#6B7280;">Enter your account password to continue</div>
            </div>
        </div>
        <p style="font-size:12px;color:#9CA3AF;margin:10px 0 14px;padding:8px 10px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;">
            <i class="fas fa-triangle-exclamation" style="color:#F97316;margin-right:5px;font-size:11px;"></i>
            Access is logged · password hides after 15 seconds
        </p>
        <input type="password" id="sa-reveal-input" placeholder="Your account password"
               style="width:100%;padding:10px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;outline:none;box-sizing:border-box;margin-bottom:8px;"
               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
        <div id="sa-reveal-error" style="display:none;font-size:12px;color:#DC2626;margin-bottom:8px;"></div>
        <div style="display:flex;gap:8px;">
            <button onclick="closeSaRevealModal()" style="flex:1;padding:9px;border:1.5px solid #E5E7EB;border-radius:8px;background:#fff;font-size:13px;font-weight:600;color:#6B7280;cursor:pointer;">Cancel</button>
            <button id="sa-reveal-btn" onclick="submitSaReveal()" style="flex:1;padding:9px;border:none;border-radius:8px;background:#4F46E5;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">Reveal</button>
        </div>
    </div>
</div>
@endsection

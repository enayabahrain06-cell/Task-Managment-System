@extends('layouts.app')
@section('title', 'Domains')

@section('content')
<style>
.dom-stat-card { background:#fff; border:1.5px solid #E5E7EB; border-radius:14px; padding:20px; display:flex; flex-direction:column; gap:4px; transition:box-shadow .15s; }
.dom-stat-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); }
.dom-stat-label { font-size:12px; color:#9CA3AF; font-weight:500; text-transform:uppercase; letter-spacing:.05em; }
.dom-stat-value { font-size:28px; font-weight:800; color:#111827; line-height:1.1; }
.dom-stat-sub   { font-size:12px; color:#6B7280; }
.status-active        { background:#ECFDF5; color:#16A34A; }
.status-expiring_soon { background:#FEF3C7; color:#D97706; }
.status-expired       { background:#FEE2E2; color:#DC2626; }
.dom-status-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:11.5px; font-weight:600; }
.dom-row:hover { background:#FAFAFA; }
@media (max-width:768px) {
    .dom-stats-grid { grid-template-columns:repeat(2,1fr) !important; }
    .dom-tbl-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; }
    .dom-tbl-scroll table { min-width:900px; }
}
</style>

<div x-data="{
    createModal: {{ $errors->any() ? 'true' : 'false' }},
    deleteModal: false,
    deleteId: null,
    deleteName: '',
    deleteConfirmInput: '',
    openDelete(id, name) { this.deleteId = id; this.deleteName = name; this.deleteConfirmInput = ''; this.deleteModal = true; },
    get deleteConfirmed() { return this.deleteConfirmInput.trim() === this.deleteName.trim(); },
    submitDelete() { if (!this.deleteConfirmed) return; this.$refs.deleteForm.submit(); },
}">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Domain Management</h1>
            <p style="font-size:13px;color:#9CA3AF;margin:4px 0 0;">Track all client domains, expiry dates, registrars, and billing info</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('admin.domains.export.pdf') }}"
               style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#fff;color:#374151;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;"
               onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='#fff'">
                <i class="fas fa-file-pdf" style="font-size:12px;color:#DC2626;"></i> Export PDF
            </a>
            <button @click="createModal = true"
                    style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="fas fa-plus" style="font-size:11px;"></i> Add Domain
            </button>
        </div>
    </div>

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
                <a href="{{ route('admin.domains.show', $d->id) }}"
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
    <div class="dom-stats-grid" style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px;">
        <div class="dom-stat-card">
            <div class="dom-stat-label">Total</div>
            <div class="dom-stat-value">{{ $totalCount }}</div>
            <div class="dom-stat-sub">All domains</div>
        </div>
        <div class="dom-stat-card" style="border-color:#A7F3D0;">
            <div class="dom-stat-label" style="color:#16A34A;">Active</div>
            <div class="dom-stat-value" style="color:#16A34A;">{{ $activeCount }}</div>
            <div class="dom-stat-sub">More than 30 days</div>
        </div>
        <div class="dom-stat-card" style="border-color:#FDE68A;">
            <div class="dom-stat-label" style="color:#D97706;">Expiring Soon</div>
            <div class="dom-stat-value" style="color:#D97706;">{{ $expiringSoonCount }}</div>
            <div class="dom-stat-sub">Within 30 days — <strong style="color:#EA580C;">{{ $weekCount }}</strong> within 7</div>
        </div>
        <div class="dom-stat-card" style="border-color:#FCA5A5;">
            <div class="dom-stat-label" style="color:#DC2626;">Expired</div>
            <div class="dom-stat-value" style="color:#DC2626;">{{ $expiredCount }}</div>
            <div class="dom-stat-sub">Need renewal</div>
        </div>
        <div class="dom-stat-card" style="border-color:#C7D2FE;background:linear-gradient(135deg,#EEF2FF,#F5F3FF);">
            <div class="dom-stat-label" style="color:#4F46E5;">Annual Spend</div>
            <div class="dom-stat-value" style="color:#4F46E5;">{{ number_format($annualTotal, 3) }}</div>
            <div class="dom-stat-sub">BHD / year (total)</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" style="background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;padding:16px 20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label style="font-size:11px;font-weight:600;color:#6B7280;display:block;margin-bottom:4px;">SEARCH</label>
            <input name="search" value="{{ request('search') }}" placeholder="Domain, registrar, customer, billing to…"
                   style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
        </div>
        <div style="min-width:160px;">
            <label style="font-size:11px;font-weight:600;color:#6B7280;display:block;margin-bottom:4px;">CUSTOMER</label>
            <select name="customer_id" style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;outline:none;">
                <option value="">All customers</option>
                @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                    {{ $c->name }}{{ $c->company ? ' – '.$c->company : '' }}
                </option>
                @endforeach
            </select>
        </div>
        @if($registrars->count())
        <div style="min-width:150px;">
            <label style="font-size:11px;font-weight:600;color:#6B7280;display:block;margin-bottom:4px;">REGISTRAR</label>
            <select name="registrar" style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;outline:none;">
                <option value="">All registrars</option>
                @foreach($registrars as $r)
                <option value="{{ $r }}" {{ request('registrar') === $r ? 'selected' : '' }}>{{ $r }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" style="padding:9px 20px;background:#6366F1;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
            <i class="fas fa-search" style="margin-right:5px;"></i>Filter
        </button>
        @if(request()->hasAny(['search','customer_id','registrar']))
        <a href="{{ route('admin.domains.index') }}" style="padding:9px 16px;background:#F3F4F6;color:#6B7280;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
            Clear
        </a>
        @endif
    </form>

    {{-- Status Tab Filter --}}
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
        @php
            $tabs = [
                'all'           => ['label'=>'All', 'count'=>$totalCount, 'color'=>'#374151'],
                'active'        => ['label'=>'Active', 'count'=>$activeCount, 'color'=>'#16A34A'],
                'expiring_soon' => ['label'=>'Expiring Soon', 'count'=>$expiringSoonCount, 'color'=>'#D97706'],
                'expired'       => ['label'=>'Expired', 'count'=>$expiredCount, 'color'=>'#DC2626'],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
        <a href="{{ request()->fullUrlWithQuery(['status' => $key]) }}"
           style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:20px;font-size:12.5px;font-weight:600;text-decoration:none;transition:all .15s;
                  {{ $statusFilter === $key ? 'background:'.$tab['color'].';color:#fff;' : 'background:#F3F4F6;color:#6B7280;' }}">
            {{ $tab['label'] }}
            <span style="font-size:11px;font-weight:700;{{ $statusFilter === $key ? 'opacity:.85' : 'color:'.$tab['color'] }}">{{ $tab['count'] }}</span>
        </a>
        @endforeach
    </div>

    {{-- Table --}}
    <div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;overflow:hidden;">
        @if($domains->isEmpty())
        <div style="padding:60px 24px;text-align:center;">
            <div style="width:56px;height:56px;background:#F3F4F6;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-globe" style="font-size:22px;color:#9CA3AF;"></i>
            </div>
            <div style="font-size:15px;font-weight:600;color:#374151;margin-bottom:6px;">No domains found</div>
            <div style="font-size:13px;color:#9CA3AF;margin-bottom:20px;">Add your first domain to start tracking renewals</div>
            <button @click="createModal = true" style="padding:9px 20px;background:#6366F1;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="fas fa-plus" style="margin-right:6px;"></i>Add Domain
            </button>
        </div>
        @else
        <div class="dom-tbl-scroll">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#F9FAFB;border-bottom:1.5px solid #E5E7EB;">
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Domain</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Customer</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Registrar</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Responsible</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Bill To</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Expires</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Cost</th>
                    <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Auto</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Status</th>
                    <th style="padding:12px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($domains as $domain)
                @php
                    $status = $domain->status;
                    $days   = $domain->days_until_expiry;
                @endphp
                <tr class="dom-row" style="border-bottom:1px solid #F3F4F6;">
                    <td style="padding:14px 16px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;background:#EEF2FF;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-globe" style="color:#6366F1;font-size:14px;"></i>
                            </div>
                            <div>
                                <a href="{{ route('admin.domains.show', $domain->id) }}"
                                   style="font-size:14px;font-weight:700;color:#111827;text-decoration:none;"
                                   onmouseover="this.style.color='#6366F1'" onmouseout="this.style.color='#111827'">
                                    {{ $domain->domain }}
                                </a>
                                @if($domain->hosting_provider)
                                <div style="font-size:11.5px;color:#9CA3AF;">Host: {{ $domain->hosting_provider }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 16px;">
                        @if($domain->customer)
                        <a href="{{ route('admin.customers.show', $domain->customer_id) }}"
                           style="font-size:13px;font-weight:600;color:#374151;text-decoration:none;"
                           onmouseover="this.style.color='#6366F1'" onmouseout="this.style.color='#374151'">
                            {{ $domain->customer->name }}
                        </a>
                        @if($domain->customer->company)
                        <div style="font-size:11.5px;color:#9CA3AF;">{{ $domain->customer->company }}</div>
                        @endif
                        @else
                        <span style="color:#D1D5DB;font-size:13px;">—</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;">
                        @if($domain->registrar)
                        <span style="font-size:13px;color:#374151;font-weight:500;">{{ $domain->registrar }}</span>
                        @else
                        <span style="color:#D1D5DB;font-size:13px;">—</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;">
                        @if($domain->responsibleUser)
                        <div style="display:flex;align-items:center;gap:7px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#4F46E5);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span style="font-size:11px;font-weight:700;color:#fff;">{{ strtoupper(substr($domain->responsibleUser->name,0,1)) }}</span>
                            </div>
                            <span style="font-size:13px;color:#374151;font-weight:500;">{{ $domain->responsibleUser->name }}</span>
                        </div>
                        @else
                        <span style="color:#D1D5DB;font-size:13px;">—</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;">
                        @if($domain->billing_to)
                        <span style="font-size:13px;color:#374151;font-weight:500;">{{ $domain->billing_to }}</span>
                        @else
                        <span style="color:#D1D5DB;font-size:13px;">—</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;">
                        @if($domain->expires_at)
                        <div style="font-size:13px;font-weight:600;color:{{ $status==='expired' ? '#DC2626' : ($status==='expiring_soon' ? '#D97706' : '#374151') }};">
                            {{ $domain->expires_at->format('d M Y') }}
                        </div>
                        <div style="font-size:11.5px;color:#9CA3AF;">
                            @if($days === null) No date
                            @elseif($days < 0) {{ abs($days) }}d ago
                            @elseif($days === 0) Today
                            @elseif($days === 1) Tomorrow
                            @else {{ $days }}d left
                            @endif
                        </div>
                        @else
                        <span style="color:#D1D5DB;font-size:13px;">—</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;">
                        @if($domain->cost > 0)
                        <span style="font-size:13px;font-weight:600;color:#111827;">{{ number_format($domain->cost, 3) }}</span>
                        <span style="font-size:11.5px;color:#9CA3AF;"> {{ $domain->currency }}</span>
                        <div style="font-size:11px;color:#9CA3AF;">{{ $billingCycles[$domain->billing_cycle] ?? $domain->billing_cycle }}</div>
                        @else
                        <span style="color:#D1D5DB;font-size:13px;">—</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;text-align:center;">
                        @if($domain->auto_renew)
                        <span title="Auto-renew ON" style="display:inline-flex;width:22px;height:22px;border-radius:50%;background:#ECFDF5;align-items:center;justify-content:center;">
                            <i class="fas fa-check" style="font-size:10px;color:#16A34A;"></i>
                        </span>
                        @else
                        <span title="Auto-renew OFF" style="display:inline-flex;width:22px;height:22px;border-radius:50%;background:#F3F4F6;align-items:center;justify-content:center;">
                            <i class="fas fa-xmark" style="font-size:10px;color:#9CA3AF;"></i>
                        </span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;">
                        <span class="dom-status-badge status-{{ $status }}">
                            @if($status==='active') <i class="fas fa-circle" style="font-size:7px;"></i> Active
                            @elseif($status==='expiring_soon') <i class="fas fa-clock" style="font-size:9px;"></i> Expiring
                            @else <i class="fas fa-triangle-exclamation" style="font-size:9px;"></i> Expired
                            @endif
                        </span>
                    </td>
                    <td style="padding:14px 16px;text-align:right;">
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <a href="{{ route('admin.domains.show', $domain->id) }}"
                               style="padding:5px 12px;background:#EEF2FF;color:#4F46E5;border-radius:7px;font-size:12px;font-weight:600;text-decoration:none;">
                                View
                            </a>
                            <button onclick="openEditModal({{ $domain->id }},{{ json_encode($domain->domain) }},{{ json_encode($domain->registrar) }},{{ $domain->customer_id ?? 'null' }},{{ $domain->responsible_user_id ?? 'null' }},{{ json_encode($domain->billing_to) }},{{ $domain->cost }},{{ json_encode($domain->currency) }},{{ json_encode($domain->billing_cycle) }},{{ $domain->auto_renew ? 'true' : 'false' }},{{ json_encode($domain->registered_at?->format('Y-m-d')) }},{{ json_encode($domain->expires_at?->format('Y-m-d')) }},{{ json_encode($domain->hosting_provider) }},{{ json_encode($domain->login_url) }},{{ json_encode($domain->username) }},{{ json_encode($domain->notes) }},{{ json_encode(implode("\n", $domain->nameservers ?? [])) }})"
                               style="padding:5px 12px;background:#F3F4F6;color:#374151;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;">
                                Edit
                            </button>
                            <button @click="openDelete({{ $domain->id }}, '{{ addslashes($domain->domain) }}')"
                                    style="padding:5px 10px;background:#FEE2E2;color:#DC2626;border:none;border-radius:7px;font-size:12px;cursor:pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>

    {{-- Delete Form --}}
    <form x-ref="deleteForm" method="POST" style="display:none;">
        @csrf @method('DELETE')
        <input type="hidden" name="_method" value="DELETE">
    </form>

    {{-- Delete Modal --}}
    <div x-show="deleteModal" style="position:fixed;inset:0;z-index:9000;overflow-y:auto;" x-cloak>
        <div style="position:fixed;inset:0;background:rgba(0,0,0,.4);" @click="deleteModal=false"></div>
        <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:24px 16px;position:relative;z-index:1;">
        <div style="background:#fff;border-radius:16px;padding:28px;width:420px;max-width:100%;">
            <div style="width:48px;height:48px;background:#FEE2E2;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-trash" style="color:#DC2626;font-size:20px;"></i>
            </div>
            <h3 style="font-size:17px;font-weight:700;color:#111827;text-align:center;margin:0 0 8px;">Delete Domain</h3>
            <p style="font-size:13px;color:#6B7280;text-align:center;margin:0 0 20px;">
                Type <strong x-text="deleteName"></strong> to confirm deletion.
            </p>
            <input x-model="deleteConfirmInput" placeholder="Type domain name to confirm"
                   style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;margin-bottom:16px;"
                   onfocus="this.style.borderColor='#DC2626'" onblur="this.style.borderColor='#E5E7EB'">
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button @click="deleteModal=false" style="padding:9px 18px;background:#F3F4F6;color:#374151;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">Cancel</button>
                <button @click="submitDelete()"
                        :disabled="!deleteConfirmed"
                        :style="deleteConfirmed ? 'background:#DC2626;color:#fff;cursor:pointer;opacity:1;' : 'background:#FCA5A5;color:#fff;cursor:not-allowed;opacity:.7;'"
                        style="padding:9px 18px;border:none;border-radius:9px;font-size:13px;font-weight:600;"
                        x-effect="if(deleteModal) $nextTick(() => { const form = $refs.deleteForm; form.action = '/admin/domains/' + deleteId; })">
                    Delete
                </button>
            </div>
        </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div x-show="createModal" x-cloak style="display:none;position:fixed;inset:0;z-index:9000;overflow-y:auto;background:rgba(0,0,0,.45);">
        <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:24px 16px;">
            <div style="position:relative;background:#fff;border-radius:16px;padding:28px;width:640px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);" @click.stop>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
                    <h3 style="font-size:18px;font-weight:700;color:#111827;margin:0;">Add Domain</h3>
                    <button @click="createModal=false" style="width:32px;height:32px;background:#F3F4F6;border:none;border-radius:8px;cursor:pointer;font-size:16px;color:#6B7280;">✕</button>
                </div>
                <form method="POST" action="{{ route('admin.domains.store') }}">
                    @csrf
                    @include('admin.domains._form', ['domain' => null])
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px;">
                        <button type="button" @click="createModal=false" style="padding:10px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
                        <button type="submit" style="padding:10px 24px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                            <i class="fas fa-plus" style="margin-right:6px;"></i>Add Domain
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="editModal" style="display:none;position:fixed;inset:0;z-index:9000;overflow-y:auto;background:rgba(0,0,0,.45);">
        <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:24px 16px;">
            <div style="position:relative;background:#fff;border-radius:16px;padding:28px;width:640px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);" onclick="event.stopPropagation()">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
                    <h3 style="font-size:18px;font-weight:700;color:#111827;margin:0;">Edit Domain</h3>
                    <button onclick="document.getElementById('editModal').style.display='none'" style="width:32px;height:32px;background:#F3F4F6;border:none;border-radius:8px;cursor:pointer;font-size:16px;color:#6B7280;">✕</button>
                </div>
                <form id="editForm" method="POST">
                    @csrf @method('PUT')
                    <div id="editFormFields"></div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px;">
                        <button type="button" onclick="document.getElementById('editModal').style.display='none'" style="padding:10px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
                        <button type="submit" style="padding:10px 24px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const domCustomers  = @json($customers);
const domStaffUsers = @json($staffUsers);
const domBillingCycles = @json($billingCycles);

function openEditModal(id, domain, registrar, customerId, responsibleUserId, billingTo, cost, currency, billingCycle, autoRenew, registeredAt, expiresAt, hostingProvider, loginUrl, username, notes, nameservers) {
    const form = document.getElementById('editForm');
    form.action = '/admin/domains/' + id;

    let customerOptions = '<option value="">— No customer —</option>';
    domCustomers.forEach(c => {
        const label = c.name + (c.company ? ' – ' + c.company : '');
        customerOptions += `<option value="${c.id}" ${c.id == customerId ? 'selected' : ''}>${label}</option>`;
    });

    let userOptions = '<option value="">— Not assigned —</option>';
    domStaffUsers.forEach(u => {
        userOptions += `<option value="${u.id}" ${u.id == responsibleUserId ? 'selected' : ''}>${u.name}</option>`;
    });

    let cycleOptions = '';
    Object.entries(domBillingCycles).forEach(([k, v]) => {
        cycleOptions += `<option value="${k}" ${k === billingCycle ? 'selected' : ''}>${v}</option>`;
    });

    const currencies = ['BHD','USD','EUR','GBP','SAR','AED','KWD','QAR','OMR'];
    let currencyOpts = currencies.map(c => `<option value="${c}" ${c===currency?'selected':''}>${c}</option>`).join('');

    const inp = (id, label, name, value, type='text', placeholder='') =>
        `<div style="flex:1;min-width:220px;"><label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">${label}</label>
        <input id="${id}" name="${name}" type="${type}" value="${value||''}" placeholder="${placeholder}"
               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:13px;outline:none;box-sizing:border-box;"
               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></div>`;

    const sel = (id, label, name, opts) =>
        `<div style="flex:1;min-width:180px;"><label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">${label}</label>
        <select id="${id}" name="${name}" style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:13px;outline:none;box-sizing:border-box;">${opts}</select></div>`;

    document.getElementById('editFormFields').innerHTML = `
        <div style="display:flex;flex-wrap:wrap;gap:14px;">
            ${inp('e_domain','Domain Name *','domain',domain,'text','example.com')}
            ${inp('e_registrar','Registrar','registrar',registrar,'text','GoDaddy, Namecheap…')}
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:14px;margin-top:14px;">
            ${sel('e_customer','Customer','customer_id',customerOptions)}
            ${sel('e_responsible','Responsible Person','responsible_user_id',userOptions)}
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:14px;margin-top:14px;">
            ${inp('e_billing_to','Bill To','billing_to',billingTo,'text','Client name, contact person…')}
            ${inp('e_hosting','Hosting Provider','hosting_provider',hostingProvider,'text','SiteGround, Cloudflare…')}
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:14px;margin-top:14px;">
            <div style="flex:1;min-width:100px;"><label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Cost *</label>
            <input name="cost" type="number" step="0.001" min="0" value="${cost||0}"
                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:13px;outline:none;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></div>
            ${sel('e_currency','Currency','currency',currencyOpts)}
            ${sel('e_cycle','Billing Cycle','billing_cycle',cycleOptions)}
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:14px;margin-top:14px;">
            ${inp('e_reg_date','Registered','registered_at',registeredAt,'date')}
            ${inp('e_exp_date','Expires *','expires_at',expiresAt,'date')}
        </div>
        <div style="margin-top:14px;"><label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Nameservers (one per line)</label>
        <textarea name="nameservers" rows="3" style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:13px;outline:none;box-sizing:border-box;resize:vertical;"
                  onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">${nameservers||''}</textarea></div>
        <div style="margin-top:14px;padding:14px;background:#F9FAFB;border-radius:10px;border:1.5px solid #E5E7EB;">
            <div style="font-size:12px;font-weight:700;color:#6B7280;margin-bottom:10px;">REGISTRAR CREDENTIALS</div>
            <div style="display:flex;flex-wrap:wrap;gap:14px;">
                ${inp('e_login_url','Login URL','login_url',loginUrl,'url','https://')}
                ${inp('e_username','Username','username',username,'text','')}
            </div>
            <div style="margin-top:10px;"><label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">New Password (leave blank to keep current)</label>
            <input name="password" type="password" placeholder="Enter new password to change"
                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:13px;outline:none;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></div>
        </div>
        <div style="margin-top:14px;display:flex;align-items:center;gap:10px;">
            <input type="checkbox" name="auto_renew" value="1" id="e_auto_renew" ${autoRenew ? 'checked' : ''}
                   style="width:16px;height:16px;accent-color:#6366F1;cursor:pointer;">
            <label for="e_auto_renew" style="font-size:13px;font-weight:600;color:#374151;cursor:pointer;">Auto-renew enabled</label>
        </div>
        <div style="margin-top:14px;"><label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Notes</label>
        <textarea name="notes" rows="3" placeholder="Internal notes…"
                  style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:13px;outline:none;box-sizing:border-box;resize:vertical;"
                  onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">${notes||''}</textarea></div>
        <input type="hidden" name="notify_days[]" value="60">
        <input type="hidden" name="notify_days[]" value="30">
        <input type="hidden" name="notify_days[]" value="14">
        <input type="hidden" name="notify_days[]" value="7">
        <input type="hidden" name="notify_days[]" value="1">
    `;

    const modal = document.getElementById('editModal');
    modal.style.display = 'block';
}
</script>
@endsection

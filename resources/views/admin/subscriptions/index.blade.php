@extends('layouts.app')
@section('title', 'Subscriptions')

@section('content')
<style>
.sub-stat-card { background:#fff; border:1.5px solid #E5E7EB; border-radius:14px; padding:20px; display:flex; flex-direction:column; gap:4px; transition:box-shadow .15s; }
.sub-stat-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); }
.sub-stat-label { font-size:12px; color:#9CA3AF; font-weight:500; text-transform:uppercase; letter-spacing:.05em; }
.sub-stat-value { font-size:28px; font-weight:800; color:#111827; line-height:1.1; }
.sub-stat-sub   { font-size:12px; color:#6B7280; }
.status-active        { background:#ECFDF5; color:#16A34A; }
.status-expiring_soon { background:#FEF3C7; color:#D97706; }
.status-expired       { background:#FEE2E2; color:#DC2626; }
.status-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:11.5px; font-weight:600; }
.cat-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
.sub-row:hover { background:#FAFAFA; }
@media (max-width:768px) {
    .sub-stats-grid { grid-template-columns:repeat(2,1fr) !important; }
    .sub-cost-grid  { grid-template-columns:1fr 1fr !important; }
    .sub-tbl-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; }
    .sub-tbl-scroll table { min-width:750px; }
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
            <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Subscriptions & Licenses</h1>
            <p style="font-size:13px;color:#9CA3AF;margin:4px 0 0;">Track all software subscriptions, renewals, and seat assignments</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('admin.subscriptions.export.pdf') }}"
               style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#fff;color:#374151;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;"
               onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='#fff'">
                <i class="fas fa-file-pdf" style="font-size:12px;color:#DC2626;"></i> Export PDF
            </a>
            <button @click="createModal = true"
                    style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="fas fa-plus" style="font-size:11px;"></i> Add Subscription
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
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

    {{-- Expiring This Week Alert --}}
    @if($expiringThisWeek->count())
    <div style="background:#FFF7ED;border:1.5px solid #FED7AA;border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px;">
        <i class="fas fa-triangle-exclamation" style="color:#EA580C;margin-top:2px;"></i>
        <div>
            <div style="font-size:13px;font-weight:700;color:#C2410C;margin-bottom:4px;">⚡ {{ $expiringThisWeek->count() }} subscription{{ $expiringThisWeek->count()>1?'s':'' }} expiring within 7 days</div>
            <div style="font-size:12px;color:#9A3412;display:flex;flex-wrap:wrap;gap:6px;">
                @foreach($expiringThisWeek as $s)
                <a href="{{ route('admin.subscriptions.show', $s->id) }}"
                   style="background:#FEE7D0;padding:3px 10px;border-radius:20px;color:#C2410C;font-weight:600;text-decoration:none;font-size:11.5px;">
                    {{ $s->name }}
                    @if($s->days_until_renewal === 0) — Today!
                    @elseif($s->days_until_renewal === 1) — Tomorrow
                    @else — {{ $s->days_until_renewal }}d
                    @endif
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Stats Row --}}
    <div class="sub-stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:16px;">
        <div class="sub-stat-card">
            <div class="sub-stat-label">Total</div>
            <div class="sub-stat-value">{{ $totalCount }}</div>
            <div class="sub-stat-sub">All subscriptions</div>
        </div>
        <div class="sub-stat-card" style="border-color:#A7F3D0;">
            <div class="sub-stat-label" style="color:#16A34A;">Active</div>
            <div class="sub-stat-value" style="color:#16A34A;">{{ $activeCount }}</div>
            <div class="sub-stat-sub">More than 30 days</div>
        </div>
        <div class="sub-stat-card" style="border-color:#FDE68A;">
            <div class="sub-stat-label" style="color:#D97706;">Expiring Soon</div>
            <div class="sub-stat-value" style="color:#D97706;">{{ $expiringSoonCount }}</div>
            <div class="sub-stat-sub">Within 30 days — <strong style="color:#EA580C;">{{ $weekCount }}</strong> within 7</div>
        </div>
        <div class="sub-stat-card" style="border-color:#FCA5A5;">
            <div class="sub-stat-label" style="color:#DC2626;">Expired</div>
            <div class="sub-stat-value" style="color:#DC2626;">{{ $expiredCount }}</div>
            <div class="sub-stat-sub">Need renewal</div>
        </div>
    </div>

    {{-- Cost Summary --}}
    <div class="sub-cost-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
        <div style="background:linear-gradient(135deg,#EEF2FF,#F5F3FF);border:1.5px solid #C7D2FE;border-radius:14px;padding:20px;display:flex;align-items:center;gap:16px;">
            <div style="width:44px;height:44px;background:#EEF2FF;border-radius:12px;display:flex;align-items:center;justify-content:center;border:1.5px solid #C7D2FE;flex-shrink:0;">
                <i class="fas fa-calendar-days" style="color:#4F46E5;font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size:12px;color:#6B7280;font-weight:500;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Monthly Spend</div>
                <div style="font-size:24px;font-weight:800;color:#4F46E5;">BHD {{ number_format($monthlyTotal, 3) }}</div>
                <div style="font-size:11px;color:#9CA3AF;">Across all active cycles</div>
            </div>
        </div>
        <div style="background:linear-gradient(135deg,#ECFDF5,#F0FDF4);border:1.5px solid #A7F3D0;border-radius:14px;padding:20px;display:flex;align-items:center;gap:16px;">
            <div style="width:44px;height:44px;background:#ECFDF5;border-radius:12px;display:flex;align-items:center;justify-content:center;border:1.5px solid #A7F3D0;flex-shrink:0;">
                <i class="fas fa-coins" style="color:#16A34A;font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size:12px;color:#6B7280;font-weight:500;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Annual Spend</div>
                <div style="font-size:24px;font-weight:800;color:#16A34A;">BHD {{ number_format($annualTotal, 3) }}</div>
                <div style="font-size:11px;color:#9CA3AF;">Projected yearly total</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;padding:16px;margin-bottom:16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" style="display:contents;">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search name or vendor..."
                   style="flex:1;min-width:200px;padding:8px 14px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#374151;outline:none;"
                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">

            <select name="category" style="padding:8px 14px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#374151;background:#fff;cursor:pointer;">
                <option value="">All Categories</option>
                @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <div style="display:flex;gap:2px;background:#F3F4F6;border-radius:8px;padding:3px;">
                @foreach(['all'=>'All','active'=>'Active','expiring_soon'=>'Expiring','expired'=>'Expired'] as $val=>$lbl)
                <a href="{{ route('admin.subscriptions.index', array_merge(request()->except('status','page'), ['status'=>$val])) }}"
                   style="padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s;
                   {{ $statusFilter === $val ? 'background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);' : 'color:#6B7280;' }}">
                    {{ $lbl }}
                </a>
                @endforeach
            </div>

            <button type="submit"
                    style="padding:8px 18px;background:#4F46E5;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="fas fa-magnifying-glass"></i>
            </button>

            @if(request()->hasAny(['search','category']))
            <a href="{{ route('admin.subscriptions.index', ['status'=>$statusFilter]) }}"
               style="padding:8px 14px;background:#F3F4F6;color:#374151;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;">
                Clear
            </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;overflow:hidden;">
        @if($subscriptions->isEmpty())
        <div style="padding:60px 24px;text-align:center;color:#9CA3AF;">
            <i class="fas fa-layer-group" style="font-size:40px;margin-bottom:12px;opacity:.3;display:block;"></i>
            <p style="font-size:15px;font-weight:500;">No subscriptions found</p>
            <p style="font-size:13px;margin-top:4px;">Click "Add Subscription" to get started</p>
        </div>
        @else
        <div class="sub-tbl-scroll">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#F9FAFB;border-bottom:1.5px solid #E5E7EB;">
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Subscription</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Category</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Cost</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Renewal</th>
                    <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Users</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Status</th>
                    <th style="padding:12px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subscriptions as $sub)
                @php
                    $catColors = \App\Models\Subscription::categoryColors();
                    $cc = $catColors[$sub->category] ?? $catColors['other'];
                    $statusClass = 'status-' . $sub->status;
                    $days = $sub->days_until_renewal;
                    $rowBg = $sub->status === 'expired' ? '#FFFAFA' : ($sub->status === 'expiring_soon' ? '#FFFEF0' : '#fff');
                @endphp
                <tr class="sub-row" style="border-bottom:1px solid #F3F4F6;background:{{ $rowBg }};">
                    <td style="padding:14px 16px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:38px;height:38px;border-radius:10px;background:{{ $cc['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                                @if($sub->logo_url)
                                    <img src="{{ $sub->logo_url }}" alt="{{ $sub->name }}" style="width:100%;height:100%;object-fit:contain;padding:5px;box-sizing:border-box;background:#fff;">
                                @else
                                    <i class="fas fa-{{ $sub->category === 'design' ? 'pen-nib' : ($sub->category === 'development' ? 'code' : ($sub->category === 'communication' ? 'comment-dots' : ($sub->category === 'marketing' ? 'bullhorn' : ($sub->category === 'security' ? 'shield-halved' : ($sub->category === 'finance' ? 'chart-line' : 'layer-group'))))) }}"
                                       style="color:{{ $cc['color'] }};font-size:15px;"></i>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('admin.subscriptions.show', $sub->id) }}"
                                   style="font-size:14px;font-weight:600;color:#111827;text-decoration:none;">{{ $sub->name }}</a>
                                @if($sub->vendor)
                                <div style="font-size:12px;color:#9CA3AF;margin-top:1px;">{{ $sub->vendor }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 16px;">
                        <span class="cat-badge" style="background:{{ $cc['bg'] }};color:{{ $cc['color'] }};">
                            {{ \App\Models\Subscription::categoryOptions()[$sub->category] ?? $sub->category }}
                        </span>
                        @if($sub->type === 'per_seat')
                        <div style="font-size:11px;color:#9CA3AF;margin-top:4px;">Per Seat</div>
                        @elseif($sub->type === 'site_license')
                        <div style="font-size:11px;color:#9CA3AF;margin-top:4px;">Site License</div>
                        @else
                        <div style="font-size:11px;color:#9CA3AF;margin-top:4px;">Shared</div>
                        @endif
                    </td>
                    <td style="padding:14px 16px;">
                        <div style="font-size:14px;font-weight:600;color:#111827;">{{ $sub->currency }} {{ number_format($sub->cost, 3) }}</div>
                        <div style="font-size:11px;color:#9CA3AF;">/ {{ $sub->billing_cycle }}</div>
                    </td>
                    <td style="padding:14px 16px;">
                        @if($sub->renewal_date)
                        <div style="font-size:13px;font-weight:500;color:#374151;">{{ $sub->renewal_date->format('d M Y') }}</div>
                        @if($days !== null)
                            @if($days < 0)
                            <div style="font-size:11px;color:#DC2626;font-weight:600;margin-top:2px;">Expired {{ abs($days) }}d ago</div>
                            @elseif($days === 0)
                            <div style="font-size:11px;color:#DC2626;font-weight:700;margin-top:2px;">⚡ Due today!</div>
                            @elseif($days <= 7)
                            <div style="font-size:11px;color:#EA580C;font-weight:600;margin-top:2px;">⚡ {{ $days }}d left</div>
                            @elseif($days <= 30)
                            <div style="font-size:11px;color:#D97706;font-weight:600;margin-top:2px;">{{ $days }}d left</div>
                            @else
                            <div style="font-size:11px;color:#9CA3AF;margin-top:2px;">{{ $days }}d left</div>
                            @endif
                        @endif
                        @else
                        <span style="color:#D1D5DB;font-size:13px;">—</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;text-align:center;">
                        <div style="display:flex;align-items:center;justify-content:center;gap:-4px;">
                            @foreach($sub->users->take(4) as $u)
                            <div title="{{ $u->name }}"
                                 style="width:28px;height:28px;border-radius:50%;background:{{ ['#6366F1','#10B981','#F59E0B','#EF4444'][$loop->index % 4] }};display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:700;border:2px solid #fff;margin-left:{{ $loop->first ? '0' : '-8px' }};">
                                {{ strtoupper(substr($u->name,0,1)) }}
                            </div>
                            @endforeach
                            @if($sub->users->count() > 4)
                            <div style="width:28px;height:28px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:9px;font-weight:700;border:2px solid #fff;margin-left:-8px;">
                                +{{ $sub->users->count() - 4 }}
                            </div>
                            @endif
                        </div>
                        @if($sub->max_seats)
                        <div style="font-size:10px;color:#9CA3AF;margin-top:4px;">{{ $sub->users_count }}/{{ $sub->max_seats }}</div>
                        @elseif($sub->users_count > 0)
                        <div style="font-size:10px;color:#9CA3AF;margin-top:4px;">{{ $sub->users_count }} user{{ $sub->users_count>1?'s':'' }}</div>
                        @endif
                    </td>
                    <td style="padding:14px 16px;">
                        <span class="status-badge {{ $statusClass }}">
                            @if($sub->status === 'active')
                            <i class="fas fa-circle" style="font-size:6px;"></i> Active
                            @elseif($sub->status === 'expiring_soon')
                            <i class="fas fa-clock" style="font-size:10px;"></i> Expiring
                            @else
                            <i class="fas fa-triangle-exclamation" style="font-size:10px;"></i> Expired
                            @endif
                        </span>
                    </td>
                    <td style="padding:14px 16px;text-align:right;">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                            <a href="{{ route('admin.subscriptions.show', $sub->id) }}"
                               style="width:32px;height:32px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;color:#4F46E5;text-decoration:none;transition:background .15s;"
                               title="View" onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='#EEF2FF'">
                                <i class="fas fa-eye" style="font-size:12px;"></i>
                            </a>
                            <button onclick="openEditModal({{ $sub->id }}, {{ json_encode(array_merge($sub->only(['name','vendor','category','type','billing_cycle','cost','currency','max_seats','website','notes','username']), ['logo_url' => $sub->logo_url, 'has_password' => !empty($sub->password), 'assigned_user_ids' => $sub->users->pluck('id')->toArray()])) }}, '{{ $sub->purchase_date?->format('Y-m-d') }}', '{{ $sub->renewal_date?->format('Y-m-d') }}', {{ json_encode($sub->notify_days) }})"
                                    style="width:32px;height:32px;border-radius:8px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#374151;border:none;cursor:pointer;transition:background .15s;"
                                    title="Edit" onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                                <i class="fas fa-pen" style="font-size:12px;"></i>
                            </button>
                            <button @click="openDelete({{ $sub->id }}, '{{ addslashes($sub->name) }}')"
                                    style="width:32px;height:32px;border-radius:8px;background:#FEF2F2;display:flex;align-items:center;justify-content:center;color:#DC2626;border:none;cursor:pointer;transition:background .15s;"
                                    title="Delete" onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                                <i class="fas fa-trash" style="font-size:12px;"></i>
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

    {{-- Create Modal --}}
    <div x-show="createModal" x-cloak
         style="position:fixed;inset:0;z-index:9999;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;overflow-y:auto;">
            <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);" @click="createModal = false"></div>
            <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:620px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
                <div style="padding:24px 24px 0;display:flex;align-items:center;justify-content:space-between;border-bottom:1.5px solid #F3F4F6;padding-bottom:16px;margin-bottom:24px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#111827;margin:0;">Add Subscription</h2>
                        <p style="font-size:13px;color:#9CA3AF;margin:2px 0 0;">Add a new software subscription or license</p>
                    </div>
                    <button @click="createModal = false"
                            style="width:32px;height:32px;border-radius:8px;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.subscriptions.store') }}" enctype="multipart/form-data" style="padding:0 24px 24px;">
                    @csrf
                    <div id="createFormContent"></div>
                    <div style="display:flex;gap:10px;margin-top:24px;">
                        <button type="submit"
                                style="flex:1;padding:11px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                            <i class="fas fa-plus" style="margin-right:6px;"></i> Create Subscription
                        </button>
                        <button type="button" @click="createModal = false"
                                style="padding:11px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="editModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);" onclick="closeEditModal()"></div>
        <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:620px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <div style="padding:24px 24px 0;display:flex;align-items:center;justify-content:space-between;border-bottom:1.5px solid #F3F4F6;padding-bottom:16px;margin-bottom:24px;">
                <div>
                    <h2 style="font-size:18px;font-weight:700;color:#111827;margin:0;">Edit Subscription</h2>
                    <p style="font-size:13px;color:#9CA3AF;margin:2px 0 0;">Update subscription details</p>
                </div>
                <button onclick="closeEditModal()"
                        style="width:32px;height:32px;border-radius:8px;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editForm" method="POST" enctype="multipart/form-data" style="padding:0 24px 24px;">
                @csrf @method('PUT')
                <div id="editFormContent"></div>
                <div style="display:flex;gap:10px;margin-top:24px;">
                    <button type="submit"
                            style="flex:1;padding:11px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-save" style="margin-right:6px;"></i> Save Changes
                    </button>
                    <button type="button" onclick="closeEditModal()"
                            style="padding:11px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirm Modal --}}
    <div x-show="deleteModal" x-cloak
         style="position:fixed;inset:0;z-index:9999;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;overflow-y:auto;">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);" @click="deleteModal = false"></div>
        <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:400px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.2);text-align:center;margin:auto;">
            <div style="width:56px;height:56px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-trash" style="color:#DC2626;font-size:20px;"></i>
            </div>
            <h3 style="font-size:17px;font-weight:700;color:#111827;margin:0 0 6px;">Delete Subscription?</h3>
            <p style="font-size:13px;color:#6B7280;margin:0 0 20px;">Type the subscription name to confirm: <strong x-text="deleteName" style="color:#111827;"></strong></p>
            <input x-model="deleteConfirmInput" type="text" placeholder="Type subscription name..."
                   style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;margin-bottom:16px;box-sizing:border-box;">
            <div style="display:flex;gap:10px;">
                <button @click="deleteModal = false"
                        style="flex:1;padding:10px;background:#F3F4F6;color:#374151;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <form x-ref="deleteForm" :action="'/admin/subscriptions/'+deleteId" method="POST" style="flex:1;">
                    @csrf @method('DELETE')
                    <button type="button" @click="submitDelete()"
                            :disabled="!deleteConfirmed"
                            :style="deleteConfirmed ? 'background:#DC2626;cursor:pointer;' : 'background:#F3F4F6;cursor:not-allowed;color:#9CA3AF;'"
                            style="width:100%;padding:10px;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;">
                        Delete
                    </button>
                </form>
            </div>
        </div>
        </div>
    </div>

</div>

<script>
const categories = @json(\App\Models\Subscription::categoryOptions());
const currencies = ['BHD', 'USD', 'EUR', 'GBP', 'SAR', 'AED', 'KWD'];
const allUsers = @json($users->map(fn($u) => ['id'=>$u->id, 'name'=>$u->name, 'email'=>$u->email])->values());

const LOGO_PLACEHOLDER = '<div style="width:100%;height:100%;background:linear-gradient(135deg,#818CF8 0%,#4F46E5 100%);display:flex;align-items:center;justify-content:center;"><i class="fas fa-layer-group" style="color:rgba(255,255,255,.85);font-size:30px;"></i></div>';

function buildFormHtml(data = {}) {
    const catOpts = Object.entries(categories).map(([k,v]) =>
        `<option value="${k}" ${data.category===k?'selected':''}>${v}</option>`).join('');
    const typeOpts = [['per_seat','Per Seat'],['site_license','Site License'],['shared','Shared']].map(([k,v]) =>
        `<option value="${k}" ${data.type===k?'selected':''}>${v}</option>`).join('');
    const cycleOpts = [['monthly','Monthly'],['annual','Annual'],['quarterly','Quarterly'],['one_time','One-time']].map(([k,v]) =>
        `<option value="${k}" ${data.billing_cycle===k?'selected':''}>${v}</option>`).join('');
    const currOpts = currencies.map(c =>
        `<option value="${c}" ${data.currency===c?'selected':''}>${c}</option>`).join('');
    const notifyDays = data.notify_days || [30,14,7,1];
    const notifyChecks = [1,7,14,30,60].map(d =>
        `<label style="display:inline-flex;align-items:center;gap:5px;font-size:13px;color:#374151;cursor:pointer;padding:5px 10px;background:${notifyDays.includes(d)?'#EEF2FF':'#F3F4F6'};border:1.5px solid ${notifyDays.includes(d)?'#C7D2FE':'#E5E7EB'};border-radius:8px;transition:all .15s;">
            <input type="checkbox" name="notify_days[]" value="${d}" ${notifyDays.includes(d)?'checked':''} onchange="toggleNotifyStyle(this)" style="accent-color:#4F46E5;"> ${d}d before
        </label>`).join('');

    /* ── Circular logo picker ── */
    const hasLogo = !!data.logo_url;
    const circleInner = hasLogo
        ? '<img src="' + escHtml(data.logo_url) + '" style="width:100%;height:100%;object-fit:contain;padding:8px;box-sizing:border-box;background:#fff;">'
        : LOGO_PLACEHOLDER;

    const removeRow = hasLogo
        ? '<div class="logo-remove-wrap" style="margin-top:7px;">' +
          '<label style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#DC2626;cursor:pointer;">' +
          '<input type="checkbox" name="remove_logo" value="1" onchange="handleRemoveLogo(this)"> Remove logo' +
          '</label></div>'
        : '';

    const logoTop =
        '<div class="logo-upload-wrapper" data-original-logo="' + escHtml(data.logo_url||'') + '" ' +
             'style="text-align:center;padding:4px 0 22px;margin-bottom:4px;border-bottom:1.5px solid #F3F4F6;">' +

            /* ring + circle + badge */
            '<div style="position:relative;display:inline-block;">' +

                /* outer glow ring */
                '<div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#818CF8,#4F46E5);padding:2.5px;box-shadow:0 0 0 4px rgba(99,102,241,.12),0 8px 24px rgba(99,102,241,.25);">' +

                    /* the clickable circle */
                    '<div class="logo-circle" ' +
                         'style="width:100%;height:100%;border-radius:50%;overflow:hidden;cursor:pointer;position:relative;background:#fff;" ' +
                         'ondragover="event.preventDefault();this.closest(\'.logo-upload-wrapper\').querySelector(\'.logo-ring\').style.boxShadow=\'0 0 0 4px rgba(99,102,241,.35),0 8px 24px rgba(99,102,241,.4)\';" ' +
                         'ondragleave="this.closest(\'.logo-upload-wrapper\').querySelector(\'.logo-ring\').style.boxShadow=\'0 0 0 4px rgba(99,102,241,.12),0 8px 24px rgba(99,102,241,.25)\';" ' +
                         'ondrop="handleLogoDrop(event,this)" ' +
                         'onmouseover="this.querySelector(\'.logo-hover\').style.opacity=\'1\'" ' +
                         'onmouseout="this.querySelector(\'.logo-hover\').style.opacity=\'0\'" ' +
                         'onclick="this.closest(\'.logo-upload-wrapper\').querySelector(\'.logo-file-input\').click()">' +

                        '<div class="logo-circle-inner" style="width:100%;height:100%;">' + circleInner + '</div>' +

                        /* hover overlay */
                        '<div class="logo-hover" style="position:absolute;inset:0;background:rgba(49,46,129,.55);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;opacity:0;transition:opacity .2s;pointer-events:none;">' +
                            '<i class="fas fa-camera" style="color:#fff;font-size:18px;"></i>' +
                            '<span style="color:rgba(255,255,255,.9);font-size:10px;font-weight:600;letter-spacing:.04em;">CHANGE</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' + /* end glow ring */

                /* camera badge */
                '<div onclick="this.closest(\'.logo-upload-wrapper\').querySelector(\'.logo-file-input\').click()" ' +
                     'style="position:absolute;bottom:3px;right:3px;width:28px;height:28px;border-radius:50%;' +
                            'background:linear-gradient(135deg,#818CF8,#4F46E5);border:2.5px solid #fff;' +
                            'display:flex;align-items:center;justify-content:center;cursor:pointer;' +
                            'box-shadow:0 3px 10px rgba(99,102,241,.55);transition:transform .15s;" ' +
                     'onmouseover="this.style.transform=\'scale(1.15)\'" onmouseout="this.style.transform=\'scale(1)\'">' +
                    '<i class="fas fa-camera" style="font-size:11px;color:#fff;pointer-events:none;"></i>' +
                '</div>' +

            '</div>' + /* end position:relative */

            /* hint text */
            '<div style="margin-top:10px;font-size:12px;color:#9CA3AF;line-height:1.4;">' +
                'Click or drag an image onto the circle' +
            '</div>' +

            removeRow +

            /* filename chip (shown after selection) */
            '<div class="logo-filename" style="display:none;margin-top:8px;justify-content:center;align-items:center;gap:6px;"></div>' +

            '<input type="file" class="logo-file-input" name="logo" accept="image/jpeg,image/png,image/webp,image/svg+xml" style="display:none;" onchange="handleLogoSelect(this)">' +
        '</div>'; /* end logo-upload-wrapper */

    /* ── Assign Users section ── */
    const assignedIds = data.assigned_user_ids || [];
    const userRows = allUsers.map(u =>
        '<label class="user-assign-row" style="display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:8px;cursor:pointer;transition:background .1s;" ' +
        'onmouseover="this.style.background=\'#F5F3FF\'" onmouseout="this.style.background=\'\'">' +
            '<input type="checkbox" name="user_ids[]" value="' + u.id + '"' + (assignedIds.includes(u.id) ? ' checked' : '') +
            ' style="accent-color:#4F46E5;width:15px;height:15px;flex-shrink:0;cursor:pointer;">' +
            '<div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#818CF8,#4F46E5);display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
                '<span style="color:#fff;font-size:11px;font-weight:700;">' + escHtml(u.name.charAt(0).toUpperCase()) + '</span>' +
            '</div>' +
            '<div style="min-width:0;flex:1;">' +
                '<div style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escHtml(u.name) + '</div>' +
                '<div style="font-size:11px;color:#9CA3AF;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escHtml(u.email) + '</div>' +
            '</div>' +
        '</label>'
    ).join('');

    const usersSection =
        '<div style="grid-column:1/-1;">' +
            '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;">' +
                '<i class="fas fa-users" style="color:#6366F1;margin-right:5px;"></i>Assign Users ' +
                '<span style="font-weight:400;color:#9CA3AF;">(optional)</span>' +
            '</label>' +
            '<div style="border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;">' +
                '<div style="padding:8px 10px;background:#F9FAFB;border-bottom:1.5px solid #E5E7EB;">' +
                    '<input type="text" placeholder="Search users…" oninput="filterUsers(this)" ' +
                    'style="width:100%;padding:6px 10px;border:1.5px solid #E5E7EB;border-radius:7px;font-size:12px;background:#fff;box-sizing:border-box;" ' +
                    'onfocus="this.style.borderColor=\'#6366F1\'" onblur="this.style.borderColor=\'#E5E7EB\'">' +
                '</div>' +
                '<div class="user-list" style="max-height:180px;overflow-y:auto;padding:4px;">' +
                    (userRows || '<div style="padding:16px;text-align:center;font-size:13px;color:#9CA3AF;">No users available</div>') +
                '</div>' +
            '</div>' +
        '</div>';

    /* ── Credentials section ── */
    const credSection = `
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Username / Email</label>
            <input type="text" name="username" value="${escHtml(data.username||'')}" placeholder="login@example.com"
                   autocomplete="off"
                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Password / License Key${data.has_password ? ' <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— leave blank to keep</span>' : ''}</label>
            <div style="position:relative;">
                <input type="password" name="password" value=""
                       placeholder="${data.has_password ? 'Leave blank to keep current' : 'Enter password or license key'}"
                       autocomplete="new-password"
                       style="width:100%;padding:9px 40px 9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                       onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                <button type="button" onclick="togglePwdField(this)"
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:#9CA3AF;display:flex;align-items:center;">
                    <i class="fas fa-eye" style="font-size:13px;"></i>
                </button>
            </div>
        </div>`;

    return logoTop + `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div style="grid-column:1/-1;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Subscription Name *</label>
            <input type="text" name="name" value="${escHtml(data.name||'')}" required
                   placeholder="e.g. Adobe Creative Cloud"
                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Vendor</label>
            <input type="text" name="vendor" value="${escHtml(data.vendor||'')}"
                   placeholder="e.g. Adobe"
                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Category *</label>
            <select name="category" style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                ${catOpts}
            </select>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">License Type *</label>
            <select name="type" style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                ${typeOpts}
            </select>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Billing Cycle *</label>
            <select name="billing_cycle" style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                ${cycleOpts}
            </select>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Cost *</label>
            <div style="display:flex;gap:6px;">
                <select name="currency" style="padding:9px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;">
                    ${currOpts}
                </select>
                <input type="number" name="cost" value="${data.cost||''}" step="0.001" min="0" required
                       placeholder="0.000"
                       style="flex:1;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                       onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
            </div>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Max Seats</label>
            <input type="number" name="max_seats" value="${data.max_seats||''}" min="1"
                   placeholder="Unlimited if blank"
                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Purchase Date</label>
            <input type="date" name="purchase_date" value="${data.purchase_date||''}"
                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;">
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Renewal Date</label>
            <input type="date" name="renewal_date" value="${data.renewal_date||''}"
                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;">
        </div>
        <div style="grid-column:1/-1;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Website</label>
            <input type="url" name="website" value="${escHtml(data.website||'')}"
                   placeholder="https://example.com"
                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
        </div>
        <div style="grid-column:1/-1;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;">Renewal Reminders</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">${notifyChecks}</div>
        </div>
        <div style="grid-column:1/-1;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Notes</label>
            <textarea name="notes" rows="3" placeholder="Login details, license keys, notes..."
                      style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;"
                      onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">${escHtml(data.notes||'')}</textarea>
        </div>
        ${credSection}
        ${usersSection}
    </div>`;
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function toggleNotifyStyle(el) {
    const lbl = el.closest('label');
    if (el.checked) { lbl.style.background='#EEF2FF'; lbl.style.borderColor='#C7D2FE'; }
    else             { lbl.style.background='#F3F4F6'; lbl.style.borderColor='#E5E7EB'; }
}

function handleLogoDrop(event, circleEl) {
    event.preventDefault();
    const ring = circleEl.closest('.logo-upload-wrapper').querySelector('.logo-ring');
    if (ring) ring.style.boxShadow = '0 0 0 4px rgba(99,102,241,.12),0 8px 24px rgba(99,102,241,.25)';
    const file = event.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        applyLogoFile(file, circleEl.closest('.logo-upload-wrapper'));
    }
}

function handleLogoSelect(input) {
    if (input.files[0]) applyLogoFile(input.files[0], input.closest('.logo-upload-wrapper'));
}

function applyLogoFile(file, wrapper) {
    const inner     = wrapper.querySelector('.logo-circle-inner');
    const fileEl    = wrapper.querySelector('.logo-filename');
    const removeWrap = wrapper.querySelector('.logo-remove-wrap');
    const input     = wrapper.querySelector('.logo-file-input');
    const reader    = new FileReader();
    reader.onload = function(e) {
        inner.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:contain;padding:8px;box-sizing:border-box;background:#fff;">';
        if (fileEl) {
            fileEl.style.display = 'inline-flex';
            fileEl.innerHTML =
                '<span style="font-size:11.5px;color:#16A34A;font-weight:600;display:flex;align-items:center;gap:4px;">' +
                    '<i class="fas fa-check-circle"></i>' + escHtml(file.name) +
                '</span>' +
                '<button type="button" onclick="clearLogoFile(this)" ' +
                        'style="font-size:11px;color:#9CA3AF;background:none;border:none;cursor:pointer;padding:0 2px;text-decoration:underline;">Remove</button>';
        }
        if (removeWrap) removeWrap.style.display = 'none';
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
    };
    reader.readAsDataURL(file);
}

function clearLogoFile(btn) {
    const wrapper     = btn.closest('.logo-upload-wrapper');
    const inner       = wrapper.querySelector('.logo-circle-inner');
    const fileEl      = wrapper.querySelector('.logo-filename');
    const removeWrap  = wrapper.querySelector('.logo-remove-wrap');
    const originalLogo = wrapper.dataset.originalLogo;

    wrapper.querySelector('.logo-file-input').value = '';

    if (originalLogo) {
        inner.innerHTML = '<img src="' + originalLogo + '" style="width:100%;height:100%;object-fit:contain;padding:8px;box-sizing:border-box;background:#fff;">';
        if (removeWrap) removeWrap.style.display = '';
    } else {
        inner.innerHTML = LOGO_PLACEHOLDER;
    }

    if (fileEl) { fileEl.style.display = 'none'; fileEl.innerHTML = ''; }

    /* restore circle opacity if remove was checked */
    const circle = wrapper.querySelector('.logo-circle');
    if (circle) { circle.style.opacity = '1'; circle.style.filter = 'none'; }
}

function handleRemoveLogo(checkbox) {
    const circle = checkbox.closest('.logo-upload-wrapper').querySelector('.logo-circle');
    circle.style.opacity = checkbox.checked ? '.25' : '1';
    circle.style.filter  = checkbox.checked ? 'grayscale(1)' : 'none';
}

function togglePwdField(btn) {
    const input = btn.parentNode.querySelector('input');
    const icon  = btn.querySelector('i');
    if (input.type === 'password') { input.type = 'text';     icon.className = 'fas fa-eye-slash'; }
    else                           { input.type = 'password'; icon.className = 'fas fa-eye'; }
}

function filterUsers(input) {
    const q = input.value.trim().toLowerCase();
    const list = input.closest('div').nextElementSibling;
    list.querySelectorAll('.user-assign-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('createFormContent').innerHTML = buildFormHtml({ category:'other', type:'per_seat', billing_cycle:'annual', currency:'BHD' });
});

function openEditModal(id, data, purchaseDate, renewalDate, notifyDays) {
    data.purchase_date = purchaseDate;
    data.renewal_date  = renewalDate;
    data.notify_days   = notifyDays;
    document.getElementById('editFormContent').innerHTML = buildFormHtml(data);
    document.getElementById('editForm').action = '/admin/subscriptions/' + id;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

@endsection

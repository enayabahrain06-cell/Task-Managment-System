@extends('layouts.app')
@section('title', 'Ad Budget Monitor')

@section('content')
@php
    // Canonical colors for the social-media task posting status (Posted/Pending).
    // These aren't lifecycle task statuses (App\Support\TaskStatusColors doesn't cover
    // them), so this is the single source of truth shared between the desktop table
    // below and the mobile card list (_mobile-index.blade.php) — never hardcode these
    // hex values a second time.
    $socialStatusColors = [
        'posted'  => ['bg' => '#D1FAE5', 'color' => '#059669', 'icon' => 'fa-circle-check', 'label' => 'Posted'],
        'pending' => ['bg' => '#FEF3C7', 'color' => '#D97706', 'icon' => 'fa-clock', 'label' => 'Pending'],
    ];
@endphp
<style>
.sb-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #F0F0F0;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    padding: 20px 24px;
    margin-bottom: 16px;
}
.sb-stat {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #F0F0F0;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.sb-stat-icon {
    width: 40px; height: 40px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 16px;
}
.sb-table { width: 100%; min-width: 600px; border-collapse: collapse; font-size: 13px; }
.sb-table th {
    padding: 9px 12px; text-align: left; font-size: 11px;
    font-weight: 700; color: #9CA3AF; text-transform: uppercase;
    letter-spacing: .06em; border-bottom: 2px solid #F3F4F6;
    white-space: nowrap; background: #FAFAFA;
}
.sb-table td { padding: 11px 12px; border-bottom: 1px solid #F3F4F6; color: #374151; vertical-align: middle; }
.sb-table tr:last-child td { border-bottom: none; }
.sb-table tr:hover td { background: #FAFBFF; }
.sb-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.sb-filter-btn {
    padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
    border: 1.5px solid #E5E7EB; background: #fff; color: #6B7280;
    cursor: pointer; transition: all .15s; text-decoration: none; display: inline-block;
}
.sb-filter-btn.active { background: #EEF2FF; color: #4F46E5; border-color: #C7D2FE; }
.sb-select {
    font-size: 12px; border: 1.5px solid #E5E7EB; border-radius: 8px;
    padding: 7px 28px 7px 10px; background: #fff; color: #374151; outline: none;
    appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
}
.sb-stats-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
.sb-filter-bar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.sb-filter-form { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
@media (max-width: 900px) {
    .sb-stats-grid { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 480px) {
    .sb-stats-grid { grid-template-columns: repeat(2,1fr); gap:8px; }
    .sb-filter-bar { flex-direction:column; align-items:stretch; }
    .sb-filter-form { flex-direction:column; width:100%; }
    .sb-filter-form > * { width:100%; min-width:0; }
    .sb-filter-form .sb-select { width:100%; }
}

/* Mobile (<=768px): the entire mobile experience for this page — header, status
   tabs, project/customer filters, KPI cards, task list, pagination — lives in
   admin/social-budget/_mobile-index.blade.php, which hides all the
   .sb-desktop-*/.sb-stats-grid/.sb-tasks-table-wrap elements below via its own
   <style> block. Desktop markup here is untouched. */
</style>

@include('admin.social-budget._mobile-index')

{{-- Page Header --}}
<div class="sb-desktop-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Ad Budget Monitor</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">Track social media tasks and their ad spend across all projects</p>
    </div>
</div>

{{-- Summary Cards --}}
<div class="sb-stats-grid mob-kpi-row">
    <div class="sb-stat mob-kpi-card">
        <div class="sb-stat-icon" style="background:#F5F3FF;">
            <i class="fas fa-share-alt" style="color:#7C3AED;"></i>
        </div>
        <div>
            <p style="font-size:22px;font-weight:800;color:#111827;margin:0;">{{ $totalCount }}</p>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Total Social Tasks</p>
        </div>
    </div>
    <div class="sb-stat mob-kpi-card">
        <div class="sb-stat-icon" style="background:#FEF3C7;">
            <i class="fas fa-clock" style="color:#D97706;"></i>
        </div>
        <div>
            <p style="font-size:22px;font-weight:800;color:#D97706;margin:0;">{{ $pendingCount }}</p>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Pending Posting</p>
        </div>
    </div>
    <div class="sb-stat mob-kpi-card">
        <div class="sb-stat-icon" style="background:#D1FAE5;">
            <i class="fas fa-circle-check" style="color:#059669;"></i>
        </div>
        <div>
            <p style="font-size:22px;font-weight:800;color:#059669;margin:0;">{{ $postedCount }}</p>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Posted</p>
        </div>
    </div>
    <div class="sb-stat mob-kpi-card">
        <div class="sb-stat-icon" style="background:#FEF3C7;">
            <i class="fas fa-wallet" style="color:#D97706;"></i>
        </div>
        <div>
            <p style="font-size:22px;font-weight:800;color:#111827;margin:0;">{{ $withBudget }}</p>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">With Budget Set</p>
        </div>
    </div>
    <div class="sb-stat mob-kpi-card">
        <div class="sb-stat-icon" style="background:#EFF6FF;">
            <i class="fas fa-coins" style="color:#2563EB;"></i>
        </div>
        <div>
            <p style="font-size:22px;font-weight:800;color:#2563EB;margin:0;">{{ $totalBudgetBhd > 0 ? number_format($totalBudgetBhd) : '—' }}</p>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Total Budget (BHD)</p>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="sb-card" style="padding:14px 20px;">
    <form method="GET" id="sbFilterForm" action="{{ route('admin.social-budget.index') }}" class="sb-filter-form">

        {{-- Status tabs (desktop) --}}
        <div class="sb-status-tabs-desktop" style="display:flex;gap:4px;background:#F3F4F6;border-radius:9px;padding:3px;">
            @foreach(['all'=>'All','pending'=>'Pending','posted'=>'Posted'] as $val => $label)
            <button type="button"
                    onclick="document.getElementById('status-input').value='{{ $val }}'; this.closest('form').submit();"
                    style="padding:5px 14px;font-size:12px;font-weight:600;border:none;border-radius:7px;cursor:pointer;transition:all .15s;
                           {{ $status === $val ? 'background:#fff;color:#4F46E5;box-shadow:0 1px 3px rgba(0,0,0,.1);' : 'background:none;color:#6B7280;' }}">
                {{ $label }}
                @if($val === 'pending' && $pendingCount > 0)
                    <span style="margin-left:4px;background:#FEF3C7;color:#D97706;padding:1px 6px;border-radius:20px;font-size:10px;">{{ $pendingCount }}</span>
                @endif
            </button>
            @endforeach
        </div>
        <input type="hidden" id="status-input" name="status" value="{{ $status }}">

        {{-- Project filter (desktop) --}}
        <select name="project_id" id="sb-project-select" class="sb-select sb-select-desktop" onchange="this.form.submit()">
            <option value="">All Projects</option>
            @foreach($allProjects as $p)
            <option value="{{ $p->id }}" {{ $projectId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>

        {{-- Customer filter (desktop) --}}
        <select name="customer_id" id="sb-customer-select" class="sb-select sb-select-desktop" onchange="this.form.submit()">
            <option value="">All Customers</option>
            @foreach($allCustomers as $c)
            <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>
                {{ $c->name }}@if($c->company) — {{ $c->company }}@endif
            </option>
            @endforeach
        </select>

        @if($projectId || $customerId || $status !== 'all')
        <a href="{{ route('admin.social-budget.index') }}"
           style="font-size:12px;color:#6B7280;text-decoration:none;display:flex;align-items:center;gap:4px;"
           onmouseover="this.style.color='#EF4444'" onmouseout="this.style.color='#6B7280'">
            <i class="fas fa-xmark" style="font-size:11px;"></i> Clear
        </a>
        @endif

    </form>
</div>

{{-- Table --}}
<div class="sb-card sb-desktop-tasklist" style="padding:0;overflow:hidden;">
    <div style="padding:16px 24px 12px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;border-radius:9px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-wallet" style="color:#D97706;font-size:13px;"></i>
            </div>
            <div>
                <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Social Media Tasks</p>
                <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">
                    {{ $tasks->count() }} {{ $status !== 'all' ? $status : '' }} task{{ $tasks->count() !== 1 ? 's' : '' }}
                    @if($projectId || $customerId) · filtered @endif
                </p>
            </div>
        </div>
    </div>

    <div class="mob-table-cards sb-tasks-table-wrap" style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
    <table class="sb-table">
        <thead>
            <tr>
                <th>Task</th>
                <th>Project</th>
                <th>Customer</th>
                <th>Assigned To</th>
                <th>Ad Budget</th>
                <th>Caption / Instructions</th>
                <th>Status</th>
                <th>Posted On</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $t)
            <tr>
                <td data-label="Task">
                    <a href="{{ route('admin.tasks.show', $t['id']) }}"
                       style="font-weight:600;color:#111827;text-decoration:none;"
                       onmouseover="this.style.color='#4F46E5'" onmouseout="this.style.color='#111827'">
                        {{ $t['title'] }}
                    </a>
                    <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;">Added {{ $t['created_at'] }}</p>
                </td>
                <td data-label="Project">
                    <span style="font-size:12px;color:#374151;font-weight:500;">{{ $t['project'] }}</span>
                </td>
                <td data-label="Customer">
                    <span style="font-size:12px;color:#374151;">{{ $t['customer'] }}</span>
                </td>
                <td data-label="Assigned To">
                    @if($t['social_user'] !== '—')
                        <span class="sb-badge" style="background:#E0F2FE;color:#0284C7;">
                            <i class="fas fa-share-alt" style="font-size:9px;"></i>
                            {{ $t['social_user'] }}
                        </span>
                    @else
                        <span style="color:#D1D5DB;font-size:12px;">—</span>
                    @endif
                </td>
                <td data-label="Ad Budget">
                    @if(!empty($t['budget']))
                        <span class="sb-badge" style="background:#FEF3C7;color:#D97706;font-size:12px;font-weight:700;">
                            <i class="fas fa-wallet" style="font-size:10px;"></i>
                            {{ $t['budget'] }}
                        </span>
                    @else
                        <span style="color:#D1D5DB;font-size:11px;">Not set</span>
                    @endif
                </td>
                <td data-label="Caption / Instructions" style="max-width:220px;">
                    @if(!empty($t['caption']))
                        <p style="font-size:11px;color:#374151;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;"
                           title="{{ $t['caption'] }}">
                            <i class="fas fa-pen-nib" style="font-size:9px;color:#8B5CF6;margin-right:3px;"></i>{{ $t['caption'] }}
                        </p>
                    @endif
                    @if(!empty($t['description']))
                        <p style="font-size:11px;color:#6B7280;margin:2px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;"
                           title="{{ $t['description'] }}">
                            <i class="fas fa-align-left" style="font-size:9px;color:#9CA3AF;margin-right:3px;"></i>{{ $t['description'] }}
                        </p>
                    @endif
                    @if(empty($t['caption']) && empty($t['description']))
                        <span style="color:#D1D5DB;font-size:11px;">—</span>
                    @endif
                </td>
                <td data-label="Status">
                    @php $ss = $socialStatusColors[$t['posted'] ? 'posted' : 'pending']; @endphp
                    <span class="sb-badge" style="background:{{ $ss['bg'] }};color:{{ $ss['color'] }};">
                        <i class="fas {{ $ss['icon'] }}" style="font-size:9px;"></i>
                        {{ $ss['label'] }}
                    </span>
                </td>
                <td data-label="Posted On" style="font-size:11px;color:#6B7280;white-space:nowrap;">
                    {{ $t['posted_at'] ?? '—' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;padding:48px 24px;">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                        <div style="width:52px;height:52px;border-radius:14px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-wallet" style="color:#D97706;font-size:22px;"></i>
                        </div>
                        <p style="font-size:14px;font-weight:600;color:#374151;margin:0;">
                            @if($status === 'posted') No posted tasks yet
                            @elseif($status === 'pending') No pending tasks
                            @else No social media tasks yet
                            @endif
                        </p>
                        <p style="font-size:12px;color:#9CA3AF;margin:0;max-width:340px;text-align:center;">
                            When a task is approved and assigned for social posting, it will appear here with its budget and caption.
                        </p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($tasks->hasPages())
@php $pgParams = array_filter(['project_id'=>$projectId,'customer_id'=>$customerId,'status'=>$status!=='all'?$status:null], fn($v)=>$v!==null); @endphp
<div class="sb-desktop-pager" style="margin-top:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <span style="font-size:12px;color:#6B7280;">Showing {{ $tasks->firstItem() }}–{{ $tasks->lastItem() }} of {{ $tasks->total() }} results</span>
    <div style="display:flex;gap:4px;align-items:center;">
        @if($tasks->onFirstPage())
            <span style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#F3F4F6;color:#D1D5DB;cursor:default;">‹ Prev</span>
        @else
            <a href="{{ $tasks->appends($pgParams)->previousPageUrl() }}" style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#F3F4F6;color:#374151;text-decoration:none;">‹ Prev</a>
        @endif
        @foreach($tasks->appends($pgParams)->getUrlRange(1, $tasks->lastPage()) as $page => $url)
            @if($page == $tasks->currentPage())
                <span style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;background:#4F46E5;color:#fff;min-width:34px;text-align:center;">{{ $page }}</span>
            @else
                <a href="{{ $url }}" style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#F3F4F6;color:#374151;text-decoration:none;min-width:34px;text-align:center;">{{ $page }}</a>
            @endif
        @endforeach
        @if($tasks->hasMorePages())
            <a href="{{ $tasks->appends($pgParams)->nextPageUrl() }}" style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#F3F4F6;color:#374151;text-decoration:none;">Next ›</a>
        @else
            <span style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#F3F4F6;color:#D1D5DB;cursor:default;">Next ›</span>
        @endif
    </div>
</div>
@endif

@endsection

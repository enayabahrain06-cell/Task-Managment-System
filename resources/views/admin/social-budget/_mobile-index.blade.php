{{--
  Mobile restyle of resources/views/admin/social-budget/index.blade.php  (Ad Budget Monitor)
  Scope: <=768px only. Desktop markup untouched.

  Now built entirely on the shared uds-* components (resources/views/components/mobile/*)
  instead of bespoke .m-* classes, so this page can't drift from the rest of the mobile
  design system: <x-mobile.segmented> for the status tabs, <x-mobile.filter-chip> for the
  project/customer filters, <x-mobile.kpi-grid>/<x-mobile.kpi-tile> for the stat row, and
  <x-mobile.record-card> for each task.

  Vars (from Admin\SocialBudgetController@index): $tasks (paginated, already
  transformed to plain arrays — NOT raw Task models), $stats, $allProjects, $allCustomers,
  $status, $socialStatusColors (defined in index.blade.php, shared with the desktop table).
  Task array keys: id, title, project (string), customer (string), social_user (string),
                   budget (string|null — free text in real data, not always numeric),
                   posted (bool), created_at / posted_at (pre-formatted strings)
  Route: admin.social-budget.index (?status=), admin.tasks.show
--}}

<div class="adb-mobile">

    <div class="m-head">
        <div class="m-head-text">
            <div class="m-title">Ad Budget Monitor</div>
            <div class="m-sub">Social tasks and their ad spend</div>
        </div>
    </div>

    {{-- Status tabs --}}
    @php
        $sbSegOptions = collect(['all' => 'All', 'pending' => 'Pending', 'posted' => 'Posted'])
            ->map(fn($label, $key) => [
                'key'   => $key,
                'label' => $label,
                'count' => $stats[$key === 'all' ? 'total' : $key],
                'href'  => route('admin.social-budget.index', array_merge(request()->query(), ['status' => $key])),
            ])->values()->all();
    @endphp
    <x-mobile.segmented :options="$sbSegOptions" :active="$status" />

    {{-- Project / customer filters --}}
    <form method="GET" action="{{ route('admin.social-budget.index') }}" class="uds-chiprow">
        <input type="hidden" name="status" value="{{ $status }}">
        <x-mobile.filter-chip :label="optional($allProjects->firstWhere('id', $projectId))->name ?? 'All projects'" :active="(bool) $projectId">
            <select name="project_id" onchange="this.form.submit()">
                <option value="">All projects</option>
                @foreach ($allProjects as $p)
                    <option value="{{ $p->id }}" @selected($projectId == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </x-mobile.filter-chip>
        <x-mobile.filter-chip :label="optional($allCustomers->firstWhere('id', $customerId))->name ?? 'All customers'" :active="(bool) $customerId">
            <select name="customer_id" onchange="this.form.submit()">
                <option value="">All customers</option>
                @foreach ($allCustomers as $c)
                    <option value="{{ $c->id }}" @selected($customerId == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </x-mobile.filter-chip>
    </form>

    {{-- KPI grid --}}
    <x-mobile.kpi-grid>
        <x-mobile.kpi-tile label="Social tasks" :value="$stats['total']" sub="in this view" />
        <x-mobile.kpi-tile label="Pending posting" :value="$stats['pending']" :sub="$stats['pending'] ? 'waiting to go live' : 'nothing waiting'" />
        <x-mobile.kpi-tile label="Posted" :value="$stats['posted']" sub="live on channels" />
        <x-mobile.kpi-tile label="Ad spend" :value="'BHD ' . number_format($stats['spend'], 3)" :sub="'budget set on ' . $stats['withBudget'] . ' tasks'" money />
    </x-mobile.kpi-grid>

    {{-- Task cards --}}
    @if ($tasks->isEmpty())
        <x-mobile.empty-state title="No social tasks" sub="Nothing matches these filters." icon="fa-wallet" />
    @else
        <div class="m-list">
            @foreach ($tasks as $t)
                @php
                    // social_budget is free-text in real data (a note, not always a number) —
                    // format as currency only when it's actually numeric, otherwise show as-is.
                    $budgetText = null;
                    if (!empty($t['budget'])) {
                        $budgetText = is_numeric(trim($t['budget']))
                            ? 'BHD ' . number_format((float) trim($t['budget']), 3)
                            : $t['budget'];
                    }
                    $initial = $t['social_user'] !== '—' ? mb_substr($t['social_user'], 0, 1) : '?';
                    $ss = $socialStatusColors[$t['posted'] ? 'posted' : 'pending'];
                @endphp
                <x-mobile.record-card
                    :href="route('admin.tasks.show', $t['id'])"
                    :title="$t['title']"
                    :context="$t['project'] . ' · ' . $t['customer']"
                    :dueText="($t['posted'] ? 'Posted ' : 'Added ') . ($t['posted'] ? $t['posted_at'] : $t['created_at'])"
                    style="margin-bottom:10px;"
                >
                    <x-slot:topLeft>
                        <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#374151;">
                            <span style="width:20px;height:20px;border-radius:99px;background:var(--mob-brand,#4F46E5);color:#fff;font-size:9px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $initial }}</span>
                            {{ $t['social_user'] !== '—' ? $t['social_user'] : 'Unassigned' }}
                        </span>
                    </x-slot:topLeft>
                    <x-slot:topRight>
                        <span style="font-size:12px;font-weight:700;white-space:nowrap;color:{{ $budgetText ? '#D97706' : '#B6BAC4' }};">{{ $budgetText ?? 'Not set' }}</span>
                    </x-slot:topRight>
                    <x-slot:status>
                        <span class="status-badge" style="background:{{ $ss['bg'] }};color:{{ $ss['color'] }};">{{ $ss['label'] }}</span>
                    </x-slot:status>
                </x-mobile.record-card>
            @endforeach
        </div>

        @if ($tasks->hasPages())
            <div class="m-pager">{{ $tasks->withQueryString()->links() }}</div>
        @endif
    @endif
</div>

<style>
.adb-mobile { display: none; }

@media (max-width: 768px) {
    /* replace the desktop header, status tabs, selects, table, KPI grid, and
       pagination — doubled selectors to out-specificity this page's own
       !important mobile rules for the same classes */
    .sb-desktop-header.sb-desktop-header, .sb-status-tabs-desktop.sb-status-tabs-desktop,
    .sb-select-desktop.sb-select-desktop, .sb-tasks-table-wrap.sb-tasks-table-wrap,
    .sb-stats-grid.sb-stats-grid, .sb-desktop-tasklist.sb-desktop-tasklist,
    .sb-desktop-pager.sb-desktop-pager { display: none !important; }

    .adb-mobile { display: block; }

    .m-head { padding: 2px 0 14px; }
    .m-title { font-size: 20px; font-weight: 700; letter-spacing: -.02em; color: #111827; }
    .m-sub { font-size: 12px; font-weight: 500; color: #6B7280; margin-top: 2px; }

    .m-list { display: flex; flex-direction: column; gap: 10px; margin-top: 16px; }

    /* Tap target for the task title link inside each mobile card (G2) */
    .m-list .uds-card-title { display: flex; align-items: center; min-height: 44px; }

    /* status-badge (shared with subscriptions/desktop) needs the compact mobile-spec
       radius/padding/weight here too — the base rule lives per-page on desktop. */
    .status-badge { display: inline-flex; align-items: center; font-size: 11px !important; font-weight: 700 !important; padding: 4px 9px !important; border-radius: 8px !important; }

    .m-pager { margin-top: 16px; }
}
</style>

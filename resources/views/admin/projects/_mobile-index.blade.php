{{--
  Mobile restyle of resources/views/admin/projects/index.blade.php
  Scope: <=768px only. Desktop markup above/below this partial stays as it is.

  Uses the shared mobile design system components:
    - <x-mobile.segmented>   (resources/views/components/mobile/segmented.blade.php)
    - <x-mobile.record-card> (resources/views/components/mobile/record-card.blade.php)
  Per the approved spec for admin/projects: compact "+ New" header, segmented
  Active/Completed with counts, no gradient stat cards, no Table/Cards toggle,
  record cards with progress + state chip + deadline, a "···" actions button.

  Vars (from Admin\ProjectController@index): $activeProjects, $completedProjects
  Project: id, name, description, color, status ('active'|'completed'), tasks_count,
           completed_tasks_count, deadline (Carbon|null)
  Route names used: admin.projects.index (?status=), admin.projects.show,
                     admin.projects.edit, admin.projects.create,
                     admin.projects.reopen, admin.projects.close, admin.projects.destroy
--}}

@php
    $state = request('status') === 'completed' ? 'done' : 'active';
    $list  = $state === 'done' ? $completedProjects : $activeProjects;
@endphp

<div class="prj-mobile">

    {{-- HEADER: compact + New, no full-width block --}}
    <div class="prj-head">
        <div class="prj-head-text">
            <div class="prj-title">Projects</div>
            <div class="prj-sub">
                {{ $state === 'done'
                    ? trans_choice(':count completed project|:count completed projects', $completedProjects->count())
                    : trans_choice(':count active project|:count active projects', $activeProjects->count()) }}
            </div>
        </div>
        @if(auth()->user()->hasPermission('manage_projects'))
            <a href="{{ route('admin.projects.create') }}" class="prj-new">
                <i class="fas fa-plus"></i> New
            </a>
        @endif
    </div>

    {{-- SEGMENTED: replaces the two gradient stat cards AND the Table/Cards toggle --}}
    <x-mobile.segmented
        :options="[
            ['key' => 'active', 'label' => 'Active', 'count' => $activeProjects->count(), 'href' => route('admin.projects.index')],
            ['key' => 'done', 'label' => 'Completed', 'count' => $completedProjects->count(), 'href' => route('admin.projects.index', ['status' => 'completed'])],
        ]"
        active="{{ $state }}"
        style="margin-bottom:14px;"
    />

    @if ($list->isEmpty())
        <div class="uds-empty">
            <p class="uds-empty-title">Nothing here</p>
            <p class="uds-empty-sub">No {{ $state === 'done' ? 'completed' : 'active' }} projects right now.</p>
        </div>
    @else
        <div class="prj-list">
            @foreach ($list as $p)
                @php
                    $c    = \App\Support\ProjectStatusColors::for($p->status);
                    $done = (int) $p->completed_tasks_count;
                    $all  = (int) $p->tasks_count;
                    $pct  = $all > 0 ? (int) round($done / $all * 100) : 0;
                    $late = $p->deadline && $p->deadline->isPast() && $p->status === 'active';
                    $dot  = $p->color ?: '#4F46E5';

                    $dueText = ! $p->deadline
                        ? 'Rolling'
                        : ($late
                            ? 'Overdue · ' . $p->deadline->format('M d, Y')
                            : ($p->status === 'active'
                                ? 'Due · ' . $p->deadline->format('M d, Y')
                                : 'Closed ' . $p->deadline->format('M d, Y')));
                @endphp

                <x-mobile.record-card
                    :href="route('admin.projects.show', $p)"
                    :title="$p->name"
                    :context="$p->description"
                    progressLabel="{{ $done }} of {{ $all }} tasks"
                    :progressPct="$pct"
                    :progressColor="$dot"
                    :dueText="$dueText"
                    :overdue="$late"
                >
                    <x-slot:status>
                        <span class="prj-chip" style="color:{{ $c['text'] }};background:{{ $c['bg'] }}">{{ $c['label'] }}</span>
                    </x-slot:status>
                    <x-slot:actions>
                        <button type="button"
                                class="prj-more-btn"
                                onclick="openProjMenu(event, this)"
                                data-view="{{ route('admin.projects.show', $p) }}"
                                data-edit="{{ route('admin.projects.edit', $p) }}"
                                data-reopen-url="{{ $p->status === 'completed' ? route('admin.projects.reopen', $p) : '' }}"
                                data-close-url="{{ $p->status !== 'completed' ? route('admin.projects.close', $p) : '' }}"
                                data-delete-url="{{ auth()->user()->hasPermission('delete_projects') ? route('admin.projects.destroy', $p) : '' }}"
                                data-name="{{ addslashes($p->name) }}"
                                aria-label="Project actions">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </button>
                    </x-slot:actions>
                </x-mobile.record-card>
            @endforeach
        </div>
    @endif
</div>

<style>
.prj-mobile { display: none; }

@media (max-width: 768px) {
    .prj-mobile { display: block; }

    /* hide the desktop header, tab bar, stat cards/banner, and Table/Cards toggle + views.
       Selectors are doubled (.x.x) to out-specificity the page's own later !important
       mobile rules for these same classes (e.g. .proj-stat-grid's swipe-strip conversion),
       since last-in-source wins when two !important rules tie on specificity. */
    .proj-page-header.proj-page-header, .proj-tab-bar.proj-tab-bar, .proj-stat-grid.proj-stat-grid,
    .proj-desktop-banner.proj-desktop-banner, .proj-view-toggle.proj-view-toggle,
    .proj-desktop-view.proj-desktop-view { display: none !important; }

    .prj-head { display: flex; align-items: flex-start; gap: 10px; padding: 2px 0 14px; }
    .prj-head-text { flex: 1; min-width: 0; }
    .prj-title { font-size: 20px; font-weight: 700; letter-spacing: -.02em; color: #111827; }
    .prj-sub { font-size: 12px; font-weight: 500; color: #6B7280; margin-top: 2px; }
    .prj-new {
        flex: none; min-height: 38px; padding: 0 13px; border-radius: 11px; border: 0;
        background: var(--mob-brand-grad, linear-gradient(135deg,#4F46E5,#6366F1)); color: #fff;
        font-size: 13px; font-weight: 700; letter-spacing: -.01em; text-decoration: none;
        display: inline-flex; align-items: center; gap: 6px;
        box-shadow: 0 8px 16px -8px rgba(79,70,229,.7);
    }
    .prj-new i { font-size: 11px; }

    .prj-list { display: flex; flex-direction: column; gap: 10px; }

    /* Project status chip — put in the record-card's status slot */
    .prj-chip {
        display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 8px;
        font-size: 11px; font-weight: 700; white-space: nowrap;
    }

    /* "···" actions button — record-card's actions slot */
    .prj-more-btn {
        flex: none; width: 34px; height: 34px; border: 1px solid #E1E4EA; border-radius: 11px;
        background: #fff; color: #6B7280; font-size: 15px; font-weight: 700; line-height: 1;
        display: flex; align-items: center; justify-content: center; cursor: pointer;
    }
}
</style>

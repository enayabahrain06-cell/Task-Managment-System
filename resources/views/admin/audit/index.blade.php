@extends('layouts.app')
@section('title', 'Audit Log')

@section('content')
@php
    $actionMeta = [
        // User
        'user.created'          => ['fa-user-plus',      '#059669', '#D1FAE5', 'Account Created'],
        'user.updated'          => ['fa-user-pen',        '#6366F1', '#EEF2FF', 'Profile Updated'],
        'user.deleted'          => ['fa-user-slash',      '#DC2626', '#FEE2E2', 'Account Deleted'],
        'user.deactivated'      => ['fa-user-lock',       '#D97706', '#FEF3C7', 'Account Deactivated'],
        'user.reactivated'      => ['fa-user-check',      '#059669', '#D1FAE5', 'Account Reactivated'],
        'user.role_changed'     => ['fa-user-shield',     '#7C3AED', '#EDE9FE', 'Role Changed'],
        'user.password_changed' => ['fa-key',             '#6B7280', '#F3F4F6', 'Password Changed'],
        'user.archived'         => ['fa-box-archive',     '#DC2626', '#FEE2E2', 'User Archived'],
        'user.restored'         => ['fa-rotate-left',     '#059669', '#D1FAE5', 'User Restored'],
        'user.held'             => ['fa-hand',            '#D97706', '#FEF3C7', 'Account On Hold'],
        'user.released'         => ['fa-hand-sparkles',   '#059669', '#D1FAE5', 'Account Released'],
        // Tasks
        'task.approved'         => ['fa-circle-check',   '#059669', '#D1FAE5', 'Task Approved'],
        'task.rejected'         => ['fa-circle-xmark',   '#DC2626', '#FEE2E2', 'Revision Requested'],
        'task.reassigned'       => ['fa-arrows-rotate',  '#6366F1', '#EEF2FF', 'Task Reassigned'],
        'task.deleted'          => ['fa-trash',           '#DC2626', '#FEE2E2', 'Task Deleted'],
        'task.force_deleted'    => ['fa-skull',           '#991B1B', '#FEE2E2', 'Task Perm. Deleted'],
        'task.reopened'         => ['fa-rotate',          '#D97706', '#FEF3C7', 'Task Reopened'],
        'task.archived'         => ['fa-box-archive',     '#6B7280', '#F3F4F6', 'Task Archived'],
        'task.delivered'        => ['fa-truck',           '#047857', '#ECFDF5', 'Task Delivered'],
        'tasks.bulk_transferred'=> ['fa-right-left',      '#D97706', '#FEF3C7', 'Tasks Transferred'],
        // Projects
        'project.created'       => ['fa-folder-plus',    '#059669', '#D1FAE5', 'Project Created'],
        'project.updated'       => ['fa-folder-open',    '#6366F1', '#EEF2FF', 'Project Updated'],
        'project.deleted'       => ['fa-folder-minus',   '#DC2626', '#FEE2E2', 'Project Deleted'],
        'project.reopened'      => ['fa-folder',          '#D97706', '#FEF3C7', 'Project Reopened'],
        'project.closed'        => ['fa-folder-closed',  '#6B7280', '#F3F4F6', 'Project Closed'],
        // Roles
        'role.created'          => ['fa-id-badge',       '#059669', '#D1FAE5', 'Role Created'],
        'role.updated'          => ['fa-id-card',        '#6366F1', '#EEF2FF', 'Role Updated'],
        'role.deleted'          => ['fa-id-card-clip',   '#DC2626', '#FEE2E2', 'Role Deleted'],
        // Settings / System
        'settings.updated'      => ['fa-gear',           '#6B7280', '#F3F4F6', 'Settings Updated'],
        'data.cleared'          => ['fa-eraser',         '#DC2626', '#FEE2E2', 'Data Cleared'],
        'system.restored'       => ['fa-database',       '#D97706', '#FEF3C7', 'System Restored'],
    ];
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">System Audit Log</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">Full record of administrative actions — who did what and when</p>
    </div>
    <span style="background:#EEF2FF;color:#4F46E5;font-size:13px;font-weight:700;padding:6px 14px;border-radius:20px;">
        {{ number_format($logs->total()) }} {{ Str::plural('entry', $logs->total()) }}
    </span>
</div>

{{-- Filters --}}
<form method="GET" style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;padding:16px 20px;margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div style="flex:1;min-width:160px;">
        <label style="font-size:11px;font-weight:600;color:#6B7280;display:block;margin-bottom:4px;">Action Type</label>
        <select name="action" style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;background:#fff;outline:none;">
            <option value="">All actions</option>
            <optgroup label="Account">
                @foreach(['user.created','user.updated','user.deleted','user.deactivated','user.reactivated','user.role_changed','user.password_changed','user.archived','user.restored','user.held','user.released'] as $a)
                <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $actionMeta[$a][3] ?? ucwords(str_replace(['.','_'],['  ',' '],$a)) }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Tasks">
                @foreach(['task.approved','task.rejected','task.reassigned','task.deleted','task.force_deleted','task.reopened','task.archived','task.delivered','tasks.bulk_transferred'] as $a)
                <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $actionMeta[$a][3] ?? ucwords(str_replace(['.','_'],['  ',' '],$a)) }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Projects">
                @foreach(['project.created','project.updated','project.deleted','project.reopened','project.closed'] as $a)
                <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $actionMeta[$a][3] ?? ucwords(str_replace(['.','_'],['  ',' '],$a)) }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Roles">
                @foreach(['role.created','role.updated','role.deleted'] as $a)
                <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $actionMeta[$a][3] ?? ucwords(str_replace(['.','_'],['  ',' '],$a)) }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Settings / System">
                @foreach(['settings.updated','data.cleared','system.restored'] as $a)
                <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $actionMeta[$a][3] ?? ucwords(str_replace(['.','_'],['  ',' '],$a)) }}</option>
                @endforeach
            </optgroup>
        </select>
    </div>
    <div style="flex:1;min-width:160px;">
        <label style="font-size:11px;font-weight:600;color:#6B7280;display:block;margin-bottom:4px;">Performed By</label>
        <select name="actor_id" style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;background:#fff;outline:none;">
            <option value="">Anyone</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}" {{ request('actor_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="flex:1;min-width:130px;">
        <label style="font-size:11px;font-weight:600;color:#6B7280;display:block;margin-bottom:4px;">From</label>
        <input type="date" name="from" value="{{ request('from') }}"
               style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;outline:none;">
    </div>
    <div style="flex:1;min-width:130px;">
        <label style="font-size:11px;font-weight:600;color:#6B7280;display:block;margin-bottom:4px;">To</label>
        <input type="date" name="to" value="{{ request('to') }}"
               style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;outline:none;">
    </div>
    <div style="display:flex;gap:8px;">
        <button type="submit"
                style="padding:8px 20px;background:#6366F1;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
            <i class="fa fa-filter" style="margin-right:4px;"></i> Filter
        </button>
        @if(request()->hasAny(['action','actor_id','from','to']))
        <a href="{{ route('admin.audit.index') }}"
           style="padding:8px 16px;background:#F3F4F6;color:#6B7280;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;">
            Clear
        </a>
        @endif
    </div>
</form>

{{-- Log entries --}}
<div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow-y:auto;max-height:68vh;">

    @forelse($logs as $log)
    @php
        [$icon, $fg, $bg, $label] = $actionMeta[$log->action] ?? ['fa-circle-dot', '#6366F1', '#EEF2FF', ucwords(str_replace(['.','_'],['  ',' '],$log->action))];
        $meta = $log->metadata ?? [];
    @endphp
    <div class="audit-row"
         onclick="openAuditModal(this)"
         data-icon="{{ $icon }}"
         data-fg="{{ $fg }}"
         data-bg="{{ $bg }}"
         data-label="{{ $label }}"
         data-action="{{ $log->action }}"
         data-actor="{{ $log->actor->name ?? 'System' }}"
         data-description="{{ $log->description }}"
         data-date="{{ $log->created_at->format('M d, Y') }}"
         data-time="{{ $log->created_at->format('H:i:s') }}"
         data-relative="{{ $log->created_at->diffForHumans() }}"
         data-ip="{{ $log->ip_address ?? '' }}"
         data-subject-type="{{ $log->subject_type ?? '' }}"
         data-subject-id="{{ $log->subject_id ?? '' }}"
         data-meta="{{ json_encode($meta) }}"
         style="display:flex;gap:14px;padding:14px 20px;border-bottom:1px solid #F9FAFB;cursor:pointer;transition:background .15s;">

        {{-- Icon --}}
        <div style="width:38px;height:38px;border-radius:10px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa {{ $icon }}" style="color:{{ $fg }};font-size:14px;"></i>
        </div>

        {{-- Content --}}
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
                        <span style="font-size:12px;font-weight:700;padding:2px 9px;border-radius:10px;background:{{ $bg }};color:{{ $fg }};">{{ $label }}</span>
                        @if($log->actor)
                        <span style="font-size:12px;font-weight:600;color:#111827;">{{ $log->actor->name }}</span>
                        @else
                        <span style="font-size:12px;color:#9CA3AF;font-style:italic;">System</span>
                        @endif
                    </div>
                    <p style="font-size:13px;color:#374151;margin:0 0 4px;line-height:1.5;">{{ $log->description }}</p>

                    {{-- Metadata tags preview --}}
                    @if(!empty($meta))
                    <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach($meta as $key => $value)
                        @if(!in_array($key, ['task_ids','changes']) && !is_null($value) && !is_array($value))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">
                            {{ str_replace('_', ' ', $key) }}: <strong>{{ $value }}</strong>
                        </span>
                        @endif
                        @endforeach
                        @if(isset($meta['changes']) && is_array($meta['changes']))
                        @foreach($meta['changes'] as $field => $change)
                        @if(isset($change['from'], $change['to']) && $change['from'] !== $change['to'])
                        <span style="font-size:11px;background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:6px;">
                            {{ $field }}: <span style="text-decoration:line-through;opacity:.7;">{{ $change['from'] ?? '—' }}</span> → <strong>{{ $change['to'] }}</strong>
                        </span>
                        @endif
                        @endforeach
                        @endif
                        @if(isset($meta['task_ids']) && is_array($meta['task_ids']))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">
                            task IDs: {{ implode(', ', array_slice($meta['task_ids'], 0, 5)) }}{{ count($meta['task_ids']) > 5 ? ' +'.( count($meta['task_ids'])-5).' more' : '' }}
                        </span>
                        @endif
                    </div>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
                    <div style="text-align:right;">
                        <p style="font-size:12px;color:#374151;font-weight:500;margin:0;">{{ $log->created_at->format('M d, Y') }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $log->created_at->format('H:i:s') }}</p>
                        @if($log->ip_address)
                        <p style="font-size:10px;color:#D1D5DB;margin:2px 0 0;font-family:monospace;">{{ $log->ip_address }}</p>
                        @endif
                    </div>
                    <i class="fa fa-chevron-right" style="color:#D1D5DB;font-size:11px;"></i>
                </div>
            </div>
        </div>

    </div>
    @empty
    <div style="padding:60px;text-align:center;">
        <div style="width:56px;height:56px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fa fa-shield-halved" style="color:#D1D5DB;font-size:24px;"></i>
        </div>
        <p style="font-size:15px;font-weight:600;color:#374151;margin:0 0 6px;">No audit records found</p>
        <p style="font-size:13px;color:#9CA3AF;margin:0;">
            @if(request()->hasAny(['action','actor_id','from','to']))
            Try adjusting your filters.
            @else
            Audit records will appear here as admins take actions.
            @endif
        </p>
    </div>
    @endforelse

</div>

@if($logs->hasPages())
<div style="margin-top:16px;">{{ $logs->links() }}</div>
@endif

{{-- ── Audit Detail Modal ──────────────────────────────────────────────── --}}
<div id="auditModal" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;">
    {{-- Backdrop --}}
    <div onclick="closeAuditModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);"></div>

    {{-- Panel --}}
    <div style="position:relative;width:100%;max-width:560px;margin:16px;background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(0,0,0,.18);overflow:hidden;max-height:92vh;display:flex;flex-direction:column;">

        {{-- Header --}}
        <div id="modal-header" style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:14px;">
            <div id="modal-icon-wrap" style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i id="modal-icon" class="fa" style="font-size:18px;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span id="modal-label" style="font-size:12px;font-weight:700;padding:3px 10px;border-radius:10px;"></span>
                    <span id="modal-action" style="font-size:11px;color:#9CA3AF;font-family:monospace;"></span>
                </div>
                <p id="modal-description" style="font-size:14px;font-weight:600;color:#111827;margin:4px 0 0;line-height:1.4;"></p>
            </div>
            <button onclick="closeAuditModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#9CA3AF;flex-shrink:0;">
                <i class="fa fa-xmark" style="font-size:18px;"></i>
            </button>
        </div>

        {{-- Body --}}
        <div style="padding:20px 24px;overflow-y:auto;flex:1;">

            {{-- Who / When / Where --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
                <div style="background:#F9FAFB;border-radius:10px;padding:12px 14px;">
                    <p style="font-size:10px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Performed By</p>
                    <p id="modal-actor" style="font-size:13px;font-weight:600;color:#111827;margin:0;"></p>
                </div>
                <div style="background:#F9FAFB;border-radius:10px;padding:12px 14px;">
                    <p style="font-size:10px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Timestamp</p>
                    <p id="modal-date" style="font-size:13px;font-weight:600;color:#111827;margin:0;"></p>
                    <p id="modal-relative" style="font-size:11px;color:#9CA3AF;margin:2px 0 0;"></p>
                </div>
                <div id="modal-ip-wrap" style="background:#F9FAFB;border-radius:10px;padding:12px 14px;">
                    <p style="font-size:10px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">IP Address</p>
                    <p id="modal-ip" style="font-size:13px;font-weight:600;color:#111827;margin:0;font-family:monospace;"></p>
                </div>
                <div id="modal-subject-wrap" style="background:#F9FAFB;border-radius:10px;padding:12px 14px;">
                    <p style="font-size:10px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Subject</p>
                    <p id="modal-subject" style="font-size:13px;font-weight:600;color:#111827;margin:0;"></p>
                </div>
            </div>

            {{-- Metadata --}}
            <div id="modal-meta-section" style="display:none;">
                <p style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;">Details</p>
                <div id="modal-meta-body" style="border:1px solid #F3F4F6;border-radius:10px;overflow:hidden;"></div>
            </div>

        </div>
    </div>
</div>

<style>
.audit-row:hover { background: #F9FAFB; }
</style>

<script>
function openAuditModal(el) {
    const d = el.dataset;
    const meta = JSON.parse(d.meta || '{}');

    // Header
    document.getElementById('modal-icon-wrap').style.background = d.bg;
    const iconEl = document.getElementById('modal-icon');
    iconEl.className = 'fa ' + d.icon;
    iconEl.style.color = d.fg;

    const labelEl = document.getElementById('modal-label');
    labelEl.textContent = d.label;
    labelEl.style.background = d.bg;
    labelEl.style.color = d.fg;

    document.getElementById('modal-action').textContent = d.action;
    document.getElementById('modal-description').textContent = d.description;

    // Info grid
    document.getElementById('modal-actor').textContent = d.actor;
    document.getElementById('modal-date').textContent  = d.date + ' · ' + d.time;
    document.getElementById('modal-relative').textContent = d.relative;

    const ipWrap = document.getElementById('modal-ip-wrap');
    if (d.ip) {
        document.getElementById('modal-ip').textContent = d.ip;
        ipWrap.style.display = '';
    } else {
        ipWrap.style.display = 'none';
    }

    const subjWrap = document.getElementById('modal-subject-wrap');
    if (d.subjectType) {
        document.getElementById('modal-subject').textContent = d.subjectType + (d.subjectId ? ' #' + d.subjectId : '');
        subjWrap.style.display = '';
    } else {
        subjWrap.style.display = 'none';
    }

    // Metadata rows
    const metaSection = document.getElementById('modal-meta-section');
    const metaBody    = document.getElementById('modal-meta-body');
    const rows = buildMetaRows(meta);

    if (rows.length) {
        metaBody.innerHTML = rows.map((r, i) =>
            `<div style="display:flex;padding:9px 14px;${i < rows.length-1 ? 'border-bottom:1px solid #F3F4F6;' : ''}background:${i%2===0?'#fff':'#FAFAFA'};">
                <span style="font-size:12px;color:#6B7280;width:140px;flex-shrink:0;font-weight:500;">${r.key}</span>
                <span style="font-size:12px;color:#111827;flex:1;word-break:break-word;">${r.val}</span>
            </div>`
        ).join('');
        metaSection.style.display = '';
    } else {
        metaSection.style.display = 'none';
    }

    const modal = document.getElementById('auditModal');
    modal.style.display = 'flex';
    requestAnimationFrame(() => modal.style.opacity = '1');
}

function buildMetaRows(meta) {
    const rows = [];
    for (const [key, value] of Object.entries(meta)) {
        const label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        if (key === 'changes' && typeof value === 'object' && value !== null) {
            for (const [field, change] of Object.entries(value)) {
                if (change && change.from !== undefined && change.to !== undefined) {
                    const fieldLabel = field.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                    const from = change.from ?? '—';
                    const to   = change.to   ?? '—';
                    rows.push({
                        key: fieldLabel,
                        val: `<span style="text-decoration:line-through;opacity:.6;">${esc(from)}</span> → <strong>${esc(to)}</strong>`
                    });
                }
            }
        } else if (key === 'task_ids' && Array.isArray(value)) {
            rows.push({ key: 'Task IDs', val: esc(value.join(', ')) });
        } else if (Array.isArray(value)) {
            rows.push({ key: label, val: esc(value.join(', ')) });
        } else if (typeof value === 'object' && value !== null) {
            // skip nested objects that were already handled
        } else if (value !== null && value !== undefined && value !== '') {
            rows.push({ key: label, val: esc(String(value)) });
        }
    }
    return rows;
}

function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function closeAuditModal() {
    document.getElementById('auditModal').style.display = 'none';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAuditModal(); });
</script>

@endsection

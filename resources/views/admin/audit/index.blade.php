@extends('layouts.app')
@section('title', 'Audit Log')

@section('content')
@php
    $actionMeta = [
        'user.created'          => ['fa-user-plus',      '#059669', '#D1FAE5', 'Account Created'],
        'user.updated'          => ['fa-user-pen',        '#6366F1', '#EEF2FF', 'Profile Updated'],
        'user.deleted'          => ['fa-user-slash',      '#DC2626', '#FEE2E2', 'Account Deleted'],
        'user.deactivated'      => ['fa-user-lock',       '#D97706', '#FEF3C7', 'Account Deactivated'],
        'user.reactivated'      => ['fa-user-check',      '#059669', '#D1FAE5', 'Account Reactivated'],
        'user.role_changed'     => ['fa-user-shield',     '#7C3AED', '#EDE9FE', 'Role Changed'],
        'user.password_changed' => ['fa-key',             '#6B7280', '#F3F4F6', 'Password Changed'],
        'tasks.bulk_transferred'=> ['fa-right-left',      '#D97706', '#FEF3C7', 'Tasks Transferred'],
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
                @foreach(['user.created','user.updated','user.deleted','user.deactivated','user.reactivated','user.role_changed','user.password_changed'] as $a)
                <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucwords(str_replace(['.','_'],['  ',' '],$a)) }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Tasks">
                <option value="tasks.bulk_transferred" {{ request('action') === 'tasks.bulk_transferred' ? 'selected' : '' }}>Tasks Transferred</option>
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
<div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden;">

    @forelse($logs as $log)
    @php
        [$icon, $fg, $bg, $label] = $actionMeta[$log->action] ?? ['fa-circle-dot', '#6366F1', '#EEF2FF', ucwords(str_replace(['.','_'],['  ',' '],$log->action))];
        $meta = $log->metadata ?? [];
    @endphp
    <div style="display:flex;gap:14px;padding:14px 20px;border-bottom:1px solid #F9FAFB;">

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

                    {{-- Metadata details --}}
                    @if(!empty($meta))
                    <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach($meta as $key => $value)
                        @if(!in_array($key, ['task_ids']) && !is_null($value) && !is_array($value))
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
                            task IDs: {{ implode(', ', array_slice($meta['task_ids'], 0, 8)) }}{{ count($meta['task_ids']) > 8 ? ' +'.( count($meta['task_ids'])-8).' more' : '' }}
                        </span>
                        @endif
                    </div>
                    @endif
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <p style="font-size:12px;color:#374151;font-weight:500;margin:0;">{{ $log->created_at->format('M d, Y') }}</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $log->created_at->format('H:i:s') }}</p>
                    @if($log->ip_address)
                    <p style="font-size:10px;color:#D1D5DB;margin:2px 0 0;font-family:monospace;">{{ $log->ip_address }}</p>
                    @endif
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

@endsection

@extends('layouts.app')
@section('title', 'Recycle Bin — Tasks')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.tasks.index') }}"
           style="width:36px;height:36px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;flex-shrink:0;">
            <i class="fa fa-arrow-left" style="font-size:13px;"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fa fa-trash-can text-red-400"></i> Recycle Bin
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $tasks->total() }} deleted {{ Str::plural('task', $tasks->total()) }} — restore or permanently delete</p>
        </div>
    </div>
</div>

@if(session('success'))
<div style="background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border:1px solid #A7F3D0;border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#065F46;font-size:14px;display:flex;gap:10px;align-items:center;">
    <i class="fa fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#991B1B;font-size:14px;display:flex;gap:10px;align-items:center;">
    <i class="fa fa-circle-exclamation"></i> {{ session('error') }}
</div>
@endif

{{-- Search --}}
<form method="GET" action="{{ route('admin.tasks.trash') }}"
      class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 mb-5 flex items-center gap-3">
    <div class="relative flex-1 min-w-48">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search deleted tasks…"
               class="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-red-400 focus:ring-2 focus:ring-red-100 bg-gray-50">
    </div>
    <button type="submit" class="px-4 py-2 bg-red-500 text-white text-sm font-semibold rounded-lg hover:bg-red-600 transition">Search</button>
    @if(request('search'))
    <a href="{{ route('admin.tasks.trash') }}" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">Clear</a>
    @endif
</form>

@if($tasks->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm py-24 text-center">
    <div style="width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,#FEE2E2,#FECACA);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <i class="fa fa-trash-can" style="font-size:28px;color:#F87171;"></i>
    </div>
    <p class="text-gray-500 font-semibold text-lg">Recycle Bin is empty</p>
    <p class="text-gray-400 text-sm mt-1">Deleted tasks will appear here</p>
    <a href="{{ route('admin.tasks.index') }}" class="mt-4 inline-block text-sm text-indigo-500 hover:underline">← Back to Tasks</a>
</div>
@else

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
<table style="width:100%;border-collapse:collapse;">
    <thead>
        <tr style="background:#FEF2F2;border-bottom:1px solid #FEE2E2;">
            <th style="padding:12px 20px;text-align:left;font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;">Task</th>
            <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;">Project</th>
            <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;">Assignee</th>
            <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;">Status at Delete</th>
            <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;">Deleted</th>
            <th style="padding:12px 16px;text-align:right;font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @php
        $statusMeta = [
            'draft'              => ['label'=>'Draft',       'bg'=>'#F3F4F6','color'=>'#6B7280'],
            'assigned'           => ['label'=>'Assigned',    'bg'=>'#EEF2FF','color'=>'#4F46E5'],
            'viewed'             => ['label'=>'Viewed',      'bg'=>'#F0F9FF','color'=>'#0369A1'],
            'in_progress'        => ['label'=>'In Progress', 'bg'=>'#FFFBEB','color'=>'#D97706'],
            'submitted'          => ['label'=>'In Review',   'bg'=>'#F5F3FF','color'=>'#7C3AED'],
            'revision_requested' => ['label'=>'Revision',    'bg'=>'#FFF7ED','color'=>'#C2410C'],
            'approved'           => ['label'=>'Approved',    'bg'=>'#F0FDF4','color'=>'#15803D'],
            'delivered'          => ['label'=>'Delivered',   'bg'=>'#F0FDF4','color'=>'#166534'],
            'archived'           => ['label'=>'Archived',    'bg'=>'#F3F4F6','color'=>'#6B7280'],
        ];
        @endphp
        @foreach($tasks as $task)
        @php $sm = $statusMeta[$task->status] ?? ['label'=>ucfirst($task->status),'bg'=>'#F3F4F6','color'=>'#6B7280']; @endphp
        <tr style="border-bottom:1px solid #FEF2F2;transition:background .12s;" onmouseover="this.style.background='#FFF5F5'" onmouseout="this.style.background=''">

            {{-- Task title --}}
            <td style="padding:14px 20px;">
                <p style="font-size:13px;font-weight:600;color:#374151;margin:0;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $task->title }}">
                    {{ $task->title }}
                </p>
                @if($task->deadline)
                <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">
                    <i class="fa fa-calendar" style="font-size:9px;margin-right:3px;"></i>Due {{ $task->deadline->format('M d, Y') }}
                </p>
                @endif
            </td>

            {{-- Project --}}
            <td style="padding:14px 16px;">
                <span style="font-size:12px;color:#6B7280;display:flex;align-items:center;gap:4px;">
                    <i class="fas fa-folder" style="font-size:10px;color:#C4B5FD;"></i>
                    {{ $task->project?->name ?? '—' }}
                </span>
            </td>

            {{-- Assignee --}}
            <td style="padding:14px 16px;">
                @if($task->assignee)
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                        {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                    </div>
                    <span style="font-size:12px;font-weight:500;color:#374151;">{{ $task->assignee->name }}</span>
                </div>
                @else
                <span style="font-size:12px;color:#D1D5DB;">Unassigned</span>
                @endif
            </td>

            {{-- Status --}}
            <td style="padding:14px 16px;">
                <span style="display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:{{ $sm['bg'] }};color:{{ $sm['color'] }};">
                    {{ $sm['label'] }}
                </span>
            </td>

            {{-- Deleted at --}}
            <td style="padding:14px 16px;">
                <span style="font-size:12px;color:#6B7280;white-space:nowrap;">{{ $task->deleted_at->format('M d, Y') }}</span>
                <p style="font-size:10px;color:#D1D5DB;margin:2px 0 0;">{{ $task->deleted_at->diffForHumans() }}</p>
            </td>

            {{-- Actions --}}
            <td style="padding:14px 16px;text-align:right;">
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">

                    {{-- Restore --}}
                    <form method="POST" action="{{ route('admin.tasks.restore', $task->id) }}">
                        @csrf
                        <button type="submit"
                                style="display:flex;align-items:center;gap:5px;padding:6px 14px;background:#ECFDF5;color:#065F46;border:1.5px solid #A7F3D0;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .15s;"
                                onmouseover="this.style.background='#D1FAE5';this.style.borderColor='#6EE7B7'" onmouseout="this.style.background='#ECFDF5';this.style.borderColor='#A7F3D0'">
                            <i class="fa fa-rotate-left" style="font-size:10px;"></i> Restore
                        </button>
                    </form>

                    {{-- Permanently delete --}}
                    <form method="POST" action="{{ route('admin.tasks.force-delete', $task->id) }}"
                          onsubmit="return confirm('Permanently delete &quot;{{ addslashes($task->title) }}&quot;? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                style="display:flex;align-items:center;gap:5px;padding:6px 14px;background:#FEF2F2;color:#DC2626;border:1.5px solid #FECACA;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .15s;"
                                onmouseover="this.style.background='#FEE2E2';this.style.borderColor='#FCA5A5'" onmouseout="this.style.background='#FEF2F2';this.style.borderColor='#FECACA'">
                            <i class="fa fa-trash" style="font-size:10px;"></i> Delete Forever
                        </button>
                    </form>

                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($tasks->hasPages())
<div style="padding:14px 20px;border-top:1px solid #FEF2F2;background:#FAFAFA;">
    {{ $tasks->links() }}
</div>
@endif

</div>{{-- /table card --}}
@endif

@endsection

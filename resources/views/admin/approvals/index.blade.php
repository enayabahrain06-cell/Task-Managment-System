@extends('layouts.app')
@section('title', 'Task Approvals')

@section('content')

{{-- ═══ VERSION DETAIL MODAL ═══ --}}
<div x-data="approvalPage()" @keydown.escape.window="closeModal()">

    {{-- Teleport modal to <body> so it escapes all stacking contexts --}}
    <template x-teleport="body">
        <div x-show="modal" x-cloak
             @click.self="closeModal()"
             style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;">

            <div style="background:#fff;border-radius:18px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;">

                {{-- Modal header --}}
                <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-file-lines" style="color:#6366F1;font-size:14px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0;" x-text="'Version ' + (sub ? sub.version : '')"></h3>
                            <p style="font-size:11px;color:#9CA3AF;margin:0;" x-text="sub ? sub.task : ''"></p>
                        </div>
                    </div>
                    <button @click="closeModal()"
                            style="width:30px;height:30px;border-radius:8px;background:#F3F4F6;border:none;cursor:pointer;color:#6B7280;font-size:13px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Modal body --}}
                <div style="padding:20px 24px;">

                    {{-- Status + date --}}
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
                        <span x-html="sub ? statusBadge(sub.status) : ''"></span>
                        <span style="font-size:12px;color:#9CA3AF;" x-text="sub ? sub.date : ''"></span>
                        <span style="font-size:12px;color:#9CA3AF;" x-text="sub ? '· by ' + sub.user : ''"></span>
                    </div>

                    {{-- Note --}}
                    <template x-if="sub && sub.note">
                        <div style="background:#F8FAFC;border-radius:10px;padding:14px 16px;margin-bottom:16px;">
                            <p style="font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.04em;margin:0 0 6px;">Submission Note</p>
                            <p style="font-size:13px;color:#374151;margin:0;line-height:1.6;" x-text="sub.note"></p>
                        </div>
                    </template>
                    <template x-if="sub && !sub.note">
                        <div style="background:#F9FAFB;border-radius:10px;padding:12px 16px;margin-bottom:16px;text-align:center;">
                            <p style="font-size:12px;color:#D1D5DB;margin:0;">No note provided</p>
                        </div>
                    </template>

                    {{-- File --}}
                    <template x-if="sub && sub.file">
                        <div>
                            <p style="font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.04em;margin:0 0 8px;">Attached File</p>
                            <a :href="sub.file" target="_blank"
                               style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1.5px solid #E0E7FF;border-radius:10px;text-decoration:none;background:#FAFBFF;"
                               onmouseover="this.style.background='#EEF2FF'" onmouseout="this.style.background='#FAFBFF'">
                                <div style="width:36px;height:36px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-paperclip" style="color:#6366F1;font-size:14px;"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <p style="font-size:13px;font-weight:600;color:#4F46E5;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="sub.filename"></p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Click to open / download</p>
                                </div>
                                <i class="fas fa-arrow-up-right-from-square" style="color:#9CA3AF;font-size:11px;flex-shrink:0;"></i>
                            </a>
                        </div>
                    </template>
                    <template x-if="sub && !sub.file">
                        <div style="background:#F9FAFB;border-radius:10px;padding:12px 16px;text-align:center;">
                            <p style="font-size:12px;color:#D1D5DB;margin:0;">No file attached</p>
                        </div>
                    </template>

                    {{-- Admin feedback --}}
                    <template x-if="sub && sub.adminNote">
                        <div style="margin-top:14px;background:#FEF2F2;border-radius:10px;padding:14px 16px;">
                            <p style="font-size:11px;font-weight:600;color:#DC2626;text-transform:uppercase;letter-spacing:.04em;margin:0 0 6px;">Admin Feedback</p>
                            <p style="font-size:13px;color:#7F1D1D;margin:0;line-height:1.6;" x-text="sub.adminNote"></p>
                        </div>
                    </template>

                </div>

                {{-- Modal footer --}}
                <div style="padding:14px 24px;border-top:1px solid #F3F4F6;display:flex;justify-content:flex-end;">
                    <button @click="closeModal()"
                            style="padding:9px 22px;background:#F3F4F6;color:#374151;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ═══ PAGE CONTENT ═══ --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">Task Approvals</h1>
            <p style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">Review and approve submitted work</p>
        </div>
        @if($tasks->total() > 0)
        <span style="background:#EDE9FE;color:#7C3AED;font-size:13px;font-weight:700;padding:6px 14px;border-radius:20px;">
            {{ $tasks->total() }} pending {{ Str::plural('review', $tasks->total()) }}
        </span>
        @endif
    </div>

    @if(session('success'))
    <div style="background:#D1FAE5;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#065F46;font-size:14px;display:flex;gap:10px;align-items:center;">
        <i class="fa fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif

    @forelse($tasks as $task)
    @php
        $latestSub = $task->submissions->first();
        $isOverdue  = $task->deadline->isPast();
    @endphp
    <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);margin-bottom:16px;overflow:hidden;">

        {{-- Task header --}}
        <div style="padding:18px 20px 14px;border-bottom:1px solid #F9FAFB;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#fff;flex-shrink:0;">
                {{ strtoupper(substr($task->assignee->name ?? 'U',0,1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $task->title }}</h3>
                <p style="font-size:12px;color:#9CA3AF;margin:0;">
                    <span style="color:#6366F1;font-weight:600;">{{ $task->assignee->name ?? 'Unknown' }}</span>
                    · {{ $task->project->name }}
                    · Deadline: <span style="{{ $isOverdue ? 'color:#DC2626;font-weight:600;' : '' }}">{{ $task->deadline->format('M d, Y') }}</span>
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                @php $pMap = ['high'=>['#FEE2E2','#DC2626'],'medium'=>['#FEF3C7','#D97706'],'low'=>['#D1FAE5','#059669']]; [$pbg,$pco] = $pMap[$task->priority] ?? ['#F3F4F6','#6B7280']; @endphp
                <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:10px;background:{{ $pbg }};color:{{ $pco }};">{{ ucfirst($task->priority) }}</span>
                <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:10px;background:#EDE9FE;color:#7C3AED;">
                    {{ $task->submissions->count() }} {{ Str::plural('version', $task->submissions->count()) }}
                </span>
            </div>
        </div>

        {{-- Latest submission --}}
        @if($latestSub)
        <div style="padding:16px 20px;border-bottom:1px solid #F9FAFB;background:#FAFBFF;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:12px;font-weight:700;color:#4F46E5;background:#EEF2FF;padding:3px 10px;border-radius:8px;">Latest · v{{ $latestSub->version }}</span>
                    <span style="font-size:11px;color:#9CA3AF;">{{ $latestSub->created_at->diffForHumans() }}</span>
                </div>
                <button @click="openModal({
                            version: {{ $latestSub->version }},
                            task: @js($task->title),
                            status: @js($latestSub->status),
                            date: @js($latestSub->created_at->format('M d, Y H:i')),
                            user: @js($task->assignee->name ?? 'Unknown'),
                            note: @js($latestSub->note),
                            file: @js($latestSub->file_path ? $latestSub->fileUrl() : null),
                            filename: @js($latestSub->original_filename),
                            adminNote: @js($latestSub->admin_note)
                        })"
                        style="display:flex;align-items:center;gap:5px;padding:5px 12px;background:#EEF2FF;color:#4F46E5;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-eye" style="font-size:10px;"></i> View Details
                </button>
            </div>
            @if($latestSub->note)
            <p style="font-size:13px;color:#374151;margin:0 0 8px;line-height:1.6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $latestSub->note }}</p>
            @endif
            @if($latestSub->file_path)
            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#6366F1;background:#EEF2FF;padding:4px 10px;border-radius:6px;">
                <i class="fa fa-paperclip" style="font-size:10px;"></i> {{ $latestSub->original_filename ?? 'Attachment' }}
            </span>
            @endif
        </div>
        @endif

        {{-- Approve / Reject forms --}}
        <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">

            {{-- Approve --}}
            <form method="POST" action="{{ route('admin.tasks.approve', $task) }}">
                @csrf
                <div style="margin-bottom:8px;">
                    <input type="text" name="note" placeholder="Optional approval note..."
                           style="width:100%;padding:8px 12px;border:1.5px solid #A7F3D0;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#F0FDF4;">
                </div>
                <button type="submit"
                        style="width:100%;background:#10B981;color:#fff;border:none;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;"
                        onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10B981'">
                    <i class="fa fa-circle-check"></i> Approve
                </button>
            </form>

            {{-- Reject --}}
            <form method="POST" action="{{ route('admin.tasks.reject', $task) }}">
                @csrf
                <div style="margin-bottom:8px;">
                    <input type="text" name="note" required placeholder="Reason for rejection (required)..."
                           style="width:100%;padding:8px 12px;border:1.5px solid #FECACA;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#FEF2F2;">
                </div>
                <button type="submit"
                        style="width:100%;background:#EF4444;color:#fff;border:none;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;"
                        onmouseover="this.style.background='#DC2626'" onmouseout="this.style.background='#EF4444'">
                    <i class="fa fa-rotate-left"></i> Request Revision
                </button>
            </form>

        </div>

        {{-- All versions toggle --}}
        @if($task->submissions->count() > 1)
        <div x-data="{ open: false }" style="border-top:1px solid #F9FAFB;">
            <button @click="open = !open"
                    style="width:100%;padding:10px 20px;background:none;border:none;cursor:pointer;font-size:12px;color:#9CA3AF;display:flex;align-items:center;gap:6px;justify-content:center;">
                <i class="fa fa-clock-rotate-left"></i>
                @php $prevCount = $task->submissions->count() - 1; $prevLabel = $prevCount === 1 ? 'version' : 'versions'; @endphp
                <span x-text="open ? 'Hide previous versions' : 'Show {{ $prevCount }} previous {{ $prevLabel }}'"></span>
                <i class="fa fa-chevron-down" :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform .2s;"></i>
            </button>
            <div x-show="open" x-cloak style="padding:0 20px 14px;display:flex;flex-direction:column;gap:8px;">
                @foreach($task->submissions->skip(1) as $sub)
                @php $sColors = ['submitted'=>['#EEF2FF','#4F46E5'],'approved'=>['#D1FAE5','#059669'],'rejected'=>['#FEE2E2','#DC2626']]; [$scbg,$scco] = $sColors[$sub->status] ?? ['#F3F4F6','#6B7280']; @endphp
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-radius:10px;background:#F9FAFB;gap:10px;">
                    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                        <span style="font-size:11px;font-weight:700;color:{{ $scco }};background:{{ $scbg }};padding:2px 9px;border-radius:8px;flex-shrink:0;">v{{ $sub->version }}</span>
                        <span style="font-size:11px;font-weight:600;color:#374151;flex-shrink:0;">{{ ucfirst($sub->status) }}</span>
                        <span style="font-size:11px;color:#9CA3AF;flex-shrink:0;">{{ $sub->created_at->format('M d, H:i') }}</span>
                        @if($sub->note)
                        <span style="font-size:11px;color:#6B7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">· {{ $sub->note }}</span>
                        @endif
                    </div>
                    <button @click="openModal({
                                version: {{ $sub->version }},
                                task: @js($task->title),
                                status: @js($sub->status),
                                date: @js($sub->created_at->format('M d, Y H:i')),
                                user: @js($task->assignee->name ?? 'Unknown'),
                                note: @js($sub->note),
                                file: @js($sub->file_path ? $sub->fileUrl() : null),
                                filename: @js($sub->original_filename),
                                adminNote: @js($sub->admin_note)
                            })"
                            style="display:flex;align-items:center;gap:4px;padding:4px 10px;background:#F3F4F6;color:#6B7280;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;flex-shrink:0;"
                            onmouseover="this.style.background='#EEF2FF';this.style.color='#4F46E5'" onmouseout="this.style.background='#F3F4F6';this.style.color='#6B7280'">
                        <i class="fas fa-eye" style="font-size:10px;"></i> View
                    </button>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
    @empty
    <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;padding:60px;text-align:center;">
        <div style="width:60px;height:60px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fa fa-circle-check" style="color:#D1D5DB;font-size:28px;"></i>
        </div>
        <p style="font-size:15px;font-weight:600;color:#374151;margin:0 0 6px;">All caught up!</p>
        <p style="font-size:13px;color:#9CA3AF;margin:0;">No tasks are pending review right now.</p>
    </div>
    @endforelse

    @if($tasks->hasPages())
    <div style="margin-top:16px;">{{ $tasks->links() }}</div>
    @endif

</div>

<script>
function approvalPage() {
    return {
        modal: false,
        sub: null,
        openModal(data) { this.sub = data; this.modal = true; },
        closeModal() { this.modal = false; this.sub = null; },
        statusBadge(status) {
            const map = {
                submitted: 'background:#EEF2FF;color:#4F46E5',
                approved:  'background:#D1FAE5;color:#059669',
                rejected:  'background:#FEE2E2;color:#DC2626',
            };
            const s = map[status] || 'background:#F3F4F6;color:#6B7280';
            const label = status ? status.charAt(0).toUpperCase() + status.slice(1) : '';
            return `<span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;${s}">${label}</span>`;
        }
    }
}
</script>

@endsection

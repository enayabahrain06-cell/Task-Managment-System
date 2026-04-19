@extends('layouts.app')
@section('title', $task->title)

@section('content')
@php
    $doneStatuses = ['approved','delivered','archived'];
    $isOverdue  = $task->deadline->isPast() && !in_array($task->status, $doneStatuses);
    $statusMap  = [
        'draft'              => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Draft'],
        'assigned'           => ['bg'=>'#E0F2FE','color'=>'#0284C7','label'=>'Assigned'],
        'viewed'             => ['bg'=>'#EEF2FF','color'=>'#4F46E5','label'=>'Viewed'],
        'in_progress'        => ['bg'=>'#FEF3C7','color'=>'#D97706','label'=>'In Progress'],
        'submitted'          => ['bg'=>'#EDE9FE','color'=>'#7C3AED','label'=>'Submitted for Review'],
        'revision_requested' => ['bg'=>'#FEE2E2','color'=>'#DC2626','label'=>'Revision Requested'],
        'approved'           => ['bg'=>'#D1FAE5','color'=>'#059669','label'=>'Approved'],
        'delivered'          => ['bg'=>'#ECFDF5','color'=>'#047857','label'=>'Delivered'],
        'archived'           => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Archived'],
        // legacy fallbacks
        'pending'            => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Pending'],
        'pending_approval'   => ['bg'=>'#EDE9FE','color'=>'#7C3AED','label'=>'In Review'],
        'completed'          => ['bg'=>'#D1FAE5','color'=>'#059669','label'=>'Completed'],
    ];
    $priorityMap = ['low'=>['bg'=>'#D1FAE5','color'=>'#059669'],'medium'=>['bg'=>'#FEF3C7','color'=>'#D97706'],'high'=>['bg'=>'#FEE2E2','color'=>'#DC2626']];
    $s = $statusMap[$task->status]    ?? $statusMap['pending'];
    $p = $priorityMap[$task->priority] ?? $priorityMap['medium'];
    $latestSub = $task->submissions->first();
    $tagColors = ['#EEF2FF:#4F46E5','#FEF3C7:#D97706','#FEE2E2:#DC2626','#D1FAE5:#059669','#FCE7F3:#BE185D','#E0E7FF:#3730A3'];
@endphp

{{-- Header --}}
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
    <a href="{{ url()->previous() }}"
       style="width:36px;height:36px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;flex-shrink:0;">
        <i class="fa fa-arrow-left" style="font-size:13px;"></i>
    </a>
    <div style="flex:1;min-width:0;">
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $task->title }}</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:2px 0 0;">
            <i class="fa fa-folder-open" style="margin-right:4px;"></i>{{ $task->project->name }}
            &nbsp;·&nbsp;<i class="fa fa-user" style="margin-right:4px;"></i>{{ $task->assignee->name ?? '—' }}
        </p>
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        @if($task->task_type)
        <span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#F3F4F6;color:#374151;"><i class="fa fa-tag" style="margin-right:4px;color:#9CA3AF;"></i>{{ $task->task_type }}</span>
        @endif
        <span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $p['bg'] }};color:{{ $p['color'] }};">{{ ucfirst($task->priority) }} Priority</span>
        <span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $s['bg'] }};color:{{ $s['color'] }};">{{ $s['label'] }}</span>
        @if($isOverdue)<span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#FEE2E2;color:#DC2626;"><i class="fa fa-clock" style="margin-right:3px;"></i>Overdue</span>@endif
        @if($task->tags)
        @foreach($task->tags as $idx => $tag)
        @php [$tbg,$tco] = explode(':', $tagColors[$idx % count($tagColors)]); @endphp
        <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $tbg }};color:{{ $tco }};">#{{ $tag }}</span>
        @endforeach
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

    {{-- Left column --}}
    <div style="display:flex;flex-direction:column;gap:20px;">

        {{-- Task Details --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-circle-info" style="color:#6366F1;"></i> Task Details
            </h2>
            @if($task->description)
            <p style="font-size:14px;color:#6B7280;line-height:1.7;margin:0 0 16px;padding-bottom:16px;border-bottom:1px solid #F3F4F6;">{{ $task->description }}</p>
            @endif
            {{-- Assignees with roles --}}
            @if($task->assignees->count())
            <div style="margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #F3F4F6;">
                <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 10px;">Assignees</p>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($task->assignees as $a)
                    <div style="display:flex;align-items:center;gap:10px;background:#FAFAFA;border-radius:10px;padding:10px 12px;">
                        <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr($a->name, 0, 1)) }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:13px;font-weight:600;color:#111827;margin:0;">{{ $a->name }}</p>
                            @if($a->pivot->role_in_task)
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $a->pivot->role_in_task }}</p>
                            @endif
                        </div>
                        <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:8px;background:#EEF2FF;color:#4F46E5;">{{ ucfirst($a->role) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                @if($task->assignees->isEmpty())
                <div style="background:#FAFAFA;border-radius:10px;padding:12px;">
                    <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 4px;">Assignee</p>
                    <p style="font-size:14px;font-weight:600;color:#111827;margin:0;">{{ $task->assignee->name ?? '—' }}</p>
                </div>
                @endif
                <div style="background:#FAFAFA;border-radius:10px;padding:12px;">
                    <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 4px;">Deadline</p>
                    <p style="font-size:14px;font-weight:600;color:{{ $isOverdue ? '#DC2626' : '#111827' }};margin:0;">
                        {{ $task->deadline->format('M d, Y') }}
                    </p>
                </div>
                @if($task->reviewer)
                <div style="background:#FAFAFA;border-radius:10px;padding:12px;">
                    <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 4px;">Reviewer</p>
                    <p style="font-size:14px;font-weight:600;color:#111827;margin:0;">{{ $task->reviewer->name }}</p>
                </div>
                @endif
                <div style="background:#FAFAFA;border-radius:10px;padding:12px;">
                    <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 4px;">First Viewed</p>
                    <p style="font-size:13px;font-weight:500;color:#111827;margin:0;">
                        {{ $task->first_viewed_at ? $task->first_viewed_at->diffForHumans() : 'Not yet opened' }}
                    </p>
                </div>
                <div style="background:#FAFAFA;border-radius:10px;padding:12px;">
                    <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 4px;">Versions</p>
                    <p style="font-size:14px;font-weight:600;color:#111827;margin:0;">{{ $task->submissions->count() }}</p>
                </div>
                @if($task->creator)
                <div style="background:#FAFAFA;border-radius:10px;padding:12px;">
                    <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 4px;">Created By</p>
                    <p style="font-size:14px;font-weight:600;color:#111827;margin:0;">{{ $task->creator->name }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Admin Actions: Approve/Reject (only when submitted) --}}
        @if(in_array($task->status, ['submitted', 'pending_approval']))
        <div style="background:#fff;border-radius:14px;border:1.5px solid #A78BFA;box-shadow:0 4px 16px rgba(124,58,237,.08);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-gavel" style="color:#7C3AED;"></i> Review Submission
            </h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <form method="POST" action="{{ route('admin.tasks.approve', $task) }}">
                    @csrf
                    <input type="text" name="note" placeholder="Optional approval note..."
                           style="width:100%;padding:8px 12px;border:1.5px solid #A7F3D0;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#F0FDF4;margin-bottom:8px;">
                    <button type="submit"
                            style="width:100%;background:#10B981;color:#fff;border:none;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <i class="fa fa-circle-check"></i> Approve
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.tasks.reject', $task) }}">
                    @csrf
                    <input type="text" name="note" required placeholder="Reason for rejection (required)..."
                           style="width:100%;padding:8px 12px;border:1.5px solid #FECACA;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#FEF2F2;margin-bottom:8px;">
                    <button type="submit"
                            style="width:100%;background:#EF4444;color:#fff;border:none;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <i class="fa fa-rotate-left"></i> Request Revision
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Deliver (only when approved) --}}
        @if($task->status === 'approved')
        <div style="background:#fff;border-radius:14px;border:1.5px solid #A7F3D0;box-shadow:0 4px 16px rgba(5,150,105,.06);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 12px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-truck" style="color:#059669;"></i> Mark as Delivered
            </h2>
            <p style="font-size:13px;color:#6B7280;margin:0 0 14px;">Notify the assignee that the final work has been delivered to the client.</p>
            <form method="POST" action="{{ route('admin.tasks.deliver', $task) }}" style="display:flex;gap:10px;">
                @csrf
                <input type="text" name="note" placeholder="Optional delivery note..."
                       style="flex:1;padding:9px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;outline:none;">
                <button type="submit"
                        style="background:#059669;color:#fff;border:none;padding:9px 20px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:6px;">
                    <i class="fa fa-truck"></i> Deliver
                </button>
            </form>
        </div>
        @endif

        {{-- Comments --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-comments" style="color:#6366F1;"></i> Comments & Updates
                <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9CA3AF;">{{ $task->comments->count() }} {{ Str::plural('comment', $task->comments->count()) }}</span>
            </h2>

            {{-- Post comment form --}}
            <form method="POST" action="{{ route('admin.tasks.comment', $task) }}" style="margin-bottom:20px;">
                @csrf
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <div style="flex:1;">
                        <textarea name="body" rows="2" required placeholder="Add a comment or update for the assignee..."
                                  style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;line-height:1.5;"
                                  onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                        <button type="submit"
                                style="margin-top:8px;background:#6366F1;color:#fff;border:none;padding:8px 18px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;">
                            <i class="fa fa-paper-plane"></i> Post Comment
                        </button>
                    </div>
                </div>
            </form>

            {{-- Comments list --}}
            @forelse($task->comments as $comment)
            @php $isAdmin = in_array($comment->user->role ?? 'user', ['admin','manager']); @endphp
            <div style="display:flex;gap:10px;padding-bottom:16px;margin-bottom:16px;border-bottom:1px solid #F9FAFB;">
                <div style="width:34px;height:34px;border-radius:50%;background:{{ $isAdmin ? 'linear-gradient(135deg,#6366F1,#8B5CF6)' : 'linear-gradient(135deg,#10B981,#059669)' }};display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">
                    {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap;">
                        <span style="font-size:13px;font-weight:600;color:#111827;">{{ $comment->user->name ?? 'Unknown' }}</span>
                        @if($isAdmin)
                        <span style="font-size:10px;font-weight:700;padding:1px 7px;border-radius:10px;background:#EEF2FF;color:#4F46E5;">Admin</span>
                        @endif
                        <span style="font-size:11px;color:#9CA3AF;">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <p style="font-size:13px;color:#374151;margin:0;line-height:1.6;">{{ $comment->body }}</p>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:24px;color:#9CA3AF;">
                <i class="fa fa-comment-slash" style="font-size:24px;margin-bottom:8px;display:block;color:#E5E7EB;"></i>
                <p style="font-size:13px;margin:0;">No comments yet. Be the first to add an update.</p>
            </div>
            @endforelse
        </div>

        {{-- Submission History --}}
        @if($task->submissions->count())
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 20px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-clock-rotate-left" style="color:#6366F1;"></i> Version History
                <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9CA3AF;">{{ $task->submissions->count() }} {{ Str::plural('version', $task->submissions->count()) }}</span>
            </h2>
            @foreach($task->submissions as $sub)
            @php
                $subColors = ['submitted'=>['#EEF2FF','#4F46E5','fa-hourglass-half','In Review'],'approved'=>['#D1FAE5','#059669','fa-circle-check','Approved'],'rejected'=>['#FEE2E2','#DC2626','fa-rotate-left','Rejected']];
                [$sbg2,$sco2,$sico,$slbl2] = $subColors[$sub->status] ?? $subColors['submitted'];
            @endphp
            <div style="display:flex;gap:14px;padding-bottom:18px;margin-bottom:18px;border-bottom:1px solid #F9FAFB;">
                <div style="flex-shrink:0;">
                    <div style="width:36px;height:36px;border-radius:50%;background:{{ $sbg2 }};display:flex;align-items:center;justify-content:center;">
                        <i class="fa {{ $sico }}" style="color:{{ $sco2 }};font-size:14px;"></i>
                    </div>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:6px;">
                        <span style="font-size:13px;font-weight:700;color:#111827;">Version {{ $sub->version }}</span>
                        <span style="font-size:11px;font-weight:600;padding:2px 9px;border-radius:10px;background:{{ $sbg2 }};color:{{ $sco2 }};">{{ $slbl2 }}</span>
                        <span style="font-size:11px;color:#9CA3AF;margin-left:auto;">{{ $sub->created_at->diffForHumans() }}</span>
                    </div>
                    @if($sub->note)
                    <p style="font-size:13px;color:#374151;margin:0 0 6px;line-height:1.5;">{{ $sub->note }}</p>
                    @endif
                    @if($sub->file_path)
                    <a href="{{ $sub->fileUrl() }}" target="_blank"
                       style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#6366F1;text-decoration:none;background:#EEF2FF;padding:5px 10px;border-radius:7px;margin-bottom:6px;">
                        <i class="fa fa-paperclip"></i> {{ $sub->original_filename ?? 'Download file' }}
                    </a>
                    @endif
                    @if($sub->admin_note)
                    <div style="background:{{ $sub->status === 'approved' ? '#F0FDF4' : '#FEF2F2' }};border-radius:8px;padding:8px 12px;border-left:3px solid {{ $sub->status === 'approved' ? '#10B981' : '#EF4444' }};">
                        <p style="font-size:11px;font-weight:600;color:{{ $sub->status === 'approved' ? '#065F46' : '#991B1B' }};margin:0 0 2px;">
                            Feedback — {{ $sub->reviewer?->name ?? 'Admin' }}
                        </p>
                        <p style="font-size:12px;color:{{ $sub->status === 'approved' ? '#047857' : '#B91C1C' }};margin:0;">{{ $sub->admin_note }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Activity Log --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 20px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-timeline" style="color:#6366F1;"></i> Activity Log
                <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9CA3AF;">{{ $task->logs->count() }} {{ Str::plural('entry', $task->logs->count()) }}</span>
            </h2>
            @forelse($task->logs->sortByDesc('created_at') as $log)
            @php
                [$aico, $aco, $abg] = $log->actionStyle();
                $meta = $log->metadata ?? [];
            @endphp
            <div style="display:flex;gap:14px;padding-bottom:16px;margin-bottom:16px;border-bottom:1px solid #F9FAFB;">
                <div style="flex-shrink:0;">
                    <div style="width:34px;height:34px;border-radius:50%;background:{{ $abg }};display:flex;align-items:center;justify-content:center;">
                        <i class="fa {{ $aico }}" style="color:{{ $aco }};font-size:13px;"></i>
                    </div>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
                        <span style="font-size:12px;font-weight:700;padding:2px 8px;border-radius:10px;background:{{ $abg }};color:{{ $aco }};">{{ $log->actionLabel() }}</span>
                        <span style="font-size:12px;font-weight:600;color:#111827;">{{ $log->user?->name ?? 'System' }}</span>
                        <span style="font-size:11px;color:#9CA3AF;">{{ $log->created_at->diffForHumans() }}</span>
                    </div>

                    {{-- Rich metadata chips --}}
                    @if(!empty($meta))
                    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:5px;">
                        @if(in_array($log->action, ['task_reassigned','task_transferred']) && isset($meta['from_user_name'], $meta['to_user_name']))
                        <span style="font-size:11px;background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:6px;">
                            <span style="text-decoration:line-through;opacity:.7;">{{ $meta['from_user_name'] }}</span>
                            <i class="fa fa-arrow-right" style="font-size:9px;margin:0 3px;"></i>
                            <strong>{{ $meta['to_user_name'] }}</strong>
                        </span>
                        @if(!empty($meta['performed_by'] ?? $meta['reassigned_by'] ?? null))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">by {{ $meta['performed_by'] ?? $meta['reassigned_by'] }}</span>
                        @endif
                        @if(!empty($meta['offboarding']))
                        <span style="font-size:11px;background:#FEE2E2;color:#DC2626;padding:2px 8px;border-radius:6px;"><i class="fa fa-user-slash" style="margin-right:3px;"></i>offboarding</span>
                        @elseif(!empty($meta['is_bulk']))
                        <span style="font-size:11px;background:#EDE9FE;color:#7C3AED;padding:2px 8px;border-radius:6px;">bulk transfer</span>
                        @endif
                        @elseif(in_array($log->action, ['status_updated_completed','status_updated_in_progress']) && isset($meta['reviewer_name']))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">reviewer: <strong>{{ $meta['reviewer_name'] }}</strong></span>
                        @if(isset($meta['submission_version']))
                        <span style="font-size:11px;background:#EEF2FF;color:#4F46E5;padding:2px 8px;border-radius:6px;">v{{ $meta['submission_version'] }}</span>
                        @endif
                        @if(isset($meta['approval_note']) && $meta['approval_note'])
                        <span style="font-size:11px;background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:6px;">note: {{ Str::limit($meta['approval_note'], 60) }}</span>
                        @endif
                        @if(isset($meta['rejection_reason']) && $meta['rejection_reason'])
                        <span style="font-size:11px;background:#FEE2E2;color:#991B1B;padding:2px 8px;border-radius:6px;">reason: {{ Str::limit($meta['rejection_reason'], 60) }}</span>
                        @endif
                        @elseif($log->action === 'status_updated_pending_approval' && isset($meta['version']))
                        <span style="font-size:11px;background:#EEF2FF;color:#4F46E5;padding:2px 8px;border-radius:6px;">v{{ $meta['version'] }}</span>
                        @if(!empty($meta['has_file']))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;"><i class="fa fa-paperclip" style="margin-right:3px;"></i>{{ $meta['filename'] ?? 'file attached' }}</span>
                        @endif
                        @if(isset($meta['submission_note']) && $meta['submission_note'])
                        <span style="font-size:11px;background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:6px;">{{ Str::limit($meta['submission_note'], 60) }}</span>
                        @endif
                        @elseif($log->action === 'first_viewed' && isset($meta['viewer_name']))
                        <span style="font-size:11px;background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:6px;"><i class="fa fa-eye" style="margin-right:3px;"></i>{{ $meta['viewer_name'] }}</span>
                        @elseif($log->action === 'status_updated_delivered' && isset($meta['delivered_by_name']))
                        <span style="font-size:11px;background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:6px;"><i class="fa fa-truck" style="margin-right:3px;"></i>{{ $meta['delivered_by_name'] }}</span>
                        @if(isset($meta['delivery_note']) && $meta['delivery_note'])
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">{{ Str::limit($meta['delivery_note'], 60) }}</span>
                        @endif
                        @elseif($log->action === 'task_created')
                        @if(isset($meta['assigned_to_name']))
                        <span style="font-size:11px;background:#EEF2FF;color:#4F46E5;padding:2px 8px;border-radius:6px;">assigned to <strong>{{ $meta['assigned_to_name'] }}</strong></span>
                        @endif
                        @if(isset($meta['priority']))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">priority: {{ $meta['priority'] }}</span>
                        @endif
                        @if(isset($meta['deadline']))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">due {{ $meta['deadline'] }}</span>
                        @endif
                        @elseif(isset($meta['old_status'], $meta['new_status']))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">
                            <span style="text-decoration:line-through;opacity:.7;">{{ str_replace('_',' ',$meta['old_status']) }}</span> → <strong>{{ str_replace('_',' ',$meta['new_status']) }}</strong>
                        </span>
                        @endif
                    </div>
                    @endif

                    @if($log->note && !in_array($log->action, ['comment_added','task_created','first_viewed']))
                    <p style="font-size:12px;color:#6B7280;background:#F9FAFB;padding:6px 10px;border-radius:8px;border-left:3px solid #E5E7EB;margin:6px 0 0;">"{{ $log->note }}"</p>
                    @endif
                </div>
            </div>
            @empty
            <p style="font-size:14px;color:#9CA3AF;text-align:center;padding:24px 0;margin:0;">No activity yet.</p>
            @endforelse
        </div>

    </div>{{-- /left --}}

    {{-- Right sidebar --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Reassign Task --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:#374151;margin:0 0 12px;text-transform:uppercase;letter-spacing:.04em;">Reassign Task</h3>
            <form method="POST" action="{{ route('admin.tasks.reassign', $task) }}">
                @csrf
                <select name="assigned_to" required
                        style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;outline:none;margin-bottom:10px;">
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ $u->id == $task->assigned_to ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
                <button type="submit"
                        style="width:100%;background:#F3F4F6;color:#374151;border:none;padding:9px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                    <i class="fa fa-arrows-rotate" style="margin-right:5px;"></i> Reassign
                </button>
            </form>
        </div>

        {{-- Quick Info --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:.04em;">Quick Info</h3>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-folder" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Project</span>
                    <a href="{{ route('admin.projects.show', $task->project_id) }}" style="font-size:13px;font-weight:600;color:#6366F1;text-decoration:none;">{{ Str::limit($task->project->name, 18) }}</a>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-calendar" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Deadline</span>
                    <span style="font-size:13px;font-weight:600;color:{{ $isOverdue ? '#DC2626' : '#111827' }};">{{ $task->deadline->format('M d, Y') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-eye" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>First Viewed</span>
                    <span style="font-size:12px;color:#6B7280;">{{ $task->first_viewed_at ? $task->first_viewed_at->format('M d') : 'Never' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-circle-half-stroke" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Status</span>
                    <span style="padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600;background:{{ $s['bg'] }};color:{{ $s['color'] }};">{{ $s['label'] }}</span>
                </div>
            </div>
        </div>

        {{-- Approvals link --}}
        <a href="{{ route('admin.approvals.index') }}"
           style="display:flex;align-items:center;justify-content:center;gap:8px;padding:12px;background:#EEF2FF;border-radius:12px;text-decoration:none;color:#4F46E5;font-size:13px;font-weight:600;">
            <i class="fa fa-list-check"></i> Back to Approvals
        </a>

        {{-- Transfer History --}}
        @if($task->transfers->isNotEmpty())
        <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:#374151;margin:0 0 12px;text-transform:uppercase;letter-spacing:.04em;display:flex;align-items:center;gap:6px;">
                <i class="fa fa-arrow-right-arrow-left" style="color:#6366F1;font-size:12px;"></i> Transfer History
            </h3>
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($task->transfers as $transfer)
                <div style="border-left:3px solid #C7D2FE;padding-left:12px;">
                    <p style="font-size:12px;font-weight:600;color:#111827;margin:0;">
                        {{ $transfer->fromUser?->name ?? 'Former Employee' }}
                        <span style="color:#6366F1;"> → </span>
                        {{ $transfer->toUser?->name ?? '—' }}
                    </p>
                    <p style="font-size:11px;color:#9CA3AF;margin:2px 0;">
                        {{ $transfer->transferred_at->format('M d, Y · H:i') }}
                        · by {{ $transfer->transferredBy?->name ?? 'Admin' }}
                    </p>
                    @if($transfer->reason)
                    <p style="font-size:11px;color:#6B7280;margin:4px 0 0;background:#F9FAFB;padding:5px 8px;border-radius:6px;line-height:1.4;">
                        "{{ \Illuminate\Support\Str::limit($transfer->reason, 100) }}"
                    </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Archive --}}
        @if(!in_array($task->status, ['archived','delivered']))
        <form method="POST" action="{{ route('admin.tasks.archive', $task) }}"
              onsubmit="return confirm('Archive this task? It will be hidden from active views.')">
            @csrf
            <button type="submit"
                    style="width:100%;background:#F3F4F6;color:#6B7280;border:none;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                <i class="fa fa-box-archive"></i> Archive Task
            </button>
        </form>
        @endif

    </div>{{-- /right --}}

</div>
@endsection

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
        'submitted'          => ['bg'=>'#EDE9FE','color'=>'#7C3AED','label'=>'In Review'],
        'completed'          => ['bg'=>'#D1FAE5','color'=>'#059669','label'=>'Completed'],
    ];
    $priorityMap = ['low'=>['bg'=>'#D1FAE5','color'=>'#059669'],'medium'=>['bg'=>'#FEF3C7','color'=>'#D97706'],'high'=>['bg'=>'#FEE2E2','color'=>'#DC2626']];
    $s = $statusMap[$task->status]    ?? $statusMap['pending'];
    $p = $priorityMap[$task->priority] ?? $priorityMap['medium'];
    $latestSub = $task->submissions->first();
    $tagColors = ['#EEF2FF:#4F46E5','#FEF3C7:#D97706','#FEE2E2:#DC2626','#D1FAE5:#059669','#FCE7F3:#BE185D','#E0E7FF:#3730A3'];
@endphp

{{-- ═══════════ APPROVAL MODAL ═══════════ --}}
<div x-data="taskApprovalPage()" @keydown.escape.window="if(approvalModal) approvalModal=false; else if(rejectModal) rejectModal=false;">

<template x-teleport="body">
    <div x-show="approvalModal" x-cloak
         style="position:fixed;inset:0;z-index:99999;backdrop-filter:blur(4px);background:rgba(15,18,40,.6);">
        <div @click.self="approvalModal=false"
             style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#fff;border-radius:22px;width:100%;max-width:500px;box-shadow:0 28px 80px rgba(0,0,0,.25);overflow:hidden;display:flex;flex-direction:column;">
            <div style="padding:22px 26px 18px;border-bottom:1px solid #F0F4F8;background:linear-gradient(135deg,#F0FDF4,#fff);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(16,185,129,.3);">
                        <i class="fas fa-circle-check" style="color:#fff;font-size:16px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Approve Submission</h3>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;" x-text="'Submitted by ' + (approvalTask?.assignee ?? '')"></p>
                    </div>
                </div>
                <button @click="approvalModal=false"
                        style="width:32px;height:32px;border-radius:9px;background:#F3F4F6;border:none;cursor:pointer;color:#6B7280;font-size:13px;display:flex;align-items:center;justify-content:center;transition:background .15s;"
                        onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="padding:14px 26px;background:#F8FAFF;border-bottom:1px solid #F0F4F8;">
                <p style="font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 4px;">Task</p>
                <p style="font-size:14px;font-weight:600;color:#111827;margin:0;line-height:1.4;" x-text="approvalTask?.title"></p>
            </div>
            <form :action="approvalTask ? approvalTask.url : '#'" method="POST" style="padding:20px 26px 24px;overflow-y:auto;">
                @csrf
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                        Approval Note <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— optional</span>
                    </label>
                    <input type="text" name="note" x-model="approvalNote"
                           placeholder="Great work! The deliverable looks perfect..."
                           style="width:100%;padding:10px 13px;border:1.5px solid #BBF7D0;background:#F0FDF4;border-radius:10px;font-size:13px;color:#111827;outline:none;box-sizing:border-box;transition:border-color .15s,box-shadow .15s;"
                           onfocus="this.style.borderColor='#34D399';this.style.boxShadow='0 0 0 3px rgba(52,211,153,.12)'"
                           onblur="this.style.borderColor='#BBF7D0';this.style.boxShadow='none'">
                </div>
                <div style="background:#F8FAFF;border:1px solid #EEF2FF;border-radius:14px;padding:18px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#EEF2FF,#DDD6FE);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-share-nodes" style="color:#6366F1;font-size:11px;"></i>
                        </div>
                        <div>
                            <p style="font-size:13px;font-weight:700;color:#111827;margin:0;">Social Media Posting</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Does this task need to be posted on social media?</p>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:14px;">
                        <button type="button" @click="approvalSocial = 'yes'"
                                :style="approvalSocial === 'yes'
                                    ? 'padding:10px 6px;border-radius:10px;border:2px solid #6366F1;background:#EEF2FF;color:#4F46E5;font-size:12px;font-weight:700;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'
                                    : 'padding:10px 6px;border-radius:10px;border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;font-size:12px;font-weight:600;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'">
                            <i class="fas fa-check-circle" :style="approvalSocial === 'yes' ? 'font-size:16px;color:#6366F1;' : 'font-size:16px;color:#D1D5DB;'"></i>
                            Yes, assign
                        </button>
                        <button type="button" @click="approvalSocial = 'no'"
                                :style="approvalSocial === 'no'
                                    ? 'padding:10px 6px;border-radius:10px;border:2px solid #6B7280;background:#F3F4F6;color:#374151;font-size:12px;font-weight:700;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'
                                    : 'padding:10px 6px;border-radius:10px;border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;font-size:12px;font-weight:600;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'">
                            <i class="fas fa-ban" :style="approvalSocial === 'no' ? 'font-size:16px;color:#6B7280;' : 'font-size:16px;color:#D1D5DB;'"></i>
                            Not needed
                        </button>
                        <button type="button" @click="approvalSocial = 'later'"
                                :style="approvalSocial === 'later'
                                    ? 'padding:10px 6px;border-radius:10px;border:2px solid #D97706;background:#FFFBEB;color:#D97706;font-size:12px;font-weight:700;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'
                                    : 'padding:10px 6px;border-radius:10px;border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;font-size:12px;font-weight:600;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'">
                            <i class="fas fa-clock" :style="approvalSocial === 'later' ? 'font-size:16px;color:#D97706;' : 'font-size:16px;color:#D1D5DB;'"></i>
                            Decide later
                        </button>
                    </div>
                    <div x-show="approvalSocial === 'yes'" x-transition style="margin-top:4px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                            Assign social post to <span style="color:#EF4444;">*</span>
                        </label>
                        <select name="social_assigned_to" x-model="approvalSocialUser"
                                style="width:100%;padding:9px 12px;border:1.5px solid #C7D2FE;background:#fff;border-radius:10px;font-size:13px;color:#374151;outline:none;cursor:pointer;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#6366F1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,.12)'"
                                onblur="this.style.borderColor='#C7D2FE';this.style.boxShadow='none'">
                            <option value="">— Select team member —</option>
                            @foreach($socialUsers as $su)
                            <option value="{{ $su->id }}">{{ $su->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="social_required"
                           :value="approvalSocial === 'yes' ? '1' : (approvalSocial === 'no' ? '0' : '')">
                </div>
                <div style="display:flex;gap:10px;margin-top:20px;">
                    <button type="button" @click="approvalModal=false"
                            style="flex:1;padding:11px;background:#F3F4F6;color:#374151;border:none;border-radius:11px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="approvalSocial === 'yes' && !approvalSocialUser"
                            :style="(approvalSocial === 'yes' && !approvalSocialUser)
                                ? 'flex:2;padding:11px;background:#D1FAE5;color:#6EE7B7;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:not-allowed;display:flex;align-items:center;justify-content:center;gap:7px;'
                                : 'flex:2;padding:11px;background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px rgba(16,185,129,.35);transition:opacity .15s;'">
                        <i class="fas fa-circle-check"></i>
                        <span x-text="approvalSocial === 'yes' && !approvalSocialUser ? 'Select a team member first' : 'Confirm Approval'"></span>
                    </button>
                </div>
            </form>
        </div>
        </div>
    </div>
</template>

{{-- ═══════════ REJECT MODAL ═══════════ --}}
<template x-teleport="body">
    <div x-show="rejectModal" x-cloak
         style="position:fixed;inset:0;z-index:99999;backdrop-filter:blur(4px);background:rgba(15,18,40,.6);">
        <div @click.self="rejectModal=false"
             style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#fff;border-radius:22px;width:100%;max-width:440px;box-shadow:0 28px 80px rgba(0,0,0,.25);overflow:hidden;display:flex;flex-direction:column;">
            <div style="padding:22px 26px 18px;border-bottom:1px solid #F0F4F8;background:linear-gradient(135deg,#FFF8F8,#fff);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#EF4444,#DC2626);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(239,68,68,.3);">
                        <i class="fas fa-rotate-left" style="color:#fff;font-size:15px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Request Revision</h3>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;" x-text="'Submitted by ' + (rejectTask?.assignee ?? '')"></p>
                    </div>
                </div>
                <button @click="rejectModal=false"
                        style="width:32px;height:32px;border-radius:9px;background:#F3F4F6;border:none;cursor:pointer;color:#6B7280;font-size:13px;display:flex;align-items:center;justify-content:center;transition:background .15s;"
                        onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="padding:14px 26px;background:#FFF8F8;border-bottom:1px solid #FEE2E2;">
                <p style="font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 4px;">Task</p>
                <p style="font-size:14px;font-weight:600;color:#111827;margin:0;line-height:1.4;" x-text="rejectTask?.title"></p>
            </div>
            <form :action="rejectTask ? rejectTask.url : '#'" method="POST" style="padding:20px 26px 24px;">
                @csrf
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                        Reason for revision <span style="color:#EF4444;">*</span>
                    </label>
                    <textarea name="note" required x-model="rejectNote" rows="3"
                              placeholder="Explain what needs to be changed..."
                              style="width:100%;padding:10px 13px;border:1.5px solid #FECACA;background:#FEF2F2;border-radius:10px;font-size:13px;color:#111827;outline:none;box-sizing:border-box;resize:vertical;transition:border-color .15s,box-shadow .15s;font-family:inherit;"
                              onfocus="this.style.borderColor='#F87171';this.style.boxShadow='0 0 0 3px rgba(248,113,113,.12)'"
                              onblur="this.style.borderColor='#FECACA';this.style.boxShadow='none'"></textarea>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" @click="rejectModal=false"
                            style="flex:1;padding:11px;background:#F3F4F6;color:#374151;border:none;border-radius:11px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="!rejectNote.trim()"
                            :style="!rejectNote.trim()
                                ? 'flex:2;padding:11px;background:#FEE2E2;color:#FCA5A5;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:not-allowed;display:flex;align-items:center;justify-content:center;gap:7px;'
                                : 'flex:2;padding:11px;background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px rgba(239,68,68,.35);transition:opacity .15s;'">
                        <i class="fas fa-rotate-left"></i>
                        Request Revision
                    </button>
                </div>
            </form>
        </div>
        </div>
    </div>
</template>

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

        {{-- Project Attachments --}}
        @if($task->project->attachments->isNotEmpty())
        <div x-data="{
                open: false,
                att: null,
                show(item) { this.att = item; this.open = true; },
                close() { this.open = false; this.att = null; }
             }"
             @keydown.escape.window="close()">

            <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
                <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                    <i class="fa fa-paperclip" style="color:#6366F1;"></i> Attachments
                    <span style="margin-left:auto;font-size:12px;font-weight:400;color:#9CA3AF;">{{ $task->project->attachments->count() }} {{ Str::plural('file', $task->project->attachments->count()) }}</span>
                </h2>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($task->project->attachments as $att)
                    @php $item = ['name'=>$att->name,'size'=>$att->humanSize(),'url'=>$att->url(),'downloadUrl'=>$att->isFile()?route('admin.attachments.download',$att):$att->url(),'icon'=>$att->iconClass(),'isLink'=>$att->isLink(),'isImage'=>in_array(strtolower(pathinfo($att->name,PATHINFO_EXTENSION)),['jpg','jpeg','png','gif','webp','svg'])]; @endphp
                    <button type="button" @click="show({{ json_encode($item) }})"
                            style="display:flex;align-items:center;gap:12px;padding:10px 12px;background:#FAFAFA;border:1px solid #F3F4F6;border-radius:10px;width:100%;text-align:left;cursor:pointer;transition:border-color .15s,background .15s;"
                            onmouseover="this.style.background='#F0F0FF';this.style.borderColor='#C7D2FE'" onmouseout="this.style.background='#FAFAFA';this.style.borderColor='#F3F4F6'">
                        <div style="width:36px;height:36px;border-radius:9px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa {{ $att->iconClass() }}" style="color:#6366F1;font-size:14px;"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $att->name }}</p>
                            @if($att->isFile() && $att->size)
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $att->humanSize() }}</p>
                            @endif
                        </div>
                        <i class="fa fa-eye" style="color:#9CA3AF;font-size:13px;flex-shrink:0;"></i>
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Attachment preview modal --}}
            <template x-teleport="body">
                <div x-show="open" x-cloak
                     @keydown.escape.window="close()"
                     style="position:fixed;inset:0;z-index:9999;">
                    <div @click.self="close()"
                         style="width:100%;height:100%;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;padding:16px;">
                    <div x-transition
                         style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:min(90vw,900px);overflow:hidden;">
                        <template x-if="att">
                        <div>
                            <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i :class="'fa '+att.icon" style="color:#6366F1;font-size:16px;"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="att.name"></p>
                                    <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;" x-text="att.size || (att.isLink ? 'External link' : '')"></p>
                                </div>
                                <button @click="close()" style="width:32px;height:32px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fa fa-xmark" style="color:#6B7280;font-size:13px;"></i>
                                </button>
                            </div>
                            <template x-if="att.isImage">
                                <div style="padding:16px 24px;border-bottom:1px solid #F3F4F6;background:#F9FAFB;display:flex;justify-content:center;">
                                    <img :src="att.url" :alt="att.name" style="max-width:100%;max-height:75vh;border-radius:10px;object-fit:contain;display:block;">
                                </div>
                            </template>
                            <div style="padding:16px 24px;display:flex;gap:10px;justify-content:flex-end;">
                                <button @click="close()"
                                        style="padding:9px 18px;background:#F3F4F6;color:#6B7280;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                                    Close
                                </button>
                                <a :href="att.downloadUrl"
                                   :target="att.isLink ? '_blank' : '_self'"
                                   :rel="att.isLink ? 'noopener' : ''"
                                   style="display:inline-flex;align-items:center;gap:6px;padding:9px 20px;background:#6366F1;color:#fff;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;transition:background .15s;"
                                   onmouseover="this.style.background='#4F46E5'" onmouseout="this.style.background='#6366F1'">
                                    <i :class="'fa '+(att.isLink ? 'fa-arrow-up-right-from-square' : 'fa-download')" style="font-size:11px;"></i>
                                    <span x-text="att.isLink ? 'Open Link' : 'Download'"></span>
                                </a>
                            </div>
                        </div>
                        </template>
                    </div>
                    </div>
                </div>
            </template>

        </div>
        @endif

        {{-- Admin Actions: Approve/Reject (only when submitted) --}}
        @if($task->status === 'submitted')
        <div style="background:#fff;border-radius:14px;border:1.5px solid #A78BFA;box-shadow:0 4px 16px rgba(124,58,237,.08);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-gavel" style="color:#7C3AED;"></i> Review Submission
            </h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <button type="button"
                        @click="openApprovalModal({
                            id:       {{ $task->id }},
                            title:    @js($task->title),
                            assignee: @js($task->assignee->name ?? 'Unknown'),
                            url:      '{{ route('admin.tasks.approve', $task) }}'
                        })"
                        style="width:100%;background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:none;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 8px rgba(16,185,129,.25);transition:opacity .15s;"
                        onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                    <i class="fa fa-circle-check"></i> Approve
                </button>
                <button type="button"
                        @click="openRejectModal({
                            id:       {{ $task->id }},
                            title:    @js($task->title),
                            assignee: @js($task->assignee->name ?? 'Unknown'),
                            url:      '{{ route('admin.tasks.reject', $task) }}'
                        })"
                        style="width:100%;background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;border:none;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 8px rgba(239,68,68,.25);transition:opacity .15s;"
                        onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                    <i class="fa fa-rotate-left"></i> Request Revision
                </button>
            </div>
        </div>
        @endif


        {{-- Reopen (when approved, delivered, or archived) --}}
        @if(in_array($task->status, ['approved','delivered','archived']))
        <div style="background:#fff;border-radius:14px;border:1.5px solid #FCD34D;box-shadow:0 4px 16px rgba(217,119,6,.06);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 8px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-rotate-right" style="color:#D97706;"></i> Reopen Task
            </h2>
            <p style="font-size:13px;color:#6B7280;margin:0 0 14px;">Send this task back to <strong>In Progress</strong> so the assignee can continue working on it.</p>
            <form method="POST" action="{{ route('admin.tasks.reopen', $task) }}"
                  onsubmit="return confirm('Reopen this task and set it back to In Progress?')">
                @csrf
                <button type="submit"
                        style="background:linear-gradient(135deg,#F59E0B,#D97706);color:#fff;border:none;padding:9px 22px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(217,119,6,.3);">
                    <i class="fa fa-rotate-right"></i> Reopen Task
                </button>
            </form>
        </div>
        @endif

        {{-- Add Comment --}}
        <div x-data="{ commentFile: '' }" style="background:#fff;border-radius:14px;border:1.5px solid #6366F1;box-shadow:0 4px 16px rgba(99,102,241,.08);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 4px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-comment" style="color:#6366F1;"></i> Add a Comment
            </h2>
            <p style="font-size:12px;color:#9CA3AF;margin:0 0 16px;">Leave a note, feedback, or update for the assignee.</p>
            <form method="POST" action="{{ route('admin.tasks.comment', $task) }}" enctype="multipart/form-data">
                @csrf
                <textarea name="body" rows="3" required placeholder="Write your comment..."
                          style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;line-height:1.5;margin-bottom:10px;"
                          onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                <div style="margin-bottom:12px;">
                    <label style="display:flex;align-items:center;gap:12px;padding:12px 16px;border:1.5px dashed #D1D5DB;border-radius:10px;cursor:pointer;background:#FAFAFA;"
                           onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#D1D5DB'">
                        <i class="fa fa-paperclip" style="color:#9CA3AF;font-size:16px;"></i>
                        <div style="flex:1;">
                            <p x-text="commentFile || 'Attach a file (optional)'" style="font-size:13px;font-weight:500;color:#374151;margin:0;"></p>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Images, PDF, ZIP and more · max 20MB</p>
                        </div>
                        <input type="file" name="file" @change="commentFile = $event.target.files[0]?.name || ''" style="display:none;">
                    </label>
                </div>
                <button type="submit"
                        style="width:100%;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(99,102,241,.3);">
                    <i class="fa fa-paper-plane" style="margin-right:6px;"></i> Post Comment
                </button>
            </form>
        </div>

        {{-- ══ Unified Timeline ══ --}}
        @php
            $tlSubMap = [
                'submitted' => ['#EEF2FF','#4F46E5','fa-hourglass-half','In Review'],
                'approved'  => ['#D1FAE5','#059669','fa-circle-check',   'Approved'],
                'rejected'  => ['#FEE2E2','#DC2626','fa-rotate-left',    'Revision Requested'],
            ];
            $timeline = collect();

            foreach ($task->submissions as $sub) {
                [$sbg,$sco,$sico,$slbl] = $tlSubMap[$sub->status] ?? $tlSubMap['submitted'];
                $timeline->push(['type'=>'submission','at'=>$sub->created_at,'sub'=>$sub,'sbg'=>$sbg,'sco'=>$sco,'sico'=>$sico,'slbl'=>$slbl]);
            }
            foreach ($task->logs->whereNotIn('action', ['comment_added', 'status_updated_submitted', 'status_updated_in_progress', 'status_updated_revision_requested', 'status_updated_approved']) as $log) {
                [$aico,$aco,$abg] = $log->actionStyle();
                $timeline->push(['type'=>'log','at'=>$log->created_at,'log'=>$log,'aico'=>$aico,'aco'=>$aco,'abg'=>$abg]);
            }
            foreach ($task->comments as $comment) {
                $timeline->push(['type'=>'comment','at'=>$comment->created_at,'comment'=>$comment,'isAdmin'=>in_array($comment->user->role ?? 'user',['admin','manager'])]);
            }
            $timeline = $timeline->sortByDesc('at')->values();

            // Find the chronologically first submission or assignee comment
            $firstWorkEntry = $timeline
                ->filter(fn($e) =>
                    $e['type'] === 'submission' ||
                    ($e['type'] === 'comment' && ($e['comment']->user_id ?? null) === $task->assigned_to)
                )
                ->sortBy('at')
                ->first();
            $firstWorkKey = $firstWorkEntry ? $firstWorkEntry['at']->toDateTimeString() : null;
        @endphp

        <div x-data="{
                open: false,
                sub: null,
                show(item) { this.sub = item; this.open = true; },
                close() { this.open = false; this.sub = null; }
             }"
             @keydown.escape.window="if(open) close()"
             style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">

            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-timeline" style="color:#6366F1;"></i> Timeline
                <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9CA3AF;">{{ $timeline->count() }} {{ Str::plural('event', $timeline->count()) }}</span>
            </h2>

            @if($timeline->isNotEmpty())
            <div>
                @foreach($timeline as $entry)
                @php $isLast = $loop->last; @endphp

                @if($entry['type'] === 'log')
                @php $log = $entry['log']; $meta = $log->metadata ?? []; @endphp
                <div style="display:flex;gap:14px;">
                    <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:32px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:{{ $entry['abg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;z-index:1;">
                            <i class="fa {{ $entry['aico'] }}" style="color:{{ $entry['aco'] }};font-size:12px;"></i>
                        </div>
                        @if(!$isLast)<div style="width:2px;flex:1;min-height:20px;background:#EBEBEB;margin:4px 0;"></div>@endif
                    </div>
                    <div style="flex:1;min-width:0;padding-bottom:{{ $isLast ? '0' : '20px' }};">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
                            <span style="font-size:12px;font-weight:700;padding:2px 8px;border-radius:10px;background:{{ $entry['abg'] }};color:{{ $entry['aco'] }};">{{ $log->actionLabel() }}</span>
                            <span style="font-size:12px;font-weight:600;color:#111827;">{{ $log->user?->name ?? 'System' }}</span>
                            <span style="font-size:11px;color:#9CA3AF;margin-left:auto;" title="{{ $log->created_at->format('Y-m-d H:i') }}">{{ $log->created_at->format('M d, H:i') }}</span>
                        </div>
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
                            @elseif($log->action === 'status_updated_submitted' && isset($meta['version']))
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
                            @elseif($log->action === 'deadline_updated' && isset($meta['old_deadline'], $meta['new_deadline']))
                            <span style="font-size:11px;background:#FEE2E2;color:#DC2626;padding:2px 8px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;">
                                <span style="text-decoration:line-through;opacity:.7;">{{ $meta['old_deadline'] }}</span>
                                <i class="fa fa-arrow-right" style="font-size:9px;"></i>
                                <strong>{{ $meta['new_deadline'] }}</strong>
                            </span>
                            @if(!empty($meta['changed_by_name']))
                            <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">by {{ $meta['changed_by_name'] }}</span>
                            @endif
                            @if(!empty($meta['reason']))
                            <span style="font-size:11px;background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:6px;">{{ Str::limit($meta['reason'], 80) }}</span>
                            @endif
                            @elseif(isset($meta['old_status'], $meta['new_status']))
                            <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">
                                <span style="text-decoration:line-through;opacity:.7;">{{ str_replace('_',' ',$meta['old_status']) }}</span> → <strong>{{ str_replace('_',' ',$meta['new_status']) }}</strong>
                            </span>
                            @endif
                        </div>
                        @endif
                        @if($log->note && !in_array($log->action, ['comment_added','task_created','first_viewed','deadline_updated']))
                        <p style="font-size:12px;color:#6B7280;background:#F9FAFB;padding:6px 10px;border-radius:8px;border-left:3px solid #E5E7EB;margin:6px 0 0;">"{{ $log->note }}"</p>
                        @endif
                    </div>
                </div>

                @elseif($entry['type'] === 'submission')
                @php
                    $sub = $entry['sub'];
                    $subExt = strtolower(pathinfo($sub->original_filename ?? '', PATHINFO_EXTENSION));
                    $subIsImage = in_array($subExt, ['jpg','jpeg','png','gif','webp','svg']);
                    $subIsVideo = in_array($subExt, ['mp4','mov','avi','webm','mkv']);
                    $subUrl = $sub->fileUrl();
                    $subIconMap = ['pdf'=>'fa-file-pdf','doc'=>'fa-file-word','docx'=>'fa-file-word','xls'=>'fa-file-excel','xlsx'=>'fa-file-excel','ppt'=>'fa-file-powerpoint','pptx'=>'fa-file-powerpoint','zip'=>'fa-file-zipper','rar'=>'fa-file-zipper','txt'=>'fa-file-lines'];
                    $subIcon = $subIconMap[$subExt] ?? 'fa-file';
                    $isFirstWork = $firstWorkKey && $entry['at']->toDateTimeString() === $firstWorkKey;
                    $subItem = $sub->file_path ? [
                        'name'        => $sub->original_filename ?? 'file',
                        'url'         => $subUrl,
                        'downloadUrl' => route('admin.submissions.download', $sub),
                        'isImage'     => $subIsImage,
                        'version'     => $sub->version,
                    ] : null;
                @endphp
                <div x-data="{ editingNote: false, showNoteHistory: false, note: {{ json_encode($sub->note ?? '') }} }" style="display:flex;gap:14px;">
                    <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:32px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:{{ $entry['sbg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;z-index:1;box-shadow:0 0 0 3px {{ $entry['sbg'] }};">
                            <i class="fa {{ $entry['sico'] }}" style="color:{{ $entry['sco'] }};font-size:12px;"></i>
                        </div>
                        @if(!$isLast)<div style="width:2px;flex:1;min-height:20px;background:#EBEBEB;margin:4px 0;"></div>@endif
                    </div>
                    <div style="flex:1;min-width:0;padding-bottom:{{ $isLast ? '0' : '20px' }};">
                        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
                            <span style="font-size:12px;font-weight:700;color:#111827;">Version {{ $sub->version }}</span>
                            <span style="font-size:11px;font-weight:600;padding:1px 8px;border-radius:8px;background:{{ $entry['sbg'] }};color:{{ $entry['sco'] }};">{{ $entry['slbl'] }}</span>
                            @if($isFirstWork)<span style="font-size:10px;font-weight:700;padding:1px 8px;border-radius:10px;background:#D1FAE5;color:#059669;display:inline-flex;align-items:center;gap:3px;"><i class="fa fa-circle-play" style="font-size:9px;"></i> Started Working</span>@endif
                            <span style="font-size:11px;color:#9CA3AF;margin-left:auto;" title="{{ $sub->created_at->format('Y-m-d H:i') }}">{{ $sub->created_at->format('M d, H:i') }}</span>
                        </div>
                        <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:12px 14px;">
                            <div style="margin-bottom:10px;">
                                <div style="display:flex;align-items:flex-start;gap:6px;">
                                    <p x-show="!editingNote" style="font-size:13px;color:#374151;margin:0;line-height:1.6;flex:1;" x-text="note || ''"></p>
                                    <button @click="editingNote=!editingNote" style="font-size:10px;background:none;border:none;color:#9CA3AF;cursor:pointer;padding:0;flex-shrink:0;margin-top:2px;" title="Edit note">
                                        <i class="fa fa-pencil" style="font-size:10px;"></i>
                                    </button>
                                    @if($sub->noteEdits->isNotEmpty())
                                    <button @click="showNoteHistory=!showNoteHistory" style="font-size:10px;background:#F3F4F6;color:#9CA3AF;border:none;padding:1px 6px;border-radius:4px;cursor:pointer;flex-shrink:0;">edited</button>
                                    @endif
                                </div>
                                <div x-show="editingNote">
                                    <form method="POST" action="{{ route('admin.tasks.submissions.note', [$task, $sub]) }}">
                                        @csrf @method('PATCH')
                                        <textarea name="note" x-model="note" rows="3"
                                                  style="width:100%;padding:10px 14px;border:1.5px solid #6366F1;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;line-height:1.5;margin-top:6px;"></textarea>
                                        <div style="display:flex;gap:8px;margin-top:8px;">
                                            <button type="submit" style="background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:7px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Save</button>
                                            <button type="button" @click="editingNote=false" style="background:#F3F4F6;color:#374151;border:none;padding:7px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                                @if($sub->noteEdits->isNotEmpty())
                                <div x-show="showNoteHistory" style="background:#fff;border:1px solid #E5E7EB;border-radius:8px;padding:10px;margin-top:6px;">
                                    @foreach($sub->noteEdits as $noteEdit)
                                    <div style="border-bottom:1px solid #F3F4F6;padding:6px 0;font-size:12px;color:#6B7280;">
                                        <span style="color:#9CA3AF;font-size:11px;">{{ $noteEdit->created_at->format('M d, Y · H:i') }}</span>
                                        <p style="margin:3px 0 0;color:#374151;">{{ $noteEdit->old_note ?? '(empty)' }}</p>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @if($sub->file_path)
                                @if($subIsImage)
                                <button type="button" @click="show({{ json_encode($subItem) }})"
                                        style="display:block;margin-bottom:10px;border-radius:8px;overflow:hidden;border:1px solid #E5E7EB;max-width:300px;width:100%;cursor:pointer;background:none;padding:0;text-align:left;transition:border-color .15s;"
                                        onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                                    <img src="{{ $subUrl }}" alt="{{ $sub->original_filename }}" style="width:100%;max-height:160px;object-fit:cover;display:block;">
                                    <div style="padding:5px 10px;background:#F3F4F6;display:flex;align-items:center;gap:6px;">
                                        <i class="fa fa-image" style="color:#6366F1;font-size:10px;"></i>
                                        <span style="font-size:11px;color:#6B7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">{{ $sub->original_filename }}</span>
                                        <i class="fa fa-expand" style="font-size:9px;color:#9CA3AF;flex-shrink:0;"></i>
                                    </div>
                                </button>
                                @elseif($subIsVideo)
                                <button type="button" @click="show({{ json_encode($subItem) }})"
                                        style="display:block;margin-bottom:10px;border-radius:8px;overflow:hidden;border:1px solid #E5E7EB;max-width:300px;width:100%;cursor:pointer;background:none;padding:0;text-align:left;transition:border-color .15s;"
                                        onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                                    <div style="background:#1F2937;height:110px;display:flex;align-items:center;justify-content:center;">
                                        <div style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;">
                                            <i class="fa fa-play" style="color:#fff;font-size:15px;margin-left:3px;"></i>
                                        </div>
                                    </div>
                                    <div style="padding:5px 10px;background:#F3F4F6;display:flex;align-items:center;gap:6px;">
                                        <i class="fa fa-video" style="color:#6366F1;font-size:10px;"></i>
                                        <span style="font-size:11px;color:#6B7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">{{ $sub->original_filename }}</span>
                                        <i class="fa fa-download" style="font-size:9px;color:#9CA3AF;flex-shrink:0;"></i>
                                    </div>
                                </button>
                                @else
                                <button type="button" @click="show({{ json_encode($subItem) }})"
                                        style="display:inline-flex;align-items:center;gap:10px;margin-bottom:10px;padding:10px 14px;background:#fff;border:1px solid #E5E7EB;border-radius:9px;cursor:pointer;max-width:300px;transition:border-color .15s;"
                                        onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                                    <div style="width:36px;height:36px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fa {{ $subIcon }}" style="color:#6366F1;font-size:16px;"></i>
                                    </div>
                                    <div style="flex:1;min-width:0;text-align:left;">
                                        <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $sub->original_filename }}</p>
                                        <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;text-transform:uppercase;">{{ $subExt ?: 'file' }}</p>
                                    </div>
                                    <i class="fa fa-eye" style="font-size:11px;color:#9CA3AF;flex-shrink:0;"></i>
                                </button>
                                @endif
                            @endif
                            @if($sub->status !== 'submitted')
                            <div style="background:{{ $sub->status === 'approved' ? '#F0FDF4' : '#FEF2F2' }};border-radius:8px;padding:8px 12px;border-left:3px solid {{ $sub->status === 'approved' ? '#10B981' : '#EF4444' }};">
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:{{ $sub->admin_note ? '4px' : '0' }};">
                                    <i class="fa {{ $sub->status === 'approved' ? 'fa-circle-check' : 'fa-rotate-left' }}" style="font-size:10px;color:{{ $sub->status === 'approved' ? '#059669' : '#DC2626' }};"></i>
                                    <span style="font-size:11px;font-weight:700;color:{{ $sub->status === 'approved' ? '#065F46' : '#991B1B' }};">{{ $sub->status === 'approved' ? 'Approved' : 'Revision Requested' }}</span>
                                    <span style="font-size:11px;color:{{ $sub->status === 'approved' ? '#059669' : '#DC2626' }};opacity:.7;">by {{ $sub->reviewer?->name ?? 'Admin' }}</span>
                                    @if($sub->reviewed_at)
                                    <span style="font-size:10px;color:#9CA3AF;margin-left:auto;">{{ $sub->reviewed_at->format('M d, H:i') }}</span>
                                    @endif
                                </div>
                                @if($sub->admin_note)
                                <p style="font-size:12px;color:{{ $sub->status === 'approved' ? '#047857' : '#B91C1C' }};margin:0;line-height:1.5;">{{ $sub->admin_note }}</p>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @elseif($entry['type'] === 'comment')
                @php
                    $comment = $entry['comment'];
                    $isAdmin = $entry['isAdmin'];
                    $cExt = strtolower(pathinfo($comment->original_filename ?? '', PATHINFO_EXTENSION));
                    $cIsImage = in_array($cExt, ['jpg','jpeg','png','gif','webp','svg']);
                    $cIsVideo = in_array($cExt, ['mp4','mov','avi','webm','mkv']);
                    $cUrl = $comment->fileUrl();
                    $cIconMap = ['pdf'=>'fa-file-pdf','doc'=>'fa-file-word','docx'=>'fa-file-word','xls'=>'fa-file-excel','xlsx'=>'fa-file-excel','ppt'=>'fa-file-powerpoint','pptx'=>'fa-file-powerpoint','zip'=>'fa-file-zipper','rar'=>'fa-file-zipper','txt'=>'fa-file-lines'];
                    $cIcon = $cIconMap[$cExt] ?? 'fa-file';
                    $isFirstWork = $firstWorkKey && $entry['at']->toDateTimeString() === $firstWorkKey;
                @endphp
                <div x-data="{ editing: false, showHistory: false, body: {{ json_encode($comment->body) }} }" style="display:flex;gap:14px;">
                    <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:32px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:{{ $isAdmin ? 'linear-gradient(135deg,#6366F1,#8B5CF6)' : 'linear-gradient(135deg,#10B981,#059669)' }};display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;z-index:1;">
                            {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                        </div>
                        @if(!$isLast)<div style="width:2px;flex:1;min-height:20px;background:#EBEBEB;margin:4px 0;"></div>@endif
                    </div>
                    <div style="flex:1;min-width:0;padding-bottom:{{ $isLast ? '0' : '20px' }};">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                            <span style="font-size:12px;font-weight:600;color:#111827;">{{ $comment->user->name ?? 'Unknown' }}</span>
                            @if($isAdmin)<span style="font-size:10px;font-weight:700;padding:1px 7px;border-radius:10px;background:#EEF2FF;color:#4F46E5;">Admin</span>@endif
                            @if($isFirstWork)<span style="font-size:10px;font-weight:700;padding:1px 8px;border-radius:10px;background:#D1FAE5;color:#059669;display:inline-flex;align-items:center;gap:3px;"><i class="fa fa-circle-play" style="font-size:9px;"></i> Started Working</span>@endif
                            @if($comment->edits->isNotEmpty())
                            <button @click="showHistory=!showHistory" style="font-size:10px;background:#F3F4F6;color:#9CA3AF;border:none;padding:1px 6px;border-radius:4px;cursor:pointer;">edited</button>
                            @endif
                            @if(auth()->id() === $comment->user_id)
                            <button @click="editing=!editing" style="font-size:10px;background:none;border:none;color:#9CA3AF;cursor:pointer;padding:0;display:flex;align-items:center;gap:3px;" title="Edit comment">
                                <i class="fa fa-pencil" style="font-size:10px;"></i>
                            </button>
                            @endif
                            <span style="font-size:11px;color:#9CA3AF;margin-left:auto;" title="{{ $comment->created_at->format('Y-m-d H:i') }}">{{ $comment->created_at->format('M d, H:i') }}</span>
                        </div>
                        <div style="background:{{ $isAdmin ? '#F5F3FF' : '#F9FAFB' }};border:1px solid {{ $isAdmin ? '#EDE9FE' : '#E5E7EB' }};border-radius:10px;padding:10px 14px;{{ $isAdmin ? 'border-left:3px solid #8B5CF6;' : '' }}">
                            <div x-show="!editing">
                                <p style="font-size:13px;color:#374151;margin:0{{ $comment->file_path ? ' 0 10px' : '' }};line-height:1.6;" x-text="body"></p>
                                @if($comment->file_path)
                                    @if($cIsImage)
                                    <a href="{{ $cUrl }}" target="_blank" style="display:block;border-radius:8px;overflow:hidden;border:1px solid #E5E7EB;max-width:280px;text-decoration:none;">
                                        <img src="{{ $cUrl }}" alt="{{ $comment->original_filename }}" style="width:100%;max-height:140px;object-fit:cover;display:block;">
                                        <div style="padding:5px 10px;background:#F3F4F6;display:flex;align-items:center;gap:6px;">
                                            <i class="fa fa-image" style="color:#6366F1;font-size:10px;"></i>
                                            <span style="font-size:11px;color:#6B7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">{{ $comment->original_filename }}</span>
                                            <i class="fa fa-arrow-up-right-from-square" style="font-size:9px;color:#9CA3AF;flex-shrink:0;"></i>
                                        </div>
                                    </a>
                                    @elseif($cIsVideo)
                                    <a href="{{ $cUrl }}" target="_blank" style="display:block;border-radius:8px;overflow:hidden;border:1px solid #E5E7EB;max-width:280px;text-decoration:none;">
                                        <div style="background:#1F2937;height:90px;display:flex;align-items:center;justify-content:center;">
                                            <div style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;">
                                                <i class="fa fa-play" style="color:#fff;font-size:13px;margin-left:2px;"></i>
                                            </div>
                                        </div>
                                        <div style="padding:5px 10px;background:#F3F4F6;display:flex;align-items:center;gap:6px;">
                                            <i class="fa fa-video" style="color:#6366F1;font-size:10px;"></i>
                                            <span style="font-size:11px;color:#6B7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">{{ $comment->original_filename }}</span>
                                            <i class="fa fa-arrow-up-right-from-square" style="font-size:9px;color:#9CA3AF;flex-shrink:0;"></i>
                                        </div>
                                    </a>
                                    @else
                                    <a href="{{ $cUrl }}" target="_blank" style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;background:#fff;border:1px solid #E5E7EB;border-radius:8px;text-decoration:none;max-width:280px;transition:border-color .15s;"
                                       onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                                        <div style="width:30px;height:30px;border-radius:7px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="fa {{ $cIcon }}" style="color:#6366F1;font-size:13px;"></i>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $comment->original_filename }}</p>
                                            <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;text-transform:uppercase;">{{ $cExt ?: 'file' }}</p>
                                        </div>
                                        <i class="fa fa-arrow-up-right-from-square" style="font-size:10px;color:#9CA3AF;flex-shrink:0;"></i>
                                    </a>
                                    @endif
                                @endif
                            </div>
                            <div x-show="editing">
                                <form method="POST" action="{{ route('admin.tasks.comments.edit', [$task, $comment]) }}">
                                    @csrf @method('PATCH')
                                    <textarea name="body" x-model="body" rows="3"
                                              style="width:100%;padding:10px 14px;border:1.5px solid #6366F1;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;line-height:1.5;"></textarea>
                                    <div style="display:flex;gap:8px;margin-top:8px;">
                                        <button type="submit" style="background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:7px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Save</button>
                                        <button type="button" @click="editing=false" style="background:#F3F4F6;color:#374151;border:none;padding:7px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @if($comment->edits->isNotEmpty())
                        <div x-show="showHistory" style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:10px;margin-top:6px;">
                            @foreach($comment->edits as $editEntry)
                            <div style="border-bottom:1px solid #F3F4F6;padding:6px 0;font-size:12px;color:#6B7280;">
                                <span style="color:#9CA3AF;font-size:11px;">{{ $editEntry->created_at->format('M d, Y · H:i') }}</span>
                                <p style="margin:3px 0 0;color:#374151;">{{ $editEntry->old_body }}</p>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                @endforeach
            </div>
            @else
            <div style="text-align:center;padding:32px 0;color:#9CA3AF;">
                <i class="fa fa-timeline" style="font-size:24px;margin-bottom:10px;display:block;color:#E5E7EB;"></i>
                <p style="font-size:13px;margin:0;">No activity yet.</p>
            </div>
            @endif

            {{-- Submission file preview modal --}}
            <template x-teleport="body">
                <div x-show="open" x-cloak
                     @keydown.escape.window="close()"
                     style="position:fixed;inset:0;z-index:9999;">
                    <div @click.self="close()"
                         style="width:100%;height:100%;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;padding:16px;">
                    <div x-transition
                         style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:min(90vw,900px);overflow:hidden;">
                        <template x-if="sub">
                        <div>
                            <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fa fa-paperclip" style="color:#6366F1;font-size:16px;"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="sub.name"></p>
                                    <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;" x-text="'Version ' + sub.version"></p>
                                </div>
                                <button @click="close()" style="width:32px;height:32px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fa fa-xmark" style="color:#6B7280;font-size:13px;"></i>
                                </button>
                            </div>
                            <template x-if="sub.isImage">
                                <div style="padding:16px 24px;border-bottom:1px solid #F3F4F6;background:#F9FAFB;display:flex;justify-content:center;">
                                    <img :src="sub.url" :alt="sub.name" style="max-width:100%;max-height:75vh;border-radius:10px;object-fit:contain;display:block;">
                                </div>
                            </template>
                            <div style="padding:16px 24px;display:flex;gap:10px;justify-content:flex-end;">
                                <button @click="close()"
                                        style="padding:9px 18px;background:#F3F4F6;color:#6B7280;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                                    Close
                                </button>
                                <a :href="sub.downloadUrl"
                                   style="display:inline-flex;align-items:center;gap:6px;padding:9px 20px;background:#6366F1;color:#fff;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;transition:background .15s;"
                                   onmouseover="this.style.background='#4F46E5'" onmouseout="this.style.background='#6366F1'">
                                    <i class="fa fa-download" style="font-size:11px;"></i> Download
                                </a>
                            </div>
                        </div>
                        </template>
                    </div>
                    </div>
                </div>
            </template>

        </div>

    </div>{{-- /left --}}

    {{-- Right sidebar --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Reassign Task --}}
        @php $isClosed = in_array($task->status, ['approved','delivered','archived']); @endphp
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:#374151;margin:0 0 12px;text-transform:uppercase;letter-spacing:.04em;">Reassign Task</h3>

            @if($isClosed)
            {{-- Locked: task must be reopened first --}}
            <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:12px 14px;margin-bottom:12px;display:flex;align-items:flex-start;gap:10px;">
                <i class="fa fa-lock" style="color:#EA580C;font-size:14px;margin-top:1px;flex-shrink:0;"></i>
                <p style="font-size:12px;color:#9A3412;margin:0;line-height:1.5;">This task is <strong>closed</strong>. Reopen it before reassigning to another team member.</p>
            </div>
            <form method="POST" action="{{ route('admin.tasks.reopen', $task) }}"
                  onsubmit="return confirm('Reopen this task so it can be reassigned?')">
                @csrf
                <button type="submit"
                        style="width:100%;background:linear-gradient(135deg,#EA580C,#F97316);color:#fff;border:none;padding:9px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 8px rgba(234,88,12,.25);">
                    <i class="fa fa-rotate-right"></i> Reopen Task
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('admin.tasks.reassign', $task) }}">
                @csrf
                @php
                    $projectMemberIds = $task->project->members->pluck('id')->toArray();
                    $projectMembers   = $users->whereIn('id', $projectMemberIds);
                    $otherUsers       = $users->whereNotIn('id', $projectMemberIds);
                @endphp
                <select name="assigned_to" required
                        style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;outline:none;margin-bottom:8px;">
                    @if($projectMembers->isNotEmpty())
                    <optgroup label="— Project Members ({{ $task->project->name }}) —">
                        @foreach($projectMembers as $u)
                        <option value="{{ $u->id }}" {{ $u->id == $task->assigned_to ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </optgroup>
                    @endif
                    @if($otherUsers->isNotEmpty())
                    <optgroup label="— Other Users —">
                        @foreach($otherUsers as $u)
                        <option value="{{ $u->id }}" {{ $u->id == $task->assigned_to ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </optgroup>
                    @endif
                </select>
                <textarea name="reason" rows="2" placeholder="Reason for reassignment (optional)"
                          style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:12px;color:#111827;background:#fff;outline:none;resize:none;margin-bottom:8px;font-family:inherit;"></textarea>
                <button type="submit"
                        style="width:100%;background:#F3F4F6;color:#374151;border:none;padding:9px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                    <i class="fa fa-arrows-rotate" style="margin-right:5px;"></i> Reassign
                </button>
            </form>
            @endif
        </div>

        {{-- Change Deadline --}}
        @php $deadlineLocked = $isClosed || ($task->project && $task->project->status === 'completed'); @endphp
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:#374151;margin:0 0 12px;text-transform:uppercase;letter-spacing:.04em;">
                <i class="fa fa-calendar-pen" style="color:#DC2626;margin-right:5px;"></i>Change Deadline
            </h3>
            @if($deadlineLocked)
            <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:12px 14px;display:flex;align-items:flex-start;gap:10px;">
                <i class="fa fa-lock" style="color:#EA580C;font-size:14px;margin-top:1px;flex-shrink:0;"></i>
                <p style="font-size:12px;color:#9A3412;margin:0;line-height:1.5;">
                    @if($isClosed)
                        This task is <strong>closed</strong>. Reopen it to change the deadline.
                    @else
                        The project is <strong>completed</strong>. Reopen the project to change task deadlines.
                    @endif
                </p>
            </div>
            @else
            <form method="POST" action="{{ route('admin.tasks.deadline', $task) }}">
                @csrf @method('PATCH')
                <input type="date" name="deadline" value="{{ $task->deadline->format('Y-m-d') }}" required
                       style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;outline:none;margin-bottom:8px;box-sizing:border-box;">
                <input type="text" name="reason" placeholder="Reason (optional)"
                       style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;outline:none;margin-bottom:8px;box-sizing:border-box;font-family:inherit;">
                <button type="submit"
                        style="width:100%;background:#FEE2E2;color:#DC2626;border:none;padding:9px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                    <i class="fa fa-calendar-pen" style="margin-right:5px;"></i> Update Deadline
                </button>
            </form>
            @endif
        </div>

        {{-- Quick Info --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:.04em;">Quick Info</h3>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-folder" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Project</span>
                    <a href="{{ route('admin.projects.show', $task->project_id) }}" style="font-size:13px;font-weight:600;color:#6366F1;text-decoration:none;">{{ Str::limit($task->project->name, 18) }}</a>
                </div>
                @php $resolvedCustomer = $task->customer ?? $task->project?->customer; @endphp
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-building" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Customer</span>
                    @if($resolvedCustomer)
                        <a href="{{ route('admin.customers.show', $resolvedCustomer->id) }}" style="font-size:13px;font-weight:600;color:#6366F1;text-decoration:none;">{{ Str::limit($resolvedCustomer->name, 18) }}</a>
                    @else
                        <span style="font-size:13px;color:#9CA3AF;">—</span>
                    @endif
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

        {{-- Close Task --}}
        @if(!in_array($task->status, ['delivered','archived']))
        <form method="POST" action="{{ route('admin.tasks.forceClose', $task) }}"
              onsubmit="return confirm('Close this task and mark it as Delivered? This will notify the assignee.')">
            @csrf
            <button type="submit"
                    style="width:100%;background:linear-gradient(135deg,#059669,#10B981);color:#fff;border:none;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 8px rgba(5,150,105,.25);transition:opacity .15s;"
                    onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                <i class="fa fa-circle-check"></i> Close Task
            </button>
        </form>
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

        {{-- Move to Recycle Bin --}}
        <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}"
              onsubmit="return confirm('Move this task to the Recycle Bin? You can restore it later.')">
            @csrf @method('DELETE')
            <button type="submit"
                    style="width:100%;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:background .15s;"
                    onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                <i class="fa fa-trash-can"></i> Move to Recycle Bin
            </button>
        </form>

    </div>{{-- /right --}}

</div>{{-- /grid --}}

</div>{{-- /x-data taskApprovalPage --}}

<script>
function taskApprovalPage() {
    return {
        approvalModal:      false,
        approvalTask:       null,
        approvalNote:       '',
        approvalSocial:     null,
        approvalSocialUser: '',

        openApprovalModal(task) {
            this.approvalTask       = task;
            this.approvalNote       = '';
            this.approvalSocial     = null;
            this.approvalSocialUser = '';
            this.approvalModal      = true;
        },

        rejectModal: false,
        rejectTask:  null,
        rejectNote:  '',

        openRejectModal(task) {
            this.rejectTask  = task;
            this.rejectNote  = '';
            this.rejectModal = true;
        },
    };
}
</script>
@endsection

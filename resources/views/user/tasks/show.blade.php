@extends('layouts.app')
@section('title', $task->title)

@section('content')
@php
    $doneStatuses = ['approved', 'delivered', 'archived'];
    $isOverdue    = $task->deadline->isPast() && !in_array($task->status, $doneStatuses);

    $statusMap = [
        'draft'              => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Draft'],
        'assigned'           => ['bg'=>'#E0F2FE','color'=>'#0284C7','label'=>'Assigned'],
        'viewed'             => ['bg'=>'#EEF2FF','color'=>'#4F46E5','label'=>'Viewed'],
        'in_progress'        => ['bg'=>'#FEF3C7','color'=>'#D97706','label'=>'In Progress'],
        'submitted'          => ['bg'=>'#EDE9FE','color'=>'#7C3AED','label'=>'Submitted for Review'],
        'revision_requested' => ['bg'=>'#FEE2E2','color'=>'#DC2626','label'=>'Revision Requested'],
        'approved'           => ['bg'=>'#D1FAE5','color'=>'#059669','label'=>'Approved'],
        'delivered'          => ['bg'=>'#ECFDF5','color'=>'#047857','label'=>'Delivered'],
        'archived'           => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Archived'],
    ];

    $priorityMap = ['low'=>['bg'=>'#D1FAE5','color'=>'#059669'],'medium'=>['bg'=>'#FEF3C7','color'=>'#D97706'],'high'=>['bg'=>'#FEE2E2','color'=>'#DC2626']];
    $s = $statusMap[$task->status] ?? $statusMap['assigned'];
    $p = $priorityMap[$task->priority] ?? $priorityMap['medium'];

    $latestSubmission = $task->submissions->first();
    $canSubmit = in_array($task->status, ['in_progress', 'revision_requested']);
    $canStartWork = in_array($task->status, ['viewed', 'revision_requested']);

    // Workflow step index (for stepper)
    $stepOrder = ['draft'=>0,'assigned'=>1,'viewed'=>2,'in_progress'=>3,'submitted'=>4,'approved'=>5,'delivered'=>6];
    $currentStep = $stepOrder[$task->status] ?? ($task->status === 'revision_requested' ? 4 : 0);
    $steps = [
        ['key'=>'assigned',    'label'=>'Assigned'],
        ['key'=>'viewed',      'label'=>'Viewed'],
        ['key'=>'in_progress', 'label'=>'In Progress'],
        ['key'=>'submitted',   'label'=>'Submitted'],
        ['key'=>'approved',    'label'=>'Approved'],
        ['key'=>'delivered',   'label'=>'Delivered'],
    ];

    // Append social media steps when required
    $hasSocial = $task->social_required === true;
    if ($hasSocial) {
        $steps[] = [
            'key'         => 'social_assigned',
            'label'       => 'Social Assigned',
            'socialState' => $task->social_assigned_to
                ? ($task->social_posted_at ? 'done' : 'active')
                : 'pending',
        ];
        $steps[] = [
            'key'         => 'social_posted',
            'label'       => 'Social Posted',
            'socialState' => $task->social_posted_at ? 'done' : 'pending',
        ];
    }
@endphp

{{-- Header --}}
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <a href="{{ route('user.tasks.index') }}"
       style="width:36px;height:36px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;flex-shrink:0;">
        <i class="fa fa-arrow-left" style="font-size:13px;"></i>
    </a>
    <div style="flex:1;min-width:0;">
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $task->title }}</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:2px 0 0;"><i class="fa fa-folder-open" style="margin-right:4px;"></i>{{ $task->project->name }}</p>
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
        <span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $p['bg'] }};color:{{ $p['color'] }};">{{ ucfirst($task->priority) }}</span>
        <span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $s['bg'] }};color:{{ $s['color'] }};">{{ $s['label'] }}</span>
        @if($isOverdue)<span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#FEE2E2;color:#DC2626;"><i class="fa fa-clock" style="margin-right:3px;"></i>Overdue</span>@endif
    </div>
</div>

{{-- Workflow Stepper --}}
<div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;margin-bottom:20px;overflow-x:auto;">
    <div style="display:flex;align-items:center;min-width:{{ $hasSocial ? '760px' : '560px' }};">
        @foreach($steps as $idx => $step)
        @php
            $isSocial = isset($step['socialState']);
            if ($isSocial) {
                $socialState = $step['socialState'];
                $done        = $socialState === 'done';
                $active      = $socialState === 'active';
                $isRev       = false;
                $dotBg       = $done ? '#059669' : ($active ? '#D97706' : '#E5E7EB');
                $dotText     = ($done || $active) ? '#fff' : '#9CA3AF';
                $labelColor  = $done ? '#059669' : ($active ? '#D97706' : '#9CA3AF');
            } else {
                $done    = $currentStep > $idx;
                $active  = $currentStep === $idx;
                $isRev   = $task->status === 'revision_requested' && $step['key'] === 'submitted';
                $dotBg   = $done ? '#6366F1' : ($active ? '#6366F1' : ($isRev ? '#DC2626' : '#E5E7EB'));
                $dotText = $done ? '#fff' : ($active ? '#fff' : ($isRev ? '#fff' : '#9CA3AF'));
                $labelColor = $active ? '#111827' : ($done ? '#6366F1' : ($isRev ? '#DC2626' : '#9CA3AF'));
            }
        @endphp
        <div style="display:flex;flex-direction:column;align-items:center;flex:1;">
            <div style="width:28px;height:28px;border-radius:50%;background:{{ $dotBg }};display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:{{ $dotText }};position:relative;z-index:1;flex-shrink:0;">
                @if($isSocial)
                    @if($done)
                        <i class="fa fa-check" style="font-size:10px;"></i>
                    @elseif($active)
                        <i class="fa fa-share-nodes" style="font-size:10px;"></i>
                    @else
                        {{ $idx + 1 }}
                    @endif
                @elseif($done)
                    <i class="fa fa-check" style="font-size:10px;"></i>
                @elseif($isRev)
                    <i class="fa fa-rotate-left" style="font-size:10px;"></i>
                @else
                    {{ $idx + 1 }}
                @endif
            </div>
            <p style="font-size:10px;font-weight:{{ $active ? '700' : '500' }};color:{{ $labelColor }};margin:4px 0 0;text-align:center;white-space:nowrap;">
                @if($isRev) Revision @else {{ $step['label'] }} @endif
            </p>
        </div>
        @if(!$loop->last)
        @php
            $nextStep     = $steps[$idx + 1] ?? null;
            $nextIsSocial = isset($nextStep['socialState']);
            if ($isSocial) {
                $connectorColor = ($step['socialState'] === 'done') ? '#059669' : '#E5E7EB';
            } elseif ($nextIsSocial) {
                $connectorColor = ($currentStep > $idx) ? '#059669' : '#E5E7EB';
            } else {
                $connectorColor = $currentStep > $idx ? '#6366F1' : '#E5E7EB';
            }
        @endphp
        <div style="flex:1;height:2px;background:{{ $connectorColor }};margin-bottom:14px;"></div>
        @endif
        @endforeach
    </div>
    @if($task->status === 'revision_requested')
    <p style="font-size:11px;color:#DC2626;text-align:center;margin:10px 0 0;font-weight:600;">
        <i class="fa fa-rotate-left" style="margin-right:4px;"></i>Revision requested — please review feedback and resubmit
    </p>
    @endif
</div>

{{-- Transfer provenance banner --}}
@if(isset($incomingTransfer) && $incomingTransfer)
<div style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;gap:14px;align-items:flex-start;">
    <div style="width:36px;height:36px;border-radius:10px;background:#E0E7FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
        <i class="fa fa-arrow-right-arrow-left" style="color:#4F46E5;font-size:13px;"></i>
    </div>
    <div>
        <p style="font-size:13px;font-weight:700;color:#3730A3;margin:0 0 3px;">This task was transferred to you</p>
        <p style="font-size:12px;color:#4F46E5;margin:0;line-height:1.6;">
            Previously assigned to <strong>{{ $incomingTransfer->fromUser?->name ?? 'a former employee' }}</strong>
            · Transferred by <strong>{{ $incomingTransfer->transferredBy?->name ?? 'an admin' }}</strong>
            on <strong>{{ $incomingTransfer->transferred_at->format('M d, Y') }}</strong>
        </p>
        <p style="font-size:11px;color:#6366F1;margin:5px 0 0;line-height:1.5;">
            <i class="fa fa-clock-rotate-left" style="margin-right:3px;"></i>
            The full previous history — comments, uploads, and review notes — is preserved below.
            Your productivity is tracked separately from the original assignee.
        </p>
    </div>
</div>
@endif

@if(session('success'))
<div style="background:#D1FAE5;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#065F46;font-size:14px;">
    <i class="fa fa-circle-check"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#FEE2E2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#991B1B;font-size:14px;">
    <i class="fa fa-circle-exclamation"></i> {{ session('error') }}
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    {{-- Left --}}
    <div style="display:flex;flex-direction:column;gap:20px;">

        {{-- Task details --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-circle-info" style="color:#6366F1;"></i> Task Details
            </h2>
            @if($task->description)
            <p style="font-size:14px;color:#6B7280;line-height:1.7;margin:0 0 16px;padding-bottom:16px;border-bottom:1px solid #F3F4F6;">{{ $task->description }}</p>
            @endif

            {{-- Tags --}}
            @if($task->tags)
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #F3F4F6;">
                @if($task->task_type)
                <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#F3F4F6;color:#374151;"><i class="fa fa-tag" style="margin-right:3px;color:#9CA3AF;"></i>{{ $task->task_type }}</span>
                @endif
                @foreach($task->tags as $tag)
                <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#EEF2FF;color:#4F46E5;">#{{ $tag }}</span>
                @endforeach
            </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="background:#FAFAFA;border-radius:10px;padding:12px;">
                    <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 4px;">Project</p>
                    <p style="font-size:14px;font-weight:600;color:#111827;margin:0;">{{ $task->project->name }}</p>
                </div>
                <div style="background:#FAFAFA;border-radius:10px;padding:12px;">
                    <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 4px;">Deadline</p>
                    <p style="font-size:14px;font-weight:600;color:{{ $isOverdue ? '#DC2626' : '#111827' }};margin:0;">
                        {{ $task->deadline->format('M d, Y') }}
                        <span style="font-size:11px;font-weight:400;color:{{ $isOverdue ? '#DC2626' : '#9CA3AF' }};"> — {{ $task->deadline->diffForHumans() }}</span>
                    </p>
                </div>
                @if($task->reviewer)
                <div style="background:#FAFAFA;border-radius:10px;padding:12px;">
                    <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 4px;">Reviewer</p>
                    <p style="font-size:14px;font-weight:600;color:#111827;margin:0;">{{ $task->reviewer->name }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Status Banner --}}
        @if($task->status === 'submitted')
        <div style="background:#F5F3FF;border:1px solid #DDD6FE;border-radius:14px;padding:20px;display:flex;align-items:flex-start;gap:16px;">
            <div style="width:44px;height:44px;border-radius:50%;background:#EDE9FE;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa fa-hourglass-half" style="color:#7C3AED;font-size:18px;"></i>
            </div>
            <div>
                <p style="font-size:14px;font-weight:700;color:#4C1D95;margin:0 0 4px;">Awaiting Admin Review</p>
                <p style="font-size:13px;color:#6D28D9;margin:0;">Your submission (v{{ $task->submissions->first()?->version ?? 1 }}) is being reviewed.</p>
                @if($latestSubmission?->admin_note)
                <p style="font-size:12px;color:#7C3AED;background:#EDE9FE;padding:8px 12px;border-radius:8px;margin:8px 0 0;font-style:italic;">"{{ $latestSubmission->admin_note }}"</p>
                @endif
            </div>
        </div>

        @elseif($task->status === 'revision_requested')
        <div style="background:#FEF2F2;border:1.5px solid #FECACA;border-radius:14px;padding:20px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <div style="width:44px;height:44px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa fa-rotate-left" style="color:#DC2626;font-size:18px;"></i>
                </div>
                <div>
                    <p style="font-size:14px;font-weight:700;color:#991B1B;margin:0;">Revision Requested</p>
                    <p style="font-size:13px;color:#B91C1C;margin:2px 0 0;">Please review the feedback and resubmit your work.</p>
                </div>
            </div>
            @if($latestSubmission?->admin_note)
            <div style="background:#fff;border-radius:10px;padding:12px 14px;border-left:3px solid #DC2626;margin-bottom:14px;">
                <p style="font-size:11px;font-weight:700;color:#DC2626;margin:0 0 4px;text-transform:uppercase;letter-spacing:.04em;">Admin Feedback</p>
                <p style="font-size:13px;color:#374151;margin:0;line-height:1.6;">{{ $latestSubmission->admin_note }}</p>
            </div>
            @endif
            <form method="POST" action="{{ route('user.tasks.updateStatus', $task) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="in_progress">
                <input type="hidden" name="note" value="Acknowledged revision request — resuming work.">
                <button type="submit"
                        style="background:#DC2626;color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
                    <i class="fa fa-play"></i> Acknowledge & Resume Work
                </button>
            </form>
        </div>

        @elseif($task->status === 'approved')
        <div style="background:#F0FDF4;border:1px solid #A7F3D0;border-radius:14px;padding:20px;display:flex;align-items:center;gap:14px;">
            <div style="width:44px;height:44px;border-radius:50%;background:#D1FAE5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa fa-circle-check" style="color:#059669;font-size:20px;"></i>
            </div>
            <div>
                <p style="font-size:14px;font-weight:700;color:#065F46;margin:0 0 2px;">Approved!</p>
                @if($latestSubmission?->admin_note)
                <p style="font-size:13px;color:#047857;margin:0;">Admin note: {{ $latestSubmission->admin_note }}</p>
                @else
                <p style="font-size:13px;color:#047857;margin:0;">Your submission was approved. Waiting for final delivery.</p>
                @endif
            </div>
        </div>

        @elseif($task->status === 'delivered')
        <div style="background:#ECFDF5;border:1px solid #6EE7B7;border-radius:14px;padding:20px;display:flex;align-items:center;gap:14px;">
            <div style="width:44px;height:44px;border-radius:50%;background:#D1FAE5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa fa-truck" style="color:#047857;font-size:18px;"></i>
            </div>
            <div>
                <p style="font-size:14px;font-weight:700;color:#065F46;margin:0 0 2px;">Work Delivered!</p>
                <p style="font-size:13px;color:#047857;margin:0;">Your completed work has been delivered to the client.</p>
            </div>
        </div>

        @elseif($canStartWork)
        {{-- Employee: start work button --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 12px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-pen-to-square" style="color:#6366F1;"></i> Update Status
            </h2>
            <form method="POST" action="{{ route('user.tasks.updateStatus', $task) }}" style="display:flex;gap:10px;align-items:flex-end;">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="in_progress">
                <div style="flex:1;">
                    <label style="font-size:12px;font-weight:600;color:#6B7280;display:block;margin-bottom:6px;">Optional note</label>
                    <input type="text" name="note" placeholder="e.g. Starting work on this now..."
                           style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;">
                </div>
                <button type="submit"
                        style="background:#6366F1;color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:6px;">
                    <i class="fa fa-play"></i> Start Working
                </button>
            </form>
        </div>

        @elseif($task->status === 'in_progress')
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:14px;padding:16px 20px;display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;border-radius:50%;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa fa-circle-play" style="color:#D97706;font-size:16px;"></i>
            </div>
            <div>
                <p style="font-size:14px;font-weight:700;color:#92400E;margin:0;">Work in progress</p>
                <p style="font-size:12px;color:#B45309;margin:0;">Submit your work below when you're ready for review.</p>
            </div>
        </div>
        @endif

        {{-- Submit Work --}}
        @if($canSubmit && auth()->user()->hasPermission('submit_work'))
        <div style="background:#fff;border-radius:14px;border:1.5px solid #6366F1;box-shadow:0 4px 16px rgba(99,102,241,.08);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 4px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-upload" style="color:#6366F1;"></i>
                @if($task->submissions->count() > 0) Submit New Version @else Submit Your Work @endif
            </h2>
            <p style="font-size:12px;color:#9CA3AF;margin:0 0 16px;">
                @if($task->submissions->count() > 0)
                You're submitting version {{ $task->submissions->count() + 1 }}. Previous versions are kept.
                @else
                Upload a file and/or add a note explaining your work.
                @endif
            </p>
            <form method="POST" action="{{ route('user.tasks.submit', $task) }}" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom:14px;">
                    <label style="font-size:12px;font-weight:600;color:#6B7280;display:block;margin-bottom:6px;">Note / Description</label>
                    <textarea name="note" rows="3" placeholder="Describe what you've done, what's included in the file..."
                              style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;line-height:1.5;"
                              onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">{{ old('note') }}</textarea>
                    @error('note')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div style="margin-bottom:16px;" x-data="{ filename: '' }">
                    <label style="font-size:12px;font-weight:600;color:#6B7280;display:block;margin-bottom:6px;">
                        Attach File <span style="font-weight:400;color:#9CA3AF;">(optional, max 20MB)</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:12px;padding:12px 16px;border:1.5px dashed #D1D5DB;border-radius:10px;cursor:pointer;transition:border-color .15s;background:#FAFAFA;"
                           onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#D1D5DB'">
                        <i class="fas fa-paperclip" style="color:#9CA3AF;font-size:18px;"></i>
                        <div style="flex:1;">
                            <p x-text="filename || 'Click to choose a file'" style="font-size:13px;font-weight:500;color:#374151;margin:0;"></p>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">PDF, DOC, ZIP, images and more</p>
                        </div>
                        <input type="file" name="file" @change="filename = $event.target.files[0]?.name || ''" style="display:none;">
                    </label>
                    @error('file')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                        style="width:100%;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(99,102,241,.3);">
                    <i class="fas fa-paper-plane" style="margin-right:6px;"></i> Submit for Review
                </button>
            </form>
        </div>
        @endif

        {{-- Submission history --}}
        @if($task->submissions->count() && auth()->user()->hasPermission('view_version_history'))
        @php
            $subColorMap = [
                'submitted' => ['#EEF2FF','#4F46E5','fa-hourglass-half','In Review'],
                'approved'  => ['#D1FAE5','#059669','fa-circle-check','Approved'],
                'rejected'  => ['#FEE2E2','#DC2626','fa-rotate-left','Revision Requested'],
            ];
            $subsJson = $task->submissions->map(function($sub) use ($subColorMap) {
                [$sbg,$sco,$sico,$slbl] = $subColorMap[$sub->status] ?? $subColorMap['submitted'];
                return [
                    'version'       => $sub->version,
                    'statusLabel'   => $slbl,
                    'statusBg'      => $sbg,
                    'statusColor'   => $sco,
                    'icon'          => $sico,
                    'time'          => $sub->created_at->format('M d, Y · H:i'),
                    'timeAgo'       => $sub->created_at->diffForHumans(),
                    'note'          => $sub->note,
                    'fileUrl'       => $sub->file_path ? $sub->fileUrl() : null,
                    'fileName'      => $sub->original_filename ?? 'Download file',
                    'adminNote'     => $sub->admin_note,
                    'adminName'     => $sub->reviewer?->name ?? 'Admin',
                    'adminApproved' => $sub->status === 'approved',
                ];
            })->values();
        @endphp
        <script>
        function versionHistoryData() {
            return {
                subs: {!! json_encode($subsJson) !!},
                open: false,
                sel: null,
                show(i) { this.sel = this.subs[i]; this.open = true; },
                close() { this.open = false; this.sel = null; }
            };
        }
        </script>
        <div x-data="versionHistoryData()"
             style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-clock-rotate-left" style="color:#6366F1;"></i> Version History
                <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9CA3AF;">{{ $task->submissions->count() }} {{ Str::plural('version', $task->submissions->count()) }}</span>
            </h2>

            @foreach($task->submissions as $i => $sub)
            @php [$sbg2,$sco2,$sico,$slbl2] = $subColorMap[$sub->status] ?? $subColorMap['submitted']; @endphp
            <div @click="show({{ $i }})"
                 style="display:flex;align-items:center;gap:14px;padding:12px;border-radius:10px;cursor:pointer;transition:background .15s;border:1px solid transparent;margin-bottom:6px;"
                 @mouseenter="$el.style.background='#F9FAFB';$el.style.borderColor='#E5E7EB'"
                 @mouseleave="$el.style.background='transparent';$el.style.borderColor='transparent'">
                <div style="width:38px;height:38px;border-radius:50%;background:{{ $sbg2 }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa {{ $sico }}" style="color:{{ $sco2 }};font-size:14px;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:13px;font-weight:700;color:#111827;">Version {{ $sub->version }}</span>
                        <span style="font-size:11px;font-weight:600;padding:2px 9px;border-radius:10px;background:{{ $sbg2 }};color:{{ $sco2 }};">{{ $slbl2 }}</span>
                    </div>
                    <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $sub->created_at->diffForHumans() }}
                        @if($sub->file_path) &middot; <i class="fa fa-paperclip"></i> Attachment @endif
                        @if($sub->admin_note) &middot; <i class="fa fa-comment-dots"></i> Admin feedback @endif
                    </p>
                </div>
                <i class="fa fa-chevron-right" style="color:#D1D5DB;font-size:11px;flex-shrink:0;"></i>
            </div>
            @endforeach

            {{-- Version detail modal --}}
            <template x-teleport="body">
                {{-- Outer: position:fixed overlay. x-show only toggles display on this div,
                     so we keep display:flex on the INNER centering wrapper, not here. --}}
                <div x-show="open" x-cloak
                     @keydown.escape.window="close()"
                     style="position:fixed;inset:0;z-index:9999;">
                    {{-- Backdrop + centering wrapper (always display:flex once outer is visible) --}}
                    <div @click.self="close()"
                         style="width:100%;height:100%;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;padding:16px;">
                        <div x-transition
                             style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:460px;overflow:hidden;">
                            <template x-if="sel">
                            <div>
                                {{-- Modal header --}}
                                <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:12px;">
                                    <div :style="'width:40px;height:40px;border-radius:50%;background:'+sel.statusBg+';display:flex;align-items:center;justify-content:center;flex-shrink:0;'">
                                        <i :class="'fa '+sel.icon" :style="'color:'+sel.statusColor+';font-size:15px;'"></i>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <span style="font-size:15px;font-weight:700;color:#111827;" x-text="'Version '+sel.version"></span>
                                            <span :style="'font-size:11px;font-weight:600;padding:2px 9px;border-radius:10px;background:'+sel.statusBg+';color:'+sel.statusColor+';'" x-text="sel.statusLabel"></span>
                                        </div>
                                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;" x-text="sel.time"></p>
                                    </div>
                                    <button @click="close()" style="width:32px;height:32px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fa fa-xmark" style="color:#6B7280;font-size:13px;"></i>
                                    </button>
                                </div>
                                {{-- Modal body --}}
                                <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px;max-height:60vh;overflow-y:auto;">
                                    {{-- Submission note --}}
                                    <template x-if="sel.note">
                                    <div>
                                        <p style="font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;margin:0 0 6px;">Submission Note</p>
                                        <p style="font-size:13px;color:#374151;margin:0;line-height:1.6;background:#F9FAFB;padding:10px 14px;border-radius:10px;" x-text="sel.note"></p>
                                    </div>
                                    </template>
                                    {{-- File attachment --}}
                                    <template x-if="sel.fileUrl">
                                    <div>
                                        <p style="font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;margin:0 0 6px;">Attachment</p>
                                        <a :href="sel.fileUrl" target="_blank"
                                           style="display:inline-flex;align-items:center;gap:8px;font-size:13px;color:#6366F1;text-decoration:none;background:#EEF2FF;padding:8px 14px;border-radius:10px;font-weight:500;">
                                            <i class="fa fa-paperclip"></i>
                                            <span x-text="sel.fileName"></span>
                                            <i class="fa fa-arrow-up-right-from-square" style="font-size:10px;opacity:.7;"></i>
                                        </a>
                                    </div>
                                    </template>
                                    {{-- Admin feedback --}}
                                    <template x-if="sel.adminNote">
                                    <div>
                                        <p style="font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;margin:0 0 6px;">Admin Feedback</p>
                                        <div :style="'background:'+(sel.adminApproved?'#F0FDF4':'#FEF2F2')+';border-radius:10px;padding:12px 14px;border-left:3px solid '+(sel.adminApproved?'#10B981':'#EF4444')+';'">
                                            <p :style="'font-size:11px;font-weight:600;color:'+(sel.adminApproved?'#065F46':'#991B1B')+';margin:0 0 4px;'" x-text="sel.adminName"></p>
                                            <p :style="'font-size:13px;color:'+(sel.adminApproved?'#047857':'#B91C1C')+';margin:0;line-height:1.5;'" x-text="sel.adminNote"></p>
                                        </div>
                                    </div>
                                    </template>
                                    {{-- Fallback --}}
                                    <template x-if="!sel.note && !sel.fileUrl && !sel.adminNote">
                                    <p style="font-size:13px;color:#9CA3AF;text-align:center;padding:10px 0;">No additional details for this version.</p>
                                    </template>
                                </div>
                            </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        @endif

        {{-- Activity log --}}
        @if(auth()->user()->hasPermission('view_activity_log'))
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
            <div style="display:flex;gap:12px;padding-bottom:16px;margin-bottom:16px;border-bottom:1px solid #F9FAFB;">
                <div style="flex-shrink:0;">
                    <div style="width:32px;height:32px;border-radius:50%;background:{{ $abg }};display:flex;align-items:center;justify-content:center;">
                        <i class="fa {{ $aico }}" style="color:{{ $aco }};font-size:12px;"></i>
                    </div>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
                        <span style="font-size:12px;font-weight:600;color:#111827;">{{ $log->user?->name ?? 'System' }}</span>
                        <span style="font-size:11px;font-weight:600;padding:1px 7px;border-radius:8px;background:{{ $abg }};color:{{ $aco }};">{{ $log->actionLabel() }}</span>
                        <span style="font-size:11px;color:#9CA3AF;margin-left:auto;">{{ $log->created_at->format('M d, H:i') }}</span>
                    </div>
                    @if(in_array($log->action, ['task_reassigned','task_transferred']) && isset($meta['from_user_name'], $meta['to_user_name']))
                    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:4px;">
                        <span style="font-size:11px;background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;">
                            <span style="text-decoration:line-through;opacity:.7;">{{ $meta['from_user_name'] }}</span>
                            <i class="fa fa-arrow-right" style="font-size:9px;"></i>
                            <strong>{{ $meta['to_user_name'] }}</strong>
                        </span>
                        @if(!empty($meta['reassigned_by'] ?? $meta['performed_by'] ?? null))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">by {{ $meta['reassigned_by'] ?? $meta['performed_by'] }}</span>
                        @endif
                    </div>
                    @elseif(isset($meta['old_status'], $meta['new_status']))
                    <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">
                        {{ str_replace('_',' ',$meta['old_status']) }} → <strong>{{ str_replace('_',' ',$meta['new_status']) }}</strong>
                    </span>
                    @endif
                    @if(isset($meta['rejection_reason']))
                    <p style="font-size:12px;color:#DC2626;background:#FEF2F2;padding:6px 10px;border-radius:8px;border-left:3px solid #EF4444;margin:5px 0 0;">"{{ $meta['rejection_reason'] }}"</p>
                    @endif
                    @if($log->note && !in_array($log->action, ['comment_added','task_created','first_viewed','task_reassigned','task_transferred']))
                    <p style="font-size:12px;color:#6B7280;background:#F9FAFB;padding:6px 10px;border-radius:8px;border-left:3px solid #E5E7EB;margin:5px 0 0;">"{{ $log->note }}"</p>
                    @endif
                </div>
            </div>
            @empty
            <p style="font-size:14px;color:#9CA3AF;text-align:center;padding:24px 0;margin:0;">No activity recorded yet.</p>
            @endforelse
        </div>
        @endif {{-- view_activity_log --}}

        {{-- Comments --}}
        @if(auth()->user()->hasPermission('view_comments'))
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-comments" style="color:#6366F1;"></i> Comments & Updates
                <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9CA3AF;">{{ $task->comments->count() }}</span>
            </h2>
            <form method="POST" action="{{ route('user.tasks.comment', $task) }}" style="margin-bottom:20px;">
                @csrf
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div style="flex:1;">
                        <textarea name="body" rows="2" required placeholder="Ask a question or post an update..."
                                  style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;line-height:1.5;"
                                  onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">{{ old('body') }}</textarea>
                        @error('body')<p style="font-size:11px;color:#DC2626;margin:3px 0 0;">{{ $message }}</p>@enderror
                        <button type="submit"
                                style="margin-top:8px;background:#6366F1;color:#fff;border:none;padding:8px 18px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;">
                            <i class="fa fa-paper-plane"></i> Post Comment
                        </button>
                    </div>
                </div>
            </form>
            @forelse($task->comments as $comment)
            @php $isAdmin = in_array($comment->user->role ?? 'user', ['admin','manager']); @endphp
            <div style="display:flex;gap:10px;padding-bottom:16px;margin-bottom:16px;border-bottom:1px solid #F9FAFB;">
                <div style="width:34px;height:34px;border-radius:50%;background:{{ $isAdmin ? 'linear-gradient(135deg,#6366F1,#8B5CF6)' : 'linear-gradient(135deg,#10B981,#059669)' }};display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">
                    {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap;">
                        <span style="font-size:13px;font-weight:600;color:#111827;">{{ $comment->user->name ?? 'Unknown' }}</span>
                        @if($isAdmin)<span style="font-size:10px;font-weight:700;padding:1px 7px;border-radius:10px;background:#EEF2FF;color:#4F46E5;">Admin</span>@endif
                        <span style="font-size:11px;color:#9CA3AF;">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <p style="font-size:13px;color:#374151;margin:0;line-height:1.6;">{{ $comment->body }}</p>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:24px;color:#9CA3AF;">
                <i class="fa fa-comment-slash" style="font-size:22px;margin-bottom:8px;display:block;color:#E5E7EB;"></i>
                <p style="font-size:13px;margin:0;">No comments yet.</p>
            </div>
            @endforelse
        </div>

        @endif {{-- view_comments --}}

    </div>{{-- /left --}}

    {{-- Right sidebar --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Quick info --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:.04em;">Quick Info</h3>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-folder" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Project</span>
                    <span style="font-size:13px;font-weight:600;color:#111827;">{{ Str::limit($task->project->name,18) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-calendar" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Deadline</span>
                    <span style="font-size:13px;font-weight:600;color:{{ $isOverdue ? '#DC2626' : '#111827' }};">{{ $task->deadline->format('M d, Y') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-flag" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Priority</span>
                    <span style="padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600;background:{{ $p['bg'] }};color:{{ $p['color'] }};">{{ ucfirst($task->priority) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-circle-half-stroke" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Status</span>
                    <span style="padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600;background:{{ $s['bg'] }};color:{{ $s['color'] }};">{{ $s['label'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-upload" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Submissions</span>
                    <span style="font-size:13px;font-weight:600;color:#111827;">{{ $task->submissions->count() }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-eye" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>First Viewed</span>
                    <span style="font-size:12px;color:#6B7280;">{{ $task->first_viewed_at ? $task->first_viewed_at->format('M d, H:i') : 'Just now' }}</span>
                </div>
            </div>
        </div>

        {{-- Time remaining --}}
        @php
            $tbg = '#EEF2FF'; $tbo = '#C7D2FE'; $tico = '#6366F1'; $ttitle = 'Due ' . $task->deadline->diffForHumans(); $tsub = $task->deadline->format('l, M d');
            if($task->status === 'delivered')      { $tbg='#ECFDF5';$tbo='#6EE7B7';$tico='#047857';$ttitle='Delivered!';$tsub='Work delivered to client.'; }
            elseif($task->status === 'approved')   { $tbg='#F0FDF4';$tbo='#A7F3D0';$tico='#059669';$ttitle='Approved!';$tsub='Awaiting delivery.'; }
            elseif($task->status === 'submitted')  { $tbg='#F5F3FF';$tbo='#DDD6FE';$tico='#7C3AED';$ttitle='Under Review';$tsub='Waiting for admin.'; }
            elseif($task->status === 'revision_requested') { $tbg='#FEF2F2';$tbo='#FECACA';$tico='#DC2626';$ttitle='Revision Needed';$tsub='Check admin feedback.'; }
            elseif($isOverdue) { $tbg='#FEF2F2';$tbo='#FECACA';$tico='#DC2626';$ttitle='Overdue';$tsub=$task->deadline->diffForHumans(); }
        @endphp
        <div style="background:{{ $tbg }};border:1px solid {{ $tbo }};border-radius:14px;padding:20px;text-align:center;">
            <i class="fa fa-clock" style="font-size:24px;color:{{ $tico }};margin-bottom:8px;display:block;"></i>
            <p style="font-size:14px;font-weight:700;color:#111827;margin:0 0 4px;">{{ $ttitle }}</p>
            <p style="font-size:12px;color:#6B7280;margin:0;">{{ $tsub }}</p>
        </div>

        {{-- Other tasks in same project --}}
        @php
            $siblingTasks = $task->project->tasks()
                ->where('id','!=',$task->id)
                ->where('assigned_to', auth()->id())
                ->orderByRaw("CASE WHEN status IN ('approved','delivered') THEN 1 ELSE 0 END")
                ->orderBy('deadline')
                ->take(4)->get();
        @endphp
        @if($siblingTasks->count())
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:.04em;">Other Tasks in Project</h3>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($siblingTasks as $sib)
                @php $sc = $statusMap[$sib->status] ?? $statusMap['assigned']; @endphp
                <a href="{{ route('user.tasks.show', $sib) }}"
                   style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;background:#FAFAFA;text-decoration:none;transition:background .15s;"
                   onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#FAFAFA'">
                    <div style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:{{ $sc['color'] }};"></div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:13px;font-weight:500;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $sib->title }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $sib->deadline->format('M d') }}</p>
                    </div>
                    <span style="font-size:11px;font-weight:500;padding:2px 8px;border-radius:10px;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};flex-shrink:0;">{{ $sc['label'] }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>{{-- /right --}}

</div>
@endsection

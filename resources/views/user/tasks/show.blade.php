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
    $canSubmit = in_array($task->status, ['viewed', 'in_progress', 'revision_requested']);

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

        {{-- Project Attachments --}}
        @if($task->project->attachments->isNotEmpty())
        @php
            $attachmentsJson = $task->project->attachments->map(fn($a) => [
                'name'    => $a->name,
                'size'    => $a->humanSize(),
                'url'     => $a->url(),
                'icon'    => $a->iconClass(),
                'isLink'  => $a->isLink(),
                'isImage' => in_array(strtolower(pathinfo($a->name, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif','webp','svg']),
            ])->values();
        @endphp
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
                    @php $item = ['name'=>$att->name,'size'=>$att->humanSize(),'url'=>$att->url(),'downloadUrl'=>$att->isFile()?route('user.attachments.download',$att):$att->url(),'icon'=>$att->iconClass(),'isLink'=>$att->isLink(),'isImage'=>in_array(strtolower(pathinfo($att->name,PATHINFO_EXTENSION)),['jpg','jpeg','png','gif','webp','svg'])]; @endphp
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
                            {{-- Header --}}
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

                            {{-- Image preview --}}
                            <template x-if="att.isImage">
                                <div style="padding:16px 24px;border-bottom:1px solid #F3F4F6;background:#F9FAFB;display:flex;justify-content:center;">
                                    <img :src="att.url" :alt="att.name" style="max-width:100%;max-height:75vh;border-radius:10px;object-fit:contain;display:block;">
                                </div>
                            </template>

                            {{-- Action footer --}}
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

        @elseif($task->status === 'viewed')
        <div style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:14px;padding:16px 20px;display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;border-radius:50%;background:#E0E7FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa fa-lightbulb" style="color:#6366F1;font-size:15px;"></i>
            </div>
            <div>
                <p style="font-size:14px;font-weight:700;color:#3730A3;margin:0;">Ready to begin?</p>
                <p style="font-size:12px;color:#6366F1;margin:0;opacity:.85;">Hit <strong>Start</strong> below to mark this task as in progress. A note or file is optional.</p>
            </div>
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

        {{-- Unified: Comment + Submit --}}
        @if($task->status !== 'revision_requested' && (auth()->user()->hasPermission('view_comments') || ($canSubmit && auth()->user()->hasPermission('submit_work'))))
        <div x-data="{ uFile: '', showModal: false, body: '{{ old('body') }}' }" style="background:#fff;border-radius:14px;border:1.5px solid #6366F1;box-shadow:0 4px 16px rgba(99,102,241,.08);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 4px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-comment" style="color:#6366F1;"></i>
                @if($task->status === 'viewed')
                    Start Working
                @elseif($canSubmit && auth()->user()->hasPermission('submit_work'))
                    Comment or Submit Work
                @else
                    Add a Comment
                @endif
            </h2>
            <p style="font-size:12px;color:#9CA3AF;margin:0 0 16px;">
                @if($task->status === 'viewed')
                    Click <strong style="color:#6366F1;">Start</strong> to begin — or add a note or file first if you'd like.
                @elseif($canSubmit && auth()->user()->hasPermission('submit_work'))
                    Post a comment to discuss, or attach your work and submit it for review.
                @else
                    Ask a question, share an update, or leave a note.
                @endif
            </p>
            <form method="POST" enctype="multipart/form-data">
                @csrf
                <textarea name="body" rows="3" x-model="body"
                          @if($task->status !== 'viewed') required @endif
                          placeholder="{{ $task->status === 'viewed' ? 'Optional — add a note before starting...' : (($canSubmit && auth()->user()->hasPermission('submit_work')) ? 'Describe your work or write a comment...' : 'Write your comment...') }}"
                          style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;line-height:1.5;margin-bottom:10px;"
                          onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">{{ old('body') }}</textarea>
                @error('body')<p style="font-size:11px;color:#DC2626;margin:-6px 0 8px;">{{ $message }}</p>@enderror
                <div style="margin-bottom:14px;">
                    <label style="display:flex;align-items:center;gap:12px;padding:12px 16px;border:1.5px dashed #D1D5DB;border-radius:10px;cursor:pointer;background:#FAFAFA;"
                           onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#D1D5DB'">
                        <i class="fa fa-paperclip" style="color:#9CA3AF;font-size:16px;"></i>
                        <div style="flex:1;">
                            <p x-text="uFile || 'Attach a file (optional)'" style="font-size:13px;font-weight:500;color:#374151;margin:0;"></p>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Images, PDF, ZIP and more · max 20MB</p>
                        </div>
                        <input type="file" name="file" @change="uFile = $event.target.files[0]?.name || ''" style="display:none;">
                    </label>
                    @error('file')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Hidden real submit targets --}}
                @if(auth()->user()->hasPermission('view_comments'))
                <button type="submit" x-ref="commentBtn" formaction="{{ route('user.tasks.comment', $task) }}" style="display:none;" aria-hidden="true"></button>
                @endif
                @if($canSubmit && auth()->user()->hasPermission('submit_work'))
                <button type="submit" x-ref="submitBtn" formaction="{{ route('user.tasks.submit', $task) }}" style="display:none;" aria-hidden="true"></button>
                @endif

                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button"
                            @click="@if($task->status === 'viewed')
                                uFile
                                    ? (showModal = true)
                                    : (body.trim()
                                        ? $refs.commentBtn.click()
                                        : document.getElementById('_startForm').submit())
                            @elseif($canSubmit && auth()->user()->hasPermission('submit_work'))
                                uFile ? (showModal = true) : $refs.commentBtn.click()
                            @else
                                $refs.commentBtn?.click()
                            @endif"
                            style="background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:10px 22px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:7px;box-shadow:0 4px 12px rgba(99,102,241,.3);">
                        @if($task->status === 'viewed')
                            <i class="fa fa-play"></i> Start
                        @else
                            <i class="fa fa-paper-plane"></i> Send
                        @endif
                    </button>
                </div>
            </form>

            {{-- Hidden start form (only when task is in viewed state) --}}
            @if($task->status === 'viewed')
            <form id="_startForm" method="POST" action="{{ route('user.tasks.updateStatus', $task) }}" style="display:none;">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="in_progress">
            </form>
            @endif

            @if($canSubmit && auth()->user()->hasPermission('submit_work'))
            {{-- Smart modal: only appears when a file is attached --}}
            <div x-show="showModal" x-transition
                 @click.self="showModal = false"
                 style="position:fixed;inset:0;background:rgba(17,24,39,.5);backdrop-filter:blur(3px);z-index:9999;">
                <div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;padding:20px;">
                    <div x-show="showModal" x-transition
                         style="background:#fff;border-radius:20px;padding:28px 24px;max-width:380px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.18);">
                        <div style="text-align:center;margin-bottom:22px;">
                            <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#EEF2FF,#C7D2FE);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                                <i class="fa fa-paper-plane" style="color:#6366F1;font-size:20px;"></i>
                            </div>
                            <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">What are you sending?</h3>
                            <p style="font-size:12px;color:#9CA3AF;margin:0;line-height:1.5;">You attached a file — is this a comment with an attachment, or your work deliverable?</p>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <button type="button" @click="showModal=false; $refs.commentBtn.click()"
                                    style="display:flex;align-items:center;gap:14px;padding:14px 16px;border:1.5px solid #E5E7EB;border-radius:12px;background:#fff;cursor:pointer;text-align:left;width:100%;"
                                    onmouseover="this.style.borderColor='#6366F1';this.style.background='#F9FAFB'" onmouseout="this.style.borderColor='#E5E7EB';this.style.background='#fff'">
                                <div style="width:40px;height:40px;border-radius:10px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fa fa-comment" style="color:#6B7280;font-size:15px;"></i>
                                </div>
                                <div>
                                    <p style="font-size:13px;font-weight:600;color:#111827;margin:0 0 2px;">Just a Comment</p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:0;">The file is a reference, not the deliverable</p>
                                </div>
                            </button>
                            <button type="button" @click="showModal=false; $refs.submitBtn.click()"
                                    style="display:flex;align-items:center;gap:14px;padding:14px 16px;border:1.5px solid #C7D2FE;border-radius:12px;background:linear-gradient(135deg,#F5F3FF,#EEF2FF);cursor:pointer;text-align:left;width:100%;"
                                    onmouseover="this.style.borderColor='#6366F1';this.style.background='#EDE9FE'" onmouseout="this.style.borderColor='#C7D2FE';this.style.background='linear-gradient(135deg,#F5F3FF,#EEF2FF)'">
                                <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#6366F1,#4F46E5);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fa fa-upload" style="color:#fff;font-size:15px;"></i>
                                </div>
                                <div>
                                    <p style="font-size:13px;font-weight:600;color:#4F46E5;margin:0 0 2px;">Submit for Review</p>
                                    <p style="font-size:11px;color:#6366F1;margin:0;opacity:.8;">This file is my deliverable — send for admin review</p>
                                </div>
                            </button>
                        </div>
                        <button type="button" @click="showModal = false"
                                style="display:block;margin:16px auto 0;background:none;border:none;font-size:12px;color:#9CA3AF;cursor:pointer;padding:4px 12px;border-radius:6px;"
                                onmouseover="this.style.color='#6B7280'" onmouseout="this.style.color='#9CA3AF'">Cancel</button>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- ══ Unified Timeline ══ --}}
        @php
            $tlSubMap = [
                'submitted' => ['#EEF2FF','#4F46E5','fa-hourglass-half','In Review'],
                'approved'  => ['#D1FAE5','#059669','fa-circle-check',   'Approved'],
                'rejected'  => ['#FEE2E2','#DC2626','fa-rotate-left',    'Revision Requested'],
            ];
            $timeline = collect();

            if (auth()->user()->hasPermission('view_version_history')) {
                foreach ($task->submissions as $sub) {
                    [$sbg,$sco,$sico,$slbl] = $tlSubMap[$sub->status] ?? $tlSubMap['submitted'];
                    $timeline->push(['type'=>'submission','at'=>$sub->created_at,'sub'=>$sub,'sbg'=>$sbg,'sco'=>$sco,'sico'=>$sico,'slbl'=>$slbl]);
                }
            }
            if (auth()->user()->hasPermission('view_activity_log')) {
                foreach ($task->logs->whereNotIn('action', ['comment_added', 'status_updated_submitted', 'status_updated_in_progress', 'status_updated_revision_requested', 'status_updated_approved']) as $log) {
                    [$aico,$aco,$abg] = $log->actionStyle();
                    $timeline->push(['type'=>'log','at'=>$log->created_at,'log'=>$log,'aico'=>$aico,'aco'=>$aco,'abg'=>$abg]);
                }
            }
            if (auth()->user()->hasPermission('view_comments')) {
                foreach ($task->comments as $comment) {
                    $timeline->push(['type'=>'comment','at'=>$comment->created_at,'comment'=>$comment,'isAdmin'=>in_array($comment->user->role ?? 'user',['admin','manager'])]);
                }
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

        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
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
                            <span style="font-size:12px;font-weight:600;color:#111827;">{{ $log->user?->name ?? 'System' }}</span>
                            <span style="font-size:11px;font-weight:600;padding:1px 7px;border-radius:8px;background:{{ $entry['abg'] }};color:{{ $entry['aco'] }};">{{ $log->actionLabel() }}</span>
                            <span style="font-size:11px;color:#9CA3AF;margin-left:auto;" title="{{ $log->created_at->format('Y-m-d H:i') }}">{{ $log->created_at->format('M d, H:i') }}</span>
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
                        @elseif($log->action === 'deadline_updated' && isset($meta['old_deadline'], $meta['new_deadline']))
                        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:4px;">
                            <span style="font-size:11px;background:#FEE2E2;color:#DC2626;padding:2px 8px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;">
                                <span style="text-decoration:line-through;opacity:.7;">{{ $meta['old_deadline'] }}</span>
                                <i class="fa fa-arrow-right" style="font-size:9px;"></i>
                                <strong>{{ $meta['new_deadline'] }}</strong>
                            </span>
                            @if(!empty($meta['reason']))
                            <span style="font-size:11px;background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:6px;">{{ Str::limit($meta['reason'], 80) }}</span>
                            @endif
                        </div>
                        @elseif(isset($meta['old_status'], $meta['new_status']))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;display:inline-block;margin-top:3px;">
                            {{ str_replace('_',' ',$meta['old_status']) }} → <strong>{{ str_replace('_',' ',$meta['new_status']) }}</strong>
                        </span>
                        @endif
                        @if(isset($meta['rejection_reason']))
                        <p style="font-size:12px;color:#DC2626;background:#FEF2F2;padding:6px 10px;border-radius:8px;border-left:3px solid #EF4444;margin:5px 0 0;">"{{ $meta['rejection_reason'] }}"</p>
                        @endif
                        @if($log->note && !in_array($log->action, ['comment_added','task_created','first_viewed','task_reassigned','task_transferred','deadline_updated']))
                        <p style="font-size:12px;color:#6B7280;background:#F9FAFB;padding:6px 10px;border-radius:8px;border-left:3px solid #E5E7EB;margin:5px 0 0;">"{{ $log->note }}"</p>
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
                @endphp
                <div x-data="{ expanded: false, editingNote: false, showNoteHistory: false, note: {{ json_encode($sub->note ?? '') }} }" style="display:flex;gap:14px;">
                    <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:32px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:{{ $entry['sbg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;z-index:1;box-shadow:0 0 0 3px {{ $entry['sbg'] }};">
                            <i class="fa {{ $entry['sico'] }}" style="color:{{ $entry['sco'] }};font-size:12px;"></i>
                        </div>
                        @if(!$isLast)<div style="width:2px;flex:1;min-height:20px;background:#EBEBEB;margin:4px 0;"></div>@endif
                    </div>
                    <div style="flex:1;min-width:0;padding-bottom:{{ $isLast ? '0' : '20px' }};">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
                            <span style="font-size:12px;font-weight:700;color:#111827;">Version {{ $sub->version }}</span>
                            <span style="font-size:11px;font-weight:600;padding:1px 8px;border-radius:8px;background:{{ $entry['sbg'] }};color:{{ $entry['sco'] }};">{{ $entry['slbl'] }}</span>
                            @if($isFirstWork)<span style="font-size:10px;font-weight:700;padding:1px 8px;border-radius:10px;background:#D1FAE5;color:#059669;display:inline-flex;align-items:center;gap:3px;"><i class="fa fa-circle-play" style="font-size:9px;"></i> Started Working</span>@endif
                            <span style="font-size:11px;color:#9CA3AF;margin-left:auto;" title="{{ $sub->created_at->format('Y-m-d H:i') }}">{{ $sub->created_at->format('M d, H:i') }}</span>
                        </div>
                        <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:12px 14px;">
                            @if($sub->note || $sub->user_id === auth()->id())
                            <div style="margin-bottom:10px;">
                                <div style="display:flex;align-items:flex-start;gap:6px;">
                                    <p x-show="!editingNote" style="font-size:13px;color:#374151;margin:0;line-height:1.6;flex:1;" x-text="note || ''"></p>
                                    @if($sub->user_id === auth()->id())
                                    <button @click="editingNote=!editingNote" style="font-size:10px;background:none;border:none;color:#9CA3AF;cursor:pointer;padding:0;flex-shrink:0;margin-top:2px;" title="Edit note">
                                        <i class="fa fa-pencil" style="font-size:10px;"></i>
                                    </button>
                                    @if($sub->noteEdits->isNotEmpty())
                                    <button @click="showNoteHistory=!showNoteHistory" style="font-size:10px;background:#F3F4F6;color:#9CA3AF;border:none;padding:1px 6px;border-radius:4px;cursor:pointer;flex-shrink:0;">edited</button>
                                    @endif
                                    @endif
                                </div>
                                <div x-show="editingNote">
                                    <form method="POST" action="{{ route('user.tasks.submissions.note', [$task, $sub]) }}">
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
                            @endif
                            @if($sub->file_path)
                                @if($subIsImage)
                                <a href="{{ $subUrl }}" target="_blank" style="display:block;margin-bottom:10px;border-radius:8px;overflow:hidden;border:1px solid #E5E7EB;max-width:300px;text-decoration:none;">
                                    <img src="{{ $subUrl }}" alt="{{ $sub->original_filename }}" style="width:100%;max-height:160px;object-fit:cover;display:block;">
                                    <div style="padding:5px 10px;background:#F3F4F6;display:flex;align-items:center;gap:6px;">
                                        <i class="fa fa-image" style="color:#6366F1;font-size:10px;"></i>
                                        <span style="font-size:11px;color:#6B7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">{{ $sub->original_filename }}</span>
                                        <i class="fa fa-arrow-up-right-from-square" style="font-size:9px;color:#9CA3AF;flex-shrink:0;"></i>
                                    </div>
                                </a>
                                @elseif($subIsVideo)
                                <a href="{{ $subUrl }}" target="_blank" style="display:block;margin-bottom:10px;border-radius:8px;overflow:hidden;border:1px solid #E5E7EB;max-width:300px;text-decoration:none;">
                                    <div style="background:#1F2937;height:110px;display:flex;align-items:center;justify-content:center;">
                                        <div style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;">
                                            <i class="fa fa-play" style="color:#fff;font-size:15px;margin-left:3px;"></i>
                                        </div>
                                    </div>
                                    <div style="padding:5px 10px;background:#F3F4F6;display:flex;align-items:center;gap:6px;">
                                        <i class="fa fa-video" style="color:#6366F1;font-size:10px;"></i>
                                        <span style="font-size:11px;color:#6B7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">{{ $sub->original_filename }}</span>
                                        <i class="fa fa-arrow-up-right-from-square" style="font-size:9px;color:#9CA3AF;flex-shrink:0;"></i>
                                    </div>
                                </a>
                                @else
                                <a href="{{ $subUrl }}" target="_blank" style="display:inline-flex;align-items:center;gap:10px;margin-bottom:10px;padding:10px 14px;background:#fff;border:1px solid #E5E7EB;border-radius:9px;text-decoration:none;max-width:300px;transition:border-color .15s;"
                                   onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                                    <div style="width:36px;height:36px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fa {{ $subIcon }}" style="color:#6366F1;font-size:16px;"></i>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $sub->original_filename }}</p>
                                        <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;text-transform:uppercase;">{{ $subExt ?: 'file' }}</p>
                                    </div>
                                    <i class="fa fa-arrow-up-right-from-square" style="font-size:11px;color:#9CA3AF;flex-shrink:0;"></i>
                                </a>
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
                                <form method="POST" action="{{ route('user.tasks.comments.edit', [$task, $comment]) }}">
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
        </div>

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
                @php $resolvedCustomer = $task->customer ?? $task->project?->customer; @endphp
                @if($resolvedCustomer)
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-building" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Customer</span>
                    <span style="font-size:13px;font-weight:600;color:#111827;">{{ Str::limit($resolvedCustomer->name, 18) }}</span>
                </div>
                @endif
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

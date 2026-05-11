@extends('layouts.app')
@section('title', $task->title)

@section('content')
@php
    $isSocialAssignee = $isSocialAssignee ?? false;
    $doneStatuses = ['approved', 'delivered', 'archived'];
    $deadlineEOD  = $task->deadline ? \App\Models\Setting::deadlineEOD($task->deadline) : null;
    $isOverdue    = $deadlineEOD && $deadlineEOD->isPast() && !in_array($task->status, $doneStatuses);

    $statusMap = [
        'draft'              => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Draft'],
        'assigned'           => ['bg'=>'#E0F2FE','color'=>'#0284C7','label'=>'Assigned'],
        'viewed'             => ['bg'=>'#EEF2FF','color'=>'#4F46E5','label'=>'Viewed'],
        'in_progress'        => ['bg'=>'#FEF3C7','color'=>'#D97706','label'=>'In Progress'],
        'paused'             => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Paused'],
        'pending_customer'   => ['bg'=>'#FFF7ED','color'=>'#C2410C','label'=>'Awaiting Customer Review'],
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
    $canSubmit = !$isSocialAssignee && in_array($task->status, ['viewed', 'in_progress', 'paused', 'revision_requested']);

    // Workflow step index (for stepper)
    $stepOrder = ['draft'=>0,'assigned'=>1,'viewed'=>2,'in_progress'=>3,'paused'=>3,'submitted'=>4,'pending_customer'=>5,'approved'=>6,'delivered'=>7];
    $currentStep = $stepOrder[$task->status] ?? ($task->status === 'revision_requested' ? 4 : 0);
    $steps = [
        ['key'=>'assigned',         'label'=>'Assigned'],
        ['key'=>'viewed',           'label'=>'Viewed'],
        ['key'=>'in_progress',      'label'=>'In Progress'],
        ['key'=>'submitted',        'label'=>'Submitted'],
        ['key'=>'pending_customer', 'label'=>'Awaiting Client'],
        ['key'=>'approved',         'label'=>'Approved'],
        ['key'=>'delivered',        'label'=>'Delivered'],
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
    <div style="display:flex;align-items:center;min-width:{{ $hasSocial ? '900px' : '660px' }};">
        @foreach($steps as $idx => $step)
        @php
            $isSocial   = isset($step['socialState']);
            $isPending  = $step['key'] === 'pending_customer';
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
                $isOverdueClient = $isOverdue && $isPending && $active;
                if ($isRev)          { $dotBg = '#DC2626'; $dotText = '#fff'; $labelColor = '#DC2626'; }
                elseif ($active && $isPending) { $dotBg = '#D97706'; $dotText = '#fff'; $labelColor = '#D97706'; }
                elseif ($done)       { $dotBg = '#6366F1'; $dotText = '#fff'; $labelColor = '#6366F1'; }
                elseif ($active)     { $dotBg = '#6366F1'; $dotText = '#fff'; $labelColor = '#111827'; }
                else                 { $dotBg = '#E5E7EB'; $dotText = '#9CA3AF'; $labelColor = '#9CA3AF'; }
            }
        @endphp
        <div style="display:flex;flex-direction:column;align-items:center;flex:1;">
            <div style="width:28px;height:28px;border-radius:50%;background:{{ $dotBg }};display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:{{ $dotText }};position:relative;z-index:1;flex-shrink:0;{{ ($active && $isPending) ? 'box-shadow:0 0 0 3px #FDE68A;' : '' }}">
                @if($isSocial)
                    @if($done) <i class="fa fa-check" style="font-size:10px;"></i>
                    @elseif($active) <i class="fa fa-share-alt" style="font-size:10px;"></i>
                    @else {{ $idx + 1 }} @endif
                @elseif($done)
                    <i class="fa fa-check" style="font-size:10px;"></i>
                @elseif($isRev)
                    <i class="fa fa-rotate-left" style="font-size:10px;"></i>
                @elseif($active && $isPending)
                    <i class="fa fa-user-clock" style="font-size:10px;"></i>
                @else
                    {{ $idx + 1 }}
                @endif
            </div>
            <p style="font-size:10px;font-weight:{{ $active ? '700' : '500' }};color:{{ $labelColor }};margin:4px 0 0;text-align:center;white-space:nowrap;">
                @if($isRev) Revision
                @elseif($active && $isPending && $isOverdue) Client Late
                @elseif($active && $isPending) Awaiting Client
                @else {{ $step['label'] }} @endif
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
    @elseif($task->status === 'pending_customer')
    <p style="font-size:11px;color:#D97706;text-align:center;margin:10px 0 0;font-weight:600;">
        <i class="fa fa-user-clock" style="margin-right:4px;"></i>Design sent to customer
        @if($task->design_sent_at) — waiting since {{ $task->design_sent_at->diffForHumans() }}@if($isOverdue) <span style="color:#DC2626;">· Deadline passed</span>@endif
        @endif
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
            on <strong>{{ $incomingTransfer->transferred_at->format(config('app.date_format', 'M d, Y')) }}</strong>
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
<div style="background:#D1FAE5;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;margin-bottom:12px;display:flex;align-items:center;gap:10px;color:#065F46;font-size:14px;">
    <i class="fa fa-circle-check"></i> {{ session('success') }}
</div>
@endif
@if(session('timer_warning'))
<div style="background:#FFFBEB;border:1.5px solid #FDE68A;border-radius:10px;padding:12px 16px;margin-bottom:12px;display:flex;align-items:flex-start;gap:10px;color:#92400E;font-size:13px;">
    <i class="fa fa-triangle-exclamation" style="color:#D97706;margin-top:1px;flex-shrink:0;"></i>
    <div>
        <strong style="display:block;margin-bottom:2px;">Outside Work Hours</strong>
        {{ session('timer_warning') }}
    </div>
</div>
@endif
@if(session('error'))
<div style="background:#FEE2E2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:12px;display:flex;align-items:center;gap:10px;color:#991B1B;font-size:14px;">
    <i class="fa fa-circle-exclamation"></i> {{ session('error') }}
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    {{-- Left --}}
    <div style="display:flex;flex-direction:column;gap:20px;">

        {{-- Task details --}}
        <div style="border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(109,40,217,.13);border:1px solid rgba(109,40,217,.12);">
            {{-- Purple gradient header --}}
            <div style="background:linear-gradient(135deg,#312e81,#4c1d95,#5b21b6);padding:16px 24px;display:flex;align-items:center;gap:10px;">
                <span style="font-size:20px;line-height:1;">💡</span>
                <h2 style="font-size:16px;font-weight:700;color:#fff;margin:0;letter-spacing:.02em;">Task Details</h2>
            </div>

            {{-- Warm golden body --}}
            <div style="background:linear-gradient(160deg,#fffbeb,#fef3c7,#fde68a22);padding:24px;">
                @if($task->description)
                <div style="font-size:20px;font-weight:500;color:#1c1917;line-height:1.65;margin:0 0 24px;padding-bottom:20px;border-bottom:1px solid rgba(217,119,6,.25);">
                    {!! nl2br(e($task->description)) !!}
                </div>
                @endif

                {{-- Tags --}}
                @if($task->tags)
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid rgba(217,119,6,.2);">
                    @if($task->task_type)
                    <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(255,255,255,.7);color:#374151;"><i class="fa fa-tag" style="margin-right:3px;color:#92400E;"></i>{{ $task->task_type }}</span>
                    @endif
                    @foreach($task->tags as $tag)
                    <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#EEF2FF;color:#4F46E5;">#{{ $tag }}</span>
                    @endforeach
                </div>
                @endif

                {{-- Info row --}}
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <div style="background:rgba(255,255,255,.75);border-radius:10px;padding:12px 16px;flex:1;min-width:110px;box-shadow:0 1px 6px rgba(0,0,0,.06);backdrop-filter:blur(4px);">
                        <p style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#92400e;font-weight:700;margin:0 0 4px;display:flex;align-items:center;gap:5px;">
                            <i class="fa fa-folder" style="font-size:9px;"></i> Project
                        </p>
                        <p style="font-size:13px;font-weight:700;color:#1c1917;margin:0;">{{ $task->project->name }}</p>
                    </div>

                    <div style="background:rgba(255,255,255,.75);border-radius:10px;padding:12px 16px;flex:1;min-width:110px;box-shadow:0 1px 6px rgba(0,0,0,.06);backdrop-filter:blur(4px);">
                        <p style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#92400e;font-weight:700;margin:0 0 4px;display:flex;align-items:center;gap:5px;">
                            <i class="fa fa-calendar" style="font-size:9px;"></i> Deadline
                        </p>
                        <p style="font-size:13px;font-weight:700;color:{{ $isOverdue ? '#DC2626' : '#1c1917' }};margin:0;">
                            {{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}
                            <span style="font-size:11px;font-weight:400;color:{{ $isOverdue ? '#DC2626' : '#92400e' }};display:block;margin-top:2px;">{{ $deadlineEOD->diffForHumans() }}</span>
                        </p>
                    </div>

                    @if($task->reviewer)
                    <div style="background:rgba(255,255,255,.75);border-radius:10px;padding:12px 16px;flex:1;min-width:110px;box-shadow:0 1px 6px rgba(0,0,0,.06);backdrop-filter:blur(4px);">
                        <p style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#92400e;font-weight:700;margin:0 0 4px;display:flex;align-items:center;gap:5px;">
                            <i class="fa fa-eye" style="font-size:9px;"></i> Reviewer
                        </p>
                        <p style="font-size:13px;font-weight:700;color:#1c1917;margin:0;">{{ $task->reviewer->name }}</p>
                    </div>
                    @endif

                    {{-- Submitted by --}}
                    @if($task->submissions->isNotEmpty())
                    @php $latestSub = $task->submissions->sortByDesc('created_at')->first(); @endphp
                    <div style="background:rgba(255,255,255,.75);border-radius:10px;padding:12px 16px;flex:1;min-width:110px;box-shadow:0 1px 6px rgba(0,0,0,.06);backdrop-filter:blur(4px);">
                        <p style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#92400e;font-weight:700;margin:0 0 4px;display:flex;align-items:center;gap:5px;">
                            <i class="fa fa-file-arrow-up" style="font-size:9px;"></i> Submitted by
                        </p>
                        <p style="font-size:13px;font-weight:700;color:#1c1917;margin:0 0 3px;">{{ $latestSub->user->name ?? '—' }}</p>
                        <p style="font-size:10px;color:#9CA3AF;margin:0;">v{{ $latestSub->version }} · {{ $latestSub->created_at->format('M d, Y') }}</p>
                    </div>
                    @endif

                    {{-- Social posting --}}
                    @if($task->social_required && $task->socialAssignee)
                    @php
                        $spIcons  = ['facebook'=>['fab fa-facebook','#1877F2'],'instagram'=>['fab fa-instagram','#E1306C'],'twitter'=>['fab fa-x-twitter','#000'],'tiktok'=>['fab fa-tiktok','#010101'],'youtube'=>['fab fa-youtube','#FF0000'],'snapchat'=>['fab fa-snapchat-ghost','#F7CA00'],'linkedin'=>['fab fa-linkedin','#0A66C2'],'other'=>['fas fa-share-nodes','#6366F1']];
                        $spFirst  = $task->socialPosts->first();
                        $spPosted = $spFirst !== null;
                    @endphp
                    <div style="background:rgba(255,255,255,.75);border-radius:10px;padding:12px 16px;flex:1;min-width:130px;box-shadow:0 1px 6px rgba(0,0,0,.06);backdrop-filter:blur(4px);">
                        <p style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#92400e;font-weight:700;margin:0 0 6px;display:flex;align-items:center;gap:5px;">
                            <i class="fas fa-share-nodes" style="font-size:9px;"></i> Social Post
                        </p>
                        <p style="font-size:13px;font-weight:700;color:#1c1917;margin:0 0 5px;">{{ $task->socialAssignee->name }}</p>
                        @if($spPosted)
                        <div style="display:flex;flex-direction:column;gap:4px;">
                            @foreach($task->socialPosts as $sp)
                            @php $ico = $spIcons[$sp->platform] ?? $spIcons['other']; @endphp
                            <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                                <i class="{{ $ico[0] }}" style="font-size:11px;color:{{ $ico[1] }};flex-shrink:0;"></i>
                                <span style="font-size:11px;font-weight:600;color:#374151;">{{ ucfirst($sp->platform) }}</span>
                                <span style="font-size:10px;font-weight:700;color:#15803D;background:#DCFCE7;padding:1px 6px;border-radius:4px;white-space:nowrap;">
                                    <i class="fas fa-circle-check" style="font-size:8px;"></i> {{ $sp->created_at->format('M d') }}
                                </span>
                                @if($sp->post_url)
                                <a href="{{ $sp->post_url }}" target="_blank" rel="noopener"
                                   style="font-size:10px;color:#4F46E5;text-decoration:none;background:#EEF2FF;padding:1px 6px;border-radius:4px;font-weight:600;white-space:nowrap;">
                                    <i class="fas fa-arrow-up-right-from-square" style="font-size:7px;"></i> View
                                </a>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @else
                        <span style="font-size:10px;font-weight:700;color:#D97706;background:#FEF3C7;padding:2px 7px;border-radius:4px;white-space:nowrap;">
                            <i class="fas fa-hourglass-half" style="font-size:8px;"></i> Pending post
                        </span>
                        @endif
                    </div>
                    @endif
                </div>
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
                    @php $item = ['name'=>$att->name,'size'=>$att->humanSize(),'url'=>$att->url(),'downloadUrl'=>$att->isFile()?route('user.attachments.download',$att):$att->url(),'icon'=>$att->iconClass(),'isLink'=>$att->isLink(),'isImage'=>in_array(strtolower(pathinfo($att->name,PATHINFO_EXTENSION)),['jpg','jpeg','png','gif','webp','svg']),'isVideo'=>in_array(strtolower(pathinfo($att->name,PATHINFO_EXTENSION)),['mp4','mov','avi','webm','mkv'])]; @endphp
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
                            {{-- Video preview --}}
                            <template x-if="att.isVideo">
                                <div style="padding:16px 24px;border-bottom:1px solid #F3F4F6;background:#000;display:flex;justify-content:center;">
                                    <video :src="att.url" controls style="max-width:100%;max-height:75vh;border-radius:10px;display:block;outline:none;"></video>
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
        @if(!$isSocialAssignee)
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
                    <p style="font-size:14px;font-weight:700;color:#991B1B;margin:0;">Revision Requested — Action Required</p>
                    <p style="font-size:13px;color:#B91C1C;margin:2px 0 0;">Read the admin feedback below, then confirm to resume your timer.</p>
                </div>
            </div>
            @if($latestSubmission?->admin_note)
            <div style="background:#fff;border-radius:10px;padding:12px 14px;border-left:3px solid #DC2626;margin-bottom:14px;">
                <p style="font-size:11px;font-weight:700;color:#DC2626;margin:0 0 4px;text-transform:uppercase;letter-spacing:.04em;">Admin Feedback</p>
                <p style="font-size:13px;color:#374151;margin:0;line-height:1.6;">{{ $latestSubmission->admin_note }}</p>
            </div>
            @endif
            <div style="background:#FFF7F7;border:1px dashed #FECACA;border-radius:10px;padding:12px 14px;margin-bottom:14px;display:flex;align-items:center;gap:10px;">
                <i class="fa fa-lock" style="color:#DC2626;font-size:14px;flex-shrink:0;"></i>
                <p style="font-size:12px;color:#991B1B;margin:0;line-height:1.5;">Your timer is paused until you acknowledge this revision. Clicking the button below starts your timer and begins tracking revision time.</p>
            </div>
            <form method="POST" action="{{ route('user.tasks.acknowledge-revision', $task) }}">
                @csrf
                <button type="submit"
                        style="background:linear-gradient(135deg,#DC2626,#B91C1C);color:#fff;border:none;padding:11px 22px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 12px rgba(220,38,38,.25);">
                    <i class="fa fa-circle-play"></i> I Accept &amp; Start Revision
                </button>
            </form>
        </div>

        @elseif($task->status === 'pending_customer')
        @php
            $pcLog = $task->logs->firstWhere('action', 'status_updated_pending_customer');
            $pcNote = $pcLog?->note;
            $pcBy   = $pcLog?->user?->name ?? 'Manager';
        @endphp
        <div style="background:#FFF7ED;border:1.5px solid #FED7AA;border-radius:14px;padding:18px 20px;display:flex;align-items:flex-start;gap:14px;">
            <div style="width:40px;height:40px;border-radius:50%;background:#FFEDD5;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                <i class="fa fa-user-clock" style="color:#C2410C;font-size:17px;"></i>
            </div>
            <div style="flex:1;">
                <p style="font-size:14px;font-weight:700;color:#7C2D12;margin:0 0 3px;">Awaiting Customer Review</p>
                <p style="font-size:12px;color:#C2410C;margin:0 0 4px;">
                    This task is on hold while the manager waits for the customer's decision.
                    No action is needed from you right now.
                </p>
                @if($pcNote && $pcNote !== 'Awaiting customer approval')
                <p style="font-size:12px;color:#9A3412;background:#FFEDD5;padding:6px 10px;border-radius:8px;border-left:3px solid #FB923C;margin:6px 0 0;">
                    <i class="fa fa-comment" style="margin-right:4px;font-size:10px;"></i>{{ $pcNote }}
                </p>
                @endif
                <p style="font-size:11px;color:#EA580C;margin:6px 0 0;opacity:.75;">Set by {{ $pcBy }}</p>
            </div>
        </div>

        @elseif($task->status === 'paused')
        <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:14px;padding:16px 20px;display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa fa-circle-pause" style="color:#6B7280;font-size:16px;"></i>
            </div>
            <div style="flex:1;">
                <p style="font-size:14px;font-weight:700;color:#374151;margin:0;">Timer Paused</p>
                <p style="font-size:12px;color:#6B7280;margin:0;">Your timer is paused. Resume when you're ready to continue working.</p>
            </div>
            <form method="POST" action="{{ route('user.tasks.timer.start', $task) }}">
                @csrf
                <button type="submit" style="background:linear-gradient(135deg,#059669,#047857);color:#fff;border:none;padding:9px 18px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;">
                    <i class="fa fa-circle-play"></i> Resume Timer
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
                @if($activeSegment)
                <span style="width:10px;height:10px;border-radius:50%;background:#D97706;animation:pulse 1.5s infinite;display:block;"></span>
                @else
                <i class="fa fa-circle-play" style="color:#D97706;font-size:16px;"></i>
                @endif
            </div>
            <div style="flex:1;">
                <p style="font-size:14px;font-weight:700;color:#92400E;margin:0;">
                    @if($activeSegment) Timer Running @else Work in Progress @endif
                </p>
                <p style="font-size:12px;color:#B45309;margin:0;">
                    @if($activeSegment) Time is being tracked. Pause if you stop working. @else Click Start Timer to begin tracking time. @endif
                </p>
            </div>
            @if($activeSegment)
            <form method="POST" action="{{ route('user.tasks.timer.pause', $task) }}">
                @csrf
                <button type="submit" style="background:#F59E0B;color:#fff;border:none;padding:9px 16px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;">
                    <i class="fa fa-circle-pause"></i> Pause
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('user.tasks.timer.start', $task) }}">
                @csrf
                <button type="submit" style="background:linear-gradient(135deg,#D97706,#B45309);color:#fff;border:none;padding:9px 16px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;">
                    <i class="fa fa-circle-play"></i> Start Timer
                </button>
            </form>
            @endif
        </div>
        @endif
        @endif {{-- !isSocialAssignee --}}

        {{-- Unified: Comment + Submit --}}
        @if(!in_array($task->status, ['revision_requested']) && (auth()->user()->hasPermission('view_comments') || ($canSubmit && auth()->user()->hasPermission('submit_work'))))
        <div x-data="{
                uFiles: [], showModal: false, body: '',
                dragging: false, dragCount: 0,
                editorFocused: false, colorOpen: false, selectedColor: '#EF4444', savedRange: null,
                handleDrop(e) {
                    this.dragCount = 0; this.dragging = false;
                    const files = Array.from(e.dataTransfer.files);
                    if (!files.length) return;
                    this.uFiles = files.map(f => f.name);
                    const input = this.$refs.uFileInput;
                    const dt = new DataTransfer();
                    files.forEach(f => dt.items.add(f));
                    input.files = dt.files;
                },
                saveRange() { const s=window.getSelection(); if(s.rangeCount) this.savedRange=s.getRangeAt(0).cloneRange(); },
                restoreRange() { if(!this.savedRange) return; const s=window.getSelection(); s.removeAllRanges(); s.addRange(this.savedRange); },
                cmd(c, v=null) { this.restoreRange(); this.$refs.rteEditor.focus(); document.execCommand(c, false, v); },
                setSize(v) { this.restoreRange(); this.$refs.rteEditor.focus(); document.execCommand('fontSize', false, v); },
                setColor(c) { this.colorOpen=false; this.selectedColor=c; this.restoreRange(); this.$refs.rteEditor.focus(); document.execCommand('foreColor', false, c); },
                addLink() { const u=prompt('Enter URL:'); if(!u) return; this.restoreRange(); this.$refs.rteEditor.focus(); document.execCommand('createLink', false, u.startsWith('http')?u:'https://'+u); },
                getBody() { return this.$refs.rteEditor?.innerHTML?.trim() || ''; }
             }" style="background:#fff;border-radius:14px;border:1.5px solid #6366F1;box-shadow:0 4px 16px rgba(99,102,241,.08);padding:24px;">
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
                <input type="hidden" name="body" x-ref="bodyInput">
                {{-- RTE --}}
                <div :style="editorFocused ? 'border:1.5px solid #6366F1;border-radius:10px;overflow:hidden;margin-bottom:10px;box-shadow:0 0 0 3px rgba(99,102,241,.08);' : 'border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;margin-bottom:10px;'"
                     style="border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;margin-bottom:10px;">
                    <div style="background:#F9FAFB;border-bottom:1px solid #E5E7EB;padding:5px 8px;display:flex;align-items:center;gap:1px;flex-wrap:wrap;">
                        <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('bold')" title="Bold"><b style="font-size:13px;">B</b></button>
                        <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('italic')" title="Italic"><i style="font-style:italic;font-size:13px;">I</i></button>
                        <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('underline')" title="Underline"><u style="font-size:13px;">U</u></button>
                        <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('strikeThrough')" title="Strikethrough"><s style="font-size:12px;">S</s></button>
                        <div style="width:1px;height:16px;background:#D1D5DB;margin:0 4px;flex-shrink:0;"></div>
                        <select @mousedown="saveRange()" @change="setSize($event.target.value);$event.target.selectedIndex=0"
                                style="height:26px;padding:0 6px;border:1px solid #E5E7EB;border-radius:6px;font-size:11px;color:#374151;background:#fff;cursor:pointer;outline:none;">
                            <option value="" disabled selected>Size</option>
                            <option value="1">Small</option>
                            <option value="3">Normal</option>
                            <option value="5">Large</option>
                            <option value="6">X-Large</option>
                        </select>
                        <div style="width:1px;height:16px;background:#D1D5DB;margin:0 4px;flex-shrink:0;"></div>
                        <button type="button" class="rte-toolbar-btn" @mousedown.prevent="addLink()" title="Add link"><i class="fa fa-link" style="font-size:11px;"></i></button>
                        <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('unlink')" title="Remove link"><i class="fa fa-link-slash" style="font-size:11px;"></i></button>
                        <div style="width:1px;height:16px;background:#D1D5DB;margin:0 4px;flex-shrink:0;"></div>
                        <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('insertUnorderedList')" title="Bullet list"><i class="fa fa-list-ul" style="font-size:11px;"></i></button>
                        <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('insertOrderedList')" title="Numbered list"><i class="fa fa-list-ol" style="font-size:11px;"></i></button>
                        <div style="width:1px;height:16px;background:#D1D5DB;margin:0 4px;flex-shrink:0;"></div>
                        {{-- Color picker --}}
                        <div style="position:relative;" @click.outside="colorOpen=false">
                            <button type="button" class="rte-toolbar-btn" @mousedown.prevent="colorOpen=!colorOpen" title="Text color" style="flex-direction:column;gap:1px;">
                                <span style="font-size:12px;font-weight:700;line-height:1;" :style="'color:'+selectedColor">A</span>
                                <span style="width:14px;height:3px;border-radius:2px;display:block;" :style="'background:'+selectedColor"></span>
                            </button>
                            <div x-show="colorOpen" style="position:fixed;z-index:9999;"
                                 x-init="$watch('colorOpen', v => { if(v) { const r = $el.previousElementSibling.getBoundingClientRect(); $el.style.left = r.left+'px'; $el.style.top = (r.bottom+6)+'px'; } })">
                                <div style="background:#fff;border:1px solid #E5E7EB;border-radius:14px;padding:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);display:grid;grid-template-columns:repeat(5,1fr);gap:7px;width:192px;">
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#212121;" @click="setColor('#212121')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#F44336;" @click="setColor('#F44336')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#E91E63;" @click="setColor('#E91E63')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#9C27B0;" @click="setColor('#9C27B0')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#673AB7;" @click="setColor('#673AB7')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#3F51B5;" @click="setColor('#3F51B5')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#2196F3;" @click="setColor('#2196F3')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#00BCD4;" @click="setColor('#00BCD4')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#009688;" @click="setColor('#009688')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#4CAF50;" @click="setColor('#4CAF50')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#8BC34A;" @click="setColor('#8BC34A')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#FFEB3B;" @click="setColor('#FFEB3B')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#FF9800;" @click="setColor('#FF9800')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#FF5722;" @click="setColor('#FF5722')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#795548;" @click="setColor('#795548')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#9E9E9E;" @click="setColor('#9E9E9E')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#607D8B;" @click="setColor('#607D8B')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#6366F1;" @click="setColor('#6366F1')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#EC4899;" @click="setColor('#EC4899')"></div>
                                    <div class="rte-color-swatch" @mousedown.prevent style="background:#fff;border:2px solid #D1D5DB;" @click="setColor('#374151')"></div>
                                </div>
                            </div>
                        </div>
                        <div style="width:1px;height:16px;background:#D1D5DB;margin:0 4px;flex-shrink:0;"></div>
                        <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('removeFormat')" title="Clear formatting"><i class="fa fa-remove-format" style="font-size:11px;"></i></button>
                    </div>
                    <div x-ref="rteEditor" contenteditable="true" class="rte-field"
                         data-placeholder="{{ $task->status === 'viewed' ? 'Optional — add a note before starting...' : (($canSubmit && auth()->user()->hasPermission('submit_work')) ? 'Describe your work or write a comment...' : 'Write your comment...') }}"
                         @focus="editorFocused=true"
                         @blur="editorFocused=false; saveRange()"
                         @keyup="saveRange()" @mouseup="saveRange()"
                         style="min-height:84px;padding:10px 14px;font-size:13px;color:#111827;outline:none;font-family:'Inter',sans-serif;line-height:1.6;background:#fff;word-break:break-word;"></div>
                </div>
                @error('body')<p style="font-size:11px;color:#DC2626;margin:-6px 0 8px;">{{ $message }}</p>@enderror
                <div style="margin-bottom:14px;">
                    <label
                        @dragover.prevent="dragging = true"
                        @dragenter.prevent="dragCount++; dragging = true"
                        @dragleave.prevent="dragCount--; dragging = dragCount > 0"
                        @drop.prevent="handleDrop($event)"
                        :style="dragging
                            ? 'display:flex;align-items:center;gap:12px;padding:12px 16px;border:1.5px dashed #6366F1;border-radius:10px;cursor:pointer;background:#EEF2FF;transition:all .15s;'
                            : 'display:flex;align-items:center;gap:12px;padding:12px 16px;border:1.5px dashed #D1D5DB;border-radius:10px;cursor:pointer;background:#FAFAFA;transition:all .15s;'">
                        <i class="fa fa-paperclip" :style="dragging ? 'color:#6366F1;font-size:16px;' : 'color:#9CA3AF;font-size:16px;'"></i>
                        <div style="flex:1;">
                            <p x-text="uFiles.length === 0 ? (dragging ? 'Drop files here' : 'Attach files — or drag & drop') : uFiles.length + (uFiles.length === 1 ? ' file selected' : ' files selected')"
                               style="font-size:13px;font-weight:500;color:#374151;margin:0;"></p>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Images, PDF, ZIP and more — drop multiple files at once</p>
                        </div>
                        <template x-if="uFiles.length > 0">
                            <button type="button" @click.prevent="uFiles=[]; $refs.uFileInput.value=''"
                                    style="width:22px;height:22px;border-radius:50%;background:#FEE2E2;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa fa-xmark" style="font-size:10px;color:#DC2626;"></i>
                            </button>
                        </template>
                        <input type="file" name="files[]" multiple x-ref="uFileInput"
                               @change="uFiles = Array.from($event.target.files).map(f => f.name)"
                               style="display:none;">
                    </label>
                    {{-- File chips --}}
                    <template x-if="uFiles.length > 0">
                        <ul style="margin:6px 0 0;padding:0;list-style:none;display:flex;flex-wrap:wrap;gap:4px;">
                            <template x-for="name in uFiles" :key="name">
                                <li style="display:flex;align-items:center;gap:4px;padding:2px 8px;background:#EEF2FF;border-radius:6px;font-size:11px;color:#4F46E5;font-weight:500;">
                                    <i class="fa fa-file" style="font-size:9px;"></i>
                                    <span x-text="name"></span>
                                </li>
                            </template>
                        </ul>
                    </template>
                    @error('files.*')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
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
                            @click="$refs.bodyInput.value = getBody();
                            @if($task->status === 'viewed')
                                uFiles.length > 0
                                    ? (showModal = true)
                                    : (getBody()
                                        ? $refs.commentBtn.click()
                                        : document.getElementById('_startForm').submit())
                            @elseif($canSubmit && auth()->user()->hasPermission('submit_work'))
                                uFiles.length > 0 ? (showModal = true) : $refs.commentBtn.click()
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
            <form id="_startForm" method="POST" action="{{ route('user.tasks.timer.start', $task) }}" style="display:none;">
                @csrf
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
                            <button type="button" @click="$refs.bodyInput.value=getBody(); showModal=false; $refs.commentBtn.click()"
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
                            <button type="button" @click="$refs.bodyInput.value=getBody(); showModal=false; $refs.submitBtn.click()"
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

        <div x-data="{
                subOpen: false,
                subItem: null,
                showSub(item) { this.subItem = item; this.subOpen = true; },
                closeSub() { this.subOpen = false; this.subItem = null; }
             }"
             @keydown.escape.window="if(subOpen) closeSub()">
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
                        @elseif($log->action === 'auto_paused' && isset($meta['paused_by_task_id']))
                        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:4px;">
                            <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;">
                                <i class="fa fa-circle-pause" style="font-size:9px;"></i>
                                paused — another task started:
                                <strong>{{ Str::limit($meta['paused_by_task_title'], 50) }}</strong>
                            </span>
                        </div>
                        @elseif(in_array($log->action, ['social_posted','social_post_edited']) && isset($meta['platform']))
                        @php
                            $spPlatformMeta = [
                                'facebook'  => ['fab fa-facebook',   '#1877F2', 'Facebook'],
                                'instagram' => ['fab fa-instagram',  '#E1306C', 'Instagram'],
                                'twitter'   => ['fab fa-x-twitter',  '#000000', 'Twitter/X'],
                                'linkedin'  => ['fab fa-linkedin',   '#0A66C2', 'LinkedIn'],
                                'tiktok'    => ['fab fa-tiktok',     '#010101', 'TikTok'],
                                'youtube'   => ['fab fa-youtube',    '#FF0000', 'YouTube'],
                                'snapchat'  => ['fab fa-snapchat',   '#F7CA00', 'Snapchat'],
                                'other'     => ['fas fa-share-alt','#6366F1', 'Other'],
                            ];
                            [$spIcon, $spColor, $spLabel] = $spPlatformMeta[$meta['platform']] ?? $spPlatformMeta['other'];
                            $spUrl = $meta['post_url'] ?? null;
                        @endphp
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px;align-items:center;">
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:2px 9px;border-radius:8px;background:#F3F4F6;color:{{ $spColor }};">
                                <i class="{{ $spIcon }}" style="font-size:11px;"></i> {{ $spLabel }}
                            </span>
                            @if($spUrl)
                            <a href="{{ $spUrl }}" target="_blank" rel="noopener"
                               style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#4F46E5;background:#EEF2FF;padding:2px 9px;border-radius:8px;text-decoration:none;"
                               onmouseover="this.style.background='#E0E7FF'" onmouseout="this.style.background='#EEF2FF'">
                                <i class="fas fa-arrow-up-right-from-square" style="font-size:9px;"></i> View Post
                            </a>
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
                        @if($log->note && !in_array($log->action, ['comment_added','task_created','first_viewed','task_reassigned','task_transferred','deadline_updated','auto_paused','social_posted','social_post_edited']))
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
                                @php
                                    $subItem = json_encode(['name'=>$sub->original_filename,'url'=>$subUrl,'isImage'=>$subIsImage,'isVideo'=>$subIsVideo,'version'=>$sub->version ?? 1]);
                                @endphp
                                @if($subIsImage)
                                <button type="button" @click="showSub({{ $subItem }})"
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
                                <button type="button" @click="showSub({{ $subItem }})"
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
                                        <i class="fa fa-expand" style="font-size:9px;color:#9CA3AF;flex-shrink:0;"></i>
                                    </div>
                                </button>
                                @else
                                <button type="button" @click="showSub({{ $subItem }})"
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
                <div x-data="{
                    editing: false, showHistory: false, body: {{ json_encode($comment->body) }},
                    editorFocused: false, colorOpen: false, selectedColor: '#EF4444', savedRange: null,
                    saveRange(){ const s=window.getSelection(); if(s.rangeCount) this.savedRange=s.getRangeAt(0).cloneRange(); },
                    restoreRange(){ if(!this.savedRange) return; const s=window.getSelection(); s.removeAllRanges(); s.addRange(this.savedRange); },
                    cmd(c,v=null){ this.restoreRange(); this.$refs.editEditor.focus(); document.execCommand(c,false,v); },
                    setSize(v){ this.restoreRange(); this.$refs.editEditor.focus(); document.execCommand('fontSize',false,v); },
                    setColor(c){ this.colorOpen=false; this.selectedColor=c; this.restoreRange(); this.$refs.editEditor.focus(); document.execCommand('foreColor',false,c); },
                    addLink(){ const u=prompt('Enter URL:'); if(!u) return; this.restoreRange(); this.$refs.editEditor.focus(); document.execCommand('createLink',false,u.startsWith('http')?u:'https://'+u); },
                    openEdit(){ this.editing=true; this.$nextTick(()=>{ if(this.$refs.editEditor) this.$refs.editEditor.innerHTML=this.body; }); }
                }" style="display:flex;gap:14px;">
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
                            <button @click="editing ? editing=false : openEdit()" style="font-size:10px;background:none;border:none;color:#9CA3AF;cursor:pointer;padding:0;display:flex;align-items:center;gap:3px;" title="Edit comment">
                                <i class="fa fa-pencil" style="font-size:10px;"></i>
                            </button>
                            @endif
                            <span style="font-size:11px;color:#9CA3AF;margin-left:auto;" title="{{ $comment->created_at->format('Y-m-d H:i') }}">{{ $comment->created_at->format('M d, H:i') }}</span>
                        </div>
                        <div style="background:{{ $isAdmin ? '#F5F3FF' : '#F9FAFB' }};border:1px solid {{ $isAdmin ? '#EDE9FE' : '#E5E7EB' }};border-radius:10px;padding:10px 14px;{{ $isAdmin ? 'border-left:3px solid #8B5CF6;' : '' }}">
                            <div x-show="!editing">
                                <div class="rte-field" style="font-size:13px;color:#374151;margin:0{{ $comment->file_path ? ' 0 10px' : '' }};line-height:1.6;padding:0;min-height:0;" x-html="body"></div>
                                @if($comment->file_path)
                                    @php $cItem = json_encode(['name'=>$comment->original_filename,'url'=>$cUrl,'isImage'=>$cIsImage,'isVideo'=>$cIsVideo,'version'=>1]); @endphp
                                    @if($cIsImage)
                                    <button type="button" @click="showSub({{ $cItem }})"
                                            style="display:block;border-radius:8px;overflow:hidden;border:1px solid #E5E7EB;max-width:280px;width:100%;cursor:pointer;background:none;padding:0;text-align:left;transition:border-color .15s;"
                                            onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                                        <img src="{{ $cUrl }}" alt="{{ $comment->original_filename }}" style="width:100%;max-height:140px;object-fit:cover;display:block;">
                                        <div style="padding:5px 10px;background:#F3F4F6;display:flex;align-items:center;gap:6px;">
                                            <i class="fa fa-image" style="color:#6366F1;font-size:10px;"></i>
                                            <span style="font-size:11px;color:#6B7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">{{ $comment->original_filename }}</span>
                                            <i class="fa fa-expand" style="font-size:9px;color:#9CA3AF;flex-shrink:0;"></i>
                                        </div>
                                    </button>
                                    @elseif($cIsVideo)
                                    <button type="button" @click="showSub({{ $cItem }})"
                                            style="display:block;border-radius:8px;overflow:hidden;border:1px solid #E5E7EB;max-width:280px;width:100%;cursor:pointer;background:none;padding:0;text-align:left;transition:border-color .15s;"
                                            onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                                        <div style="background:#1F2937;height:90px;display:flex;align-items:center;justify-content:center;">
                                            <div style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;">
                                                <i class="fa fa-play" style="color:#fff;font-size:13px;margin-left:2px;"></i>
                                            </div>
                                        </div>
                                        <div style="padding:5px 10px;background:#F3F4F6;display:flex;align-items:center;gap:6px;">
                                            <i class="fa fa-video" style="color:#6366F1;font-size:10px;"></i>
                                            <span style="font-size:11px;color:#6B7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">{{ $comment->original_filename }}</span>
                                            <i class="fa fa-expand" style="font-size:9px;color:#9CA3AF;flex-shrink:0;"></i>
                                        </div>
                                    </button>
                                    @else
                                    <button type="button" @click="showSub({{ $cItem }})"
                                            style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;background:#fff;border:1px solid #E5E7EB;border-radius:8px;cursor:pointer;max-width:280px;transition:border-color .15s;"
                                            onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                                        <div style="width:30px;height:30px;border-radius:7px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="fa {{ $cIcon }}" style="color:#6366F1;font-size:13px;"></i>
                                        </div>
                                        <div style="flex:1;min-width:0;text-align:left;">
                                            <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $comment->original_filename }}</p>
                                            <p style="font-size:10px;color:#9CA3AF;margin:1px 0 0;text-transform:uppercase;">{{ $cExt ?: 'file' }}</p>
                                        </div>
                                        <i class="fa fa-eye" style="font-size:10px;color:#9CA3AF;flex-shrink:0;"></i>
                                    </button>
                                    @endif
                                @endif
                            </div>
                            <div x-show="editing">
                                <form method="POST" action="{{ route('user.tasks.comments.edit', [$task, $comment]) }}"
                                      @submit.prevent="body=$refs.editEditor.innerHTML.trim(); if(body&&body!=='<br>'){$refs.editBodyInput.value=body;$el.submit();}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="body" x-ref="editBodyInput">
                                    <div :style="editorFocused?'border:1.5px solid #6366F1;border-radius:10px;overflow:hidden;margin-bottom:8px;box-shadow:0 0 0 3px rgba(99,102,241,.08);':'border:1.5px solid #6366F1;border-radius:10px;overflow:hidden;margin-bottom:8px;'"
                                         style="border:1.5px solid #6366F1;border-radius:10px;overflow:hidden;margin-bottom:8px;">
                                        <div style="background:#F9FAFB;border-bottom:1px solid #E5E7EB;padding:4px 8px;display:flex;align-items:center;gap:1px;flex-wrap:wrap;">
                                            <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('bold')" title="Bold"><b style="font-size:12px;">B</b></button>
                                            <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('italic')" title="Italic"><i style="font-style:italic;font-size:12px;">I</i></button>
                                            <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('underline')" title="Underline"><u style="font-size:12px;">U</u></button>
                                            <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('strikeThrough')" title="Strikethrough"><s style="font-size:11px;">S</s></button>
                                            <div style="width:1px;height:14px;background:#D1D5DB;margin:0 3px;flex-shrink:0;"></div>
                                            <select @mousedown="saveRange()" @change="setSize($event.target.value);$event.target.selectedIndex=0"
                                                    style="height:24px;padding:0 5px;border:1px solid #E5E7EB;border-radius:5px;font-size:10px;color:#374151;background:#fff;cursor:pointer;outline:none;">
                                                <option value="" disabled selected>Size</option>
                                                <option value="1">Small</option>
                                                <option value="3">Normal</option>
                                                <option value="5">Large</option>
                                            </select>
                                            <div style="width:1px;height:14px;background:#D1D5DB;margin:0 3px;flex-shrink:0;"></div>
                                            <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('insertUnorderedList')" title="Bullet list"><i class="fa fa-list-ul" style="font-size:10px;"></i></button>
                                            <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('insertOrderedList')" title="Numbered list"><i class="fa fa-list-ol" style="font-size:10px;"></i></button>
                                            <div style="width:1px;height:14px;background:#D1D5DB;margin:0 3px;flex-shrink:0;"></div>
                                            <div style="position:relative;" @click.outside="colorOpen=false">
                                                <button type="button" class="rte-toolbar-btn" @mousedown.prevent="colorOpen=!colorOpen" title="Text color" style="flex-direction:column;gap:1px;">
                                                    <span style="font-size:11px;font-weight:700;line-height:1;" :style="'color:'+selectedColor">A</span>
                                                    <span style="width:12px;height:3px;border-radius:2px;display:block;" :style="'background:'+selectedColor"></span>
                                                </button>
                                                <div x-show="colorOpen" style="position:fixed;z-index:9999;"
                                                     x-init="$watch('colorOpen', v => { if(v) { const r = $el.previousElementSibling.getBoundingClientRect(); $el.style.left = r.left+'px'; $el.style.top = (r.bottom+6)+'px'; } })">
                                                    <div style="background:#fff;border:1px solid #E5E7EB;border-radius:14px;padding:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);display:grid;grid-template-columns:repeat(5,1fr);gap:7px;width:192px;">
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#212121;" @click="setColor('#212121')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#F44336;" @click="setColor('#F44336')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#E91E63;" @click="setColor('#E91E63')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#9C27B0;" @click="setColor('#9C27B0')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#673AB7;" @click="setColor('#673AB7')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#3F51B5;" @click="setColor('#3F51B5')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#2196F3;" @click="setColor('#2196F3')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#00BCD4;" @click="setColor('#00BCD4')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#009688;" @click="setColor('#009688')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#4CAF50;" @click="setColor('#4CAF50')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#8BC34A;" @click="setColor('#8BC34A')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#FFEB3B;" @click="setColor('#FFEB3B')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#FF9800;" @click="setColor('#FF9800')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#FF5722;" @click="setColor('#FF5722')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#795548;" @click="setColor('#795548')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#9E9E9E;" @click="setColor('#9E9E9E')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#607D8B;" @click="setColor('#607D8B')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#6366F1;" @click="setColor('#6366F1')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#EC4899;" @click="setColor('#EC4899')"></div>
                                                        <div class="rte-color-swatch" @mousedown.prevent style="background:#fff;border:2px solid #D1D5DB;" @click="setColor('#374151')"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="width:1px;height:14px;background:#D1D5DB;margin:0 3px;flex-shrink:0;"></div>
                                            <button type="button" class="rte-toolbar-btn" @mousedown.prevent="cmd('removeFormat')" title="Clear formatting"><i class="fa fa-remove-format" style="font-size:10px;"></i></button>
                                        </div>
                                        <div x-ref="editEditor" contenteditable="true" class="rte-field"
                                             data-placeholder="Edit your comment..."
                                             @focus="editorFocused=true"
                                             @blur="editorFocused=false; saveRange()"
                                             @keyup="saveRange()" @mouseup="saveRange()"
                                             style="min-height:60px;padding:9px 12px;font-size:13px;color:#111827;outline:none;font-family:'Inter',sans-serif;line-height:1.6;background:#fff;word-break:break-word;"></div>
                                    </div>
                                    <div style="display:flex;gap:8px;">
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

        {{-- Submission file preview modal --}}
        <template x-teleport="body">
            <div x-show="subOpen" x-cloak
                 @keydown.escape.window="closeSub()"
                 style="position:fixed;inset:0;z-index:9999;">
                <div @click.self="closeSub()"
                     style="width:100%;height:100%;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;padding:16px;">
                    <div x-transition style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:100%;max-width:min(90vw,900px);overflow:hidden;">
                        <template x-if="subItem">
                        <div>
                            <div style="padding:18px 22px 14px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:12px;">
                                <div style="width:38px;height:38px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fa fa-paperclip" style="color:#6366F1;font-size:15px;"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="subItem.name"></p>
                                    <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;" x-text="'Version ' + subItem.version"></p>
                                </div>
                                <button @click="closeSub()" style="width:32px;height:32px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fa fa-xmark" style="color:#6B7280;font-size:13px;"></i>
                                </button>
                            </div>
                            <template x-if="subItem.isImage">
                                <div style="padding:16px 24px;border-bottom:1px solid #F3F4F6;background:#F9FAFB;display:flex;justify-content:center;">
                                    <img :src="subItem.url" :alt="subItem.name" style="max-width:100%;max-height:75vh;border-radius:10px;object-fit:contain;display:block;">
                                </div>
                            </template>
                            <template x-if="subItem.isVideo">
                                <div style="padding:16px 24px;border-bottom:1px solid #F3F4F6;background:#1F2937;display:flex;justify-content:center;">
                                    <video :src="subItem.url" controls style="max-width:100%;max-height:75vh;border-radius:10px;display:block;"></video>
                                </div>
                            </template>
                            <div style="padding:14px 22px;display:flex;gap:10px;justify-content:flex-end;">
                                <button @click="closeSub()" style="padding:8px 18px;background:#F3F4F6;color:#6B7280;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">Close</button>
                                <a :href="subItem.url" download style="display:inline-flex;align-items:center;gap:6px;padding:8px 20px;background:#6366F1;color:#fff;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;"
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

        </div>{{-- /lightbox wrapper --}}

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
                    <span style="font-size:13px;font-weight:600;color:{{ $isOverdue ? '#DC2626' : '#111827' }};">{{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}</span>
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
                @php
                    $doneLog = in_array($task->status, ['approved','delivered','archived'])
                        ? $task->logs->whereIn('action', ['status_updated_approved','status_updated_delivered','status_updated_archived'])->sortByDesc('created_at')->first()
                        : null;
                    if ($doneLog) {
                        $diffSec = $task->created_at->diffInSeconds($doneLog->created_at);
                        $days    = intdiv($diffSec, 86400);
                        $hrs     = intdiv($diffSec % 86400, 3600);
                        $mins    = intdiv($diffSec % 3600, 60);
                        $finishDuration = $days > 0
                            ? "{$days}d " . ($hrs > 0 ? "{$hrs}h" : '')
                            : ($hrs > 0 ? "{$hrs}h {$mins}m" : "{$mins}m");
                        $finishDuration = trim($finishDuration);
                    }
                @endphp
                @if($doneLog)
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-hourglass-end" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Time to Finish</span>
                    <span style="font-size:12px;font-weight:600;color:#059669;">{{ $finishDuration }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Timer Widget --}}
        @php
            $timerDoneStatuses = ['approved', 'delivered', 'archived'];
            $showTimer = !in_array($task->status, ['draft', 'assigned']);
            $timerRunning = $activeSegment !== null;
            $activeSegmentStartTs = $activeSegment ? $activeSegment->started_at->timestamp : 0;
        @endphp
        @if($showTimer)
        <div id="timerWidget" style="background:#fff;border-radius:14px;border:1px solid {{ $timerRunning ? '#FDE68A' : '#F3F4F6' }};box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;text-align:center;transition:border-color .3s;">
            <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:4px;">
                <i class="fa fa-stopwatch" style="color:{{ $timerRunning ? '#D97706' : '#9CA3AF' }};font-size:13px;"></i>
                <h3 style="font-size:12px;font-weight:600;color:#6B7280;margin:0;text-transform:uppercase;letter-spacing:.04em;">Time Tracked</h3>
                @if($timerRunning)
                <span style="width:8px;height:8px;border-radius:50%;background:#D97706;animation:pulse 1.5s infinite;display:inline-block;margin-left:2px;"></span>
                @endif
            </div>
            <p id="timerDisplay" style="font-size:30px;font-weight:700;color:#111827;margin:8px 0 4px;font-variant-numeric:tabular-nums;letter-spacing:-.5px;">00:00:00</p>
            <p id="timerSession" style="font-size:11px;color:#9CA3AF;margin:0 0 14px;">
                @if($timerRunning) Session running @elseif(in_array($task->status, $timerDoneStatuses)) Work complete @elseif($task->status === 'pending_customer') Awaiting customer @else Timer paused @endif
            </p>
            @if(!in_array($task->status, $timerDoneStatuses) && !in_array($task->status, ['submitted', 'revision_requested', 'pending_customer']))
                @if($timerRunning)
                <form method="POST" action="{{ route('user.tasks.timer.pause', $task) }}" style="display:inline;">
                    @csrf
                    <button type="submit" style="background:#F3F4F6;color:#374151;border:none;padding:8px 20px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                        <i class="fa fa-circle-pause"></i> Pause Timer
                    </button>
                </form>
                @elseif(in_array($task->status, ['in_progress', 'paused', 'viewed']))
                <form method="POST" action="{{ route('user.tasks.timer.start', $task) }}" style="display:inline;">
                    @csrf
                    <button type="submit" style="background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:8px 20px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(99,102,241,.3);">
                        <i class="fa fa-circle-play"></i> {{ $task->status === 'viewed' ? 'Start Timer' : ($completedTimerSeconds > 0 ? 'Resume Timer' : 'Start Timer') }}
                    </button>
                </form>
                @endif
            @endif
            @if($completedTimerSeconds > 0 && $timerRunning)
            <p style="font-size:10px;color:#D1D5DB;margin:10px 0 0;">includes previous sessions</p>
            @endif
        </div>
        @endif

        <style>
            @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
            .rte-field:empty:before { content: attr(data-placeholder); color: #9CA3AF; pointer-events: none; display: block; }
            .rte-field a { color: #4F46E5; text-decoration: underline; }
            .rte-field ul { list-style-type: disc; padding-left: 1.5em; margin: 4px 0; }
            .rte-field ol { list-style-type: decimal; padding-left: 1.5em; margin: 4px 0; }
            .rte-field li { margin: 2px 0; }
            .rte-toolbar-btn { width:28px;height:28px;border:none;background:none;border-radius:6px;cursor:pointer;font-size:13px;color:#374151;display:flex;align-items:center;justify-content:center;transition:background .12s;flex-shrink:0; }
            .rte-toolbar-btn:hover { background:#E5E7EB; }
            .rte-color-swatch { width:26px;height:26px;border-radius:50%;border:2px solid transparent;cursor:pointer;flex-shrink:0;transition:transform .15s,box-shadow .15s; }
            .rte-color-swatch:hover { transform:scale(1.2);box-shadow:0 3px 10px rgba(0,0,0,.3); }
        </style>
        <script>
        (function() {
            var completedSeconds = {{ $completedTimerSeconds }};
            var activeStartTs    = {{ $activeSegmentStartTs }};
            var display          = document.getElementById('timerDisplay');
            var session          = document.getElementById('timerSession');
            if (!display) return;

            function fmt(s) {
                var h = Math.floor(s / 3600);
                var m = Math.floor((s % 3600) / 60);
                var sec = s % 60;
                return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (sec < 10 ? '0' : '') + sec;
            }

            function tick() {
                var totalSeconds = completedSeconds;
                if (activeStartTs > 0) {
                    var now = Math.floor(Date.now() / 1000);
                    totalSeconds += Math.max(0, now - activeStartTs);
                }
                display.textContent = fmt(totalSeconds);
            }

            tick();
            if (activeStartTs > 0) {
                setInterval(tick, 1000);
                if (session) session.textContent = 'Session running';
            } else {
                if (session && completedSeconds === 0) session.textContent = 'No time tracked yet';
            }
        })();
        </script>

        {{-- Time remaining --}}
        @php
            $tbg = '#EEF2FF'; $tbo = '#C7D2FE'; $tico = '#6366F1'; $ttitle = 'Due ' . $deadlineEOD->diffForHumans(); $tsub = $task->deadline->format('l, M d');
            if($task->status === 'delivered')      { $tbg='#ECFDF5';$tbo='#6EE7B7';$tico='#047857';$ttitle='Delivered!';$tsub='Work delivered to client.'; }
            elseif($task->status === 'approved')   { $tbg='#F0FDF4';$tbo='#A7F3D0';$tico='#059669';$ttitle='Approved!';$tsub='Awaiting delivery.'; }
            elseif($task->status === 'submitted')  { $tbg='#F5F3FF';$tbo='#DDD6FE';$tico='#7C3AED';$ttitle='Under Review';$tsub='Waiting for admin.'; }
            elseif($task->status === 'revision_requested') { $tbg='#FEF2F2';$tbo='#FECACA';$tico='#DC2626';$ttitle='Revision Needed';$tsub='Check admin feedback.'; }
            elseif($isOverdue) { $tbg='#FEF2F2';$tbo='#FECACA';$tico='#DC2626';$ttitle='Overdue';$tsub=$deadlineEOD->diffForHumans(); }
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

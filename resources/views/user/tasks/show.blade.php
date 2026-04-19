@extends('layouts.app')
@section('title', $task->title)

@section('content')
@php
    $isOverdue  = $task->deadline->isPast() && !in_array($task->status, ['completed','pending_approval','delivered']);
    $statusMap  = [
        'pending'          => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Pending'],
        'in_progress'      => ['bg'=>'#FEF3C7','color'=>'#D97706','label'=>'In Progress'],
        'pending_approval' => ['bg'=>'#EDE9FE','color'=>'#7C3AED','label'=>'In Review'],
        'completed'        => ['bg'=>'#D1FAE5','color'=>'#059669','label'=>'Completed'],
        'delivered'        => ['bg'=>'#ECFDF5','color'=>'#047857','label'=>'Delivered'],
    ];
    $priorityMap = ['low'=>['bg'=>'#D1FAE5','color'=>'#059669'],'medium'=>['bg'=>'#FEF3C7','color'=>'#D97706'],'high'=>['bg'=>'#FEE2E2','color'=>'#DC2626']];
    $s = $statusMap[$task->status]    ?? $statusMap['pending'];
    $p = $priorityMap[$task->priority] ?? $priorityMap['medium'];
    $latestSubmission = $task->submissions->first(); // already ordered desc
    $canSubmit = !in_array($task->status, ['completed', 'delivered']);
@endphp

{{-- Header --}}
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
    <a href="{{ route('user.tasks.index') }}"
       style="width:36px;height:36px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;flex-shrink:0;">
        <i class="fa fa-arrow-left" style="font-size:13px;"></i>
    </a>
    <div>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">{{ $task->title }}</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:2px 0 0;"><i class="fa fa-folder-open" style="margin-right:4px;"></i>{{ $task->project->name }}</p>
    </div>
    <div style="margin-left:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
        <span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $p['bg'] }};color:{{ $p['color'] }};">{{ ucfirst($task->priority) }} Priority</span>
        <span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $s['bg'] }};color:{{ $s['color'] }};">{{ $s['label'] }}</span>
        @if($isOverdue)<span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#FEE2E2;color:#DC2626;"><i class="fa fa-clock" style="margin-right:3px;"></i>Overdue</span>@endif
    </div>
</div>

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
            <p style="font-size:14px;color:#6B7280;line-height:1.7;margin:0 0 20px;padding-bottom:20px;border-bottom:1px solid #F3F4F6;">{{ $task->description }}</p>
            @endif
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div style="background:#FAFAFA;border-radius:10px;padding:14px;">
                    <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 6px;">Project</p>
                    <p style="font-size:14px;font-weight:600;color:#111827;margin:0;">{{ $task->project->name }}</p>
                </div>
                <div style="background:#FAFAFA;border-radius:10px;padding:14px;">
                    <p style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;font-weight:600;margin:0 0 6px;">Deadline</p>
                    <p style="font-size:14px;font-weight:600;color:{{ $isOverdue ? '#DC2626' : '#111827' }};margin:0;">
                        {{ $task->deadline->format('M d, Y') }}
                        <span style="font-size:11px;font-weight:400;color:{{ $isOverdue ? '#DC2626' : '#9CA3AF' }};"> — {{ $task->deadline->diffForHumans() }}</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Status / Pending Approval banner --}}
        @if($task->status === 'pending_approval')
        <div style="background:#F5F3FF;border:1px solid #DDD6FE;border-radius:14px;padding:20px;display:flex;align-items:flex-start;gap:16px;">
            <div style="width:44px;height:44px;border-radius:50%;background:#EDE9FE;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa fa-hourglass-half" style="color:#7C3AED;font-size:18px;"></i>
            </div>
            <div>
                <p style="font-size:14px;font-weight:700;color:#4C1D95;margin:0 0 4px;">Awaiting Admin Review</p>
                <p style="font-size:13px;color:#6D28D9;margin:0 0 8px;">Your submission (v{{ $task->submissions->first()?->version ?? 1 }}) is being reviewed. You can submit another version while waiting.</p>
                @if($latestSubmission?->admin_note)
                <p style="font-size:12px;color:#7C3AED;background:#EDE9FE;padding:8px 12px;border-radius:8px;margin:0;font-style:italic;">"{{ $latestSubmission->admin_note }}"</p>
                @endif
            </div>
        </div>
        @elseif($task->status === 'completed')
        <div style="background:#F0FDF4;border:1px solid #A7F3D0;border-radius:14px;padding:20px;display:flex;align-items:center;gap:14px;">
            <div style="width:44px;height:44px;border-radius:50%;background:#D1FAE5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa fa-circle-check" style="color:#059669;font-size:20px;"></i>
            </div>
            <div>
                <p style="font-size:14px;font-weight:700;color:#065F46;margin:0 0 2px;">Task Approved & Completed</p>
                @if($latestSubmission?->admin_note)
                <p style="font-size:13px;color:#047857;margin:0;">Admin note: {{ $latestSubmission->admin_note }}</p>
                @else
                <p style="font-size:13px;color:#047857;margin:0;">Your submission was approved by the admin.</p>
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
        @else
        {{-- Quick status change (only pending ↔ in_progress) --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 14px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-pen-to-square" style="color:#6366F1;"></i> Update Status
            </h2>
            <form method="POST" action="{{ route('user.tasks.updateStatus', $task) }}">
                @csrf @method('PATCH')
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#6B7280;display:block;margin-bottom:6px;">Status</label>
                        <select name="status" required style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;background:#fff;outline:none;">
                            <option value="pending"     {{ old('status',$task->status)==='pending'     ? 'selected':'' }}>Pending</option>
                            <option value="in_progress" {{ old('status',$task->status)==='in_progress' ? 'selected':'' }}>In Progress</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#6B7280;display:block;margin-bottom:6px;">Note <span style="font-weight:400;color:#9CA3AF;">(optional)</span></label>
                        <input type="text" name="note" value="{{ old('note') }}" placeholder="Add a note..."
                               style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                    </div>
                </div>
                <button type="submit" style="background:#F3F4F6;color:#374151;border:none;padding:9px 20px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                    Save Status
                </button>
            </form>
        </div>
        @endif

        {{-- Submit Work / New Version --}}
        @if($canSubmit)
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
                        <input type="file" name="file" class="hidden"
                               @change="filename = $event.target.files[0]?.name || ''"
                               style="display:none;">
                    </label>
                    @error('file')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                        style="width:100%;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(99,102,241,.3);">
                    <i class="fas fa-paper-plane" style="margin-right:6px;"></i>
                    Submit for Review
                </button>
            </form>
        </div>
        @endif

        {{-- Submission history --}}
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
            <div style="display:flex;gap:14px;padding-bottom:20px;margin-bottom:20px;border-bottom:1px solid #F9FAFB;">
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
                            Admin feedback — {{ $sub->reviewer?->name ?? 'Admin' }}
                        </p>
                        <p style="font-size:12px;color:{{ $sub->status === 'approved' ? '#047857' : '#B91C1C' }};margin:0;">{{ $sub->admin_note }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Activity log --}}
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
            <div style="display:flex;gap:14px;padding-bottom:20px;margin-bottom:20px;border-bottom:1px solid #F9FAFB;">
                <div style="flex-shrink:0;">
                    <div style="width:36px;height:36px;border-radius:50%;background:{{ $abg }};display:flex;align-items:center;justify-content:center;">
                        <i class="fa {{ $aico }}" style="color:{{ $aco }};font-size:14px;"></i>
                    </div>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                        @if($log->user)
                        <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr($log->user->name,0,1)) }}
                        </div>
                        <span style="font-size:13px;font-weight:600;color:#111827;">{{ $log->user->name }}</span>
                        @else
                        <span style="font-size:13px;color:#9CA3AF;font-style:italic;">System</span>
                        @endif
                        <span style="font-size:11px;font-weight:600;padding:1px 7px;border-radius:8px;background:{{ $abg }};color:{{ $aco }};">{{ $log->actionLabel() }}</span>
                        <span style="font-size:11px;color:#6B7280;margin-left:auto;">{{ $log->created_at->format('M d, H:i') }}</span>
                    </div>

                    {{-- Rich metadata chips --}}
                    @if(!empty($meta))
                    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:5px;">
                        @if($log->action === 'task_reassigned' && isset($meta['from_user_name'], $meta['to_user_name']))
                        <span style="font-size:11px;background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:6px;">
                            <span style="text-decoration:line-through;opacity:.7;">{{ $meta['from_user_name'] }}</span> → <strong>{{ $meta['to_user_name'] }}</strong>
                        </span>
                        @elseif($log->action === 'status_updated_pending_approval' && isset($meta['version']))
                        <span style="font-size:11px;background:#EEF2FF;color:#4F46E5;padding:2px 8px;border-radius:6px;">v{{ $meta['version'] }}</span>
                        @if(!empty($meta['has_file']))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;"><i class="fa fa-paperclip" style="margin-right:3px;"></i>{{ $meta['filename'] ?? 'file attached' }}</span>
                        @endif
                        @elseif(in_array($log->action, ['status_updated_completed','status_updated_in_progress']) && isset($meta['reviewer_name']))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">reviewer: <strong>{{ $meta['reviewer_name'] }}</strong></span>
                        @if(isset($meta['submission_version']))
                        <span style="font-size:11px;background:#EEF2FF;color:#4F46E5;padding:2px 8px;border-radius:6px;">v{{ $meta['submission_version'] }}</span>
                        @endif
                        @elseif($log->action === 'status_updated_delivered' && isset($meta['delivered_by_name']))
                        <span style="font-size:11px;background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:6px;"><i class="fa fa-truck" style="margin-right:3px;"></i>{{ $meta['delivered_by_name'] }}</span>
                        @elseif(isset($meta['old_status'], $meta['new_status']))
                        <span style="font-size:11px;background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:6px;">
                            <span style="text-decoration:line-through;opacity:.7;">{{ str_replace('_',' ',$meta['old_status']) }}</span> → <strong>{{ str_replace('_',' ',$meta['new_status']) }}</strong>
                        </span>
                        @endif
                    </div>
                    @endif

                    @if($log->note && !in_array($log->action, ['comment_added','task_created','first_viewed']))
                    <p style="font-size:12px;color:#6B7280;background:#F9FAFB;padding:8px 12px;border-radius:8px;border-left:3px solid #E5E7EB;margin:6px 0 0;">"{{ $log->note }}"</p>
                    @endif
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:40px 0;color:#9CA3AF;">
                <p style="font-size:14px;margin:0;">No activity recorded yet.</p>
            </div>
            @endforelse
        </div>

        {{-- Comments & Updates --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:24px;">
            <h2 style="font-size:15px;font-weight:600;color:#374151;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <i class="fa fa-comments" style="color:#6366F1;"></i> Comments & Updates
                <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9CA3AF;">{{ $task->comments->count() }} {{ Str::plural('comment', $task->comments->count()) }}</span>
            </h2>

            {{-- Post comment --}}
            <form method="POST" action="{{ route('user.tasks.comment', $task) }}" style="margin-bottom:20px;">
                @csrf
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    @if(auth()->user()?->avatarUrl())
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="" style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    @else
                        <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                    @endif
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

            {{-- Comment list --}}
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
                <i class="fa fa-comment-slash" style="font-size:22px;margin-bottom:8px;display:block;color:#E5E7EB;"></i>
                <p style="font-size:13px;margin:0;">No comments yet. Post a question or update for the admin.</p>
            </div>
            @endforelse
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
                    <span style="font-size:13px;font-weight:600;color:#111827;">{{ Str::limit($task->project->name,20) }}</span>
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
                    <span style="font-size:13px;color:#6B7280;"><i class="fa fa-upload" style="width:16px;color:#9CA3AF;margin-right:6px;"></i>Versions</span>
                    <span style="font-size:13px;font-weight:600;color:#111827;">{{ $task->submissions->count() }}</span>
                </div>
            </div>
        </div>

        {{-- Time remaining --}}
        <div style="background:{{ $isOverdue ? '#FEF2F2' : (in_array($task->status,['completed','delivered']) ? '#F0FDF4' : ($task->status==='pending_approval' ? '#F5F3FF' : '#EEF2FF')) }};border:1px solid {{ $isOverdue ? '#FECACA' : (in_array($task->status,['completed','delivered']) ? '#A7F3D0' : ($task->status==='pending_approval' ? '#DDD6FE' : '#C7D2FE')) }};border-radius:14px;padding:20px;text-align:center;">
            <i class="fa fa-clock" style="font-size:24px;color:{{ $isOverdue ? '#DC2626' : (in_array($task->status,['completed','delivered']) ? '#059669' : ($task->status==='pending_approval' ? '#7C3AED' : '#6366F1')) }};margin-bottom:8px;display:block;"></i>
            @if($task->status === 'delivered')
                <p style="font-size:14px;font-weight:700;color:#065F46;margin:0 0 4px;">Delivered!</p>
                <p style="font-size:12px;color:#047857;margin:0;">Work delivered to client.</p>
            @elseif($task->status === 'completed')
                <p style="font-size:14px;font-weight:700;color:#065F46;margin:0 0 4px;">Approved!</p>
                <p style="font-size:12px;color:#047857;margin:0;">This task is complete.</p>
            @elseif($task->status === 'pending_approval')
                <p style="font-size:14px;font-weight:700;color:#4C1D95;margin:0 0 4px;">Under Review</p>
                <p style="font-size:12px;color:#6D28D9;margin:0;">Waiting for admin approval</p>
            @elseif($isOverdue)
                <p style="font-size:14px;font-weight:700;color:#DC2626;margin:0 0 4px;">Overdue</p>
                <p style="font-size:12px;color:#B91C1C;margin:0;">{{ $task->deadline->diffForHumans() }}</p>
            @else
                <p style="font-size:14px;font-weight:700;color:#4338CA;margin:0 0 4px;">Due {{ $task->deadline->diffForHumans() }}</p>
                <p style="font-size:12px;color:#6366F1;margin:0;">{{ $task->deadline->format('l, M d') }}</p>
            @endif
        </div>

        {{-- Other tasks in same project --}}
        @php
            $siblingTasks = $task->project->tasks()
                ->where('id','!=',$task->id)
                ->where('assigned_to', auth()->id())
                ->orderByRaw("CASE WHEN status='completed' THEN 1 ELSE 0 END")
                ->orderBy('deadline')
                ->take(4)->get();
        @endphp
        @if($siblingTasks->count())
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:.04em;">Other Tasks in Project</h3>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($siblingTasks as $sib)
                @php $sc = $statusMap[$sib->status] ?? $statusMap['pending']; @endphp
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

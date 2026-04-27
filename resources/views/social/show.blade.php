@extends('layouts.app')
@section('title', 'Social Media Post — ' . $task->title)

@section('content')

@php
$statusMap = [
    'draft'               => ['Draft',              '#F3F4F6','#6B7280'],
    'assigned'            => ['Assigned',           '#EEF2FF','#4F46E5'],
    'viewed'              => ['Viewed',             '#F0FDF4','#16A34A'],
    'in_progress'         => ['In Progress',        '#FFFBEB','#D97706'],
    'submitted'           => ['In Review',          '#F5F3FF','#7C3AED'],
    'revision_requested'  => ['Revision Requested', '#FEF2F2','#DC2626'],
    'approved'            => ['Approved',           '#ECFDF5','#059669'],
    'delivered'           => ['Delivered',          '#EFF6FF','#2563EB'],
    'archived'            => ['Archived',           '#F9FAFB','#9CA3AF'],
];
[$statusLabel, $statusBg, $statusColor] = $statusMap[$task->status] ?? [ucfirst($task->status), '#F3F4F6', '#6B7280'];

$priorityMap = [
    'high'   => ['High',   '#FEE2E2','#DC2626'],
    'medium' => ['Medium', '#FEF3C7','#D97706'],
    'low'    => ['Low',    '#D1FAE5','#059669'],
];
[$priorityLabel, $priorityBg, $priorityColor] = $priorityMap[$task->priority] ?? [ucfirst($task->priority), '#F3F4F6', '#6B7280'];

$projStatusMap = [
    'active'    => ['Active',    '#EEF2FF','#4F46E5'],
    'completed' => ['Completed', '#ECFDF5','#059669'],
    'on_hold'   => ['On Hold',   '#FEF3C7','#D97706'],
    'cancelled' => ['Cancelled', '#FEF2F2','#DC2626'],
];
$proj = $task->project;
$latestSub = $task->submissions->first();

$pMeta = [
    'facebook'  => ['Facebook',   'fa-facebook',   '#1877F2','#EBF3FF'],
    'instagram' => ['Instagram',  'fa-instagram',  '#E1306C','#FFF0F5'],
    'twitter'   => ['Twitter / X','fa-x-twitter',  '#000000','#F5F5F5'],
    'linkedin'  => ['LinkedIn',   'fa-linkedin',   '#0A66C2','#EAF2FB'],
    'tiktok'    => ['TikTok',     'fa-tiktok',     '#010101','#F5F5F5'],
    'youtube'   => ['YouTube',    'fa-youtube',    '#FF0000','#FFF0F0'],
    'snapchat'  => ['Snapchat',   'fa-snapchat',   '#F7CA00','#FFFDE7'],
    'other'     => ['Other',      'fa-share-nodes','#6366F1','#EEF2FF'],
];
@endphp

{{-- ── Page Header ── --}}
<div style="display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:24px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(99,102,241,.3);">
            <i class="fas fa-share-nodes" style="color:#fff;font-size:20px;"></i>
        </div>
        <div>
            <h1 style="font-size:20px;font-weight:800;color:#111827;margin:0;">Social Media Assignment</h1>
            <p style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">Review the task details and record where you've published it</p>
        </div>
    </div>
    <a href="{{ url()->previous() }}"
       style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6B7280;text-decoration:none;padding:8px 16px;border:1px solid #E5E7EB;border-radius:10px;background:#fff;"
       onmouseover="this.style.borderColor='#6366F1';this.style.color='#4F46E5'" onmouseout="this.style.borderColor='#E5E7EB';this.style.color='#6B7280'">
        <i class="fas fa-arrow-left" style="font-size:11px;"></i> Go Back
    </a>
</div>

@if(session('success'))
<div style="background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border:1px solid #A7F3D0;border-radius:12px;padding:14px 18px;margin-bottom:20px;color:#065F46;font-size:14px;display:flex;gap:10px;align-items:center;">
    <i class="fas fa-circle-check" style="color:#10B981;font-size:16px;flex-shrink:0;"></i>
    {{ session('success') }}
</div>
@endif

{{-- ── Two-column layout ── --}}
<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

    {{-- LEFT COLUMN --}}
    <div style="display:flex;flex-direction:column;gap:20px;">

        {{-- ── Full Task Details Card ── --}}
        <div style="background:#fff;border-radius:18px;border:1px solid #E5E7EB;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;">

            {{-- Task header --}}
            <div style="padding:20px 24px 18px;border-bottom:1px solid #F3F4F6;background:linear-gradient(135deg,#F8F9FF,#fff);">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:0;">
                        <h2 style="font-size:18px;font-weight:800;color:#111827;margin:0 0 10px;line-height:1.3;">{{ $task->title }}</h2>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span style="font-size:11px;font-weight:700;padding:4px 11px;border-radius:20px;background:{{ $statusBg }};color:{{ $statusColor }};">
                                {{ $statusLabel }}
                            </span>
                            <span style="font-size:11px;font-weight:700;padding:4px 11px;border-radius:20px;background:{{ $priorityBg }};color:{{ $priorityColor }};">
                                <i class="fas fa-flag" style="font-size:9px;margin-right:3px;"></i>{{ $priorityLabel }} Priority
                            </span>
                            @if($task->task_type)
                            <span style="font-size:11px;font-weight:600;padding:4px 11px;border-radius:20px;background:#F3F4F6;color:#6B7280;">
                                {{ ucfirst(str_replace('_',' ',$task->task_type)) }}
                            </span>
                            @endif
                        </div>
                    </div>
                    @if($task->social_posted_at)
                    <div style="flex-shrink:0;display:flex;align-items:center;gap:6px;padding:6px 14px;background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border:1px solid #A7F3D0;border-radius:10px;">
                        <i class="fas fa-circle-check" style="color:#10B981;font-size:13px;"></i>
                        <span style="font-size:12px;font-weight:700;color:#065F46;">Posted</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Task meta grid --}}
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:0;border-bottom:1px solid #F3F4F6;">
                @if($task->deadline)
                <div style="padding:14px 24px;border-right:1px solid #F3F4F6;border-bottom:1px solid #F3F4F6;">
                    <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 5px;">Deadline</p>
                    @php $isOverdue = $task->deadline->isPast() && !in_array($task->status,['approved','delivered','archived']); @endphp
                    <p style="font-size:13px;font-weight:700;color:{{ $isOverdue ? '#DC2626' : '#111827' }};margin:0;display:flex;align-items:center;gap:5px;">
                        <i class="fas fa-calendar-day" style="font-size:11px;color:{{ $isOverdue ? '#EF4444' : '#A5B4FC' }};"></i>
                        {{ $task->deadline->format('M d, Y') }}
                        @if($isOverdue)<span style="font-size:10px;background:#FEE2E2;color:#DC2626;padding:1px 6px;border-radius:8px;">Overdue</span>@endif
                    </p>
                </div>
                @endif

                @if($task->assignee)
                <div style="padding:14px 24px;border-bottom:1px solid #F3F4F6;">
                    <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 5px;">Assigned To</p>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr($task->assignee->name,0,1)) }}
                        </div>
                        <div>
                            <p style="font-size:13px;font-weight:600;color:#111827;margin:0;">{{ $task->assignee->name }}</p>
                            @if($task->assignee->job_title)<p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $task->assignee->job_title }}</p>@endif
                        </div>
                    </div>
                </div>
                @endif

                @if($task->creator)
                <div style="padding:14px 24px;border-right:1px solid #F3F4F6;">
                    <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 5px;">Created By</p>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#0E7490,#0891B2);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr($task->creator->name,0,1)) }}
                        </div>
                        <p style="font-size:13px;font-weight:600;color:#111827;margin:0;">{{ $task->creator->name }}</p>
                    </div>
                </div>
                @endif

                <div style="padding:14px 24px;">
                    <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 5px;">Created</p>
                    <p style="font-size:13px;font-weight:600;color:#111827;margin:0;">{{ $task->created_at->format('M d, Y') }}</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">{{ $task->created_at->diffForHumans() }}</p>
                </div>
            </div>

            {{-- Description / Brief --}}
            @if($task->description)
            <div style="padding:18px 24px;border-bottom:1px solid #F3F4F6;">
                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;">
                    <i class="fas fa-align-left" style="margin-right:5px;"></i>Content Brief
                </p>
                <div style="font-size:14px;color:#374151;line-height:1.8;white-space:pre-line;background:#F9FAFB;border-radius:10px;padding:14px 16px;border:1px solid #F0F0F0;">{{ $task->description }}</div>
            </div>
            @endif

            {{-- Tags --}}
            @if(!empty($task->tags))
            <div style="padding:14px 24px;border-bottom:1px solid #F3F4F6;">
                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 8px;">Tags</p>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach($task->tags as $tag)
                    <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:#EEF2FF;color:#4F46E5;">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Submission file --}}
            @if($latestSub)
            <div style="padding:18px 24px;">
                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 12px;">
                    <i class="fas fa-paperclip" style="margin-right:5px;"></i>Submission Files
                    <span style="font-size:10px;background:#EEF2FF;color:#4F46E5;padding:2px 8px;border-radius:20px;margin-left:6px;font-weight:700;">v{{ $latestSub->version }}</span>
                </p>
                @if($latestSub->note)
                <div style="background:#F8FAFC;border-radius:10px;padding:12px 16px;margin-bottom:14px;border:1px solid #EEF2F8;">
                    <p style="font-size:13px;color:#374151;margin:0;line-height:1.65;font-style:italic;">"{{ $latestSub->note }}"</p>
                </div>
                @endif
                @if($latestSub->file_path)
                @php
                    $fname = $latestSub->original_filename ?? '';
                    $fext  = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                    $furl  = $latestSub->fileUrl();
                    $isImg = in_array($fext, ['jpg','jpeg','png','gif','webp','svg']);
                    $isVid = in_array($fext, ['mp4','mov','avi','mkv','webm']);
                @endphp
                @if($isImg)
                <div style="border-radius:12px;overflow:hidden;border:1.5px solid #DDE3F5;max-height:420px;">
                    <img src="{{ $furl }}" alt="{{ $fname }}" style="width:100%;max-height:420px;object-fit:contain;display:block;background:#F9FAFB;">
                </div>
                @elseif($isVid)
                <div style="border-radius:12px;overflow:hidden;border:1.5px solid #DDE3F5;">
                    <video src="{{ $furl }}" controls style="width:100%;display:block;max-height:360px;background:#000;"></video>
                </div>
                @else
                <a href="{{ $furl }}" download style="display:flex;align-items:center;gap:12px;padding:14px 18px;background:#F8FAFF;border:1.5px solid #DDE3F5;border-radius:12px;text-decoration:none;" onmouseover="this.style.background='#EEF2FF'" onmouseout="this.style.background='#F8FAFF'">
                    <i class="fas fa-file-arrow-down" style="color:#6366F1;font-size:20px;"></i>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#4F46E5;margin:0;">{{ $fname }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Click to download</p>
                    </div>
                </a>
                @endif
                @endif
            </div>
            @endif
        </div>

        {{-- ── Published Post History ── --}}
        @if($task->socialPosts->isNotEmpty())
        <div style="background:#fff;border-radius:18px;border:1px solid #E5E7EB;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;">
            <div style="padding:16px 24px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:10px;">
                <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-circle-check" style="color:#10B981;font-size:13px;"></i>
                </div>
                <div>
                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Published Posts</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $task->socialPosts->count() }} {{ Str::plural('platform', $task->socialPosts->count()) }} recorded</p>
                </div>
            </div>
            <div style="padding:16px 24px;display:flex;flex-direction:column;gap:10px;">
                @foreach($task->socialPosts as $post)
                @php [$pLabel,$pIcon,$pColor,$pBg] = $pMeta[$post->platform] ?? $pMeta['other']; @endphp
                <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;background:#FAFBFF;border:1px solid #F0F2F8;border-radius:12px;">
                    <div style="width:38px;height:38px;border-radius:10px;background:{{ $pBg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(0,0,0,.06);">
                        <i class="fab {{ $pIcon }}" style="color:{{ $pColor }};font-size:17px;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                            <span style="font-size:13px;font-weight:700;color:#111827;">{{ $pLabel }}</span>
                            <span style="font-size:11px;color:#9CA3AF;">{{ $post->created_at->format('M d, Y · H:i') }}</span>
                            @if($post->user)
                            <span style="font-size:11px;color:#6B7280;">by {{ $post->user->name }}</span>
                            @endif
                        </div>
                        @if($post->post_url)
                        <a href="{{ $post->post_url }}" target="_blank" rel="noopener"
                           style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#4F46E5;text-decoration:none;margin-bottom:4px;font-weight:500;background:#EEF2FF;padding:3px 10px;border-radius:20px;"
                           onmouseover="this.style.background='#E0E7FF'" onmouseout="this.style.background='#EEF2FF'">
                            <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Post
                        </a>
                        @endif
                        @if($post->note)
                        <p style="font-size:12px;color:#6B7280;margin:4px 0 0;line-height:1.5;">{{ $post->note }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ── Add New Post Form / Locked State ── --}}
        @if($task->social_posted_at)
        {{-- Locked: already submitted --}}
        <div style="background:#fff;border-radius:18px;border:1px solid #A7F3D0;box-shadow:0 2px 12px rgba(16,185,129,.1);overflow:hidden;">
            <div style="padding:24px;text-align:center;">
                <div style="width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 4px 14px rgba(16,185,129,.2);">
                    <i class="fas fa-circle-check" style="color:#059669;font-size:28px;"></i>
                </div>
                <h3 style="font-size:16px;font-weight:800;color:#065F46;margin:0 0 6px;">Posts Submitted Successfully</h3>
                <p style="font-size:13px;color:#6B7280;margin:0 0 4px;">Posts have been recorded for this task.</p>
                <p style="font-size:12px;color:#9CA3AF;margin:0;">Submitted on {{ $task->social_posted_at->format('M d, Y · H:i') }}</p>
                @if(!in_array(auth()->user()->role, ['admin','manager']))
                <div style="margin-top:18px;padding:12px 16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;display:inline-block;">
                    <p style="font-size:12px;color:#065F46;margin:0;">
                        <i class="fas fa-lock" style="margin-right:5px;font-size:11px;"></i>
                        To make changes, ask your admin to reopen this submission.
                    </p>
                </div>
                @endif
            </div>
        </div>
        @else
        {{-- Open: user can record posts --}}
        <div style="background:#fff;border-radius:18px;border:1px solid #E5E7EB;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;"
             x-data="socialForm()">

            <div style="padding:18px 24px;border-bottom:1px solid #F3F4F6;background:linear-gradient(135deg,#F8F9FF,#fff);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div>
                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">
                        <i class="fas fa-share-nodes" style="color:#6366F1;margin-right:8px;"></i>
                        Record Posts
                    </p>
                    <p style="font-size:12px;color:#9CA3AF;margin:4px 0 0;">Add one or more platforms with their post links</p>
                </div>
                <button type="button" @click="add()"
                        style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#EEF2FF;color:#4F46E5;border:1.5px solid #C7D2FE;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;"
                        onmouseover="this.style.background='#E0E7FF'" onmouseout="this.style.background='#EEF2FF'">
                    <i class="fas fa-plus" style="font-size:10px;"></i> Add Another Platform
                </button>
            </div>

            <form method="POST" action="{{ route('social.add-post', $task) }}" style="padding:20px 24px 24px;">
                @csrf

                @if($errors->any())
                <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#991B1B;">
                    <i class="fas fa-circle-exclamation" style="margin-right:6px;"></i>{{ $errors->first() }}
                </div>
                @endif

                <div style="display:flex;flex-direction:column;gap:20px;">
                    <template x-for="(entry, idx) in entries" :key="idx">
                        <div :style="entries.length > 1 ? 'background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:14px;padding:18px 18px 16px;' : ''">

                            <div x-show="entries.length > 1" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                                <span style="font-size:12px;font-weight:700;color:#6366F1;text-transform:uppercase;letter-spacing:.05em;"
                                      x-text="'Platform #' + (idx + 1)"></span>
                                <button type="button" @click="remove(idx)"
                                        style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#EF4444;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:4px 10px;cursor:pointer;"
                                        onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                                    <i class="fas fa-trash-can" style="font-size:10px;"></i> Remove
                                </button>
                            </div>

                            <div style="margin-bottom:16px;">
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:10px;">
                                    Platform <span style="color:#EF4444;">*</span>
                                </label>
                                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
                                    <template x-for="[key, meta] in Object.entries(platforms)" :key="key">
                                        <button type="button"
                                                @click="entry.platform = key"
                                                :style="entry.platform === key
                                                    ? `border:2.5px solid ${meta.color};background:${meta.bg};border-radius:12px;padding:13px 8px;text-align:center;cursor:pointer;transition:all .15s;box-shadow:0 2px 8px rgba(0,0,0,.1);`
                                                    : 'border:1.5px solid #E5E7EB;background:#FAFAFA;border-radius:12px;padding:13px 8px;text-align:center;cursor:pointer;transition:all .15s;'"
                                                @mouseover="if(entry.platform !== key){ $el.style.borderColor = meta.color; $el.style.background = meta.bg; }"
                                                @mouseout="if(entry.platform !== key){ $el.style.borderColor = '#E5E7EB'; $el.style.background = '#FAFAFA'; }">
                                            <i :class="'fab ' + meta.icon"
                                               :style="'font-size:24px;color:' + meta.color + ';display:block;margin-bottom:6px;'"></i>
                                            <span x-text="meta.label" style="font-size:11px;font-weight:600;color:#374151;white-space:nowrap;"></span>
                                        </button>
                                    </template>
                                </div>
                                <input type="hidden" :name="'platform[' + idx + ']'" x-model="entry.platform">
                                <p x-show="submitAttempted && !entry.platform" style="margin:6px 0 0;font-size:11px;color:#EF4444;" x-cloak>
                                    <i class="fas fa-circle-exclamation" style="margin-right:3px;"></i>Please select a platform
                                </p>
                            </div>

                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                    Post Link <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— optional but recommended</span>
                                </label>
                                <div style="position:relative;">
                                    <i class="fas fa-link" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;pointer-events:none;"></i>
                                    <input type="url" :name="'post_url[' + idx + ']'" x-model="entry.url"
                                           placeholder="https://www.facebook.com/your-post-link"
                                           style="width:100%;padding:10px 14px 10px 34px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;background:#fff;"
                                           onfocus="this.style.borderColor='#6366F1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,.1)'"
                                           onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none'">
                                </div>
                            </div>

                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                    Note <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— optional</span>
                                </label>
                                <textarea :name="'note[' + idx + ']'" x-model="entry.note" rows="2"
                                          placeholder="Any extra info (e.g. boosted, scheduled, reached X people)..."
                                          style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;background:#fff;"
                                          onfocus="this.style.borderColor='#6366F1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,.1)'"
                                          onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none'"></textarea>
                            </div>

                        </div>
                    </template>
                </div>

                <button type="button" @click="add()"
                        style="display:flex;align-items:center;gap:8px;width:100%;margin-top:14px;padding:12px;border:1.5px dashed #C7D2FE;border-radius:12px;background:transparent;color:#6366F1;font-size:13px;font-weight:600;cursor:pointer;justify-content:center;transition:background .15s;"
                        onmouseover="this.style.background='#EEF2FF'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-plus" style="font-size:11px;"></i> Add Another Platform
                </button>

                <div style="margin-top:22px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <button type="submit"
                            @click="submitAttempted = true"
                            :disabled="!canSubmit"
                            :style="canSubmit
                                ? 'display:inline-flex;align-items:center;gap:9px;padding:12px 28px;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(99,102,241,.35);'
                                : 'display:inline-flex;align-items:center;gap:9px;padding:12px 28px;background:#E5E7EB;color:#9CA3AF;border:none;border-radius:12px;font-size:14px;font-weight:700;cursor:not-allowed;'"
                            onmouseover="if(!this.disabled){ this.style.opacity='.9'; this.style.transform='translateY(-1px)'; }"
                            onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)'">
                        <i class="fas fa-circle-check" style="font-size:15px;"></i>
                        <span x-text="entries.length > 1 ? 'Record ' + entries.length + ' Posts' : 'Record Post'"></span>
                    </button>
                    <span x-show="!canSubmit" style="font-size:12px;color:#9CA3AF;" x-cloak>
                        Select a platform for each entry
                    </span>
                </div>
            </form>
        </div>
        @endif

    </div>{{-- /left --}}

    {{-- RIGHT COLUMN (sidebar) --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- ── Project Card ── --}}
        @if($proj)
        @php [$projStatusLabel,$projStatusBg,$projStatusColor] = $projStatusMap[$proj->status] ?? [ucfirst($proj->status),'#F3F4F6','#6B7280']; @endphp
        <div style="background:#fff;border-radius:18px;border:1px solid #E5E7EB;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #F3F4F6;background:linear-gradient(135deg,#F0FDF4,#fff);">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:2px;">
                    <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#059669,#10B981);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-diagram-project" style="color:#fff;font-size:13px;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0;">Project</p>
                        <h3 style="font-size:14px;font-weight:800;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->name }}</h3>
                    </div>
                </div>
            </div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;">

                {{-- Status --}}
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:12px;color:#6B7280;">Status</span>
                    <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:{{ $projStatusBg }};color:{{ $projStatusColor }};">{{ $projStatusLabel }}</span>
                </div>

                {{-- Deadline --}}
                @if($proj->deadline)
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:12px;color:#6B7280;">Deadline</span>
                    <span style="font-size:12px;font-weight:600;color:#111827;">{{ $proj->deadline->format('M d, Y') }}</span>
                </div>
                @endif

                {{-- Description --}}
                @if($proj->description)
                <div style="background:#F9FAFB;border-radius:10px;padding:10px 12px;border:1px solid #F0F0F0;">
                    <p style="font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 5px;">About</p>
                    <p style="font-size:12px;color:#374151;margin:0;line-height:1.6;">{{ Str::limit($proj->description, 160) }}</p>
                </div>
                @endif

                {{-- Progress --}}
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <span style="font-size:12px;color:#6B7280;">Progress</span>
                        <span style="font-size:12px;font-weight:700;color:#059669;">{{ $projectProgress }}%</span>
                    </div>
                    <div style="height:7px;background:#F0F0F0;border-radius:999px;overflow:hidden;">
                        <div style="height:100%;width:{{ $projectProgress }}%;background:linear-gradient(90deg,#059669,#10B981);border-radius:999px;transition:width .6s;"></div>
                    </div>
                    <p style="font-size:11px;color:#9CA3AF;margin:5px 0 0;">{{ $projectCompletedCount }} / {{ $projectTaskCount }} tasks completed</p>
                </div>

                {{-- Members --}}
                @if($proj->members->isNotEmpty())
                <div>
                    <p style="font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 8px;">Team Members</p>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach($proj->members->take(8) as $member)
                        <div title="{{ $member->name }}"
                             style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#fff;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.12);">
                            {{ strtoupper(substr($member->name,0,1)) }}
                        </div>
                        @endforeach
                        @if($proj->members->count() > 8)
                        <div style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#6B7280;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.08);">
                            +{{ $proj->members->count() - 8 }}
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>
        </div>
        @endif

        {{-- ── Social Assignee Card ── --}}
        @if($task->socialAssignee)
        <div style="background:#fff;border-radius:18px;border:1px solid #E5E7EB;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:16px 20px;">
            <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;">
                <i class="fas fa-share-nodes" style="margin-right:4px;color:#6366F1;"></i>Social Media Handler
            </p>
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:#fff;flex-shrink:0;">
                    {{ strtoupper(substr($task->socialAssignee->name,0,1)) }}
                </div>
                <div>
                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">{{ $task->socialAssignee->name }}</p>
                    @if($task->socialAssignee->job_title)
                    <p style="font-size:12px;color:#9CA3AF;margin:1px 0 0;">{{ $task->socialAssignee->job_title }}</p>
                    @endif
                </div>
            </div>
            @if($task->social_posted_at)
            <div style="margin-top:12px;padding:8px 12px;background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border-radius:10px;border:1px solid #A7F3D0;">
                <p style="font-size:11px;font-weight:600;color:#065F46;margin:0;">
                    <i class="fas fa-circle-check" style="margin-right:4px;"></i>
                    First posted {{ $task->social_posted_at->format('M d, Y') }}
                </p>
            </div>
            @endif
        </div>
        @endif

        {{-- ── Multi-Assignee list ── --}}
        @if($task->assignees->isNotEmpty())
        <div style="background:#fff;border-radius:18px;border:1px solid #E5E7EB;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:16px 20px;">
            <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;">
                <i class="fas fa-users" style="margin-right:4px;color:#6366F1;"></i>Task Assignees
            </p>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($task->assignees as $a)
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#0E7490,#0891B2);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#fff;flex-shrink:0;">
                        {{ strtoupper(substr($a->name,0,1)) }}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:12px;font-weight:600;color:#111827;margin:0;">{{ $a->name }}</p>
                        @if($a->pivot->role_in_task)
                        <p style="font-size:10px;color:#9CA3AF;margin:0;">{{ ucfirst($a->pivot->role_in_task) }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>{{-- /right --}}

</div>{{-- /grid --}}

@push('scripts')
<script>
function socialForm() {
    return {
        entries: [{ platform: '', url: '', note: '' }],
        submitAttempted: false,
        platforms: {
            facebook:  { label: 'Facebook',    icon: 'fa-facebook',    color: '#1877F2', bg: '#EBF3FF' },
            instagram: { label: 'Instagram',   icon: 'fa-instagram',   color: '#E1306C', bg: '#FFF0F5' },
            twitter:   { label: 'Twitter / X', icon: 'fa-x-twitter',   color: '#000000', bg: '#F5F5F5' },
            linkedin:  { label: 'LinkedIn',    icon: 'fa-linkedin',    color: '#0A66C2', bg: '#EAF2FB' },
            tiktok:    { label: 'TikTok',      icon: 'fa-tiktok',      color: '#010101', bg: '#F5F5F5' },
            youtube:   { label: 'YouTube',     icon: 'fa-youtube',     color: '#FF0000', bg: '#FFF0F0' },
            snapchat:  { label: 'Snapchat',    icon: 'fa-snapchat',    color: '#F7CA00', bg: '#FFFDE7' },
            other:     { label: 'Other',       icon: 'fa-share-nodes', color: '#6366F1', bg: '#EEF2FF' },
        },
        add() { this.entries.push({ platform: '', url: '', note: '' }); },
        remove(idx) { this.entries.splice(idx, 1); },
        get canSubmit() { return this.entries.every(e => e.platform !== ''); },
    };
}
</script>
@endpush

@endsection

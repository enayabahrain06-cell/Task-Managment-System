@extends('layouts.app')
@section('title', 'Offboard — ' . $user->name)

@section('content')
<div style="max-width:860px;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('admin.users.index') }}"
           style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:10px;background:#F3F4F6;color:#6B7280;text-decoration:none;flex-shrink:0;">
            <i class="fa fa-arrow-left" style="font-size:13px;"></i>
        </a>
        <div>
            <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">Offboard User</h1>
            <p style="font-size:13px;color:#9CA3AF;margin:2px 0 0;">Manage account access and task handover for {{ $user->name }}</p>
        </div>
    </div>

    {{-- Validation errors --}}
    @if ($errors->any())
    <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:14px 18px;margin-bottom:20px;">
        <p style="font-size:13px;font-weight:600;color:#991B1B;margin:0 0 6px;"><i class="fa fa-circle-exclamation" style="margin-right:6px;"></i>Please fix the following:</p>
        <ul style="margin:0;padding-left:20px;">
            @foreach ($errors->all() as $error)
            <li style="font-size:13px;color:#B91C1C;">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Warning banner --}}
    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;gap:14px;align-items:flex-start;">
        <div style="width:38px;height:38px;border-radius:10px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
            <i class="fa fa-triangle-exclamation" style="color:#D97706;font-size:15px;"></i>
        </div>
        <div>
            <p style="font-size:14px;font-weight:700;color:#92400E;margin:0 0 4px;">You are about to offboard {{ $user->name }}</p>
            <p style="font-size:13px;color:#B45309;margin:0;line-height:1.6;">
                All completed tasks, comments, uploads, and productivity history remain permanently stored under this profile.
                Only unfinished tasks can be transferred to a replacement.
                The transferred tasks keep their full previous history — comments, uploads, review notes, and status timeline.
                This action is logged with timestamp, performer, and reason.
            </p>
        </div>
    </div>

    {{-- User profile card --}}
    @php
        $avatarColors   = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6'];
        $roleColors     = ['admin'=>['#DC2626','#FEE2E2'],'manager'=>['#D97706','#FEF3C7'],'user'=>['#059669','#D1FAE5']];
        [$rc, $rb]      = $roleColors[$user->role] ?? ['#6366F1','#EEF2FF'];
        $doneStatuses   = ['approved','delivered','archived'];
        $completedCount = \App\Models\Task::where('assigned_to', $user->id)->whereIn('status', $doneStatuses)->count();
        $totalTasks     = \App\Models\Task::where('assigned_to', $user->id)->count();
    @endphp

    <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:20px;margin-bottom:20px;display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
        @if($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar))
        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}"
             style="width:58px;height:58px;border-radius:50%;object-fit:cover;border:2px solid #E5E7EB;flex-shrink:0;">
        @else
        <div style="width:58px;height:58px;border-radius:50%;background:{{ $avatarColors[$user->id % count($avatarColors)] }};display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff;flex-shrink:0;">
            {{ strtoupper(substr($user->name,0,1)) }}
        </div>
        @endif
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
                <p style="font-size:16px;font-weight:700;color:#111827;margin:0;">{{ $user->name }}</p>
                <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:10px;background:{{ $rb }};color:{{ $rc }};">{{ ucfirst($user->role) }}</span>
                <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:10px;background:#FEF3C7;color:#D97706;">{{ ucfirst($user->status) }}</span>
            </div>
            <p style="font-size:13px;color:#6B7280;margin:0;">{{ $user->email }}{{ $user->job_title ? ' · ' . $user->job_title : '' }}</p>
            <p style="font-size:12px;color:#9CA3AF;margin:3px 0 0;">Member since {{ $user->created_at->format('M d, Y') }}</p>
        </div>
        <div style="display:flex;gap:20px;flex-shrink:0;text-align:center;">
            <div>
                <p style="font-size:24px;font-weight:700;color:#DC2626;margin:0;line-height:1;">{{ $unfinishedTasks->count() }}</p>
                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Unfinished</p>
            </div>
            <div>
                <p style="font-size:24px;font-weight:700;color:#059669;margin:0;line-height:1;">{{ $completedCount }}</p>
                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Completed</p>
            </div>
            <div>
                <p style="font-size:24px;font-weight:700;color:#6366F1;margin:0;line-height:1;">{{ $totalTasks }}</p>
                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Total Tasks</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.offboard.process', $user) }}" id="offboardForm">
        @csrf

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;" class="offboard-grid">

            {{-- Left: Action + Reason --}}
            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Action selection --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:20px;"
                     x-data="{ action: '{{ old('action', 'archive') }}' }">
                    <p style="font-size:11px;font-weight:700;color:#9CA3AF;margin:0 0 14px;text-transform:uppercase;letter-spacing:.06em;">Action</p>

                    <label @click="action='archive'"
                           :style="action==='archive' ? 'border-color:#EF4444;background:#FFF5F5;' : 'border-color:#E5E7EB;background:#fff;'"
                           style="display:flex;gap:12px;padding:14px;border-radius:10px;border:2px solid #E5E7EB;cursor:pointer;margin-bottom:10px;transition:all .15s;">
                        <input type="radio" name="action" value="archive" x-model="action"
                               style="margin-top:2px;accent-color:#EF4444;flex-shrink:0;">
                        <div>
                            <p style="font-size:13px;font-weight:700;color:#DC2626;margin:0 0 3px;">
                                <i class="fa fa-box-archive" style="margin-right:5px;"></i>Archive (Permanent)
                            </p>
                            <p style="font-size:12px;color:#6B7280;margin:0;line-height:1.5;">Account is permanently disabled. User can never sign in again. All records and history are preserved exactly as-is.</p>
                        </div>
                    </label>

                    <label @click="action='deactivate'"
                           :style="action==='deactivate' ? 'border-color:#F59E0B;background:#FFFBEB;' : 'border-color:#E5E7EB;background:#fff;'"
                           style="display:flex;gap:12px;padding:14px;border-radius:10px;border:2px solid #E5E7EB;cursor:pointer;transition:all .15s;">
                        <input type="radio" name="action" value="deactivate" x-model="action"
                               style="margin-top:2px;accent-color:#D97706;flex-shrink:0;">
                        <div>
                            <p style="font-size:13px;font-weight:700;color:#D97706;margin:0 0 3px;">
                                <i class="fa fa-user-lock" style="margin-right:5px;"></i>Deactivate (Temporary)
                            </p>
                            <p style="font-size:12px;color:#6B7280;margin:0;line-height:1.5;">Account is suspended and can be reactivated. User cannot sign in while deactivated.</p>
                        </div>
                    </label>
                </div>

                {{-- Reason --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:20px;flex:1;">
                    <label style="font-size:11px;font-weight:700;color:#9CA3AF;display:block;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">
                        Reason for Offboarding <span style="color:#EF4444;">*</span>
                    </label>
                    <textarea name="reason" rows="6" required minlength="10" maxlength="1000"
                              placeholder="e.g. Employee resigned on April 19, 2026. All client design tasks must be handed to the replacement. New hire starts May 1st..."
                              style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('reason') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:13px;color:#374151;resize:vertical;outline:none;font-family:inherit;line-height:1.6;box-sizing:border-box;min-height:120px;">{{ old('reason') }}</textarea>
                    @error('reason')
                    <p style="font-size:12px;color:#EF4444;margin:4px 0 0;">{{ $message }}</p>
                    @enderror
                    <p style="font-size:11px;color:#9CA3AF;margin:8px 0 0;">
                        <i class="fa fa-lock" style="margin-right:4px;"></i>
                        Recorded in the system audit trail with performer name, timestamp, and all transferred task IDs.
                    </p>
                </div>

            </div>

            {{-- Right: Task Handover --}}
            <div>
                <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:20px;height:100%;box-sizing:border-box;display:flex;flex-direction:column;gap:14px;">
                    <p style="font-size:11px;font-weight:700;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.06em;">Task Handover</p>

                    @if($unfinishedTasks->isEmpty())
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px 0;">
                        <div style="width:48px;height:48px;border-radius:50%;background:#D1FAE5;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="fa fa-circle-check" style="color:#059669;font-size:20px;"></i>
                        </div>
                        <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">No unfinished tasks</p>
                        <p style="font-size:13px;color:#9CA3AF;margin:0;">All tasks are completed or delivered — no transfer needed.</p>
                    </div>
                    @else

                    <div style="background:#FEF3C7;border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
                        <i class="fa fa-triangle-exclamation" style="color:#D97706;flex-shrink:0;"></i>
                        <p style="font-size:12px;color:#92400E;margin:0;">
                            {{ $unfinishedTasks->count() }} unfinished {{ Str::plural('task', $unfinishedTasks->count()) }} will be left unassigned unless you select a recipient below.
                        </p>
                    </div>

                    {{-- Task list --}}
                    @php
                    $statusMap = [
                        'draft'               => ['Draft',             '#6B7280','#F3F4F6'],
                        'assigned'            => ['Assigned',          '#4F46E5','#EEF2FF'],
                        'viewed'              => ['Viewed',            '#0284C7','#E0F2FE'],
                        'in_progress'         => ['In Progress',       '#D97706','#FEF3C7'],
                        'submitted'           => ['In Review',         '#7C3AED','#EDE9FE'],
                        'revision_requested'  => ['Revision Needed',   '#DC2626','#FEE2E2'],
                    ];
                    @endphp

                    <div style="max-height:200px;overflow-y:auto;border:1px solid #F3F4F6;border-radius:10px;flex-shrink:0;">
                        @foreach($unfinishedTasks as $task)
                        @php [$sl,$sc,$sb] = $statusMap[$task->status] ?? [ucwords(str_replace('_',' ',$task->status)),'#6366F1','#EEF2FF']; @endphp
                        <div style="padding:10px 14px;border-bottom:1px solid #F9FAFB;display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                            <div style="min-width:0;flex:1;">
                                <p style="font-size:12px;font-weight:600;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $task->title }}</p>
                                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">
                                    {{ $task->project->name ?? '—' }}
                                    @if($task->deadline) · due {{ $task->deadline->format('M d') }} @endif
                                    @if($task->assignees->count() > 1)
                                    · <span style="color:#6366F1;">{{ $task->assignees->count() }} assignees</span>
                                    @endif
                                </p>
                            </div>
                            <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:8px;background:{{ $sb }};color:{{ $sc }};white-space:nowrap;flex-shrink:0;">{{ $sl }}</span>
                        </div>
                        @endforeach
                    </div>

                    {{-- Recipient selector --}}
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                            Transfer all unfinished tasks to
                        </label>
                        <select name="to_user_id"
                                style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('to_user_id') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:13px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                            <option value="">— Leave unassigned —</option>
                            @foreach($recipients as $r)
                            <option value="{{ $r->id }}" {{ old('to_user_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }}{{ $r->job_title ? ' — ' . $r->job_title : '' }} ({{ ucfirst($r->role) }})
                            </option>
                            @endforeach
                        </select>
                        @error('to_user_id')
                        <p style="font-size:12px;color:#EF4444;margin:4px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Replacement user tip --}}
                    <div style="background:#EEF2FF;border-radius:10px;padding:12px 14px;">
                        <p style="font-size:12px;color:#4338CA;margin:0;line-height:1.6;">
                            <i class="fa fa-circle-info" style="margin-right:5px;"></i>
                            Need to create a replacement account first?
                            <a href="{{ route('admin.users.create') }}" target="_blank"
                               style="font-weight:700;color:#4338CA;text-decoration:underline;">
                                Create new user <i class="fa fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                            </a>
                            — then come back and select them above.
                        </p>
                        <p style="font-size:11px;color:#6366F1;margin:6px 0 0;">
                            <i class="fa fa-shield-halved" style="margin-right:4px;"></i>
                            The replacement will see the full task history (past comments, uploads, review notes) but their own productivity metrics start fresh.
                        </p>
                    </div>

                    @endif
                </div>
            </div>
        </div>

        {{-- What is preserved --}}
        <div style="background:#F0FDF4;border:1px solid #A7F3D0;border-radius:12px;padding:18px 20px;margin-bottom:16px;">
            <p style="font-size:13px;font-weight:700;color:#065F46;margin:0 0 12px;display:flex;align-items:center;gap:7px;">
                <i class="fa fa-shield-halved" style="font-size:14px;"></i> What is preserved after offboarding
            </p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;">
                @foreach([
                    ['fa-circle-check',     'All completed & delivered tasks stay under this profile'],
                    ['fa-comment',          'Comments and review notes are kept intact on every task'],
                    ['fa-paperclip',        'Uploaded files and attachments are retained'],
                    ['fa-chart-bar',        'Productivity metrics stay attributed to this user'],
                    ['fa-clock-rotate-left','Transferred tasks keep full status & comment history'],
                    ['fa-user',             'Profile identity, name, and reporting history are untouched'],
                    ['fa-lock',             'Transfer is logged: who did it, when, and why'],
                    ['fa-user-plus',        'Replacement employee gets a clean productivity slate'],
                ] as [$icon, $text])
                <div style="display:flex;align-items:flex-start;gap:8px;">
                    <i class="fa {{ $icon }}" style="color:#059669;font-size:11px;margin-top:2px;flex-shrink:0;"></i>
                    <span style="font-size:12px;color:#047857;line-height:1.4;">{{ $text }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Confirmation + Submit --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;"
             x-data="{ confirmed: false }">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" x-model="confirmed"
                       style="width:18px;height:18px;accent-color:#6366F1;flex-shrink:0;">
                <span style="font-size:13px;color:#374151;line-height:1.5;">
                    I understand this action is irreversible for archived accounts and will immediately revoke
                    <strong>{{ $user->name }}</strong>'s access to the system.
                </span>
            </label>
            <div style="display:flex;gap:10px;flex-shrink:0;">
                <a href="{{ route('admin.users.index') }}"
                   style="padding:10px 22px;background:#F3F4F6;color:#374151;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">
                    Cancel
                </a>
                <button type="submit" :disabled="!confirmed"
                        :style="!confirmed ? 'opacity:0.5;cursor:not-allowed;' : ''"
                        style="padding:10px 28px;background:#DC2626;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;">
                    <i class="fa fa-user-slash"></i> Proceed with Offboarding
                </button>
            </div>
        </div>

    </form>
</div>

<style>
@media (max-width:700px) {
    .offboard-grid { grid-template-columns: 1fr !important; }
}
</style>
@endsection

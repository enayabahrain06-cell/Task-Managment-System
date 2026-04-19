@extends('layouts.app')
@section('title', 'Offboard User — ' . $user->name)

@section('content')
<div class="max-w-3xl">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 transition">
            <i class="fa fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Offboard User</h1>
            <p class="text-sm text-gray-400 mt-0.5">Deactivate or archive {{ $user->name }} and manage their task handover</p>
        </div>
    </div>

    @if ($errors->any())
    <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:14px 18px;margin-bottom:20px;">
        <p style="font-size:13px;font-weight:600;color:#991B1B;margin:0 0 6px;"><i class="fa fa-circle-exclamation mr-2"></i>Please fix the following:</p>
        <ul style="margin:0;padding-left:20px;">
            @foreach ($errors->all() as $error)
            <li style="font-size:13px;color:#B91C1C;">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Warning banner --}}
    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;gap:14px;align-items:flex-start;">
        <div style="width:38px;height:38px;border-radius:10px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa fa-triangle-exclamation" style="color:#D97706;font-size:16px;"></i>
        </div>
        <div>
            <p style="font-size:14px;font-weight:700;color:#92400E;margin:0 0 4px;">You are about to offboard {{ $user->name }}</p>
            <p style="font-size:13px;color:#B45309;margin:0;line-height:1.6;">
                All completed tasks, comments, uploads, and historical data will remain stored under this profile.
                Only unfinished tasks can be transferred to a replacement user.
                This action is logged.
            </p>
        </div>
    </div>

    {{-- User profile card --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;margin-bottom:20px;display:flex;gap:16px;align-items:center;">
        @if($user->avatarUrl())
        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}"
             style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid #E5E7EB;flex-shrink:0;">
        @else
        @php $avatarColors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6']; @endphp
        <div style="width:56px;height:56px;border-radius:50%;background:{{ $avatarColors[$user->id % count($avatarColors)] }};display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#fff;flex-shrink:0;">
            {{ strtoupper(substr($user->name,0,1)) }}
        </div>
        @endif
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <p style="font-size:16px;font-weight:700;color:#111827;margin:0;">{{ $user->name }}</p>
                @php
                    $roleColors = ['admin'=>['#DC2626','#FEE2E2'],'manager'=>['#D97706','#FEF3C7'],'user'=>['#059669','#D1FAE5']];
                    [$rc,$rb] = $roleColors[$user->role] ?? ['#6366F1','#EEF2FF'];
                @endphp
                <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:10px;background:{{ $rb }};color:{{ $rc }};">{{ ucfirst($user->role) }}</span>
                <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:10px;background:#FEF3C7;color:#D97706;">{{ ucfirst($user->status) }}</span>
            </div>
            <p style="font-size:13px;color:#6B7280;margin:4px 0 0;">{{ $user->email }}{{ $user->job_title ? ' · ' . $user->job_title : '' }}</p>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Member since {{ $user->created_at->format('M d, Y') }}</p>
        </div>
        <div style="text-align:right;flex-shrink:0;">
            @php
                $completedCount = \App\Models\Task::where('assigned_to', $user->id)->whereIn('status',['completed','delivered'])->count();
            @endphp
            <p style="font-size:22px;font-weight:700;color:#111827;margin:0;">{{ $unfinishedTasks->count() }}</p>
            <p style="font-size:11px;color:#9CA3AF;margin:0;">unfinished tasks</p>
            <p style="font-size:11px;color:#6B7280;margin:4px 0 0;">{{ $completedCount }} completed</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.offboard.process', $user) }}" id="offboardForm">
        @csrf

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;" class="offboard-grid">

            {{-- Left: Action + Reason --}}
            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Action selection --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
                    <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:.04em;">Action</p>

                    <label style="display:flex;gap:12px;padding:14px;border-radius:10px;border:2px solid {{ old('action')==='archive' ? '#EF4444' : '#E5E7EB' }};cursor:pointer;margin-bottom:10px;transition:border-color .15s;"
                           x-data x-on:click="$el.style.borderColor='#EF4444';document.getElementById('action_deactivate_label').style.borderColor='#E5E7EB'">
                        <input type="radio" name="action" value="archive" id="action_archive"
                               {{ old('action','archive')==='archive' ? 'checked' : '' }}
                               style="margin-top:2px;accent-color:#EF4444;flex-shrink:0;">
                        <div>
                            <p style="font-size:13px;font-weight:700;color:#DC2626;margin:0 0 3px;"><i class="fa fa-box-archive mr-1.5"></i>Archive (Permanent)</p>
                            <p style="font-size:12px;color:#6B7280;margin:0;line-height:1.5;">Account is permanently disabled. The user can never sign in again. All records are preserved for reporting history.</p>
                        </div>
                    </label>

                    <label id="action_deactivate_label"
                           style="display:flex;gap:12px;padding:14px;border-radius:10px;border:2px solid {{ old('action')==='deactivate' ? '#D97706' : '#E5E7EB' }};cursor:pointer;transition:border-color .15s;"
                           x-data x-on:click="$el.style.borderColor='#D97706';document.getElementById('action_archive').closest('label').style.borderColor='#E5E7EB'">
                        <input type="radio" name="action" value="deactivate" id="action_deactivate"
                               {{ old('action')==='deactivate' ? 'checked' : '' }}
                               style="margin-top:2px;accent-color:#D97706;flex-shrink:0;">
                        <div>
                            <p style="font-size:13px;font-weight:700;color:#D97706;margin:0 0 3px;"><i class="fa fa-user-lock mr-1.5"></i>Deactivate (Temporary)</p>
                            <p style="font-size:12px;color:#6B7280;margin:0;line-height:1.5;">Account is suspended but can be reactivated later. The user cannot sign in while deactivated.</p>
                        </div>
                    </label>
                </div>

                {{-- Reason --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;">
                    <label style="font-size:13px;font-weight:600;color:#374151;display:block;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">
                        Reason <span style="color:#EF4444;">*</span>
                    </label>
                    <textarea name="reason" rows="5" required minlength="10" maxlength="1000"
                              placeholder="e.g. Employee resigned on April 18, 2026. Replacement will be hired next month. All client tasks need to be transferred to the team lead..."
                              style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('reason') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:13px;color:#374151;resize:vertical;outline:none;font-family:inherit;line-height:1.6;box-sizing:border-box;">{{ old('reason') }}</textarea>
                    @error('reason')
                    <p style="font-size:12px;color:#EF4444;margin:4px 0 0;">{{ $message }}</p>
                    @enderror
                    <p style="font-size:11px;color:#9CA3AF;margin:6px 0 0;">This is logged in the system audit trail alongside who performed the action and when.</p>
                </div>

            </div>

            {{-- Right: Task Transfer --}}
            <div>
                <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;height:100%;box-sizing:border-box;">
                    <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:.04em;">Task Handover</p>

                    @if($unfinishedTasks->isEmpty())
                    <div style="text-align:center;padding:30px 0;">
                        <div style="width:48px;height:48px;border-radius:50%;background:#D1FAE5;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="fa fa-circle-check" style="color:#059669;font-size:20px;"></i>
                        </div>
                        <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">No unfinished tasks</p>
                        <p style="font-size:13px;color:#9CA3AF;margin:0;">All tasks are completed or delivered. No transfer needed.</p>
                    </div>
                    @else

                    <div style="background:#FEF3C7;border-radius:10px;padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;gap:10px;">
                        <i class="fa fa-triangle-exclamation" style="color:#D97706;"></i>
                        <p style="font-size:12px;color:#92400E;margin:0;">{{ $unfinishedTasks->count() }} unfinished {{ Str::plural('task', $unfinishedTasks->count()) }} will be left unassigned unless you select a recipient below.</p>
                    </div>

                    {{-- Task list --}}
                    <div style="max-height:180px;overflow-y:auto;margin-bottom:14px;border:1px solid #F3F4F6;border-radius:10px;">
                        @foreach($unfinishedTasks as $task)
                        @php
                            $statusColors = ['pending'=>['#6B7280','#F3F4F6'],'in_progress'=>['#D97706','#FEF3C7'],'pending_approval'=>['#7C3AED','#EDE9FE']];
                            [$sc,$sb] = $statusColors[$task->status] ?? ['#6366F1','#EEF2FF'];
                        @endphp
                        <div style="padding:10px 14px;border-bottom:1px solid #F9FAFB;display:flex;justify-content:space-between;align-items:center;gap:10px;">
                            <div style="min-width:0;">
                                <p style="font-size:12px;font-weight:600;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $task->title }}</p>
                                <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">{{ $task->project->name ?? '—' }} · due {{ $task->deadline->format('M d') }}</p>
                            </div>
                            <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:8px;background:{{ $sb }};color:{{ $sc }};white-space:nowrap;flex-shrink:0;">{{ ucwords(str_replace('_',' ',$task->status)) }}</span>
                        </div>
                        @endforeach
                    </div>

                    {{-- Recipient selector --}}
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Transfer tasks to</label>
                        <select name="to_user_id"
                                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                            <option value="">— Leave unassigned —</option>
                            @foreach($recipients as $r)
                            <option value="{{ $r->id }}" {{ old('to_user_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }} ({{ ucfirst($r->role) }}){{ $r->job_title ? ' — ' . $r->job_title : '' }}
                            </option>
                            @endforeach
                        </select>
                        @error('to_user_id')
                        <p style="font-size:12px;color:#EF4444;margin:4px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Note about creating a replacement user --}}
                    <div style="background:#EEF2FF;border-radius:10px;padding:10px 14px;margin-top:12px;">
                        <p style="font-size:12px;color:#4F46E5;margin:0;">
                            <i class="fa fa-circle-info mr-1.5"></i>
                            Need to onboard a replacement first?
                            <a href="{{ route('admin.users.create') }}" target="_blank"
                               style="font-weight:600;color:#4338CA;text-decoration:underline;">Create new user <i class="fa fa-arrow-up-right-from-square text-xs"></i></a>
                            — then come back and select them above.
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Data preservation notice --}}
        <div style="background:#F0FDF4;border:1px solid #A7F3D0;border-radius:12px;padding:16px 20px;margin-bottom:20px;">
            <p style="font-size:13px;font-weight:600;color:#065F46;margin:0 0 8px;"><i class="fa fa-shield-halved mr-2"></i>What is preserved</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                @foreach([
                    ['fa-circle-check','All completed tasks remain under this profile'],
                    ['fa-comment','Comments and review notes are kept intact'],
                    ['fa-paperclip','Uploaded files and artwork are retained'],
                    ['fa-chart-bar','Productivity metrics stay attributed to this user'],
                    ['fa-clock-rotate-left','Full task history is viewable on transferred tasks'],
                    ['fa-user','Profile, identity, and reporting history are untouched'],
                ] as [$icon,$text])
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa {{ $icon }}" style="color:#059669;font-size:11px;flex-shrink:0;"></i>
                    <span style="font-size:12px;color:#047857;">{{ $text }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Confirmation + Submit --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F3F4F6;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;" x-data="{confirmed:false}">
                <input type="checkbox" id="confirmCheck" required
                       style="width:18px;height:18px;accent-color:#6366F1;flex-shrink:0;">
                <span style="font-size:13px;color:#374151;line-height:1.5;">
                    I understand this action is logged and will affect {{ $user->name }}'s account access immediately.
                </span>
            </label>
            <div style="display:flex;gap:10px;flex-shrink:0;">
                <a href="{{ route('admin.users.index') }}"
                   style="padding:10px 22px;background:#F3F4F6;color:#374151;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:background .15s;">
                    Cancel
                </a>
                <button type="submit"
                        onclick="return document.getElementById('confirmCheck').checked || (alert('Please confirm you understand the consequences.'), false)"
                        style="padding:10px 28px;background:#DC2626;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:background .15s;">
                    <i class="fa fa-user-slash mr-2"></i>Proceed with Offboarding
                </button>
            </div>
        </div>

    </form>

</div>

<style>
@media (max-width: 680px) {
    .offboard-grid { grid-template-columns: 1fr !important; }
}
</style>
@endsection

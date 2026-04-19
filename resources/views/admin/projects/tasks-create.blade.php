@extends('layouts.app')
@section('title', 'Add Task — ' . $project->name)

@section('content')
<div style="max-width:660px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('admin.projects.show', $project) }}"
           style="width:34px;height:34px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;">
            <i class="fa fa-arrow-left" style="font-size:13px;"></i>
        </a>
        <div>
            <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">Add Task</h1>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">{{ $project->name }}</p>
        </div>
    </div>

    <div x-data="{
        allUsers: {{ $members->map(fn($u) => ['id' => (string)$u->id, 'name' => $u->name, 'role' => ucfirst($u->role)])->toJson() }},
        assignees: [{ user_id: '', role: '' }],
        addAssignee() { this.assignees.push({ user_id: '', role: '' }); },
        removeAssignee(i) { if (this.assignees.length > 1) this.assignees.splice(i, 1); }
    }"
    style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:28px;">

        <form method="POST" action="{{ route('admin.projects.tasks.store', $project) }}">
            @csrf

            {{-- Title --}}
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Task Title <span style="color:#EF4444;">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Design landing page"
                       style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('title') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                @error('title')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            {{-- Task Type + Tags --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Task Type</label>
                    <input type="text" name="task_type" value="{{ old('task_type') }}"
                           placeholder="e.g. Video Production, Design"
                           style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Tags</label>
                    <input type="text" name="tags" value="{{ old('tags') }}"
                           placeholder="#video, #branding, #urgent"
                           style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;">
                </div>
            </div>

            {{-- Assignees --}}
            <div style="margin-bottom:16px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <label style="font-size:12px;font-weight:600;color:#374151;">Assignees <span style="font-weight:400;color:#9CA3AF;">— auto-added as project members</span></label>
                    <button type="button" @click="addAssignee()"
                            style="font-size:11px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:none;padding:4px 12px;border-radius:6px;cursor:pointer;">
                        + Add Person
                    </button>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <template x-for="(assignee, i) in assignees" :key="i">
                        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:center;">
                            <select :name="'assignees['+i+'][user_id]'" x-model="assignee.user_id"
                                    style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                                <option value="">— Select person —</option>
                                <template x-for="u in allUsers" :key="u.id">
                                    <option :value="u.id" x-text="u.name + ' (' + u.role + ')'"></option>
                                </template>
                            </select>
                            <input type="text" :name="'assignees['+i+'][role]'" x-model="assignee.role"
                                   placeholder="Role (e.g. video editor)"
                                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;">
                            <button type="button" @click="removeAssignee(i)" x-show="assignees.length > 1"
                                    style="width:28px;height:28px;border-radius:8px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-times"></i>
                            </button>
                            <div x-show="assignees.length === 1" style="width:28px;"></div>
                        </div>
                    </template>
                </div>
                @error('assignees')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            {{-- Reviewer + Priority + Deadline --}}
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Reviewer</label>
                    <select name="reviewer_id"
                            style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                        <option value="">— None —</option>
                        @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ old('reviewer_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Priority <span style="color:#EF4444;">*</span></label>
                    <select name="priority" required
                            style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                        <option value="low"    {{ old('priority','medium')==='low'    ? 'selected':'' }}>Low</option>
                        <option value="medium" {{ old('priority','medium')==='medium' ? 'selected':'' }}>Medium</option>
                        <option value="high"   {{ old('priority','medium')==='high'   ? 'selected':'' }}>High</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Deadline <span style="color:#EF4444;">*</span></label>
                    <input type="date" name="deadline" value="{{ old('deadline') }}" required
                           style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('deadline') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;">
                    @error('deadline')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Description --}}
            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Description</label>
                <textarea name="description" rows="3" placeholder="What needs to be done..."
                          style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;">{{ old('description') }}</textarea>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit"
                        style="flex:1;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    <i class="fa fa-plus" style="margin-right:6px;"></i>Add Task
                </button>
                <a href="{{ route('admin.projects.show', $project) }}"
                   style="flex:1;background:#F3F4F6;color:#374151;padding:11px;border-radius:10px;font-size:14px;font-weight:600;text-align:center;text-decoration:none;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

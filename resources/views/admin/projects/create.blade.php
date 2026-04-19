@extends('layouts.app')
@section('title', 'Create Project')

@section('content')
<div style="max-width:740px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('admin.projects.index') }}"
           style="width:34px;height:34px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;">
            <i class="fa fa-arrow-left" style="font-size:13px;"></i>
        </a>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">Create New Project</h1>
    </div>

    <div x-data="{
        selectedMembers: {{ json_encode(array_map('strval', old('members', []))) }},
        allUsers: {{ $users->map(fn($u) => ['id' => (string)$u->id, 'name' => $u->name, 'role' => ucfirst($u->role), 'job' => $u->job_title ?? ''])->toJson() }},
        search: '',
        tasks: {{ old('tasks') ? json_encode(array_map(fn($t) => ['title' => $t['title'] ?? '', 'assigned_to' => $t['assigned_to'] ?? '', 'priority' => $t['priority'] ?? 'medium', 'deadline' => $t['deadline'] ?? '', 'description' => $t['description'] ?? ''], old('tasks'))) : '[{title:\'\',assigned_to:\'\',priority:\'medium\',deadline:\'\',description:\'\'}]' }},
        get visibleUsers() {
            if (!this.search) return this.allUsers;
            return this.allUsers.filter(u => u.name.toLowerCase().includes(this.search.toLowerCase()));
        },
        get assignableMembers() {
            if (this.selectedMembers.length === 0) return this.allUsers;
            return this.allUsers.filter(u => this.selectedMembers.includes(u.id));
        },
        addTask() { this.tasks.push({title:'',assigned_to:'',priority:'medium',deadline:'',description:''}); },
        removeTask(i) { if (this.tasks.length > 1) this.tasks.splice(i, 1); }
    }"
    style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:28px;">
        <form method="POST" action="{{ route('admin.projects.store') }}">
            @csrf

            {{-- Name --}}
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Project Name <span style="color:#EF4444;">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="e.g. Mobile App Redesign"
                       style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('name') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                @error('name')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            {{-- Description --}}
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Description</label>
                <textarea name="description" rows="2" placeholder="Brief description of the project..."
                          style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;">{{ old('description') }}</textarea>
            </div>

            {{-- Deadline --}}
            <div style="margin-bottom:22px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Deadline <span style="color:#EF4444;">*</span></label>
                <input type="date" name="deadline" value="{{ old('deadline') }}" required
                       style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('deadline') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                @error('deadline')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            {{-- Divider --}}
            <div style="height:1px;background:#F3F4F6;margin-bottom:22px;"></div>

            {{-- Team Members --}}
            <div style="margin-bottom:22px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                    Assign Team Members
                    <span style="font-weight:400;color:#9CA3AF;">— select who works on this project</span>
                </label>

                <input type="text" x-model="search" placeholder="Search members..."
                       style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;margin-bottom:8px;">

                <div style="border:1.5px solid #E5E7EB;border-radius:10px;max-height:200px;overflow-y:auto;">
                    <template x-for="u in visibleUsers" :key="u.id">
                        <div>
                            <label style="display:flex;align-items:center;gap:10px;padding:9px 14px;cursor:pointer;border-bottom:1px solid #F9FAFB;transition:background .1s;"
                                   onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                                <input type="checkbox" name="members[]" :value="u.id"
                                       x-model="selectedMembers"
                                       style="width:15px;height:15px;accent-color:#6366F1;flex-shrink:0;">
                                <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;"
                                     x-text="u.name.charAt(0).toUpperCase()"></div>
                                <div style="flex:1;min-width:0;">
                                    <p style="font-size:13px;font-weight:600;color:#111827;margin:0;" x-text="u.name"></p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:0;" x-text="u.role + (u.job ? ' · ' + u.job : '')"></p>
                                </div>
                                <span x-show="selectedMembers.includes(u.id)"
                                      style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;background:#EEF2FF;color:#4F46E5;">Added</span>
                            </label>
                        </div>
                    </template>
                    <div x-show="visibleUsers.length === 0" style="padding:16px;font-size:13px;color:#9CA3AF;text-align:center;">No users found.</div>
                </div>
                <p style="font-size:11px;color:#9CA3AF;margin:6px 0 0;">
                    <span x-text="selectedMembers.length"></span> member<span x-show="selectedMembers.length !== 1">s</span> selected
                </p>
            </div>

            {{-- Divider --}}
            <div style="height:1px;background:#F3F4F6;margin-bottom:22px;"></div>

            {{-- Tasks --}}
            <div style="margin-bottom:24px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:1px;">Assign Tasks</label>
                        <p style="font-size:11px;color:#9CA3AF;margin:0;">Add tasks and assign them to team members now</p>
                    </div>
                    <button type="button" @click="addTask()"
                            style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:#EEF2FF;color:#4F46E5;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-plus" style="font-size:10px;"></i> Add Task
                    </button>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;">
                    <template x-for="(task, i) in tasks" :key="i">
                        <div style="border:1.5px solid #E5E7EB;border-radius:12px;padding:16px;background:#FAFBFF;position:relative;">

                            {{-- Row number + remove --}}
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                                <span style="font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.04em;">
                                    Task <span x-text="i + 1"></span>
                                </span>
                                <button type="button" @click="removeTask(i)"
                                        x-show="tasks.length > 1"
                                        style="width:24px;height:24px;border-radius:6px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            {{-- Title --}}
                            <div style="margin-bottom:10px;">
                                <input type="text" :name="'tasks['+i+'][title]'" x-model="task.title"
                                       placeholder="Task title *"
                                       style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                            </div>

                            {{-- Assign / Priority / Deadline row --}}
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px;">
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Assign To *</label>
                                    <select :name="'tasks['+i+'][assigned_to]'" x-model="task.assigned_to"
                                            style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                                        <option value="">— Select —</option>
                                        <template x-for="m in assignableMembers" :key="m.id">
                                            <option :value="m.id" x-text="m.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Priority *</label>
                                    <select :name="'tasks['+i+'][priority]'" x-model="task.priority"
                                            style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Deadline *</label>
                                    <input type="date" :name="'tasks['+i+'][deadline]'" x-model="task.deadline"
                                           style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                </div>
                            </div>

                            {{-- Optional description --}}
                            <div>
                                <input type="text" :name="'tasks['+i+'][description]'" x-model="task.description"
                                       placeholder="Task description (optional)"
                                       style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                            </div>
                        </div>
                    </template>
                </div>

                @if($errors->has('tasks.*'))
                <p style="font-size:11px;color:#DC2626;margin-top:6px;">Please fill in all required task fields (title, assignee, priority, deadline).</p>
                @endif
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit"
                        style="flex:1;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    <i class="fa fa-rocket" style="margin-right:6px;"></i>Create Project &amp; Assign Tasks
                </button>
                <a href="{{ route('admin.projects.index') }}"
                   style="flex:1;background:#F3F4F6;color:#374151;border:none;padding:11px;border-radius:10px;font-size:14px;font-weight:600;text-align:center;text-decoration:none;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

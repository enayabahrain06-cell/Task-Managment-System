@extends('layouts.app')
@section('title', 'Create Project')

@section('content')
<div style="max-width:780px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('admin.projects.index') }}"
           style="width:34px;height:34px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;">
            <i class="fa fa-arrow-left" style="font-size:13px;"></i>
        </a>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">Create New Project</h1>
    </div>

    <div x-data="{
        allUsers: {{ $users->map(fn($u) => ['id' => (string)$u->id, 'name' => $u->name, 'role' => ucfirst($u->role), 'job' => $u->job_title ?? ''])->toJson() }},

        _blankTask() {
            return { title:'', task_type:'', tags:'', assignees:[{user_id:'',role:''}], reviewer_id:'', priority:'medium', deadline:'', description:'',
                     mentionOpen:false, mentionResults:[], mentionCursor:0, mentionStart:-1, _ta:null };
        },

        tasks: [],
        files: [],
        links: [],
        dragover: false,
        init() { this.tasks = [this._blankTask()]; },

        addTask()            { this.tasks.push(this._blankTask()); },
        removeTask(i)        { if (this.tasks.length > 1) this.tasks.splice(i, 1); },
        addAssignee(i)       { this.tasks[i].assignees.push({user_id:'', role:''}); },
        removeAssignee(i, j) { if (this.tasks[i].assignees.length > 1) this.tasks[i].assignees.splice(j, 1); },

        /* ── @mention logic ── */
        onDescInput(event, i) {
            const ta  = event.target;
            const t   = this.tasks[i];
            t._ta     = ta;
            const pos = ta.selectionStart;
            const m   = ta.value.slice(0, pos).match(/@([^\s@]*)$/);
            if (m) {
                const q       = m[1].toLowerCase();
                t.mentionStart   = pos - m[0].length;
                t.mentionResults = this.allUsers.filter(u =>
                    u.name.toLowerCase().includes(q) || (u.job && u.job.toLowerCase().includes(q))
                ).slice(0, 6);
                t.mentionOpen   = t.mentionResults.length > 0;
                t.mentionCursor = 0;
            } else {
                t.mentionOpen = false;
            }
        },

        mentionKeydown(event, i) {
            const t = this.tasks[i];
            if (!t.mentionOpen) return;
            if (event.key === 'ArrowDown')  { event.preventDefault(); t.mentionCursor = Math.min(t.mentionCursor + 1, t.mentionResults.length - 1); }
            else if (event.key === 'ArrowUp')   { event.preventDefault(); t.mentionCursor = Math.max(t.mentionCursor - 1, 0); }
            else if (event.key === 'Enter')     { event.preventDefault(); this.pickMention(t.mentionResults[t.mentionCursor], i); }
            else if (event.key === 'Escape')    { t.mentionOpen = false; }
        },

        pickMention(user, i) {
            const t      = this.tasks[i];
            const ta     = t._ta;
            const curPos = ta ? ta.selectionStart : (t.mentionStart + 1 + (t.mentionResults[t.mentionCursor]?.name.length ?? 0));
            const insert = '@' + user.name + ' ';
            t.description = t.description.slice(0, t.mentionStart) + insert + t.description.slice(curPos);
            t.mentionOpen = false;
            if (ta) {
                this.$nextTick(() => {
                    ta.focus();
                    const cur = t.mentionStart + insert.length;
                    ta.setSelectionRange(cur, cur);
                });
            }
        },

        avatarColor(id) {
            return ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4'][parseInt(id) % 8];
        },

        addLink()      { this.links.push({ url: '', label: '' }); },
        removeLink(i)  { this.links.splice(i, 1); },

        handleFiles(event) {
            const incoming = event.dataTransfer ? event.dataTransfer.files : event.target.files;
            const dt = new DataTransfer();
            for (let f of this.files) dt.items.add(f);
            for (let f of incoming) dt.items.add(f);
            this.files = Array.from(dt.files);
            this.$refs.fileInput.files = dt.files;
        },

        removeFile(i) {
            const dt = new DataTransfer();
            this.files.forEach((f, idx) => { if (idx !== i) dt.items.add(f); });
            this.files = Array.from(dt.files);
            this.$refs.fileInput.files = dt.files;
        },

        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        fileIcon(name) {
            const ext = name.split('.').pop().toLowerCase();
            if (['pdf'].includes(ext))                          return 'fa-file-pdf';
            if (['doc','docx'].includes(ext))                   return 'fa-file-word';
            if (['xls','xlsx'].includes(ext))                   return 'fa-file-excel';
            if (['ppt','pptx'].includes(ext))                   return 'fa-file-powerpoint';
            if (['zip','rar','7z'].includes(ext))               return 'fa-file-zipper';
            if (['jpg','jpeg','png','gif','webp','svg'].includes(ext)) return 'fa-file-image';
            if (['mp4','mov','avi','mkv'].includes(ext))        return 'fa-file-video';
            if (['mp3','wav','aac'].includes(ext))              return 'fa-file-audio';
            return 'fa-file';
        }
    }"
    style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:28px;">
        <form method="POST" action="{{ route('admin.projects.store') }}" enctype="multipart/form-data">
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

            {{-- Attachments --}}
            <div style="margin-bottom:22px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;">
                    Attachments
                    <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— files or links</span>
                </label>

                {{-- Drop Zone --}}
                <div @dragover.prevent="dragover = true"
                     @dragleave.prevent="dragover = false"
                     @drop.prevent="dragover = false; handleFiles($event)"
                     @click="$refs.fileInput.click()"
                     :style="dragover ? 'border-color:#6366F1;background:#EEF2FF;' : 'border-color:#E5E7EB;background:#FAFAFA;'"
                     style="border:2px dashed;border-radius:10px;padding:22px;text-align:center;cursor:pointer;transition:all .2s;">
                    <i class="fas fa-cloud-upload-alt" style="font-size:22px;color:#9CA3AF;margin-bottom:6px;display:block;"></i>
                    <p style="font-size:13px;color:#6B7280;margin:0;">Drag &amp; drop files or <span style="color:#6366F1;font-weight:600;">browse</span></p>
                    <p style="font-size:11px;color:#9CA3AF;margin:4px 0 0;">Max 20 MB per file</p>
                    <input type="file" name="attachments[]" multiple x-ref="fileInput"
                           @change="handleFiles($event)" style="display:none;">
                </div>

                {{-- File List --}}
                <template x-if="files.length > 0">
                    <div style="margin-top:8px;display:flex;flex-direction:column;gap:5px;">
                        <template x-for="(file, i) in files" :key="i">
                            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;">
                                <i :class="'fas ' + fileIcon(file.name)" style="font-size:14px;color:#6366F1;flex-shrink:0;"></i>
                                <div style="flex:1;min-width:0;">
                                    <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="file.name"></p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:0;" x-text="formatSize(file.size)"></p>
                                </div>
                                <button type="button" @click.stop="removeFile(i)"
                                        style="width:22px;height:22px;border-radius:6px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Links --}}
                <div style="margin-top:14px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <label style="font-size:11px;font-weight:600;color:#6B7280;">Links</label>
                        <button type="button" @click="addLink()"
                                style="font-size:10px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:none;padding:3px 10px;border-radius:6px;cursor:pointer;">
                            + Add Link
                        </button>
                    </div>
                    <template x-if="links.length > 0">
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <template x-for="(link, i) in links" :key="i">
                                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center;">
                                    <input type="url" :name="'links['+i+'][url]'" x-model="link.url"
                                           placeholder="https://..."
                                           style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                    <input type="text" :name="'links['+i+'][label]'" x-model="link.label"
                                           placeholder="Label (optional)"
                                           style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                    <button type="button" @click="removeLink(i)"
                                            style="width:26px;height:26px;border-radius:6px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                    <p x-show="links.length === 0" style="font-size:11px;color:#9CA3AF;margin:0;">No links added yet — click "+ Add Link" to add one.</p>
                </div>
            </div>

            {{-- Divider --}}
            <div style="height:1px;background:#F3F4F6;margin-bottom:22px;"></div>

            {{-- Tasks --}}
            <div style="margin-bottom:24px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:2px;">Assign Tasks</label>
                        <p style="font-size:11px;color:#9CA3AF;margin:0;">Assigned employees are automatically added as project members</p>
                    </div>
                    <button type="button" @click="addTask()"
                            style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:#EEF2FF;color:#4F46E5;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-plus" style="font-size:10px;"></i> Add Task
                    </button>
                </div>

                <div style="display:flex;flex-direction:column;gap:14px;">
                    <template x-for="(task, i) in tasks" :key="i">
                        <div style="border:1.5px solid #E5E7EB;border-radius:12px;padding:18px;background:#FAFBFF;position:relative;">

                            {{-- Header --}}
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                                <span style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">
                                    Task <span x-text="i + 1"></span>
                                </span>
                                <button type="button" @click="removeTask(i)" x-show="tasks.length > 1"
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

                            {{-- Task type + Tags --}}
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Task Type</label>
                                    <input type="text" :name="'tasks['+i+'][task_type]'" x-model="task.task_type"
                                           placeholder="e.g. Video Production, Design"
                                           style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                </div>
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Tags <span style="font-weight:400;color:#9CA3AF;">(comma separated, e.g. #video, #urgent)</span></label>
                                    <input type="text" :name="'tasks['+i+'][tags]'" x-model="task.tags"
                                           placeholder="#video, #branding, #urgent"
                                           style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                </div>
                            </div>

                            {{-- Assignees --}}
                            <div style="margin-bottom:12px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                    <label style="font-size:11px;font-weight:600;color:#6B7280;">Assignees</label>
                                    <button type="button" @click="addAssignee(i)"
                                            style="font-size:10px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:none;padding:3px 10px;border-radius:6px;cursor:pointer;">
                                        + Add Person
                                    </button>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:6px;">
                                    <template x-for="(assignee, j) in task.assignees" :key="j">
                                        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center;">
                                            <select :name="'tasks['+i+'][assignees]['+j+'][user_id]'" x-model="assignee.user_id"
                                                    style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                                                <option value="">— Select person —</option>
                                                <template x-for="u in allUsers" :key="u.id">
                                                    <option :value="u.id" x-text="u.name + ' (' + u.role + ')'"></option>
                                                </template>
                                            </select>
                                            <input type="text" :name="'tasks['+i+'][assignees]['+j+'][role]'" x-model="assignee.role"
                                                   placeholder="Role (e.g. video editor, designer)"
                                                   style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                            <button type="button" @click="removeAssignee(i, j)" x-show="task.assignees.length > 1"
                                                    style="width:26px;height:26px;border-radius:6px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <div x-show="task.assignees.length === 1" style="width:26px;"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Reviewer + Priority + Deadline --}}
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px;">
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Reviewer</label>
                                    <select :name="'tasks['+i+'][reviewer_id]'" x-model="task.reviewer_id"
                                            style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                                        <option value="">— None —</option>
                                        <template x-for="u in allUsers" :key="u.id">
                                            <option :value="u.id" x-text="u.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Priority</label>
                                    <select :name="'tasks['+i+'][priority]'" x-model="task.priority"
                                            style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Deadline</label>
                                    <input type="date" :name="'tasks['+i+'][deadline]'" x-model="task.deadline"
                                           style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                </div>
                            </div>

                            {{-- Description with @mention --}}
                            <div style="position:relative;">
                                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">
                                    Description
                                    <span style="font-weight:400;color:#9CA3AF;">— type <kbd style="font-size:10px;background:#F3F4F6;border:1px solid #E5E7EB;border-radius:4px;padding:0 4px;">@</kbd> to contextually mention a team member</span>
                                </label>
                                <textarea
                                    :name="'tasks['+i+'][description]'"
                                    x-model="task.description"
                                    @input="onDescInput($event, i)"
                                    @keydown="mentionKeydown($event, i)"
                                    @blur="setTimeout(() => { tasks[i].mentionOpen = false }, 150)"
                                    rows="3"
                                    placeholder="Task brief, context, or notes — type @ to mention someone for contextual reference"
                                    style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;resize:vertical;font-family:'Inter',sans-serif;line-height:1.6;"
                                ></textarea>

                                {{-- @mention dropdown --}}
                                <div x-show="task.mentionOpen" x-cloak
                                     style="position:absolute;left:0;right:0;z-index:100;background:#fff;border:1.5px solid #C7D2FE;border-radius:10px;box-shadow:0 8px 32px rgba(79,70,229,.15);overflow:hidden;margin-top:3px;">
                                    <div style="padding:6px 12px;font-size:10px;font-weight:700;color:#6366F1;text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid #EEF2FF;background:#F5F3FF;display:flex;align-items:center;gap:6px;">
                                        <i class="fas fa-at" style="font-size:9px;"></i> Contextual mention — not an official assignment
                                    </div>
                                    <template x-for="(u, idx) in task.mentionResults" :key="u.id">
                                        <button type="button"
                                                @mousedown.prevent="pickMention(u, i)"
                                                :style="task.mentionCursor === idx ? 'background:#EEF2FF;' : 'background:#fff;'"
                                                style="width:100%;padding:8px 12px;display:flex;align-items:center;gap:10px;border:none;border-bottom:1px solid #F9FAFB;cursor:pointer;text-align:left;transition:background .1s;">
                                            <div :style="'width:28px;height:28px;border-radius:50%;background:'+avatarColor(u.id)+';display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;'"
                                                 x-text="u.name.charAt(0).toUpperCase()">
                                            </div>
                                            <div style="flex:1;min-width:0;">
                                                <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="u.name"></p>
                                                <p style="font-size:11px;color:#9CA3AF;margin:0;" x-text="u.role + (u.job ? ' · ' + u.job : '')"></p>
                                            </div>
                                            <span style="font-size:10px;font-weight:600;color:#4F46E5;background:#EEF2FF;padding:2px 7px;border-radius:5px;flex-shrink:0;white-space:nowrap;">
                                                @mention
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>

                @if($errors->has('tasks.*'))
                <p style="font-size:11px;color:#DC2626;margin-top:6px;">Please check all required task fields.</p>
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

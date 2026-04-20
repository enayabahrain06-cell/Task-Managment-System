@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div x-data="{
    wizardOpen: false,
    quickOpen: false,
    step: 1,
    totalSteps: 3,

    allUsers: {{ $users->map(fn($u) => ['id' => (string)$u->id, 'name' => $u->name, 'role' => ucfirst($u->role), 'job' => $u->job_title ?? ''])->toJson() }},

    _blankTask() {
        return { title:'', task_type:'', tags:'', assignees:[{user_id:'',role:''}], reviewer_id:'', priority:'medium', deadline:'', description:'',
                 mentionOpen:false, mentionResults:[], mentionCursor:0, mentionStart:-1, _ta:null,
                 titleError:false, assigneeError:false };
    },

    tasks: [],
    files: [],
    links: [],
    dragover: false,
    nameError: false,
    deadlineError: false,

    init() { this.tasks = [this._blankTask()]; },

    openWizard() { this.step = 1; this.nameError = false; this.deadlineError = false; this.wizardOpen = true; document.body.style.overflow = 'hidden'; },
    closeWizard() { this.wizardOpen = false; document.body.style.overflow = ''; },

    nextStep() {
        if (this.step === 1) {
            this.nameError     = !this.$refs.wizardName?.value.trim();
            this.deadlineError = !this.$refs.wizardDeadline?.value;
            if (this.nameError || this.deadlineError) return;
        }
        if (this.step === 2) {
            let hasError = false;
            for (const task of this.tasks) {
                task.titleError    = !task.title.trim();
                task.assigneeError = !task.assignees.some(a => a.user_id);
                if (task.titleError || task.assigneeError) hasError = true;
            }
            if (hasError) return;
        }
        if (this.step < this.totalSteps) this.step++;
    },
    prevStep() { if (this.step > 1) this.step--; },

    addTask()            { this.tasks.push(this._blankTask()); },
    removeTask(i)        { if (this.tasks.length > 1) this.tasks.splice(i, 1); },
    addAssignee(i)       { this.tasks[i].assignees.push({user_id:'', role:''}); },
    removeAssignee(i, j) { if (this.tasks[i].assignees.length > 1) this.tasks[i].assignees.splice(j, 1); },

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
}">

{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $projects->total() }} total projects</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="quickOpen = true" class="flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition border border-gray-200 shadow-sm">
            <i class="fa fa-bolt text-amber-500"></i> Quick Task
        </button>
        <button @click="openWizard()" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm shadow-indigo-200">
            <i class="fa fa-plus"></i> New Project
        </button>
    </div>
</div>

{{-- Projects Table --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50/50">
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tasks</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Deadline</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($projects as $project)
            @php
                $statusBg = ['active' => 'bg-emerald-100 text-emerald-700', 'completed' => 'bg-gray-100 text-gray-600', 'overdue' => 'bg-red-100 text-red-600'];
            @endphp
            <tr class="hover:bg-gray-50/70 transition">
                <td class="px-5 py-3.5">
                    <a href="{{ route('admin.projects.show', $project) }}" class="text-sm font-semibold text-gray-900 hover:text-indigo-600 transition">
                        {{ $project->name }}
                    </a>
                    @if($project->description)
                    <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $project->description }}</p>
                    @endif
                </td>
                <td class="px-5 py-3.5">
                    <span class="inline-flex items-center gap-1 text-sm text-gray-700">
                        <i class="fa fa-tasks text-gray-300 text-xs"></i> {{ $project->tasks_count }}
                    </span>
                </td>
                <td class="px-5 py-3.5">
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $statusBg[$project->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($project->status) }}
                    </span>
                </td>
                <td class="px-5 py-3.5 text-sm {{ $project->deadline < now() && $project->status !== 'completed' ? 'text-red-500 font-semibold' : 'text-gray-500' }}">
                    {{ $project->deadline->format('M d, Y') }}
                </td>
                <td class="px-5 py-3.5 text-sm text-gray-400">{{ $project->created_at->format('M d') }}</td>
                <td class="px-5 py-3.5">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.projects.show', $project) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-2.5 py-1.5 rounded-lg transition">View</a>
                        <a href="{{ route('admin.projects.edit', $project) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition">Edit</a>
                        <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" class="inline"
                              onsubmit="return confirm('Delete {{ addslashes($project->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded-lg transition">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-5 py-12 text-center">
                    <i class="fa fa-project-diagram text-4xl text-gray-200 mb-3"></i>
                    <p class="text-sm text-gray-400">No projects found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($projects->hasPages())
    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
        {{ $projects->links() }}
    </div>
    @endif
</div>

{{-- ══════════════════ QUICK TASK MODAL ══════════════════ --}}
<div x-show="quickOpen" x-cloak style="position:fixed;inset:0;z-index:9999;">
<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;">

    <div @click="quickOpen = false" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(3px);"></div>

    <div style="position:relative;width:100%;max-width:480px;background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,0.2);overflow:hidden;">

        {{-- Header --}}
        <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:10px;background:#FFFBEB;display:flex;align-items:center;justify-content:center;">
                    <i class="fa fa-bolt" style="color:#F59E0B;font-size:15px;"></i>
                </div>
                <div>
                    <h2 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Quick Task</h2>
                    <p style="font-size:11px;color:#9CA3AF;margin:0;">Assign a task instantly to a team member</p>
                </div>
            </div>
            <button @click="quickOpen = false"
                    style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:13px;">
                <i class="fa fa-times"></i>
            </button>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.tasks.quick') }}" style="padding:20px 24px 24px;">
            @csrf

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">
                    Task Title <span style="color:#EF4444;">*</span>
                </label>
                <input type="text" name="title" required placeholder="e.g. Update hero banner image"
                       style="width:100%;padding:9px 13px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;"
                       onfocus="this.style.borderColor='#F59E0B'" onblur="this.style.borderColor='#E5E7EB'">
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Description</label>
                <textarea name="description" rows="2" placeholder="Brief details or notes..."
                          style="width:100%;padding:9px 13px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;"
                          onfocus="this.style.borderColor='#F59E0B'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">
                    Assign To <span style="color:#EF4444;">*</span>
                </label>
                <select name="assigned_to" required
                        style="width:100%;padding:9px 13px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;box-sizing:border-box;outline:none;">
                    <option value="">— Select team member —</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ ucfirst($u->role) }})</option>
                    @endforeach
                </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Priority <span style="color:#EF4444;">*</span></label>
                    <select name="priority" required
                            style="width:100%;padding:9px 13px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;box-sizing:border-box;outline:none;">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Deadline <span style="color:#EF4444;">*</span></label>
                    <input type="date" name="deadline" required
                           style="width:100%;padding:9px 13px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;">
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">
                    Link to Project <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— optional</span>
                </label>
                <select name="project_id"
                        style="width:100%;padding:9px 13px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;box-sizing:border-box;outline:none;">
                    <option value="">— No project (standalone) —</option>
                    @foreach($projects as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    style="width:100%;padding:11px;background:linear-gradient(135deg,#F59E0B,#D97706);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 12px rgba(245,158,11,.35);">
                <i class="fa fa-bolt"></i> Assign Task
            </button>
        </form>

    </div>
</div>
</div>

{{-- ══════════════════ WIZARD MODAL ══════════════════ --}}
<div x-show="wizardOpen" x-cloak style="position:fixed;inset:0;z-index:9999;">
<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;">

    {{-- Backdrop --}}
    <div @click="closeWizard()" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(3px);"></div>

    {{-- Modal --}}
    <div style="position:relative;width:100%;max-width:700px;max-height:90vh;background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,0.2);display:flex;flex-direction:column;overflow:hidden;">

        {{-- Modal Header --}}
        <div style="padding:20px 24px 0;flex-shrink:0;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <div>
                    <h2 style="font-size:18px;font-weight:700;color:#111827;margin:0;">New Project</h2>
                    <p style="font-size:12px;color:#9CA3AF;margin:4px 0 0;" x-text="'Step ' + step + ' of ' + totalSteps"></p>
                </div>
                <button @click="closeWizard()"
                        style="width:32px;height:32px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:14px;">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            {{-- Step Indicators --}}
            <div style="display:flex;align-items:center;gap:0;margin-bottom:24px;">
                <template x-for="s in totalSteps" :key="s">
                    <div style="display:flex;align-items:center;flex:1;">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;flex:1;">
                            <div :style="step >= s
                                    ? 'width:32px;height:32px;border-radius:50%;background:#4F46E5;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;transition:all .2s;'
                                    : 'width:32px;height:32px;border-radius:50%;background:#F3F4F6;color:#9CA3AF;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;transition:all .2s;'">
                                <span x-show="step > s"><i class="fa fa-check" style="font-size:11px;"></i></span>
                                <span x-show="step <= s" x-text="s"></span>
                            </div>
                            <span :style="step >= s ? 'font-size:10px;font-weight:600;color:#4F46E5;' : 'font-size:10px;font-weight:600;color:#9CA3AF;'"
                                  x-text="s === 1 ? 'Details' : s === 2 ? 'Tasks' : 'Attachments'"></span>
                        </div>
                        <template x-if="s < totalSteps">
                            <div :style="step > s ? 'flex:1;height:2px;background:#4F46E5;margin:0 4px;margin-bottom:20px;transition:all .3s;' : 'flex:1;height:2px;background:#E5E7EB;margin:0 4px;margin-bottom:20px;transition:all .3s;'"></div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Modal Body --}}
        <form method="POST" action="{{ route('admin.projects.store') }}" enctype="multipart/form-data" id="projectWizardForm">
            @csrf
            <div style="flex:1;overflow-y:auto;padding:0 24px 8px;">

                {{-- ── STEP 1: Project Details ── --}}
                <div x-show="step === 1">
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                            Project Name <span style="color:#EF4444;">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               x-ref="wizardName"
                               @input="nameError = false"
                               placeholder="e.g. Mobile App Redesign"
                               :style="nameError ? 'width:100%;padding:10px 14px;border:1.5px solid #EF4444;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;background:#FEF2F2;transition:border-color .2s;' : 'width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;background:#fff;transition:border-color .2s;'"
                               onfocus="this.style.borderColor='#6366F1'" onblur="if(!this.closest('[x-data]').__x.$data.nameError) this.style.borderColor='#E5E7EB'">
                        <p x-show="nameError" style="margin:4px 0 0;font-size:11px;color:#EF4444;"><i class="fa fa-circle-exclamation" style="margin-right:3px;"></i>Project name is required.</p>
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                            Description
                            <span style="font-size:11px;font-weight:400;color:#9CA3AF;margin-left:4px;">— keep it brief</span>
                        </label>
                        <textarea name="description" rows="2" placeholder="Short summary of the project goal..."
                                  style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;resize:none;font-family:'Inter',sans-serif;transition:border-color .2s;"
                                  onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">{{ old('description') }}</textarea>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:8px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                Deadline <span style="color:#EF4444;">*</span>
                            </label>
                            <input type="date" name="deadline" value="{{ old('deadline') }}" required
                                   x-ref="wizardDeadline"
                                   @change="deadlineError = false"
                                   :style="deadlineError ? 'width:100%;padding:10px 14px;border:1.5px solid #EF4444;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;background:#FEF2F2;transition:border-color .2s;' : 'width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;transition:border-color .2s;'">
                            <p x-show="deadlineError" style="margin:4px 0 0;font-size:11px;color:#EF4444;"><i class="fa fa-circle-exclamation" style="margin-right:3px;"></i>Deadline is required.</p>
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                First Review Date
                                <span style="font-size:10px;font-weight:400;color:#9CA3AF;">optional</span>
                            </label>
                            <input type="date" name="first_review_date" value="{{ old('first_review_date') }}"
                                   style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;transition:border-color .2s;"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                        </div>
                    </div>
                </div>

                {{-- ── STEP 3: Attachments & Links ── --}}
                <div x-show="step === 3">
                    {{-- Drop Zone --}}
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;">
                        Files <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— max 20 MB each</span>
                    </label>
                    <div @dragover.prevent="dragover = true"
                         @dragleave.prevent="dragover = false"
                         @drop.prevent="dragover = false; handleFiles($event)"
                         @click="$refs.fileInput.click()"
                         :style="dragover ? 'border-color:#6366F1;background:#EEF2FF;' : 'border-color:#E5E7EB;background:#FAFAFA;'"
                         style="border:2px dashed;border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:10px;">
                        <i class="fas fa-cloud-upload-alt" style="font-size:24px;color:#9CA3AF;margin-bottom:8px;display:block;"></i>
                        <p style="font-size:13px;color:#6B7280;margin:0;">Drag &amp; drop files or <span style="color:#6366F1;font-weight:600;">browse</span></p>
                        <input type="file" name="attachments[]" multiple x-ref="fileInput"
                               @change="handleFiles($event)" style="display:none;">
                    </div>

                    <template x-if="files.length > 0">
                        <div style="margin-bottom:14px;display:flex;flex-direction:column;gap:5px;">
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
                    <div style="margin-top:16px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                            <label style="font-size:12px;font-weight:600;color:#374151;">Links</label>
                            <button type="button" @click="addLink()"
                                    style="font-size:11px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:none;padding:4px 12px;border-radius:6px;cursor:pointer;">
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
                        <p x-show="links.length === 0" style="font-size:11px;color:#9CA3AF;margin:0;">No links yet — click "+ Add Link".</p>
                    </div>
                </div>

                {{-- ── STEP 2: Tasks ── --}}
                <div x-show="step === 2">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                        <div>
                            <p style="font-size:13px;font-weight:700;color:#374151;margin:0;">Assign Tasks</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Assignees are automatically added as project members</p>
                        </div>
                        <button type="button" @click="addTask()"
                                style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:#EEF2FF;color:#4F46E5;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                            <i class="fas fa-plus" style="font-size:10px;"></i> Add Task
                        </button>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:14px;">
                        <template x-for="(task, i) in tasks" :key="i">
                            <div :style="(task.titleError || task.assigneeError) ? 'border:1.5px solid #FCA5A5;border-radius:12px;padding:16px;background:#FAFBFF;position:relative;' : 'border:1.5px solid #E5E7EB;border-radius:12px;padding:16px;background:#FAFBFF;position:relative;'">

                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                                    <span style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">
                                        Task <span x-text="i + 1"></span>
                                    </span>
                                    <button type="button" @click="removeTask(i)" x-show="tasks.length > 1"
                                            style="width:24px;height:24px;border-radius:6px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <div style="margin-bottom:10px;">
                                    <input type="text" :name="'tasks['+i+'][title]'" x-model="task.title"
                                           @input="task.titleError = false"
                                           placeholder="Task title *"
                                           :style="task.titleError ? 'width:100%;padding:9px 12px;border:1.5px solid #EF4444;border-radius:8px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;background:#FEF2F2;' : 'width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;background:#fff;'">
                                    <p x-show="task.titleError" style="margin:3px 0 0;font-size:11px;color:#EF4444;display:flex;align-items:center;gap:3px;">
                                        <i class="fa fa-circle-exclamation"></i> Task title is required.
                                    </p>
                                </div>

                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                                    <div>
                                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Task Type</label>
                                        <input type="text" :name="'tasks['+i+'][task_type]'" x-model="task.task_type"
                                               placeholder="e.g. Design, Video"
                                               style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Tags <span style="font-weight:400;color:#9CA3AF;">(comma separated)</span></label>
                                        <input type="text" :name="'tasks['+i+'][tags]'" x-model="task.tags"
                                               placeholder="#video, #urgent"
                                               style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                    </div>
                                </div>

                                <div style="margin-bottom:10px;">
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
                                                        @change="task.assigneeError = false"
                                                        :style="task.assigneeError && j === 0 ? 'width:100%;padding:7px 10px;border:1.5px solid #EF4444;border-radius:8px;font-size:12px;color:#111827;background:#FEF2F2;outline:none;box-sizing:border-box;' : 'width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;background:#fff;outline:none;box-sizing:border-box;'">
                                                    <option value="">— Select person —</option>
                                                    <template x-for="u in allUsers" :key="u.id">
                                                        <option :value="u.id" x-text="u.name + ' (' + u.role + ')'"></option>
                                                    </template>
                                                </select>
                                                <input type="text" :name="'tasks['+i+'][assignees]['+j+'][role]'" x-model="assignee.role"
                                                       placeholder="Role (e.g. designer)"
                                                       style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                                <button type="button" @click="removeAssignee(i, j)" x-show="task.assignees.length > 1"
                                                        style="width:26px;height:26px;border-radius:6px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <div x-show="task.assignees.length === 1" style="width:26px;"></div>
                                            </div>
                                        </template>
                                        <p x-show="task.assigneeError" style="margin:4px 0 0;font-size:11px;color:#EF4444;display:flex;align-items:center;gap:3px;">
                                            <i class="fa fa-circle-exclamation"></i> Please assign at least one person.
                                        </p>
                                    </div>
                                </div>

                                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px;">
                                    <div>
                                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Reviewer</label>
                                        <select :name="'tasks['+i+'][reviewer_id]'" x-model="task.reviewer_id"
                                                style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                                            <option value="">— None —</option>
                                            <template x-for="u in allUsers" :key="u.id">
                                                <option :value="u.id" x-text="u.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Priority</label>
                                        <select :name="'tasks['+i+'][priority]'" x-model="task.priority"
                                                style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Deadline</label>
                                        <input type="date" :name="'tasks['+i+'][deadline]'" x-model="task.deadline"
                                               style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                    </div>
                                </div>

                                <div style="position:relative;">
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">
                                        Description
                                        <span style="font-weight:400;color:#9CA3AF;">— type <kbd style="font-size:10px;background:#F3F4F6;border:1px solid #E5E7EB;border-radius:4px;padding:0 4px;">@</kbd> to mention</span>
                                    </label>
                                    <textarea :name="'tasks['+i+'][description]'"
                                        x-model="task.description"
                                        @input="onDescInput($event, i)"
                                        @keydown="mentionKeydown($event, i)"
                                        @blur="setTimeout(() => { tasks[i].mentionOpen = false }, 150)"
                                        rows="2"
                                        placeholder="Task brief or notes..."
                                        style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;resize:vertical;font-family:'Inter',sans-serif;line-height:1.6;"
                                    ></textarea>

                                    <div x-show="task.mentionOpen" x-cloak
                                         style="position:absolute;left:0;right:0;z-index:200;background:#fff;border:1.5px solid #C7D2FE;border-radius:10px;box-shadow:0 8px 32px rgba(79,70,229,.15);overflow:hidden;margin-top:3px;">
                                        <div style="padding:5px 12px;font-size:10px;font-weight:700;color:#6366F1;text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid #EEF2FF;background:#F5F3FF;">
                                            <i class="fas fa-at" style="font-size:9px;"></i> Mention
                                        </div>
                                        <template x-for="(u, idx) in task.mentionResults" :key="u.id">
                                            <button type="button"
                                                    @mousedown.prevent="pickMention(u, i)"
                                                    :style="task.mentionCursor === idx ? 'background:#EEF2FF;' : 'background:#fff;'"
                                                    style="width:100%;padding:7px 12px;display:flex;align-items:center;gap:10px;border:none;border-bottom:1px solid #F9FAFB;cursor:pointer;text-align:left;transition:background .1s;">
                                                <div :style="'width:26px;height:26px;border-radius:50%;background:'+avatarColor(u.id)+';display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;'"
                                                     x-text="u.name.charAt(0).toUpperCase()"></div>
                                                <div style="flex:1;min-width:0;">
                                                    <p style="font-size:12px;font-weight:600;color:#111827;margin:0;" x-text="u.name"></p>
                                                    <p style="font-size:11px;color:#9CA3AF;margin:0;" x-text="u.role"></p>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div style="padding:16px 24px;border-top:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fff;">

                <button type="button" @click="step > 1 ? prevStep() : closeWizard()"
                        style="padding:9px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
                    <i class="fa fa-arrow-left" style="font-size:11px;"></i>
                    <span x-text="step > 1 ? 'Back' : 'Cancel'"></span>
                </button>

                <div style="display:flex;align-items:center;gap:6px;">
                    <template x-for="s in totalSteps" :key="s">
                        <div :style="step === s
                            ? 'width:8px;height:8px;border-radius:50%;background:#4F46E5;transition:all .2s;'
                            : 'width:6px;height:6px;border-radius:50%;background:#E5E7EB;transition:all .2s;'"></div>
                    </template>
                </div>

                <template x-if="step < totalSteps">
                    <button type="button" @click="nextStep()"
                            style="padding:9px 20px;background:#4F46E5;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
                        Next <i class="fa fa-arrow-right" style="font-size:11px;"></i>
                    </button>
                </template>

                <template x-if="step === totalSteps">
                    <button type="submit"
                            style="padding:9px 20px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(99,102,241,.35);">
                        <i class="fa fa-rocket" style="font-size:11px;"></i> Create Project
                    </button>
                </template>

            </div>
        </form>

    </div>

</div>
</div>

</div>
@endsection

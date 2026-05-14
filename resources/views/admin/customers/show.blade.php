@extends('layouts.app')
@section('title', $customer->name)

@section('content')
@php
    $_tsBg    = ['draft'=>'#F3F4F6','assigned'=>'#EEF2FF','viewed'=>'#E0F2FE','in_progress'=>'#FFF7ED','submitted'=>'#F5F3FF','revision_requested'=>'#FEE2E2','approved'=>'#ECFDF5','delivered'=>'#ECFDF5','archived'=>'#F3F4F6'];
    $_tsColor = ['draft'=>'#6B7280','assigned'=>'#4F46E5','viewed'=>'#0369A1','in_progress'=>'#EA580C','submitted'=>'#7C3AED','revision_requested'=>'#DC2626','approved'=>'#16A34A','delivered'=>'#16A34A','archived'=>'#9CA3AF'];
    $_tsLabel = ['draft'=>'Draft','assigned'=>'Assigned','viewed'=>'Viewed','in_progress'=>'In Progress','submitted'=>'In Review','revision_requested'=>'Revision','approved'=>'Approved','delivered'=>'Delivered','archived'=>'Archived'];
    $_psBg    = ['active'=>'#EEF2FF','completed'=>'#ECFDF5','pending'=>'#FEF3C7','on_hold'=>'#FEF3C7','cancelled'=>'#FEE2E2'];
    $_psColor = ['active'=>'#4F46E5','completed'=>'#16A34A','pending'=>'#D97706','on_hold'=>'#D97706','cancelled'=>'#DC2626'];
    $_psLabel = ['active'=>'Active','completed'=>'Completed','pending'=>'Pending','on_hold'=>'On Hold','cancelled'=>'Cancelled'];
    $_overdueReason = function($t) {
        if (!$t->deadline || !$t->deadline->isPast() || in_array($t->status, ['approved','delivered','archived'])) return null;
        return match($t->status) {
            'pending_customer'   => ['label'=>'Waiting: Customer',  'bg'=>'#FEF3C7','color'=>'#D97706','icon'=>'fa-user-clock'],
            'submitted'          => ['label'=>'Waiting: Admin Review','bg'=>'#EEF2FF','color'=>'#4F46E5','icon'=>'fa-user-shield'],
            'revision_requested' => ['label'=>'Waiting: Revision',  'bg'=>'#FEE2E2','color'=>'#DC2626','icon'=>'fa-rotate-left'],
            'in_progress'        => ['label'=>'Waiting: Assignee',  'bg'=>'#FFF7ED','color'=>'#EA580C','icon'=>'fa-user-pen'],
            'viewed'             => ['label'=>'Waiting: Assignee',  'bg'=>'#FFF7ED','color'=>'#EA580C','icon'=>'fa-user-pen'],
            'assigned'           => ['label'=>'Waiting: Assignee',  'bg'=>'#FFF7ED','color'=>'#EA580C','icon'=>'fa-user-pen'],
            'draft'              => ['label'=>'Not Started',         'bg'=>'#F3F4F6','color'=>'#6B7280','icon'=>'fa-circle-pause'],
            default              => ['label'=>'Overdue',             'bg'=>'#FEE2E2','color'=>'#DC2626','icon'=>'fa-triangle-exclamation'],
        };
    };
    $_mapTask = fn($t) => [
        'title'         => $t->title,
        'project'       => $t->project->name ?? '—',
        'assignee'      => $t->assignee->name ?? null,
        'statusLabel'   => $_tsLabel[$t->status] ?? ucfirst(str_replace('_',' ',$t->status)),
        'statusBg'      => $_tsBg[$t->status]    ?? '#F3F4F6',
        'statusColor'   => $_tsColor[$t->status]  ?? '#374151',
        'deadline'      => $t->deadline ? $t->deadline->format(config('app.date_format', 'M d, Y')) : null,
        'overdue'       => $t->deadline && $t->deadline->isPast() && !in_array($t->status, ['approved','delivered','archived']),
        'overdueReason' => $_overdueReason($t),
        'url'           => route('admin.tasks.show', $t->id),
    ];
    $_allTasks = $customer->tasks;
    $taskGroupsJson = json_encode([
        'pending' => $_allTasks->whereIn('status', ['draft','assigned','viewed'])->map($_mapTask)->values(),
        'active'  => $_allTasks->whereIn('status', ['in_progress','submitted','revision_requested'])->map($_mapTask)->values(),
        'done'    => $_allTasks->whereIn('status', ['approved','delivered','archived'])->map($_mapTask)->values(),
        'overdue' => $_allTasks->filter(fn($t) => $t->deadline && $t->deadline->isPast() && !in_array($t->status, ['approved','delivered','archived']))->map($_mapTask)->values(),
    ]);
    $customerFilesJson = json_encode($customer->tasks->flatMap(fn($t) =>
        ($t->submissions ?? collect())->map(fn($s) => [
            'task_title'   => $t->title,
            'version'      => $s->version,
            'filename'     => $s->original_filename ?? basename($s->file_path),
            'file_url'     => url(\Illuminate\Support\Facades\Storage::url($s->file_path)),
            'status'       => $s->status,
            'status_label' => match($s->status) {
                'approved'  => 'Approved',
                'rejected'  => 'Revision',
                'submitted' => 'Submitted',
                default     => ucfirst($s->status ?? ''),
            },
        ])
    )->values());
    $customerProjectsJson = json_encode($customer->projects->map(fn($p) => [
        'name'        => $p->name,
        'tasksCount'  => $p->tasks_count ?? 0,
        'statusLabel' => $_psLabel[$p->status] ?? ucfirst($p->status),
        'statusBg'    => $_psBg[$p->status]    ?? '#F3F4F6',
        'statusColor' => $_psColor[$p->status]  ?? '#374151',
        'deadline'    => $p->deadline ? $p->deadline->format(config('app.date_format', 'M d, Y')) : null,
        'overdue'     => $p->deadline && $p->deadline->isPast() && $p->status !== 'completed',
        'url'         => route('admin.projects.show', $p->id),
    ])->values());
@endphp
<style>
.cust-show-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
.cust-show-split { display:grid; grid-template-columns:1fr 2fr; gap:20px; align-items:start; }
@media (max-width: 900px) {
    .cust-show-stats-grid { grid-template-columns: repeat(2,1fr); }
    .cust-show-split { grid-template-columns: 1fr; }
}
.cust-tbl-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.cust-tbl-scroll table { min-width:500px; }
@media (max-width: 768px) {
    .cust-tbl-scroll { overflow-x:auto !important; -webkit-overflow-scrolling:touch; }
    .cust-tbl-scroll table { min-width:500px !important; }
}
@media (max-width: 480px) {
    .cust-show-stats-grid { grid-template-columns: repeat(2,1fr); gap:8px; }
}
</style>
<script>window._reviewSuffix = @json("has been submitted for review. We'd love your feedback before we finalize approval.");</script>
<div x-data="{
    task: null,    openTask(t) { this.task = t; document.body.style.overflow='hidden'; },
    project: null, openProject(p) { this.project = p; document.body.style.overflow='hidden'; },
    statsModal: null,
    statsTab: 'tasks',
    taskGroups: {{ $taskGroupsJson }},
    customerProjects: {{ $customerProjectsJson }},
    openStats(group) { this.statsModal = group; this.statsTab = 'tasks'; document.body.style.overflow='hidden'; },
    get statsModalTasks() { return this.statsModal && this.taskGroups[this.statsModal] ? this.taskGroups[this.statsModal] : []; },
    get statsModalLabel() {
        const m = { pending: 'Pending', active: 'In Progress', done: 'Completed', overdue: 'Overdue' };
        return m[this.statsModal] || '';
    },

    // ── Send Update ──
    sendModal: false,
    sendChannel: 'whatsapp',
    sendProject: null,
    sendNote: '',
    sending: false,
    sendResult: null,
    cName:  @js($customer->name),
    cEmail: @js($customer->email ?? ''),
    cPhone: @js(preg_replace('/\D/', '', $customer->phone ?? '')),
    sendMode: 'text',
    selectedFile: null,
    customerFiles: {{ $customerFilesJson }},
    fileCaption: '',
    editableMsg: '',
    _reviewSuffix: window._reviewSuffix,

    get sendMsg() {
        if (this.sendMode === 'file' && this.selectedFile) {
            let msg = 'Hello ' + this.cName + ', your design for \x22' + this.selectedFile.task_title + '\x22 ' + this._reviewSuffix;
            if (this.fileCaption.trim()) msg += '\n\n' + this.fileCaption.trim();
            return msg;
        }
        const p = this.sendProject;
        let msg = `Hello ${this.cName},\n\n`;
        if (p) {
            msg += `Here is an update on your project '${p.name}':\n\n`;
            msg += `• Status: ${p.statusLabel}\n`;
            msg += `• Tasks: ${p.tasksCount} task${p.tasksCount !== 1 ? 's' : ''}`;
            if (p.deadline) msg += `\n• Deadline: ${p.deadline}${p.overdue ? ' ⚠ Overdue' : ''}`;
        } else {
            msg += `I hope this message finds you well.`;
        }
        if (this.sendNote.trim()) msg += `\n\n${this.sendNote.trim()}`;
        msg += `\n\nPlease feel free to reach out if you have any questions.`;
        return msg;
    },

    openSend(channel) {
        this.sendChannel  = channel || 'whatsapp';
        this.sendProject  = this.customerProjects.length === 1 ? this.customerProjects[0] : null;
        this.sendNote     = '';
        this.sendResult   = null;
        this.sending      = false;
        this.sendMode     = 'text';
        this.selectedFile = null;
        this.fileCaption  = '';
        this.editableMsg  = this.sendMsg;
        this.sendModal    = true;
        document.body.style.overflow = 'hidden';
    },
    async doSend() {
        this.sendResult = null;
        if (this.sendChannel === 'whatsapp') {
            if (!this.cPhone) { this.sendResult = { ok: false, message: 'No phone number on file for this customer.' }; return; }
            if (this.sendMode === 'file' && !this.selectedFile) { this.sendResult = { ok: false, message: 'Please select a file to send.' }; return; }
            this.sending = true;
            try {
                let fetchUrl, bodyData;
                if (this.sendMode === 'file') {
                    fetchUrl = '{{ route('admin.approvals.whatsapp-customer-media') }}';
                    bodyData = { phone: this.cPhone, file_url: this.selectedFile.file_url, filename: this.selectedFile.filename, caption: this.editableMsg };
                } else {
                    fetchUrl = '{{ route('admin.approvals.whatsapp-customer') }}';
                    bodyData = { phone: this.cPhone, message: this.editableMsg };
                }
                const res = await fetch(fetchUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(bodyData)
                });
                const d = await res.json();
                this.sendResult = d;
                if (d.ok) { this.sendNote = ''; this.fileCaption = ''; setTimeout(() => { this.close(); }, 2200); }
            } catch(e) {
                this.sendResult = { ok: false, message: 'Network error. Please try again.' };
            } finally {
                this.sending = false;
            }
        } else {
            if (!this.cEmail) { this.sendResult = { ok: false, message: 'No email address on file for this customer.' }; return; }
            const subj = this.sendProject ? `Project Update – ${this.sendProject.name}` : `Message for ${this.cName}`;
            window.location.href = 'mailto:' + this.cEmail + '?subject=' + encodeURIComponent(subj) + '&body=' + encodeURIComponent(this.editableMsg);
        }
    },

    approvalModal: null,
    approveNote: '',
    approving: false,
    openApproval(d) { this.approvalModal = d; this.approveNote = ''; this.approving = false; document.body.style.overflow='hidden'; },

    close() { this.task = null; this.project = null; this.statsModal = null; this.approvalModal = null; this.approveNote = ''; this.approving = false; this.sendModal = false; this.sendResult = null; this.sending = false; this.sendMode = 'text'; this.selectedFile = null; this.fileCaption = ''; document.body.style.overflow=''; }
}" x-init="editableMsg = sendMsg; $watch('sendMode', () => { editableMsg = sendMsg; }); $watch('selectedFile', () => { editableMsg = sendMsg; }); $watch('sendProject', () => { editableMsg = sendMsg; });" @keydown.escape.window="close()" style="max-width:900px;">

{{-- Project Preview Modal --}}
<template x-if="project">
<div style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;"
     @click.self="close()">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.22);">

        <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:22px 24px 0;">
            <div style="flex:1;min-width:0;padding-right:12px;">
                <p style="font-size:11px;color:#9CA3AF;margin:0 0 4px;text-transform:uppercase;letter-spacing:.04em;">Project</p>
                <h2 x-text="project.name" style="font-size:17px;font-weight:700;color:#111827;margin:0;line-height:1.35;"></h2>
            </div>
            <button @click="close()"
                    style="flex-shrink:0;width:32px;height:32px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:16px;">
                ×
            </button>
        </div>

        <div style="padding:20px 24px 24px;">

            <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
                <span x-text="project.statusLabel"
                      :style="'padding:3px 12px;border-radius:20px;font-size:12px;font-weight:600;background:'+project.statusBg+';color:'+project.statusColor+';'"></span>
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="width:110px;font-size:12px;color:#9CA3AF;flex-shrink:0;"><i class="fa fa-list-check" style="width:14px;margin-right:5px;"></i>Tasks</span>
                    <span x-text="project.tasksCount + ' tasks'" style="font-size:13px;font-weight:600;color:#111827;"></span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="width:110px;font-size:12px;color:#9CA3AF;flex-shrink:0;"><i class="fa fa-calendar" style="width:14px;margin-right:5px;"></i>Deadline</span>
                    <span x-text="project.deadline || '—'"
                          :style="'font-size:13px;font-weight:600;color:'+(project.overdue?'#DC2626':'#111827')+';'"></span>
                    <span x-show="project.overdue" style="font-size:11px;color:#DC2626;font-weight:600;">⚠ Overdue</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;" x-show="project.description">
                    <span style="width:110px;font-size:12px;color:#9CA3AF;flex-shrink:0;align-self:flex-start;padding-top:1px;"><i class="fa fa-align-left" style="width:14px;margin-right:5px;"></i>Description</span>
                    <span x-text="project.description" style="font-size:13px;color:#374151;line-height:1.55;"></span>
                </div>
            </div>

            <a :href="project.url"
               style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:10px;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;box-shadow:0 2px 8px rgba(99,102,241,.3);">
                <i class="fa fa-arrow-up-right-from-square" style="font-size:11px;"></i> Open Full Project
            </a>

        </div>
    </div>
</div>
</template>

{{-- Task Preview Modal --}}
<template x-if="task">
<div style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;"
     @click.self="close()">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.22);">

        {{-- Modal header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:22px 24px 0;">
            <div style="flex:1;min-width:0;padding-right:12px;">
                <p x-text="task.project" style="font-size:11px;color:#9CA3AF;margin:0 0 4px;text-transform:uppercase;letter-spacing:.04em;"></p>
                <h2 x-text="task.title" style="font-size:17px;font-weight:700;color:#111827;margin:0;line-height:1.35;"></h2>
            </div>
            <button @click="close()"
                    style="flex-shrink:0;width:32px;height:32px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:16px;">
                ×
            </button>
        </div>

        {{-- Modal body --}}
        <div style="padding:20px 24px 24px;">

            {{-- Status + Priority --}}
            <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
                <span x-text="task.statusLabel"
                      :style="'padding:3px 12px;border-radius:20px;font-size:12px;font-weight:600;background:'+task.statusBg+';color:'+task.statusColor+';'"></span>
                <span x-text="task.priorityLabel"
                      :style="'padding:3px 12px;border-radius:20px;font-size:12px;font-weight:600;background:'+task.priorityBg+';color:'+task.priorityColor+';'"
                      x-show="task.priorityLabel"></span>
            </div>

            {{-- Info rows --}}
            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="width:110px;font-size:12px;color:#9CA3AF;flex-shrink:0;"><i class="fa fa-user" style="width:14px;margin-right:5px;"></i>Assignee</span>
                    <span x-text="task.assignee || '—'" style="font-size:13px;font-weight:600;color:#111827;"></span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="width:110px;font-size:12px;color:#9CA3AF;flex-shrink:0;"><i class="fa fa-folder" style="width:14px;margin-right:5px;"></i>Project</span>
                    <span x-text="task.project || '—'" style="font-size:13px;font-weight:600;color:#111827;"></span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="width:110px;font-size:12px;color:#9CA3AF;flex-shrink:0;"><i class="fa fa-calendar" style="width:14px;margin-right:5px;"></i>Deadline</span>
                    <span x-text="task.deadline || '—'"
                          :style="'font-size:13px;font-weight:600;color:'+(task.overdue?'#DC2626':'#111827')+';'"></span>
                    <span x-show="task.overdue" style="font-size:11px;color:#DC2626;font-weight:600;">⚠ Overdue</span>
                </div>
                {{-- Overdue reason row --}}
                <template x-if="task.overdue && task.overdueReason">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="width:110px;font-size:12px;color:#9CA3AF;flex-shrink:0;"><i class="fa fa-circle-exclamation" style="width:14px;margin-right:5px;"></i>Blocked by</span>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;"
                          :style="'background:'+task.overdueReason.bg+';color:'+task.overdueReason.color+';'">
                        <i :class="'fas '+task.overdueReason.icon" style="font-size:10px;"></i>
                        <span x-text="task.overdueReason.label"></span>
                    </span>
                </div>
                </template>
                <div style="display:flex;align-items:center;gap:12px;" x-show="task.description">
                    <span style="width:110px;font-size:12px;color:#9CA3AF;flex-shrink:0;align-self:flex-start;padding-top:1px;"><i class="fa fa-align-left" style="width:14px;margin-right:5px;"></i>Description</span>
                    <span x-text="task.description" style="font-size:13px;color:#374151;line-height:1.55;"></span>
                </div>
            </div>

            {{-- Open full task button --}}
            <a :href="task.url"
               style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:10px;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;box-shadow:0 2px 8px rgba(99,102,241,.3);">
                <i class="fa fa-arrow-up-right-from-square" style="font-size:11px;"></i> Open Full Task
            </a>

        </div>
    </div>
</div>
</template>

{{-- Stats Group Modal --}}
<template x-if="statsModal !== null">
<div style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;"
     @click.self="close()">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:560px;max-height:82vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.22);">

        {{-- Modal header --}}
        <div style="padding:20px 24px 0;flex-shrink:0;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
                         :style="statsModal==='pending'?'background:#EEF2FF;':statsModal==='active'?'background:#FFF7ED;':statsModal==='done'?'background:#ECFDF5;':'background:#FEF2F2;'">
                        <i :class="statsModal==='pending'?'fas fa-clock':statsModal==='active'?'fas fa-spinner':statsModal==='done'?'fas fa-circle-check':'fas fa-triangle-exclamation'"
                           :style="statsModal==='pending'?'color:#6366F1;font-size:13px;':statsModal==='active'?'color:#EA580C;font-size:13px;':statsModal==='done'?'color:#16A34A;font-size:13px;':'color:#DC2626;font-size:13px;'"></i>
                    </div>
                    <h2 x-text="statsModalLabel" style="font-size:15px;font-weight:700;color:#111827;margin:0;"></h2>
                </div>
                <button @click="close()"
                        style="width:32px;height:32px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:16px;">×</button>
            </div>

            {{-- Tabs --}}
            <div style="display:flex;gap:2px;background:#F3F4F6;border-radius:12px;padding:4px;width:fit-content;">
                <button @click="statsTab='tasks'"
                        :style="statsTab==='tasks'
                            ? 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);'
                            : 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;'"
                        style="display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);">
                    <i class="fas fa-list-check" style="font-size:11px;"></i>
                    Tasks <span x-text="'('+statsModalTasks.length+')'" style="font-weight:400;opacity:.7;"></span>
                </button>
                <button @click="statsTab='projects'"
                        :style="statsTab==='projects'
                            ? 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);'
                            : 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;'"
                        style="display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;">
                    <i class="fas fa-folder" style="font-size:11px;"></i>
                    Projects <span x-text="'('+customerProjects.length+')'" style="font-weight:400;opacity:.7;"></span>
                </button>
            </div>
        </div>

        {{-- Tab content --}}
        <div style="overflow-y:auto;padding:8px 16px 20px;flex:1;">

            {{-- Tasks tab --}}
            <div x-show="statsTab==='tasks'">
                <template x-if="statsModalTasks.length === 0">
                    <p style="text-align:center;font-size:13px;color:#9CA3AF;padding:24px 0;">No tasks in this category.</p>
                </template>
                <template x-for="t in statsModalTasks" :key="t.url">
                    <a :href="t.url"
                       style="display:flex;align-items:center;gap:12px;padding:12px 8px;border-bottom:1px solid #F9FAFB;text-decoration:none;border-radius:8px;transition:background .12s;"
                       onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                        <div style="min-width:0;flex:1;">
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <p x-text="t.title" style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:220px;"></p>
                                {{-- Overdue reason badge --}}
                                <template x-if="t.overdue && t.overdueReason">
                                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;flex-shrink:0;white-space:nowrap;"
                                          :style="'background:'+t.overdueReason.bg+';color:'+t.overdueReason.color+';'">
                                        <i :class="'fa '+t.overdueReason.icon" style="font-size:9px;"></i>
                                        <span x-text="t.overdueReason.label"></span>
                                    </span>
                                </template>
                            </div>
                            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span style="display:flex;align-items:center;gap:3px;">
                                    <i class="fa fa-folder" style="font-size:9px;"></i><span x-text="t.project"></span>
                                </span>
                                <template x-if="t.assignee">
                                    <span style="display:flex;align-items:center;gap:3px;">
                                        <i class="fa fa-user" style="font-size:9px;"></i><span x-text="t.assignee"></span>
                                    </span>
                                </template>
                                <template x-if="t.deadline">
                                    <span style="display:flex;align-items:center;gap:3px;" :style="t.overdue?'color:#DC2626;font-weight:600;':''">
                                        <i class="fa fa-calendar" style="font-size:9px;"></i><span x-text="t.deadline"></span>
                                    </span>
                                </template>
                            </p>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                            <span x-text="t.statusLabel"
                                  :style="'font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:'+t.statusBg+';color:'+t.statusColor+';'"></span>
                            <i class="fa fa-arrow-up-right-from-square" style="font-size:10px;color:#D1D5DB;"></i>
                        </div>
                    </a>
                </template>
            </div>

            {{-- Projects tab --}}
            <div x-show="statsTab==='projects'">
                <template x-if="customerProjects.length === 0">
                    <p style="text-align:center;font-size:13px;color:#9CA3AF;padding:24px 0;">No projects linked to this customer.</p>
                </template>
                <template x-for="p in customerProjects" :key="p.url">
                    <a :href="p.url"
                       style="display:flex;align-items:center;gap:12px;padding:10px 8px;border-bottom:1px solid #F9FAFB;text-decoration:none;border-radius:8px;transition:background .12s;"
                       onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                        <div style="width:34px;height:34px;border-radius:9px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-folder-open" style="font-size:13px;color:#6B7280;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <p x-text="p.name" style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px;"></p>
                                <span x-show="p.overdue" style="font-size:10px;font-weight:600;color:#DC2626;flex-shrink:0;">⚠ Overdue</span>
                            </div>
                            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;display:flex;align-items:center;gap:8px;">
                                <span style="display:flex;align-items:center;gap:3px;">
                                    <i class="fa fa-list-check" style="font-size:9px;"></i>
                                    <span x-text="p.tasksCount+' task'+(p.tasksCount===1?'':'s')"></span>
                                </span>
                                <template x-if="p.deadline">
                                    <span style="display:flex;align-items:center;gap:3px;" :style="p.overdue?'color:#DC2626;font-weight:600;':''">
                                        <i class="fa fa-calendar" style="font-size:9px;"></i><span x-text="p.deadline"></span>
                                    </span>
                                </template>
                            </p>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                            <span x-text="p.statusLabel"
                                  :style="'font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:'+p.statusBg+';color:'+p.statusColor+';'"></span>
                            <i class="fa fa-arrow-up-right-from-square" style="font-size:10px;color:#D1D5DB;"></i>
                        </div>
                    </a>
                </template>
            </div>

        </div>
    </div>
</div>
</template>

    {{-- ══ SEND UPDATE MODAL ══ --}}
    <template x-if="sendModal">
    <div style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;" @click.self="close()">
        <div style="position:absolute;inset:0;background:rgba(10,12,30,.6);backdrop-filter:blur(4px);"></div>
        <div style="position:relative;background:#fff;border-radius:24px;width:100%;max-width:520px;max-height:92vh;display:flex;flex-direction:column;box-shadow:0 32px 90px rgba(0,0,0,.28);overflow:hidden;">

            {{-- Header --}}
            <div style="padding:20px 24px 18px;border-bottom:1px solid #F0F2F8;display:flex;align-items:center;gap:12px;flex-shrink:0;background:linear-gradient(135deg,#F8F9FF,#fff);">
                @if($customer->logo)
                <img src="{{ Storage::url($customer->logo) }}" alt="{{ $customer->name }}"
                     style="width:42px;height:42px;border-radius:11px;object-fit:cover;border:1.5px solid #E5E7EB;flex-shrink:0;">
                @else
                <div style="width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff;flex-shrink:0;">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                @endif
                <div style="flex:1;min-width:0;">
                    <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0;">Send to {{ $customer->name }}</h3>
                    @if($customer->company)<p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">{{ $customer->company }}</p>@endif
                </div>
                <button @click="close()"
                        style="width:32px;height:32px;border-radius:9px;background:#F3F4F6;border:none;cursor:pointer;color:#6B7280;font-size:13px;display:flex;align-items:center;justify-content:center;transition:background .15s;"
                        onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Body --}}
            <div style="overflow-y:auto;flex:1;padding:20px 24px;">

                {{-- Channel selector --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:20px;">
                    <button type="button" @click="sendChannel='whatsapp'"
                            :style="sendChannel==='whatsapp'
                                ? 'display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border-radius:12px;border:2px solid #25D366;background:#F0FDF4;color:#16A34A;font-size:13px;font-weight:700;cursor:pointer;transition:all .15s;'
                                : 'display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border-radius:12px;border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;'">
                        <i class="fab fa-whatsapp" :style="sendChannel==='whatsapp' ? 'font-size:17px;color:#25D366;' : 'font-size:17px;color:#9CA3AF;'"></i>
                        WhatsApp
                    </button>
                    <button type="button" @click="sendChannel='email'"
                            :style="sendChannel==='email'
                                ? 'display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border-radius:12px;border:2px solid #6366F1;background:#EEF2FF;color:#4F46E5;font-size:13px;font-weight:700;cursor:pointer;transition:all .15s;'
                                : 'display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border-radius:12px;border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;'">
                        <i class="fas fa-envelope" :style="sendChannel==='email' ? 'font-size:14px;color:#6366F1;' : 'font-size:14px;color:#9CA3AF;'"></i>
                        Email
                    </button>
                </div>

                {{-- Contact info strip --}}
                <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#F8FAFF;border-radius:10px;border:1px solid #EEF2FF;margin-bottom:18px;">
                    <template x-if="sendChannel==='whatsapp'">
                        <div style="display:flex;align-items:center;gap:8px;width:100%;">
                            <i class="fab fa-whatsapp" style="color:#25D366;font-size:16px;flex-shrink:0;"></i>
                            <span style="font-size:13px;color:#374151;font-weight:600;">{{ $customer->phone ?? '—' }}</span>
                            @if(!$customer->phone)
                            <span style="font-size:11px;color:#DC2626;margin-left:4px;">No phone on file —
                                <a href="{{ route('admin.customers.edit', $customer) }}" style="color:#4F46E5;text-decoration:underline;">add one</a>
                            </span>
                            @endif
                        </div>
                    </template>
                    <template x-if="sendChannel==='email'">
                        <div style="display:flex;align-items:center;gap:8px;width:100%;">
                            <i class="fas fa-envelope" style="color:#6366F1;font-size:14px;flex-shrink:0;"></i>
                            <span style="font-size:13px;color:#374151;font-weight:600;">{{ $customer->email ?? '—' }}</span>
                            @if(!$customer->email)
                            <span style="font-size:11px;color:#DC2626;margin-left:4px;">No email on file —
                                <a href="{{ route('admin.customers.edit', $customer) }}" style="color:#4F46E5;text-decoration:underline;">add one</a>
                            </span>
                            @endif
                        </div>
                    </template>
                </div>

                {{-- Mode toggle: Text Update vs Send File (WhatsApp only, only if files exist) --}}
                <div x-show="sendChannel==='whatsapp' && customerFiles.length > 0" style="margin-bottom:18px;">
                    <label style="display:block;font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:7px;">
                        <i class="fas fa-paper-plane" style="color:#A78BFA;font-size:10px;margin-right:3px;"></i> What to send
                    </label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <button type="button" @click="sendMode='text'"
                                :style="sendMode==='text'
                                    ? 'display:flex;align-items:center;justify-content:center;gap:7px;padding:9px;border-radius:10px;border:2px solid #6366F1;background:#EEF2FF;color:#4F46E5;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s;'
                                    : 'display:flex;align-items:center;justify-content:center;gap:7px;padding:9px;border-radius:10px;border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;'">
                            <i class="fas fa-comment-dots" style="font-size:12px;"></i> Text Update
                        </button>
                        <button type="button" @click="sendMode='file'"
                                :style="sendMode==='file'
                                    ? 'display:flex;align-items:center;justify-content:center;gap:7px;padding:9px;border-radius:10px;border:2px solid #6366F1;background:#EEF2FF;color:#4F46E5;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s;'
                                    : 'display:flex;align-items:center;justify-content:center;gap:7px;padding:9px;border-radius:10px;border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;'">
                            <i class="fas fa-paperclip" style="font-size:12px;"></i> Send File
                            <span x-text="'(' + customerFiles.length + ')'" style="font-size:10px;font-weight:400;opacity:.7;"></span>
                        </button>
                    </div>
                </div>

                {{-- TEXT MODE: Project selector --}}
                <div x-show="sendMode==='text'">
                    @if($customer->projects->count() > 0)
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                            <i class="fas fa-folder" style="color:#A78BFA;font-size:10px;margin-right:3px;"></i> Project (optional)
                        </label>
                        <select @change="sendProject = $event.target.value ? customerProjects.find(p => p.name === $event.target.value) : null"
                                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;outline:none;cursor:pointer;transition:border-color .15s;"
                                onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                            <option value="">— No specific project —</option>
                            @foreach($customer->projects as $proj)
                            <option value="{{ $proj->name }}" {{ $customer->projects->count() === 1 ? 'selected' : '' }}>
                                {{ $proj->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <template x-if="sendProject">
                        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#F8F9FF;border:1.5px solid #EEF2FF;border-radius:10px;margin-bottom:16px;">
                            <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#EEF2FF,#DDD6FE);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-folder-open" style="color:#6366F1;font-size:13px;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <p style="font-size:13px;font-weight:600;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="sendProject.name"></p>
                                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <span x-text="sendProject.statusLabel" :style="`padding:1px 7px;border-radius:20px;font-weight:600;background:${sendProject.statusBg};color:${sendProject.statusColor};`"></span>
                                    <span x-text="sendProject.tasksCount+' task'+(sendProject.tasksCount!==1?'s':'')"></span>
                                    <template x-if="sendProject.deadline">
                                        <span :style="sendProject.overdue?'color:#DC2626;font-weight:600;':''" x-text="sendProject.deadline+(sendProject.overdue?' ⚠':'')"></span>
                                    </template>
                                </p>
                            </div>
                            <a :href="sendProject.url" target="_blank" rel="noopener"
                               style="font-size:10px;color:#6366F1;text-decoration:none;white-space:nowrap;display:flex;align-items:center;gap:3px;flex-shrink:0;"
                               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                <i class="fas fa-arrow-up-right-from-square" style="font-size:9px;"></i> View
                            </a>
                        </div>
                    </template>
                    @endif
                </div>

                {{-- Editable message --}}
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                        <i class="fas fa-comment-dots" style="color:#A78BFA;font-size:10px;margin-right:3px;"></i>
                        <span x-text="sendMode==='file' ? 'Message (sent with file)' : 'Message'"></span>
                        <span style="font-size:10px;font-weight:400;color:#9CA3AF;margin-left:4px;">— editable</span>
                    </label>
                    <textarea x-model="editableMsg" rows="5"
                              style="width:100%;padding:13px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:12px;color:#374151;line-height:1.7;resize:vertical;box-sizing:border-box;font-family:inherit;outline:none;transition:border-color .15s;"
                              onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                </div>

                {{-- TEXT MODE: Personal note --}}
                <div x-show="sendMode==='text'">
                    <label style="display:block;font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                        <i class="fas fa-pen" style="color:#A78BFA;font-size:9px;margin-right:3px;"></i> Add a personal note <span style="font-weight:400;color:#9CA3AF;">(optional)</span>
                    </label>
                    <textarea x-model="sendNote" rows="2" placeholder="e.g. Let us know your thoughts, we're happy to revise…"
                              style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:12px;color:#111827;outline:none;resize:vertical;box-sizing:border-box;line-height:1.6;transition:border-color .15s;font-family:inherit;"
                              onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                </div>

                {{-- FILE MODE: File picker + extra note --}}
                <div x-show="sendMode==='file'">
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                            <i class="fas fa-paperclip" style="color:#A78BFA;font-size:10px;margin-right:3px;"></i> Select file to send
                        </label>
                        <select @change="selectedFile = $event.target.value ? customerFiles.find(f => f.file_url === $event.target.value) : null"
                                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:12px;color:#111827;background:#fff;outline:none;cursor:pointer;transition:border-color .15s;"
                                onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                            <option value="">— Choose a submission file —</option>
                            @foreach($customer->tasks as $t)
                                @foreach($t->submissions ?? [] as $sub)
                                    @if($sub->file_path)
                                    <option value="{{ url(\Illuminate\Support\Facades\Storage::url($sub->file_path)) }}">
                                        {{ $t->title }} — v{{ $sub->version }} ({{ $sub->original_filename ?? basename($sub->file_path) }})
                                    </option>
                                    @endif
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <template x-if="selectedFile">
                        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#F8F9FF;border:1.5px solid #EEF2FF;border-radius:10px;margin-bottom:16px;">
                            <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#EEF2FF,#DDD6FE);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-file" style="color:#6366F1;font-size:13px;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <p style="font-size:13px;font-weight:600;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="selectedFile.filename"></p>
                                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <span x-text="selectedFile.task_title"></span>
                                    <span>·</span>
                                    <span x-text="'v' + selectedFile.version"></span>
                                    <span x-text="selectedFile.status_label"
                                          :style="selectedFile.status==='approved' ? 'padding:1px 7px;border-radius:20px;font-weight:600;background:#D1FAE5;color:#065F46;' : (selectedFile.status==='submitted' ? 'padding:1px 7px;border-radius:20px;font-weight:600;background:#EDE9FE;color:#5B21B6;' : 'padding:1px 7px;border-radius:20px;font-weight:600;background:#FEE2E2;color:#991B1B;')"></span>
                                </p>
                            </div>
                        </div>
                    </template>

                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                            <i class="fas fa-pen" style="color:#A78BFA;font-size:9px;margin-right:3px;"></i> Add a personal note <span style="font-weight:400;color:#9CA3AF;">(optional — appended to message)</span>
                        </label>
                        <textarea x-model="fileCaption" rows="2" placeholder="e.g. Please let us know if you'd like any changes…"
                                  style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:12px;color:#111827;outline:none;resize:vertical;box-sizing:border-box;line-height:1.6;transition:border-color .15s;font-family:inherit;"
                                  onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                    </div>
                </div>

            </div>

            {{-- Result feedback --}}
            <div x-show="sendResult" x-transition style="margin:0 24px 12px;">
                <div :style="sendResult && sendResult.ok
                        ? 'display:flex;align-items:center;gap:8px;padding:10px 14px;background:#ECFDF5;border:1px solid #6EE7B7;border-radius:10px;font-size:13px;font-weight:600;color:#065F46;'
                        : 'display:flex;align-items:center;gap:8px;padding:10px 14px;background:#FEE2E2;border:1px solid #FCA5A5;border-radius:10px;font-size:13px;font-weight:600;color:#991B1B;'">
                    <i :class="sendResult && sendResult.ok ? 'fas fa-circle-check' : 'fas fa-circle-exclamation'" style="font-size:14px;flex-shrink:0;"></i>
                    <span x-text="sendResult ? (typeof sendResult.message === 'string' ? sendResult.message : JSON.stringify(sendResult.message)) : ''"></span>
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding:14px 24px;border-top:1px solid #F0F2F8;display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-shrink:0;background:#FAFBFF;">
                <button type="button" @click="close()"
                        style="padding:9px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s;"
                        onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                    Cancel
                </button>
                <button type="button" @click="doSend()" :disabled="sending || (sendMode==='file' && !selectedFile)"
                        :style="sendChannel==='whatsapp'
                            ? 'display:inline-flex;align-items:center;gap:7px;padding:9px 22px;background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 3px 10px rgba(37,211,102,.3);transition:opacity .15s;'
                            : 'display:inline-flex;align-items:center;gap:7px;padding:9px 22px;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 3px 10px rgba(99,102,241,.3);transition:opacity .15s;'"
                        onmouseover="if(!sending)this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                    <template x-if="sending">
                        <i class="fas fa-spinner fa-spin" style="font-size:14px;"></i>
                    </template>
                    <template x-if="!sending">
                        <i :class="sendChannel==='whatsapp' ? (sendMode==='file' ? 'fas fa-paperclip' : 'fab fa-whatsapp') : 'fas fa-paper-plane'"
                           :style="sendChannel==='whatsapp' ? 'font-size:15px;' : 'font-size:13px;'"></i>
                    </template>
                    <span x-text="sending ? 'Sending…' : (sendChannel==='whatsapp' ? (sendMode==='file' ? 'Send File via WhatsApp' : 'Send via WhatsApp') : 'Open Email Client ↗')"></span>
                </button>
            </div>

        </div>
    </div>
    </template>

    {{-- Header --}}
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('admin.customers.index') }}" class="no-print"
           style="width:34px;height:34px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;">
            <i class="fa fa-arrow-left" style="font-size:13px;"></i>
        </a>
        <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;">
                @if($customer->logo)
                <img src="{{ Storage::url($customer->logo) }}" alt="{{ $customer->name }}"
                     style="width:48px;height:48px;border-radius:12px;object-fit:cover;border:1.5px solid #E5E7EB;flex-shrink:0;">
                @else
                <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                @endif
                <div>
                    <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">{{ $customer->name }}</h1>
                    @if($customer->company)
                    <p style="font-size:13px;color:#9CA3AF;margin:1px 0 0;">{{ $customer->company }}</p>
                    @endif
                </div>
            </div>
        </div>
        <a href="{{ route('admin.customers.edit', $customer) }}" class="no-print"
           style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:#EEF2FF;color:#4F46E5;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;">
            <i class="fas fa-pencil" style="font-size:11px;"></i> Edit
        </a>

        {{-- Export / Print dropdown --}}
        <div x-data="{ exportOpen: false }" style="position:relative;">
            <button @click="exportOpen = !exportOpen" @click.outside="exportOpen = false"
                    style="display:inline-flex;align-items:center;gap:7px;padding:8px 14px;background:#F9FAFB;border:1px solid #E5E7EB;color:#374151;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="fas fa-download" style="font-size:11px;"></i> Export
                <i class="fas fa-chevron-down" style="font-size:9px;transition:transform .2s;" :style="exportOpen ? 'transform:rotate(180deg)' : ''"></i>
            </button>
            <div x-show="exportOpen" x-transition
                 style="position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #E5E7EB;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.1);min-width:170px;z-index:200;overflow:hidden;">
                <button @click="exportOpen=false; window.exportCustomerCSV()"
                        style="width:100%;padding:10px 16px;background:none;border:none;text-align:left;font-size:13px;font-weight:500;color:#374151;cursor:pointer;display:flex;align-items:center;gap:9px;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                    <i class="fas fa-file-csv" style="font-size:13px;color:#16A34A;width:16px;text-align:center;"></i> Export CSV
                </button>
                <div style="height:1px;background:#F3F4F6;"></div>
                <button @click="exportOpen=false; window.exportCustomerExcel()"
                        style="width:100%;padding:10px 16px;background:none;border:none;text-align:left;font-size:13px;font-weight:500;color:#374151;cursor:pointer;display:flex;align-items:center;gap:9px;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                    <i class="fas fa-file-excel" style="font-size:13px;color:#16A34A;width:16px;text-align:center;"></i> Export Excel
                </button>
                <div style="height:1px;background:#F3F4F6;"></div>
                <button @click="exportOpen=false; window.printCustomerPage()"
                        style="width:100%;padding:10px 16px;background:none;border:none;text-align:left;font-size:13px;font-weight:500;color:#374151;cursor:pointer;display:flex;align-items:center;gap:9px;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                    <i class="fas fa-print" style="font-size:13px;color:#6366F1;width:16px;text-align:center;"></i> Print / PDF
                </button>
            </div>
        </div>
    </div>

    {{-- Quick Stats Bar --}}
    @php
        $allTasks     = $customer->tasks;
        $statPending  = $allTasks->whereIn('status', ['draft','assigned','viewed'])->count();
        $statActive   = $allTasks->whereIn('status', ['in_progress','submitted','revision_requested'])->count();
        $statDone     = $allTasks->whereIn('status', ['approved','delivered','archived'])->count();
        $statOverdue  = $allTasks->filter(fn($t) => $t->deadline && $t->deadline->isPast() && !in_array($t->status, ['approved','delivered','archived']))->count();

        // CSV export data
        $_statusLabel = ['draft'=>'Draft','assigned'=>'Assigned','viewed'=>'Viewed','in_progress'=>'In Progress','submitted'=>'In Review','revision_requested'=>'Revision','approved'=>'Approved','delivered'=>'Delivered','archived'=>'Archived','pending_customer'=>'Awaiting Customer'];
        $csvRows = $allTasks->map(fn($t) => [
            'title'          => $t->title,
            'project'        => $t->project->name ?? '',
            'status'         => $_statusLabel[$t->status] ?? ucfirst(str_replace('_',' ',$t->status)),
            'priority'       => ucfirst($t->priority ?? ''),
            'assignee'       => $t->assignee->name ?? '',
            'deadline'       => $t->deadline ? $t->deadline->format('Y-m-d') : '',
            'design_sent'    => $t->design_sent_at ? $t->design_sent_at->format('Y-m-d H:i') : '',
            'cust_approved'  => $t->customer_approved_at ? $t->customer_approved_at->format('Y-m-d H:i') : '',
            'approval_time'  => ($t->design_sent_at && $t->customer_approved_at)
                                ? round(abs($t->design_sent_at->diffInSeconds($t->customer_approved_at)) / 3600, 1) . 'h'
                                : '',
        ])->values()->toArray();
    @endphp
    <div class="cust-show-stats-grid">
        <button @click="openStats('pending')"
                style="background:#fff;border-radius:12px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:14px 16px;display:flex;align-items:center;gap:12px;cursor:pointer;text-align:left;transition:box-shadow .15s,border-color .15s;"
                onmouseover="this.style.boxShadow='0 4px 12px rgba(99,102,241,.15)';this.style.borderColor='#C7D2FE';"
                onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.04)';this.style.borderColor='#F0F0F0';">
            <div style="width:38px;height:38px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-clock" style="color:#6366F1;font-size:14px;"></i>
            </div>
            <div>
                <p style="font-size:20px;font-weight:700;color:#6366F1;margin:0;line-height:1;">{{ $statPending }}</p>
                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Pending</p>
            </div>
        </button>
        <button @click="openStats('active')"
                style="background:#fff;border-radius:12px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:14px 16px;display:flex;align-items:center;gap:12px;cursor:pointer;text-align:left;transition:box-shadow .15s,border-color .15s;"
                onmouseover="this.style.boxShadow='0 4px 12px rgba(234,88,12,.15)';this.style.borderColor='#FED7AA';"
                onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.04)';this.style.borderColor='#F0F0F0';">
            <div style="width:38px;height:38px;border-radius:10px;background:#FFF7ED;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-spinner" style="color:#EA580C;font-size:14px;"></i>
            </div>
            <div>
                <p style="font-size:20px;font-weight:700;color:#EA580C;margin:0;line-height:1;">{{ $statActive }}</p>
                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">In Progress</p>
            </div>
        </button>
        <button @click="openStats('done')"
                style="background:#fff;border-radius:12px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:14px 16px;display:flex;align-items:center;gap:12px;cursor:pointer;text-align:left;transition:box-shadow .15s,border-color .15s;"
                onmouseover="this.style.boxShadow='0 4px 12px rgba(22,163,74,.15)';this.style.borderColor='#A7F3D0';"
                onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.04)';this.style.borderColor='#F0F0F0';">
            <div style="width:38px;height:38px;border-radius:10px;background:#ECFDF5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-circle-check" style="color:#16A34A;font-size:14px;"></i>
            </div>
            <div>
                <p style="font-size:20px;font-weight:700;color:#16A34A;margin:0;line-height:1;">{{ $statDone }}</p>
                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Completed</p>
            </div>
        </button>
        <button @click="openStats('overdue')"
                style="background:#fff;border-radius:12px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:14px 16px;display:flex;align-items:center;gap:12px;cursor:pointer;text-align:left;transition:box-shadow .15s,border-color .15s;"
                onmouseover="this.style.boxShadow='0 4px 12px rgba(220,38,38,.15)';this.style.borderColor='#FECACA';"
                onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.04)';this.style.borderColor='#F0F0F0';">
            <div style="width:38px;height:38px;border-radius:10px;background:#FEF2F2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-triangle-exclamation" style="color:#DC2626;font-size:14px;"></i>
            </div>
            <div>
                <p style="font-size:20px;font-weight:700;color:#DC2626;margin:0;line-height:1;">{{ $statOverdue }}</p>
                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Overdue</p>
            </div>
        </button>
    </div>

    <div class="cust-show-split" style="gap:20px;align-items:start;">

        {{-- Contact card --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:22px;">
            <h2 style="font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin:0 0 16px;">Contact Info</h2>

            {{-- Email --}}
            @if($customer->email)
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <div style="width:32px;height:32px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-envelope" style="font-size:13px;color:#6366F1;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Email</p>
                    <a href="mailto:{{ $customer->email }}" style="font-size:12px;color:#111827;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;">{{ $customer->email }}</a>
                </div>
            </div>
            @else
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;opacity:.5;">
                <div style="width:32px;height:32px;border-radius:8px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-envelope" style="font-size:13px;color:#9CA3AF;"></i>
                </div>
                <div>
                    <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Email</p>
                    <p style="font-size:12px;color:#D1D5DB;margin:0;">Not on file</p>
                </div>
            </div>
            @endif

            {{-- Phone --}}
            @if($customer->phone)
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                <div style="width:32px;height:32px;border-radius:8px;background:#F0FDF4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-phone" style="font-size:13px;color:#16A34A;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Phone / WhatsApp</p>
                    <a href="tel:{{ $customer->phone }}" style="font-size:12px;color:#111827;text-decoration:none;">{{ $customer->phone }}</a>
                </div>
            </div>
            @else
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;opacity:.5;">
                <div style="width:32px;height:32px;border-radius:8px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-phone" style="font-size:13px;color:#9CA3AF;"></i>
                </div>
                <div>
                    <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Phone / WhatsApp</p>
                    <p style="font-size:12px;color:#D1D5DB;margin:0;">Not on file</p>
                </div>
            </div>
            @endif

            {{-- Quick action buttons --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px;">
                <button type="button" @click="openSend('whatsapp')"
                        style="display:flex;align-items:center;justify-content:center;gap:6px;padding:9px 10px;background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(37,211,102,.25);transition:opacity .15s;"
                        onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'"
                        title="{{ $customer->phone ? 'Send via WhatsApp' : 'No phone on file' }}">
                    <i class="fab fa-whatsapp" style="font-size:14px;"></i> WhatsApp
                </button>
                <button type="button" @click="openSend('email')"
                        style="display:flex;align-items:center;justify-content:center;gap:6px;padding:9px 10px;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(99,102,241,.25);transition:opacity .15s;"
                        onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'"
                        title="{{ $customer->email ? 'Send via Email' : 'No email on file' }}">
                    <i class="fas fa-paper-plane" style="font-size:12px;"></i> Email
                </button>
            </div>

            @if($customer->notes)
            <div style="margin-bottom:16px;padding:12px 14px;background:#FAFAFA;border-radius:10px;border:1px solid #F3F4F6;">
                <p style="font-size:10px;color:#9CA3AF;margin:0 0 5px;text-transform:uppercase;letter-spacing:.04em;display:flex;align-items:center;gap:4px;">
                    <i class="fas fa-note-sticky" style="font-size:9px;"></i> Notes
                </p>
                <p style="font-size:12px;color:#374151;margin:0;line-height:1.6;white-space:pre-wrap;">{{ $customer->notes }}</p>
            </div>
            @endif

            <div style="padding-top:16px;border-top:1px solid #F3F4F6;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;text-align:center;">
                    <div style="background:#F9FAFB;border-radius:10px;padding:12px;">
                        <p style="font-size:20px;font-weight:700;color:#4F46E5;margin:0;">{{ $customer->projects->count() }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Projects</p>
                    </div>
                    <div style="background:#F9FAFB;border-radius:10px;padding:12px;">
                        <p style="font-size:20px;font-weight:700;color:#16A34A;margin:0;">{{ $customer->tasks->count() }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Tasks</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Projects + Tasks --}}
        <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- Projects --}}
            <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:22px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h2 style="font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin:0;">
                        Projects <span style="font-weight:500;color:#9CA3AF;">({{ $customer->projects->count() }})</span>
                    </h2>
                    <a href="{{ route('admin.projects.create') }}"
                       style="font-size:11px;font-weight:600;color:#4F46E5;background:#EEF2FF;padding:4px 10px;border-radius:6px;text-decoration:none;">
                        + New Project
                    </a>
                </div>
                @php
                    $psBg    = ['active'=>'#EEF2FF','completed'=>'#ECFDF5','on_hold'=>'#FEF3C7','cancelled'=>'#FEE2E2'];
                    $psColor = ['active'=>'#4F46E5','completed'=>'#16A34A','on_hold'=>'#D97706','cancelled'=>'#DC2626'];
                    $psLabel = ['active'=>'Active','completed'=>'Completed','on_hold'=>'On Hold','cancelled'=>'Cancelled'];
                @endphp
                @forelse($customer->projects as $project)
                @php
                    $projectData = json_encode([
                        'name'        => $project->name,
                        'statusLabel' => $psLabel[$project->status] ?? ucfirst($project->status),
                        'statusBg'    => $psBg[$project->status]    ?? '#F3F4F6',
                        'statusColor' => $psColor[$project->status]  ?? '#374151',
                        'tasksCount'  => $project->tasks_count,
                        'deadline'    => $project->deadline ? $project->deadline->format(config('app.date_format', 'M d, Y')) : null,
                        'overdue'     => $project->deadline && $project->deadline->isPast() && $project->status !== 'completed',
                        'description' => $project->description ? \Illuminate\Support\Str::limit($project->description, 200) : null,
                        'url'         => route('admin.projects.show', $project->id),
                    ]);
                @endphp
                <button @click="openProject({{ $projectData }})"
                        style="display:flex;align-items:center;justify-content:space-between;padding:10px 8px;border-bottom:1px solid #F9FAFB;width:100%;background:none;border-left:none;border-right:none;border-top:none;cursor:pointer;text-align:left;transition:background .12s;border-radius:6px;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                    <div style="min-width:0;flex:1;padding-right:12px;">
                        <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $project->name }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">
                            {{ $project->tasks_count }} tasks
                            @if($project->deadline) · {{ $project->deadline->format(config('app.date_format', 'M d, Y')) }} @endif
                        </p>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                        <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:{{ $psBg[$project->status] ?? '#F3F4F6' }};color:{{ $psColor[$project->status] ?? '#374151' }};">
                            {{ $psLabel[$project->status] ?? ucfirst($project->status) }}
                        </span>
                        <i class="fa fa-chevron-right" style="font-size:10px;color:#D1D5DB;"></i>
                    </div>
                </button>
                @empty
                <p style="font-size:13px;color:#9CA3AF;text-align:center;padding:16px 0 4px;">No projects linked yet.</p>
                @endforelse
            </div>

            {{-- Tasks (direct only — tasks in customer projects are shown via the Projects section) --}}
            @php
                $directTasks = $customer->tasks->where('customer_id', $customer->id)->values();
                $tsBg    = ['draft'=>'#F3F4F6','assigned'=>'#EEF2FF','viewed'=>'#E0F2FE','in_progress'=>'#FFF7ED','submitted'=>'#F5F3FF','revision_requested'=>'#FEE2E2','approved'=>'#ECFDF5','delivered'=>'#ECFDF5','archived'=>'#F3F4F6'];
                $tsColor = ['draft'=>'#6B7280','assigned'=>'#4F46E5','viewed'=>'#0369A1','in_progress'=>'#EA580C','submitted'=>'#7C3AED','revision_requested'=>'#DC2626','approved'=>'#16A34A','delivered'=>'#16A34A','archived'=>'#9CA3AF'];
                $tsLabel = ['draft'=>'Draft','assigned'=>'Assigned','viewed'=>'Viewed','in_progress'=>'In Progress','submitted'=>'In Review','revision_requested'=>'Revision','approved'=>'Approved','delivered'=>'Delivered','archived'=>'Archived'];
                $prBg    = ['high'=>'#FEE2E2','medium'=>'#FEF3C7','low'=>'#DCFCE7'];
                $prColor = ['high'=>'#DC2626','medium'=>'#D97706','low'=>'#16A34A'];
            @endphp
            <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:22px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                    <h2 style="font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin:0;">
                        Tasks <span style="font-weight:500;color:#9CA3AF;">({{ $directTasks->count() }})</span>
                    </h2>
                </div>

                @forelse($directTasks as $task)
                @php
                    $isOverdue = $task->deadline && $task->deadline->isPast() && !in_array($task->status, ['approved','delivered','archived']);
                    $tGroup    = in_array($task->status, ['draft','assigned','viewed']) ? 'pending'
                               : (in_array($task->status, ['in_progress','submitted','revision_requested']) ? 'active' : 'done');
                    $taskData  = json_encode([
                        'title'         => $task->title,
                        'project'       => $task->project->name ?? '—',
                        'assignee'      => $task->assignee->name ?? null,
                        'status'        => $task->status,
                        'statusLabel'   => $tsLabel[$task->status] ?? ucfirst(str_replace('_',' ',$task->status)),
                        'statusBg'      => $tsBg[$task->status]    ?? '#F3F4F6',
                        'statusColor'   => $tsColor[$task->status]  ?? '#374151',
                        'priorityLabel' => $task->priority ? ucfirst($task->priority) : null,
                        'priorityBg'    => $prBg[$task->priority]   ?? '#F3F4F6',
                        'priorityColor' => $prColor[$task->priority] ?? '#374151',
                        'deadline'      => $task->deadline ? $task->deadline->format(config('app.date_format', 'M d, Y')) : null,
                        'overdue'       => $isOverdue,
                        'description'   => $task->description ? \Illuminate\Support\Str::limit($task->description, 200) : null,
                        'url'           => route('admin.tasks.show', $task->id),
                    ]);
                @endphp
                <button @click="openTask({{ $taskData }})"
                        style="display:flex;align-items:center;justify-content:space-between;padding:10px 8px;border-bottom:1px solid #F9FAFB;width:100%;background:none;border-left:none;border-right:none;border-top:none;cursor:pointer;text-align:left;transition:background .12s;border-radius:6px;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                    <div style="min-width:0;flex:1;padding-right:12px;">
                        <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $task->title }}
                            @if($isOverdue)
                            <span style="font-size:10px;font-weight:600;color:#DC2626;margin-left:6px;">⚠ Overdue</span>
                            @endif
                        </p>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">
                            {{ $task->project->name ?? '—' }}
                            @if($task->assignee) · {{ $task->assignee->name }} @endif
                            @if($task->deadline) · {{ $task->deadline->format(config('app.date_format', 'M d, Y')) }} @endif
                        </p>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                        <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:{{ $tsBg[$task->status] ?? '#F3F4F6' }};color:{{ $tsColor[$task->status] ?? '#374151' }};">
                            {{ $tsLabel[$task->status] ?? ucfirst(str_replace('_', ' ', $task->status)) }}
                        </span>
                        <i class="fa fa-chevron-right" style="font-size:10px;color:#D1D5DB;"></i>
                    </div>
                </button>
                @empty
                <p style="font-size:13px;color:#9CA3AF;text-align:center;padding:16px 0 4px;">No tasks linked yet.</p>
                @endforelse

            </div>

        </div>
    </div>

    {{-- ══ Design Approval Timeline ══ --}}
    @php
        $approvalTasks = $customer->tasks
            ->filter(fn($t) => !is_null($t->design_sent_at) || $t->status === 'pending_customer')
            ->sortByDesc(fn($t) => $t->design_sent_at ?? $t->updated_at);
        $approvedRows = $approvalTasks->filter(fn($t) => !is_null($t->customer_approved_at));
        $avgH = $approvedRows->isNotEmpty()
            ? round($approvedRows->avg(fn($t) => $t->design_sent_at->diffInMinutes($t->customer_approved_at) / 60), 1)
            : null;
    @endphp
    <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:22px;margin-top:20px;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
            <h2 style="font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin:0;display:flex;align-items:center;gap:8px;">
                <span style="width:22px;height:22px;border-radius:6px;background:#FEF3C7;display:inline-flex;align-items:center;justify-content:center;">
                    <i class="fas fa-stopwatch" style="font-size:10px;color:#D97706;"></i>
                </span>
                Design Approval Timeline
            </h2>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                @if($approvedRows->isNotEmpty())
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:20px;font-size:11px;font-weight:700;color:#16A34A;">
                    <i class="fas fa-circle-check" style="font-size:10px;"></i>
                    {{ $approvedRows->count() }} approved
                </span>
                @endif
                @if($approvalTasks->filter(fn($t) => is_null($t->customer_approved_at))->isNotEmpty())
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:20px;font-size:11px;font-weight:700;color:#D97706;">
                    <i class="fas fa-hourglass-half" style="font-size:10px;"></i>
                    {{ $approvalTasks->filter(fn($t) => is_null($t->customer_approved_at))->count() }} waiting
                </span>
                @endif
                @if($avgH !== null)
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:20px;font-size:11px;font-weight:700;color:#4F46E5;">
                    <i class="fas fa-clock" style="font-size:10px;"></i>
                    Avg {{ $avgH }}h to approve
                </span>
                @endif
            </div>
        </div>

        @if($approvalTasks->isEmpty())
        <div style="text-align:center;padding:36px 20px;">
            <div style="width:46px;height:46px;border-radius:13px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="fas fa-stopwatch" style="font-size:19px;color:#D97706;"></i>
            </div>
            <p style="font-size:14px;font-weight:700;color:#111827;margin:0 0 5px;">No approval data yet</p>
            <p style="font-size:12px;color:#9CA3AF;margin:0 auto;max-width:340px;line-height:1.6;">Once a manager marks one of this customer's tasks as <strong>"Awaiting Customer Approval"</strong> from the Approvals page, the send time and approval duration will appear here.</p>
        </div>
        @else
        <div style="display:flex;flex-direction:column;gap:0;">
        @foreach($approvalTasks as $t)
        @php
            $sent        = $t->design_sent_at ?? $t->updated_at;
            $approved    = $t->customer_approved_at;
            $isApproved  = !is_null($approved);
            $diffSec     = abs($isApproved ? $sent->diffInSeconds($approved) : now()->diffInSeconds($sent));
            $diffMin     = (int) round($diffSec / 60);
            $diffH       = round($diffSec / 3600, 1);
            $diffDays    = round($diffSec / 86400, 1);
            $timeStr     = $diffSec < 3600
                           ? $diffMin . 'm'
                           : ($diffH < 24 ? $diffH . 'h' : $diffDays . 'd');
            $timeBg      = $isApproved
                           ? ($diffH <= 24 ? '#F0FDF4' : ($diffH <= 72 ? '#FEF3C7' : '#FEF2F2'))
                           : '#FEF3C7';
            $timeCo      = $isApproved
                           ? ($diffH <= 24 ? '#16A34A' : ($diffH <= 72 ? '#D97706' : '#DC2626'))
                           : '#D97706';
        @endphp
        @php
            $modalData = json_encode([
                'taskId'       => $t->id,
                'taskUrl'      => route('admin.tasks.show', $t->id),
                'title'        => $t->title,
                'project'      => $t->project->name ?? null,
                'assignee'     => $t->assignee->name ?? null,
                'assigneeInit' => $t->assignee ? strtoupper(substr($t->assignee->name, 0, 1)) : null,
                'status'       => $t->status,
                'isApproved'   => $isApproved,
                'sentDate'     => $sent->format(config('app.date_format', 'M d, Y')),
                'sentTime'     => $sent->format('h:i A'),
                'sentRaw'      => $sent->toIso8601String(),
                'approvedDate' => $approved ? $approved->format(config('app.date_format', 'M d, Y')) : null,
                'approvedTime' => $approved ? $approved->format('h:i A') : null,
                'timeStr'      => $isApproved ? $timeStr : $timeStr . ' waiting',
                'timeBg'       => $timeBg,
                'timeCo'       => $timeCo,
                'diffH'        => $diffH,
                'sentHumans'   => $sent->diffForHumans(),
            ]);
        @endphp
        <div @click="openApproval({{ $modalData }})"
             style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid #F9FAFB;cursor:pointer;border-radius:6px;transition:background .15s;"
             onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">

            {{-- Left: task + timeline --}}
            <div style="min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                    <span style="font-size:13px;font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:280px;display:block;"
                          title="{{ $t->title }}">{{ $t->title }}</span>
                    @if($t->project)
                    <span style="font-size:11px;color:#9CA3AF;display:flex;align-items:center;gap:3px;flex-shrink:0;">
                        <i class="fas fa-folder" style="font-size:9px;"></i> {{ $t->project->name }}
                    </span>
                    @endif
                </div>

                {{-- Timeline row --}}
                <div style="display:flex;align-items:center;gap:0;flex-wrap:wrap;">

                    {{-- Sent bubble --}}
                    <div style="display:flex;align-items:center;gap:6px;">
                        <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#25D366,#128C7E);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fab fa-whatsapp" style="font-size:11px;color:#fff;"></i>
                        </div>
                        <div>
                            <p style="font-size:11px;font-weight:600;color:#374151;margin:0;white-space:nowrap;">Sent</p>
                            <p style="font-size:10px;color:#9CA3AF;margin:0;white-space:nowrap;">{{ $sent->format(config('app.date_format', 'M d, Y')) }} · {{ $sent->format('h:i A') }}</p>
                        </div>
                    </div>

                    {{-- Arrow + duration --}}
                    <div style="display:flex;align-items:center;gap:6px;margin:0 10px;">
                        <div style="height:1px;width:20px;background:#E5E7EB;flex-shrink:0;"></div>
                        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:{{ $timeBg }};color:{{ $timeCo }};white-space:nowrap;">
                            {{ $isApproved ? $timeStr : $timeStr . ' waiting' }}
                        </span>
                        <div style="height:1px;width:20px;background:#E5E7EB;flex-shrink:0;"></div>
                    </div>

                    {{-- Approved / Waiting bubble --}}
                    @if($isApproved)
                    <div style="display:flex;align-items:center;gap:6px;">
                        <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-circle-check" style="font-size:11px;color:#fff;"></i>
                        </div>
                        <div>
                            <p style="font-size:11px;font-weight:600;color:#16A34A;margin:0;white-space:nowrap;">Customer Approved</p>
                            <p style="font-size:10px;color:#9CA3AF;margin:0;white-space:nowrap;">{{ $approved->format(config('app.date_format', 'M d, Y')) }} · {{ $approved->format('h:i A') }}</p>
                        </div>
                    </div>
                    @else
                    <div style="display:flex;align-items:center;gap:6px;">
                        <div style="width:26px;height:26px;border-radius:50%;background:#FEF3C7;border:2px dashed #FCD34D;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-hourglass-half" style="font-size:10px;color:#D97706;"></i>
                        </div>
                        <div>
                            <p style="font-size:11px;font-weight:600;color:#D97706;margin:0;white-space:nowrap;">Awaiting Response</p>
                            <p style="font-size:10px;color:#9CA3AF;margin:0;">since {{ $sent->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endif

                </div>
            </div>

            {{-- Right: assignee + expand hint --}}
            <div style="text-align:right;flex-shrink:0;display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                @if($t->assignee)
                <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
                    <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;">
                        {{ strtoupper(substr($t->assignee->name, 0, 1)) }}
                    </div>
                    <span style="font-size:11px;color:#6B7280;">{{ $t->assignee->name }}</span>
                </div>
                @endif
                <span style="font-size:10px;color:#C4B5FD;"><i class="fas fa-expand-alt" style="font-size:9px;"></i> details</span>
            </div>

        </div>
        @endforeach
        </div>
        @endif

    </div>

    {{-- ══ Approval Detail Modal ══ --}}
    <template x-if="approvalModal">
    <div @click.self="close()" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;">
        <div style="background:#fff;border-radius:16px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;">

            {{-- Header --}}
            <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                <div style="min-width:0;">
                    <p style="font-size:11px;font-weight:600;letter-spacing:.06em;color:#9CA3AF;text-transform:uppercase;margin:0 0 4px;">Design Approval</p>
                    <h3 x-text="approvalModal.title" style="font-size:15px;font-weight:700;color:#111827;margin:0;line-height:1.3;word-break:break-word;"></h3>
                    <div style="display:flex;align-items:center;gap:10px;margin-top:6px;flex-wrap:wrap;">
                        <template x-if="approvalModal.project">
                            <span style="font-size:11px;color:#9CA3AF;display:flex;align-items:center;gap:4px;">
                                <i class="fas fa-folder" style="font-size:9px;"></i>
                                <span x-text="approvalModal.project"></span>
                            </span>
                        </template>
                        <template x-if="approvalModal.assignee">
                            <span style="font-size:11px;color:#6B7280;display:flex;align-items:center;gap:5px;">
                                <span style="width:18px;height:18px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;" x-text="approvalModal.assigneeInit"></span>
                                <span x-text="approvalModal.assignee"></span>
                            </span>
                        </template>
                    </div>
                </div>
                <button @click="close()" style="width:32px;height:32px;border-radius:50%;border:none;background:#F3F4F6;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-times" style="font-size:13px;color:#6B7280;"></i>
                </button>
            </div>

            {{-- Timeline --}}
            <div style="padding:24px;">

                {{-- Step 1: Design Sent --}}
                <div style="display:flex;gap:14px;align-items:flex-start;">
                    <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
                        <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#25D366,#128C7E);display:flex;align-items:center;justify-content:center;">
                            <i class="fab fa-whatsapp" style="font-size:15px;color:#fff;"></i>
                        </div>
                        <div style="width:2px;flex:1;min-height:32px;margin:4px 0;" :style="approvalModal.isApproved ? 'background:#E5E7EB' : 'background:repeating-linear-gradient(to bottom,#FCD34D 0,#FCD34D 4px,transparent 4px,transparent 8px)'"></div>
                    </div>
                    <div style="padding-top:6px;padding-bottom:20px;">
                        <p style="font-size:13px;font-weight:700;color:#374151;margin:0 0 2px;">Design sent via WhatsApp</p>
                        <p style="font-size:12px;color:#6B7280;margin:0;" x-text="approvalModal.sentDate + ' · ' + approvalModal.sentTime"></p>
                    </div>
                </div>

                {{-- Duration badge --}}
                <div style="display:flex;gap:14px;align-items:center;margin:-8px 0 0 18px;">
                    <div style="width:2px;"></div>
                    <span style="font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;margin-left:-1px;"
                          :style="'background:' + approvalModal.timeBg + ';color:' + approvalModal.timeCo"
                          x-text="approvalModal.timeStr"></span>
                </div>

                {{-- Step 2: Approved or Waiting --}}
                <div style="display:flex;gap:14px;align-items:flex-start;margin-top:0;">
                    <div style="flex-shrink:0;">
                        <template x-if="approvalModal.isApproved">
                            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-circle-check" style="font-size:15px;color:#fff;"></i>
                            </div>
                        </template>
                        <template x-if="!approvalModal.isApproved">
                            <div style="width:36px;height:36px;border-radius:50%;background:#FEF3C7;border:2px dashed #FCD34D;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-hourglass-half" style="font-size:13px;color:#D97706;"></i>
                            </div>
                        </template>
                    </div>
                    <div style="padding-top:6px;">
                        <template x-if="approvalModal.isApproved">
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#16A34A;margin:0 0 2px;">Customer Approved</p>
                                <p style="font-size:12px;color:#6B7280;margin:0;" x-text="approvalModal.approvedDate + ' · ' + approvalModal.approvedTime"></p>
                            </div>
                        </template>
                        <template x-if="!approvalModal.isApproved">
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#D97706;margin:0 0 2px;">Awaiting Customer Response</p>
                                <p style="font-size:12px;color:#9CA3AF;margin:0;">Sent <span x-text="approvalModal.sentHumans"></span> — no reply yet</p>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            {{-- Note field (only when pending) --}}
            <template x-if="approvalModal && !approvalModal.isApproved">
            <div style="padding:0 24px 16px;">
                <textarea x-model="approveNote" placeholder="Add a note (optional)…"
                          style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:13px;color:#374151;resize:none;outline:none;box-sizing:border-box;line-height:1.5;"
                          rows="2"
                          onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
            </div>
            </template>

            {{-- Footer --}}
            <div style="padding:14px 24px;border-top:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <a :href="approvalModal.taskUrl"
                   style="font-size:12px;font-weight:600;color:#4F46E5;text-decoration:none;display:flex;align-items:center;gap:5px;">
                    <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i> Open Task
                </a>
                <div style="display:flex;gap:8px;align-items:center;">
                    <button @click="close()" style="padding:8px 18px;border-radius:8px;border:1px solid #E5E7EB;background:#fff;font-size:13px;font-weight:600;color:#374151;cursor:pointer;">
                        Close
                    </button>
                    <template x-if="approvalModal && !approvalModal.isApproved">
                        <button
                            :disabled="approving"
                            @click="
                                approving = true;
                                $refs.quickApproveForm.querySelector('[name=note]').value = approveNote;
                                $refs.quickApproveForm.action = '/admin/tasks/' + approvalModal.taskId + '/approve';
                                $refs.quickApproveForm.submit();
                            "
                            :style="approving
                                ? 'padding:8px 20px;border-radius:8px;border:none;background:#D1FAE5;color:#6EE7B7;font-size:13px;font-weight:700;cursor:not-allowed;display:flex;align-items:center;gap:7px;'
                                : 'padding:8px 20px;border-radius:8px;border:none;background:linear-gradient(135deg,#10B981,#059669);color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px;box-shadow:0 4px 14px rgba(16,185,129,.3);'">
                            <i class="fas fa-circle-check"></i>
                            <span x-text="approving ? 'Approving…' : 'Confirm Approval'"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Hidden approval form --}}
            <form x-ref="quickApproveForm" method="POST" action="#" style="display:none;">
                @csrf
                <input type="hidden" name="note" value="">
            </form>

        </div>
    </div>
    </template>

</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
@media print {
    .app-sidebar,
    .sidebar-overlay,
    header.app-topbar,
    [x-data*="exportOpen"],
    .no-print { display:none !important; }

    .app-shell,
    .app-main  { display:block !important; }

    .app-main  { margin-left:0 !important; padding:0 !important; }

    main.app-content { padding:0 !important; margin:0 !important; }

    body { background:#fff !important; }

    * { box-shadow:none !important; }

    @page { margin:15mm; }
}
</style>
@php
    $exportCustomerMeta = ['name'=>$customer->name,'company'=>$customer->company,'email'=>$customer->email,'phone'=>$customer->phone];
    $exportCustomerSlug = Str::slug($customer->name);
    $exportToday        = now()->format('Y-m-d');
    $exportProjects     = $customer->projects->map(fn($p) => [
        'name'        => $p->name,
        'status'      => $_psLabel[$p->status] ?? ucfirst($p->status),
        'tasks_count' => $p->tasks_count ?? 0,
        'deadline'    => $p->deadline ? $p->deadline->format('Y-m-d') : '',
        'overdue'     => ($p->deadline && $p->deadline->isPast() && $p->status !== 'completed') ? 'Yes' : '',
    ])->values()->toArray();
    $exportStats = ['pending'=>$statPending,'active'=>$statActive,'done'=>$statDone,'overdue'=>$statOverdue];
@endphp
<script>
window.exportCustomerCSV = function () {
    const customer = @json($exportCustomerMeta);
    const rows     = @json($csvRows);

    const headers  = ['Task','Project','Status','Priority','Assignee','Deadline','Design Sent','Customer Approved','Approval Time'];
    const fields   = ['title','project','status','priority','assignee','deadline','design_sent','cust_approved','approval_time'];

    const esc = v => '"' + String(v ?? '').replace(/"/g, '""') + '"';

    let csv = '# Customer: ' + customer.name;
    if (customer.company) csv += ' — ' + customer.company;
    if (customer.email)   csv += '\n# Email: ' + customer.email;
    if (customer.phone)   csv += '\n# Phone: ' + customer.phone;
    csv += '\n# Exported: ' + new Date().toLocaleString() + '\n\n';
    csv += headers.map(esc).join(',') + '\n';
    csv += rows.map(r => fields.map(f => esc(r[f])).join(',')).join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = '{{ $exportCustomerSlug }}_tasks_{{ $exportToday }}.csv';
    a.click();
    URL.revokeObjectURL(url);
};

window.exportCustomerExcel = function () {
    if (typeof XLSX === 'undefined') {
        alert('Excel library not loaded. Please refresh the page and try again.');
        return;
    }
    const customer = @json($exportCustomerMeta);
    const rows     = @json($csvRows);
    const projects = @json($exportProjects);
    const stats    = @json($exportStats);
    const today    = new Date().toLocaleString();

    const wb = XLSX.utils.book_new();

    // ── Sheet 1: Summary (customer info + stats + projects) ──
    const summaryData = [
        ['CUSTOMER REPORT', '', '', '', ''],
        ['', '', '', '', ''],
        ['Customer', customer.name,          '', 'Exported', today],
        ['Company',  customer.company || '—', '', '',         ''],
        ['Email',    customer.email   || '—', '', '',         ''],
        ['Phone',    customer.phone   || '—', '', '',         ''],
        ['', '', '', '', ''],
        ['TASK SUMMARY', '', '', '', ''],
        ['Pending', 'In Progress', 'Completed', 'Overdue', ''],
        [stats.pending, stats.active, stats.done, stats.overdue, ''],
        ['', '', '', '', ''],
    ];
    if (projects.length) {
        summaryData.push(['PROJECTS', '', '', '', '']);
        summaryData.push(['Project Name', 'Status', 'Tasks', 'Deadline', 'Overdue']);
        projects.forEach(p => summaryData.push([p.name, p.status, p.tasks_count, p.deadline || '—', p.overdue || '']));
    }
    const ws1 = XLSX.utils.aoa_to_sheet(summaryData);
    ws1['!cols'] = [{wch:30},{wch:24},{wch:12},{wch:18},{wch:22}];
    XLSX.utils.book_append_sheet(wb, ws1, 'Summary');

    // ── Sheet 2: All Tasks ──
    const taskHeaders = ['Task','Project','Status','Priority','Assignee','Deadline','Design Sent','Customer Approved','Approval Time'];
    const fields      = ['title','project','status','priority','assignee','deadline','design_sent','cust_approved','approval_time'];
    const taskData    = [
        ['TASK LIST — ' + customer.name + (customer.company ? ' / ' + customer.company : ''), '', '', '', '', '', '', '', ''],
        ['', '', '', '', '', '', '', '', ''],
        taskHeaders,
        ...rows.map(r => fields.map(f => r[f] ?? '')),
    ];
    const ws2 = XLSX.utils.aoa_to_sheet(taskData);
    ws2['!cols'] = [{wch:34},{wch:24},{wch:14},{wch:10},{wch:16},{wch:12},{wch:20},{wch:20},{wch:14}];
    XLSX.utils.book_append_sheet(wb, ws2, 'Tasks');

    XLSX.writeFile(wb, '{{ $exportCustomerSlug }}_report_{{ $exportToday }}.xlsx');
};

window.printCustomerPage = function () {
    window.print();
};
</script>
@endpush

@endsection

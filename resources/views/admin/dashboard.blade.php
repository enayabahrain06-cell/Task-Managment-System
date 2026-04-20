@extends('layouts.app')

@section('title', 'Overview')

@section('content')

<style>
/* ── Box-sizing reset (cross-browser) ── */
*, *::before, *::after { box-sizing: border-box; }

/* ── Responsive grid helpers ── */
.stats-grid   { display: -ms-grid; display:grid; -ms-grid-columns: 1fr 16px 1fr 16px 1fr 16px 1fr; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px; }
.charts-grid  { display: -ms-grid; display:grid; -ms-grid-columns: 3fr 16px 2fr;  grid-template-columns:3fr 2fr; gap:16px; margin-bottom:16px; align-items:start; }
.bottom-grid  { display: -ms-grid; display:grid; -ms-grid-columns: 2fr 16px 3fr;  grid-template-columns:2fr 3fr; gap:16px; margin-bottom:0; align-items:start; }

/* ── Working Hours chart — fixed height so it never balloons ── */
.wh-chart-wrap { position:relative; width:100%; height:220px; }

/* ── Project Stats card — matched height ── */
.project-stats-card { display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-orient:vertical; -webkit-box-direction:normal; -ms-flex-direction:column; flex-direction:column; }

@media(max-width:1200px){
    .charts-grid  { grid-template-columns:3fr 2fr; }
    .bottom-grid  { grid-template-columns:2fr 3fr; }
}
@media(max-width:1024px){
    .charts-grid, .bottom-grid { -ms-grid-columns:1fr; grid-template-columns:1fr; }
}
@media(max-width:700px){
    .stats-grid { -ms-grid-columns:1fr 16px 1fr; grid-template-columns:repeat(2,1fr); }
}
@media(max-width:420px){
    .stats-grid { -ms-grid-columns:1fr; grid-template-columns:1fr; }
}

/* ── Card base ── */
.dash-card {
    background:#fff;
    border-radius:14px;
    border:1px solid #F0F0F0;
    -webkit-box-shadow:0 1px 4px rgba(0,0,0,0.05);
    box-shadow:0 1px 4px rgba(0,0,0,0.05);
    padding:20px;
    -webkit-transition: box-shadow 0.2s, -webkit-transform 0.2s;
    transition: box-shadow 0.2s, transform 0.2s;
}
.dash-card:hover {
    -webkit-box-shadow:0 6px 24px rgba(0,0,0,0.08);
    box-shadow:0 6px 24px rgba(0,0,0,0.08);
}

/* ── Stat cards ── */
.stat-card { border-radius:14px; padding:18px 20px; position:relative; overflow:hidden; color:#fff; }
.stat-card-blob { position:absolute; top:-20px; right:-20px; width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.12); }
.stat-card-label { font-size:12px; font-weight:500; color:rgba(255,255,255,0.75); margin:0 0 8px; }
.stat-card-value { font-size:34px; font-weight:700; line-height:1; margin:0; }
.stat-card-sub   { font-size:11px; color:rgba(255,255,255,0.6); margin:6px 0 0; }
.stat-card-menu  { position:absolute; top:14px; right:14px; background:rgba(255,255,255,0.15); border:none; border-radius:6px; width:26px; height:26px; cursor:pointer; display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; -webkit-box-pack:center; -ms-flex-pack:center; justify-content:center; color:#fff; font-size:11px; }

/* ── Calendar dark card ── */
.cal-card { background:-webkit-linear-gradient(315deg,#1a2040 0%,#1e2756 100%); background:linear-gradient(135deg,#1a2040 0%,#1e2756 100%); border-radius:14px; padding:20px; color:#fff; }

/* ── Entrance animations ── */
@-webkit-keyframes fadeInUp {
    from { opacity:0; -webkit-transform:translateY(14px); transform:translateY(14px); }
    to   { opacity:1; -webkit-transform:translateY(0);    transform:translateY(0); }
}
@keyframes fadeInUp {
    from { opacity:0; transform:translateY(14px); }
    to   { opacity:1; transform:translateY(0); }
}
@-webkit-keyframes fillCircle {
    from { stroke-dasharray: 0 100; }
}
@keyframes fillCircle {
    from { stroke-dasharray: 0 100; }
}
.anim-card {
    -webkit-animation: fadeInUp 0.45s cubic-bezier(0.22,1,0.36,1) both;
    animation: fadeInUp 0.45s cubic-bezier(0.22,1,0.36,1) both;
}
.anim-d1  { -webkit-animation-delay:0.04s; animation-delay:0.04s; }
.anim-d2  { -webkit-animation-delay:0.10s; animation-delay:0.10s; }
.anim-d3  { -webkit-animation-delay:0.16s; animation-delay:0.16s; }
.anim-d4  { -webkit-animation-delay:0.22s; animation-delay:0.22s; }
.anim-d5  { -webkit-animation-delay:0.28s; animation-delay:0.28s; }
.anim-d6  { -webkit-animation-delay:0.34s; animation-delay:0.34s; }

/* ── Auto-refresh pulse ── */
@-webkit-keyframes pulse {
    0%,100% { opacity:1; -webkit-transform:scale(1); transform:scale(1); }
    50%      { opacity:.4; -webkit-transform:scale(0.8); transform:scale(0.8); }
}
@keyframes pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.4; transform:scale(0.8); }
}
.rv-pulse { -webkit-animation:pulse 1s ease-in-out infinite; animation:pulse 1s ease-in-out infinite; }

/* ── Rate circles ── */
.rate-circle {
    -webkit-animation: fillCircle 1.1s cubic-bezier(0.65,0,0.35,1) both;
    animation: fillCircle 1.1s cubic-bezier(0.65,0,0.35,1) both;
    -webkit-animation-delay:0.6s;
    animation-delay:0.6s;
}

/* ── Task Modal ── */
.task-modal-backdrop { position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(0,0,0,0.45); z-index:200; display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; -webkit-box-pack:center; -ms-flex-pack:center; justify-content:center; padding:16px; }
.task-modal { background:#fff; border-radius:20px; width:100%; max-width:560px; max-height:90vh; overflow-y:auto; -webkit-box-shadow:0 20px 60px rgba(0,0,0,0.2); box-shadow:0 20px 60px rgba(0,0,0,0.2); }
.task-modal-header { display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; -webkit-box-pack:justify; -ms-flex-pack:justify; justify-content:space-between; padding:22px 24px 0; }
.task-modal-body   { padding:20px 24px 24px; }
.form-label { display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-input {
    width:100%; padding:9px 12px; font-size:13px;
    border:1.5px solid #E5E7EB; border-radius:9px;
    color:#111827; outline:none;
    font-family:'Inter',system-ui,-apple-system,sans-serif;
    -webkit-transition:border-color 0.15s; transition:border-color 0.15s;
    background:#fff;
    -webkit-box-sizing:border-box; box-sizing:border-box;
}
.form-input:focus { border-color:#6366F1; -webkit-box-shadow:0 0 0 3px rgba(99,102,241,0.12); box-shadow:0 0 0 3px rgba(99,102,241,0.12); }
.form-select {
    -webkit-appearance:none;
    -moz-appearance:none;
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 12px center;
    padding-right:32px;
}
.priority-btn { -webkit-box-flex:1; -ms-flex:1; flex:1; padding:8px; font-size:12px; font-weight:600; border-radius:8px; border:1.5px solid #E5E7EB; cursor:pointer; -webkit-transition:all 0.15s; transition:all 0.15s; text-align:center; background:#fff; color:#6B7280; }
.priority-btn.active-low    { background:#F0FDF4; border-color:#10B981; color:#059669; }
.priority-btn.active-medium { background:#FFFBEB; border-color:#F59E0B; color:#D97706; }
.priority-btn.active-high   { background:#FFF1F2; border-color:#EF4444; color:#DC2626; }
</style>

{{-- ══ Page Title + Quick Create Modals ══ --}}
<div x-data="dashModals()" x-init="init()" style="margin-bottom:22px;">

    {{-- Title row --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Overview</h1>
            <p style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">
                Welcome back, {{ auth()->user()->name }}!
                <span id="refreshIndicator" style="display:inline-flex;align-items:center;gap:4px;margin-left:8px;font-size:11px;color:#9CA3AF;vertical-align:middle;">
                    <span id="refreshDot" style="width:6px;height:6px;border-radius:50%;background:#10B981;display:inline-block;"></span>
                    <span id="refreshLabel">Live</span>
                </span>
            </p>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;background:#fff;border:1px solid #E5E7EB;border-radius:8px;padding:7px 12px;cursor:pointer;">
                <i class="fas fa-calendar-days" style="color:#9CA3AF;font-size:11px;"></i>
                <span>Last Week</span>
                <i class="fas fa-chevron-down" style="color:#9CA3AF;font-size:10px;"></i>
            </div>
            {{-- NEW PROJECT BUTTON --}}
            <button @click="projectOpen = true"
                    style="display:flex;align-items:center;gap:7px;background:#fff;color:#4F46E5;font-size:13px;font-weight:600;padding:9px 18px;border-radius:9px;border:1.5px solid #C7D2FE;cursor:pointer;">
                <i class="fas fa-folder-plus" style="font-size:11px;"></i> New Project
            </button>
            {{-- QUICK TASK BUTTON --}}
            <button @click="taskOpen = true"
                    style="display:flex;align-items:center;gap:7px;background:#F59E0B;color:#fff;font-size:13px;font-weight:600;padding:9px 18px;border-radius:9px;border:none;cursor:pointer;box-shadow:0 2px 10px rgba(245,158,11,0.4);">
                <i class="fas fa-bolt" style="font-size:11px;"></i> Quick Task
            </button>
            <button style="width:36px;height:36px;background:#fff;border:1px solid #E5E7EB;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#9CA3AF;">
                <i class="fas fa-ellipsis-h"></i>
            </button>
        </div>
    </div>

    {{-- ══ Quick Task Modal ══ --}}
    <div x-show="taskOpen" x-cloak style="position:fixed;inset:0;z-index:9999;">
    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;">

        <div @click="taskOpen = false" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);-webkit-backdrop-filter:blur(3px);backdrop-filter:blur(3px);"></div>

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
                <button @click="taskOpen = false"
                        style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:13px;">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('admin.tasks.quick') }}" style="padding:20px 24px 24px;">
                @csrf

                <div style="margin-bottom:14px;">
                    <label class="form-label">Task Title <span style="color:#EF4444;">*</span></label>
                    <input type="text" name="title" class="form-input" placeholder="e.g. Update hero banner image" required
                           value="{{ old('title') }}"
                           onfocus="this.style.borderColor='#F59E0B';this.style.boxShadow='0 0 0 3px rgba(245,158,11,0.12)'"
                           onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow=''">
                </div>

                <div style="margin-bottom:14px;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="2"
                              placeholder="Brief details or notes..." style="resize:vertical;"
                              onfocus="this.style.borderColor='#F59E0B';this.style.boxShadow='0 0 0 3px rgba(245,158,11,0.12)'"
                              onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow=''">{{ old('description') }}</textarea>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="form-label">Assign To <span style="color:#EF4444;">*</span></label>
                    <select name="assigned_to" class="form-input form-select" required>
                        <option value="">— Select team member —</option>
                        @foreach($allUsers as $member)
                        <option value="{{ $member->id }}" {{ old('assigned_to') == $member->id ? 'selected' : '' }}>
                            {{ $member->name }} ({{ ucfirst($member->role) }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                    <div>
                        <label class="form-label">Priority <span style="color:#EF4444;">*</span></label>
                        <select name="priority" class="form-input form-select" required>
                            <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority','medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Deadline <span style="color:#EF4444;">*</span></label>
                        <input type="date" name="deadline" class="form-input" required
                               min="{{ date('Y-m-d') }}" value="{{ old('deadline') }}">
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <label class="form-label">Link to Project <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— optional</span></label>
                    <select name="project_id" class="form-input form-select">
                        <option value="">— No project (standalone) —</option>
                        @foreach($allProjects as $proj)
                        <option value="{{ $proj->id }}" {{ old('project_id') == $proj->id ? 'selected' : '' }}>
                            {{ $proj->name }}
                        </option>
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

    {{-- ══ New Project Wizard Modal ══ --}}
    <div x-show="projectOpen" x-cloak style="position:fixed;inset:0;z-index:9999;">
    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;">

        <div @click="projectOpen = false" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);-webkit-backdrop-filter:blur(3px);backdrop-filter:blur(3px);"></div>

        <div style="position:relative;width:100%;max-width:700px;max-height:90vh;background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,0.2);display:flex;flex-direction:column;overflow:hidden;">

            {{-- Header --}}
            <div style="padding:20px 24px 0;flex-shrink:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#111827;margin:0;">New Project</h2>
                        <p style="font-size:12px;color:#9CA3AF;margin:4px 0 0;" x-text="'Step ' + projectStep + ' of 3'"></p>
                    </div>
                    <button @click="projectOpen = false"
                            style="width:32px;height:32px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:14px;">
                        <i class="fa fa-times"></i>
                    </button>
                </div>

                {{-- Step Indicators --}}
                <div style="display:flex;align-items:center;gap:0;margin-bottom:24px;">
                    <template x-for="s in 3" :key="s">
                        <div style="display:flex;align-items:center;flex:1;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:4px;flex:1;">
                                <div :style="projectStep >= s ? 'width:32px;height:32px;border-radius:50%;background:#4F46E5;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;' : 'width:32px;height:32px;border-radius:50%;background:#F3F4F6;color:#9CA3AF;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;'">
                                    <span x-show="projectStep > s"><i class="fa fa-check" style="font-size:11px;"></i></span>
                                    <span x-show="projectStep <= s" x-text="s"></span>
                                </div>
                                <span :style="projectStep >= s ? 'font-size:10px;font-weight:600;color:#4F46E5;' : 'font-size:10px;font-weight:600;color:#9CA3AF;'"
                                      x-text="s === 1 ? 'Details' : s === 2 ? 'Tasks' : 'Attachments'"></span>
                            </div>
                            <template x-if="s < 3">
                                <div :style="projectStep > s ? 'flex:1;height:2px;background:#4F46E5;margin:0 4px;margin-bottom:20px;' : 'flex:1;height:2px;background:#E5E7EB;margin:0 4px;margin-bottom:20px;'"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Body --}}
            <form method="POST" action="{{ route('admin.projects.store') }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                @csrf
                <div style="flex:1;overflow-y:auto;padding:0 24px 8px;">

                    {{-- Step 1: Details --}}
                    <div x-show="projectStep === 1">
                        <div style="margin-bottom:16px;">
                            <label class="form-label">Project Name <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="name" class="form-input" placeholder="e.g. Mobile App Redesign" required value="{{ old('name') }}"
                                   x-ref="pWizardName" @input="pNameError = false"
                                   :style="pNameError ? 'border-color:#EF4444;background:#FEF2F2;' : ''">
                            <p x-show="pNameError" style="margin:4px 0 0;font-size:11px;color:#EF4444;"><i class="fa fa-circle-exclamation" style="margin-right:3px;"></i>Project name is required.</p>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label class="form-label">
                                Description
                                <span style="font-size:11px;font-weight:400;color:#9CA3AF;margin-left:4px;">— keep it brief</span>
                            </label>
                            <textarea name="description" class="form-input" rows="2" placeholder="Short summary of the project goal..." style="resize:none;">{{ old('description') }}</textarea>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:8px;">
                            <div>
                                <label class="form-label">Deadline <span style="color:#EF4444;">*</span></label>
                                <input type="date" name="deadline" class="form-input" required min="{{ date('Y-m-d') }}" value="{{ old('deadline') }}"
                                       x-ref="pWizardDeadline" @change="pDeadlineError = false"
                                       :style="pDeadlineError ? 'border-color:#EF4444;background:#FEF2F2;' : ''">
                                <p x-show="pDeadlineError" style="margin:4px 0 0;font-size:11px;color:#EF4444;"><i class="fa fa-circle-exclamation" style="margin-right:3px;"></i>Deadline is required.</p>
                            </div>
                            <div>
                                <label class="form-label">
                                    First Review Date
                                    <span style="font-size:10px;font-weight:400;color:#9CA3AF;">optional</span>
                                </label>
                                <input type="date" name="first_review_date" class="form-input" value="{{ old('first_review_date') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Step 3: Attachments --}}
                    <div x-show="projectStep === 3">
                        <label class="form-label" style="margin-bottom:8px;">Files <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— max 20 MB each</span></label>
                        <div @dragover.prevent="pDragover = true" @dragleave.prevent="pDragover = false"
                             @drop.prevent="pDragover = false; pHandleFiles($event)"
                             @click="$refs.pFileInput.click()"
                             :style="pDragover ? 'border-color:#6366F1;background:#EEF2FF;' : 'border-color:#E5E7EB;background:#FAFAFA;'"
                             style="border:2px dashed;border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:10px;">
                            <i class="fas fa-cloud-upload-alt" style="font-size:24px;color:#9CA3AF;margin-bottom:8px;display:block;"></i>
                            <p style="font-size:13px;color:#6B7280;margin:0;">Drag &amp; drop files or <span style="color:#6366F1;font-weight:600;">browse</span></p>
                            <input type="file" name="attachments[]" multiple x-ref="pFileInput" @change="pHandleFiles($event)" style="display:none;">
                        </div>
                        <template x-if="pFiles.length > 0">
                            <div style="margin-bottom:14px;display:flex;flex-direction:column;gap:5px;">
                                <template x-for="(file, i) in pFiles" :key="i">
                                    <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;">
                                        <i :class="'fas ' + pFileIcon(file.name)" style="font-size:14px;color:#6366F1;flex-shrink:0;"></i>
                                        <div style="flex:1;min-width:0;">
                                            <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="file.name"></p>
                                            <p style="font-size:11px;color:#9CA3AF;margin:0;" x-text="pFormatSize(file.size)"></p>
                                        </div>
                                        <button type="button" @click.stop="pRemoveFile(i)" style="width:22px;height:22px;border-radius:6px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:10px;display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <div style="margin-top:16px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                <label class="form-label" style="margin:0;">Links</label>
                                <button type="button" @click="pLinks.push({url:'',label:''})" style="font-size:11px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:none;padding:4px 12px;border-radius:6px;cursor:pointer;">+ Add Link</button>
                            </div>
                            <template x-if="pLinks.length > 0">
                                <div style="display:flex;flex-direction:column;gap:6px;">
                                    <template x-for="(link, i) in pLinks" :key="i">
                                        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center;">
                                            <input type="url" :name="'links['+i+'][url]'" x-model="link.url" placeholder="https://..." class="form-input">
                                            <input type="text" :name="'links['+i+'][label]'" x-model="link.label" placeholder="Label (optional)" class="form-input">
                                            <button type="button" @click="pLinks.splice(i,1)" style="width:26px;height:26px;border-radius:6px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <p x-show="pLinks.length === 0" style="font-size:11px;color:#9CA3AF;margin:0;">No links yet — click "+ Add Link".</p>
                        </div>
                    </div>

                    {{-- Step 2: Tasks --}}
                    <div x-show="projectStep === 2">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#374151;margin:0;">Assign Tasks</p>
                                <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Assignees are automatically added as project members</p>
                            </div>
                            <button type="button" @click="pAddTask()" style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:#EEF2FF;color:#4F46E5;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                                <i class="fas fa-plus" style="font-size:10px;"></i> Add Task
                            </button>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:14px;">
                            <template x-for="(task, i) in pTasks" :key="i">
                                <div :style="(task.titleError || task.assigneeError) ? 'border:1.5px solid #FCA5A5;border-radius:12px;padding:16px;background:#FAFBFF;' : 'border:1.5px solid #E5E7EB;border-radius:12px;padding:16px;background:#FAFBFF;'">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                                        <span style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Task <span x-text="i+1"></span></span>
                                        <button type="button" @click="if(pTasks.length>1) pTasks.splice(i,1)" x-show="pTasks.length > 1" style="width:24px;height:24px;border-radius:6px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div style="margin-bottom:10px;">
                                        <input type="text" :name="'tasks['+i+'][title]'" x-model="task.title"
                                               @input="task.titleError = false"
                                               placeholder="Task title *" class="form-input"
                                               :style="task.titleError ? 'border-color:#EF4444;background:#FEF2F2;' : ''">
                                        <p x-show="task.titleError" style="margin:3px 0 0;font-size:11px;color:#EF4444;display:flex;align-items:center;gap:3px;">
                                            <i class="fa fa-circle-exclamation"></i> Task title is required.
                                        </p>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                                        <div>
                                            <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Task Type</label>
                                            <input type="text" :name="'tasks['+i+'][task_type]'" x-model="task.task_type" placeholder="e.g. Design, Video" class="form-input">
                                        </div>
                                        <div>
                                            <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Tags</label>
                                            <input type="text" :name="'tasks['+i+'][tags]'" x-model="task.tags" placeholder="#video, #urgent" class="form-input">
                                        </div>
                                    </div>
                                    <div style="margin-bottom:10px;">
                                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                            <label style="font-size:11px;font-weight:600;color:#6B7280;">Assignees</label>
                                            <button type="button" @click="task.assignees.push({user_id:'',role:''})" style="font-size:10px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:none;padding:3px 10px;border-radius:6px;cursor:pointer;">+ Add Person</button>
                                        </div>
                                        <div style="display:flex;flex-direction:column;gap:6px;">
                                            <template x-for="(assignee, j) in task.assignees" :key="j">
                                                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center;">
                                                    <select :name="'tasks['+i+'][assignees]['+j+'][user_id]'" x-model="assignee.user_id"
                                                            @change="task.assigneeError = false"
                                                            class="form-input form-select"
                                                            :style="task.assigneeError && j === 0 ? 'border-color:#EF4444;background:#FEF2F2;' : ''">
                                                        <option value="">— Select person —</option>
                                                        <template x-for="u in pAllUsers" :key="u.id">
                                                            <option :value="u.id" x-text="u.name + ' (' + u.role + ')'"></option>
                                                        </template>
                                                    </select>
                                                    <input type="text" :name="'tasks['+i+'][assignees]['+j+'][role]'" x-model="assignee.role" placeholder="Role (e.g. designer)" class="form-input">
                                                    <button type="button" @click="if(task.assignees.length>1) task.assignees.splice(j,1)" x-show="task.assignees.length > 1" style="width:26px;height:26px;border-radius:6px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;">
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
                                            <select :name="'tasks['+i+'][reviewer_id]'" x-model="task.reviewer_id" class="form-input form-select">
                                                <option value="">— None —</option>
                                                <template x-for="u in pAllUsers" :key="u.id">
                                                    <option :value="u.id" x-text="u.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Priority</label>
                                            <select :name="'tasks['+i+'][priority]'" x-model="task.priority" class="form-input form-select">
                                                <option value="low">Low</option>
                                                <option value="medium">Medium</option>
                                                <option value="high">High</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Deadline</label>
                                            <input type="date" :name="'tasks['+i+'][deadline]'" x-model="task.deadline" class="form-input">
                                        </div>
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Description</label>
                                        <textarea :name="'tasks['+i+'][description]'" x-model="task.description" rows="2" placeholder="Task brief or notes..." class="form-input" style="resize:vertical;font-family:'Inter',sans-serif;"></textarea>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div style="padding:16px 24px;border-top:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fff;">
                    <button type="button" @click="projectStep > 1 ? projectStep-- : projectOpen = false"
                            style="padding:9px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
                        <i class="fa fa-arrow-left" style="font-size:11px;"></i>
                        <span x-text="projectStep > 1 ? 'Back' : 'Cancel'"></span>
                    </button>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <template x-for="s in 3" :key="s">
                            <div :style="projectStep === s ? 'width:8px;height:8px;border-radius:50%;background:#4F46E5;' : 'width:6px;height:6px;border-radius:50%;background:#E5E7EB;'"></div>
                        </template>
                    </div>
                    <template x-if="projectStep < 3">
                        <button type="button" @click="pNextStep()"
                                style="padding:9px 20px;background:#4F46E5;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
                            Next <i class="fa fa-arrow-right" style="font-size:11px;"></i>
                        </button>
                    </template>
                    <template x-if="projectStep === 3">
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

@push('scripts')
<script>
function dashModals() {
    return {
        /* ── Quick Task ── */
        taskOpen:       {{ $errors->any() ? 'true' : 'false' }},
        taskSubmitting: false,
        priority:       '{{ old('priority', 'medium') }}',

        /* ── Project Wizard ── */
        projectOpen:    false,
        projectStep:    1,
        pDragover:      false,
        pFiles:         [],
        pLinks:         [],
        pTasks:         [],
        pNameError:     false,
        pDeadlineError: false,
        pAllUsers:      @json($allUsers->map(fn($u) => ['id' => (string)$u->id, 'name' => $u->name, 'role' => ucfirst($u->role)])),

        pBlankTask() {
            return { title:'', task_type:'', tags:'', assignees:[{user_id:'',role:''}], reviewer_id:'', priority:'medium', deadline:'', description:'',
                     titleError:false, assigneeError:false };
        },

        pNextStep() {
            if (this.projectStep === 1) {
                this.pNameError     = !this.$refs.pWizardName?.value.trim();
                this.pDeadlineError = !this.$refs.pWizardDeadline?.value;
                if (this.pNameError || this.pDeadlineError) return;
            }
            if (this.projectStep === 2) {
                let hasError = false;
                for (const task of this.pTasks) {
                    task.titleError    = !task.title.trim();
                    task.assigneeError = !task.assignees.some(a => a.user_id);
                    if (task.titleError || task.assigneeError) hasError = true;
                }
                if (hasError) return;
            }
            if (this.projectStep < 3) this.projectStep++;
        },

        init() {
            this.pTasks = [this.pBlankTask()];
            this.$watch('projectOpen', v => {
                if (v) { this.projectStep = 1; this.pNameError = false; this.pDeadlineError = false; document.body.style.overflow = 'hidden'; }
                else { document.body.style.overflow = ''; }
            });
        },

        pAddTask() { this.pTasks.push(this.pBlankTask()); },

        pHandleFiles(event) {
            const incoming = event.dataTransfer ? event.dataTransfer.files : event.target.files;
            const dt = new DataTransfer();
            for (let f of this.pFiles) dt.items.add(f);
            for (let f of incoming) dt.items.add(f);
            this.pFiles = Array.from(dt.files);
            this.$refs.pFileInput.files = dt.files;
        },

        pRemoveFile(i) {
            const dt = new DataTransfer();
            this.pFiles.forEach((f, idx) => { if (idx !== i) dt.items.add(f); });
            this.pFiles = Array.from(dt.files);
            this.$refs.pFileInput.files = dt.files;
        },

        pFormatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
            return (bytes/1048576).toFixed(1) + ' MB';
        },

        pFileIcon(name) {
            const ext = name.split('.').pop().toLowerCase();
            if (['pdf'].includes(ext))                               return 'fa-file-pdf';
            if (['doc','docx'].includes(ext))                        return 'fa-file-word';
            if (['xls','xlsx'].includes(ext))                        return 'fa-file-excel';
            if (['jpg','jpeg','png','gif','webp','svg'].includes(ext))return 'fa-file-image';
            if (['zip','rar','7z'].includes(ext))                    return 'fa-file-zipper';
            if (['mp4','mov','avi'].includes(ext))                   return 'fa-file-video';
            return 'fa-file';
        },
    };
}
</script>
@endpush

{{-- ══ Stats Row ══ --}}
<div class="stats-grid">

    {{-- Tasks --}}
    <a href="{{ route('admin.dashboard') }}" style="text-decoration:none;">
    <div class="stat-card anim-card anim-d1" style="background:linear-gradient(135deg,#4F46E5,#6366F1);cursor:pointer;transition:transform .15s,box-shadow .15s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(79,70,229,.4)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''"
         onmousedown="this.style.transform='translateY(-1px)'"
         onmouseup="this.style.transform='translateY(-3px)'">
        <div class="stat-card-blob"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p class="stat-card-label">Tasks</p>
            <button class="stat-card-menu" onclick="event.preventDefault()"><i class="fas fa-ellipsis-h"></i></button>
        </div>
        <p class="stat-card-value stat-count" data-target="{{ $totalTasks }}" data-rv="totalTasks">{{ $totalTasks }}</p>
        <p class="stat-card-sub">Open Tasks</p>
    </div>
    </a>

    {{-- Projects --}}
    <a href="{{ route('admin.projects.index') }}" style="text-decoration:none;">
    <div class="stat-card anim-card anim-d2" style="background:linear-gradient(135deg,#059669,#10B981);cursor:pointer;transition:transform .15s,box-shadow .15s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(5,150,105,.4)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''"
         onmousedown="this.style.transform='translateY(-1px)'"
         onmouseup="this.style.transform='translateY(-3px)'">
        <div class="stat-card-blob"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p class="stat-card-label">Projects</p>
            <button class="stat-card-menu" onclick="event.preventDefault()"><i class="fas fa-ellipsis-h"></i></button>
        </div>
        <p class="stat-card-value stat-count" data-target="{{ $activeProjects }}" data-rv="activeProjects">{{ $activeProjects }}</p>
        <p class="stat-card-sub">Active Projects</p>
    </div>
    </a>

    {{-- Meetings --}}
    <a href="{{ route('calendar.index') }}" style="text-decoration:none;">
    <div class="stat-card anim-card anim-d3" style="background:linear-gradient(135deg,#7C3AED,#8B5CF6);cursor:pointer;transition:transform .15s,box-shadow .15s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(124,58,237,.4)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''"
         onmousedown="this.style.transform='translateY(-1px)'"
         onmouseup="this.style.transform='translateY(-3px)'">
        <div class="stat-card-blob"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p class="stat-card-label">Meetings</p>
            <button class="stat-card-menu" onclick="event.preventDefault()"><i class="fas fa-ellipsis-h"></i></button>
        </div>
        <p class="stat-card-value stat-count" data-target="{{ $scheduledMeetings }}" data-rv="scheduledMeetings">{{ $scheduledMeetings }}</p>
        <p class="stat-card-sub">Scheduled Meetings</p>
    </div>
    </a>

    {{-- Member Status --}}
    <div class="dash-card anim-card anim-d4" style="padding:18px 20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <p style="font-size:12px;font-weight:500;color:#6B7280;margin:0;">Member Status</p>
            <a href="{{ route('team.index') }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:500;">View more</a>
        </div>
        <p class="stat-count" data-target="{{ $totalMembers }}" data-rv="totalMembers" style="font-size:32px;font-weight:700;color:#111827;margin:0 0 2px;line-height:1;">{{ $totalMembers }}</p>
        <p style="font-size:11px;color:#9CA3AF;margin:0 0 10px;">Total Members</p>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:5px;">
                <span data-rv="activeMembers" style="width:24px;height:24px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#4F46E5;">{{ $activeMembers }}</span>
                <span style="font-size:11px;color:#9CA3AF;">Active</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;">
                <span data-rv="managerCount" style="width:24px;height:24px;border-radius:8px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#D97706;">{{ $managerCount }}</span>
                <span style="font-size:11px;color:#9CA3AF;">Mgr</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;">
                <span data-rv="userCount" style="width:24px;height:24px;border-radius:8px;background:#F0FDF4;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#16A34A;">{{ $userCount }}</span>
                <span style="font-size:11px;color:#9CA3AF;">Users</span>
            </div>
        </div>
    </div>
</div>

{{-- ══ Task Analytics ══ --}}
<div class="dash-card anim-card anim-d5" style="margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
        <div>
            <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-chart-pie" style="color:#6366F1;font-size:13px;"></i> Task Analytics
            </h3>
            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">All-time overview across every task in the system</p>
        </div>
        <a href="{{ route('admin.projects.index') }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:600;">View Projects →</a>
    </div>

    {{-- Main grid: status counts --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:14px;">

        @php
        $analyticsItems = [
            ['label'=>'Total Assigned',  'value'=>$taskOverview['assigned'],     'key'=>'assigned',      'icon'=>'fa-user-check',          'bg'=>'#EEF2FF','color'=>'#4F46E5'],
            ['label'=>'Pending',         'value'=>$taskOverview['pending'],      'key'=>'pending',       'icon'=>'fa-clock',               'bg'=>'#F3F4F6','color'=>'#6B7280'],
            ['label'=>'In Progress',     'value'=>$taskOverview['in_progress'],  'key'=>'in_progress',   'icon'=>'fa-spinner',             'bg'=>'#FEF3C7','color'=>'#D97706'],
            ['label'=>'Waiting Review',  'value'=>$taskOverview['in_review'],    'key'=>'in_review',     'icon'=>'fa-gavel',               'bg'=>'#EDE9FE','color'=>'#7C3AED'],
            ['label'=>'Completed',       'value'=>$taskOverview['completed'],    'key'=>'completed',     'icon'=>'fa-circle-check',        'bg'=>'#D1FAE5','color'=>'#059669'],
            ['label'=>'Delivered',       'value'=>$taskOverview['delivered'],    'key'=>'delivered',     'icon'=>'fa-truck',               'bg'=>'#ECFDF5','color'=>'#047857'],
            ['label'=>'Overdue',         'value'=>$taskOverview['overdue'],      'key'=>'overdue',       'icon'=>'fa-triangle-exclamation','bg'=>'#FEE2E2','color'=>'#DC2626'],
            ['label'=>'Due Today',       'value'=>$taskOverview['due_today'],    'key'=>'due_today',     'icon'=>'fa-calendar-day',        'bg'=>'#FFF7ED','color'=>'#EA580C'],
            ['label'=>'Due This Week',   'value'=>$taskOverview['due_this_week'],'key'=>'due_this_week', 'icon'=>'fa-calendar-week',       'bg'=>'#F0F9FF','color'=>'#0284C7'],
        ];
        @endphp

        @foreach($analyticsItems as $item)
        <div style="background:{{ $item['bg'] }};border-radius:12px;padding:14px 12px;display:flex;flex-direction:column;gap:6px;">
            <div style="display:flex;align-items:center;gap:6px;">
                <i class="fas {{ $item['icon'] }}" style="font-size:12px;color:{{ $item['color'] }};"></i>
                <span style="font-size:11px;font-weight:600;color:{{ $item['color'] }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $item['label'] }}</span>
            </div>
            <p data-rv="overview_{{ $item['key'] }}" style="font-size:26px;font-weight:700;color:#111827;margin:0;line-height:1;">{{ $item['value'] }}</p>
        </div>
        @endforeach

    </div>

    {{-- Rate metrics row --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">

        {{-- Completion Rate --}}
        <div style="background:#F8FAFC;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:14px;">
            <div style="position:relative;width:50px;height:50px;flex-shrink:0;">
                <svg viewBox="0 0 36 36" style="width:50px;height:50px;transform:rotate(-90deg);">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#E5E7EB" stroke-width="3"/>
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#10B981" stroke-width="3"
                            stroke-dasharray="{{ $completionRate }} {{ 100 - $completionRate }}"
                            stroke-linecap="round" class="rate-circle" id="rateCircleCompletion"/>
                </svg>
                <span id="rateTextCompletion" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#111827;">{{ $completionRate }}%</span>
            </div>
            <div>
                <p style="font-size:13px;font-weight:700;color:#111827;margin:0;">Completion Rate</p>
                <p id="rateSubCompletion" style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">{{ $taskOverview['completed'] + $taskOverview['delivered'] }} of {{ $taskOverview['total'] }} tasks done</p>
            </div>
        </div>

        {{-- On-time Rate --}}
        <div style="background:#F8FAFC;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:14px;">
            <div style="position:relative;width:50px;height:50px;flex-shrink:0;">
                <svg viewBox="0 0 36 36" style="width:50px;height:50px;transform:rotate(-90deg);">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#E5E7EB" stroke-width="3"/>
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#6366F1" stroke-width="3"
                            stroke-dasharray="{{ $onTimeRate }} {{ 100 - $onTimeRate }}"
                            stroke-linecap="round" class="rate-circle" id="rateCircleOnTime" style="animation-delay:0.75s"/>
                </svg>
                <span id="rateTextOnTime" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#111827;">{{ $onTimeRate }}%</span>
            </div>
            <div>
                <p style="font-size:13px;font-weight:700;color:#111827;margin:0;">On-time Rate</p>
                <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Tasks completed before deadline</p>
            </div>
        </div>

        {{-- Review Cycles --}}
        <div style="background:#F8FAFC;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:14px;">
            <div style="width:50px;height:50px;border-radius:12px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-rotate" style="color:#7C3AED;font-size:18px;"></i>
            </div>
            <div>
                <p style="font-size:13px;font-weight:700;color:#111827;margin:0;">Review Cycles</p>
                <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">
                    <span id="reviewCyclesVal" style="font-size:22px;font-weight:700;color:#7C3AED;display:block;line-height:1.2;">{{ $reviewCycles }}</span>
                    Total submissions made
                </p>
            </div>
        </div>

    </div>
</div>

{{-- ══ Charts Row ══ --}}
<div class="charts-grid">

    {{-- Working Hours Line Chart --}}
    <div class="dash-card anim-card anim-d5" style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;">
        <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;margin-bottom:16px;-ms-flex-wrap:wrap;flex-wrap:wrap;gap:8px;">
            <div>
                <h3 style="font-size:14px;font-weight:600;color:#111827;margin:0;">Working Hours Statistics</h3>
                <p  style="font-size:12px;color:#9CA3AF;margin:3px 0 0;">Task activity over the past 7 days</p>
            </div>
            <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;gap:2px;background:#F3F4F6;border-radius:8px;padding:3px;">
                <button style="padding:4px 12px;font-size:12px;font-weight:500;background:#fff;border:none;border-radius:6px;cursor:pointer;color:#374151;-webkit-box-shadow:0 1px 2px rgba(0,0,0,0.06);box-shadow:0 1px 2px rgba(0,0,0,0.06);">Week</button>
                <button style="padding:4px 12px;font-size:12px;font-weight:500;background:none;border:none;border-radius:6px;cursor:pointer;color:#9CA3AF;">Month</button>
                <button style="padding:4px 12px;font-size:12px;font-weight:500;background:none;border:none;border-radius:6px;cursor:pointer;color:#9CA3AF;">Year</button>
            </div>
        </div>
        <div class="wh-chart-wrap">
            <canvas id="workingHoursChart"></canvas>
        </div>
    </div>

    {{-- Project Statistics Donut + Project List --}}
    <div class="dash-card anim-card anim-d6 project-stats-card">
        <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:14px;font-weight:600;color:#111827;margin:0;">Project Statistics</h3>
            <a href="{{ route('admin.projects.index') }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:500;">View more</a>
        </div>

        {{-- Donut + legend side-by-side --}}
        <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;gap:20px;margin-bottom:16px;-ms-flex-wrap:wrap;flex-wrap:wrap;">
            <div style="position:relative;width:140px;height:140px;-ms-flex-negative:0;flex-shrink:0;">
                <canvas id="projectStatsChart" style="width:140px!important;height:140px!important;"></canvas>
                <div style="position:absolute;top:0;right:0;bottom:0;left:0;display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;pointer-events:none;">
                    <p style="font-size:26px;font-weight:700;color:#111827;margin:0;line-height:1;">{{ $activeProjects }}</p>
                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Active</p>
                </div>
            </div>
            <div style="-webkit-box-flex:1;-ms-flex:1;flex:1;min-width:0;">
                <div style="display:-webkit-box;display:-ms-flexbox;display:flex;gap:20px;margin-bottom:14px;-ms-flex-wrap:wrap;flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <p style="font-size:22px;font-weight:700;color:#10B981;margin:0;line-height:1;">{{ $taskStats['completed'] }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:4px 0 0;">Success</p>
                    </div>
                    <div style="text-align:center;">
                        <p style="font-size:22px;font-weight:700;color:#F59E0B;margin:0;line-height:1;">{{ $taskStats['pending'] }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:4px 0 0;">Pending</p>
                    </div>
                    <div style="text-align:center;">
                        <p style="font-size:22px;font-weight:700;color:#6366F1;margin:0;line-height:1;">{{ $taskStats['in_progress'] }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:4px 0 0;">On-Going</p>
                    </div>
                </div>
                <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;gap:6px;">
                    @php $legendItems = [['Completed','#10B981'],['In Progress','#F59E0B'],['Pending','#60A5FA'],['Overdue','#EF4444']]; @endphp
                    @foreach($legendItems as [$lbl,$lc])
                    <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;gap:6px;">
                        <span style="width:9px;height:9px;border-radius:50%;background:{{ $lc }};display:inline-block;-ms-flex-negative:0;flex-shrink:0;"></span>
                        <span style="font-size:12px;color:#6B7280;">{{ $lbl }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Project list --}}
        <div style="border-top:1px solid #F3F4F6;padding-top:14px;display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;gap:8px;">
            @forelse($projects->take(4) as $proj)
            @php $pColors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6']; $c = $pColors[$loop->index % 5]; @endphp
            <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;gap:8px;">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $c }};-ms-flex-negative:0;flex-shrink:0;display:inline-block;"></span>
                <span style="-webkit-box-flex:1;-ms-flex:1;flex:1;font-size:12px;color:#374151;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->name }}</span>
                <button style="background:none;border:none;cursor:pointer;color:#D1D5DB;font-size:11px;padding:0;line-height:1;"><i class="fas fa-ellipsis-h"></i></button>
            </div>
            @empty
            <p style="font-size:12px;color:#9CA3AF;text-align:center;margin:0;">No projects yet</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ══ Bottom Row: Workload + Calendar ══ --}}
<div class="bottom-grid">

    {{-- Task Workload Bar Chart --}}
    <div class="dash-card anim-card anim-d6" style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;">
        <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:14px;font-weight:600;color:#111827;margin:0;">Task Workload</h3>
            <a href="{{ route('team.index') }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:500;">View more</a>
        </div>
        @if(count($workloadLabels) > 0)
            <div style="position:relative;width:100%;height:220px;">
                <canvas id="workloadChart"></canvas>
            </div>
        @else
            <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;height:220px;color:#D1D5DB;">
                <i class="fas fa-chart-bar" style="font-size:28px;margin-bottom:8px;"></i>
                <p style="font-size:12px;margin:0;">No data yet</p>
            </div>
        @endif
    </div>

    {{-- Calendar and Meetings dark card --}}
    @php
        $avatarColors  = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4'];
        $meetingColors = ['#1B4D3E','#1E3A5F','#3B1F5E','#4A1942','#1A3A4A'];
        $allMeetings   = $todayMeetings->count() ? $todayMeetings : $todayTaskEvents;
        $showMore      = max(0, $allMeetings->count() - 2);
    @endphp

    <div class="cal-card">

        {{-- Header --}}
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <div style="width:32px;height:32px;background:rgba(99,102,241,0.3);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-calendar-days" style="color:#A5B4FC;font-size:13px;"></i>
            </div>
            <span style="font-size:15px;font-weight:700;color:#fff;">Calendar and Meetings</span>
        </div>

        <div style="display:flex;gap:20px;flex-wrap:wrap;">

            {{-- Mini Calendar --}}
            <div style="min-width:220px;flex:0 0 220px;">

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:13px;font-weight:600;color:#E0E7FF;">{{ $calMonthLabel }}</span>
                        <button style="width:20px;height:20px;border-radius:50%;background:rgba(255,255,255,0.1);border:none;color:#A5B4FC;cursor:pointer;font-size:9px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button style="width:20px;height:20px;border-radius:50%;background:rgba(255,255,255,0.1);border:none;color:#A5B4FC;cursor:pointer;font-size:9px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <span style="font-size:10px;color:#6B7CB9;background:rgba(255,255,255,0.07);padding:2px 8px;border-radius:20px;">{{ $calWeekCount }} weeks</span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:1px;margin-bottom:5px;">
                    @foreach(['MO','TU','WE','TH','FR','SA','SU'] as $dh)
                    <div style="text-align:center;font-size:9px;font-weight:600;color:#6B7CB9;">{{ $dh }}</div>
                    @endforeach
                </div>

                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:1px;">
                    @foreach($calWeeks as $week)
                    @foreach($week as $day)
                    @php
                        $dk      = $day->format('Y-m-d');
                        $isToday = $dk === $calTodayKey;
                        $inMonth = $day->month === $firstOfMonth->month;
                        $dots    = $taskDotMap[$dk] ?? [];
                    @endphp
                    <div style="text-align:center;padding:1px 0;">
                        <div style="width:22px;height:22px;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:{{ $isToday ? '700' : '400' }};{{ $isToday ? 'background:#6366F1;color:#fff;' : ($inMonth ? 'color:#C7D2FE;' : 'color:#3A4570;') }}">{{ $day->format('j') }}</div>
                        @if(count($dots))
                        <div style="display:flex;justify-content:center;gap:1px;margin-top:1px;">
                            @foreach(array_slice($dots,0,3) as $dot)
                            <span style="width:3px;height:3px;border-radius:50%;background:{{ $dot }};display:inline-block;"></span>
                            @endforeach
                        </div>
                        @else
                        <div style="height:4px;"></div>
                        @endif
                    </div>
                    @endforeach
                    @endforeach
                </div>
            </div>

            {{-- Today Meetings --}}
            <div style="flex:1;min-width:0;">
                <div style="font-size:11px;font-weight:700;color:#6B7CB9;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:10px;">Today meetings</div>

                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;align-items:start;">

                    @forelse($allMeetings->take(2) as $i => $event)
                    @php
                        $bg      = $meetingColors[$i % count($meetingColors)];
                        $isEvent = $event instanceof \App\Models\CalendarEvent;
                        $title   = $event->title;
                        $desc    = $isEvent ? ($event->description ?? '') : ('Project: '.($event->project->name ?? ''));
                        $time    = $isEvent ? $event->start_date->format('H:i') : now()->format('H:i');
                        $person  = $isEvent ? $event->user : ($event->assignee ?? null);
                    @endphp
                    <div style="background:{{ $bg }};border-radius:10px;padding:12px;min-height:100px;display:flex;flex-direction:column;gap:6px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:11px;color:rgba(255,255,255,0.6);">{{ $time }}</span>
                            @if($person)
                            <div style="width:20px;height:20px;border-radius:50%;background:{{ $avatarColors[$i % count($avatarColors)] }};display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;color:#fff;">
                                {{ strtoupper(substr($person->name ?? 'U',0,1)) }}
                            </div>
                            @endif
                        </div>
                        <p style="font-size:13px;font-weight:700;color:#fff;margin:0;line-height:1.3;">{{ $title }}</p>
                        @if($desc)
                        <p style="font-size:10px;color:rgba(255,255,255,0.5);margin:0;line-height:1.4;">{{ Str::limit($desc,45) }}</p>
                        @endif
                    </div>
                    @empty
                    <div style="background:rgba(255,255,255,0.05);border-radius:10px;padding:14px;text-align:center;color:rgba(255,255,255,0.35);font-size:11px;grid-column:1/-1;">
                        <i class="fas fa-calendar-check" style="font-size:20px;margin-bottom:6px;display:block;"></i>
                        No meetings today
                    </div>
                    @endforelse

                    {{-- X More --}}
                    @if($showMore > 0)
                    <div style="background:rgba(255,255,255,0.07);border-radius:10px;padding:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;min-height:80px;cursor:pointer;"
                         onclick="window.location='{{ route('calendar.index') }}'">
                        <span style="font-size:18px;font-weight:700;color:#E0E7FF;">{{ $showMore }}</span>
                        <span style="font-size:10px;color:rgba(255,255,255,0.4);">More</span>
                    </div>
                    @endif

                    {{-- Add New Meeting --}}
                    <a href="{{ route('calendar.index') }}"
                       style="background:rgba(255,255,255,0.07);border-radius:10px;padding:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;min-height:80px;text-decoration:none;transition:background 0.15s;"
                       onmouseover="this.style.background='rgba(99,102,241,0.3)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.07)'">
                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-plus" style="color:#A5B4FC;font-size:12px;"></i>
                        </div>
                        <span style="font-size:10px;color:rgba(255,255,255,0.45);text-align:center;line-height:1.3;">Add new<br>meeting</span>
                    </a>

                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
// Count-up animation for stat numbers
(function() {
    var els = document.querySelectorAll('.stat-count[data-target]');
    els.forEach(function(el) {
        var target = parseInt(el.getAttribute('data-target'), 10);
        if (!target || target <= 0) return;
        var duration = 900, start = null;
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(eased * target);
            if (p < 1) requestAnimationFrame(step);
        }
        el.textContent = '0';
        requestAnimationFrame(step);
    });
})();

Chart.defaults.font = { family: 'Inter, sans-serif', size: 12 };
Chart.defaults.color = '#9CA3AF';

// Line Chart
var chartWorkingHours = new Chart(document.getElementById('workingHoursChart'), {
    type: 'line',
    data: {
        labels: @json($weekLabels),
        datasets: [{
            label: 'Activity',
            data: @json($weekData),
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99,102,241,0.08)',
            borderWidth: 2.5,
            pointRadius: 4,
            pointBackgroundColor: '#6366F1',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            tension: 0.4,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: { grid: { color: '#F3F4F6' }, border: { display: false }, beginAtZero: true, ticks: { stepSize: 1, maxTicksLimit: 6 } }
        }
    }
});

// Donut Chart
var chartProjectStats = new Chart(document.getElementById('projectStatsChart'), {
    type: 'doughnut',
    data: {
        labels: ['Completed','In Progress','Pending','Overdue'],
        datasets: [{
            data: [
                {{ $taskStats['completed'] }},
                {{ $taskStats['in_progress'] }},
                {{ $taskStats['pending'] }},
                {{ $taskStats['overdue'] }}
            ],
            backgroundColor: ['#10B981','#F59E0B','#60A5FA','#EF4444'],
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '70%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: function(ctx) { return ' ' + ctx.label + ': ' + ctx.parsed; } } }
        }
    }
});

// Bar Chart — Task Workload
var chartWorkload = null;
@if(count($workloadLabels) > 0)
chartWorkload = new Chart(document.getElementById('workloadChart'), {
    type: 'bar',
    data: {
        labels: @json($workloadLabels),
        datasets: [{
            label: 'Open Tasks',
            data: @json($workloadData),
            backgroundColor: [
                'rgba(99,102,241,0.85)',
                'rgba(16,185,129,0.85)',
                'rgba(245,158,11,0.85)',
                'rgba(239,68,68,0.85)',
                'rgba(139,92,246,0.85)',
                'rgba(59,130,246,0.85)',
            ],
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: { grid: { color: '#F3F4F6' }, border: { display: false }, beginAtZero: true, ticks: { stepSize: 1, maxTicksLimit: 6 } }
        }
    }
});
@endif

// ── Auto-Refresh (every 60 seconds) ──────────────────────────────────────
(function() {
    var REFRESH_URL    = '{{ route('admin.dashboard.refresh') }}';
    var INTERVAL_MS    = 60000;
    var dot            = document.getElementById('refreshDot');
    var label          = document.getElementById('refreshLabel');
    var workloadWrap   = document.getElementById('workloadChart') ? document.getElementById('workloadChart').parentNode : null;

    function setRV(key, val) {
        var els = document.querySelectorAll('[data-rv="' + key + '"]');
        for (var i = 0; i < els.length; i++) els[i].textContent = val;
    }

    function updateRateCircle(circleId, textId, rate) {
        var circle = document.getElementById(circleId);
        var text   = document.getElementById(textId);
        if (circle) circle.setAttribute('stroke-dasharray', rate + ' ' + (100 - rate));
        if (text)   text.textContent = rate + '%';
    }

    function doRefresh() {
        // show pulsing indicator
        if (dot)   { dot.classList.add('rv-pulse'); dot.style.background = '#F59E0B'; }
        if (label) label.textContent = 'Refreshing…';

        fetch(REFRESH_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function(d) {
                // ── Stat cards ──
                setRV('totalTasks',        d.totalTasks);
                setRV('activeProjects',    d.activeProjects);
                setRV('scheduledMeetings', d.scheduledMeetings);
                setRV('totalMembers',      d.totalMembers);
                setRV('activeMembers',     d.activeMembers);
                setRV('managerCount',      d.managerCount);
                setRV('userCount',         d.userCount);

                // ── Task Analytics tiles ──
                var ov = d.taskOverview;
                setRV('overview_assigned',      ov.assigned);
                setRV('overview_pending',        ov.pending);
                setRV('overview_in_progress',    ov.in_progress);
                setRV('overview_in_review',      ov.in_review);
                setRV('overview_completed',      ov.completed);
                setRV('overview_delivered',      ov.delivered);
                setRV('overview_overdue',        ov.overdue);
                setRV('overview_due_today',      ov.due_today);
                setRV('overview_due_this_week',  ov.due_this_week);

                // ── Rate circles ──
                updateRateCircle('rateCircleCompletion', 'rateTextCompletion', d.completionRate);
                updateRateCircle('rateCircleOnTime',     'rateTextOnTime',     d.onTimeRate);
                var rcEl = document.getElementById('reviewCyclesVal');
                if (rcEl) rcEl.textContent = d.reviewCycles;
                var rsSub = document.getElementById('rateSubCompletion');
                if (rsSub) rsSub.textContent = (ov.completed + ov.delivered) + ' of ' + ov.total + ' tasks done';

                // ── Working Hours chart ──
                if (typeof chartWorkingHours !== 'undefined' && chartWorkingHours) {
                    chartWorkingHours.data.labels = d.weekLabels;
                    chartWorkingHours.data.datasets[0].data = d.weekData;
                    chartWorkingHours.update('none');
                }

                // ── Project Stats donut ──
                if (typeof chartProjectStats !== 'undefined' && chartProjectStats) {
                    var ts = d.taskStats;
                    chartProjectStats.data.datasets[0].data = [ts.completed, ts.in_progress, ts.pending, ts.overdue];
                    chartProjectStats.update('none');
                    // update center label
                    var centerEl = document.querySelector('#projectStatsChart').parentNode.querySelector('p');
                    if (centerEl) centerEl.textContent = d.activeProjects;
                }

                // ── Workload bar chart ──
                if (d.workloadData && d.workloadData.length > 0) {
                    if (typeof chartWorkload !== 'undefined' && chartWorkload) {
                        chartWorkload.data.labels = d.workloadLabels;
                        chartWorkload.data.datasets[0].data = d.workloadData;
                        chartWorkload.update('none');
                    } else if (workloadWrap) {
                        // chart wasn't created on load (no data then) — create it now
                        var canvas = document.createElement('canvas');
                        canvas.id = 'workloadChart';
                        workloadWrap.innerHTML = '';
                        workloadWrap.appendChild(canvas);
                        chartWorkload = new Chart(canvas, {
                            type: 'bar',
                            data: {
                                labels: d.workloadLabels,
                                datasets: [{
                                    label: 'Open Tasks',
                                    data: d.workloadData,
                                    backgroundColor: ['rgba(99,102,241,0.85)','rgba(16,185,129,0.85)','rgba(245,158,11,0.85)','rgba(239,68,68,0.85)','rgba(139,92,246,0.85)','rgba(59,130,246,0.85)'],
                                    borderRadius: 6, borderSkipped: false,
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { grid: { display: false }, border: { display: false } },
                                    y: { grid: { color: '#F3F4F6' }, border: { display: false }, beginAtZero: true, ticks: { stepSize: 1, maxTicksLimit: 6 } }
                                }
                            }
                        });
                    }
                }

                // ── Indicator: success ──
                if (dot)   { dot.classList.remove('rv-pulse'); dot.style.background = '#10B981'; }
                if (label) label.textContent = 'Updated ' + d.refreshedAt;
            })
            .catch(function() {
                if (dot)   { dot.classList.remove('rv-pulse'); dot.style.background = '#EF4444'; }
                if (label) label.textContent = 'Refresh failed';
            });
    }

    setInterval(doRefresh, INTERVAL_MS);
})();
</script>
@endpush

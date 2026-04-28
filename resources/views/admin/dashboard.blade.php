@extends('layouts.app')

@section('title', 'Overview')

@section('content')

<style>
/* ── Box-sizing reset (cross-browser) ── */
*, *::before, *::after { box-sizing: border-box; }

/* ── Responsive grid helpers ── */
.stats-grid   { display: -ms-grid; display:grid; -ms-grid-columns: 1fr 16px 1fr 16px 1fr 16px 1fr; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px; }
.charts-grid  { display: -ms-grid; display:grid; -ms-grid-columns: 3fr 16px 2fr;  grid-template-columns:3fr 2fr; gap:16px; margin-bottom:16px; align-items:stretch; }
.bottom-grid  { display: -ms-grid; display:grid; -ms-grid-columns: 3fr 16px 2fr;  grid-template-columns:3fr 2fr; gap:16px; margin-bottom:0; align-items:stretch; }

/* ── Working Hours chart — grows to fill card height ── */
.wh-chart-wrap { position:relative; width:100%; height:220px; min-height:220px; }

/* ── Project Stats card — matched height ── */
.project-stats-card { display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-orient:vertical; -webkit-box-direction:normal; -ms-flex-direction:column; flex-direction:column; }

@media(max-width:1200px){
    .charts-grid  { grid-template-columns:3fr 2fr; }
    .bottom-grid  { grid-template-columns:3fr 2fr; }
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
            <form method="POST" action="{{ $dashQuickTaskUrl }}" style="padding:20px 24px 24px;">
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

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
                    <div>
                        <label class="form-label">Link to Project <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— optional</span></label>
                        <select name="project_id" class="form-input form-select">
                            <option value="">— No project —</option>
                            @foreach($allProjects as $proj)
                            <option value="{{ $proj->id }}" {{ old('project_id') == $proj->id ? 'selected' : '' }}>
                                {{ $proj->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Customer <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— optional</span></label>
                        <select name="customer_id" class="form-input form-select"
                                onfocus="this.style.borderColor='#F59E0B';this.style.boxShadow='0 0 0 3px rgba(245,158,11,0.12)'"
                                onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow=''">
                            <option value="">— No customer —</option>
                            @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}{{ $c->company ? ' ('.$c->company.')' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
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

        <div @click="projectOpen = false" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(4px);"></div>

        <div style="position:relative;width:100%;max-width:680px;max-height:92vh;background:#fff;border-radius:24px;box-shadow:0 32px 80px rgba(0,0,0,0.22);display:flex;flex-direction:column;">

            {{-- Header --}}
            <div style="padding:24px 28px 0;flex-shrink:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#111827;margin:0;">New Project</h2>
                        <p style="font-size:12px;color:#9CA3AF;margin:3px 0 0;" x-text="'Step ' + projectStep + ' of 3'"></p>
                    </div>
                    <button @click="projectOpen = false"
                            style="width:34px;height:34px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:14px;flex-shrink:0;">
                        <i class="fa fa-times"></i>
                    </button>
                </div>

                {{-- Step Indicators --}}
                <div style="display:flex;align-items:flex-start;justify-content:center;margin-bottom:28px;">
                    <template x-for="s in 3" :key="s">
                        <div style="display:flex;align-items:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:6px;min-width:72px;">
                                <button type="button"
                                        @click="if(s <= projectStep) projectStep = s"
                                        :style="projectStep >= s
                                            ? 'width:36px;height:36px;border-radius:50%;background:#4F46E5;color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;box-shadow:0 2px 10px rgba(79,70,229,.35);'
                                            : 'width:36px;height:36px;border-radius:50%;background:#F3F4F6;color:#9CA3AF;border:none;font-size:13px;font-weight:700;cursor:default;display:flex;align-items:center;justify-content:center;transition:all .2s;'">
                                    <span x-show="projectStep > s" style="display:flex;"><i class="fa fa-check" style="font-size:11px;"></i></span>
                                    <span x-show="projectStep <= s" x-text="s"></span>
                                </button>
                                <span :style="projectStep >= s ? 'font-size:11px;font-weight:600;color:#4F46E5;white-space:nowrap;' : 'font-size:11px;font-weight:500;color:#9CA3AF;white-space:nowrap;'"
                                      x-text="s === 1 ? 'Details' : s === 2 ? 'Tasks' : 'Attachments'"></span>
                            </div>
                            <template x-if="s < 3">
                                <div :style="projectStep > s
                                    ? 'width:64px;height:2px;background:#4F46E5;border-radius:2px;margin:0 4px;margin-bottom:22px;transition:all .3s;'
                                    : 'width:64px;height:2px;background:#E5E7EB;border-radius:2px;margin:0 4px;margin-bottom:22px;transition:all .3s;'"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Body --}}
            <form method="POST" action="{{ $dashProjectStoreUrl }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;flex:1;min-height:0;">
                @csrf
                <div style="flex:1;overflow-y:auto;padding:0 28px 4px;">

                    {{-- Step 1: Details --}}
                    <div x-show="projectStep === 1">
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                Project Name <span style="color:#EF4444;">*</span>
                            </label>
                            <input type="text" name="name" required value="{{ old('name') }}"
                                   x-ref="pWizardName" @input="pNameError = false"
                                   placeholder="e.g. Mobile App Redesign"
                                   :style="pNameError
                                       ? 'width:100%;padding:10px 14px;border:1.5px solid #EF4444;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;background:#FEF2F2;'
                                       : 'width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;background:#fff;'"
                                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                            <p x-show="pNameError" style="margin:4px 0 0;font-size:11px;color:#EF4444;">
                                <i class="fa fa-circle-exclamation" style="margin-right:3px;"></i>Project name is required.
                            </p>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                Brief <span style="font-size:11px;font-weight:400;color:#9CA3AF;margin-left:4px;">— short summary of the project goal</span>
                            </label>
                            <textarea name="description" rows="2" placeholder="What is this project about?"
                                      style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;resize:none;font-family:'Inter',sans-serif;"
                                      onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">{{ old('description') }}</textarea>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                    First Review Date
                                    <span style="font-size:10px;font-weight:400;color:#9CA3AF;">optional</span>
                                </label>
                                <input type="date" name="first_review_date" value="{{ old('first_review_date') }}"
                                       style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;"
                                       onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                    Deadline <span style="color:#EF4444;">*</span>
                                </label>
                                <input type="date" name="deadline" required value="{{ old('deadline') }}"
                                       x-ref="pWizardDeadline" @change="pDeadlineError = false"
                                       :style="pDeadlineError
                                           ? 'width:100%;padding:10px 14px;border:1.5px solid #EF4444;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;background:#FEF2F2;'
                                           : 'width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;'">
                                <p x-show="pDeadlineError" style="margin:4px 0 0;font-size:11px;color:#EF4444;">
                                    <i class="fa fa-circle-exclamation" style="margin-right:3px;"></i>Deadline is required.
                                </p>
                            </div>
                        </div>
                        <div style="margin-bottom:8px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                <label style="font-size:12px;font-weight:600;color:#374151;">
                                    Customer
                                    <span style="font-size:10px;font-weight:400;color:#9CA3AF;">— optional</span>
                                </label>
                                <a href="{{ route('admin.customers.create') }}" target="_blank"
                                   style="font-size:11px;font-weight:600;color:#4F46E5;text-decoration:none;display:flex;align-items:center;gap:3px;">
                                    <i class="fas fa-plus" style="font-size:9px;"></i> New customer
                                </a>
                            </div>
                            <select name="customer_id"
                                    style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;background:#fff;outline:none;box-sizing:border-box;"
                                    onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                                <option value="">— No customer —</option>
                                @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}{{ $c->company ? ' ('.$c->company.')' : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Step 2: Tasks --}}
                    <div x-show="projectStep === 2">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#374151;margin:0;">Assign Tasks</p>
                                <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Assignees are automatically added as project members</p>
                            </div>
                            <button type="button" @click="pAddTask()"
                                    style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:#EEF2FF;color:#4F46E5;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                                <i class="fas fa-plus" style="font-size:10px;"></i> Add Task
                            </button>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:14px;">
                            <template x-for="(task, i) in pTasks" :key="i">
                                <div :style="(task.titleError || task.assigneeError)
                                    ? 'border:1.5px solid #FCA5A5;border-radius:14px;padding:18px;background:#FAFBFF;'
                                    : 'border:1.5px solid #E5E7EB;border-radius:14px;padding:18px;background:#FAFBFF;'">

                                    {{-- Task header --}}
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                                        <span style="font-size:11px;font-weight:700;color:#6366F1;text-transform:uppercase;letter-spacing:.06em;background:#EEF2FF;padding:3px 10px;border-radius:20px;">
                                            Task <span x-text="i + 1"></span>
                                        </span>
                                        <button type="button" @click="if(pTasks.length>1) pTasks.splice(i,1)" x-show="pTasks.length > 1"
                                                style="width:26px;height:26px;border-radius:7px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>

                                    {{-- Title --}}
                                    <div style="margin-bottom:12px;">
                                        <input type="text" :name="'tasks['+i+'][title]'" x-model="task.title"
                                               @input="task.titleError = false"
                                               placeholder="Task title *"
                                               :style="task.titleError
                                                   ? 'width:100%;padding:9px 12px;border:1.5px solid #EF4444;border-radius:8px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;background:#FEF2F2;'
                                                   : 'width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;background:#fff;'">
                                        <p x-show="task.titleError" style="margin:3px 0 0;font-size:11px;color:#EF4444;display:flex;align-items:center;gap:3px;">
                                            <i class="fa fa-circle-exclamation"></i> Task title is required.
                                        </p>
                                    </div>

                                    {{-- Assignees --}}
                                    <div style="margin-bottom:12px;">
                                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                            <label style="font-size:11px;font-weight:600;color:#6B7280;">Assignees <span style="color:#EF4444;">*</span></label>
                                            <button type="button" @click="task.assignees.push({user_id:'',role:''})"
                                                    style="font-size:10px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:none;padding:3px 10px;border-radius:6px;cursor:pointer;">
                                                + Add Person
                                            </button>
                                        </div>
                                        <div style="display:flex;flex-direction:column;gap:6px;">
                                            <template x-for="(assignee, j) in task.assignees" :key="j">
                                                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center;">
                                                    <select :name="'tasks['+i+'][assignees]['+j+'][user_id]'" x-model="assignee.user_id"
                                                            @change="task.assigneeError = false"
                                                            :style="task.assigneeError && j === 0
                                                                ? 'width:100%;padding:7px 10px;border:1.5px solid #EF4444;border-radius:8px;font-size:12px;color:#111827;background:#FEF2F2;outline:none;box-sizing:border-box;'
                                                                : 'width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;background:#fff;outline:none;box-sizing:border-box;'">
                                                        <option value="">— Select person —</option>
                                                        <template x-for="u in pAllUsers" :key="u.id">
                                                            <option :value="u.id" x-text="u.name + ' (' + u.role + ')'"></option>
                                                        </template>
                                                    </select>
                                                    <input type="text" :name="'tasks['+i+'][assignees]['+j+'][role]'" x-model="assignee.role"
                                                           placeholder="Role (e.g. designer)"
                                                           style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                                    <button type="button" @click="if(task.assignees.length>1) task.assignees.splice(j,1)" x-show="task.assignees.length > 1"
                                                            style="width:28px;height:28px;border-radius:7px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <div x-show="task.assignees.length === 1" style="width:28px;"></div>
                                                </div>
                                            </template>
                                            <p x-show="task.assigneeError" style="margin:4px 0 0;font-size:11px;color:#EF4444;display:flex;align-items:center;gap:3px;">
                                                <i class="fa fa-circle-exclamation"></i> Please assign at least one person.
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Reviewer + Priority + Deadline --}}
                                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px;">
                                        <div>
                                            <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">Reviewer</label>
                                            <select :name="'tasks['+i+'][reviewer_id]'" x-model="task.reviewer_id"
                                                    style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                                                <option value="">— None —</option>
                                                <template x-for="u in pAllUsers" :key="u.id">
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

                                    {{-- Brief with @mention --}}
                                    <div style="position:relative;margin-bottom:12px;">
                                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">
                                            Brief
                                            <span style="font-weight:400;color:#9CA3AF;">— type <kbd style="font-size:10px;background:#F3F4F6;border:1px solid #E5E7EB;border-radius:4px;padding:0 4px;">@</kbd> to mention</span>
                                        </label>
                                        <textarea :name="'tasks['+i+'][description]'"
                                            x-model="task.description"
                                            @input="pOnDescInput($event, i)"
                                            @keydown="pMentionKeydown($event, i)"
                                            @blur="setTimeout(() => { pTasks[i].mentionOpen = false }, 150)"
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
                                                        @mousedown.prevent="pPickMention(u, i)"
                                                        :style="task.mentionCursor === idx ? 'background:#EEF2FF;' : 'background:#fff;'"
                                                        style="width:100%;padding:7px 12px;display:flex;align-items:center;gap:10px;border:none;border-bottom:1px solid #F9FAFB;cursor:pointer;text-align:left;transition:background .1s;">
                                                    <div :style="'width:26px;height:26px;border-radius:50%;background:'+pAvatarColor(u.id)+';display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;'"
                                                         x-text="u.name.charAt(0).toUpperCase()"></div>
                                                    <div style="flex:1;min-width:0;">
                                                        <p style="font-size:12px;font-weight:600;color:#111827;margin:0;" x-text="u.name"></p>
                                                        <p style="font-size:11px;color:#9CA3AF;margin:0;" x-text="u.role"></p>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Tags --}}
                                    <div>
                                        <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">
                                            Tags <span style="font-weight:400;color:#9CA3AF;">— comma separated, e.g. #design, #urgent</span>
                                        </label>
                                        <input type="text" :name="'tasks['+i+'][tags]'" x-model="task.tags"
                                               placeholder="#video, #urgent"
                                               style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Step 3: Attachments --}}
                    <div x-show="projectStep === 3">
                        <p style="font-size:12px;font-weight:600;color:#374151;margin:0 0 10px;">
                            Files <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— max 20 MB each</span>
                        </p>
                        <div @dragover.prevent="pDragover = true"
                             @dragleave.prevent="pDragover = false"
                             @drop.prevent="pDragover = false; pHandleFiles($event)"
                             @click="$refs.pFileInput.click()"
                             :style="pDragover
                                 ? 'border:2px dashed #6366F1;border-radius:14px;padding:32px 24px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:14px;background:#EEF2FF;'
                                 : 'border:2px dashed #D1D5DB;border-radius:14px;padding:32px 24px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:14px;background:#FAFAFA;'">
                            <div style="width:48px;height:48px;border-radius:12px;background:#F0F9FF;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                                <i class="fas fa-cloud-arrow-up" style="font-size:20px;color:#0EA5E9;"></i>
                            </div>
                            <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 4px;">Drop files here or <span style="color:#6366F1;">browse</span></p>
                            <p style="font-size:11px;color:#9CA3AF;margin:0;">PDF, Word, Images, Video — up to 20 MB</p>
                            <input type="file" name="attachments[]" multiple x-ref="pFileInput"
                                   @change="pHandleFiles($event)" style="display:none;">
                        </div>
                        <template x-if="pFiles.length > 0">
                            <div style="margin-bottom:16px;display:flex;flex-direction:column;gap:6px;">
                                <template x-for="(file, i) in pFiles" :key="i">
                                    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;">
                                        <div style="width:34px;height:34px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i :class="'fas ' + pFileIcon(file.name)" style="font-size:14px;color:#6366F1;"></i>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="file.name"></p>
                                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;" x-text="pFormatSize(file.size)"></p>
                                        </div>
                                        <button type="button" @click.stop="pRemoveFile(i)"
                                                style="width:26px;height:26px;border-radius:7px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <div style="margin-top:8px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                                <p style="font-size:12px;font-weight:600;color:#374151;margin:0;">Links</p>
                                <button type="button" @click="pLinks.push({url:'',label:''})"
                                        style="font-size:11px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:none;padding:5px 14px;border-radius:7px;cursor:pointer;display:flex;align-items:center;gap:4px;">
                                    <i class="fas fa-plus" style="font-size:9px;"></i> Add Link
                                </button>
                            </div>
                            <template x-if="pLinks.length > 0">
                                <div style="display:flex;flex-direction:column;gap:8px;">
                                    <template x-for="(link, i) in pLinks" :key="i">
                                        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center;">
                                            <input type="url" :name="'links['+i+'][url]'" x-model="link.url"
                                                   placeholder="https://..."
                                                   style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                            <input type="text" :name="'links['+i+'][label]'" x-model="link.label"
                                                   placeholder="Label (optional)"
                                                   style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                            <button type="button" @click="pLinks.splice(i,1)"
                                                    style="width:28px;height:28px;border-radius:7px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <p x-show="pLinks.length === 0" style="font-size:12px;color:#9CA3AF;margin:0;">No links yet — click "+ Add Link".</p>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div style="padding:16px 28px;border-top:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fff;border-radius:0 0 24px 24px;">
                    <button type="button" @click="projectStep > 1 ? projectStep-- : projectOpen = false"
                            style="padding:9px 20px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
                        <i class="fa fa-arrow-left" style="font-size:11px;"></i>
                        <span x-text="projectStep > 1 ? 'Back' : 'Cancel'"></span>
                    </button>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <template x-for="s in 3" :key="s">
                            <div :style="projectStep === s
                                ? 'width:8px;height:8px;border-radius:50%;background:#4F46E5;transition:all .2s;'
                                : 'width:6px;height:6px;border-radius:50%;background:#E5E7EB;transition:all .2s;'"></div>
                        </template>
                    </div>
                    <template x-if="projectStep < 3">
                        <button type="button" @click="pNextStep()"
                                style="padding:9px 22px;background:#4F46E5;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(79,70,229,.3);">
                            Next <i class="fa fa-arrow-right" style="font-size:11px;"></i>
                        </button>
                    </template>
                    <template x-if="projectStep === 3">
                        <button type="submit"
                                style="padding:9px 22px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 4px 14px rgba(99,102,241,.35);">
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
        pAllUsers:      {!! $allUsers->map(fn($u) => ['id' => (string)$u->id, 'name' => $u->name, 'role' => ucfirst($u->role), 'job' => $u->job_title ?? ''])->toJson() !!},

        pBlankTask() {
            return { title:'', task_type:'', tags:'', assignees:[{user_id:'',role:''}], reviewer_id:'', priority:'medium', deadline:'', description:'',
                     mentionOpen:false, mentionResults:[], mentionCursor:0, mentionStart:-1, _ta:null,
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

        pOnDescInput(event, i) {
            const ta  = event.target;
            const t   = this.pTasks[i];
            t._ta     = ta;
            const pos = ta.selectionStart;
            const m   = ta.value.slice(0, pos).match(/@([^\s@]*)$/);
            if (m) {
                const q          = m[1].toLowerCase();
                t.mentionStart   = pos - m[0].length;
                t.mentionResults = this.pAllUsers.filter(u =>
                    u.name.toLowerCase().includes(q) || (u.job && u.job.toLowerCase().includes(q))
                ).slice(0, 6);
                t.mentionOpen   = t.mentionResults.length > 0;
                t.mentionCursor = 0;
            } else {
                t.mentionOpen = false;
            }
        },

        pMentionKeydown(event, i) {
            const t = this.pTasks[i];
            if (!t.mentionOpen) return;
            if (event.key === 'ArrowDown')       { event.preventDefault(); t.mentionCursor = Math.min(t.mentionCursor + 1, t.mentionResults.length - 1); }
            else if (event.key === 'ArrowUp')    { event.preventDefault(); t.mentionCursor = Math.max(t.mentionCursor - 1, 0); }
            else if (event.key === 'Enter')      { event.preventDefault(); this.pPickMention(t.mentionResults[t.mentionCursor], i); }
            else if (event.key === 'Escape')     { t.mentionOpen = false; }
        },

        pPickMention(user, i) {
            const t      = this.pTasks[i];
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

        pAvatarColor(id) {
            return ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4'][parseInt(id) % 8];
        },

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
            if (['pdf'].includes(ext))                                    return 'fa-file-pdf';
            if (['doc','docx'].includes(ext))                             return 'fa-file-word';
            if (['xls','xlsx'].includes(ext))                             return 'fa-file-excel';
            if (['ppt','pptx'].includes(ext))                             return 'fa-file-powerpoint';
            if (['zip','rar','7z'].includes(ext))                         return 'fa-file-zipper';
            if (['jpg','jpeg','png','gif','webp','svg'].includes(ext))    return 'fa-file-image';
            if (['mp4','mov','avi','mkv'].includes(ext))                  return 'fa-file-video';
            if (['mp3','wav','aac'].includes(ext))                        return 'fa-file-audio';
            return 'fa-file';
        },
    };
}
</script>
@endpush

@php
    $devMode    = ($appSettings['developer_mode'] ?? '0') === '1';
    $devHidden  = json_decode($appSettings['hidden_elements'] ?? '[]', true) ?: [];
    $devExtras  = json_decode($appSettings['shown_extras']    ?? '[]', true) ?: [];
    $allExtras  = [
        'dash_priority_chart'   => 'Tasks by Priority',
        'dash_team_performance' => 'Team Performance',
        'dash_project_progress' => 'Project Progress',
    ];
@endphp

{{-- ══ Dev Mode Banner (always rendered, shown/hidden via JS) ══ --}}
<div id="dev-mode-banner" style="display:{{ $devMode ? 'flex' : 'none' }};align-items:center;justify-content:space-between;gap:12px;padding:10px 18px;background:linear-gradient(90deg,#4F46E5,#7C3AED);border-radius:12px;margin-bottom:16px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-code" style="color:#fff;font-size:13px;"></i>
        </div>
        <div>
            <p style="font-size:13px;font-weight:700;color:#fff;margin:0;">Developer Mode Active</p>
            <p style="font-size:11px;color:rgba(255,255,255,.7);margin:0;">Click any dashboard section to hide it. Go to Settings → Developer to restore hidden sections.</p>
        </div>
    </div>
    <a href="{{ route('admin.settings.index') }}#developer"
       style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:rgba(255,255,255,.15);color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;border:1px solid rgba(255,255,255,.25);flex-shrink:0;transition:background .15s;"
       onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
        <i class="fas fa-gear" style="font-size:10px;"></i> Manage Sections
    </a>
</div>

{{-- ══ Stats Row ══ --}}
@if(!in_array('dash_stats', $devHidden))
<div class="stats-grid" data-dev-key="dash_stats" data-dev-label="Overview Cards">

    {{-- Tasks --}}
    <a href="{{ $dashHomeUrl }}" style="text-decoration:none;display:flex;">
    <div class="stat-card anim-card anim-d1" style="flex:1;background:linear-gradient(135deg,#4F46E5,#6366F1);cursor:pointer;transition:transform .15s,box-shadow .15s;"
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
    <a href="{{ $dashProjectsUrl }}" style="text-decoration:none;display:flex;">
    <div class="stat-card anim-card anim-d2" style="flex:1;background:linear-gradient(135deg,#059669,#10B981);cursor:pointer;transition:transform .15s,box-shadow .15s;"
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
    <a href="{{ route('calendar.index') }}" style="text-decoration:none;display:flex;">
    <div class="stat-card anim-card anim-d3" style="flex:1;background:linear-gradient(135deg,#7C3AED,#8B5CF6);cursor:pointer;transition:transform .15s,box-shadow .15s;"
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

    {{-- Team --}}
    <a href="{{ route('team.index') }}" style="text-decoration:none;display:flex;">
    <div class="stat-card anim-card anim-d4" style="flex:1;background:linear-gradient(135deg,#0E7490,#0891B2);cursor:pointer;transition:transform .15s,box-shadow .15s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(8,145,178,.4)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''"
         onmousedown="this.style.transform='translateY(-1px)'"
         onmouseup="this.style.transform='translateY(-3px)'">
        <div class="stat-card-blob"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p class="stat-card-label">Team</p>
            <button class="stat-card-menu" onclick="event.preventDefault()"><i class="fas fa-ellipsis-h"></i></button>
        </div>
        <p class="stat-card-value stat-count" data-target="{{ $totalMembers }}" data-rv="totalMembers">{{ $totalMembers }}</p>
        <p class="stat-card-sub">
            <span data-rv="activeMembers">{{ $activeMembers }}</span> active ·
            <span data-rv="managerCount">{{ $managerCount }}</span> mgr ·
            <span data-rv="userCount">{{ $userCount }}</span> users
        </p>
    </div>
    </a>

</div>
@endif

{{-- ══ Task Analytics ══ --}}
@if(!in_array('dash_task_analytics', $devHidden))
<div class="dash-card anim-card anim-d5" style="margin-bottom:16px;" data-dev-key="dash_task_analytics" data-dev-label="Task Analytics">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
        <div>
            <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-chart-pie" style="color:#6366F1;font-size:13px;"></i> Task Analytics
            </h3>
            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">All-time overview across every task in the system</p>
        </div>
        <a href="{{ $dashProjectsUrl }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:600;">View Projects →</a>
    </div>

    {{-- Main grid: status counts --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;">

        @php
        $tasksBase = route('admin.tasks.index');
        $analyticsItems = [
            ['label'=>'Pending',        'value'=>$taskOverview['pending'],                                       'key'=>'pending',       'icon'=>'fa-clock',               'bg'=>'#F3F4F6','color'=>'#6B7280', 'url'=>$tasksBase.'?filter=pending'],
            ['label'=>'In Progress',    'value'=>$taskOverview['in_progress'],                                   'key'=>'in_progress',   'icon'=>'fa-spinner',             'bg'=>'#FEF3C7','color'=>'#D97706', 'url'=>$tasksBase.'?status=in_progress'],
            ['label'=>'Waiting Review', 'value'=>$taskOverview['in_review'],                                     'key'=>'in_review',     'icon'=>'fa-gavel',               'bg'=>'#EDE9FE','color'=>'#7C3AED', 'url'=>$tasksBase.'?status=submitted'],
            ['label'=>'Done',           'value'=>$taskOverview['completed'] + $taskOverview['delivered'] + ($taskOverview['archived'] ?? 0), 'key'=>'done', 'icon'=>'fa-circle-check', 'bg'=>'#D1FAE5','color'=>'#059669', 'url'=>$tasksBase.'?filter=done'],
            ['label'=>'Overdue',        'value'=>$taskOverview['overdue'],                                       'key'=>'overdue',       'icon'=>'fa-triangle-exclamation','bg'=>'#FEE2E2','color'=>'#DC2626', 'url'=>$tasksBase.'?overdue=1'],
            ['label'=>'Due This Week',  'value'=>$taskOverview['due_this_week'],                                 'key'=>'due_this_week', 'icon'=>'fa-calendar-week',       'bg'=>'#F0F9FF','color'=>'#0284C7', 'url'=>$tasksBase.'?filter=due_this_week'],
            ['label'=>'Reopened',       'value'=>$taskOverview['reopened'],                                      'key'=>'reopened',      'icon'=>'fa-rotate-right',        'bg'=>'#FFF7ED','color'=>'#EA580C', 'url'=>$tasksBase.'?filter=reopened'],
            ['label'=>'Reassigned',     'value'=>$taskOverview['reassigned'],                                    'key'=>'reassigned',    'icon'=>'fa-arrows-rotate',       'bg'=>'#F0FDF4','color'=>'#16A34A', 'url'=>$tasksBase.'?filter=reassigned'],
        ];
        @endphp

        @foreach($analyticsItems as $item)
        <button onclick="openAnalyticsModal('{{ $item['key'] }}','{{ $item['label'] }}','{{ $item['color'] }}','{{ $item['bg'] }}','{{ $item['icon'] }}','{{ $item['url'] }}')"
                style="background:{{ $item['bg'] }};border-radius:12px;padding:14px 12px;display:flex;flex-direction:column;gap:6px;border:none;cursor:pointer;text-align:left;width:100%;transition:filter .15s,transform .15s,box-shadow .15s;"
                onmouseover="this.style.filter='brightness(0.96)';this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.08)'"
                onmouseout="this.style.filter='';this.style.transform='';this.style.boxShadow=''">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <i class="fas {{ $item['icon'] }}" style="font-size:12px;color:{{ $item['color'] }};"></i>
                    <span style="font-size:11px;font-weight:600;color:{{ $item['color'] }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $item['label'] }}</span>
                </div>
                <i class="fas fa-chevron-right" style="font-size:8px;color:{{ $item['color'] }};opacity:.5;"></i>
            </div>
            <p data-rv="overview_{{ $item['key'] }}" style="font-size:26px;font-weight:700;color:#111827;margin:0;line-height:1;">{{ $item['value'] }}</p>
        </button>
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
                <p id="rateSubCompletion" style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">{{ $taskOverview['completed'] + $taskOverview['delivered'] + ($taskOverview['archived'] ?? 0) }} of {{ $taskOverview['total'] }} tasks done</p>
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
@endif

{{-- ══ Charts Row ══ --}}
@if(!in_array('dash_working_hours', $devHidden) || !in_array('dash_project_stats', $devHidden))
<div class="charts-grid">

    {{-- Working Hours Line Chart --}}
    @if(!in_array('dash_working_hours', $devHidden))
    <div class="dash-card anim-card anim-d5" style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;" data-dev-key="dash_working_hours" data-dev-label="Working Hours Chart">
        <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;margin-bottom:16px;-ms-flex-wrap:wrap;flex-wrap:wrap;gap:8px;">
            <div>
                <h3 style="font-size:14px;font-weight:600;color:#111827;margin:0;">Working Hours Statistics</h3>
                <p id="wh-subtitle" style="font-size:12px;color:#9CA3AF;margin:3px 0 0;">Task activity last week</p>
            </div>
            <div id="wh-period-btns" style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;gap:2px;background:#F3F4F6;border-radius:8px;padding:3px;">
                <button data-period="week"  onclick="switchWhPeriod(this)" style="padding:4px 12px;font-size:12px;font-weight:500;background:#fff;border:none;border-radius:6px;cursor:pointer;color:#374151;box-shadow:0 1px 2px rgba(0,0,0,0.06);">Week</button>
                <button data-period="month" onclick="switchWhPeriod(this)" style="padding:4px 12px;font-size:12px;font-weight:500;background:none;border:none;border-radius:6px;cursor:pointer;color:#9CA3AF;">Month</button>
                <button data-period="year"  onclick="switchWhPeriod(this)" style="padding:4px 12px;font-size:12px;font-weight:500;background:none;border:none;border-radius:6px;cursor:pointer;color:#9CA3AF;">Year</button>
            </div>
        </div>
        <div class="wh-chart-wrap" style="flex:1;min-height:220px;cursor:pointer;">
            <canvas id="workingHoursChart"></canvas>
        </div>
        <p style="font-size:11px;color:#C4B5FD;margin:8px 0 0;text-align:center;">
            <i class="fas fa-hand-pointer" style="font-size:10px;margin-right:4px;"></i>Click any point to view tasks
        </p>
    </div>
    @endif

    {{-- Project Statistics Donut + Project List --}}
    @if(!in_array('dash_project_stats', $devHidden))
    <div class="dash-card anim-card anim-d6 project-stats-card" data-dev-key="dash_project_stats" data-dev-label="Project Statistics">
        <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:14px;font-weight:600;color:#111827;margin:0;">Project Statistics</h3>
            <a href="{{ $dashProjectsUrl }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:500;">View more</a>
        </div>

        {{-- Donut + legend side-by-side --}}
        <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;gap:20px;margin-bottom:16px;-ms-flex-wrap:wrap;flex-wrap:wrap;">
            <div style="position:relative;width:140px;height:140px;-ms-flex-negative:0;flex-shrink:0;">
                <canvas id="projectStatsChart" style="width:140px!important;height:140px!important;"></canvas>
                <div style="position:absolute;top:0;right:0;bottom:0;left:0;display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;pointer-events:none;">
                    <p style="font-size:26px;font-weight:700;color:#111827;margin:0;line-height:1;" data-rv="projectStats_total">{{ $taskStats['completed'] + $taskStats['in_progress'] + $taskStats['pending'] + $taskStats['overdue'] }}</p>
                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Projects</p>
                </div>
            </div>
            <div style="-webkit-box-flex:1;-ms-flex:1;flex:1;min-width:0;">
                <div style="display:-webkit-box;display:-ms-flexbox;display:flex;gap:20px;margin-bottom:14px;-ms-flex-wrap:wrap;flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <p style="font-size:22px;font-weight:700;color:#10B981;margin:0;line-height:1;" data-rv="projectStats_completed">{{ $taskStats['completed'] }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:4px 0 0;">Completed</p>
                    </div>
                    <div style="text-align:center;">
                        <p style="font-size:22px;font-weight:700;color:#6366F1;margin:0;line-height:1;" data-rv="projectStats_in_progress">{{ $taskStats['in_progress'] }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:4px 0 0;">In Progress</p>
                    </div>
                    <div style="text-align:center;">
                        <p style="font-size:22px;font-weight:700;color:#60A5FA;margin:0;line-height:1;" data-rv="projectStats_pending">{{ $taskStats['pending'] }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:4px 0 0;">Pending</p>
                    </div>
                    <div style="text-align:center;">
                        <p style="font-size:22px;font-weight:700;color:#EF4444;margin:0;line-height:1;" data-rv="projectStats_overdue">{{ $taskStats['overdue'] }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:4px 0 0;">Overdue</p>
                    </div>
                </div>
                <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;gap:6px;">
                    @php $legendItems = [['Completed','#10B981'],['In Progress','#6366F1'],['Pending','#60A5FA'],['Overdue','#EF4444']]; @endphp
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

        {{-- Social Media mini section --}}
        <div style="border-top:1px solid #F3F4F6;padding-top:14px;margin-top:6px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <div style="width:20px;height:20px;border-radius:6px;background:linear-gradient(135deg,#EEF2FF,#DDD6FE);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-share-nodes" style="color:#6366F1;font-size:9px;"></i>
                    </div>
                    <span style="font-size:12px;font-weight:700;color:#374151;">Social Media</span>
                </div>
                <a href="{{ route('admin.approvals.index') }}?tab=social" style="font-size:10px;color:#4F46E5;text-decoration:none;font-weight:600;">View all →</a>
            </div>

            {{-- 3 stat bubbles --}}
            <div style="display:-webkit-box;display:-ms-flexbox;display:flex;gap:10px;margin-bottom:10px;-ms-flex-wrap:wrap;flex-wrap:wrap;">
                <div style="text-align:center;flex:1;min-width:52px;">
                    <p style="font-size:20px;font-weight:700;color:#6366F1;margin:0;line-height:1;" data-rv="socialPostsTotal">{{ $socialPostsTotal }}</p>
                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Total Posts</p>
                </div>
                <div style="text-align:center;flex:1;min-width:52px;">
                    <p style="font-size:20px;font-weight:700;color:#D97706;margin:0;line-height:1;" data-rv="socialPostsMonth">{{ $socialPostsMonth }}</p>
                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">This Month</p>
                </div>
                <div style="text-align:center;flex:1;min-width:52px;">
                    <p style="font-size:20px;font-weight:700;color:{{ $socialPending > 0 ? '#EA580C' : '#10B981' }};margin:0;line-height:1;" data-rv="socialPending">{{ $socialPending }}</p>
                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Pending</p>
                </div>
            </div>

            {{-- Platform icons row --}}
            @if($socialPlatformStats->isNotEmpty())
            <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-ms-flex-wrap:wrap;flex-wrap:wrap;gap:4px;">
                @php $spIcons=['facebook'=>['fa-facebook','#1877F2'],'instagram'=>['fa-instagram','#E1306C'],'twitter'=>['fa-x-twitter','#000000'],'linkedin'=>['fa-linkedin','#0A66C2'],'tiktok'=>['fa-tiktok','#010101'],'youtube'=>['fa-youtube','#FF0000'],'snapchat'=>['fa-snapchat','#F7CA00'],'other'=>['fa-share-nodes','#6366F1']]; @endphp
                @foreach($socialPlatformStats->take(5) as $ps)
                @php [$spIco,$spCol] = $spIcons[$ps->platform] ?? $spIcons['other']; @endphp
                <button onclick="openSocialPostsModal('{{ $ps->platform }}')"
                        title="View {{ ucfirst($ps->platform) }} posts"
                        style="display:inline-flex;align-items:center;gap:3px;background:#F3F4F6;border:none;border-radius:20px;padding:4px 10px;cursor:pointer;transition:all .15s;"
                        onmouseover="this.style.background='#E0E7FF';this.style.transform='translateY(-1px)';this.style.boxShadow='0 2px 8px rgba(99,102,241,.15)'"
                        onmouseout="this.style.background='#F3F4F6';this.style.transform='';this.style.boxShadow=''">
                    <i class="fab {{ $spIco }}" style="font-size:11px;color:{{ $spCol }};"></i>
                    <span style="font-size:10px;font-weight:700;color:#374151;">{{ $ps->total }}</span>
                </button>
                @endforeach
            </div>
            @else
            <p style="font-size:11px;color:#D1D5DB;margin:0;">No social posts yet — assign from History tab.</p>
            @endif
        </div>

    </div>
    @endif
</div>
@endif

{{-- ══ Bottom Row: Workload + Customer Tasks ══ --}}
@if(!in_array('dash_workload', $devHidden) || !in_array('dash_customers', $devHidden))
<div class="bottom-grid">

    {{-- Task Workload Bar Chart --}}
    @if(!in_array('dash_workload', $devHidden))
    <div class="dash-card anim-card anim-d6" style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;" data-dev-key="dash_workload" data-dev-label="Task Workload Chart">
        <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:14px;font-weight:600;color:#111827;margin:0;">Task Workload</h3>
            <a href="{{ route('team.index') }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:500;">View more</a>
        </div>
        @if(count($workloadLabels) > 0)
            <div style="position:relative;width:100%;flex:1;min-height:220px;cursor:pointer;">
                <canvas id="workloadChart"></canvas>
            </div>
            <p style="font-size:11px;color:#C4B5FD;margin:8px 0 0;text-align:center;">
                <i class="fas fa-hand-pointer" style="font-size:10px;margin-right:4px;"></i>Click any bar to view tasks
            </p>
        @else
            <div style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;flex:1;min-height:220px;color:#D1D5DB;">
                <i class="fas fa-chart-bar" style="font-size:28px;margin-bottom:8px;"></i>
                <p style="font-size:12px;margin:0;">No data yet</p>
            </div>
        @endif
    </div>
    @endif

    {{-- Tasks by Customer donut chart --}}
    @if(!in_array('dash_customers', $devHidden))
    @php
        $custColors      = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4','#F97316','#14B8A6'];
        $custTotal       = $customerTaskDist->sum('tasks_count') + $unassignedTaskCount;
        $hasAnyCust      = $customerTaskDist->isNotEmpty() || $unassignedTaskCount > 0;
        $custLegColorIdx = 0;
    @endphp

    <div class="dash-card anim-card anim-d6" style="display:flex;flex-direction:column;" data-dev-key="dash_customers" data-dev-label="Tasks by Customer">

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div>
                <h3 style="font-size:14px;font-weight:600;color:#111827;margin:0;">Tasks by Customer</h3>
                <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Current task distribution across clients</p>
            </div>
            <a href="{{ route('admin.customers.index') }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:500;">View all →</a>
        </div>

        @if(!$hasAnyCust)
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;min-height:220px;color:#D1D5DB;">
            <i class="fas fa-building" style="font-size:28px;margin-bottom:8px;"></i>
            <p style="font-size:12px;margin:0;">No customer data yet</p>
        </div>
        @else

        {{-- Donut + legend side by side --}}
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;flex:1;">

            {{-- Donut --}}
            <div style="position:relative;width:150px;height:150px;flex-shrink:0;">
                <canvas id="customerTasksChart" style="width:150px!important;height:150px!important;"></canvas>
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                    <p style="font-size:26px;font-weight:700;color:#111827;margin:0;line-height:1;">{{ $custTotal }}</p>
                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;">Total</p>
                </div>
            </div>

            {{-- Legend --}}
            <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:7px;overflow-y:auto;max-height:200px;">
                @foreach($customerTaskDist as $cust)
                @php
                    if ($cust->tasks_count > 0) {
                        $cc = $custColors[$custLegColorIdx % count($custColors)];
                        $custLegColorIdx++;
                    } else {
                        $cc = '#E5E7EB';
                    }
                    $pct = $custTotal > 0 ? round($cust->tasks_count / $custTotal * 100) : 0;
                @endphp
                <a href="{{ route('admin.customers.show', $cust->id) }}" style="text-decoration:none;display:block;">
                <div style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:8px;transition:background .12s;"
                     onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                    <span style="width:10px;height:10px;border-radius:50%;background:{{ $cc }};flex-shrink:0;display:inline-block;{{ $cust->tasks_count === 0 ? 'border:1px solid #D1D5DB;' : '' }}"></span>
                    <span style="flex:1;font-size:12px;font-weight:600;color:{{ $cust->tasks_count > 0 ? '#374151' : '#9CA3AF' }};overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $cust->name }}</span>
                    <span style="font-size:12px;font-weight:700;color:{{ $cust->tasks_count > 0 ? '#111827' : '#9CA3AF' }};flex-shrink:0;">{{ $cust->tasks_count }}</span>
                    @if($cust->tasks_count > 0)
                    <span style="font-size:10px;color:#9CA3AF;min-width:30px;text-align:right;flex-shrink:0;">{{ $pct }}%</span>
                    @else
                    <span style="font-size:10px;color:#D1D5DB;min-width:30px;text-align:right;flex-shrink:0;">—</span>
                    @endif
                </div>
                </a>
                @endforeach

                @if($unassignedTaskCount > 0)
                @php $pct = $custTotal > 0 ? round($unassignedTaskCount / $custTotal * 100) : 0; @endphp
                <div style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:8px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#E5E7EB;border:1px solid #D1D5DB;flex-shrink:0;display:inline-block;"></span>
                    <span style="flex:1;font-size:12px;font-weight:600;color:#9CA3AF;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">No customer</span>
                    <span style="font-size:12px;font-weight:700;color:#6B7280;flex-shrink:0;">{{ $unassignedTaskCount }}</span>
                    <span style="font-size:10px;color:#9CA3AF;min-width:30px;text-align:right;flex-shrink:0;">{{ $pct }}%</span>
                </div>
                @endif
            </div>
        </div>

        @endif
    </div>
    @endif

</div>
@endif

{{-- ══ EXTRA: Tasks by Priority ══ --}}
@if(in_array('dash_priority_chart', $devExtras))
<div class="dash-card anim-card" style="margin-top:16px;display:flex;gap:28px;align-items:center;flex-wrap:wrap;" data-dev-key="dash_priority_chart" data-dev-label="Tasks by Priority" data-dev-type="extra">
    <div style="flex:0 0 auto;">
        <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0 0 4px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-flag" style="color:#EF4444;font-size:13px;"></i> Tasks by Priority
        </h3>
        <p style="font-size:11px;color:#9CA3AF;margin:0 0 14px;">Distribution of tasks across priority levels</p>
        <div style="position:relative;width:160px;height:160px;">
            <canvas id="priorityChart"></canvas>
        </div>
    </div>
    <div style="flex:1;min-width:160px;">
        @php $prioItems = [['High','#EF4444',$priorityData['data'][0]],['Medium','#F59E0B',$priorityData['data'][1]],['Low','#10B981',$priorityData['data'][2]]]; @endphp
        @foreach($prioItems as [$plabel,$pcolor,$pval])
        @php $ptotal = array_sum($priorityData['data']); $ppct = $ptotal > 0 ? round($pval/$ptotal*100) : 0; @endphp
        <div style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                <div style="display:flex;align-items:center;gap:7px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:{{ $pcolor }};display:inline-block;"></span>
                    <span style="font-size:13px;font-weight:600;color:#374151;">{{ $plabel }}</span>
                </div>
                <span style="font-size:13px;font-weight:700;color:#111827;">{{ $pval }}</span>
            </div>
            <div style="height:6px;background:#F3F4F6;border-radius:3px;overflow:hidden;">
                <div style="height:100%;width:{{ $ppct }}%;background:{{ $pcolor }};border-radius:3px;transition:width .6s ease;"></div>
            </div>
        </div>
        @endforeach
        <p style="font-size:11px;color:#9CA3AF;margin:8px 0 0;">Total: {{ array_sum($priorityData['data']) }} tasks</p>
    </div>
</div>
@endif

{{-- ══ EXTRA: Team Performance ══ --}}
@if(in_array('dash_team_performance', $devExtras))
<div class="dash-card anim-card" style="margin-top:16px;" data-dev-key="dash_team_performance" data-dev-label="Team Performance" data-dev-type="extra">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
        <div>
            <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-users" style="color:#6366F1;font-size:13px;"></i> Team Performance
            </h3>
            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Completed vs in-progress tasks per team member</p>
        </div>
        <a href="{{ route('team.index') }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:600;">View Team →</a>
    </div>
    <div style="position:relative;width:100%;height:240px;">
        <canvas id="teamPerfChart"></canvas>
    </div>
</div>
@endif

{{-- ══ EXTRA: Project Progress ══ --}}
@if(in_array('dash_project_progress', $devExtras))
<div class="dash-card anim-card" style="margin-top:16px;" data-dev-key="dash_project_progress" data-dev-label="Project Progress" data-dev-type="extra">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
        <div>
            <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-diagram-project" style="color:#10B981;font-size:13px;"></i> Project Progress
            </h3>
            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Task completion percentage per project</p>
        </div>
        <a href="{{ $dashProjectsUrl }}" style="font-size:11px;color:#4F46E5;text-decoration:none;font-weight:600;">View Projects →</a>
    </div>
    <div style="display:flex;flex-direction:column;gap:12px;">
        @forelse($projectProgressData as $pp)
        @php $barColor = $pp['percent'] >= 75 ? '#10B981' : ($pp['percent'] >= 40 ? '#6366F1' : '#F59E0B'); @endphp
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <span style="font-size:13px;font-weight:600;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:60%;">{{ $pp['name'] }}</span>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <span style="font-size:11px;color:#9CA3AF;">{{ $pp['done'] }}/{{ $pp['total'] }}</span>
                    <span style="font-size:12px;font-weight:700;color:{{ $barColor }};min-width:36px;text-align:right;">{{ $pp['percent'] }}%</span>
                </div>
            </div>
            <div style="height:8px;background:#F3F4F6;border-radius:4px;overflow:hidden;">
                <div style="height:100%;width:{{ $pp['percent'] }}%;background:{{ $barColor }};border-radius:4px;transition:width .7s ease;"></div>
            </div>
        </div>
        @empty
        <p style="font-size:12px;color:#9CA3AF;text-align:center;margin:0;">No projects yet</p>
        @endforelse
    </div>
</div>
@endif

{{-- ══ Recent Tasks ══ --}}
@if(!in_array('dash_recent_tasks', $devHidden))
<div x-data="{
        view: localStorage.getItem('dash_tasks_view') || 'table',
        setView(v) { this.view = v; localStorage.setItem('dash_tasks_view', v); }
     }" style="margin-top:16px;" data-dev-key="dash_recent_tasks" data-dev-label="Recent Tasks">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
        <div>
            <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-list-check" style="color:#6366F1;font-size:13px;"></i> Recent Tasks
            </h3>
            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Last 12 updated tasks across all projects</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            {{-- Toggle --}}
            <div style="display:flex;gap:2px;background:#F3F4F6;border-radius:12px;padding:4px;">
                <button @click="setView('table')" :style="view==='table'
                            ? 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);'
                            : 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;'" style="display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);">
                    <i class="fa fa-table-list" style="font-size:11px;"></i> Table
                </button>
                <button @click="setView('cards')" :style="view==='cards'
                            ? 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);'
                            : 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;'" style="display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:transparent;color:#6B7280;">
                    <i class="fa fa-grip" style="font-size:11px;"></i> Cards
                </button>
            </div>
            <a href="{{ route('admin.tasks.index') }}"
               style="font-size:12px;font-weight:600;color:#4F46E5;text-decoration:none;white-space:nowrap;">View all →</a>
        </div>
    </div>

    @php
    $statusMeta = [
        'draft'              => ['label'=>'Draft',       'bg'=>'#F3F4F6','color'=>'#6B7280'],
        'assigned'           => ['label'=>'Assigned',    'bg'=>'#EEF2FF','color'=>'#4F46E5'],
        'viewed'             => ['label'=>'Viewed',      'bg'=>'#E0F2FE','color'=>'#0369A1'],
        'in_progress'        => ['label'=>'In Progress', 'bg'=>'#FEF3C7','color'=>'#D97706'],
        'submitted'          => ['label'=>'In Review',   'bg'=>'#EDE9FE','color'=>'#7C3AED'],
        'revision_requested' => ['label'=>'Revision',    'bg'=>'#FEE2E2','color'=>'#DC2626'],
        'approved'           => ['label'=>'Approved',    'bg'=>'#D1FAE5','color'=>'#059669'],
        'delivered'          => ['label'=>'Delivered',   'bg'=>'#ECFDF5','color'=>'#047857'],
        'archived'           => ['label'=>'Archived',    'bg'=>'#F3F4F6','color'=>'#6B7280'],
    ];
    $priorityMeta = [
        'high'   => ['label'=>'High',   'bg'=>'#FEE2E2','color'=>'#DC2626'],
        'medium' => ['label'=>'Med',    'bg'=>'#FEF3C7','color'=>'#D97706'],
        'low'    => ['label'=>'Low',    'bg'=>'#D1FAE5','color'=>'#059669'],
    ];
    @endphp

    @if($recentTasks->isEmpty())
    <div class="dash-card" style="text-align:center;padding:40px;color:#9CA3AF;">
        <i class="fas fa-list-check" style="font-size:28px;margin-bottom:10px;display:block;"></i>
        <p style="margin:0;font-size:13px;">No tasks yet.</p>
    </div>
    @else

    {{-- TABLE VIEW --}}
    <div x-show="view==='table'">
        <div class="dash-card" style="padding:0;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1.5px solid #F3F4F6;background:#FAFAFA;">
                        <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Task</th>
                        <th style="padding:11px 14px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Project</th>
                        <th style="padding:11px 14px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Assignee</th>
                        <th style="padding:11px 14px;text-align:center;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Status</th>
                        <th style="padding:11px 14px;text-align:center;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Priority</th>
                        <th style="padding:11px 14px;text-align:center;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Deadline</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTasks as $task)
                    @php
                        $sm = $statusMeta[$task->status] ?? ['label'=>ucfirst($task->status),'bg'=>'#F3F4F6','color'=>'#6B7280'];
                        $pm = $priorityMeta[$task->priority ?? 'medium'] ?? null;
                        $isOverdue = $task->deadline && $task->deadline->isPast() && !in_array($task->status, ['approved','delivered','archived']);
                    @endphp
                    <tr style="border-bottom:1px solid #F9FAFB;transition:background .1s;"
                        onmouseover="this.style.background='#FAFBFF'" onmouseout="this.style.background=''">
                        <td style="padding:11px 16px;max-width:220px;">
                            <a href="{{ route('admin.tasks.show', $task) }}"
                               style="font-size:13px;font-weight:600;color:#111827;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                               onmouseover="this.style.color='#4F46E5'" onmouseout="this.style.color='#111827'">
                                {{ $task->title }}
                            </a>
                        </td>
                        <td style="padding:11px 14px;">
                            <span style="font-size:12px;color:#6B7280;">{{ $task->project->name ?? '—' }}</span>
                        </td>
                        <td style="padding:11px 14px;">
                            @if($task->assignee)
                            <div style="display:flex;align-items:center;gap:7px;">
                                <div style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0;">
                                    {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                                </div>
                                <span style="font-size:12px;color:#374151;">{{ $task->assignee->name }}</span>
                            </div>
                            @else
                            <span style="font-size:12px;color:#D1D5DB;">Unassigned</span>
                            @endif
                        </td>
                        <td style="padding:11px 14px;text-align:center;">
                            <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $sm['bg'] }};color:{{ $sm['color'] }};">
                                {{ $sm['label'] }}
                            </span>
                        </td>
                        <td style="padding:11px 14px;text-align:center;">
                            @if($pm)
                            <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $pm['bg'] }};color:{{ $pm['color'] }};">
                                {{ $pm['label'] }}
                            </span>
                            @else
                            <span style="color:#D1D5DB;font-size:12px;">—</span>
                            @endif
                        </td>
                        <td style="padding:11px 14px;text-align:center;">
                            @if($task->deadline)
                            <span style="font-size:12px;font-weight:600;color:{{ $isOverdue ? '#EF4444' : '#374151' }};">
                                {{ $isOverdue ? '⚠ ' : '' }}{{ $task->deadline->format('M d, Y') }}
                            </span>
                            @else
                            <span style="color:#D1D5DB;font-size:12px;">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- CARD VIEW --}}
    <div x-show="view==='cards'">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;">
            @foreach($recentTasks as $task)
            @php
                $sm = $statusMeta[$task->status] ?? ['label'=>ucfirst($task->status),'bg'=>'#F3F4F6','color'=>'#6B7280'];
                $pm = $priorityMeta[$task->priority ?? 'medium'] ?? null;
                $isOverdue = $task->deadline && $task->deadline->isPast() && !in_array($task->status, ['approved','delivered','archived']);
            @endphp
            <a href="{{ route('admin.tasks.show', $task) }}" style="text-decoration:none;display:block;">
                <div class="dash-card" style="padding:16px;cursor:pointer;transition:box-shadow .15s,transform .15s;"
                     onmouseover="this.style.boxShadow='0 6px 24px rgba(0,0,0,.1)';this.style.transform='translateY(-2px)'"
                     onmouseout="this.style.boxShadow='';this.style.transform=''">

                    {{-- Top: status + priority --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $sm['bg'] }};color:{{ $sm['color'] }};">
                            {{ $sm['label'] }}
                        </span>
                        @if($pm)
                        <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600;background:{{ $pm['bg'] }};color:{{ $pm['color'] }};">
                            {{ $pm['label'] }}
                        </span>
                        @endif
                    </div>

                    {{-- Title --}}
                    <p style="font-size:13px;font-weight:700;color:#111827;margin:0 0 8px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                        {{ $task->title }}
                    </p>

                    {{-- Project --}}
                    @if($task->project)
                    <p style="font-size:11px;color:#6B7280;margin:0 0 10px;display:flex;align-items:center;gap:4px;">
                        <i class="fas fa-diagram-project" style="font-size:10px;color:#9CA3AF;"></i>
                        {{ $task->project->name }}
                    </p>
                    @endif

                    {{-- Footer: assignee + deadline --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;border-top:1px solid #F3F4F6;padding-top:10px;">
                        @if($task->assignee)
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;color:#fff;flex-shrink:0;">
                                {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                            </div>
                            <span style="font-size:11px;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px;">{{ $task->assignee->name }}</span>
                        </div>
                        @else
                        <span style="font-size:11px;color:#D1D5DB;">Unassigned</span>
                        @endif

                        @if($task->deadline)
                        <span style="font-size:11px;font-weight:600;color:{{ $isOverdue ? '#EF4444' : '#9CA3AF' }};">
                            {{ $isOverdue ? '⚠ ' : '' }}{{ $task->deadline->format('M d') }}
                        </span>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>

    @endif
</div>
@endif

{{-- Chart Tasks Modal --}}
<div id="chartTasksModal" style="display:none;position:fixed;inset:0;z-index:9999;overflow-y:auto;">
    {{-- Backdrop --}}
    <div id="chartTasksBackdrop" onclick="closeChartTasksModal()" style="position:fixed;inset:0;background:rgba(15,23,42,0.55);backdrop-filter:blur(3px);"></div>
    {{-- Panel --}}
    <div style="position:relative;z-index:1;margin:48px auto 48px;max-width:600px;width:calc(100% - 32px);">
        <div style="background:#fff;border-radius:20px;box-shadow:0 24px 64px rgba(15,23,42,0.18);overflow:hidden;">

            {{-- Header --}}
            <div style="background:linear-gradient(135deg,#6366F1,#8B5CF6);padding:20px 24px 18px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                <div>
                    <p style="font-size:11px;font-weight:600;color:rgba(255,255,255,0.7);text-transform:uppercase;letter-spacing:.07em;margin:0 0 4px;">Working Hours — Task Activity</p>
                    <h2 id="chartTasksDate" style="font-size:17px;font-weight:700;color:#fff;margin:0;"></h2>
                </div>
                <button onclick="closeChartTasksModal()" style="flex-shrink:0;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.15);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Loading state --}}
            <div id="chartTasksLoading" style="display:none;padding:48px 24px;text-align:center;">
                <div style="width:40px;height:40px;border:3px solid #E0E7FF;border-top-color:#6366F1;border-radius:50%;animation:chartModalSpin 0.7s linear infinite;margin:0 auto 14px;"></div>
                <p style="font-size:13px;color:#9CA3AF;margin:0;">Loading tasks…</p>
            </div>

            {{-- Empty state --}}
            <div id="chartTasksEmpty" style="display:none;padding:48px 24px;text-align:center;">
                <div style="width:56px;height:56px;border-radius:16px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                    <i class="fas fa-inbox" style="font-size:24px;color:#D1D5DB;"></i>
                </div>
                <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">No tasks for this period</p>
                <p style="font-size:12px;color:#9CA3AF;margin:0;">No tasks were created or completed here.</p>
            </div>

            {{-- Task list --}}
            <div id="chartTasksList" style="display:none;padding:16px 20px;max-height:480px;overflow-y:auto;">
                <p id="chartTasksCount" style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin:0 0 12px;padding:0 4px;"></p>
                <div id="chartTasksItems"></div>
            </div>

        </div>
    </div>
</div>
<style>
@keyframes chartModalSpin { to { transform: rotate(360deg); } }
</style>

{{-- Social Posts Modal --}}
<div id="socialPostsModal" style="display:none;position:fixed;inset:0;z-index:9999;overflow-y:auto;">
    <div onclick="closeSocialPostsModal()" style="position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(3px);"></div>
    <div style="position:relative;z-index:1;margin:48px auto 48px;max-width:560px;width:calc(100% - 32px);">
        <div style="background:#fff;border-radius:20px;box-shadow:0 24px 64px rgba(15,23,42,.18);overflow:hidden;">

            {{-- Header --}}
            <div id="socialModalHeader" style="padding:20px 24px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div id="socialModalIcon" style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;background:#F3F4F6;flex-shrink:0;"></div>
                    <div>
                        <p style="font-size:11px;font-weight:600;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.07em;margin:0 0 2px;" id="socialModalSub">Social Media Posts</p>
                        <h2 id="socialModalTitle" style="font-size:16px;font-weight:800;color:#fff;margin:0;"></h2>
                    </div>
                </div>
                <button onclick="closeSocialPostsModal()" style="flex-shrink:0;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.18);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;" onmouseover="this.style.background='rgba(255,255,255,.28)'" onmouseout="this.style.background='rgba(255,255,255,.18)'">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Loading --}}
            <div id="socialModalLoading" style="padding:48px 24px;text-align:center;">
                <div style="width:40px;height:40px;border:3px solid #E0E7FF;border-top-color:#6366F1;border-radius:50%;animation:chartModalSpin .7s linear infinite;margin:0 auto 14px;"></div>
                <p style="font-size:13px;color:#9CA3AF;margin:0;">Loading posts…</p>
            </div>

            {{-- Empty --}}
            <div id="socialModalEmpty" style="display:none;padding:48px 24px;text-align:center;">
                <div style="width:56px;height:56px;border-radius:16px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                    <i class="fas fa-share-nodes" style="font-size:24px;color:#D1D5DB;"></i>
                </div>
                <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">No posts found</p>
                <p style="font-size:12px;color:#9CA3AF;margin:0;">No social media posts have been recorded yet.</p>
            </div>

            {{-- List --}}
            <div id="socialModalList" style="display:none;max-height:480px;overflow-y:auto;">
                <p id="socialModalCount" style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin:0;padding:0 24px 10px;"></p>
                <div id="socialModalItems" style="padding:0 16px 20px;display:flex;flex-direction:column;gap:8px;"></div>
            </div>

        </div>
    </div>
</div>

{{-- Workload Tasks Modal --}}
<div id="workloadModal" style="display:none;position:fixed;inset:0;z-index:9999;overflow-y:auto;">
    <div id="workloadModalBackdrop" onclick="closeWorkloadModal()" style="position:fixed;inset:0;background:rgba(15,23,42,0.55);backdrop-filter:blur(3px);"></div>
    <div style="position:relative;z-index:1;margin:48px auto 48px;max-width:600px;width:calc(100% - 32px);">
        <div style="background:#fff;border-radius:20px;box-shadow:0 24px 64px rgba(15,23,42,0.18);overflow:hidden;">

            {{-- Header --}}
            <div id="workloadModalHeader" style="background:linear-gradient(135deg,#10B981,#059669);padding:20px 24px 18px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                <div style="display:flex;align-items:center;gap:14px;">
                    <div id="workloadModalAvatar" style="width:48px;height:48px;border-radius:14px;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;flex-shrink:0;"></div>
                    <div>
                        <p style="font-size:11px;font-weight:600;color:rgba(255,255,255,0.75);text-transform:uppercase;letter-spacing:.07em;margin:0 0 3px;">Task Workload</p>
                        <h2 id="workloadModalName" style="font-size:17px;font-weight:700;color:#fff;margin:0;"></h2>
                        <p id="workloadModalSub" style="font-size:12px;color:rgba(255,255,255,0.75);margin:3px 0 0;"></p>
                    </div>
                </div>
                <button onclick="closeWorkloadModal()" style="flex-shrink:0;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.15);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Loading --}}
            <div id="workloadModalLoading" style="display:none;padding:48px 24px;text-align:center;">
                <div style="width:40px;height:40px;border:3px solid #D1FAE5;border-top-color:#10B981;border-radius:50%;animation:chartModalSpin 0.7s linear infinite;margin:0 auto 14px;"></div>
                <p style="font-size:13px;color:#9CA3AF;margin:0;">Loading tasks…</p>
            </div>

            {{-- Empty --}}
            <div id="workloadModalEmpty" style="display:none;padding:48px 24px;text-align:center;">
                <div style="width:56px;height:56px;border-radius:16px;background:#F0FDF4;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                    <i class="fas fa-check-circle" style="font-size:24px;color:#10B981;"></i>
                </div>
                <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">All clear!</p>
                <p style="font-size:12px;color:#9CA3AF;margin:0;">This person has no open tasks right now.</p>
            </div>

            {{-- Task list --}}
            <div id="workloadModalList" style="display:none;padding:16px 20px;max-height:480px;overflow-y:auto;">
                <p id="workloadModalCount" style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin:0 0 12px;padding:0 4px;"></p>
                <div id="workloadModalItems"></div>
            </div>

        </div>
    </div>
</div>

{{-- Analytics Tasks Modal --}}
<div id="analyticsModal" style="display:none;position:fixed;inset:0;z-index:9999;overflow-y:auto;">
    <div id="analyticsModalBackdrop" onclick="closeAnalyticsModal()" style="position:fixed;inset:0;background:rgba(15,23,42,0.55);backdrop-filter:blur(3px);"></div>
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:center;min-height:100%;padding:40px 16px;">
        <div style="background:#fff;border-radius:20px;width:100%;max-width:640px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,0.22);animation:amSlideUp .22s ease;">
            <div id="analyticsModalHeader" style="padding:20px 24px 18px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div id="analyticsModalIcon" style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0;"></div>
                    <div>
                        <h2 id="analyticsModalTitle" style="font-size:17px;font-weight:700;color:#fff;margin:0;"></h2>
                        <p id="analyticsModalSub" style="font-size:12px;color:rgba(255,255,255,0.75);margin:3px 0 0;">Task list</p>
                    </div>
                </div>
                <button onclick="closeAnalyticsModal()" style="background:rgba(255,255,255,0.15);border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-times" style="color:#fff;font-size:13px;"></i>
                </button>
            </div>

            <div id="analyticsModalLoading" style="display:none;padding:48px 24px;text-align:center;">
                <div style="width:32px;height:32px;border:3px solid #E5E7EB;border-top-color:#6366F1;border-radius:50%;animation:amSpin 0.7s linear infinite;display:inline-block;"></div>
                <p style="margin:12px 0 0;font-size:13px;color:#9CA3AF;">Loading tasks…</p>
            </div>

            <div id="analyticsModalEmpty" style="display:none;padding:48px 24px;text-align:center;">
                <i class="fas fa-check-circle" style="font-size:36px;color:#D1FAE5;"></i>
                <p style="margin:10px 0 0;font-size:14px;color:#6B7280;">No tasks in this category.</p>
            </div>

            <div id="analyticsModalList" style="display:none;padding:16px 20px 8px;">
                <p id="analyticsModalCount" style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin:0 0 12px;padding:0 4px;"></p>
                <div id="analyticsModalItems" style="max-height:420px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;"></div>
                <div id="analyticsModalFooter" style="padding:16px 4px 8px;border-top:1px solid #F3F4F6;margin-top:12px;">
                    <a id="analyticsModalViewAll" href="#" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#6366F1;text-decoration:none;">
                        View all tasks <i class="fas fa-arrow-right" style="font-size:10px;"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
@keyframes amSlideUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
@keyframes amSpin    { to { transform:rotate(360deg); } }
</style>

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
var _whCurrentPeriod = 'week';
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
            pointRadius: 5,
            pointHoverRadius: 7,
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
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return ' ' + ctx.parsed.y + ' task' + (ctx.parsed.y !== 1 ? 's' : ''); } } } },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: { grid: { color: '#F3F4F6' }, border: { display: false }, beginAtZero: true, ticks: { stepSize: 1, maxTicksLimit: 6 } }
        },
        onClick: function(evt, elements) {
            if (!elements || !elements.length) return;
            var idx = elements[0].index;
            openChartTasksModal(_whCurrentPeriod, idx);
        },
        onHover: function(evt, elements) {
            evt.native.target.style.cursor = elements && elements.length ? 'pointer' : 'default';
        }
    }
});

// Working Hours period switcher — shared core
var _whLoading = false;
var _whSubtitles = {
    'today':      'Task activity today (hourly)',
    'week':       'Task activity this week (7 days)',
    'last_week':  'Task activity last week',
    'month':      'Task activity this month (30 days)',
    'last_month': 'Task activity last month',
    'year':       'Task activity this year (12 months)',
};

function loadWhData(period) {
    if (_whLoading) return;
    _whLoading = true;
    _whCurrentPeriod = period;
fetch('{{ route('admin.dashboard.working-hours') }}?period=' + period, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            chartWorkingHours.data.labels = d.labels;
            chartWorkingHours.data.datasets[0].data = d.data;
            chartWorkingHours.update();
            var subEl = document.getElementById('wh-subtitle');
            if (subEl && _whSubtitles[period]) subEl.textContent = _whSubtitles[period];
        })
        .catch(function() {})
        .finally(function() { _whLoading = false; });
}

// Chart Tasks Modal
function openChartTasksModal(period, index) {
    var modal    = document.getElementById('chartTasksModal');
    var loading  = document.getElementById('chartTasksLoading');
    var empty    = document.getElementById('chartTasksEmpty');
    var list     = document.getElementById('chartTasksList');
    var dateEl   = document.getElementById('chartTasksDate');
    var countEl  = document.getElementById('chartTasksCount');
    var itemsEl  = document.getElementById('chartTasksItems');

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    loading.style.display = 'block';
    empty.style.display   = 'none';
    list.style.display    = 'none';
    dateEl.textContent    = '…';

fetch('{{ route('admin.dashboard.chart-tasks') }}?period=' + encodeURIComponent(period) + '&index=' + index, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            loading.style.display = 'none';
            dateEl.textContent = d.label || '';

            if (!d.tasks || !d.tasks.length) {
                empty.style.display = 'block';
                return;
            }

            var statusMeta = {
                draft:              { label: 'Draft',       bg: '#F3F4F6', color: '#6B7280' },
                assigned:           { label: 'Assigned',    bg: '#EEF2FF', color: '#4F46E5' },
                viewed:             { label: 'Viewed',      bg: '#F0F9FF', color: '#0369A1' },
                in_progress:        { label: 'In Progress', bg: '#FFFBEB', color: '#D97706' },
                submitted:          { label: 'In Review',   bg: '#F5F3FF', color: '#7C3AED' },
                revision_requested: { label: 'Revision',    bg: '#FFF7ED', color: '#C2410C' },
                approved:           { label: 'Approved',    bg: '#F0FDF4', color: '#15803D' },
                delivered:          { label: 'Delivered',   bg: '#ECFDF5', color: '#065F46' },
                archived:           { label: 'Archived',    bg: '#F3F4F6', color: '#6B7280' },
            };
            var priorityMeta = {
                high:   { label: 'High',   color: '#EF4444' },
                medium: { label: 'Med',    color: '#F59E0B' },
                low:    { label: 'Low',    color: '#10B981' },
            };

            countEl.textContent = d.tasks.length + ' task' + (d.tasks.length !== 1 ? 's' : '');
            itemsEl.innerHTML   = '';

            d.tasks.forEach(function(task) {
                var sm = statusMeta[task.status] || { label: task.status, bg: '#F3F4F6', color: '#6B7280' };
                var pm = priorityMeta[task.priority] || null;
                var initials = task.assignee ? task.assignee.split(' ').map(function(w){ return w[0]; }).join('').substring(0,2).toUpperCase() : '';

                var row = document.createElement('a');
                row.href = task.url;
                row.style.cssText = 'display:block;padding:12px 14px;border-radius:12px;border:1px solid #F3F4F6;margin-bottom:8px;text-decoration:none;transition:all .15s;background:#fff;';
                row.onmouseover = function(){ this.style.background='#F5F3FF'; this.style.borderColor='#C4B5FD'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(99,102,241,0.1)'; };
                row.onmouseout  = function(){ this.style.background='#fff'; this.style.borderColor='#F3F4F6'; this.style.transform=''; this.style.boxShadow=''; };

                var html = '<div style="display:flex;align-items:flex-start;gap:10px;">';

                // Status dot
                html += '<div style="width:8px;height:8px;border-radius:50%;background:' + sm.color + ';margin-top:5px;flex-shrink:0;"></div>';

                // Main content
                html += '<div style="flex:1;min-width:0;">';
                html += '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">';
                html += '<p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">' + _escHtml(task.title) + '</p>';
                html += '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:' + sm.bg + ';color:' + sm.color + ';white-space:nowrap;">' + sm.label + '</span>';
                html += '</div>';

                // Meta row
                html += '<div style="display:flex;align-items:center;gap:10px;margin-top:6px;flex-wrap:wrap;">';
                if (task.project) {
                    html += '<span style="font-size:11px;color:#9CA3AF;display:flex;align-items:center;gap:3px;"><i class="fas fa-folder" style="font-size:9px;color:#C4B5FD;"></i>' + _escHtml(task.project) + '</span>';
                }
                if (task.assignee) {
                    html += '<span style="font-size:11px;color:#9CA3AF;display:flex;align-items:center;gap:4px;"><span style="width:16px;height:16px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:inline-flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;color:#fff;">' + initials + '</span>' + _escHtml(task.assignee) + '</span>';
                }
                if (task.deadline) {
                    html += '<span style="font-size:11px;color:#9CA3AF;display:flex;align-items:center;gap:3px;"><i class="fas fa-calendar-alt" style="font-size:9px;"></i>' + _escHtml(task.deadline) + '</span>';
                }
                if (pm) {
                    html += '<span style="font-size:10px;font-weight:600;color:' + pm.color + ';">' + pm.label + '</span>';
                }
                html += '</div>';

                html += '</div>';
                html += '<i class="fas fa-chevron-right" style="font-size:11px;color:#C4B5FD;flex-shrink:0;margin-top:2px;"></i>';
                html += '</div>';

                row.innerHTML = html;
                itemsEl.appendChild(row);
            });

            list.style.display = 'block';
        })
        .catch(function() {
            loading.style.display = 'none';
            empty.style.display   = 'block';
        });
}

function closeChartTasksModal() {
    document.getElementById('chartTasksModal').style.display = 'none';
    document.body.style.overflow = '';
}

// ── Workload Modal ─────────────────────────────────────────────────────────
var _wlColors = [
    'linear-gradient(135deg,#6366F1,#8B5CF6)',
    'linear-gradient(135deg,#10B981,#059669)',
    'linear-gradient(135deg,#F59E0B,#D97706)',
    'linear-gradient(135deg,#EF4444,#DC2626)',
    'linear-gradient(135deg,#8B5CF6,#7C3AED)',
    'linear-gradient(135deg,#3B82F6,#2563EB)',
];

function openWorkloadModal(index) {
    var modal    = document.getElementById('workloadModal');
    var header   = document.getElementById('workloadModalHeader');
    var avatar   = document.getElementById('workloadModalAvatar');
    var nameEl   = document.getElementById('workloadModalName');
    var subEl    = document.getElementById('workloadModalSub');
    var loading  = document.getElementById('workloadModalLoading');
    var empty    = document.getElementById('workloadModalEmpty');
    var list     = document.getElementById('workloadModalList');
    var countEl  = document.getElementById('workloadModalCount');
    var itemsEl  = document.getElementById('workloadModalItems');

    // Set colour based on bar index
    var grad = _wlColors[index % _wlColors.length];
    header.style.background = grad;

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    loading.style.display = 'block';
    empty.style.display   = 'none';
    list.style.display    = 'none';
    avatar.textContent    = '…';
    nameEl.textContent    = 'Loading…';
    subEl.textContent     = '';

fetch('{{ route('admin.dashboard.workload-tasks') }}?index=' + index, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            loading.style.display = 'none';

            if (!d.user) { empty.style.display = 'block'; return; }

            avatar.textContent  = d.user.initials;
            nameEl.textContent  = d.user.name;
            subEl.textContent   = d.user.open_tasks + ' open task' + (d.user.open_tasks !== 1 ? 's' : '');

            if (!d.tasks || !d.tasks.length) { empty.style.display = 'block'; return; }

            var statusMeta = {
                draft:              { label: 'Draft',       bg: '#F3F4F6', color: '#6B7280' },
                assigned:           { label: 'Assigned',    bg: '#EEF2FF', color: '#4F46E5' },
                viewed:             { label: 'Viewed',      bg: '#F0F9FF', color: '#0369A1' },
                in_progress:        { label: 'In Progress', bg: '#FFFBEB', color: '#D97706' },
                submitted:          { label: 'In Review',   bg: '#F5F3FF', color: '#7C3AED' },
                revision_requested: { label: 'Revision',    bg: '#FFF7ED', color: '#C2410C' },
                approved:           { label: 'Approved',    bg: '#F0FDF4', color: '#15803D' },
                delivered:          { label: 'Delivered',   bg: '#ECFDF5', color: '#065F46' },
                archived:           { label: 'Archived',    bg: '#F3F4F6', color: '#6B7280' },
            };
            var priorityMeta = {
                high:   { label: 'High',   color: '#EF4444' },
                medium: { label: 'Med',    color: '#F59E0B' },
                low:    { label: 'Low',    color: '#10B981' },
            };

            countEl.textContent = d.tasks.length + ' task' + (d.tasks.length !== 1 ? 's' : '');
            itemsEl.innerHTML   = '';

            d.tasks.forEach(function(task) {
                var sm = statusMeta[task.status] || { label: task.status, bg: '#F3F4F6', color: '#6B7280' };
                var pm = priorityMeta[task.priority] || null;

                var row = document.createElement('a');
                row.href = task.url;
                row.style.cssText = 'display:block;padding:12px 14px;border-radius:12px;border:1px solid #F3F4F6;margin-bottom:8px;text-decoration:none;background:#fff;transition:all .15s;';
                row.onmouseover = function(){ this.style.background='#F0FDF4'; this.style.borderColor='#6EE7B7'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(16,185,129,0.1)'; };
                row.onmouseout  = function(){ this.style.background='#fff'; this.style.borderColor='#F3F4F6'; this.style.transform=''; this.style.boxShadow=''; };

                var html = '<div style="display:flex;align-items:flex-start;gap:10px;">';
                html += '<div style="width:8px;height:8px;border-radius:50%;background:' + sm.color + ';margin-top:5px;flex-shrink:0;"></div>';
                html += '<div style="flex:1;min-width:0;">';

                // Title row
                html += '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">';
                html += '<p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">' + _escHtml(task.title) + '</p>';
                html += '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:' + sm.bg + ';color:' + sm.color + ';white-space:nowrap;">' + sm.label + '</span>';
                html += '</div>';

                // Meta
                html += '<div style="display:flex;align-items:center;gap:10px;margin-top:6px;flex-wrap:wrap;">';
                if (task.project) {
                    html += '<span style="font-size:11px;color:#9CA3AF;display:flex;align-items:center;gap:3px;"><i class="fas fa-folder" style="font-size:9px;color:#6EE7B7;"></i>' + _escHtml(task.project) + '</span>';
                }
                if (task.deadline) {
                    html += '<span style="font-size:11px;color:#9CA3AF;display:flex;align-items:center;gap:3px;"><i class="fas fa-calendar-alt" style="font-size:9px;"></i>' + _escHtml(task.deadline) + '</span>';
                }
                if (pm) {
                    html += '<span style="font-size:10px;font-weight:600;color:' + pm.color + ';">' + pm.label + '</span>';
                }
                html += '</div>';

                html += '</div>';
                html += '<i class="fas fa-chevron-right" style="font-size:11px;color:#6EE7B7;flex-shrink:0;margin-top:2px;"></i>';
                html += '</div>';

                row.innerHTML = html;
                itemsEl.appendChild(row);
            });

            list.style.display = 'block';
        })
        .catch(function() {
            loading.style.display = 'none';
            empty.style.display   = 'block';
        });
}

function closeWorkloadModal() {
    document.getElementById('workloadModal').style.display = 'none';
    document.body.style.overflow = '';
}

// ── Social Posts Modal ─────────────────────────────────────────────────────
var _spPlatformMeta = {
    facebook:  { icon: 'fa-facebook',   color: '#1877F2', grad: 'linear-gradient(135deg,#1877F2,#0C5DC7)' },
    instagram: { icon: 'fa-instagram',  color: '#E1306C', grad: 'linear-gradient(135deg,#F58529,#DD2A7B,#8134AF)' },
    twitter:   { icon: 'fa-x-twitter',  color: '#000000', grad: 'linear-gradient(135deg,#1DA1F2,#0D8FDB)' },
    linkedin:  { icon: 'fa-linkedin',   color: '#0A66C2', grad: 'linear-gradient(135deg,#0A66C2,#004182)' },
    tiktok:    { icon: 'fa-tiktok',     color: '#010101', grad: 'linear-gradient(135deg,#010101,#69C9D0)' },
    youtube:   { icon: 'fa-youtube',    color: '#FF0000', grad: 'linear-gradient(135deg,#FF0000,#CC0000)' },
    snapchat:  { icon: 'fa-snapchat',   color: '#F7CA00', grad: 'linear-gradient(135deg,#F7CA00,#C9A600)' },
    other:     { icon: 'fa-share-nodes',color: '#6366F1', grad: 'linear-gradient(135deg,#6366F1,#8B5CF6)' },
};

function openSocialPostsModal(platform) {
    var modal    = document.getElementById('socialPostsModal');
    var header   = document.getElementById('socialModalHeader');
    var iconEl   = document.getElementById('socialModalIcon');
    var titleEl  = document.getElementById('socialModalTitle');
    var subEl    = document.getElementById('socialModalSub');
    var loading  = document.getElementById('socialModalLoading');
    var empty    = document.getElementById('socialModalEmpty');
    var list     = document.getElementById('socialModalList');

    var meta = _spPlatformMeta[platform] || _spPlatformMeta['other'];
    header.style.background = meta.grad;
    iconEl.innerHTML = '<i class="fab ' + meta.icon + '" style="color:#fff;font-size:20px;"></i>';
    iconEl.style.background = 'rgba(255,255,255,.2)';
    titleEl.textContent = platform ? (platform.charAt(0).toUpperCase() + platform.slice(1)) : 'All Platforms';
    subEl.textContent   = 'Social Media Posts';

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    loading.style.display = 'block';
    empty.style.display   = 'none';
    list.style.display    = 'none';

fetch('{{ route('admin.dashboard.social-posts') }}?platform=' + encodeURIComponent(platform), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } })
        .then(function(r){ return r.json(); })
        .then(function(d){ renderSocialPosts(d.posts, meta); })
        .catch(function(){ loading.style.display = 'none'; empty.style.display = 'block'; });
}

function renderSocialPosts(posts, meta) {
    var loading = document.getElementById('socialModalLoading');
    var empty   = document.getElementById('socialModalEmpty');
    var list    = document.getElementById('socialModalList');
    var countEl = document.getElementById('socialModalCount');
    var itemsEl = document.getElementById('socialModalItems');

    loading.style.display = 'none';

    if (!posts || !posts.length) {
        empty.style.display = 'block';
        return;
    }

    countEl.textContent = posts.length + ' post' + (posts.length !== 1 ? 's' : '');
    itemsEl.innerHTML   = '';

    posts.forEach(function(p) {
        var pm = _spPlatformMeta[p.platform] || _spPlatformMeta['other'];

        var card = document.createElement('div');
        card.style.cssText = 'background:#fff;border:1px solid #F3F4F6;border-radius:14px;overflow:hidden;transition:all .15s;';

        var hasUrl = !!p.postUrl;
        var html = '';

        if (hasUrl) {
            html += '<a href="' + _escHtml(p.postUrl) + '" target="_blank" rel="noopener" style="display:block;text-decoration:none;padding:14px 16px;" onmouseover="this.parentNode.style.borderColor=\'#C7D2FE\';this.parentNode.style.boxShadow=\'0 4px 14px rgba(99,102,241,.1)\';this.parentNode.style.transform=\'translateY(-1px)\'" onmouseout="this.parentNode.style.borderColor=\'#F3F4F6\';this.parentNode.style.boxShadow=\'\';this.parentNode.style.transform=\'\'">';
        } else {
            html += '<div style="padding:14px 16px;">';
        }

        html += '<div style="display:flex;align-items:flex-start;gap:12px;">';
        // Platform icon circle
        html += '<div style="width:36px;height:36px;border-radius:10px;background:' + (hasUrl ? pm.grad : '#F3F4F6') + ';display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
        html += '<i class="fab ' + pm.icon + '" style="font-size:16px;color:' + (hasUrl ? '#fff' : pm.color) + ';"></i>';
        html += '</div>';

        html += '<div style="flex:1;min-width:0;">';
        // Task name
        if (p.task) {
            html += '<p style="font-size:13px;font-weight:700;color:#111827;margin:0 0 3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + _escHtml(p.task) + '</p>';
        }
        // Note
        if (p.note) {
            html += '<p style="font-size:12px;color:#6B7280;margin:0 0 6px;line-height:1.5;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + _escHtml(p.note) + '</p>';
        }
        // Meta row
        html += '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">';
        if (p.postedBy) html += '<span style="font-size:10px;color:#9CA3AF;display:flex;align-items:center;gap:3px;"><i class="fas fa-user" style="font-size:8px;"></i>' + _escHtml(p.postedBy) + '</span>';
        html += '<span style="font-size:10px;color:#D1D5DB;">·</span>';
        html += '<span style="font-size:10px;color:#9CA3AF;" title="' + _escHtml(p.postedAt) + '">' + _escHtml(p.diffHumans) + '</span>';
        if (hasUrl) {
            html += '<span style="margin-left:auto;font-size:10px;font-weight:600;color:#6366F1;display:flex;align-items:center;gap:3px;"><i class="fas fa-arrow-up-right-from-square" style="font-size:8px;"></i> Open post</span>';
        }
        html += '</div>';
        html += '</div></div>';

        html += hasUrl ? '</a>' : '</div>';
        card.innerHTML = html;
        itemsEl.appendChild(card);
    });

    list.style.display = 'block';
}

function closeSocialPostsModal() {
    document.getElementById('socialPostsModal').style.display = 'none';
    document.body.style.overflow = '';
}

// ── Analytics Tasks Modal ───────────────────────────────────────────────
var _amUrl = '{{ route('admin.dashboard.analytics-tasks') }}';
var _amCurrentFilter = null;

function openAnalyticsModal(filter, label, color, bg, icon, viewAllUrl) {
    _amCurrentFilter = filter;
    var modal   = document.getElementById('analyticsModal');
    var header  = document.getElementById('analyticsModalHeader');
    var iconEl  = document.getElementById('analyticsModalIcon');
    var titleEl = document.getElementById('analyticsModalTitle');
    var loading = document.getElementById('analyticsModalLoading');
    var empty   = document.getElementById('analyticsModalEmpty');
    var list    = document.getElementById('analyticsModalList');
    var countEl = document.getElementById('analyticsModalCount');
    var items   = document.getElementById('analyticsModalItems');
    var footerA = document.getElementById('analyticsModalViewAll');

    // Style header with tile's brand colour
    header.style.background = 'linear-gradient(135deg,' + color + 'dd,' + color + ')';
    iconEl.innerHTML = '<i class="fas ' + _escHtml(icon) + '"></i>';
    titleEl.textContent = label;
    footerA.href = viewAllUrl || '#';
    footerA.style.color = color;

    loading.style.display = 'block';
    empty.style.display   = 'none';
    list.style.display    = 'none';
    items.innerHTML       = '';
    countEl.textContent   = '';

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';

fetch(_amUrl + '?filter=' + encodeURIComponent(filter), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            loading.style.display = 'none';
            var tasks = data.tasks || [];
            if (!tasks || tasks.length === 0) {
                empty.style.display = 'block';
                return;
            }
            countEl.textContent = tasks.length + ' task' + (tasks.length !== 1 ? 's' : '');
            items.innerHTML = tasks.map(function(t) {
                var deadlineHtml = '';
                if (t.deadline) {
                    var dStyle = t.isOverdue
                        ? 'color:#EF4444;font-weight:600;'
                        : 'color:#9CA3AF;';
                    deadlineHtml = '<span style="' + dStyle + 'font-size:11px;display:flex;align-items:center;gap:3px;">'
                        + (t.isOverdue ? '<i class="fas fa-exclamation-circle"></i> ' : '<i class="far fa-calendar"></i> ')
                        + _escHtml(t.deadline) + '</span>';
                }
                var priorityColors = { high: '#EF4444', medium: '#F59E0B', low: '#10B981' };
                var priorityBgs   = { high: '#FEF2F2', medium: '#FFFBEB', low: '#ECFDF5' };
                var priorityHtml  = '';
                if (t.priority && t.priorityMeta) {
                    priorityHtml = '<span style="font-size:10px;font-weight:700;color:' + _escHtml(t.priorityMeta.color) + ';background:' + _escHtml(priorityBgs[t.priority] || '#F3F4F6') + ';padding:2px 7px;border-radius:20px;">'
                        + _escHtml(t.priorityMeta.label) + '</span>';
                }
                var assigneeHtml = '';
                if (t.assignee) {
                    assigneeHtml = '<span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:#6B7280;">'
                        + '<span style="width:20px;height:20px;border-radius:50%;background:' + _escHtml(t.statusBg || '#E5E7EB') + ';display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:' + _escHtml(t.statusColor || '#374151') + ';">' + _escHtml(t.initials || t.assignee.charAt(0).toUpperCase()) + '</span>'
                        + _escHtml(t.assignee) + '</span>';
                }
                var projectHtml = t.project
                    ? '<span style="font-size:11px;color:#9CA3AF;"><i class="fas fa-folder" style="font-size:9px;margin-right:3px;"></i>' + _escHtml(t.project) + '</span>'
                    : '';
                return '<a href="' + _escHtml(t.url) + '" style="display:block;text-decoration:none;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:12px 14px;transition:box-shadow .15s;" '
                    + 'onmouseover="this.style.boxShadow=\'0 4px 12px rgba(0,0,0,0.08)\'" onmouseout="this.style.boxShadow=\'none\'">'
                    + '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">'
                    +   '<p style="font-size:13px;font-weight:600;color:#111827;margin:0 0 6px;line-height:1.4;">' + _escHtml(t.title) + '</p>'
                    +   '<span style="font-size:10px;font-weight:700;color:' + _escHtml(t.statusColor) + ';background:' + _escHtml(t.statusBg) + ';padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0;">' + _escHtml(t.statusLabel) + '</span>'
                    + '</div>'
                    + '<div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;">'
                    + priorityHtml + assigneeHtml + projectHtml + deadlineHtml
                    + '</div>'
                    + '</a>';
            }).join('');
            list.style.display = 'block';
        })
        .catch(function() {
            loading.style.display = 'none';
            empty.style.display   = 'block';
        });
}

function closeAnalyticsModal() {
    document.getElementById('analyticsModal').style.display = 'none';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeChartTasksModal();
        closeWorkloadModal();
        closeSocialPostsModal();
        closeAnalyticsModal();
    }
});

function _escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}


// Called by the inline Week/Month/Year buttons
function switchWhPeriod(btn) {
    var period = btn.getAttribute('data-period');
    document.querySelectorAll('#wh-period-btns button').forEach(function(b) {
        var active = b === btn;
        b.style.background = active ? '#fff' : 'none';
        b.style.color      = active ? '#374151' : '#9CA3AF';
        b.style.boxShadow  = active ? '0 1px 2px rgba(0,0,0,0.06)' : 'none';
    });
    loadWhData(period);
}

// Called by the top-level date-range dropdown
function switchWhPeriodByValue(period) {
    // Sync inline buttons (highlight if value matches one of them)
    document.querySelectorAll('#wh-period-btns button').forEach(function(b) {
        var match = b.getAttribute('data-period') === period;
        b.style.background = match ? '#fff' : 'none';
        b.style.color      = match ? '#374151' : '#9CA3AF';
        b.style.boxShadow  = match ? '0 1px 2px rgba(0,0,0,0.06)' : 'none';
    });
    loadWhData(period);
}

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
            backgroundColor: ['#10B981','#6366F1','#60A5FA','#EF4444'],
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

// Donut Chart — Tasks by Customer
(function() {
    var el = document.getElementById('customerTasksChart');
    if (!el) return;
    @php
        $custChartLabels = [];
        $custChartData   = [];
        $custChartColors = [];
        $palette  = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4','#F97316','#14B8A6'];
        $chartIdx = 0;
        foreach ($customerTaskDist as $c) {
            if ($c->tasks_count > 0) {
                $custChartLabels[] = $c->name;
                $custChartData[]   = $c->tasks_count;
                $custChartColors[] = $palette[$chartIdx % count($palette)];
                $chartIdx++;
            }
        }
        if ($unassignedTaskCount > 0) {
            $custChartLabels[] = 'No customer';
            $custChartData[]   = $unassignedTaskCount;
            $custChartColors[] = '#E5E7EB';
        }
    @endphp
    new Chart(el, {
        type: 'doughnut',
        data: {
            labels: @json($custChartLabels),
            datasets: [{
                data: @json($custChartData),
                backgroundColor: @json($custChartColors),
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
                tooltip: { callbacks: { label: function(ctx) { return ' ' + ctx.label + ': ' + ctx.parsed + ' tasks'; } } }
            }
        }
    });
})();

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
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return ' ' + ctx.parsed.y + ' open task' + (ctx.parsed.y !== 1 ? 's' : ''); } } } },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: { grid: { color: '#F3F4F6' }, border: { display: false }, beginAtZero: true, ticks: { stepSize: 1, maxTicksLimit: 6 } }
        },
        onClick: function(evt, elements) {
            if (!elements || !elements.length) return;
            openWorkloadModal(elements[0].index);
        },
        onHover: function(evt, elements) {
            evt.native.target.style.cursor = elements && elements.length ? 'pointer' : 'default';
        }
    }
});
@endif

// ── Auto-Refresh (every 60 seconds) ──────────────────────────────────────
(function() {
    var REFRESH_URL    = '{{ $dashRefreshUrl }}';
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
                setRV('overview_done',           (ov.completed || 0) + (ov.delivered || 0) + (ov.archived || 0));
                setRV('overview_overdue',        ov.overdue);
                setRV('overview_due_today',      ov.due_today);
                setRV('overview_due_this_week',  ov.due_this_week);
                setRV('overview_reopened',       ov.reopened   || 0);
                setRV('overview_reassigned',     ov.reassigned || 0);

                // ── Rate circles ──
                updateRateCircle('rateCircleCompletion', 'rateTextCompletion', d.completionRate);
                updateRateCircle('rateCircleOnTime',     'rateTextOnTime',     d.onTimeRate);
                var rcEl = document.getElementById('reviewCyclesVal');
                if (rcEl) rcEl.textContent = d.reviewCycles;
                var rsSub = document.getElementById('rateSubCompletion');
                if (rsSub) rsSub.textContent = ((ov.completed || 0) + (ov.delivered || 0) + (ov.archived || 0)) + ' of ' + ov.total + ' tasks done';

                // ── Social Media stats ──
                if (d.socialPostsTotal !== undefined) setRV('socialPostsTotal', d.socialPostsTotal);
                if (d.socialPostsMonth !== undefined) setRV('socialPostsMonth', d.socialPostsMonth);
                if (d.socialPending    !== undefined) setRV('socialPending',    d.socialPending);

                // ── Working Hours chart ──
                if (typeof chartWorkingHours !== 'undefined' && chartWorkingHours) {
                    chartWorkingHours.data.labels = d.weekLabels;
                    chartWorkingHours.data.datasets[0].data = d.weekData;
                    chartWorkingHours.update('none');
                }

                // ── Project Stats donut ──
                if (typeof chartProjectStats !== 'undefined' && chartProjectStats && d.taskStats) {
                    var ts = d.taskStats;
                    chartProjectStats.data.datasets[0].data = [ts.completed, ts.in_progress, ts.pending, ts.overdue];
                    chartProjectStats.update('none');
                    setRV('projectStats_completed',  ts.completed);
                    setRV('projectStats_in_progress',ts.in_progress);
                    setRV('projectStats_pending',    ts.pending);
                    setRV('projectStats_overdue',    ts.overdue);
                    setRV('projectStats_total', (ts.completed||0)+(ts.in_progress||0)+(ts.pending||0)+(ts.overdue||0));
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

{{-- Extra Charts JS --}}
@if(in_array('dash_priority_chart', $devExtras))
<script>
(function() {
    var ctx = document.getElementById('priorityChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($priorityData['labels']),
            datasets: [{ data: @json($priorityData['data']), backgroundColor: ['#EF4444','#F59E0B','#10B981'], borderWidth: 0, hoverOffset: 6 }]
        },
        options: { cutout: '68%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c) { return ' ' + c.label + ': ' + c.parsed; } } } }, animation: { duration: 700 } }
    });
})();
</script>
@endif

@if(in_array('dash_team_performance', $devExtras))
<script>
(function() {
    var ctx = document.getElementById('teamPerfChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($teamPerfData['labels']),
            datasets: [
                { label: 'Completed', data: @json($teamPerfData['completed']),   backgroundColor: '#10B981', borderRadius: 5, barThickness: 14 },
                { label: 'In Progress', data: @json($teamPerfData['in_progress']), backgroundColor: '#6366F1', borderRadius: 5, barThickness: 14 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 10, padding: 14 } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 11 }, stepSize: 1 } }
            }
        }
    });
})();
</script>
@endif

@endpush

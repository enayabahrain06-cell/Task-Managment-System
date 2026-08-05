@extends('layouts.app')
@section('title', 'Add Task — ' . $project->name)

@push('styles')
<style>
.tc-wrap { max-width:660px; width:100%; }
.tc-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px; }
.tc-grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:16px; }
.tc-assignee-row { display:grid; grid-template-columns:1fr 1fr auto; gap:10px; align-items:center; }
@media(max-width:768px){
    .tc-back-btn { width:44px !important; height:44px !important; }
    .tc-wrap { padding:0; }
    .tc-wrap > div {
        padding:16px !important;
        border-radius:var(--mob-r-lg, 20px) !important;
        box-shadow:var(--mob-shadow-1, 0 2px 10px rgba(17,24,39,.05)) !important;
    }
    .tc-grid2 { grid-template-columns:1fr !important; gap:10px !important; }
    .tc-grid3 { grid-template-columns:1fr !important; gap:10px !important; }
    .tc-assignee-row { grid-template-columns:1fr auto !important; }
    .tc-assignee-row > *:nth-child(2) { grid-column:1; }

    .tc-wrap input,
    .tc-wrap select,
    .tc-wrap textarea {
        width:100% !important; min-height:46px !important; font-size:16px !important; box-sizing:border-box !important;
        border-radius:var(--mob-r-sm, 12px) !important;
    }
    .tc-wrap textarea { min-height:90px !important; }

    /* Recurring "repeat" pill row: 4 columns is too cramped on phones */
    .tc-wrap [style*="grid-template-columns:repeat(4,1fr)"] {
        grid-template-columns:1fr 1fr !important; gap:8px !important;
    }
    .tc-wrap [style*="grid-template-columns:repeat(4,1fr)"] > label > div {
        min-height:44px !important; display:flex !important; align-items:center !important; justify-content:center !important; box-sizing:border-box !important;
    }

    .mob-touch-btn {
        min-width:44px !important; min-height:44px !important;
        display:inline-flex !important; align-items:center !important; justify-content:center !important;
    }

    .tc-recur-grid { grid-template-columns:1fr !important; }

    .tc-actions { flex-direction:column !important; gap:10px !important; }
    .tc-actions > * { width:100% !important; min-height:46px !important; font-size:15px !important; }
}
</style>
@endpush

@section('content')
<div class="tc-wrap" style="max-width:660px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('admin.projects.show', $project) }}" class="tc-back-btn"
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
        removeAssignee(i) { if (this.assignees.length > 1) this.assignees.splice(i, 1); },
        tcSubmitting: false,
        tcTitleDuplicate: false,
        tcDupTimer: null,
        checkTcDuplicate() {
            clearTimeout(this.tcDupTimer);
            const titleEl    = document.getElementById('tcTitleInput');
            const warnEl     = document.getElementById('tcTitleDupWarn');
            const customerId = document.getElementById('tcCustomerSelect').value;
            const title      = titleEl.value.trim();
            if (!title) {
                warnEl.style.display = 'none';
                this.tcTitleDuplicate = false;
                return;
            }
            this.tcDupTimer = setTimeout(async () => {
                const params = new URLSearchParams({ title, project_id: '{{ $project->id }}' });
                if (customerId) params.set('customer_id', customerId);
                try {
                    const res  = await fetch(`{{ route('admin.tasks.check-duplicate-title') }}?${params}`);
                    const data = await res.json();
                    this.tcTitleDuplicate = !!data.duplicate;
                    if (data.duplicate) {
                        warnEl.textContent = `A task named '${title}' already exists for this customer (${data.count}${data.count >= 5 ? '+' : ''}). Use a different title to continue.`;
                        warnEl.style.color = '#DC2626';
                        warnEl.style.display = 'block';
                    } else {
                        warnEl.style.display = 'none';
                    }
                } catch (e) {
                    this.tcTitleDuplicate = false;
                    warnEl.style.display = 'none';
                }
            }, 450);
        }
    }"
    style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:28px;">

        <form method="POST" action="{{ route('admin.projects.tasks.store', $project) }}"
              @submit="if (tcSubmitting || tcTitleDuplicate) { $event.preventDefault(); return; } tcSubmitting = true">
            @csrf

            {{-- Title --}}
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Task Title <span style="color:#EF4444;">*</span></label>
                <input type="text" name="title" id="tcTitleInput" value="{{ old('title') }}" required placeholder="e.g. Design landing page"
                       style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('title') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;"
                       @input="checkTcDuplicate()">
                @error('title')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
                <p id="tcTitleDupWarn" style="display:none;font-size:11px;color:#DC2626;margin-top:5px;font-weight:600;"></p>
            </div>

            {{-- Task Type + Tags --}}
            <div class="tc-grid2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
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
                    <button type="button" @click="addAssignee()" class="mob-touch-btn"
                            style="font-size:11px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:none;padding:4px 12px;border-radius:6px;cursor:pointer;">
                        + Add Person
                    </button>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <template x-for="(assignee, i) in assignees" :key="i">
                        <div class="tc-assignee-row" style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:center;">
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
                            <button type="button" @click="removeAssignee(i)" x-show="assignees.length > 1" class="mob-touch-btn"
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
            <div class="tc-grid3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:16px;">
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

            {{-- Customer --}}
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Customer <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— optional</span></label>
                <select name="customer_id" id="tcCustomerSelect"
                        style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;background:#fff;outline:none;box-sizing:border-box;"
                        @change="checkTcDuplicate()">
                    <option value="">— No customer —</option>
                    @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ old('customer_id', $project->customer_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}{{ $c->company ? ' ('.$c->company.')' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Description --}}
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Description</label>
                <textarea name="description" rows="3" placeholder="What needs to be done..."
                          style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;">{{ old('description') }}</textarea>
            </div>

            @if(\App\Models\Setting::get('show_recurring_tasks','1') === '1')
            {{-- Recurring --}}
            <div x-data="{ recurring: '{{ old('recurring_type','') }}' }"
                 style="margin-bottom:24px;background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:12px;padding:16px;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:26px;height:26px;border-radius:7px;background:#ECFEFF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-rotate" style="font-size:11px;color:#0891B2;"></i>
                    </div>
                    <p style="font-size:12px;font-weight:700;color:#111827;margin:0;">Repeat this task</p>
                </div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:0;">
                    @foreach([''=>'No repeat','daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly'] as $val=>$label)
                    <label style="cursor:pointer;">
                        <input type="radio" name="recurring_type" value="{{ $val }}" x-model="recurring"
                               style="display:none;">
                        <div :style="recurring==='{{ $val }}' ? 'background:#0891B2;color:#fff;border-color:#0891B2;' : 'background:#fff;color:#374151;border-color:#E5E7EB;'"
                             style="padding:8px 4px;border-radius:9px;border:1.5px solid;text-align:center;font-size:12px;font-weight:600;transition:all .15s;">
                            {{ $label }}
                        </div>
                    </label>
                    @endforeach
                </div>
                <div x-show="recurring !== ''" x-collapse class="tc-recur-grid" style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:5px;">End date <span style="font-weight:400;color:#9CA3AF;">— optional</span></label>
                        <input type="date" name="recurring_end_date" value="{{ old('recurring_end_date') }}"
                               style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:5px;">Max repeats <span style="font-weight:400;color:#9CA3AF;">— optional</span></label>
                        <input type="number" name="recurring_max" value="{{ old('recurring_max') }}" min="1" placeholder="e.g. 12"
                               style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;">
                    </div>
                </div>
            </div>
            @endif

            <div class="tc-actions mob-sticky-action-bar" style="display:flex;gap:10px;">
                <button type="submit" :disabled="tcSubmitting || tcTitleDuplicate"
                        :style="(tcSubmitting || tcTitleDuplicate) ? 'flex:1;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:14px;font-weight:600;cursor:not-allowed;opacity:0.8;' : 'flex:1;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;'">
                    <i class="fa" :class="tcSubmitting ? 'fa-spinner fa-spin' : 'fa-plus'" style="margin-right:6px;"></i>
                    <span x-text="tcTitleDuplicate ? 'Duplicate Title' : (tcSubmitting ? 'Adding...' : 'Add Task')"></span>
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

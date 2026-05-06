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
    $_mapTask = fn($t) => [
        'title'       => $t->title,
        'project'     => $t->project->name ?? '—',
        'assignee'    => $t->assignee->name ?? null,
        'statusLabel' => $_tsLabel[$t->status] ?? ucfirst(str_replace('_',' ',$t->status)),
        'statusBg'    => $_tsBg[$t->status]    ?? '#F3F4F6',
        'statusColor' => $_tsColor[$t->status]  ?? '#374151',
        'deadline'    => $t->deadline ? $t->deadline->format('M d, Y') : null,
        'overdue'     => $t->deadline && $t->deadline->isPast() && !in_array($t->status, ['approved','delivered','archived']),
        'url'         => route('admin.tasks.show', $t->id),
    ];
    $_allTasks = $customer->tasks;
    $taskGroupsJson = json_encode([
        'pending' => $_allTasks->whereIn('status', ['draft','assigned','viewed'])->map($_mapTask)->values(),
        'active'  => $_allTasks->whereIn('status', ['in_progress','submitted','revision_requested'])->map($_mapTask)->values(),
        'done'    => $_allTasks->whereIn('status', ['approved','delivered','archived'])->map($_mapTask)->values(),
        'overdue' => $_allTasks->filter(fn($t) => $t->deadline && $t->deadline->isPast() && !in_array($t->status, ['approved','delivered','archived']))->map($_mapTask)->values(),
    ]);
    $customerProjectsJson = json_encode($customer->projects->map(fn($p) => [
        'name'        => $p->name,
        'tasksCount'  => $p->tasks_count ?? 0,
        'statusLabel' => $_psLabel[$p->status] ?? ucfirst($p->status),
        'statusBg'    => $_psBg[$p->status]    ?? '#F3F4F6',
        'statusColor' => $_psColor[$p->status]  ?? '#374151',
        'deadline'    => $p->deadline ? $p->deadline->format('M d, Y') : null,
        'overdue'     => $p->deadline && $p->deadline->isPast() && $p->status !== 'completed',
        'url'         => route('admin.projects.show', $p->id),
    ])->values());
@endphp
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
    close() { this.task = null; this.project = null; this.statsModal = null; document.body.style.overflow=''; }
}" @keydown.escape.window="close()" style="max-width:900px;">

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
                       style="display:flex;align-items:center;gap:12px;padding:10px 8px;border-bottom:1px solid #F9FAFB;text-decoration:none;border-radius:8px;transition:background .12s;"
                       onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                        <div style="min-width:0;flex:1;">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <p x-text="t.title" style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px;"></p>
                                <span x-show="t.overdue" style="font-size:10px;font-weight:600;color:#DC2626;flex-shrink:0;">⚠ Overdue</span>
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

    {{-- Header --}}
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('admin.customers.index') }}"
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
        <a href="{{ route('admin.customers.edit', $customer) }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:#EEF2FF;color:#4F46E5;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;">
            <i class="fas fa-pencil" style="font-size:11px;"></i> Edit
        </a>
    </div>

    {{-- Quick Stats Bar --}}
    @php
        $allTasks     = $customer->tasks;
        $statPending  = $allTasks->whereIn('status', ['draft','assigned','viewed'])->count();
        $statActive   = $allTasks->whereIn('status', ['in_progress','submitted','revision_requested'])->count();
        $statDone     = $allTasks->whereIn('status', ['approved','delivered','archived'])->count();
        $statOverdue  = $allTasks->filter(fn($t) => $t->deadline && $t->deadline->isPast() && !in_array($t->status, ['approved','delivered','archived']))->count();
    @endphp
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
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

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;align-items:start;">

        {{-- Contact card --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:22px;">
            <h2 style="font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin:0 0 16px;">Contact Info</h2>
            @if($customer->email)
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <div style="width:32px;height:32px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-envelope" style="font-size:13px;color:#6366F1;"></i>
                </div>
                <div>
                    <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Email</p>
                    <a href="mailto:{{ $customer->email }}" style="font-size:13px;color:#111827;text-decoration:none;">{{ $customer->email }}</a>
                </div>
            </div>
            @endif
            @if($customer->phone)
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <div style="width:32px;height:32px;border-radius:8px;background:#F0FDF4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-phone" style="font-size:13px;color:#16A34A;"></i>
                </div>
                <div style="flex:1;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Phone</p>
                    <a href="tel:{{ $customer->phone }}" style="font-size:13px;color:#111827;text-decoration:none;">{{ $customer->phone }}</a>
                </div>
                @if($customer->whatsappUrl())
                <a href="{{ $customer->whatsappUrl() }}" target="_blank" rel="noopener"
                   title="Contact on WhatsApp"
                   style="display:inline-flex;align-items:center;gap:5px;padding:6px 11px;background:#25D366;color:#fff;border-radius:8px;font-size:11px;font-weight:700;text-decoration:none;flex-shrink:0;transition:opacity .15s;"
                   onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <i class="fab fa-whatsapp" style="font-size:14px;"></i> WhatsApp
                </a>
                @endif
            </div>
            @endif

            @if($customer->notes)
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #F3F4F6;">
                <p style="font-size:10px;color:#9CA3AF;margin:0 0 6px;text-transform:uppercase;letter-spacing:.04em;">Notes</p>
                <p style="font-size:13px;color:#374151;margin:0;line-height:1.6;white-space:pre-wrap;">{{ $customer->notes }}</p>
            </div>
            @endif

            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #F3F4F6;">
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
                        'deadline'    => $project->deadline ? $project->deadline->format('M d, Y') : null,
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
                            @if($project->deadline) · {{ $project->deadline->format('M d, Y') }} @endif
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
                        'deadline'      => $task->deadline ? $task->deadline->format('M d, Y') : null,
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
                            @if($task->deadline) · {{ $task->deadline->format('M d, Y') }} @endif
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
</div>
@endsection

@extends('layouts.app')
@section('title', $customer->name)

@section('content')
<div x-data="{
    task: null,    openTask(t) { this.task = t; document.body.style.overflow='hidden'; },
    project: null, openProject(p) { this.project = p; document.body.style.overflow='hidden'; },
    close() { this.task = null; this.project = null; document.body.style.overflow=''; }
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
                <div>
                    <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Phone</p>
                    <a href="tel:{{ $customer->phone }}" style="font-size:13px;color:#111827;text-decoration:none;">{{ $customer->phone }}</a>
                </div>
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

            {{-- Tasks --}}
            <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:22px;">
                <h2 style="font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin:0 0 16px;">
                    Tasks <span style="font-weight:500;color:#9CA3AF;">({{ $customer->tasks->count() }})</span>
                </h2>
                @php
                    $tsBg = ['draft'=>'#F3F4F6','assigned'=>'#EEF2FF','viewed'=>'#E0F2FE','in_progress'=>'#FFF7ED','submitted'=>'#F5F3FF','revision_requested'=>'#FEE2E2','approved'=>'#ECFDF5','delivered'=>'#ECFDF5','archived'=>'#F3F4F6'];
                    $tsColor = ['draft'=>'#6B7280','assigned'=>'#4F46E5','viewed'=>'#0369A1','in_progress'=>'#EA580C','submitted'=>'#7C3AED','revision_requested'=>'#DC2626','approved'=>'#16A34A','delivered'=>'#16A34A','archived'=>'#9CA3AF'];
                    $tsLabel = ['draft'=>'Draft','assigned'=>'Assigned','viewed'=>'Viewed','in_progress'=>'In Progress','submitted'=>'In Review','revision_requested'=>'Revision','approved'=>'Approved','delivered'=>'Delivered','archived'=>'Archived'];
                    $prBg    = ['high'=>'#FEE2E2','medium'=>'#FEF3C7','low'=>'#DCFCE7'];
                    $prColor = ['high'=>'#DC2626','medium'=>'#D97706','low'=>'#16A34A'];
                @endphp
                @forelse($customer->tasks->take(20) as $task)
                @php
                    $taskData = json_encode([

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
                        'overdue'       => $task->deadline && $task->deadline->isPast() && !in_array($task->status, ['approved','delivered','archived']),
                        'description'   => $task->description ? \Illuminate\Support\Str::limit($task->description, 200) : null,
                        'url'           => route('admin.tasks.show', $task->id),
                    ]);
                @endphp
                <button @click="openTask({{ $taskData }})"
                        style="display:flex;align-items:center;justify-content:space-between;padding:10px 8px;border-bottom:1px solid #F9FAFB;width:100%;background:none;border-left:none;border-right:none;border-top:none;cursor:pointer;text-align:left;transition:background .12s;border-radius:6px;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                    <div style="min-width:0;flex:1;padding-right:12px;">
                        <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $task->title }}</p>
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

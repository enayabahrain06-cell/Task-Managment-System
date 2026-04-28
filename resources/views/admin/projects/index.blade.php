@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div x-data="{
    wizardOpen: false,
    quickOpen: false,
    view: localStorage.getItem('projects_view') || 'table',
    setView(v) { this.view = v; localStorage.setItem('projects_view', v); },
    step: 1,
    totalSteps: 3,

    allUsers: {{ $users->map(fn($u) => ['id' => (string)$u->id, 'name' => $u->name, 'email' => $u->email, 'role' => ucfirst($u->role), 'job' => $u->job_title ?? ''])->toJson() }},

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
        <p class="text-sm text-gray-500 mt-0.5">{{ $projects->total() }} {{ request('status') === 'completed' ? 'completed' : 'active' }} project{{ $projects->total() !== 1 ? 's' : '' }}</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="openWizard()" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm shadow-indigo-200">
            <i class="fa fa-plus"></i> New Project
        </button>
        <button @click="quickOpen = true" class="flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition border border-gray-200 shadow-sm">
            <i class="fa fa-bolt text-amber-500"></i> Quick Task
        </button>
    </div>
</div>

{{-- Stat Cards --}}
<style>
.proj-stat-card { border-radius:14px; padding:18px 20px; position:relative; overflow:hidden; color:#fff; cursor:default; transition:transform .15s,box-shadow .15s; }
.proj-stat-card:hover { transform:translateY(-3px); }
.proj-stat-card-blob { position:absolute; top:-20px; right:-20px; width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.12); }
.proj-stat-card-label { font-size:12px; font-weight:500; color:rgba(255,255,255,0.75); margin:0 0 8px; }
.proj-stat-card-value { font-size:34px; font-weight:700; line-height:1; margin:0; }
.proj-stat-card-sub   { font-size:11px; color:rgba(255,255,255,0.6); margin:6px 0 0; }
</style>

@php
$currentStatus   = request('status', '');
$isOverdueFilter = request()->boolean('overdue');
$isCompletedTab  = ($currentStatus === 'completed');
@endphp

{{-- Tab Bar --}}
<div style="display:flex;align-items:center;gap:3px;background:#F3F4F6;border-radius:12px;padding:4px;width:fit-content;margin-bottom:20px;">
    <a href="{{ route('admin.projects.index') }}"
       style="display:flex;align-items:center;gap:8px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;{{ !$isCompletedTab ? 'background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);' : 'background:transparent;color:#6B7280;' }}">
        <i class="fa fa-circle-play" style="font-size:11px;"></i>
        Active
        <span style="font-size:11px;font-weight:700;padding:1px 8px;border-radius:20px;{{ !$isCompletedTab ? 'background:#EEF2FF;color:#4F46E5;' : 'background:#E5E7EB;color:#9CA3AF;' }}">
            {{ $stats['total'] - $stats['completed'] }}
        </span>
    </a>
    <a href="{{ route('admin.projects.index', ['status'=>'completed']) }}"
       style="display:flex;align-items:center;gap:8px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;{{ $isCompletedTab ? 'background:#fff;color:#7C3AED;box-shadow:0 1px 4px rgba(0,0,0,.08);' : 'background:transparent;color:#6B7280;' }}">
        <i class="fa fa-circle-check" style="font-size:11px;"></i>
        Completed
        <span style="font-size:11px;font-weight:700;padding:1px 8px;border-radius:20px;{{ $isCompletedTab ? 'background:#EDE9FE;color:#7C3AED;' : 'background:#E5E7EB;color:#9CA3AF;' }}">
            {{ $stats['completed'] }}
        </span>
    </a>
</div>

@if(!$isCompletedTab)
{{-- Active tab: 3 stat cards (All Active / Active only / Overdue) --}}
@php
$statDefs = [
    ['label'=>'All Active',  'value'=>$stats['total'] - $stats['completed'], 'sub'=>'Non-completed',   'grad'=>'linear-gradient(135deg,#4F46E5,#6366F1)', 'shadow'=>'rgba(79,70,229,.4)',   'url'=> route('admin.projects.index'),                       'active'=> !$currentStatus && !$isOverdueFilter],
    ['label'=>'Active',      'value'=>$stats['active'],                      'sub'=>'Currently Active', 'grad'=>'linear-gradient(135deg,#059669,#10B981)', 'shadow'=>'rgba(5,150,105,.4)',   'url'=> route('admin.projects.index', ['status'=>'active']),  'active'=> $currentStatus === 'active'],
    ['label'=>'Overdue',     'value'=>$stats['overdue'],                     'sub'=>'Past Deadline',    'grad'=>'linear-gradient(135deg,#DC2626,#EF4444)', 'shadow'=>'rgba(220,38,38,.4)',   'url'=> route('admin.projects.index') . '?overdue=1',        'active'=> $isOverdueFilter],
    ['label'=>'Completed',   'value'=>$stats['completed'],                   'sub'=>'All time done',    'grad'=>'linear-gradient(135deg,#7C3AED,#8B5CF6)', 'shadow'=>'rgba(124,58,237,.4)',  'url'=> route('admin.projects.index', ['status'=>'completed']),'active'=> false],
];
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    @foreach($statDefs as $card)
    @php $isActive = $card['active']; @endphp
    <a href="{{ $card['url'] }}" style="text-decoration:none;display:flex;">
        <div class="proj-stat-card"
             style="flex:1;background:{{ $card['grad'] }};{{ $isActive ? 'transform:translateY(-3px);box-shadow:0 8px 24px '.$card['shadow'].';outline:3px solid rgba(255,255,255,0.4);' : '' }}"
             onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px {{ $card['shadow'] }}'"
             onmouseout="this.style.transform='{{ $isActive ? 'translateY(-3px)' : '' }}';this.style.boxShadow='{{ $isActive ? '0 8px 24px '.$card['shadow'] : '' }}'">
            <div class="proj-stat-card-blob"></div>
            <p class="proj-stat-card-label">{{ $card['label'] }}</p>
            <p class="proj-stat-card-value">{{ $card['value'] }}</p>
            <p class="proj-stat-card-sub">{{ $card['sub'] }}</p>
        </div>
    </a>
    @endforeach
</div>
@if($currentStatus === 'active' || $isOverdueFilter)
<div style="margin-bottom:16px;display:flex;align-items:center;gap:8px;">
    <span style="font-size:13px;color:#6B7280;">Filtering: <strong style="color:#111827;">{{ $isOverdueFilter ? 'Overdue only' : 'Active only' }}</strong></span>
    <a href="{{ route('admin.projects.index') }}" style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:#EF4444;text-decoration:none;background:#FEF2F2;border:1px solid #FECACA;padding:3px 9px;border-radius:6px;">
        <i class="fas fa-times" style="font-size:10px;"></i> Clear filter
    </a>
</div>
@endif

@else
{{-- Completed tab: summary banner instead of stat cards --}}
<div style="display:flex;align-items:center;gap:14px;padding:16px 20px;background:linear-gradient(135deg,#F5F3FF,#EDE9FE);border-radius:14px;border:1px solid #DDD6FE;margin-bottom:24px;">
    <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,#7C3AED,#8B5CF6);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(124,58,237,.3);">
        <i class="fa fa-circle-check" style="color:#fff;font-size:18px;"></i>
    </div>
    <div style="flex:1;">
        <p style="font-size:15px;font-weight:700;color:#5B21B6;margin:0;">{{ $stats['completed'] }} Completed Project{{ $stats['completed'] !== 1 ? 's' : '' }}</p>
        <p style="font-size:12px;color:#7C3AED;margin:3px 0 0;opacity:.8;">Archived work — these projects have been closed and marked as done.</p>
    </div>
    <a href="{{ route('admin.projects.index') }}"
       style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#7C3AED;text-decoration:none;background:rgba(255,255,255,.6);border:1px solid #C4B5FD;padding:7px 14px;border-radius:8px;">
        <i class="fa fa-arrow-left" style="font-size:10px;"></i> Back to Active
    </a>
</div>
@endif

{{-- View Toggle --}}
<div style="display:flex;gap:2px;background:#F3F4F6;border-radius:12px;padding:4px;margin-bottom:22px;width:fit-content;">
    <button @click="setView('table')"
            :style="view==='table'
                ? 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);'
                : 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;'">
        <i class="fa fa-table-list" style="font-size:11px;"></i> Table
    </button>
    <button @click="setView('cards')"
            :style="view==='cards'
                ? 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);'
                : 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;'">
        <i class="fa fa-grip" style="font-size:11px;"></i> Cards
    </button>
</div>

{{-- ══ TABLE VIEW ══ --}}
<div x-show="view === 'table'" x-cloak>
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
                    @if($project->customer)
                    <div class="flex items-center gap-1 mt-1">
                        <i class="fas fa-building" style="font-size:9px;color:#818CF8;"></i>
                        <span class="text-xs font-medium" style="color:#4F46E5;">{{ $project->customer->name }}{{ $project->customer->company ? ' · '.$project->customer->company : '' }}</span>
                    </div>
                    @elseif($project->description)
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
                        @if($project->status === 'completed')
                        <form action="{{ route('admin.projects.reopen', $project) }}" method="POST" class="inline"
                              onsubmit="return confirm('Reopen {{ addslashes($project->name) }} and set it back to Active?')">
                            @csrf
                            <button type="submit" class="text-xs font-medium text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 px-2.5 py-1.5 rounded-lg transition">Reopen</button>
                        </form>
                        @else
                        <form action="{{ route('admin.projects.close', $project) }}" method="POST" class="inline"
                              onsubmit="return confirm('Close &quot;{{ addslashes($project->name) }}&quot; and mark it as Completed?')">
                            @csrf
                            <button type="submit" class="text-xs font-medium text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1.5 rounded-lg transition">Close</button>
                        </form>
                        @endif
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
</div>{{-- end table view --}}

{{-- ══ CARD VIEW ══ --}}
<div x-show="view === 'cards'" x-cloak>
    @if($projects->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm py-24 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-diagram-project text-2xl text-gray-300"></i>
        </div>
        <p class="text-gray-500 font-semibold">No projects found</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-5">
        @foreach($projects as $project)
        @php
            $pct        = $project->tasks_count > 0 ? round(($project->completed_tasks_count / $project->tasks_count) * 100) : 0;
            $isOverdue  = $project->deadline < now() && $project->status !== 'completed';
            $isDone     = $project->status === 'completed';
            $statusMap  = [
                'active'    => ['label'=>'Active',    'color'=>'#16A34A','bg'=>'#DCFCE7'],
                'completed' => ['label'=>'Completed', 'color'=>'#64748B','bg'=>'#F1F5F9'],
                'overdue'   => ['label'=>'Overdue',   'color'=>'#DC2626','bg'=>'#FEE2E2'],
            ];
            $s = $statusMap[$project->status] ?? $statusMap['active'];
            $members    = $project->members->take(4);
            $extraCount = max(0, $project->members->count() - 4);
            $avatarColors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4'];
        @endphp

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-100 transition group flex flex-col overflow-hidden cursor-pointer"
             onclick="window.location='{{ route('admin.projects.show', $project) }}'">

            <div class="p-5 flex flex-col gap-3 flex-1">

                {{-- Top row: status badge + task count --}}
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                          style="background:{{ $s['bg'] }};color:{{ $s['color'] }};">
                        {{ $s['label'] }}
                    </span>
                    <span class="text-xs text-gray-400 flex items-center gap-1">
                        <i class="fas fa-list-check" style="font-size:10px;"></i>
                        {{ $project->tasks_count }}
                    </span>
                </div>

                {{-- Title --}}
                <h3 class="text-sm font-semibold text-gray-800 leading-snug group-hover:text-indigo-600 transition line-clamp-2">
                    {{ $project->name }}
                </h3>
                @if($project->customer)
                <div class="flex items-center gap-1" style="margin-top:-2px;">
                    <i class="fas fa-building" style="font-size:9px;color:#818CF8;"></i>
                    <span class="text-xs font-medium" style="color:#4F46E5;">{{ $project->customer->name }}{{ $project->customer->company ? ' · '.$project->customer->company : '' }}</span>
                </div>
                @endif

                {{-- Progress bar --}}
                <div>
                    <div class="flex justify-between text-xs text-gray-400 mb-1.5">
                        <span>{{ $project->completed_tasks_count }}/{{ $project->tasks_count }} tasks</span>
                        <span class="font-semibold text-gray-600">{{ $pct }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full transition-all {{ $isDone ? 'bg-emerald-400' : 'bg-indigo-400' }}"
                             style="width:{{ $pct }}%;"></div>
                    </div>
                </div>

                {{-- Members --}}
                <div class="flex items-center gap-2 mt-auto">
                    @if($members->isEmpty())
                        <i class="fas fa-users text-gray-200 text-xs"></i>
                        <span class="text-xs text-gray-300">No members</span>
                    @else
                        <div class="flex items-center">
                            @foreach($members as $mi => $member)
                            @php $aColor = $avatarColors[$member->id % 8]; @endphp
                            <div class="w-6 h-6 rounded-full border-2 border-white overflow-hidden flex-shrink-0"
                                 style="margin-left:{{ $mi > 0 ? '-8px' : '0' }};position:relative;z-index:{{ 10 - $mi }};"
                                 title="{{ $member->name }}">
                                @if($member->avatar)
                                    <img src="{{ Storage::url($member->avatar) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-white font-bold"
                                         style="background:{{ $aColor }};font-size:9px;">
                                        {{ strtoupper(substr($member->name,0,1)) }}
                                    </div>
                                @endif
                            </div>
                            @endforeach
                            @if($extraCount > 0)
                            <div class="w-6 h-6 rounded-full border-2 border-white bg-gray-100 flex items-center justify-center flex-shrink-0"
                                 style="margin-left:-8px;font-size:8px;font-weight:700;color:#6B7280;">
                                +{{ $extraCount }}
                            </div>
                            @endif
                        </div>
                        <span class="text-xs text-gray-500">{{ $project->members->count() }} member{{ $project->members->count() !== 1 ? 's' : '' }}</span>
                    @endif
                </div>

                {{-- Deadline + actions --}}
                <div class="flex items-center justify-between gap-1.5 pt-2.5 border-t border-gray-50">
                    <div class="flex items-center gap-1.5">
                        @if($isOverdue)
                        <i class="fas fa-triangle-exclamation text-red-400 text-xs"></i>
                        <span class="text-xs font-semibold text-red-500">Overdue · {{ $project->deadline->format('M d') }}</span>
                        @else
                        <i class="fas fa-calendar-days text-gray-300 text-xs"></i>
                        <span class="text-xs text-gray-400">Due {{ $project->deadline->format('M d, Y') }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                        <a href="{{ route('admin.projects.edit', $project) }}"
                           class="w-6 h-6 rounded-lg bg-gray-100 hover:bg-indigo-100 flex items-center justify-center text-gray-400 hover:text-indigo-600 transition"
                           style="text-decoration:none;" title="Edit">
                            <i class="fa fa-pen" style="font-size:10px;"></i>
                        </a>
                        @if($project->status === 'completed')
                        <form action="{{ route('admin.projects.reopen', $project) }}" method="POST"
                              onsubmit="return confirm('Reopen {{ addslashes($project->name) }} and set it back to Active?')" style="display:contents;">
                            @csrf
                            <button type="submit"
                                    class="w-6 h-6 rounded-lg bg-amber-50 hover:bg-amber-100 flex items-center justify-center text-amber-400 hover:text-amber-600 transition"
                                    title="Reopen Project" style="cursor:pointer;border:none;">
                                <i class="fa fa-rotate-right" style="font-size:10px;"></i>
                            </button>
                        </form>
                        @else
                        <form action="{{ route('admin.projects.close', $project) }}" method="POST"
                              onsubmit="return confirm('Close &quot;{{ addslashes($project->name) }}&quot; and mark it as Completed?')" style="display:contents;">
                            @csrf
                            <button type="submit"
                                    class="w-6 h-6 rounded-lg bg-emerald-50 hover:bg-emerald-100 flex items-center justify-center text-emerald-400 hover:text-emerald-600 transition"
                                    title="Close Project" style="cursor:pointer;border:none;">
                                <i class="fa fa-check" style="font-size:10px;"></i>
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('admin.projects.destroy', $project) }}" method="POST"
                              onsubmit="return confirm('Delete {{ addslashes($project->name) }}?')" style="display:contents;">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-6 h-6 rounded-lg bg-gray-100 hover:bg-red-100 flex items-center justify-center text-gray-400 hover:text-red-500 transition"
                                    title="Delete" style="cursor:pointer;border:none;">
                                <i class="fa fa-trash" style="font-size:10px;"></i>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
        @endforeach
    </div>

    @if($projects->hasPages())
    <div class="mt-4">{{ $projects->links() }}</div>
    @endif
    @endif
</div>{{-- end card view --}}

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
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ ucfirst($u->role) }}) — {{ $u->email }}</option>
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
    <div @click="closeWizard()" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(4px);"></div>

    {{-- Modal --}}
    <div style="position:relative;width:100%;max-width:680px;max-height:92vh;background:#fff;border-radius:24px;box-shadow:0 32px 80px rgba(0,0,0,0.22);display:flex;flex-direction:column;">

        {{-- ── Header ── --}}
        <div style="padding:24px 28px 0;flex-shrink:0;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
                <div>
                    <h2 style="font-size:18px;font-weight:700;color:#111827;margin:0;">New Project</h2>
                    <p style="font-size:12px;color:#9CA3AF;margin:3px 0 0;" x-text="'Step ' + step + ' of ' + totalSteps"></p>
                </div>
                <button @click="closeWizard()"
                        style="width:34px;height:34px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:14px;flex-shrink:0;">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            {{-- Step Indicators (centered, equal spacing, clickable) --}}
            <div style="display:flex;align-items:flex-start;justify-content:center;margin-bottom:28px;">
                <template x-for="s in totalSteps" :key="s">
                    <div style="display:flex;align-items:center;">
                        {{-- Circle + Label --}}
                        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;min-width:72px;">
                            <button type="button"
                                    @click="if(s <= step) step = s"
                                    :style="step >= s
                                        ? 'width:36px;height:36px;border-radius:50%;background:#4F46E5;color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;box-shadow:0 2px 10px rgba(79,70,229,.35);'
                                        : 'width:36px;height:36px;border-radius:50%;background:#F3F4F6;color:#9CA3AF;border:none;font-size:13px;font-weight:700;cursor:default;display:flex;align-items:center;justify-content:center;transition:all .2s;'">
                                <span x-show="step > s" style="display:flex;"><i class="fa fa-check" style="font-size:11px;"></i></span>
                                <span x-show="step <= s" x-text="s"></span>
                            </button>
                            <span :style="step >= s ? 'font-size:11px;font-weight:600;color:#4F46E5;white-space:nowrap;' : 'font-size:11px;font-weight:500;color:#9CA3AF;white-space:nowrap;'"
                                  x-text="s === 1 ? 'Details' : s === 2 ? 'Tasks' : 'Attachments'"></span>
                        </div>
                        {{-- Connector --}}
                        <template x-if="s < totalSteps">
                            <div :style="step > s
                                ? 'width:64px;height:2px;background:#4F46E5;border-radius:2px;margin:0 4px;margin-bottom:22px;transition:all .3s;'
                                : 'width:64px;height:2px;background:#E5E7EB;border-radius:2px;margin:0 4px;margin-bottom:22px;transition:all .3s;'"></div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── Scrollable Form Body ── --}}
        <form method="POST" action="{{ route('admin.projects.store') }}" enctype="multipart/form-data"
              id="projectWizardForm" style="display:flex;flex-direction:column;flex:1;min-height:0;">
            @csrf

            <div style="flex:1;overflow-y:auto;padding:0 28px 4px;">

                {{-- ── STEP 1: Project Details ── --}}
                <div x-show="step === 1">

                    {{-- Project Name --}}
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                            Project Name <span style="color:#EF4444;">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               x-ref="wizardName" @input="nameError = false"
                               placeholder="e.g. Mobile App Redesign"
                               :style="nameError
                                   ? 'width:100%;padding:10px 14px;border:1.5px solid #EF4444;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;background:#FEF2F2;'
                                   : 'width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;background:#fff;'"
                               onfocus="this.style.borderColor='#6366F1'" onblur="if(!this.closest('[x-data]').__x.$data.nameError) this.style.borderColor='#E5E7EB'">
                        <p x-show="nameError" style="margin:4px 0 0;font-size:11px;color:#EF4444;">
                            <i class="fa fa-circle-exclamation" style="margin-right:3px;"></i>Project name is required.
                        </p>
                    </div>

                    {{-- Brief --}}
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                            Brief <span style="font-size:11px;font-weight:400;color:#9CA3AF;margin-left:4px;">— short summary of the project goal</span>
                        </label>
                        <textarea name="description" rows="2" placeholder="What is this project about?"
                                  style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;resize:none;font-family:'Inter',sans-serif;"
                                  onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">{{ old('description') }}</textarea>
                    </div>

                    {{-- First Review Date (first) then Deadline --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:8px;">
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
                            <input type="date" name="deadline" value="{{ old('deadline') }}" required
                                   x-ref="wizardDeadline" @change="deadlineError = false"
                                   :style="deadlineError
                                       ? 'width:100%;padding:10px 14px;border:1.5px solid #EF4444;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;background:#FEF2F2;'
                                       : 'width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;'">
                            <p x-show="deadlineError" style="margin:4px 0 0;font-size:11px;color:#EF4444;">
                                <i class="fa fa-circle-exclamation" style="margin-right:3px;"></i>Deadline is required.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ── STEP 2: Tasks ── --}}
                <div x-show="step === 2">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
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
                            <div :style="(task.titleError || task.assigneeError)
                                ? 'border:1.5px solid #FCA5A5;border-radius:14px;padding:18px;background:#FAFBFF;'
                                : 'border:1.5px solid #E5E7EB;border-radius:14px;padding:18px;background:#FAFBFF;'">

                                {{-- Task header --}}
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                                    <span style="font-size:11px;font-weight:700;color:#6366F1;text-transform:uppercase;letter-spacing:.06em;background:#EEF2FF;padding:3px 10px;border-radius:20px;">
                                        Task <span x-text="i + 1"></span>
                                    </span>
                                    <button type="button" @click="removeTask(i)" x-show="tasks.length > 1"
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
                                                        :style="task.assigneeError && j === 0
                                                            ? 'width:100%;padding:7px 10px;border:1.5px solid #EF4444;border-radius:8px;font-size:12px;color:#111827;background:#FEF2F2;outline:none;box-sizing:border-box;'
                                                            : 'width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;background:#fff;outline:none;box-sizing:border-box;'">
                                                    <option value="">— Select person —</option>
                                                    <template x-for="u in allUsers" :key="u.id">
                                                        <option :value="u.id" x-text="u.name + ' (' + u.role + ') — ' + u.email"></option>
                                                    </template>
                                                </select>
                                                <input type="text" :name="'tasks['+i+'][assignees]['+j+'][role]'" x-model="assignee.role"
                                                       placeholder="Role (e.g. designer)"
                                                       style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                                <button type="button" @click="removeAssignee(i, j)" x-show="task.assignees.length > 1"
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
                                            <template x-for="u in allUsers" :key="u.id">
                                                <option :value="u.id" x-text="u.name + ' — ' + u.email"></option>
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

                                {{-- Brief / Description --}}
                                <div style="position:relative;margin-bottom:12px;">
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:4px;">
                                        Brief
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

                                {{-- Tags (at bottom) --}}
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

                {{-- ── STEP 3: Attachments & Links ── --}}
                <div x-show="step === 3">

                    {{-- Drop Zone (fixed: all styles inside :style) --}}
                    <p style="font-size:12px;font-weight:600;color:#374151;margin:0 0 10px;">
                        Files <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— max 20 MB each</span>
                    </p>
                    <div @dragover.prevent="dragover = true"
                         @dragleave.prevent="dragover = false"
                         @drop.prevent="dragover = false; handleFiles($event)"
                         @click="$refs.fileInput.click()"
                         :style="dragover
                             ? 'border:2px dashed #6366F1;border-radius:14px;padding:32px 24px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:14px;background:#EEF2FF;'
                             : 'border:2px dashed #D1D5DB;border-radius:14px;padding:32px 24px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:14px;background:#FAFAFA;'">
                        <div style="width:48px;height:48px;border-radius:12px;background:#F0F9FF;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="fas fa-cloud-arrow-up" style="font-size:20px;color:#0EA5E9;"></i>
                        </div>
                        <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 4px;">Drop files here or <span style="color:#6366F1;">browse</span></p>
                        <p style="font-size:11px;color:#9CA3AF;margin:0;">PDF, Word, Images, Video — up to 20 MB</p>
                        <input type="file" name="attachments[]" multiple x-ref="fileInput"
                               @change="handleFiles($event)" style="display:none;">
                    </div>

                    {{-- File list --}}
                    <template x-if="files.length > 0">
                        <div style="margin-bottom:16px;display:flex;flex-direction:column;gap:6px;">
                            <template x-for="(file, i) in files" :key="i">
                                <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;">
                                    <div style="width:34px;height:34px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i :class="'fas ' + fileIcon(file.name)" style="font-size:14px;color:#6366F1;"></i>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="file.name"></p>
                                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;" x-text="formatSize(file.size)"></p>
                                    </div>
                                    <button type="button" @click.stop="removeFile(i)"
                                            style="width:26px;height:26px;border-radius:7px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Links --}}
                    <div style="margin-top:8px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <p style="font-size:12px;font-weight:600;color:#374151;margin:0;">Links</p>
                            <button type="button" @click="addLink()"
                                    style="font-size:11px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:none;padding:5px 14px;border-radius:7px;cursor:pointer;display:flex;align-items:center;gap:4px;">
                                <i class="fas fa-plus" style="font-size:9px;"></i> Add Link
                            </button>
                        </div>
                        <template x-if="links.length > 0">
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                <template x-for="(link, i) in links" :key="i">
                                    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center;">
                                        <input type="url" :name="'links['+i+'][url]'" x-model="link.url"
                                               placeholder="https://..."
                                               style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                        <input type="text" :name="'links['+i+'][label]'" x-model="link.label"
                                               placeholder="Label (optional)"
                                               style="width:100%;padding:8px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
                                        <button type="button" @click="removeLink(i)"
                                                style="width:28px;height:28px;border-radius:7px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <p x-show="links.length === 0" style="font-size:12px;color:#9CA3AF;margin:0;">No links yet — click "+ Add Link".</p>
                    </div>

                </div>

            </div>{{-- end scrollable body --}}

            {{-- ── Footer ── --}}
            <div style="padding:16px 28px;border-top:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fff;border-radius:0 0 24px 24px;">

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
                            style="padding:9px 22px;background:#4F46E5;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(79,70,229,.3);">
                        Next <i class="fa fa-arrow-right" style="font-size:11px;"></i>
                    </button>
                </template>

                <template x-if="step === totalSteps">
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
@endsection

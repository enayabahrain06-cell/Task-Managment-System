@extends('layouts.app')

@section('title', 'Activities')

@section('content')

@php
    $currentAction    = request('action', '');
    $currentDateRange = request('date_range', '');
    $currentSort      = request('sort', 'newest');
    $hasFilters       = $currentAction || $currentDateRange || $currentSort !== 'newest';

    $dateRangeLabels = ['today'=>'Today','yesterday'=>'Yesterday','week'=>'This Week','month'=>'This Month'];
    $sortLabels      = ['newest'=>'Newest first','oldest'=>'Oldest first'];

    function actUrl($params) {
        $base = array_merge(request()->only(['user_id','action','date_range','sort']), $params);
        $base = array_filter($base, fn($v) => $v !== '' && $v !== null);
        return route('activities.index', $base);
    }
@endphp

<div x-data="{ releaseOpen: false }">

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Activities</h1>
        <p class="text-sm text-gray-500 mt-0.5">Latest activity feed across all teams</p>
    </div>
    <div class="flex items-center gap-2">

        {{-- ── Filters dropdown ── --}}
        <div x-data="{ open: false }" style="position:relative;">
            <button @click="open = !open" @click.outside="open = false"
                    style="display:inline-flex;align-items:center;gap:7px;padding:7px 14px;font-size:13px;font-weight:500;border-radius:9px;border:1.5px solid {{ $hasFilters ? '#6366F1' : '#E5E7EB' }};background:{{ $hasFilters ? '#EEF2FF' : '#fff' }};color:{{ $hasFilters ? '#4F46E5' : '#374151' }};cursor:pointer;transition:all .15s;white-space:nowrap;">
                <i class="fa fa-filter" style="font-size:12px;color:{{ $hasFilters ? '#6366F1' : '#9CA3AF' }};"></i>
                Filters
                @if($hasFilters)<span style="width:7px;height:7px;border-radius:50%;background:#6366F1;display:inline-block;margin-left:1px;"></span>@endif
                <i class="fa fa-chevron-down" style="font-size:10px;opacity:.5;" :style="open ? 'transform:rotate(180deg)' : ''"></i>
            </button>

            <div x-show="open" x-cloak @click.outside="open = false"
                 style="position:absolute;right:0;top:calc(100% + 6px);z-index:200;width:270px;background:#fff;border:1.5px solid #E5E7EB;border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,0.12);overflow:hidden;">

                {{-- Action type --}}
                <div style="padding:14px 16px 10px;border-bottom:1px solid #F3F4F6;">
                    <p style="font-size:10.5px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.07em;margin:0 0 10px;">Action Type</p>
                    <div style="display:flex;flex-direction:column;gap:3px;">
                        <a href="{{ actUrl(['action' => '']) }}"
                           style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:7px;font-size:12.5px;font-weight:500;text-decoration:none;transition:background .12s;{{ $currentAction === '' ? 'background:#EEF2FF;color:#4F46E5;' : 'color:#374151;' }}"
                           onmouseover="if(this.style.color!=='rgb(79, 70, 229)')this.style.background='#F9FAFB'" onmouseout="if(this.style.color!=='rgb(79, 70, 229)')this.style.background='transparent'">
                            <i class="fas fa-layer-group" style="width:14px;font-size:11px;color:#9CA3AF;"></i> All actions
                        </a>
                        @foreach($actionTypes as $action)
                        @php
                            $label = ucwords(str_replace(['_',' updated '], [' ', ' → '], $action));
                        @endphp
                        <a href="{{ actUrl(['action' => $action]) }}"
                           style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:7px;font-size:12.5px;font-weight:500;text-decoration:none;transition:background .12s;{{ $currentAction === $action ? 'background:#EEF2FF;color:#4F46E5;' : 'color:#374151;' }}"
                           onmouseover="if(this.style.color!=='rgb(79, 70, 229)')this.style.background='#F9FAFB'" onmouseout="if(this.style.color!=='rgb(79, 70, 229)')this.style.background='transparent'">
                            <i class="fas fa-circle" style="width:14px;font-size:7px;color:#9CA3AF;"></i> {{ $label }}
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Date range --}}
                <div style="padding:12px 16px 14px;">
                    <p style="font-size:10.5px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.07em;margin:0 0 10px;">Date Range</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                        @foreach([''=>'All time','today'=>'Today','yesterday'=>'Yesterday','week'=>'This Week','month'=>'This Month'] as $val => $lbl)
                        <a href="{{ actUrl(['date_range' => $val]) }}"
                           style="text-align:center;padding:6px 8px;border-radius:7px;font-size:12px;font-weight:500;text-decoration:none;border:1.5px solid {{ $currentDateRange === $val ? '#6366F1' : '#E5E7EB' }};background:{{ $currentDateRange === $val ? '#EEF2FF' : '#fff' }};color:{{ $currentDateRange === $val ? '#4F46E5' : '#374151' }};transition:all .12s;"
                           onmouseover="if(this.style.borderColor!=='rgb(99, 102, 241)')this.style.background='#F9FAFB'" onmouseout="if(this.style.borderColor!=='rgb(99, 102, 241)')this.style.background='#fff'">
                            {{ $lbl }}
                        </a>
                        @endforeach
                    </div>
                </div>

                @if($hasFilters)
                <div style="padding:10px 16px;border-top:1px solid #F3F4F6;background:#FAFAFA;">
                    <a href="{{ route('activities.index', array_filter(['user_id' => request('user_id')])) }}"
                       style="display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:600;color:#EF4444;text-decoration:none;">
                        <i class="fas fa-times-circle" style="font-size:11px;"></i> Clear all filters
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- ── Sort dropdown ── --}}
        <div x-data="{ open: false }" style="position:relative;">
            <button @click="open = !open" @click.outside="open = false"
                    style="display:inline-flex;align-items:center;gap:7px;padding:7px 14px;font-size:13px;font-weight:500;border-radius:9px;border:1.5px solid #E5E7EB;background:#fff;color:#374151;cursor:pointer;transition:all .15s;white-space:nowrap;">
                <i class="fa fa-sort" style="font-size:12px;color:#9CA3AF;"></i>
                {{ $sortLabels[$currentSort] ?? 'Newest first' }}
                <i class="fa fa-chevron-down" style="font-size:10px;opacity:.5;" :style="open ? 'transform:rotate(180deg)' : ''"></i>
            </button>

            <div x-show="open" x-cloak @click.outside="open = false"
                 style="position:absolute;right:0;top:calc(100% + 6px);z-index:200;width:190px;background:#fff;border:1.5px solid #E5E7EB;border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,0.12);overflow:hidden;padding:6px;">
                @foreach(['newest'=>['Newest first','fa-arrow-down-wide-short'],'oldest'=>['Oldest first','fa-arrow-up-wide-short']] as $val => [$lbl,$icon])
                <a href="{{ actUrl(['sort' => $val]) }}"
                   style="display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;transition:background .12s;{{ $currentSort === $val ? 'background:#EEF2FF;color:#4F46E5;' : 'color:#374151;' }}"
                   onmouseover="if(this.style.color!=='rgb(79, 70, 229)')this.style.background='#F9FAFB'" onmouseout="if(this.style.color!=='rgb(79, 70, 229)')this.style.background='transparent'">
                    <i class="fas {{ $icon }}" style="font-size:12px;{{ $currentSort === $val ? 'color:#6366F1;' : 'color:#9CA3AF;' }}"></i>
                    {{ $lbl }}
                    @if($currentSort === $val)<i class="fas fa-check" style="font-size:10px;color:#6366F1;margin-left:auto;"></i>@endif
                </a>
                @endforeach
            </div>
        </div>

        <button @click="releaseOpen=true"
                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <i class="fa fa-rocket"></i> Release
        </button>
    </div>
</div>

{{-- ══ Release Modal ══ --}}
<div x-show="releaseOpen" x-cloak style="position:fixed;inset:0;z-index:9999;">
    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;">
        <div @click="releaseOpen=false" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(3px);"></div>
        <div style="position:relative;width:100%;max-width:500px;background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,.2);overflow:hidden;">

            {{-- Header --}}
            <div style="padding:20px 24px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;">
                        <i class="fa fa-rocket" style="color:#6366F1;font-size:15px;"></i>
                    </div>
                    <div>
                        <h2 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Publish Release</h2>
                        <p style="font-size:11px;color:#9CA3AF;margin:0;">Announce a new release to the team</p>
                    </div>
                </div>
                <button @click="releaseOpen=false"
                        style="width:30px;height:30px;border-radius:50%;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('activities.release') }}" style="padding:20px 24px 24px;">
                @csrf
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Release Title <span style="color:#EF4444;">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Website Redesign Launch"
                           style="width:100%;padding:9px 12px;font-size:13px;border:1.5px solid #E5E7EB;border-radius:9px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Version <span style="font-weight:400;color:#9CA3AF;">(optional)</span></label>
                    <input type="text" name="version" placeholder="e.g. v2.1.0"
                           style="width:100%;padding:9px 12px;font-size:13px;border:1.5px solid #E5E7EB;border-radius:9px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Release Notes <span style="font-weight:400;color:#9CA3AF;">(optional)</span></label>
                    <textarea name="description" rows="4" placeholder="Describe what's included in this release…"
                              style="width:100%;padding:9px 12px;font-size:13px;border:1.5px solid #E5E7EB;border-radius:9px;outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;"
                              onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" @click="releaseOpen=false"
                            style="padding:9px 18px;font-size:13px;font-weight:600;border:1.5px solid #E5E7EB;border-radius:9px;background:#fff;color:#374151;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit"
                            style="padding:9px 20px;font-size:13px;font-weight:600;border:none;border-radius:9px;background:#6366F1;color:#fff;cursor:pointer;display:flex;align-items:center;gap:7px;">
                        <i class="fa fa-rocket" style="font-size:11px;"></i> Publish Release
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

    {{-- Left: Teams --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Teams</h3>
            @php $totalNotif = 0; @endphp
            <span class="text-xs bg-indigo-100 text-indigo-600 font-medium px-2 py-0.5 rounded-full">
                {{ $teams->flatten()->count() }} members
            </span>
        </div>

        @if($selectedUser)
        <div class="mb-3 px-1">
            <a href="{{ route('activities.index') }}" class="inline-flex items-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">
                <i class="fa fa-times-circle"></i> Clear filter
            </a>
        </div>
        @endif

        <div class="space-y-2">
            @php $teamColors = ['manager' => '#6366F1', 'user' => '#10B981']; @endphp
            @foreach($teams as $role => $members)
            <div class="mb-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-1">{{ ucfirst($role) }} Team</p>
                @foreach($members as $member)
                @php $isActive = $selectedUser && $selectedUser->id === $member->id; @endphp
                <a href="{{ route('activities.index', ['user_id' => $member->id]) }}"
                   class="flex items-center gap-2 p-2 rounded-lg transition {{ $isActive ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                         style="background:{{ $teamColors[$role] ?? '#6366F1' }}">
                        {{ strtoupper(substr($member->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium {{ $isActive ? 'text-indigo-700' : 'text-gray-800' }} truncate">{{ $member->name }}</p>
                        <p class="text-xs text-gray-400">{{ $member->tasks_count }} tasks</p>
                    </div>
                    <span class="w-2 h-2 bg-emerald-400 rounded-full flex-shrink-0"></span>
                </a>
                @endforeach
            </div>
            @endforeach

            @if($teams->flatten()->count() === 0)
            <p class="text-sm text-gray-400 text-center py-6">No team members</p>
            @endif
        </div>
    </div>

    {{-- Right: Activity Feed --}}
    <div class="lg:col-span-3 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h3 class="font-semibold text-gray-900">Latest Activity Feed</h3>
                @if($selectedUser)
                <span class="inline-flex items-center gap-1.5 text-xs bg-indigo-100 text-indigo-700 font-medium px-2.5 py-1 rounded-full">
                    <span class="w-5 h-5 rounded-full bg-indigo-500 text-white flex items-center justify-center text-[10px] font-bold">
                        {{ strtoupper(substr($selectedUser->name, 0, 1)) }}
                    </span>
                    {{ $selectedUser->name }}
                </span>
                @endif
            </div>
            <span class="text-xs text-gray-400">{{ $activities->total() }} {{ $selectedUser ? 'activities' : 'total activities' }}</span>
        </div>

        @php
            $lastDate = null;
            $actionIcons = [
                'status_updated_completed'   => ['icon' => 'fa-check-circle', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50'],
                'status_updated_in_progress' => ['icon' => 'fa-spinner',      'color' => 'text-amber-500',   'bg' => 'bg-amber-50'],
                'status_updated_pending'     => ['icon' => 'fa-clock',        'color' => 'text-gray-400',    'bg' => 'bg-gray-50'],
                'release_published'          => ['icon' => 'fa-rocket',       'color' => 'text-indigo-500',  'bg' => 'bg-indigo-50'],
            ];
            $colors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6'];
        @endphp

        <div class="divide-y divide-gray-50 max-h-[600px] overflow-y-auto">
            @forelse($activities as $i => $log)
            @php
                $dateStr = $log->created_at->isToday() ? 'Today' : ($log->created_at->isYesterday() ? 'Yesterday' : $log->created_at->format('M d, Y'));
                $style   = $actionIcons[$log->action] ?? ['icon' => 'fa-bolt', 'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50'];
                $color   = $colors[$log->user_id % count($colors)];
                $actionText = match($log->action) {
                    'status_updated_completed'   => 'marked task as completed',
                    'status_updated_in_progress' => 'started working on task',
                    'status_updated_pending'     => 'moved task to pending',
                    'release_published'          => 'published a new release',
                    default => str_replace(['status_updated_', '_'], ['updated status to ', ' '], $log->action),
                };
                $releaseData = ($log->action === 'release_published' && $log->note)
                    ? json_decode($log->note, true)
                    : null;
            @endphp

            @if($lastDate !== $dateStr)
            @php $lastDate = $dateStr; @endphp
            <div class="px-5 py-2 bg-gray-50">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $dateStr }}</p>
            </div>
            @endif

            {{-- ── Special: Release card ── --}}
            @if($releaseData)
            <div class="px-5 py-4 bg-gradient-to-r from-indigo-50 to-purple-50 border-l-4 border-indigo-400">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                         style="background:{{ $color }}">
                        {{ strtoupper(substr($log->user->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-gray-900 text-sm">{{ $log->user->name ?? 'Unknown' }}</span>
                            <span class="text-xs text-gray-500">published a release</span>
                            @if(!empty($releaseData['version']))
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                                {{ $releaseData['version'] }}
                            </span>
                            @endif
                            <span class="text-xs text-gray-400 ml-auto">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm font-semibold text-indigo-800 mt-1">🚀 {{ $releaseData['title'] }}</p>
                        @if(!empty($releaseData['description']))
                        <p class="text-xs text-gray-600 mt-1 whitespace-pre-line">{{ $releaseData['description'] }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @continue
            @endif

            @php
                $initReactions = $log->reactions->groupBy('emoji')->map(fn($g) => $g->count());
                $myReactions   = $log->reactions->where('user_id', auth()->id())->pluck('emoji')->toArray();
                $initReplies   = $log->replies->map(fn($r) => [
                    'id'         => $r->id,
                    'body'       => $r->body,
                    'user'       => $r->user->name ?? '?',
                    'initial'    => strtoupper(substr($r->user->name ?? '?', 0, 1)),
                    'time'       => $r->created_at->diffForHumans(),
                    'mine'       => $r->user_id === auth()->id(),
                    'delete_url' => route('activities.reply.delete', $r),
                ]);
                $taskUrl = null;
                if ($log->task) {
                    $taskUrl = in_array(auth()->user()->role, ['admin','manager'])
                        ? route('admin.tasks.show', $log->task)
                        : route('user.tasks.show', $log->task);
                }
            @endphp
            <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50/70 transition {{ $taskUrl ? 'cursor-pointer' : '' }}"
                 @if($taskUrl) @click="if(!$event.target.closest('[data-no-nav]')) window.location='{{ $taskUrl }}'" @endif
                 x-data="{
                    showPicker: false,
                    showReply:  false,
                    replyText:  '',
                    submitting: false,
                    reactions:  {{ Js::from($initReactions) }},
                    myReactions:{{ Js::from($myReactions) }},
                    replies:    {{ Js::from($initReplies) }},
                    emojis: ['👍','❤️','😄','😮','🎉','🔥'],
                    async toggleReact(emoji) {
                        this.showPicker = false;
                        const res = await fetch('{{ route('activities.react', $log) }}', {
                            method:'POST',
                            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                            body: JSON.stringify({emoji})
                        });
                        const d = await res.json();
                        this.reactions = d.counts;
                        if (d.reacted) { if (!this.myReactions.includes(emoji)) this.myReactions.push(emoji); }
                        else { this.myReactions = this.myReactions.filter(e => e !== emoji); }
                    },
                    async submitReply() {
                        if (!this.replyText.trim() || this.submitting) return;
                        this.submitting = true;
                        const res = await fetch('{{ route('activities.reply', $log) }}', {
                            method:'POST',
                            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                            body: JSON.stringify({body: this.replyText.trim()})
                        });
                        const d = await res.json();
                        this.replies.push(d);
                        this.replyText = '';
                        this.submitting = false;
                    },
                    async deleteReply(r) {
                        await fetch(r.delete_url, {method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});
                        this.replies = this.replies.filter(x => x.id !== r.id);
                    }
                 }">

                {{-- Time --}}
                <span class="text-xs text-gray-400 w-10 flex-shrink-0 mt-0.5 pt-1">{{ $log->created_at->format('H:i') }}</span>

                {{-- Avatar --}}
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                     style="background:{{ $color }}">
                    {{ strtoupper(substr($log->user->name ?? 'U', 0, 1)) }}
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800">
                        <span class="font-semibold text-gray-900">{{ $log->user->name ?? 'Unknown' }}</span>
                        {{ $actionText }}
                        @if($log->task)
                        @if($taskUrl)
                        <a href="{{ $taskUrl }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline" data-no-nav>#{{ $log->task->title }}</a>
                        @else
                        <span class="font-medium text-indigo-600">#{{ $log->task->title }}</span>
                        @endif
                        @if($log->task->project)
                        in <span class="font-medium text-gray-700">{{ $log->task->project->name }}</span>
                        @endif
                        @endif
                    </p>

                    @if($log->note)
                    <div class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-100 text-sm text-gray-600">
                        {{ $log->note }}
                    </div>
                    @endif

                    {{-- Existing reaction badges --}}
                    <div class="flex flex-wrap gap-1 mt-2" data-no-nav>
                        <template x-for="(count, emoji) in reactions" :key="emoji">
                            <button @click="toggleReact(emoji)"
                                    :class="myReactions.includes(emoji) ? 'bg-indigo-100 text-indigo-700 border-indigo-300' : 'bg-gray-100 text-gray-600 border-gray-200'"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-xs font-medium transition hover:scale-105">
                                <span x-text="emoji"></span><span x-text="count"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Action bar --}}
                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-400" data-no-nav>
                        {{-- React button + picker --}}
                        <div style="position:relative;">
                            <button @click="showPicker=!showPicker" class="hover:text-indigo-600 transition flex items-center gap-1">
                                <i class="fa fa-smile"></i> React
                            </button>
                            <div x-show="showPicker" @click.outside="showPicker=false" x-transition
                                 style="position:absolute;bottom:calc(100% + 6px);left:0;background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:50;display:flex;gap:4px;">
                                <template x-for="e in emojis" :key="e">
                                    <button @click="toggleReact(e)"
                                            :class="myReactions.includes(e) ? 'bg-indigo-100 scale-110' : 'hover:bg-gray-100'"
                                            class="text-lg rounded-lg p-1 transition transform hover:scale-125" x-text="e"></button>
                                </template>
                            </div>
                        </div>

                        {{-- Reply toggle --}}
                        <button @click="showReply=!showReply" class="hover:text-indigo-600 transition flex items-center gap-1">
                            <i class="fa fa-reply"></i> Reply
                        </button>

                        <span class="flex items-center gap-1">
                            <i class="fa fa-clock"></i> {{ $log->created_at->diffForHumans() }}
                        </span>

                        @if($taskUrl)
                        <a href="{{ $taskUrl }}" data-no-nav
                           class="ml-auto flex items-center gap-1 text-indigo-500 hover:text-indigo-700 font-medium transition">
                            View task <i class="fa fa-arrow-right" style="font-size:10px;"></i>
                        </a>
                        @endif
                    </div>

                    {{-- Existing replies --}}
                    <div class="mt-3 flex flex-col gap-2" data-no-nav>
                        <template x-for="r in replies" :key="r.id">
                            <div class="flex items-start gap-2 group/reply">
                                <div class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                     x-text="r.initial"></div>
                                <div class="flex-1 bg-gray-50 rounded-xl px-3 py-2 border border-gray-100">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-semibold text-gray-700" x-text="r.user"></span>
                                        <div class="flex items-center gap-2 text-xs text-gray-400">
                                            <span x-text="r.time"></span>
                                            <button x-show="r.mine" @click="deleteReply(r)"
                                                    class="opacity-0 group-hover/reply:opacity-100 text-red-400 hover:text-red-600 transition">
                                                <i class="fa fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-0.5" x-text="r.body"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Reply input --}}
                    <div x-show="showReply" x-transition class="mt-3 flex items-center gap-2" data-no-nav>
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                             style="background:#6366F1">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <div class="flex-1 flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 focus-within:border-indigo-400 transition">
                            <input x-model="replyText"
                                   @keydown.enter.prevent="submitReply()"
                                   @keydown.escape="showReply=false; replyText=''"
                                   type="text" placeholder="Write a reply… (Enter to send)"
                                   class="flex-1 bg-transparent text-xs text-gray-700 outline-none placeholder-gray-400">
                            <button @click="submitReply()" :disabled="submitting || !replyText.trim()"
                                    class="text-indigo-500 hover:text-indigo-700 disabled:opacity-40 transition">
                                <i class="fa fa-paper-plane text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Activity icon --}}
                <div class="w-8 h-8 {{ $style['bg'] }} rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fa {{ $style['icon'] }} {{ $style['color'] }} text-sm"></i>
                </div>
            </div>
            @empty
            <div class="px-5 py-16 text-center">
                <i class="fa fa-bolt text-5xl text-gray-200 mb-3"></i>
                <p class="text-gray-400">No activity recorded yet</p>
                <p class="text-xs text-gray-400 mt-1">Activity will appear here when team members update tasks</p>
            </div>
            @endforelse
        </div>

        @if($activities->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
            {{ $activities->links() }}
        </div>
        @endif
    </div>

</div>

</div>{{-- /x-data releaseOpen --}}
@endsection

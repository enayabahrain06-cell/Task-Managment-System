@extends('layouts.mobile')

{{-- resources/views/mobile/team/index.blade.php --}}
{{-- Admin sees Team + Projects; clients get the Projects half only (tab label "Projects"). --}}
{{-- Vars: $members (name, initial, open_tasks, capacity, note), $projects (name, pct, color, meta) --}}

@section('title', 'Team')

@section('mobile-content')

@php
    $warn     = '#F59E0B';
    $warnText = '#B45309';
    $success  = '#34C77B';
    $ringC    = 2 * M_PI * 14;
@endphp

@if ($isAdmin)
    <h3 class="px-5 pb-3 text-[15px] font-bold tracking-[-0.01em] text-[var(--mob-ink)]">Workload this week</h3>

    {{-- WORKLOAD — one card, rows inside --}}
    @php $workloadMax = $members->max('open_tasks') ?: 1; @endphp
    <section class="mx-4 bg-white border border-[var(--mob-line)] rounded-[18px] p-4
                    flex flex-col gap-[15px] shadow-sm">
        @foreach ($members as $m)
            @php
                $pct = min(100, round($m->open_tasks / $workloadMax * 100));
                $hot = $m->note === 'Busiest right now';
            @endphp
            <div class="flex items-center gap-[11px]">
                @if ($m->avatarUrl())
                <img src="{{ $m->avatarUrl() }}" alt="{{ $m->name }}"
                     class="w-[34px] h-[34px] shrink-0 rounded-full object-cover">
                @else
                <span class="w-[34px] h-[34px] shrink-0 rounded-full text-white text-[13px] font-bold
                             flex items-center justify-center"
                      style="background:var(--mob-brand-grad)">{{ $m->initial }}</span>
                @endif

                <span class="flex-1 min-w-0">
                    <span class="flex items-baseline justify-between gap-2">
                        <span class="text-[13.5px] font-semibold text-[var(--mob-ink)] truncate">{{ $m->name }}</span>
                        <span class="text-[12px] font-bold text-[var(--mob-ink-2)] tabular-nums">{{ $m->open_tasks }} {{ Str::plural('task', $m->open_tasks) }}</span>
                    </span>

                    <span class="block h-[6px] rounded-[3px] bg-[var(--mob-line)] mt-[7px] overflow-hidden">
                        <span class="block h-full rounded-[3px]"
                              style="width:{{ $pct }}%; background:{{ $hot ? $warn : 'var(--mob-brand-accent)' }};"></span>
                    </span>

                    <span class="block text-[11px] font-bold mt-1.5" style="color:{{ $hot ? $warnText : 'var(--mob-ink-3)' }}">{{ $m->note }}</span>
                </span>
            </div>
        @endforeach
    </section>
@endif

@php $projectsDone = $projects->where('pct', 100)->count(); @endphp
<div class="flex items-baseline justify-between px-5 pt-[22px] pb-3">
    <h3 class="text-[15px] font-bold tracking-[-0.01em] text-[var(--mob-ink)]">Projects</h3>
    <span class="text-[12px] font-bold text-[var(--mob-ink-3)]">{{ $projectsDone }} of {{ $projects->count() }} complete</span>
</div>

{{-- PROJECT CARD — one card, divided rows, each with a progress ring --}}
<div class="mx-4 bg-white border border-[var(--mob-line)] rounded-[18px] shadow-sm overflow-hidden">
    @foreach ($projects as $p)
        @php
            $dash      = round($p->pct / 100 * $ringC, 2);
            $ringColor = $p->pct === 100 ? $success : 'var(--mob-brand-accent)';
            $pctColor  = $p->pct === 100 ? $success : ($p->pct === 0 ? $warnText : 'var(--mob-ink)');
            $subColor  = $p->pct === 0 ? $warnText : 'var(--mob-ink-3)';
        @endphp
        <a href="{{ route('mobile.tasks', ['project' => $p->id]) }}"
           class="flex items-center gap-3 px-4 py-[13px] min-h-[44px] {{ $loop->last ? '' : 'border-b border-[var(--mob-line)]' }}">
            <svg width="34" height="34" viewBox="0 0 34 34" style="flex-shrink:0;transform:rotate(-90deg)">
                <circle cx="17" cy="17" r="14" fill="none" stroke="#EFEFF5" stroke-width="4"></circle>
                <circle cx="17" cy="17" r="14" fill="none" stroke="{{ $ringColor }}" stroke-width="4"
                        stroke-linecap="round" stroke-dasharray="{{ $dash }} {{ round($ringC, 2) }}"></circle>
            </svg>

            <span class="flex-1 min-w-0">
                <span class="block text-[14px] font-semibold text-[var(--mob-ink)] tracking-[-0.01em] truncate">{{ $p->name }}</span>
                <span class="block text-[12px] font-semibold mt-0.5" style="color:{{ $subColor }}">{{ $p->meta }}</span>
            </span>

            <span class="text-[13px] font-bold tabular-nums shrink-0" style="color:{{ $pctColor }}">{{ $p->pct }}%</span>
        </a>
    @endforeach
</div>

@endsection

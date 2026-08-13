@extends('layouts.mobile')

{{-- resources/views/mobile/team/index.blade.php --}}
{{-- Admin sees Team + Projects; clients get the Projects half only (tab label "Projects"). --}}
{{-- Vars: $members (name, initial, open_tasks, capacity, note), $projects (name, pct, color, meta) --}}

@section('title', 'Team')

@section('mobile-content')

@if ($isAdmin)
    <h3 class="px-5 pb-3 text-[15px] font-bold tracking-[-0.01em] text-[var(--mob-ink)]">Workload this week</h3>

    {{-- WORKLOAD — one card, rows inside --}}
    <section class="mx-4 bg-white border border-[var(--mob-line)] rounded-[18px] p-4
                    flex flex-col gap-[15px] shadow-sm">
        @foreach ($members as $m)
            @php
                $cap  = $m->capacity ?: 40;
                $pct  = min(100, round($m->open_tasks / $cap * 100));
                $over = $m->open_tasks > $cap * 0.85;
            @endphp
            <div class="flex items-center gap-[11px]">
                <span class="w-[34px] h-[34px] shrink-0 rounded-full text-white text-[13px] font-bold
                             flex items-center justify-center"
                      style="background:var(--mob-brand-grad)">{{ $m->initial }}</span>

                <span class="flex-1 min-w-0">
                    <span class="flex items-baseline justify-between gap-2">
                        <span class="text-[13.5px] font-semibold text-[var(--mob-ink)] truncate">{{ $m->name }}</span>
                        <span class="text-[12px] font-bold text-[var(--mob-ink-2)] tabular-nums">{{ $m->open_tasks }} tasks</span>
                    </span>

                    <span class="block h-[7px] rounded-full bg-gray-100 mt-[7px] overflow-hidden">
                        <span class="block h-full rounded-full"
                              style="width:{{ $pct }}%;
                                     {{ $over
                                        ? 'background:var(--mob-brand-grad);opacity:1'
                                        : 'background:var(--mob-brand-accent);opacity:.75' }}"></span>
                    </span>

                    <span class="block text-[11px] font-medium text-[var(--mob-ink-3)] mt-1.5">{{ $m->note }}</span>
                </span>
            </div>
        @endforeach
    </section>
@endif

<h3 class="px-5 pt-[22px] pb-3 text-[15px] font-bold tracking-[-0.01em] text-[var(--mob-ink)]">Projects</h3>

{{-- PROJECT CARDS --}}
<div class="flex flex-col gap-[9px] px-4 pb-1">
    @foreach ($projects as $p)
        <a href="{{ route('mobile.tasks', ['project' => $p->id]) }}"
           class="block bg-white border border-[var(--mob-line)] rounded-2xl p-3.5 shadow-sm min-h-[44px]">
            <div class="flex items-center gap-[9px]">
                <span class="w-[9px] h-[9px] rounded-[3px] shrink-0" style="background:{{ $p->color }}"></span>
                <span class="flex-1 text-[14px] font-semibold text-[var(--mob-ink)] tracking-[-0.01em] truncate">{{ $p->name }}</span>
                <span class="text-[12px] font-bold text-[var(--mob-ink-2)] tabular-nums">{{ $p->pct }}%</span>
            </div>

            <div class="h-[7px] rounded-full bg-gray-100 mt-[11px] overflow-hidden">
                <span class="block h-full rounded-full"
                      style="width:{{ $p->pct }}%;background:{{ $p->color }};opacity:.85"></span>
            </div>

            <div class="text-[11.5px] font-medium text-[var(--mob-ink-3)] mt-[9px]">{{ $p->meta }}</div>
        </a>
    @endforeach
</div>

@endsection

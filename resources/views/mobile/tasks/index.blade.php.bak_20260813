@extends('layouts.mobile')

{{-- resources/views/mobile/tasks/index.blade.php --}}
{{-- Vars: $filters, $activeFilter, $activeLabel, $tasks, $totalCount --}}

@section('title', 'Tasks')

@section('mobile-content')

{{-- FILTER SEGMENTS — horizontal scroll, selected = brand fill --}}
<div class="flex gap-2 px-4 pt-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
    @foreach ($filters as $f)
        @php $on = $f['key'] === $activeFilter; @endphp
        <a href="{{ route('mobile.tasks', ['filter' => $f['key']]) }}"
           class="shrink-0 min-h-[44px] inline-flex items-center px-3.5 rounded-[11px] border
                  text-[12.5px] font-semibold whitespace-nowrap"
           style="{{ $on
                ? 'background:var(--mob-brand);border-color:var(--mob-brand);color:#fff'
                : 'background:#fff;border-color:#E1E4EA;color:var(--mob-ink-2)' }}">{{ $f['label'] }}</a>
    @endforeach
</div>

{{-- COUNT LINE --}}
<div class="px-5 pt-3.5 pb-2 text-[12px] font-semibold text-[var(--mob-ink-3)]">
    {{ trans_choice(':count task|:count tasks', $totalCount) }} · {{ strtolower($activeLabel) }}
    @if ($totalCount > $tasks->count())
        <span class="font-normal">(showing {{ $tasks->count() }})</span>
    @endif
</div>

@if ($tasks->isEmpty())
    <div class="m-4 px-5 py-[34px] text-center bg-white border border-dashed border-gray-300 rounded-[18px]">
        <div class="text-[14.5px] font-semibold text-[var(--mob-ink)]">Nothing here</div>
        <div class="text-[12.5px] text-[var(--mob-ink-3)] mt-1.5">Clear queue for this filter.</div>
    </div>
@else
    {{-- TASK ROWS — 1px hairlines via bg gap, status rail on the left --}}
    <div class="mx-4 rounded-[18px] overflow-hidden border border-[var(--mob-line)]
                divide-y divide-[var(--mob-line)]">
        @foreach ($tasks as $task)
            <a href="{{ route('mobile.tasks.show', $task) }}"
               class="flex items-center gap-3 bg-white px-3.5 py-3.5 min-h-[68px] active:bg-[#FAFBFF]">

                <x-status-rail :status="$task->status" />

                <span class="flex-1 min-w-0">
                    <span class="block text-[14.5px] font-semibold text-[var(--mob-ink)]
                                 tracking-[-0.012em] truncate">{{ $task->title }}</span>
                    <span class="flex items-center gap-[7px] mt-1.5">
                        <x-status-chip :status="$task->status" />
                        <span class="text-[11.5px] font-medium text-[var(--mob-ink-3)] max-w-[13ch] truncate">{{ $task->project->name ?? 'Quick Tasks' }}</span>
                    </span>
                </span>

                <span class="flex flex-col items-end gap-1.5 shrink-0">
                    <span class="text-[11.5px] font-bold tabular-nums {{ $task->isOverdue() ? 'text-red-600' : 'text-[var(--mob-ink-3)]' }}">
                        {{ $task->deadline?->format('M d') ?? '—' }}
                    </span>
                    <span class="w-[22px] h-[22px] rounded-full text-white text-[9px] font-bold
                                 flex items-center justify-center"
                          style="background:var(--mob-brand-grad)">{{ $task->assignee->initial ?? '?' }}</span>
                </span>
            </a>
        @endforeach
    </div>
@endif

@endsection

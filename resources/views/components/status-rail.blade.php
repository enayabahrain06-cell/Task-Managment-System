@props(['status'])
@php $c = \App\Support\TaskStatusColors::for($status); @endphp
<span {{ $attributes->merge(['class' => 'w-[3px] self-stretch min-h-[34px] rounded-full opacity-85 shrink-0']) }}
      style="background:{{ $c['text'] }}"></span>

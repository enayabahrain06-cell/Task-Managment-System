@props(['status'])
@php $c = \App\Support\TaskStatusColors::for($status); @endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap']) }}
      style="color:{{ $c['text'] }};background:{{ $c['bg'] }}">{{ $c['label'] }}</span>

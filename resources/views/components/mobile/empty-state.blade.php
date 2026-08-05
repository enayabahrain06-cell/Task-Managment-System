@props(['title', 'sub' => null, 'icon' => 'fa-inbox'])
<div {{ $attributes->merge(['class' => 'uds-empty']) }}>
    <i class="fas {{ $icon }}" style="font-size:22px;color:#D1D5DB;margin-bottom:10px;display:block;"></i>
    <p class="uds-empty-title">{{ $title }}</p>
    @if($sub)
        <p class="uds-empty-sub">{{ $sub }}</p>
    @endif
</div>

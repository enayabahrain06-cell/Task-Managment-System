@props(['href', 'status' => null, 'title', 'meta' => null])
{{--
    Dense list row: status rail + title + meta line + right-hand slot (date / avatar).
    Wrap a run of these in <div class="uds-list">...</div> for the 1px-hairline container.
--}}
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'uds-list-row']) }}>
    @if($status)
        <x-status-rail :status="$status" />
    @endif
    <div style="flex:1;min-width:0;">
        <div class="uds-list-title">{{ $title }}</div>
        @if($meta)
            <div class="uds-list-meta">{{ $meta }}</div>
        @endif
    </div>
    @isset($right)
        <div style="flex-shrink:0;">{{ $right }}</div>
    @endisset
</a>

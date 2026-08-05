@props(['label', 'count', 'pct', 'color' => '#4F46E5', 'dot' => true])
{{-- One row of the "Bars" pattern: count/% on one line, track+fill underneath. --}}
<div {{ $attributes }}>
    <div style="display:flex;align-items:center;gap:8px;">
        @if($dot)
            <span style="width:7px;height:7px;border-radius:50%;background:{{ $color }};flex-shrink:0;"></span>
        @endif
        <span style="flex:1;font-size:12px;font-weight:600;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $label }}</span>
        <span style="font-size:12px;font-weight:700;color:#111827;font-variant-numeric:tabular-nums;">{{ $count }}</span>
        <span style="width:38px;text-align:right;font-size:11.5px;font-weight:600;color:#9CA3AF;font-variant-numeric:tabular-nums;">{{ $pct }}%</span>
    </div>
    <div class="uds-track" style="margin-top:5px;">
        <span class="uds-track-fill" style="width:{{ $pct }}%;background:{{ $color }};"></span>
    </div>
</div>

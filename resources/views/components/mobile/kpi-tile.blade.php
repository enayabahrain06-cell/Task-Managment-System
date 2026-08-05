@props(['label', 'value', 'sub' => null, 'money' => false])
{{--
    One KPI tile for the 2-col grid. White card, dark numeral — never a colored gradient.
    Pass the `money` boolean prop for currency values (e.g. "BHD 1,234.000") — renders at
    18px via the shared .uds-kpi-value.is-money modifier instead of the default 25px so
    longer currency strings don't overflow the tile.
--}}
<div {{ $attributes->merge(['class' => 'uds-kpi-tile']) }}>
    <div class="uds-eyebrow">{{ $label }}</div>
    <div class="uds-kpi-value {{ $money ? 'is-money' : '' }}">{{ $value }}</div>
    @if($sub)
        <div class="uds-kpi-sub">{{ $sub }}</div>
    @endif
</div>

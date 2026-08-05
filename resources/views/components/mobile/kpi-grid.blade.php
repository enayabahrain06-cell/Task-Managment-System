{{-- 2-column KPI grid wrapper — see kpi-tile.blade.php for the tiles inside it. --}}
<div {{ $attributes->merge(['class' => 'uds-kpi-grid']) }}>
    {{ $slot }}
</div>

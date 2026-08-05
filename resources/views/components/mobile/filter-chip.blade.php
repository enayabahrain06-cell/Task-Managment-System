@props(['label', 'active' => false])
{{--
    Filter chip wrapping a native <select> — the slot IS the <select> element, so the
    caller controls its name/options/optgroups/onchange exactly as on desktop. The select
    is absolutely positioned with opacity:0 over the chip so tapping anywhere opens the
    OS picker. Mobile-only; desktop keeps its own full <select> UI untouched.

    Usage:
        <x-mobile.filter-chip :label="$selectedProject?->name ?? 'All projects'">
            <select name="project_id" onchange="this.form.submit()"> ... </select>
        </x-mobile.filter-chip>
--}}
<label {{ $attributes->merge(['class' => 'uds-chip ' . ($active ? 'is-active' : '')]) }}>
    <span>{{ $label }}</span>
    <i class="fas fa-chevron-down"></i>
    {{ $slot }}
</label>

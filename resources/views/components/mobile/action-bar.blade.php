{{--
    Sticky bottom action bar (page-level primary + secondary). Fixed above the global
    bottom tab bar, respects the safe-area inset. Pair with .uds-btn-ghost (secondary)
    then .uds-btn-primary (flex:1) inside the slot. The page needs matching bottom
    padding (e.g. `.app-content{padding-bottom:110px!important}` in that page's own
    mobile media query) so the last section isn't hidden behind the bar.
--}}
<div {{ $attributes->merge(['class' => 'uds-actionbar no-print']) }}>
    {{ $slot }}
</div>

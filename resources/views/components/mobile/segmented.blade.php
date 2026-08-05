@props(['options', 'active'])
{{--
    Segmented control (ranges, buckets, Active/Completed, any 2-5 exclusive options).
    Each $options[] item: ['key', 'label', 'count' => optional, 'href' => optional, 'onclick' => optional].
    - If an option has 'href', it renders as a link (page-navigation tabs).
    - Else it renders as a <button onclick="..."> (JS-driven, e.g. submit a form field).
    Mobile-only markup — desktop pages keep their own tab/filter UI untouched.
--}}
<div {{ $attributes->merge(['class' => 'uds-seg']) }}>
    @foreach($options as $opt)
        @php $isOn = (string) ($opt['key'] ?? null) === (string) $active; @endphp
        @if(!empty($opt['href']))
            <a href="{{ $opt['href'] }}" class="uds-seg-opt {{ $isOn ? 'is-on' : '' }}">
                {{ $opt['label'] }}
                @isset($opt['count'])
                    <span class="uds-seg-count">{{ $opt['count'] }}</span>
                @endisset
            </a>
        @else
            <button type="button" onclick="{{ $opt['onclick'] ?? '' }}" class="uds-seg-opt {{ $isOn ? 'is-on' : '' }}">
                {{ $opt['label'] }}
                @isset($opt['count'])
                    <span class="uds-seg-count">{{ $opt['count'] }}</span>
                @endisset
            </button>
        @endif
    @endforeach
</div>

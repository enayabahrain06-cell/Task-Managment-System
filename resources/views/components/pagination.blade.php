@props(['paginator', 'mt' => '16px'])
@if($paginator->hasPages())
<div style="margin-top:{{ $mt }};display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <span style="font-size:12px;color:#6B7280;">
        Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} results
    </span>
    <div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap;">
        @if($paginator->onFirstPage())
            <span style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#F3F4F6;color:#D1D5DB;cursor:default;">‹ Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#F3F4F6;color:#374151;text-decoration:none;">‹ Prev</a>
        @endif

        @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if($page == $paginator->currentPage())
                <span style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;background:#4F46E5;color:#fff;min-width:34px;text-align:center;display:inline-block;">{{ $page }}</span>
            @else
                <a href="{{ $url }}" style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#F3F4F6;color:#374151;text-decoration:none;min-width:34px;text-align:center;display:inline-block;">{{ $page }}</a>
            @endif
        @endforeach

        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#F3F4F6;color:#374151;text-decoration:none;">Next ›</a>
        @else
            <span style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#F3F4F6;color:#D1D5DB;cursor:default;">Next ›</span>
        @endif
    </div>
</div>
@endif

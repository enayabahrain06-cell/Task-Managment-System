@extends('layouts.app')
@section('title', 'Alerts')

@section('content')
{{-- Mobile-only: bring action buttons up to the app's 44px touch-target standard
     (matches /admin/dashboard and /activities); desktop sizing is untouched. --}}
<style>
@media (max-width: 768px) {
    .alert-action-btn { min-height: 44px; padding-top: 10px !important; padding-bottom: 10px !important; }
}
</style>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <div>
        <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Alerts</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">{{ $notifications->total() }} {{ Str::plural('notification', $notifications->total()) }}</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <form method="POST" action="{{ route('notifications.mark-all-read') }}">
            @csrf
            <button type="submit" class="alert-action-btn" style="font-size:12.5px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:none;border-radius:8px;padding:8px 14px;cursor:pointer;">Mark all read</button>
        </form>
        <form method="POST" action="{{ route('notifications.clear-all') }}" onsubmit="return confirm('Clear all notifications?')">
            @csrf
            <button type="submit" class="alert-action-btn" style="font-size:12.5px;font-weight:600;color:#DC2626;background:#FEF2F2;border:none;border-radius:8px;padding:8px 14px;cursor:pointer;">Clear all</button>
        </form>
    </div>
</div>

<div style="display:flex;flex-direction:column;gap:9px;">
    @php
        $alertPalettes = ['indigo' => ['#EEF2FF', '#4F46E5'], 'green' => ['#F0FDF4', '#16A34A'], 'red' => ['#FEF2F2', '#DC2626'], 'amber' => ['#FFFBEB', '#D97706']];
    @endphp
    @forelse($notifications as $n)
    @php
        $nData = $n->data;
        [$abg, $aico] = $alertPalettes[$nData['color'] ?? 'indigo'] ?? $alertPalettes['indigo'];
    @endphp
    <a href="{{ route('notifications.read', $n->id) }}"
       style="display:flex;gap:12px;background:#fff;border:1px solid #EDEFF3;border-radius:16px;padding:14px;text-decoration:none;box-shadow:0 1px 2px rgba(17,24,39,.04);min-height:44px;">
        <span style="width:38px;height:38px;flex-shrink:0;border-radius:12px;display:flex;align-items:center;justify-content:center;background:{{ $abg }};">
            <i class="fas {{ $nData['icon'] ?? 'fa-bell' }}" style="font-size:15px;color:{{ $aico }};"></i>
        </span>
        <span style="flex:1;min-width:0;">
            <span style="display:block;font-size:13.5px;font-weight:700;color:#111827;line-height:1.4;">{{ $nData['title'] ?? '' }}</span>
            <span style="display:block;font-size:12px;color:#6B7280;margin-top:2px;line-height:1.5;">{{ $nData['message'] ?? '' }}</span>
            <span style="display:block;font-size:11px;font-weight:500;color:#9CA3AF;margin-top:6px;">{{ $n->created_at->diffForHumans() }}</span>
        </span>
        @unless($n->read_at)
        <span style="width:8px;height:8px;border-radius:99px;background:var(--mob-brand);flex-shrink:0;margin-top:6px;"></span>
        @endunless
    </a>
    @empty
    <div style="text-align:center;padding:56px 20px;background:#fff;border:1px solid #EDEFF3;border-radius:16px;">
        <div style="width:48px;height:48px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <i class="fas fa-bell-slash" style="color:#D1D5DB;font-size:20px;"></i>
        </div>
        <p style="font-size:13.5px;font-weight:600;color:#374151;margin:0;">No alerts</p>
        <p style="font-size:12px;color:#9CA3AF;margin:4px 0 0;">You're all caught up.</p>
    </div>
    @endforelse
</div>

<div style="margin-top:16px;">
    <x-pagination :paginator="$notifications" />
</div>
@endsection

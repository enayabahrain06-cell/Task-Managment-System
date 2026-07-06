{{-- Reusable renewal/payment history card — used by both admin and user domain show pages --}}
@php
    $lastRenewal = $renewalHistory->firstWhere('action', 'renewed');
@endphp
<div class="info-card" style="margin-top:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div style="font-size:13px;font-weight:700;color:#374151;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-clock-rotate-left" style="color:#6366F1;font-size:13px;"></i>
            Renewal &amp; Payment History
            @if($renewalHistory->isNotEmpty())
            <span style="background:#EEF2FF;color:#4F46E5;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;">{{ $renewalHistory->count() }}</span>
            @endif
        </div>
    </div>

    @if($lastRenewal)
    <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-circle-check" style="color:#16A34A;font-size:14px;"></i>
        <div>
            <div style="font-size:13px;font-weight:700;color:#16A34A;">Last renewed {{ $lastRenewal->created_at->diffForHumans() }}</div>
            <div style="font-size:11.5px;color:#059669;opacity:.85;">{{ $lastRenewal->created_at->format('d M Y, H:i') }} by {{ $lastRenewal->actor?->name ?? 'System' }}</div>
        </div>
    </div>
    @endif

    @if($renewalHistory->isEmpty())
        <div style="text-align:center;padding:24px 0;">
            <p style="font-size:13px;color:#9CA3AF;margin:0;">No renewal history yet.</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:0;">
            @foreach($renewalHistory as $i => $log)
                @php
                    $isRenew = $log->action === 'renewed';
                    $icoBg   = $isRenew ? '#ECFDF5' : '#EEF2FF';
                    $icoFg   = $isRenew ? '#16A34A' : '#4F46E5';
                    $icoCls  = $isRenew ? 'fa-rotate' : 'fa-plus';
                @endphp
                <div style="display:flex;gap:12px;padding:10px 0;{{ $i < $renewalHistory->count()-1 ? 'border-bottom:1px solid #F3F4F6;' : '' }}">
                    <div style="width:30px;height:30px;border-radius:50%;background:{{ $icoBg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas {{ $icoCls }}" style="font-size:12px;color:{{ $icoFg }};"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:13px;color:#374151;margin:0;line-height:1.4;">{{ $log->description }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $log->actor?->name ?? 'System' }} &middot; {{ $log->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

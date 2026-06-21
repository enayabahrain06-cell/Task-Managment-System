<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 11px; color: #111; margin: 0; padding: 0; }
    h1 { font-size: 18px; font-weight: 800; margin: 0 0 4px; color: #1F2937; }
    .sub { font-size: 11px; color: #6B7280; margin-bottom: 20px; }
    .stat-row { display: flex; gap: 16px; margin-bottom: 20px; }
    .stat-box { flex: 1; border: 1.5px solid #E5E7EB; border-radius: 8px; padding: 10px 14px; }
    .stat-label { font-size: 9px; color: #9CA3AF; font-weight: 600; text-transform: uppercase; }
    .stat-val { font-size: 20px; font-weight: 800; color: #111827; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th { padding: 8px 10px; text-align: left; font-size: 9px; font-weight: 700; color: #6B7280; text-transform: uppercase; background: #F9FAFB; border-bottom: 2px solid #E5E7EB; }
    td { padding: 8px 10px; font-size: 10px; border-bottom: 1px solid #F3F4F6; color: #374151; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 700; }
    .active        { background: #ECFDF5; color: #16A34A; }
    .expiring_soon { background: #FEF3C7; color: #D97706; }
    .expired       { background: #FEE2E2; color: #DC2626; }
    .footer { font-size: 9px; color: #9CA3AF; margin-top: 24px; text-align: center; }
</style>
</head>
<body>
    <h1>Domain Register</h1>
    <div class="sub">Generated {{ $summary['generated_at'] }} &nbsp;·&nbsp;
        {{ $summary['total'] }} domains &nbsp;·&nbsp;
        Annual spend: {{ number_format($summary['annual_total'], 3) }} BHD
    </div>

    <div class="stat-row">
        <div class="stat-box"><div class="stat-label">Total</div><div class="stat-val">{{ $summary['total'] }}</div></div>
        <div class="stat-box" style="border-color:#A7F3D0;"><div class="stat-label" style="color:#16A34A;">Active</div><div class="stat-val" style="color:#16A34A;">{{ $summary['active'] }}</div></div>
        <div class="stat-box" style="border-color:#FDE68A;"><div class="stat-label" style="color:#D97706;">Expiring Soon</div><div class="stat-val" style="color:#D97706;">{{ $summary['expiring_soon'] }}</div></div>
        <div class="stat-box" style="border-color:#FCA5A5;"><div class="stat-label" style="color:#DC2626;">Expired</div><div class="stat-val" style="color:#DC2626;">{{ $summary['expired'] }}</div></div>
        <div class="stat-box" style="border-color:#C7D2FE;"><div class="stat-label" style="color:#4F46E5;">Annual Total</div><div class="stat-val" style="color:#4F46E5;">{{ number_format($summary['annual_total'], 3) }} BHD</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Domain</th>
                <th>Customer</th>
                <th>Registrar</th>
                <th>Responsible</th>
                <th>Bill To</th>
                <th>Expires</th>
                <th>Cost (BHD/yr)</th>
                <th>Auto</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($all as $d)
            @php $billingCycles = \App\Models\Domain::billingCycleOptions(); @endphp
            <tr>
                <td><strong>{{ $d->domain }}</strong>{{ $d->hosting_provider ? '<br><span style="color:#9CA3AF;font-size:9px;">'.$d->hosting_provider.'</span>' : '' }}</td>
                <td>{{ $d->customer?->name ?? '—' }}</td>
                <td>{{ $d->registrar ?? '—' }}</td>
                <td>{{ $d->responsibleUser?->name ?? '—' }}</td>
                <td>{{ $d->billing_to ?? '—' }}</td>
                <td>{{ $d->expires_at ? $d->expires_at->format('d M Y') : '—' }}</td>
                <td>{{ number_format($d->annual_cost, 3) }}</td>
                <td>{{ $d->auto_renew ? '✓' : '—' }}</td>
                <td><span class="badge {{ $d->status }}">{{ ucfirst(str_replace('_',' ',$d->status)) }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($summary['by_registrar']->count())
    <div style="margin-top:24px;">
        <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:8px;">By Registrar</div>
        <table style="width:auto;">
            <thead><tr><th>Registrar</th><th>Count</th></tr></thead>
            <tbody>
                @foreach($summary['by_registrar'] as $row)
                <tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($summary['by_customer']->count())
    <div style="margin-top:16px;">
        <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:8px;">By Customer</div>
        <table style="width:auto;">
            <thead><tr><th>Customer</th><th>Count</th></tr></thead>
            <tbody>
                @foreach($summary['by_customer'] as $row)
                <tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">Confidential — Generated by {{ config('app.name') }}</div>
</body>
</html>

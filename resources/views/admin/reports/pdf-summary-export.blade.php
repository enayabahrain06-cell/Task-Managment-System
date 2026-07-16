<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
.page { padding: 28px 32px; }

.app-name  { font-size: 14px; font-weight: bold; color: #4F46E5; }
.sub-title { font-size: 11px; color: #6B7280; margin-top: 2px; }
.meta-txt  { font-size: 9px; color: #9CA3AF; margin-top: 2px; }

.section-title { font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase;
    letter-spacing: 0.8px; border-bottom: 2px solid #4F46E5; padding-bottom: 5px;
    margin-top: 20px; margin-bottom: 10px; }

table.data { width: 100%; border-collapse: collapse; font-size: 10px; }
table.data th { background: #F3F4F6; padding: 7px 8px; text-align: left; font-size: 9px;
    font-weight: bold; color: #6B7280; text-transform: uppercase; border-bottom: 2px solid #E5E7EB; }
table.data td { padding: 7px 8px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
table.data tr:last-child td { border-bottom: none; }
table.data tr:nth-child(even) td { background: #FAFAFA; }
table.data tfoot td { background: #EEF2FF; font-weight: bold; color: #374151; padding: 8px; }

.bar-bg   { background: #EEF2FF; border-radius: 4px; height: 7px; width: 90px; display: inline-block; }
.bar-fill { border-radius: 4px; height: 7px; background: #4F46E5; display: block; }

.footer-txt { font-size: 8px; color: #9CA3AF; }
</style>
</head>
<body>
<div class="page">

{{-- Header --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:3px solid #4F46E5;padding-bottom:12px;margin-bottom:16px;">
<tr>
    <td>
        @if(!empty($logoBase64))
        <table cellpadding="0" cellspacing="0">
        <tr>
            <td valign="middle"><img src="{{ $logoBase64 }}" style="height:44px;max-width:160px;display:block;"></td>
            <td valign="middle" style="padding-left:10px;">
                <div style="font-size:15px;font-weight:bold;color:#111827;line-height:1.2;">{{ $companyName }}</div>
                <div style="font-size:9px;color:#9CA3AF;margin-top:2px;">{{ $appName }}</div>
            </td>
        </tr>
        </table>
        @else
            <div class="app-name">{{ $companyName }}</div>
        @endif
        <div class="sub-title" style="margin-top:6px;">Company Summary Report — Task % by Customer</div>
        <div class="meta-txt">Generated: {{ $generatedAt }}</div>
    </td>
    <td></td>
</tr>
</table>

<div class="section-title">{{ $monthLabel }}</div>
@if($stats->isEmpty())
    <p style="text-align:center;padding:14px;color:#9CA3AF;">No task activity this month.</p>
@else
<table class="data" width="100%">
    <thead>
    <tr>
        <th width="24">#</th>
        <th>Customer</th>
        <th>Tasks</th>
        <th>Projects</th>
        <th width="110">Share</th>
        <th style="text-align:right;">% of Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($stats as $i => $c)
    <tr>
        <td style="color:#9CA3AF;">{{ $i + 1 }}</td>
        <td style="font-weight:bold;">{{ $c['name'] }}</td>
        <td style="color:#4F46E5;font-weight:bold;">{{ $c['total'] }}</td>
        <td style="color:#7C3AED;font-weight:bold;">{{ $c['projects'] }}</td>
        <td>
            <span class="bar-bg"><span class="bar-fill" style="width:{{ $c['share_pct'] }}%;"></span></span>
        </td>
        <td style="text-align:right;font-weight:bold;color:#4F46E5;">{{ $c['share_pct'] }}%</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td colspan="2">Total</td>
        <td>{{ $stats->sum('total') }}</td>
        <td>{{ $stats->sum('projects') }}</td>
        <td></td>
        <td style="text-align:right;">{{ $stats->sum('share_pct') }}%</td>
    </tr>
    </tfoot>
</table>
@endif

@include('admin.reports.partials.pdf-summary-block')

{{-- Footer --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E5E7EB;padding-top:10px;margin-top:20px;">
<tr>
    <td class="footer-txt">{{ $appName }} — Company Summary Report</td>
    <td class="footer-txt" align="right">Generated on {{ $generatedAt }}</td>
</tr>
</table>

</div>
</body>
</html>

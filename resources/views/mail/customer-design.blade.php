<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Design Ready for Review</title>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

@php
    use App\Models\Setting;
    $appName      = Setting::get('company_name', config('app.name'));
    $primaryColor = Setting::get('primary_color', '#4F46E5');
    $projectName  = $task->project?->name ?? null;
    $deadline     = $task->deadline ? $task->deadline->format('D, M d Y') : null;
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);max-width:560px;width:100%;">

        {{-- Header --}}
        <tr>
          <td style="background:{{ $primaryColor }};padding:24px 32px;">
            <p style="margin:0;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-.3px;">{{ $appName }}</p>
            <p style="margin:4px 0 0;font-size:12px;color:rgba(255,255,255,.7);">Design Delivery</p>
          </td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="padding:32px 32px 24px;">

            <p style="margin:0 0 6px;font-size:13px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;">Design Ready</p>
            <h1 style="margin:0 0 20px;font-size:22px;font-weight:800;color:#111827;line-height:1.3;">{{ $task->title }}</h1>

            <p style="margin:0 0 20px;font-size:14px;color:#374151;line-height:1.6;">
                Dear <strong>{{ $customerName }}</strong>,<br><br>
                @if($customMessage)
                    {{ $customMessage }}
                @else
                    Your design has been completed and approved. Please review it at your earliest convenience.
                @endif
            </p>

            {{-- Detail pills --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
              @if($projectName)
              <tr>
                <td style="padding:8px 12px;background:#F9FAFB;border-radius:8px;border:1px solid #E5E7EB;">
                  <span style="font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Project</span>
                  <p style="margin:2px 0 0;font-size:14px;font-weight:600;color:#111827;">{{ $projectName }}</p>
                </td>
              </tr>
              @endif
              @if($adminNote)
              <tr><td style="height:6px;"></td></tr>
              <tr>
                <td style="padding:10px 14px;background:#EEF2FF;border-radius:8px;border:1px solid #C7D2FE;border-left:3px solid {{ $primaryColor }};">
                  <span style="font-size:11px;font-weight:700;color:#4F46E5;text-transform:uppercase;letter-spacing:.05em;">Note from {{ $senderName ?? 'our team' }}</span>
                  <p style="margin:4px 0 0;font-size:13px;color:#374151;line-height:1.6;">{{ $adminNote }}</p>
                </td>
              </tr>
              @endif
            </table>

            <p style="margin:0;font-size:13px;color:#6B7280;line-height:1.6;">
                If you have any questions or feedback, please reach out to us directly.
            </p>

          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="padding:16px 32px 24px;border-top:1px solid #F3F4F6;">
            <p style="margin:0;font-size:11px;color:#9CA3AF;line-height:1.6;">
                Sent by <strong>{{ $senderName ?? $appName }}</strong> via {{ $appName }}.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>

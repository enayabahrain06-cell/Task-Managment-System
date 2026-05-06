<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Task Assigned</title>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

@php
    use App\Models\Setting;
    $appName     = Setting::get('company_name', config('app.name'));
    $primaryColor = Setting::get('primary_color', '#4F46E5');
    $projectName = $task->project?->name ?? 'N/A';
    $customer    = $task->customer?->name ?? $task->project?->customer?->name ?? null;
    $deadline    = $task->deadline ? $task->deadline->format('D, M d Y') : null;
    $taskUrl     = route('user.tasks.show', $task->id);
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);max-width:560px;width:100%;">

        {{-- Header bar --}}
        <tr>
          <td style="background:{{ $primaryColor }};padding:24px 32px;">
            <p style="margin:0;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-.3px;">{{ $appName }}</p>
            <p style="margin:4px 0 0;font-size:12px;color:rgba(255,255,255,.7);">Task Management</p>
          </td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="padding:32px 32px 24px;">

            <p style="margin:0 0 6px;font-size:13px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;">New Assignment</p>
            <h1 style="margin:0 0 20px;font-size:22px;font-weight:800;color:#111827;line-height:1.3;">{{ $task->title }}</h1>

            <p style="margin:0 0 20px;font-size:14px;color:#374151;line-height:1.6;">
                Hi <strong>{{ $assignee->name }}</strong>, a new task has been assigned to you.
                @if($deadline) Please complete it by <strong>{{ $deadline }}</strong>. @endif
            </p>

            {{-- Detail pills --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
              @if($projectName !== 'N/A')
              <tr>
                <td style="padding:8px 12px;background:#F9FAFB;border-radius:8px;border:1px solid #E5E7EB;margin-bottom:6px;">
                  <span style="font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Project</span>
                  <p style="margin:2px 0 0;font-size:14px;font-weight:600;color:#111827;">{{ $projectName }}</p>
                </td>
              </tr>
              @endif
              @if($customer)
              <tr><td style="height:6px;"></td></tr>
              <tr>
                <td style="padding:8px 12px;background:#F9FAFB;border-radius:8px;border:1px solid #E5E7EB;">
                  <span style="font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Customer</span>
                  <p style="margin:2px 0 0;font-size:14px;font-weight:600;color:#111827;">{{ $customer }}</p>
                </td>
              </tr>
              @endif
              @if($deadline)
              <tr><td style="height:6px;"></td></tr>
              <tr>
                <td style="padding:8px 12px;background:#FEF3C7;border-radius:8px;border:1px solid #FDE68A;">
                  <span style="font-size:11px;font-weight:700;color:#92400E;text-transform:uppercase;letter-spacing:.05em;">Deadline</span>
                  <p style="margin:2px 0 0;font-size:14px;font-weight:700;color:#92400E;">{{ $deadline }}</p>
                </td>
              </tr>
              @endif
              @if($task->priority)
              <tr><td style="height:6px;"></td></tr>
              <tr>
                <td style="padding:8px 12px;background:#F9FAFB;border-radius:8px;border:1px solid #E5E7EB;">
                  <span style="font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Priority</span>
                  <p style="margin:2px 0 0;font-size:14px;font-weight:600;color:{{ $task->priority === 'high' ? '#DC2626' : ($task->priority === 'medium' ? '#D97706' : '#059669') }};">{{ ucfirst($task->priority) }}</p>
                </td>
              </tr>
              @endif
            </table>

            {{-- CTA button --}}
            <table cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
              <tr>
                <td style="background:{{ $primaryColor }};border-radius:8px;">
                  <a href="{{ $taskUrl }}"
                     style="display:inline-block;padding:12px 28px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:8px;">
                    View My Task →
                  </a>
                </td>
              </tr>
            </table>

            @if($task->description)
            <div style="padding:12px 14px;background:#F9FAFB;border-left:3px solid {{ $primaryColor }};border-radius:0 6px 6px 0;margin-bottom:0;">
              <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Description</p>
              <p style="margin:0;font-size:13px;color:#374151;line-height:1.6;">{{ Str::limit(strip_tags($task->description), 300) }}</p>
            </div>
            @endif

          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="padding:16px 32px 24px;border-top:1px solid #F3F4F6;">
            <p style="margin:0;font-size:11px;color:#9CA3AF;line-height:1.6;">
              You received this email because you were assigned a task in {{ $appName }}.
              If you have questions, contact your manager directly.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>

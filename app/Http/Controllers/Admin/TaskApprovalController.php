<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CustomerDesignMail;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskSocialPost;
use App\Models\TaskSubmission;
use App\Models\TaskTimerSegment;
use App\Models\User;
use App\Notifications\SocialMediaAssigned;
use App\Notifications\SocialMediaPosted;
use App\Notifications\TaskApproved;
use App\Notifications\TaskRejected;
use App\Services\AuditLogger;
use App\Services\NasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TaskApprovalController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('view_approvals')) {
            abort(403, 'You do not have permission to view Approvals.');
        }

        $tab = $request->get('tab', 'pending');

        $tasks = Task::where('status', 'submitted')
            ->with(['project.customer', 'customer', 'assignee', 'assignees', 'submissions' => fn($q) => $q->latest()])
            ->latest()
            ->paginate(10, ['*'], 'page');

        $hSort     = $request->get('hsort', 'date');
        $hDir      = $request->get('hdir', 'desc') === 'asc' ? 'asc' : 'desc';
        $hFrom     = $request->get('hfrom');
        $hTo       = $request->get('hto');
        $hDecision = $request->get('hdecision');
        $hSearch   = $request->get('hsearch');

        $historyQuery = TaskSubmission::whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('reviewed_at')
            ->with(['task.project', 'task.assignee', 'task.socialAssignee', 'task.socialPosts.user', 'reviewer']);

        if ($hDecision) {
            $historyQuery->where('status', $hDecision);
        }
        if ($hFrom) {
            $historyQuery->whereDate('reviewed_at', '>=', $hFrom);
        }
        if ($hTo) {
            $historyQuery->whereDate('reviewed_at', '<=', $hTo);
        }
        if ($hSearch) {
            $historyQuery->where(function ($q) use ($hSearch) {
                $q->whereHas('task', fn($q2) => $q2->where('title', 'like', "%{$hSearch}%"))
                  ->orWhereHas('task.assignee', fn($q2) => $q2->where('name', 'like', "%{$hSearch}%"))
                  ->orWhereHas('reviewer', fn($q2) => $q2->where('name', 'like', "%{$hSearch}%"));
            });
        }

        match ($hSort) {
            'task'     => $historyQuery->orderByRaw("(SELECT title FROM tasks WHERE tasks.id = task_submissions.task_id) {$hDir}"),
            'assignee' => $historyQuery->orderByRaw("(SELECT name FROM users WHERE users.id = (SELECT assigned_to FROM tasks WHERE tasks.id = task_submissions.task_id)) {$hDir}"),
            'reviewer' => $historyQuery->orderByRaw("(SELECT name FROM users WHERE users.id = task_submissions.reviewed_by) {$hDir}"),
            'decision' => $historyQuery->orderBy('task_submissions.status', $hDir),
            default    => $historyQuery->orderBy('task_submissions.reviewed_at', $hDir),
        };

        $history = $historyQuery->paginate(10, ['*'], 'hpage');

        $socialTasks = Task::whereNotNull('social_assigned_to')
            ->whereNull('social_posted_at')
            ->with(['project', 'assignee', 'socialAssignee', 'socialPosts.user'])
            ->latest()
            ->paginate(10, ['*'], 'spage');

        $publishedSocialTasks = Task::whereNotNull('social_assigned_to')
            ->whereNotNull('social_posted_at')
            ->with(['project', 'assignee', 'socialAssignee', 'socialPosts'])
            ->orderByDesc('social_posted_at')
            ->paginate(10, ['*'], 'ppage');

        $socialUsers = User::where('role', 'user')->orderBy('name')->get();

        $awaitingTasks = Task::where('status', 'pending_customer')
            ->with(['project.customer', 'customer', 'assignee', 'assignees', 'submissions' => fn($q) => $q->latest()])
            ->latest()
            ->paginate(10, ['*'], 'apage');

        $dlSearch   = $request->get('dlsearch');
        $dlAssignee = $request->get('dlassignee');

        $decideLaterQuery = Task::whereIn('status', ['delivered', 'approved', 'archived'])
            ->whereNull('social_required')
            ->whereNull('social_assigned_to')
            ->with(['project', 'assignee', 'submissions' => fn($q) => $q->latest()]);

        if ($dlSearch) {
            $decideLaterQuery->where('title', 'like', "%{$dlSearch}%");
        }
        if ($dlAssignee) {
            $decideLaterQuery->where('assigned_to', $dlAssignee);
        }

        $decideLaterTasks = $decideLaterQuery->latest()->paginate(10, ['*'], 'dpage');

        $dlAssignees = User::whereIn('id',
            Task::whereIn('status', ['delivered', 'approved', 'archived'])
                ->whereNull('social_required')
                ->whereNull('social_assigned_to')
                ->whereNotNull('assigned_to')
                ->pluck('assigned_to')
                ->unique()
        )->orderBy('name')->get();

        return view('admin.approvals.index', compact(
            'tasks', 'history', 'tab', 'socialTasks', 'publishedSocialTasks', 'socialUsers',
            'hSort', 'hDir', 'hFrom', 'hTo', 'hDecision', 'hSearch', 'awaitingTasks',
            'decideLaterTasks', 'dlSearch', 'dlAssignee', 'dlAssignees'
        ));
    }

    /**
     * Record how long the reviewer spent on a review cycle.
     * started_at = when the task most recently became 'submitted'.
     */
    private function recordReviewSegment(Task $task): void
    {
        $submittedLog = TaskLog::where('task_id', $task->id)
            ->where('action', 'status_updated_submitted')
            ->orderByDesc('created_at')
            ->first();

        if (!$submittedLog) return;

        $startedAt = $submittedLog->created_at;
        $endedAt   = now();
        $seconds   = (int) $startedAt->diffInSeconds($endedAt);

        TaskTimerSegment::create([
            'task_id'          => $task->id,
            'user_id'          => auth()->id(),
            'phase'            => 'review',
            'started_at'       => $startedAt,
            'ended_at'         => $endedAt,
            'duration_seconds' => $seconds,
            'pause_reason'     => 'system',
        ]);
    }

    /**
     * Record social media phase time for the assignee.
     * started_at = when social was assigned.
     */
    private function recordSocialSegment(Task $task): void
    {
        if (!$task->social_assigned_to) return;

        $assignedLog = TaskLog::where('task_id', $task->id)
            ->where('action', 'social_assigned')
            ->orderByDesc('created_at')
            ->first();

        $startedAt = $assignedLog?->created_at ?? $task->created_at;
        $endedAt   = now();
        $seconds   = (int) $startedAt->diffInSeconds($endedAt);

        TaskTimerSegment::create([
            'task_id'          => $task->id,
            'user_id'          => $task->social_assigned_to,
            'phase'            => 'social',
            'started_at'       => $startedAt,
            'ended_at'         => $endedAt,
            'duration_seconds' => $seconds,
            'pause_reason'     => 'system',
        ]);
    }

    public function pendingCustomer(Request $request, Task $task)
    {
        if (!auth()->user()->hasPermission('view_approvals')) {
            abort(403);
        }

        $request->validate(['note' => 'nullable|string|max:500']);

        $allowedStatuses = ['submitted', 'pending_customer', 'paused', 'in_progress'];
        if (!in_array($task->status, $allowedStatuses)) {
            return back()->with('error', 'Task cannot be marked as awaiting customer approval from its current status.');
        }

        $oldStatus = $task->status;
        $task->update([
            'status'         => 'pending_customer',
            'design_sent_at' => $task->design_sent_at ?? now(),
        ]);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_pending_customer',
            'note'     => $request->note ?: 'Awaiting customer approval',
            'metadata' => [
                'old_status'    => $oldStatus,
                'new_status'    => 'pending_customer',
                'reviewer_id'   => auth()->id(),
                'reviewer_name' => auth()->user()->name,
                'note'          => $request->note,
            ],
        ]);

        AuditLogger::log(
            'task.pending_customer',
            $task,
            'Task "' . $task->title . '" marked as awaiting customer approval',
            ['task_id' => $task->id, 'note' => $request->note]
        );

        return back()->with('success', 'Task marked as awaiting customer approval.');
    }

    public function approve(Request $request, Task $task)
    {
        $request->validate([
            'note'                      => 'nullable|string|max:500',
            'social_required'           => 'nullable|in:1,0',
            'social_assigned_to'        => 'nullable|exists:users,id',
            'social_platforms'          => 'nullable|array',
            'social_platforms.*'        => 'nullable|string|in:facebook,instagram,twitter,linkedin,tiktok,youtube,snapchat,other',
            'social_description'        => 'nullable|string|max:2000',
            'social_caption'            => 'nullable|string|max:5000',
            'social_budget'             => 'nullable|string|max:100',
            'notify_customer_email'     => 'nullable|in:0,1',
            'notify_customer_whatsapp'  => 'nullable|in:0,1',
            'customer_message'          => 'nullable|string|max:2000',
            'customer_email_override'   => 'nullable|email|max:255',
            'customer_phone_override'   => 'nullable|string|max:30',
        ]);

        $latestSub = TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->orderByDesc('version')
            ->first();

        // Close any open employee timer segments permanently on approval
        TaskTimerSegment::where('task_id', $task->id)
            ->whereNull('ended_at')
            ->each(function (TaskTimerSegment $seg) {
                $seg->update([
                    'ended_at'         => now(),
                    'duration_seconds' => (int) $seg->started_at->diffInSeconds(now()),
                    'pause_reason'     => 'approved',
                ]);
            });

        // Auto-record how long this review cycle took
        $this->recordReviewSegment($task);

        $task->update(array_filter([
            'status'               => 'delivered',
            'customer_approved_at' => $task->status === 'pending_customer' ? now() : null,
        ], fn($v) => $v !== null));

        TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->update([
                'status'      => 'approved',
                'admin_note'  => $request->note,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

        if ($latestSub?->file_path) {
            $nas     = app(NasService::class);
            $nasPath = $nas->copyToNas($task, $latestSub->file_path, $latestSub->original_filename ?? basename($latestSub->file_path), '07_Delivered', $latestSub->version);
            $nas->copyToNasDeliverable($task, $latestSub->file_path, $latestSub->original_filename ?? basename($latestSub->file_path));
            if ($nasPath) $latestSub->update(['nas_path' => $nasPath]);
        }

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_delivered',
            'note'     => $request->note ? 'Approved & delivered: ' . $request->note : 'Approved and delivered by admin',
            'metadata' => [
                'old_status'         => 'submitted',
                'new_status'         => 'delivered',
                'reviewer_id'        => auth()->id(),
                'reviewer_name'      => auth()->user()->name,
                'submission_version' => $latestSub?->version,
                'approval_note'      => $request->note,
            ],
        ]);

        AuditLogger::log(
            'task.approved',
            $task,
            'Task "' . $task->title . '" approved' . ($request->note ? ': ' . $request->note : ''),
            ['task_id' => $task->id, 'task_title' => $task->title, 'note' => $request->note]
        );

        if ($task->assignee && Setting::get('notify_on_approve', '1') === '1') {
            $task->assignee->notify(new TaskApproved($task, $request->note));
        }

        // Send design to customer via email if requested
        if ($request->input('notify_customer_email') === '1') {
            $customer     = $task->customer ?? $task->project?->customer;
            $toEmail      = $customer?->email ?? $request->input('customer_email_override');
            $toName       = $customer?->name ?? 'Customer';
            $customerId   = $customer?->id;
            if ($toEmail) {
                $attachmentFiles = [];
                if ($latestSub?->file_path) {
                    $attachmentFiles[] = [
                        'path' => $latestSub->file_path,
                        'name' => $latestSub->original_filename ?? basename($latestSub->file_path),
                    ];
                }
                try {
                    Mail::to($toEmail)->send(new CustomerDesignMail(
                        task:            $task,
                        customerName:    $toName,
                        customMessage:   $request->customer_message ?: null,
                        adminNote:       $request->note ?: null,
                        senderName:      auth()->user()->name,
                        attachmentFiles: $attachmentFiles,
                    ));
                    TaskLog::create([
                        'task_id'  => $task->id,
                        'user_id'  => auth()->id(),
                        'action'   => 'customer_notified',
                        'note'     => 'Design sent to ' . $toName . ' <' . $toEmail . '> via email' . ($latestSub?->file_path ? ' with attachment' : ''),
                        'metadata' => ['customer_id' => $customerId, 'customer_email' => $toEmail, 'has_attachment' => !empty($attachmentFiles)],
                    ]);
                } catch (\Throwable $e) {
                    // Email failed — don't block the approval
                    \Log::warning('CustomerDesignMail failed: ' . $e->getMessage());
                }
            }
        }

        // Send design to customer via WhatsApp API if requested
        if ($request->input('notify_customer_whatsapp') === '1' && Setting::get('wa_enabled', '0') === '1') {
            $customer    = $task->customer ?? $task->project?->customer;
            $toPhone     = $customer?->phone ?? $request->input('customer_phone_override');
            $toName      = $customer?->name ?? 'Customer';
            if ($toPhone) {
                $digits     = preg_replace('/\D/', '', $toPhone);
                $company    = Setting::get('company_name', config('app.name'));
                $designLink = $latestSub?->file_path ? "View design: " . url('storage/' . $latestSub->file_path) : '';
                $adminNote  = $request->customer_message ? $request->customer_message . "\n\n" : '';
                $tpl        = Setting::get('wa_tpl_customer_design',
                    "Hello {customer_name},\n\nYour design for \"{task_title}\" has been approved and is ready for your review. 🎨\n\n{admin_note}{design_link}\n\n{company}"
                );
                $body = str_replace(
                    ['{customer_name}', '{task_title}', '{design_link}', '{admin_note}', '{company}'],
                    [$toName,          $task->title,   $designLink,     $adminNote,     $company],
                    $tpl
                );
                try {
                    $this->dispatchWhatsapp($digits, $body);
                    TaskLog::create([
                        'task_id'  => $task->id,
                        'user_id'  => auth()->id(),
                        'action'   => 'customer_notified',
                        'note'     => 'Design sent to ' . $toName . ' via WhatsApp',
                        'metadata' => ['channel' => 'whatsapp', 'phone' => $toPhone],
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('Customer WhatsApp send failed: ' . $e->getMessage());
                }
            }
        }

        $task->project?->autoComplete();

        // Handle social media decision made during approval
        if ($request->filled('social_required')) {
            $needed = (bool) $request->input('social_required');
            $task->update(['social_required' => $needed]);

            if (!$needed) {
                $task->update([
                    'social_assigned_to' => null,
                    'social_posted_at'   => null,
                    'social_description' => null,
                    'social_caption'     => null,
                    'social_budget'      => null,
                    'social_platforms'   => null,
                ]);
            } elseif ($request->filled('social_assigned_to')) {
                $socialUser = User::find($request->social_assigned_to);
                if ($socialUser) {
                    $platforms = array_values(array_filter((array) $request->input('social_platforms', [])));
                    $task->update([
                        'social_assigned_to' => $socialUser->id,
                        'social_description' => $request->social_description ?: null,
                        'social_caption'     => $request->social_caption     ?: null,
                        'social_budget'      => $request->social_budget       ?: null,
                        'social_platforms'   => $platforms ?: null,
                    ]);
                    TaskLog::create([
                        'task_id'  => $task->id,
                        'user_id'  => auth()->id(),
                        'action'   => 'social_assigned',
                        'note'     => 'Assigned to ' . $socialUser->name . ' for social media posting',
                        'metadata' => [
                            'social_description' => $request->social_description ?: null,
                            'social_caption'     => $request->social_caption     ?: null,
                            'social_budget'      => $request->social_budget       ?: null,
                            'social_platforms'   => $platforms ?: null,
                        ],
                    ]);
                    if (Setting::get('notify_on_social', '1') === '1') {
                        $socialUser->notify(new SocialMediaAssigned($task, auth()->user()));
                    }
                }
            }
        }

        $successMsg = 'Task approved.';
        if ($request->input('social_required') === '1' && $request->filled('social_assigned_to')) {
            $assignedUser = User::find($request->social_assigned_to);
            if ($assignedUser) {
                $successMsg = 'Task approved and assigned to ' . $assignedUser->name . ' for social media posting.';
            }
        } elseif ($request->input('social_required') === '0') {
            $successMsg = 'Task approved. No social media posting required.';
        }

        return back()->with('success', $successMsg);
    }

    public function sendWhatsappToCustomer(Request $request)
    {
        $request->validate([
            'phone'   => 'required|string|max:30',
            'message' => 'required|string|max:4000',
        ]);

        if (Setting::get('wa_enabled', '0') !== '1') {
            return response()->json(['ok' => false, 'message' => 'WhatsApp API is not enabled. Configure it in Settings → WhatsApp.'], 422);
        }
        $token = Setting::get('wa_token', '');
        if (!$token) {
            return response()->json(['ok' => false, 'message' => 'WhatsApp API token is not set.'], 422);
        }

        $digits = preg_replace('/\D/', '', $request->phone);
        if (!$digits) {
            return response()->json(['ok' => false, 'message' => 'Invalid phone number.'], 422);
        }

        try {
            $result = $this->dispatchWhatsapp($digits, $request->message);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function sendWhatsappMediaToCustomer(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string|max:30',
            'file_url' => 'required|string|max:2000',
            'filename' => 'required|string|max:255',
            'caption'  => 'nullable|string|max:1000',
        ]);

        if (Setting::get('wa_enabled', '0') !== '1') {
            return response()->json(['ok' => false, 'message' => 'WhatsApp API is not enabled. Configure it in Settings → WhatsApp.'], 422);
        }
        $token = Setting::get('wa_token', '');
        if (!$token) {
            return response()->json(['ok' => false, 'message' => 'WhatsApp API token is not set.'], 422);
        }
        $provider = Setting::get('wa_provider', 'ultramsg');
        if ($provider !== 'ultramsg') {
            return response()->json(['ok' => false, 'message' => 'File attachments are only supported with the UltraMsg provider.'], 422);
        }
        $instanceId = Setting::get('wa_instance_id', '');
        if (!$instanceId) {
            return response()->json(['ok' => false, 'message' => 'UltraMsg instance ID not set.'], 422);
        }

        $digits = preg_replace('/\D/', '', $request->phone);
        if (!$digits) {
            return response()->json(['ok' => false, 'message' => 'Invalid phone number.'], 422);
        }

        $ext     = strtolower(pathinfo($request->filename, PATHINFO_EXTENSION));
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

        try {
            if ($isImage) {
                $response = Http::asForm()->post(
                    "https://api.ultramsg.com/{$instanceId}/messages/image",
                    ['token' => $token, 'to' => $digits, 'image' => $request->file_url, 'caption' => $request->caption ?? '']
                );
            } else {
                $response = Http::asForm()->post(
                    "https://api.ultramsg.com/{$instanceId}/messages/document",
                    ['token' => $token, 'to' => $digits, 'filename' => $request->filename, 'document' => $request->file_url, 'caption' => $request->caption ?? '']
                );
            }
            $data = $response->json() ?? [];
            $ok   = isset($data['sent']) && $data['sent'] === 'true';
            return response()->json([
                'ok'      => $ok,
                'message' => $ok ? 'File sent via WhatsApp.' : $this->extractUltramsgError($data),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function waErrMsg(mixed $val, string $fallback = 'Unknown error.'): string
    {
        if ($val === null || $val === '') return $fallback;
        if (is_array($val)) {
            // UltraMsg sometimes returns [{"field": "error message"}]
            if (isset($val[0]) && is_array($val[0])) {
                $parts = [];
                foreach ($val[0] as $v) {
                    if (is_scalar($v)) $parts[] = (string) $v;
                }
                return $parts ? implode('. ', $parts) : $fallback;
            }
            return isset($val['message']) ? (string) $val['message'] : json_encode($val);
        }
        if (is_object($val)) {
            $arr = (array) $val;
            return isset($arr['message']) ? (string) $arr['message'] : json_encode($arr);
        }
        return (string) $val;
    }

    private function extractUltramsgError(array $data): string
    {
        // Top-level numeric array: [{"image":"file not exist"}]
        if (isset($data[0]) && is_array($data[0])) {
            return $this->waErrMsg($data[0]);
        }
        return $this->waErrMsg($data['error'] ?? $data['message'] ?? null, 'UltraMsg error.');
    }

    private function dispatchWhatsapp(string $digits, string $body): array
    {
        $provider = Setting::get('wa_provider', 'ultramsg');
        $token    = Setting::get('wa_token', '');

        return match ($provider) {
            'ultramsg' => $this->sendUltramsg($token, $digits, $body),
            'twilio'   => $this->sendTwilio($token, $digits, $body),
            'meta'     => $this->sendMeta($token, $digits, $body),
            default    => ['ok' => false, 'message' => 'Unknown provider.'],
        };
    }

    private function sendUltramsg(string $token, string $phone, string $body): array
    {
        $instanceId = Setting::get('wa_instance_id', '');
        if (!$instanceId) return ['ok' => false, 'message' => 'UltraMsg instance ID not set.'];
        $response = Http::asForm()->post(
            "https://api.ultramsg.com/{$instanceId}/messages/chat",
            ['token' => $token, 'to' => $phone, 'body' => $body]
        );
        $data = $response->json() ?? [];
        return isset($data['sent']) && $data['sent'] === 'true'
            ? ['ok' => true, 'message' => 'Sent via UltraMsg.']
            : ['ok' => false, 'message' => $this->extractUltramsgError($data)];
    }

    private function sendTwilio(string $token, string $phone, string $body): array
    {
        $accountSid = Setting::get('wa_account_sid', '');
        $fromNumber = Setting::get('wa_from_number', '');
        if (!$accountSid || !$fromNumber) return ['ok' => false, 'message' => 'Twilio credentials incomplete.'];
        $response = Http::withBasicAuth($accountSid, $token)->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => 'whatsapp:+' . ltrim($fromNumber, '+'),
                'To'   => 'whatsapp:+' . ltrim($phone, '+'),
                'Body' => $body,
            ]);
        return $response->successful()
            ? ['ok' => true, 'message' => 'Sent via Twilio.']
            : ['ok' => false, 'message' => $this->waErrMsg($response->json('message'), 'Twilio error.')];
    }

    private function sendMeta(string $token, string $phone, string $body): array
    {
        $phoneNumberId = Setting::get('wa_phone_number_id', '');
        if (!$phoneNumberId) return ['ok' => false, 'message' => 'Meta phone number ID not set.'];
        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $phone,
                'type'              => 'text',
                'text'              => ['body' => $body],
            ]);
        return $response->successful()
            ? ['ok' => true, 'message' => 'Sent via Meta API.']
            : ['ok' => false, 'message' => $this->waErrMsg($response->json('error.message') ?? $response->json('error'), 'Meta error.')];
    }

    public function reject(Request $request, Task $task)
    {
        $request->validate(['note' => 'required|string|max:500']);

        $latestSub = TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->orderByDesc('version')
            ->first();

        // Auto-record review time before changing status
        $this->recordReviewSegment($task);

        $task->update(['status' => 'revision_requested']);

        TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->update([
                'status'      => 'rejected',
                'admin_note'  => $request->note,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

        if ($latestSub?->file_path) {
            $nas     = app(NasService::class);
            $nasPath = $nas->copyToNas($task, $latestSub->file_path, $latestSub->original_filename ?? basename($latestSub->file_path), '06_Rejected', $latestSub->version);
            if ($nasPath) $latestSub->update(['nas_path' => $nasPath]);
        }

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_revision_requested',
            'note'     => 'Revision requested: ' . $request->note,
            'metadata' => [
                'old_status'         => 'submitted',
                'new_status'         => 'revision_requested',
                'reviewer_id'        => auth()->id(),
                'reviewer_name'      => auth()->user()->name,
                'submission_version' => $latestSub?->version,
                'rejection_reason'   => $request->note,
            ],
        ]);

        AuditLogger::log(
            'task.rejected',
            $task,
            'Revision requested for "' . $task->title . '": ' . $request->note,
            ['task_id' => $task->id, 'task_title' => $task->title, 'reason' => $request->note]
        );

        if ($task->assignee && Setting::get('notify_on_reject', '1') === '1') {
            $task->assignee->notify(new TaskRejected($task, $request->note));
        }

        return back()->with('success', 'Revision requested — assignee has been notified.');
    }

    public function setSocialRequired(Request $request, Task $task)
    {
        $request->validate(['required' => 'required|in:1,0']);

        $needed = (bool) $request->input('required');
        $task->update(['social_required' => $needed]);

        if (!$needed) {
            $task->update(['social_assigned_to' => null, 'social_posted_at' => null]);
        }

        return back()->with('success', $needed
            ? '"' . $task->title . '" marked for social media posting.'
            : '"' . $task->title . '" — social media posting not required.');
    }

    public function assignSocial(Request $request, Task $task)
    {
        $request->validate([
            'social_user_id'     => 'required|exists:users,id',
            'social_description' => 'nullable|string|max:2000',
            'social_caption'     => 'nullable|string|max:5000',
            'social_budget'      => 'nullable|string|max:100',
        ]);

        $user = User::findOrFail($request->social_user_id);

        $task->update([
            'social_assigned_to' => $user->id,
            'social_posted_at'   => null,
            'social_description' => $request->social_description ?: null,
            'social_caption'     => $request->social_caption     ?: null,
            'social_budget'      => $request->social_budget       ?: null,
        ]);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'social_assigned',
            'note'     => 'Assigned to ' . $user->name . ' for social media posting',
            'metadata' => [
                'social_description' => $request->social_description ?: null,
                'social_caption'     => $request->social_caption     ?: null,
                'social_budget'      => $request->social_budget       ?: null,
            ],
        ]);

        if (Setting::get('notify_on_social', '1') === '1') {
            $user->notify(new SocialMediaAssigned($task, auth()->user()));
        }

        return back()->with('success', '"' . $task->title . '" assigned to ' . $user->name . ' for social media posting.');
    }

    public function updateSocialPost(Request $request, TaskSocialPost $post)
    {
        $request->validate([
            'platform' => 'required|string|in:facebook,instagram,twitter,linkedin,tiktok,youtube,snapchat,other',
            'post_url' => 'nullable|url|max:1000',
            'note'     => 'nullable|string|max:1000',
        ]);

        $post->update([
            'platform' => $request->platform,
            'post_url' => $request->post_url ?: null,
            'note'     => $request->note ?: null,
        ]);

        TaskLog::create([
            'task_id'  => $post->task_id,
            'user_id'  => auth()->id(),
            'action'   => 'social_post_edited',
            'note'     => 'Edited ' . $post->platformLabel() . ' post record',
            'metadata' => ['platform' => $request->platform, 'post_url' => $request->post_url, 'note' => $request->note],
        ]);

        return back()->with('success', 'Post record updated.');
    }

    public function deleteSocialPost(TaskSocialPost $post)
    {
        $taskId = $post->task_id;
        $label  = $post->platformLabel();
        $post->delete();

        if (!TaskSocialPost::where('task_id', $taskId)->exists()) {
            Task::find($taskId)?->update(['social_posted_at' => null]);
        }

        TaskLog::create([
            'task_id' => $taskId,
            'user_id' => auth()->id(),
            'action'  => 'social_post_deleted',
            'note'    => 'Deleted ' . $label . ' post record',
        ]);

        return back()->with('success', 'Post record removed.');
    }

    public function addPost(Request $request, Task $task)
    {
        $user = auth()->user();
        if ($user->id !== (int) $task->social_assigned_to && !in_array($user->role, ['admin', 'manager'])) {
            abort(403, 'You are not assigned to this social media post.');
        }

        $request->validate([
            'platform'   => 'required|array|min:1',
            'platform.*' => 'required|string|in:facebook,instagram,twitter,linkedin,tiktok,youtube,snapchat,other',
            'post_url'   => 'nullable|array',
            'post_url.*' => 'nullable|url|max:1000',
            'note'       => 'nullable|array',
            'note.*'     => 'nullable|string|max:1000',
        ]);

        $platformLabels = [
            'facebook' => 'Facebook', 'instagram' => 'Instagram', 'twitter' => 'Twitter/X',
            'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube',
            'snapchat' => 'Snapchat', 'other' => 'Other',
        ];

        $platforms = $request->input('platform', []);
        $urls      = $request->input('post_url', []);
        $notes     = $request->input('note', []);
        $recorded  = [];

        $nas         = app(NasService::class);
        $approvedSub = $task->submissions()->where('status', 'approved')->latest()->first();

        foreach ($platforms as $i => $platform) {
            $url  = $urls[$i] ?? null;
            $note = $notes[$i] ?? null;

            TaskSocialPost::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'platform' => $platform,
                'post_url' => $url ?: null,
                'note'     => $note ?: null,
            ]);

            $label = $platformLabels[$platform] ?? ucfirst($platform);
            $recorded[] = $label;

            TaskLog::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'action'   => 'social_posted',
                'note'     => 'Posted on ' . $label . ($url ? ' — ' . $url : ''),
                'metadata' => ['platform' => $platform, 'post_url' => $url, 'note' => $note],
            ]);

            // Copy approved file + post info to Social_Media NAS folder
            if ($approvedSub?->file_path) {
                $nas->copyToNasSocial(
                    $task,
                    $approvedSub->file_path,
                    $approvedSub->original_filename ?? basename($approvedSub->file_path),
                    $platform,
                    [
                        'task_title' => $task->title,
                        'company'    => $task->customer?->name ?? $task->project?->customer?->name ?? '—',
                        'platform'   => $platform,
                        'posted_by'  => auth()->user()->name . ' (' . auth()->user()->email . ')',
                        'posted_at'  => now()->format('D, d M Y H:i'),
                        'post_url'   => $url ?: '',
                        'note'       => $note ?: '',
                    ]
                );
            }
        }

        if (!$task->social_posted_at) {
            // First time posting — record social phase duration for billing
            $this->recordSocialSegment($task);
            $task->update(['social_posted_at' => now()]);
        }

        if (Setting::get('notify_on_social', '1') === '1') {
            User::whereIn('role', ['admin', 'manager'])->get()
                ->each(fn($u) => $u->notify(new SocialMediaPosted($task, auth()->user())));

            if ($task->assignee && $task->assignee->id !== auth()->id()) {
                $task->assignee->notify(new SocialMediaPosted($task, auth()->user()));
            }
        }

        $summary = count($recorded) === 1
            ? $recorded[0]
            : implode(', ', array_slice($recorded, 0, -1)) . ' & ' . last($recorded);

        return back()->with('success', count($recorded) . ' ' . Str::plural('post', count($recorded)) . ' recorded on ' . $summary . '! The team has been notified.');
    }

    public function reopenSocial(Task $task)
    {
        $task->update(['social_posted_at' => null]);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action'  => 'social_reopened',
            'note'    => 'Social media submission reopened by ' . auth()->user()->name,
        ]);

        return redirect()->route('admin.approvals.index', ['tab' => 'published'])
            ->with('success', '"' . $task->title . '" has been reopened — the assignee can now record posts again.');
    }

    // Legacy — kept for backward compatibility, redirects to add-post
    public function markPosted(Task $task)
    {
        return redirect()->route('social.show', $task);
    }

    public function showSocial(Task $task)
    {
        $user = auth()->user();
        if ($user->id !== (int) $task->social_assigned_to && !in_array($user->role, ['admin', 'manager'])) {
            abort(403, 'You are not assigned to this social media post.');
        }

        $task->load([
            'project.creator',
            'project.members',
            'assignee',
            'assignees',
            'socialAssignee',
            'creator',
            'socialPosts.user',
            'submissions' => fn($q) => $q->latest(),
        ]);

        $realProject           = $task->project && !$task->project->is_quick ? $task->project : null;
        $projectTaskCount      = $realProject?->tasks()->count() ?? 0;
        $projectCompletedCount = $realProject?->tasks()->whereIn('status', ['approved','delivered','archived'])->count() ?? 0;
        $projectProgress       = $projectTaskCount > 0 ? round($projectCompletedCount / $projectTaskCount * 100) : 0;

        return view('social.show', compact('task', 'projectTaskCount', 'projectCompletedCount', 'projectProgress'));
    }

    public function bulkDecideLater(Request $request)
    {
        $request->validate([
            'task_ids'       => 'required|array|min:1',
            'task_ids.*'     => 'exists:tasks,id',
            'action'         => 'required|in:not_needed,assign',
            'social_user_id' => 'required_if:action,assign|nullable|exists:users,id',
        ]);

        $tasks = Task::whereIn('id', $request->task_ids)
            ->whereIn('status', ['delivered', 'approved', 'archived'])
            ->whereNull('social_required')
            ->whereNull('social_assigned_to')
            ->get();

        if ($tasks->isEmpty()) {
            return back()->with('error', 'No eligible tasks found.');
        }

        if ($request->action === 'not_needed') {
            foreach ($tasks as $task) {
                $task->update(['social_required' => false, 'social_assigned_to' => null]);
            }
            return back()->with('success', $tasks->count() . ' task(s) marked as social not needed.');
        }

        $user = User::findOrFail($request->social_user_id);
        foreach ($tasks as $task) {
            $task->update([
                'social_required'    => true,
                'social_assigned_to' => $user->id,
            ]);
            $user->notify(new \App\Notifications\SocialMediaAssigned($task));
        }

        return back()->with('success', $tasks->count() . ' task(s) assigned to ' . $user->name . ' for social media.');
    }
}

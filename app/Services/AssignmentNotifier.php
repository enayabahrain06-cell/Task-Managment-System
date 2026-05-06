<?php

namespace App\Services;

use App\Mail\TaskAssignedMail;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AssignmentNotifier
{
    /**
     * Send email and/or WhatsApp when a task is assigned to a user.
     * Respects the email_on_assign and wa_on_assign settings.
     */
    public static function taskAssigned(Task $task, User $assignee): void
    {
        $task->loadMissing(['project', 'customer']);

        if (Setting::get('email_on_assign') === '1') {
            static::sendEmail($task, $assignee);
        }

        if (Setting::get('wa_on_assign') === '1') {
            static::sendWhatsApp($task, $assignee);
        }
    }

    // ── Email ─────────────────────────────────────────────────────────────────

    private static function sendEmail(Task $task, User $assignee): void
    {
        if (!$assignee->email) return;

        try {
            Mail::to($assignee->email)->send(new TaskAssignedMail($task, $assignee));
        } catch (\Throwable $e) {
            Log::error('AssignmentNotifier email failed', [
                'task_id' => $task->id,
                'user_id' => $assignee->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // ── WhatsApp ──────────────────────────────────────────────────────────────

    private static function sendWhatsApp(Task $task, User $assignee): void
    {
        if (!Setting::get('wa_enabled') || Setting::get('wa_enabled') !== '1') return;

        $phone = $assignee->phone ?? '';
        if (!$phone) return;

        $provider = Setting::get('wa_provider', 'ultramsg');
        $token    = Setting::get('wa_token', '');
        if (!$token) return;

        $body = static::buildWhatsAppBody($task, $assignee);

        try {
            match ($provider) {
                'ultramsg' => static::sendUltramsg($token, $phone, $body),
                'twilio'   => static::sendTwilio($token, $phone, $body),
                'meta'     => static::sendMeta($token, $phone, $body),
                default    => null,
            };
        } catch (\Throwable $e) {
            Log::error('AssignmentNotifier WhatsApp failed', [
                'task_id'  => $task->id,
                'user_id'  => $assignee->id,
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private static function buildWhatsAppBody(Task $task, User $assignee): string
    {
        $template    = Setting::get('wa_tpl_assigned', "Hello {user_name}!\n\nYou have been assigned a new task:\n📋 {task_title}\n📁 Project: {project_name}\n⏰ Deadline: {deadline}\n\n{company}");
        $companyName = Setting::get('company_name', config('app.name'));
        $projectName = $task->project?->name ?? 'N/A';
        $customer    = $task->customer?->name ?? $task->project?->customer?->name ?? 'N/A';
        $deadline    = $task->deadline ? $task->deadline->format('D, M d Y') : 'No deadline';

        return str_replace(
            ['{user_name}', '{task_title}', '{project_name}', '{customer_name}', '{deadline}', '{company}'],
            [$assignee->name, $task->title, $projectName, $customer, $deadline, $companyName],
            $template
        );
    }

    private static function sendUltramsg(string $token, string $phone, string $body): void
    {
        $instanceId = Setting::get('wa_instance_id', '');
        if (!$instanceId) return;

        Http::asForm()->post(
            "https://api.ultramsg.com/{$instanceId}/messages/chat",
            ['token' => $token, 'to' => $phone, 'body' => $body]
        );
    }

    private static function sendTwilio(string $token, string $phone, string $body): void
    {
        $accountSid = Setting::get('wa_account_sid', '');
        $fromNumber = Setting::get('wa_from_number', '');
        if (!$accountSid || !$fromNumber) return;

        Http::withBasicAuth($accountSid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => 'whatsapp:+' . ltrim($fromNumber, '+'),
                'To'   => 'whatsapp:+' . ltrim($phone, '+'),
                'Body' => $body,
            ]);
    }

    private static function sendMeta(string $token, string $phone, string $body): void
    {
        $phoneNumberId = Setting::get('wa_phone_number_id', '');
        if (!$phoneNumberId) return;

        Http::withToken($token)
            ->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $phone,
                'type'              => 'text',
                'text'              => ['body' => $body],
            ]);
    }
}

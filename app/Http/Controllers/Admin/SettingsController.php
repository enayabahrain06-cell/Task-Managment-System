<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    /** Default values for every setting key. */
    private array $defaults = [
        // General
        'app_name'              => 'Dash',
        'app_tagline'           => 'Task Management System',
        'department_name'       => 'Product Department',
        'copyright'             => '',
        'timezone'              => 'UTC',
        'date_format'           => 'Y-m-d',
        // Branding
        'company_name'          => 'Product Co.',
        'primary_color'         => '#4F46E5',
        'accent_color'          => '#6366F1',
        'logo_path'             => '',
        'favicon_path'          => '',
        'login_bg_type'         => 'gradient',
        'login_bg_color'        => '#e8eaf6',
        'login_bg_image'        => '',
        // Team
        'default_role'          => 'user',
        'allow_registration'    => '1',
        'max_tasks_per_user'    => '50',
        // Notifications
        'email_notifications'   => '1',
        'task_reminder_days'    => '2',
        'notify_on_assign'      => '1',
        'notify_on_complete'    => '1',
        // Security
        'min_password_length'   => '8',
        'session_timeout'       => '120',
        'require_strong_password' => '0',
    ];

    public function index()
    {
        $settings = array_merge(
            $this->defaults,
            Setting::all()->pluck('value', 'key')->toArray(),
            [
                'mail_host'         => config('mail.mailers.smtp.host',       'smtp.mailtrap.io'),
                'mail_port'         => config('mail.mailers.smtp.port',       587),
                'mail_username'     => config('mail.mailers.smtp.username',   ''),
                'mail_encryption'   => config('mail.mailers.smtp.encryption', 'tls'),
                'mail_from_address' => config('mail.from.address',            ''),
                'mail_from_name'    => config('mail.from.name',               config('app.name')),
            ]
        );

        $stats = [
            'users'    => User::count(),
            'projects' => Project::count(),
            'tasks'    => Task::count(),
            'db_size'  => $this->dbSizeKb(),
        ];

        return view('admin.settings', compact('settings', 'stats'));
    }

    public function updateGeneral(Request $request)
    {
        $request->validate([
            'app_name'        => 'required|string|max:60',
            'app_tagline'     => 'nullable|string|max:120',
            'department_name' => 'nullable|string|max:80',
            'timezone'        => 'required|string',
            'date_format'     => 'required|string|max:20',
        ]);

        Setting::setMany($request->only(
            'app_name', 'app_tagline', 'department_name', 'timezone', 'date_format'
        ));

        // Sync APP_NAME in .env so config('app.name') stays consistent
        $this->updateEnvKey('APP_NAME', $request->app_name);

        return back()->with('success', 'General settings saved.')->withFragment('general');
    }

    public function updateBranding(Request $request)
    {
        $request->validate([
            'company_name'     => 'required|string|max:60',
            'copyright'        => 'nullable|string|max:160',
            'primary_color'    => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color'     => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'logo'             => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'favicon'          => 'nullable|image|mimes:png,jpg,jpeg,ico,svg|max:512',
            'login_bg_type'    => 'nullable|in:gradient,color,image',
            'login_bg_color'   => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'login_bg_image'   => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        Setting::setMany($request->only('company_name', 'copyright', 'primary_color', 'accent_color'));

        // Login background type and color
        if ($request->filled('login_bg_type')) {
            Setting::set('login_bg_type', $request->login_bg_type);
        }
        if ($request->filled('login_bg_color')) {
            Setting::set('login_bg_color', $request->login_bg_color);
        }

        if ($request->hasFile('logo')) {
            $old = Setting::get('logo_path');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('logo')->store('branding', 'public');
            Setting::set('logo_path', $path);
        }

        if ($request->hasFile('favicon')) {
            $old = Setting::get('favicon_path');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('favicon')->store('branding', 'public');
            Setting::set('favicon_path', $path);
        }

        if ($request->hasFile('login_bg_image')) {
            $old = Setting::get('login_bg_image');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('login_bg_image')->store('branding', 'public');
            Setting::set('login_bg_image', $path);
        }

        if ($request->input('remove_logo') === '1') {
            $old = Setting::get('logo_path');
            if ($old) Storage::disk('public')->delete($old);
            Setting::set('logo_path', '');
        }

        if ($request->input('remove_favicon') === '1') {
            $old = Setting::get('favicon_path');
            if ($old) Storage::disk('public')->delete($old);
            Setting::set('favicon_path', '');
        }

        if ($request->input('remove_login_bg_image') === '1') {
            $old = Setting::get('login_bg_image');
            if ($old) Storage::disk('public')->delete($old);
            Setting::set('login_bg_image', '');
        }

        return back()->with('success', 'Branding saved.')->withFragment('branding');
    }

    public function updateTeam(Request $request)
    {
        $request->validate([
            'default_role'       => 'required|in:user,manager',
            'allow_registration' => 'nullable|boolean',
            'max_tasks_per_user' => 'required|integer|min:1|max:500',
        ]);

        Setting::setMany([
            'default_role'       => $request->default_role,
            'allow_registration' => $request->boolean('allow_registration') ? '1' : '0',
            'max_tasks_per_user' => $request->max_tasks_per_user,
        ]);

        return back()->with('success', 'Team settings saved.')->withFragment('team');
    }

    public function updateNotifications(Request $request)
    {
        Setting::setMany([
            'email_notifications' => $request->boolean('email_notifications') ? '1' : '0',
            'task_reminder_days'  => $request->input('task_reminder_days', 2),
            'notify_on_assign'    => $request->boolean('notify_on_assign') ? '1' : '0',
            'notify_on_complete'  => $request->boolean('notify_on_complete') ? '1' : '0',
        ]);

        return back()->with('success', 'Notification preferences saved.')->withFragment('notifications');
    }

    public function updateMail(Request $request)
    {
        $request->validate([
            'mail_host'         => 'required|string|max:120',
            'mail_port'         => 'required|integer|in:25,465,587,2525',
            'mail_username'     => 'required|string|max:120',
            'mail_from_address' => 'required|email|max:120',
            'mail_from_name'    => 'required|string|max:80',
            'mail_encryption'   => 'nullable|in:tls,ssl,starttls,',
        ]);

        $this->updateEnvKey('MAIL_MAILER',       'smtp');
        $this->updateEnvKey('MAIL_HOST',         $request->mail_host);
        $this->updateEnvKey('MAIL_PORT',         $request->mail_port);
        $this->updateEnvKey('MAIL_USERNAME',     $request->mail_username);
        $this->updateEnvKey('MAIL_ENCRYPTION',   $request->input('mail_encryption', 'tls'));
        $this->updateEnvKey('MAIL_FROM_ADDRESS', $request->mail_from_address);
        $this->updateEnvKey('MAIL_FROM_NAME',    $request->mail_from_name);

        if ($request->filled('mail_password')) {
            $this->updateEnvKey('MAIL_PASSWORD', $request->mail_password);
        }

        return back()->with('success', 'Mail settings saved.')->withFragment('mail');
    }

    public function testMail(Request $request)
    {
        $request->validate([
            'to'           => 'required|email',
            'host'         => 'required|string',
            'port'         => 'required|integer',
            'username'     => 'required|string',
            'from_address' => 'required|email',
            'from_name'    => 'required|string',
        ]);

        try {
            $password   = $request->filled('password')
                ? $request->password
                : config('mail.mailers.smtp.password', '');
            $encryption = strtolower($request->input('encryption', 'tls'));

            $user = rawurlencode($request->username);
            $pass = rawurlencode($password);
            $host = $request->host;
            $port = (int) $request->port;

            $dsn = match($encryption) {
                'ssl'   => "smtps://{$user}:{$pass}@{$host}:{$port}",
                'tls'   => "smtp://{$user}:{$pass}@{$host}:{$port}?encryption=tls",
                default => "smtp://{$user}:{$pass}@{$host}:{$port}",
            };

            $transport = \Symfony\Component\Mailer\Transport::fromDsn($dsn);
            $mailer    = new \Symfony\Component\Mailer\Mailer($transport);

            $html = '<div style="font-family:Inter,sans-serif;max-width:480px;margin:0 auto;padding:32px 24px;border:1px solid #E5E7EB;border-radius:12px;">'
                  . '<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">'
                  . '<div style="width:42px;height:42px;background:#EEF2FF;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;">✅</div>'
                  . '<h2 style="margin:0;font-size:18px;color:#111827;">SMTP Test Successful</h2></div>'
                  . '<p style="color:#374151;margin:0 0 12px;">Your SMTP configuration for <strong>' . e(config('app.name')) . '</strong> is working correctly.</p>'
                  . '<p style="color:#9CA3AF;font-size:12px;margin:0;border-top:1px solid #F3F4F6;padding-top:12px;">Host: ' . e($request->host) . ':' . $port . ' · Sent: ' . now()->format('F d, Y H:i') . '</p>'
                  . '</div>';

            $email = (new \Symfony\Component\Mime\Email())
                ->from(new \Symfony\Component\Mime\Address($request->from_address, $request->from_name))
                ->to($request->to)
                ->subject('SMTP Test — ' . config('app.name'))
                ->html($html)
                ->text('SMTP test from ' . config('app.name') . ' — configuration is working correctly. Sent: ' . now()->format('F d, Y H:i'));

            $mailer->send($email);

            return response()->json(['ok' => true, 'message' => 'Test email sent to ' . $request->to]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateSecurity(Request $request)
    {
        $request->validate([
            'min_password_length'    => 'required|integer|min:6|max:32',
            'session_timeout'        => 'required|integer|min:15|max:1440',
        ]);

        Setting::setMany([
            'min_password_length'      => $request->min_password_length,
            'session_timeout'          => $request->session_timeout,
            'require_strong_password'  => $request->boolean('require_strong_password') ? '1' : '0',
        ]);

        return back()->with('success', 'Security settings saved.')->withFragment('security');
    }

    // ── Exports ──────────────────────────────────────────────────────────

    public function exportUsers(): StreamedResponse
    {
        return $this->csvResponse('users_export_'.now()->format('Ymd').'.csv', function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Name', 'Email', 'Role', 'Tasks Assigned', 'Registered At']);
            User::withCount('tasks')->each(function ($u) use ($out) {
                fputcsv($out, [$u->id, $u->name, $u->email, $u->role, $u->tasks_count, $u->created_at->format('Y-m-d')]);
            });
            fclose($out);
        });
    }

    public function exportTasks(): StreamedResponse
    {
        return $this->csvResponse('tasks_export_'.now()->format('Ymd').'.csv', function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Title', 'Project', 'Assigned To', 'Status', 'Priority', 'Deadline', 'Created At']);
            Task::with(['project', 'assignee'])->each(function ($t) use ($out) {
                fputcsv($out, [
                    $t->id, $t->title,
                    $t->project->name  ?? '',
                    $t->assignee->name ?? '',
                    $t->status, $t->priority,
                    $t->deadline->format('Y-m-d'),
                    $t->created_at->format('Y-m-d'),
                ]);
            });
            fclose($out);
        });
    }

    public function exportProjects(): StreamedResponse
    {
        return $this->csvResponse('projects_export_'.now()->format('Ymd').'.csv', function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Name', 'Status', 'Deadline', 'Tasks Count', 'Created At']);
            Project::withCount('tasks')->each(function ($p) use ($out) {
                fputcsv($out, [$p->id, $p->name, $p->status, $p->deadline->format('Y-m-d'), $p->tasks_count, $p->created_at->format('Y-m-d')]);
            });
            fclose($out);
        });
    }

    // ── Full System Backup / Restore ─────────────────────────────────────

    public function downloadBackup()
    {
        $dbPath  = database_path('database.sqlite');
        $filename = 'backup_' . now()->format('Ymd_His') . '.sqlite';

        return response()->download($dbPath, $filename, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function restoreBackup(Request $request)
    {
        $request->validate(['backup_file' => 'required|file|max:51200']);

        $file    = $request->file('backup_file');
        $tmpPath = $file->getRealPath();

        // Verify it is a valid SQLite file by checking the header magic bytes
        $handle = fopen($tmpPath, 'rb');
        $magic  = fread($handle, 16);
        fclose($handle);

        if (strncmp($magic, "SQLite format 3\000", 16) !== 0) {
            return back()->withErrors(['backup_file' => 'Invalid file — please upload a .sqlite backup file created by this system.'])->withFragment('backup');
        }

        $dbPath = database_path('database.sqlite');

        // Close all DB connections before replacing the file
        DB::disconnect();

        // Keep a copy of the current DB just in case
        $safeCopy = $dbPath . '.pre_restore_' . now()->format('YmdHis');
        copy($dbPath, $safeCopy);

        try {
            copy($tmpPath, $dbPath);
            // Remove the safety copy on success
            @unlink($safeCopy);
        } catch (\Throwable $e) {
            // Roll back
            copy($safeCopy, $dbPath);
            @unlink($safeCopy);
            return back()->with('error', 'Restore failed: ' . $e->getMessage())->withFragment('backup');
        }

        return redirect()->route('admin.settings.index')->with('success', 'Full system restore completed successfully. All data has been restored.')->withFragment('backup');
    }

    // ── Restores ─────────────────────────────────────────────────────────

    public function restoreUsers(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        [$created, $updated, $skipped] = [0, 0, 0];
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));

        $need = ['name', 'email', 'role'];
        if (count(array_diff($need, $headers)) > 0) {
            fclose($handle);
            return back()->withErrors(['file' => 'CSV must have columns: name, email, role'])->withFragment('backup');
        }

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            $email = strtolower(trim($data['email'] ?? ''));
            $role  = in_array($data['role'] ?? '', ['admin','manager','user']) ? $data['role'] : 'user';
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $skipped++; continue; }

            $existing = User::where('email', $email)->first();
            if ($existing) {
                $existing->update(['name' => $data['name'] ?? $existing->name, 'role' => $role]);
                $updated++;
            } else {
                User::create(['name' => $data['name'] ?? 'User', 'email' => $email, 'role' => $role, 'password' => bcrypt(\Illuminate\Support\Str::random(16))]);
                $created++;
            }
        }
        fclose($handle);

        return back()->with('success', "Users restored: {$created} created, {$updated} updated, {$skipped} skipped.")->withFragment('backup');
    }

    public function restoreProjects(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        [$created, $updated, $skipped] = [0, 0, 0];
        $handle  = fopen($request->file('file')->getRealPath(), 'r');
        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));

        if (!in_array('name', $headers) || !in_array('deadline', $headers)) {
            fclose($handle);
            return back()->withErrors(['file' => 'CSV must have columns: name, deadline'])->withFragment('backup');
        }

        $adminId = auth()->id();
        while (($row = fgetcsv($handle)) !== false) {
            $data     = array_combine($headers, $row);
            $name     = trim($data['name'] ?? '');
            $deadline = trim($data['deadline'] ?? '');
            if (!$name || !$deadline) { $skipped++; continue; }

            $status   = in_array($data['status'] ?? '', ['active','completed','overdue']) ? $data['status'] : 'active';
            $existing = Project::where('name', $name)->first();
            if ($existing) {
                $existing->update(['status' => $status, 'deadline' => $deadline]);
                $updated++;
            } else {
                Project::create(['name' => $name, 'description' => $data['description'] ?? null, 'deadline' => $deadline, 'status' => $status, 'created_by' => $adminId]);
                $created++;
            }
        }
        fclose($handle);

        return back()->with('success', "Projects restored: {$created} created, {$updated} updated, {$skipped} skipped.")->withFragment('backup');
    }

    public function restoreTasks(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        [$created, $skipped] = [0, 0];
        $handle  = fopen($request->file('file')->getRealPath(), 'r');
        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));

        $need = ['title', 'project', 'assigned to', 'deadline'];
        if (count(array_diff($need, $headers)) > 0) {
            fclose($handle);
            return back()->withErrors(['file' => 'CSV must have columns: title, project, assigned to, deadline'])->withFragment('backup');
        }

        while (($row = fgetcsv($handle)) !== false) {
            $data    = array_combine($headers, $row);
            $title   = trim($data['title'] ?? '');
            $projName = trim($data['project'] ?? '');
            $userName = trim($data['assigned to'] ?? '');
            $deadline = trim($data['deadline'] ?? '');
            if (!$title || !$projName || !$deadline) { $skipped++; continue; }

            $project  = Project::where('name', $projName)->first();
            $assignee = User::where('name', $userName)->first();
            if (!$project || !$assignee) { $skipped++; continue; }

            $exists = Task::where('title', $title)->where('project_id', $project->id)->exists();
            if ($exists) { $skipped++; continue; }

            $priority = in_array($data['priority'] ?? '', ['low','medium','high']) ? $data['priority'] : 'medium';
            $status   = in_array($data['status'] ?? '', ['pending','in_progress','completed','pending_approval']) ? $data['status'] : 'pending';
            Task::create(['title' => $title, 'project_id' => $project->id, 'assigned_to' => $assignee->id, 'priority' => $priority, 'status' => $status, 'deadline' => $deadline]);
            $created++;
        }
        fclose($handle);

        return back()->with('success', "Tasks restored: {$created} created, {$skipped} skipped.")->withFragment('backup');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function csvResponse(string $filename, callable $callback): StreamedResponse
    {
        return response()->streamDownload(function () use ($callback) {
            $callback();
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Update a single key in the .env file. */
    private function updateEnvKey(string $key, string $value): void
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            return;
        }

        $escaped = str_contains($value, ' ') ? '"' . addslashes($value) . '"' : $value;
        $content = file_get_contents($envPath);

        if (preg_match('/^' . preg_quote($key, '/') . '=/m', $content)) {
            $content = preg_replace(
                '/^' . preg_quote($key, '/') . '=.*/m',
                $key . '=' . $escaped,
                $content
            );
        } else {
            $content .= PHP_EOL . $key . '=' . $escaped . PHP_EOL;
        }

        file_put_contents($envPath, $content);
    }

    private function dbSizeKb(): int
    {
        try {
            $path = database_path('database.sqlite');
            return file_exists($path) ? (int) round(filesize($path) / 1024) : 0;
        } catch (\Throwable) {
            return 0;
        }
    }
}

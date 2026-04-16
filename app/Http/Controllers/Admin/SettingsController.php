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
            Setting::all()->pluck('value', 'key')->toArray()
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
            'primary_color'    => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color'     => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'logo'             => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'favicon'          => 'nullable|image|mimes:png,jpg,jpeg,ico,svg|max:512',
            'login_bg_type'    => 'nullable|in:gradient,color,image',
            'login_bg_color'   => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'login_bg_image'   => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        Setting::setMany($request->only('company_name', 'primary_color', 'accent_color'));

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

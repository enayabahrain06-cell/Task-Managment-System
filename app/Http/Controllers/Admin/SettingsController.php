<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        'login_bg_position'     => 'center center',
        'login_bg_size'         => 'cover',
        'login_bg_attachment'   => 'fixed',
        'login_bg_overlay'      => '0',
        // Team & Files
        'default_role'          => 'user',
        'allow_registration'       => '0',
        'manager_can_edit_admin'   => '0',
        'manager_can_view_roles'   => '0',
        'max_tasks_per_user'    => '50',
        'default_task_priority' => 'medium',
        'max_upload_mb'         => '0',
        // Notifications
        'email_notifications'   => '1',
        'task_reminder_days'    => '2',
        'notify_on_assign'      => '1',
        'notify_on_complete'    => '1',
        'notify_on_approve'     => '1',
        'notify_on_reject'      => '1',
        'notify_on_comment'     => '1',
        'notify_on_deliver'     => '1',
        'notify_on_reassign'    => '1',
        'notify_on_transfer'    => '1',
        'notify_on_social'      => '1',
        'notify_on_report'      => '1',
        'notify_on_viewed'      => '0',
        'email_on_assign'       => '0',
        'wa_on_assign'          => '0',
        'notif_sound_type'      => 'chime',
        'notif_sound_volume'    => '0.3',
        // Security
        'min_password_length'      => '8',
        'session_timeout'          => '120',
        'require_strong_password'  => '0',
        'max_login_attempts'       => '5',
        // System
        'maintenance_mode'         => '0',
        // WhatsApp
        'wa_enabled'               => '0',
        'wa_provider'              => 'ultramsg',
        'wa_instance_id'           => '',
        'wa_account_sid'           => '',
        'wa_from_number'           => '',
        'wa_phone_number_id'       => '',
        'wa_waba_id'               => '',
        'wa_tpl_assigned'          => "Hello {user_name}!\n\nYou have been assigned a new task:\n📋 {task_title}\n📁 Project: {project_name}\n👤 Customer: {customer_name}\n⏰ Deadline: {deadline}\n\n{company}",
        'wa_tpl_approved'          => "Hi {user_name},\n\nYour task has been approved! ✅\n📋 {task_title}\n📁 {project_name}\n\nGreat work!\n{company}",
        'wa_tpl_reminder'          => "Hi {user_name},\n\nReminder: your task deadline is in {days_left} day(s).\n📋 {task_title}\n⏰ Due: {deadline}\n\n{company}",
        'wa_tpl_overdue'           => "Hi {user_name},\n\n⚠️ Your task is overdue:\n📋 {task_title}\n⏰ Was due: {deadline}\n\nPlease submit as soon as possible.\n{company}",
        'wa_tpl_social'            => "Hi {user_name},\n\nA task has been assigned to you for social media posting:\n📋 {task_title}\n📁 {project_name}\n👤 Customer: {customer_name}\n\n{company}",
        'wa_tpl_customer_design'   => "Hello {customer_name},\n\nYour design for \"{task_title}\" has been approved and is ready for your review. 🎨\n\n{admin_note}{design_link}\n\n{company}",
        'wa_tpl_customer_preview'  => "Hello {customer_name},\n\nYour design for \"{task_title}\" is ready for your review. We'd love your feedback before we finalize approval. 👀\n\n{design_link}\n\n{company}",
        // Storage / NAS
        'storage_gdrive_enabled'          => '0',
        'storage_gdrive_client_id'        => '',
        'storage_gdrive_client_sec'       => '',
        'storage_gdrive_folder_id'        => '',
        'storage_gdrive_sa_json'          => '',
        'storage_gdrive_delegate_email'   => '',
        'storage_onedrive_enabled'    => '0',
        'storage_onedrive_client_id'  => '',
        'storage_onedrive_client_sec' => '',
        'storage_onedrive_tenant_id'  => '',
        'storage_onedrive_folder_id'  => '',
        'storage_omv_enabled'   => '0',
        'storage_omv_protocol'  => 'smb',
        'storage_omv_host'      => '',
        'storage_omv_port'      => '',
        'storage_omv_username'  => '',
        'storage_omv_password'  => '',
        'storage_omv_path'      => '',
        'storage_omv_share'     => '',
        // Storage folder structure
        'storage_root_path'            => 'Marketing_System',
        'storage_auto_create_folders'  => '1',
        'storage_auto_move_files'      => '1',
        'storage_create_brand_assets'  => '1',
        'storage_file_naming_pattern'  => '{company}_{project}_{type}_{desc}_{date}_v{ver}',
        // Chat Agent
        'agent_name'       => 'Task Assistant',
        'agent_subtitle'   => 'Ask me anything about your tasks',
        'agent_welcome'    => "👋 Hi! I'm your **Task Assistant**. I can show your tasks, stats, overdue items, projects, and more.",
        'agent_color'      => '#4F46E5',
        'agent_icon'       => 'robot',
        'support_user_id'  => '',
    ];

    public function index()
    {
        if (!auth()->user()->hasPermission('manage_settings')) {
            abort(403, 'You do not have permission to access Settings.');
        }

        $dbSettings = Setting::all()->pluck('value', 'key')->toArray();

        // Don't let empty DB strings override defaults for template fields
        foreach ($dbSettings as $key => $value) {
            if (str_starts_with($key, 'wa_tpl_') && $value === '') {
                unset($dbSettings[$key]);
            }
        }

        $settings = array_merge(
            $this->defaults,
            $dbSettings,
            [
                'mail_host'         => config('mail.mailers.smtp.host',       'smtp.mailtrap.io'),
                'mail_port'         => config('mail.mailers.smtp.port',       587),
                'mail_username'     => config('mail.mailers.smtp.username',   ''),
                'mail_encryption'   => config('mail.mailers.smtp.encryption', 'tls'),
                'mail_from_address' => config('mail.from.address',            ''),
                'mail_from_name'    => config('mail.from.name',               config('app.name')),
            ]
        );

        $dbBytes = file_exists(database_path('database.sqlite')) ? filesize(database_path('database.sqlite')) : 0;
        $stats = [
            'users'      => User::count(),
            'projects'   => Project::where('is_quick', false)->count(),
            'tasks'      => Task::count(),
            'db_size'    => $dbBytes >= 1048576
                                ? round($dbBytes / 1048576, 1) . ' MB'
                                : round($dbBytes / 1024) . ' KB',
        ];

        $supportUsers = User::whereIn('role', ['admin', 'manager'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $aboutPageTeamMembers = User::where('status', 'active')
            ->where('role', '!=', 'admin')
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'job_title', 'role']);

        return view('admin.settings', compact('settings', 'stats', 'supportUsers', 'aboutPageTeamMembers'));
    }

    public function toggleDevMode()
    {
        $current = Setting::get('developer_mode', '0');
        $new     = $current === '1' ? '0' : '1';
        Setting::set('developer_mode', $new);
        return response()->json(['developer_mode' => $new === '1']);
    }

    public function clearCache()
    {
        \Artisan::call('cache:clear');
        \Artisan::call('config:clear');
        \Artisan::call('view:clear');
        \Artisan::call('route:clear');
        AuditLogger::log('settings.updated', null, 'Application cache cleared', []);
        return response()->json(['success' => true, 'message' => 'Cache cleared successfully']);
    }

    public function toggleMaintenance()
    {
        $current = Setting::get('maintenance_mode', '0');
        $new     = $current === '1' ? '0' : '1';
        Setting::set('maintenance_mode', $new);
        AuditLogger::log('settings.updated', null, 'Maintenance mode ' . ($new === '1' ? 'enabled' : 'disabled'), ['maintenance_mode' => $new]);
        return response()->json(['maintenance_mode' => $new === '1']);
    }

    public function toggleHourlyRate()
    {
        $current = Setting::get('hide_hourly_rate', '0');
        $new     = $current === '1' ? '0' : '1';
        Setting::set('hide_hourly_rate', $new);
        return response()->json(['hide_hourly_rate' => $new === '1']);
    }

    public function toggleApprovalCustomerNotify()
    {
        $current = Setting::get('hide_approval_customer_notify', '0');
        $new     = $current === '1' ? '0' : '1';
        Setting::set('hide_approval_customer_notify', $new);
        return response()->json(['hide_approval_customer_notify' => $new === '1']);
    }

    public function toggleHideWaWeb()
    {
        $current = Setting::get('hide_wa_web_button', '0');
        $new     = $current === '1' ? '0' : '1';
        Setting::set('hide_wa_web_button', $new);
        return response()->json(['hide_wa_web_button' => $new === '1']);
    }

    public function toggleHideSummarize()
    {
        $current = Setting::get('hide_summarize_button', '0');
        $new     = $current === '1' ? '0' : '1';
        Setting::set('hide_summarize_button', $new);
        return response()->json(['hide_summarize_button' => $new === '1']);
    }

    public function toggleHideFeaturesTab()
    {
        $current = Setting::get('hide_features_tab', '0');
        $new     = $current === '1' ? '0' : '1';
        Setting::set('hide_features_tab', $new);
        return response()->json(['hide_features_tab' => $new === '1']);
    }

    public function toggleManagerRolesAccess()
    {
        $current = Setting::get('manager_can_view_roles', '0');
        $new     = $current === '1' ? '0' : '1';
        Setting::set('manager_can_view_roles', $new);
        AuditLogger::log('settings.updated', null, 'Manager roles access ' . ($new === '1' ? 'enabled' : 'disabled'), ['manager_can_view_roles' => $new]);
        return response()->json(['manager_can_view_roles' => $new === '1']);
    }

    public function toggleManagerAdminAccess()
    {
        $current = Setting::get('manager_can_edit_admin', '0');
        $new     = $current === '1' ? '0' : '1';
        Setting::set('manager_can_edit_admin', $new);
        AuditLogger::log('settings.updated', null, 'Manager admin access ' . ($new === '1' ? 'enabled' : 'disabled'), ['manager_can_edit_admin' => $new]);
        return response()->json(['manager_can_edit_admin' => $new === '1']);
    }

    public function toggleElement(Request $request)
    {
        $request->validate(['key' => 'required|string|max:80', 'action' => 'required|in:hide,restore,add,remove']);

        if (in_array($request->action, ['add', 'remove'])) {
            // Extra (default-hidden) elements
            $extras = json_decode(Setting::get('shown_extras', '[]'), true) ?: [];
            if ($request->action === 'add') {
                if (!in_array($request->key, $extras)) $extras[] = $request->key;
            } else {
                $extras = array_values(array_filter($extras, fn($k) => $k !== $request->key));
            }
            Setting::set('shown_extras', json_encode($extras));
            return response()->json(['ok' => true, 'shown_extras' => $extras]);
        }

        // Default-visible elements
        $hidden = json_decode(Setting::get('hidden_elements', '[]'), true) ?: [];
        if ($request->action === 'hide') {
            if (!in_array($request->key, $hidden)) $hidden[] = $request->key;
        } else {
            $hidden = array_values(array_filter($hidden, fn($k) => $k !== $request->key));
        }
        Setting::set('hidden_elements', json_encode($hidden));
        return response()->json(['ok' => true, 'hidden' => $hidden]);
    }

    public function toggleNavItem(Request $request)
    {
        $request->validate(['key' => 'required|string|max:80', 'action' => 'required|in:hide,show']);

        $hidden = json_decode(Setting::get('nav_hidden', '[]'), true) ?: [];
        if ($request->action === 'hide') {
            if (!in_array($request->key, $hidden)) $hidden[] = $request->key;
        } else {
            $hidden = array_values(array_filter($hidden, fn($k) => $k !== $request->key));
        }
        Setting::set('nav_hidden', json_encode($hidden));
        return response()->json(['ok' => true, 'nav_hidden' => $hidden]);
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

        AuditLogger::log('settings.updated', null, 'General settings updated', ['section' => 'general']);

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
            'login_bg_type'       => 'nullable|in:gradient,color,image',
            'login_bg_color'      => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'login_bg_image'      => 'nullable|file|mimes:png,jpg,jpeg,webp,mp4,webm,mov,m4v',
            'login_team_artwork'    => 'nullable|array',
            'login_team_artwork.*'  => 'file|mimes:png,jpg,jpeg,webp,mp4,webm,mov,m4v',
            'remove_login_team_artwork'    => 'nullable|array',
            'remove_login_team_artwork.*'  => 'string',
            'login_deco_color'    => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'login_bg_position'   => 'nullable|string|in:top left,top center,top right,center left,center center,center right,bottom left,bottom center,bottom right',
            'login_bg_size'       => 'nullable|in:cover,contain,auto',
            'login_bg_attachment' => 'nullable|in:fixed,scroll',
            'login_bg_overlay'    => 'nullable|integer|min:0|max:80',
            'login_hero_tagline'  => 'nullable|string|max:120',
            'login_pill_text'     => 'nullable|string|max:60',
            'login_pill_accent'   => 'nullable|string|max:60',
        ]);

        Setting::setMany($request->only('company_name', 'copyright', 'primary_color', 'accent_color'));

        // Login background type and color
        if ($request->filled('login_bg_type')) {
            Setting::set('login_bg_type', $request->login_bg_type);
        }
        if ($request->filled('login_bg_color')) {
            Setting::set('login_bg_color', $request->login_bg_color);
        }
        if ($request->filled('login_deco_color')) {
            Setting::set('login_deco_color', $request->login_deco_color);
        }
        if ($request->filled('login_bg_position')) {
            Setting::set('login_bg_position', $request->login_bg_position);
        }
        if ($request->filled('login_bg_size')) {
            Setting::set('login_bg_size', $request->login_bg_size);
        }
        if ($request->filled('login_bg_attachment')) {
            Setting::set('login_bg_attachment', $request->login_bg_attachment);
        }
        Setting::set('login_bg_overlay', (int) $request->input('login_bg_overlay', 0));
        Setting::set('login_show_doodles', $request->has('login_show_doodles') ? '1' : '0');
        Setting::set('login_hero_tagline', $request->input('login_hero_tagline', 'Together we build. Together we achieve.'));
        Setting::set('login_pill_text', $request->input('login_pill_text', 'One Team. One Goal.'));
        Setting::set('login_pill_accent', $request->input('login_pill_accent', 'Unlimited Impact.'));

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

        $artworkRaw = Setting::get('login_team_artwork', '');
        $artworkList = json_decode($artworkRaw, true);
        if (!is_array($artworkList)) {
            $artworkList = $artworkRaw !== '' ? [$artworkRaw] : [];
        }

        $removeArtworkPaths = (array) $request->input('remove_login_team_artwork', []);
        if ($removeArtworkPaths) {
            foreach ($removeArtworkPaths as $path) {
                if (in_array($path, $artworkList, true)) {
                    Storage::disk('public')->delete($path);
                }
            }
            $artworkList = array_values(array_diff($artworkList, $removeArtworkPaths));
        }

        if ($request->hasFile('login_team_artwork')) {
            foreach ($request->file('login_team_artwork') as $file) {
                $artworkList[] = $file->store('branding', 'public');
            }
        }

        if ($removeArtworkPaths || $request->hasFile('login_team_artwork')) {
            Setting::set('login_team_artwork', json_encode(array_values($artworkList)));
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

        AuditLogger::log('settings.updated', null, 'Branding settings updated', ['section' => 'branding']);

        return back()->with('success', 'Branding saved.')->withFragment('branding');
    }

    public function updateAboutPage(Request $request)
    {
        $rules = [
            'about_page_intro'            => 'nullable|string|max:200',
            'about_page_cta_text'         => 'nullable|string|max:40',
            'about_page_cta_link'         => 'nullable|string|max:255',
            'about_page_services_heading' => 'nullable|string|max:60',
            'about_page_who_text'         => 'nullable|string|max:600',
            'about_page_mission'          => 'nullable|string|max:400',
            'about_page_vision'           => 'nullable|string|max:400',
        ];
        foreach ([1, 2, 3, 4, 5, 6] as $i) {
            $rules["about_page_service{$i}_title"] = 'nullable|string|max:60';
            $rules["about_page_service{$i}_desc"]  = 'nullable|string|max:160';
            $rules["about_page_value{$i}_title"]   = 'nullable|string|max:40';
            $rules["about_page_value{$i}_desc"]    = 'nullable|string|max:160';
        }
        foreach ([1, 2, 3, 4] as $i) {
            $rules["login_frame{$i}_title"] = 'nullable|string|max:40';
            $rules["login_frame{$i}_desc"]  = 'nullable|string|max:60';
        }
        $request->validate($rules);

        Setting::set('about_page_enabled', $request->has('about_page_enabled') ? '1' : '0');
        Setting::set('about_page_intro', $request->input('about_page_intro', ''));
        Setting::set('about_page_cta_enabled', $request->has('about_page_cta_enabled') ? '1' : '0');
        Setting::set('about_page_cta_text', $request->input('about_page_cta_text', 'Get in Touch'));
        Setting::set('about_page_cta_link', $request->input('about_page_cta_link', ''));
        Setting::set('about_page_services_heading', $request->input('about_page_services_heading', 'What We Do'));
        Setting::set('about_page_who_text', $request->input('about_page_who_text', ''));
        Setting::set('about_page_mission', $request->input('about_page_mission', ''));
        Setting::set('about_page_vision', $request->input('about_page_vision', ''));
        foreach ([1, 2, 3, 4, 5, 6] as $i) {
            Setting::set("about_page_service{$i}_title", $request->input("about_page_service{$i}_title", ''));
            Setting::set("about_page_service{$i}_desc", $request->input("about_page_service{$i}_desc", ''));
            Setting::set("about_page_value{$i}_title", $request->input("about_page_value{$i}_title", ''));
            Setting::set("about_page_value{$i}_desc", $request->input("about_page_value{$i}_desc", ''));
        }
        foreach ([1, 2, 3, 4] as $i) {
            Setting::set("login_frame{$i}_title", $request->input("login_frame{$i}_title", ''));
            Setting::set("login_frame{$i}_desc", $request->input("login_frame{$i}_desc", ''));
        }

        AuditLogger::log('settings.updated', null, 'About page settings updated', ['section' => 'about_page']);

        return back()->with('success', 'About page settings saved.')->withFragment('about_page');
    }

    public function updateAboutPageTeamPhoto(Request $request, User $user)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->avatar = $request->file('avatar')->store('avatars', 'public');
        $user->save();

        AuditLogger::log('settings.updated', $user, "Updated {$user->name}'s About Page photo", ['section' => 'about_page']);

        return back()->with('success', "{$user->name}'s photo updated.")->withFragment('about_page');
    }

    public function updateTeam(Request $request)
    {
        $request->validate([
            'default_role'          => 'required|in:user,manager',
            'allow_registration'    => 'nullable|boolean',
            'max_tasks_per_user'    => 'required|integer|min:1|max:500',
            'default_task_priority' => 'required|in:low,medium,high',
            'max_upload_mb'         => 'nullable|integer|min:0',
            'work_start_time'       => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'work_end_time'         => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'deadline_end_time'     => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'work_days'             => 'nullable|array',
            'work_days.*'           => 'integer|between:1,7',
        ]);

        $workDays = $request->input('work_days', [1,2,3,4,5]);

        Setting::setMany([
            'default_role'          => $request->default_role,
            'allow_registration'    => $request->boolean('allow_registration') ? '1' : '0',
            'max_tasks_per_user'    => $request->max_tasks_per_user,
            'default_task_priority' => $request->default_task_priority,
            'max_upload_mb'         => $request->max_upload_mb,
            'work_start_time'       => $request->input('work_start_time', '09:00'),
            'work_end_time'         => $request->input('work_end_time', '18:00'),
            'deadline_end_time'     => $request->input('deadline_end_time', '23:59'),
            'work_days'             => json_encode(array_map('intval', $workDays)),
        ]);

        AuditLogger::log('settings.updated', null, 'Team settings updated', ['section' => 'team']);

        return back()->with('success', 'Team settings saved.')->withFragment('team');
    }

    public function updateNotifications(Request $request)
    {
        $boolKeys = [
            'email_notifications', 'notify_on_assign', 'notify_on_complete',
            'notify_on_approve', 'notify_on_reject', 'notify_on_comment',
            'notify_on_deliver', 'notify_on_reassign', 'notify_on_transfer',
            'notify_on_social', 'notify_on_report', 'notify_on_viewed',
            'email_on_assign', 'wa_on_assign',
        ];

        $volume = (float) $request->input('notif_sound_volume', 0.3);
        $volume = max(0.05, min(1.0, $volume));

        $data = [
            'task_reminder_days'  => $request->input('task_reminder_days', 2),
            'notif_sound_type'    => in_array($request->input('notif_sound_type'), ['chime','ding','double','pop','alert','none'])
                                        ? $request->input('notif_sound_type') : 'chime',
            'notif_sound_volume'  => (string) round($volume, 2),
        ];
        foreach ($boolKeys as $key) {
            $data[$key] = $request->boolean($key) ? '1' : '0';
        }

        Setting::setMany($data);

        AuditLogger::log('settings.updated', null, 'Notification settings updated', ['section' => 'notifications']);

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
            'max_login_attempts'     => 'required|integer|min:3|max:20',
        ]);

        Setting::setMany([
            'min_password_length'      => $request->min_password_length,
            'session_timeout'          => $request->session_timeout,
            'require_strong_password'  => $request->boolean('require_strong_password') ? '1' : '0',
            'max_login_attempts'       => $request->max_login_attempts,
            'force_mfa'                => $request->boolean('force_mfa') ? '1' : '0',
        ]);

        AuditLogger::log('settings.updated', null, 'Security settings updated', ['section' => 'security']);

        return back()->with('success', 'Security settings saved.')->withFragment('security');
    }

    // ── Chat Agent ────────────────────────────────────────────────────────

    public function toggleHideAgent()
    {
        $current = Setting::get('hide_agent', '0');
        $new     = $current === '1' ? '0' : '1';
        Setting::set('hide_agent', $new);
        return response()->json(['hide_agent' => $new === '1']);
    }

    public function updateAgent(Request $request)
    {
        $request->validate([
            'agent_name'      => 'required|string|max:60',
            'agent_subtitle'  => 'nullable|string|max:120',
            'agent_welcome'   => 'nullable|string|max:400',
            'agent_color'     => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'agent_icon'      => 'required|in:robot,brain,comments,headset,bolt,star',
            'support_user_id' => 'nullable|exists:users,id',
        ]);

        Setting::setMany($request->only('agent_name', 'agent_subtitle', 'agent_welcome', 'agent_color', 'agent_icon'));
        Setting::set('support_user_id', $request->input('support_user_id', ''));

        AuditLogger::log('settings.updated', null, 'Chat agent settings updated', ['section' => 'agent']);

        return back()->with('success', 'Chat agent settings saved.')->withFragment('agent');
    }

    // ── WhatsApp ──────────────────────────────────────────────────────────

    public function updateWhatsapp(Request $request)
    {
        $request->validate([
            'wa_provider'        => 'required|in:ultramsg,twilio,meta',
            'wa_instance_id'     => 'nullable|string|max:80',
            'wa_account_sid'     => 'nullable|string|max:120',
            'wa_from_number'     => 'nullable|string|max:30',
            'wa_phone_number_id' => 'nullable|string|max:60',
            'wa_waba_id'         => 'nullable|string|max:60',
            'wa_tpl_assigned'          => 'nullable|string|max:2000',
            'wa_tpl_approved'          => 'nullable|string|max:2000',
            'wa_tpl_reminder'          => 'nullable|string|max:2000',
            'wa_tpl_overdue'           => 'nullable|string|max:2000',
            'wa_tpl_social'            => 'nullable|string|max:2000',
            'wa_tpl_customer_design'   => 'nullable|string|max:2000',
            'wa_tpl_customer_preview'  => 'nullable|string|max:2000',
        ]);

        $data = [
            'wa_enabled'         => $request->boolean('wa_enabled') ? '1' : '0',
            'wa_provider'        => $request->wa_provider,
            'wa_instance_id'     => $request->input('wa_instance_id', ''),
            'wa_account_sid'     => $request->input('wa_account_sid', ''),
            'wa_from_number'     => $request->input('wa_from_number', ''),
            'wa_phone_number_id' => $request->input('wa_phone_number_id', ''),
            'wa_waba_id'         => $request->input('wa_waba_id', ''),
            'wa_tpl_assigned'          => $request->input('wa_tpl_assigned', ''),
            'wa_tpl_approved'          => $request->input('wa_tpl_approved', ''),
            'wa_tpl_reminder'          => $request->input('wa_tpl_reminder', ''),
            'wa_tpl_overdue'           => $request->input('wa_tpl_overdue', ''),
            'wa_tpl_social'            => $request->input('wa_tpl_social', ''),
            'wa_tpl_customer_design'   => $request->input('wa_tpl_customer_design', ''),
            'wa_tpl_customer_preview'  => $request->input('wa_tpl_customer_preview', ''),
        ];

        if ($request->filled('wa_token')) {
            $data['wa_token'] = $request->wa_token;
        }

        Setting::setMany($data);

        AuditLogger::log('settings.updated', null, 'WhatsApp settings updated', ['section' => 'whatsapp', 'provider' => $request->wa_provider]);

        return back()->with('success', 'WhatsApp settings saved.')->withFragment('whatsapp');
    }

    public function updateStorage(Request $request)
    {
        $data = [
            'storage_gdrive_enabled'         => $request->boolean('storage_gdrive_enabled') ? '1' : '0',
            'storage_gdrive_client_id'       => $request->input('storage_gdrive_client_id', ''),
            'storage_gdrive_folder_id'       => $request->input('storage_gdrive_folder_id', ''),
            'storage_gdrive_delegate_email'  => $request->input('storage_gdrive_delegate_email', ''),
            'storage_onedrive_enabled'    => $request->boolean('storage_onedrive_enabled') ? '1' : '0',
            'storage_onedrive_client_id'  => $request->input('storage_onedrive_client_id', ''),
            'storage_onedrive_tenant_id'  => $request->input('storage_onedrive_tenant_id', ''),
            'storage_onedrive_folder_id'  => $request->input('storage_onedrive_folder_id', ''),
            'storage_omv_enabled'   => $request->boolean('storage_omv_enabled') ? '1' : '0',
            'storage_omv_only'      => $request->boolean('storage_omv_only') ? '1' : '0',
            'storage_omv_protocol'  => $request->input('storage_omv_protocol', 'smb'),
            'storage_omv_host'      => $request->input('storage_omv_host', ''),
            'storage_omv_port'      => $request->input('storage_omv_port', ''),
            'storage_omv_username'  => $request->input('storage_omv_username', ''),
            'storage_omv_share'     => $request->input('storage_omv_share', ''),
            'storage_omv_path'      => $request->input('storage_omv_path', ''),
            'storage_root_path'           => $request->input('storage_root_path', 'Marketing_System'),
            'storage_auto_create_folders' => $request->boolean('storage_auto_create_folders') ? '1' : '0',
            'storage_auto_move_files'     => $request->boolean('storage_auto_move_files') ? '1' : '0',
            'storage_create_brand_assets' => $request->boolean('storage_create_brand_assets') ? '1' : '0',
            'storage_file_naming_pattern' => $request->input('storage_file_naming_pattern', '{company}_{project}_{type}_{desc}_{date}_v{ver}'),
        ];

        if ($request->filled('storage_gdrive_client_sec')) {
            $data['storage_gdrive_client_sec'] = $request->storage_gdrive_client_sec;
        }
        if ($request->filled('storage_gdrive_sa_json')) {
            $data['storage_gdrive_sa_json'] = $request->storage_gdrive_sa_json;
        }
        if ($request->filled('storage_onedrive_client_sec')) {
            $data['storage_onedrive_client_sec'] = $request->storage_onedrive_client_sec;
        }
        if ($request->filled('storage_omv_password')) {
            $data['storage_omv_password'] = $request->storage_omv_password;
        }

        Setting::setMany($data);

        AuditLogger::log('settings.updated', null, 'Storage settings updated', ['section' => 'storage']);

        return back()->with('success', 'Storage settings saved.')->withFragment('storage');
    }

    public function updateNasSchema(Request $request)
    {
        $request->validate(['schema_json' => 'required|string']);

        $decoded = json_decode($request->schema_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return back()->with('error', 'Invalid JSON — please check the schema and try again.')->withFragment('storage');
        }

        Setting::set('storage_nas_schema', json_encode($decoded));
        AuditLogger::log('settings.updated', null, 'NAS folder schema updated', ['section' => 'storage']);

        return back()->with('success', 'NAS folder schema saved.')->withFragment('storage');
    }

    public function resetNasSchema(Request $request)
    {
        Setting::where('key', 'storage_nas_schema')->delete();
        AuditLogger::log('settings.updated', null, 'NAS folder schema reset to default', ['section' => 'storage']);

        return back()->with('success', 'NAS folder schema reset to default.')->withFragment('storage');
    }

    public function testStorageGdrive(Request $request)
    {
        $clientId  = Setting::get('storage_gdrive_client_id', '');
        $clientSec = Setting::get('storage_gdrive_client_sec', '');

        if (!$clientId || !$clientSec) {
            return response()->json(['ok' => false, 'message' => 'Client ID and Client Secret are required. Save them first.']);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->timeout(10)
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $clientId,
                    'client_secret' => $clientSec,
                ]);

            // Google will return 400 with "unsupported_grant_type" — that still proves
            // the credentials reached the server and the Client ID format is valid.
            $body = $response->json();
            if ($response->status() === 401 || ($body['error'] ?? '') === 'invalid_client') {
                return response()->json(['ok' => false, 'message' => 'Invalid Client ID or Client Secret — authentication failed.']);
            }
            // Any other response (including 400 unsupported_grant_type) means credentials reached Google OK.
            return response()->json(['ok' => true, 'message' => 'Google API reachable and credentials accepted. ✓']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
    }

    public function testStorageOnedrive(Request $request)
    {
        $clientId  = Setting::get('storage_onedrive_client_id', '');
        $clientSec = Setting::get('storage_onedrive_client_sec', '');
        $tenantId  = Setting::get('storage_onedrive_tenant_id', 'common');

        if (!$clientId || !$clientSec) {
            return response()->json(['ok' => false, 'message' => 'Client ID and Client Secret are required. Save them first.']);
        }

        $tenant = $tenantId ?: 'common';

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->timeout(10)
                ->post("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $clientId,
                    'client_secret' => $clientSec,
                    'scope'         => 'https://graph.microsoft.com/.default',
                ]);

            $body = $response->json();
            if (isset($body['access_token'])) {
                return response()->json(['ok' => true, 'message' => 'OneDrive connected successfully — access token obtained. ✓']);
            }
            $error = $body['error_description'] ?? ($body['error'] ?? 'Authentication failed');
            return response()->json(['ok' => false, 'message' => $error]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
    }

    public function testStorageOmv(Request $request)
    {
        $host     = Setting::get('storage_omv_host', '');
        $port     = Setting::get('storage_omv_port', '');
        $protocol = Setting::get('storage_omv_protocol', 'smb');
        $username = Setting::get('storage_omv_username', '');
        $password = Setting::get('storage_omv_password', '');
        $path     = Setting::get('storage_omv_path', '');

        if (!$host) {
            return response()->json(['ok' => false, 'message' => 'Host / IP address is required. Save it first.']);
        }

        $defaultPorts = ['smb' => 445, 'nfs' => 2049, 'webdav' => 80, 'ftp' => 21];
        $checkPort    = (int) ($port ?: ($defaultPorts[$protocol] ?? 80));

        // WebDAV: full HTTP check with credentials
        if ($protocol === 'webdav') {
            $url = $path ?: "http://{$host}:{$checkPort}";
            if (!str_starts_with($url, 'http')) {
                $url = "http://{$url}";
            }
            try {
                $resp = \Illuminate\Support\Facades\Http::withBasicAuth($username, $password)
                    ->timeout(8)
                    ->withOptions(['verify' => false])
                    ->send('PROPFIND', $url, ['headers' => ['Depth' => '0']]);

                if (in_array($resp->status(), [207, 200, 401])) {
                    if ($resp->status() === 401) {
                        return response()->json(['ok' => false, 'message' => 'WebDAV server reachable but credentials were rejected (401 Unauthorized).']);
                    }
                    return response()->json(['ok' => true, 'message' => "WebDAV server responded ({$resp->status()}). Connection OK. ✓"]);
                }
                return response()->json(['ok' => false, 'message' => "WebDAV responded with HTTP {$resp->status()}."]);
            } catch (\Throwable $e) {
                return response()->json(['ok' => false, 'message' => 'WebDAV connection failed: ' . $e->getMessage()]);
            }
        }

        // FTP: PHP native check
        if ($protocol === 'ftp' && function_exists('ftp_connect')) {
            try {
                $conn = @ftp_connect($host, $checkPort, 8);
                if (!$conn) {
                    return response()->json(['ok' => false, 'message' => "Could not connect to FTP at {$host}:{$checkPort}."]);
                }
                $login = $username ? @ftp_login($conn, $username, $password) : false;
                ftp_close($conn);
                if ($username && !$login) {
                    return response()->json(['ok' => false, 'message' => 'FTP server reachable but login failed — check username/password.']);
                }
                return response()->json(['ok' => true, 'message' => "FTP connected to {$host}:{$checkPort} successfully. ✓"]);
            } catch (\Throwable $e) {
                return response()->json(['ok' => false, 'message' => 'FTP error: ' . $e->getMessage()]);
            }
        }

        // SMB / NFS / fallback: TCP socket reachability check
        $errno  = 0;
        $errstr = '';
        $sock   = @fsockopen($host, $checkPort, $errno, $errstr, 8);
        if ($sock) {
            fclose($sock);
            $label = strtoupper($protocol);
            return response()->json(['ok' => true, 'message' => "{$label} port {$checkPort} on {$host} is open and reachable. ✓"]);
        }

        return response()->json(['ok' => false, 'message' => "Cannot reach {$host}:{$checkPort} — {$errstr} (errno {$errno})."]);
    }

    public function migrateLocalToNas(Request $request)
    {
        $nas = app(NasService::class);

        if (!$nas->isEnabled()) {
            return response()->json(['ok' => false, 'message' => 'NAS is not fully configured. Fill in Host, Share, Username and Password and save first.']);
        }

        $success = 0;
        $failed  = 0;
        $skipped = 0;

        // Submissions
        TaskSubmission::whereNotNull('file_path')->whereNull('nas_path')
            ->with('task.project.customer', 'task.customer')
            ->chunk(50, function ($rows) use ($nas, &$success, &$failed, &$skipped) {
                foreach ($rows as $sub) {
                    if (!$sub->task) { $skipped++; continue; }
                    if (!Storage::disk('public')->exists($sub->file_path)) { $skipped++; continue; }
                    $nasPath = $nas->copyToNas($sub->task, $sub->file_path, $sub->original_filename ?? basename($sub->file_path), '03_Working', $sub->version ?? 0);
                    if ($nasPath) {
                        $sub->update(['nas_path' => $nasPath]);
                        $success++;
                    } else {
                        $failed++;
                    }
                }
            });

        // Comment attachments
        TaskComment::whereNotNull('file_path')->whereNull('nas_path')
            ->with('task.project.customer', 'task.customer')
            ->chunk(50, function ($rows) use ($nas, &$success, &$failed, &$skipped) {
                foreach ($rows as $comment) {
                    if (!$comment->task) { $skipped++; continue; }
                    if (!Storage::disk('public')->exists($comment->file_path)) { $skipped++; continue; }
                    $nasPath = $nas->copyToNasReference($comment->task, $comment->file_path, $comment->original_filename ?? basename($comment->file_path));
                    if ($nasPath) {
                        $comment->update(['nas_path' => $nasPath]);
                        $success++;
                    } else {
                        $failed++;
                    }
                }
            });

        // Project/task attachments
        ProjectAttachment::where('type', 'file')->whereNotNull('path')->whereNull('nas_path')
            ->with('task.project.customer', 'task.customer')
            ->chunk(50, function ($rows) use ($nas, &$success, &$failed, &$skipped) {
                foreach ($rows as $att) {
                    if (!$att->task) { $skipped++; continue; }
                    if (!Storage::disk('public')->exists($att->path)) { $skipped++; continue; }
                    $nasPath = $nas->copyToNasReference($att->task, $att->path, $att->name);
                    if ($nasPath) {
                        $att->update(['nas_path' => $nasPath]);
                        $success++;
                    } else {
                        $failed++;
                    }
                }
            });

        $total = $success + $failed + $skipped;

        if ($total === 0) {
            return response()->json(['ok' => true, 'message' => 'No local-only files found. Everything is already on NAS or there are no files yet.', 'results' => compact('success', 'failed', 'skipped')]);
        }

        $msg = "{$success} of {$total} files migrated to NAS successfully.";
        if ($failed  > 0) $msg .= " {$failed} failed (check NAS connection).";
        if ($skipped > 0) $msg .= " {$skipped} skipped (file missing from disk or no linked task).";

        return response()->json(['ok' => $failed === 0, 'message' => $msg, 'results' => compact('success', 'failed', 'skipped')]);
    }

    public function recoverNasPaths(Request $request)
    {
        $nas = app(NasService::class);

        if (!$nas->isEnabled()) {
            return response()->json(['ok' => false, 'message' => 'NAS is not configured.']);
        }

        $recovered = 0;
        $notFound  = 0;

        // Submissions
        TaskSubmission::whereNotNull('file_path')->whereNull('nas_path')
            ->with('task.project.customer', 'task.customer')
            ->chunk(50, function ($rows) use ($nas, &$recovered, &$notFound) {
                foreach ($rows as $sub) {
                    if (!$sub->task) { $notFound++; continue; }
                    $nasPath = $nas->findNasPath($sub->task, $sub->original_filename ?? basename($sub->file_path), '03_Working', $sub->version ?? 0);
                    if ($nasPath) {
                        $sub->update(['nas_path' => $nasPath]);
                        $recovered++;
                    } else {
                        $notFound++;
                    }
                }
            });

        // Comment attachments
        TaskComment::whereNotNull('file_path')->whereNull('nas_path')
            ->with('task.project.customer', 'task.customer')
            ->chunk(50, function ($rows) use ($nas, &$recovered, &$notFound) {
                foreach ($rows as $comment) {
                    if (!$comment->task) { $notFound++; continue; }
                    $nasPath = $nas->findNasPathReference($comment->task, $comment->original_filename ?? basename($comment->file_path));
                    if ($nasPath) {
                        $comment->update(['nas_path' => $nasPath]);
                        $recovered++;
                    } else {
                        $notFound++;
                    }
                }
            });

        // Project/task attachments
        ProjectAttachment::where('type', 'file')->whereNotNull('path')->whereNull('nas_path')
            ->with('task.project.customer', 'task.customer')
            ->chunk(50, function ($rows) use ($nas, &$recovered, &$notFound) {
                foreach ($rows as $att) {
                    if (!$att->task) { $notFound++; continue; }
                    $nasPath = $nas->findNasPathReference($att->task, $att->name);
                    if ($nasPath) {
                        $att->update(['nas_path' => $nasPath]);
                        $recovered++;
                    } else {
                        $notFound++;
                    }
                }
            });

        $total = $recovered + $notFound;

        if ($total === 0) {
            return response()->json(['ok' => true, 'message' => 'No missing NAS paths found — everything is already linked.', 'recovered' => 0, 'notFound' => 0]);
        }

        $msg = "{$recovered} of {$total} records recovered — NAS paths restored in database.";
        if ($notFound > 0) $msg .= " {$notFound} could not be found on NAS (files were never uploaded or are in an unknown location).";

        return response()->json(['ok' => true, 'message' => $msg, 'recovered' => $recovered, 'notFound' => $notFound]);
    }

    public function backupToGdrive(Request $request)
    {
        $saJson        = Setting::get('storage_gdrive_sa_json', '');
        $folderId      = Setting::get('storage_gdrive_folder_id', '');
        $delegateEmail = Setting::get('storage_gdrive_delegate_email', '');

        if (!$saJson) {
            return response()->json(['ok' => false, 'message' => 'Service Account JSON is not set. Paste it in the Google Drive card and save.']);
        }

        if (!$folderId && !$delegateEmail) {
            return response()->json(['ok' => false, 'message' => 'Set either a Shared Drive Folder ID or a Delegate Email (for domain-wide delegation) so the backup has somewhere to go.']);
        }

        $credentials = json_decode($saJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($credentials['private_key'], $credentials['client_email'])) {
            return response()->json(['ok' => false, 'message' => 'Invalid Service Account JSON — check the format.']);
        }

        try {
            $token    = $this->googleServiceAccountToken($credentials, $delegateEmail);
            $dbPath   = database_path('database.sqlite');
            $filename = 'backup_' . now()->format('Ymd_His') . '.sqlite';
            $file     = $this->driveUpload($token, $dbPath, $filename, 'application/x-sqlite3', $folderId);

            $fileId   = $file['id'] ?? 'unknown';
            $webLink  = $file['webViewLink'] ?? null;

            AuditLogger::log('settings.backup', null, 'Database backed up to Google Drive', ['file' => $filename, 'drive_id' => $fileId]);

            return response()->json([
                'ok'      => true,
                'message' => "Backup uploaded: {$filename}",
                'link'    => $webLink,
                'file_id' => $fileId,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Backup failed: ' . $e->getMessage()]);
        }
    }

    private function googleServiceAccountToken(array $creds, string $delegateEmail = ''): string
    {
        $now     = time();
        $header  = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims  = [
            'iss'   => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];
        if ($delegateEmail) {
            $claims['sub'] = $delegateEmail;
        }
        $payload = $this->b64url(json_encode($claims));

        $sigInput = "{$header}.{$payload}";
        $key      = openssl_pkey_get_private($creds['private_key']);
        if (!$key) {
            throw new \RuntimeException('Could not load private key from Service Account JSON.');
        }
        openssl_sign($sigInput, $sig, $key, OPENSSL_ALGO_SHA256);
        $jwt = "{$sigInput}." . $this->b64url($sig);

        $resp = \Illuminate\Support\Facades\Http::asForm()->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

        $body = $resp->json();
        if (!isset($body['access_token'])) {
            throw new \RuntimeException($body['error_description'] ?? ($body['error'] ?? 'Failed to get access token'));
        }

        return $body['access_token'];
    }

    private function driveUpload(string $token, string $filePath, string $name, string $mime, string $folderId = ''): array
    {
        $meta = ['name' => $name, 'mimeType' => $mime];
        if ($folderId) {
            $meta['parents'] = [$folderId];
        }

        $content  = file_get_contents($filePath);
        $boundary = 'gdrive_' . uniqid();
        $body     = "--{$boundary}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n"
                  . json_encode($meta)
                  . "\r\n--{$boundary}\r\nContent-Type: {$mime}\r\n\r\n"
                  . $content
                  . "\r\n--{$boundary}--";

        $resp = \Illuminate\Support\Facades\Http::withToken($token)
            ->withBody($body, "multipart/related; boundary={$boundary}")
            ->timeout(120)
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true&fields=id,name,webViewLink');

        if (!$resp->successful()) {
            $err = $resp->json()['error']['message'] ?? "HTTP {$resp->status()}";
            throw new \RuntimeException($err);
        }

        return $resp->json();
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function testWhatsapp(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:30']);

        $enabled  = Setting::get('wa_enabled', '0') === '1';
        $provider = Setting::get('wa_provider', 'ultramsg');
        $token    = Setting::get('wa_token', '');
        $phone    = preg_replace('/\D/', '', $request->phone);

        if (!$enabled) {
            return response()->json(['ok' => false, 'message' => 'WhatsApp is disabled. Enable it and save first.'], 422);
        }
        if (!$token) {
            return response()->json(['ok' => false, 'message' => 'API token is not set.'], 422);
        }
        if (!$phone) {
            return response()->json(['ok' => false, 'message' => 'Invalid phone number.'], 422);
        }

        $appName = Setting::get('app_name', config('app.name'));
        $body    = "This is a test message from {$appName}. WhatsApp integration is working correctly! ✅";

        try {
            $result = $this->sendWhatsappMessage($provider, $token, $phone, $body);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function broadcastWhatsapp(Request $request)
    {
        $request->validate([
            'recipients' => 'required|in:all,custom',
            'body'       => 'required|string|max:4096',
            'numbers'    => 'nullable|string',
        ]);

        $enabled  = Setting::get('wa_enabled', '0') === '1';
        $provider = Setting::get('wa_provider', 'ultramsg');
        $token    = Setting::get('wa_token', '');

        if (!$enabled || !$token) {
            return response()->json(['ok' => false, 'message' => 'WhatsApp is not configured or disabled.'], 422);
        }

        $appName    = Setting::get('app_name', config('app.name'));
        $company    = Setting::get('company_name', $appName);
        $sent       = 0;
        $failed     = 0;
        $errors     = [];

        if ($request->recipients === 'custom') {
            // Parse comma or newline separated numbers
            $rawNumbers = preg_split('/[\r\n,]+/', $request->input('numbers', ''));
            foreach ($rawNumbers as $raw) {
                $digits = preg_replace('/\D/', '', trim($raw));
                if (!$digits) continue;
                $body = str_replace(['{company}'], [$company], $request->body);
                $res = $this->sendWhatsappMessage($provider, $token, $digits, $body);
                $res['ok'] ? $sent++ : ($failed++ && $errors[] = "+{$digits}: " . $res['message']);
            }
        } else {
            // Send to all customers with phones
            $customers = \App\Models\Customer::whereNotNull('phone')->where('phone', '!=', '')->get();
            foreach ($customers as $customer) {
                $digits = preg_replace('/\D/', '', $customer->phone);
                if (!$digits) continue;
                $body = str_replace(
                    ['{customer_name}', '{customer_phone}', '{customer_email}', '{company}'],
                    [$customer->name, $customer->phone, $customer->email ?? '', $company],
                    $request->body
                );
                $res = $this->sendWhatsappMessage($provider, $token, $digits, $body);
                $res['ok'] ? $sent++ : ($failed++ && $errors[] = $customer->name . ': ' . $res['message']);
            }
        }

        return response()->json([
            'ok'     => true,
            'sent'   => $sent,
            'failed' => $failed,
            'errors' => array_slice($errors, 0, 5),
            'message' => "Done: {$sent} sent" . ($failed ? ", {$failed} failed" : '') . '.',
        ]);
    }

    private function sendWhatsappMessage(string $provider, string $token, string $phone, string $body): array
    {
        return match ($provider) {
            'ultramsg' => $this->sendUltramsg($token, $phone, $body),
            'twilio'   => $this->sendTwilio($token, $phone, $body),
            'meta'     => $this->sendMeta($token, $phone, $body),
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

        $data = $response->json();
        if ($response->successful() && isset($data['sent']) && $data['sent'] === 'true') {
            return ['ok' => true, 'message' => 'Message sent successfully.'];
        }
        return ['ok' => false, 'message' => $data['error'] ?? $data['message'] ?? 'UltraMsg error.'];
    }

    private function sendTwilio(string $token, string $phone, string $body): array
    {
        $accountSid = Setting::get('wa_account_sid', '');
        $fromNumber = Setting::get('wa_from_number', '');
        if (!$accountSid || !$fromNumber) return ['ok' => false, 'message' => 'Twilio Account SID / From Number not set.'];

        $response = Http::withBasicAuth($accountSid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => 'whatsapp:+' . ltrim($fromNumber, '+'),
                'To'   => 'whatsapp:+' . ltrim($phone, '+'),
                'Body' => $body,
            ]);

        $data = $response->json();
        if ($response->successful() && isset($data['sid'])) {
            return ['ok' => true, 'message' => 'Message sent (SID: ' . $data['sid'] . ').'];
        }
        return ['ok' => false, 'message' => $data['message'] ?? 'Twilio error.'];
    }

    private function sendMeta(string $token, string $phone, string $body): array
    {
        $phoneNumberId = Setting::get('wa_phone_number_id', '');
        if (!$phoneNumberId) return ['ok' => false, 'message' => 'Meta Phone Number ID not set.'];

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $phone,
                'type'              => 'text',
                'text'              => ['body' => $body],
            ]);

        $data = $response->json();
        if ($response->successful() && isset($data['messages'])) {
            return ['ok' => true, 'message' => 'Message sent via Meta Cloud API.'];
        }
        return ['ok' => false, 'message' => $data['error']['message'] ?? 'Meta API error.'];
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

    public function verifyRestorePassword(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        $ok = \Illuminate\Support\Facades\Hash::check($request->input('password'), auth()->user()->password);
        return response()->json(['ok' => $ok]);
    }

    public function downloadBackup()
    {
        $stamp       = now()->format('Ymd_His');
        $filename    = 'backup_' . $stamp . '.zip';
        $dbPath      = database_path('database.sqlite');
        $storageBase = storage_path('app/public');

        $tmpDir = sys_get_temp_dir() . '/bk_' . $stamp;
        mkdir($tmpDir);
        mkdir($tmpDir . '/database');
        mkdir($tmpDir . '/reports');
        symlink($dbPath, $tmpDir . '/database/database.sqlite');
        symlink($storageBase, $tmpDir . '/storage');

        // Generate snapshot PDFs into the reports/ folder
        $this->generateBackupPdfs($tmpDir . '/reports');

        $zipCmd = 'cd ' . escapeshellarg($tmpDir) . ' && zip -0 -r - database storage reports 2>/dev/null';

        return response()->stream(function () use ($zipCmd, $tmpDir) {
            $proc = popen($zipCmd, 'r');
            while (!feof($proc)) {
                echo fread($proc, 65536);
                flush();
            }
            pclose($proc);
            @unlink($tmpDir . '/database/database.sqlite');
            @unlink($tmpDir . '/storage');
            @unlink($tmpDir . '/reports/system-summary.pdf');
            @unlink($tmpDir . '/reports/user-performance.pdf');
            foreach (glob($tmpDir . '/reports/users/*.pdf') ?: [] as $f) @unlink($f);
            @rmdir($tmpDir . '/reports/users');
            @rmdir($tmpDir . '/database');
            @rmdir($tmpDir . '/reports');
            @rmdir($tmpDir);
        }, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Accel-Buffering'   => 'no',
        ]);
    }

    private function imageToBase64(string $storagePath): string
    {
        $full = storage_path('app/public/' . ltrim($storagePath, '/'));
        if (!file_exists($full)) return '';
        $ext  = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $mime = match($ext) { 'png' => 'png', 'gif' => 'gif', 'webp' => 'webp', default => 'jpeg' };
        return 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($full));
    }

    private function generateBackupPdfs(string $dir): void
    {
        $appName     = Setting::get('app_name', config('app.name', 'TaskMS'));
        $generatedAt = now()->format('F j, Y  H:i');
        $doneStatuses = ['approved', 'delivered'];

        // Embed logo as base64 so DomPDF can render it without HTTP requests
        $logoPath   = Setting::get('logo_path', '');
        $logoBase64 = $logoPath ? $this->imageToBase64($logoPath) : '';

        // ── Summary PDF data ────────────────────────────────────────────────
        $totalTasks     = Task::count();
        $completedTasks = Task::whereIn('status', $doneStatuses)->count();
        $pendingTasks   = Task::whereIn('status', ['draft','assigned','viewed','in_progress','paused'])->count();
        $totalUsers     = User::where('status', 'active')->count();
        $totalProjects  = Project::where('is_quick', false)->count();
        $completionRate = $totalTasks > 0 ? round($completedTasks / $totalTasks * 100) : 0;

        $tasksByStatus = Task::select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')
            ->orderByDesc('cnt')
            ->get();

        $projects = Project::where('is_quick', false)
            ->withCount(['tasks as task_count', 'tasks as done_count' => fn($q) => $q->whereIn('status', $doneStatuses)])
            ->orderByDesc('task_count')
            ->get();

        $summaryPdf = Pdf::loadView('admin.backup.pdf-summary', compact(
            'appName', 'generatedAt', 'totalTasks', 'completedTasks',
            'pendingTasks', 'totalUsers', 'totalProjects', 'completionRate',
            'tasksByStatus', 'projects', 'logoBase64'
        ))->setPaper('a4', 'portrait');

        file_put_contents($dir . '/system-summary.pdf', $summaryPdf->output());

        // ── User performance PDF data ───────────────────────────────────────
        $allUsers   = User::orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'manager' THEN 2 ELSE 3 END")
                          ->orderBy('name')
                          ->get();

        $userData = $allUsers->map(function ($u) use ($doneStatuses) {
            $scope = fn($q) => $q->where('assigned_to', $u->id)
                ->orWhereExists(fn($x) => $x->selectRaw('1')
                    ->from('task_assignees')
                    ->whereColumn('task_assignees.task_id', 'tasks.id')
                    ->where('task_assignees.user_id', $u->id));

            $total     = Task::where($scope)->count();
            $completed = Task::where($scope)->whereIn('status', $doneStatuses)->count();
            $pending   = Task::where($scope)->whereIn('status', ['assigned','viewed','in_progress','paused'])->count();
            $inReview  = Task::where($scope)->whereIn('status', ['submitted','revision_requested'])->count();

            return (object) [
                'name'      => $u->name,
                'role'      => $u->role,
                'total'     => $total,
                'completed' => $completed,
                'pending'   => $pending,
                'in_review' => $inReview,
                'last_seen' => $u->last_seen_at ? $u->last_seen_at->format('d M Y') : 'Never',
            ];
        });

        $totalAssigned  = $userData->sum('total');
        $totalCompleted = $userData->sum('completed');
        $overallRate    = $totalAssigned > 0 ? round($totalCompleted / $totalAssigned * 100) : 0;
        $totalUsersAll  = $allUsers->count();
        $activeUsers    = $allUsers->where('status', 'active')->count();

        $perfPdf = Pdf::loadView('admin.backup.pdf-performance', [
            'appName'        => $appName,
            'generatedAt'    => $generatedAt,
            'users'          => $userData,
            'totalUsers'     => $totalUsersAll,
            'activeUsers'    => $activeUsers,
            'totalAssigned'  => $totalAssigned,
            'totalCompleted' => $totalCompleted,
            'overallRate'    => $overallRate,
            'logoBase64'     => $logoBase64,
        ])->setPaper('a4', 'landscape');

        file_put_contents($dir . '/user-performance.pdf', $perfPdf->output());

        // ── Per-user individual PDFs ────────────────────────────────────────
        $usersDir = $dir . '/users';
        mkdir($usersDir, 0755, true);

        foreach ($allUsers as $u) {
            $scope = fn($q) => $q->where('assigned_to', $u->id)
                ->orWhereExists(fn($x) => $x->selectRaw('1')
                    ->from('task_assignees')
                    ->whereColumn('task_assignees.task_id', 'tasks.id')
                    ->where('task_assignees.user_id', $u->id));

            $tasks          = Task::where($scope)->with('project')->orderByDesc('created_at')->get();
            $totalTasks     = $tasks->count();
            $completedTasks = $tasks->whereIn('status', $doneStatuses)->count();
            $pendingTasks   = $tasks->whereIn('status', ['assigned','viewed','in_progress','paused'])->count();
            $inReviewTasks  = $tasks->whereIn('status', ['submitted','revision_requested'])->count();
            $completionRate = $totalTasks > 0 ? round($completedTasks / $totalTasks * 100) : 0;
            $avatarBase64   = $u->avatar ? $this->imageToBase64($u->avatar) : '';

            $userPdf = Pdf::loadView('admin.backup.pdf-user-report', [
                'appName'        => $appName,
                'generatedAt'    => $generatedAt,
                'user'           => $u,
                'tasks'          => $tasks,
                'totalTasks'     => $totalTasks,
                'completedTasks' => $completedTasks,
                'pendingTasks'   => $pendingTasks,
                'inReviewTasks'  => $inReviewTasks,
                'completionRate' => $completionRate,
                'logoBase64'     => $logoBase64,
                'avatarBase64'   => $avatarBase64,
            ])->setPaper('a4', 'portrait');

            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($u->name));
            file_put_contents($usersDir . '/' . $slug . '.pdf', $userPdf->output());
        }
    }

    public function downloadBackupSqlite()
    {
        $dbPath = database_path('database.sqlite');
        $stamp  = now()->format('Ymd_His');
        return response()->download($dbPath, 'backup_' . $stamp . '.sqlite', [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    public function restoreFromNas(Request $request)
    {
        $request->validate(['nas_path' => 'required|string|max:500']);

        $nas     = app(\App\Services\NasService::class);
        $nasPath = $request->input('nas_path');
        $tmpPath = sys_get_temp_dir() . '/restore_nas_' . now()->format('YmdHis') . '_' . basename($nasPath);

        if (!$nas->pullFromNas($nasPath, $tmpPath)) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Failed to download backup from NAS. Check NAS connection.')
                ->with('_fragment', 'backup');
        }

        $result = $this->performRestore($tmpPath);
        @unlink($tmpPath);
        return $result;
    }

    public function saveBackupToNas()
    {
        $nas = app(\App\Services\NasService::class);
        if (!$nas->isEnabled()) {
            return response()->json(['ok' => false, 'msg' => 'NAS is not enabled.']);
        }

        $stamp       = now()->format('Ymd_His');
        $filename    = 'backup_' . $stamp . '.zip';
        $zipPath     = sys_get_temp_dir() . '/' . $filename;
        $dbPath      = database_path('database.sqlite');
        $storageBase = storage_path('app/public');

        // Build temp dir with symlinks for clean ZIP paths
        $tmpDir = sys_get_temp_dir() . '/bk_' . $stamp;
        mkdir($tmpDir);
        mkdir($tmpDir . '/database');
        mkdir($tmpDir . '/reports');
        symlink($dbPath, $tmpDir . '/database/database.sqlite');
        symlink($storageBase, $tmpDir . '/storage');
        $this->generateBackupPdfs($tmpDir . '/reports');

        exec('cd ' . escapeshellarg($tmpDir) . ' && zip -0 -r ' . escapeshellarg($zipPath) . ' database storage reports 2>/dev/null', $out, $code);

        @unlink($tmpDir . '/database/database.sqlite');
        @unlink($tmpDir . '/storage');
        @unlink($tmpDir . '/reports/system-summary.pdf');
        @unlink($tmpDir . '/reports/user-performance.pdf');
        foreach (glob($tmpDir . '/reports/users/*.pdf') ?: [] as $f) @unlink($f);
        @rmdir($tmpDir . '/reports/users');
        @rmdir($tmpDir . '/database');
        @rmdir($tmpDir . '/reports');
        @rmdir($tmpDir);

        if ($code !== 0 || !file_exists($zipPath)) {
            return response()->json(['ok' => false, 'msg' => 'Failed to create ZIP archive.']);
        }

        $ok = $nas->saveBackupToNas($zipPath, $filename);
        @unlink($zipPath);

        if (!$ok) {
            return response()->json(['ok' => false, 'msg' => 'ZIP created but upload to NAS failed. Check NAS connection.']);
        }

        AuditLogger::log('system.backup', null, 'Full backup saved to NAS: ' . $filename, []);

        return response()->json(['ok' => true, 'msg' => 'Backup saved to NAS: Backups/' . now()->format('Y/Y-m') . '/' . $filename]);
    }

    public function saveBackupSqliteToNas()
    {
        $nas = app(\App\Services\NasService::class);
        if (!$nas->isEnabled()) {
            return response()->json(['ok' => false, 'msg' => 'NAS is not enabled.']);
        }

        $dbPath   = database_path('database.sqlite');
        $stamp    = now()->format('Ymd_His');
        $filename = 'backup_' . $stamp . '.sqlite';

        $ok = $nas->saveBackupToNas($dbPath, $filename);

        if (!$ok) {
            return response()->json(['ok' => false, 'msg' => 'Upload to NAS failed. Check NAS connection.']);
        }

        AuditLogger::log('system.backup', null, 'Database-only backup saved to NAS: ' . $filename, []);

        return response()->json(['ok' => true, 'msg' => 'Database saved to NAS: Backups/' . now()->format('Y/Y-m') . '/' . $filename]);
    }

    public function listNasBackups()
    {
        $nas = app(\App\Services\NasService::class);
        if (!$nas->isEnabled()) {
            return response()->json(['files' => [], 'error' => 'NAS not enabled']);
        }
        return response()->json(['files' => $nas->listNasBackups()]);
    }

    public function listServerBackups()
    {
        $dir   = storage_path('app/backups');
        $files = [];
        foreach (glob($dir . '/*.{zip,sqlite}', GLOB_BRACE) as $path) {
            $files[] = [
                'name'     => basename($path),
                'size'     => round(filesize($path) / 1048576, 1),
                'modified' => date('Y-m-d H:i', filemtime($path)),
            ];
        }
        usort($files, fn($a, $b) => strcmp($b['modified'], $a['modified']));
        return response()->json(['files' => $files, 'path' => $dir]);
    }

    public function restoreFromServer(Request $request)
    {
        $request->validate(['filename' => 'required|string|max:255']);

        $filename = basename($request->input('filename'));
        $path     = storage_path('app/backups/' . $filename);

        if (!file_exists($path)) {
            return back()->with('error', 'File not found on server: ' . $filename);
        }

        return $this->performRestore($path);
    }

    public function restoreBackup(Request $request)
    {
        $request->validate(['backup_file' => 'required|file']);
        return $this->performRestore($request->file('backup_file')->getRealPath());
    }

    private function performRestore(string $filePath)
    {
        $handle = fopen($filePath, 'rb');
        $magic  = fread($handle, 16);
        fclose($handle);

        $isZip    = substr($magic, 0, 4) === "PK\x03\x04";
        $isSqlite = strncmp($magic, "SQLite format 3\000", 16) === 0;

        if (!$isZip && !$isSqlite) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Invalid file — must be a .zip or .sqlite backup created by this system.')
                ->with('_fragment', 'backup');
        }

        $dbPath   = database_path('database.sqlite');
        $safeCopy = $dbPath . '.pre_restore_' . now()->format('YmdHis');
        copy($dbPath, $safeCopy);

        try {
            if ($isSqlite) {
                DB::disconnect();
                copy($filePath, $dbPath);

            } else {
                $zip = new \ZipArchive();
                if ($zip->open($filePath) !== true) {
                    throw new \RuntimeException('Could not open ZIP archive.');
                }

                $dbEntry = $zip->getFromName('database/database.sqlite');
                if ($dbEntry === false) {
                    $zip->close();
                    throw new \RuntimeException('ZIP does not contain database/database.sqlite.');
                }
                DB::disconnect();
                file_put_contents($dbPath, $dbEntry);

                // Restore files — supports both path formats:
                //   new: storage/branding/file.jpg  → storage/app/public/branding/file.jpg
                //   old: storage/public/branding/file.jpg → storage/app/public/branding/file.jpg
                $storageBase = storage_path('app/public');
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (str_starts_with($name, 'storage/public/')) {
                        $relative = substr($name, strlen('storage/public/'));
                    } elseif (str_starts_with($name, 'storage/') && !str_ends_with($name, '/')) {
                        $relative = substr($name, strlen('storage/'));
                    } else {
                        continue;
                    }
                    if ($relative === '') continue;
                    $dest = $storageBase . '/' . $relative;
                    @mkdir(dirname($dest), 0755, true);
                    file_put_contents($dest, $zip->getFromIndex($i));
                }
                $zip->close();
            }

            @unlink($safeCopy);

        } catch (\Throwable $e) {
            copy($safeCopy, $dbPath);
            @unlink($safeCopy);
            return redirect()->route('admin.settings.index')
                ->with('error', 'Restore failed: ' . $e->getMessage())
                ->with('_fragment', 'backup');
        }

        AuditLogger::log('system.restored', null, 'Full system backup restored', []);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Full system restore completed. Database and all files have been restored.')
            ->with('_fragment', 'backup');
    }

    // ── Restores ─────────────────────────────────────────────────────────

    private function settingsBackupRedirect(array $errors = [], string $success = '')
    {
        $redirect = redirect()->route('admin.settings.index')->with('_fragment', 'backup');
        if ($errors) return $redirect->withErrors($errors);
        return $redirect->with('success', $success);
    }

    public function restoreUsers(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|max:5120|mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/octet-stream']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->settingsBackupRedirect($e->errors());
        }

        [$created, $updated, $skipped] = [0, 0, 0];
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));

        $need = ['name', 'email', 'role'];
        if (count(array_diff($need, $headers)) > 0) {
            fclose($handle);
            return $this->settingsBackupRedirect(['file' => 'CSV must have columns: name, email, role']);
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

        return $this->settingsBackupRedirect([], "Users restored: {$created} created, {$updated} updated, {$skipped} skipped.");
    }

    public function restoreProjects(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|max:5120|mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/octet-stream']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->settingsBackupRedirect($e->errors());
        }

        [$created, $updated, $skipped] = [0, 0, 0];
        $handle  = fopen($request->file('file')->getRealPath(), 'r');
        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));

        if (!in_array('name', $headers) || !in_array('deadline', $headers)) {
            fclose($handle);
            return $this->settingsBackupRedirect(['file' => 'CSV must have columns: name, deadline']);
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

        return $this->settingsBackupRedirect([], "Projects restored: {$created} created, {$updated} updated, {$skipped} skipped.");
    }

    public function restoreTasks(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|max:5120|mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/octet-stream']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->settingsBackupRedirect($e->errors());
        }

        [$created, $skipped] = [0, 0];
        $handle  = fopen($request->file('file')->getRealPath(), 'r');
        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));

        $need = ['title', 'project', 'assigned to', 'deadline'];
        if (count(array_diff($need, $headers)) > 0) {
            fclose($handle);
            return $this->settingsBackupRedirect(['file' => 'CSV must have columns: title, project, assigned to, deadline']);
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
            $status   = in_array($data['status'] ?? '', ['pending','in_progress','completed','submitted','assigned','draft']) ? $data['status'] : 'pending';
            Task::create(['title' => $title, 'project_id' => $project->id, 'assigned_to' => $assignee->id, 'priority' => $priority, 'status' => $status, 'deadline' => $deadline]);
            $created++;
        }
        fclose($handle);

        return $this->settingsBackupRedirect([], "Tasks restored: {$created} created, {$skipped} skipped.");
    }

    // ── Clear Data ───────────────────────────────────────────────────────

    public function clearData(Request $request)
    {
        $type = $request->input('type');

        $allowed = ['notifications', 'messages', 'audit_logs', 'task_activity', 'tasks_projects', 'full_reset'];
        if (!in_array($type, $allowed)) {
            return back()->with('error', 'Invalid clear type.')->withFragment('danger');
        }

        if ($type === 'full_reset') {
            $request->validate([
                'password'       => 'required|string',
                'signatory_name' => 'required|string|max:150',
                'agreed'         => 'required|accepted',
            ]);

            if (!\Illuminate\Support\Facades\Hash::check($request->input('password'), auth()->user()->password)) {
                return back()->with('error', 'Password incorrect. Full data reset was cancelled.')->withFragment('danger');
            }
        }

        // Disable foreign key checks for SQLite so deletes don't fail on constraints
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            match ($type) {
                'notifications'  => DB::table('notifications')->delete(),

                'messages'       => DB::table('messages')->delete(),

                'audit_logs'     => DB::table('audit_logs')->delete(),

                'task_activity'  => (function () {
                    DB::table('activity_reactions')->delete();
                    DB::table('activity_replies')->delete();
                    DB::table('task_logs')->delete();
                    DB::table('task_comments')->delete();
                    DB::table('task_submissions')->delete();
                })(),

                'tasks_projects' => (function () {
                    DB::table('task_social_posts')->delete();
                    DB::table('task_transfers')->delete();
                    DB::table('task_assignees')->delete();
                    DB::table('task_submissions')->delete();
                    DB::table('task_comments')->delete();
                    DB::table('task_logs')->delete();
                    DB::table('activity_reactions')->delete();
                    DB::table('activity_replies')->delete();
                    DB::table('tasks')->delete();
                    DB::table('project_attachments')->delete();
                    DB::table('project_user')->delete();
                    DB::table('projects')->delete();
                    DB::table('calendar_events')->delete();
                    DB::table('meetings')->delete();
                })(),

                'full_reset'     => (function () {
                    DB::table('notifications')->delete();
                    DB::table('messages')->delete();
                    DB::table('message_group_users')->delete();
                    DB::table('message_groups')->delete();
                    DB::table('audit_logs')->delete();
                    DB::table('task_social_posts')->delete();
                    DB::table('task_transfers')->delete();
                    DB::table('task_assignees')->delete();
                    DB::table('task_submissions')->delete();
                    DB::table('task_comments')->delete();
                    DB::table('task_logs')->delete();
                    DB::table('activity_reactions')->delete();
                    DB::table('activity_replies')->delete();
                    DB::table('tasks')->delete();
                    DB::table('project_attachments')->delete();
                    DB::table('project_user')->delete();
                    DB::table('projects')->delete();
                    DB::table('calendar_events')->delete();
                    DB::table('meetings')->delete();
                })(),
            };
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        $labels = [
            'notifications'  => 'All notifications cleared.',
            'messages'       => 'All messages cleared.',
            'audit_logs'     => 'Audit logs cleared.',
            'task_activity'  => 'Task logs, comments and submissions cleared.',
            'tasks_projects' => 'All tasks, projects and social media posts cleared.',
            'full_reset'     => 'Full data reset completed. Users and settings are untouched.',
        ];

        $auditMeta = ['type' => $type];
        if ($type === 'full_reset') {
            $auditMeta['signatory_name']  = $request->input('signatory_name');
            $auditMeta['signatory_email'] = auth()->user()->email;
            $auditMeta['acknowledged_at'] = now()->toDateTimeString();
        }
        AuditLogger::log('data.cleared', null, 'Data cleared: ' . $type, $auditMeta);

        return back()->with('success', $labels[$type])->withFragment('danger');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function csvResponse(string $filename, callable $callback): StreamedResponse
    {
        return response()->streamDownload(function () use ($callback) {
            $callback();
        }, $filename, ['Content-Type' => 'text/csv']);
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

    private function updateEnvKey(string $key, string $value): void
    {
        $path    = base_path('.env');
        $content = file_get_contents($path);
        $escaped = preg_quote('=' . env($key), '/');

        if (preg_match("/^{$key}={$escaped}/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } elseif (preg_match("/^{$key}=/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}";
        }

        file_put_contents($path, $content);
    }

    // ── Feature toggles ────────────────────────────────────────────────────────

    private function featureToggle(string $key): \Illuminate\Http\JsonResponse
    {
        $new = Setting::get($key, '1') === '1' ? '0' : '1';
        Setting::set($key, $new);
        return response()->json([$key => $new === '1']);
    }

    public function disableAllFeatures()
    {
        $keys = [
            'show_gantt_chart', 'show_excel_export', 'show_workload_view',
            'show_task_dependencies', 'show_recurring_tasks',
            'show_time_tracking', 'show_task_templates',
        ];
        foreach ($keys as $key) {
            Setting::set($key, '0');
        }
        return response()->json(['ok' => true]);
    }

    public function toggleGanttChart()          { return $this->featureToggle('show_gantt_chart'); }
    public function toggleExcelExport()         { return $this->featureToggle('show_excel_export'); }
    public function toggleWorkloadView()        { return $this->featureToggle('show_workload_view'); }
    public function toggleTaskDependencies()    { return $this->featureToggle('show_task_dependencies'); }
    public function toggleRecurringTasks()      { return $this->featureToggle('show_recurring_tasks'); }
    public function toggleTimeTracking()        { return $this->featureToggle('show_time_tracking'); }
    public function toggleTaskTemplates()       { return $this->featureToggle('show_task_templates'); }
}

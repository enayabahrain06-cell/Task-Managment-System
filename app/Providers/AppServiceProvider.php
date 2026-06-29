<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Setting;
use App\Observers\DatabaseNotificationObserver;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Publish MQTT push when any DB notification is created
        DatabaseNotification::observe(DatabaseNotificationObserver::class);

        // Load all boot-time settings in a single query instead of 10 individual ones
        try {
            $bootSettings = Setting::getMany([
                'timezone', 'date_format',
                'storage_omv_protocol', 'storage_omv_host', 'storage_omv_port',
                'storage_omv_username', 'storage_omv_password', 'storage_omv_path',
                'storage_omv_share', 'storage_nas_schema',
                'min_password_length', 'require_strong_password',
            ]);

            $timezone   = $bootSettings['timezone']    ?? config('app.timezone', 'UTC');
            $dateFormat = $bootSettings['date_format'] ?? 'M d, Y';
            config(['app.timezone'    => $timezone]);
            config(['app.date_format' => $dateFormat]);
            date_default_timezone_set($timezone);
            \Carbon\Carbon::setLocale(config('app.locale', 'en'));

            $minLen = max(6, (int)($bootSettings['min_password_length'] ?? 8));
            $strong = ($bootSettings['require_strong_password'] ?? '0') === '1';
            Password::defaults(function () use ($minLen, $strong) {
                $rule = Password::min($minLen);
                return $strong ? $rule->mixedCase()->numbers()->symbols() : $rule;
            });

            config(['nas-file-manager.connection' => [
                'protocol'   => $bootSettings['storage_omv_protocol'] ?? 'smb',
                'host'       => $bootSettings['storage_omv_host']     ?? '',
                'port'       => (int) (($bootSettings['storage_omv_port'] ?? '') ?: 445),
                'username'   => $bootSettings['storage_omv_username'] ?? '',
                'password'   => $bootSettings['storage_omv_password'] ?? '',
                'path'       => $bootSettings['storage_omv_path']     ?? '/',
                'smb_share'  => $bootSettings['storage_omv_share']    ?? '',
                'smb_domain' => '',
            ]]);

            $savedSchema = $bootSettings['storage_nas_schema'] ?? '';
            if ($savedSchema) {
                $decoded = json_decode($savedSchema, true);
                if (is_array($decoded)) {
                    config(['nas-file-manager.schema' => $decoded]);
                }
            }
        } catch (\Throwable) {
            // settings table may not exist yet (migrations)
        }

        // Share recent projects with the navigation sidebar
        View::composer('layouts.navigation', function ($view) {
            if (auth()->check()) {
                $recentProjects = Project::latest()->take(3)->get();
                $view->with('recentProjects', $recentProjects);
            }
        });

        // Share notifications with the main app layout
        View::composer('layouts.app', function ($view) {
            if (auth()->check()) {
                try {
                    $user = auth()->user();

                    // Auto-delete old notifications — runs on ~1% of requests to avoid per-request write
                    if (random_int(1, 100) === 1) {
                        $user->notifications()->where('created_at', '<', now()->subDays(30))->delete();
                    }

                    // Only show unread notifications — read ones are dismissed from the list
                    $notifications     = $user->unreadNotifications()->latest()->get();
                    $notificationCount = $notifications->count();

                    $view->with('notifications', $notifications);
                    $view->with('notificationCount', $notificationCount);
                } catch (\Throwable) {
                    $view->with('notifications', collect());
                    $view->with('notificationCount', 0);
                }
            } else {
                $view->with('notifications', collect());
                $view->with('notificationCount', 0);
            }
        });

        // Share global app settings with all views
        View::composer('*', function ($view) {
            try {
                $appSettings = Setting::getMany(['app_name','app_tagline','company_name','primary_color','department_name','logo_path','favicon_path','login_bg_type','login_bg_color','login_bg_image','copyright','developer_mode','hidden_elements','shown_extras','nav_hidden','maintenance_mode','hide_approval_customer_notify','hide_hourly_rate','hide_wa_web_button','notif_sound_type','notif_sound_volume','agent_name','agent_subtitle','agent_welcome','agent_color','agent_icon','hide_agent','support_user_id','hide_summarize_button']);
                $view->with('appSettings', array_merge([
                    'app_name'        => 'Dash',
                    'app_tagline'     => '',
                    'company_name'    => 'Product Co.',
                    'primary_color'   => '#4F46E5',
                    'department_name' => 'Product Department',
                    'logo_path'       => '',
                    'favicon_path'    => '',
                    'login_bg_type'   => 'gradient',
                    'login_bg_color'  => '#e8eaf6',
                    'login_bg_image'  => '',
                    'copyright'       => '',
                    'developer_mode'   => '0',
                    'hidden_elements'  => '[]',
                    'shown_extras'     => '[]',
                    'maintenance_mode'              => '0',
                    'hide_approval_customer_notify' => '0',
                    'hide_hourly_rate'              => '0',
                    'hide_wa_web_button'            => '0',
                    'notif_sound_type'              => 'chime',
                    'notif_sound_volume'            => '0.3',
                    'agent_name'      => 'Task Assistant',
                    'agent_subtitle'  => 'Ask me anything about your tasks',
                    'agent_welcome'   => "👋 Hi! I'm your **Task Assistant**. I can show your tasks, stats, overdue items, projects, and more.",
                    'agent_color'     => '#4F46E5',
                    'agent_icon'      => 'robot',
                    'hide_agent'             => '0',
                    'support_user_id'        => '',
                    'hide_summarize_button'  => '0',
                ], $appSettings));
            } catch (\Throwable) {
                $view->with('appSettings', [
                    'app_name'         => 'Dash',
                    'app_tagline'      => '',
                    'company_name'     => 'Product Co.',
                    'primary_color'    => '#4F46E5',
                    'department_name'  => 'Product Department',
                    'logo_path'        => '',
                    'favicon_path'     => '',
                    'login_bg_type'    => 'gradient',
                    'login_bg_color'   => '#e8eaf6',
                    'login_bg_image'   => '',
                    'copyright'        => '',
                    'developer_mode'   => '0',
                    'hidden_elements'  => '[]',
                    'shown_extras'     => '[]',
                    'maintenance_mode'              => '0',
                    'hide_approval_customer_notify' => '0',
                    'hide_hourly_rate'              => '0',
                    'hide_wa_web_button'            => '0',
                    'notif_sound_type'              => 'chime',
                    'notif_sound_volume'            => '0.3',
                    'agent_name'      => 'Task Assistant',
                    'agent_subtitle'  => 'Ask me anything about your tasks',
                    'agent_welcome'   => "👋 Hi! I'm your **Task Assistant**. I can show your tasks, stats, overdue items, projects, and more.",
                    'agent_color'     => '#4F46E5',
                    'agent_icon'      => 'robot',
                    'hide_agent'            => '0',
                    'support_user_id'       => '',
                    'hide_summarize_button' => '0',
                ]);
            }
        });
    }
}

<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Setting;
use App\Observers\DatabaseNotificationObserver;
use Carbon\Carbon;
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

            $timezone = $bootSettings['timezone'] ?? config('app.timezone', 'UTC');
            $dateFormat = $bootSettings['date_format'] ?? 'M d, Y';
            config(['app.timezone' => $timezone]);
            config(['app.date_format' => $dateFormat]);
            date_default_timezone_set($timezone);
            Carbon::setLocale(config('app.locale', 'en'));

            $minLen = max(6, (int) ($bootSettings['min_password_length'] ?? 8));
            $strong = ($bootSettings['require_strong_password'] ?? '0') === '1';
            Password::defaults(function () use ($minLen, $strong) {
                $rule = Password::min($minLen);

                return $strong ? $rule->mixedCase()->numbers()->symbols() : $rule;
            });

            config(['nas-file-manager.connection' => [
                'protocol' => $bootSettings['storage_omv_protocol'] ?? 'smb',
                'host' => $bootSettings['storage_omv_host'] ?? '',
                'port' => (int) (($bootSettings['storage_omv_port'] ?? '') ?: 445),
                'username' => $bootSettings['storage_omv_username'] ?? '',
                'password' => $bootSettings['storage_omv_password'] ?? '',
                'path' => $bootSettings['storage_omv_path'] ?? '/',
                'smb_share' => $bootSettings['storage_omv_share'] ?? '',
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
                    $notifications = $user->unreadNotifications()->latest()->get();
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
                $aboutPageKeys = [
                    'about_page_enabled', 'about_page_intro', 'about_page_cta_enabled', 'about_page_cta_text', 'about_page_cta_link',
                    'about_page_services_heading', 'about_page_who_text', 'about_page_mission', 'about_page_vision',
                    'about_page_bg_image', 'about_page_bg_overlay',
                    'about_page_show_who', 'about_page_show_stats', 'about_page_show_mission_vision', 'about_page_show_values',
                    'about_page_show_team', 'about_page_show_journey', 'about_page_show_services', 'about_page_show_gallery',
                    'about_page_mission_hidden', 'about_page_vision_hidden',
                    'about_page_quote_text', 'about_page_stat3_value', 'about_page_stat4_value',
                ];
                foreach ([1, 2, 3, 4, 5, 6] as $i) {
                    $aboutPageKeys[] = "about_page_service{$i}_title";
                    $aboutPageKeys[] = "about_page_service{$i}_desc";
                    $aboutPageKeys[] = "about_page_service{$i}_hidden";
                    $aboutPageKeys[] = "about_page_value{$i}_title";
                    $aboutPageKeys[] = "about_page_value{$i}_desc";
                    $aboutPageKeys[] = "about_page_value{$i}_hidden";
                }
                foreach ([1, 2, 3, 4] as $i) {
                    $aboutPageKeys[] = "about_page_stat{$i}_hidden";
                    $aboutPageKeys[] = "about_page_journey{$i}_hidden";
                }
                foreach ([1, 2, 3, 4, 5] as $i) {
                    $aboutPageKeys[] = "login_frame{$i}_title";
                    $aboutPageKeys[] = "login_frame{$i}_desc";
                    $aboutPageKeys[] = "login_frame{$i}_icon";
                    $aboutPageKeys[] = "login_frame{$i}_x";
                    $aboutPageKeys[] = "login_frame{$i}_y";
                    $aboutPageKeys[] = "login_frame{$i}_scale";
                }

                $appSettings = Setting::getMany(array_merge([
                    'app_name', 'app_tagline', 'company_name', 'primary_color', 'department_name', 'logo_path', 'favicon_path',
                    'login_bg_type', 'login_bg_color', 'login_bg_image', 'login_team_artwork', 'login_bg_position', 'login_bg_size',
                    'login_bg_attachment', 'login_bg_overlay', 'login_deco_color', 'login_show_doodles', 'login_hero_tagline',
                    'login_pill_text', 'login_pill_accent', 'copyright', 'developer_mode', 'hidden_elements', 'shown_extras',
                    'nav_hidden', 'maintenance_mode', 'hide_approval_customer_notify', 'hide_hourly_rate', 'hide_wa_web_button',
                    'notif_sound_type', 'notif_sound_volume', 'agent_name', 'agent_subtitle', 'agent_welcome', 'agent_color',
                    'agent_icon', 'hide_agent', 'support_user_id', 'hide_summarize_button', 'hide_features_tab',
                ], $aboutPageKeys));
                $merged = array_merge([
                    'app_name' => 'Dash',
                    'app_tagline' => '',
                    'company_name' => 'Product Co.',
                    'primary_color' => '#4F46E5',
                    'department_name' => 'Product Department',
                    'logo_path' => '',
                    'favicon_path' => '',
                    'login_bg_type' => 'gradient',
                    'login_bg_color' => '#e8eaf6',
                    'login_bg_image' => '',
                    'login_team_artwork' => '',
                    'login_bg_position' => 'center center',
                    'login_bg_size' => 'cover',
                    'login_bg_attachment' => 'fixed',
                    'login_bg_overlay' => '0',
                    'login_deco_color' => '#4F46E5',
                    'login_show_doodles' => '1',
                    'login_hero_tagline' => 'Together we build. Together we achieve.',
                    'login_pill_text' => 'One Team. One Goal.',
                    'login_pill_accent' => 'Unlimited Impact.',
                    'copyright' => '',
                    'developer_mode' => '0',
                    'hidden_elements' => '[]',
                    'shown_extras' => '[]',
                    'maintenance_mode' => '0',
                    'hide_approval_customer_notify' => '0',
                    'hide_hourly_rate' => '0',
                    'hide_wa_web_button' => '0',
                    'notif_sound_type' => 'chime',
                    'notif_sound_volume' => '0.3',
                    'agent_name' => 'Task Assistant',
                    'agent_subtitle' => 'Ask me anything about your tasks',
                    'agent_welcome' => "👋 Hi! I'm your **Task Assistant**. I can show your tasks, stats, overdue items, projects, and more.",
                    'agent_color' => '#4F46E5',
                    'agent_icon' => 'robot',
                    'hide_agent' => '0',
                    'support_user_id' => '',
                    'hide_summarize_button' => '0',
                    'about_page_enabled' => '1',
                    'about_page_intro' => '',
                    'about_page_cta_enabled' => '1',
                    'about_page_cta_text' => 'Get in Touch',
                    'about_page_cta_link' => '',
                    'about_page_services_heading' => 'What We Do',
                    'about_page_service1_title' => '',
                    'about_page_service1_desc' => '',
                    'about_page_service2_title' => '',
                    'about_page_service2_desc' => '',
                    'about_page_service3_title' => '',
                    'about_page_service3_desc' => '',
                    'about_page_service4_title' => '',
                    'about_page_service4_desc' => '',
                    'about_page_service5_title' => '',
                    'about_page_service5_desc' => '',
                    'about_page_service6_title' => '',
                    'about_page_service6_desc' => '',
                    'about_page_who_text' => '',
                    'about_page_mission' => '',
                    'about_page_vision' => '',
                    'about_page_bg_image' => '',
                    'about_page_bg_overlay' => '0',
                    'about_page_show_who' => '1', 'about_page_show_stats' => '1', 'about_page_show_mission_vision' => '1',
                    'about_page_show_values' => '1', 'about_page_show_team' => '1', 'about_page_show_journey' => '1',
                    'about_page_show_services' => '1', 'about_page_show_gallery' => '1',
                    'about_page_mission_hidden' => '0', 'about_page_vision_hidden' => '0',
                    'about_page_quote_text' => '', 'about_page_stat3_value' => '', 'about_page_stat4_value' => '',
                    'about_page_value1_title' => '', 'about_page_value1_desc' => '',
                    'about_page_value2_title' => '', 'about_page_value2_desc' => '',
                    'about_page_value3_title' => '', 'about_page_value3_desc' => '',
                    'about_page_value4_title' => '', 'about_page_value4_desc' => '',
                    'about_page_value5_title' => '', 'about_page_value5_desc' => '',
                    'about_page_value6_title' => '', 'about_page_value6_desc' => '',
                    'login_frame1_title' => 'Project Lead',      'login_frame1_desc' => 'Leads with vision',    'login_frame1_x' => '2',  'login_frame1_y' => '6',  'login_frame1_scale' => '1',
                    'login_frame2_title' => 'Creative Designer', 'login_frame2_desc' => 'Designs the future',   'login_frame2_x' => '84', 'login_frame2_y' => '6',  'login_frame2_scale' => '1',
                    'login_frame3_title' => 'Developer',         'login_frame3_desc' => 'Builds with code',     'login_frame3_x' => '2',  'login_frame3_y' => '66', 'login_frame3_scale' => '1',
                    'login_frame4_title' => 'Strategist',        'login_frame4_desc' => 'Plans for success',    'login_frame4_x' => '84', 'login_frame4_y' => '66', 'login_frame4_scale' => '1',
                    'login_frame5_title' => 'Team Member',       'login_frame5_desc' => 'Gets things done',     'login_frame5_x' => '84', 'login_frame5_y' => '36', 'login_frame5_scale' => '1',
                ], $appSettings);

                $artworkRaw = $merged['login_team_artwork'] ?? '';
                $artworkList = json_decode($artworkRaw, true);
                if (! is_array($artworkList)) {
                    $artworkList = $artworkRaw !== '' ? [$artworkRaw] : [];
                }
                $merged['login_team_artwork'] = array_values($artworkList);

                $view->with('appSettings', $merged);
            } catch (\Throwable) {
                $view->with('appSettings', [
                    'app_name' => 'Dash',
                    'app_tagline' => '',
                    'company_name' => 'Product Co.',
                    'primary_color' => '#4F46E5',
                    'department_name' => 'Product Department',
                    'logo_path' => '',
                    'favicon_path' => '',
                    'login_bg_type' => 'gradient',
                    'login_bg_color' => '#e8eaf6',
                    'login_bg_image' => '',
                    'login_team_artwork' => [],
                    'login_bg_position' => 'center center',
                    'login_bg_size' => 'cover',
                    'login_bg_attachment' => 'fixed',
                    'login_bg_overlay' => '0',
                    'login_deco_color' => '#4F46E5',
                    'login_show_doodles' => '1',
                    'login_hero_tagline' => 'Together we build. Together we achieve.',
                    'login_pill_text' => 'One Team. One Goal.',
                    'login_pill_accent' => 'Unlimited Impact.',
                    'copyright' => '',
                    'developer_mode' => '0',
                    'hidden_elements' => '[]',
                    'shown_extras' => '[]',
                    'maintenance_mode' => '0',
                    'hide_approval_customer_notify' => '0',
                    'hide_hourly_rate' => '0',
                    'hide_wa_web_button' => '0',
                    'notif_sound_type' => 'chime',
                    'notif_sound_volume' => '0.3',
                    'agent_name' => 'Task Assistant',
                    'agent_subtitle' => 'Ask me anything about your tasks',
                    'agent_welcome' => "👋 Hi! I'm your **Task Assistant**. I can show your tasks, stats, overdue items, projects, and more.",
                    'agent_color' => '#4F46E5',
                    'agent_icon' => 'robot',
                    'hide_agent' => '0',
                    'support_user_id' => '',
                    'hide_summarize_button' => '0',
                    'about_page_enabled' => '1',
                    'about_page_intro' => '',
                    'about_page_cta_enabled' => '1',
                    'about_page_cta_text' => 'Get in Touch',
                    'about_page_cta_link' => '',
                    'about_page_services_heading' => 'What We Do',
                    'about_page_service1_title' => '',
                    'about_page_service1_desc' => '',
                    'about_page_service2_title' => '',
                    'about_page_service2_desc' => '',
                    'about_page_service3_title' => '',
                    'about_page_service3_desc' => '',
                    'about_page_service4_title' => '',
                    'about_page_service4_desc' => '',
                    'about_page_service5_title' => '',
                    'about_page_service5_desc' => '',
                    'about_page_service6_title' => '',
                    'about_page_service6_desc' => '',
                    'about_page_who_text' => '',
                    'about_page_mission' => '',
                    'about_page_vision' => '',
                    'about_page_bg_image' => '',
                    'about_page_bg_overlay' => '0',
                    'about_page_show_who' => '1', 'about_page_show_stats' => '1', 'about_page_show_mission_vision' => '1',
                    'about_page_show_values' => '1', 'about_page_show_team' => '1', 'about_page_show_journey' => '1',
                    'about_page_show_services' => '1', 'about_page_show_gallery' => '1',
                    'about_page_mission_hidden' => '0', 'about_page_vision_hidden' => '0',
                    'about_page_quote_text' => '', 'about_page_stat3_value' => '', 'about_page_stat4_value' => '',
                    'about_page_value1_title' => '', 'about_page_value1_desc' => '',
                    'about_page_value2_title' => '', 'about_page_value2_desc' => '',
                    'about_page_value3_title' => '', 'about_page_value3_desc' => '',
                    'about_page_value4_title' => '', 'about_page_value4_desc' => '',
                    'about_page_value5_title' => '', 'about_page_value5_desc' => '',
                    'about_page_value6_title' => '', 'about_page_value6_desc' => '',
                    'login_frame1_title' => 'Project Lead',      'login_frame1_desc' => 'Leads with vision',    'login_frame1_x' => '2',  'login_frame1_y' => '6',  'login_frame1_scale' => '1',
                    'login_frame2_title' => 'Creative Designer', 'login_frame2_desc' => 'Designs the future',   'login_frame2_x' => '84', 'login_frame2_y' => '6',  'login_frame2_scale' => '1',
                    'login_frame3_title' => 'Developer',         'login_frame3_desc' => 'Builds with code',     'login_frame3_x' => '2',  'login_frame3_y' => '66', 'login_frame3_scale' => '1',
                    'login_frame4_title' => 'Strategist',        'login_frame4_desc' => 'Plans for success',    'login_frame4_x' => '84', 'login_frame4_y' => '66', 'login_frame4_scale' => '1',
                    'login_frame5_title' => 'Team Member',       'login_frame5_desc' => 'Gets things done',     'login_frame5_x' => '84', 'login_frame5_y' => '36', 'login_frame5_scale' => '1',
                ]);
            }
        });
    }
}

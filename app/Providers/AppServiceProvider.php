<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Share recent projects with the navigation sidebar
        View::composer('layouts.navigation', function ($view) {
            if (auth()->check()) {
                $recentProjects = Project::latest()->take(3)->get();
                $view->with('recentProjects', $recentProjects);
            }
        });

        // Share global app settings with all views
        View::composer('*', function ($view) {
            try {
                $appSettings = Setting::getMany(['app_name','company_name','primary_color','department_name','logo_path','favicon_path','login_bg_type','login_bg_color','login_bg_image']);
                $view->with('appSettings', array_merge([
                    'app_name'        => 'Dash',
                    'company_name'    => 'Product Co.',
                    'primary_color'   => '#4F46E5',
                    'department_name' => 'Product Department',
                    'logo_path'       => '',
                    'favicon_path'    => '',
                    'login_bg_type'   => 'gradient',
                    'login_bg_color'  => '#e8eaf6',
                    'login_bg_image'  => '',
                ], $appSettings));
            } catch (\Throwable) {
                $view->with('appSettings', [
                    'app_name'        => 'Dash',
                    'company_name'    => 'Product Co.',
                    'primary_color'   => '#4F46E5',
                    'department_name' => 'Product Department',
                    'logo_path'       => '',
                    'favicon_path'    => '',
                    'login_bg_type'   => 'gradient',
                    'login_bg_color'  => '#e8eaf6',
                    'login_bg_image'  => '',
                ]);
            }
        });
    }
}

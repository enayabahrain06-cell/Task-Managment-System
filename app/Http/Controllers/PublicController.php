<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;

class PublicController extends Controller
{
    // appSettings is shared with every view by the global composer in AppServiceProvider
    public function about()
    {
        if (Setting::get('about_page_enabled', '1') !== '1') {
            abort(404);
        }

        $teamMembers = User::where('status', 'active')
            ->where('role', '!=', 'admin')
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'job_title', 'role']);

        // Real counts for the stats section — never fabricate numbers that can be queried
        $activeMemberCount = User::where('status', 'active')->count();
        $completedProjectCount = Project::where('is_quick', false)->where('status', 'completed')->count();
        $tasksDeliveredCount = Task::where('status', 'delivered')->count();

        // Developer Mode live editing directly on this public page — admin-only, same gate as the login page's editor
        $devEditOn = auth()->check()
            && auth()->user()->role === 'admin'
            && Setting::get('developer_mode', '0') === '1';

        return view('public.about', compact(
            'teamMembers', 'activeMemberCount', 'completedProjectCount', 'tasksDeliveredCount', 'devEditOn'
        ));
    }
}

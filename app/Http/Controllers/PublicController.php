<?php

namespace App\Http\Controllers;

use App\Models\Setting;
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

        return view('public.about', compact('teamMembers'));
    }
}

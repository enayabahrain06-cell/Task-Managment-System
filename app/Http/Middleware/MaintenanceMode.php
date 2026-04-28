<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        if (Setting::get('maintenance_mode', '0') !== '1') {
            return $next($request);
        }

        // Admin always gets through
        if (auth()->check() && auth()->user()->role === 'admin') {
            return $next($request);
        }

        // Allow login/logout so admin can still sign in
        if ($request->routeIs('login', 'logout', 'password.*')) {
            return $next($request);
        }

        return response()->view('errors.maintenance', [], 503);
    }
}

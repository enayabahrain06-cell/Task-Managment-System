<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Same as Laravel's default `guest` middleware — redirects an already-authenticated
 * user away from the login form — except an authenticated admin is let through when
 * Developer Mode is on, so they can drag-and-drop the team frames directly on the
 * real /login page instead of being bounced to their dashboard.
 */
class RedirectIfAuthenticatedForLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user) {
            $isDevAdmin = $user->role === 'admin' && Setting::get('developer_mode', '0') === '1';

            if (! $isDevAdmin) {
                return redirect(match ($user->role) {
                    'admin'   => '/admin/dashboard',
                    'manager' => '/manager/dashboard',
                    default   => '/user/dashboard',
                });
            }
        }

        return $next($request);
    }
}

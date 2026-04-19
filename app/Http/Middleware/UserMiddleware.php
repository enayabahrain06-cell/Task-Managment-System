<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect('/login');
        }

        if ($user->status !== 'active') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $msg = $user->status === 'archived'
                ? 'Your account has been archived and is no longer accessible.'
                : 'Your account has been deactivated. Please contact your administrator.';
            return redirect('/login')->withErrors(['email' => $msg]);
        }

        return $next($request);
    }
}


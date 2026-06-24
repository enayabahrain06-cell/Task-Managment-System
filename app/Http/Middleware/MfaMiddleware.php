<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class MfaMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (! $user) {
            return $next($request);
        }

        $forceMfa = Setting::get('force_mfa', '0') === '1';

        // Force-MFA: global setting OR per-user requirement
        if (($forceMfa || $user->mfa_required) && ! $user->mfa_enabled && ! session('mfa_verified')) {
            if (! $request->routeIs('mfa.*')) {
                return redirect()->route('mfa.setup');
            }
        }

        // MFA enabled on account but not yet verified this session
        if ($user->mfa_enabled && ! session('mfa_verified')) {
            if (! $request->routeIs('mfa.*')) {
                return redirect()->route('mfa.challenge');
            }
        }

        return $next($request);
    }
}

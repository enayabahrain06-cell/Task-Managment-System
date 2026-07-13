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

        // mfa_required tri-state: null = follow global policy, 1/true = always required, 0/false = admin-exempted
        $mfaRequired = $user->getRawOriginal('mfa_required');
        if ($mfaRequired === null) {
            $mustSetup = $forceMfa;          // follow global
        } elseif ((int) $mfaRequired === 1) {
            $mustSetup = true;               // admin explicitly required
        } else {
            $mustSetup = false;              // admin explicitly exempted — bypass force_mfa
        }

        if ($mustSetup && ! $user->mfa_enabled && ! session('mfa_verified')) {
            if (! $request->routeIs('mfa.*')) {
                return redirect()->route('mfa.setup');
            }
        }

        // MFA enabled on account but not yet verified this session.
        // Only challenge when MFA is actually required for this user —
        // either individually (mfa_required=1) or via the global force_mfa policy.
        // When force_mfa is off and the user has no individual requirement, the
        // account's MFA setup is kept but the challenge is skipped.
        if ($mustSetup && $user->mfa_enabled && ! session('mfa_verified')) {
            if (! $request->routeIs('mfa.*')) {
                return redirect()->route('mfa.challenge');
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;

class PostLoginRedirect
{
    /**
     * Resolve where a freshly authenticated user should land — MFA challenge/setup
     * if required, otherwise their role dashboard (or the originally intended URL).
     */
    public static function url(User $user): string
    {
        $forceMfa = Setting::get('force_mfa', '0') === '1';
        $mfaRequired = $user->getRawOriginal('mfa_required');
        $mustMfa = $mfaRequired === null ? $forceMfa : ((int) $mfaRequired === 1);

        if ($mustMfa && $user->mfa_enabled) {
            return route('mfa.challenge');
        }

        if ($mustMfa && ! $user->mfa_enabled) {
            return route('mfa.setup');
        }

        $default = match ($user->role) {
            'admin'   => '/admin/dashboard',
            'manager' => '/manager/dashboard',
            'user'    => '/user/dashboard',
            default   => '/user/dashboard',
        };

        return url(session()->pull('url.intended', $default));
    }
}

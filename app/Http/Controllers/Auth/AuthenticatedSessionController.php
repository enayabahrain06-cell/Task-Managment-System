<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user     = auth()->user();
        $forceMfa = \App\Models\Setting::get('force_mfa', '0') === '1';

        // Resolve per-user MFA requirement (tri-state: null=global, 1=always, 0=exempt)
        $mfaRequired = $user->getRawOriginal('mfa_required');
        if ($mfaRequired === null) {
            $mustMfa = $forceMfa;
        } elseif ((int) $mfaRequired === 1) {
            $mustMfa = true;
        } else {
            $mustMfa = false;
        }

        // Challenge only when MFA is actually required for this user
        if ($mustMfa && $user->mfa_enabled) {
            return redirect()->route('mfa.challenge');
        }

        // Force-MFA: user hasn't set up MFA yet — send to setup
        if ($mustMfa && ! $user->mfa_enabled) {
            return redirect()->route('mfa.setup');
        }

        return match($user->role) {
            'admin'   => redirect()->intended('/admin/dashboard'),
            'manager' => redirect()->intended('/manager/dashboard'),
            'user'    => redirect()->intended('/user/dashboard'),
            default   => redirect()->intended('/user/dashboard'),
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}


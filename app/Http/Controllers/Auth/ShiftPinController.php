<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PostLoginRedirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class ShiftPinController extends Controller
{
    /** Staff eligible for Shift PIN quick sign-in on a shared counter device. */
    public function staff(): JsonResponse
    {
        $users = User::where('status', 'active')
            ->whereNotNull('pin')
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'role'])
            ->map(fn (User $u) => [
                'id'     => $u->id,
                'name'   => $u->name,
                'avatar' => $u->avatarUrl(),
                'role'   => $u->role,
            ])
            ->values();

        return response()->json(['users' => $users]);
    }

    private const MAX_ATTEMPTS = 5;

    private const LOCK_SECONDS = 900;

    public function authenticate(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'pin'     => ['required', 'digits:4'],
        ]);

        $user = User::find($request->integer('user_id'));
        $throttleKey = 'shift-pin:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'state'       => 'locked',
                'message'     => 'Too many attempts. Try again in '.max(1, (int) ceil($seconds / 60)).' minute(s), or sign in with a password.',
                'retry_after' => $seconds,
            ], 429);
        }

        // PIN was removed (e.g. by an admin) after this device loaded the staff list.
        if (! $user->hasPin()) {
            return response()->json([
                'state'   => 'unavailable',
                'message' => 'Shift PIN sign-in is no longer available for this account.',
            ], 422);
        }

        // Account was deactivated/archived after this device loaded the staff list — never
        // let a correct PIN through for a non-active account, same as password login.
        if ($user->status !== 'active') {
            return response()->json([
                'state'   => 'inactive',
                'message' => $user->status === 'archived'
                    ? 'This account has been archived and can no longer sign in.'
                    : 'This account has been deactivated. Please contact your administrator.',
            ], 422);
        }

        if (! Hash::check($request->string('pin'), $user->getRawOriginal('pin'))) {
            RateLimiter::hit($throttleKey, self::LOCK_SECONDS);

            return response()->json([
                'state'              => 'wrong_pin',
                'message'            => 'Incorrect PIN.',
                'attempts_remaining' => max(0, RateLimiter::remaining($throttleKey, self::MAX_ATTEMPTS)),
            ], 422);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['state' => 'ok', 'redirect' => PostLoginRedirect::url($user)]);
    }
}

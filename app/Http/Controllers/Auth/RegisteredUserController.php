<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $isSetup = User::count() === 0;

        if (!$isSetup && Setting::get('allow_registration', '0') !== '1') {
            return redirect()->route('login')->with('status', 'Registration is currently closed. Please contact your administrator.');
        }

        return view('auth.register', compact('isSetup'));
    }

    public function store(Request $request): RedirectResponse
    {
        $isSetup = User::count() === 0;

        if (!$isSetup && Setting::get('allow_registration', '0') !== '1') {
            return redirect()->route('login')->with('status', 'Registration is currently closed.');
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $isSetup ? 'admin' : 'user',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route(match($user->role) {
            'admin'   => 'admin.dashboard',
            'manager' => 'manager.dashboard',
            default   => 'user.dashboard',
        });
    }
}


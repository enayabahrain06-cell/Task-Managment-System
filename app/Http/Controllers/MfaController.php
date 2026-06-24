<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class MfaController extends Controller
{
    private function g2fa(): Google2FA
    {
        return new Google2FA();
    }

    // ── Setup page ─────────────────────────────────────────────────────────

    public function setup()
    {
        $user   = auth()->user();
        $g2fa   = $this->g2fa();

        // Generate a fresh secret only if none stored yet (or pending in session)
        $secret = session('mfa_pending_secret') ?? $g2fa->generateSecretKey();
        session(['mfa_pending_secret' => $secret]);

        $appName = Setting::get('app_name', config('app.name', 'TaskMS'));
        $otpUrl  = $g2fa->getQRCodeUrl($appName, $user->email, $secret);

        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrSvg  = $writer->writeString($otpUrl);

        return view('auth.mfa-setup', compact('secret', 'qrSvg'));
    }

    // ── Enable (verify first TOTP, save secret) ────────────────────────────

    public function enable(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $user   = auth()->user();
        $g2fa   = $this->g2fa();
        $secret = session('mfa_pending_secret');

        if (! $secret) {
            return back()->withErrors(['code' => 'Setup session expired. Please try again.']);
        }

        $valid = $g2fa->verifyKey($secret, $request->code, 1);

        if (! $valid) {
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        $codes = $this->generateRecoveryCodes();

        $user->update([
            'mfa_secret'         => $secret,
            'mfa_enabled'        => true,
            'mfa_recovery_codes' => $codes,
        ]);

        session()->forget('mfa_pending_secret');
        session(['mfa_verified' => true]);

        return view('auth.mfa-recovery-codes', ['codes' => $codes]);
    }

    // ── Disable ────────────────────────────────────────────────────────────

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = auth()->user();

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user->update([
            'mfa_secret'         => null,
            'mfa_enabled'        => false,
            'mfa_recovery_codes' => null,
        ]);

        session()->forget('mfa_verified');

        return back()->with('mfa_disabled', true);
    }

    // ── Challenge page (post-login TOTP prompt) ────────────────────────────

    public function challenge()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (session('mfa_verified')) {
            return $this->redirectToDashboard();
        }

        return view('auth.mfa-challenge');
    }

    // ── Verify challenge ───────────────────────────────────────────────────

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user  = auth()->user();
        $g2fa  = $this->g2fa();
        $input = preg_replace('/\s+/', '', $request->code);

        // Try TOTP
        if (strlen($input) === 6 && ctype_digit($input)) {
            $valid = $g2fa->verifyKey($user->mfa_secret, $input, 1);
            if ($valid) {
                session(['mfa_verified' => true]);
                return $this->redirectToDashboard();
            }
        }

        // Try recovery code
        $codes = $user->mfa_recovery_codes ?? [];
        foreach ($codes as $i => $stored) {
            if (hash_equals($stored, strtoupper($input))) {
                unset($codes[$i]);
                $user->update(['mfa_recovery_codes' => array_values($codes)]);
                session(['mfa_verified' => true]);
                return $this->redirectToDashboard();
            }
        }

        return back()->withErrors(['code' => 'Invalid authentication code. Please try again.']);
    }

    // ── Regenerate recovery codes ──────────────────────────────────────────

    public function regenerateCodes(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = auth()->user();

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $codes = $this->generateRecoveryCodes();
        $user->update(['mfa_recovery_codes' => $codes]);

        return view('auth.mfa-recovery-codes', ['codes' => $codes, 'regenerated' => true]);
    }

    // ── Email recovery (lost authenticator app) ───────────────────────────

    public function emailRecovery()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (session('mfa_verified')) {
            return $this->redirectToDashboard();
        }

        $user   = auth()->user();
        $masked = $this->maskEmail($user->email);
        $sent   = session('mfa_email_code_sent', false);

        return view('auth.mfa-email-recovery', compact('masked', 'sent'));
    }

    public function sendEmailCode(Request $request)
    {
        $user = auth()->user();

        $throttleKey = "mfa_email_throttle_{$user->id}";
        if (Cache::get($throttleKey, 0) >= 3) {
            return back()->withErrors(['throttle' => 'Too many requests. Please wait 10 minutes before trying again.']);
        }
        Cache::put($throttleKey, Cache::get($throttleKey, 0) + 1, now()->addMinutes(10));

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("mfa_email_otp_{$user->id}", bcrypt($otp), now()->addMinutes(10));

        $appName = Setting::get('app_name', config('app.name', 'TaskMS'));

        Mail::raw(
            "Hi {$user->name},\n\n"
            . "Your MFA recovery code is:\n\n"
            . "  {$otp}\n\n"
            . "This code expires in 10 minutes and can only be used once.\n\n"
            . "If you did not request this, please contact your administrator immediately.\n\n"
            . "— {$appName}",
            function ($message) use ($user, $appName) {
                $message->to($user->email)
                        ->subject("MFA Recovery Code — {$appName}");
            }
        );

        session(['mfa_email_code_sent' => true]);

        return redirect()->route('mfa.email-recovery')->with('code_sent', true);
    }

    public function verifyEmailCode(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $user   = auth()->user();
        $stored = Cache::get("mfa_email_otp_{$user->id}");

        if (! $stored || ! Hash::check($request->code, $stored)) {
            return back()->withErrors(['code' => 'Invalid or expired code. Please request a new one.']);
        }

        Cache::forget("mfa_email_otp_{$user->id}");
        Cache::forget("mfa_email_throttle_{$user->id}");
        session(['mfa_verified' => true]);

        return $this->redirectToDashboard();
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = min(2, strlen($local));
        $masked  = substr($local, 0, $visible) . str_repeat('*', max(1, strlen($local) - $visible));

        return $masked . '@' . $domain;
    }

    private function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))->map(fn() =>
            strtoupper(Str::random(5) . '-' . Str::random(5))
        )->toArray();
    }

    private function redirectToDashboard()
    {
        $user = auth()->user();
        $intended = session()->pull('url.intended');
        if ($intended) return redirect($intended);

        return match($user->role) {
            'admin'   => redirect()->route('admin.dashboard'),
            'manager' => redirect()->route('manager.dashboard'),
            default   => redirect()->route('user.dashboard'),
        };
    }
}

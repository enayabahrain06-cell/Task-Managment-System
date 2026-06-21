<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LicensesController extends Controller
{
    public function revealPassword(Request $request, Subscription $subscription)
    {
        $request->validate(['password' => 'required|string']);

        // Ensure the user is actually assigned to this subscription
        if (!$subscription->users()->where('user_id', auth()->id())->exists()) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        if (!Hash::check($request->password, auth()->user()->password)) {
            return response()->json(['error' => 'Incorrect password.'], 403);
        }

        if (!$subscription->password) {
            return response()->json(['error' => 'No password stored.'], 404);
        }

        AuditLogger::log(
            'reveal_password',
            $subscription,
            'User revealed password for subscription: ' . $subscription->name,
            ['ip' => $request->ip()]
        );

        return response()->json(['secret' => $subscription->decrypted_password]);
    }

    public function index()
    {
        $user    = auth()->user();
        $licenses = Subscription::whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->with([
                'users'       => fn($q) => $q->where('user_id', $user->id),
                'attachments' => fn($q) => $q->orderBy('created_at'),
            ])
            ->orderBy('renewal_date')
            ->get();

        $assignerIds = $licenses->flatMap(fn($l) => $l->users->pluck('pivot.assigned_by'))->filter()->unique();
        $assigners   = User::whereIn('id', $assignerIds)->pluck('name', 'id');

        $totalCount        = $licenses->count();
        $activeCount       = $licenses->filter(fn($l) => $l->status === 'active')->count();
        $expiringSoonCount = $licenses->filter(fn($l) => $l->status === 'expiring_soon')->count();
        $expiredCount      = $licenses->filter(fn($l) => $l->status === 'expired')->count();

        $socialAccounts = $user->socialAccounts()->with('customer')->orderBy('platform')->get();
        $saPlatforms    = SocialAccount::platforms();

        return view('user.licenses.index', compact(
            'licenses', 'assigners',
            'totalCount', 'activeCount', 'expiringSoonCount', 'expiredCount',
            'socialAccounts', 'saPlatforms'
        ));
    }
}

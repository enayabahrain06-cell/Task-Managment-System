<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Subscription;

class LicensesController extends Controller
{
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

        return view('user.licenses.index', compact('licenses'));
    }
}

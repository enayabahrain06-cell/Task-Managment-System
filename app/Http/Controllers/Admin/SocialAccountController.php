<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SocialAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = SocialAccount::with(['creator:id,name', 'users:id,name,email,avatar,role', 'customer:id,name,company,logo']);

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }
        if ($request->filled('customer')) {
            $query->where('customer_id', $request->customer);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('username', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"));
        }

        $accounts   = $query->orderBy('platform')->orderBy('name')->get();
        $platforms  = SocialAccount::platforms();
        $byPlatform = $accounts->groupBy('platform');
        $allUsers   = User::select('id', 'name', 'email', 'avatar', 'role')
                          ->whereIn('role', ['admin', 'manager', 'user'])
                          ->orderBy('name')->get();
        $customers  = Customer::select('id', 'name', 'company', 'logo')->orderBy('name')->get();

        $stats = [
            'total'     => $accounts->count(),
            'active'    => $accounts->where('status', 'active')->count(),
            'inactive'  => $accounts->where('status', 'inactive')->count(),
            'suspended' => $accounts->where('status', 'suspended')->count(),
        ];

        return view('admin.social-accounts.index', compact(
            'accounts', 'platforms', 'byPlatform', 'stats', 'allUsers', 'customers'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'platform'    => 'required|string|in:' . implode(',', array_keys(SocialAccount::platforms())),
            'customer_id' => 'nullable|exists:customers,id',
            'username'    => 'nullable|string|max:255',
            'email'       => 'nullable|string|max:255',
            'password'    => 'nullable|string|max:1000',
            'account_id'  => 'nullable|string|max:255',
            'page_url'    => 'nullable|url|max:500',
            'status'      => 'required|in:active,inactive,suspended',
            'notes'       => 'nullable|string|max:2000',
            'user_ids'    => 'nullable|array',
            'user_ids.*'  => 'exists:users,id',
        ]);

        $data['created_by'] = auth()->id();

        if ($request->filled('password')) {
            $data['password'] = Crypt::encryptString($request->password);
        }

        $account = SocialAccount::create($data);

        if ($request->filled('user_ids')) {
            $account->users()->sync($request->user_ids);
        }

        AuditLogger::log('created', $account, "Created social account: {$account->name}");

        return back()->with('success', "Account \"{$account->name}\" added successfully.");
    }

    public function update(Request $request, SocialAccount $socialAccount)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'platform'    => 'required|string|in:' . implode(',', array_keys(SocialAccount::platforms())),
            'customer_id' => 'nullable|exists:customers,id',
            'username'    => 'nullable|string|max:255',
            'email'       => 'nullable|string|max:255',
            'password'    => 'nullable|string|max:1000',
            'account_id'  => 'nullable|string|max:255',
            'page_url'    => 'nullable|url|max:500',
            'status'      => 'required|in:active,inactive,suspended',
            'notes'       => 'nullable|string|max:2000',
            'user_ids'    => 'nullable|array',
            'user_ids.*'  => 'exists:users,id',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Crypt::encryptString($request->password);
        } else {
            unset($data['password']);
        }

        $socialAccount->update($data);
        $socialAccount->users()->sync($request->input('user_ids', []));

        AuditLogger::log('updated', $socialAccount, "Updated social account: {$socialAccount->name}");

        return back()->with('success', "Account \"{$socialAccount->name}\" updated.");
    }

    public function destroy(SocialAccount $socialAccount)
    {
        $name = $socialAccount->name;
        $socialAccount->delete();

        AuditLogger::log('deleted', null, "Deleted social account: {$name}");

        return back()->with('success', "Account \"{$name}\" deleted.");
    }
}

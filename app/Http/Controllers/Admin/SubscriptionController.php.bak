<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionAttachment;
use App\Models\User;
use App\Notifications\SubscriptionAssigned;
use App\Notifications\SubscriptionRemoved;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with(['creator:id,name', 'users:id,name,email'])
            ->withCount('users');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('vendor', 'like', "%{$s}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $all = $query->orderBy('renewal_date')->get();

        $totalCount        = $all->count();
        $activeCount       = $all->filter(fn($s) => $s->status === 'active')->count();
        $expiringSoonCount = $all->filter(fn($s) => $s->status === 'expiring_soon')->count();
        $expiredCount      = $all->filter(fn($s) => $s->status === 'expired')->count();
        $weekCount         = $all->filter(fn($s) => $s->days_until_renewal !== null && $s->days_until_renewal >= 0 && $s->days_until_renewal <= 7)->count();

        $monthlyTotal = $all->sum(fn($s) => $s->monthly_cost);
        $annualTotal  = $all->sum(fn($s) => $s->annual_cost);

        $expiringThisWeek = $all->filter(fn($s) => $s->days_until_renewal !== null && $s->days_until_renewal >= 0 && $s->days_until_renewal <= 7)->values();

        $statusFilter  = $request->get('status', 'all');
        $subscriptions = match ($statusFilter) {
            'active'        => $all->filter(fn($s) => $s->status === 'active')->values(),
            'expiring_soon' => $all->filter(fn($s) => $s->status === 'expiring_soon')->values(),
            'expired'       => $all->filter(fn($s) => $s->status === 'expired')->values(),
            default         => $all,
        };

        $users      = User::whereIn('role', ['admin', 'manager', 'user'])->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']);
        $categories = Subscription::categoryOptions();

        return view('admin.subscriptions.index', compact(
            'subscriptions', 'totalCount', 'activeCount', 'expiringSoonCount',
            'expiredCount', 'weekCount', 'monthlyTotal', 'annualTotal',
            'expiringThisWeek', 'users', 'categories', 'statusFilter'
        ));
    }

    public function exportPdf(Request $request)
    {
        $all = Subscription::with(['users:id,name'])
            ->withCount('users')
            ->orderBy('renewal_date')
            ->get();

        $catColors = Subscription::categoryColors();
        $catNames  = Subscription::categoryOptions();

        $summary = [
            'total'          => $all->count(),
            'active'         => $all->filter(fn($s) => $s->status === 'active')->count(),
            'expiring_soon'  => $all->filter(fn($s) => $s->status === 'expiring_soon')->count(),
            'expired'        => $all->filter(fn($s) => $s->status === 'expired')->count(),
            'monthly_total'  => $all->sum(fn($s) => $s->monthly_cost),
            'annual_total'   => $all->sum(fn($s) => $s->annual_cost),
            'generated_at'   => now()->format('d M Y, H:i'),
            'by_category'    => $all->groupBy('category')->map(fn($g, $k) => [
                'label'   => $catNames[$k] ?? $k,
                'count'   => $g->count(),
                'annual'  => $g->sum(fn($s) => $s->annual_cost),
            ])->sortByDesc('annual')->values(),
        ];

        $settings  = \App\Models\Setting::pluck('value', 'key');
        $appName   = $settings['app_name'] ?? config('app.name');
        $logoPath  = null;
        if (!empty($settings['logo_path'])) {
            $path = storage_path('app/public/' . ltrim($settings['logo_path'], '/'));
            if (file_exists($path)) {
                $ext      = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime     = $ext === 'jpg' ? 'jpeg' : $ext;
                $logoPath = 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        $pdf = Pdf::loadView('admin.subscriptions.pdf', compact('all', 'summary', 'catColors', 'catNames', 'appName', 'logoPath', 'settings'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => false]);

        $filename = 'subscriptions-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    public function revealPassword(Request $request, Subscription $subscription)
    {
        $request->validate(['password' => 'required|string']);

        if (!\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
            return response()->json(['error' => 'Incorrect password.'], 403);
        }

        if (!$subscription->password) {
            return response()->json(['error' => 'No password stored.'], 404);
        }

        AuditLogger::log(
            'reveal_password',
            $subscription,
            'Revealed password for subscription: ' . $subscription->name,
            ['ip' => $request->ip()]
        );

        return response()->json(['secret' => $subscription->decrypted_password]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'vendor'        => 'nullable|string|max:255',
            'category'      => 'required|string',
            'type'          => 'required|string',
            'billing_cycle' => 'required|string',
            'cost'          => 'required|numeric|min:0',
            'currency'      => 'required|string|max:10',
            'max_seats'     => 'nullable|integer|min:1',
            'website'       => 'nullable|url|max:500',
            'purchase_date' => 'nullable|date',
            'renewal_date'  => 'nullable|date',
            'notify_days'   => 'nullable|array',
            'notify_days.*' => 'integer',
            'notes'         => 'nullable|string|max:2000',
            'username'      => 'nullable|string|max:255',
            'password'      => 'nullable|string|max:1000',
            'logo'          => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'user_ids'      => 'nullable|array',
            'user_ids.*'    => 'integer|exists:users,id',
        ]);

        $data['notify_days'] = $data['notify_days'] ?? [30, 14, 7, 1];
        $data['created_by']  = auth()->id();

        if ($request->filled('password')) {
            $data['password'] = Crypt::encryptString($request->password);
        }

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('subscriptions/logos', 'public');
        }

        unset($data['logo'], $data['user_ids']);

        $subscription = Subscription::create($data);

        if ($request->filled('user_ids')) {
            $userIds = array_filter((array) $request->user_ids);
            $pivot = [];
            foreach ($userIds as $uid) {
                $pivot[$uid] = ['assigned_by' => auth()->id(), 'assigned_at' => now()];
            }
            $subscription->users()->attach($pivot);
            foreach ($userIds as $uid) {
                $user = User::find($uid);
                if ($user) $user->notify(new SubscriptionAssigned($subscription, auth()->user()));
            }
        }

        AuditLogger::log('created', $subscription, "Created subscription: {$subscription->name}");

        return redirect()->route('admin.subscriptions.index')
            ->with('success', "Subscription \"{$subscription->name}\" created successfully.");
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['creator:id,name', 'users', 'attachments.uploader:id,name']);

        $assignedIds    = $subscription->users->pluck('id')->toArray();
        $availableUsers = User::where('status', 'active')
            ->whereNotIn('id', $assignedIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $categories = Subscription::categoryOptions();

        return view('admin.subscriptions.show', compact('subscription', 'availableUsers', 'categories'));
    }

    public function uploadAttachment(Request $request, Subscription $subscription)
    {
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt,zip',
            'comment' => 'nullable|string|max:1000',
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store("subscriptions/{$subscription->id}/attachments", 'public');
            $subscription->attachments()->create([
                'uploaded_by' => auth()->id(),
                'filename'    => $file->getClientOriginalName(),
                'path'        => $path,
                'size'        => $file->getSize(),
                'mime_type'   => $file->getMimeType(),
                'comment'     => $request->filled('comment') ? trim($request->comment) : null,
            ]);
        }

        AuditLogger::log('uploaded', $subscription, "Uploaded " . count($request->file('files')) . " attachment(s) to: {$subscription->name}");

        return back()->with('success', count($request->file('files')) . ' file(s) uploaded successfully.');
    }

    public function deleteAttachment(Subscription $subscription, SubscriptionAttachment $attachment)
    {
        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        AuditLogger::log('deleted', $subscription, "Deleted attachment \"{$attachment->filename}\" from: {$subscription->name}");

        return back()->with('success', "File \"{$attachment->filename}\" deleted.");
    }

    public function update(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'vendor'        => 'nullable|string|max:255',
            'category'      => 'required|string',
            'type'          => 'required|string',
            'billing_cycle' => 'required|string',
            'cost'          => 'required|numeric|min:0',
            'currency'      => 'required|string|max:10',
            'max_seats'     => 'nullable|integer|min:1',
            'website'       => 'nullable|url|max:500',
            'purchase_date' => 'nullable|date',
            'renewal_date'  => 'nullable|date',
            'notify_days'   => 'nullable|array',
            'notify_days.*' => 'integer',
            'notes'         => 'nullable|string|max:2000',
            'username'      => 'nullable|string|max:255',
            'password'      => 'nullable|string|max:1000',
            'logo'          => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'remove_logo'   => 'nullable|boolean',
            'user_ids'      => 'nullable|array',
            'user_ids.*'    => 'integer|exists:users,id',
        ]);

        $data['notify_days'] = $data['notify_days'] ?? [30, 14, 7, 1];

        // Password: only update if a new one was submitted
        if ($request->filled('password')) {
            $data['password'] = Crypt::encryptString($request->password);
        } else {
            unset($data['password']);
        }

        // Logo handling
        if ($request->hasFile('logo')) {
            if ($subscription->logo_path) {
                Storage::disk('public')->delete($subscription->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('subscriptions/logos', 'public');
        } elseif ($request->boolean('remove_logo') && $subscription->logo_path) {
            Storage::disk('public')->delete($subscription->logo_path);
            $data['logo_path'] = null;
        } else {
            unset($data['logo_path']);
        }

        unset($data['logo'], $data['remove_logo'], $data['user_ids']);

        $subscription->update($data);

        // Only sync users when the field was explicitly submitted (index edit modal)
        if ($request->has('user_ids')) {
            $userIds = array_filter((array) $request->user_ids);
            $pivot = [];
            foreach ($userIds as $uid) {
                $pivot[$uid] = ['assigned_by' => auth()->id(), 'assigned_at' => now()];
            }
            $subscription->users()->sync($pivot);
        }

        AuditLogger::log('updated', $subscription, "Updated subscription: {$subscription->name}");

        return redirect()->route('admin.subscriptions.show', $subscription)
            ->with('success', 'Subscription updated successfully.');
    }

    public function destroy(Subscription $subscription)
    {
        if ($subscription->logo_path) {
            Storage::disk('public')->delete($subscription->logo_path);
        }

        $name = $subscription->name;
        $subscription->delete();

        AuditLogger::log('deleted', null, "Deleted subscription: {$name}");

        return redirect()->route('admin.subscriptions.index')
            ->with('success', "Subscription \"{$name}\" deleted.");
    }

    public function assignUser(Request $request, Subscription $subscription)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $userId = $request->user_id;

        if ($subscription->users()->where('user_id', $userId)->exists()) {
            return back()->with('error', 'User is already assigned to this subscription.');
        }

        $subscription->users()->attach($userId, [
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        $user = User::find($userId);
        $user->notify(new SubscriptionAssigned($subscription, auth()->user()));

        AuditLogger::log('assigned', $subscription, "Assigned {$user->name} to subscription: {$subscription->name}");

        return back()->with('success', "{$user->name} added to {$subscription->name}.");
    }

    public function removeUser(Subscription $subscription, User $user)
    {
        $subscription->users()->detach($user->id);

        $user->notify(new SubscriptionRemoved($subscription, auth()->user()));

        AuditLogger::log('removed', $subscription, "Removed {$user->name} from subscription: {$subscription->name}");

        return back()->with('success', "{$user->name} removed from {$subscription->name}.");
    }
}

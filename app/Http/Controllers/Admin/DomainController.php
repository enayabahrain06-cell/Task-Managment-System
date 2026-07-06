<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\GroupsCostsByCurrency;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Domain;
use App\Models\DomainAttachment;
use App\Models\User;
use App\Notifications\DomainAssigned;
use App\Services\AuditLogger;
use App\Services\MqttService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class DomainController extends Controller
{
    use GroupsCostsByCurrency;

    public function index(Request $request)
    {
        $query = Domain::with(['customer:id,name,company', 'responsibleUser:id,name', 'responsibleUsers:id,name', 'creator:id,name']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('domain', 'like', "%{$s}%")
                  ->orWhere('registrar', 'like', "%{$s}%")
                  ->orWhere('billing_to', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$s}%")
                      ->orWhere('company', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('registrar')) {
            $query->where('registrar', $request->registrar);
        }

        $all = $query->orderBy('expires_at')->get();

        $totalCount        = $all->count();
        $activeCount       = $all->filter(fn($d) => $d->status === 'active')->count();
        $expiringSoonCount = $all->filter(fn($d) => $d->status === 'expiring_soon')->count();
        $expiredCount      = $all->filter(fn($d) => $d->status === 'expired')->count();
        $weekCount         = $all->filter(fn($d) => $d->days_until_expiry !== null && $d->days_until_expiry >= 0 && $d->days_until_expiry <= 7)->count();
        $annualTotalsByCurrency = $this->totalsByCurrency($all, fn($d) => $d->annual_cost);

        $expiringThisWeek = $all->filter(fn($d) => $d->days_until_expiry !== null && $d->days_until_expiry >= 0 && $d->days_until_expiry <= 7)->values();
        $expiringDomains  = $all->filter(fn($d) => $d->status === 'expiring_soon')->values();
        $showExpiringPopup = $expiringDomains->isNotEmpty() && !session('domains_expiry_popup_dismissed', false);

        $statusFilter = $request->get('status', 'all');
        $domains = match ($statusFilter) {
            'active'        => $all->filter(fn($d) => $d->status === 'active')->values(),
            'expiring_soon' => $all->filter(fn($d) => $d->status === 'expiring_soon')->values(),
            'expired'       => $all->filter(fn($d) => $d->status === 'expired')->values(),
            default         => $all,
        };

        $customers  = Customer::orderBy('name')->get(['id', 'name', 'company']);
        $staffUsers = User::whereIn('role', ['admin', 'manager', 'user'])->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email', 'role']);
        $registrars = Domain::whereNotNull('registrar')->distinct()->pluck('registrar')->sort()->values();
        $billingCycles = Domain::billingCycleOptions();

        return view('admin.domains.index', compact(
            'domains', 'totalCount', 'activeCount', 'expiringSoonCount',
            'expiredCount', 'weekCount', 'annualTotalsByCurrency', 'expiringThisWeek', 'expiringDomains', 'showExpiringPopup',
            'customers', 'staffUsers', 'registrars', 'billingCycles', 'statusFilter'
        ));
    }

    public function exportPdf()
    {
        $all = Domain::with(['customer:id,name,company', 'responsibleUser:id,name', 'responsibleUsers:id,name'])
            ->orderBy('expires_at')
            ->get();

        $cycleAnnualMap = ['annual' => 1, 'biennial' => 2, 'triennial' => 3, 'one_time' => 0];
        $annualTotalsByCurrency = $this->totalsByCurrency($all, fn($d) => $d->annual_cost);
        $summary = [
            'total'            => $all->count(),
            'active'           => $all->filter(fn($d) => $d->status === 'active')->count(),
            'expiring_soon'    => $all->filter(fn($d) => $d->status === 'expiring_soon')->count(),
            'expired'          => $all->filter(fn($d) => $d->status === 'expired')->count(),
            'auto_renew_count' => $all->filter(fn($d) => $d->auto_renew)->count(),
            'annual_total_by_currency'  => $annualTotalsByCurrency,
            'monthly_total_by_currency' => $annualTotalsByCurrency->map(fn($amt) => round($amt / 12, 3)),
            'generated_at'     => now()->format('d M Y, H:i'),
            'by_billing_cycle' => $all->groupBy('billing_cycle')->map(fn($g, $k) => [
                'label'             => ucfirst(str_replace('_', ' ', $k ?: 'unknown')),
                'count'             => $g->count(),
                'annual'            => $g->sum(fn($d) => $d->annual_cost),
                'annual_by_currency'=> $this->totalsByCurrency($g, fn($d) => $d->annual_cost),
            ])->sortByDesc('annual')->values(),
            'by_registrar'     => $all->groupBy('registrar')->map(fn($g, $k) => [
                'label' => $k ?: 'Unknown',
                'count' => $g->count(),
                'annual'=> $g->sum(fn($d) => $d->annual_cost),
            ])->sortByDesc('count')->values(),
            'by_customer'      => $all->groupBy(fn($d) => $d->customer?->name ?? 'No Customer')
                ->map(fn($g, $k) => ['label' => $k, 'count' => $g->count(), 'annual' => $g->sum(fn($d) => $d->annual_cost)])
                ->sortByDesc('count')->values(),
        ];

        $settings = \App\Models\Setting::pluck('value', 'key');
        $appName  = $settings['app_name'] ?? config('app.name');
        $logoPath = null;
        if (!empty($settings['logo_path'])) {
            $path = storage_path('app/public/' . ltrim($settings['logo_path'], '/'));
            if (file_exists($path)) {
                $ext      = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime     = $ext === 'jpg' ? 'jpeg' : $ext;
                $logoPath = 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        $pdf = Pdf::loadView('admin.domains.pdf', compact('all', 'summary', 'settings', 'appName', 'logoPath'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => false]);

        return $pdf->download('domains-' . now()->format('Y-m-d') . '.pdf');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'domain'              => 'required|string|max:255',
            'registrar'          => 'nullable|string|max:255',
            'customer_id'        => 'nullable|exists:customers,id',
            'responsible_user_ids'   => 'nullable|array',
            'responsible_user_ids.*' => 'integer|exists:users,id',
            'billing_to'         => 'nullable|string|max:255',
            'cost'               => 'required|numeric|min:0',
            'currency'           => 'required|string|max:10',
            'billing_cycle'      => 'required|string',
            'auto_renew'         => 'nullable|boolean',
            'registered_at'      => 'nullable|date',
            'expires_at'         => 'nullable|date',
            'notify_days'        => 'nullable|array',
            'notify_days.*'      => 'integer',
            'nameservers'        => 'nullable|string|max:2000',
            'hosting_provider'   => 'nullable|string|max:255',
            'login_url'          => 'nullable|url|max:500',
            'username'           => 'nullable|string|max:255',
            'password'           => 'nullable|string|max:1000',
            'notes'              => 'nullable|string|max:2000',
        ]);

        $responsibleIds = array_values(array_unique(array_filter($data['responsible_user_ids'] ?? [])));
        unset($data['responsible_user_ids']);
        $data['responsible_user_id'] = $responsibleIds[0] ?? null;

        $data['auto_renew']  = $request->boolean('auto_renew');
        $data['notify_days'] = $data['notify_days'] ?? [60, 30, 14, 7, 1];
        $data['created_by']  = auth()->id();

        if ($request->filled('password')) {
            $data['password'] = Crypt::encryptString($request->password);
        }

        // Parse nameservers: one per line → JSON array
        if (!empty($data['nameservers'])) {
            $ns = array_filter(array_map('trim', explode("\n", $data['nameservers'])));
            $data['nameservers'] = array_values($ns);
        } else {
            $data['nameservers'] = null;
        }

        $domain = Domain::create($data);
        $domain->responsibleUsers()->sync($responsibleIds);

        // Notify responsible users
        foreach (User::whereIn('id', $responsibleIds)->get() as $responsible) {
            $responsible->notify(new DomainAssigned($domain, auth()->user()));
            MqttService::notifyUser($responsible->id, [
                'notif_type'   => 'domain_assigned',
                'unread_count' => $responsible->unreadNotifications()->count(),
                'title'        => 'Domain Responsibility Assigned',
                'message'      => "You are now responsible for: {$domain->domain}",
            ]);
        }

        AuditLogger::log('created', $domain, "Created domain: {$domain->domain}");

        return redirect()->route('admin.domains.index')
            ->with('success', "Domain \"{$domain->domain}\" added successfully.");
    }

    public function show(Domain $domain)
    {
        $domain->load(['customer', 'responsibleUser', 'responsibleUsers', 'creator', 'attachments.uploader']);

        $renewalHistory = \App\Models\AuditLog::forSubject('Domain', $domain->id)
            ->whereIn('action', ['created', 'renewed'])
            ->with('actor:id,name')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.domains.show', compact('domain', 'renewalHistory'));
    }

    public function storeAttachment(Request $request, Domain $domain)
    {
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'required|file|max:20480',
        ]);

        $count = $this->storeDomainAttachments($request, $domain);

        AuditLogger::log('uploaded', $domain, "Uploaded {$count} attachment(s) to {$domain->domain}");

        return back()->with('success', 'Attachment(s) uploaded successfully.');
    }

    public function downloadAttachment(Domain $domain, DomainAttachment $attachment)
    {
        abort_if($attachment->domain_id !== $domain->id, 404);
        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
    }

    public function destroyAttachment(Domain $domain, DomainAttachment $attachment)
    {
        abort_if($attachment->domain_id !== $domain->id, 404);
        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        AuditLogger::log('deleted', $domain, "Deleted attachment \"{$attachment->original_name}\" from {$domain->domain}");

        return back()->with('success', 'Attachment deleted.');
    }

    public function quickRenew(Request $request, Domain $domain)
    {
        $years = ['annual' => 1, 'biennial' => 2, 'triennial' => 3, 'one_time' => 0][$domain->billing_cycle] ?? 0;
        abort_if($years === 0, 422, 'This domain\'s billing cycle does not support quick renewal.');

        $request->validate(['files.*' => 'file|max:20480']);

        $base = ($domain->expires_at && $domain->expires_at->isFuture()) ? $domain->expires_at : now();
        $oldExpiry = $domain->expires_at?->format('d M Y') ?? '—';
        $newExpiry = $base->copy()->addYears($years);

        $domain->update(['expires_at' => $newExpiry]);

        $attachedCount = $request->hasFile('files') ? $this->storeDomainAttachments($request, $domain) : 0;

        $description = "Renewed {$domain->domain} — expiry moved from {$oldExpiry} to {$newExpiry->format('d M Y')}";
        if ($attachedCount) {
            $description .= " ({$attachedCount} attachment(s) added)";
        }
        AuditLogger::log('renewed', $domain, $description);

        return back()->with('success', "Domain renewed. New expiry date: {$newExpiry->format('d M Y')}.");
    }

    private function storeDomainAttachments(Request $request, Domain $domain): int
    {
        $files = $request->file('files');

        foreach ($files as $file) {
            $path = $file->store("domain-attachments/{$domain->id}", 'public');
            $domain->attachments()->create([
                'uploaded_by'   => auth()->id(),
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'size'          => $file->getSize(),
                'mime_type'     => $file->getMimeType(),
            ]);
        }

        return count($files);
    }

    public function update(Request $request, Domain $domain)
    {
        $data = $request->validate([
            'domain'              => 'required|string|max:255',
            'registrar'          => 'nullable|string|max:255',
            'customer_id'        => 'nullable|exists:customers,id',
            'responsible_user_ids'   => 'nullable|array',
            'responsible_user_ids.*' => 'integer|exists:users,id',
            'billing_to'         => 'nullable|string|max:255',
            'cost'               => 'required|numeric|min:0',
            'currency'           => 'required|string|max:10',
            'billing_cycle'      => 'required|string',
            'auto_renew'         => 'nullable|boolean',
            'registered_at'      => 'nullable|date',
            'expires_at'         => 'nullable|date',
            'notify_days'        => 'nullable|array',
            'notify_days.*'      => 'integer',
            'nameservers'        => 'nullable|string|max:2000',
            'hosting_provider'   => 'nullable|string|max:255',
            'login_url'          => 'nullable|url|max:500',
            'username'           => 'nullable|string|max:255',
            'password'           => 'nullable|string|max:1000',
            'notes'              => 'nullable|string|max:2000',
        ]);

        $responsibleIds = array_values(array_unique(array_filter($data['responsible_user_ids'] ?? [])));
        unset($data['responsible_user_ids']);
        $data['responsible_user_id'] = $responsibleIds[0] ?? null;

        $data['auto_renew']  = $request->boolean('auto_renew');
        $data['notify_days'] = $data['notify_days'] ?? [60, 30, 14, 7, 1];

        if ($request->filled('password')) {
            $data['password'] = Crypt::encryptString($request->password);
        } else {
            unset($data['password']);
        }

        if (!empty($data['nameservers'])) {
            $ns = array_filter(array_map('trim', explode("\n", $data['nameservers'])));
            $data['nameservers'] = array_values($ns);
        } else {
            $data['nameservers'] = null;
        }

        $oldResponsibleIds = $domain->responsibleUsers()->pluck('user_id')->all();
        $domain->update($data);
        $domain->responsibleUsers()->sync($responsibleIds);

        // Notify newly-added responsible users only
        $newlyAdded = array_diff($responsibleIds, $oldResponsibleIds);
        foreach (User::whereIn('id', $newlyAdded)->get() as $responsible) {
            $responsible->notify(new DomainAssigned($domain, auth()->user()));
            MqttService::notifyUser($responsible->id, [
                'notif_type'   => 'domain_assigned',
                'unread_count' => $responsible->unreadNotifications()->count(),
                'title'        => 'Domain Responsibility Assigned',
                'message'      => "You are now responsible for: {$domain->domain}",
            ]);
        }

        AuditLogger::log('updated', $domain, "Updated domain: {$domain->domain}");

        return redirect()->route('admin.domains.show', $domain)
            ->with('success', 'Domain updated successfully.');
    }

    public function destroy(Domain $domain)
    {
        $name = $domain->domain;
        $domain->delete();

        AuditLogger::log('deleted', null, "Deleted domain: {$name}");

        return redirect()->route('admin.domains.index')
            ->with('success', "Domain \"{$name}\" deleted.");
    }

    public function revealPassword(Request $request, Domain $domain)
    {
        $request->validate(['password' => 'required|string']);

        if (!\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
            return response()->json(['error' => 'Incorrect password.'], 403);
        }

        if (!$domain->password) {
            return response()->json(['error' => 'No password stored.'], 404);
        }

        AuditLogger::log(
            'reveal_password',
            $domain,
            'Revealed password for domain: ' . $domain->domain,
            ['ip' => $request->ip()]
        );

        return response()->json(['secret' => $domain->decrypted_password]);
    }
}

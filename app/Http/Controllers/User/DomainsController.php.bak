<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Concerns\GroupsCostsByCurrency;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Domain;
use App\Models\DomainAttachment;
use App\Models\User;
use App\Notifications\DomainAssigned;
use App\Services\AuditLogger;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DomainsController extends Controller
{
    use GroupsCostsByCurrency;

    public function index()
    {
        $domains = Domain::with(['customer:id,name,company', 'creator:id,name', 'responsibleUsers:id,name'])
            ->where('responsible_user_id', auth()->id())
            ->orWhereHas('responsibleUsers', fn($q) => $q->where('user_id', auth()->id()))
            ->orderBy('expires_at')
            ->get();

        $expiringDomains = $domains->filter(fn($d) => $d->status === 'expiring_soon')->values();
        $showExpiringPopup = $expiringDomains->isNotEmpty() && !session('domains_expiry_popup_dismissed', false);

        $totalCount        = $domains->count();
        $activeCount       = $domains->filter(fn($d) => $d->status === 'active')->count();
        $expiringSoonCount = $expiringDomains->count();
        $expiredCount      = $domains->filter(fn($d) => $d->status === 'expired')->count();
        $expiringThisWeek  = $domains->filter(fn($d) => $d->days_until_expiry !== null && $d->days_until_expiry >= 0 && $d->days_until_expiry <= 7)->values();
        $weekCount         = $expiringThisWeek->count();
        $annualTotalsByCurrency = $this->totalsByCurrency($domains, fn($d) => $d->annual_cost);

        $customers  = Customer::orderBy('name')->get(['id', 'name', 'company']);
        $staffUsers = User::whereIn('role', ['admin', 'manager', 'user'])->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email', 'role']);
        $billingCycles = Domain::billingCycleOptions();

        return view('user.domains.index', compact(
            'domains', 'expiringDomains', 'showExpiringPopup', 'customers', 'staffUsers', 'billingCycles',
            'totalCount', 'activeCount', 'expiringSoonCount', 'expiredCount', 'weekCount', 'expiringThisWeek', 'annualTotalsByCurrency'
        ));
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

        $responsibleIds = array_values(array_unique(array_filter(array_merge(
            $data['responsible_user_ids'] ?? [],
            [auth()->id()]
        ))));
        unset($data['responsible_user_ids']);
        $data['responsible_user_id'] = $responsibleIds[0] ?? auth()->id();

        $data['auto_renew']  = $request->boolean('auto_renew');
        $data['notify_days'] = $data['notify_days'] ?? [60, 30, 14, 7, 1];
        $data['created_by']  = auth()->id();

        if ($request->filled('password')) {
            $data['password'] = Crypt::encryptString($request->password);
        }

        if (!empty($data['nameservers'])) {
            $ns = array_filter(array_map('trim', explode("\n", $data['nameservers'])));
            $data['nameservers'] = array_values($ns);
        } else {
            $data['nameservers'] = null;
        }

        $domain = Domain::create($data);
        $domain->responsibleUsers()->sync($responsibleIds);

        // Notify co-responsible users (not the creator themselves)
        foreach (User::whereIn('id', $responsibleIds)->where('id', '!=', auth()->id())->get() as $responsible) {
            $responsible->notify(new DomainAssigned($domain, auth()->user()));
            MqttService::notifyUser($responsible->id, [
                'notif_type'   => 'domain_assigned',
                'unread_count' => $responsible->unreadNotifications()->count(),
                'title'        => 'Domain Responsibility Assigned',
                'message'      => "You are now responsible for: {$domain->domain}",
            ]);
        }

        AuditLogger::log('created', $domain, "Created domain: {$domain->domain}");

        return redirect()->route('user.domains.show', $domain)
            ->with('success', "Domain \"{$domain->domain}\" added successfully.");
    }

    public function show(Domain $domain)
    {
        abort_if(!$domain->isResponsibleUser(auth()->id()), 403);

        $domain->load(['customer:id,name,company', 'creator:id,name', 'attachments.uploader:id,name', 'responsibleUsers:id,name']);

        $status = $domain->status;
        $days   = $domain->days_until_expiry;
        $billingCycles = Domain::billingCycleOptions();

        $renewalHistory = \App\Models\AuditLog::forSubject('Domain', $domain->id)
            ->whereIn('action', ['created', 'renewed'])
            ->with('actor:id,name')
            ->orderByDesc('created_at')
            ->get();

        return view('user.domains.show', compact('domain', 'status', 'days', 'billingCycles', 'renewalHistory'));
    }

    public function quickRenew(Request $request, Domain $domain)
    {
        abort_if(!$domain->isResponsibleUser(auth()->id()), 403);

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

    public function revealPassword(Request $request, Domain $domain)
    {
        abort_if(!$domain->isResponsibleUser(auth()->id()), 403);

        $request->validate(['password' => 'required|string']);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return response()->json(['error' => 'Incorrect password.'], 403);
        }

        if (!$domain->password) {
            return response()->json(['error' => 'No password stored.'], 404);
        }

        AuditLogger::log(
            'reveal_password',
            $domain,
            'User revealed password for domain: ' . $domain->domain,
            ['ip' => $request->ip()]
        );

        return response()->json(['secret' => $domain->decrypted_password]);
    }

    public function storeAttachment(Request $request, Domain $domain)
    {
        abort_if(!$domain->isResponsibleUser(auth()->id()), 403);

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
        abort_if(!$domain->isResponsibleUser(auth()->id()), 403);
        abort_if($attachment->domain_id !== $domain->id, 404);

        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
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
}

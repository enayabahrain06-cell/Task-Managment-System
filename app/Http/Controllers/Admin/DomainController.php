<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Domain;
use App\Models\DomainAttachment;
use App\Models\User;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $query = Domain::with(['customer:id,name,company', 'responsibleUser:id,name', 'creator:id,name']);

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
        $annualTotal       = $all->sum(fn($d) => $d->annual_cost);

        $expiringThisWeek = $all->filter(fn($d) => $d->days_until_expiry !== null && $d->days_until_expiry >= 0 && $d->days_until_expiry <= 7)->values();

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
            'expiredCount', 'weekCount', 'annualTotal', 'expiringThisWeek',
            'customers', 'staffUsers', 'registrars', 'billingCycles', 'statusFilter'
        ));
    }

    public function exportPdf()
    {
        $all = Domain::with(['customer:id,name,company', 'responsibleUser:id,name'])
            ->orderBy('expires_at')
            ->get();

        $summary = [
            'total'         => $all->count(),
            'active'        => $all->filter(fn($d) => $d->status === 'active')->count(),
            'expiring_soon' => $all->filter(fn($d) => $d->status === 'expiring_soon')->count(),
            'expired'       => $all->filter(fn($d) => $d->status === 'expired')->count(),
            'annual_total'  => $all->sum(fn($d) => $d->annual_cost),
            'generated_at'  => now()->format('d M Y, H:i'),
            'by_registrar'  => $all->groupBy('registrar')->map(fn($g, $k) => [
                'label' => $k ?: 'Unknown',
                'count' => $g->count(),
            ])->sortByDesc('count')->values(),
            'by_customer'   => $all->groupBy(fn($d) => $d->customer?->name ?? 'No Customer')
                ->map(fn($g, $k) => ['label' => $k, 'count' => $g->count()])
                ->sortByDesc('count')->values(),
        ];

        $pdf = Pdf::loadView('admin.domains.pdf', compact('all', 'summary'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => false]);

        return $pdf->download('domains-' . now()->format('Y-m-d') . '.pdf');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'domain'              => 'required|string|max:255',
            'registrar'          => 'nullable|string|max:255',
            'customer_id'        => 'nullable|exists:customers,id',
            'responsible_user_id'=> 'nullable|exists:users,id',
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

        AuditLogger::log('created', $domain, "Created domain: {$domain->domain}");

        return redirect()->route('admin.domains.index')
            ->with('success', "Domain \"{$domain->domain}\" added successfully.");
    }

    public function show(Domain $domain)
    {
        $domain->load(['customer', 'responsibleUser', 'creator', 'attachments.uploader']);
        return view('admin.domains.show', compact('domain'));
    }

    public function storeAttachment(Request $request, Domain $domain)
    {
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'required|file|max:20480',
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store("domain-attachments/{$domain->id}", 'public');
            $domain->attachments()->create([
                'uploaded_by'   => auth()->id(),
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'size'          => $file->getSize(),
                'mime_type'     => $file->getMimeType(),
            ]);
        }

        AuditLogger::log('uploaded', $domain, 'Uploaded ' . count($request->file('files')) . ' attachment(s) to ' . $domain->domain);

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

    public function update(Request $request, Domain $domain)
    {
        $data = $request->validate([
            'domain'              => 'required|string|max:255',
            'registrar'          => 'nullable|string|max:255',
            'customer_id'        => 'nullable|exists:customers,id',
            'responsible_user_id'=> 'nullable|exists:users,id',
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

        $domain->update($data);

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
}

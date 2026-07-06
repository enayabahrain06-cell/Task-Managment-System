<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DomainAttachment;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DomainsController extends Controller
{
    public function index()
    {
        $domains = Domain::with(['customer:id,name,company', 'creator:id,name', 'responsibleUsers:id,name'])
            ->where('responsible_user_id', auth()->id())
            ->orWhereHas('responsibleUsers', fn($q) => $q->where('user_id', auth()->id()))
            ->orderBy('expires_at')
            ->get();

        $expiringDomains = $domains->filter(fn($d) => $d->status === 'expiring_soon')->values();
        $showExpiringPopup = $expiringDomains->isNotEmpty() && !session('domains_expiry_popup_dismissed', false);

        return view('user.domains.index', compact('domains', 'expiringDomains', 'showExpiringPopup'));
    }

    public function show(Domain $domain)
    {
        abort_if(!$domain->isResponsibleUser(auth()->id()), 403);

        $domain->load(['customer:id,name,company', 'creator:id,name', 'attachments.uploader:id,name', 'responsibleUsers:id,name']);

        $status = $domain->status;
        $days   = $domain->days_until_expiry;
        $billingCycles = Domain::billingCycleOptions();

        return view('user.domains.show', compact('domain', 'status', 'days', 'billingCycles'));
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
        abort_if(!$domain->isResponsibleUser(auth()->id()), 403);
        abort_if($attachment->domain_id !== $domain->id, 404);

        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
    }
}

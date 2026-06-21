<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Domain;

class DomainsController extends Controller
{
    public function index()
    {
        $domains = Domain::with(['customer:id,name,company', 'creator:id,name'])
            ->where('responsible_user_id', auth()->id())
            ->orderBy('expires_at')
            ->get();

        return view('user.domains.index', compact('domains'));
    }
}

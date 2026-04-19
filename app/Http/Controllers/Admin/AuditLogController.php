<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('actor')->latest();

        if ($request->filled('action')) {
            $query->where('action', 'like', $request->action . '%');
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->actor_id);
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs  = $query->paginate(40)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        $actionGroups = [
            'user'  => ['user.created','user.updated','user.deleted','user.deactivated','user.reactivated','user.role_changed','user.password_changed'],
            'tasks' => ['tasks.bulk_transferred'],
        ];

        return view('admin.audit.index', compact('logs', 'users', 'actionGroups'));
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!in_array($user->role, ['admin', 'manager'])) {
            // Allow regular users who hold the specific permission for this admin section
            $pathPermissions = [
                'admin/domains'         => 'manage_domains',
                'admin/subscriptions'   => 'manage_subscriptions',
                'admin/social-accounts' => 'manage_social_accounts',
                'admin/social-budget'   => 'view_social_budget',
                'admin/customers'       => 'manage_customers',
                'admin/approvals'       => 'view_approvals',
                'admin/audit'           => 'view_audit_log',
                // admin/reports intentionally excluded — admin reports are admin/manager only
            ];

            $path    = ltrim($request->path(), '/');
            $allowed = false;
            foreach ($pathPermissions as $prefix => $permission) {
                if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                    $allowed = $user->hasPermission($permission);
                    break;
                }
            }

            if (!$allowed) {
                abort(403, 'Access denied. You do not have permission to view this page.');
            }
        }

        if ($user->status !== 'active') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login')->withErrors([
                'email' => 'Your account has been deactivated.',
            ]);
        }

        return $next($request);
    }
}


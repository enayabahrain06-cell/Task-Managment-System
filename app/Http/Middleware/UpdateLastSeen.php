<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            $needsUpdate = is_null($user->last_seen_at)
                || $user->last_seen_at->lt(now()->subMinute());

            if ($needsUpdate) {
                $user->timestamps = false;
                $user->presence_status = $user->presence_status === 'offline' ? 'online' : $user->presence_status;
                $user->last_seen_at    = now();
                $user->save();
            }
        }

        return $next($request);
    }
}

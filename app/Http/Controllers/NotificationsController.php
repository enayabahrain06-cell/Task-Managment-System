<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationsController extends Controller
{
    public function markRead(string $id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
            $url = $notification->data['url'] ?? null;
            if ($url) {
                return redirect($url);
            }
        }

        return back();
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    public function stream(Request $request): StreamedResponse
    {
        $user = auth()->user();

        // Release the session lock so other requests from the same user aren't blocked
        session()->save();

        return response()->stream(function () use ($user) {
            set_time_limit(0);

            // Tell Apache mod_deflate not to buffer this response
            if (function_exists('apache_setenv')) {
                apache_setenv('no-gzip', '1');
            }

            $lastCount = -1;
            $ticks     = 0;
            $maxTicks  = 55; // reconnect after ~55 s to keep connections fresh

            while ($ticks < $maxTicks) {
                if (connection_aborted()) {
                    break;
                }

                $count = $user->unreadNotifications()->count();

                if ($count !== $lastCount) {
                    echo 'data: ' . json_encode(['count' => $count]) . "\n\n";
                    $lastCount = $count;
                } elseif ($ticks % 10 === 0) {
                    // Heartbeat every ~10 s so proxies don't close the connection
                    echo ": heartbeat\n\n";
                }

                flush();
                sleep(1);
                $ticks++;
            }

            echo 'data: ' . json_encode(['reconnect' => true]) . "\n\n";
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

class NotificationsController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);

        return view('alerts.index', compact('notifications'));
    }

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

    public function clearAll()
    {
        auth()->user()->notifications()->delete();

        return back();
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}

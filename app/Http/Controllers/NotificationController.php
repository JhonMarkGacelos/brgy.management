<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function recent()
    {
        $user = Auth::user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()
                ->latest()
                ->take(10)
                ->get()
                ->map(fn ($n) => [
                    'id'        => $n->id,
                    'icon'      => $n->data['icon']  ?? 'fa-bell',
                    'color'     => $n->data['color'] ?? 'gray',
                    'title'     => $n->data['title'] ?? 'Notification',
                    'body'      => $n->data['body']  ?? '',
                    'url'       => $n->data['url']   ?? '#',
                    'read'      => $n->read_at !== null,
                    'time_ago'  => $n->created_at->diffForHumans(),
                ]),
        ]);
    }

    public function index()
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return $url ? redirect($url) : back();
    }

    public function readAll()
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}

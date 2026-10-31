<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    public function markRead(Request $request, string $notification)
    {
        $request->user()->notifications()
            ->whereKey($notification)
            ->first()
            ?->markAsRead();

        return redirect()->route('notifications.index');
    }
}

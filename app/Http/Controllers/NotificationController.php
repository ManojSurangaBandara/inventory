<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = Notification::where('organization_id', $user->organization_id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            })
            ->orderByDesc('id')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = Notification::where('organization_id', $user->organization_id)->findOrFail($id);
        $notification->update(['is_read' => true]);

        if ($notification->link_url) {
            return redirect($notification->link_url);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead()
    {
        $user = Auth::user();
        Notification::where('organization_id', $user->organization_id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            })
            ->update(['is_read' => true]);

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}

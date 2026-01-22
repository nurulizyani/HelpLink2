<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display all notifications (GLOBAL)
     */
    public function index()
    {
        $notifications = Notification::orderBy('created_at', 'desc')->get();

        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead($id)
    {
        Notification::where('id', $id)->update([
            'is_read' => 1
        ]);

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark ALL notifications as read
     */
    public function readAll()
    {
        Notification::where('is_read', 0)->update([
            'is_read' => 1
        ]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
 * Get unread notifications (AJAX - topbar)
 */
public function unread()
{
    $notifications = Notification::where('is_read', 0)
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get()
        ->map(function ($n) {
            return [
                'id' => $n->id,
                'title' => $n->title ?? 'Notification',
                'message' => $n->message,
                'time' => $n->created_at->diffForHumans(),
            ];
        });

    return response()->json([
        'count' => Notification::where('is_read', 0)->count(),
        'notifications' => $notifications
    ]);
}

}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user notifications.
     * Returns format matching Flutter NotificationModel expectations.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\\Models\\User')
            ->latest()
            ->paginate(20);

        // Transform to match Flutter NotificationModel
        $transformed = $notifications->through(function ($notification) {
            return [
                'id' => $notification->id, // UUID string
                'type' => $notification->type,
                'data' => $notification->data ?? [],
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->format('Y-m-d H:i'),
            ];
        });

        return response()->json($transformed);
    }

    /**
     * Mark notification as read using read_at timestamp.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\\Models\\User')
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'message' => 'تم تعليم الإشعار كمقروء',
        ]);
    }

    /**
     * Get unread notifications count using read_at IS NULL.
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();

        $count = Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\\Models\\User')
            ->unread()
            ->count();

        return response()->json([
            'count' => $count,
        ]);
    }

    /**
     * Helper: Create a notification for a user.
     * Callable from other controllers/services.
     */
    public static function createForUser(int $userId, string $type, array $data): Notification
    {
        return Notification::create([
            'type' => $type,
            'notifiable_id' => $userId,
            'notifiable_type' => 'App\\Models\\User',
            'data' => $data,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Get user's notifications with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $type = $request->input('type'); // Optional filter by type

        $query = Notification::forUser($request->user()->id)
            ->with('conversation')
            ->latest();

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->paginate($perPage);

        return response()->json([
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ]
        ]);
    }

    /**
     * Get unread notifications count (for bell badge)
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user()->id);

        return response()->json([
            'unread_count' => $count
        ]);
    }

    /**
     * Get a specific notification
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $notification = Notification::forUser($request->user()->id)
            ->with('conversation')
            ->findOrFail($id);

        return response()->json([
            'notification' => $notification
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::forUser($request->user()->id)
            ->findOrFail($id);

        $this->notificationService->markAsRead($id);

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification->fresh()
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $notification = Notification::forUser($request->user()->id)
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted'
        ]);
    }

    /**
     * Get recent unread notifications (for dropdown preview)
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);

        $notifications = Notification::forUser($request->user()->id)
            ->unread()
            ->with('conversation')
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount($request->user()->id)
        ]);
    }
}

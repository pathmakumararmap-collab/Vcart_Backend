<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    #[OA\Get(
        path: '/notifications',
        tags: ['Customer Orders'],
        summary: "List the authenticated user's notifications",
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Notification list')],
    )]
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->notifications()->latest()->paginate(20),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}

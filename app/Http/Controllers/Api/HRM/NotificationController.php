<?php

namespace App\Http\Controllers\Api\HRM;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated employee.
     */
    public function index()
    {
        try {
            $notifications = Notification::where('employee_id', Auth::user()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $notifications,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark all notifications as read for the authenticated employee.
     */
    public function markAsRead(Request $request)
    {
        try {
            Notification::where('id', $request->id)
                ->where('is_seen', false)
                ->update(['is_seen' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notifications marked as read.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAllAsRead(Request $request)
    {
        try {
            Notification::where('employee_id', auth()->id())
                ->where('is_seen', false)
                ->update(['is_seen' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notifications marked as read.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send FCM notification manually
     */
    public function sendFcm(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'title' => 'required',
            'body' => 'required',
        ]);

        $result = \App\Services\FcmService::sendNotification(
            $request->token,
            [
                'title' => $request->title,
                'body' => $request->body,
            ]
        );

        if ($result) {
            return response()->json(['success' => true, 'data' => $result]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send notification'], 500);
    }
}

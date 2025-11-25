<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use App\Services\FirebaseService;
use App\Models\Notification;
use Auth;
use Illuminate\Support\Facades\Http;
use Google\Client as GoogleClient;


class PushNotificationController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function sendPushNotification(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required',
                'body' => 'required',
            ]);

            $user     =  Auth::user();
            $id       = $user->id;
            $fcmToken = $user->fcm_token;

            if (!$fcmToken) {
                return response()->json(['message' => 'FCM token not found'], 404);
            }

            $this->firebaseService->sendNotification($fcmToken, $request->title, $request->body);

            $notificationHistory = Notification::create([
                'user_id'        => $id,
                'title'          => $request->title,
                'body'           => $request->body,
            ]);

            \Log::info('notifications sent successfully.');

            return response()->json(['message' => 'Notification sent successfully'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add to wishlist: ' . $e->getMessage()
            ], 500);
        }

    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Events\CustomNotificationEvent;
use App\Services\FirebaseService;
use App\Models\Notification;

class NotificationController extends Controller
{
    public $user;
    protected $firebaseService;
    public $notification;

    public function __construct(User $user, Notification $notification, FirebaseService $firebaseService) {
        $this->user = $user;
        $this->firebaseService = $firebaseService;
        $this->notification = $notification;
    }

    public function index() {
        $notifications = $this->notification->where('type', 'custom')->orderBy('created_at', 'desc')->take(5)->get();
       return view('notifications.index', compact('notifications'));
    }

    public function sendNotificationToAllUsers(Request $request)
    {
        $title = $request->notification_title;
        $body = $request->notification_body;

        $users = $this->user->where('notifications_enabled', 1)
                            ->where('is_blocked', false)
                            ->whereNotNull('fcm_token')
                            ->get();

        if ($users->isEmpty()) {
            return response()->json(['success' => 'No users with FCM tokens found'], 200);
        }

        $tokens = $users->pluck('fcm_token')->toArray();

        $this->firebaseService->sendNotificationToUsers($tokens, $title, $body);

        Notification::create([
            'user_id' => NULL,
            'title'   => $title,
            'body'    => $body,
            'type'    => 'custom'
        ]);
        
        \Log::info('notification send to all users');

        return response()->json(['success' => 'Notification sent to all users'], 200);
    }

}

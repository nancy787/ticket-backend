<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(config_path('firebase_credentials.json'));

        $this->messaging = $factory->createMessaging();

    }

    public function sendNotification($token, $title, $body)
    {
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body));

        $this->messaging->send($message);
    }
    
    public function sendNotificationByTopic($topic, $title, $body)
    {
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body));

        $this->messaging->send($message);
    }

    public function sendNotificationToUsers(array $tokens, $title, $body)
    {
        $messages = array_map(function($token) use ($title, $body) {
            return CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body));
        }, $tokens);

        $this->messaging->sendAll($messages);
    }
}


?>
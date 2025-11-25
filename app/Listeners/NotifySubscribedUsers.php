<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\TopicService;
use App\Events\SubscriptionNotificationEvent;
use App\Services\FirebaseService;
use App\Models\User;

class NotifySubscribedUsers
{
    /**
     * Create the event listener.
     */
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionNotificationEvent $event): void
    {
        $topics = TopicService::$topics;
        $ticket = $event->ticket;
        $categoryType = $event->categoryType;
        $topic = str_replace(["'", " "], ["", "_"], $categoryType);

        $title = "A new ticket #{$ticket->ticket_id} is available for sale";
        $body  = "New Ticket #{$ticket->ticket_id}  for event {$ticket->event->name} category {$categoryType} is created and open for Sale";

        try {
            $subscribedUsers = User::whereHas('subscriptions', function ($query) use ($categoryType) {
                $query->whereHas('category', function ($categoryQuery) use ($categoryType) {
                    $categoryQuery->where('name', $categoryType);
                });
            })->get();

            if ($subscribedUsers->isNotEmpty()) {
                $this->firebaseService->sendNotificationByTopic($topic, $title, $body);
                \Log::info("Notification sent to topic {$topic}: Title {$title}, Body {$body}");
            }

        } catch (\Exception $e) {
            \Log::error("Failed to send notification to" . $e->getMessage());
        }
    }

}

<?php

namespace App\Listeners;

use App\Events\TicketAddedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Wishlist;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\FirebaseService;
use App\Models\Notification;
use App\Models\User;


class SendTicketNotification
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
    public function handle(TicketAddedEvent $event): void
    {
        $newTicket = $event->ticket;

        $mywishLists = Wishlist::where('event_id', $newTicket->event_id)
                               ->with(['user' => function($query) {
                                   $query->where('notifications_enabled', true)
                                         ->whereNotNull('fcm_token');
                               }])
                               ->get();
         $notifiedUsers = [];

         foreach ($mywishLists as $wishlist) {
            $user = $wishlist->user;

            if ($user && !in_array($user->id, $notifiedUsers)) {
                $notifiedUsers[] = $user->id;

                $title = "New ticket added on your wishilist";
                $body = "A ticket on your wishlist {$newTicket->event->name} {$newTicket->ticketCategory->name} has been added for sale '#{$newTicket->ticket_id}'";

                try {
                    $this->firebaseService->sendNotification($user->fcm_token, $title, $body);
                    Notification::create([
                        'user_id' => $user->id,
                        'title'   => $title,
                        'body'    => $body,
                    ]);
                    \Log::info("Notification sent and saved for user ID {$user->id}, {$user->name} Ticket Body {$body}");
                } catch (\Exception $e) {
                    \Log::error("Failed to send notification to user ID {$user->id}: " . $e->getMessage());
                }
            }
        }
    }
}

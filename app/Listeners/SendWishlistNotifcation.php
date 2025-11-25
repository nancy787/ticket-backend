<?php

namespace App\Listeners;

use App\Events\EventAddedToWishlist;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Wishlist;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\FirebaseService;
use App\Models\Notification;
use App\Models\User;

class SendWishlistNotifcation
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

    public function handle(EventAddedToWishlist $event): void
    { 
        $user = $event->authUser;
        $eventName = Event::find($event->eventId)->name;
        $ticketCatgories = TicketCategory::whereIn('id', $event->categories)->pluck('name')->toArray();
        $categoryName =  implode(', ' , $ticketCatgories);
        $title = "Event added to wishlist";
        $body  = "You have successfully added '{$eventName}' {$categoryName} to your wishlist.";

        try {
            $this->firebaseService->sendNotification($user->fcm_token, $title, $body);
            Notification::create([
                'user_id' => $user->id,
                'title'   => $title,
                'body'    => $body,
            ]);
            \Log::info("Notification sent and saved for user ID {$user->id} user Name {$user->name} with message: {$body}");
        } catch (\Exception $e) {
            \Log::error("Failed to send notification to user ID {$user->id} user Name {$user->name}: " . $e->getMessage());
        }
    }
}

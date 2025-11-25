<?php

namespace App\Listeners;


use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Wishlist;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\FirebaseService;
use App\Models\Notification;
use App\Models\User;
use App\Models\WishlistSubscription;
use App\Events\WislistSubscriptionNotificationEvent;

class WishlistSubscriptionNotificationListner
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

     public function handle(WislistSubscriptionNotificationEvent $event): void
     {
         $ticket = $event->ticket;
         $ticketEvent = $ticket->event;

         // Extract category IDs
         $singleCategoryId = $ticket->category_id;
         $ticketCategories = json_decode($ticket->category_ids, true) ?: [];
         $allTicketCategories = array_filter(array_merge([$singleCategoryId], $ticketCategories));

         $subscriptions = WishlistSubscription::where(function ($query) use ($ticketEvent, $allTicketCategories) {
             $this->addEventFiltering($query, $ticketEvent, $allTicketCategories);
         })->get();

         // Send notifications
         $this->notifyUsers($subscriptions, $ticket);
     }

     /**
      * Adds filtering logic based on event, continent, country, and category.
      */

      protected function addEventFiltering($query, $ticketEvent, $allTicketCategories): void
      {
          // Filter by event_id and matching categories
          if ($ticketEvent->id) {
              $query->orWhere(function ($query) use ($ticketEvent, $allTicketCategories) {
                  $query->where('event_id', $ticketEvent->id)
                        ->where(function ($query) use ($allTicketCategories) {
                            $this->addCategoryFilter($query, $allTicketCategories);
                        });
              });
          }

          // Filter by continent_id and matching categories (No event or country specified)
          if ($ticketEvent->continent_id) {
              $query->orWhere(function ($query) use ($ticketEvent, $allTicketCategories) {
                  $query->where('continent_id', $ticketEvent->continent_id)
                        ->whereNull('event_id')
                        ->whereNull('country_id') // Ensure no country is linked
                        ->where(function ($query) use ($allTicketCategories) {
                            $this->addCategoryFilter($query, $allTicketCategories);
                        });
              });
          }

          // Filter by country_id and matching categories (No event or continent specified)
          if ($ticketEvent->country_id) {
              $query->orWhere(function ($query) use ($ticketEvent, $allTicketCategories) {
                  $query->where('country_id', $ticketEvent->country_id)
                        ->whereNull('continent_id') // Ensure no continent is linked
                        ->whereNull('event_id')     // Ensure no event is linked
                        ->where(function ($query) use ($allTicketCategories) {
                            $this->addCategoryFilter($query, $allTicketCategories);
                        });
              });
          }
          // General category filter (No event, continent, or country associated)
          if (!empty($allTicketCategories)) {
              $query->orWhere(function ($query) use ($allTicketCategories) {
                  $query->whereNotNull('category_ids')
                        ->whereNull('continent_id') // Global category subscription
                        ->whereNull('event_id')
                        ->whereNull('country_id')
                        ->where(function ($query) use ($allTicketCategories) {
                            $this->addCategoryFilter($query, $allTicketCategories);
                        });
              });
          }
      }

     /**
      * Adds a category filter to the query using JSON search.
      */

     protected function addCategoryFilter($query, $allTicketCategories): void
     {
         foreach ($allTicketCategories as $category) {
             $query->orWhereJsonContains('category_ids', $category);
         }
     }

     /**
      * Sends notifications to the users subscribed to the wishlist.
      */

     protected function notifyUsers($subscriptions, $ticket): void
     {
         $notifiedUsers = [];
     
         foreach ($subscriptions as $subscription) {
             $user = $subscription->user;
     
             if ($user && ( $user->is_premium || $user->is_free_subcription ) && $user->notifications_enabled && !in_array($user->id, $notifiedUsers)) {
                 $notifiedUsers[] = $user->id;
     
                 $title = "New ticket listed for sale";
                 $body = "A ticket for {$ticket->event->name} {$ticket->ticketCategory->name} has been listed for sale '#{$ticket->ticket_id}'";
                 \Log::info('log 2 Wishlist subscription items retrieved: ' . $body);
     
                 try {
                     $this->firebaseService->sendNotification($user->fcm_token, $title, $body);
                     Notification::create([
                         'user_id' => $user->id,
                         'title'   => $title,
                         'body'    => $body,
                     ]);
                     \Log::info("log 3 Notification sent and saved for user ID {$user->id}, {$user->name} Ticket Body {$body}");
                 } catch (\Exception $e) {
                     \Log::error("log error Failed to send notification to user ID {$user->id}: " . $e->getMessage());
                 }
             }
         }
     }

}

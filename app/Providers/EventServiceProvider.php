<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\TicketAddedEvent;
use App\Events\EventAddedToWishlist;
use App\Events\RemoveFromWishlistEvent;
use App\Listeners\SendTicketNotification;
use App\Listeners\SendWishlistNotifcation;
use App\Listeners\SendRemovedFormWishlistNotifcation;
use App\Events\SubscriptionNotificationEvent;
use App\Listeners\NotifySubscribedUsers;
use App\Events\WislistSubscriptionNotificationEvent;
use App\Listeners\WishlistSubscriptionNotificationListner;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TicketAddedEvent::class => [
            SendTicketNotification::class,
        ],
        EventAddedToWishlist::class => [
            SendWishlistNotifcation::class,
        ],
        RemoveFromWishlistEvent::class => [
            SendRemovedFormWishlistNotifcation::class,
        ],
        SubscriptionNotificationEvent::class => [
            NotifySubscribedUsers::class,
        ],
        WislistSubscriptionNotificationEvent::class => [
            WishlistSubscriptionNotificationListner::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemoveFromWishlistEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $eventId;
    public $authUser;
    public $categories;

    /**
     * Create a new event instance.
     */
    public function __construct($eventId, $authUser, $categories)
    {
        $this->eventId     = $eventId;
        $this->authUser    = $authUser;
        $this->categories  = $categories;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}

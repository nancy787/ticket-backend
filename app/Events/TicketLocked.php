<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketLocked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $ticketId;
    public $lockedByUserId;
    public $lockedUntil;

    public function __construct($ticketId, $lockedByUserId, $lockedUntil)
    {
        $this->ticketId = $ticketId;
        $this->lockedByUserId = $lockedByUserId;
        $this->lockedUntil = $lockedUntil;
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel('tickets');
    }

    public function broadcastWith()
    { 
        return [
            'ticket_id' => $this->ticketId,
            'locked_by_user_id' => $this->lockedByUserId,
            'locked_until' => $this->lockedUntil,
        ];
    }
}

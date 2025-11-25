<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Event;
use App\Models\Ticket;

class SellTicket extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function event() {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function ticket() {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }
}

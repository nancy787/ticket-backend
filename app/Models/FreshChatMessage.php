<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreshChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
     'sender_id',
     'receiver_id',
     'ticket_id',
     'sender_user_email',
     'receiver_user_email',
     'freschat_user_id',
     'freschat_conversation_id',
     'message',
     'message_send_from'
    ];
}

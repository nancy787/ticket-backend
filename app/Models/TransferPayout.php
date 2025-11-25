<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id', 'buyer_id', 'ticket_id', 'stripe_connected_id', 'destination', 'currency', 'amount', 'status', 'details'
    ];
}

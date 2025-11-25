<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stripe_payment_intent_id',
        'amount',
        'status',
        'items',
        'type',
        'currency_type',
        'transaction_id',
        'coupon_code',
        'description',
        'stripe_payment_link_url',
        'purchased_by',
        'stripe_payment_link_id',
        'ticket_id',
        'stripe_connect_account_id',
        'is_transfered'
    ];

    protected $casts = [
        'items' => 'array', // Automatically cast JSON to array
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

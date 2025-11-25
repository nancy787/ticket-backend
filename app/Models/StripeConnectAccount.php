<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class StripeConnectAccount extends Model
{
    use HasFactory;

    protected $fillable  = [ 'user_id' ,  'stripe_account_id',  'stripe_account_status', 'temporary_status', 'is_created'];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

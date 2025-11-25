<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConditionalFee extends Model
{
    use HasFactory;
    protected $fillable = ['currency_type', 'application_fee_amount'];
}

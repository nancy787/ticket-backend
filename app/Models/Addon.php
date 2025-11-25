<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    use HasFactory;

    protected $fillable = [
        'photo_pack',
        'race_with_friend',
        'spectator',
        'charity_ticket'
    ];
}

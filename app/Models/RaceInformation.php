<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaceInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'title',
        'value'
    ];

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Continent;

class Country extends Model
{
    use HasFactory;

    protected  $guarded = [];

    public function continent()
    {
        return $this->belongsTo(Continent::class);
    }
}


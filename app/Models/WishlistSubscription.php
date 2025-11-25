<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\TicketCategory;
use App\Models\Continent;
use App\Models\Country;

class WishlistSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'continent_id',
        'country_id',
        'event_id',
        'category_ids',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function continent()
    {
        return $this->belongsTo(Continent::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function ticketCategory()
    {
        return $this->belongsTo(TicketCategory::class, 'category_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Ticket;
use App\Models\RaceInformation;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'subtitle',
        'description',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'continent',
        'start_date',
        'end_date',
        'banner_name',
        'image',
        'open_for',
        'active',
        'archived',
        'city_code',
        'currency',
        'continent_id',
        'country_id',
    ];

    public function tickets() {

        return $this->hasMany(Ticket::class, 'event_id', 'id');
    }

    public function ticketCategoryCounts()
    {
        return $this->tickets()
            ->selectRaw('category_id, COUNT(*) as total')
            ->where('available_for', 'available')
            ->groupBy('category_id');
    }

    public function raceInformation() {

        return $this->hasMany(RaceInformation::class);
    }

    public function availableticketCategoryCounts()
    {
        return $this->tickets()
            ->selectRaw('category_id, COUNT(*) as total')
            ->where('available_for', 'available')
            ->where('isverified', 1)
            ->groupBy('category_id');
    }

    public function unavailableticketCategoryCounts()
    {
        return $this->tickets()
            ->selectRaw('category_id, COUNT(*) as total')
            ->where('available_for', 'available')
            ->where('isverified', 0)
            ->groupBy('category_id');
    }

    public function wishlistSubscriptions() {
        return $this->hasMany(Wishlist::class, 'event_id', 'id');
    }

    public function advancewishlistSubscriptions() {
        return $this->hasMany(WishlistSubscription::class, 'event_id', 'id');
    }
}

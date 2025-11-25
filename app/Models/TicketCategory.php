<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Ticket;
use App\Models\TicketCategoryType;
use App\Models\Subscription;

class TicketCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'ticket_category_type_id',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'category_id', 'id');
    }

    public function categoryType()
    {
        return $this->belongsTo(TicketCategoryType::class, 'ticket_category_type_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}

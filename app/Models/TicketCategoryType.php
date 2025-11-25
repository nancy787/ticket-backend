<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use  App\Models\TicketCategory;

class TicketCategoryType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function ticketCategories()
    {
        return $this->hasMany(TicketCategory::class, 'ticket_category_type_id', 'id');
    }

}

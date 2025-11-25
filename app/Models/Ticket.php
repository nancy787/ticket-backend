<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Event;
use App\Models\TicketCategory;
use App\Models\SellTicket;
use App\Models\user;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'event_id',
        'category_id',
        'name',
        'description',
        'image',
        'stock_quantity',
        'available_for',
        'charity_ticket',
        'photo_pack',
        'isverified',
        'price',
        'service',
        'total',
        'start_date',
        'end_date',
        'ticket_link',
        'day',
        'change_fee',
        'currency_type',
        'race_with_friend',
        'spectator',
        'archive',
        'created_by',
        'buyer',
        'resale',
        'multiple_tickets',
        'locked_until',
        'locked_by_user_id',
        'seller_paid',
        'unpersonalised_ticket',
        'dublicate_link',
        'sold_date',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }
    public function ticketCategory()
    {
        return $this->belongsTo(TicketCategory::class, 'category_id', 'id');
    }

    public function ticketCategoryType()
    {
        return $this->hasOneThrough(
            TicketCategoryType::class,
            TicketCategory::class,'id', 'id', 'category_id', 'ticket_category_type_id'
        );
    }

    public function user() {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function buyerName() {
        return $this->belongsTo(User::class, 'buyer', 'id');
    }

    public function soldTicket() {
        return $this->belongsTo(SellTicket::class, 'id', 'ticket_id');
    }
}

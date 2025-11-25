<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\TicketCategory;
use App\Models\Ticket;
use App\Models\TicketCategoryType;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Auth;
use App\Models\SellTicket;
use App\Events\TicketAddedEvent;
use Illuminate\Support\Facades\DB;
use App\Notifications\TicketAddedNotification;
use App\Notifications\ResaleTicketNotification;
use App\Models\User;
use App\Events\SubscriptionNotificationEvent;

class TicketController extends Controller
{
    protected $event;
    protected $ticketCategory;
    protected $ticket;
    protected $ticketCategoryType;

    public function __construct(Event $event, TicketCategory $ticketCategory, Ticket $ticket, TicketCategoryType $ticketCategoryType) {

        $this->event = $event;
        $this->ticketCategory = $ticketCategory;
        $this->ticket = $ticket;
        $this->ticketCategoryType = $ticketCategoryType;
    }

    public function index(Request $request)
    {
        try {
            $userId = Auth::user()->id;

            $ticketData = $this->ticket->select('tickets.*', 'events.id as event_id', 'events.name as event_name')
                                        ->with('event')
                                        ->where('archive', 0)
                                        ->whereIn('available_for', ['available', 'pending'])
                                        ->join('events', 'tickets.event_id', '=', 'events.id')
                                        ->orderBy('tickets.created_at', 'desc')
                                        ->orderBy('events.name', 'asc');

            if ($request->name) {
                $ticketData->where('tickets.name', 'like', '%' . $request->name . '%');
            }

            if ($request->category) {
                $ticketCategoryName   = $request->category;
                $ticketCategoryTypeId = $this->ticketCategoryType->whereIn('name', json_decode($ticketCategoryName))->pluck('id');

                $ticketData->whereHas('ticketCategory', function ($query) use ($ticketCategoryTypeId) {
                    $query->whereIn('ticket_category_type_id', json_decode($ticketCategoryTypeId));
                });
            }

            if ($request->event) {
                $eventName = $request->event;
                $ticketData->whereHas('event', function ($query) use ($eventName) {
                    $query->whereIn('name', json_decode($eventName));
                });
            }

            if($request->ticket_categories) {
               $categoryIds = $this->ticketCategory->whereIn('name', json_decode($request->ticket_categories))->pluck('id');
               $ticketData->whereIn('category_id', $categoryIds);
            }

            if($request->my_tickets) {
                $ticketData->where('tickets.created_by', $userId);
            }else {
                $ticketData->where('isverified', 1);
            }

            $ticketData = $ticketData->paginate(env('PAGE', 10));

            $formattedTickets = [];

            foreach ($ticketData as $ticket) {
                $ticketDetails = [
                    'id'                => $ticket->id,
                    'ticket_id'         => $ticket->ticket_id,
                    'event_id'          => $ticket->event_id,
                    'event_name'        => $ticket->event->name,
                    'event_address'     => $ticket->event->address,
                    'country'           => $ticket->event->country,
                    'continent'         => $ticket->event->continent,
                    'category_type'     => $ticket->ticketCategoryType->name,
                    'category_name'     => $ticket->ticketCategory->name,
                    'image'             => $ticket->image,
                    'available_for'     => $ticket->available_for,
                    'charity_ticket'    => $ticket->charity_ticket,
                    'photo_pack'        => $ticket->photo_pack,
                    'race_with_friend'  => $ticket->race_with_friend,
                    'spectator'         => $ticket->spectator,
                    'price'             => $ticket->price,
                    'change_fee'        => $ticket->change_fee,
                    'service'           => $ticket->service,
                    'total'             => $ticket->total,
                    'isverified'        => $ticket->isverified,
                    'currency_type'     => $ticket->currency_type,
                    'start_date'        => formatgetDate($ticket->start_date),
                    'end_date'          => formatgetDate($ticket->end_date),
                    'day'               => formatgetDay($ticket->day),
                    'created_at'        => $ticket->created_at,
                    'updated_at'        => $ticket->updated_at,
                    'created_by'        => $ticket->user->name ?? '',
                    'unpersonalised_ticket' => $ticket->unpersonalised_ticket
                ];

                $formattedTickets[] = $ticketDetails;
            }

            return response()->json([
                'message'    => 'success',
                'ticketData' => $formattedTickets,
                'pagination' => [
                    'current_page' => $ticketData->currentPage(),
                    'total_pages'  => $ticketData->lastPage(),
                    'per_page'     => $ticketData->perPage(),
                    'total'        => $ticketData->total(),
                ]
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch ticket data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'event_id'          => 'required',
            'category'          => 'required',
            'category_type'     => 'required',
            'start_date'        => 'required|date_format:d-m-Y',
            'end_date'          => 'required|after_or_equal:start_date',
            'available_for'     => 'required|in:available,pending,sold,withdrawn',
            'photo_pack'        => 'numeric|between:0,5',
            'race_with_friend'  => 'numeric|between:0,5',
            'spectator'         => 'numeric|between:0,5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 200);
        }

        $events = $this->event->find($request->event_id);

        if(!$events) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.'
            ], 200);
        }

        $startDate = formatDateForInput($request->start_date);
        $endDate   = formatDateForInput($request->end_date);
        $day       = formatDateForInput($request->day);

        if ($day < $startDate || $day > $endDate) {
            return response()->json([
                'success' => false,
                'message' => 'The day must be between the start and end date',
            ], 200);
        }

        try {
            $ticketId               = $this->generateTicketId($request->event_id) ?? null;
            $ticketCategoryId       = $this->getTicketCategory($request->category);
            $ticketCategoryTypeId   = $this->getTicketSubCategory($request->category_type);
            if(!$ticketCategoryTypeId) {
                return response()->json([
                    'success' => false,
                    'message' => "This category_type is not available"
                ], 200);
            }

            $imageUrl = $events->image;
            $authUser = Auth::user();
            $ticketLink = json_decode($request->ticket_link);

            foreach ($ticketLink as $link) {
                $existingTicket = $this->ticket->whereJsonContains('ticket_link', $link)->first();
                if ($existingTicket) {
                    $duplicateFlag = true;
                    break;
                }
            }

            $ticketData = [
                'ticket_id'         => $ticketId,
                'event_id'          => $request->event_id,
                'category_id'       => $ticketCategoryTypeId,
                'image'             => $imageUrl,
                'start_date'        => $startDate,
                'end_date'          => $endDate,
                'day'               => $day,
                'available_for'     => $request->available_for,
                'ticket_link'       => json_encode($ticketLink),
                'charity_ticket'    => $request->charity_ticket,
                'photo_pack'        => $request->photo_pack ?? null,
                'spectator'         => $request->spectator ?? null,
                'race_with_friend'  => $request->race_with_friend ?? null,
                'created_by'        => $authUser->id,
                'currency_type'     => $events->currency ?? null,
                'dublicate_link'    => $duplicateFlag ?? false
            ];

            $ticket = $this->ticket->create($ticketData);


            return response()->json([
                'success'      => true,
                'message'      => 'Ticket created successfully',
                'ticketData'   => $ticketData,
                'eventName'    => $ticket->event->name,
                'categoryName' => $request->category_type
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($ticketId) {
        try {
            $ticket = $this->ticket->find($ticketId);

            if (!$ticket) {
                return response()->json(['error' => 'Ticket not found'], 404);
            }

            if($ticket->is_locked) {
                return response()->json([
                    'error' => 'Ticket is locked',
                ], 404);
            }

            if($ticket->available_for == 'sold') {
                return response()->json([
                    'error' => 'Ticket is sold',
                ], 404);
            }

            $ticket->delete();

            if (!empty($ticket->image)) {
                $imagePath = public_path($ticket->image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Ticket deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function getTicketCategory($ticketCategoryType) {
        $ticketCategoryId = $this->ticketCategoryType->where('name', $ticketCategoryType)->pluck('id')->first();
        return $ticketCategoryId;
    }

    protected function getTicketSubCategory($ticketCategorySubType) {
        $ticketCategoryTypeId = $this->ticketCategory->where('name', $ticketCategorySubType)->pluck('id')->first();
        return $ticketCategoryTypeId;
    }

    protected function ImageUpload(Request $request)
    {
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('eventImages', 'public');
            return Storage::url($imagePath);
        }
        return null;
    }

    public function getTicketCategories(Request $request) {

        try{
            $ticketCategories = $this->ticketCategory->with('categoryType');

            if ($request->category) {    
                $ticketCategoryName   = $request->category;
                $ticketCategoryTypeId = $this->ticketCategoryType->select('id')->whereIn('name', json_decode($ticketCategoryName))->pluck('id');
                $ticketCategories->whereIn('ticket_category_type_id', json_decode($ticketCategoryTypeId));
            }

            $perPage = $request->input('page', env('PAGE', 20));
            
            $ticketCategories = $ticketCategories->paginate($perPage);

            return response()->json([
                'message' => 'success',
                'ticketCategories' => $ticketCategories
            ]);

         } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ticket details : ' . $e->getMessage()
            ], 500);
        }

    }

    protected function generateTicketId($eventId) {

        $eventCityCode = $this->event->where('id', $eventId)->pluck('city_code')->first();

        return DB::transaction(function () use ($eventCityCode) {
            $lastTicket = $this->ticket->lockForUpdate()->orderBy('id', 'desc')->first();
            if ($lastTicket) {
                $newTicketId = $lastTicket->id + 1;
            } else {
                $newTicketId = 1;
            }

            return $eventCityCode . $newTicketId;
        });
    }

    public function update($ticketId, Request $request) {

        try {

            $events = $this->event->where('id', $request->event_id)->first();

            if(!$events) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found.'
                ], 200);
            }

            $ticket = $this->ticket->find($ticketId);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found.'
                ], 200);
            }

            if ($request->has('category_type')) {
                $ticketCategoryTypeId = $this->getTicketSubCategory($request->category_type);
                if (!$ticketCategoryTypeId) {
                    return response()->json([
                        'success' => false,
                        'message' => "This category_type is not available"
                    ], 200);
                }

                $ticket->category_id = $ticketCategoryTypeId;
            }

            $updateFields = [
                'event_id',
                'category_id',
                'image',
                'start_date',
                'end_date',
                'day',
                'available_for',
                'ticket_link',
                'charity_ticket',
                'photo_pack',
                'spectator',
                'race_with_friend',
            ];

            if ($request->has('start_date')) {
                $ticket->start_date = formatDateForInput($request->start_date);
            }
            if ($request->has('end_date')) {
                $ticket->end_date = formatDateForInput($request->end_date);
            }

            if ($request->has('day')) {
                $ticket->day = formatDateForInput($request->day);
            }

            $ticketLinks = $request->ticket_links ?? [];
            $duplicateFlag = false;

            if (!empty($ticketLinks)) {
                foreach ($ticketLinks as $link) {
                    $existingTicket = $this->ticket
                        ->whereJsonContains('ticket_link', $link)->where('id', '!=', $ticketId)->first();
                    if ($existingTicket) {
                        $duplicateFlag = true;
                        break;
                    }
                }
            }

            if ($request->has('ticket_links')) {
                $ticketLink = is_array($request->ticket_links) ? $request->ticket_links : json_decode($request->ticket_links);
                $ticket->ticket_link = json_encode($ticketLink);
            }

            foreach ($updateFields as $field) {
                if ($request->has($field) && !in_array($field, ['start_date', 'end_date', 'day', 'ticket_link'])) {
                    $ticket->{$field} = $request->input($field);
                }
            }

            $ticket->dublicate_link = $duplicateFlag;

            $ticket->update();
    
            return response()->json([
                'success' => true,
                'message' => 'Ticket updated successfully',
                'ticket' => $ticket
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getpurchasedTickets(Request $request) {
        try {
            $userId = Auth::user()->id;

            $getSoldpurchasedTickets = SellTicket::select(
                'sell_tickets.*', 'tickets.*',
                'ticket_categories.name as category_name', 'ticket_category_types.name as category_type',
                'events.name as event_name', 'events.address as address', 'events.country as country', 'events.continent as continent'
                 )
                    ->join('tickets', 'sell_tickets.ticket_id', '=', 'tickets.id')
                    ->join('ticket_categories', 'tickets.category_id', '=', 'ticket_categories.id')
                    ->join('ticket_category_types', 'ticket_categories.ticket_category_type_id', '=', 'ticket_category_types.id')
                    ->join('events', 'events.id', '=', 'tickets.event_id')
                    ->where('sell_tickets.user_id', $userId)
                    ->where('tickets.resale', 1)
                    ->where('tickets.archive', 1)
                    ->get();
            
            $getBuypurchasedTickets = $this->ticket->where('buyer', $userId)
                                    ->where('available_for', 'sold')
                                    ->where('tickets.resale', 1)
                                    ->where('tickets.archive', 1)
                                    ->get();

            $purchasedTickets = $getSoldpurchasedTickets->merge($getBuypurchasedTickets);
            $todayDate = Carbon::today()->format('d-m-Y');

            $todayTickets = $purchasedTickets->filter(function ($ticket) use ($todayDate) {
                return $ticket->start_date === $todayDate;
            });

            $futureTickets = $purchasedTickets->filter(function ($ticket) use ($todayDate) {
                return $ticket->start_date !== $todayDate; // Tickets with future start dates
            })->sortByDesc('start_date');

            $sortedTickets = $todayTickets->merge($futureTickets);

            $formattedTickets = $sortedTickets->map(function ($ticket) {
                return [
                    'id'                => $ticket->id,
                    'ticket_id'         => $ticket->ticket_id,
                    'event_id'          => $ticket->event_id,
                    'event_name'        => $ticket->event_name ?? $ticket->event->name,
                    'event_address'     => $ticket->address ?? $ticket->event->address,
                    'country'           => $ticket->country ?? $ticket->event->country,
                    'continent'         => $ticket->continent ?? $ticket->event->continent,
                    'category_type'     => $ticket->category_type ?? $ticket->ticketCategoryType->name,
                    'category_name'     => $ticket->category_name ?? $ticket->ticketCategory->name,
                    'image'             => $ticket->image,
                    'available_for'     => $ticket->available_for,
                    'charity_ticket'    => $ticket->charity_ticket,
                    'photo_pack'        => $ticket->photo_pack,
                    'race_with_friend'  => $ticket->race_with_friend,
                    'spectator'         => $ticket->spectator,
                    'price'             => $ticket->price,
                    'change_fee'        => $ticket->change_fee,
                    'service'           => $ticket->service,
                    'total'             => $ticket->total,
                    'isverified'        => $ticket->isverified,
                    'currency_type'     => $ticket->currency_type,
                    'start_date'        => formatgetDate($ticket->start_date),
                    'end_date'          => formatgetDate($ticket->end_date),
                    'day'               => formatgetDay($ticket->day),
                    'created_at'        => $ticket->created_at,
                    'updated_at'        => $ticket->updated_at,
                    'purchased_by'      =>$ticket->buyerName ?  $ticket->buyerName->name : '',
                    'attachment' => [
                        'link' => array_merge(
                            is_array(json_decode($ticket->ticket_link, true)) ? json_decode($ticket->ticket_link, true) : [],
                            $ticket->pdf ? array_map(
                                fn($pdf) => env('APP_URL') . str_replace('/storage//storage/', '/storage/', Storage::url($pdf)),
                                is_array(json_decode($ticket->pdf, true)) ? json_decode($ticket->pdf, true) : []
                            ) : []
                        ),
                    ],
                ];
            });

            return response()->json([
                'message'    => 'success',
                'ticketData' => $formattedTickets->toArray()
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list purchased tickets: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resaleTicket(Request $request, $ticketId) {

        $authUser  = Auth::user();
        $today     = formatDateForInput(Carbon::now());

        $purchasedTicket = $this->ticket->where('buyer', $authUser->id)
                                        ->where('archive', 1)
                                        ->where('end_date', '>=', $today)
                                        ->find($ticketId);

        if (!$purchasedTicket) {
            return response()->json([
                'message' => 'Ticket not found.',
            ], 404);
        }

        $admins = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->get();

        $admins->each(function($admin) use ($purchasedTicket, $authUser) {
            $admin->notify(new ResaleTicketNotification($purchasedTicket, $authUser, $admin));
        });

        $purchasedTicket->update([
            'resale'  => false,
        ]);

        $ticketId  = $this->generateTicketId($purchasedTicket->event_id) ?? null;

        $purchasedTicketData = [
            'ticket_id'         => $ticketId ?? null,
            'event_id'          => $purchasedTicket->event_id ?? null,
            'category_id'       => $purchasedTicket->category_id ?? null,
            'name'              => $purchasedTicket->name ?? null,
            'description'       => $purchasedTicket->description ?? null,
            'image'             => $purchasedTicket->image ?? null,
            'stock_quantity'    => $purchasedTicket->stock_quantity ?? null,
            'available_for'     => 'available',
            'charity_ticket'    => $purchasedTicket->charity_ticket ?? null,
            'photo_pack'        => $purchasedTicket->photo_pack ?? null,
            'isverified'        => false,
            'currency_type'     => $purchasedTicket->currency_type ?? null,
            'price'             => $purchasedTicket->price ?? null,
            'service'           => $purchasedTicket->service ?? null,
            'change_fee'        => $purchasedTicket->change_fee ?? null,
            'total'             => $purchasedTicket->total ?? null,
            'start_date'        => $purchasedTicket->start_date ?? null,
            'end_date'          => $purchasedTicket->end_date ?? null,
            'day'               => $purchasedTicket->day ?? null,
            'ticket_link'       => $purchasedTicket->ticket_link ?? null,
            'created_at'        => $purchasedTicket->created_at ?? null,
            'updated_at'        => $purchasedTicket->updated_at ?? null,
            'race_with_friend'  => $purchasedTicket->race_with_friend ?? null,
            'spectator'         => $purchasedTicket->spectator ?? null,
            'archive'           => 0,
            'created_by'        => $authUser->id,
            'buyer'             => null,
            'resale'            => false,
        ];

        $ticket = $this->ticket->create($purchasedTicketData);

        return response()->json([
            'message'   => 'ticket resaled successfully.',
            'NewTicket' => $ticket
        ]);
    }

    public function lockTicket($ticketId)
    {
        try {
            $ticket = $this->ticket->findOrFail($ticketId);
            $user = Auth::user();

            if ($ticket->locked_until && Carbon::now()->lt($ticket->locked_until)) {
                $now = Carbon::now();
                $remainingTimeInSeconds = $now->diffInSeconds($ticket->locked_until);
                $remainingMinutes = floor($remainingTimeInSeconds / 60);
                $remainingSeconds = $remainingTimeInSeconds % 60;

                return response()->json([
                    'error' => 'Purchaser making payment, please try again later.',
                    'is_locked' =>  true,
                    'locked_by_user' => $user->name,
                    'locked_by_user_id' => $user->id,
                    'locked_until' => $ticket->locked_until->toDateTimeString(),
                    'remaining_time_minutes' => $remainingMinutes,
                    'remaining_time_seconds' => $remainingSeconds,
                ], 200);
            }
            $ticket->locked_until = Carbon::now()->addMinutes(3);
            $ticket->locked_by_user_id = $user->id;
            $ticket->is_locked = true;
            $ticket->save();
            $lockedDuration = Carbon::now()->diff($ticket->locked_until);
            $lockedMinutes = $lockedDuration->i;
            $lockedSeconds = $lockedDuration->s;

            return response()->json([
                'message' => 'Ticket locked. Purchaser making payment',
                'is_locked' => true,
                'locked_by_user' => $user->name,
                'locked_by_user_id' => $ticket->id,
                'locked_until' => $ticket->locked_until->toDateTimeString(),
                'locked_time_minutes' => $lockedMinutes,
                'locked_time_seconds' => $lockedSeconds,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to lock ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkTicketStatus($ticketId)
    {
        try {
            $ticket = $this->ticket->findOrFail($ticketId);
            $user = Auth::user();

            $isLocked = 0;
            $remainingMinutes = 0;
            $remainingSeconds = 0;

            if ($ticket->locked_until && Carbon::now()->lt($ticket->locked_until) && $ticket->available_for == 'available') {
                $isLocked = 1;
                $remainingTimeInSeconds = Carbon::now()->diffInSeconds($ticket->locked_until);
                $remainingMinutes = floor($remainingTimeInSeconds / 60);
                $remainingSeconds = $remainingTimeInSeconds % 60;
            }
    
            return response()->json([
                'is_locked' => $isLocked,
                'locked_by_user' => $isLocked ? $user->name : null,
                'locked_by_user_id' => $isLocked ? $ticket->locked_by_user_id : null,
                'locked_until' => $ticket->locked_until ? $ticket->locked_until->toDateTimeString() : null,
                'remaining_time_minutes' => $remainingMinutes,
                'remaining_time_seconds' => $remainingSeconds,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to check ticket status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function soldTickets(Request $request) {

        try {
            $userId = Auth::user()->id;

            $ticketData = $this->ticket->select('tickets.*', 'events.id as event_id', 'events.name as event_name')
                                        ->with('event')
                                        ->where('available_for', 'sold')
                                        ->where('tickets.created_by', $userId)
                                        ->join('events', 'tickets.event_id', '=', 'events.id')
                                        ->orderBy('events.name', 'asc');

            if ($request->name) {
                $ticketData->where('tickets.name', 'like', '%' . $request->name . '%');
            }

            if ($request->category) {
                $ticketCategoryName   = $request->category;
                $ticketCategoryTypeId = $this->ticketCategoryType->whereIn('name', json_decode($ticketCategoryName))->pluck('id');

                $ticketData->whereHas('ticketCategory', function ($query) use ($ticketCategoryTypeId) {
                    $query->whereIn('ticket_category_type_id', json_decode($ticketCategoryTypeId));
                });
            }

            if ($request->event) {
                $eventName = $request->event;
                $ticketData->whereHas('event', function ($query) use ($eventName) {
                    $query->whereIn('name', json_decode($eventName));
                });
            }

            if($request->ticket_categories) {
                $categoryIds = $this->ticketCategory->whereIn('name', json_decode($request->ticket_categories))->pluck('id');
                $ticketData->whereIn('category_id', $categoryIds);
            }

            $ticketData = $ticketData->paginate(env('PAGE', 10));

            $formattedTickets = [];

            foreach ($ticketData as $ticket) {
                $ticketDetails = [
                    'id'                => $ticket->id,
                    'ticket_id'         => $ticket->ticket_id,
                    'event_id'          => $ticket->event_id,
                    'event_name'        => $ticket->event->name,
                    'event_address'     => $ticket->event->address,
                    'country'           => $ticket->event->country,
                    'continent'         => $ticket->event->continent,
                    'category_type'     => $ticket->ticketCategoryType->name,
                    'category_name'     => $ticket->ticketCategory->name,
                    'image'             => $ticket->image,
                    'available_for'     => $ticket->available_for,
                    'charity_ticket'    => $ticket->charity_ticket,
                    'photo_pack'        => $ticket->photo_pack,
                    'race_with_friend'  => $ticket->race_with_friend,
                    'spectator'         => $ticket->spectator,
                    'price'             => $ticket->price,
                    'change_fee'        => $ticket->change_fee,
                    'service'           => $ticket->service,
                    'total'             => $ticket->total,
                    'isverified'        => $ticket->isverified,
                    'currency_type'     => $ticket->currency_type,
                    'start_date'        => formatgetDate($ticket->start_date),
                    'end_date'          => formatgetDate($ticket->end_date),
                    'day'               => formatgetDay($ticket->day),
                    'created_at'        => $ticket->created_at,
                    'updated_at'        => $ticket->updated_at,
                    'created_by'        => $ticket->user->name ?? '',
                    'unpersonalised_ticket' => $ticket->unpersonalised_ticket,
                    'archived'             => $ticket->archive
                ];

                $formattedTickets[] = $ticketDetails;
            }

            return response()->json([
                'message'    => 'success',
                'ticketData' => $formattedTickets,
                'pagination' => [
                    'current_page' => $ticketData->currentPage(),
                    'total_pages'  => $ticketData->lastPage(),
                    'per_page'     => $ticketData->perPage(),
                    'total'        => $ticketData->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch ticket data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function unlockTicket(Request $request, $ticketId) {
        try {
            $ticket = $this->ticket->findOrFail($ticketId);
            if($ticket->available_for == 'sold') {
                return response()->json([
                    'message' => 'ticket is sold'
                ], 500);
            }
            $ticket->locked_until = null;
            $ticket->locked_by_user_id = null;
            $ticket->is_locked = false;
            $ticket->save();
            return response()->json([
                'message' => 'Ticket Unlocked',
                'ticket'  => $ticket->id,
            ], 200);
        } catch (\Exception $th) {
            return response()->json([
                'message' => 'Failed to fetch ticket data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function PurchasedTab(Request $request, $ticketId)
    {
        try {
            $user = Auth::user();
            $ticket = $this->ticket->find($ticketId);
            if(!$ticket) {
                return response()->json(['error' => 'Ticket not found'], 404);
            }
            return response()->json([
                'message' => 'succeess',
                'ticket'   => $ticket,
                'multiple_tickets' => $ticket->multiple_tickets,
            ],200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
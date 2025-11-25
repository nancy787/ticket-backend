<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketCategory;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon; 
use Illuminate\Support\Str;
use App\Models\Addon;
use App\Models\TicketCategoryType;
use App\Models\User;
use App\Models\SellTicket;
use Auth;
use App\Events\TicketAddedEvent;
use App\Mail\TicketPurchaseMail;
use Illuminate\Support\Facades\Mail;
use PDF;
use App\Traits\StoresEmailRecords;
use Illuminate\Support\Facades\DB;
use App\Events\SubscriptionNotificationEvent;
use App\Events\WislistSubscriptionNotificationEvent;
use App\Mail\TicketSoldMail;
use DataTables;
use App\Services\FreshChatService;
use App\Models\FreshChatMessage;
use Stripe\Transfer;
use Stripe\Stripe;
use Stripe\StripeClient;
use App\Models\Country;
use App\Models\TransferPayout;
use Validator;

class TicketController extends Controller
{
    use StoresEmailRecords;

    protected $ticketCategory;
    protected $event;
    protected $ticket;
    protected $ticketCategoryType;
    protected $user;
    protected $sellticket;
    protected $freshChatService;
    protected $freshchatMessage;

    public function __construct(TicketCategory $ticketCategory,
        Event $event,
        Ticket $ticket,
        Addon $addon,
        TicketCategoryType $ticketCategoryType,
        User $user,
        SellTicket $sellticket,
        FreshChatService $freshChatService,
        FreshChatMessage $freshchatMessage
    )
    {
        $this->ticketCategory = $ticketCategory;
        $this->event = $event;
        $this->ticket = $ticket;
        $this->addon = $addon;
        $this->ticketCategoryType = $ticketCategoryType;
        $this->user = $user;
        $this->sellticket = $sellticket;
        $this->freshChatService = $freshChatService;
        $this->freshchatMessage = $freshchatMessage;
        $this->stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
    }

    public function index(Request $request) {
        $userData = $this->user->select('id', 'name', 'email')->where('is_blocked', 0)->get();
        return view('tickets.index', compact('userData'));
    }

    public function getTicketData(Request $request)
    {
        $columns = [
            null,
            'tickets.ticket_id',
            null,
            'tickets.available_for',
            'tickets.updated_at',
            'events.name',
            'tickets.start_date',
            'ticket_categories.name',
            'tickets.day',
            'tickets.charity_ticket',
            'tickets.photo_pack',
            'tickets.race_with_friend',
            'tickets.spectator',
            'tickets.multiple_tickets',
            'tickets.unpersonalised_ticket',
            'tickets.total',
            'created_by_user.name',
            'buyers.name',
            'tickets.isverified',
            null,
        ];

        $ticketDetails = $this->ticket
                            ->select('tickets.*', 'events.name as city_name', 'ticket_categories.name as category_name',
                                'created_by_user.name as seller', 'buyers.name as buyer', 'buyers.id as buyer_id')
                            ->join('events', 'tickets.event_id', '=', 'events.id')
                            ->join('ticket_categories', 'tickets.category_id', '=', 'ticket_categories.id')
                            ->leftJoin('users as created_by_user', 'tickets.created_by', '=', 'created_by_user.id')
                            ->leftJoin('users as buyers', 'tickets.buyer', '=', 'buyers.id')
                            ->where('tickets.archive', 0);

        if ($request->has('order')) {
            $order = $request->input('order')[0];
            $columnIndex = $order['column'];
            $direction = $order['dir'];
            if ($columnIndex === null || !isset($columns[$columnIndex])) {
                            $ticketDetails->orderByRaw("CASE
                                                WHEN tickets.available_for = 'available' AND tickets.isverified = 0 THEN 0
                                                WHEN tickets.available_for = 'available' AND tickets.isverified = 1 THEN 1
                                                WHEN tickets.available_for = 'sold' THEN 2
                                                ELSE 3
                                                END ASC")
                                ->orderBy('id', 'DESC');
            } else {
                if (isset($columns[$columnIndex]) && $columns[$columnIndex] !== null) {
                    $ticketDetails->orderBy($columns[$columnIndex], $direction);
                    }
            }
        } else {
            $ticketDetails->orderByRaw("CASE
            WHEN tickets.available_for = 'available' AND tickets.isverified = 0 THEN 0
            WHEN tickets.available_for = 'available' AND tickets.isverified = 1 THEN 1
            WHEN tickets.available_for = 'sold' THEN 2
            ELSE 3
            END ASC");
        }

        if ($request->has('city') && $request->city != '') {
            $ticketDetails->whereHas('event', function($q) use ($request) {
                $q->where('events.name', $request->city);
            });
        }
        
        if ($request->has('division') && $request->division != '') {
            $ticketDetails->whereHas('ticketCategory', function($q) use ($request) {
                $q->where('name', $request->division);
            });
        }

        if ($request->has('day') && $request->day != '') {
            $formattedDay = strtoupper(substr($request->day, 0, 3));
            $ticketDetails->whereRaw("UPPER(DATE_FORMAT(day, '%a')) = ?", [$formattedDay]);
        }

        if ($request->has('status') && $request->status) {
            $ticketDetails->where('available_for', strtolower($request->status));
        }

        if ($request->has('eventId')) {
            $ticketDetails->where('event_id', $request->eventId)
                          ->where('tickets.available_for', 'available');
        }

        if ($request->has('ticket_status')) {
            $ticketDetails->where('tickets.available_for', $request->ticket_status);
        }

        if ($request->has('search') && $request->input('search.value') != '') {
            $searchValue = $request->input('search.value');
            $ticketDetails->where(function($q) use ($searchValue) {
                $q->where('ticket_id', 'like', "%{$searchValue}%")
                    ->orWhere('available_for', 'like', "%{$searchValue}%")
                    ->orWhere('events.name', 'like', "%{$searchValue}%")
                    ->orWhere('ticket_categories.name', 'like', "%{$searchValue}%")
                    ->orWhere('charity_ticket', 'like', "%{$searchValue}%")
                    ->orWhere('photo_pack', 'like', "%{$searchValue}%")
                    ->orWhere('race_with_friend', 'like', "%{$searchValue}%")
                    ->orWhere('spectator', 'like', "%{$searchValue}%")
                    ->orWhere('multiple_tickets', 'like', "%{$searchValue}%")
                    ->orWhere('unpersonalised_ticket', 'like', "%{$searchValue}%")
                    ->orWhere('total', 'like', "%{$searchValue}%")
                    ->orWhere('buyers.name', 'like', "%{$searchValue}%")
                    ->orWhere('created_by_user.name', 'like', "%{$searchValue}%")
                    ->orWhere('isverified', 'like', "%{$searchValue}%");
            });
        }

        if ($request->has('ticketId') && $request->has('eventId')) {
            $ticketDetails = $ticketDetails->where('event_id', $request->eventId)
                                           ->where('tickets.category_id', $request->ticketId)
                                           ->where('tickets.available_for', 'available');
        }

        if ($request->has('ticketId') && !$request->has('eventId')) {
            $ticketDetails  =  $this->ticket->where('tickets.id', $request->ticketId);
        }

        $start = $request->input('start', 0);
        $length = $request->input('length', 100);

        $totalRecords = (clone $ticketDetails)->count();

        $tickets = $ticketDetails->skip($start)->take($length)->get();
 
        $tickets->map(function ($ticket) {
            $startDate = Carbon::parse($ticket->start_date)->format('d');
            $endDate   = Carbon::parse($ticket->end_date)->format('d');
            $monthYear = Carbon::parse($ticket->start_date)->format('M, Y');

            $ticket->formatted_date = $startDate . '-' . $endDate . ' ' . $monthYear;

            return $ticket;
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'tickets'       => $tickets,
            'data' => $tickets->map(function ($ticket) {
                $status = $ticket->available_for;
                $color = match ($status) {
                    'pending' => 'orange',
                    'sold' => 'danger',
                    'available' => 'success',
                    'withdrawn' => 'warning',
                    default => 'secondary',
                };

                return [
                    'checkbox' => $ticket->available_for === 'sold'
                        ? '<input type="checkbox" name="bulkarchive[]" class="bulkarchive" value="' . $ticket->id . '">'
                        : '',
                    'ticket_id' => '<a href="' . route('ticket.edit', $ticket->id) . '">#' . $ticket->ticket_id . '</a>',
                    'sale/resell' => match ($ticket->available_for) {
                        'available' => $ticket->isverified
                            ? '<button type="button" class="btn btn-success btn-sm" onclick="openModal(' . $ticket->id . ', \'' . $ticket->available_for . '\')">Sell</button>'
                            : '',
                        'pending' => '<button type="button" class="btn btn-orange btn-sm" onclick="openModal(' . $ticket->id . ', \'' . $ticket->available_for . '\')">addBuyer</button>',
                        'sold' => $ticket->isverified
                            ? '<button type="button" class="btn btn-secondary btn-sm" onclick="openModal(' . $ticket->id . ', \'' . $ticket->available_for . '\')">Resell</button>'
                            : '',
                        default => '',
                    } ?? '', // Ensure a default value is returned.                    
                    'ticket_status' => '<div class="dropdown">
                    <button class="btn btn-' . $color . ' dropdown-toggle" type="button" id="statusDropdown' . $ticket->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        ' . ucfirst($status) . '
                    </button>
                    <div class="dropdown-menu" aria-labelledby="statusDropdown' . $ticket->id . '">
                        <!-- Form for Pending status -->
                        <form action="' . route('ticket.change-status', ['id' => $ticket->id]) . '" method="POST" style="display: none;" id="status-pending-form-' . $ticket->id . '">
                            ' . csrf_field() . '
                            <input type="hidden" name="status" value="pending">
                        </form>
                        <a class="dropdown-item status-change btn-orange" href="#" onclick="document.getElementById(\'status-pending-form-' . $ticket->id . '\').submit();">Pending</a>

                        ' . ($ticket->isverified
                            ? '<form action="' . route('ticket.change-status', ['id' => $ticket->id]) . '" method="POST" style="display: none;" id="status-sold-form-' . $ticket->id . '">
                                ' . csrf_field() . '
                                <input type="hidden" name="status" value="sold">
                              </form>
                              <a class="dropdown-item status-change bg-danger" href="#" onclick="document.getElementById(\'status-sold-form-' . $ticket->id . '\').submit();">Sold</a>'
                            : '') . '

                        <form action="' . route('ticket.change-status', ['id' => $ticket->id]) . '" method="POST" style="display: none;" id="status-available-form-' . $ticket->id . '">
                            ' . csrf_field() . '
                            <input type="hidden" name="status" value="available">
                        </form>
                        <a class="dropdown-item status-change bg-success" href="#" onclick="document.getElementById(\'status-available-form-' . $ticket->id . '\').submit();">Available</a>

                        <form action="' . route('ticket.change-status', ['id' => $ticket->id]) . '" method="POST" style="display: none;" id="status-withdrawn-form-' . $ticket->id . '">
                            ' . csrf_field() . '
                            <input type="hidden" name="status" value="withdrawn">
                        </form>
                        <a class="dropdown-item status-change bg-warning" href="#" onclick="document.getElementById(\'status-withdrawn-form-' . $ticket->id . '\').submit();">Withdrawn</a>
                    </div>
                </div>',

                    'sold_date' => $ticket->available_for == 'sold' ? formatSoldDate($ticket->updated_at) : 'No',
                    'city_name' => $ticket->event ? ucfirst($ticket->event->address) : '',
                    'dates' => $ticket->formatted_date,
                    'division' => ucfirst($ticket->ticketCategory->name),
                    'day' => formatDay($ticket->day),
                    'cha' => $ticket->charity_ticket ? 'Yes' : 'No',
                    'pho' => $ticket->photo_pack ?? 'No',
                    'fri' => $ticket->race_with_friend ?? 'No',
                    'spe' => $ticket->spectator ?? 'No',
                    'mt' => $ticket->multiple_tickets ? 'yes' :  'No',
                    'ut' => '<span style="color: ' . ($ticket->unpersonalised_ticket ? 'green' : 'red') . ';">'
                            . ($ticket->unpersonalised_ticket ? 'yes' : 'No')
                            . '</span>',
                    'dl' => '<span style="color: ' . ($ticket->dublicate_link ? 'green' : 'red') . ';">'
                    . ($ticket->dublicate_link ? 'yes' : 'No')
                    . '</span>',
                    'total' => $ticket->currency_type . ' ' . (format_price($ticket->total) ?? '0.00'),
                    'seller' => isset($ticket->user->name) 
                            ? '<a href="javascript:void(0);" onclick="userDetails(' . $ticket->user->id . ')">' . $ticket->user->name . '</a>' 
                            : '__',

                    'buyer' => isset($ticket->buyer)
                    ? '<a href="javascript:void(0);" onclick="userDetails(' . $ticket->buyer_id. ')">' . $ticket->buyer . '</a>' 
                    : '__',
                    'verified' =>  $ticket->isverified 
                        ? '<i class="nav-icon fas fa-check" style="color: green;"></i>'
                        : ($ticket->available_for == 'available' && !$ticket->isverified 
                            ? '<i class="nav-icon fas fa-times" style="color: red;"></i>'
                                . '<a href="javascript:void(0);" class="btn btn-sm btn-primary m-2" onclick="verifyTicket(' . $ticket->id . ');">verify</a>'
                                . '<form id="verify-ticket-' . $ticket->id . '" action="' . route('ticket.verify-ticket', $ticket->id) . '" method="POST">'
                                    . csrf_field()
                                    . method_field('POST')
                                . '</form>'
                            : '<i class="nav-icon fas fa-times" style="color: red;"></i>'),
                
                    'actions' => $this->generateTicketActions($ticket), 
                ];
            }),
        ]);
    }

    public function create(Request $request) 
    {
        $today = Carbon::now();
        $today = Carbon::parse($today)->format('Y-m-d');

        $eventDetails = $this->event->select('id', 'name')
                                    ->where('archived', 0)
                                    ->where('end_date', '>=', $today)
                                    ->orderBy('name', 'asc')
                                    ->get();

        $ticketCategoryTypes = $this->ticketCategoryType->all();
        $addons = $this->addon->first();
        $userData = $this->user->select('id', 'name', 'email')->where('is_blocked', 0)->get();

        return view('tickets.create', compact( 'eventDetails', 'addons', 'ticketCategoryTypes', 'userData'));
    }

    public function store(Request $request) 
    {
        $request->validate([
            'start_date'  => 'required',
            'end_date'    => 'required|after_or_equal:start_date',
            'day'         => 'required',
            'category_id' => 'required'
        ]);
        $ticketId = $this->generateTicketId($request->event_id) ?? null;
        $imageUrl = $this->ImageUpload($request);
        $authUser = Auth::user()->id;
        $ticketLinks  = $request->ticket_links ?? [];

        if (empty(array_filter($ticketLinks))) {
            $ticketLinks = [];
        }

        foreach ($ticketLinks as $link) {
            $existingTicket = $this->ticket->whereJsonContains('ticket_link', $link)->first();
            if ($existingTicket) {
                $duplicateFlag = true;
                break;
            }
        }

        $ticketData = [
            'ticket_id'        => $ticketId,
            'event_id'         => $request->event_id,
            'category_id'      => $request->category_id,
            'image'            => $imageUrl,
            'start_date'       => $request->start_date,
            'end_date'         => $request->end_date,
            'day'              => $request->day,
            'currency_type'    => $request->currency_type,
            'price'            => $request->price ?? null,
            'service'          => $request->service_charge ?? null,
            'change_fee'       => $request->change_fee ?? null,
            'total'            => $request->total ?? null,
            'available_for'    => $request->available_for,
            'ticket_link'      => json_encode($ticketLinks),
            'charity_ticket'   => $request->charity_ticket ?? 0,
            'photo_pack'       => $request->photo_pack ?? null,
            'isverified'       => $request->isverified ?? 0,
            'spectator'        => $request->spectator,
            'race_with_friend'  => $request->race_with_friend,
            'created_by'        => $request->seller ?? $authUser, //who creates the ticket will become the seller
            'multiple_tickets'  => $request->multiple_tickets ?? 0,
            'unpersonalised_ticket' => $request->unpersonalised_ticket ?? 0,
            'dublicate_link'   => $duplicateFlag ?? false
        ];

        $selectedTicketCategory = $this->ticketCategory->select('name')->where('id', $request->category_id)->first();
        $ticket = $this->ticket->create($ticketData);

        $createdTicket = $this->ticket->findOrFail($ticket->id);

        if($createdTicket->isverified && $createdTicket->available_for == 'available' ) {
            event(new TicketAddedEvent($createdTicket));
            event(new WislistSubscriptionNotificationEvent($createdTicket));
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket Created successfully with ticketId #' . $createdTicket->ticket_id
        ]);
    }

    public function edit($id) 
    {
        $today = Carbon::now();
        $today = Carbon::parse($today)->format('Y-m-d');

        $ticketData          = $this->ticket->with('event', 'ticketCategory.categoryType')->findOrFail($id);
        $selectedCategoryType = $ticketData->ticketCategory->categoryType->id ?? null;

        $ticketCategories    = $this->ticketCategory->where('ticket_category_type_id', $selectedCategoryType)->get();

        $eventDetails = $this->event->select('id', 'name')
                                    // ->where('active', 1)
                                    ->where('archived', 0)
                                    ->where('end_date', '>=', $today)
                                    ->orderBy('name', 'asc')
                                    ->get();

        $addons = $this->addon->first();
        $ticketCategoryTypes = $this->ticketCategoryType->all();
        $userData = $this->user->select('id', 'name', 'email')->where('is_blocked', 0)->get();
        $ticketLinks = $ticketData->ticket_link ? json_decode($ticketData->ticket_link, true) : [];

        return view('tickets.create', compact('ticketData',
                                             'ticketCategories',
                                             'eventDetails',
                                             'selectedCategoryType',
                                             'addons',
                                             'ticketCategoryTypes',
                                             'userData', 
                                             'ticketLinks'
                                            ));
    }

    public function update(Request $request, $id) 
    {
        $request->validate([
            'start_date'  => 'required',
            'end_date'    => 'required|after_or_equal:start_date',
            'day'         => 'required'
        ]);

        $ticket = $this->ticket->findOrFail($id);
        $imageUrl = $this->ImageUpload($request) ?? $ticket->image;
        $authUser = Auth::user()->id;
        $ticketLinks  = $request->ticket_links ?? [];
        $initialIsVerified = $ticket->isverified;

        if (empty(array_filter($ticketLinks))) {
            $ticketLinks = [];
        }

        foreach ($ticketLinks as $link) {
            $existingTicket = $this->ticket->whereJsonContains('ticket_link', $link)->where('id', '!=', $id)->first();
            if ($existingTicket) {
                $duplicateFlag = true;
                break;
            }
        }

        $ticketData = [
            'ticket_id'        => $ticket->ticket_id,
            'event_id'         => $request->event_id,
            'category_id'      => $request->category_id,
            'image'            => $imageUrl,
            'start_date'       => $request->start_date,
            'end_date'         => $request->end_date,
            'day'              => $request->day,
            'currency_type'    => $request->currency_type,
            'price'            => $request->price ?? null,
            'service'          => $request->service_charge ?? null,
            'change_fee'       => $request->change_fee ?? null,
            'total'            => $request->total ?? null,
            'available_for'    => $request->available_for,
            'ticket_link'      => json_encode($ticketLinks),
            'charity_ticket'   => $request->charity_ticket ?? 0,
            'photo_pack'       => $request->photo_pack ?? null,
            'isverified'       => $request->isverified ?? 0,
            'spectator'        => $request->spectator,
            'race_with_friend' => $request->race_with_friend,
            'created_by'       => $request->seller ?? $authUser, //who creates the ticket will become the seller
            'multiple_tickets'  => $request->multiple_tickets ?? 0,
            'unpersonalised_ticket' => $request->unpersonalised_ticket ?? 0,
            'dublicate_link'   => $duplicateFlag ?? false
        ];

        $ticket->update($ticketData);
        $updatedTicket = $this->ticket->findOrFail($id);

        if (!$initialIsVerified && $updatedTicket->isverified && $updatedTicket->available_for == 'available') {
            event(new TicketAddedEvent($updatedTicket));
            event(new WislistSubscriptionNotificationEvent($updatedTicket));
        }

        return redirect()->route('ticket.index')->with('success', 'Ticket Updated successfully.');
    }

    public function view($id) 
    {
        $ticketData          = $this->ticket->with('event', 'ticketCategory.categoryType')->findOrFail($id);

        $selectedCategoryType = $ticketData->ticketCategory->categoryType->id ?? null;

        $ticketCategories    = $this->ticketCategory->where('ticket_category_type_id', $selectedCategoryType)->get();
        $eventDetails        = $this->event->select('id', 'name')->where('active', 1)->get();
        $addons = $this->addon->first();
        $ticketCategoryTypes = $this->ticketCategoryType->all();
        $userData = $this->user->select('id', 'name', 'email')->where('is_blocked', 0)->get();
        return view('tickets.view', compact('ticketData', 'ticketCategories', 'eventDetails', 'selectedCategoryType', 'addons', 'ticketCategoryTypes', 'userData'));
    }

    public function destroy($id) 
    {
        $ticket = $this->ticket->with('event', 'ticketCategory', 'soldTicket')->findOrFail($id);
        if ($ticket->soldTicket()->exists()) {
            $ticket->soldTicket()->delete();
        }
        $ticket->delete();

        return redirect()->route('ticket.index')->with('message', 'Ticket deleted successfully.');
    }

    protected function ImageUpload(Request $request)
    {
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('eventImages', 'public');
            return Storage::url($imagePath);
        }

        return null;
    }

    public function verifyTicket($id) {
        $ticket = $this->ticket->find($id);

        if($ticket) {
            $ticket->update(['isverified' => 1]);
            event(new TicketAddedEvent($ticket));
            event(new WislistSubscriptionNotificationEvent($ticket));

            return redirect()->route('ticket.edit', ['id' => $ticket->id])
                             ->with('success', 'Ticket Verified successfully.');
        }

        return redirect()->route('ticket.index')->with('error', 'Ticket not found.');
    }

    public function addCategory(Request $request) 
    {
        $request->validate([
            'category_name'        => 'required',
            'category_description' => 'required',
        ]);

        $this->ticketCategory->create([
            'ticket_category_type_id'   => $request->category_type,
            'name'                      => $request->category_name,
            'description'               => $request->category_description,
        ]);

        return redirect()->route('ticket.create')->with('success', 'Category Added successfully.');
    }

    public function getCategories($categoryTypeId) {

        $categories = $this->ticketCategory->where('ticket_category_type_id', $categoryTypeId)->get();

        return response()->json($categories);
    }

    public function addTicketCategories(Request $request) {

        $ticketCategories = $this->ticketCategory->all();
        $ticketCategoryTypes = $this->ticketCategoryType->all();

        return view('tickets.ticket_catgories.add_category', compact('ticketCategories', 'ticketCategoryTypes'));

    }

    public function getTicketCategories(Request $request) {
    
        $ticketCategories = $this->ticketCategory
                            ->join('ticket_category_types', 'ticket_categories.ticket_category_type_id', '=', 'ticket_category_types.id')
                            ->orderBy('ticket_category_types.name')
                            ->orderBy('ticket_categories.name')
                            ->select('ticket_categories.*')
                            ->get();

       return view('tickets.ticket_catgories.index', compact('ticketCategories'));
    }

    public function changeStatus(Request $request, $id) {

        $ticket = $this->ticket->findOrFail($id);

        $ticket->update([
            'available_for' => $request->status,
        ]);

        return redirect()->route('ticket.index')->with('success', 'Status updated successfully.');

    }

    public function Addons()  {
        $addons = $this->addon->first();
        return view('tickets.addons.index', compact('addons'));
    } 

    public function addNewCategory(Request $request) {
        $ticketCategories = $this->ticketCategory->all();
        $ticketCategoryTypes = $this->ticketCategoryType->all();

        return view('tickets.ticket_catgories.add_category', compact('ticketCategories', 'ticketCategoryTypes'));
    }

    public function updateAddons(Request $request)
    {
        $this->addon->updateOrCreate([],
            [
            'photo_pack' => $request->photoPack,
            'race_with_friend' => $request->raceWithFriend,
            'spectator' => $request->spectator,
            'charity_ticket' => $request->charityTicket
            ]);
      
        return response()->json(['status' => 'success', 'message' => 'Addons updated successfully.']);
    }

    public function editCategory(Request $request,$catgeoryId) {

        $ticketCategoryData = $this->ticketCategory->findOrFail($catgeoryId);

        $ticketCategoryTypes = $this->ticketCategoryType->all();

        return view('tickets.ticket_catgories.add_category', compact('ticketCategoryData' , 'ticketCategoryTypes'));
    }

    public function updateCategory(Request $request,$catgeoryId) {

        $request->validate([
            'category_name'        => 'required',
            'category_description' => 'required',
        ]);

        $ticketCategoryData = $this->ticketCategory->findOrFail($catgeoryId);

        $ticketCategoryData->update([
            'ticket_category_type_id'    =>  $request->category_type,
            'name'                       =>  $request->category_name,
            'description'                =>  $request->category_description,
        ]);

        return redirect()->route('ticket.ticket_categories')->with('message', 'TicketCategory updated successfully.');
    }

    public function deleteCategory($id) {

        $this->ticketCategory->findOrFail($id)->delete();
        return redirect()->route('ticket.ticket_categories')->with('message', 'TicketCategory deleted successfully.');
    }

    public function archiveList(Request $request) {
        $ticketDetails = $this->ticket->select(
            'tickets.*',
            'ticket_categories.name as category_name',
            'events.name as event_name',
            'events.address as event_address',
            'seller.name as seller_name',
            'seller.is_stripe_connected as is_stripe_connected',
            'seller.id as seller_id',
            'stripe_connect_accounts.stripe_account_id as stripe_account_id',
            'buyer.name as buyer_name',
            'buyer.id as buyer_id',
           'tickets.updated_at as sold_date',
        )
        ->join('ticket_categories', 'tickets.category_id', '=', 'ticket_categories.id')
        ->join('events', 'tickets.event_id', '=', 'events.id')
        ->join('users as seller', 'tickets.created_by', '=', 'seller.id')
        ->leftJoin('users as buyer', 'tickets.buyer', '=', 'buyer.id')
        ->leftJoin('stripe_connect_accounts', 'seller.id', '=', 'stripe_connect_accounts.user_id')
        ->where('tickets.archive', 1)
        ->get()
        ->groupBy('tickets.id');

        return view('tickets.archive_list', compact('ticketDetails'));
    }

    public function getarchiveList(Request $request) {

        $columns = [
            'tickets.ticket_id',
            'stripe_connect_accounts.stripe_account_id',
            'tickets.seller_paid',
            'tickets.available_for',
            'tickets.sold_date',
            'events.name',
            'tickets.updated_at',
            'ticket_categories.name',
            'tickets.day',
            'tickets.charity_ticket',
            'tickets.photo_pack',
            'tickets.race_with_friend',
            'tickets.spectator',
            'tickets.multiple_tickets',
            'tickets.unpersonalised_ticket',
            'tickets.price',
            'seller.name',
            'buyer.name',
            'tickets.isverified',
            null,
        ];

        $ticketDetails = $this->ticket->select(
            'tickets.*',
            'ticket_categories.name as category_name',
            'events.name as event_name',
            'events.address as event_address',
            'seller.name as seller_name',
            'seller.is_stripe_connected as is_stripe_connected',
            'seller.id as seller_id',
            'stripe_connect_accounts.stripe_account_id as stripe_account_id',
            'buyer.name as buyer_name',
            'buyer.id as buyer_id',
            'tickets.sold_date as sold_date',
        )
        ->join('ticket_categories', 'tickets.category_id', '=', 'ticket_categories.id')
        ->join('events', 'tickets.event_id', '=', 'events.id')
        ->join('users as seller', 'tickets.created_by', '=', 'seller.id')
        ->leftJoin('users as buyer', 'tickets.buyer', '=', 'buyer.id')
        ->leftJoin('stripe_connect_accounts', 'seller.id', '=', 'stripe_connect_accounts.user_id')
        ->where('tickets.archive', 1)
        ->where('tickets.available_for', 'sold')
        ->groupBy('tickets.id');

        if (!$request->has('order')) {
            $ticketDetails->orderBy('tickets.sold_date', 'desc');
        } else {
            $order = $request->input('order')[0];
            $columnIndex = $order['column'];
            $direction = $order['dir'];
            if (isset($columns[$columnIndex])) {
                $ticketDetails->orderBy($columns[$columnIndex], $direction);
            }
        }

        if ($request->has('search') && $request->input('search.value') != '') {
            $searchValue = trim($request->input('search.value'));

            $ticketDetails->where(function ($q) use ($searchValue) {
                $q->where('tickets.ticket_id', 'like', "%{$searchValue}%")
                    ->orwhere('tickets.id', $searchValue)
                    ->orWhereRaw("CAST(seller.is_stripe_connected AS CHAR) LIKE ?", ["%{$searchValue}%"])
                    ->orWhere('available_for', 'like', "%{$searchValue}%")
                    ->orWhere('events.name', 'like', "%{$searchValue}%")
                    ->orWhere('ticket_categories.name', 'like', "%{$searchValue}%")
                    ->orWhereRaw("CAST(tickets.charity_ticket AS CHAR) LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("CAST(tickets.photo_pack AS CHAR) LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("CAST(tickets.race_with_friend AS CHAR) LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("CAST(tickets.spectator AS CHAR) LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("CAST(tickets.multiple_tickets AS CHAR) LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("CAST(tickets.unpersonalised_ticket AS CHAR) LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("CAST(tickets.price AS CHAR) LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("IFNULL(buyer.name, '') LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("IFNULL(seller.name, '') LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("CAST(tickets.isverified AS CHAR) LIKE ?", ["%{$searchValue}%"]);
            });
        }

        $start = $request->input('start', 0);
        $length = $request->input('length', 100);

        $totalRecords =$this->ticket->where('tickets.archive', 1)->where('tickets.available_for', 'sold')->count();
        $tickets = $ticketDetails->skip($start)->take($length)->get();

        $tickets->map(function ($ticket) {
            $startDate = $ticket->start_date ? Carbon::parse($ticket->start_date)->format('d') : 'N/A';
            $endDate = $ticket->end_date ? Carbon::parse($ticket->end_date)->format('d') : 'N/A';
            $monthYear = $ticket->start_date ? Carbon::parse($ticket->start_date)->format('M, Y') : 'N/A';

            $ticket->formatted_date = ($startDate !== 'N/A' && $endDate !== 'N/A')
                ? $startDate . '-' . $endDate . ' ' . $monthYear
                : 'N/A';

            return $ticket;
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'tickets' => $tickets,
            'data' => $tickets->map(function ($ticket) {
                return [
                    'ticket_id' => '<a href="' . route('ticket.view', $ticket->id) . '">#' . ($ticket->ticket_id ?? 'N/A') . '</a>',
                    'seller_account' => !empty($ticket->stripe_account_id)
                                    ? '<a href="' . env('STRIPE_DASHBOARD_URL') . $ticket->stripe_account_id . '/activity" target="_blank" style="color: green;">Yes</a>' 
                                    : '<span style="color: red;">No</span>',
                    'seller_paid' => (function ($ticket) {
                                    $status = $ticket->seller_paid ? 'yes' : 'no';
                                    $color = $ticket->seller_paid ? '#28a745' : '#dc3545';

                                    return '<select class="dropdown-status"
                                                    style="border: 2px solid ' . $color . '; padding: 5px; border-radius: 8px; color: #fff; background-color: ' . $color . ';"
                                                    onchange="updateSellerPaid(' . e($ticket->id) . ', this)">
                                                <option value="yes" ' . ($status === 'yes' ? 'selected' : '') . '>Yes</option>
                                                <option value="no" ' . ($status === 'no' ? 'selected' : '') . '>No</option>
                                            </select>';
                                })($ticket),
                    'ticket_status' => '<button class="btn btn-danger" type="button" disabled>' . e($ticket->available_for) . '</button>',
                    'sold_date' => $ticket->sold_date ? formatSoldDate($ticket->sold_date): 'N/A',
                    'event_name' => ucfirst($ticket->event_name ?? 'N/A'),
                    'formatted_date' => $ticket->formatted_date,
                    'category_name' => ucfirst($ticket->category_name ?? 'N/A'),
                    'Day' => formatDay($ticket->day),
                    'charity_ticket' => $ticket->charity_ticket ? 'Yes' : 'No',
                    'photo_pack' => $ticket->photo_pack ? 'Yes' : 'No',
                    'race_with_friend' => $ticket->race_with_friend ? 'Yes' : 'No',
                    'spectator' => $ticket->spectator ? 'Yes' : 'No',
                    'multiple_tickets' => $ticket->multiple_tickets ? 'Yes' : 'No',
                    'unpersonalised_ticket' => '<span style="color: ' . ($ticket->unpersonalised_ticket ? 'green' : 'red') . ';">'
                        . ($ticket->unpersonalised_ticket ? 'Yes' : 'No') . '</span>',
                    'price' => $ticket->currency_type . ' ' . (format_price($ticket->price) ?? '0.00'),
                    'seller_name' => !empty($ticket->seller_id)
                    ? '<a href="' . route('users.view', ['id' => $ticket->seller_id]) . '" target="_blank">' . e($ticket->seller_name) . '</a>'
                    : '__',
                    'buyer_name' => !empty($ticket->buyer_id)
                        ? '<a href="' . route('users.view', ['id' => $ticket->buyer_id]) . '" target="_blank">' . e($ticket->buyer_name) . '</a>'
                        : '__',
                    'isverified' => $ticket->isverified
                        ? '<i class="nav-icon fas fa-check" style="color: green;"></i>'
                        : '<i class="nav-icon fas fa-times" style="color: red;"></i>',
                    'actions' => '
                        <a href="javascript:void(0);" onclick="unArchive(' . e($ticket->id) . ');" class="btn btn-primary btn-sm">
                            <i class="fas fa-archive"></i>
                        </a>
                        <form id="unarchive-' . e($ticket->id) . '" action="' . route('ticket.unarchive', $ticket->id) . '" method="POST" style="display:none;">
                            ' . csrf_field() . method_field('POST') . '
                        </form>'
                        . (!$ticket->seller_paid && $ticket->user && $ticket->user->is_stripe_connected && $ticket->user->stripeConnect ? '
                        <a href="javascript:void(0);" onclick="showPayoutModal(' . htmlspecialchars(json_encode([
                            'id' => $ticket->id,
                            'currency' => $ticket->currency_type,
                            'price' => $ticket->price,
                            'seller_name' => $ticket->seller_name,
                            'stripe_account_id' => $ticket->user->stripeConnect->stripe_account_id
                        ]), ENT_QUOTES, 'UTF-8') . ')" class="btn btn-danger btn-sm">
                            Payouts
                        </a>' : ''),
                ];
            }),
        ]);
    }

    public function archive($id) {

        $archiveTickets = $this->ticket->with('soldTicket.user')->findorFail($id);

        if (!$archiveTickets->soldTicket) {
            return redirect()->route('ticket.index')->with('error', 'Sold ticket not found.');
        }

        $buyerId        = $archiveTickets->buyerName->id;
        $buyerName      = $archiveTickets->buyerName->name;
        $buyerEmail     = $archiveTickets->buyerName->email;
        $sellerEmail    = $archiveTickets->user->email;
        $sellerName     = $archiveTickets->user->name;
        $sellerId       = $archiveTickets->user->id;
        $authUser       = Auth::user();
        $senderId       = $authUser->id;
        $senderEmail    = $authUser->email;
        $isStripeConnected = $archiveTickets->user->is_stripe_connected;
        if(!$buyerEmail) {
            return redirect()->route('ticket.index')->with('error', 'Buyer email not found.');
        }
        if($archiveTickets) {
            $archiveTickets->update([
                    'archive' => 1,
                    'resale'  => 1
                ]);
        }
        $mailInstance = new TicketPurchaseMail($archiveTickets, $buyerName);
        $mailInstanceSold = new TicketSoldMail($archiveTickets, $sellerName, $isStripeConnected);

        Mail::to($buyerEmail)->send($mailInstance);
        Mail::to($sellerEmail)->send($mailInstanceSold);
        $this->storeEmailRecord($buyerId, env('MAIL_FROM_ADDRESS'), $buyerEmail, $mailInstance);
        $this->storeEmailRecord($sellerId, env('MAIL_FROM_ADDRESS'), $sellerEmail, $mailInstanceSold);

        if($buyerEmail && $archiveTickets->user->chat_enabled) {
            $message = $this->buyerMessage($buyerName, $archiveTickets);
            $this->sendFreshChatMessage($senderId, $buyerId, $senderEmail, $buyerEmail, $message, $archiveTickets->id);
        }

        if($sellerEmail && $archiveTickets->buyerName->chat_enabled) {
            $message = $this->sellerMessage($sellerName, $archiveTickets);
            $this->sendFreshChatMessage($senderId, $sellerId, $senderEmail, $sellerEmail, $message, $archiveTickets->id);
        }

        return redirect()->route('ticket.index')->with('success', 'Tickets archived successfully.');
    }

    public function Unarchive($id) {

        $archiveTickets = $this->ticket->findorFail($id);

        if($archiveTickets) {
            $archiveTickets->update(['archive' => 0]);
        }

        return redirect()->route('ticket.index')->with('success', 'Tickets unarchived  successfully.');
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

    public function sellTicket(Request $request) {

        $request->validate([
            'buyer_name' => 'required',
            'user_id'    => 'required|exists:users,id'
        ]);

        $ticketId = $request->ticket_id;

        $pdfs = [];

        if ($request->hasFile('pdf-files')) {
            foreach ($request->file('pdf-files') as $file) {
                $pdfPath = $this->pdfUpload($file);
                $pdfs[] = $pdfPath;
            }
        }

        $links = $request->input('links', []);

        if (empty(array_filter($links))) {
            $links = [];
        }

        $userId = $request->user_id;
        $ticket = $this->ticket->find($ticketId);

        $sellTicket = $this->sellticket->updateOrCreate([
            'ticket_id' => $ticketId,
        ],[
            'user_id'   => $userId,
            'event_id'  => $ticket->event_id,
            'pdf'       => json_encode($pdfs),
            'link'      => json_encode($links),
        ]);

        if($ticket && $request->status == 'pending') {
                $ticket->update([
                        'buyer' => $userId,
                        'ticket_link' => json_encode($links),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Buyer added Successfully',
                ], 200);
        };

        if($ticket) {
            $ticket->update(['available_for' => 'sold',
                              'buyer' => $userId,
                              'ticket_link' => json_encode($links),
                              'resale' => 1,
                              'sold_date' => Carbon::now()
                            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket Selled successfully',
        ], 200);

    }

    public function generatePDF($id, Request $request) {
        $ticket = $this->ticket->with('event','ticketCategory', 'ticketCategoryType', 'user', 'buyerName')->find($id);
        $eventName = $ticket->event->name;
        $ticketCategory = $ticket->ticketCategory->name;
        $categoryType = $ticket->ticketCategoryType->name;
        $ticketId = $ticket->ticket_id;
        $createdBy = $ticket->user->name ?? '' ;
        $buyer = $ticket->buyerName->name ?? '';

        if(!$ticket) {
            return redirect()->route('ticket.index')->with('success', 'Tickets not found');
        }

        $pdf = PDF::loadView('pdf.pdf_view', [
                            'ticket'         => $ticket->toArray(),
                            'eventName'      => ucFirst($eventName),
                            'ticketCategory' => ucFirst($ticketCategory),
                            'categoryType'   => ucFirst($categoryType),
                            'createdBy'      => ucFirst($createdBy),
                            'buyer'         => ucFirst($buyer)
                        ]);

        return $pdf->stream('ticket_'.$ticketId.'.pdf');

    }

    protected function pdfUpload($file)
    {
        if ($file) {
            $pdfName = time() . '_' . $file->getClientOriginalName();
            $pdfPath = $file->storeAs('pdfs', $pdfName, 'public');
            return Storage::url($pdfPath);
        }
        return null;
    }

    public function getSoldTicket($id)
    {
        $ticketDetail = $this->ticket->with('soldTicket')->find($id);

        if ($ticketDetail) {
            return response()->json([
                'ticket_id'   => $ticketDetail->id,
                'ticket_link' => $ticketDetail->ticket_link ? json_decode($ticketDetail->ticket_link) :  [],
                'buyerName'   => $ticketDetail->buyerName->name ?? '',
                'user_id'     => $ticketDetail->buyerName->id ?? '',
                'buyerEmail'  => $ticketDetail->buyerName->email ?? '',
                'pdf'         => $ticketDetail->soldTicket->pdf ?? '',
            ]);
        } else {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        return response()->json(['error' => 'Invalid request'], 400);
    }

    public function bulkArchive(Request $request)
    {
        $ticketIds = $request->ids;
        if (!$ticketIds) {
            return response()->json(['message' => 'No tickets selected'], 400);
        }

        foreach ($ticketIds as $id) {
            $archiveTickets = $this->ticket->with('soldTicket')->findorFail($id);
            $soldTicket     = $archiveTickets->soldTicket;

            $buyerId         = $archiveTickets->buyerName->id;
            $buyerName       = $archiveTickets->buyerName->name;
            $buyerEmail      = $archiveTickets->buyerName->email;
            $sellerEmail     = $archiveTickets->user->email;
            $sellerName      = $archiveTickets->user->name;
            $sellerId        = $archiveTickets->user->id;
            $authUser        = Auth::user();
            $senderId        = $authUser->id;
            $senderEmail     = $authUser->email;
            $isStripeConnected = $archiveTickets->user->is_stripe_connected;
            if ($archiveTickets) {
                $archiveTickets->update(['archive' => 1,
                                        'resale'   => 1
                                    ]);

                $mailInstance = new TicketPurchaseMail($archiveTickets, $buyerName);
                $mailInstanceSold = new TicketSoldMail($archiveTickets, $sellerName, $isStripeConnected);

                Mail::to($buyerEmail)->send($mailInstance);
                Mail::to($sellerEmail)->send($mailInstanceSold);

                $this->storeEmailRecord($buyerId, env('MAIL_FROM_ADDRESS'), $buyerEmail, $mailInstance);
                $this->storeEmailRecord($sellerId, env('MAIL_FROM_ADDRESS'), $sellerEmail, $mailInstanceSold);

                if($buyerEmail && $archiveTickets->user->chat_enabled) {
                    $message = $this->buyerMessage($buyerName, $archiveTickets);
                    $this->sendFreshChatMessage($senderId, $buyerId, $senderEmail, $buyerEmail, $message, $archiveTickets->id);
                }

                if($sellerEmail && $archiveTickets->buyerName->chat_enabled) {
                    $message = $this->sellerMessage($sellerName, $archiveTickets);
                    $this->sendFreshChatMessage($senderId, $sellerId, $senderEmail, $sellerEmail, $message, $archiveTickets->id);
                }
            }
        }

        return response()->json(['message' => 'Tickets archived successfully']);
    }
    public function removeBuyer($id) {
        $ticketDetail = $this->ticket->findorFail($id);
        if(!$ticketDetail) {
            return redirect()->route('ticket.index')->with('error', 'Buyer not found.');
        }
        $ticketDetail->update([
            'buyer'  => null
        ]);
        return redirect()->route('ticket.index')->with('success', 'buyer removed successfully.');
    }

    public function updateSellerPaid(Request $request) {
        $ticketId = $request->ticket_id;
        $ticket  = $this->ticket->findorFail($ticketId);
        if(!$ticket) {
            return response()->json([
                'message'  => 'Ticket not found'
            ], 404);
        }
        $ticket->seller_paid = $request->seller_paid === "yes" ? 1 : 0;
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'Seller Paid status updated successfully!',
            'seller_paid' => $ticket->seller_paid,
        ]);
    }

    public function filterTickets(Request $request) {
        $cities    = $this->ticket->join('events', 'events.id', '=', 'tickets.event_id')->select('events.name')->distinct()->orderBy('events.name', 'asc')->pluck('events.name');
        $divisions = $this->ticket->join('ticket_categories', 'ticket_categories.id', '=', 'tickets.category_id')->distinct()->orderBy('ticket_categories.name', 'asc')->pluck('ticket_categories.name'); 
        $days      = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $status    = ['Available', 'Pending', 'Withdrawn', 'Sold'];
        $query     = $this->ticket->with('event', 'ticketCategory');

        $ticketData = $query->get();

        return response()->json([
            'cities'     => $cities,
            'divisions'  => $divisions,
            'days'       => $days,
            'status'     => $status,
            'ticketData' => $ticketData
        ]);
    }

    public function generateTicketActions($ticket)
    {
        $actions = '<td>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton-' . $ticket->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    More
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton-' . $ticket->id . '">';

        if (isset($ticket->ticket_link) && $ticket->ticket_link) {
            $links = is_array($ticket->ticket_link) ? $ticket->ticket_link : json_decode($ticket->ticket_link, true);
            if (is_array($links)) {
                $actions .= '<div class="dropdown-submenu">
                    <a class="dropdown-item dropdown-toggle" href="#">Open Tickets</a>
                    <div class="dropdown-menu">';
                foreach ($links as $index => $link) {
                    $actions .= '<a class="dropdown-item ticket-link" href="' . $link . '" target="_blank" data-index="' . $index . '">
                        <i class="fa fa-link text-primary p-1"></i> Ticket ' . ($index + 1) . '
                    </a>';
                }
                $actions .= '</div></div>';
            } else {
                $actions .= '<p>No valid links found.</p>';
            }
        }

        $actions .= '<a class="dropdown-item" href="' . route('ticket.edit', $ticket->id) . '"><i class="fas fa-edit text-primary p-2"></i> Edit</a>
            <a class="dropdown-item" href="javascript:void(0);" onclick="deleteTicket(' . $ticket->id . ');"><i class="fas fa-trash text-danger p-2"></i> Delete</a>';

        if ($ticket->available_for == 'sold') {
            $actions .= '<a class="dropdown-item" href="javascript:void(0);" onclick="archiveTicket(' . $ticket->id . ');"><i class="fas fa-archive text-success p-2"></i> Archive</a>';
        }

        $actions .= '<a class="dropdown-item" target="_blank" href="' . route('ticket.generate-pdf', $ticket->id) . '"><i class="fas fa-file-pdf p-2"></i> Generate PDF</a>';

        $actions .= '<form id="delete-form-' . $ticket->id . '" action="' . route('ticket.delete', $ticket->id) . '" method="POST" style="display: none;" class="btn btn-primary">'
            . csrf_field()
            . method_field('POST')
            . '</form>';

        if ($ticket->buyer != null && $ticket->available_for == 'available') {
            $actions .= '<a class="dropdown-item" href="javascript:void(0);" onclick="removeBuyer(' . $ticket->id . ');"><i class="fas fa-trash text-success p-2"></i> Remove Buyer</a>';
        }

        if ($ticket->available_for == 'sold') {
            $actions .= '<form id="archive-' . $ticket->id . '" action="' . route('ticket.moveToarchive', $ticket->id) . '" method="POST" style="display: none;">'
                . csrf_field()
                . method_field('POST')
                . '</form>';
        }

        if ($ticket->buyer != null && $ticket->available_for == 'available') {
            $actions .= '<form id="remove-buyer-' . $ticket->id . '" action="' . route('ticket.remove-buyer', $ticket->id) . '" method="POST" style="display: none;">'
                . csrf_field()
                . method_field('POST')
                . '</form>';
        }


        $actions .= '</div></div></td>';

        return $actions;
    }

    public function deletedTickets(Request $request) {
        return view('tickets.delete-list');
    }

    public function getdeletedTickets(Request $request)
    {
        $columns = [
            null,
            'tickets.ticket_id',
            null,
            'tickets.available_for',
            'tickets.updated_at',
            'tickets.start_date',
            'tickets.day',
            'tickets.charity_ticket',
            'tickets.photo_pack',
            'tickets.race_with_friend',
            'tickets.spectator',
            'tickets.multiple_tickets',
            'tickets.unpersonalised_ticket',
            'tickets.total',
            'tickets.isverified',
            null,
            null,
        ];

        $ticketDetails = $this->ticket->onlyTrashed();

        if ($request->has('order')) {
            $ticketDetails->orderBy('tickets.deleted_at', 'desc');
        }

        if ($request->has('city') && $request->city != '') {
            $ticketDetails->whereHas('event', function($q) use ($request) {
                $q->where('events.name', $request->city);
            });
        }

        if ($request->has('division') && $request->division != '') {
            $ticketDetails->whereHas('ticketCategory', function($q) use ($request) {
                $q->where('name', $request->division);
            });
        }

        if ($request->has('day') && $request->day != '') {
            $formattedDay = strtoupper(substr($request->day, 0, 3));
            $ticketDetails->whereRaw("UPPER(DATE_FORMAT(day, '%a')) = ?", [$formattedDay]);
        }

        if ($request->has('eventId')) {
            $ticketDetails->where('event_id', $request->eventId)
                          ->where('tickets.available_for', 'available');
        }

        if ($request->has('ticket_status')) {
            $ticketDetails->where('tickets.available_for', $request->ticket_status);
        }

        if ($request->has('search') && $request->input('search.value') != '') {
            $searchValue = $request->input('search.value');
            $ticketDetails->where(function($q) use ($searchValue) {
                $q->where('ticket_id', 'like', "%{$searchValue}%")
                    ->orWhere('available_for', 'like', "%{$searchValue}%")
                    ->orWhere('charity_ticket', 'like', "%{$searchValue}%")
                    ->orWhere('photo_pack', 'like', "%{$searchValue}%")
                    ->orWhere('race_with_friend', 'like', "%{$searchValue}%")
                    ->orWhere('spectator', 'like', "%{$searchValue}%")
                    ->orWhere('multiple_tickets', 'like', "%{$searchValue}%")
                    ->orWhere('unpersonalised_ticket', 'like', "%{$searchValue}%")
                    ->orWhere('total', 'like', "%{$searchValue}%")
                    ->orWhere('isverified', 'like', "%{$searchValue}%");
            });
        }

        if ($request->has('ticketId') && $request->has('eventId')) {
            $ticketDetails = $ticketDetails->where('event_id', $request->eventId)
                                           ->where('tickets.category_id', $request->ticketId)
                                           ->where('tickets.available_for', 'available');
        }

        if ($request->has('ticketId') && !$request->has('eventId')) {
            $ticketDetails  =  $this->ticket->where('tickets.id', $request->ticketId);
        }

        $start = $request->input('start', 0);
        $length = $request->input('length', 100);

        $totalRecords = (clone $ticketDetails)->count();

        $tickets = $ticketDetails->skip($start)->take($length)->get();

        $tickets->map(function ($ticket) {
            $startDate = Carbon::parse($ticket->start_date)->format('d');
            $endDate   = Carbon::parse($ticket->end_date)->format('d');
            $monthYear = Carbon::parse($ticket->start_date)->format('M, Y');

            $ticket->formatted_date = $startDate . '-' . $endDate . ' ' . $monthYear;

            return $ticket;
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'tickets'       => $tickets,
            'data' => $tickets->map(function ($ticket) {
                $status = $ticket->available_for;
                $color = match ($status) {
                    'pending' => 'orange',
                    'sold' => 'danger',
                    'available' => 'success',
                    'withdrawn' => 'warning',
                    default => 'secondary',
                };
                return [
                    'ticket_id' => '<a href="#">#' . $ticket->ticket_id . '</a>',
                    'ticket_status' => '<div class="dropdown">
                    <button class="btn btn-' . $color . ' dropdown-toggle" type="button" id="statusDropdown' . $ticket->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" disabled>
                        ' . ucfirst($status) . '
                    </button>
                </div>',

                    'sold_date' => $ticket->available_for == 'sold' ? formatSoldDate($ticket->updated_at) : 'No',
                    'city_name' => $ticket->event ? ucfirst($ticket->event->address) : '',
                    'dates' => $ticket->formatted_date,
                    'division' => ucfirst($ticket->ticketCategory->name),
                    'day' => formatDay($ticket->day),
                    'cha' => $ticket->charity_ticket ? 'Yes' : 'No',
                    'pho' => $ticket->photo_pack ?? 'No',
                    'fri' => $ticket->race_with_friend ?? 'No',
                    'spe' => $ticket->spectator ?? 'No',
                    'mt' => $ticket->multiple_tickets ? 'yes' :  'No',
                    'ut' => '<span style="color: ' . ($ticket->unpersonalised_ticket ? 'green' : 'red') . ';">'
                            . ($ticket->unpersonalised_ticket ? 'yes' : 'No')
                            . '</span>',
                    'total' => $ticket->currency_type . ' ' . (format_price($ticket->total) ?? '0.00'),
                    'seller' => isset($ticket->user->name)
                            ? '<a href="javascript:void(0);" onclick="userDetails(' . $ticket->user->id . ')">' . $ticket->user->name . '</a>'
                            : '__',
                    'buyer' => isset($ticket->buyer)
                    ? '<a href="javascript:void(0);" onclick="userDetails(' . $ticket->buyerName->id. ')">' . $ticket->buyerName->name . '</a>'
                    : '__',
                    'verified' =>  $ticket->isverified
                        ? '<i class="nav-icon fas fa-check" style="color: green;"></i>'
                        : ($ticket->available_for == 'available' && !$ticket->isverified
                            ? '<i class="nav-icon fas fa-times" style="color: red;"></i>'
                            : '<i class="nav-icon fas fa-times" style="color: red;"></i>'),
                    'deleted_at' => formatDate($ticket->deleted_at),
                    'restore_tickets' => $ticket->deleted_at
                        ? '<button type="button" class="btn btn-warning btn-sm" onclick="confirmRestore(' . $ticket->id . ')">Restore</button>'
                        : '',
                ];
            }),
        ]);
    }

    public function sendFreshChatMessage($senderId, $id, $senderEmail, $email, $message, $ticketId) {
       $freshChatUserId =  $this->freshChatService->getUserFromFreshchat($email);
        if(!$freshChatUserId) {
            $user = $this->user->find($id);
            if($user) {
                $email  = $user->email;
                $name   = $user->name;
                if (strpos($name, ' ') !== false) {
                    [$firstName, $lastName] = explode(' ', $name, 2);
                } else {
                    $firstName = $name;
                    $lastName = '';
                }
                $phoneNumber = $user->phone_number;
                $location     = $user->address;
                $freshChatUserId  = $this->freshChatService->createUser($firstName, $lastName, $email, $phone, $location);
            }
        }
        $conversationId =  $this->freshChatService->getConversation($freshChatUserId);
        if(!$conversationId) {
           $sendMessage =  $this->freshChatService->CreateConversation($freshChatUserId, $message);
        }else{
            $sendMessage = $this->freshChatService->sendMessage($conversationId, $message, $freshChatUserId);
        }
        $this->storeFreshChatData($senderId, $id, $senderEmail, $email, $freshChatUserId, $conversationId, $message, $ticketId);
    }

    public function  storeFreshChatData($senderId, $id, $senderEmail, $email, $freshChatUserId, $conversationId, $message, $ticketId) {
        $storeFreshchatData  = $this->freshchatMessage->create([
            'sender_id'                   => $senderId,
            'receiver_id'                 => $id,
            'ticket_id'                   => $ticketId,
            'sender_user_email'           => $senderEmail,
            'receiver_user_email'         => $email,
            'freschat_user_id'            => $freshChatUserId,
            'freschat_conversation_id'    => $conversationId,
            'message'                     => $message,
            'message_send_from'           => 'archive'
        ]);
        return;
    }

    public function buyerMessage($buyerName, $archiveTickets) {
        $message = "Dear {$buyerName} ,
        You have bought #{$archiveTickets->ticket_id} for '{$archiveTickets->event->name} {$archiveTickets->ticketCategory->name}'.
        ";
        return $message;
    }

    public function sellerMessage($sellerName, $archiveTickets) {
        if($archiveTickets->user->is_stripe_connected) {
            $message = "Dear {$sellerName} ,
            Your ticket #{$archiveTickets->ticket_id} for '{$archiveTickets->event->name} {$archiveTickets->ticketCategory->name}' has now sold.";
        }else{
            $message = "Dear {$sellerName} ,
            Your ticket #{$archiveTickets->ticket_id} for '{$archiveTickets->event->name} {$archiveTickets->ticketCategory->name}' has now sold..";
        }
        return $message;
    }

    public function restoreTicket($ticketId) {
        $ticket = Ticket::withTrashed()->find($ticketId);

        if ($ticket && $ticket->trashed()) {
            $ticket->restore();
            return redirect()->back()->with('success', 'Ticket restored successfully.');
        }

        return redirect()->back()->with('error', 'Ticket not found or already restored.');
    }

    public function transferFunds(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'currency'          => 'required',
            'amount'            => 'required|numeric|min:1',
            'stripe_account_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $ticket = Ticket::findOrFail($id);

        if (!$ticket || $ticket->available_for !== 'sold') {
            return response()->json(['error' => 'Ticket not found or not sold.'], 404);
        }

        if (!$ticket->user->is_stripe_connected ||
            empty($ticket->user->stripeConnect->stripe_account_id)) {
            return response()->json(['error' => 'User is not connected to Stripe.'], 400);
        }

        try {
            $currency = Country::where('currency_sign', $request->currency)->value('currency_type');
            $transfer = $this->stripe->transfers->create([
                'amount'      => $request->amount * 100,
                'currency'    => strtolower($currency),
                'destination' => $request->stripe_account_id,
                'metadata'    => [
                    'ticket_id'   => $ticket->id,
                    'event_name'  => $ticket->event->name ?? 'Unknown Event',
                    'buyer_name'  => optional($ticket->buyerName)->name,
                    'seller_name' => $ticket->user->name,
                    'transfer_reason' => 'Transfer amount to seller'
                ],
            ]);

            $ticket->update(['seller_paid' => true]);

            TransferPayout::create([
                'seller_id'          => $ticket->user->id,
                'buyer_id'           => optional($ticket->buyerName)->id,
                'ticket_id'          => $ticket->id,
                'stripe_connected_id'=> $ticket->user->stripeConnect->id,
                'destination'        => $request->stripe_account_id,
                'currency'           => $currency,
                'amount'             => $request->amount,
                'status'             => 'succeeded',
                'details'            => json_encode($transfer),
            ]);

            return response()->json([
                'success'  => true,
                'message'  => 'Funds transferred successfully.',
                'transfer' => $transfer
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

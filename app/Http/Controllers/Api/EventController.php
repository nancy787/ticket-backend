<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\TicketCategory;
use Illuminate\Support\Facades\Auth;
use App\Models\Continent;
use App\Models\Country;
use App\Models\TicketCategoryType;
use Carbon\Carbon;

class EventController extends Controller
{
    protected $event;
    protected $ticketCategory;
    protected $continent;
    protected $country;
    protected $ticketCategoryType;

    public function __construct(Event $event, TicketCategory $ticketCategory, Continent $continent, Country $country, TicketCategoryType $ticketCategoryType ) {

        $this->event = $event;
        $this->ticketCategory = $ticketCategory;
        $this->continent = $continent;
        $this->country = $country;
        $this->ticketCategoryType = $ticketCategoryType;
    }

    public function index(Request $request)
    {
        try {
            $validContinents =  ["Africa", "Antarctica", "Asia", "Europe", "North America", "Oceania", "South America"];

            $eventData = $this->event->where('active', 1)
                                     ->where('archived', 0)
                                     ->with(['tickets' => function ($query) {
                                        $query->where('available_for', 'available');
                                        $query->where('isverified', 1);
                                     }])
                                     ->withCount(['tickets']);

            if($request->eventId) {
                    $eventData->where('id', $request->eventId);
            }

            if ($request->continents) {

                $getContinetsId = $this->continent->whereIn('name', json_decode($request->continents))->pluck('id')->toArray();

                if (!empty($getContinetsId)) {
                    $eventData->whereIn('continent_id', $getContinetsId);
                } else {
                    return response()->json(['message' => 'Invalid Continents'], 200);
                }
            }

            if ($request->country) {
                $eventData->whereIn('country', json_decode($request->country));
            }

            if ($request->city) {
                $eventData->whereIn('address', json_decode($request->city));
            }

            if ($request->state) {
                $eventData->where('state', 'like', '%' . $request->state . '%');
            }

            if ($request->start_date) {
                $formattedStartDate = date('Y-m-d', strtotime($request->start_date));
                $eventData->whereDate('start_date', $formattedStartDate);
            }

            if ($request->start_date && $request->end_date) {
                $formattedStartDate = date('Y-m-d', strtotime($request->start_date));
                $formattedEndDate = date('Y-m-d', strtotime($request->end_date));
                $eventData->whereDate('start_date', '>=', $formattedStartDate)
                          ->whereDate('end_date', '<=', $formattedEndDate);
            }

            if ($request->category) {
                $ticketCategoryName = $request->category;
                $ticketCategoryTypeId = $this->ticketCategoryType->whereIn('name', json_decode($ticketCategoryName))->pluck('id');
                $eventData->whereHas('tickets.ticketCategory', function($query) use ($ticketCategoryTypeId) {
                    $query->where('tickets.available_for', 'available');
                    $query->where('tickets.isverified', 1);
                    $query->whereIn('ticket_category_type_id', json_decode($ticketCategoryTypeId));
                });
            }

            // if ($request->subcategory) {
            //     $ticketSubCategory = $request->subcategory;
            //     $ticketSubCategoryId = $this->ticketCategory->whereIn('name', json_decode($ticketSubCategory))->pluck('id')->toArray();
            //     $eventData->whereHas('tickets.ticketCategory', function($query) use ($ticketSubCategoryId) {
            //         $query->where('tickets.available_for', 'available');
            //         $query->where('tickets.isverified', 1);
            //         $query->whereIn('tickets.category_id', $ticketSubCategoryId);
            //     });
            // }

            $eventData = $eventData->orderBy('start_date', 'asc')->paginate(10);

            $formattedEvents  = [];

            foreach ($eventData as $event) {
                        $eventDetails = [
                            'id'                => $event->id,
                            'name'              => $event->name,
                            'subtitle'          => $event->subtitle,
                            'description'       => $event->description,
                            'address'           => $event->address,
                            'city'              => $event->city,
                            'state'             => $event->state,
                            'currency'          => $event->currency,
                            'continent_id'      => $event->continent_id,
                            'country_id'        => $event->country_id,
                            'continent'         => $event->continent,
                            'country'           => $event->country,
                            'start_date'        => $event->start_date,
                            'end_date'          => $event->end_date, 
                            'race_information'  => $event->race_information, 
                            'image'             => $event->image, 
                            'open_for'          => $event->open_for, 
                            'active'            => $event->active, 
                            'city_code'         => $event->city_code, 
                            'total_tickets'     => $event->tickets->count(),
                            'created_at'        => formatDate($event->created_at),
                            'available_tickets' => [],
                            'unavailable_tickets' => []
                        ];

                foreach ($event->availableticketCategoryCounts as $ticketCategoryCount) {
                    $eventDetails['available_tickets'][] = [
                        'categoryId' => $ticketCategoryCount->ticketCategory->id,
                        'categoryName' => $ticketCategoryCount->ticketCategory->name,
                        'count' => $ticketCategoryCount['total']
                    ];
                }

                foreach ($event->unavailableticketCategoryCounts as $unavailableTickets) {
                    $eventDetails['unavailable_tickets'][] = [
                        'categoryId' => $unavailableTickets->ticketCategory->id,
                        'categoryName' => $unavailableTickets->ticketCategory->name,
                        'count' => $unavailableTickets['total']
                    ];
                }

                $formattedEvents[] = $eventDetails;
            }

            if (empty($formattedEvents)) {
                return response()->json([
                    'success' => 'No events found',
                    'events' => $formattedEvents,
                ], 200);
            }

            return response()->json([
                'events' => $formattedEvents,
                'pagination' => [
                    'current_page' => $eventData->currentPage(),
                    'total_pages' => $eventData->lastPage(),
                    'per_page' => $eventData->perPage(),
                    'total' => $eventData->total(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch events: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getEventAttribute(Request $request)
    {
        try {

            $perPage = $request->input('per_page', env('PAGE'));
            $eventData          = $this->event->get();
            $categories         = $this->ticketCategory->pluck('name')->unique()->values();
            $continents         = $this->continent->pluck('name')->all();
            $country            = $eventData->pluck('country')->unique()->sort()->values();
            $cities             = $eventData->pluck('address')->unique()->sort()->values();
            $ticketCategory     = $this->ticketCategoryType->pluck('name')->map(function ($name) {
                return ucfirst($name);
            })->all();

            $ticketSubCategory  = $this->ticketCategory->pluck('name')->all();

            $attributes = [
                'continents' => $continents,
                'countries'  => $country,
                'cities'     => $cities,
                'ticketcategories' => $ticketCategory,
                'ticketSubcategory' => $ticketSubCategory,
            ];

            return response()->json([
                'attributes' => $attributes
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch event attributes: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function searchEvent(Request $request)
    {
        try {
            $searchQuery = trim($request->input('searchQuery'));
    
            $eventData = $this->event->select('events.*', \DB::raw('COUNT(DISTINCT tickets.id) as total_tickets'))
                                        ->leftJoin('tickets', function ($join) {
                                            $join->on('events.id', '=', 'tickets.event_id')
                                                ->where('tickets.available_for', 'available')
                                                ->where('tickets.isverified', 1)
                                                ->whereNull('tickets.deleted_at');
                                        })
                                        ->where('events.active', 1)
                                        ->where('events.archived', 0)
                                        ->groupBy('events.id');

            if (!empty($searchQuery)) {
                $eventData->where(function ($query) use ($searchQuery) {
                    $query->where('events.name', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('events.country', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('events.address', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('events.continent', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('events.city', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('events.state', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('events.city_code', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('events.subtitle', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('events.description', 'LIKE', "%{$searchQuery}%");
                });
            }

            if ($request->has('category')) {
                $ticketCategoryName = $request->input('category');
                $ticketCategoryTypeId = $this->ticketCategoryType->where('name', $ticketCategoryName)->pluck('id');
                $eventData->whereHas('tickets.ticketCategory', function ($query) use ($ticketCategoryTypeId) {
                    $query->whereIn('ticket_category_type_id', json_decode($ticketCategoryTypeId));
                });
            }

            $eventData = $eventData->orderBy('events.id', 'desc')->paginate(100);

            $formattedEvents = [];

            foreach ($eventData as $event) {
                $availableTickets = \DB::table('tickets')
                    ->join('ticket_categories', 'tickets.category_id', '=', 'ticket_categories.id')
                    ->select('ticket_categories.name as categoryName', \DB::raw('COUNT(tickets.id) as count'))
                    ->where('tickets.event_id', $event->id)
                    ->where('tickets.available_for', 'available')
                    ->where('tickets.isverified', 1)
                    ->whereNull('tickets.deleted_at')
                    ->groupBy('ticket_categories.name')
                    ->get();

                if ($availableTickets->isEmpty()) {
                    continue;
                }

                $formattedEvents[] = [
                    'id'                => $event->id,
                    'name'              => $event->name,
                    'subtitle'          => $event->subtitle,
                    'description'       => $event->description,
                    'address'           => $event->address,
                    'city'              => $event->city,
                    'state'             => $event->state,
                    'currency'          => $event->currency,
                    'continent_id'      => $event->continent_id,
                    'country_id'        => $event->country_id,
                    'continent'         => $event->continent,
                    'country'           => $event->country,
                    'start_date'        => $event->start_date,
                    'end_date'          => $event->end_date,
                    'race_information'  => $event->race_information,
                    'image'             => $event->image,
                    'open_for'          => $event->open_for,
                    'active'            => $event->active,
                    'city_code'         => $event->city_code,
                    'total_tickets'     => $event->total_tickets,
                    'available_tickets' => $availableTickets,
                ];
            }

            if (empty($formattedEvents)) {
                return response()->json([
                    'events'  => [],
                    'message' => 'No events found',
                ], 200);
            }

            return response()->json([
                'events' => $formattedEvents,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch events: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function dropdownEvent(Request $request) {
        try {
            $today = Carbon::now();
            $today = Carbon::parse($today)->format('Y-m-d');

            $eventName = $this->event->select('id','name', 'start_date', 'end_date', 'active')
                                    // ->where('active', 1)
                                    ->where('end_date', '>=', $today)
                                    ->where('archived', 0)
                                    ->orderBy('name', 'asc')->get();

            return response()->json([
                'events' => $eventName,
            ]);

         } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch events: ' . $e->getMessage(),
            ], 500);
        }
    }
}

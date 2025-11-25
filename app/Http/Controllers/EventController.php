<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use App\Models\RaceInformation;
use App\Models\Continent;
use App\Models\Country;
use App\Models\TicketCategory;
use App\Models\TicketCategoryType;

class EventController extends Controller
{
    protected $event;
    protected $raceInformation;
    protected $continent;
    protected $country;
    protected $ticketCategory;
    protected $ticketCategoryType;

    public function __construct(Event $event, RaceInformation $raceInformation, Continent $continent, Country $country, TicketCategory $ticketCategory, TicketCategoryType $ticketCategoryType)
    {
        $this->event           = $event;
        $this->raceInformation = $raceInformation;
        $this->continent       = $continent;
        $this->country         = $country;
        $this->ticketCategory  = $ticketCategory;
        $this->ticketCategoryType = $ticketCategoryType;
    }

    public function index(Request $request) {

        $ticketCategoryType = $this->ticketCategoryType->with('ticketCategories')->get();

        $events = $this->event->with(['tickets.ticketCategory'])
                                ->where('archived', 0)
                                ->with(['tickets' => function ($query) {
                                    $query->where('available_for', 'available');
                                }])
                                ->withCount('tickets');

        if ($request->has('searchInput') && !empty($request->input('searchInput'))) {
            $searchInput = $request->input('searchInput');
            $events->where(function ($q) use ($searchInput) {
                $q->where('name', 'LIKE', '%' . $searchInput . '%')
                  ->orWhere('address', 'LIKE', '%' . $searchInput . '%');
            });
        }

        if ($request->category_type) {
            $categoryTypeId = $request->category_type;
            $events->whereHas('tickets.ticketCategory', function($query) use ($categoryTypeId) {
                $query->where('tickets.available_for', 'available');
                $query->where('ticket_category_type_id', $categoryTypeId);
            });
        }
        
        $eventData = $events->orderBy('start_date', 'asc')->paginate(9);

        return view('events.index', compact('eventData', 'ticketCategoryType'));
    }

    public function create() {

        $allContinent    = $this->continent->all();
        $countries       = $this->country->orderBy('name', 'asc')->get();
        $eventCategories = $this->ticketCategoryType->all();

        return view('events.create', compact('allContinent', 'countries', 'eventCategories'));
    }

    public function store(Request $request) {

         $request->validate([
            'name'              => 'required|string',
            'subtitle'          => 'required|string',
            'description'       => 'required',
            'address'           => 'required',
            'start_date'        => 'required',
            'end_date'          => 'required|after_or_equal:start_date',
            'image'             => 'required|image|mimes:jpeg,png',
            'open_for'          => 'required',
            'city_code'         => 'required|string',
        ]);

        $isActive  = $request->active;

        $imagePath = $request->file('image')->store('public/eventImages');
        $imageUrl  = Storage::url(str_replace('public/', '', $imagePath));

        $continent = $request->continent ?  $this->continent->find($request->continent) : null ;
        $country   = $request->country   ?  $this->country->find($request->country) : null ;

        $eventData =  $this->event->create([
                            'name'         => $request->name,
                            'subtitle'     => $request->subtitle,
                            'description'  => $request->description,
                            'address'      => $request->address,
                            'continent_id' => $request->continent ?? null,
                            'country_id'   => $request->country ?? null,
                            'start_date'   => $request->start_date,
                            'end_date'     => $request->end_date,
                            'image'        => $imageUrl,
                            'open_for'     => $request->open_for,
                            'active'       => $isActive,
                            'city_code'    => $request->city_code,
                            'currency'     => $request->currency,
                            'country'      => $country->name ?? null,
                            'continent'    => $continent->name ?? null,
                        ]);

        return redirect()->route('event.index')->with('success', 'Event created  successfully.');

    }

    public function edit($id) {

        $eventData = $this->event->findorFail($id);
        $allContinent    = $this->continent->all();
        $countries       = $this->country->orderBy('name', 'asc')->get();
        $eventCategories = $this->ticketCategoryType->all();

        return view('events.create', [
                                'eventData' => $eventData,
                                 'eventCategories' => $eventCategories,
                                 'allContinent' => $allContinent,
                                 'countries' => $countries
                                ]);
    }

    public function update(Request $request, $id) {

        $request->validate([
            'name'              => 'required|string',
            'subtitle'          => 'required|string',
            'description'       => 'required',
            'address'           => 'required',
            'country'           => 'required',
            'continent'         => 'required',
            'start_date'        => 'required',
            'end_date'          => 'required',
            'open_for'          => 'required',
            'city_code'         => 'required|string'
        ]);

        $isActive  = $request->active;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('eventImages', 'public');
            $imageUrl = Storage::url($imagePath);
        }

        $eventData = $this->event->findOrFail($id);
        $continent = $request->continent ?  $this->continent->find($request->continent) : null ;
        $country   = $request->country   ?  $this->country->find($request->country) : null ;

     $updateEvent =  $eventData->update([
                            'name'              => $request->name,
                            'subtitle'          => $request->subtitle,
                            'description'       => $request->description,
                            'address'           => $request->address,
                            'city'              => $request->city,
                            'state'             => $request->state,
                            'start_date'        => $request->start_date,
                            'end_date'          => $request->end_date,
                            'image'             => $imageUrl ?? $eventData->image,
                            'open_for'          => $request->open_for,
                            'active'            => $isActive,
                            'city_code'         => $request->city_code,
                            'currency'          => $request->currency,
                            'continent_id'      => $request->continent ?? null,
                            'country_id'        => $request->country ?? null,
                            'country'           => $country->name ?? null,
                            'continent'         => $continent->name ?? null,
                        ]);

        if ($updateEvent && $isActive) {
            $tickets = $eventData->tickets()->where('available_for', 'pending')->get();
            foreach ($tickets as $ticket) {
                $ticket->update([
                    'available_for' => 'available',
                ]);
            }
        }

        return redirect()->route('event.index')->with('success', 'Event updated successfully.');

    }

    public function view($id) {
        $eventData = $this->event->findorFail($id);
        return view('events.view', ['eventData' => $eventData]);
    }

    public function destroy(Request $request, $id) {
        $event = $this->event->with(['tickets.soldTicket', 'wishlistSubscriptions', 'advancewishlistSubscriptions'])->findOrFail($id);
        foreach ($event->tickets as $ticket) {
            $ticket->soldTicket()->delete();
        }

        $event->tickets()->delete();
        $event->wishlistSubscriptions()->delete();
        $event->advancewishlistSubscriptions()->delete();
        $event->delete();

        return redirect()->route('event.index')->with('message', 'Event deleted successfully.');
    }

    public function addRaceInformation(Request $request, $id) {
        
        $eventData = $this->event->findorFail($id);
        return view('events.race_information', ['eventData' => $eventData]);
    }

    public function storeRaceInformation(Request $request, $id) {

        $eventData = $this->event->findOrFail($id);
        $existingRaceInformation = $eventData->raceInformation()->get();
    
        $titles = $request->titles ?? [];
        $values = $request->values ?? [];

        $updatedOrCreatedIds = [];

        foreach ($titles as $index => $title) {
            if (!empty($title) && !empty($values[$index])) {
                $raceInformation = $existingRaceInformation->where('id', $index)->first();

                $raceInformation = $this->raceInformation->updateOrCreate(
                    ['id' => $raceInformation ? $raceInformation->id : null],
                    [
                        'event_id' => $eventData->id,
                        'title' => $title,
                        'value' => $values[$index]
                    ]
                );
                $updatedOrCreatedIds[] = $raceInformation->id;
            }
        }

        $existingIds = $existingRaceInformation->pluck('id')->toArray();
        $idsToRemove = array_diff($existingIds, $updatedOrCreatedIds);

        $this->raceInformation->destroy($idsToRemove);

        $updated = $eventData->raceInformation()->where('updated_at', '>', $eventData->updated_at)->exists();
        $message = $updated ? 'Race Information Updated successfully' : 'Race Information Added successfully';

        return redirect()->route('event.index')->with('success', $message);
    }

    public function archive(Request $request) {

        $eventData = $this->event->where('end_date', '<=', Carbon::now())
                            ->Orwhere('archived', true);

        if ($request->has('searchInput') && !empty($request->input('searchInput'))) {
            $searchInput = $request->input('searchInput');
            $eventData->where(function ($q) use ($searchInput) {
                $q->where('name', 'LIKE', '%' . $searchInput . '%')
                    ->orWhere('address', 'LIKE', '%' . $searchInput . '%');
            });
        }

        $eventData = $eventData->get();

        return view('events.archive_list', compact('eventData'));

    }

    public function moveToArchive($id) {
        $archiveEvents = $this->event->findOrFail($id);

        if ($archiveEvents) {
            $archiveEvents->update(['archived' => true]);
            $event = $this->event->with([
                'tickets.soldTicket',
                'wishlistSubscriptions',
                'advancewishlistSubscriptions'
            ])->findOrFail($id);

            foreach ($event->tickets as $ticket) {
                if ($ticket->available_for != 'sold') {
                    $ticket->delete();
                }
            }
            $event->wishlistSubscriptions()->delete();
            $event->advancewishlistSubscriptions()->delete();
        }

        $eventData = $this->event->where('end_date', '<=', Carbon::now())
                            ->orWhere('archived', true)
                            ->get();

        return view('events.archive_list', compact('eventData'));
    }

    public function getCountries($continentId) {
    
        $countries = $this->country->select('id', 'name', 'currency_sign')
                        ->where('continent_id', $continentId)
                        ->orderBy('name', 'asc')
                        ->get();

        return response()->json($countries);
    }
    
    public function getCountryCurrency($countryId) {
        $countryCurrency = $this->country->select('currency_sign')->where('id', $countryId)->get();
        return response()->json($countryCurrency);
    }

    public function getEventDetails($eventId) {

        if($eventId) {
            $eventDetails = $this->event->where('id', $eventId)->first(['start_date', 'end_date', 'country', 'continent_id', 'country_id', 'active']);
            $countryId = $eventDetails->country_id ?? '';
            $currencySign = '';

            if ($countryId) {
                $countryCurrency = $this->country->where('id', $countryId)->first();
                $currencySign = $countryCurrency->currency_sign;
            }

            return response()->json([
                'eventDetails' => $eventDetails,
                'currencySign' => $currencySign,
             ], 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function Unarchive($eventId) {

        $archiveEvents = $this->event->findorFail($eventId);

        if($archiveEvents) {
            $archiveEvents->update(['archived' => false]);
            $archiveEvents->tickets()->withTrashed()->restore();
        }

        return redirect()->route('event.index')->with('success', 'Event unarchived  successfully.');

    }

    public function inactiveEvents(Request $request) {
        try {
            $activeEvents = $this->event->where('active', 1)->get();
            foreach($activeEvents as $activeEvent) {
                $activeEvent->update(['active' => 0]);
            }
            return response()->json([
                'success'  => 'Events inactive successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to inactive events:' . $e->getMessage()
            ], 500);
        }
    }

    public function activateEvents(Request $request) {
        try {
            $activeEvents = $this->event->where('active', 0)->get();
            foreach($activeEvents as $activeEvent) {
                $activeEvent->update(['active' => 1]);
            }
            return response()->json([
                'success'  => 'Events activated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to activate events:' . $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wishlist;
use App\Models\Event;
use App\Models\Ticket;
use Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Events\EventAddedToWishlist;
use App\Events\RemoveFromWishlistEvent;

class WishlistController extends Controller
{
    protected $wishlist;
    protected $event;
    protected $ticket;

    public function __construct(Wishlist $wishlist, Event $event, Ticket $ticket) {

        $this->wishlist  = $wishlist;
        $this->event     = $event;
        $this->ticket    = $ticket;
    }

    public function addToWishlist(Request $request)
    {
        try {
            $authUser = Auth::user();

            if (!$authUser->is_premium && !$authUser->is_free_subcription) {
                return response()->json([
                    'success' => false,
                    'message'  => 'You are not a premium user. Please take a premium subscription for wishlist!'
                ], 200);
            }

            $userId = $authUser->id;
            $eventId = $request->event_id;
            $categoryIds = json_decode($request->category_id);
            $event = $this->event->find($eventId);

            if($request->category_id){
                $categoryIds = json_decode($request->category_id, true);

                if (is_null($categoryIds)) {
                    $categoryIds = explode(',', $request->category_id);
                }

                $categoryIds = is_array($categoryIds) ? $categoryIds : [$categoryIds];
            } else {
                $categoryIds = $this->ticket->where('tickets.available_for', 'available')
                                            ->where('tickets.event_id', $eventId)
                                            ->pluck('category_id')
                                            ->unique()
                                            ->toArray();
            }

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found.'
                ], 200);
            }

            $wishlist = [];

            $eventExists = $this->wishlist->where('user_id', $userId)->where('event_id', $eventId)->get();

            if($categoryIds) {
                $categories = [];
                foreach ($categoryIds as $categoryId) {
                    $eventExists = $eventExists->where('category_id', $categoryId);

                    if ($eventExists->isNotEmpty()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Event already added to wishlist',
                        ], 200);
                    }

                    $wishlist[] = $this->wishlist->create([
                        'user_id' => $userId,
                        'event_id' => $eventId,
                        'category_id' => $categoryId
                    ]);
                }

                $categories[] = $categoryId;
            }

            $categories = $this->wishlist->pluck('category_id')->toArray();
            event(new EventAddedToWishlist($eventId, $authUser, $categories));

            return response()->json([
                'success' => true,
                'message' => 'Events added to wishlist successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add to wishlist: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMyWishlist(Request $request) {
        try {
                $authUser = Auth::user();

                if (!$authUser->is_premium && !$authUser->is_free_subcription) {
                    return response()->json([
                        'success' => false,
                        'message'  => 'You are not a premium user. Please take a premium subscription for wishlist!'
                    ], 200);
                }

                $wishlists = $this->wishlist->with(['event.tickets' => function ($query) {
                                                $query->where('available_for', 'available');
                                                $query->with('ticketCategory');
                                            }])->where('user_id', $authUser->id)->get();

                $categoryIds = $this->wishlist->where('user_id', $authUser->id)->pluck('category_id');

                $ticketCategories = DB::table('ticket_categories')->select('id', 'name')->whereIn('id', $categoryIds)->get()->keyBy('id');

                $eventsWithCategories = $wishlists->groupBy('event_id')->map(function($eventWishlists) use ($ticketCategories) {
                    $eventsData = $eventWishlists->first();
                    $totalAvailableTickets = 0;
                    $eventDetails = [
                        'id'                => $eventsData->event->id,
                        'name'              => $eventsData->event->name,
                        'subtitle'          => $eventsData->event->subtitle,
                        'description'       => $eventsData->event->description,
                        'address'           => $eventsData->event->address,
                        'city'              => $eventsData->event->city,
                        'state'             => $eventsData->event->state,
                        'currency'          => $eventsData->event->currency,
                        'continent_id'      => $eventsData->event->continent_id,
                        'country_id'        => $eventsData->event->country_id,
                        'continent'         => $eventsData->event->continent,
                        'country'           => $eventsData->event->country,
                        'start_date'        => $eventsData->event->start_date,
                        'end_date'          => $eventsData->event->end_date,
                        'race_information'  => $eventsData->event->race_information,
                        'image'             => $eventsData->event->image,
                        'open_for'          => $eventsData->event->open_for,
                        'active'            => $eventsData->event->active,
                        'city_code'         => $eventsData->event->city_code,
                        'total_tickets'     => $totalAvailableTickets,
                        'available_tickets' => [],
                    ];

                    foreach ($eventWishlists as $wishlist) {
                        if ($wishlist->category_id) {
                            $category = $ticketCategories->get($wishlist->category_id);
                            $availableTickets = $eventsData->event->tickets->where('category_id', $wishlist->category_id)->count();

                            $eventDetails['available_tickets'][] = [
                                'categoryId'   => $category->id,
                                'categoryName' => $category->name,
                                'count'        => $availableTickets,
                            ];
                        }

                        $totalAvailableTickets += $availableTickets;
                    }

                    $eventDetails['total_tickets'] = $totalAvailableTickets;

                    return $eventDetails;
                })->values();

                return response()->json([
                 'wishlists' => $eventsWithCategories
                ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add to wishlist: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeFromWishlist($eventId, Request $request) {

        try {
            $authUser = Auth::user();

            if (!$authUser->is_premium && !$authUser->is_free_subcription) {
                return response()->json([
                    'success' => false,
                    'message'  => 'You are not a premium user. Please take a premium subscription for wishlist!'
                ], 200);
            }

            $categoryIds = json_decode($request->category_id);
            $mywishList = $this->wishlist->where('user_id', $authUser->id);

            if($eventId) {

                $mywishList = $mywishList->where('event_id', $eventId);

                if(!$mywishList->exists())     {
                    return response()->json([
                        'success' => true,
                        'message' => 'Event is not available in the wishlist',
                        'wishlist' => []
                    ], 200);
                }
            }

            if ($eventId && $categoryIds) {
                $categories = [];
                foreach ($categoryIds as $categoryId) {
                    if (!$categoryId) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Category ID is not valid!',
                        ]);
                    }
                    $categoryExists = $this->wishlist->where('user_id', $authUser->id)
                                                     ->where('event_id', $eventId)
                                                     ->where('category_id', $categoryId)
                                                     ->exists();
                    if (!$categoryExists) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Category ID ' . $categoryId . ' is not available in the wishlist!',
                        ]);
                    }

                    $categories[] = $categoryId;
                }
                $deletedRows = $this->wishlist->where('user_id', $authUser->id)
                                              ->where('event_id', $eventId)
                                              ->whereIn('category_id', $categoryIds)
                                              ->delete();

                event(new RemoveFromWishlistEvent($eventId, $authUser, $categories));

                return response()->json([
                    'success' => true,
                    'message' => 'Selected categories removed from the wishlist',
                ]);
            }

            $categories = $mywishList->pluck('category_id')->toArray();
            $mywishList->delete();
            event(new RemoveFromWishlistEvent($eventId, $authUser, $categories));

            return response()->json([
                'success' => true,
                'wishlist' => [],
                'message' => 'Removed From the Wishlist',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to Remove from wishlist: ' . $e->getMessage()
            ], 500);
        }
    }
}

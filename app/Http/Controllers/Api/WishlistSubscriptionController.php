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
use App\Models\User;
use App\Models\WishlistSubscription;
use Carbon\Carbon;

class WishlistSubscriptionController extends Controller
{
    protected $event;
    protected $ticketCategory;
    protected $continent;
    protected $country;
    protected $ticketCategoryType;

    public function __construct(Event $event, TicketCategory $ticketCategory, Continent $continent, Country $country, TicketCategoryType $ticketCategoryType, WishlistSubscription  $wishlistSubcription ) {

        $this->event = $event;
        $this->ticketCategory = $ticketCategory;
        $this->continent = $continent;
        $this->country = $country;
        $this->ticketCategoryType = $ticketCategoryType;
        $this->wishlistSubcription = $wishlistSubcription;
    }

    public function getSubscriptionAttributes(Request $request) {
        try {

                $today = Carbon::now();
                $today = Carbon::parse($today)->format('Y-m-d');

                $eventData          = $this->event->select('id', 'name as event_name')->where('end_date', '>=', $today)->where('archived', 0)->orderBy('name', 'asc')->get();
                $continents         = $this->continent->select('id', 'name as continent_name')->get();
                $countries          = $this->event->select('country_id', 'country as country_name')->distinct()->orderBy('country_name', 'asc')->get();
                $ticketSubCategory  = $this->ticketCategory->select('id', 'name as category_name')->orderBy('category_name', 'asc')->get();
                $allSubCategory     = $this->ticketCategory->select('id', 'name as all_category_name')->orderBy('all_category_name', 'asc')->get();

                $attributes = [
                    'continents'    => $continents,
                    'countries'     => $countries,
                    'events'        => $eventData,
                    'categories'    => $ticketSubCategory
                ];

                return response()->json([
                    'attributes' => $attributes,
                    'Allcategory' => $allSubCategory
                ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch subscription attributes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSubscribedWislist(Request $request)
    {
        try {
            $authUser = Auth::user();

            if (!$authUser->is_premium && !$authUser->is_free_subcription) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a premium user. Please take a premium subscription for wishlist!'
                ], 200);
            }

            $subscribedWishlist = $this->wishlistSubcription->where('user_id', $authUser->id)->get();

            $categoryIds = $subscribedWishlist->whereNull('country_id')->whereNull('continent_id')->whereNull('event_id')->pluck('category_ids')->map(function($item) {
                return json_decode($item, true);
            })->flatten()->unique()->filter()->toArray();

            $wishlistWithDetails = $subscribedWishlist->map(function ($subscription) {
                $categoryIds = json_decode($subscription->category_ids, true);
                $categories = $categoryIds
                    ? $this->ticketCategory->whereIn('id', $categoryIds)->get()->map(function ($category) {
                        return [
                            'name' => $category->name,
                            'id' => $category->id
                        ];
                    })->toArray()
                    : [];

                $eventName = optional($subscription->event)->name;
                $countryName = optional($subscription->country)->name;
                $continentName = optional($subscription->continent)->name;

                return [
                    'userName' => $subscription->user->name,
                    'user_id' => $subscription->user->id,
                    'country_id' => $subscription->country_id ? $subscription->country->id : null,
                    'country_name' => $countryName,
                    'continent_id' => $subscription->continent_id ? $subscription->continent->id : null,
                    'continent_name' => $continentName,
                    'event_id' =>  optional($subscription->event)->id,
                    'event_name' => $eventName,
                    'categories' => $categories,
                ];
            });

            $allSubCategory = $this->ticketCategory->whereIn('id', $categoryIds)->get(['id', 'name as category_name']);

            return response()->json([
                'subscribedWishlist' => $wishlistWithDetails,
                'allCategories' => $allSubCategory,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch subscription attributes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function subscribeToWishlist(Request $request) {
        try {
            $categoryIds    = json_decode($request->category_ids);
            $eventIds       = json_decode($request->event_ids);
            $countryIds     = json_decode($request->country_ids);
            $continentIds   = json_decode($request->continent_ids);
            $user           = Auth::user();
            $userId         = $user->id;

            if (!$user->is_premium && !$user->is_free_subcription) {
                return response()->json([
                    'success' => false,
                    'message'  => 'You are not a premium user. Please take a premium subscription for wishlist!'
                ], 200);
            }

            if($eventIds) {
                $existingEventsSubscriptions = $this->wishlistSubcription->whereIn('event_id', $eventIds)->pluck('event_id')->toArray();
                $newEventSubscriptions = array_diff($eventIds, $existingEventsSubscriptions);
                
                foreach ($eventIds as $key => $eventId) {
                    $categoriesForEvent = $request->input("event_categories_$eventId");
                    $categoriesForEvent = $categoriesForEvent ?  json_decode($categoriesForEvent, true) : []; 

                    $wishlistSubcriptions = $this->wishlistSubcription->updateOrCreate(
                        [
                            'user_id' => $userId,
                            'event_id' => $eventId
                        ],
                        [
                            'category_ids' => json_encode($categoriesForEvent)
                        ]
                    );
                }

            }

            if($countryIds) {
                $existingCountrySubscriptions = $this->wishlistSubcription->whereIn('country_id', $countryIds)->pluck('country_id')->toArray();
                $newEventCountrySubscriptions = array_diff($countryIds, $existingCountrySubscriptions);

                foreach ($countryIds as $key => $countryId) {
                    $categoriesForCountry = $request->input("country_categories_$countryId");
                    $categoriesForCountry = $categoriesForCountry ?  json_decode($categoriesForCountry, true) : []; 
                    $wishlistSubcriptions = $this->wishlistSubcription->updateOrCreate(
                        [
                            'user_id' => $userId,
                            'country_id' => $countryId
                        ],
                        [
                            'category_ids' => json_encode($categoriesForCountry)
                        ]
                    );
                }
            }

            if($continentIds) {
                $existingContinentSubscriptions = $this->wishlistSubcription->whereIn('continent_id', $continentIds)->pluck('continent_id')->toArray();
                $newEventContinentSubscriptions = array_diff($continentIds, $existingContinentSubscriptions);
                    foreach ($continentIds as $key => $continentId) 
                    {
                        $categoriesForContinent = $request->input("continent_categories_$continentId");
                        $categoriesForContinent = $categoriesForContinent ?  json_decode($categoriesForContinent, true) : []; 

                        $wishlistSubcriptions = $this->wishlistSubcription->updateOrCreate(
                            [
                                'user_id'        => $userId,
                                'continent_id'   => $continentId
                            ],
                            [
                                'category_ids' => json_encode($categoriesForContinent)
                            ]
                        );
                    }
            }

            if($categoryIds) {

                $existingSubscriptions = $this->wishlistSubcription->whereNull('event_id')->whereNull('country_id')->whereNull('continent_id')->whereJsonContains('category_ids', $categoryIds)->pluck('category_ids')->toArray();
                $newSubscriptions = array_diff($categoryIds, $existingSubscriptions);

                $categorySubscriptions = $this->wishlistSubcription->updateOrCreate(
                    [ 
                        'user_id'         => $userId,
                        'event_id'        => null,
                        'country_id'      => null,
                        'continent_id'    => null
                    ],
                    [
                    'category_ids' => $categoryIds ? json_encode($categoryIds) : []
                    ]
                );
            }

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to Subscribe ' . $e->getMessage()
            ], 500);
        }
        return response()->json(['message' => 'Subscribed to selected categories successfully.'], 200);
    }

    public function unsubscribeFromWishlist(Request $request)
    {
        $subscribedEventId      = $request->event_id;
        $subscribedCountryId    = $request->country_id;
        $subscribedContinentId  = $request->continent_id;
        $subscribedCategoryId   = $request->category_id;
        $categoryIdToRemove     = $request->category_id;

        $user = Auth::user();
        $userId = $user->id;

        if ($subscribedEventId) {
            $subscribedEvent = $this->wishlistSubcription
                ->where('event_id', $subscribedEventId)
                ->where('user_id', $userId)
                ->first();

            if ($subscribedEvent) {
                $categoryIds = json_decode($subscribedEvent->category_ids, true);

                if (($key = array_search($categoryIdToRemove, $categoryIds)) !== false) {
                    unset($categoryIds[$key]);
                }

                $categoryIds = array_values($categoryIds);
                $subscribedEvent->category_ids = json_encode($categoryIds);        
                $subscribedEvent->save();
            }
        }

        if ($subscribedCountryId) {
            $subscribedCountry = $this->wishlistSubcription
                ->where('country_id', $subscribedCountryId)
                ->where('user_id', $userId)
                ->first();

            if ($subscribedCountry) {
                $categoryIds = json_decode($subscribedCountry->category_ids, true);

                if (($key = array_search($categoryIdToRemove, $categoryIds)) !== false) {
                    unset($categoryIds[$key]);
                }

                $categoryIds = array_values($categoryIds);

                if (empty($categoryIds)) {
                    $subscribedCountry->delete();
                    return response()->json(['message' => 'Successfully unsubscribed from the category.']);
                }

                $subscribedCountry->category_ids = json_encode($categoryIds);        
                $subscribedCountry->save();
            }
        }

        if ($subscribedContinentId) {
            $subscribedContinent = $this->wishlistSubcription
                ->where('continent_id', $subscribedContinentId)
                ->where('user_id', $userId)
                ->first();

            if ($subscribedContinent) {
                $categoryIds = json_decode($subscribedContinent->category_ids, true);

                if (($key = array_search($categoryIdToRemove, $categoryIds)) !== false) {
                    unset($categoryIds[$key]);
                }

                $categoryIds = array_values($categoryIds);

                if (empty($categoryIds)) {
                    $subscribedContinent->delete();
                    return response()->json(['message' => 'Successfully unsubscribed from the category.']);
                }

                $subscribedContinent->category_ids = json_encode($categoryIds);        
                $subscribedContinent->save();
            }
        }

        if ($subscribedCategoryId) {
            $subscribedCategory = $this->wishlistSubcription
                                            ->where('user_id', $userId)
                                            ->whereNull('event_id')
                                            ->whereNull('country_id')
                                            ->whereNull('continent_id')
                                            ->first();

            if ($subscribedCategory) {
                $categoryIds = json_decode($subscribedCategory->category_ids, true);

                if (($key = array_search($categoryIdToRemove, $categoryIds)) !== false) {
                    unset($categoryIds[$key]);
                }

                $categoryIds = array_values($categoryIds);

                if (empty($categoryIds)) {
                    $subscribedCategory->delete();
                    return response()->json(['message' => 'Successfully unsubscribed from the category.']);
                }

                $subscribedCategory->category_ids = json_encode($categoryIds);
                $subscribedCategory->save();
            }
        }

        return response()->json(['message' => 'Successfully unsubscribed from the category.']);
    }

}

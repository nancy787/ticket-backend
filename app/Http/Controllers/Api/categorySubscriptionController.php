<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TicketCategory;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class categorySubscriptionController extends Controller
{
    public function addSubscription(Request $request) {

        $validated = $request->validate([
            'category_ids' => 'required',
            'category_ids.*' => 'exists:ticket_categories,id',
        ]);
        try {
            $categoryIds = json_decode($request->category_ids);
            $user = Auth::user();

            if (!$user->is_premium && !$user->is_free_subcription) {
                return response()->json([
                    'success' => false,
                    'message'  => 'You are not a premium user. Please take a premium subscription!'
                ], 200);
            }

            $existingSubscriptions = $user->subscriptions()->whereIn('category_id', $categoryIds)->pluck('category_id')->toArray();
            $newSubscriptions = array_diff($categoryIds, $existingSubscriptions);

            if (!empty($newSubscriptions)) {
                $user->subscriptions()->createMany(array_map(function ($categoryId) use ($user) {
                    return ['user_id' => $user->id, 'category_id' => $categoryId];
                }, $newSubscriptions));
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to Subscribe ' . $e->getMessage()
            ], 500);
        }
        return response()->json(['message' => 'Subscribed to selected categories successfully.'], 200);
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'category_ids' => 'required',
            'category_ids.*' => 'exists:ticket_categories,id',
        ]);

        try {
            $categoryIds = json_decode($request->category_ids);
            $user = Auth::user();

            if (!$user->is_premium && !$user->is_free_subcription) {
                return response()->json([
                    'success' => false,
                    'message'  => 'You are not a premium user. Please take a premium subscription!'
                ], 200);
            }

            $user->subscriptions()->whereIn('category_id', $categoryIds)->delete();
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to Unsubscribe: ' . $e->getMessage()
            ], 500);
        }

        return response()->json(['message' => 'Unsubscribed from selected categories successfully.'], 200);
    }
}

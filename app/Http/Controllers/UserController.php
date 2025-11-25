<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\SellTicket;
use App\Models\Wishlist;
use App\Models\Transaction;
use App\Models\Country;
use App\Models\WishlistSubscription;
use App\Models\TicketCategory;
use DataTables;
use Stripe\Stripe;
use Stripe\Account;
use App\Models\StripeConnectAccount;

class UserController extends Controller
{
    protected $user;
    protected $transaction;

    public function __construct(User $user, Transaction $transaction, WishlistSubscription $wishlistSubcription, TicketCategory $ticketCategory) {
        $this->user                 = $user;
        $this->transaction          = $transaction;
        $this->wishlistSubcription  = $wishlistSubcription;
        $this->ticketCategory       = $ticketCategory;
    }

    public function index(Request $request) {
        return view('users.index');
    }
    public function getUsersData(Request $request) {

        $query = $this->user->with('roles')->whereDoesntHave('roles', function($query) {
            $query->where('name', 'admin');
        });

        if ($request->userId) {
            if($request->userId == 1) {
                return redirect()->route('profile.edit');
            }else {
                return redirect()->route('users.view', ['id' => $request->userId ]);
            }
        }

        if ($request->has('search') && $request->input('search.value') != '') {
            $searchValue = $request->input('search.value');
            $query = $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('email', 'like', "%{$searchValue}%")
                  ->orWhere('gender', 'like', "%{$searchValue}%")
                  ->orWhere('country', 'like', "%{$searchValue}%")
                  ->orWhere('nationality', 'like', "%{$searchValue}%")
                  ->orWhere('address', 'like', "%{$searchValue}%")
                  ->orWhere('device_type', 'like', "%{$searchValue}%")
                  ->orWhere('app_version', 'like', "%{$searchValue}%");
            });
        }

        if ($request->has('order')) {
            $columnIndex = $request->input('order.0.column');
            $direction = $request->input('order.0.dir');

            $columns = [
                'id', 'name', 'email', 'gender', 'country', 'nationality', 'address', 'device_type', 'app_version'
            ];

            $column = $columns[$columnIndex] ?? 'created_at';
            $query = $query->orderBy($column, $direction);
        } else {
            $query = $query->orderBy('created_at', 'asc');
        }

        $totalRecords = $query->count();

        $users = $query->skip($request->input('start'))
                       ->take($request->input('length'))
                       ->get();

        return response()->json([
            'draw' => (int)$request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $users->map(function($user) {
                $deleteStripeAccount = '';
                if ($user->stripeConnect && $user->stripeConnect->stripe_account_id) {
                    $deleteStripeAccount = '
                        <a href="javascript:void(0);" onclick="deleteConnectedAccount(' . $user->id . ');" class="btn text-danger">
                            <i class="fas fa-trash"></i> Delete stripe connected account
                        </a>
                        <form id="delete-connected-form-' . $user->id . '" action="' . route('users.delete-stripe-connected-account', ['id' => $user->id]) . '" method="POST" style="display: none;">
                            ' . csrf_field() . '
                        </form>
                    ';
                }
                return [
                    'checkbox' => '<input type="checkbox" name="bulkDelete[]" class="bulkDelete" value="' . $user->id . '">',
                    'name' => ucfirst($user->name),
                    'email' => $user->email,
                    'gender' => $user->gender ? ucfirst($user->gender) : '---',
                    'country' => $user->country ? ucfirst($user->country) : '---',
                    'nationality' => $user->nationality ? ucfirst($user->nationality) : '---',
                    'address' => $user->address ?? '---',
                    'device' => $user->device_type ?? '---',
                    'current_version' => $user->app_version ?? '---',
                    'action' => '
                        <div class="d-flex gap-2">
                            <!-- Edit Button -->
                            <a href="' . route('users.edit', ['id' => $user->id]) . '" class="btn text-primary">
                                <i class="fas fa-edit"></i>
                            </a>

                            <!-- Delete Button -->
                            <a href="javascript:void(0);" onclick="deleteUser(' . $user->id . ');" class="btn text-danger">
                                <i class="fas fa-trash"></i>
                            </a>
                            <form id="delete-form-' . $user->id . '" action="' . route('users.delete', ['id' => $user->id]) . '" method="POST" style="display: none;">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '
                            </form>

                            <!-- Block/Unblock Button -->
                            ' . (!$user->is_blocked ?
                            '<a href="' . route('users.block', ['id' => $user->id]) . '" class="btn text-danger">
                                <i class="fas fa-ban"></i> Block
                            </a>' :
                            '<a href="' . route('users.unblock', ['id' => $user->id]) . '" class="btn text-primary">
                                <i class="fas fa-unlock"></i> Unblock
                            </a>') . '

                            <!-- View Button -->
                            <a href="' . route('users.view', ['id' => $user->id]) . '" class="btn text-primary">
                                View
                            </a>
                            ' . $deleteStripeAccount . '
                        </div>
                    ',
                ];
            }),
        ]);
    }

    public function create()
    {
        $getCountries = Country::select('id', 'name', 'nationality')->get();
        return view('users.create-edit', compact('getCountries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required',
            'email'      => 'required|email|unique:users,email|regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
            'password'   => 'required|string|min:6|confirmed',
            'gender'     => 'required',
            'country'    => 'required',
            'nationality'=> 'required',
            'address'    => 'required',
        ]);

        $this->user->create([
            'name'       => $request->name,
            'email'      =>  $request->email,
            'password'   => Hash::make($request->password),
            'gender'     => $request->gender,
            'country'    => $request->country,
            'nationality' => $request->nationality,
            'address'    => $request->address,
            'sign_up_as' => $request->sign_up_as ?? null,
            'is_premium' => $request->is_premium == 1 ? true : false,
            'is_free_subcription' => $request->is_free_subcription == 1 ? true : false,
            'chat_enabled' => $request->chat_enabled == 1 ? true : false,
        ]);

        if ($request['sign_up_as'] === 'SELLER') {
            $role = Role::where('name', 'seller')->first();
        } else {
            $role = Role::where('name', 'buyer')->first();
        }

        if (!$role) {
            throw new \Exception('Role not found.');
        }

        $this->user->assignRole($role);

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function edit($id)
    {
        $user = $this->user->find($id);
        $getCountries = Country::select('id', 'name', 'nationality')
                                  ->orderBy('name', 'asc')
                                  ->orderBy('nationality', 'asc')
                                  ->get();

        $selectedCountry = $user->country;
        $selectedNationality = $user->nationality;
        return view('users.create-edit', compact('user','getCountries', 'selectedCountry', 'selectedNationality'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required',
            'email' => [
                'required',
                'email',
                'unique:users,email,' . $id,
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'gender'     => 'required',
            'country'    => 'required',
            'nationality'=> 'required',
        ]);

        $userData = [
            'name'       => $request->name,
            'email'      =>  $request->email,
            'password'   => Hash::make($request->password),
            'gender'     => $request->gender,
            'country'    => $request->country,
            'nationality' => $request->nationality,
            'address'    => $request->address,
            'sign_up_as' => $request->sign_up_as ?? null,
            'is_premium' => $request->is_premium == 1 ? true : false,
            'is_free_subcription' => $request->is_free_subcription == 1 ? true : false,
            'chat_enabled' => $request->chat_enabled == 1 ? true : false,
        ];

        $user = $this->user->find($id);

        $user->update($userData);

        $roleName = $request->sign_up_as === 'seller' ? 'seller' : 'buyer';

        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            throw new \Exception('Role not found.');
        }

        $user->roles()->sync([$role->id]);

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    public function delete($id)
    {
        try {
            $user = $this->user->findOrFail($id)->delete();
            return redirect()->route('users.index')->with('message', 'User deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Failed to delete user.');
        }
    }

    public function view($id) {
        $user = $this->user->select('id', 'name', 'email')->where('id', $id)->first();

        return view('users.view', compact('user'));
    }

    public function block($id)
    {
        $user = $this->user->findOrFail($id);
        $user->is_blocked = true;
        $user->save();

        return redirect()->back()->with('message', 'User Blocked successfully.');
    }

    public function unblock($id)
    {
        $user = $this->user->findOrFail($id);
        $user->is_blocked = false;
        $user->save();

        return redirect()->back()->with('success', 'User unblocked successfully!');
    }

    public function ticketStatus($id, Request $request) {

        $userId = $id;

        if($request->status == 'soldTickets') {
            $userDetail = $this->user->select('tickets.*', 'events.name as event_name')
                            ->join('tickets', 'users.id', '=', 'tickets.created_by')
                            ->join('events', 'events.id', '=', 'tickets.event_id')
                            ->where('tickets.created_by', $userId);
        }

        if($request->status == 'purchaseHistory') {
            $userDetail = SellTicket::select('sell_tickets.*', 'users.name as user_name', 'events.name as event_name', 'tickets.*')
                                        ->join('users', 'sell_tickets.user_id', '=', 'users.id')
                                        ->join('events', 'sell_tickets.event_id', '=', 'events.id')
                                        ->join('tickets', 'sell_tickets.ticket_id', '=', 'tickets.id')
                                        ->where('sell_tickets.user_id', $userId);
        }

        if($request->status == 'wishlistItems') {
            $userDetail = Wishlist::select('wishlists.*', 'users.name as user_name', 'events.name as event_name', 'tickets.*')
                                    ->join('users', 'wishlists.user_id', '=', 'users.id')
                                    ->join('events', 'wishlists.event_id', '=', 'events.id')
                                    ->join('tickets', 'wishlists.event_id', '=', 'tickets.event_id')
                                    ->where('wishlists.user_id', $userId);
        }

        $userDetail = $userDetail->groupBy('tickets.id')->orderBy('tickets.id', 'DESC')->get();

        return response()->json([
            'userDetail' => $userDetail,
        ], 200);

    }

    public function viewdeletedUser() {
        $users = User::onlyTrashed()->get();
        return view('users.deleted-user', compact('users'));
    }

    public function restoreUser($id) {

        $users = User::onlyTrashed()->find($id);

        if(!$users) {
            return back()->with('message', 'no users found');
        }

        $users->restore();

        return redirect()->route('users.index')->with('success', 'User restored successfully.');
    }

    public function deletePermanent($id) {
        try {
            $user = $this->user->findOrFail($id)->forceDelete();
            return redirect()->route('users.index')->with('message', 'User deleted Permanent');
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Failed to delete user.');
        }
    }

    public function bulkDelete(Request $request) {
        $selectedIds = $request->ids;

        if (!empty($selectedIds)) {
            $deleted = $this->user->destroy($selectedIds);
            return response()->json(['message' => "{$deleted} users deleted successfully"]);
        } else {
            return response()->json(['message' => 'No users selected for deletion'], 400);
        }
    }

    public function bulkRestore(Request $request) {
        $selectedIds = $request->ids;
        if (!empty($selectedIds)) {
            $restored = $this->user->withTrashed()->whereIn('id', $selectedIds)->restore();
            return response()->json(['message' => "{$restored} users restored successfully"]);
        } else {
            return response()->json(['message' => 'No users selected for restoration'], 400);
        }
    }

    public function getTransectionHistory($id) {

        $userId = $id;

        $transactionHistory = $this->transaction->where('user_id', $userId)->whereNot('status', 'pending')->orderBy('created_at', 'desc')->get();

        if(!$transactionHistory) {
            return response()->json(['message' => 'No transactions history found'], 400);
        }

        return response()->json([
            'transactionHistory' => $transactionHistory,
        ], 200);
    }

    public function getSubscribedWishlist($id)  {

        $subscribedWishlist = $this->wishlistSubcription->where('user_id', $id)->get();

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

            $eventName  = $subscription->event_id ? $subscription->event->name : null;
            $countryName = $subscription->country_id ? $subscription->country->name : null;
            $continentName = $subscription->continent_id ? $subscription->continent->name : null;

            return [
                'country_name' => $countryName,
                'continent_name' => $continentName,
                'event_name' => $eventName,
                'categories' => $categories,
            ];
        });
        return response()->json([
            'subscribedWishlist' => $wishlistWithDetails,
        ], 200);
    }

    public function chatEnable(Request $request) {
        return $this->updateChat(true);
    }

    public function chatDisable() {
        return $this->updateChat(false);
    }

    private function updateChat(bool $enabled) {
        $users = $this->user->get();
        if (!$users) {
            return back()->with('message', 'user not found');
        }
        foreach($users as $user) {
            $user->update([
                'chat_enabled' => $enabled
            ]);
        }
 
        $message = $enabled ? 'Chat enabled successfully' : 'Chat disabled successfully';
        return back()->with('success', $message);
    }

    public function deleteStripeConnectedAccount($id) {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $user = $this->user->with('stripeConnect')->findOrFail($id);
            if(!$user) {
                return back()->with('message', 'user not found');
            }

            $accountId = $user->stripeConnect ? $user->stripeConnect->stripe_account_id : null;

            if(!$accountId) {
                return back()->with('message', 'Stripe connected account not found');
            }

            $account = Account::retrieve($accountId);
            $stripeConnectedAccount = StripeConnectAccount::with('user')->where('stripe_account_id', $accountId)->first();
            if($stripeConnectedAccount) {
                if ($stripeConnectedAccount->user) {
                    $stripeConnectedAccount->user->update([
                        'is_stripe_connected' => false
                    ]);
                }
                $stripeConnectedAccount->delete();
            }
            $account->delete();
            $this->info("Connected account {$accountId} deleted successfully.");
            return redirect()->route('users.index')->with('message', 'User deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while deleting the account.');
        }
    }
}

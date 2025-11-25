<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\Models\User;
use Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Carbon\Carbon;
use App\Models\Country;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegisterMail;
use App\Traits\StoresEmailRecords;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    use StoresEmailRecords;

    public function register(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name'          => 'required',
                'email'         => 'required|email|unique:users,email',
                'password'      => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            DB::beginTransaction();

            $password  = Hash::make($request->password);

            $user = User::create([
                'name'           => $request->name,
                'gender'         => $request->gender,
                'address'        => $request->address,
                'country'        => $request->country,
                'nationality'    => $request->nationality,
                'email'          => $request->email,
                'password'       => $password,
            ]);

            $token = JWTAuth::fromUser($user);

            DB::commit();

            $response = [
                'success' => true,
                'data'    => [
                    'token' => $token,
                ],
                'message' => $user->name . ' Registered successfully',
                'userInfo' => $user
            ];

            $userName  = $user->name;
            $userEmail = $user->email;
            $userId    = $user->id;
            $mailInstance = new UserRegisterMail($userName);
            Mail::to($userEmail)->send($mailInstance);
            $this->storeEmailRecord($userId, env('MAIL_FROM_ADDRESS'), $user->email, $mailInstance);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            if ($user->is_blocked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been blocked, please contact support',
                    'is_blocked' => $user->is_blocked,
                ], 401);
            }

            // $credentials = $request->only('email', 'password');

            // if (!$token = JWTAuth::attempt($credentials)) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Unauthorized'
            //     ], 401);
            // }

            if ($request->fcm_token) {
                $user->update(['fcm_token' => $request->fcm_token]);
            }

            // Generate the JWT token (only in production or standard environments)
            $token = JWTAuth::fromUser($user);
            $success['token'] = $token;

            $response = [
                'success' => true,
                'data'    => $success,
                'message' => $user->name . ' logged in successfully'
            ];

            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided'
                ], 400);
            }

            JWTAuth::parseToken()->invalidate();

            $response = [
                'success' => true,
                'message' => 'User logged out successfully'
            ];

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to log out: ' . $e->getMessage()
            ], 500);
        }
    }

    public function changeEmail(Request $request) {

        try {
            $user =  Auth::user();

            if(!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 200);
            }

            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email,' . $user->id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            $user->email = $request->email;
            $user->save();

            $response = [
                'success'  => true,
                'message' => $user->name . ' your email is updated successfully',
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $user =  Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 200);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'you password is incorrect',
            ], 200);
        }
        
        $validator = Validator::make($request->all(), [
            'new_password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 200);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully',
        ]);
    }

    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 200);
        }

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json(['message' => __($status)], 200);
            } else {
                return response()->json(['message' => __($status)], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send password reset email.'
            ], 500);
        }
    }

    public function googleSignIn(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'google_response' => 'required|json',
                'device_type'     => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            $googleResponse = json_decode($request->input('google_response'), true);

            if (is_null($googleResponse)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON format in google_response',
                ], 400);
            }
    
            if (!isset($googleResponse['idToken']) || !isset($googleResponse['user']['id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google response',
                ], 400);
            }

            $existingUser = User::where('email', $googleResponse['user']['email'])->first();

            if ($existingUser && $existingUser->google_id !== $googleResponse['user']['id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already exists',
                ], 400);
            }

            $user = User::where('google_id', $googleResponse['user']['id'])->first();

            if (!$user) {
                $user = User::create([
                        'name'          => $googleResponse['user']['name'],
                        'email'         => $googleResponse['user']['email'],
                        'google_id'     => $googleResponse['user']['id'],
                        'google_token'  => $googleResponse['idToken'],
                        'password'      => Hash::make(Str::random(16)),
                        'device_type'   => $request->device_type,
                        'fcm_token'     => $request->fcm_token,
                ]);

            } else {
                $user->google_token = $googleResponse['idToken'];
                $user->fcm_token = $request->fcm_token;
                $user->save();
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'data'    => [
                    'token' => $token,
                ],
                'message' => $user->name . ' signed in successfully',
                'userInfo'  => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProfile(Request $request) {
        try {

            $user =  Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 200);
            }

            $validator = Validator::make($request->all(), [
                'name'  => 'required',
                'email' => 'required|email|unique:users,email,' . $user->id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'country' => $request->country,
                'phone_number' => $request->phone_number,
                'nationality'  => $request->nationality,
            ];

            $user->update($userData);

            return response()->json([
                'success' => true,
                'userinfo' => $user,
                'message' => $user->name . ' your profile is updated successfully',
            ], 200);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteAccount() {
        try {
            $userData = Auth::user()->delete();

            return response()->json([
                'success' => true,
                'message' => 'User Deleted Successfully'
            ], 200);

        } catch (\Exception $th) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function restoreAccount($id) {
        try {
            $userData = User::onlyTrashed()->find($id);

            if(!$userData) {
                return response()->json([
                    'suceess' => false,
                    'message' => 'No User Found'
                ], 404);
            }

            $deletionDate = $userData->deleted_at;
            $oneMonthAgo = Carbon::now()->addMonth();

            if ($oneMonthAgo < $deletionDate) {
                return response()->json(['message' => 'Cannot restore account. The restoration period has expired.'], 403);
            }

            $userData->restore();

            return response()->json([
                'success' => true,
                'message' => 'user restored successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteAccountPermanent() {
        try {
            $userData = Auth::user()->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'User Deleted Successfully'
            ], 200);

        } catch (\Exception $th) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function enableNotification() {
        try {
            $userData = Auth::user();

            if(!$userData) {
                    return response()->json([
                        'suceess' => false,
                        'message' => 'No User Found'
                    ], 404);
                }

            $userData->update(['notifications_enabled' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notification enabled successfully'
            ], 200);

        } catch (\Exception $th) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function disableNotification() {
        try {
            $userData = Auth::user();

            if(!$userData) {
                    return response()->json([
                        'suceess' => false,
                        'message' => 'No User Found'
                    ], 404);
                }

            $userData->update(['notifications_enabled' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Notification disabled successfully'
            ], 200);

        } catch (\Exception $th) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function appleSignIn(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'apple_response' => 'required|json',
                'device_type'     => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            $appleResponse = json_decode($request->input('apple_response'), true);

            if (!isset($appleResponse['identityToken']) || !isset($appleResponse['user'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Apple response',
                ], 400);
            }

            $user = User::where('apple_id', $appleResponse['user'])->first();

            if (!$user) {
                $user = User::create([
                        'name'          => $appleResponse['fullName']['givenName'],
                        'email'         => $appleResponse['email'],
                        'apple_id'      => $appleResponse['user'],
                        'apple_token'   => $appleResponse['identityToken'],
                        'password'      => Hash::make(Str::random(16)),
                        'device_type'   => $request->device_type,
                        'fcm_token'     => $request->fcm_token,
                ]);

            } else {
                $user->apple_token = $appleResponse['identityToken'];
                $user->fcm_token   = $request->fcm_token;
                $user->save();
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'data'    => [
                    'token' => $token,
                ],
                'message' => $user->name . ' signed in successfully',
                'userInfo' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCountryAttribute() {
        $countriesWithNationality = Country::select('id','name', 'nationality')->orderBy('name', 'asc')->get();
        $countries   = $countriesWithNationality->pluck('name');
        $nationality  = $countriesWithNationality->pluck('nationality');
        return response()->json([
            'countries' => $countries,
            'nationality' => $nationality,
            'countriesWithNationality' => $countriesWithNationality,
        ]);
    }

    public function notifications() {
        try {
            $userId = Auth::user()->id;
            $oneWeekAgo = Carbon::now()->subDays(7)->startOfDay();
            $userNotifications = Notification::where(function($query) use ($userId) {
                                                    $query->where('user_id', $userId)
                                                          ->orWhere('type', 'custom');
                                                })
                                                ->whereDate('created_at', '>=', $oneWeekAgo)
                                                ->orderBy('created_at', 'desc')
                                                ->get();
            return response()->json([
                'success' => true,
                'userNotifications' => $userNotifications,
                'message' => 'Notifications fetched successfully',
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateCountry(Request $request) {
        try {
                $user = Auth::user();
                if(!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'user not found'
                    ], 404);
                }
                $user->update([
                    'country'  => $request->country,
                    'nationality' => $request->nationality
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'user country updated successfully'
                ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCredentials() {
        $pKey = env('STRIPE_PUBLISH_KEY');
        return response ([
            'p_key' => $pKey
        ], 200);
    }

    public function getAppVersion() {
        try {
            $iosVersion = env('IOS_VERSION', 1.7);
            $androidVersion = env('ANDROID_VERSION', 1.7);
            $amazonVersion = env('AMAZON_VERSION', 1.7);

            return response([
                'ios_version'       => $iosVersion,
                'android_version'   => $androidVersion,
                'amazon_version'    => $amazonVersion,
            ], 200);
        } catch (\Exception $th) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function ChatboatUpdate() {
        try {
            $authUser = Auth::user();
            $user = User::findOrFail($authUser->id);
            $user->update(['chat_enabled' => !$user->chat_enabled]);
            return response()->json([
                'success' => true,
                'message' => 'Chat status updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateVersion(Request $request) {
        try {
            $authUser = Auth::user();
            $user = User::findOrFail($authUser->id);
            $deviceType = $request->device_type ?? null;
            $appVersion = $request->app_version ?? null;
            $user->update(['device_type' => $deviceType,
                            'app_version'   => $appVersion
                        ]);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

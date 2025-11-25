<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::middleware(['auth:api'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AuthController::class)->group(function() {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('forgetpassword', 'forgetPassword');
    Route::Post('googleSignIn', 'googleSignIn');
    Route::Post('appleSignIn', 'appleSignIn');
    Route::post('restore-account/{id}', 'restoreAccount');

    // Route::middleware(['auth:api'])->group(function () {
        Route::post('logout', 'logout');
        Route::Post('updateProfile', 'updateProfile');
        Route::Post('changeEmail', 'changeEmail');
        Route::Post('changePassword', 'changePassword');
        Route::delete('delete-account', 'deleteAccount');
        Route::delete('permanent-delete-account', 'deleteAccountPermanent');
        Route::post('enable-notification', 'enableNotification');
        Route::post('disable-notification', 'disableNotification');
        Route::get('notifications', 'notifications');
        Route::Post('updateCountry', 'updateCountry');
        Route::Post('enable-disable-chat', 'ChatboatUpdate');
        Route::Post('updateVersion', 'updateVersion');
    // });
    Route::get('getCountryAttribute', 'getCountryAttribute');
});

Route::middleware(['auth:api', 'checkIfBlocked'])->group(function () {

    Route::prefix('events')->group(function () {
        Route::get('/getevents', 'Api\EventController@index')->name('event.index');
        Route::get('/getEventAttribute', 'Api\EventController@getEventAttribute')->name('event.attribute');
        Route::post('/search-events', 'Api\EventController@searchEvent')->name('event.search-events');
        Route::get('/dropdownEvents', 'Api\EventController@dropdownEvent')->name('event.dropdown-events');
    });

    Route::prefix('tickets')->group(function () {
        Route::get('/getTickets', 'Api\TicketController@index')->name('ticket.index');
        Route::post('/createTicket', 'Api\TicketController@store')->name('ticket.store');
        Route::patch('/editTicket/{ticketId}', 'Api\TicketController@update')->name('ticket.update');
        Route::delete('/delete/{ticketId}', 'Api\TicketController@destroy')->name('ticket.delete');
        Route::get('/get-ticket-categories', 'Api\TicketController@getTicketCategories')->name('ticket.getcategories');
        Route::get('/get-purchased-tickets', 'Api\TicketController@getpurchasedTickets')->name('ticket.get-purchased-tickets');
        Route::post('/resale-ticket/{ticketId}', 'Api\TicketController@resaleTicket')->name('ticket.resale-ticket');
        Route::post('/lock-ticket/{ticketId}', 'Api\TicketController@lockTicket')->name('ticket.lock-ticket');
        Route::get('/check-ticket-status/{ticketId}', 'Api\TicketController@checkTicketStatus')->name('ticket.check-ticket-status');
        Route::get('/sold-tickets', 'Api\TicketController@soldTickets')->name('ticket.sold-tickets');
        Route::get('/purchased-tab/{ticketId}', 'Api\TicketController@PurchasedTab');
    });

    Route::prefix('wishlist')->group(function () {
        Route::post('/add-to-wishlist', 'Api\WishlistController@addToWishlist')->name('wishlist.add');
        Route::get('/get-my-wishlist', 'Api\WishlistController@getMyWishlist')->name('wishlist.get');
        Route::delete('/remove-from-wishlist/{eventId}', 'Api\WishlistController@removeFromWishlist')->name('wishlist.remove');
    });

    Route::prefix('category-subscription')->group(function () {
        Route::post('/subscribe', 'Api\categorySubscriptionController@addSubscription')->name('add.subscription');
        Route::post('/unsubscribe', 'Api\categorySubscriptionController@unsubscribe')->name('unsubscribe');
    });

    Route::prefix('wishlist-subscription')->group(function () {
        Route::get('/subscribe-attribute', 'Api\WishlistSubscriptionController@getSubscriptionAttributes')->name('get-subscription-attributes');
        Route::post('/subscribe-to-wishlist', 'Api\WishlistSubscriptionController@subscribeToWishlist')->name('subscribe-to-wishlist');
        Route::get('/get-subscribed-wishlist', 'Api\WishlistSubscriptionController@getSubscribedWislist')->name('get-to-wishlist');
        Route::post('/unsubscribe-from-wishlist', 'Api\WishlistSubscriptionController@unsubscribeFromWishlist')->name('unsubscribe-from-wishlist');
    });

    Route::prefix('payment')->group(function () {
        Route::post('/create-payment-intent', 'Api\PaymentController@createPaymentIntent');
        Route::post('/confirm-payment', 'Api\PaymentController@confirmPayment');
        Route::post('/stripe/webhook', 'Api\PaymentController@handleWebhook');
        Route::get('/transaction-history', 'Api\PaymentController@getTransactionHistory');
        Route::post('/apple-pay', 'Api\PaymentController@handleApplePay');
        Route::post('/buy-tickets', 'Api\PaymentController@buyTicketWithConnect');
        Route::post('/create-payment', 'Api\PaymentController@createPayment');
        Route::post('/update-transaction', 'Api\PaymentController@updateTransaction');
        Route::post('/success', 'Api\PaymentController@paymentSuccess');
        Route::post('/ticket-confirm-payment', 'Api\PaymentController@ticketConfirmPayment');
   //     Route::post('/buy-tickets-with-connect', 'Api\PaymentController@buyTicketWithConnect');
    });

    Route::prefix('stripe-connect')->group(function () {
        Route::post('/create-account' , 'Api\PaymentController@createOrUpdateAccount');
        Route::get('/login-link' , 'Api\PaymentController@getStripeLoginLink');
        Route::get('/account-status' , 'Api\PaymentController@getAccountStatus');
    });
});

Route::get('/getBanners','Api\BannerController@index')->name('banner.index');
Route::get('/terms_and_conditions','Api\ProfileController@termsAndCondition')->name('terms_and_conditions');
Route::get('/help_and_support','Api\ProfileController@helpAndSupport')->name('help_and_support');
Route::get('/privacy_policy','Api\ProfileController@privacyPolicy')->name('privacy_policy');

Route::post('/push-notification','Api\PushNotificationController@sendPushNotification')->middleware('auth:api');
Route::get('/credentials','Api\AuthController@getCredentials');
Route::get('/app-versions','Api\AuthController@getAppVersion');
Route::post('/payment/webhook', 'Api\PaymentController@handlePaymentWebhook');
Route::post('/payment/webhook/stripe-connect', 'Api\PaymentController@handlePaymentWebhookWithConnect');
Route::get('/get-faqs', 'FAQController@getFaqs');
Route::get('/account/return', 'Api\PaymentController@handleStripeReturn');
Route::post('/handle-refund', 'Api\PaymentController@handleRefundWebhook');
Route::get('/country-codes', 'Api\PaymentController@getCountryCodes');
Route::post('/unlock-ticket/{ticketId}', 'Api\TicketController@unlockTicket')->name('ticket.unlock-ticket');
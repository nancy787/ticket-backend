<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/success', function () {
    return view('emails.success');
})->name('email.success');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::group(['middleware' => ['auth', 'admin:admin']], function () {
    Route::get('/', 'UserController@index')->name('users');

    Route::prefix('users')->group(function () {
        Route::get('/', 'UserController@index')->name('users.index');
        Route::get('/users-data', 'UserController@getUsersData')->name('users.data');
        Route::get('/create', 'UserController@create')->name('users.create');
        Route::post('/store', 'UserController@store')->name('users.store');
        Route::get('/edit/{id}', 'UserController@edit')->name('users.edit');
        Route::post('/update/{id}', 'UserController@update')->name('users.update');
        Route::delete('/delete/{id}', 'UserController@delete')->name('users.delete');
        Route::get('/view/{id}', 'UserController@view')->name('users.view');
        Route::get('/users/block/{id}', 'UserController@block')->name('users.block');
        Route::get('/users/unblock/{id}', 'UserController@unblock')->name('users.unblock');
        Route::get('/ticket-status/{id}', 'UserController@ticketStatus')->name('users.ticket-status');
        Route::get('/deleted-user', 'UserController@viewdeletedUser')->name('users.view-deleted-user');
        Route::post('/restore-user/{id}', 'UserController@restoreUser')->name('users.restore');
        Route::delete('/delete-permanent/{id}', 'UserController@deletePermanent')->name('users.permanent-delete');
        Route::post('/bulk-delete', 'UserController@bulkDelete')->name('users.bulk-delete');
        Route::post('/bulk-restore', 'UserController@bulkRestore')->name('users.bulk-restore');
        Route::get('/transaction-history/{id}', 'UserController@getTransectionHistory')->name('users.transaction-history');
        Route::get('/subscribed-wishlist/{id}', 'UserController@getSubscribedWishlist')->name('users.subscribed-wishlist');
        Route::post('/chat-enable', 'UserController@chatEnable')->name('users.chat-enable');
        Route::post('/chat-disable', 'UserController@chatDisable')->name('users.chat-disable');
        Route::post('/delete-stripe-connected-account/{id}', 'UserController@deleteStripeConnectedAccount')->name('users.delete-stripe-connected-account');
    });

    Route::prefix('events')->group(function () {
            Route::get('/', 'EventController@index')->name('event.index');
            Route::get('/create', 'EventController@create')->name('event.create');
            Route::get('/edit/{id}', 'EventController@edit')->name('event.edit');
            Route::post('/store', 'EventController@store')->name('event.store');
            Route::get('/view/{id}', 'EventController@view')->name('event.view');
            Route::post('/update/{id}', 'EventController@update')->name('event.update');
            Route::post('/delete/{id}', 'EventController@destroy')->name('event.delete');
            Route::get('/raceinformation/{id}', 'EventController@addRaceInformation')->name('event.raceinfomation');
            Route::post('/store-race-info/{id}', 'EventController@storeRaceInformation')->name('event.store-raceinfo');
            Route::get('/archive', 'EventController@archive')->name('event.archive');
            Route::post('/move-to-archive/{id}', 'EventController@moveToarchive')->name('event.moveToarchive');
            Route::post('/unarchive/{id}', 'EventController@Unarchive')->name('event.unarchive');
            Route::get('/countries/{continent}', 'EventController@getCountries')->name('event.getCountries');
            Route::get('/getCountryCurrency/{countryId}', 'EventController@getCountryCurrency')->name('event.getCountryCurrency');
            Route::post('/inactive-events', 'EventController@inactiveEvents')->name('event.inactive');
            Route::post('/activate-events', 'EventController@activateEvents')->name('event.active');
    });

    Route::prefix('tickets')->group(function () {
        Route::get('/', 'TicketController@index')->name('ticket.index');
        Route::get('/ticket-data', 'TicketController@getTicketData')->name('ticket.data');
        Route::get('/create', 'TicketController@create')->name('ticket.create');
        Route::post('/store', 'TicketController@store')->name('ticket.store');
        Route::get('/edit/{id}', 'TicketController@edit')->name('ticket.edit');
        Route::get('/view/{id}', 'TicketController@view')->name('ticket.view');
        Route::post('/update/{id}', 'TicketController@update')->name('ticket.update');
        Route::post('/delete/{id}', 'TicketController@destroy')->name('ticket.delete');
        Route::get('/addNewTicketCategory', 'TicketController@addNewCategory')->name('ticket.add-new-ticket-category');
        Route::post('/add-ticket-category', 'TicketController@addCategory')->name('ticket.add-ticket-category');
        Route::get('/get-categories/{categoryTypeId}','TicketController@getCategories')->name('ticket.getcategory');
        Route::get('/edit-category/{categoryId}','TicketController@editCategory')->name('ticket.edit-category');
        Route::post('/update-category/{categoryId}','TicketController@updateCategory')->name('ticket.update-category');
        Route::post('/delete-category/{categoryId}','TicketController@deleteCategory')->name('ticket.delete-category');
        Route::post('/verifyticket/{id}', 'TicketController@verifyTicket')->name('ticket.verify-ticket');
        Route::get('/ticket_categories','TicketController@getTicketCategories')->name('ticket.ticket_categories');  
        Route::get('/event-details/{event}', 'EventController@getEventDetails')->name('ticket.getEventDetails');  
        Route::post('/changestatus/{id}', 'TicketController@changeStatus')->name('ticket.change-status');
        Route::get('/addons', 'TicketController@Addons')->name('ticket.addons');
        Route::post('/update-addons', 'TicketController@updateAddons')->name('ticket.update-addons');
        Route::get('/archive', 'TicketController@archiveList')->name('ticket.archive');
        Route::get('/get-archive', 'TicketController@getarchiveList')->name('ticket.getarchive');
        Route::post('/move-to-archive/{id}', 'TicketController@archive')->name('ticket.moveToarchive');
        Route::post('/unarchive/{id}', 'TicketController@Unarchive')->name('ticket.unarchive');
        Route::get('/user-details', 'TicketController@userDetail')->name('ticket.user-details');
        Route::post('/sell-ticket', 'TicketController@sellTicket')->name('ticket.sell-ticket');
        Route::get('/generate-pdf/{id}', 'TicketController@generatePDF')->name('ticket.generate-pdf');
        Route::get('/sold-ticket/{id}', 'TicketController@getSoldTicket')->name('ticket.sold-ticket');
        Route::post('/bulk-archive', 'TicketController@bulkArchive')->name('ticket.bulk-archive');
        Route::post('/remove-buyer/{id}', 'TicketController@removeBuyer')->name('ticket.remove-buyer');
        Route::post('/update-seller-paid', 'TicketController@updateSellerPaid')->name('ticket.update-seller-paid');
        Route::get('/filter', 'TicketController@filterTickets')->name('ticket.filter');
        Route::get('/deleted-tickets', 'TicketController@deletedTickets')->name('ticket.deleted-tickets');
        Route::get('/get-deleted-tickets', 'TicketController@getdeletedTickets')->name('ticket.get-deleted-tickets');
        Route::post('/restore/{id}', 'TicketController@restoreTicket')->name('tickets.restore');
        Route::post('/transfer-funds/{id}', 'TicketController@transferFunds')->name('tickets.transfer-funds');
    });

    Route::prefix('banner')->group(function() {
        Route::get('/pages', 'BannerController@index')->name('banner.index');
        Route::get('/create', 'BannerController@create')->name('banner.create');
        Route::post('/store', 'BannerController@store')->name('banner.store');
        Route::get('/edit/{id}', 'BannerController@edit')->name('banner.edit');
        Route::post('/update/{id}', 'BannerController@update')->name('banner.update');
        Route::post('/delete/{id}', 'BannerController@destroy')->name('banner.delete');
    });

    Route::prefix('reports')->group(function() {
        Route::get('/', 'ReportController@index')->name('report.index');
        Route::get('/app-users', 'ReportController@appUserReport')->name('report.app-users');
        Route::get('/wislist-reports', 'ReportController@wishlistReport')->name('report.wislist-reports');
        Route::get('/ticket-sold-reports', 'ReportController@ticketSoldReport')->name('report.ticket-sold-reports');
        Route::get('/sales-reports', 'ReportController@ticketsalesReport')->name('report.sales-reports');
        Route::get('/wishlist-subscription-reports', 'ReportController@wishlistSubscriptionReports')->name('reports.wishlist-subscription-reports');
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/list', 'NotificationController@index')->name('notification.index');
        Route::post('/send-notification', 'NotificationController@sendNotificationToAllUsers')->name('notification.send-notification');
    });
});
    Route::get('/payment-success', function () {
        return view('payment.success');
    })->name('payment.success');

    Route::get('/failed', function () {
        return view('payment.failed');
    })->name('payment.failed');


    Route::get('/terms-and-condition', 'TermsAndServiceController@index')->name('terms-and-condition');
    Route::get('/change-terms-and-condition/{id}', 'TermsAndServiceController@edit')->name('change-terms-and-condition');
    Route::post('/update-terms-and-condition/{id}', 'TermsAndServiceController@update')->name('update-terms-and-condition');
    Route::get('/help-and-suppport','TermsAndServiceController@helpAndSupport')->name('help-and-suppport');
    Route::post('/contact-us', 'TermsAndServiceController@contactUs')->name('contact-us');
    Route::get('/f_a_ques', 'FAQController@index')->name('f_a_ques');
    Route::get('/add-f_a_q', 'FAQController@create')->name('add-f_a_q');
    Route::Post('/store-f_a_q', 'FAQController@store')->name('store-f_a_q');
    Route::Post('/faqs.delete/{id}', 'FAQController@delete')->name('faqs.delete');

    Route::get('account/refresh', function() {
        return view('payment.refresh');
    });

    Route::get('account/return', function () {
        return view('payment.return');
    });

require __DIR__.'/auth.php';

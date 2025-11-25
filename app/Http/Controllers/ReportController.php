<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Wishlist;
use App\Models\Ticket;
use App\Models\WishlistSubscription;
use App\Exports\UsersExport;
use App\Exports\WishlistExport;
use App\Exports\TicketsSoldExport;
use App\Exports\TicketsSaleExport;
use App\Exports\WishlistSubscriptionExport;
use App\Models\Transaction;
use App\Models\Event;

class ReportController extends Controller
{
    public function __construct(User $user, Wishlist $wishlist, Ticket $ticket, WishlistSubscription $wishlistSubscription, Transaction $transaction, Event $event){
        $this->user                   = $user;
        $this->wishlist               = $wishlist;
        $this->ticket                 = $ticket;
        $this->wishlistSubscription   = $wishlistSubscription;
        $this->transaction            = $transaction;
        $this->event                  = $event;
    }

    public function index(Request $request) {

       $getEventName = $this->event->select('id', 'name')->where('active', 1)->where('archived', 0)->get();
       return view('reports.index', compact('getEventName'));
    }

    public function appUserReport(Request $request) {
        $today = Carbon::now();

        $users = $this->user->selectRaw('Upper(country) as country')
                            ->addSelect('gender')
                            ->whereNotNull('country')
                            ->orderBy('created_at', 'desc');

        if (($request->start_date && $request->end_date) || ($request->start_time &&  $request->end_time)) {
            $startDate = Carbon::parse($request->start_date);
            $endDate   = Carbon::parse($request->end_date);

            if ($request->has('start_time') && $request->start_time) {
                $startDate->setTimeFromTimeString($request->start_time);
            } else {
                $startDate->startOfDay();
            }

            if ($request->has('end_time') && $request->end_time) {
                $endDate->setTimeFromTimeString($request->end_time);
            } else {
                $endDate->endOfDay();
            }

            if (!$request->has('start_date') || !$request->has('end_date')) {
                $startDate = $today->startOfDay();
                $endDate   = $today->endOfDay();
            }

            $formattedStartDate = $startDate->format('Y-m-d H:i:s');
            $formattedEndDate = $endDate->format('Y-m-d H:i:s');

            $users = $users->whereBetween('created_at', [$startDate, $endDate]);
        }

        $users = $users->get()->groupBy('country')
                        ->map(function ($countryGroup) {
                            return $countryGroup->groupBy(function ($user) {
                                // Group users by gender, including 'Not Mentioned' for NULL genders
                                return $user->gender ?? 'Not Mentioned';
                            })->map(function ($genderGroup) {
                                return $genderGroup->count();
                            });
                        });

        $exportData = [];
        $totalNotMentioned = 0;
        foreach ($users as $country => $genders) {
            $notMentionedCount = $genders['Not Mentioned'] ?? 0;

            $totalNotMentioned += $notMentionedCount;

            $total = ($genders['male'] ?? 0) + ($genders['female'] ?? 0) + ($genders['other'] ?? 0) + $totalNotMentioned;

            $exportData[] = [
                'Country'       => $country,
                'Male'          => $genders['male'] ?? '0',
                'Female'        => $genders['female'] ?? '0',
                'Other'         => $genders['other'] ?? '0',
                'Not Mentioned' => $totalNotMentioned ?? '0',
                'Total'         => $total ?? '0',
                'Wishlist Subs' => '0'
            ];
        }

        if (($request->start_date && $request->end_date) || ($request->start_time && $request->end_time)) {
            $fileName = $this->getFileName('users_app_report_', $formattedStartDate, $formattedEndDate);
        } else {
            $formattedStartDate = $today->startOfDay()->format('Y-m-d H:i:s');
            $formattedEndDate   = $today->endOfDay()->format('Y-m-d H:i:s');
            $fileName = $this->getFileName('users_app_report_', $formattedStartDate, $formattedEndDate);
        }

        $filePath = 'public/reports/userReports/' . $fileName;
        Excel::store(new UsersExport($exportData), $filePath);

        return response()->json([
            'success' => true,
            'reportUrl' => asset('storage/reports/userReports/' . $fileName)
        ]);
    }

    public function wishlistReport(Request $request) {

        $wishlistReports = $this->wishlist->with('event', 'ticketCategory')->orderBy('created_at', 'desc');

        if($request->event_id) {
            $wishlistReports = $wishlistReports->where('event_id', $request->event_id);
        }

        $wishlistReports = $wishlistReports->get();

        $groupedData = $wishlistReports->groupBy('event.name')->map(function ($items) {
            return $items->groupBy('ticketCategory.name')->map(function ($categoryItems) {
                return $categoryItems->count();
            });
        });

        $allCategories = $groupedData->flatMap(function ($categories) {
            return $categories->keys();
        })->unique();

        $exportData = [];

        foreach ($groupedData as $eventName => $categories) {
            $row = ['Event' => $eventName];
            $totalCategories = 0;
    
            foreach ($allCategories as $category) {
                $count = $categories[$category] ?? '0';
                $row[$category] = $count;
                $totalCategories += $count;
            }

            $row['Total Categories'] = $totalCategories;
            $exportData[] = $row;
        }

        $NameEvent = str_replace(' ', '_', $wishlistReports->first()->event->name);
        $fileName = 'wishlist_report_' . $NameEvent . '.csv';
        $filePath = 'public/reports/wishlistReports/' . $fileName;

        Excel::store(new WishlistExport($exportData, $allCategories), $filePath);

        return response()->json([
            'success' => true,
            'reportUrl' => asset('storage/reports/wishlistReports/' . $fileName)
        ]);
    }

    public function ticketSoldReport(Request $request) {
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->endOfDay();

        $formattedStartDate = $startDate->format('Y-m-d');
        $formattedEndDate   = $endDate->format('Y-m-d');

        $ticketSoldReports = $this->ticket->where('archive', 1)
                                        ->with('event', 'ticketCategory')
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $soldTickets = $ticketSoldReports->groupBy('event.name')->map(function ($items) {
            return $items->groupBy('ticketCategory.name')->map(function ($categoryItems) {
                return $categoryItems->count();
            });
        });

        $allCategories = $soldTickets->flatMap(function ($categories) {
                        return $categories->keys();
                    })->unique();

        $exportData = [];

        foreach ($soldTickets as $eventName => $categories) {
            $row = ['Event' => $eventName];
            $totalCategories = 0;

            foreach ($allCategories as $category) {
                $count = $categories[$category] ?? '0';
                $row[$category] = $count;
                $totalCategories += $count;
            }

            $row['Total Categories'] = $totalCategories;
    
            $exportData[] = $row;
        }

        $fileName = $this->getFileName('ticket_sold_', $formattedStartDate, $formattedEndDate);
        $filePath = 'public/reports/ticketSoldReports/' . $fileName;

        Excel::store(new TicketsSoldExport($exportData, $allCategories), $filePath);

        return response()->json([
            'success' => true,
            'reportUrl' => asset('storage/reports/ticketSoldReports/' . $fileName)
        ]);
    }

    public function ticketsalesReport(Request $request) {

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->endOfDay();
    
        $formattedStartDate = $startDate->format('Y-m-d');
        $formattedEndDate = $endDate->format('Y-m-d');

        $ticketSaleReports = $this->ticket->where('available_for', 'sale')->whereBetween('created_at', [$startDate, $endDate])->orderBy('created_at', 'desc')->get();

        $fileName = $this->getFileName('ticket_sale', $formattedStartDate, $formattedEndDate);

        $filePath = 'public/reports/ticketSaleReports/' . $fileName;

        Excel::store(new TicketsSaleExport($ticketSaleReports), $filePath);

        return response()->json([
            'success' => true,
            'reportUrl' => asset('storage/reports/ticketSaleReports/' . $fileName)
        ]);

    }

    public function wishlistSubscriptionReports(Request $request)  {

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->endOfDay();

        $formattedStartDate = $startDate->format('Y-m-d');
        $formattedEndDate = $endDate->format('Y-m-d');

        $wishlistSubscriptionReports = $this->transaction->with('user')->whereBetween('created_at', [$startDate, $endDate])->where('status', 'succeeded')->orderBy('created_at', 'desc')->get();
        $fileName = $this->getFileName('wishlist_subscription', $formattedStartDate, $formattedEndDate);

        $filePath = 'public/reports/wishlistSubscriptionReports/' . $fileName;

        Excel::store(new WishlistSubscriptionExport($wishlistSubscriptionReports), $filePath);

        return response()->json([
            'success' => true,
            'reportUrl' => asset('storage/reports/wishlistSubscriptionReports/' . $fileName)
        ]);
    }

    protected function getFileName($reportName, $formattedStartDate, $formattedEndDate) {
        return $reportName.'_'.$formattedStartDate.'_to_'.$formattedEndDate.'.csv';
    }

}

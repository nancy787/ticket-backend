<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WishlistSubscriptionExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $wishlistSubscriptionReports;

    public function __construct($wishlistSubscriptionReports)
    {
        $this->wishlistSubscriptionReports = $wishlistSubscriptionReports;
    }

    public function collection()
    {
        return $this->wishlistSubscriptionReports->map(function ($subscriptionReport) {
            return [
                'Date'        => formatgetDate($subscriptionReport->created_at),
                'Buyer'       => $subscriptionReport->user ? $subscriptionReport->user->name : NULL,
                'Currency'    => $subscriptionReport->currency_type,
                'Price'       => $subscriptionReport->amount ?? '0.00',
                'Expiry Date' => $subscriptionReport->user ? formatgetDate($subscriptionReport->user->subscription_expire_date) : NULL
            ];
        });
    }


    public function headings(): array
    {
        return [
            'Date',
            'Buyer',
            'Currency',
            'Price',
            'Expiry Date'
        ];
    }
}

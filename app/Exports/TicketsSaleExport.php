<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TicketsSaleExport implements FromCollection, WithHeadings
{
    /**
     *@return \Illuminate\Support\Collection
    */

    protected $ticketSaleReports;

    public function __construct($ticketSaleReports)
    {
        $this->ticketSaleReports = $ticketSaleReports;
    }

    public function collection()
    {
        return $this->ticketSaleReports->map(function ($ticket) {
            return [
                'Date'     => formatgetDate($ticket->created_at),
                'Ticket'   => $ticket->ticket_id,
                'Event'    => $ticket->event->name,
                'Category' => $ticket->ticketCategory->name,
                'Seller'   => $ticket->user->name,
                'Buyer'    => $ticket->buyerName->name ?? '',
                'Currency' => $ticket->currency ?? '0',
                'Price'    => $ticket->price ?? '0',
                'Change'   => $ticket->change_fee ?? '0',
                'Fee'      => $ticket->service ?? '0',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date',
            'Ticket',
            'Event',
            'Category',
            'Seller',
            'Buyer',
            'Currency',
            'Price',
            'Change',
            'Fee',
        ];
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketPurchaseReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */

    public $buyerName;
    public $archiveTickets;
    public $transaction;
 
    public function __construct($buyerName, $archiveTickets, $transaction)
    {
        $this->buyerName         = $buyerName;
        $this->archiveTickets    = $archiveTickets;
        $this->transaction       = $transaction;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Purchased',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */

    public function attachments(): array
    {
        return [];
    }

    public function build()
    {
        return $this->view('emails.ticket_purchase_receipt_email')
                    ->subject('Ticket Purchased')
                    ->with([
                        'buyerName'  => $this->buyerName,
                        'sellTicket' => $this->archiveTickets,
                        'transaction'  => $this->transaction,
                    ]);
    }
}

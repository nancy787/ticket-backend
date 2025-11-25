<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketSoldMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sellerName;
    public $archiveTickets;
    public $isStripeConnected;

    /**
     * Create a new message instance.
     */
    public function __construct($archiveTickets, $sellerName, $isStripeConnected)
    {
        $this->archiveTickets = $archiveTickets;
        $this->sellerName = $sellerName;
        $this->isStripeConnected = $isStripeConnected;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Sold',
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

    public function build() {
        $email = $this->view('emails.sold_ticket_mail')
                        ->with([
                            'sellTicket' => $this->archiveTickets,
                            'sellerName'  => $this->sellerName,
                            'isStripeConnected'  => $this->isStripeConnected
                        ]);
        return $email;
    }
}

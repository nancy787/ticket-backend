<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketPurchaseMail extends Mailable
{
    use Queueable, SerializesModels;

    public $archiveTickets;
    public $buyerName;

    /**
     * Create a new message instance.
     */

    public function __construct($archiveTickets, $buyerName)
    {
        $this->archiveTickets = $archiveTickets;
        $this->buyerName = $buyerName;
    }
    /**
     * Get the message envelope.
     */

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Purchase',
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
        $email = $this->view('emails.purchase_ticket_mail')
                      ->with([
                          'sellTicket' => $this->archiveTickets,
                          'buyerName' => $this->buyerName,
                      ]);

        // $pdfs = json_decode($this->soldTicket->pdf, true);

        // if (is_array($pdfs)) {
        //     foreach ($pdfs as $pdfPath) {
        //         $cleanedPath = str_replace('storage/', '', $pdfPath);
        //         $fullPath = storage_path('app/public/' . $cleanedPath);
        //         if ($cleanedPath && file_exists($fullPath)) {
        //             $email->attach($fullPath);
        //         }
        //     }
        // }


        return $email;
    }
}

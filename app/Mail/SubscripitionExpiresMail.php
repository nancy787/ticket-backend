<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscripitionExpiresMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    protected $subscriptionExpiresDate;
    protected $userName;

    public function __construct($subscriptionExpiresDate, $userName)
    {
        $this->subscriptionExpiresDate = $subscriptionExpiresDate;
        $this->userName = $userName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscripition Expires Notice',
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
        $email = $this->view('emails.subscription_expires_email')
                      ->with([
                          'userName' => $this->userName,
                          'subscriptionExpiresDate' => $this->subscriptionExpiresDate,
                      ]);

        return $email;
    }

}

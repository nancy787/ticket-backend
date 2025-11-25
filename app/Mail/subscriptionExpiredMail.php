<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class subscriptionExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    protected $subscriptionExpiredDate;
    protected $userName;

    public function __construct($subscriptionExpiredDate, $userName)
    {
        $this->subscriptionExpiredDate = $subscriptionExpiredDate;
        $this->userName = $userName;
    }

    /**
     * Get the message envelope.
     */

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription Expired Mail',
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
        $email = $this->view('emails.subscription_expired_email')
                      ->with([
                          'userName' => $this->userName,
                          'subscriptionExpiredDate' => $this->subscriptionExpiredDate,
                      ]);

        return $email;
    }
}

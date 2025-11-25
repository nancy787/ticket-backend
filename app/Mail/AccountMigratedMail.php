<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountMigratedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $user;
    public $onboardingLink;

    public function __construct($user, $onboardingLink)
    {
        $this->user = $user;
        $this->onboardingLink = $onboardingLink;
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Migrated Mail',
        );
    }

    public function build()
    {
        return $this->subject('Your Stripe Account Has Been Migrated')
                    ->view('emails.account_migrated')
                    ->with([
                        'user' => $this->user,
                        'onboardingLink' => $this->onboardingLink,
                    ]);
    }
}

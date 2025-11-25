<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResaleTicketNotification extends Notification
{
    use Queueable;

    protected $purchasedTicket;
    protected $authUser;
    protected $admin;
    /**
     * Create a new notification instance.
     */

    public function __construct($purchasedTicket, $authUser, $admin)
    {
        $this->purchasedTicket   = $purchasedTicket;
        $this->authUser = $authUser;
        $this->admin    = $admin;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('A new ticket is resell by '. $this->authUser->name, ($this->authUser->email))
                    ->greeting('Hello, '.$this->admin->name)
                    ->line('A New Ticket #'.$this->purchasedTicket->ticket_id. ' Resell by '. $this->authUser->name.' ('. $this->authUser->email.') '. 'is open for sale')
                    ->action('View Ticket', url('/tickets/edit/' . $this->purchasedTicket->id))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

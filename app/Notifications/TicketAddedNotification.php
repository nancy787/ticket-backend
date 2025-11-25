<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAddedNotification extends Notification
{
    use Queueable;

    protected $ticket;
    protected $authUser;
    protected $admin;
    /**
     * Create a new notification instance.
     */
    public function __construct($ticket, $authUser, $admin)
    {
        $this->ticket   = $ticket;
        $this->authUser = $authUser;
        $this->admin = $admin;
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
                    ->subject('A new ticket is created by '. $this->authUser->name, ($this->authUser->email))
                    ->greeting('Hello, '.$this->admin->name)
                    ->line('A New Ticket #'.$this->ticket->ticket_id. ' Created by '. $this->authUser->name.' ('. $this->authUser->email.') ')
                    ->action('View Ticket', url('/tickets/edit/' . $this->ticket->id))
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

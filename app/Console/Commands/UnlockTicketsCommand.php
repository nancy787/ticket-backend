<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use Carbon\Carbon;

class UnlockTicketsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:unlock-tickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unlock tickets that are locked for more than 3 minutes.';

    /**
     * Execute the console command.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $expiredTickets = Ticket::where('available_for', 'available')->where('locked_until', '<', Carbon::now())->get();
        if ($expiredTickets->isEmpty()) {
            // \Log::info('No expired tickets to unlock.');
            return;
        }

        foreach ($expiredTickets as $ticket) {
            $ticket->locked_until = null;
            $ticket->locked_by_user_id = null;
            $ticket->is_locked = false;
            $ticket->save();
            // \Log::info("Ticket ID {$ticket->id} has been unlocked.");
        }

        // \Log::info('Expired tickets have been unlocked.');
    }
}

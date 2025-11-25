<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class ArchiveTicketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archive:tickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is move those ticktes which is sold';

    /**
     * Execute the console command.
     */
 
    protected $ticket;

    /**
     * Create a new command instance.
     *
     * @param Ticket $ticket
     * @return void
     */

    public function __construct(Ticket $ticket)
    {
        parent::__construct();
        $this->ticket = $ticket;
    }

    public function handle()
    {
        $ticketData = $this->ticket->where('available_for', 'sold')->get();

        if ($ticketData->isNotEmpty()) {
            foreach ($ticketData as $ticket) {
                $ticket->update(['archive' => 1]);
            }
             Log::info('Tickets moved to archive successfully');
            return 'Ticket moved to archive successfully';
        } else {
              Log::info('Tickets moved to archive successfully');
            return 'No Ticket to be archived';
        }
    }
}

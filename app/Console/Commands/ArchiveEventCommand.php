<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use Carbon\Carbon;

class ArchiveEventCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archive-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is move those events which has end date is less than today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $archiveData = Event::where('end_date', '<=', Carbon::now())->get();

        if ($archiveData->isNotEmpty()) {
            foreach ($archiveData as $archive) {
                $archive->update(['archived' => true]);
            }

            return 'Events moved to archive successfully';
        } else {
            return 'No events to be archived';
        }
    }
}

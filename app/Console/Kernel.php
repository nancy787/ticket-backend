<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */

     protected $commands = [
        \App\Console\Commands\ArchiveTicketCommand::class,
        \App\Console\Commands\SubscriptionExpiresCommand::class,
        \App\Console\Commands\subscriptionExpiredCommand::class,
        \App\Console\Commands\UnlockTicketsCommand::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        if (!app()->environment('local')) {
            $schedule->command('archive-event')->everyMinute();
        }
        if(!app()->environment('local')) {
            $schedule->command('subscription-expires')->daily();
        }
        if(!app()->environment('local')) {
            $schedule->command('subscription-expired')->daily();
        }
        if(!app()->environment('local')) {
            $schedule->command('tickets:unlock-tickets')->everyMinute();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

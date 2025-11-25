<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateTransactionsReportForNotTransafferd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate-transactions-report-for-not-transafferd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate reports for transactions that have been made but not sent to the sellers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        $oneWeekAgo = Carbon::now()->subDays(7)->timestamp;
        $now = Carbon::now()->timestamp;
    }
}

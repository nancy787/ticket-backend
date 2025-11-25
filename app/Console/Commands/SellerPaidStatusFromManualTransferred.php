<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\Transfer;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\StripeConnectAccount;
use App\Models\Ticket;

class SellerPaidStatusFromManualTransferred extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seller-paid-status-from-manual-transferred';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update the seller paid status from the manual transfers we have done in stripe connect.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $transfers = Transfer::all(['limit' => 10]);
        foreach ($transfers->data as $transfer) {
            $stripeConnectdAccount = StripeConnectAccount::where('stripe_account_id',  $transfer->destination)->first();
            if(!$stripeConnectdAccount) {
                $this->info('no connected account');
                continue;
            }
            $this->info("Transfer ID: {$transfer->destination}, Amount: {$transfer->amount}, Status: {$transfer->status}");
        }
    }
}

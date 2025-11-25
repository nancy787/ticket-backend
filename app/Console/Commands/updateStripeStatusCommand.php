<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StripeConnectAccount;

class updateStripeStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-stripe-status-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'it will updated stripe account status for temporary';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inactiveAccounts = StripeConnectAccount::with('user')->where('stripe_account_status', 'inactive')->whereNull('temporary_status')->get();
        foreach($inactiveAccounts as $inactiveAccount) {
            $inactiveAccount->update([
                'stripe_account_status' => 'active',
                'temporary_status'     => 'inactive'
            ]);
            if ($inactiveAccount->user) {
                $inactiveAccount->user->update(['is_stripe_connected' => true]);
            }
        }
    }
}

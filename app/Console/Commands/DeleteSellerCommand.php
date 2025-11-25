<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\Account;
use App\Models\StripeConnectAccount;

class DeleteSellerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete-seller-command {accountId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It will delete users stripe accoun';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountId = $this->argument('accountId');
        try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $account = Account::retrieve($accountId);
            $stripeConnectedAccount = StripeConnectAccount::with('user')->where('stripe_account_id', $accountId)->first();
            if($stripeConnectedAccount) {
                if ($stripeConnectedAccount->user) {
                    $stripeConnectedAccount->user->update([
                        'is_stripe_connected' => false
                    ]);
                }
                $stripeConnectedAccount->delete();             
            }
            $account->delete();
            $this->info("Connected account {$accountId} deleted successfully.");
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StripeConnectAccount;
use Stripe\Account;
use Stripe\Stripe;
use Illuminate\Support\Facades\Log;

class CheckStripeAccountStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check-stripe-account-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'it will updated stripe account status for enabled and completed accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting Stripe account status check.');
        
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    
        $inactiveAccounts = StripeConnectAccount::with('user')->where('stripe_account_status', 'inactive')->get();

        Log::info('Found ' . $inactiveAccounts->count() . ' inactive Stripe accounts to check.');

        foreach ($inactiveAccounts as $inactiveAccount) {
            if (!$inactiveAccount->stripe_account_id) {
                Log::warning("Skipping user ID: {$inactiveAccount->user_id} - No Stripe Account ID found.");
                continue;
            }

            try {
                Log::info("Checking Stripe account: {$inactiveAccount->stripe_account_id}");

                $stripeAccount = Account::retrieve($inactiveAccount->stripe_account_id);

                // Log Stripe account details for debugging
                Log::info("Stripe account details: submitted={$stripeAccount->details_submitted}, charges_enabled={$stripeAccount->charges_enabled}, payouts_enabled={$stripeAccount->payouts_enabled}");

                // Check if the account is fully enabled or partially active
                if ($stripeAccount->details_submitted &&
                    $stripeAccount->charges_enabled &&
                    $stripeAccount->payouts_enabled) {

                    Log::info("Stripe account ID {$inactiveAccount->stripe_account_id} is fully active.");

                    $inactiveAccount->update([
                        'stripe_account_status' => 'active',
                    ]);

                    if ($inactiveAccount->user) {
                        $inactiveAccount->user->update(['is_stripe_connected' => true]);
                        Log::info("Updated user ID: {$inactiveAccount->user->id}, set is_stripe_connected to true.");
                    }
                }
                elseif ($stripeAccount->details_submitted && ($stripeAccount->charges_enabled || $stripeAccount->payouts_enabled)) {
                    Log::info("Stripe account ID {$inactiveAccount->stripe_account_id} is partially active.");
    
                    $inactiveAccount->update([
                        'stripe_account_status' => 'active',
                    ]);
                }
                else {
                    Log::info("Stripe account ID: {$inactiveAccount->stripe_account_id} is not fully enabled.");
                }
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                Log::error("Invalid Stripe Account ID: {$inactiveAccount->stripe_account_id} - " . $e->getMessage());
            } catch (\Exception $e) {
                Log::error("Stripe account retrieval failed for ID: {$inactiveAccount->stripe_account_id}. Error: " . $e->getMessage());
            }
        }
    
        Log::info('Stripe account status check completed.');
        return 0;
    }
}

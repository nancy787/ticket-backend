<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Transfer;
use App\Models\User;
use App\Models\StripeConnectAccount;
use App\Mail\AccountMigratedMail;
use Illuminate\Support\Facades\Mail;

class MigrateCustomToExpressCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-custom-to-express-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing Stripe Custom accounts to Express accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $connectedAccounts = StripeConnectAccount::whereNotNull('stripe_account_id')->get();

        if ($connectedAccounts->isEmpty()) {
            $this->info("No connected accounts found in the database.");
            return;
        }

        $this->info(count($connectedAccounts) . " connected accounts found in database.");
    
        foreach ($connectedAccounts as $record) {
            $customAccountId = $record->stripe_account_id;
    
            try {
                // Step 1: Verify if the account exists on Stripe
                $account = Account::retrieve($customAccountId);
                if (!$account || $account->type !== 'custom') {
                    $this->warn("Skipping: Account {$customAccountId} is not a custom account.");
                    continue;
                }
                $balance = \Stripe\Balance::retrieve(['stripe_account' => $customAccountId]);
                $availableAmount = $balance->available[0]->amount ?? 0;
                if ($availableAmount > 0) {
                    // Skip migration if balance exists
                    $this->warn("Skipping migration for {$customAccountId}: Balance available ($availableAmount)");
                    // Mark account as pending migration
                    $record->update(['temporary_status' => 'pending_migration']);
                    continue;
                }

                $newAccount = Account::create([
                    'type' => 'express',
                    'email' => $record->user->email,
                    'country' => $account->country ?? 'US',
                    'business_type' => 'individual',
                    'business_profile' => [
                        'url' => env('APP_URL'),
                        'mcc' => env('MCC', 5734),
                    ],
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers' => ['requested' => true],
                    ],
                    'settings' => [
                        'payouts' => [
                            'schedule' => ['interval' => 'manual']
                        ]
                    ],
                ]);
    
                $this->info("New Express Account Created: " . $newAccount->id);
    
                // Step 3: Generate an onboarding link for the new account
                $returnUrl = url('api/account/return') . '?account_id=' . $newAccount->id;
                $accountLink = AccountLink::create([
                    'account' => $newAccount->id,
                    'refresh_url' => url('/account/refresh'),
                    'return_url' => $returnUrl,
                    'type' => 'account_onboarding',
                ]);
    
                $this->info("Onboarding Link: " . $accountLink->url);
    
                // Step 4: Update the database with the new Express Account ID
                $record->update([
                    'stripe_account_id' => $newAccount->id,
                    'stripe_account_status' => 'inactive',
                    'temporary_status' => 'migrated'
                ]);
    
                // Step 5: Send an email to the user
                if ($record->user) {
                    Mail::to($record->user->email)->send(new AccountMigratedMail($record->user, $accountLink->url));
                    $this->info("Migration email sent to: " . $record->user->email);
                } else {
                    $this->warn("No associated user found for account: " . $newAccount->id);
                }
    
            } catch (\Exception $e) {
                $this->error("Error migrating account {$customAccountId}: " . $e->getMessage());
            }
        }

        $this->info('Migration process completed.');
    }
}

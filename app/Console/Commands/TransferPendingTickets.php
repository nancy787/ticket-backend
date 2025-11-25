<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use Stripe\Stripe;
use Stripe\Transfer;
use App\Models\Transaction;
use Stripe\Balance;
use App\Models\ConditionalFee;

class TransferPendingTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:transfer-pending-tickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer payments for tickets that have not yet been sent to Stripe Connected Accounts';

    /**
     * Execute the console command.
     */

     public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        $this->info("Fetching tickets and checking pending transfers...");
        $tickets = Ticket::where('available_for', 'sold')->where('created_at', '>=', now()->subDays(7))->get();

        if ($tickets->isEmpty()) {
            $this->info("No tickets found for the last 7 days.");
            return;
        }

        foreach ($tickets as $key => $ticket) {
            $transaction = Transaction::where('ticket_id', $ticket->id)
                                                ->where('status', 'succeeded')
                                                ->where('type', 'ticket purchased')
                                                ->whereNotNull('stripe_payment_intent_id')
                                                ->whereNotNull('stripe_connect_account_id')
                                                ->where('is_transfered', 0)
                                                ->latest()
                                                ->first();

            if(!$transaction) {
                $this->info("Skipping Ticket ID {$ticket->id}, already transferred.");
                continue;
            }

            $conditionalAmount = ConditionalFee::where('currency_type', $transaction->currency_type)->first();
            $applicationFeeAmount = $conditionalAmount->application_fee_amount ?? 0;
            $amountInCents = intval($transaction->amount * 100);
            $applicationFeeInCents = intval($applicationFeeAmount);
            $finalAmount = $amountInCents - $applicationFeeInCents;
            if ($finalAmount <= 0) {
                $this->error("Error transferring Ticket ID {$ticket->id}: Transfer amount is too low after fees.");
                continue;
            }

            try {
                $transfer = Transfer::create([
                    'amount'         => $finalAmount,
                    'currency'       => $transaction->currency_type,
                    'destination'    => $transaction->stripe_connect_account_id,
                    'transfer_group' => $transaction->stripe_payment_intent_id, 
                ]);

                $transaction->update(['is_transfered' => true]);
                $ticket->update(['seller_paid' => true]);
            } catch (\Exception $e){
                $this->error("Error transferring Ticket ID {$ticket->id}: " . $e->getMessage());
            }
        }
    }
}

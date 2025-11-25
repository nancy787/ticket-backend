<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Transfer;
use App\Models\Ticket;
use App\Models\Transaction;
use Carbon\Carbon;

class UpdateSellerPaid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-seller-paid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update seller is paid';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $transactions = Transaction::where('status', 'succeeded')->where('type', 'ticket purchased')
                                                                ->whereNotNull('stripe_payment_intent_id')
                                                                ->whereNotNull('stripe_connect_account_id')
                                                                ->whereRaw("DATE_FORMAT(updated_at, '%Y-%m-%d') = ?", [Carbon::today()->format('Y-m-d')])
                                                                ->latest()
                                                                ->get();
        foreach ($transactions as $transaction) {
            $ticket = Ticket::where('id', $transaction->ticket_id)->where('available_for', 'sold')->first();
            if (!$ticket) {
                $this->warn("No valid transaction found for ticket ID: {$ticket->id}");
                continue;
            }
    
            try {
                $paymentIntent = PaymentIntent::retrieve($transaction->stripe_payment_intent_id);
                if ($paymentIntent->status === 'succeeded') {
                    if (!empty($paymentIntent->transfer_data) && isset($paymentIntent->transfer_data->destination)) {
                        $ticket->update(['seller_paid' => true]);
                        $this->info("Payment successfully marked as paid for ticket ID: {$transaction->ticket_id}");
                    } else {
                        $ticket->update(['seller_paid' => false]);
                        $this->warn("Payment for ticket ID: {$transaction->ticket_id} has NOT been transferred to a connected account.");
                    }
                } else {
                    $this->warn("Payment for ticket ID: {$transaction->ticket_id} is not yet successful.");
                }
            } catch (\Exception $e) {
                $this->error("Failed to check payment transfer for ticket ID: {$transaction->ticket_id}. Error: " . $e->getMessage());
            }
        }
    }
    
}

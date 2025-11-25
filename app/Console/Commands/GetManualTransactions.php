<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\Charge;

class GetManualTransactions extends Command
{
    protected $signature = 'stripe:manual-transactions';
    protected $description = 'Fetch all manual transactions (created via Stripe Dashboard)';

    public function handle()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        $manualTransactions = [];
        $lastChargeId = null;

        do {
            $params = ['limit' => 100];
            if ($lastChargeId) {
                $params['starting_after'] = $lastChargeId;
            }

            // Fetch charges
            $charges = Charge::all($params);

            foreach ($charges->data as $charge) {
                if (
                    empty($charge->invoice) &&
                    empty($charge->subscription) &&
                    empty($charge->metadata) &&
                    empty($charge->source)
                ) {
                    $manualTransactions[] = [
                        'ID' => $charge->id,
                        'Amount' => number_format($charge->amount / 100, 2) . ' ' . strtoupper($charge->currency),
                        'Status' => $charge->status,
                        'Created At' => date('Y-m-d H:i:s', $charge->created),
                        'Payment Intent' => $charge->payment_intent ?? 'N/A',
                        'Description' => $charge->description ?? 'N/A',
                        'Receipt URL' => $charge->receipt_url ?? 'N/A',
                    ];
                }
            }

            $lastChargeId = !empty($charges->data) ? end($charges->data)->id : null;

        } while (!empty($lastChargeId) && $charges->has_more);

        // Print output to console instead of returning JSON
        $this->info('Manual transactions retrieved successfully:');
        print_r($manualTransactions); // Display on terminal

        // Log data for debugging
        \Log::info('Manual Transactions:', $manualTransactions);
    }
}

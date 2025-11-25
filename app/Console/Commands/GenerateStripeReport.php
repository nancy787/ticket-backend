<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateStripeReport extends Command
{
    protected $signature = 'stripe:generate-report';
    protected $description = 'Generate a report of completed payments sent to Stripe Connect accounts for the past week';

    public function handle()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        $oneWeekAgo = Carbon::now()->subDays(7)->timestamp;
        $now = Carbon::now()->timestamp;

        $payments = PaymentIntent::all([
            'limit' => 100,
            'created' => [
                'gte' => $oneWeekAgo,
                'lte' => $now
            ],
        ]);
        $transactions = [];
        foreach ($payments->autoPagingIterator() as $payment) {
            if ($payment->status === 'succeeded' && isset($payment->transfer_data)) {
                $transactionData = DB::table('transactions')->where('stripe_payment_intent_id', $payment->id)
                                                            ->where('stripe_connect_account_id', $payment->transfer_data->destination)->first();

                if (!$transactionData) {
                    continue;
                }
                $ticket = DB::table('tickets')->where('id', $transactionData->ticket_id)->first();

                $sellerData = $ticket ? DB::table('users')->select('name', 'email')->where('id', $ticket->created_by)->first() : 'N/A';
                $buyerData = $ticket ? DB::table('users')->select('name', 'email')->where('id', $transactionData->purchased_by)->first() : 'N/A';

                $transactions[] = [
                    'ID' => $payment->id,
                    'Ticket' => $ticket->ticket_id,
                    'Amount' => number_format($payment->amount / 100, 2),
                    'Currency' => strtoupper($payment->currency),
                    'Destination_Account' => $payment->transfer_data->destination,
                    'Created_Date' => date('Y-m-d H:i:s', $payment->created),
                    'Seller'       => $sellerData->name,
                    'Seller email' => $sellerData->email,
                    'Buyer'         => $buyerData->name,
                    'Buyer'         => $buyerData->email
                ];
            }
        }

        if (empty($transactions)) {
            $this->warn("No transactions found for the past week.");
            return;
        }

        $filePath = 'stripe_transactions_one_week' . date('Ymd_His') . '.csv';
        $csvFile = Storage::disk('local')->path($filePath);
        $handle = fopen($csvFile, 'w');
        fputcsv($handle, array_keys($transactions[0]));

        foreach ($transactions as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->info("Report generated: " . $csvFile);
    }
}


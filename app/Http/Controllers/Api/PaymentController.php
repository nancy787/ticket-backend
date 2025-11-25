<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use App\Models\PaymentDetail;
use App\Models\Transaction;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentReceiptEmail;
use App\Traits\StoresEmailRecords;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Models\Country;
use App\Mail\TicketPurchaseReceiptMail;
use App\Mail\TicketSoldMail;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use App\Services\FreshChatService;
use App\Models\FreshChatMessage;
use App\Models\StripeConnectAccount;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;
use Stripe\AccountLink;
use App\Models\ConditionalFee;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Transfer;
use Stripe\Refund;
use Stripe\Dispute;
use Stripe\Charge;
use Illuminate\Support\Facades\Cache;

class PaymentController extends Controller
{
    use StoresEmailRecords;
    protected $client;
    protected $baseUrl;
    protected $ticket;
    protected $user;
    protected $freshChatService;
    protected $freshchatMessage;
    protected $stripeConnectAccount;

    public function __construct(Ticket $ticket, User $user, FreshChatService $freshChatService, FreshChatMessage $freshchatMessage, StripeConnectAccount $stripeConnectAccount)
    {
        $this->stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
        $this->client = new Client();
        $this->baseUrl = env('REVOLUL_PAYMENT_URL');
        $this->ticket  = $ticket;
        $this->user    = $user;
        $this->freshChatService = $freshChatService;
        $this->freshchatMessage = $freshchatMessage;
        $this->stripeConnectAccount = $stripeConnectAccount;
    }

    public function createPaymentIntent(request $request) {

        try {
            $user = Auth::user();
            $amount = env('AMOUNT', 3500);

            $randomNumber = str_pad(random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $randomAlphanumeric = Str::random(14);
            $transactionId = $randomNumber . '_' . $randomAlphanumeric;
            $couponCode   = $request->coupon_code ?? null;

            $customer = $this->stripe->customers->create([
                'email' => $user->email,
                'name'  => $user->name,
            ]);

            if ($couponCode) {
                $coupon = $this->stripe->coupons->retrieve($couponCode, []);
                if ($coupon) {
                    $discountAmount = $this->calculateDiscount($amount, $coupon);
                    $amount -= $discountAmount;
                } else {
                    return response()->json(['error' => 'Invalid coupon code'], 400);
                }
            }

            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount,
                'currency' => 'eur',
                'payment_method_types' => ['card'],
                'customer' => $customer->id,
                'description' => 'Wishlist Subscription'
            ]);

            $transaction = Transaction::create([
                'transaction_id' => $transactionId,
                'user_id' => $user->id,
                'amount'  => 35,
                'status'  => 'pending',
                'stripe_payment_intent_id' => $paymentIntent->id,
                'currency_type'   => 'EUR',
                'coupon_code'     => $couponCode,
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ],200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function confirmPayment(Request $request)
    {
        try {
            $user = Auth::user();
            $paymentIntent = $this->stripe->paymentIntents->retrieve($request->payment_intent_id);
            $transaction = Transaction::where('stripe_payment_intent_id', $request->payment_intent_id)->first();

            if (!$transaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            switch ($paymentIntent->status) {
                case 'succeeded':
                    $transaction->update([
                        'status' => 'succeeded',
                        'type'   => 'wishlist-subscription'
                    ]);

                    $this->updateUserSubscription($user, $transaction);

                    return response()->json(['message' => 'Payment successful'], 200);

                case 'requires_payment_method':
                        $transaction->update([
                            'status' => 'failed'
                        ]);
                        $this->stripe->paymentIntents->cancel($request->payment_intent_id);

                    return response()->json(['message' => 'Payment requires a new payment method'], 400);

                case 'requires_confirmation':
                    $transaction->update([
                        'status' => 'requires_confirmation'
                    ]);

                    return response()->json(['message' => 'Payment requires confirmation'], 400);

                case 'requires_action':
                    $transaction->update([
                        'status' => 'requires_action'
                    ]);

                    return response()->json(['message' => 'Payment requires additional action'], 400);
                    case 'pending':
                        $transaction->update([
                            'status' => 'failed'
                        ]);

                        return response()->json(['message' => 'Payment pending'], 400);
                case 'canceled':
                    $transaction->update([
                        'status' => 'canceled'
                    ]);

                    return response()->json(['message' => 'Payment was canceled'], 400);

                default:
                    $transaction->update([
                        'status' => 'failed'
                    ]);

                    return response()->json(['message' => 'Payment not completed'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function updateUserSubscription($user, $transaction)
    {
        $now = now();
        if ($user->subscription_expire_date) {
            $currentExpiration = Carbon::parse($user->subscription_expire_date);
            $subscriptionEnd = $currentExpiration->addMonths(12);
        } else {
            $subscriptionEnd = $now->addMonths(12);
        }

        $user->update([
            'is_premium' => true,
            'subscription_expire_date' => $subscriptionEnd,
            'is_subscribed'    => true
        ]);

        $mailInstance = new PaymentReceiptEmail($user, $transaction);
        Mail::to($user->email)->send($mailInstance);
    }

    public function handleWebhook(Request $request)
    {
        $event = $request->getContent();
        try {
            $event = json_decode($event, true);
            if ($event['type'] === 'payment_intent.succeeded') {
                $paymentIntent = $event['data']['object'];
                $transaction = Transaction::where('stripe_payment_intent_id', $paymentIntent['id'])->first();
                if ($transaction) {
                    $transaction->update(['status' => 'succeeded']);
                }
            }

            if ($event['type'] === 'payment_intent.payment_failed') {
                $paymentIntent = $event['data']['object'];
                $transaction = Transaction::where('stripe_payment_intent_id', $paymentIntent['id'])->first();
                if ($transaction) {
                    $transaction->update(['status' => 'failed']);
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getTransactionHistory(Request $request)
    {
        $userId = Auth::user()->id;

        $transactions = Transaction::where('user_id', $userId)
                                    ->whereNotNull('transaction_id')
                                    ->where('status', '!=', 'pending')
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        return response()->json($transactions);
    }

    public function handleApplePay(Request $request)
    {
        try {
            $user = Auth::user();
            $receipt = $request->input('receipt');

            if (empty($receipt)) {
                return response()->json(['error' => 'Receipt data is required'], 400);
            }

            $response = \Http::post(env('IN_APP_PURCHASE'), [
                'receipt-data' => $receipt,
                'password' => env('SHARED_SECRET_KEY'),
            ]);

            if ($response->successful()) {
                $transaction = Transaction::create([
                    'transaction_id' => $request->transactionId,
                    'user_id'       => $user->id,
                    'amount'  => 15,
                    'currency_type' => 'EUR',
                    'status'  => 'succeeded',
                    'type'   => 'wishlist-subscription',
                ]);

                $this->updateUserSubscription($user, $transaction);

                return response()->json([
                    'message' => 'Payment successful',
                    'status' => 'verified',
                ], 200);
            }

            Log::error('Apple Pay verification failed', [
                'response' => $response->body(),
                'transactionId' => $request->transactionId,
            ]);

            $transaction = Transaction::create([
                'transaction_id' => $request->transactionId,
                'user_id' => $user->id,
                'amount'  => 15,
                'currency_type' => 'EUR',
                'status'  => 'failed',
            ]);

            return response()->json(['status' => 'unverified', 'details' => $response->body()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function buyTicket(request $request) {
      try {
            $user               = Auth::user();
            $amount             = $request->amount * 100; //in cent
            $currency           = Country::where('name', $request->country)->where('currency_sign', $request->currency)->value('currency_type') ?? 'USD';
            $randomNumber       = str_pad(random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $randomAlphanumeric = Str::random(14);
            $transactionId      = $randomNumber . '_' . $randomAlphanumeric;
            $ticketId           = $request->ticket_id ?? '';
            $couponCode         = $request->coupon_code ?? null;
            $ticket             = $this->ticket->where('ticket_id', $ticketId)->first();

            if (!$ticket || $ticket->available_for !== 'available') {
                Log::info('The ticket is not available: ' . $ticketId);
                return response()->json(['error' => 'The ticket is not available'], 404);
            }

            $paymentCheckResponse = $this->checkPaymentStatus($ticketId, $ticket->id);
            if ($paymentCheckResponse) {
                return $paymentCheckResponse;
            }

            Log::info('Confirm payment for ticket ID: ' . $ticketId);

            $getTicketTransaction = Transaction::where('ticket_id', $ticket->id)
                                    ->where('status', 'succeeded')
                                    ->where('type', 'ticket purchased')
                                    ->first();

            if($getTicketTransaction) {
                Log::info('The ticket is already paid: ' . $ticketId);
                return response()->json(['error' => 'The ticket is already paid'], 404);
            }

            if (bccomp((string) $ticket->total, (string) ($amount / 100), 2) !== 0) {
                Log::info('Amount does not match ticket total ' . $ticketId);
                return response()->json(['error' => 'Amount does not match ticket total'], 400);
            }
            $maxLimits = [
                'DKK' => 4000 * 100,
                'SEK' => 4000 * 100,
                'NOK' => 4000 * 100,
                'NEK' => 4000 * 100,
                'EUR' => 600 * 100,
                'GBP' => 600 * 100,
                'ZL'  => 2000 * 100,
                'USD' => 700 * 100,
                'AUD' => 700 * 100,
            ];

            if (isset($maxLimits[$currency]) && $amount > $maxLimits[$currency]) {
                return response()->json(['error' => 'The amount exceeds the allowed limit for ' . $currency], 400);
            }

            $customer = $this->stripe->customers->create([
                'email' => $user->email,
                'name'  => $user->name,
                'metadata' => [
                    'user_id'        => $user->id,
                    'ticket_id'      => $ticketId,
                    'transaction_id' => $transactionId,
                    'country'        => $user->country,
                    'ticketId'       => $ticket->id
                ],
            ]);

            if ($couponCode) {
                $coupon = $this->stripe->coupons->retrieve($couponCode, []);
                if ($coupon) {
                    $discountAmount = $this->calculateDiscount($amount, $coupon);
                    $amount -= $discountAmount;
                } else {
                    return response()->json(['error' => 'Invalid coupon code'], 400);
                }
            }

            $price = $this->stripe->prices->create([
                'unit_amount' => $amount,
                'currency'    => $currency,
                'product_data' => [
                    'name' => 'Ticket Purchase #' . $ticketId,
                ],
            ]);

            $checkoutSession = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
               'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' => 'Ticket Purchase',
                                'description'   => 'A payment is generated for '.'#'.$ticketId.' by user '.$user->email,
                            ],
                            'unit_amount' => $amount
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'metadata' => [
                    'user_id'        => $user->id,
                    'ticket_id'      => $ticketId,
                    'transaction_id' => $transactionId,
                    'description'   => 'A payment is generated for '.'#'.$ticketId.'by user '.$user->email,
                ],
                'customer'     => $customer->id,
                'currency'     => $currency,
                'success_url'  => route('payment.success'),
                'cancel_url'   => route('payment.failed'),
            ]);
            Log::info('Checkout session ' . $ticketId);
            DB::beginTransaction();
            try {
                $transaction = Transaction::create([
                    'transaction_id'            => $transactionId,
                    'user_id'                   => $user->id,
                    'amount'                    => $amount/100,
                    'status'                    => 'pending',
                    'stripe_payment_link_url'   => $checkoutSession->url,
                    'currency_type'             => $currency,
                    'coupon_code'               => $couponCode,
                    'description'               => 'A payment is generated for'.'# '.$ticketId.'by user '.$user->email,
                    'purchased_by'              => $user->id,
                    'stripe_payment_link_id'    => $checkoutSession->id,
                    'ticket_id'                 => $ticket->id
                ]);
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
            return response()->json([
                'paymentLink' => $checkoutSession->url,
                'sessionId'   => $checkoutSession->id,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createPayment(Request $request)
    {
        try {
            $apiKey   = env('REVOLUT_API_KEY');
            $endpoint = "{$this->baseUrl}/orders";
            $user     = Auth::user();
            $ticketId = $request->ticket_id ?? '';

            $currencyType = Country::where('name', $request->country)
                                   ->where('currency_sign', $request->currency)
                                   ->value('currency_type') ?? 'USD';

            $validated = $request->validate(['amount' => 'required|numeric|min:1']);
            $amount = $validated['amount'] * 100;

            $payload = [
                'amount'        => $amount,
                'currency'      => $currencyType,
                'description'   => '#'.$ticketId.' Ticket purchased by '.$user->email,
                'metadata'      => [
                    'buyer_id'           => $user->id,
                    'byyer_email'        => $user->email,
                    'buyer_name'         => $user->name,
                    'buyer_country'      =>  $user->country,
                    'buyer_nationality'  =>  $user->nationality,
                    'buyer_address'      =>  $user->address,
                    'transaction_type'   => 'ticket purchase'
                ],
            ];

            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer $apiKey",
                    'Content-Type' => 'application/json',
                    'Revolut-Api-Version' => env('REVOLUT_API_VERSION'),
                ],
                'json' => $payload
            ]);

            $data = json_decode($response->getBody(), true);

            $transaction = Transaction::create([
                'user_id'        => $user->id,
                'transaction_id' => $data['id'],
                'amount'         => $validated['amount'],
                'status'         => 'pending',
                'currency_type'  => $currencyType,
                'type'           => 'ticket purchase'
            ]);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function paymentSuccess(Request $request) {
        try {
            $apiKey          = env('REVOLUT_API_KEY');
            $orderId         = $request->order_id;
            $ticketId        = $request->ticket_id;

            $transaction = Transaction::where('transaction_id', $orderId)->first();
            $user = Auth::user();

            if (!$transaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            $transactionId = $transaction->transaction_id;
            $endpoint      = "{$this->baseUrl}/orders/{$transactionId}/payments";

            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer $apiKey",
                    'Content-Type' => 'application/json',
                    'Revolut-Api-Version' => env('REVOLUT_API_VERSION'),
                ]
            ]);

          $paymentResponse = json_decode($response->getBody(), true);
          $transactionStatus = $this->updateTransaction($transactionId);
          $ticket = $this->ticket->find($ticketId);

            foreach ($paymentResponse as $payment) {
                if (isset($payment['state']) && ($payment['state'] == 'captured' || $payment['state'] == 'completed')) {
                    $this->updateTicketStatus($ticketId);
                    if (!$ticket->multiple_tickets) {
                        $this->sendMail($ticketId, $user, $transaction);
                    }
                    break;
                }
            }

            return response()->json([
                'message' => 'success',
                'paymentResponse' => $paymentResponse,
                'transactionStatus' => $transactionStatus,
                'multiple_tickets'  => $ticket->multiple_tickets,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function updateTransaction($transactionId) {
        try {
            $apiKey = env('REVOLUT_API_KEY');
            $endpoint = "{$this->baseUrl}/orders/{$transactionId}";

            $transaction = Transaction::where('transaction_id', $transactionId)->first();

            if (!$transaction) {
                throw new \Exception('Transaction not found');
            }

            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer $apiKey",
                    'Content-Type' => 'application/json',
                    'Revolut-Api-Version' => env('REVOLUT_API_VERSION'),
                ]
            ]);

            $statusData = json_decode($response->getBody(), true);

            $transactionStatus = $statusData['payments'][0]['state'] ?? 'pending';

            if ($statusData['state'] == 'completed') {
                $transaction->update(['status' => 'succeeded']);
            } else {
                $transaction->update(['status' => $transactionStatus]);
            }

            return $transactionStatus;

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function sendMail($ticketId, $user, $transaction) {

        $archiveTickets = $this->ticket->findorFail($ticketId);
        $buyerId        = $user->id;
        $buyerName      = $user->name;
        $buyerEmail     = $user->email;
        $sellerEmail    = $archiveTickets->user->email;
        $sellerName     = $archiveTickets->user->name;
        $sellerId       = $archiveTickets->user->id;
        $isStripeConnected = $archiveTickets->user->is_stripe_connected;
        $mailInstance       = new TicketPurchaseReceiptMail($buyerName, $archiveTickets, $transaction);
        $mailInstanceSold   = new TicketSoldMail($archiveTickets, $sellerName, $isStripeConnected);
        Mail::to($buyerEmail)->send($mailInstance);
        Mail::to($sellerEmail)->send($mailInstanceSold);
        $this->storeEmailRecord($buyerId, env('MAIL_FROM_ADDRESS'), $buyerEmail, $mailInstance);
        $this->storeEmailRecord($sellerId, env('MAIL_FROM_ADDRESS'), $sellerEmail, $mailInstanceSold);

        return;
    }

    private function updateTicketStatus($ticketId) {

        $user = Auth::user();
        $ticket = $this->ticket->find($ticketId);

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket not found',
            ], 404);
        }
        $updateData = [
            'buyer' => $user->id,
            'available_for' => 'sold',
        ];

        if (!$ticket->multiple_tickets) {
            $updateData['archive'] = 1;
            $updateData['resale'] = 1;
        }

        $ticket->update($updateData);
        return response()->json([
            'message' => 'Ticket status updated successfully',
        ], 200);
    }

    public function ticketConfirmPayment(Request $request)
    {
        try {
            $user = Auth::user();
            $checkoutSessionId = $request->session_id;
            $transaction = Transaction::where('stripe_payment_link_id', $checkoutSessionId)->first(); // Look up the transaction by Checkout Session ID
            $ticketId = $request->ticket_id ?? '';
            $ticket = $this->ticket->find($ticketId);
            if(!$ticket) {
                return response()->json(['error' => 'Ticket not found'], 404);
            }
            if($ticket->available_for == 'sold' && $ticket->buyer == $user->id) {
                return response()->json([
                    'message' => 'Ticket sold Succcessfully',
                    'multiple_tickets' => $ticket->multiple_tickets,
                ], 200);
            }
            if ($ticket->available_for !== 'available') {
                return response()->json(['error' => 'Ticket is already sold or reserved'], 400);
            }
            if (!$transaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            $statusMessage = 'Payment not completed';
            $transactionStatus = 'failed';
            $description = 'Payment for ticket #'.$ticketId.' by user '.$user->email;
            $checkoutSession = $this->stripe->checkout->sessions->retrieve($checkoutSessionId);
            Log::info('checkout session');
            switch ($checkoutSession->payment_status) {
                case 'paid':
                    $transactionStatus = 'succeeded';
                    $statusMessage = 'Payment successful';
                    $this->updateTicketStatus($ticketId);
                    if (!$ticket->multiple_tickets) {
                        $this->sendMail($ticketId, $user, $transaction);
                    }
                    $description = 'Ticket purchase successful: #'.$ticketId.' for user '.$user->email;
                    break;

                case 'unpaid':
                    $statusMessage = 'Payment is incomplete';
                    $description = 'Payment for ticket #'.$ticketId.' is incomplete. User: '.$user->email;
                    break;

                case 'requires_payment_method':
                    $statusMessage = 'Payment failed';
                    $description = 'Payment failed for ticket #'.$ticketId.' by user '.$user->email;
                    break;

                default:
                    $statusMessage = 'Unknown payment status';
                    $description = 'Unknown payment status for ticket #'.$ticketId.' by user '.$user->email;
                    break;
            }

            $updatedSession = $this->stripe->checkout->sessions->update($checkoutSessionId, [
                'metadata' => [
                    'transaction_status' => $transactionStatus,
                    'ticket_id' => $ticketId,
                    'user_email' => $user->email,
                    'description' => 'Ticket #'.$ticketId.' purchased by user '.$user->email, // Updating the description of the session
                ],
            ]);

            $transaction->update([
                'status' => $transactionStatus,
                'type' => $transactionStatus === 'succeeded' ? 'ticket purchased' : $transaction->type,
                'description' => $description,
            ]);

            return response()->json([
                'message' => 'succeess',
                'transactionStatus' => $transactionStatus,
                'multiple_tickets' => $ticket->multiple_tickets,
            ],200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function calculateDiscount($amount, $coupon) {
        if (isset($coupon->percent_off)) {
            return ($amount * $coupon->percent_off) / 100;
        } elseif (isset($coupon->amount_off)) {
            return $coupon->amount_off;
        }
        return 0;
    }

    private function checkPaymentStatus($ticketId, $id) {
        try {
            $events = $this->stripe->events->all([
                'type' => 'checkout.session.completed',
            ]);

            if (empty($events->data)) {
                Log::info('No completed checkout sessions found.');
                return null;
            }

            $filteredEvents = array_filter($events->data, function ($event) use ($ticketId) {
                return isset($event->data->object->metadata['ticket_id']) &&
                       $event->data->object->metadata['ticket_id'] == $ticketId;
            });

            $filteredEvents = array_values($filteredEvents);
            $successfulTransactions = [];

            foreach ($filteredEvents as $event) {
                $session       = $event->data->object;
                $sessionId     = $session->id ?? null;
                $userId        = $session->metadata->user_id ?? null;
                $paymentStatus = $session->payment_status ?? null;
                $customerEmail = $session->customer_details->email ?? '';
                Log::info('Payment Session Details', [
                    'sessionId'     => $sessionId,
                    'userId'        => $userId,
                    'paymentStatus' => $paymentStatus,
                    'customerEmail' => $customerEmail,
                ]);
                if (isset($session->metadata->ticket_id) && $session->metadata->ticket_id == $ticketId) {
                    $successfulTransactions[] = [
                        'session_id'     => $sessionId,
                        'amount_total'   => $session->amount_total / 100, // Convert cents to main currency unit
                        'currency'       => $session->currency,
                        'customer_email' => $customerEmail,
                        'payment_status' => $session->payment_status,
                        'user_id'        => $userId,
                        'ticket_id'      => $session->metadata->ticket_id,
                    ];

                    $checkSuccessTransaction = Transaction::where('ticket_id', $id)
                        ->where('stripe_payment_link_id', $sessionId)
                        ->where('status', 'pending')
                        ->first();

                    if ($checkSuccessTransaction) {
                        $checkSuccessTransaction->update([
                            'status' => $paymentStatus == 'paid' ? 'succeeded' : $paymentStatus,
                            'purchased_by' => $session->metadata->user_id ?? null,
                            'description'  => 'Ticket purchase successful: #' . $ticketId . ' for user ' . $customerEmail,
                            'type' => $paymentStatus === 'paid' ? 'ticket purchased' : $checkSuccessTransaction->type,
                        ]);

                        $ticket = $this->ticket->find($id);
                        $user = User::where('id', $userId)->first();
                      
                        if (!$user) {
                            Log::error('User not found for userId: ' . $userId);
                            return response()->json(['message' => 'User not found.'], 404);
                        }
                        if ($ticket) {
                            $updateData = [
                                'buyer' => $userId,
                                'available_for' => 'sold',
                            ];
                            if (!$ticket->multiple_tickets) {
                                $updateData['archive'] = 1;
                                $updateData['resale'] = 1;
                            }
                            $ticket->update($updateData);

                            if(!$ticket->multiple_tickets && $user){
                                $this->sendMail($ticket->id, $user, $checkSuccessTransaction);
                            }
                        }

                        return response()->json([
                            'message' => 'ticket is already sold',
                            'transactions' => $successfulTransactions,
                        ], 404);
                    }
                }
            }
            Log::info('No matching pending transaction found for ticket ID: ' . $ticketId);
        } catch (\Exception $e) {
            Log::error('Error in checkPaymentStatus', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function checkExistingTransaction($ticketId, $userId) {
        try {
            $existingTransaction = Transaction::where('user_id', $userId)->where('ticket_id', $ticketId)->where('status', 'pending')->first();
            if ($existingTransaction) {
                return response()->json([
                    'paymentLink' => $existingTransaction->stripe_payment_link_url,
                    'sessionId' => $existingTransaction->stripe_payment_link_id,
                ], 200);
            }
           } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
          }
    }

    private function updatePurchasedTicketStatus($id, $userId) {
        try {
            $ticket = $this->ticket->find($id);
            if (!$ticket) {
                return response()->json([
                    'message' => 'Ticket not found',
                ], 404);
            }
            $updateData = [
                'buyer' => $userId,
                'available_for' => 'sold',
            ];
            if (!$ticket->multiple_tickets) {
                $updateData['archive'] = 1;
                $updateData['resale'] = 1;
            }
            $ticket->update($updateData);
            return response()->json([
                'message' => 'Ticket status updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handlePaymentWebhook(Request $request)
    {
        try {
            Log::info('Received payment webhook');
    
            $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');
            $payload = $request->getContent();
            $sig_header = $request->header('Stripe-Signature');
    
            // Validate webhook signature and construct event
            try {
                $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
                Log::info('Webhook event constructed successfully', ['event' => (array) $event]);
            } catch (\UnexpectedValueException $e) {
                Log::error('Invalid payload', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid payload'], 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                Log::error('Invalid signature', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }
    
            // Extract Stripe payment link ID
            $stripePaymentLinkId = $event->data->object->id ?? null;
            if (!$stripePaymentLinkId) {
                Log::error('Stripe payment link ID is missing in the event data');
                return response()->json(['error' => 'Payment link ID missing'], 400);
            }
    
            Log::info('Stripe Payment Link ID', ['stripePaymentLinkId' => $stripePaymentLinkId]);
    
            // Retrieve transaction
            $transaction = Transaction::where('stripe_payment_link_id', $stripePaymentLinkId)->first();
            if (!$transaction) {
                Log::error('Transaction not found for Stripe Payment Link ID', ['stripePaymentLinkId' => $stripePaymentLinkId]);
                return response()->json(['error' => 'Transaction not found'], 404);
            }
            Log::info('Transaction found', ['transaction' => $transaction]);
    
            // Process event type
            switch ($event->type) {
                case 'checkout.session.completed':
                    $transaction->update([
                        'status' => 'succeeded',
                        'type' => 'ticket purchased'
                    ]);
    
                    $ticket = $this->ticket->find($transaction->ticket_id);
                    if ($ticket) {
                        if ($ticket->available_for === 'available') {

                            $updateData = [
                                'available_for' => 'sold',
                                'buyer' => $transaction->user_id,
                            ];
                            if (!$ticket->multiple_tickets) {
                                $updateData['archive'] = 1;
                                $updateData['resale'] = 1;
                            }
                            $ticket->update($updateData);
    
                            if (!$ticket->multiple_tickets) {
                                $user = User::find($transaction->user_id);
                                if ($user) {
                                    $this->sendMail($transaction->ticket_id, $user, $transaction);
                                    if($ticket->user->chat_enabled) {
                                        $this->sendFreshChatMessage(null, $ticket->user->id, null, $ticket->user->email, $ticket);
                                    }
                                } else {
                                    Log::error('User not found for ID', ['user_id' => $transaction->user_id]);
                                }
                            }
                        } else {
                            Log::warning('Ticket is not available for sale', ['ticket_id' => $transaction->ticket_id]);
                        }
                    } else {
                        Log::error('Ticket not found for ID', ['ticket_id' => $transaction->ticket_id]);
                    }
                    return response()->json([
                        'message' => 'Payment successful and ticket updated',
                        'ticket_id' => $transaction->ticket_id,
                        'buyer'     => $transaction->user->name
                    ], 200);
    
                case 'checkout.session.expired':
                    $transaction->update(['status' => 'failed']);
                    return response()->json(['message' => 'Payment session expired'], 400);
    
                case 'payment_intent.succeeded':
                    Log::info('Payment intent succeeded for transaction', ['transaction_id' => $transaction->id]);
                    $transaction->update(['status' => 'payment_intent_succeeded']);
                    break;
    
                case 'payment_intent.created':
                    Log::info('Payment intent created');
                    break;
    
                case 'charge.updated':
                    Log::info('Charge updated');
                    break;
    
                default:
                    Log::warning('Unhandled webhook event type', ['event_type' => $event->type]);
                    $transaction->update(['status' => 'unknown_status']);
            }
    
            return response()->json(['message' => 'Webhook handled successfully'], 200);
    
        } catch (\Exception $e) {
            Log::error('Error processing payment webhook', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function sendFreshChatMessage($senderId = null, $id, $senderEmail = null, $email, $ticket) {
        $userData = $this->user->find($id);
        if(!$userData) {
            return response()->json([
                'message'  => 'user not found'
            ], 404);
        }
        if($userData->is_stripe_connected) {
            $message = "Dear {$userData->name} ,
            Your ticket #{$ticket->ticket_id} for '{$ticket->event->name} {$ticket->ticketCategory->name}' has now sold.
             query in app.
            Thank you for using  Tickets.";
        }else{
            $message = "Dear {$userData->name} ,
            Your ticket #{$ticket->ticket_id} for '{$ticket->event->name} {$ticket->ticketCategory->name}' has now sold..";
        }

        $freshChatUserId =  $this->freshChatService->getUserFromFreshchat($email);

         if(!$freshChatUserId) {
             if($userData) {
                 $email  = $userData->email;
                 $name   = $userData->name;
                 if (strpos($name, ' ') !== false) {
                     [$firstName, $lastName] = explode(' ', $name, 2);
                 } else {
                     $firstName = $name;
                     $lastName = '';
                 }
                 $phoneNumber = $userData->phone_number;
                 $location     = $userData->address;
                 $freshChatUserId  = $this->freshChatService->createUser($firstName, $lastName, $email, $phone, $location);
             }
         }

         $conversationId =  $this->freshChatService->getConversation($freshChatUserId);

         if(!$conversationId) {
            $sendMessage =  $this->freshChatService->CreateConversation($freshChatUserId, $message);
         }else{
             $sendMessage = $this->freshChatService->sendMessage($conversationId, $message, $freshChatUserId);
         }
         $this->storeFreshChatData($id, $email, $freshChatUserId, $conversationId, $message, $ticket->id);
     }

     public function  storeFreshChatData($id, $email, $freshChatUserId, $conversationId, $message, $ticketId) {
        $storeFreshchatData  = $this->freshchatMessage->create([
            'sender_id'                   => null,
            'receiver_id'                 => $id,
            'ticket_id'                   => $ticketId,
            'sender_user_email'           => null,
            'receiver_user_email'         => $email,
            'freschat_user_id'            => $freshChatUserId,
            'freschat_conversation_id'    => $conversationId,
            'message'                     => $message,
            'message_send_from'           => 'payment_success'
        ]);
        return;
    }

    public function createOrUpdateAccount(Request $request)
    {
       try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $ip = request()->ip();
            $countryCode = $request->country_code ?? 'UK';
            $user = Auth::user();
            $stripeAccount = $this->stripeConnectAccount->where('user_id', $user->id)->first();
            if (!$stripeAccount) {
                $account = StripeAccount::create([
                    'type' => 'express',
                    'email' => $user->email,
                    'country' => $countryCode,
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
                $stripeAccount = new StripeConnectAccount();
                $stripeAccount->user_id = $user->id;
                $stripeAccount->stripe_account_id = $account->id;
                $validStatuses = ['completed', 'active', 'enabled'];
                $accountStatus = $account->status;
                $stripeAccount->is_created  = true;
                if (in_array($accountStatus, $validStatuses)) {
                    $stripeAccount->stripe_account_status = 'active';
                } else {
                    $stripeAccount->stripe_account_status = 'inactive';
                }
                $stripeAccount->save();
            } else {
                $updateData = [];
               if (!empty($updateData)) {
                    $updatedAccount = StripeAccount::update($stripeAccount->stripe_account_id, $updateData);
                    $accountStatus = $updatedAccount->stripe_account_status;
                    if (in_array($accountStatus, $validStatuses)) {
                        $stripeAccount->stripe_account_status = 'active';
                    } else {
                        $stripeAccount->stripe_account_status = 'inactive';
                    }
                    $stripeAccount->save();
               }
            }

            if ($stripeAccount->stripe_account_status === 'active') {
                $user->is_stripe_connected = true;
                $user->save();
            }

            $returnUrl = url('api/account/return') . '?account_id=' . $stripeAccount->stripe_account_id;

            $accountLink = AccountLink::create([
                'account' => $stripeAccount->stripe_account_id,
                'refresh_url' => url('/account/refresh'),
                'return_url' => $returnUrl,
                'type' => 'account_onboarding',
            ]);

            $response = [
                'AccountId' => $stripeAccount->stripe_account_id,
                'AccountStatus' => $stripeAccount->stripe_account_status,
                'AccountUrl' => $accountLink->url,
            ];

            if ($stripeAccount->stripe_account_status === 'inactive') {
                $response['AccountUrl'] = $accountLink->url;
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function buyTicketWithConnect(Request $request)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
            $user            = Auth::user();
            $amount          = $request->amount * 100;
            $transactionId   = Str::random(16);
            $ticketId        = $request->ticket_id;

            $ticket          = $this->ticket->where('ticket_id', $ticketId)->first();

            if (!$ticket || $ticket->available_for !== 'available' || $ticket->archive) {
                return response()->json(['error' => 'Ticket is not available'], 404);
            }

            if ($ticket->is_locked && $ticket->locked_by_user_id != $user->id) {
                return response()->json(['error' => 'Ticket is locked by another user'], 403);
            }

            $ticketEvent   = $ticket->event;
            $country       = $ticketEvent->country ?? $request->country;
            $currencySign  = $ticketEvent->currency ?? $request->currency;
            $currency  = Country::where('name', $country)->where('currency_sign', $currencySign)->value('currency_type') ?? 'USD';

            $getTicketTransaction = Transaction::where('ticket_id', $ticket->id)
                                                ->where('status', 'succeeded')
                                                 ->where('type', 'ticket purchased')->first();

            if ($getTicketTransaction) {
                return response()->json(['error' => 'Ticket is already purchesed'], 404);
            }

            Transaction::where('ticket_id', $ticket->id)
                            ->where('status', 'pending')
                            ->where('created_at', '<', now()->subMinutes(3))
                            ->update(['status' => 'expired']);

            $getLockedTicket = Transaction::where('ticket_id', $ticket->id)
                                        ->where('status', 'pending')
                                        ->where('created_at', '>=', now()->subMinutes(3))
                                        ->first();

            if ($getLockedTicket) {
                return response()->json(['error' => 'Ticket is locked for payment'], 404);
            }

            if (bccomp((string)$ticket->total, (string)($amount / 100), 2) !== 0) {
                return response()->json(['error' => 'Amount does not match ticket total'], 400);
            }

            $maxLimits = [
                'DKK' => 4000 * 100,
                'SEK' => 4000 * 100,
                'NOK' => 4000 * 100,
                'NEK' => 4000 * 100,
                'EUR' => 600 * 100,
                'GBP' => 600 * 100,
                'ZL'  => 2000 * 100,
                'USD' => 700 * 100,
                'AUD' => 700 * 100,
            ];

            if (isset($maxLimits[$currency]) && $amount > $maxLimits[$currency]) {
                return response()->json(['error' => 'The amount exceeds the allowed limit for ' . $currency], 400);
            }

            $startTime = strtotime('-1 hour');
            $endOfDay = time();
            $events = $this->stripe->events->all([
                'type' => 'checkout.session.completed',
                'created' => [
                    'gte' => $startTime,
                    'lte' => $endOfDay,
                ],
            ]);

            $filteredEvents = array_filter($events->data, function ($event) use ($ticket) {
                return isset($event->data->object->metadata['ticket_id']) &&
                       $event->data->object->metadata['ticket_id'] == $ticket->id;
            });

            foreach ($filteredEvents as $event) {
                $session       = $event->data->object;
                $sessionId     = $session->id ?? null;
                $userId        = $session->metadata->user_id ?? null;
                $paymentStatus = $session->payment_status ?? null;
                $customerEmail = $session->customer_details->email ?? '';
                Log::info('Payment Session Details', [
                    'sessionId'     => $sessionId,
                    'userId'        => $userId,
                    'paymentStatus' => $paymentStatus,
                    'customerEmail' => $customerEmail,
                ]);

                if (isset($session->metadata->ticket_id) && $session->metadata->ticket_id == $ticket->id) {
                    $successfulTransactions[] = [
                        'session_id'     => $sessionId,
                        'amount_total'   => $session->amount_total / 100,
                        'currency'       => $session->currency,
                        'customer_email' => $customerEmail,
                        'payment_status' => $session->payment_status,
                        'user_id'        => $userId,
                        'ticket_id'      => $session->metadata->ticket_id,
                        'stripe_payment_intent_id' => $session->payment_intent
                    ];
                    $checkSuccessTransaction = Transaction::where('ticket_id', $ticket->id)
                                                            ->where('stripe_payment_link_id', $sessionId)
                                                            ->first();

                    if ($checkSuccessTransaction) {
                     $transactionData =  $checkSuccessTransaction->update([
                            'status' => $paymentStatus == 'paid' ? 'succeeded' : $paymentStatus,
                            'purchased_by' => $session->metadata->user_id ?? null,
                            'description'  => 'Ticket purchase successful: #' . $ticketId . ' for user ' . $customerEmail,
                            'type' => $paymentStatus === 'paid' ? 'ticket purchased' : $checkSuccessTransaction->type,
                        ]);

                        $ticket = $this->ticket->find($ticket->id);
                        if ($ticket->available_for === 'available') {
                             $this->updateTicketStatusData($ticket, $checkSuccessTransaction);
                        }
                        return response()->json([
                            'message' => 'ticket is already sold',
                            'transactions' => $successfulTransactions,
                        ], 404);
                    }
                }
            }

            $seller = $ticket->user;
            if (!$seller) {
                return response()->json(['error' => 'Seller not found'], 400);
            }

            $sellerAccount = $seller->stripeConnect ?? null;
            $conditionalAmount = ConditionalFee::where('currency_type', $currency)->first();
            $applicationFeeAmount = $conditionalAmount->application_fee_amount ?? '0';

            $customer = $this->createOrRetrieveCustomer($user);
            $checkoutSessionData = [
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' => 'Ticket Purchase',
                                'description' => 'Payment for ticket #' . $ticketId,
                            ],
                            'unit_amount' => $amount,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'metadata' => [
                    'user_id'        => $user->id,
                    'ticket_id'      => $ticket->id,
                    'transaction_id' => $transactionId,
                    'ticket_currency' => $currency
                ],
                'customer'      => $customer->id,
                'success_url'   => route('payment.success'),
                'cancel_url'    => route('payment.failed'),
            ];
            $sellerAccountId = isset($sellerAccount->stripe_account_id) && !empty($sellerAccount->stripe_account_id) ? $sellerAccount->stripe_account_id : '';

            if ($sellerAccount && $sellerAccount->stripe_account_status == 'active' && $sellerAccount->temporary_status != 'inactive') {
                if ($applicationFeeAmount > 0) {
                    $checkoutSessionData['payment_intent_data'] = [
                        'on_behalf_of' => $sellerAccount->stripe_account_id,
                        'application_fee_amount' => $applicationFeeAmount,
                        'transfer_data' => [
                            'destination' => $sellerAccount->stripe_account_id,
                        ],
                    ];
                    $checkoutSessionData['metadata']['sellerAccount'] = $sellerAccount->stripe_account_id;
                }
            }

            $checkoutSession = $stripe->checkout->sessions->create($checkoutSessionData);
            $transaction = $this->createOrRetrieveTransaction($checkoutSession->metadata->transaction_id, $ticket, $user, $amount, $currency, $checkoutSession->url, $checkoutSession->id, $sellerAccountId);
            return response()->json([
                'paymentLink' => $checkoutSession->url,
                'sessionId'   => $checkoutSession->id,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handlePaymentWebhookWithConnect(Request $request)
    {
        try {
            Log::info('Received payment webhook');

            $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');
            $payload = $request->getContent();
            $sig_header = $request->header('Stripe-Signature');
            try {
                $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
                Log::info('Webhook event constructed successfully', ['event' => (array) $event]);
            } catch (\UnexpectedValueException $e) {
                Log::error('Invalid payload', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid payload'], 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                Log::error('Invalid signature', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $stripePaymentLinkId = $event->data->object->id ?? null;
            if (!$stripePaymentLinkId) {
                Log::error('Stripe payment link ID is missing in the event data');
                return response()->json(['error' => 'Payment link ID missing'], 400);
            }

            $transaction = Transaction::where('stripe_payment_link_id', $stripePaymentLinkId)->first();
            if (!$transaction) {
                Log::error('Transaction not found for Stripe Payment Link ID', ['stripePaymentLinkId' => $stripePaymentLinkId]);
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;
                    $this->handleCheckoutSessionCompleted($transaction, $session);
                    break;

                case 'checkout.session.expired':
                    $this->handleCheckoutSessionExpired($transaction);
                    break;

                case 'payment_intent.succeeded':
                    Log::info('Payment intent succeeded for transaction', ['transaction_id' => $transaction->id]);
                    $transaction->update(['status' => 'payment_intent_succeeded']);
                    break;

                case 'payment_intent.created':
                    Log::info('Payment intent created');
                    break;

                case 'charge.updated':
                    Log::info('Charge updated');
                    break;

                default:
                    Log::warning('Unhandled webhook event type', ['event_type' => $event->type]);
                    $transaction->update(['status' => 'unknown_status']);
            }

            return response()->json(['message' => 'Webhook handled successfully'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing payment webhook', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createOrRetrieveCustomer($user)
    {
        try {
            $customers = $this->stripe->customers->search([
                'query' => "email:'{$user->email}'",
            ]);

            if (!empty($customers->data)) {
                return $customers->data[0];
            }

            return $this->stripe->customers->create([
                'email'    => $user->email,
                'name'     => $user->name,
                'metadata' => [
                    'user_id'  => $user->id,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating or retrieving customer', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            throw $e;
        }
    }

    private function createOrRetrieveTransaction($transactionId, $ticket, $user, $amount, $currency, $checkoutSessionUrl, $checkoutSessionId, $sellerStripeAccountId)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::Create([
                'user_id' => $user->id,
                'ticket_id' => $ticket->id,
                'transaction_id' => $transactionId,
                'purchased_by' => $user->id,
                'amount' => $amount / 100,
                'currency_type' => $currency,
                'status' => 'pending',
                'type' => null,
                'stripe_payment_link_url' => $checkoutSessionUrl,
                'description' => 'A payment is generated for #'.$ticket->ticket_id.' by user '.$user->email,
                'stripe_payment_link_id' => $checkoutSessionId,
                'stripe_connect_account_id' => $sellerStripeAccountId ?? null,
            ]);

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating transaction', ['error' => $e->getMessage(), 'event' => $event]);
            return null;
        }
    }

    private function handleCheckoutSessionCompleted($transaction, $session)
    {
        $transaction->update([
            'status' => 'succeeded',
            'type' => 'ticket purchased',
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
        ]);

        $ticket = $this->ticket->find($transaction->ticket_id);
       Log::info('handleCheckoutSessionCompleted', ['ticket' => $ticket]);

        if (!$ticket) {
            Log::error('Ticket not found for transaction', ['transaction_id' => $transaction->id]);
            throw new \Exception('Ticket not found');
        }
    
        if ($ticket->available_for === 'available') {
           Log::info('handleCheckoutSessionCompleted');
            $this->updateTicketStatusData($ticket, $transaction);
        } else {
            Log::warning('Ticket is not available for sale', ['ticket_id' => $transaction->ticket_id]);
        }
    }

    private function handleCheckoutSessionExpired($transaction)
    {
        $transaction->update(['status' => 'failed']);
        Log::info('handleCheckoutSessionExpired', ['transaction' => $transaction]);
        Log::info('Checkout session expired', ['transaction_id' => $transaction->id]);
    }

    private function updateTicketStatusData($ticket, $transaction)
    {
        $userId  =  $transaction->user_id;
        $updateData = [
            'available_for' => 'sold',
            'buyer' => $userId,
            'sold_date' => Carbon::now()
        ];

        if (!$ticket->multiple_tickets) {
            $updateData['archive'] = 1;
            $updateData['resale'] = 1;
        }

       $ticket->update($updateData);
       $user = User::find($userId);

       if($ticket->user->is_stripe_connected && $ticket->available_for == 'sold') {
            $this->updateSellerPaid($ticket, $transaction);
       }

       if (!$ticket->multiple_tickets) {
        if ($user) {
            $this->sendMail($ticket->id, $user, $transaction);
            if ($ticket->user->chat_enabled) {
                $this->sendFreshChatMessage(null, $ticket->user->id, null, $ticket->user->email, $ticket);
            }
        } else {
            Log::error('User not found for ticket purchase', ['user_id' => $userId]);
        }
    }
    }

    public function updatePaymentIntentDescription($ticket, $userEmail, $checkoutSessionId)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        try {
            $checkoutSession = Session::retrieve($checkoutSessionId);
            $paymentIntentId = $checkoutSession->payment_intent;
            $paymentIntent =   PaymentIntent::update($paymentIntentId, [
                'description'  => 'A payment is generated for '.'#'.$ticketId.' by user '.$userEmail,
            ]);
            return $paymentIntent;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getSellerSupportedCurrencies($stripeAccountId)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        try {
            $account = StripeAccount::retrieve($stripeAccountId);
            return $account->currencies_supported ?? []; // Returns an array of supported currencies
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function transferFunds($stripeAccountId, $amount, $currency)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $supportedCurrencies = $this->getSellerSupportedCurrencies($stripeAccountId);
        $finalCurrency = is_array($supportedCurrencies) && in_array($currency, $supportedCurrencies) ? $currency : 'usd';
        try {
            $transfer = Transfer::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => $finalCurrency, // Transfer in the correct currency
                'destination' => $stripeAccountId, // Seller's Stripe account
            ]);

            return response()->json(['transfer' => $transfer]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleRefundWebhook(Request $request) {
        try {
            $endpoint_secret = env('STRIPE_REFUND_SECRET');
            $payload = $request->getContent();
            $sig_header = $request->header('Stripe-Signature');

            // Validate webhook signature and construct event
            try {
                $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
                Log::info('Webhook event received', ['event_type' => $event->type, 'event_id' => $event->id]);
            } catch (\UnexpectedValueException $e) {
                Log::error('Invalid payload', ['error' => $e->getMessage(), 'payload' => $payload]);
                return response()->json(['error' => 'Invalid payload'], 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                Log::error('Invalid signature', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            switch ($event->type) {
                case 'charge.refunded':
                    $this->handleRefund($event);
                    break;

                case 'charge.dispute.created':
                    $this->handleDispute($event);
                    break;

                case 'charge.failed':
                    $this->handleTransferFailure($event);
                    break;
    
                default:
                    Log::warning("Unhandled event type", ['event_type' => $event->type]);
            }
        } catch (\Exception $e) {
            Log::error("Webhook processing failed", ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** * Reverse transfer from seller before refunding the buyer */

    public function handleRefund($event) {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $chargeId = $event->data->object->id;
            $transferId = $event->data->object->transfer; // Stripe transfer ID
            $paymentIntentId = $event->data->object->payment_intent; // Payment Intent ID

            Log::info("Processing refund", ['charge_id' => $chargeId, 'transfer_id' => $transferId, 'paymentIntentId' => $paymentIntentId]);

            $charge = Charge::retrieve($chargeId);
            if ($charge->refunded) {
                Log::warning("Charge already refunded", ['charge_id' => $chargeId]);
            } else {
                if ($transferId) {
                    $transfer = Transfer::retrieve($transferId);
                    if (count($transfer->reversals->data) === 0) {
                        $transferReversal = $transfer->reversals->create();
                        Log::info("Transfer reversed successfully", ['transfer_reversal' => (array) $transferReversal]);
                    } else {
                        Log::warning("Transfer already fully reversed", ['transfer_id' => $transferId]);
                    }
                }
                $refund = Refund::create([
                    'charge' => $chargeId,
                ]);
                Log::info("Refund processed successfully", ['refund_id' => $refund->id]);
            }
            // Update database regardless of refund status
            $transaction = Transaction::where('stripe_payment_intent_id', $paymentIntentId)->where('status', 'succeeded')->where('type', 'ticket purchased')->first();
            if ($transaction) {
                $ticketId = $transaction->ticket_id;
                $ticketData = $this->ticket->where('id', $ticketId)->where('available_for', 'sold')->first();
                if ($ticketData) {
                    $ticketData->update([
                        'available_for' => 'available',
                        'archive' => 0,
                        'buyer' => null
                    ]);
                    $transaction->update([
                        'status' => 'refunded',
                        'type' => 'ticket refund'
                    ]);
                }
                Log::info("Transaction updated successfully", ['transaction_id' => $transaction->id, 'new_status' => 'refunded']);
            } else {
                Log::warning("No matching transaction found", ['payment_intent_id' => $paymentIntentId]);
            }
        } catch (\Exception $e) {
            Log::error("Refund processing failed", ['error' => $e->getMessage()]);
        }
    }

    /** * Handle disputes (chargebacks) */
    public function handleDispute($event) {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

            $dispute = $event->data->object;
            $chargeId = $dispute->charge;
            $paymentIntentId = $dispute->payment_intent;

            Log::info("Dispute detected", ['dispute_id' => $dispute->id, 'charge_id' => $chargeId, 'payment_intent_id' => $paymentIntentId]);

            if ($dispute->status == 'needs_response') {
                $refund = Refund::create([
                    'charge' => $chargeId,
                ]);
                Log::info("Charge refunded due to dispute", ['refund_id' => $refund->id]);

                $transaction = Transaction::where('stripe_payment_intent_id', $paymentIntentId)->where('status', 'succeeded')->where('type', 'ticket purchased')->first();
                if ($transaction) {
                    $ticketId = $transaction->ticket_id;
                    $ticketData = $this->ticket->where('id', $ticketId)->first();
                    if($ticketData) {
                        $ticketData->update([
                            'available_for' => 'available',
                            'archive' => 0,
                            'buyer'  => null,
                        ]);
                    $transaction->update([
                        'status' => 'disputed',
                        'type'   => 'chargeback'
                    ]);
                }
                    Log::info("Transaction updated successfully", ['transaction_id' => $transaction->id, 'new_status' => 'disputed']);
                } else {
                    Log::warning("No matching transaction found for dispute", ['payment_intent_id' => $paymentIntentId]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Dispute handling failed", ['error' => $e->getMessage()]);
        }
    }

    /** * Handle failed transfers (e.g., incorrect bank details) */
    public function handleTransferFailure($event) {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

            $transfer = $event->data->object;
            $paymentIntentId = $transfer->metadata->payment_intent ?? null;

            Log::error("Transfer failed", ['transfer_id' => $transfer->id, 'amount' => $transfer->amount, 'payment_intent_id' => $paymentIntentId]);

            if ($paymentIntentId) {
                $transaction = Transaction::where('stripe_payment_intent_id', $paymentIntentId)->first();
                if ($transaction) {
                    $transaction->update([
                        'status' => 'failed',
                        'type' => 'transfer failure'
                    ]);
                    Log::info("Transaction updated successfully", ['transaction_id' => $transaction->id, 'new_status' => 'failed']);
                } else {
                    Log::warning("No matching transaction found for failed transfer", ['payment_intent_id' => $paymentIntentId]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed transfer handling error", ['error' => $e->getMessage()]);
        }
    }

    public function handleStripeReturn(Request $request)
    {
        try {
            $accountId = $request->input('account_id');

            if (!$accountId) {
                return view('payment.refresh', ['message' => 'Stripe account not found']);
            }

            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $account = StripeAccount::retrieve($accountId);

            if (!$account) {
                return response()->json(['error' => 'Stripe account not found'], 404);
            }

            $validStatuses = ['completed', 'active', 'enabled'];
            $accountStatus = $account->status ?? 'inactive';
            $newStatus = in_array($accountStatus, $validStatuses) ? 'active' : 'inactive';
            $stripeAccount = StripeConnectAccount::where('stripe_account_id', $accountId)->first();

            if ($stripeAccount) {
                $stripeAccount->stripe_account_status = $account->payouts_enabled ? 'active' : 'inactive';
                $stripeAccount->save();

                $user = User::where('id', $stripeAccount->user_id)->first();
                if ($user && $stripeAccount->stripe_account_status === 'active') {
                    $user->is_stripe_connected = true;
                    $user->save();
                }
            }
            return view('payment.return', ['account' => $account]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getCountryCode($ip) {
        if ($ip === '127.0.0.1' || substr($ip, 0, 3) === '192' || substr($ip, 0, 6) === '172.16' || substr($ip, 0, 7) === '10.0.0') {
            $countryCode = 'GB';
        } else {
            $cacheKey = "geoip_{$ip}";
            $countryCode = Cache::remember($cacheKey, now()->addHours(6), function () use ($ip) {
                $response = @file_get_contents("http://ip-api.com/json/{$ip}");
                $data = $response ? json_decode($response, true) : null;
                return $data['countryCode'] ?? 'GB';
            });
        }
       return $countryCode;
    }


    public function getStripeLoginLink(Request $request)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $user = Auth::user();
            $stripeAccount = $this->stripeConnectAccount->where('user_id', $user->id)->first();
            if (!$stripeAccount || !$stripeAccount->stripe_account_id) {
                return response()->json(['error' => 'No connected Stripe account found'], 404);
            }
            $loginLink = StripeAccount::createLoginLink($stripeAccount->stripe_account_id);
            return response()->json(['login_url' => $loginLink->url], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getCountryCodes(Request $request) {
        $countryCodes = Country::select('name', 'stripe_supported_country_type')->whereNotNull('stripe_supported_country_type')->orderBy('name', 'asc')->get();
        return response()->json([
            'countryCodes' => $countryCodes
        ], 200);
    }

    public function getAccountStatus(Request $request) {
        $user = Auth::user();
        $stripeAccount = $this->stripeConnectAccount->where('user_id', $user->id)->first();
        return response()->json([
            'AccountStatus' => optional($stripeAccount)->stripe_account_status ?? 'inactive',
            'is_created' => $stripeAccount ? $stripeAccount->is_created : false
        ], 200);
    }

    private function updateSellerPaid($ticket, $stripePaymentIntentId) {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        try {
            $paymentIntent = PaymentIntent::retrieve($stripePaymentIntentId);
            Log::info($paymentIntent);
            if (!$paymentIntent) {
                return response()->json([
                    'success' => false,
                    'message' => 'PaymentIntent not found'
                ], 404);
            }

            if ($paymentIntent->status === 'succeeded') {
                if (!empty($paymentIntent->transfer_data) && isset($paymentIntent->transfer_data->destination)) {
                    $ticket->update([
                                    'seller_paid' => true,
                                    'is_transfered' => true
                            ]);
                    Log::info("Payment successfully marked as paid for ticket ID: {$transaction->ticket_id}");
                }else{
                    $ticket->update(['seller_paid' => false]);
                    Log::info("Payment for ticket ID: {$transaction->ticket_id} has NOT been transferred to a connected account.");
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

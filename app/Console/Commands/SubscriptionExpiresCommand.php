<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use App\Mail\SubscripitionExpiresMail;
use App\Traits\StoresEmailRecords;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Mail;

class SubscriptionExpiresCommand extends Command
{
    use StoresEmailRecords;

    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        parent::__construct();
        $this->firebaseService = $firebaseService;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'subscription-expires';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify users when their premium subscription is one week from expiring';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $now = Carbon::now()->startOfDay();
        $oneMonthBeforeExpiry = $now->copy()->addWeek();

        $title = 'Subscription expires';
        $body =  'Your premium subscription will expire in less than one month. Please renew it.';

        $usersForReminder = User::whereDate('subscription_expire_date', '<=', $oneMonthBeforeExpiry)
                                ->whereDate('subscription_expire_date', '>', $now)
                                ->get();

        foreach ($usersForReminder as $key => $user) {

            $userId  = $user->id;
            $userEmail = $user->email;
            $userName = $user->name;
            $subscriptionExpiresDate = $user->subscription_expire_date;
            $token = $user->fcm_token;

            $mailInstance = new SubscripitionExpiresMail($subscriptionExpiresDate, $userName);
            Mail::to($userEmail)->send($mailInstance);

            $this->storeEmailRecord($userId, env('MAIL_FROM_ADDRESS'), $userEmail, $mailInstance);

            \Log::info('Subscription expires email sent');

            if($token && $user->notifications_enabled)  {
               $this->firebaseService->sendNotification($token, $title, $body);

                Notification::create([
                    'user_id' => $userId,
                    'title'   => $title,
                    'body'    => $body,
                ]);

                \Log::info('subscription expires notification send to all users');
            }
        }

        \Log::info('subscription expires command send');

        return 0;
    }
}

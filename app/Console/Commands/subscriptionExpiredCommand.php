<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use App\Mail\subscriptionExpiredMail;
use App\Traits\StoresEmailRecords;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Mail;

class subscriptionExpiredCommand extends Command
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
    protected $signature = 'subscription-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify users when their premium subscription is expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now()->startOfDay();

        $title = 'Subscription Expired';
        $body =  'Your subscription is expired';

        $usersForReminder = User::whereDate('subscription_expire_date', '=', $now)->get();

        foreach ($usersForReminder as $key => $user) {

            $user->update([
                'is_premium' => false
            ]);

            $userId  = $user->id;
            $userEmail = $user->email;
            $userName = $user->name;
            $subscriptionExpiredDate = $user->subscription_expire_date;
            $token = $user->fcm_token;

            $mailInstance = new subscriptionExpiredMail($subscriptionExpiredDate, $userName);
            Mail::to($userEmail)->send($mailInstance);

            $this->storeEmailRecord($userId, env('MAIL_FROM_ADDRESS'), $userEmail, $mailInstance);

            \Log::info('Subscription expired email sent');

            if($token && $user->notifications_enabled)  {
               $this->firebaseService->sendNotification($token, $title, $body);

                Notification::create([
                    'user_id' => $userId,
                    'title'   => $title,
                    'body'    => $body,
                ]);

                \Log::info('subscription expired notification send to all users');
            }
        }

        \Log::info('subscription expired command send');

        return 0;
    }
}

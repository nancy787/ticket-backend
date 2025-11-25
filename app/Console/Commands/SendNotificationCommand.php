<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wishlist;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\FirebaseService;
use App\Models\Notification;
use App\Models\User;

class SendNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:wishlist-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this will send notification to the events or categories';

     /**
     * The Firebase service instance.
     *
     * @var FirebaseService
     */

    protected $firebaseService;

     /**
     * Create a new command instance.
     *
     * @param FirebaseService $firebaseService
     * @return void
     */
    public function __construct(FirebaseService $firebaseService)
    {
        parent::__construct();
        $this->firebaseService = $firebaseService;
    }

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $mywishLists = Ticket::select('tickets.*', 'wishlists.*')
                                        ->join('wishlists', 'wishlists.event_id', '=', 'tickets.event_id')
                                        ->where('tickets.available_for', '=','available')
                                        ->orderBy('tickets.category_id')
                                        ->get();

        $wishlistsByUser = $mywishLists->groupBy('user_id');

        foreach ($wishlistsByUser as $userId => $tickets) {
            $latestTicket = $tickets->sortByDesc('created_at')->first();

            if ($latestTicket) {

                $title = "New Ticket Added";
                $body = "A new tickets '{$latestTicket->ticket_id}' has been added to this event";

                $user = User::find($userId);
                $token = $user->fcm_token;

                if ($user) {

                    $this->firebaseService->sendNotification($token, $title, $body);

                    Notification::create([
                        'user_id'        => $user->id,
                        'title'          => $title,
                        'body'           => $body,
                    ]);

                    $this->info("Notification sent and saved for user ID {$user->id}, {$user->name}");
                }
            }
        }

        $this->info('notifications sent successfully.');
        return 0;

    }
}

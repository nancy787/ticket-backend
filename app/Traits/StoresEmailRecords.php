<?php
namespace App\Traits;

use App\Models\Email;
use Illuminate\Support\Facades\Mail;
use Exception;

trait StoresEmailRecords
{
    public function storeEmailRecord($userId, $from, $to, $mailInstance = null)
    {  
        $subject = $mailInstance->subject;
        $content = $mailInstance->render();

        try {
            Email::create([
                'user_id' => $userId,
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'content' => $content,
            ]);
            return true;

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email and store record.'.$e->getMessage(),
            ], 500);
        }

        return true;
    }
}

?>
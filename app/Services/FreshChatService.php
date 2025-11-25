<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FreshChatService
{
    private $appId;
    private $appKey;
    private $apiUrl;

    public function __construct()
    {
        $this->appId  = env('FRESHCHAT_APP_ID');
        $this->appKey = env('FRESHCHAT_API_TOKEN');
        $this->apiUrl = env('FRESHCHAT_URL');
        $this->actorId = env('ACTOR_ID');
        $this->channelId = env('CHANNEL_ID');
    }

    /**
     * Send a message to a user in Freshchat.
     *
     * @param string $userId
     * @param string $message
     * @return array
     */

    public function getUserFromFreshchat($email)
    {
        $url = "{$this->apiUrl}/v2/users?email=" .$email;
        $freshChatUserId = null;
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->appKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['users']) && count($data['users']) > 0) {
                 $freshChatUserId = $data['users'][0]['id'] ?? null;
                }
                return $freshChatUserId;
            } else {
                \Log::error('Error fetching user from Freshchat: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            \Log::error('Exception while fetching user from Freshchat: ' . $e->getMessage());
            return null;
        }
    }

    public function createUser($firstName, $lastName, $email, $phone, $location)
    {
        $url = "{$this->apiUrl}/v2/users";
        $payload = [
            "first_name"  => $firstName,
            "last_name"   => $lastName,
            "email"       => $email,
            "phone"       => $phone,
            "properties" => [
                "name" => [
                    "location" => $location,
                ]
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->appKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $payload);

        return $response->json();
    }

    public function getConversation($freshChatUserId) {
        try {
            $url = "{$this->apiUrl}/v2/users/{$freshChatUserId}/conversations";
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->appKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['conversations'])) {
                    $conversationId = $data['conversations'][0]['id'] ?? null;
                }
            }
            return $conversationId;
        }catch (\Exception $e) {
                \Log::error('Exception while fetching user from Freshchat: ' . $e->getMessage());
                return null;
        }
    }

    public function sendMessage($conversationId, $message, $freshChatUserId)
    {
        try {
            $url = "{$this->apiUrl}/v2/conversations/{$conversationId}/messages";
            $payload = [
                "message_parts" => [
                    [
                        "text" => [
                            "content" => $message,
                        ],
                    ],
                ],
                "message_type"  => "normal",
                "actor_type"    => "agent",
                "user_id"       => $freshChatUserId,
                "actor_id"      => $this->actorId,
            ];
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->appKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, $payload);

            return $response->json();
        }catch (\Exception $e) {
            \Log::error('Exception while fetching user from Freshchat: ' . $e->getMessage());
            return null;
        }
    }

    public function CreateConversation($freshChatUserId, $message) {
        try {
            $url = "{$this->apiUrl}/v2/conversations";
                $payload  = [
                    'channel_id' => $this->channelId,
                    'messages'  => [
                        'message_parts' => [
                            'text' => [
                                'content' => $message
                            ]
                        ],
                        'actor_id' => $this->actorId,
                        'actor_type' => "agent",
                        'user_id'  => $freshChatUserId,
                    ],
                    'app_id' => $this->actorId,
                    'status' =>  "new",
                    'users' => [
                        "id" =>  $freshChatUserId
                    ]
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->appKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post($url, $payload);
            }
            catch (\Exception $e) {
                \Log::error('Exception while fetching user from Freshchat: ' . $e->getMessage());
                return null;
            }
    }
}

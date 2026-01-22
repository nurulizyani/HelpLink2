<?php

namespace App\Helpers;

use Google\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FCMHelper
{
   public static function sendPushNotification(string $token, string $title, string $body, array $data = []): void
{
    if (!$token) {
        \Log::warning('FCMHelper stopped: empty token.');
        return; // kalau user tak ada token
    }

    \Log::info('Sending push to token: ' . $token);

    try {
        // 1️⃣ Load Firebase credentials
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/firebase-key.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        // 2️⃣ Hantar notification ke FCM endpoint
        $projectId = env('FIREBASE_PROJECT_ID');
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => array_merge($data, [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'title' => $title,
                    'body'  => $body,
                ]),
            ],
        ];

        // 3️⃣ Hantar ke Firebase
        $response = \Http::withToken($accessToken)->post($url, $payload);

        if ($response->failed()) {
            \Log::error('FCM Error: ' . $response->body());
        } else {
            \Log::info('FCM Success: ' . $response->body());
        }

    } catch (\Exception $e) {
        \Log::error('FCM Exception: ' . $e->getMessage());
    }
}

}

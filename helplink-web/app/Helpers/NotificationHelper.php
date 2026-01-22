<?php

namespace App\Helpers;

use App\Models\Notification;
use App\Models\User;
use App\Helpers\FCMHelper;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    /**
     * Create and send notification to a user safely.
     *
     * @param int         $userId
     * @param string      $title
     * @param string      $message
     * @param string      $type       system | chat | offer | request
     * @param array       $data       extra payload (conversation_id, offer_id, request_id)
     */
    public static function send(
        $userId,
        $title,
        $message,
        $type = 'system',
        array $data = []
    ) {
        if (!$userId) {
            Log::warning('Notification skipped: user_id is null.');
            return;
        }

        // ===============================
        // 1️⃣ CHECK USER
        // ===============================
        $user = User::find($userId);
        if (!$user) {
            Log::warning("Notification skipped: user {$userId} not found.");
            return;
        }

        try {
            // ===============================
            // 2️⃣ SAVE TO DATABASE
            // ===============================
            Notification::create([
                'user_id' => $user->id,
                'title'   => $title,
                'message' => $message,
                'type'    => $type,
                'data'    => !empty($data) ? json_encode($data) : null,
            ]);

            Log::info("Notification saved for user_id {$userId}: {$title}");

            // ===============================
            // 3️⃣ SEND FCM PUSH
            // ===============================
            if (!empty($user->fcm_token)) {
                Log::info("Sending push to token: {$user->fcm_token}");

                // Merge default + custom data
                $payloadData = array_merge([
                    'type' => $type,
                ], $data);

                FCMHelper::sendPushNotification(
                    $user->fcm_token,
                    $title,
                    $message,
                    $payloadData
                );
            } else {
                Log::warning("User {$userId} has no FCM token.");
            }

        } catch (\Exception $e) {
            Log::error(
                "Notification error for user_id {$userId}: " . $e->getMessage()
            );
        }
    }
}

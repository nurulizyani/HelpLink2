<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();
        Log::info('Telegram webhook received:', $data);

        if (isset($data['message'])) {
            $message = $data['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';

            if (str_starts_with($text, '/start')) {
                $parts = explode(' ', $text);
                if (isset($parts[1])) {
                    $userId = $parts[1];
                    $user = User::find($userId);
                    if ($user) {
                        $user->telegram_id = $chatId;
                        $user->save();

                        $this->sendTelegramMessage($chatId, "✅ Your HelpLink account is now connected to Telegram.");
                    } else {
                        $this->sendTelegramMessage($chatId, "❌ User not found.");
                    }
                } else {
                    $this->sendTelegramMessage($chatId, "❌ Invalid command format. Use /start {user_id}");
                }
            }
        }

        return response('OK', 200);
    }

    private function sendTelegramMessage($chatId, $text)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $response = Http::post($url, [
            'chat_id' => $chatId,
            'text' => $text,
        ]);

        Log::info('Telegram sendMessage response: ' . $response->body());
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    public static function sendMessage($text)
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $response = Http::withOptions(['verify' => false])->post($url, [
    'chat_id' => $chatId,
    'text' => $text,
]);

        return $response->successful();
    }
}

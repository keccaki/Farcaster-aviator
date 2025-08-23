<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramBotService
{
    protected string $apiUrl;

    public function __construct()
    {
        $token = config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";
    }

    /**
     * Send a text message via the Telegram Bot API.
     *
     * @param int $chatId Telegram chat ID
     * @param string $text Message text
     * @param array $options Additional API parameters
     * @return array The API response as an array
     */
    public function sendMessage(int $chatId, string $text, array $options = []): array
    {
        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ], $options);

        $response = Http::post($this->apiUrl . 'sendMessage', $payload);
        return $response->json();
    }
} 
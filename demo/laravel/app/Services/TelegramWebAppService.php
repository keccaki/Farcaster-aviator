<?php

namespace App\Services;

class TelegramWebAppService
{
    protected string $secretKey;

    public function __construct()
    {
        $botToken = config('services.telegram.bot_token');
        $this->secretKey = hash('sha256', $botToken, true);
    }

    /**
     * Verify and parse Telegram Web App initData.
     *
     * @param string $initData Raw initData string
     * @return array Parsed key=>value pairs
     * @throws \InvalidArgumentException on signature failure
     */
    public function verifyInitData(string $initData): array
    {
        $data = [];
        foreach (explode('&', $initData) as $param) {
            [$key, $value] = array_pad(explode('=', $param, 2), 2, '');
            $data[$key] = urldecode($value);
        }

        if (!isset($data['hash'])) {
            throw new \InvalidArgumentException('Missing initData hash');
        }
        $hash = $data['hash'];
        unset($data['hash']);

        ksort($data);
        $dataCheckString = implode("\n", array_map(fn($k) => "{$k}={$data[$k]}", array_keys($data)));

        $hmac = hash_hmac('sha256', $dataCheckString, $this->secretKey, true);
        $hmacHex = bin2hex($hmac);

        if (!hash_equals($hmacHex, $hash)) {
            throw new \InvalidArgumentException('Invalid initData signature');
        }

        return $data;
    }
} 
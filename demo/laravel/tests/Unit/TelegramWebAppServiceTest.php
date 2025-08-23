<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TelegramWebAppService;
use InvalidArgumentException;

class TelegramWebAppServiceTest extends TestCase
{
    public function testVerifyInitDataSuccess()
    {
        config()->set('services.telegram.bot_token', '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11');
        $service = new TelegramWebAppService();

        $data = ['id' => '42', 'user' => 'Alice'];
        ksort($data);
        $dataCheckString = implode("\n", array_map(fn($k) => "{$k}={$data[$k]}", array_keys($data)));
        $secretKey = hash('sha256', config('services.telegram.bot_token'), true);
        $hmac = hash_hmac('sha256', $dataCheckString, $secretKey, true);
        $hashHex = bin2hex($hmac);

        $initData = "user=Alice&id=42&hash={$hashHex}";
        $result = $service->verifyInitData($initData);

        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['user'], $result['user']);
    }

    public function testVerifyInitDataMissingHash()
    {
        $this->expectException(InvalidArgumentException::class);
        config()->set('services.telegram.bot_token', 'token');
        $service = new TelegramWebAppService();
        $service->verifyInitData('foo=bar');
    }

    public function testVerifyInitDataInvalidSignature()
    {
        $this->expectException(InvalidArgumentException::class);
        config()->set('services.telegram.bot_token', 'token');
        $service = new TelegramWebAppService();
        $service->verifyInitData('foo=bar&hash=deadbeef');
    }
} 
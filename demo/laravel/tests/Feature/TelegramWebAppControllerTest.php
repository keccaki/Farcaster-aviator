<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class TelegramWebAppControllerTest extends TestCase
{
    public function testInitDataEndpointSuccess()
    {
        $this->withoutMiddleware();

        $botToken = 'myToken';
        config()->set('services.telegram.bot_token', $botToken);
        $secretKey = hash('sha256', $botToken, true);
        $data = ['id' => '99', 'user' => 'Bob'];
        ksort($data);
        $dataCheckString = implode("\n", array_map(fn($k) => "{$k}={$data[$k]}", array_keys($data)));
        $hmac = hash_hmac('sha256', $dataCheckString, $secretKey, true);
        $hashHex = bin2hex($hmac);

        $initData = "user=Bob&id=99&hash={$hashHex}";

        $response = $this->postJson(route('telegram.initdata'), ['initData' => $initData]);

        $response->assertStatus(200)
                 ->assertJson($data)
                 ->assertSessionHas('telegram_user', $data);
    }

    public function testInitDataEndpointValidationError()
    {
        $this->withoutMiddleware();
        $response = $this->postJson(route('telegram.initdata'), []);
        $response->assertStatus(422);
    }

    public function testSendResultUnauthorized()
    {
        $this->withoutMiddleware();
        $response = $this->postJson(route('telegram.results'), ['crashPoint' => 2.5, 'betAmount' => 100]);
        $response->assertStatus(403)
                 ->assertJson(['error' => 'No Telegram user in session']);
    }

    public function testSendResultSuccess()
    {
        $this->withoutMiddleware();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200)
        ]);

        $sessionData = ['id' => 555];
        $payload = ['crashPoint' => 3.5, 'betAmount' => 123];

        $response = $this->withSession(['telegram_user' => $sessionData])
                         ->postJson(route('telegram.results'), $payload);

        $response->assertStatus(200)
                 ->assertJson(['ok' => true]);
    }
} 
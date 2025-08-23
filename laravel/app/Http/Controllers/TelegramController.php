<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    private $botToken;
    private $providerToken;

    public function __construct()
    {
        $this->botToken = env('TELEGRAM_BOT_TOKEN');
        $this->providerToken = env('TELEGRAM_PAYMENT_PROVIDER_TOKEN');
    }

    /**
     * Validate Telegram WebApp data
     */
    public function validateInitData(Request $request)
    {
        try {
            $initData = $request->input('initData');
            $hash = $request->input('hash');
            
            // Validate the hash using Telegram's algorithm
            $dataCheckString = $this->buildDataCheckString($initData);
            $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
            $calculatedHash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));
            
            return response()->json([
                'valid' => hash_equals($hash, $calculatedHash)
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram validation error: ' . $e->getMessage());
            return response()->json(['error' => 'Validation failed'], 400);
        }
    }

    /**
     * Create a payment invoice
     */
    public function createInvoice(Request $request)
    {
        try {
            $amount = $request->input('amount');
            $userId = $request->input('user_id');
            
            // Create invoice using Telegram Payments API
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/createInvoiceLink", [
                'title' => 'Deposit to Aviator Game',
                'description' => "Add {$amount} USD to your game balance",
                'payload' => json_encode([
                    'user_id' => $userId,
                    'type' => 'deposit',
                    'amount' => $amount
                ]),
                'provider_token' => $this->providerToken,
                'currency' => 'USD',
                'prices' => [[
                    'label' => 'Game Deposit',
                    'amount' => $amount * 100 // Convert to cents
                ]],
            ]);
            
            $result = $response->json();
            
            if (isset($result['ok']) && $result['ok']) {
                return response()->json([
                    'invoice_url' => $result['result']
                ]);
            } else {
                throw new \Exception('Failed to create invoice: ' . json_encode($result));
            }
        } catch (\Exception $e) {
            Log::error('Telegram payment error: ' . $e->getMessage());
            return response()->json(['error' => 'Payment creation failed'], 400);
        }
    }

    /**
     * Handle successful payment notification
     */
    public function handlePaymentSuccess(Request $request)
    {
        try {
            $payload = json_decode($request->input('payload'), true);
            $userId = $payload['user_id'];
            $amount = $payload['amount'];
            
            // Update user's wallet
            $wallet = Wallet::where('user_id', $userId)->first();
            $wallet->balance += $amount;
            $wallet->save();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage());
            return response()->json(['error' => 'Payment processing failed'], 400);
        }
    }

    /**
     * Process withdrawal request
     */
    public function processWithdrawal(Request $request)
    {
        try {
            $amount = $request->input('amount');
            $userId = $request->input('user_id');
            
            // Verify user has sufficient balance
            $wallet = Wallet::where('user_id', $userId)->first();
            
            if ($wallet->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance'
                ], 400);
            }
            
            // Process withdrawal through Telegram
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $userId,
                'text' => "Your withdrawal of {$amount} USD is being processed. You will receive the funds shortly."
            ]);
            
            // Update wallet balance
            $wallet->balance -= $amount;
            $wallet->save();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Withdrawal error: ' . $e->getMessage());
            return response()->json(['error' => 'Withdrawal failed'], 400);
        }
    }

    /**
     * Build data check string for validation
     */
    private function buildDataCheckString($initData)
    {
        $data = [];
        parse_str($initData, $data);
        
        unset($data['hash']);
        ksort($data);
        
        $dataCheckString = '';
        foreach ($data as $key => $value) {
            $dataCheckString .= $key . '=' . $value . "\n";
        }
        
        return trim($dataCheckString);
    }
} 
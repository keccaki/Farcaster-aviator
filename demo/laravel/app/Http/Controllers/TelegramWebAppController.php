<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TelegramWebAppService;
use App\Services\TelegramBotService;

class TelegramWebAppController extends Controller
{
    /**
     * Handle Telegram Web App initData verification
     *
     * @param Request $request
     * @param TelegramWebAppService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function init(Request $request, TelegramWebAppService $service)
    {
        $request->validate(['initData' => 'required|string']);
        $data = $service->verifyInitData($request->input('initData'));
        session(['telegram_user' => $data]);
        return response()->json($data);
    }

    /**
     * Send game result back to the user's Telegram chat.
     *
     * @param \Illuminate\Http\Request $request
     * @param TelegramBotService $botService
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResult(Request $request, TelegramBotService $botService)
    {
        $request->validate([
            'crashPoint' => 'required|numeric',
            'betAmount' => 'required|numeric',
        ]);

        $user = session('telegram_user');
        if (!isset($user['id'])) {
            return response()->json(['error' => 'No Telegram user in session'], 403);
        }
        $chatId = $user['id'];
        $crash = $request->input('crashPoint');
        $bet = $request->input('betAmount');

        $text = "<b>Game Result</b>\nBet: {$bet}\nCrash at: {$crash}x";

        $response = $botService->sendMessage($chatId, $text);

        return response()->json($response);
    }
} 
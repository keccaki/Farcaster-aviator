<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;

// Telegram Mini App Routes
Route::prefix('telegram')->group(function () {
    Route::post('/validate-init-data', [TelegramController::class, 'validateInitData']);
    Route::post('/create-invoice', [TelegramController::class, 'createInvoice']);
    Route::post('/payment-success', [TelegramController::class, 'handlePaymentSuccess']);
    Route::post('/withdraw', [TelegramController::class, 'processWithdrawal']);
}); 
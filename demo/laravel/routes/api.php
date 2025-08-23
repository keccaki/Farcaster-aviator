<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Userdetail;
use App\Http\Controllers\CryptoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Data api
Route::post('/user/withdrawal_list', [Userdetail::class,"withdrawal_list"]);

/*
|--------------------------------------------------------------------------
| Crypto Payment API Routes
|--------------------------------------------------------------------------
|
| These routes handle crypto wallet operations, deposits, and withdrawals.
| They are designed to work alongside existing payment methods.
|
*/

Route::prefix('crypto')->group(function () {
    // Wallet Management
    Route::get('/wallet', [CryptoController::class, 'getWallet']);
    Route::get('/balance', [CryptoController::class, 'getBalance']);
    Route::get('/deposit-address', [CryptoController::class, 'getDepositAddress']);
    
    // Transactions
    Route::post('/withdraw', [CryptoController::class, 'withdraw']);
    Route::get('/transactions', [CryptoController::class, 'getTransactionHistory']);
    Route::get('/withdrawal-status/{transactionId}', [CryptoController::class, 'getWithdrawalStatus']);
    
    // Monitoring (for testing)
    Route::post('/monitor-deposits', [CryptoController::class, 'monitorDeposits']);
    
    // System Information
    Route::get('/supported-currencies', [CryptoController::class, 'getSupportedCurrencies']);
    
    // Admin crypto management routes
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [CryptoController::class, 'getAdminStats']);
        Route::get('/transactions', [CryptoController::class, 'getAdminTransactions']);
        Route::get('/approvals', [CryptoController::class, 'getAdminApprovals']);
        Route::post('/approve/{id}', [CryptoController::class, 'approveWithdrawal']);
        Route::post('/reject/{id}', [CryptoController::class, 'rejectWithdrawal']);
        Route::post('/settings', [CryptoController::class, 'updateSettings']);
        Route::post('/start-monitoring', [CryptoController::class, 'startMonitoring']);
    });
});

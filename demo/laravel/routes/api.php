<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Userdetail;
use App\Http\Controllers\CryptoController;
use App\Http\Controllers\FarcasterController;

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

/*
|--------------------------------------------------------------------------
| Farcaster Integration API Routes
|--------------------------------------------------------------------------
|
| These routes handle Farcaster frame interactions, payments, and social features.
| They integrate with the existing Aviator game backend.
|
*/

Route::prefix('farcaster')->group(function () {
    // Wallet and balance management
    Route::get('/wallet/{fid}', [FarcasterController::class, 'getWallet']);
    
    // Payment processing (integrates with existing crypto system)
    Route::post('/deposit', [FarcasterController::class, 'processDeposit']);
    Route::post('/withdraw', [CryptoController::class, 'withdraw']); // Reuse existing withdrawal
    
    // Game interactions
    Route::get('/game-state', [FarcasterController::class, 'getCurrentGameState']);
    Route::post('/bet', [FarcasterController::class, 'placeBet']);
    Route::post('/cashout', [FarcasterController::class, 'cashOut']);
    Route::get('/history/{fid}', [FarcasterController::class, 'getGameHistory']);
    
    // Social features
    Route::post('/social-payment', [FarcasterController::class, 'processSocialPayment']);
    Route::get('/leaderboard', [FarcasterController::class, 'getLeaderboard']);
    
    // Analytics and tracking
    Route::post('/track', [FarcasterController::class, 'trackEvent']);
    Route::get('/stats/{fid}', [FarcasterController::class, 'getUserStats']);
    
    // Tournament system (to be implemented)
    Route::get('/tournaments', function() {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'id' => 'daily_001',
                    'name' => 'Daily High Multiplier',
                    'type' => 'biggest_multiplier',
                    'prizePool' => 1000,
                    'entryFee' => 10,
                    'participants' => 45,
                    'maxParticipants' => 100,
                    'status' => 'active',
                    'endTime' => now()->addHours(6)->toISOString()
                ]
            ]
        ]);
    });
    
    Route::post('/tournament/join', function() {
        return response()->json([
            'success' => true,
            'message' => 'Tournament feature coming soon!'
        ]);
    });
    
    // Referral system
    Route::post('/referral', function(Request $request) {
        // Basic referral processing
        $referrerFid = $request->input('referrerFid');
        $newUserFid = $request->input('newUserFid');
        
        // Give bonuses using existing wallet system
        $referrer = \App\Models\User::where('farcaster_id', $referrerFid)->first();
        $newUser = \App\Models\User::where('farcaster_id', $newUserFid)->first();
        
        if ($referrer && $newUser) {
            addwallet($referrer->id, 20); // $20 referrer bonus
            addwallet($newUser->id, 10);  // $10 new user bonus
            
            return response()->json([
                'success' => true,
                'message' => 'Referral bonuses applied!',
                'referrerBonus' => 20,
                'newUserBonus' => 10
            ]);
        }
        
        return response()->json([
            'success' => false,
            'error' => 'Users not found'
        ], 404);
    });
});

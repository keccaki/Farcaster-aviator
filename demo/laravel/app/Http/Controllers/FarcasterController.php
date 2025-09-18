<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Userbit;
use App\Models\Gameresult;
use App\Models\CryptoTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class FarcasterController extends Controller
{
    /**
     * Get user wallet information for Farcaster
     */
    public function getWallet($fid)
    {
        try {
            $user = User::where('farcaster_id', $fid)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            $wallet = Wallet::where('userid', $user->id)->first();
            
            if (!$wallet) {
                // Create wallet if doesn't exist
                $wallet = new Wallet;
                $wallet->userid = $user->id;
                $wallet->amount = 0.0;
                $wallet->save();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'fid' => $fid,
                    'balance' => (float) $wallet->amount,
                    'username' => $user->name,
                    'totalDeposited' => $this->getTotalDeposited($user->id),
                    'totalWon' => $this->getTotalWon($user->id),
                    'gamesPlayed' => $this->getGamesPlayed($user->id)
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Farcaster get wallet failed', [
                'fid' => $fid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get wallet information'
            ], 500);
        }
    }

    /**
     * Process Farcaster deposit using existing crypto system
     */
    public function processDeposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fid' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'transactionHash' => 'required|string',
            'fromAddress' => 'required|string',
            'currency' => 'string|in:USDC,ETH,DEGEN'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 400);
        }

        try {
            $user = User::where('farcaster_id', $request->fid)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            $currency = $request->currency ?? 'USDC';
            $amount = (float) $request->amount;

            // Use existing crypto transaction system
            $result = addcryptotransaction(
                $user->id,
                $currency,
                $amount,
                'deposit',
                $request->transactionHash,
                'confirmed'
            );

            if ($result) {
                // Get updated balance
                $wallet = Wallet::where('userid', $user->id)->first();
                $newBalance = $wallet ? $wallet->amount : 0;

                // Log Farcaster deposit
                Log::info('Farcaster deposit processed', [
                    'fid' => $request->fid,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'tx_hash' => $request->transactionHash
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'newBalance' => $newBalance,
                        'depositAmount' => $amount,
                        'currency' => $currency,
                        'transactionHash' => $request->transactionHash
                    ],
                    'message' => 'Deposit successful'
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Deposit processing failed'
            ], 500);

        } catch (Exception $e) {
            Log::error('Farcaster deposit failed', [
                'fid' => $request->fid,
                'amount' => $request->amount,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Deposit failed'
            ], 500);
        }
    }

    /**
     * Process bet placement for Farcaster users
     */
    public function placeBet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fid' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'roundId' => 'required|string',
            'autoCashOut' => 'numeric|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 400);
        }

        try {
            $user = User::where('farcaster_id', $request->fid)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            $wallet = Wallet::where('userid', $user->id)->first();
            
            if (!$wallet || $wallet->amount < $request->amount) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient balance'
                ], 400);
            }

            // Deduct bet amount from wallet
            $wallet->amount -= $request->amount;
            $wallet->save();

            // Create bet record using existing system
            $bet = new Userbit;
            $bet->userid = $user->id;
            $bet->amount = $request->amount;
            $bet->gameid = currentid(); // Use existing game ID system
            $bet->status = 'active';
            $bet->auto_cashout = $request->autoCashOut ?? null;
            $bet->placed_at = now();
            $bet->save();

            // Add transaction record
            addtransaction(
                $user->id,
                'farcaster_bet',
                'bet_' . $bet->id,
                'debit',
                $request->amount,
                'bet',
                'Farcaster bet placed - Round: ' . $request->roundId,
                '1'
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'betId' => $bet->id,
                    'amount' => $request->amount,
                    'roundId' => $request->roundId,
                    'newBalance' => $wallet->amount,
                    'autoCashOut' => $request->autoCashOut
                ],
                'message' => 'Bet placed successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Farcaster bet placement failed', [
                'fid' => $request->fid,
                'amount' => $request->amount,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Bet placement failed'
            ], 500);
        }
    }

    /**
     * Process cash out for Farcaster users
     */
    public function cashOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fid' => 'required|integer',
            'betId' => 'required|string',
            'multiplier' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 400);
        }

        try {
            $user = User::where('farcaster_id', $request->fid)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            // Find active bet
            $bet = Userbit::where('userid', $user->id)
                ->where('status', 'active')
                ->where('id', $request->betId)
                ->first();

            if (!$bet) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active bet found'
                ], 404);
            }

            // Calculate win amount
            $winAmount = $bet->amount * $request->multiplier;

            // Update bet status
            $bet->status = 'cashed_out';
            $bet->cashout_multiplier = $request->multiplier;
            $bet->win_amount = $winAmount;
            $bet->cashed_out_at = now();
            $bet->save();

            // Add win to wallet
            $wallet = Wallet::where('userid', $user->id)->first();
            $wallet->amount += $winAmount;
            $wallet->save();

            // Add win transaction
            addtransaction(
                $user->id,
                'farcaster_win',
                'win_' . $bet->id,
                'credit',
                $winAmount,
                'win',
                'Farcaster win - ' . $request->multiplier . 'x multiplier',
                '1'
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'winAmount' => $winAmount,
                    'multiplier' => $request->multiplier,
                    'newBalance' => $wallet->amount,
                    'betAmount' => $bet->amount
                ],
                'message' => 'Cash out successful'
            ]);

        } catch (Exception $e) {
            Log::error('Farcaster cash out failed', [
                'fid' => $request->fid,
                'betId' => $request->betId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Cash out failed'
            ], 500);
        }
    }

    /**
     * Get user game history
     */
    public function getGameHistory($fid, Request $request)
    {
        try {
            $user = User::where('farcaster_id', $fid)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            $limit = $request->get('limit', 50);
            
            $history = Userbit::where('userid', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($bet) {
                    return [
                        'id' => $bet->id,
                        'amount' => (float) $bet->amount,
                        'status' => $bet->status,
                        'multiplier' => $bet->cashout_multiplier,
                        'winAmount' => $bet->win_amount,
                        'placedAt' => $bet->created_at,
                        'cashedOutAt' => $bet->cashed_out_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (Exception $e) {
            Log::error('Get game history failed', [
                'fid' => $fid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get game history'
            ], 500);
        }
    }

    /**
     * Process social payment (tip, gift bet, etc.)
     */
    public function processSocialPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fromFid' => 'required|integer',
            'toFid' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:tip,gift_bet,split_win',
            'message' => 'string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 400);
        }

        try {
            $fromUser = User::where('farcaster_id', $request->fromFid)->first();
            $toUser = User::where('farcaster_id', $request->toFid)->first();

            if (!$fromUser || !$toUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            $fromWallet = Wallet::where('userid', $fromUser->id)->first();
            
            if (!$fromWallet || $fromWallet->amount < $request->amount) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient balance'
                ], 400);
            }

            // Process transfer using existing system
            addwallet($fromUser->id, -$request->amount);
            addwallet($toUser->id, $request->amount);

            // Add transaction records
            addtransaction(
                $fromUser->id,
                'farcaster_social',
                'social_' . uniqid(),
                'debit',
                $request->amount,
                $request->type,
                'Social payment to @' . $toUser->farcaster_username . ': ' . ($request->message ?? ''),
                '1'
            );

            addtransaction(
                $toUser->id,
                'farcaster_social',
                'social_' . uniqid(),
                'credit',
                $request->amount,
                $request->type,
                'Social payment from @' . $fromUser->farcaster_username . ': ' . ($request->message ?? ''),
                '1'
            );

            return response()->json([
                'success' => true,
                'message' => 'Social payment sent successfully',
                'data' => [
                    'amount' => $request->amount,
                    'type' => $request->type,
                    'from' => $fromUser->farcaster_username,
                    'to' => $toUser->farcaster_username
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Social payment failed', [
                'fromFid' => $request->fromFid,
                'toFid' => $request->toFid,
                'amount' => $request->amount,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Social payment failed'
            ], 500);
        }
    }

    /**
     * Get leaderboard data
     */
    public function getLeaderboard(Request $request)
    {
        try {
            $type = $request->get('type', 'daily');
            $limit = $request->get('limit', 10);

            $query = DB::table('users')
                ->join('userbits', 'users.id', '=', 'userbits.userid')
                ->whereNotNull('users.farcaster_id')
                ->where('userbits.status', 'cashed_out');

            // Apply time filter based on type
            switch ($type) {
                case 'daily':
                    $query->where('userbits.created_at', '>=', Carbon::today());
                    break;
                case 'weekly':
                    $query->where('userbits.created_at', '>=', Carbon::now()->startOfWeek());
                    break;
                case 'monthly':
                    $query->where('userbits.created_at', '>=', Carbon::now()->startOfMonth());
                    break;
            }

            $leaderboard = $query
                ->select([
                    'users.farcaster_id as fid',
                    'users.name as username',
                    'users.farcaster_username',
                    DB::raw('MAX(userbits.cashout_multiplier) as bestMultiplier'),
                    DB::raw('SUM(userbits.win_amount) as totalWinnings'),
                    DB::raw('COUNT(userbits.id) as gamesPlayed')
                ])
                ->groupBy('users.id', 'users.farcaster_id', 'users.name', 'users.farcaster_username')
                ->orderBy('bestMultiplier', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($player, $index) {
                    return [
                        'position' => $index + 1,
                        'fid' => $player->fid,
                        'username' => $player->username,
                        'bestMultiplier' => round($player->bestMultiplier, 2),
                        'totalWinnings' => round($player->totalWinnings, 2),
                        'gamesPlayed' => $player->gamesPlayed
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $leaderboard,
                'type' => $type
            ]);

        } catch (Exception $e) {
            Log::error('Get leaderboard failed', [
                'type' => $request->get('type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get leaderboard'
            ], 500);
        }
    }

    /**
     * Track analytics events
     */
    public function trackEvent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fid' => 'required|integer',
                'event' => 'required|string',
                'properties' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 400);
            }

            // Log event for analytics (extend with proper analytics service)
            Log::info('Farcaster event tracked', [
                'fid' => $request->fid,
                'event' => $request->event,
                'properties' => $request->properties ?? [],
                'timestamp' => now(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event tracked'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Event tracking failed'
            ], 500);
        }
    }

    // Helper methods
    private function getTotalDeposited($userId)
    {
        return CryptoTransaction::where('user_id', $userId)
            ->where('transaction_type', 'deposit')
            ->where('status', 'confirmed')
            ->sum('amount_usd') ?? 0;
    }

    private function getTotalWon($userId)
    {
        return Userbit::where('userid', $userId)
            ->where('status', 'cashed_out')
            ->sum('win_amount') ?? 0;
    }

    private function getGamesPlayed($userId)
    {
        return Userbit::where('userid', $userId)->count();
    }
}


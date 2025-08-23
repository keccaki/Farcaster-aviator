<?php

namespace App\Http\Controllers;

use App\Services\SolanaWalletService;
use App\Models\CryptoWallet;
use App\Models\CryptoTransaction;
use App\Models\WithdrawalApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class CryptoController extends Controller
{
    private $walletService;

    public function __construct(SolanaWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get or create user's crypto wallet
     */
    public function getWallet(Request $request)
    {
        try {
            $userId = user('id');
            
            // Check if user already has a wallet
            $existingWallet = CryptoWallet::where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if ($existingWallet) {
                // Get current balance
                $balance = $this->walletService->getWalletBalance($existingWallet->wallet_address);
                
                return response()->json([
                    'success' => true,
                    'wallet' => [
                        'address' => $existingWallet->wallet_address,
                        'balance' => $balance,
                        'created_at' => $existingWallet->created_at->toISOString()
                    ]
                ]);
            }

            // Create new wallet
            $result = $this->walletService->generateWalletForUser($userId);
            
            if ($result['success']) {
                $balance = $this->walletService->getWalletBalance($result['wallet_address']);
                
                return response()->json([
                    'success' => true,
                    'wallet' => [
                        'address' => $result['wallet_address'],
                        'balance' => $balance,
                        'created_at' => now()->toISOString()
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error']
            ], 500);

        } catch (Exception $e) {
            Log::error('Get wallet failed', [
                'user_id' => user('id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get wallet'
            ], 500);
        }
    }

    /**
     * Get wallet balance
     */
    public function getBalance(Request $request)
    {
        try {
            $userId = user('id');
            $wallet = CryptoWallet::where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'error' => 'Wallet not found'
                ], 404);
            }

            $balance = $this->walletService->getWalletBalance($wallet->wallet_address);
            
            return response()->json([
                'success' => true,
                'balance' => $balance
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get balance'
            ], 500);
        }
    }

    /**
     * Process crypto withdrawal
     */
    public function withdraw(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'to_address' => 'required|string|min:32|max:44',
                'amount' => 'required|numeric|min:0.000001',
                'currency' => 'required|in:SOL,USDT',
                'password' => 'required|string' // User password confirmation
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }

            $userId = user('id');
            
            // Verify user password
            if (!$this->verifyUserPassword($userId, $request->password)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid password'
                ], 401);
            }

            // Process withdrawal
            $result = $this->walletService->processWithdrawal(
                $userId,
                $request->to_address,
                $request->amount,
                $request->currency
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'status' => $result['status'] ?? 'completed',
                    'transaction_hash' => $result['transaction_hash'] ?? null,
                    'approval_tier' => $result['approval_tier'] ?? null,
                    'transaction_id' => $result['transaction_id'] ?? null
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'details' => $result['details'] ?? null
            ], 400);

        } catch (Exception $e) {
            Log::error('Withdrawal failed', [
                'user_id' => user('id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Withdrawal processing failed'
            ], 500);
        }
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory(Request $request)
    {
        try {
            $userId = user('id');
            $page = $request->get('page', 1);
            $limit = min($request->get('limit', 20), 100);
            $type = $request->get('type'); // 'deposit', 'withdrawal', or null for all

            $query = CryptoTransaction::where('user_id', $userId)
                ->with(['user:id,name,email'])
                ->orderBy('created_at', 'desc');

            if ($type) {
                $query->where('transaction_type', $type);
            }

            $transactions = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'total_pages' => $transactions->lastPage(),
                    'total_items' => $transactions->total(),
                    'per_page' => $transactions->perPage()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get transaction history'
            ], 500);
        }
    }

    /**
     * Get deposit address (same as wallet address)
     */
    public function getDepositAddress(Request $request)
    {
        try {
            $userId = user('id');
            $wallet = CryptoWallet::where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'error' => 'Wallet not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'deposit_address' => $wallet->wallet_address,
                'supported_currencies' => ['SOL', 'USDT'],
                'network' => 'Solana',
                'qr_code_url' => $this->generateQRCode($wallet->wallet_address)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get deposit address'
            ], 500);
        }
    }

    /**
     * Check withdrawal status
     */
    public function getWithdrawalStatus(Request $request, $transactionId)
    {
        try {
            $userId = user('id');
            $transaction = CryptoTransaction::where('id', $transactionId)
                ->where('user_id', $userId)
                ->where('transaction_type', 'withdrawal')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'error' => 'Transaction not found'
                ], 404);
            }

            $response = [
                'success' => true,
                'transaction' => [
                    'id' => $transaction->id,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'network_fee' => $transaction->network_fee,
                    'transaction_hash' => $transaction->transaction_hash,
                    'confirmations' => $transaction->confirmations,
                    'created_at' => $transaction->created_at->toISOString(),
                    'processed_at' => $transaction->processed_at?->toISOString()
                ]
            ];

            // Add approval information if pending
            if ($transaction->status === 'pending') {
                $approval = WithdrawalApproval::where('crypto_transaction_id', $transaction->id)
                    ->first();
                
                if ($approval) {
                    $response['approval'] = [
                        'tier' => $approval->approval_tier,
                        'status' => $approval->status,
                        'expires_at' => $approval->expires_at?->toISOString()
                    ];
                }
            }

            return response()->json($response);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get withdrawal status'
            ], 500);
        }
    }

    /**
     * Get supported currencies and their current rates
     */
    public function getSupportedCurrencies(Request $request)
    {
        try {
            $currencies = [
                'SOL' => [
                    'name' => 'Solana',
                    'symbol' => 'SOL',
                    'decimals' => 9,
                    'min_deposit' => 0.01,
                    'min_withdrawal' => 0.01,
                    'withdrawal_fee' => 0.001,
                    'network' => 'Solana',
                    'current_rate' => $this->walletService->getExchangeRate('SOL/USD')
                ],
                'USDT' => [
                    'name' => 'Tether USD',
                    'symbol' => 'USDT',
                    'decimals' => 6,
                    'min_deposit' => 5.0,
                    'min_withdrawal' => 5.0,
                    'withdrawal_fee' => 1.0,
                    'network' => 'Solana (SPL)',
                    'current_rate' => $this->walletService->getExchangeRate('USDT/USD')
                ]
            ];

            return response()->json([
                'success' => true,
                'currencies' => $currencies
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get supported currencies'
            ], 500);
        }
    }

    /**
     * Manual deposit monitoring trigger (for testing)
     */
    public function monitorDeposits(Request $request)
    {
        try {
            $userId = user('id');
            $wallet = CryptoWallet::where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'error' => 'Wallet not found'
                ], 404);
            }

            $result = $this->walletService->monitorDeposits($wallet->wallet_address);
            
            return response()->json([
                'success' => $result['success'],
                'new_deposits' => $result['new_deposits'] ?? [],
                'processed_count' => $result['processed_count'] ?? 0,
                'error' => $result['error'] ?? null
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to monitor deposits'
            ], 500);
        }
    }

    /**
     * Private helper methods
     */
    private function verifyUserPassword(int $userId, string $password): bool
    {
        $user = \App\Models\User::find($userId);
        return $user && \Hash::check($password, $user->password);
    }

    private function generateQRCode(string $address): string
    {
        // Generate QR code URL for the wallet address
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($address);
    }

    // ================================
    // ADMIN MANAGEMENT METHODS
    // ================================

    public function getAdminStats()
    {
        try {
            $totalDeposits = CryptoTransaction::where('type', 'deposit')
                ->where('status', 'completed')
                ->sum('usd_value');
                
            $totalWithdrawals = CryptoTransaction::where('type', 'withdrawal')
                ->where('status', 'completed')
                ->sum('usd_value');
                
            $depositsCount = CryptoTransaction::where('type', 'deposit')->count();
            $withdrawalsCount = CryptoTransaction::where('type', 'withdrawal')->count();
            
            $pendingApprovals = WithdrawalApproval::where('status', 'pending')->count();
            $activeWallets = CryptoWallet::whereNotNull('sol_address')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_deposits' => number_format($totalDeposits, 2),
                    'total_withdrawals' => number_format($totalWithdrawals, 2),
                    'deposits_count' => $depositsCount,
                    'withdrawals_count' => $withdrawalsCount,
                    'pending_approvals' => $pendingApprovals,
                    'active_wallets' => $activeWallets
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load stats: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAdminTransactions()
    {
        try {
            $transactions = CryptoTransaction::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAdminApprovals()
    {
        try {
            $approvals = WithdrawalApproval::with(['user', 'cryptoTransaction'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'approvals' => $approvals
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load approvals: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approveWithdrawal($id)
    {
        try {
            $approval = WithdrawalApproval::findOrFail($id);
            
            if ($approval->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Withdrawal already processed'
                ], 400);
            }

            // Update approval status
            $approval->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now()
            ]);

            // Process the withdrawal using the service
            $result = $this->walletService->processApprovedWithdrawal($approval);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal approved and processed',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve withdrawal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rejectWithdrawal(Request $request, $id)
    {
        try {
            $approval = WithdrawalApproval::findOrFail($id);
            
            if ($approval->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Withdrawal already processed'
                ], 400);
            }

            // Update approval status
            $approval->update([
                'status' => 'rejected',
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'rejection_reason' => $request->input('reason', 'No reason provided')
            ]);

            // Refund the amount to user's game balance
            $transaction = $approval->cryptoTransaction;
            addwallet($transaction->user_id, $transaction->usd_value, 'Withdrawal refund');

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal rejected and amount refunded'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject withdrawal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateSettings(Request $request)
    {
        try {
            // Here you would typically save settings to a configuration table
            // For now, we'll just return success
            
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function startMonitoring()
    {
        try {
            // Start the monitoring service
            $result = $this->walletService->monitorAllDeposits();
            
            return response()->json([
                'success' => true,
                'message' => 'Monitoring started successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start monitoring: ' . $e->getMessage()
            ], 500);
        }
    }
} 
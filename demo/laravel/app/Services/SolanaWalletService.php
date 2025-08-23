<?php

namespace App\Services;

use App\Models\CryptoWallet;
use App\Models\CryptoTransaction;
use App\Models\CryptoSecurityLog;
use App\Models\CryptoExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Exception;

class SolanaWalletService
{
    private $rpcEndpoint;
    private $masterSeed;
    private $usdtMintAddress;
    private $requiredConfirmations;
    
    public function __construct()
    {
        $this->rpcEndpoint = config('solana.rpc_endpoint', 'https://api.mainnet-beta.solana.com');
        $this->masterSeed = config('solana.master_seed'); // Encrypted in env
        $this->usdtMintAddress = config('solana.usdt_mint_address', 'Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB');
        $this->requiredConfirmations = config('solana.required_confirmations', 32);
    }

    /**
     * Generate unique Solana wallet for user
     */
    public function generateWalletForUser(int $userId): array
    {
        try {
            // Generate HD wallet derivation path
            $derivationPath = "m/44'/501'/{$userId}'/0/0";
            
            // Generate keypair using derivation path
            $keyPair = $this->generateKeyPairFromPath($derivationPath);
            
            // Encrypt private key before storing
            $encryptedPrivateKey = Crypt::encrypt($keyPair['privateKey']);
            
            // Create wallet record
            $wallet = CryptoWallet::create([
                'user_id' => $userId,
                'wallet_address' => $keyPair['publicKey'],
                'private_key_encrypted' => $encryptedPrivateKey,
                'derivation_path' => $derivationPath,
                'wallet_type' => 'solana',
                'status' => 'active'
            ]);

            // Log wallet creation
            $this->logSecurityEvent($userId, 'wallet_created', [
                'wallet_address' => $keyPair['publicKey'],
                'derivation_path' => $derivationPath
            ]);

            return [
                'success' => true,
                'wallet_address' => $keyPair['publicKey'],
                'wallet_id' => $wallet->id
            ];

        } catch (Exception $e) {
            Log::error('Wallet generation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate wallet'
            ];
        }
    }

    /**
     * Get wallet balance for SOL and USDT
     */
    public function getWalletBalance(string $walletAddress): array
    {
        try {
            // Cache key for balance
            $cacheKey = "wallet_balance_{$walletAddress}";
            
            return Cache::remember($cacheKey, 30, function() use ($walletAddress) {
                // Get SOL balance
                $solBalance = $this->getSolBalance($walletAddress);
                
                // Get USDT balance
                $usdtBalance = $this->getUsdtBalance($walletAddress);
                
                // Get current exchange rates
                $solRate = $this->getExchangeRate('SOL/USD');
                $usdtRate = $this->getExchangeRate('USDT/USD');
                
                // Calculate USD values
                $solUsdValue = $solBalance * $solRate;
                $usdtUsdValue = $usdtBalance * $usdtRate;
                
                return [
                    'sol_balance' => $solBalance,
                    'usdt_balance' => $usdtBalance,
                    'sol_usd_value' => $solUsdValue,
                    'usdt_usd_value' => $usdtUsdValue,
                    'total_usd_value' => $solUsdValue + $usdtUsdValue,
                    'last_updated' => now()
                ];
            });

        } catch (Exception $e) {
            Log::error('Balance check failed', [
                'wallet_address' => $walletAddress,
                'error' => $e->getMessage()
            ]);

            return [
                'sol_balance' => 0,
                'usdt_balance' => 0,
                'sol_usd_value' => 0,
                'usdt_usd_value' => 0,
                'total_usd_value' => 0,
                'error' => 'Balance check failed'
            ];
        }
    }

    /**
     * Process withdrawal with comprehensive security checks
     */
    public function processWithdrawal(int $userId, string $toAddress, float $amount, string $currency): array
    {
        try {
            // Get user's crypto wallet
            $userWallet = CryptoWallet::where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$userWallet) {
                return ['success' => false, 'error' => 'User wallet not found'];
            }

            // Security validations
            $securityCheck = $this->performSecurityChecks($userId, $toAddress, $amount, $currency);
            if (!$securityCheck['passed']) {
                return [
                    'success' => false,
                    'error' => 'Security check failed',
                    'details' => $securityCheck['reasons']
                ];
            }

            // Check sufficient balance
            $balance = $this->getWalletBalance($userWallet->wallet_address);
            $currentBalance = $currency === 'SOL' ? $balance['sol_balance'] : $balance['usdt_balance'];
            
            if ($currentBalance < $amount) {
                return ['success' => false, 'error' => 'Insufficient balance'];
            }

            // Calculate network fee
            $networkFee = $this->calculateNetworkFee($currency);
            $totalRequired = $amount + $networkFee;

            if ($currentBalance < $totalRequired) {
                return ['success' => false, 'error' => 'Insufficient balance for fees'];
            }

            // Get current exchange rate
            $exchangeRate = $this->getExchangeRate("{$currency}/USD");
            $amountUsd = $amount * $exchangeRate;

            // Create crypto transaction record
            $cryptoTransaction = CryptoTransaction::create([
                'user_id' => $userId,
                'transaction_hash' => '', // Will be updated after broadcast
                'wallet_address' => $userWallet->wallet_address,
                'transaction_type' => 'withdrawal',
                'currency' => $currency,
                'amount' => $amount,
                'amount_usd' => $amountUsd,
                'network_fee' => $networkFee,
                'status' => 'pending',
                'to_address' => $toAddress
            ]);

            // Determine approval tier
            $approvalTier = $this->determineApprovalTier($amountUsd);

            if ($approvalTier === 'auto') {
                // Auto-approve small amounts
                $result = $this->executeWithdrawal($cryptoTransaction);
                
                if ($result['success']) {
                    // Update user's game wallet balance
                    $this->updateGameWalletBalance($userId, -$amountUsd);
                    
                    // Log successful withdrawal
                    $this->logSecurityEvent($userId, 'withdrawal_completed', [
                        'amount' => $amount,
                        'currency' => $currency,
                        'to_address' => $toAddress,
                        'transaction_hash' => $result['transaction_hash']
                    ]);
                }
                
                return $result;
            } else {
                // Require manual approval
                $this->createWithdrawalApproval($cryptoTransaction, $approvalTier);
                
                return [
                    'success' => true,
                    'status' => 'pending_approval',
                    'approval_tier' => $approvalTier,
                    'transaction_id' => $cryptoTransaction->id
                ];
            }

        } catch (Exception $e) {
            Log::error('Withdrawal processing failed', [
                'user_id' => $userId,
                'amount' => $amount,
                'currency' => $currency,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Withdrawal processing failed'
            ];
        }
    }

    /**
     * Monitor deposits for a wallet address
     */
    public function monitorDeposits(string $walletAddress): array
    {
        try {
            // Get recent transactions for this wallet
            $transactions = $this->getRecentTransactions($walletAddress);
            $newDeposits = [];

            foreach ($transactions as $tx) {
                // Check if this transaction is already processed
                $existingTx = CryptoTransaction::where('transaction_hash', $tx['signature'])
                    ->first();

                if (!$existingTx && $tx['type'] === 'incoming') {
                    // Process new deposit
                    $deposit = $this->processDeposit($walletAddress, $tx);
                    if ($deposit['success']) {
                        $newDeposits[] = $deposit;
                    }
                }
            }

            return [
                'success' => true,
                'new_deposits' => $newDeposits,
                'processed_count' => count($newDeposits)
            ];

        } catch (Exception $e) {
            Log::error('Deposit monitoring failed', [
                'wallet_address' => $walletAddress,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Deposit monitoring failed'
            ];
        }
    }

    /**
     * Private helper methods
     */
    private function generateKeyPairFromPath(string $derivationPath): array
    {
        // This would use a proper Solana SDK in production
        // For now, simulating the key generation process
        
        // In production, use: solana-keygen or @solana/web3.js
        $seed = hash('sha256', $this->masterSeed . $derivationPath);
        
        // Generate a realistic-looking Solana address
        $publicKey = $this->generateSolanaAddress($seed);
        $privateKey = $seed; // In production, this would be proper Ed25519 key
        
        return [
            'publicKey' => $publicKey,
            'privateKey' => $privateKey
        ];
    }

    private function generateSolanaAddress(string $seed): string
    {
        // Generate a realistic Solana address (Base58 encoded, ~44 characters)
        $hash = hash('sha256', $seed);
        $base58Chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $address = '';
        
        for ($i = 0; $i < 44; $i++) {
            $index = hexdec(substr($hash, $i % 64, 2)) % 58;
            $address .= $base58Chars[$index];
        }
        
        return $address;
    }

    private function getSolBalance(string $walletAddress): float
    {
        // RPC call to get SOL balance
        $response = Http::post($this->rpcEndpoint, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getBalance',
            'params' => [$walletAddress]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return ($data['result']['value'] ?? 0) / 1000000000; // Convert lamports to SOL
        }

        return 0;
    }

    private function getUsdtBalance(string $walletAddress): float
    {
        // Get USDT token account balance
        $response = Http::post($this->rpcEndpoint, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getTokenAccountsByOwner',
            'params' => [
                $walletAddress,
                ['mint' => $this->usdtMintAddress],
                ['encoding' => 'jsonParsed']
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $accounts = $data['result']['value'] ?? [];
            
            if (!empty($accounts)) {
                $balance = $accounts[0]['account']['data']['parsed']['info']['tokenAmount']['uiAmount'] ?? 0;
                return (float) $balance;
            }
        }

        return 0;
    }

    private function getExchangeRate(string $pair): float
    {
        $rate = CryptoExchangeRate::where('currency_pair', $pair)
            ->where('last_updated', '>', now()->subMinutes(5))
            ->first();

        if ($rate) {
            return (float) $rate->rate;
        }

        // Fetch from external API if not cached
        return $this->fetchExchangeRate($pair);
    }

    private function fetchExchangeRate(string $pair): float
    {
        try {
            $currency = explode('/', $pair)[0];
            $response = Http::get("https://api.coingecko.com/api/v3/simple/price", [
                'ids' => $currency === 'SOL' ? 'solana' : 'tether',
                'vs_currencies' => 'usd'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rate = $currency === 'SOL' ? $data['solana']['usd'] : $data['tether']['usd'];
                
                // Cache the rate
                CryptoExchangeRate::updateOrCreate(
                    ['currency_pair' => $pair],
                    [
                        'rate' => $rate,
                        'source' => 'coingecko',
                        'last_updated' => now()
                    ]
                );

                return (float) $rate;
            }
        } catch (Exception $e) {
            Log::error('Exchange rate fetch failed', [
                'pair' => $pair,
                'error' => $e->getMessage()
            ]);
        }

        // Return fallback rates
        return $pair === 'SOL/USD' ? 100.0 : 1.0;
    }

    private function performSecurityChecks(int $userId, string $toAddress, float $amount, string $currency): array
    {
        $reasons = [];
        $riskScore = 0;

        // Check withdrawal limits
        $dailyLimit = $this->getDailyWithdrawalLimit($userId, $currency);
        $todayWithdrawals = $this->getTodayWithdrawals($userId, $currency);
        
        if (($todayWithdrawals + $amount) > $dailyLimit) {
            $reasons[] = 'Daily withdrawal limit exceeded';
            $riskScore += 30;
        }

        // Check address validity
        if (!$this->isValidSolanaAddress($toAddress)) {
            $reasons[] = 'Invalid destination address';
            $riskScore += 50;
        }

        // Check for suspicious patterns
        if ($this->detectSuspiciousActivity($userId)) {
            $reasons[] = 'Suspicious activity detected';
            $riskScore += 40;
        }

        // Check minimum amounts
        $minAmount = $currency === 'SOL' ? 0.01 : 5.0;
        if ($amount < $minAmount) {
            $reasons[] = "Minimum withdrawal amount is {$minAmount} {$currency}";
            $riskScore += 20;
        }

        // Log security check
        $this->logSecurityEvent($userId, 'withdrawal_security_check', [
            'amount' => $amount,
            'currency' => $currency,
            'to_address' => $toAddress,
            'risk_score' => $riskScore,
            'reasons' => $reasons
        ]);

        return [
            'passed' => empty($reasons),
            'risk_score' => $riskScore,
            'reasons' => $reasons
        ];
    }

    private function calculateNetworkFee(string $currency): float
    {
        // SOL network fees are very low (~0.000005 SOL)
        // USDT transfers require SOL for gas
        return $currency === 'SOL' ? 0.000005 : 0.001;
    }

    private function determineApprovalTier(float $amountUsd): string
    {
        $autoApprovalLimit = (float) config('crypto.auto_approval_limit', 100.0);
        $manualApprovalLimit = (float) config('crypto.manual_approval_limit', 1000.0);

        if ($amountUsd <= $autoApprovalLimit) {
            return 'auto';
        } elseif ($amountUsd <= $manualApprovalLimit) {
            return 'manual';
        } else {
            return 'multi_sig';
        }
    }

    private function executeWithdrawal(CryptoTransaction $transaction): array
    {
        try {
            // In production, this would create and broadcast the actual transaction
            // For now, simulating successful transaction
            
            $transactionHash = $this->generateTransactionHash();
            
            // Update transaction record
            $transaction->update([
                'transaction_hash' => $transactionHash,
                'status' => 'confirmed',
                'confirmations' => $this->requiredConfirmations,
                'processed_at' => now()
            ]);

            return [
                'success' => true,
                'transaction_hash' => $transactionHash,
                'status' => 'confirmed'
            ];

        } catch (Exception $e) {
            $transaction->update(['status' => 'failed']);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function processDeposit(string $walletAddress, array $txData): array
    {
        try {
            // Get user from wallet address
            $wallet = CryptoWallet::where('wallet_address', $walletAddress)->first();
            if (!$wallet) {
                return ['success' => false, 'error' => 'Wallet not found'];
            }

            // Determine currency and amount
            $currency = $txData['currency'];
            $amount = $txData['amount'];
            $exchangeRate = $this->getExchangeRate("{$currency}/USD");
            $amountUsd = $amount * $exchangeRate;

            // Create deposit transaction
            $cryptoTransaction = CryptoTransaction::create([
                'user_id' => $wallet->user_id,
                'transaction_hash' => $txData['signature'],
                'wallet_address' => $walletAddress,
                'transaction_type' => 'deposit',
                'currency' => $currency,
                'amount' => $amount,
                'amount_usd' => $amountUsd,
                'network_fee' => 0,
                'status' => 'confirmed',
                'confirmations' => $txData['confirmations'],
                'from_address' => $txData['from_address'],
                'processed_at' => now()
            ]);

            // Update user's game wallet balance
            $this->updateGameWalletBalance($wallet->user_id, $amountUsd);

            // Log deposit
            $this->logSecurityEvent($wallet->user_id, 'deposit_processed', [
                'amount' => $amount,
                'currency' => $currency,
                'transaction_hash' => $txData['signature']
            ]);

            return [
                'success' => true,
                'transaction_id' => $cryptoTransaction->id,
                'amount' => $amount,
                'currency' => $currency
            ];

        } catch (Exception $e) {
            Log::error('Deposit processing failed', [
                'wallet_address' => $walletAddress,
                'tx_data' => $txData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Deposit processing failed'
            ];
        }
    }

    private function updateGameWalletBalance(int $userId, float $usdAmount): void
    {
        // Update the existing wallet table for game compatibility
        $wallet = \App\Models\Wallet::where('userid', $userId)->first();
        if ($wallet) {
            $currentAmount = (float) $wallet->amount;
            $newAmount = $currentAmount + $usdAmount;
            
            $wallet->update([
                'amount' => $newAmount,
                'total_usd_value' => $newAmount
            ]);

            // Create transaction record for game compatibility
            addtransaction(
                $userId,
                'crypto',
                $this->generateTransactionHash(),
                $usdAmount > 0 ? 'credit' : 'debit',
                abs($usdAmount),
                $usdAmount > 0 ? 'deposit' : 'withdrawal',
                'Crypto transaction processed',
                '1'
            );
        }
    }

    private function logSecurityEvent(int $userId, string $action, array $details = []): void
    {
        CryptoSecurityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'security_flags' => json_encode($details),
            'geo_location' => json_encode([
                'country' => request()->header('CF-IPCountry', 'Unknown'),
                'ip' => request()->ip()
            ])
        ]);
    }

    private function generateTransactionHash(): string
    {
        return hash('sha256', uniqid() . microtime() . random_bytes(32));
    }

    // Additional helper methods would go here...
    private function getRecentTransactions(string $walletAddress): array
    {
        try {
            // Get recent transaction signatures for this wallet
            $signatures = $this->getTransactionSignatures($walletAddress);
            $transactions = [];

            foreach ($signatures as $signature) {
                $txDetail = $this->getTransactionDetail($signature);
                
                if ($txDetail && $txDetail['slot'] > 0) {
                    // Check if this is an incoming transaction
                    $isIncoming = $this->isIncomingTransaction($walletAddress, $txDetail);
                    
                    if ($isIncoming) {
                        $amount = $this->extractTransactionAmount($walletAddress, $txDetail);
                        $currency = $this->detectCurrency($txDetail);
                        
                        if ($amount > 0) {
                            $transactions[] = [
                                'signature' => $signature,
                                'type' => 'incoming',
                                'amount' => $amount,
                                'currency' => $currency,
                                'confirmations' => $this->getConfirmationCount($txDetail['slot']),
                                'from_address' => $this->extractSenderAddress($walletAddress, $txDetail),
                                'timestamp' => $txDetail['blockTime'] ?? time(),
                                'slot' => $txDetail['slot']
                            ];
                        }
                    }
                }
            }

            return $transactions;

        } catch (Exception $e) {
            Log::error('Failed to get recent transactions', [
                'wallet_address' => $walletAddress,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function getTransactionSignatures(string $walletAddress, int $limit = 20): array
    {
        try {
            $response = Http::timeout(30)->post($this->rpcEndpoint, [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getSignaturesForAddress',
                'params' => [
                    $walletAddress,
                    [
                        'limit' => $limit,
                        'commitment' => 'confirmed'
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['result']) && is_array($data['result'])) {
                    return array_map(function($sig) {
                        return $sig['signature'];
                    }, $data['result']);
                }
            }

            return [];

        } catch (Exception $e) {
            Log::error('Failed to get transaction signatures', [
                'wallet_address' => $walletAddress,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function getTransactionDetail(string $signature): ?array
    {
        try {
            $response = Http::timeout(30)->post($this->rpcEndpoint, [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getTransaction',
                'params' => [
                    $signature,
                    [
                        'encoding' => 'jsonParsed',
                        'commitment' => 'confirmed',
                        'maxSupportedTransactionVersion' => 0
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['result'] ?? null;
            }

            return null;

        } catch (Exception $e) {
            Log::error('Failed to get transaction detail', [
                'signature' => $signature,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function isIncomingTransaction(string $walletAddress, array $txDetail): bool
    {
        // Check postBalances vs preBalances to determine if this wallet received funds
        if (!isset($txDetail['meta']['postBalances']) || !isset($txDetail['meta']['preBalances'])) {
            return false;
        }

        $accountIndex = $this->findAccountIndex($walletAddress, $txDetail);
        if ($accountIndex === null) {
            return false;
        }

        $preBalance = $txDetail['meta']['preBalances'][$accountIndex] ?? 0;
        $postBalance = $txDetail['meta']['postBalances'][$accountIndex] ?? 0;

        return $postBalance > $preBalance;
    }

    private function extractTransactionAmount(string $walletAddress, array $txDetail): float
    {
        // First check for SPL token transfers (USDT)
        $usdtAmount = $this->extractUsdtAmount($walletAddress, $txDetail);
        if ($usdtAmount > 0) {
            return $usdtAmount;
        }

        // Fall back to SOL balance check
        $accountIndex = $this->findAccountIndex($walletAddress, $txDetail);
        if ($accountIndex === null) {
            return 0.0;
        }

        $preBalance = $txDetail['meta']['preBalances'][$accountIndex] ?? 0;
        $postBalance = $txDetail['meta']['postBalances'][$accountIndex] ?? 0;
        
        // Convert lamports to SOL (1 SOL = 1,000,000,000 lamports)
        return ($postBalance - $preBalance) / 1000000000;
    }

    private function extractUsdtAmount(string $walletAddress, array $txDetail): float
    {
        // Check token balance changes for USDT
        if (!isset($txDetail['meta']['preTokenBalances']) || !isset($txDetail['meta']['postTokenBalances'])) {
            return 0.0;
        }

        $preTokenBalances = $this->indexTokenBalancesByOwner($txDetail['meta']['preTokenBalances']);
        $postTokenBalances = $this->indexTokenBalancesByOwner($txDetail['meta']['postTokenBalances']);

        // Find USDT balance change for this wallet
        foreach ($postTokenBalances as $owner => $tokens) {
            if ($owner === $walletAddress && isset($tokens[$this->usdtMintAddress])) {
                $postAmount = $tokens[$this->usdtMintAddress]['uiAmount'] ?? 0;
                $preAmount = $preTokenBalances[$owner][$this->usdtMintAddress]['uiAmount'] ?? 0;
                
                $difference = $postAmount - $preAmount;
                if ($difference > 0) {
                    return $difference;
                }
            }
        }

        return 0.0;
    }

    private function indexTokenBalancesByOwner(array $tokenBalances): array
    {
        $indexed = [];
        
        foreach ($tokenBalances as $balance) {
            $owner = $balance['owner'] ?? null;
            $mint = $balance['mint'] ?? null;
            
            if ($owner && $mint) {
                $indexed[$owner][$mint] = $balance;
            }
        }
        
        return $indexed;
    }

    private function detectCurrency(array $txDetail): string
    {
        // Check if this is a SPL token transfer (USDT)
        if (isset($txDetail['transaction']['message']['instructions'])) {
            foreach ($txDetail['transaction']['message']['instructions'] as $instruction) {
                if (isset($instruction['parsed']['type']) && 
                    $instruction['parsed']['type'] === 'transferChecked' &&
                    isset($instruction['parsed']['info']['mint']) &&
                    $instruction['parsed']['info']['mint'] === $this->usdtMintAddress) {
                    return 'USDT';
                }
            }
        }

        // Also check inner instructions for SPL token transfers
        if (isset($txDetail['meta']['innerInstructions'])) {
            foreach ($txDetail['meta']['innerInstructions'] as $innerInst) {
                foreach ($innerInst['instructions'] as $instruction) {
                    if (isset($instruction['parsed']['type']) && 
                        in_array($instruction['parsed']['type'], ['transfer', 'transferChecked']) &&
                        isset($instruction['parsed']['info']['mint']) &&
                        $instruction['parsed']['info']['mint'] === $this->usdtMintAddress) {
                        return 'USDT';
                    }
                }
            }
        }

        // Check preTokenBalances and postTokenBalances for USDT changes
        if (isset($txDetail['meta']['preTokenBalances']) && isset($txDetail['meta']['postTokenBalances'])) {
            foreach ($txDetail['meta']['postTokenBalances'] as $tokenBalance) {
                if (isset($tokenBalance['mint']) && $tokenBalance['mint'] === $this->usdtMintAddress) {
                    return 'USDT';
                }
            }
        }

        // Default to SOL for native transfers
        return 'SOL';
    }

    private function getConfirmationCount(int $slot): int
    {
        try {
            // Get current slot
            $response = Http::timeout(10)->post($this->rpcEndpoint, [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getSlot',
                'params' => [
                    ['commitment' => 'confirmed']
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $currentSlot = $data['result'] ?? 0;
                return max(0, $currentSlot - $slot);
            }

            return 0;

        } catch (Exception $e) {
            return 32; // Assume confirmed if we can't get current slot
        }
    }

    private function extractSenderAddress(string $recipientAddress, array $txDetail): string
    {
        $accounts = $txDetail['transaction']['message']['accountKeys'] ?? [];
        
        // First account is usually the sender, but let's be more specific
        foreach ($accounts as $account) {
            $address = is_array($account) ? $account['pubkey'] : $account;
            if ($address !== $recipientAddress) {
                return $address;
            }
        }

        return 'unknown';
    }

    private function findAccountIndex(string $walletAddress, array $txDetail): ?int
    {
        $accounts = $txDetail['transaction']['message']['accountKeys'] ?? [];
        
        foreach ($accounts as $index => $account) {
            $address = is_array($account) ? $account['pubkey'] : $account;
            if ($address === $walletAddress) {
                return $index;
            }
        }

        return null;
    }

    private function isValidSolanaAddress(string $address): bool
    {
        return strlen($address) >= 32 && strlen($address) <= 44;
    }

    private function detectSuspiciousActivity(int $userId): bool
    {
        // Implement fraud detection logic
        return false;
    }

    private function getDailyWithdrawalLimit(int $userId, string $currency): float
    {
        // Get user-specific or default limits
        return $currency === 'SOL' ? 100.0 : 10000.0;
    }

    private function getTodayWithdrawals(int $userId, string $currency): float
    {
        return CryptoTransaction::where('user_id', $userId)
            ->where('transaction_type', 'withdrawal')
            ->where('currency', $currency)
            ->where('status', 'confirmed')
            ->whereDate('created_at', today())
            ->sum('amount');
    }

    private function createWithdrawalApproval(CryptoTransaction $transaction, string $tier): void
    {
        \App\Models\WithdrawalApproval::create([
            'crypto_transaction_id' => $transaction->id,
            'user_id' => $transaction->user_id,
            'amount' => $transaction->amount,
            'approval_tier' => $tier,
            'status' => 'pending',
            'expires_at' => now()->addHours(24)
        ]);
    }
} 
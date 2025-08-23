<?php

namespace App\Console\Commands;

use App\Models\CryptoWallet;
use App\Services\SolanaWalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class MonitorCryptoDeposits extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'crypto:monitor-deposits
                          {--interval=30 : Monitoring interval in seconds}
                          {--wallets= : Comma-separated wallet addresses to monitor (optional)}
                          {--once : Run monitoring once instead of continuously}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor crypto deposits for all active wallets continuously';

    private SolanaWalletService $walletService;
    private int $monitoringInterval;
    private bool $shouldStop = false;

    public function __construct(SolanaWalletService $walletService)
    {
        parent::__construct();
        $this->walletService = $walletService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->monitoringInterval = (int) $this->option('interval');
        $runOnce = $this->option('once');
        $specificWallets = $this->option('wallets');

        $this->info("🚀 Starting crypto deposit monitoring...");
        $this->info("⏱️  Monitoring interval: {$this->monitoringInterval} seconds");
        
        if ($runOnce) {
            $this->info("🔄 Running one-time scan...");
            return $this->performMonitoringCycle($specificWallets);
        }

        $this->info("♾️  Continuous monitoring mode - Press Ctrl+C to stop");
        $this->setupSignalHandlers();

        while (!$this->shouldStop) {
            try {
                $this->performMonitoringCycle($specificWallets);
                
                if (!$this->shouldStop) {
                    $this->comment("💤 Waiting {$this->monitoringInterval} seconds before next scan...");
                    sleep($this->monitoringInterval);
                }
            } catch (Exception $e) {
                $this->error("❌ Monitoring cycle failed: " . $e->getMessage());
                Log::error('Crypto deposit monitoring failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Wait before retrying on error
                sleep(min($this->monitoringInterval, 60));
            }
        }

        $this->info("🛑 Crypto deposit monitoring stopped");
        return 0;
    }

    private function performMonitoringCycle(?string $specificWallets): int
    {
        $startTime = microtime(true);
        
        // Get wallets to monitor
        $wallets = $this->getWalletsToMonitor($specificWallets);
        
        if ($wallets->isEmpty()) {
            $this->warn("⚠️  No active crypto wallets found to monitor");
            return 1;
        }

        $this->info("🔍 Monitoring {$wallets->count()} crypto wallets...");
        
        $totalDeposits = 0;
        $totalAmount = 0.0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($wallets->count());
        $progressBar->start();

        foreach ($wallets as $wallet) {
            try {
                $result = $this->walletService->monitorDeposits($wallet->wallet_address);
                
                if ($result['success']) {
                    $depositsFound = $result['processed_count'] ?? 0;
                    $totalDeposits += $depositsFound;
                    
                    if ($depositsFound > 0) {
                        foreach ($result['new_deposits'] as $deposit) {
                            $totalAmount += $deposit['amount'];
                            $this->newLine();
                            $this->info("💰 New deposit: {$deposit['amount']} {$deposit['currency']} for user {$wallet->user_id}");
                        }
                    }
                } else {
                    $errors++;
                    $this->newLine();
                    $this->error("❌ Failed to monitor wallet {$wallet->wallet_address}: " . ($result['error'] ?? 'Unknown error'));
                }
                
            } catch (Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("❌ Exception monitoring wallet {$wallet->wallet_address}: " . $e->getMessage());
                
                Log::error('Individual wallet monitoring failed', [
                    'wallet_id' => $wallet->id,
                    'wallet_address' => $wallet->wallet_address,
                    'user_id' => $wallet->user_id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $duration = round(microtime(true) - $startTime, 2);
        
        // Summary
        $this->info("📊 Monitoring cycle completed in {$duration}s");
        $this->info("✅ New deposits found: {$totalDeposits}");
        if ($totalAmount > 0) {
            $this->info("💵 Total deposit amount: $" . number_format($totalAmount, 2));
        }
        if ($errors > 0) {
            $this->warn("⚠️  Errors encountered: {$errors}");
        }

        // Log monitoring summary
        Log::info('Crypto deposit monitoring cycle completed', [
            'wallets_monitored' => $wallets->count(),
            'new_deposits' => $totalDeposits,
            'total_amount_usd' => $totalAmount,
            'errors' => $errors,
            'duration_seconds' => $duration
        ]);

        return $errors > 0 ? 1 : 0;
    }

    private function getWalletsToMonitor(?string $specificWallets): \Illuminate\Database\Eloquent\Collection
    {
        $query = CryptoWallet::where('status', 'active')
            ->where('wallet_type', 'solana');

        if ($specificWallets) {
            $walletAddresses = array_map('trim', explode(',', $specificWallets));
            $query->whereIn('wallet_address', $walletAddresses);
        }

        return $query->with('user')->get();
    }

    private function setupSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
            pcntl_async_signals(true);
        }
    }

    public function handleSignal(int $signal): void
    {
        $this->newLine();
        $this->info("📡 Received signal {$signal}, shutting down gracefully...");
        $this->shouldStop = true;
    }
} 
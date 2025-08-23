<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MagicLoginToken;

class CleanupMagicTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magic-tokens:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired magic login tokens from the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Cleaning up expired magic login tokens...');
        
        $deletedCount = MagicLoginToken::cleanupExpiredTokens();
        
        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} expired token(s).");
        } else {
            $this->info('No expired tokens found.');
        }
        
        return Command::SUCCESS;
    }
} 
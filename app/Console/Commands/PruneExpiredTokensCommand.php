<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ApiTokenManager;
use Illuminate\Console\Command;

/**
 * Prune Expired API Tokens Command
 * 
 * Removes expired personal access tokens from the database.
 */
class PruneExpiredTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tokens:prune-expired {--hours=24 : Hours after expiration to delete tokens}';

    /**
     * The console command description.
     */
    protected $description = 'Prune expired personal access tokens';

    /**
     * Execute the console command.
     */
    public function handle(ApiTokenManager $tokenManager): int
    {
        $hours = (int) $this->option('hours');
        
        $this->info("Pruning tokens expired for more than {$hours} hours...");
        
        $count = $tokenManager->pruneExpiredTokens($hours);
        
        $this->info("Pruned {$count} expired tokens.");
        
        return self::SUCCESS;
    }
}
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PersonalAccessToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Prune Expired Personal Access Tokens
 * 
 * Security command to clean up expired API tokens and prevent
 * token table bloat that could impact performance and security.
 * 
 * Should be scheduled to run daily via Laravel Scheduler.
 */
class PruneExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tokens:prune 
                           {--hours=24 : Hours after expiration to delete tokens}
                           {--dry-run : Show what would be deleted without actually deleting}
                           {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Prune expired personal access tokens for security and performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($hours < 1) {
            $this->error('Hours must be at least 1');
            return 1;
        }

        // Find expired tokens
        $expiredTokens = PersonalAccessToken::expired()
            ->where('created_at', '<', now()->subHours($hours));

        $count = $expiredTokens->count();

        if ($count === 0) {
            $this->info('No expired tokens found to prune.');
            return 0;
        }

        // Show what will be deleted
        $this->info("Found {$count} expired tokens to prune (older than {$hours} hours after expiration)");

        if ($dryRun) {
            $this->table(
                ['ID', 'Name', 'Tokenable Type', 'Expired At', 'Created At'],
                $expiredTokens->limit(10)->get()->map(function ($token) {
                    return [
                        $token->id,
                        $token->name,
                        $token->tokenable_type,
                        $token->expires_at?->format('Y-m-d H:i:s') ?? 'Never',
                        $token->created_at->format('Y-m-d H:i:s'),
                    ];
                })->toArray()
            );

            if ($count > 10) {
                $this->info("... and " . ($count - 10) . " more tokens");
            }

            $this->info('Dry run completed. Use --force to actually delete tokens.');
            return 0;
        }

        // Confirm deletion unless forced
        if (!$force && !$this->confirm("Are you sure you want to delete {$count} expired tokens?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Perform deletion with progress bar
        $this->info('Deleting expired tokens...');
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $deleted = 0;
        $batchSize = 100;

        // Delete in batches to avoid memory issues
        do {
            $batch = PersonalAccessToken::expired()
                ->where('created_at', '<', now()->subHours($hours))
                ->limit($batchSize)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $token) {
                $token->delete();
                $deleted++;
                $progressBar->advance();
            }

            // Small delay to prevent overwhelming the database
            usleep(10000); // 10ms

        } while ($batch->count() === $batchSize);

        $progressBar->finish();
        $this->newLine();

        // Log the operation
        Log::info('Expired API tokens pruned', [
            'tokens_deleted' => $deleted,
            'hours_threshold' => $hours,
            'command_user' => $this->getUser(),
        ]);

        $this->info("Successfully pruned {$deleted} expired tokens.");

        // Show statistics
        $this->showTokenStatistics();

        return 0;
    }

    /**
     * Show current token statistics.
     */
    private function showTokenStatistics(): void
    {
        $total = PersonalAccessToken::count();
        $active = PersonalAccessToken::active()->count();
        $expired = PersonalAccessToken::expired()->count();
        $recentlyUsed = PersonalAccessToken::recentlyUsed(7)->count();

        $this->newLine();
        $this->info('Current Token Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Tokens', $total],
                ['Active Tokens', $active],
                ['Expired Tokens', $expired],
                ['Used in Last 7 Days', $recentlyUsed],
            ]
        );

        // Warn if too many expired tokens remain
        if ($expired > 1000) {
            $this->warn("Warning: {$expired} expired tokens still exist. Consider running with fewer hours threshold.");
        }
    }

    /**
     * Get the current user running the command.
     */
    private function getUser(): string
    {
        return posix_getpwuid(posix_geteuid())['name'] ?? 'unknown';
    }
}
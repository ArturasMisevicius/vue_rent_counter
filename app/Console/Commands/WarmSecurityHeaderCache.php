<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Security\SecurityHeaderFactory;
use App\ValueObjects\SecurityNonce;
use Illuminate\Console\Command;

/**
 * Warm Security Header Cache Command
 * 
 * Pre-warms the security header cache with all context templates
 * to improve first-request performance.
 */
final class WarmSecurityHeaderCache extends Command
{
    protected $signature = 'security:warm-cache 
                           {--force : Force cache refresh even if already warmed}';

    protected $description = 'Warm the security header cache with all context templates';

    public function handle(SecurityHeaderFactory $factory): int
    {
        $this->info('Warming security header cache...');
        
        $contexts = ['api', 'admin', 'tenant', 'production', 'development'];
        $nonce = SecurityNonce::generate();
        
        $startTime = microtime(true);
        
        foreach ($contexts as $context) {
            $this->line("Warming cache for context: {$context}");
            
            try {
                if (method_exists($factory, 'createForContextOptimized')) {
                    $factory->createForContextOptimized($context, $nonce);
                } else {
                    $factory->createForContext($context, $nonce);
                }
                
                $this->info("✓ {$context} context cached");
            } catch (\Exception $e) {
                $this->error("✗ Failed to cache {$context}: " . $e->getMessage());
                return Command::FAILURE;
            }
        }
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        $this->newLine();
        $this->info("Security header cache warmed successfully in {$duration}ms");
        $this->info("All {$contexts} contexts are now cached for optimal performance");
        
        return Command::SUCCESS;
    }
}
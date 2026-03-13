<?php

namespace App\Console\Commands;

use App\Services\ScheduledExportService;
use Illuminate\Console\Command;

class CleanupExportFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:cleanup {--days=30 : Number of days to keep export files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old export files to free up storage space';

    /**
     * Execute the console command.
     */
    public function handle(ScheduledExportService $exportService): int
    {
        $daysToKeep = (int) $this->option('days');
        
        $this->info("Cleaning up export files older than {$daysToKeep} days...");
        
        try {
            $result = $exportService->cleanupOldExports($daysToKeep);
            
            $this->info("✓ Cleanup completed successfully");
            $this->line("  - Files deleted: " . $result['deleted_count']);
            $this->line("  - Cutoff date: " . $result['cutoff_date']);
            
            if ($result['deleted_count'] > 0) {
                $this->line("  - Deleted files:");
                foreach ($result['deleted_files'] as $file) {
                    $this->line("    • " . $file);
                }
            }
            
            // Show current statistics
            $stats = $exportService->getExportStatistics();
            $this->line("");
            $this->info("Current export storage statistics:");
            $this->line("  - Total files: " . $stats['total_files']);
            $this->line("  - Total size: " . $stats['total_size_mb'] . " MB");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cleanup process failed: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
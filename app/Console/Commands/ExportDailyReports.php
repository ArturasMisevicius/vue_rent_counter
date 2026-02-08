<?php

namespace App\Console\Commands;

use App\Services\ScheduledExportService;
use Illuminate\Console\Command;

class ExportDailyReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:daily-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute daily scheduled exports for superadmin dashboard';

    /**
     * Execute the console command.
     */
    public function handle(ScheduledExportService $exportService): int
    {
        $this->info('Starting daily export process...');
        
        try {
            $results = $exportService->executeDailyExports();
            
            foreach ($results as $exportType => $result) {
                if ($result['success']) {
                    $this->info("✓ {$exportType}: " . count($result['files']) . ' files generated');
                    
                    foreach ($result['files'] as $file) {
                        $this->line("  - " . basename($file));
                    }
                } else {
                    $this->error("✗ {$exportType}: " . $result['error']);
                }
            }
            
            $this->info('Daily export process completed successfully.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Daily export process failed: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
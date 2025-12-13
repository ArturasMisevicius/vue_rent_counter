<?php

namespace App\Console\Commands;

use App\Services\ScheduledExportService;
use Illuminate\Console\Command;

class ExportWeeklyReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:weekly-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute weekly scheduled exports for superadmin dashboard';

    /**
     * Execute the console command.
     */
    public function handle(ScheduledExportService $exportService): int
    {
        $this->info('Starting weekly export process...');
        
        try {
            $results = $exportService->executeWeeklyExports();
            
            foreach ($results as $exportType => $result) {
                if ($result['success']) {
                    $this->info("✓ {$exportType}: " . count($result['files']) . ' files generated');
                    
                    foreach ($result['files'] as $file) {
                        $this->line("  - " . basename($file));
                    }
                    
                    if (isset($result['period'])) {
                        $this->line("  Period: " . $result['period']);
                    }
                } else {
                    $this->error("✗ {$exportType}: " . $result['error']);
                }
            }
            
            $this->info('Weekly export process completed successfully.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Weekly export process failed: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
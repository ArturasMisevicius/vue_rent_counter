<?php

namespace App\Console\Commands;

use App\Models\Building;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Calculate and store summer average gyvatukas for all buildings.
 * 
 * This command should run at the start of the heating season (October 1st)
 * to calculate the average circulation energy from the previous summer
 * (May-September) for use as a norm during winter billing.
 */
class CalculateSummerAverageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gyvatukas:calculate-summer-average 
                            {--year= : The year for which to calculate (defaults to previous year if run in January-April, current year otherwise)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and store summer average gyvatukas (circulation fee) for all buildings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting summer average gyvatukas calculation...');

        // Determine which year's summer to calculate
        $year = $this->option('year');
        
        if (!$year) {
            $currentMonth = now()->month;
            // If we're in Jan-Apr, calculate for previous year's summer
            // If we're in May-Dec, calculate for current year's summer
            $year = $currentMonth <= 4 ? now()->year - 1 : now()->year;
        }

        // Define summer period: May 1 - September 30
        $startDate = Carbon::create($year, 5, 1)->startOfDay();
        $endDate = Carbon::create($year, 9, 30)->endOfDay();

        $this->info("Calculating for summer period: {$startDate->toDateString()} to {$endDate->toDateString()}");

        // Get all buildings
        $buildings = Building::all();

        if ($buildings->isEmpty()) {
            $this->warn('No buildings found in the system.');
            return self::SUCCESS;
        }

        $this->info("Found {$buildings->count()} building(s) to process.");

        $successCount = 0;
        $errorCount = 0;

        // Process each building
        $progressBar = $this->output->createProgressBar($buildings->count());
        $progressBar->start();

        foreach ($buildings as $building) {
            try {
                $average = $building->calculateSummerAverage($startDate, $endDate);
                
                $this->newLine();
                $this->line("Building ID {$building->id} ({$building->address}): {$average} kWh average");
                
                $successCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to calculate for Building ID {$building->id}: {$e->getMessage()}");
                $errorCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("Calculation complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Successful', $successCount],
                ['Failed', $errorCount],
                ['Total', $buildings->count()],
            ]
        );

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\GyvatukasSummerAverageService;
use App\ValueObjects\SummerPeriod;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Calculate Summer Average Gyvatukas Command
 * 
 * This command calculates the average gyvatukas (circulation fee) for all buildings
 * across the summer months (May-September) and stores the result for use during
 * the heating season (October-April).
 * 
 * Scheduled to run automatically on October 1st each year.
 * 
 * Requirements: 4.4
 */
final class CalculateSummerAverageCommand extends Command
{
    /**
     * Create a new command instance.
     */
    public function __construct(
        private readonly GyvatukasSummerAverageService $service
    ) {
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gyvatukas:calculate-summer-average 
                            {--year= : The year to calculate for (defaults to previous year)}
                            {--building= : Calculate for a specific building ID only}
                            {--force : Force recalculation even if already calculated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and store summer average gyvatukas for all buildings (May-September)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Validate and parse input
            $year = $this->getYear();
            $buildingId = $this->getBuildingId();
            $force = $this->option('force') ?? false;

            // Create summer period
            $period = new SummerPeriod($year);

            $this->info('Starting summer average gyvatukas calculation...');
            $this->info("Calculating for period: {$period->description()}");

            // Process buildings
            if ($buildingId !== null) {
                return $this->processSingleBuilding($buildingId, $period, $force);
            }

            return $this->processAllBuildings($period, $force);
        } catch (InvalidArgumentException $e) {
            $this->error("Invalid input: {$e->getMessage()}");
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error("Unexpected error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Process a single building.
     */
    private function processSingleBuilding(int $buildingId, SummerPeriod $period, bool $force): int
    {
        $result = $this->service->calculateForBuildingId($buildingId, $period, $force);

        if ($result === null) {
            $this->error("Building #{$buildingId} not found.");
            return self::FAILURE;
        }

        $this->displayResult($result);

        return $result->isSuccess() ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Process all buildings with chunked processing.
     */
    private function processAllBuildings(SummerPeriod $period, bool $force): int
    {
        $progressBar = null;

        $stats = $this->service->calculateForAllBuildings(
            period: $period,
            force: $force,
            chunkSize: 100,
            callback: function ($result) use (&$progressBar) {
                if ($progressBar === null) {
                    // Initialize progress bar on first callback
                    $progressBar = $this->output->createProgressBar();
                    $progressBar->start();
                }

                $this->displayResult($result);
                $progressBar->advance();
            }
        );

        if ($progressBar !== null) {
            $progressBar->finish();
            $this->newLine(2);
        }

        // Display summary
        $this->displaySummary($stats);

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Display a single calculation result.
     */
    private function displayResult($result): void
    {
        $this->newLine();

        match ($result->status) {
            'success' => $this->line("  ✓ {$result->getMessage()}"),
            'skipped' => $this->line("  ⊘ {$result->getMessage()}"),
            'failed' => $this->error("  ✗ {$result->getMessage()}"),
            default => null,
        };
    }

    /**
     * Display summary statistics.
     */
    private function displaySummary(array $stats): void
    {
        $total = $stats['success'] + $stats['skipped'] + $stats['failed'];

        $this->info('=== Summary ===');
        $this->info("Total buildings: {$total}");
        $this->info("Successfully calculated: {$stats['success']}");

        if ($stats['skipped'] > 0) {
            $this->line("Skipped (already calculated): {$stats['skipped']}");
        }

        if ($stats['failed'] > 0) {
            $this->error("Errors: {$stats['failed']}");
            $this->newLine();
            $this->warn('Some buildings failed to calculate. Check the logs for details.');
        } else {
            $this->newLine();
            $this->info('✓ Summer average calculation completed successfully!');
        }
    }

    /**
     * Get and validate the year option.
     *
     * @throws InvalidArgumentException
     */
    private function getYear(): int
    {
        $year = $this->option('year');

        if ($year === null) {
            return now()->subYear()->year;
        }

        if (!is_numeric($year)) {
            throw new InvalidArgumentException('Year must be a numeric value');
        }

        return (int) $year;
    }

    /**
     * Get and validate the building ID option.
     *
     * @throws InvalidArgumentException
     */
    private function getBuildingId(): ?int
    {
        $buildingId = $this->option('building');

        if ($buildingId === null) {
            return null;
        }

        if (!is_numeric($buildingId) || (int) $buildingId <= 0) {
            throw new InvalidArgumentException('Building ID must be a positive integer');
        }

        return (int) $buildingId;
    }
}

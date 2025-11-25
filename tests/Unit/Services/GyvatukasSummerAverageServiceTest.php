<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Building;
use App\Services\GyvatukasSummerAverageService;
use App\ValueObjects\SummerPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class GyvatukasSummerAverageServiceTest extends TestCase
{
    use RefreshDatabase;

    private GyvatukasSummerAverageService $service;
    private Building $building;
    private SummerPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new GyvatukasSummerAverageService();
        $this->building = Building::factory()->create();
        $this->period = new SummerPeriod(2023);
    }

    public function test_calculates_for_building_successfully(): void
    {
        $result = $this->service->calculateForBuilding($this->building, $this->period);

        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->average);
        $this->building->refresh();
        $this->assertNotNull($this->building->gyvatukas_last_calculated);
    }

    public function test_skips_already_calculated_building(): void
    {
        // First calculation
        $result1 = $this->service->calculateForBuilding($this->building, $this->period);
        $this->assertTrue($result1->isSuccess());

        // Get a fresh instance of the building from database
        $freshBuilding = Building::find($this->building->id);

        // Second calculation without force should skip
        $result = $this->service->calculateForBuilding($freshBuilding, $this->period, false);

        $this->assertTrue($result->isSkipped());
        $this->assertStringContainsString('Already calculated', $result->errorMessage);
    }

    public function test_forces_recalculation_when_requested(): void
    {
        // First calculation
        $this->service->calculateForBuilding($this->building, $this->period);

        // Force recalculation
        $result = $this->service->calculateForBuilding($this->building, $this->period, true);

        $this->assertTrue($result->isSuccess());
    }

    public function test_calculates_for_multiple_buildings(): void
    {
        $buildings = Building::factory()->count(3)->create();

        $results = $this->service->calculateForBuildings($buildings, $this->period);

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($result) => $result->isSuccess()));
    }

    public function test_calculates_for_all_buildings_with_chunking(): void
    {
        // Create 4 more buildings (setUp already created 1, so total will be 5)
        Building::factory()->count(4)->create();

        $callbackCount = 0;
        $stats = $this->service->calculateForAllBuildings(
            period: $this->period,
            force: false,
            chunkSize: 2,
            callback: function () use (&$callbackCount) {
                $callbackCount++;
            }
        );

        $this->assertEquals(5, $callbackCount);
        $this->assertEquals(5, $stats['success']);
        $this->assertEquals(0, $stats['skipped']);
        $this->assertEquals(0, $stats['failed']);
    }

    public function test_calculates_for_building_by_id(): void
    {
        $result = $this->service->calculateForBuildingId(
            $this->building->id,
            $this->period
        );

        $this->assertNotNull($result);
        $this->assertTrue($result->isSuccess());
    }

    public function test_returns_null_for_nonexistent_building_id(): void
    {
        $result = $this->service->calculateForBuildingId(99999, $this->period);

        $this->assertNull($result);
    }

    public function test_logs_successful_calculation(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Summer average calculated for building', \Mockery::type('array'));

        $this->service->calculateForBuilding($this->building, $this->period);
    }

    public function test_handles_calculation_errors_gracefully(): void
    {
        // Create a building that will cause an error
        $building = Building::factory()->create();
        
        // Mock the calculateSummerAverage method to throw an exception
        $building = \Mockery::mock($building)->makePartial();
        $building->shouldReceive('calculateSummerAverage')
            ->andThrow(new \Exception('Test error'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to calculate summer average for building', \Mockery::type('array'));

        $result = $this->service->calculateForBuilding($building, $this->period);

        $this->assertTrue($result->isFailed());
        $this->assertEquals('Test error', $result->errorMessage);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\BillingRecord;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Services\AnomalyDetector;
use App\Services\ConsumptionPredictor;
use App\Services\PredictiveAnalyticsService;
use App\Services\TrendAnalyzer;
use App\ValueObjects\AnomalyDetectionResult;
use App\ValueObjects\ConsumptionPrediction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PredictiveAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private PredictiveAnalyticsService $service;
    private ConsumptionPredictor $predictor;
    private AnomalyDetector $detector;
    private TrendAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->predictor = new ConsumptionPredictor();
        $this->detector = new AnomalyDetector();
        $this->analyzer = new TrendAnalyzer();
        
        $this->service = new PredictiveAnalyticsService(
            $this->predictor,
            $this->detector,
            $this->analyzer
        );
    }

    public function test_predicts_consumption_with_historical_data(): void
    {
        $property = Property::factory()->create(['area_sqm' => 75.0, 'floor' => 2, 'rooms' => 3]);
        $utilityService = UtilityService::factory()->create(['type' => 'electricity']);
        
        // Create historical billing records
        $startDate = now()->subMonths(6);
        for ($i = 0; $i < 6; $i++) {
            BillingRecord::factory()->create([
                'property_id' => $property->id,
                'utility_service_id' => $utilityService->id,
                'consumption' => 150 + ($i * 10), // Increasing trend
                'reading_date' => $startDate->copy()->addMonths($i),
            ]);
        }

        $predictionStart = now()->startOfMonth();
        $predictionEnd = now()->endOfMonth();

        $prediction = $this->service->predictConsumption(
            $property,
            $utilityService,
            $predictionStart,
            $predictionEnd
        );

        $this->assertInstanceOf(ConsumptionPrediction::class, $prediction);
        $this->assertGreaterThan(0, $prediction->getEstimatedConsumption());
        $this->assertIsArray($prediction->getConfidenceInterval());
        $this->assertArrayHasKey('lower', $prediction->getConfidenceInterval());
        $this->assertArrayHasKey('upper', $prediction->getConfidenceInterval());
        $this->assertGreaterThan(0, $prediction->getConfidenceLevel());
        $this->assertLessThanOrEqual(1, $prediction->getConfidenceLevel());
    }

    public function test_predicts_consumption_without_historical_data(): void
    {
        $property = Property::factory()->create(['area_sqm' => 100.0]);
        $utilityService = UtilityService::factory()->create(['type' => 'heating']);

        $predictionStart = now()->startOfMonth();
        $predictionEnd = now()->endOfMonth();

        $prediction = $this->service->predictConsumption(
            $property,
            $utilityService,
            $predictionStart,
            $predictionEnd
        );

        $this->assertInstanceOf(ConsumptionPrediction::class, $prediction);
        $this->assertGreaterThan(0, $prediction->getEstimatedConsumption());
        $this->assertEquals('default_estimation', $prediction->getPredictionMethod());
        $this->assertLessThan(0.5, $prediction->getConfidenceLevel()); // Low confidence for no data
    }

    public function test_detects_anomalies_in_consumption(): void
    {
        $property = Property::factory()->create();
        $utilityService = UtilityService::factory()->create(['type' => 'water']);
        
        // Create normal consumption records
        for ($i = 0; $i < 10; $i++) {
            BillingRecord::factory()->create([
                'property_id' => $property->id,
                'utility_service_id' => $utilityService->id,
                'consumption' => 100 + rand(-10, 10), // Normal variation
                'reading_date' => now()->subDays(30 - $i * 3),
            ]);
        }
        
        // Create anomalous record
        BillingRecord::factory()->create([
            'property_id' => $property->id,
            'utility_service_id' => $utilityService->id,
            'consumption' => 500, // Anomalously high
            'reading_date' => now()->subDays(5),
        ]);

        $result = $this->service->detectAnomalies(
            $property,
            $utilityService,
            now()->subMonth(),
            now()
        );

        $this->assertInstanceOf(AnomalyDetectionResult::class, $result);
        $this->assertTrue($result->hasAnomalies());
        $this->assertGreaterThan(0, $result->getAnomalyCount());
    }

    public function test_analyzes_consumption_trends(): void
    {
        $property = Property::factory()->create();
        $utilityService = UtilityService::factory()->create(['type' => 'electricity']);
        
        // Create trend data (increasing consumption)
        for ($i = 0; $i < 12; $i++) {
            BillingRecord::factory()->create([
                'property_id' => $property->id,
                'utility_service_id' => $utilityService->id,
                'consumption' => 100 + ($i * 5), // Increasing trend
                'reading_date' => now()->subMonths(12 - $i),
            ]);
        }

        $trends = $this->service->analyzeTrends($property, $utilityService, 12);

        $this->assertIsArray($trends);
        $this->assertArrayHasKey('growth_rate', $trends);
        $this->assertArrayHasKey('seasonal_patterns', $trends);
        $this->assertArrayHasKey('volatility', $trends);
        $this->assertArrayHasKey('efficiency_trends', $trends);
        $this->assertGreaterThan(0, $trends['growth_rate']); // Should detect increasing trend
    }

    public function test_generates_billing_estimate(): void
    {
        $property = Property::factory()->create();
        $utilityService = UtilityService::factory()->create([
            'type' => 'electricity',
            'base_rate' => 0.25,
        ]);
        
        ServiceConfiguration::factory()->create([
            'property_id' => $property->id,
            'utility_service_id' => $utilityService->id,
            'tariff_structure' => ['rate' => 0.25],
            'base_rate' => 0.25,
        ]);
        
        // Create historical data
        for ($i = 0; $i < 6; $i++) {
            BillingRecord::factory()->create([
                'property_id' => $property->id,
                'utility_service_id' => $utilityService->id,
                'consumption' => 200,
                'reading_date' => now()->subMonths(6 - $i),
            ]);
        }

        $billingMonth = now()->addMonth();
        $estimate = $this->service->generateBillingEstimate(
            $property,
            $utilityService,
            $billingMonth
        );

        $this->assertIsArray($estimate);
        $this->assertArrayHasKey('predicted_consumption', $estimate);
        $this->assertArrayHasKey('confidence_interval', $estimate);
        $this->assertArrayHasKey('estimated_cost', $estimate);
        $this->assertArrayHasKey('cost_range', $estimate);
        $this->assertArrayHasKey('factors', $estimate);
        
        $this->assertGreaterThan(0, $estimate['predicted_consumption']);
        $this->assertGreaterThan(0, $estimate['estimated_cost']);
        $this->assertIsArray($estimate['cost_range']);
        $this->assertArrayHasKey('min', $estimate['cost_range']);
        $this->assertArrayHasKey('max', $estimate['cost_range']);
    }

    public function test_throws_exception_for_missing_service_configuration(): void
    {
        $property = Property::factory()->create();
        $utilityService = UtilityService::factory()->create();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No service configuration found');

        $this->service->generateBillingEstimate(
            $property,
            $utilityService,
            now()->addMonth()
        );
    }

    public function test_applies_seasonal_factors_correctly(): void
    {
        $property = Property::factory()->create();
        $heatingService = UtilityService::factory()->create(['type' => 'heating']);
        
        // Create winter data
        BillingRecord::factory()->create([
            'property_id' => $property->id,
            'utility_service_id' => $heatingService->id,
            'consumption' => 100,
            'reading_date' => Carbon::create(2024, 1, 15), // Winter
        ]);

        // Create summer data  
        BillingRecord::factory()->create([
            'property_id' => $property->id,
            'utility_service_id' => $heatingService->id,
            'consumption' => 20,
            'reading_date' => Carbon::create(2024, 7, 15), // Summer
        ]);

        $winterPrediction = $this->service->predictConsumption(
            $property,
            $heatingService,
            Carbon::create(2024, 12, 1),
            Carbon::create(2024, 12, 31)
        );

        $summerPrediction = $this->service->predictConsumption(
            $property,
            $heatingService,
            Carbon::create(2024, 6, 1),
            Carbon::create(2024, 6, 30)
        );

        // Winter prediction should be higher than summer for heating
        $this->assertGreaterThan(
            $summerPrediction->getEstimatedConsumption(),
            $winterPrediction->getEstimatedConsumption()
        );
    }

    public function test_handles_empty_data_gracefully(): void
    {
        $property = Property::factory()->create();
        $utilityService = UtilityService::factory()->create();

        $result = $this->service->detectAnomalies(
            $property,
            $utilityService,
            now()->subMonth(),
            now()
        );

        $this->assertInstanceOf(AnomalyDetectionResult::class, $result);
        $this->assertFalse($result->hasAnomalies());
        $this->assertEquals(0, $result->getAnomalyCount());

        $trends = $this->service->analyzeTrends($property, $utilityService);
        
        $this->assertIsArray($trends);
        $this->assertEquals('unknown', $trends['trend_direction']);
        $this->assertEquals(0, $trends['growth_rate']);
    }

    public function test_property_factors_affect_predictions(): void
    {
        $smallProperty = Property::factory()->create([
            'area_sqm' => 50.0,
            'floor' => 1,
            'rooms' => 2,
            'is_occupied' => true,
        ]);
        
        $largeProperty = Property::factory()->create([
            'area_sqm' => 150.0,
            'floor' => 5,
            'rooms' => 5,
            'is_occupied' => true,
        ]);
        
        $utilityService = UtilityService::factory()->create(['type' => 'electricity']);

        $smallPrediction = $this->service->predictConsumption(
            $smallProperty,
            $utilityService,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $largePrediction = $this->service->predictConsumption(
            $largeProperty,
            $utilityService,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        // Larger property should have higher predicted consumption
        $this->assertGreaterThan(
            $smallPrediction->getEstimatedConsumption(),
            $largePrediction->getEstimatedConsumption()
        );
    }
}
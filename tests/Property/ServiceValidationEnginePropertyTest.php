<?php

declare(strict_types=1);

namespace Tests\Property;

use App\Enums\InputMethod;
use App\Enums\ValidationStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Models\User;
use App\Services\ServiceValidationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Property-based tests for ServiceValidationEngine.
 * 
 * Tests invariants and edge cases using property-based testing methodology
 * to ensure the validation engine behaves correctly across a wide range of inputs.
 */
class ServiceValidationEnginePropertyTest extends TestCase
{
    use RefreshDatabase;

    private ServiceValidationEngine $validationEngine;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationEngine = app(ServiceValidationEngine::class);
        $this->user = User::factory()->create(['tenant_id' => 1]);
        $this->actingAs($this->user);
    }

    /**
     * Property: Validation results always have required structure
     * 
     * For any meter reading, validation should always return an array with
     * the required keys: is_valid, errors, warnings, recommendations, metadata
     */
    public function test_property_validation_result_structure_invariant(): void
    {
        $this->runPropertyTest(100, function () {
            // Generate random meter reading
            $meter = Meter::factory()->create(['tenant_id' => 1]);
            $reading = MeterReading::factory()->create([
                'tenant_id' => 1,
                'meter_id' => $meter->id,
                'value' => fake()->randomFloat(2, 0, 100000),
                'reading_date' => fake()->dateTimeBetween('-1 year', 'now'),
            ]);

            // Act
            $result = $this->validationEngine->validateMeterReading($reading);

            // Assert invariant: result structure is always consistent
            $this->assertIsArray($result);
            $this->assertArrayHasKey('is_valid', $result);
            $this->assertArrayHasKey('errors', $result);
            $this->assertArrayHasKey('warnings', $result);
            $this->assertArrayHasKey('metadata', $result);
            
            $this->assertIsBool($result['is_valid']);
            $this->assertIsArray($result['errors']);
            $this->assertIsArray($result['warnings']);
            $this->assertIsArray($result['metadata']);
        });
    }

    /**
     * Property: Batch validation count consistency
     * 
     * For any collection of readings, the sum of valid + invalid readings
     * should always equal the total number of readings processed.
     */
    public function test_property_batch_validation_count_consistency(): void
    {
        $this->runPropertyTest(50, function () {
            $meter = Meter::factory()->create(['tenant_id' => 1]);
            
            // Generate random number of readings (1-20)
            $readingCount = fake()->numberBetween(1, 20);
            $readings = collect();
            
            for ($i = 0; $i < $readingCount; $i++) {
                $readings->push(MeterReading::factory()->create([
                    'tenant_id' => 1,
                    'meter_id' => $meter->id,
                    'value' => fake()->randomFloat(2, 0, 10000),
                ]));
            }

            // Act
            $result = $this->validationEngine->batchValidateReadings($readings);

            // Assert invariant: counts are consistent
            $this->assertEquals(
                $readingCount,
                $result['total_readings'],
                'Total readings count mismatch'
            );
            
            $this->assertEquals(
                $result['total_readings'],
                $result['valid_readings'] + $result['invalid_readings'],
                'Valid + Invalid should equal total'
            );
            
            $this->assertEquals(
                $readingCount,
                count($result['results']),
                'Results array should have entry for each reading'
            );
        });
    }

    /**
     * Property: Rate schedule sanitization is idempotent
     * 
     * Applying sanitization multiple times should produce the same result.
     */
    public function test_property_rate_schedule_sanitization_idempotent(): void
    {
        $this->runPropertyTest(30, function () {
            $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 1]);
            
            // Generate random rate schedule
            $rateSchedule = [
                'rate_per_unit' => fake()->randomFloat(4, 0.01, 1.00),
                'effective_from' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
                'monthly_rate' => fake()->randomFloat(2, 10, 1000),
            ];

            // Act - validate twice
            $result1 = $this->validationEngine->validateRateChangeRestrictions($serviceConfig, $rateSchedule);
            $result2 = $this->validationEngine->validateRateChangeRestrictions($serviceConfig, $rateSchedule);

            // Assert invariant: results are identical (idempotent)
            $this->assertEquals($result1, $result2, 'Rate schedule validation should be idempotent');
        });
    }

    /**
     * Property: Validation status transitions are monotonic
     * 
     * Once a reading is validated, it should not become invalid through
     * subsequent validations (assuming no data changes).
     */
    public function test_property_validation_status_monotonic(): void
    {
        $this->runPropertyTest(20, function () {
            $meter = Meter::factory()->create(['tenant_id' => 1]);
            
            // Create a reading with valid consumption
            $reading = MeterReading::factory()->create([
                'tenant_id' => 1,
                'meter_id' => $meter->id,
                'value' => fake()->randomFloat(2, 50, 500), // Reasonable consumption
                'validation_status' => ValidationStatus::PENDING,
            ]);

            // First validation
            $result1 = $this->validationEngine->validateMeterReading($reading);
            
            // If first validation passed, second should also pass (monotonic)
            if ($result1['is_valid']) {
                $result2 = $this->validationEngine->validateMeterReading($reading);
                
                $this->assertTrue(
                    $result2['is_valid'],
                    'Valid reading should remain valid on re-validation'
                );
            }
        });
    }

    /**
     * Property: Estimated reading true-up calculations are symmetric
     * 
     * If estimated reading A vs actual reading B has true-up amount X,
     * then estimated reading B vs actual reading A should have true-up amount -X.
     */
    public function test_property_estimated_reading_true_up_symmetry(): void
    {
        $this->runPropertyTest(25, function () {
            $meter = Meter::factory()->create(['tenant_id' => 1]);
            
            $estimatedValue = fake()->randomFloat(2, 100, 1000);
            $actualValue = fake()->randomFloat(2, 100, 1000);
            
            $estimatedReading = MeterReading::factory()->create([
                'tenant_id' => 1,
                'meter_id' => $meter->id,
                'value' => $estimatedValue,
                'input_method' => InputMethod::ESTIMATED,
            ]);
            
            $actualReading = MeterReading::factory()->create([
                'tenant_id' => 1,
                'meter_id' => $meter->id,
                'value' => $actualValue,
                'input_method' => InputMethod::MANUAL,
            ]);

            // Act - calculate true-up in both directions
            $result1 = $this->validationEngine->validateEstimatedReading($estimatedReading, $actualReading);
            $result2 = $this->validationEngine->validateEstimatedReading($actualReading, $estimatedReading);

            // Assert symmetry property
            if (isset($result1['true_up_amount']) && isset($result2['true_up_amount'])) {
                $this->assertEquals(
                    -$result1['true_up_amount'],
                    $result2['true_up_amount'],
                    'True-up amounts should be symmetric',
                    0.01 // Allow small floating point differences
                );
            }
        });
    }

    /**
     * Property: Validation performance scales linearly with batch size
     * 
     * Processing time should scale roughly linearly with the number of readings
     * (within reasonable bounds due to optimizations).
     */
    public function test_property_batch_validation_performance_scaling(): void
    {
        $meter = Meter::factory()->create(['tenant_id' => 1]);
        
        $performanceData = [];
        
        // Test different batch sizes
        foreach ([5, 10, 20] as $batchSize) {
            $readings = collect();
            for ($i = 0; $i < $batchSize; $i++) {
                $readings->push(MeterReading::factory()->create([
                    'tenant_id' => 1,
                    'meter_id' => $meter->id,
                    'value' => fake()->randomFloat(2, 100, 1000),
                ]));
            }

            $startTime = microtime(true);
            $result = $this->validationEngine->batchValidateReadings($readings);
            $endTime = microtime(true);
            
            $duration = $endTime - $startTime;
            $performanceData[$batchSize] = [
                'duration' => $duration,
                'per_reading' => $duration / $batchSize,
            ];
        }

        // Assert that performance per reading doesn't degrade significantly
        $perReadingTimes = array_column($performanceData, 'per_reading');
        $maxTime = max($perReadingTimes);
        $minTime = min($perReadingTimes);
        
        // Performance should not degrade by more than 3x between batch sizes
        $this->assertLessThan(
            $minTime * 3,
            $maxTime,
            'Performance per reading should not degrade significantly with batch size'
        );
    }

    /**
     * Property: Authorization is consistently enforced
     * 
     * For any reading from a different tenant, validation should always fail
     * with unauthorized error, regardless of other reading properties.
     */
    public function test_property_authorization_consistency(): void
    {
        $this->runPropertyTest(30, function () {
            // Create reading for different tenant
            $meter = Meter::factory()->create(['tenant_id' => 2]);
            $reading = MeterReading::factory()->create([
                'tenant_id' => 2,
                'meter_id' => $meter->id,
                'value' => fake()->randomFloat(2, 0, 10000),
                'input_method' => fake()->randomElement(InputMethod::cases()),
            ]);

            // Act (user is from tenant 1, reading is from tenant 2)
            $result = $this->validationEngine->validateMeterReading($reading);

            // Assert invariant: always unauthorized
            $this->assertFalse($result['is_valid']);
            $this->assertNotEmpty($result['errors']);
            $this->assertStringContainsString('Unauthorized', $result['errors'][0]);
        });
    }

    /**
     * Property: Input method validation is deterministic
     * 
     * For the same reading with the same input method, validation should
     * always produce the same result regarding input method requirements.
     */
    public function test_property_input_method_validation_deterministic(): void
    {
        $this->runPropertyTest(20, function () {
            $meter = Meter::factory()->create(['tenant_id' => 1]);
            $inputMethod = fake()->randomElement(InputMethod::cases());
            
            $reading = MeterReading::factory()->create([
                'tenant_id' => 1,
                'meter_id' => $meter->id,
                'input_method' => $inputMethod,
                'photo_path' => $inputMethod === InputMethod::PHOTO_OCR ? null : fake()->filePath(),
            ]);

            // Validate multiple times
            $result1 = $this->validationEngine->validateMeterReading($reading);
            $result2 = $this->validationEngine->validateMeterReading($reading);

            // Assert deterministic behavior for input method validation
            $this->assertEquals(
                $result1['is_valid'],
                $result2['is_valid'],
                'Input method validation should be deterministic'
            );
            
            $this->assertEquals(
                $result1['errors'],
                $result2['errors'],
                'Input method errors should be consistent'
            );
        });
    }

    /**
     * Property: Validation metadata is always informative
     * 
     * Validation metadata should always contain useful information about
     * the validation process, regardless of the result.
     */
    public function test_property_validation_metadata_informative(): void
    {
        $this->runPropertyTest(50, function () {
            $meter = Meter::factory()->create(['tenant_id' => 1]);
            $reading = MeterReading::factory()->create([
                'tenant_id' => 1,
                'meter_id' => $meter->id,
                'value' => fake()->randomFloat(2, 0, 10000),
            ]);

            // Act
            $result = $this->validationEngine->validateMeterReading($reading);

            // Assert metadata properties
            $this->assertArrayHasKey('metadata', $result);
            $metadata = $result['metadata'];
            
            $this->assertIsArray($metadata);
            $this->assertArrayHasKey('error_count', $metadata);
            $this->assertArrayHasKey('warning_count', $metadata);
            
            $this->assertIsInt($metadata['error_count']);
            $this->assertIsInt($metadata['warning_count']);
            
            // Error count should match actual errors
            $this->assertEquals(
                count($result['errors']),
                $metadata['error_count'],
                'Metadata error count should match actual errors'
            );
            
            // Warning count should match actual warnings
            $this->assertEquals(
                count($result['warnings']),
                $metadata['warning_count'],
                'Metadata warning count should match actual warnings'
            );
        });
    }

    /**
     * Helper method to run property-based tests with specified iterations
     */
    private function runPropertyTest(int $iterations, callable $testFunction): void
    {
        for ($i = 0; $i < $iterations; $i++) {
            try {
                $testFunction();
            } catch (\Exception $e) {
                $this->fail("Property test failed on iteration {$i}: " . $e->getMessage());
            }
        }
    }
}
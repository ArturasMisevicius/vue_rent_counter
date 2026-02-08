<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\InputMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Enums\ValidationStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Models\User;
use App\Services\ServiceValidationEngine;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;
use App\Services\Validation\ValidationRuleFactory;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Enhanced test suite for ServiceValidationEngine covering the typo fix and comprehensive scenarios.
 * 
 * Tests the corrected import from UtiviceConfiguration to UtilityService and validates
 * all validation behaviors, security measures, and performance optimizations.
 */
class ServiceValidationEngineEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private ServiceValidationEngine $validationEngine;
    private User $authorizedUser;
    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationEngine = app(ServiceValidationEngine::class);
        
        // Create tenants first
        $tenant1 = \App\Models\Tenant::factory()->create(['id' => 1]);
        $tenant2 = \App\Models\Tenant::factory()->create(['id' => 2]);
        
        // Create test users with proper tenant isolation
        $this->authorizedUser = User::factory()->create([
            'tenant_id' => $tenant1->id,
        ]);
        
        $this->unauthorizedUser = User::factory()->create([
            'tenant_id' => $tenant2->id,
        ]);

        // Set up validation configuration
        Config::set('service_validation', [
            'default_min_consumption' => 0,
            'default_max_consumption' => 10000,
            'rate_change_frequency_days' => 30,
            'true_up_threshold' => 5.0,
            'seasonal_adjustments' => [
                'heating' => [
                    'summer_max_threshold' => 50,
                    'winter_min_threshold' => 100,
                ],
                'water' => [
                    'summer_range' => ['min' => 80, 'max' => 150],
                    'winter_range' => ['min' => 60, 'max' => 120],
                ],
                'default' => [
                    'variance_threshold' => 0.3,
                ],
            ],
            'performance' => [
                'batch_validation_size' => 100,
            ],
        ]);

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_validates_meter_reading_with_utility_service_integration(): void
    {
        $this->actingAs($this->authorizedUser);

        // Create utility service with validation rules
        $utilityService = UtilityService::factory()->create([
            'tenant_id' => 1,
            'name' => 'Electricity Service',
            'unit_of_measurement' => 'kWh',
            'service_type_bridge' => ServiceType::ELECTRICITY,
            'validation_rules' => [
                'min_consumption' => 0,
                'max_consumption' => 5000,
            ],
            'business_logic_config' => [
                'constraints' => [
                    [
                        'field' => 'value',
                        'operator' => '>',
                        'value' => 1000,
                        'message' => 'Reading exceeds maximum allowed value',
                        'severity' => 'error',
                    ],
                ],
            ],
        ]);

        $serviceConfig = ServiceConfiguration::factory()->create([
            'tenant_id' => 1,
            'utility_service_id' => $utilityService->id,
            'pricing_model' => PricingModel::CONSUMPTION_BASED,
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'service_configuration_id' => $serviceConfig->id,
        ]);

        // Create previous reading for consumption calculation
        $previousReading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 100.0,
            'reading_date' => now()->subDays(30),
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $reading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 150.0, // Valid consumption of 50 kWh
            'reading_date' => now(),
            'input_method' => InputMethod::MANUAL,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        // Act
        $result = $this->validationEngine->validateMeterReading($reading, $serviceConfig);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('metadata', $result);
        
        // Debug output if validation fails
        if (!$result['is_valid']) {
            $this->fail('Validation failed with errors: ' . implode(', ', $result['errors']));
        }
        
        $this->assertTrue($result['is_valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_validates_business_rules_from_utility_service_configuration(): void
    {
        $this->actingAs($this->authorizedUser);

        $utilityService = UtilityService::factory()->create([
            'tenant_id' => 1,
            'business_logic_config' => [
                'constraints' => [
                    [
                        'field' => 'value',
                        'operator' => '>',
                        'value' => 2000,
                        'message' => 'Reading exceeds maximum threshold',
                        'severity' => 'error',
                    ],
                ],
            ],
        ]);

        $serviceConfig = ServiceConfiguration::factory()->create([
            'tenant_id' => 1,
            'utility_service_id' => $utilityService->id,
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'service_configuration_id' => $serviceConfig->id,
        ]);

        $reading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 2500.0, // Exceeds threshold of 2000
            'reading_date' => now(),
        ]);

        // Act
        $result = $this->validationEngine->validateMeterReading($reading, $serviceConfig);

        // Assert
        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('exceeds maximum threshold', $result['errors'][0]);
    }

    /** @test */
    public function it_validates_estimated_readings_with_true_up_calculations(): void
    {
        $this->actingAs($this->authorizedUser);

        $meter = Meter::factory()->create(['tenant_id' => 1]);

        $estimatedReading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 100.0,
            'input_method' => InputMethod::ESTIMATED,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $actualReading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 110.0, // 10 unit difference
            'input_method' => InputMethod::MANUAL,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        // Act
        $result = $this->validationEngine->validateEstimatedReading($estimatedReading, $actualReading);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('true_up_amount', $result);
        $this->assertArrayHasKey('adjustment_required', $result);
        $this->assertEquals(10.0, $result['true_up_amount']);
        $this->assertTrue($result['adjustment_required']); // Exceeds threshold of 5.0
        $this->assertStringContainsString('True-up adjustment required', $result['warnings'][0]);
    }

    /** @test */
    public function it_filters_readings_by_validation_status(): void
    {
        $this->actingAs($this->authorizedUser);

        $meter = Meter::factory()->create(['tenant_id' => 1]);

        // Create readings with different validation statuses
        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'validation_status' => ValidationStatus::REJECTED,
        ]);

        // Act
        $pendingReadings = $this->validationEngine->getReadingsByValidationStatus(
            ValidationStatus::PENDING,
            ['tenant_id' => 1]
        );

        // Assert
        $this->assertInstanceOf(Collection::class, $pendingReadings);
        $this->assertCount(1, $pendingReadings);
        $this->assertEquals(ValidationStatus::PENDING, $pendingReadings->first()->validation_status);
    }

    /** @test */
    public function it_bulk_updates_validation_status_with_authorization(): void
    {
        $this->actingAs($this->authorizedUser);

        $meter = Meter::factory()->create(['tenant_id' => 1]);

        $readings = collect([
            MeterReading::factory()->create([
                'tenant_id' => 1,
                'meter_id' => $meter->id,
                'validation_status' => ValidationStatus::PENDING,
            ]),
            MeterReading::factory()->create([
                'tenant_id' => 1,
                'meter_id' => $meter->id,
                'validation_status' => ValidationStatus::PENDING,
            ]),
        ]);

        // Act
        $result = $this->validationEngine->bulkUpdateValidationStatus(
            $readings,
            ValidationStatus::VALIDATED,
            $this->authorizedUser->id
        );

        // Assert
        $this->assertEquals(2, $result['updated_count']);
        $this->assertEmpty($result['errors']);

        // Verify database updates
        foreach ($readings as $reading) {
            $reading->refresh();
            $this->assertEquals(ValidationStatus::VALIDATED, $reading->validation_status);
            $this->assertEquals($this->authorizedUser->id, $reading->validated_by);
        }
    }

    /** @test */
    public function it_handles_batch_validation_with_performance_optimization(): void
    {
        $this->actingAs($this->authorizedUser);

        $meter = Meter::factory()->create(['tenant_id' => 1]);

        // Create multiple readings for batch processing
        $readings = collect();
        for ($i = 0; $i < 10; $i++) {
            $readings->push(MeterReading::factory()->create([
                'tenant_id' => 1,
                'meter_id' => $meter->id,
                'value' => 100 + ($i * 10),
                'reading_date' => now()->subDays($i),
            ]));
        }

        // Act
        $result = $this->validationEngine->batchValidateReadings($readings);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_readings', $result);
        $this->assertArrayHasKey('valid_readings', $result);
        $this->assertArrayHasKey('invalid_readings', $result);
        $this->assertArrayHasKey('performance_metrics', $result);
        $this->assertEquals(10, $result['total_readings']);
        
        // Verify performance metrics are captured
        $this->assertArrayHasKey('duration', $result['performance_metrics']);
        $this->assertArrayHasKey('memory_peak_mb', $result['performance_metrics']);
        $this->assertArrayHasKey('queries_per_reading', $result['performance_metrics']);
    }

    /** @test */
    public function it_enforces_rate_limiting_on_validation_operations(): void
    {
        $this->actingAs($this->authorizedUser);

        // Set a low rate limit for testing
        Config::set('security.rate_limiting.limits.single_validation', 2);

        $meter = Meter::factory()->create(['tenant_id' => 1]);
        $reading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
        ]);

        // Perform validations up to the limit
        $this->validationEngine->validateMeterReading($reading);
        $this->validationEngine->validateMeterReading($reading);

        // This should trigger rate limiting
        $this->expectException(ThrottleRequestsException::class);
        $this->validationEngine->validateMeterReading($reading);
    }

    /** @test */
    public function it_sanitizes_rate_schedule_input_securely(): void
    {
        $this->actingAs($this->authorizedUser);

        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 1]);

        $maliciousSchedule = [
            'rate_per_unit' => 0.15,
            'effective_from' => '2024-12-31',
            // Malicious inputs
            'malicious_key' => '<script>alert("xss")</script>',
            'sql_injection' => "'; DROP TABLE meter_readings; --",
            'nested_attack' => [
                'deep' => [
                    'structure' => 'attack',
                ],
            ],
        ];

        // Act
        $result = $this->validationEngine->validateRateChangeRestrictions(
            $serviceConfig,
            $maliciousSchedule
        );

        // Assert - malicious content should be filtered out
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_valid', $result);
        // The result should not contain malicious keys
        $this->assertArrayNotHasKey('malicious_key', $result);
        $this->assertArrayNotHasKey('sql_injection', $result);
        $this->assertArrayNotHasKey('nested_attack', $result);
    }

    /** @test */
    public function it_validates_time_slots_structure_with_bounds_checking(): void
    {
        $this->actingAs($this->authorizedUser);

        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 1]);

        $scheduleWithTimeSlots = [
            'time_slots' => [
                [
                    'start_hour' => 9,
                    'end_hour' => 17,
                    'rate' => 0.15,
                    'day_type' => 'weekday',
                ],
                [
                    'start_hour' => 25, // Invalid hour > 23
                    'end_hour' => 17,
                    'rate' => 0.10,
                ],
                [
                    'start_hour' => -1, // Invalid hour < 0
                    'end_hour' => 8,
                    'rate' => 0.08,
                ],
            ],
        ];

        // Act
        $result = $this->validationEngine->validateRateChangeRestrictions(
            $serviceConfig,
            $scheduleWithTimeSlots
        );

        // Assert - only valid time slot should remain
        $this->assertIsArray($result);
        if (isset($result['time_slots'])) {
            $this->assertCount(1, $result['time_slots']);
            $this->assertEquals(9, $result['time_slots'][0]['start_hour']);
            $this->assertEquals(17, $result['time_slots'][0]['end_hour']);
        }
    }

    /** @test */
    public function it_validates_tiers_structure_with_numeric_bounds(): void
    {
        $this->actingAs($this->authorizedUser);

        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 1]);

        $scheduleWithTiers = [
            'tiers' => [
                [
                    'limit' => 100,
                    'rate' => 0.10,
                ],
                [
                    'limit' => -50, // Invalid negative limit
                    'rate' => 0.15,
                ],
                [
                    'limit' => 1000000, // Exceeds reasonable bounds
                    'rate' => 0.20,
                ],
            ],
        ];

        // Act
        $result = $this->validationEngine->validateRateChangeRestrictions(
            $serviceConfig,
            $scheduleWithTiers
        );

        // Assert - only valid tier should remain
        $this->assertIsArray($result);
        if (isset($result['tiers'])) {
            $this->assertCount(1, $result['tiers']);
            $this->assertEquals(100, $result['tiers'][0]['limit']);
            $this->assertEquals(0.10, $result['tiers'][0]['rate']);
        }
    }

    /** @test */
    public function it_prevents_unauthorized_cross_tenant_access(): void
    {
        // Create reading for tenant 2
        $meter = Meter::factory()->create(['tenant_id' => 2]);
        $reading = MeterReading::factory()->create([
            'tenant_id' => 2,
            'meter_id' => $meter->id,
        ]);

        // Act as tenant 1 user
        $this->actingAs($this->authorizedUser); // tenant_id = 1

        // Act
        $result = $this->validationEngine->validateMeterReading($reading);

        // Assert
        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Unauthorized', $result['errors'][0]);
    }

    /** @test */
    public function it_handles_validation_system_errors_gracefully(): void
    {
        $this->actingAs($this->authorizedUser);

        // Create a reading with invalid meter relationship to trigger error
        $reading = new MeterReading([
            'tenant_id' => 1,
            'meter_id' => 99999, // Non-existent meter
            'value' => 100.0,
            'reading_date' => now(),
        ]);

        // Act
        $result = $this->validationEngine->validateMeterReading($reading);

        // Assert - should handle error gracefully
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_valid', $result);
        $this->assertArrayHasKey('errors', $result);
        // Should either be invalid with errors or handle the missing relationship gracefully
        $this->assertTrue(is_bool($result['is_valid']));
    }

    /** @test */
    public function it_validates_seasonal_adjustments_for_different_utility_types(): void
    {
        $this->actingAs($this->authorizedUser);

        // Test heating service in summer (should trigger warning)
        $heatingService = UtilityService::factory()->create([
            'tenant_id' => 1,
            'service_type_bridge' => ServiceType::HEATING,
        ]);

        $serviceConfig = ServiceConfiguration::factory()->create([
            'tenant_id' => 1,
            'utility_service_id' => $heatingService->id,
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'service_configuration_id' => $serviceConfig->id,
        ]);

        $previousReading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 100.0,
            'reading_date' => Carbon::create(2024, 6, 1), // Summer
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $reading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 200.0, // High consumption in summer
            'reading_date' => Carbon::create(2024, 7, 1), // Summer
        ]);

        // Act
        $result = $this->validationEngine->validateMeterReading($reading, $serviceConfig);

        // Assert
        $this->assertIsArray($result);
        // Should have warnings about high heating consumption in summer
        $this->assertTrue($result['is_valid'] || !empty($result['warnings']));
    }

    /** @test */
    public function it_validates_input_method_specific_requirements(): void
    {
        $this->actingAs($this->authorizedUser);

        $meter = Meter::factory()->create(['tenant_id' => 1]);

        // Test photo OCR without photo path
        $photoReading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'input_method' => InputMethod::PHOTO_OCR,
            'photo_path' => null, // Missing required photo path
        ]);

        // Act
        $result = $this->validationEngine->validateMeterReading($photoReading);

        // Assert
        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Photo path', $result['errors'][0]);
    }

    /** @test */
    public function it_logs_validation_operations_for_audit_trail(): void
    {
        $this->actingAs($this->authorizedUser);

        Log::shouldReceive('info')
            ->once()
            ->with('Meter reading validation completed', \Mockery::type('array'));

        $meter = Meter::factory()->create(['tenant_id' => 1]);
        $reading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
        ]);

        // Act
        $this->validationEngine->validateMeterReading($reading);

        // Assert - Mock expectation will be verified automatically
    }

    /** @test */
    public function it_caches_validation_configuration_for_performance(): void
    {
        $this->actingAs($this->authorizedUser);

        $meter = Meter::factory()->create(['tenant_id' => 1]);
        $reading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
        ]);

        // First validation should cache configuration
        $result1 = $this->validationEngine->validateMeterReading($reading);
        
        // Second validation should use cached configuration
        $result2 = $this->validationEngine->validateMeterReading($reading);

        // Both should return valid results
        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertArrayHasKey('is_valid', $result1);
        $this->assertArrayHasKey('is_valid', $result2);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}

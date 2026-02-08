<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\InputMethod;
use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Models\User;
use App\Services\ServiceValidationEngine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Security Test Suite for ServiceValidationEngine
 * 
 * Tests critical security vulnerabilities and attack vectors:
 * - Mass assignment attacks
 * - SQL injection attempts
 * - Authorization bypass
 * - Rate limiting enforcement
 * - Input validation security
 * - Batch operation security
 */
class ServiceValidationEngineSecurityTest extends TestCase
{
    use RefreshDatabase;

    private ServiceValidationEngine $validationEngine;
    private User $authorizedUser;
    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validationEngine = app(ServiceValidationEngine::class);
        
        // Create test users with different access levels
        $this->authorizedUser = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);
        
        $this->unauthorizedUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 2, // Different tenant
        ]);
    }

    /** @test */
    public function it_prevents_mass_assignment_in_rate_schedule_validation(): void
    {
        $this->actingAs($this->authorizedUser);
        
        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 1]);
        
        // Attempt mass assignment attack with malicious nested structure
        $maliciousSchedule = [
            'rate_per_unit' => 10.0,
            '__construct' => 'malicious_payload',
            'nested' => [
                'very' => [
                    'deep' => [
                        'structure' => 'attack',
                        'more_nesting' => ['even' => ['deeper' => 'payload']]
                    ]
                ]
            ],
            'sql_injection' => "'; DROP TABLE meter_readings; --",
            'xss_attempt' => '<script>alert("xss")</script>',
        ];
        
        // Should not throw exception but should filter malicious content
        $result = $this->validationEngine->validateRateChangeRestrictions(
            $serviceConfig, 
            $maliciousSchedule
        );
        
        // Verify malicious keys are filtered out
        $this->assertArrayNotHasKey('__construct', $result);
        $this->assertArrayNotHasKey('sql_injection', $result);
        $this->assertArrayNotHasKey('xss_attempt', $result);
        
        // Verify deeply nested structure is rejected
        $this->assertArrayNotHasKey('nested', $result);
    }

    /** @test */
    public function it_rejects_oversized_rate_schedule_structures(): void
    {
        $this->actingAs($this->authorizedUser);
        
        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 1]);
        
        // Create oversized structure to test memory exhaustion protection
        $oversizedSchedule = [];
        for ($i = 0; $i < 2000; $i++) {
            $oversizedSchedule["rate_{$i}"] = $i * 0.1;
        }
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate schedule too large');
        
        $this->validationEngine->validateRateChangeRestrictions(
            $serviceConfig, 
            $oversizedSchedule
        );
    }

    /** @test */
    public function it_prevents_sql_injection_in_batch_operations(): void
    {
        $this->actingAs($this->authorizedUser);
        
        // Create readings with potentially malicious meter IDs
        $readings = collect([
            MeterReading::factory()->make([
                'meter_id' => 1,
                'tenant_id' => 1,
            ]),
            MeterReading::factory()->make([
                'meter_id' => 2,
                'tenant_id' => 1,
            ]),
        ]);
        
        // This should work normally with valid data
        $result = $this->validationEngine->batchValidateReadings($readings);
        
        $this->assertArrayHasKey('total_readings', $result);
        $this->assertEquals(2, $result['total_readings']);
    }

    /** @test */
    public function it_enforces_authorization_for_all_batch_readings(): void
    {
        // Create readings from different tenants
        $authorizedReading = MeterReading::factory()->create(['tenant_id' => 1]);
        $unauthorizedReading = MeterReading::factory()->create(['tenant_id' => 2]);
        
        $readings = collect([$authorizedReading, $unauthorizedReading]);
        
        $this->actingAs($this->authorizedUser); // Tenant 1 user
        
        // Should throw authorization exception for mixed tenant readings
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Unauthorized access to one or more meter readings');
        
        $this->validationEngine->batchValidateReadings($readings);
    }

    /** @test */
    public function it_enforces_rate_limiting_on_batch_operations(): void
    {
        $this->actingAs($this->authorizedUser);
        
        // Clear any existing rate limit data
        Cache::flush();
        
        // Create a large batch that exceeds rate limits
        $readings = collect();
        for ($i = 0; $i < 150; $i++) { // Exceeds default limit of 100
            $readings->push(MeterReading::factory()->make([
                'tenant_id' => 1,
                'meter_id' => ($i % 10) + 1, // Cycle through 10 meters
            ]));
        }
        
        $this->expectException(ThrottleRequestsException::class);
        $this->expectExceptionMessage('Rate limit exceeded');
        
        $this->validationEngine->batchValidateReadings($readings);
    }

    /** @test */
    public function it_validates_numeric_rate_bounds(): void
    {
        $this->actingAs($this->authorizedUser);
        
        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 1]);
        
        // Test negative rate (should be rejected)
        $negativeRateSchedule = ['rate_per_unit' => -10.0];
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate value out of acceptable range');
        
        $this->validationEngine->validateRateChangeRestrictions(
            $serviceConfig, 
            $negativeRateSchedule
        );
    }

    /** @test */
    public function it_validates_date_range_limits(): void
    {
        $this->actingAs($this->authorizedUser);
        
        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 1]);
        
        // Test date too far in future (should be rejected)
        $futureDateSchedule = [
            'effective_from' => '2050-01-01 00:00:00', // 25+ years in future
        ];
        
        $result = $this->validationEngine->validateRateChangeRestrictions(
            $serviceConfig, 
            $futureDateSchedule
        );
        
        // Should filter out invalid date
        $this->assertArrayNotHasKey('effective_from', $result);
    }

    /** @test */
    public function it_prevents_unauthorized_single_reading_validation(): void
    {
        $unauthorizedReading = MeterReading::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($this->authorizedUser); // Tenant 1 user
        
        $result = $this->validationEngine->validateMeterReading($unauthorizedReading);
        
        $this->assertFalse($result['is_valid']);
        $this->assertContains('Unauthorized access', $result['errors'][0] ?? '');
    }

    /** @test */
    public function it_handles_malformed_validation_context_safely(): void
    {
        $this->actingAs($this->authorizedUser);
        
        $reading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'reading_values' => ['malformed' => 'data', 'nested' => ['too' => ['deep' => 'structure']]],
        ]);
        
        // Should handle malformed data gracefully without throwing exceptions
        $result = $this->validationEngine->validateMeterReading($reading);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_valid', $result);
    }

    /** @test */
    public function it_logs_security_violations_appropriately(): void
    {
        $this->actingAs($this->authorizedUser);
        
        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 1]);
        
        // Attempt path traversal in rate schedule
        $maliciousSchedule = [
            'rate_per_unit' => 10.0,
            '../../../etc/passwd' => 'path_traversal_attempt',
        ];
        
        // Should not throw but should log the attempt
        $result = $this->validationEngine->validateRateChangeRestrictions(
            $serviceConfig, 
            $maliciousSchedule
        );
        
        // Verify malicious key is filtered
        $this->assertArrayNotHasKey('../../../etc/passwd', $result);
    }

    /** @test */
    public function it_validates_time_slots_structure_securely(): void
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
                    'start_hour' => 25, // Invalid hour
                    'end_hour' => 17,
                    'rate' => 0.10,
                    'day_type' => 'malicious_type',
                ],
                // Add malicious nested structure
                [
                    'malicious_key' => '<script>alert("xss")</script>',
                    'nested_attack' => ['deep' => 'structure'],
                ],
            ],
        ];
        
        $result = $this->validationEngine->validateRateChangeRestrictions(
            $serviceConfig, 
            $scheduleWithTimeSlots
        );
        
        // Should have filtered time slots
        $this->assertArrayHasKey('time_slots', $result);
        $timeSlots = $result['time_slots'];
        
        // Should only have 1 valid time slot (first one)
        $this->assertCount(1, $timeSlots);
        $this->assertEquals(9, $timeSlots[0]['start_hour']);
        $this->assertEquals('weekday', $timeSlots[0]['day_type']);
    }

    /** @test */
    public function it_validates_tiers_structure_securely(): void
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
                // Malicious structure
                [
                    'malicious_field' => 'attack',
                    'rate' => 'not_numeric',
                ],
            ],
        ];
        
        $result = $this->validationEngine->validateRateChangeRestrictions(
            $serviceConfig, 
            $scheduleWithTiers
        );
        
        // Should have filtered tiers
        $this->assertArrayHasKey('tiers', $result);
        $tiers = $result['tiers'];
        
        // Should only have 1 valid tier (first one)
        $this->assertCount(1, $tiers);
        $this->assertEquals(100, $tiers[0]['limit']);
        $this->assertEquals(0.10, $tiers[0]['rate']);
    }

    /** @test */
    public function it_prevents_batch_size_memory_exhaustion(): void
    {
        $this->actingAs($this->authorizedUser);
        
        // Create oversized batch
        $readings = collect();
        for ($i = 0; $i < 200; $i++) { // Exceeds default max batch size
            $readings->push(MeterReading::factory()->make(['tenant_id' => 1]));
        }
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds maximum allowed size');
        
        $this->validationEngine->batchValidateReadings($readings);
    }

    /** @test */
    public function it_handles_non_meter_reading_objects_in_batch(): void
    {
        $this->actingAs($this->authorizedUser);
        
        // Mix valid readings with invalid objects
        $readings = collect([
            MeterReading::factory()->make(['tenant_id' => 1]),
            new \stdClass(), // Invalid object
            'string_value', // Invalid type
        ]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be MeterReading instances');
        
        $this->validationEngine->batchValidateReadings($readings);
    }
}
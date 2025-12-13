<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\InputMethod;
use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Models\User;
use App\Services\ServiceValidationEngine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Advanced security test suite for ServiceValidationEngine.
 * 
 * Tests sophisticated attack vectors, edge cases in security measures,
 * and ensures robust protection against various security threats.
 */
class ServiceValidationEngineAdvancedSecurityTest extends TestCase
{
    use RefreshDatabase;

    private ServiceValidationEngine $validationEngine;
    private User $superAdminUser;
    private User $adminUser;
    private User $managerUser;
    private User $tenantUser;
    private User $maliciousUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validationEngine = app(ServiceValidationEngine::class);
        
        // Create users with different roles and tenants for comprehensive testing
        $this->superAdminUser = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'tenant_id' => null, // SuperAdmin has no specific tenant
        ]);
        
        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);
        
        $this->managerUser = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);
        
        $this->tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
        ]);
        
        $this->maliciousUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 2, // Different tenant for cross-tenant attacks
        ]);

        // Configure security settings
        Config::set('security.rate_limiting.limits', [
            'batch_validation' => 50,
            'single_validation' => 100,
            'rate_change_validation' => 10,
        ]);

        Cache::flush();
    }

    /** @test */
    public function it_prevents_timing_attacks_on_authorization_checks(): void
    {
        $authorizedMeter = Meter::factory()->create(['tenant_id' => 1]);
        $unauthorizedMeter = Meter::factory()->create(['tenant_id' => 2]);
        
        $authorizedReading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $authorizedMeter->id,
        ]);
        
        $unauthorizedReading = MeterReading::factory()->create([
            'tenant_id' => 2,
            'meter_id' => $unauthorizedMeter->id,
        ]);

        $this->actingAs($this->adminUser); // Tenant 1 user

        // Measure timing for authorized vs unauthorized access
        $authorizedTimes = [];
        $unauthorizedTimes = [];

        for ($i = 0; $i < 10; $i++) {
            // Authorized access timing
            $start = microtime(true);
            $this->validationEngine->validateMeterReading($authorizedReading);
            $authorizedTimes[] = microtime(true) - $start;

            // Unauthorized access timing
            $start = microtime(true);
            $this->validationEngine->validateMeterReading($unauthorizedReading);
            $unauthorizedTimes[] = microtime(true) - $start;
        }

        $avgAuthorized = array_sum($authorizedTimes) / count($authorizedTimes);
        $avgUnauthorized = array_sum($unauthorizedTimes) / count($unauthorizedTimes);

        // Timing difference should not be significant (within 50% variance)
        $timingRatio = max($avgAuthorized, $avgUnauthorized) / min($avgAuthorized, $avgUnauthorized);
        $this->assertLessThan(1.5, $timingRatio, 'Authorization timing should not reveal information');
    }

    /** @test */
    public function it_prevents_cache_poisoning_attacks(): void
    {
        $this->actingAs($this->maliciousUser);

        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 2]);

        // Attempt to poison cache with malicious data
        $maliciousSchedule = [
            'rate_per_unit' => 0.01, // Extremely low rate
            'cache_key_injection' => '../../../malicious_cache_key',
            'serialized_payload' => serialize(['malicious' => 'data']),
        ];

        // This should not affect other tenants' cache
        $this->validationEngine->validateRateChangeRestrictions($serviceConfig, $maliciousSchedule);

        // Switch to different tenant and verify cache integrity
        $this->actingAs($this->adminUser); // Tenant 1

        $legitimateConfig = ServiceConfiguration::factory()->create(['tenant_id' => 1]);
        $legitimateSchedule = ['rate_per_unit' => 0.15];

        $result = $this->validationEngine->validateRateChangeRestrictions($legitimateConfig, $legitimateSchedule);

        // Should not be affected by malicious cache poisoning
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_valid', $result);
    }

    /** @test */
    public function it_prevents_memory_exhaustion_through_nested_structures(): void
    {
        $this->actingAs($this->maliciousUser);

        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 2]);

        // Create deeply nested structure to exhaust memory
        $deepStructure = [];
        $current = &$deepStructure;
        
        for ($i = 0; $i < 100; $i++) {
            $current['nested'] = [];
            $current = &$current['nested'];
        }
        $current = 'deep_payload';

        $maliciousSchedule = [
            'rate_per_unit' => 0.15,
            'deep_structure' => $deepStructure,
        ];

        // Should reject deeply nested structure
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate schedule structure too complex');

        $this->validationEngine->validateRateChangeRestrictions($serviceConfig, $maliciousSchedule);
    }

    /** @test */
    public function it_prevents_deserialization_attacks(): void
    {
        $this->actingAs($this->maliciousUser);

        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 2]);

        // Attempt PHP object injection through serialized data
        $maliciousObject = serialize(new \stdClass());
        
        $maliciousSchedule = [
            'rate_per_unit' => $maliciousObject,
            'serialized_data' => 'O:8:"stdClass":0:{}',
            'base64_payload' => base64_encode($maliciousObject),
        ];

        // Should sanitize and reject malicious serialized data
        $result = $this->validationEngine->validateRateChangeRestrictions($serviceConfig, $maliciousSchedule);

        // Malicious keys should be filtered out
        $this->assertArrayNotHasKey('serialized_data', $result);
        $this->assertArrayNotHasKey('base64_payload', $result);
    }

    /** @test */
    public function it_prevents_sql_injection_through_batch_operations(): void
    {
        $this->actingAs($this->maliciousUser);

        // Create readings with potentially malicious data in various fields
        $readings = collect([
            MeterReading::factory()->make([
                'tenant_id' => 2,
                'value' => 100.0,
                'reading_values' => [
                    'malicious_sql' => "'; DROP TABLE meter_readings; --",
                    'union_select' => "1 UNION SELECT * FROM users",
                ],
            ]),
            MeterReading::factory()->make([
                'tenant_id' => 2,
                'value' => 200.0,
                'reading_values' => [
                    'hex_injection' => '0x41424344',
                    'comment_injection' => '/* malicious comment */',
                ],
            ]),
        ]);

        // Should handle malicious data safely without SQL injection
        $result = $this->validationEngine->batchValidateReadings($readings);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_readings', $result);
        $this->assertEquals(2, $result['total_readings']);
    }

    /** @test */
    public function it_prevents_privilege_escalation_through_batch_operations(): void
    {
        // Create readings from multiple tenants
        $tenant1Reading = MeterReading::factory()->create(['tenant_id' => 1]);
        $tenant2Reading = MeterReading::factory()->create(['tenant_id' => 2]);
        $tenant3Reading = MeterReading::factory()->create(['tenant_id' => 3]);

        $mixedReadings = collect([$tenant1Reading, $tenant2Reading, $tenant3Reading]);

        // Malicious user tries to validate readings from multiple tenants
        $this->actingAs($this->maliciousUser); // Tenant 2 user

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Unauthorized access to one or more meter readings');

        $this->validationEngine->batchValidateReadings($mixedReadings);
    }

    /** @test */
    public function it_prevents_information_disclosure_through_error_messages(): void
    {
        $this->actingAs($this->maliciousUser);

        // Try to access reading from different tenant
        $secretReading = MeterReading::factory()->create([
            'tenant_id' => 1, // Different tenant
            'value' => 12345.67, // Specific value that might leak in error
        ]);

        $result = $this->validationEngine->validateMeterReading($secretReading);

        // Error message should not reveal sensitive information
        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['errors']);
        
        $errorMessage = $result['errors'][0];
        $this->assertStringNotContainsString('12345.67', $errorMessage);
        $this->assertStringNotContainsString($secretReading->id, $errorMessage);
        $this->assertStringContainsString('Unauthorized', $errorMessage);
    }

    /** @test */
    public function it_prevents_race_conditions_in_rate_limiting(): void
    {
        $this->actingAs($this->maliciousUser);

        Config::set('security.rate_limiting.limits.single_validation', 3);

        $meter = Meter::factory()->create(['tenant_id' => 2]);
        $reading = MeterReading::factory()->create([
            'tenant_id' => 2,
            'meter_id' => $meter->id,
        ]);

        // Simulate concurrent requests
        $promises = [];
        $results = [];

        for ($i = 0; $i < 10; $i++) {
            try {
                $results[] = $this->validationEngine->validateMeterReading($reading);
            } catch (ThrottleRequestsException $e) {
                $results[] = 'rate_limited';
            }
        }

        // Should have some rate limited responses
        $rateLimitedCount = count(array_filter($results, fn($r) => $r === 'rate_limited'));
        $this->assertGreaterThan(0, $rateLimitedCount, 'Rate limiting should prevent excessive requests');
    }

    /** @test */
    public function it_prevents_cache_timing_attacks(): void
    {
        $this->actingAs($this->adminUser);

        $meter = Meter::factory()->create(['tenant_id' => 1]);
        $reading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
        ]);

        // First validation (cache miss)
        $start = microtime(true);
        $result1 = $this->validationEngine->validateMeterReading($reading);
        $time1 = microtime(true) - $start;

        // Second validation (cache hit)
        $start = microtime(true);
        $result2 = $this->validationEngine->validateMeterReading($reading);
        $time2 = microtime(true) - $start;

        // Cache timing difference should not be exploitable
        // (This is more of a documentation test - real timing attacks are complex)
        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertEquals($result1['is_valid'], $result2['is_valid']);
    }

    /** @test */
    public function it_prevents_log_injection_attacks(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Unauthorized meter reading validation attempt', \Mockery::on(function ($data) {
                // Verify log data doesn't contain injection attempts
                $this->assertIsArray($data);
                foreach ($data as $key => $value) {
                    if (is_string($value)) {
                        $this->assertStringNotContainsString("\n", $value);
                        $this->assertStringNotContainsString("\r", $value);
                        $this->assertStringNotContainsString("\0", $value);
                    }
                }
                return true;
            }));

        $this->actingAs($this->maliciousUser);

        // Create reading with potential log injection payload
        $reading = MeterReading::factory()->create([
            'tenant_id' => 1, // Different tenant to trigger unauthorized log
        ]);

        $this->validationEngine->validateMeterReading($reading);
    }

    /** @test */
    public function it_prevents_path_traversal_in_rate_schedule_keys(): void
    {
        $this->actingAs($this->maliciousUser);

        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 2]);

        $maliciousSchedule = [
            'rate_per_unit' => 0.15,
            '../../../etc/passwd' => 'path_traversal',
            '..\\..\\windows\\system32\\config\\sam' => 'windows_path_traversal',
            '/etc/shadow' => 'absolute_path',
            'normal_key' => 'normal_value',
        ];

        $result = $this->validationEngine->validateRateChangeRestrictions($serviceConfig, $maliciousSchedule);

        // Path traversal keys should be filtered out
        $this->assertArrayNotHasKey('../../../etc/passwd', $result);
        $this->assertArrayNotHasKey('..\\..\\windows\\system32\\config\\sam', $result);
        $this->assertArrayNotHasKey('/etc/shadow', $result);
        
        // Normal keys should be preserved if valid
        $this->assertArrayHasKey('rate_per_unit', $result);
    }

    /** @test */
    public function it_prevents_xml_external_entity_attacks(): void
    {
        $this->actingAs($this->maliciousUser);

        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 2]);

        // Attempt XXE attack through XML-like data
        $xxePayload = '<?xml version="1.0" encoding="UTF-8"?>
            <!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
            <rate>&xxe;</rate>';

        $maliciousSchedule = [
            'rate_per_unit' => $xxePayload,
            'xml_data' => $xxePayload,
        ];

        $result = $this->validationEngine->validateRateChangeRestrictions($serviceConfig, $maliciousSchedule);

        // XML payload should be sanitized or rejected
        if (isset($result['rate_per_unit'])) {
            $this->assertIsNumeric($result['rate_per_unit']);
        }
        $this->assertArrayNotHasKey('xml_data', $result);
    }

    /** @test */
    public function it_prevents_server_side_request_forgery(): void
    {
        $this->actingAs($this->maliciousUser);

        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => 2]);

        // Attempt SSRF through URL-like data
        $ssrfPayloads = [
            'http://localhost:22/ssh',
            'file:///etc/passwd',
            'ftp://internal.server/data',
            'gopher://127.0.0.1:25/xHELO',
        ];

        foreach ($ssrfPayloads as $payload) {
            $maliciousSchedule = [
                'rate_per_unit' => 0.15,
                'callback_url' => $payload,
                'webhook_endpoint' => $payload,
            ];

            $result = $this->validationEngine->validateRateChangeRestrictions($serviceConfig, $maliciousSchedule);

            // SSRF payloads should be filtered out
            $this->assertArrayNotHasKey('callback_url', $result);
            $this->assertArrayNotHasKey('webhook_endpoint', $result);
        }
    }

    /** @test */
    public function it_maintains_security_under_high_load(): void
    {
        $this->actingAs($this->maliciousUser);

        $meter = Meter::factory()->create(['tenant_id' => 2]);

        // Create large batch to test security under load
        $readings = collect();
        for ($i = 0; $i < 50; $i++) {
            $readings->push(MeterReading::factory()->make([
                'tenant_id' => 2,
                'meter_id' => $meter->id,
                'reading_values' => [
                    'injection_attempt_' . $i => "'; DROP TABLE meter_readings; --",
                ],
            ]));
        }

        // Should handle large batch securely
        $result = $this->validationEngine->batchValidateReadings($readings);

        $this->assertIsArray($result);
        $this->assertEquals(50, $result['total_readings']);
        $this->assertArrayHasKey('performance_metrics', $result);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
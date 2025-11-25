<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\UserRole;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * MeterReadingUpdateControllerSecurityTest
 * 
 * Security-focused tests for MeterReadingUpdateController.
 * 
 * Coverage:
 * - Authorization enforcement
 * - Rate limiting validation
 * - Security logging verification
 * - Cross-tenant access prevention
 * - Error handling security
 * - Audit trail integrity
 * 
 * @package Tests\Security
 * @group security
 * @group controllers
 * @group meter-readings
 */
class MeterReadingUpdateControllerSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiter before each test
        RateLimiter::clear('meter-reading-operations:*');
    }

    /**
     * Test that unauthenticated users are redirected to login.
     */
    public function test_unauthenticated_users_cannot_update_meter_readings(): void
    {
        $reading = MeterReading::factory()->create();

        $response = $this->put(route('meter-readings.update', $reading), [
            'value' => 1150.00,
            'change_reason' => 'Correcting reading',
        ]);

        $response->assertRedirect(route('login'));
    }

    /**
     * Test that tenant users cannot update meter readings.
     */
    public function test_tenant_users_cannot_update_meter_readings(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $reading = MeterReading::factory()->create();

        $this->actingAs($tenant);

        $response = $this->put(route('meter-readings.update', $reading), [
            'value' => 1150.00,
            'change_reason' => 'Correcting reading',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test that managers cannot update readings outside their tenant.
     */
    public function test_managers_cannot_update_cross_tenant_readings(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);
        
        $reading = MeterReading::factory()->create(['tenant_id' => 2]);

        $this->actingAs($manager);

        $response = $this->put(route('meter-readings.update', $reading), [
            'value' => 1150.00,
            'change_reason' => 'Correcting reading',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test that managers can update readings within their tenant.
     */
    public function test_managers_can_update_readings_within_tenant(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);
        
        $reading = MeterReading::factory()->create(['tenant_id' => 1]);

        $this->actingAs($manager);

        $response = $this->put(route('meter-readings.update', $reading), [
            'value' => 1150.00,
            'change_reason' => 'Correcting reading',
        ]);

        $response->assertRedirect();
    }

    /**
     * Test that admins can update readings across tenants.
     */
    public function test_admins_can_update_readings_across_tenants(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $reading = MeterReading::factory()->create(['tenant_id' => 2]);

        $this->actingAs($admin);

        $response = $this->put(route('meter-readings.update', $reading), [
            'value' => 1150.00,
            'change_reason' => 'Correcting reading',
        ]);

        $response->assertRedirect();
    }

    /**
     * Test that superadmins can update any reading.
     */
    public function test_superadmins_can_update_any_reading(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $reading = MeterReading::factory()->create();

        $this->actingAs($superadmin);

        $response = $this->put(route('meter-readings.update', $reading), [
            'value' => 1150.00,
            'change_reason' => 'Correcting reading',
        ]);

        $response->assertRedirect();
    }

    /**
     * Test that rate limiting prevents excessive updates.
     */
    public function test_rate_limiting_prevents_excessive_updates(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);
        
        $reading = MeterReading::factory()->create(['tenant_id' => 1]);

        $this->actingAs($manager);

        // Make 21 requests (limit is 20 per hour)
        for ($i = 0; $i < 21; $i++) {
            $response = $this->put(route('meter-readings.update', $reading), [
                'value' => 1000 + $i,
                'change_reason' => "Update {$i}",
            ]);

            if ($i < 20) {
                $response->assertRedirect();
            } else {
                $response->assertStatus(429); // Too Many Requests
            }
        }
    }

    /**
     * Test that security events are logged.
     */
    public function test_security_events_are_logged(): void
    {
        Log::spy();

        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);
        
        $reading = MeterReading::factory()->create(['tenant_id' => 1]);

        $this->actingAs($manager);

        $this->put(route('meter-readings.update', $reading), [
            'value' => 1150.00,
            'change_reason' => 'Correcting reading',
        ]);

        Log::shouldHaveReceived('info')
            ->with('Meter reading update initiated', \Mockery::type('array'));
    }

    /**
     * Test that failed updates are logged.
     */
    public function test_failed_updates_are_logged(): void
    {
        Log::spy();

        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);
        
        $reading = MeterReading::factory()->create(['tenant_id' => 1]);

        $this->actingAs($manager);

        // Trigger validation error (invalid value)
        $this->put(route('meter-readings.update', $reading), [
            'value' => -100, // Negative value should fail
            'change_reason' => 'Invalid update',
        ]);

        // Should log validation failure
        Log::shouldHaveReceived('error')
            ->with('Meter reading update failed', \Mockery::type('array'));
    }

    /**
     * Test authorization matrix for all roles.
     */
    public function test_authorization_matrix_for_all_roles(): void
    {
        $reading = MeterReading::factory()->create(['tenant_id' => 1]);

        $roles = [
            UserRole::SUPERADMIN => true,
            UserRole::ADMIN => true,
            UserRole::MANAGER => true, // Within tenant
            UserRole::TENANT => false,
        ];

        foreach ($roles as $role => $expected) {
            $user = User::factory()->create([
                'role' => $role,
                'tenant_id' => 1,
            ]);
            
            $this->actingAs($user);

            $response = $this->put(route('meter-readings.update', $reading), [
                'value' => 1150.00,
                'change_reason' => 'Test update',
            ]);

            if ($expected) {
                $response->assertRedirect();
            } else {
                $response->assertForbidden();
            }
        }
    }

    /**
     * Test that error messages don't leak sensitive information.
     */
    public function test_error_messages_dont_leak_sensitive_information(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);
        
        $reading = MeterReading::factory()->create(['tenant_id' => 1]);

        $this->actingAs($manager);

        // Trigger validation error
        $response = $this->put(route('meter-readings.update', $reading), [
            'value' => 'invalid',
            'change_reason' => 'Test',
        ]);

        // Should not contain database schema information
        $response->assertDontSee('database');
        $response->assertDontSee('SQL');
        $response->assertDontSee('table');
    }
}

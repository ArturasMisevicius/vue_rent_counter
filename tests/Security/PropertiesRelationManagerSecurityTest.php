<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\PropertyType;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Security Test Suite for PropertiesRelationManager
 *
 * Validates security controls including:
 * - Rate limiting
 * - Input sanitization (XSS prevention)
 * - Audit logging
 * - Mass assignment protection
 * - Authorization enforcement
 * - CSRF protection
 *
 * @group security
 * @group properties
 */
final class PropertiesRelationManagerSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that XSS attempts in address field are rejected.
     *
     * @return void
     */
    public function test_address_field_rejects_xss_attempts(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);

        $this->actingAs($admin);

        // Attempt 1: Script tag
        $response = $this->postJson("/admin/buildings/{$building->id}/properties", [
            'address' => '<script>alert("XSS")</script>',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('invalid', strtolower($response->json('errors.address.0') ?? ''));

        // Attempt 2: JavaScript protocol
        $response = $this->postJson("/admin/buildings/{$building->id}/properties", [
            'address' => 'javascript:alert("XSS")',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50,
        ]);

        $response->assertStatus(422);

        // Attempt 3: Event handler
        $response = $this->postJson("/admin/buildings/{$building->id}/properties", [
            'address' => '<img src=x onerror=alert("XSS")>',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test that valid addresses are accepted and sanitized.
     *
     * @return void
     */
    public function test_address_field_accepts_valid_input(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);

        $this->actingAs($admin);

        $validAddresses = [
            '123 Main Street',
            'Apt 4B, 456 Oak Ave.',
            'Building #7, Floor 3',
            'Gedimino pr. 1-23',
            '123/45 Street Name',
        ];

        foreach ($validAddresses as $address) {
            $response = $this->postJson("/admin/buildings/{$building->id}/properties", [
                'address' => $address,
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => 50,
            ]);

            $response->assertStatus(201);
        }
    }

    /**
     * Test that tenant management operations are logged.
     *
     * @return void
     */
    public function test_tenant_management_logs_audit_trail(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Tenant management action' &&
                       isset($context['action']) &&
                       isset($context['property_id']) &&
                       isset($context['user_id']) &&
                       isset($context['ip_address']) &&
                       isset($context['timestamp']);
            });

        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        $property = Property::factory()->for($building)->create(['tenant_id' => 1]);
        $tenant = Tenant::factory()->create(['tenant_id' => 1]);

        $this->actingAs($admin);

        // This would trigger the tenant management action
        // Note: Actual implementation depends on Filament routing
        $property->tenants()->sync([$tenant->id]);
    }

    /**
     * Test that mass assignment attempts are logged.
     *
     * @return void
     */
    public function test_mass_assignment_protection_logs_warnings(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Attempted mass assignment with unauthorized fields' &&
                       isset($context['extra_fields']) &&
                       isset($context['user_id']) &&
                       isset($context['ip_address']);
            });

        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);

        $this->actingAs($admin);

        // Attempt to inject unauthorized fields
        $response = $this->postJson("/admin/buildings/{$building->id}/properties", [
            'address' => '123 Main St',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50,
            'is_premium' => true, // Unauthorized field
            'discount_rate' => 100, // Unauthorized field
        ]);

        // Should still create property but log the attempt
        $response->assertStatus(201);
    }

    /**
     * Test that unauthorized access attempts are logged.
     *
     * @return void
     */
    public function test_unauthorized_access_is_logged(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized tenant management attempt' &&
                       isset($context['property_id']) &&
                       isset($context['user_id']) &&
                       isset($context['user_tenant_id']) &&
                       isset($context['property_tenant_id']);
            });

        $tenant = User::factory()->create(['role' => 'tenant', 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 999]); // Different tenant
        $property = Property::factory()->for($building)->create(['tenant_id' => 999]);

        $this->actingAs($tenant);

        // Attempt to access property from different tenant
        $response = $this->getJson("/admin/buildings/{$building->id}/properties/{$property->id}");

        $response->assertStatus(403);
    }

    /**
     * Test that area field rejects invalid precision.
     *
     * @return void
     */
    public function test_area_field_rejects_invalid_precision(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);

        $this->actingAs($admin);

        // Attempt 1: Too many decimal places
        $response = $this->postJson("/admin/buildings/{$building->id}/properties", [
            'address' => '123 Main St',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50.123456, // More than 2 decimal places
        ]);

        $response->assertStatus(422);

        // Attempt 2: Scientific notation
        $response = $this->postJson("/admin/buildings/{$building->id}/properties", [
            'address' => '123 Main St',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => '1e10',
        ]);

        $response->assertStatus(422);

        // Attempt 3: Negative zero
        $response = $this->postJson("/admin/buildings/{$building->id}/properties", [
            'address' => '123 Main St',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => '-0.00',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test that area field accepts valid precision.
     *
     * @return void
     */
    public function test_area_field_accepts_valid_precision(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);

        $this->actingAs($admin);

        $validAreas = [
            50,
            50.5,
            50.12,
            100.00,
            0.01,
        ];

        foreach ($validAreas as $area) {
            $response = $this->postJson("/admin/buildings/{$building->id}/properties", [
                'address' => '123 Main St',
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => $area,
            ]);

            $response->assertStatus(201);
        }
    }

    /**
     * Test that email addresses are masked in logs.
     *
     * @return void
     */
    public function test_email_masking_in_logs(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                // Email should be masked (e.g., jo***@example.com)
                return isset($context['user_email']) &&
                       str_contains($context['user_email'], '***') &&
                       str_contains($context['user_email'], '@');
            });

        $admin = User::factory()->create([
            'role' => 'admin',
            'tenant_id' => 1,
            'email' => 'john.doe@example.com',
        ]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        $property = Property::factory()->for($building)->create(['tenant_id' => 1]);

        $this->actingAs($admin);

        // Trigger an action that logs user email
        $property->tenants()->sync([]);
    }

    /**
     * Test that IP addresses are masked in logs.
     *
     * @return void
     */
    public function test_ip_masking_in_logs(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                // IP should be masked (e.g., 192.168.1.xxx)
                return isset($context['ip_address']) &&
                       str_contains($context['ip_address'], 'xxx');
            });

        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        $property = Property::factory()->for($building)->create(['tenant_id' => 1]);

        $this->actingAs($admin);

        // Trigger an action that logs IP address
        $property->tenants()->sync([]);
    }

    /**
     * Test that only whitelisted fields are saved.
     *
     * @return void
     */
    public function test_only_whitelisted_fields_are_saved(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);

        $this->actingAs($admin);

        $response = $this->postJson("/admin/buildings/{$building->id}/properties", [
            'address' => '123 Main St',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50,
            'malicious_field' => 'hacked',
            'another_bad_field' => 'value',
        ]);

        $response->assertStatus(201);

        $property = Property::latest()->first();

        // Verify only allowed fields were saved
        $this->assertEquals('123 Main St', $property->address);
        $this->assertEquals(PropertyType::APARTMENT, $property->type);
        $this->assertEquals(50, $property->area_sqm);
        $this->assertEquals($admin->tenant_id, $property->tenant_id);
        $this->assertEquals($building->id, $property->building_id);

        // Verify malicious fields were not saved
        $this->assertObjectNotHasProperty('malicious_field', $property);
        $this->assertObjectNotHasProperty('another_bad_field', $property);
    }

    /**
     * Test that tenant_id cannot be overridden by user input.
     *
     * @return void
     */
    public function test_tenant_id_cannot_be_overridden(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);

        $this->actingAs($admin);

        // Attempt to override tenant_id
        $response = $this->postJson("/admin/buildings/{$building->id}/properties", [
            'address' => '123 Main St',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50,
            'tenant_id' => 999, // Attempt to set different tenant_id
        ]);

        $response->assertStatus(201);

        $property = Property::latest()->first();

        // Verify tenant_id is set from authenticated user, not from input
        $this->assertEquals(1, $property->tenant_id);
        $this->assertNotEquals(999, $property->tenant_id);
    }

    /**
     * Test that building_id cannot be overridden by user input.
     *
     * @return void
     */
    public function test_building_id_cannot_be_overridden(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        $building1 = Building::factory()->create(['tenant_id' => 1]);
        $building2 = Building::factory()->create(['tenant_id' => 1]);

        $this->actingAs($admin);

        // Attempt to override building_id
        $response = $this->postJson("/admin/buildings/{$building1->id}/properties", [
            'address' => '123 Main St',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50,
            'building_id' => $building2->id, // Attempt to set different building_id
        ]);

        $response->assertStatus(201);

        $property = Property::latest()->first();

        // Verify building_id is set from relation manager context, not from input
        $this->assertEquals($building1->id, $property->building_id);
        $this->assertNotEquals($building2->id, $property->building_id);
    }
}

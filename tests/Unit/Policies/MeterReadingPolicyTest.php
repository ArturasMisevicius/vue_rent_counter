<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\MeterReadingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MeterReadingPolicyTest
 * 
 * Tests authorization rules for meter reading operations.
 * 
 * Requirements:
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.3: Manager can create and update meter readings
 * - 11.4: Tenant can only view their own meter readings
 * - 7.3: Cross-tenant access prevention
 */
class MeterReadingPolicyTest extends TestCase
{
    use RefreshDatabase;

    private MeterReadingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new MeterReadingPolicy();
    }

    /**
     * Test that all roles can view meter readings list.
     * 
     * Requirements: 11.1, 11.4
     */
    public function test_all_roles_can_view_any_meter_readings(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->policy->viewAny($superadmin));
        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->viewAny($manager));
        $this->assertTrue($this->policy->viewAny($tenant));
    }

    /**
     * Test that managers can view meter readings within their tenant.
     * 
     * Requirements: 11.3, 7.3
     */
    public function test_managers_can_view_meter_readings_within_tenant(): void
    {
        $tenantId = 1;
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenantId,
        ]);

        // Create meter with tenant_id = 1
        $meter1 = \App\Models\Meter::factory()->create(['tenant_id' => $tenantId]);
        $reading = MeterReading::factory()->forMeter($meter1)->create();

        // Create user for tenant 2 and authenticate as them temporarily
        $otherUser = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 2,
        ]);
        $this->actingAs($otherUser);
        
        // Create meter with tenant_id = 2
        $meter2 = \App\Models\Meter::factory()->create(['tenant_id' => 2]);
        $otherReading = MeterReading::factory()->forMeter($meter2)->create();
        
        // Switch back to original manager
        $this->actingAs($manager);
        
        // Fetch without global scope to verify tenant_id
        $otherReadingCheck = MeterReading::withoutGlobalScopes()->find($otherReading->id);
        $this->assertEquals(2, $otherReadingCheck->tenant_id, 'Other reading should have tenant_id = 2');

        $this->assertTrue($this->policy->view($manager, $reading));
        $this->assertFalse($this->policy->view($manager, $otherReadingCheck)); // Requirement 7.3
    }

    /**
     * Test that tenants can only view meter readings for their properties.
     * 
     * Requirements: 11.4
     */
    public function test_tenants_can_only_view_own_meter_readings(): void
    {
        $tenantId = 1;
        $email = 'tenant@example.com';
        
        $property = Property::factory()->create(['tenant_id' => $tenantId]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => $tenantId,
            'email' => $email,
        ]);
        $property->tenants()->attach($tenantRecord->id);

        $user = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenantId,
            'email' => $email,
        ]);

        $ownMeter = \App\Models\Meter::factory()->create([
            'tenant_id' => $tenantId,
            'property_id' => $property->id,
        ]);
        $ownReading = MeterReading::factory()->forMeter($ownMeter)->create();

        $otherProperty = Property::factory()->create(['tenant_id' => $tenantId]);
        $otherMeter = \App\Models\Meter::factory()->create([
            'tenant_id' => $tenantId,
            'property_id' => $otherProperty->id,
        ]);
        $otherReading = MeterReading::factory()->forMeter($otherMeter)->create();

        $this->assertTrue($this->policy->view($user, $ownReading));
        $this->assertFalse($this->policy->view($user, $otherReading)); // Requirement 11.4
    }

    /**
     * Test that admins and managers can create meter readings.
     * 
     * Requirements: 11.3
     */
    public function test_admins_and_managers_can_create_meter_readings(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->policy->create($superadmin));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->create($manager)); // Requirement 11.3
        $this->assertFalse($this->policy->create($tenant));
    }

    /**
     * Test that admins and managers can update meter readings.
     * 
     * Requirements: 11.3, 7.3
     */
    public function test_admins_and_managers_can_update_meter_readings(): void
    {
        $tenantId = 1;
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $tenantId,
        ]);
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenantId,
        ]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $reading = MeterReading::factory()->create(['tenant_id' => $tenantId]);

        $this->assertTrue($this->policy->update($admin, $reading));
        $this->assertTrue($this->policy->update($manager, $reading)); // Requirement 11.3
        $this->assertFalse($this->policy->update($tenant, $reading));
    }

    /**
     * Test cross-tenant access prevention for updates.
     * 
     * Requirements: 7.3
     */
    public function test_cross_tenant_access_prevention_for_updates(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);

        // Create user for tenant 2 and authenticate as them temporarily
        $otherUser = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 2,
        ]);
        $this->actingAs($otherUser);
        
        // Create meter with tenant_id = 2
        $otherMeter = \App\Models\Meter::factory()->create(['tenant_id' => 2]);
        // Create reading for tenant 2
        $otherTenantReading = MeterReading::factory()->forMeter($otherMeter)->create();
        
        // Switch back to original manager
        $this->actingAs($manager);
        
        // Fetch without global scope to get actual model
        $otherTenantReading = MeterReading::withoutGlobalScopes()->find($otherTenantReading->id);
        
        // Verify it's actually tenant 2
        $this->assertEquals(2, $otherTenantReading->tenant_id);

        $this->assertFalse($this->policy->view($manager, $otherTenantReading));
        $this->assertFalse($this->policy->update($manager, $otherTenantReading));
    }

    /**
     * Test that only admins can delete meter readings.
     * 
     * Requirements: 11.1
     */
    public function test_only_admins_can_delete_meter_readings(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $reading = MeterReading::factory()->create();

        $this->assertTrue($this->policy->delete($superadmin, $reading));
        $this->assertTrue($this->policy->delete($admin, $reading));
        $this->assertFalse($this->policy->delete($manager, $reading));
        $this->assertFalse($this->policy->delete($tenant, $reading));
    }
}

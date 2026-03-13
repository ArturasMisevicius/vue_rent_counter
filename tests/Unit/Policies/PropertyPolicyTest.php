<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\User;
use App\Policies\PropertyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for PropertyPolicy
 *
 * Tests:
 * - Role-based authorization (Superadmin, Admin, Manager, Tenant)
 * - CRUD actions (viewAny, view, create, update, delete, restore, forceDelete)
 * - Cross-tenant security (User A cannot modify User B's resources)
 * - Tenant isolation and ownership checks
 * - Special Tenant role restrictions (can only view assigned property)
 */
final class PropertyPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PropertyPolicy $policy;
    private User $superadmin;
    private User $admin;
    private User $manager;
    private User $tenant;
    private User $otherTenantManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PropertyPolicy();

        // Create users with different roles
        $this->superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => 1,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $this->manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);

        $this->tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'property_id' => null, // Will be set in specific tests
        ]);

        $this->otherTenantManager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 2,
        ]);
    }

    /** @test */
    public function superadmin_can_view_any_properties(): void
    {
        $this->assertTrue($this->policy->viewAny($this->superadmin));
    }

    /** @test */
    public function admin_can_view_any_properties(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    /** @test */
    public function manager_can_view_any_properties(): void
    {
        $this->assertTrue($this->policy->viewAny($this->manager));
    }

    /** @test */
    public function tenant_cannot_view_any_properties(): void
    {
        $this->assertFalse($this->policy->viewAny($this->tenant));
    }

    /** @test */
    public function superadmin_can_view_any_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);

        $this->assertTrue($this->policy->view($this->superadmin, $property));
    }

    /** @test */
    public function admin_can_view_any_property_regardless_of_tenant(): void
    {
        $property = Property::factory()->create(['tenant_id' => 2]);

        $this->assertTrue($this->policy->view($this->admin, $property));
    }

    /** @test */
    public function manager_can_view_own_tenant_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);

        $this->assertTrue($this->policy->view($this->manager, $property));
    }

    /** @test */
    public function manager_cannot_view_other_tenant_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 2]);

        $this->assertFalse($this->policy->view($this->manager, $property));
    }

    /** @test */
    public function tenant_can_view_assigned_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $this->tenant->property_id = $property->id;
        $this->tenant->save();

        $this->assertTrue($this->policy->view($this->tenant, $property));
    }

    /** @test */
    public function tenant_cannot_view_non_assigned_property(): void
    {
        $assignedProperty = Property::factory()->create(['tenant_id' => 1]);
        $otherProperty = Property::factory()->create(['tenant_id' => 1]);

        $this->tenant->property_id = $assignedProperty->id;
        $this->tenant->save();

        $this->assertFalse($this->policy->view($this->tenant, $otherProperty));
    }

    /** @test */
    public function tenant_without_property_cannot_view_any_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $this->tenant->property_id = null;

        $this->assertFalse($this->policy->view($this->tenant, $property));
    }

    /** @test */
    public function superadmin_can_create_properties(): void
    {
        $this->assertTrue($this->policy->create($this->superadmin));
    }

    /** @test */
    public function admin_can_create_properties(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    /** @test */
    public function manager_can_create_properties(): void
    {
        $this->assertTrue($this->policy->create($this->manager));
    }

    /** @test */
    public function tenant_cannot_create_properties(): void
    {
        $this->assertFalse($this->policy->create($this->tenant));
    }

    /** @test */
    public function superadmin_can_update_any_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 2]);

        $this->assertTrue($this->policy->update($this->superadmin, $property));
    }

    /** @test */
    public function admin_can_update_any_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 2]);

        $this->assertTrue($this->policy->update($this->admin, $property));
    }

    /** @test */
    public function manager_can_update_own_tenant_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);

        $this->assertTrue($this->policy->update($this->manager, $property));
    }

    /** @test */
    public function manager_cannot_update_other_tenant_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 2]);

        $this->assertFalse($this->policy->update($this->manager, $property));
    }

    /** @test */
    public function tenant_cannot_update_any_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $this->tenant->property_id = $property->id;

        $this->assertFalse($this->policy->update($this->tenant, $property));
    }

    /** @test */
    public function cross_tenant_security_manager_cannot_update_other_tenant_property(): void
    {
        // Manager from tenant 1
        $managerTenant1 = $this->manager;

        // Property from tenant 2
        $propertyTenant2 = Property::factory()->create(['tenant_id' => 2]);

        // Assert cross-tenant security: Manager A CANNOT update Property B
        $this->assertFalse($this->policy->update($managerTenant1, $propertyTenant2));
    }

    /** @test */
    public function superadmin_can_delete_any_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 2]);

        $this->assertTrue($this->policy->delete($this->superadmin, $property));
    }

    /** @test */
    public function admin_can_delete_any_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 2]);

        $this->assertTrue($this->policy->delete($this->admin, $property));
    }

    /** @test */
    public function manager_can_delete_own_tenant_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);

        $this->assertTrue($this->policy->delete($this->manager, $property));
    }

    /** @test */
    public function manager_cannot_delete_other_tenant_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 2]);

        $this->assertFalse($this->policy->delete($this->manager, $property));
    }

    /** @test */
    public function tenant_cannot_delete_any_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $this->tenant->property_id = $property->id;

        $this->assertFalse($this->policy->delete($this->tenant, $property));
    }

    /** @test */
    public function cross_tenant_security_manager_cannot_delete_other_tenant_property(): void
    {
        // Manager from tenant 1
        $managerTenant1 = $this->manager;

        // Property from tenant 2
        $propertyTenant2 = Property::factory()->create(['tenant_id' => 2]);

        // Assert cross-tenant security: Manager A CANNOT delete Property B
        $this->assertFalse($this->policy->delete($managerTenant1, $propertyTenant2));
    }

    /** @test */
    public function superadmin_can_restore_any_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 2]);

        $this->assertTrue($this->policy->restore($this->superadmin, $property));
    }

    /** @test */
    public function admin_can_restore_any_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 2]);

        $this->assertTrue($this->policy->restore($this->admin, $property));
    }

    /** @test */
    public function manager_can_restore_own_tenant_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);

        $this->assertTrue($this->policy->restore($this->manager, $property));
    }

    /** @test */
    public function manager_cannot_restore_other_tenant_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 2]);

        $this->assertFalse($this->policy->restore($this->manager, $property));
    }

    /** @test */
    public function tenant_cannot_restore_any_property(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);

        $this->assertFalse($this->policy->restore($this->tenant, $property));
    }

    /** @test */
    public function only_superadmin_can_force_delete(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);

        $this->assertTrue($this->policy->forceDelete($this->superadmin, $property));
    }

    /** @test */
    public function admin_cannot_force_delete(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);

        $this->assertFalse($this->policy->forceDelete($this->admin, $property));
    }

    /** @test */
    public function manager_cannot_force_delete(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);

        $this->assertFalse($this->policy->forceDelete($this->manager, $property));
    }

    /** @test */
    public function tenant_cannot_force_delete(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);

        $this->assertFalse($this->policy->forceDelete($this->tenant, $property));
    }
}

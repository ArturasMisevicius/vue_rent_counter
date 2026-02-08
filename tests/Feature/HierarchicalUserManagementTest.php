<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HierarchicalUserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the users table has all required hierarchical columns.
     */
    public function test_users_table_has_hierarchical_columns(): void
    {
        $columns = Schema::getColumnListing('users');
        
        $this->assertContains('property_id', $columns);
        $this->assertContains('parent_user_id', $columns);
        $this->assertContains('is_active', $columns);
        $this->assertContains('organization_name', $columns);
    }

    /**
     * Test that the subscriptions table exists with all required columns.
     */
    public function test_subscriptions_table_exists_with_required_columns(): void
    {
        $columns = Schema::getColumnListing('subscriptions');
        
        $this->assertContains('user_id', $columns);
        $this->assertContains('plan_type', $columns);
        $this->assertContains('status', $columns);
        $this->assertContains('starts_at', $columns);
        $this->assertContains('expires_at', $columns);
        $this->assertContains('max_properties', $columns);
        $this->assertContains('max_tenants', $columns);
    }

    /**
     * Test that the user_assignments_audit table exists with all required columns.
     */
    public function test_user_assignments_audit_table_exists_with_required_columns(): void
    {
        $columns = Schema::getColumnListing('user_assignments_audit');
        
        $this->assertContains('user_id', $columns);
        $this->assertContains('property_id', $columns);
        $this->assertContains('previous_property_id', $columns);
        $this->assertContains('performed_by', $columns);
        $this->assertContains('action', $columns);
        $this->assertContains('reason', $columns);
    }

    /**
     * Test that UserRole enum includes superadmin.
     */
    public function test_user_role_enum_includes_superadmin(): void
    {
        $roles = array_map(fn($case) => $case->value, UserRole::cases());
        
        $this->assertContains('superadmin', $roles);
        $this->assertContains('admin', $roles);
        $this->assertContains('manager', $roles);
        $this->assertContains('tenant', $roles);
    }

    /**
     * Test that User model has property relationship.
     */
    public function test_user_model_has_property_relationship(): void
    {
        $building = Building::factory()->create();
        $property = Property::factory()->create(['building_id' => $building->id]);
        $user = User::factory()->create([
            'role' => UserRole::TENANT,
            'property_id' => $property->id,
        ]);

        $this->assertInstanceOf(Property::class, $user->property);
        $this->assertEquals($property->id, $user->property->id);
    }

    /**
     * Test that User model has parentUser relationship.
     */
    public function test_user_model_has_parent_user_relationship(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'parent_user_id' => $admin->id,
        ]);

        $this->assertInstanceOf(User::class, $tenant->parentUser);
        $this->assertEquals($admin->id, $tenant->parentUser->id);
    }

    /**
     * Test that User model has childUsers relationship.
     */
    public function test_user_model_has_child_users_relationship(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tenant1 = User::factory()->create([
            'role' => UserRole::TENANT,
            'parent_user_id' => $admin->id,
        ]);
        $tenant2 = User::factory()->create([
            'role' => UserRole::TENANT,
            'parent_user_id' => $admin->id,
        ]);

        $this->assertCount(2, $admin->childUsers);
        $this->assertTrue($admin->childUsers->contains($tenant1));
        $this->assertTrue($admin->childUsers->contains($tenant2));
    }

    /**
     * Test that User model has subscription relationship.
     */
    public function test_user_model_has_subscription_relationship(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $subscription = Subscription::factory()->create([
            'user_id' => $admin->id,
        ]);

        $this->assertInstanceOf(Subscription::class, $admin->subscription);
        $this->assertEquals($subscription->id, $admin->subscription->id);
    }

    /**
     * Test that User model has meterReadings relationship.
     */
    public function test_user_model_has_meter_readings_relationship(): void
    {
        $user = User::factory()->create();

        // Just verify the relationship exists and returns a collection
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->meterReadings);
    }

    /**
     * Test that Subscription model has required methods.
     */
    public function test_subscription_model_has_required_methods(): void
    {
        $subscription = new Subscription();

        $this->assertTrue(method_exists($subscription, 'isActive'));
        $this->assertTrue(method_exists($subscription, 'isExpired'));
        $this->assertTrue(method_exists($subscription, 'daysUntilExpiry'));
        $this->assertTrue(method_exists($subscription, 'canAddProperty'));
        $this->assertTrue(method_exists($subscription, 'canAddTenant'));
    }

    /**
     * Test that User model has role helper methods.
     */
    public function test_user_model_has_role_helper_methods(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($superadmin->isSuperadmin());
        $this->assertFalse($superadmin->isAdmin());
        $this->assertFalse($superadmin->isManager());
        $this->assertFalse($superadmin->isTenantUser());

        $this->assertFalse($admin->isSuperadmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isManager());
        $this->assertFalse($admin->isTenantUser());

        $this->assertFalse($manager->isSuperadmin());
        $this->assertFalse($manager->isAdmin());
        $this->assertTrue($manager->isManager());
        $this->assertFalse($manager->isTenantUser());

        $this->assertFalse($tenant->isSuperadmin());
        $this->assertFalse($tenant->isAdmin());
        $this->assertFalse($tenant->isManager());
        $this->assertTrue($tenant->isTenantUser());
    }

    /**
     * Test that User model fillable includes new fields.
     */
    public function test_user_model_fillable_includes_new_fields(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('property_id', $fillable);
        $this->assertContains('parent_user_id', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('organization_name', $fillable);
    }

    /**
     * Test that User model casts is_active to boolean.
     */
    public function test_user_model_casts_is_active_to_boolean(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->assertIsBool($user->is_active);
        $this->assertTrue($user->is_active);
    }

    /**
     * Test creating a complete hierarchical user structure.
     */
    public function test_creating_complete_hierarchical_user_structure(): void
    {
        // Create a superadmin
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        // Create an admin with subscription
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
            'organization_name' => 'Test Organization',
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $admin->id,
            'plan_type' => 'professional',
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'max_properties' => 50,
            'max_tenants' => 200,
        ]);

        // Create a building and property
        $building = Building::factory()->create(['tenant_id' => 1]);
        $property = Property::factory()->create([
            'building_id' => $building->id,
            'tenant_id' => 1,
        ]);

        // Create a tenant user
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'property_id' => $property->id,
            'parent_user_id' => $admin->id,
            'is_active' => true,
        ]);

        // Verify the structure
        $this->assertTrue($superadmin->isSuperadmin());
        $this->assertNull($superadmin->tenant_id);

        $this->assertTrue($admin->isAdmin());
        $this->assertEquals(1, $admin->tenant_id);
        $this->assertEquals('Test Organization', $admin->organization_name);
        $this->assertNotNull($admin->subscription);
        $this->assertTrue($admin->subscription->isActive());

        $this->assertTrue($tenant->isTenantUser());
        $this->assertEquals(1, $tenant->tenant_id);
        $this->assertEquals($property->id, $tenant->property_id);
        $this->assertEquals($admin->id, $tenant->parent_user_id);
        $this->assertNotNull($tenant->property);
        $this->assertNotNull($tenant->parentUser);
    }
}

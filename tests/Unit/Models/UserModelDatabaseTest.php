<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Property;
use App\Models\SystemTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

/**
 * User Model Database Tests
 * 
 * Tests database constraints, indexes, and relationships
 */
class UserModelDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_constraint_enforced(): void
    {
        $this->expectException(QueryException::class);
        
        User::factory()->create(['role' => 'invalid_role']);
    }

    public function test_superadmin_tenant_hierarchy_constraint(): void
    {
        // Superadmin should have null tenant_id
        $superadmin = User::factory()->superadmin()->create();
        
        $this->assertNull($superadmin->tenant_id);
        $this->assertEquals(UserRole::SUPERADMIN, $superadmin->role);
    }

    public function test_admin_tenant_hierarchy_constraint(): void
    {
        // Admin should have tenant_id but no property_id
        $admin = User::factory()->admin()->create();
        
        $this->assertNotNull($admin->tenant_id);
        $this->assertNull($admin->property_id);
        $this->assertEquals(UserRole::ADMIN, $admin->role);
    }

    public function test_tenant_hierarchy_constraint(): void
    {
        // Tenant should have both tenant_id and property_id
        $tenant = User::factory()->tenant()->create();
        
        $this->assertNotNull($tenant->tenant_id);
        $this->assertNotNull($tenant->property_id);
        $this->assertNotNull($tenant->parent_user_id);
        $this->assertEquals(UserRole::TENANT, $tenant->role);
    }

    public function test_parent_relationship_constraint(): void
    {
        // Tenant should have parent_user_id
        $admin = User::factory()->admin()->create();
        $tenant = User::factory()->tenant($admin->tenant_id, null, $admin->id)->create();
        
        $this->assertEquals($admin->id, $tenant->parent_user_id);
        $this->assertNull($admin->parent_user_id);
    }

    public function test_user_scopes_work_correctly(): void
    {
        $activeTenant = User::factory()->tenant()->create(['is_active' => true]);
        $inactiveTenant = User::factory()->tenant()->create(['is_active' => false]);
        $suspendedTenant = User::factory()->tenant()->suspended()->create();
        
        // Test active scope
        $activeUsers = User::active()->get();
        $this->assertTrue($activeUsers->contains($activeTenant));
        $this->assertFalse($activeUsers->contains($inactiveTenant));
        $this->assertFalse($activeUsers->contains($suspendedTenant));
        
        // Test suspended scope
        $suspendedUsers = User::suspended()->get();
        $this->assertTrue($suspendedUsers->contains($suspendedTenant));
        $this->assertFalse($suspendedUsers->contains($activeTenant));
    }

    public function test_user_api_eligible_scope(): void
    {
        $eligibleUser = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
            'suspended_at' => null,
        ]);
        
        $ineligibleUser = User::factory()->create([
            'is_active' => false,
            'email_verified_at' => null,
            'suspended_at' => now(),
        ]);
        
        $eligibleUsers = User::apiEligible()->get();
        
        $this->assertTrue($eligibleUsers->contains($eligibleUser));
        $this->assertFalse($eligibleUsers->contains($ineligibleUser));
    }

    public function test_user_relationships_load_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $property = Property::factory()->create(['tenant_id' => $admin->tenant_id]);
        $tenant = User::factory()->tenant($admin->tenant_id, $property->id, $admin->id)->create();
        
        // Test parent relationship
        $this->assertEquals($admin->id, $tenant->parentUser->id);
        
        // Test child relationship
        $this->assertTrue($admin->childUsers->contains($tenant));
        
        // Test property relationship
        $this->assertEquals($property->id, $tenant->property->id);
    }

    public function test_user_with_common_relations_scope(): void
    {
        $tenant = User::factory()->tenant()->create();
        
        $users = User::withCommonRelations()->where('id', $tenant->id)->get();
        
        // Should not trigger additional queries for these relationships
        $this->assertTrue($users->first()->relationLoaded('property'));
        $this->assertTrue($users->first()->relationLoaded('parentUser'));
    }

    public function test_user_api_token_methods(): void
    {
        $user = User::factory()->admin()->create();
        
        // Test token creation
        $token = $user->createApiToken('test-token');
        $this->assertIsString($token);
        
        // Test token count
        $this->assertEquals(1, $user->getActiveTokensCount());
        
        // Test token revocation
        $user->revokeAllApiTokens();
        $this->assertEquals(0, $user->fresh()->getActiveTokensCount());
    }

    public function test_user_role_priority_ordering(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $tenant = User::factory()->tenant()->create();
        
        $orderedUsers = User::orderedByRole()->get();
        
        $roles = $orderedUsers->pluck('role')->toArray();
        
        // Should be ordered: superadmin, admin, manager, tenant
        $expectedOrder = [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ];
        
        $this->assertEquals($expectedOrder, array_values(array_unique($roles)));
    }

    public function test_user_soft_deletes_work(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;
        
        // Soft delete
        $user->delete();
        
        // Should not be found in normal queries
        $this->assertNull(User::find($userId));
        
        // Should be found with trashed
        $this->assertNotNull(User::withTrashed()->find($userId));
    }

    public function test_user_cache_clearing(): void
    {
        $user = User::factory()->admin()->create();
        
        // This should not throw any exceptions
        $user->clearCache();
        
        $this->assertTrue(true);
    }

    public function test_user_capabilities_and_state(): void
    {
        $user = User::factory()->admin()->create();
        
        $capabilities = $user->getCapabilities();
        $state = $user->getState();
        
        $this->assertTrue($capabilities->canManageProperties());
        $this->assertTrue($state->isActive());
        $this->assertIsArray($capabilities->toArray());
        $this->assertIsArray($state->toArray());
    }
}
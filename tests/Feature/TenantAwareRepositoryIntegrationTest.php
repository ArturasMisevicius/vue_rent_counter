<?php

namespace Tests\Feature;

use App\Contracts\TenantContextInterface;
use App\Models\User;
use App\Repositories\TenantAwareUserRepository;
use App\ValueObjects\TenantId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAwareRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private TenantContextInterface $tenantContext;
    private TenantAwareUserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContextInterface::class);
        $this->userRepository = new TenantAwareUserRepository($this->tenantContext);
    }

    public function test_repository_respects_tenant_context(): void
    {
        $tenant1 = new TenantId('tenant-1');
        $tenant2 = new TenantId('tenant-2');
        
        // Create users for different tenants
        $user1 = User::factory()->create([
            'name' => 'User 1',
            'email' => 'user1@tenant1.com',
            'tenant_id' => $tenant1->getValue(),
        ]);
        
        $user2 = User::factory()->create([
            'name' => 'User 2',
            'email' => 'user2@tenant2.com',
            'tenant_id' => $tenant2->getValue(),
        ]);
        
        // Set tenant context to tenant-1
        $this->userRepository->setTenantContext($tenant1);
        
        // Should only find user from tenant-1
        $foundUser = $this->userRepository->findByEmail('user1@tenant1.com');
        $this->assertNotNull($foundUser);
        $this->assertEquals($user1->id, $foundUser->id);
        
        // Should not find user from tenant-2
        $notFoundUser = $this->userRepository->findByEmail('user2@tenant2.com');
        $this->assertNull($notFoundUser);
        
        // Switch to tenant-2
        $this->userRepository->setTenantContext($tenant2);
        
        // Should now find user from tenant-2
        $foundUser2 = $this->userRepository->findByEmail('user2@tenant2.com');
        $this->assertNotNull($foundUser2);
        $this->assertEquals($user2->id, $foundUser2->id);
        
        // Should not find user from tenant-1
        $notFoundUser1 = $this->userRepository->findByEmail('user1@tenant1.com');
        $this->assertNull($notFoundUser1);
    }

    public function test_repository_can_query_specific_tenant(): void
    {
        $tenant1 = new TenantId('tenant-1');
        $tenant2 = new TenantId('tenant-2');
        
        // Create users for different tenants
        User::factory()->create([
            'name' => 'User 1',
            'email' => 'user1@tenant1.com',
            'tenant_id' => $tenant1->getValue(),
        ]);
        
        User::factory()->create([
            'name' => 'User 2',
            'email' => 'user2@tenant2.com',
            'tenant_id' => $tenant2->getValue(),
        ]);
        
        // Query specific tenant without setting context
        $user1 = $this->userRepository->findByEmailForTenant('user1@tenant1.com', $tenant1);
        $this->assertNotNull($user1);
        $this->assertEquals('user1@tenant1.com', $user1->email);
        
        $user2 = $this->userRepository->findByEmailForTenant('user2@tenant2.com', $tenant2);
        $this->assertNotNull($user2);
        $this->assertEquals('user2@tenant2.com', $user2->email);
        
        // Cross-tenant queries should return null
        $crossTenantUser = $this->userRepository->findByEmailForTenant('user1@tenant1.com', $tenant2);
        $this->assertNull($crossTenantUser);
    }

    public function test_repository_create_adds_tenant_id(): void
    {
        $tenantId = new TenantId('test-tenant');
        $this->userRepository->setTenantContext($tenantId);
        
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ];
        
        $user = $this->userRepository->create($userData);
        
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals($tenantId->getValue(), $user->tenant_id);
    }

    public function test_repository_create_for_tenant_adds_specific_tenant_id(): void
    {
        $tenantId = new TenantId('specific-tenant');
        
        $userData = [
            'name' => 'Specific User',
            'email' => 'specific@example.com',
            'password' => bcrypt('password'),
        ];
        
        $user = $this->userRepository->createForTenant($userData, $tenantId);
        
        $this->assertEquals('Specific User', $user->name);
        $this->assertEquals('specific@example.com', $user->email);
        $this->assertEquals($tenantId->getValue(), $user->tenant_id);
    }

    public function test_repository_get_all_respects_tenant_context(): void
    {
        $tenant1 = new TenantId('tenant-1');
        $tenant2 = new TenantId('tenant-2');
        
        // Create users for different tenants
        User::factory()->count(3)->create(['tenant_id' => $tenant1->getValue()]);
        User::factory()->count(2)->create(['tenant_id' => $tenant2->getValue()]);
        
        // Set context to tenant-1
        $this->userRepository->setTenantContext($tenant1);
        $tenant1Users = $this->userRepository->getAll();
        $this->assertCount(3, $tenant1Users);
        
        // Set context to tenant-2
        $this->userRepository->setTenantContext($tenant2);
        $tenant2Users = $this->userRepository->getAll();
        $this->assertCount(2, $tenant2Users);
    }

    public function test_repository_pagination_respects_tenant_context(): void
    {
        $tenant1 = new TenantId('tenant-1');
        $tenant2 = new TenantId('tenant-2');
        
        // Create users for different tenants
        User::factory()->count(10)->create(['tenant_id' => $tenant1->getValue()]);
        User::factory()->count(5)->create(['tenant_id' => $tenant2->getValue()]);
        
        // Test pagination for tenant-1
        $this->userRepository->setTenantContext($tenant1);
        $paginated1 = $this->userRepository->getPaginated(5);
        $this->assertEquals(10, $paginated1->total());
        $this->assertCount(5, $paginated1->items());
        
        // Test pagination for tenant-2
        $this->userRepository->setTenantContext($tenant2);
        $paginated2 = $this->userRepository->getPaginated(5);
        $this->assertEquals(5, $paginated2->total());
        $this->assertCount(5, $paginated2->items());
    }

    public function test_repository_update_respects_tenant_context(): void
    {
        $tenant1 = new TenantId('tenant-1');
        $tenant2 = new TenantId('tenant-2');
        
        $user1 = User::factory()->create([
            'name' => 'Original Name',
            'tenant_id' => $tenant1->getValue(),
        ]);
        
        $user2 = User::factory()->create([
            'name' => 'Other User',
            'tenant_id' => $tenant2->getValue(),
        ]);
        
        // Set context to tenant-1
        $this->userRepository->setTenantContext($tenant1);
        
        // Should be able to update user from tenant-1
        $updatedUser = $this->userRepository->update($user1->id, ['name' => 'Updated Name']);
        $this->assertNotNull($updatedUser);
        $this->assertEquals('Updated Name', $updatedUser->name);
        
        // Should not be able to update user from tenant-2
        $notUpdatedUser = $this->userRepository->update($user2->id, ['name' => 'Should Not Update']);
        $this->assertNull($notUpdatedUser);
        
        // Verify user2 was not updated
        $user2->refresh();
        $this->assertEquals('Other User', $user2->name);
    }

    public function test_repository_delete_respects_tenant_context(): void
    {
        $tenant1 = new TenantId('tenant-1');
        $tenant2 = new TenantId('tenant-2');
        
        $user1 = User::factory()->create(['tenant_id' => $tenant1->getValue()]);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->getValue()]);
        
        // Set context to tenant-1
        $this->userRepository->setTenantContext($tenant1);
        
        // Should be able to delete user from tenant-1
        $deleted = $this->userRepository->delete($user1->id);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('users', ['id' => $user1->id]);
        
        // Should not be able to delete user from tenant-2
        $notDeleted = $this->userRepository->delete($user2->id);
        $this->assertFalse($notDeleted);
        $this->assertDatabaseHas('users', ['id' => $user2->id]);
    }

    public function test_repository_count_respects_tenant_context(): void
    {
        $tenant1 = new TenantId('tenant-1');
        $tenant2 = new TenantId('tenant-2');
        
        User::factory()->count(7)->create(['tenant_id' => $tenant1->getValue()]);
        User::factory()->count(3)->create(['tenant_id' => $tenant2->getValue()]);
        
        // Count for tenant-1
        $this->userRepository->setTenantContext($tenant1);
        $count1 = $this->userRepository->count();
        $this->assertEquals(7, $count1);
        
        // Count for tenant-2
        $this->userRepository->setTenantContext($tenant2);
        $count2 = $this->userRepository->count();
        $this->assertEquals(3, $count2);
    }

    public function test_repository_search_respects_tenant_context(): void
    {
        $tenant1 = new TenantId('tenant-1');
        $tenant2 = new TenantId('tenant-2');
        
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@tenant1.com',
            'tenant_id' => $tenant1->getValue(),
        ]);
        
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@tenant2.com',
            'tenant_id' => $tenant2->getValue(),
        ]);
        
        // Search in tenant-1
        $this->userRepository->setTenantContext($tenant1);
        $results1 = $this->userRepository->searchUsers('john');
        $this->assertCount(1, $results1);
        $this->assertEquals('john@tenant1.com', $results1->first()->email);
        
        // Search in tenant-2
        $this->userRepository->setTenantContext($tenant2);
        $results2 = $this->userRepository->searchUsers('john');
        $this->assertCount(1, $results2);
        $this->assertEquals('john@tenant2.com', $results2->first()->email);
    }

    public function test_repository_user_stats_respects_tenant_context(): void
    {
        $tenant1 = new TenantId('tenant-1');
        $tenant2 = new TenantId('tenant-2');
        
        // Create users for tenant-1
        User::factory()->count(2)->create([
            'tenant_id' => $tenant1->getValue(),
            'is_active' => true,
            'role' => 'admin',
        ]);
        User::factory()->count(3)->create([
            'tenant_id' => $tenant1->getValue(),
            'is_active' => true,
            'role' => 'user',
        ]);
        User::factory()->create([
            'tenant_id' => $tenant1->getValue(),
            'is_active' => false,
            'role' => 'user',
        ]);
        
        // Create users for tenant-2
        User::factory()->create([
            'tenant_id' => $tenant2->getValue(),
            'is_active' => true,
            'role' => 'admin',
        ]);
        
        // Get stats for tenant-1
        $this->userRepository->setTenantContext($tenant1);
        $stats1 = $this->userRepository->getUserStats();
        
        $this->assertEquals(6, $stats1['total']);
        $this->assertEquals(5, $stats1['active']);
        $this->assertEquals(1, $stats1['inactive']);
        $this->assertEquals(['admin' => 2, 'user' => 4], $stats1['by_role']);
        
        // Get stats for tenant-2
        $this->userRepository->setTenantContext($tenant2);
        $stats2 = $this->userRepository->getUserStats();
        
        $this->assertEquals(1, $stats2['total']);
        $this->assertEquals(1, $stats2['active']);
        $this->assertEquals(0, $stats2['inactive']);
        $this->assertEquals(['admin' => 1], $stats2['by_role']);
    }
}
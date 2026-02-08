<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User Repository Unit Tests
 * 
 * Tests the UserRepository implementation to ensure all methods
 * work correctly with proper error handling and data integrity.
 */
class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = new UserRepository(new User());
    }

    /** @test */
    public function it_can_create_a_user(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ];

        $user = $this->userRepository->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals(UserRole::ADMIN, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    /** @test */
    public function it_can_find_user_by_id(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $foundUser = $this->userRepository->find($user->id);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertEquals('Jane Doe', $foundUser->name);
    }

    /** @test */
    public function it_returns_null_when_user_not_found(): void
    {
        $foundUser = $this->userRepository->find(999);

        $this->assertNull($foundUser);
    }

    /** @test */
    public function it_can_find_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $foundUser = $this->userRepository->findByEmail('test@example.com');

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertEquals('test@example.com', $foundUser->email);
    }

    /** @test */
    public function it_can_find_users_by_role(): void
    {
        User::factory()->count(3)->create(['role' => UserRole::ADMIN]);
        User::factory()->count(2)->create(['role' => UserRole::TENANT]);

        $adminUsers = $this->userRepository->findByRole(UserRole::ADMIN);
        $tenantUsers = $this->userRepository->findByRole(UserRole::TENANT);

        $this->assertInstanceOf(Collection::class, $adminUsers);
        $this->assertInstanceOf(Collection::class, $tenantUsers);
        $this->assertCount(3, $adminUsers);
        $this->assertCount(2, $tenantUsers);
        
        $adminUsers->each(function ($user) {
            $this->assertEquals(UserRole::ADMIN, $user->role);
        });
    }

    /** @test */
    public function it_can_find_active_users(): void
    {
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->count(2)->create(['is_active' => false]);

        $activeUsers = $this->userRepository->findActiveUsers();

        $this->assertInstanceOf(Collection::class, $activeUsers);
        $this->assertCount(3, $activeUsers);
        
        $activeUsers->each(function ($user) {
            $this->assertTrue($user->is_active);
        });
    }

    /** @test */
    public function it_can_update_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $updatedUser = $this->userRepository->update($user->id, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertEquals('updated@example.com', $updatedUser->email);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    /** @test */
    public function it_can_delete_user(): void
    {
        $user = User::factory()->create();

        $result = $this->userRepository->delete($user->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function it_can_search_users(): void
    {
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
        ]);
        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
        ]);
        User::factory()->create([
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
        ]);

        $searchResults = $this->userRepository->searchUsers('john');

        $this->assertInstanceOf(Collection::class, $searchResults);
        $this->assertCount(2, $searchResults); // John Smith and Bob Johnson
    }

    /** @test */
    public function it_can_count_users_by_role(): void
    {
        User::factory()->count(5)->create(['role' => UserRole::ADMIN]);
        User::factory()->count(3)->create(['role' => UserRole::TENANT]);

        $adminCount = $this->userRepository->countByRole(UserRole::ADMIN);
        $tenantCount = $this->userRepository->countByRole(UserRole::TENANT);

        $this->assertEquals(5, $adminCount);
        $this->assertEquals(3, $tenantCount);
    }

    /** @test */
    public function it_can_activate_user(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => 'Test suspension',
        ]);

        $activatedUser = $this->userRepository->activateUser($user->id);

        $this->assertTrue($activatedUser->is_active);
        $this->assertNull($activatedUser->suspended_at);
        $this->assertNull($activatedUser->suspension_reason);
    }

    /** @test */
    public function it_can_deactivate_user(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $deactivatedUser = $this->userRepository->deactivateUser($user->id, 'Test reason');

        $this->assertFalse($deactivatedUser->is_active);
        $this->assertNotNull($deactivatedUser->suspended_at);
        $this->assertEquals('Test reason', $deactivatedUser->suspension_reason);
    }

    /** @test */
    public function it_can_update_last_login(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);

        $updatedUser = $this->userRepository->updateLastLogin($user->id);

        $this->assertNotNull($updatedUser->last_login_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $updatedUser->last_login_at);
    }

    /** @test */
    public function it_can_get_user_stats(): void
    {
        User::factory()->count(3)->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        User::factory()->count(2)->create(['role' => UserRole::TENANT, 'is_active' => true]);
        User::factory()->count(1)->create(['role' => UserRole::ADMIN, 'is_active' => false]);

        $stats = $this->userRepository->getUserStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_users', $stats);
        $this->assertArrayHasKey('active_users', $stats);
        $this->assertArrayHasKey('admin_users', $stats);
        $this->assertArrayHasKey('tenant_users', $stats);
        
        $this->assertEquals(6, $stats['total_users']);
        $this->assertEquals(5, $stats['active_users']);
        $this->assertEquals(4, $stats['admin_users']); // 3 active + 1 inactive
        $this->assertEquals(2, $stats['tenant_users']);
    }

    /** @test */
    public function it_can_find_users_created_between_dates(): void
    {
        $startDate = now()->subDays(7);
        $endDate = now()->subDays(1);
        
        // Create users within the date range
        User::factory()->count(3)->create(['created_at' => now()->subDays(3)]);
        
        // Create users outside the date range
        User::factory()->count(2)->create(['created_at' => now()->subDays(10)]);

        $users = $this->userRepository->findCreatedBetween($startDate, $endDate);

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertCount(3, $users);
    }

    /** @test */
    public function it_can_paginate_users(): void
    {
        User::factory()->count(25)->create();

        $paginatedUsers = $this->userRepository->paginate(10);

        $this->assertEquals(10, $paginatedUsers->perPage());
        $this->assertEquals(25, $paginatedUsers->total());
        $this->assertEquals(3, $paginatedUsers->lastPage());
    }

    /** @test */
    public function it_can_use_query_builder_methods(): void
    {
        User::factory()->count(5)->create(['role' => UserRole::ADMIN]);
        User::factory()->count(3)->create(['role' => UserRole::TENANT]);

        $adminUsers = $this->userRepository
            ->where('role', UserRole::ADMIN)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        $this->assertInstanceOf(Collection::class, $adminUsers);
        $this->assertCount(3, $adminUsers);
        
        $adminUsers->each(function ($user) {
            $this->assertEquals(UserRole::ADMIN, $user->role);
        });
    }

    /** @test */
    public function it_can_eager_load_relationships(): void
    {
        $user = User::factory()->create();

        $userWithRelations = $this->userRepository
            ->with(['property', 'subscription'])
            ->find($user->id);

        $this->assertInstanceOf(User::class, $userWithRelations);
        // Check that relationships are loaded (no additional queries)
        $this->assertTrue($userWithRelations->relationLoaded('property'));
        $this->assertTrue($userWithRelations->relationLoaded('subscription'));
    }
}
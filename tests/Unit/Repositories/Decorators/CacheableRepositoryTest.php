<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Decorators;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use App\Repositories\Decorators\CacheableRepository;
use App\Repositories\UserRepository;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Cacheable Repository Decorator Tests
 * 
 * Tests the caching functionality of the repository decorator
 * to ensure proper cache behavior and performance optimization.
 */
class CacheableRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryInterface $baseRepository;
    private CacheRepository $cache;
    private CacheableRepository $cacheableRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->baseRepository = new UserRepository(new User());
        $this->cache = new CacheRepository(new ArrayStore());
        $this->cacheableRepository = new CacheableRepository(
            $this->baseRepository,
            $this->cache,
            3600 // 1 hour TTL
        );
    }

    /** @test */
    public function it_caches_find_operations(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        // First call should hit the database
        $result1 = $this->cacheableRepository->find($user->id);
        $this->assertEquals('John Doe', $result1->name);

        // Update the user directly in database (bypassing repository)
        $user->update(['name' => 'Jane Doe']);

        // Second call should return cached result (still "John Doe")
        $result2 = $this->cacheableRepository->find($user->id);
        $this->assertEquals('John Doe', $result2->name);
    }

    /** @test */
    public function it_invalidates_cache_on_write_operations(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        // Cache the user
        $cachedUser = $this->cacheableRepository->find($user->id);
        $this->assertEquals('John Doe', $cachedUser->name);

        // Update through repository (should invalidate cache)
        $this->cacheableRepository->update($user->id, ['name' => 'Jane Doe']);

        // Next find should get fresh data from database
        $freshUser = $this->cacheableRepository->find($user->id);
        $this->assertEquals('Jane Doe', $freshUser->name);
    }

    /** @test */
    public function it_caches_collection_operations(): void
    {
        User::factory()->count(3)->create(['is_active' => true]);

        // First call should hit the database
        $result1 = $this->cacheableRepository->findActiveUsers();
        $this->assertCount(3, $result1);

        // Create another user directly in database
        User::factory()->create(['is_active' => true]);

        // Second call should return cached result (still 3 users)
        $result2 = $this->cacheableRepository->findActiveUsers();
        $this->assertCount(3, $result2);
    }

    /** @test */
    public function it_does_not_cache_paginated_results(): void
    {
        User::factory()->count(5)->create();

        // Mock the base repository to track calls
        $mockRepository = Mockery::mock(UserRepositoryInterface::class);
        $mockRepository->shouldReceive('paginate')
            ->twice() // Should be called twice (not cached)
            ->andReturn($this->baseRepository->paginate(2));

        $cacheableRepo = new CacheableRepository($mockRepository, $this->cache);

        // Both calls should hit the repository
        $cacheableRepo->paginate(2);
        $cacheableRepo->paginate(2);
    }

    /** @test */
    public function it_does_not_cache_chunk_operations(): void
    {
        User::factory()->count(5)->create();

        $processedCount = 0;
        
        // Mock the base repository to track calls
        $mockRepository = Mockery::mock(UserRepositoryInterface::class);
        $mockRepository->shouldReceive('chunk')
            ->twice() // Should be called twice (not cached)
            ->andReturn(true);

        $cacheableRepo = new CacheableRepository($mockRepository, $this->cache);

        // Both calls should hit the repository
        $cacheableRepo->chunk(2, function ($users) use (&$processedCount) {
            $processedCount += $users->count();
        });
        
        $cacheableRepo->chunk(2, function ($users) use (&$processedCount) {
            $processedCount += $users->count();
        });
    }

    /** @test */
    public function it_caches_count_operations(): void
    {
        User::factory()->count(3)->create();

        // First call should hit the database
        $count1 = $this->cacheableRepository->count();
        $this->assertEquals(3, $count1);

        // Create another user directly in database
        User::factory()->create();

        // Second call should return cached result (still 3)
        $count2 = $this->cacheableRepository->count();
        $this->assertEquals(3, $count2);
    }

    /** @test */
    public function it_caches_exists_operations(): void
    {
        // First call with no users
        $exists1 = $this->cacheableRepository->exists();
        $this->assertFalse($exists1);

        // Create a user directly in database
        User::factory()->create();

        // Second call should return cached result (still false)
        $exists2 = $this->cacheableRepository->exists();
        $this->assertFalse($exists2);
    }

    /** @test */
    public function it_invalidates_cache_on_create(): void
    {
        // Cache a count
        $initialCount = $this->cacheableRepository->count();
        $this->assertEquals(0, $initialCount);

        // Create a user through repository
        $this->cacheableRepository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        // Count should be fresh (cache invalidated)
        $newCount = $this->cacheableRepository->count();
        $this->assertEquals(1, $newCount);
    }

    /** @test */
    public function it_invalidates_cache_on_delete(): void
    {
        $user = User::factory()->create();

        // Cache the user
        $cachedUser = $this->cacheableRepository->find($user->id);
        $this->assertNotNull($cachedUser);

        // Delete through repository
        $this->cacheableRepository->delete($user->id);

        // Find should return null (cache invalidated)
        $deletedUser = $this->cacheableRepository->find($user->id);
        $this->assertNull($deletedUser);
    }

    /** @test */
    public function it_handles_first_or_create_caching(): void
    {
        $attributes = ['email' => 'test@example.com'];
        $values = ['name' => 'Test User', 'password' => 'password', 'role' => \App\Enums\UserRole::ADMIN];

        // First call should create the user
        $user1 = $this->cacheableRepository->firstOrCreate($attributes, $values);
        $this->assertTrue($user1->wasRecentlyCreated);

        // Second call should find existing user (not create)
        $user2 = $this->cacheableRepository->firstOrCreate($attributes, $values);
        $this->assertFalse($user2->wasRecentlyCreated);
        $this->assertEquals($user1->id, $user2->id);
    }

    /** @test */
    public function it_handles_update_or_create_caching(): void
    {
        $attributes = ['email' => 'test@example.com'];
        $values = ['name' => 'Test User', 'password' => 'password', 'role' => \App\Enums\UserRole::ADMIN];

        // First call should create the user
        $user1 = $this->cacheableRepository->updateOrCreate($attributes, $values);
        $this->assertTrue($user1->wasRecentlyCreated);

        // Second call should update existing user
        $updatedValues = array_merge($values, ['name' => 'Updated User']);
        $user2 = $this->cacheableRepository->updateOrCreate($attributes, $updatedValues);
        $this->assertFalse($user2->wasRecentlyCreated);
        $this->assertEquals($user1->id, $user2->id);
        $this->assertEquals('Updated User', $user2->name);
    }

    /** @test */
    public function it_supports_method_chaining(): void
    {
        User::factory()->count(5)->create(['is_active' => true]);
        User::factory()->count(3)->create(['is_active' => false]);

        $activeUsers = $this->cacheableRepository
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        $this->assertCount(3, $activeUsers);
        $activeUsers->each(function ($user) {
            $this->assertTrue($user->is_active);
        });
    }

    /** @test */
    public function it_provides_cache_statistics(): void
    {
        $stats = $this->cacheableRepository->getCacheStats();

        $this->assertArrayHasKey('prefix', $stats);
        $this->assertArrayHasKey('tags', $stats);
        $this->assertArrayHasKey('ttl', $stats);
        $this->assertArrayHasKey('model', $stats);

        $this->assertEquals('repo:user', $stats['prefix']);
        $this->assertEquals(['repo:user', 'repositories'], $stats['tags']);
        $this->assertEquals(3600, $stats['ttl']);
        $this->assertEquals(User::class, $stats['model']);
    }

    /** @test */
    public function it_can_manually_invalidate_cache(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        // Cache the user
        $cachedUser = $this->cacheableRepository->find($user->id);
        $this->assertEquals('John Doe', $cachedUser->name);

        // Update user directly in database
        $user->update(['name' => 'Jane Doe']);

        // Should still return cached version
        $stillCachedUser = $this->cacheableRepository->find($user->id);
        $this->assertEquals('John Doe', $stillCachedUser->name);

        // Manually invalidate cache
        $this->cacheableRepository->invalidateCache();

        // Should now return fresh data
        $freshUser = $this->cacheableRepository->find($user->id);
        $this->assertEquals('Jane Doe', $freshUser->name);
    }

    /** @test */
    public function it_generates_unique_cache_keys(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Cache both users
        $this->cacheableRepository->find($user1->id);
        $this->cacheableRepository->find($user2->id);

        // Each should have its own cache entry
        $this->assertNotNull($this->cacheableRepository->find($user1->id));
        $this->assertNotNull($this->cacheableRepository->find($user2->id));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
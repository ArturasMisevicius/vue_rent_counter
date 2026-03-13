<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Contracts\InvoiceRepositoryInterface;
use App\Contracts\PropertyRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Enums\InvoiceStatus;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;
use App\Repositories\Criteria\ActiveUsers;
use App\Repositories\Criteria\DateRange;
use App\Repositories\Criteria\InvoicesByStatus;
use App\Repositories\Criteria\PropertiesByType;
use App\Repositories\Criteria\SearchTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Repository Integration Tests
 * 
 * Tests the complete repository pattern integration including:
 * - Service provider bindings
 * - Repository interactions
 * - Criteria pattern usage
 * - Cross-repository operations
 * - Transaction handling
 */
class RepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryInterface $userRepository;
    private PropertyRepositoryInterface $propertyRepository;
    private InvoiceRepositoryInterface $invoiceRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Resolve repositories from service container
        $this->userRepository = app(UserRepositoryInterface::class);
        $this->propertyRepository = app(PropertyRepositoryInterface::class);
        $this->invoiceRepository = app(InvoiceRepositoryInterface::class);
    }

    /** @test */
    public function repositories_are_properly_bound_in_service_container(): void
    {
        $this->assertInstanceOf(UserRepositoryInterface::class, $this->userRepository);
        $this->assertInstanceOf(PropertyRepositoryInterface::class, $this->propertyRepository);
        $this->assertInstanceOf(InvoiceRepositoryInterface::class, $this->invoiceRepository);
    }

    /** @test */
    public function repositories_are_singletons(): void
    {
        $userRepo1 = app(UserRepositoryInterface::class);
        $userRepo2 = app(UserRepositoryInterface::class);

        $this->assertSame($userRepo1, $userRepo2);
    }

    /** @test */
    public function active_users_criteria_works_correctly(): void
    {
        // Create test data
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->count(2)->create(['is_active' => false]);

        $criteria = new ActiveUsers();
        $query = User::query();
        $filteredQuery = $criteria->apply($query);
        $activeUsers = $filteredQuery->get();

        $this->assertCount(3, $activeUsers);
        $activeUsers->each(function ($user) {
            $this->assertTrue($user->is_active);
        });
    }

    /** @test */
    public function date_range_criteria_works_correctly(): void
    {
        $startDate = now()->subDays(7);
        $endDate = now()->subDays(1);

        // Create users within and outside the date range
        User::factory()->count(3)->create(['created_at' => now()->subDays(3)]);
        User::factory()->count(2)->create(['created_at' => now()->subDays(10)]);

        $criteria = DateRange::createdBetween($startDate, $endDate);
        $query = User::query();
        $filteredQuery = $criteria->apply($query);
        $users = $filteredQuery->get();

        $this->assertCount(3, $users);
    }

    /** @test */
    public function search_term_criteria_works_correctly(): void
    {
        User::factory()->create(['name' => 'John Smith', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        User::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $criteria = SearchTerm::forUsers('john');
        $query = User::query();
        $filteredQuery = $criteria->apply($query);
        $users = $filteredQuery->get();

        $this->assertCount(2, $users); // John Smith and Bob Johnson
    }

    /** @test */
    public function invoices_by_status_criteria_works_correctly(): void
    {
        Invoice::factory()->count(3)->create(['status' => InvoiceStatus::DRAFT]);
        Invoice::factory()->count(2)->create(['status' => InvoiceStatus::PAID]);
        Invoice::factory()->count(1)->create(['status' => InvoiceStatus::FINALIZED]);

        $criteria = InvoicesByStatus::drafts();
        $query = Invoice::query();
        $filteredQuery = $criteria->apply($query);
        $invoices = $filteredQuery->get();

        $this->assertCount(3, $invoices);
        $invoices->each(function ($invoice) {
            $this->assertEquals(InvoiceStatus::DRAFT, $invoice->status);
        });
    }

    /** @test */
    public function properties_by_type_criteria_works_correctly(): void
    {
        Property::factory()->count(3)->create(['type' => PropertyType::APARTMENT]);
        Property::factory()->count(2)->create(['type' => PropertyType::HOUSE]);
        Property::factory()->count(1)->create(['type' => PropertyType::OFFICE]);

        $criteria = PropertiesByType::residential();
        $query = Property::query();
        $filteredQuery = $criteria->apply($query);
        $properties = $filteredQuery->get();

        $this->assertCount(5, $properties); // 3 apartments + 2 houses
    }

    /** @test */
    public function multiple_criteria_can_be_combined(): void
    {
        // Create test data
        User::factory()->create([
            'name' => 'John Active',
            'email' => 'john@example.com',
            'is_active' => true,
            'created_at' => now()->subDays(3),
        ]);
        User::factory()->create([
            'name' => 'John Inactive',
            'email' => 'john.inactive@example.com',
            'is_active' => false,
            'created_at' => now()->subDays(3),
        ]);
        User::factory()->create([
            'name' => 'Jane Active',
            'email' => 'jane@example.com',
            'is_active' => true,
            'created_at' => now()->subDays(10),
        ]);

        $query = User::query();

        // Apply multiple criteria
        $activeCriteria = new ActiveUsers();
        $query = $activeCriteria->apply($query);

        $searchCriteria = SearchTerm::forUsers('john');
        $query = $searchCriteria->apply($query);

        $dateCriteria = DateRange::createdBetween(now()->subDays(7), now());
        $query = $dateCriteria->apply($query);

        $users = $query->get();

        $this->assertCount(1, $users); // Only "John Active" matches all criteria
        $this->assertEquals('John Active', $users->first()->name);
    }

    /** @test */
    public function repository_transactions_work_correctly(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ];

        // Test successful transaction
        $user = $this->userRepository->transaction(function () use ($userData) {
            return $this->userRepository->create($userData);
        });

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

        // Test transaction rollback on exception
        try {
            $this->userRepository->transaction(function () use ($userData) {
                $this->userRepository->create($userData);
                // This should cause a duplicate email error and rollback
                $this->userRepository->create($userData);
            });
        } catch (\Throwable $e) {
            // Expected exception
        }

        // Should still only have one user
        $this->assertEquals(1, User::where('email', 'test@example.com')->count());
    }

    /** @test */
    public function bulk_operations_work_correctly(): void
    {
        // Test bulk create
        $usersData = [
            [
                'name' => 'User 1',
                'email' => 'user1@example.com',
                'password' => 'password123',
                'role' => UserRole::ADMIN,
                'is_active' => true,
            ],
            [
                'name' => 'User 2',
                'email' => 'user2@example.com',
                'password' => 'password123',
                'role' => UserRole::TENANT,
                'is_active' => true,
            ],
        ];

        $users = $this->userRepository->bulkCreate($usersData);

        $this->assertCount(2, $users);
        $this->assertDatabaseHas('users', ['email' => 'user1@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'user2@example.com']);

        // Test bulk update
        $userIds = $users->pluck('id')->toArray();
        $updatedCount = $this->userRepository->bulkUpdate($userIds, ['is_active' => false]);

        $this->assertEquals(2, $updatedCount);
        $this->assertEquals(0, User::whereIn('id', $userIds)->where('is_active', true)->count());

        // Test bulk delete
        $deletedCount = $this->userRepository->bulkDelete($userIds);

        $this->assertEquals(2, $deletedCount);
        $this->assertEquals(0, User::whereIn('id', $userIds)->count());
    }

    /** @test */
    public function repository_error_handling_works_correctly(): void
    {
        // Test finding non-existent user
        $user = $this->userRepository->find(999);
        $this->assertNull($user);

        // Test findOrFail with non-existent user
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->userRepository->findOrFail(999);
    }

    /** @test */
    public function repository_query_builder_methods_work(): void
    {
        User::factory()->count(10)->create(['role' => UserRole::ADMIN]);
        User::factory()->count(5)->create(['role' => UserRole::TENANT]);

        $adminUsers = $this->userRepository
            ->where('role', UserRole::ADMIN)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $this->assertCount(5, $adminUsers);
        $adminUsers->each(function ($user) {
            $this->assertEquals(UserRole::ADMIN, $user->role);
        });

        // Test fresh query
        $allUsers = $this->userRepository->fresh()->get();
        $this->assertCount(15, $allUsers);
    }

    /** @test */
    public function repository_relationships_work_correctly(): void
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();

        $userWithRelations = $this->userRepository
            ->with(['property'])
            ->find($user->id);

        $this->assertTrue($userWithRelations->relationLoaded('property'));
    }

    /** @test */
    public function cross_repository_operations_work(): void
    {
        // Create a user and property
        $user = $this->userRepository->create([
            'name' => 'Property Owner',
            'email' => 'owner@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $property = $this->propertyRepository->create([
            'address' => '123 Test Street',
            'type' => PropertyType::APARTMENT,
            'area_sqm' => 75.5,
        ]);

        // Verify both were created
        $this->assertDatabaseHas('users', ['email' => 'owner@example.com']);
        $this->assertDatabaseHas('properties', ['address' => '123 Test Street']);

        // Test repository stats
        $userStats = $this->userRepository->getUserStats();
        $propertyStats = $this->propertyRepository->getPropertyStats();

        $this->assertArrayHasKey('total_users', $userStats);
        $this->assertArrayHasKey('total_properties', $propertyStats);
        $this->assertEquals(1, $userStats['total_users']);
        $this->assertEquals(1, $propertyStats['total_properties']);
    }

    /** @test */
    public function repository_caching_and_performance_features_work(): void
    {
        // Create test data
        User::factory()->count(100)->create();

        // Test chunking
        $processedCount = 0;
        $result = $this->userRepository->chunk(25, function ($users) use (&$processedCount) {
            $processedCount += $users->count();
            return true;
        });

        $this->assertTrue($result);
        $this->assertEquals(100, $processedCount);

        // Test counting
        $totalUsers = $this->userRepository->count();
        $this->assertEquals(100, $totalUsers);

        // Test existence check
        $hasUsers = $this->userRepository->exists();
        $this->assertTrue($hasUsers);
    }
}
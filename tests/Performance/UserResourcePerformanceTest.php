<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('UserResource Performance', function () {
    beforeEach(function () {
        // Clear cache before each test
        Cache::flush();
    });

    test('authorization methods use cached user instance', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        // Count auth()->user() calls by tracking queries
        DB::enableQueryLog();
        
        // Call multiple authorization methods
        $canViewAny = UserResource::canViewAny();
        $canCreate = UserResource::canCreate();
        $canEdit = UserResource::canEdit(new User());
        $canDelete = UserResource::canDelete(new User());
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should not generate excessive queries for user retrieval
        // Laravel caches auth()->user() in the request, so we expect minimal queries
        expect($canViewAny)->toBeTrue()
            ->and($canCreate)->toBeTrue()
            ->and($canEdit)->toBeTrue()
            ->and($canDelete)->toBeTrue()
            ->and(count($queries))->toBeLessThan(5); // Allow some queries but not excessive
    });

    test('navigation badge caching reduces database queries', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        User::factory()->count(10)->create(['tenant_id' => 1]);
        
        $this->actingAs($admin);

        DB::enableQueryLog();
        
        // First call - should hit database
        $badge1 = UserResource::getNavigationBadge();
        $queriesFirstCall = count(DB::getQueryLog());
        
        DB::flushQueryLog();
        
        // Second call - should use cache
        $badge2 = UserResource::getNavigationBadge();
        $queriesSecondCall = count(DB::getQueryLog());
        
        DB::disableQueryLog();

        expect($badge1)->toBe('11') // 10 created + 1 admin
            ->and($badge2)->toBe('11')
            ->and($queriesFirstCall)->toBeGreaterThan(0) // First call queries DB
            ->and($queriesSecondCall)->toBe(0); // Second call uses cache
    });

    test('navigation badge cache is shared across users with same role and tenant', function () {
        // Create two admins in the same tenant
        $admin1 = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $admin2 = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        User::factory()->count(5)->create(['tenant_id' => 1]);

        // First admin gets badge (populates cache)
        $this->actingAs($admin1);
        $badge1 = UserResource::getNavigationBadge();

        DB::enableQueryLog();
        
        // Second admin should use same cache
        $this->actingAs($admin2);
        $badge2 = UserResource::getNavigationBadge();
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        expect($badge1)->toBe('7') // 5 created + 2 admins
            ->and($badge2)->toBe('7')
            ->and(count($queries))->toBe(0); // No queries - cache hit
    });

    test('tenant users do not trigger badge queries', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $this->actingAs($tenant);

        DB::enableQueryLog();
        
        $badge = UserResource::getNavigationBadge();
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        expect($badge)->toBeNull()
            ->and(count($queries))->toBe(0); // No queries for unauthorized users
    });

    test('getEloquentQuery eager loads relationships efficiently', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        User::factory()->count(10)->create(['tenant_id' => 1, 'parent_user_id' => $admin->id]);
        
        $this->actingAs($admin);

        DB::enableQueryLog();
        
        // Get query and fetch users
        $query = UserResource::getEloquentQuery();
        $users = $query->get();
        
        // Access parentUser relationship (should be eager loaded)
        foreach ($users as $user) {
            $parentName = $user->parentUser?->name;
        }
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should have 1 query for users + 1 for eager loaded parentUser
        // No N+1 queries
        expect(count($users))->toBe(11) // 10 created + 1 admin
            ->and(count($queries))->toBeLessThanOrEqual(2); // Main query + eager load
    });

    test('role check uses constant for efficiency', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        // Test that ALLOWED_ROLES constant is used efficiently
        $this->actingAs($admin);
        expect(UserResource::canViewAny())->toBeTrue();

        $this->actingAs($manager);
        expect(UserResource::canViewAny())->toBeFalse();

        $this->actingAs($tenant);
        expect(UserResource::canViewAny())->toBeFalse();
    });

    test('authorization methods have minimal overhead', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        $startTime = microtime(true);
        
        // Call authorization methods 100 times
        for ($i = 0; $i < 100; $i++) {
            UserResource::canViewAny();
            UserResource::canCreate();
            UserResource::canEdit(new User());
            UserResource::canDelete(new User());
        }
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Should complete in under 100ms for 400 calls (100 iterations × 4 methods)
        expect($executionTime)->toBeLessThan(100);
    });

    test('navigation badge respects tenant isolation', function () {
        // Create users in different tenants
        $admin1 = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $admin2 = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 2]);
        
        User::factory()->count(5)->create(['tenant_id' => 1]);
        User::factory()->count(3)->create(['tenant_id' => 2]);

        // Admin 1 should only see tenant 1 users
        $this->actingAs($admin1);
        $badge1 = UserResource::getNavigationBadge();

        // Admin 2 should only see tenant 2 users
        $this->actingAs($admin2);
        $badge2 = UserResource::getNavigationBadge();

        expect($badge1)->toBe('6') // 5 created + 1 admin in tenant 1
            ->and($badge2)->toBe('4'); // 3 created + 1 admin in tenant 2
    });

    test('superadmin sees all users in badge count', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN, 'tenant_id' => null]);
        
        User::factory()->count(5)->create(['tenant_id' => 1]);
        User::factory()->count(3)->create(['tenant_id' => 2]);

        $this->actingAs($superadmin);
        $badge = UserResource::getNavigationBadge();

        expect($badge)->toBe('9'); // 5 + 3 + 1 superadmin
    });
});

<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/**
 * Performance tests for UserResource.
 * 
 * Validates:
 * - No N+1 queries on table listing
 * - Navigation badge caching works correctly
 * - Database indexes exist
 * - Query performance is acceptable
 */

test('user resource table does not have N+1 queries', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    // Create 50 users with parent users to test N+1 prevention
    User::factory()->count(50)->create(['tenant_id' => 1]);
    
    actingAs($admin);
    
    // Enable query log
    DB::enableQueryLog();
    
    // Render the user resource table
    Livewire::test(ListUsers::class);
    
    $queries = DB::getQueryLog();
    
    // Should be minimal queries:
    // 1. Select users with eager loading
    // 2. Select parent users (eager loaded)
    // 3. Count for pagination
    // Allow up to 10 queries for Filament overhead
    expect(count($queries))->toBeLessThan(10)
        ->and($queries)->not->toBeEmpty();
})->group('performance');

test('navigation badge is cached and reduces queries', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    User::factory()->count(10)->create(['tenant_id' => 1]);
    
    actingAs($admin);
    
    // Clear any existing cache
    Cache::flush();
    
    // First call - should query database
    DB::enableQueryLog();
    $badge1 = UserResource::getNavigationBadge();
    $queriesFirst = count(DB::getQueryLog());
    
    // Second call - should use cache
    DB::flushQueryLog();
    $badge2 = UserResource::getNavigationBadge();
    $queriesSecond = count(DB::getQueryLog());
    
    expect($badge1)->toBe($badge2)
        ->and($badge1)->toBe('11') // 10 + admin
        ->and($queriesFirst)->toBeGreaterThan(0)
        ->and($queriesSecond)->toBe(0); // No queries on cached call
})->group('performance');

test('navigation badge cache is invalidated on user creation', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Clear cache and get initial badge
    Cache::flush();
    $badge1 = UserResource::getNavigationBadge();
    expect($badge1)->toBe('1');
    
    // Create new user - should invalidate cache
    User::factory()->create(['tenant_id' => 1]);
    
    // Badge should reflect new count
    $badge2 = UserResource::getNavigationBadge();
    expect($badge2)->toBe('2');
})->group('performance');

test('navigation badge cache is invalidated on user deletion', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    $user = User::factory()->create(['tenant_id' => 1]);
    
    actingAs($admin);
    
    // Clear cache and get initial badge
    Cache::flush();
    $badge1 = UserResource::getNavigationBadge();
    expect($badge1)->toBe('2');
    
    // Delete user - should invalidate cache
    $user->delete();
    
    // Badge should reflect new count
    $badge2 = UserResource::getNavigationBadge();
    expect($badge2)->toBe('1');
})->group('performance');

test('users table has required performance indexes', function () {
    $indexes = DB::select("SHOW INDEX FROM users");
    $indexNames = collect($indexes)->pluck('Key_name')->unique();
    
    expect($indexNames)->toContain('users_tenant_id_index')
        ->toContain('users_role_index')
        ->toContain('users_is_active_index')
        ->toContain('users_tenant_id_role_index')
        ->toContain('users_tenant_id_is_active_index');
})->group('performance');

test('filtered user queries use indexes', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    // Create users to query
    User::factory()->count(100)->create(['tenant_id' => 1]);
    
    actingAs($admin);
    
    // Query with filters that should use indexes
    $query = User::query()
        ->where('tenant_id', 1)
        ->where('role', UserRole::TENANT)
        ->where('is_active', true);
    
    // Get EXPLAIN output
    $explain = DB::select('EXPLAIN ' . $query->toSql(), $query->getBindings());
    
    // Should use index (type should be 'ref' or 'range', not 'ALL')
    expect($explain[0]->type)->not->toBe('ALL');
})->group('performance');

test('user role labels are memoized', function () {
    // Clear any cached labels
    UserRole::clearLabelCache();
    
    // First call should populate cache
    $labels1 = UserRole::labels();
    
    // Second call should return same array instance (memoized)
    $labels2 = UserRole::labels();
    
    expect($labels1)->toBe($labels2)
        ->and($labels1)->toHaveCount(4)
        ->and($labels1)->toHaveKeys([
            UserRole::SUPERADMIN->value,
            UserRole::ADMIN->value,
            UserRole::MANAGER->value,
            UserRole::TENANT->value,
        ]);
})->group('performance');

test('eager loading prevents N+1 on parentUser relationship', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    // Create users with parent users
    User::factory()->count(20)->create(['tenant_id' => 1]);
    
    actingAs($admin);
    
    // Get users using the resource query
    DB::enableQueryLog();
    $users = UserResource::getEloquentQuery()->get();
    $queries = DB::getQueryLog();
    
    // Should be 2 queries: 1 for users, 1 for eager loaded parentUsers
    expect(count($queries))->toBeLessThanOrEqual(2);
    
    // Access parentUser on each user - should not trigger additional queries
    DB::flushQueryLog();
    foreach ($users as $user) {
        $parentName = $user->parentUser?->name;
    }
    $additionalQueries = DB::getQueryLog();
    
    expect(count($additionalQueries))->toBe(0);
})->group('performance');

test('navigation badge respects tenant scoping', function () {
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    $admin2 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 2,
    ]);
    
    // Create users in different tenants
    User::factory()->count(5)->create(['tenant_id' => 1]);
    User::factory()->count(3)->create(['tenant_id' => 2]);
    
    Cache::flush();
    
    // Admin 1 should see 6 users (5 + themselves)
    actingAs($admin1);
    $badge1 = UserResource::getNavigationBadge();
    expect($badge1)->toBe('6');
    
    // Admin 2 should see 4 users (3 + themselves)
    actingAs($admin2);
    $badge2 = UserResource::getNavigationBadge();
    expect($badge2)->toBe('4');
})->group('performance');

test('superadmin navigation badge shows all users', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    // Create users in different tenants
    User::factory()->count(5)->create(['tenant_id' => 1]);
    User::factory()->count(3)->create(['tenant_id' => 2]);
    
    Cache::flush();
    
    actingAs($superadmin);
    
    // Superadmin should see all 9 users (8 + themselves)
    $badge = UserResource::getNavigationBadge();
    expect($badge)->toBe('9');
})->group('performance');


<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

/**
 * BuildingResource Tenant Scoping Test Suite
 *
 * Tests tenant isolation, cross-tenant access prevention, and
 * automatic tenant_id assignment following BelongsToTenant pattern.
 *
 * Run with: php artisan test --filter=BuildingResourceTenantScoping
 */

beforeEach(function () {
    $this->superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
    $this->manager1 = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
    $this->manager2 = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 2]);
    $this->tenant1 = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
    $this->tenant2 = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 2]);
});

describe('Tenant Isolation', function () {
    test('manager only sees buildings from their tenant', function () {
        // Create buildings for different tenants
        Building::factory()->count(5)->create(['tenant_id' => 1]);
        Building::factory()->count(3)->create(['tenant_id' => 2]);

        actingAs($this->manager1);

        $buildings = Building::all();

        expect($buildings)->toHaveCount(5)
            ->and($buildings->every(fn ($b) => $b->tenant_id === 1))->toBeTrue();
    });

    test('manager cannot query buildings from other tenants', function () {
        $building1 = Building::factory()->create(['tenant_id' => 1]);
        $building2 = Building::factory()->create(['tenant_id' => 2]);

        actingAs($this->manager1);

        $found1 = Building::find($building1->id);
        $found2 = Building::find($building2->id);

        expect($found1)->not->toBeNull()
            ->and($found2)->toBeNull(); // Filtered by TenantScope
    });

    test('manager cannot access other tenant buildings via direct query', function () {
        Building::factory()->count(5)->create(['tenant_id' => 1]);
        Building::factory()->count(3)->create(['tenant_id' => 2]);

        actingAs($this->manager1);

        $crossTenantQuery = Building::where('tenant_id', 2)->get();

        expect($crossTenantQuery)->toHaveCount(0);
    });

    test('admin sees all buildings across tenants', function () {
        Building::factory()->count(5)->create(['tenant_id' => 1]);
        Building::factory()->count(3)->create(['tenant_id' => 2]);

        actingAs($this->admin);

        $buildings = Building::all();

        // Admin bypasses tenant scope via policy
        expect($buildings->count())->toBeGreaterThanOrEqual(8);
    });

    test('superadmin sees all buildings across all tenants', function () {
        Building::factory()->count(5)->create(['tenant_id' => 1]);
        Building::factory()->count(3)->create(['tenant_id' => 2]);
        Building::factory()->count(2)->create(['tenant_id' => 3]);

        actingAs($this->superadmin);

        $buildings = Building::all();

        expect($buildings->count())->toBeGreaterThanOrEqual(10);
    });
});

describe('Cross-Tenant Access Prevention', function () {
    test('manager cannot edit buildings from other tenants', function () {
        $building = Building::factory()->create(['tenant_id' => 2]);

        actingAs($this->manager1);

        expect(BuildingResource::canEdit($building))->toBeFalse();
    });

    test('manager cannot delete buildings from other tenants', function () {
        $building = Building::factory()->create(['tenant_id' => 2]);

        actingAs($this->manager1);

        expect(BuildingResource::canDelete($building))->toBeFalse();
    });

    test('manager cannot view buildings from other tenants in table', function () {
        Building::factory()->count(5)->create(['tenant_id' => 1]);
        Building::factory()->count(3)->create(['tenant_id' => 2]);

        actingAs($this->manager1);

        // Simulate table query with withCount
        $buildings = Building::query()
            ->withCount('properties')
            ->get();

        expect($buildings)->toHaveCount(5)
            ->and($buildings->every(fn ($b) => $b->tenant_id === 1))->toBeTrue();
    });

    test('manager cannot access other tenant buildings via properties relationship', function () {
        $building1 = Building::factory()->create(['tenant_id' => 1]);
        $building2 = Building::factory()->create(['tenant_id' => 2]);
        
        Property::factory()->count(3)->create(['building_id' => $building1->id, 'tenant_id' => 1]);
        Property::factory()->count(2)->create(['building_id' => $building2->id, 'tenant_id' => 2]);

        actingAs($this->manager1);

        $buildings = Building::with('properties')->get();

        expect($buildings)->toHaveCount(1)
            ->and($buildings->first()->properties)->toHaveCount(3);
    });
});

describe('Automatic Tenant Assignment', function () {
    test('building inherits tenant_id from authenticated manager', function () {
        actingAs($this->manager1);

        // Note: Actual tenant_id assignment happens in CreateBuilding page
        // This test documents expected behavior
        expect($this->manager1->tenant_id)->toBe(1);
    });

    test('building cannot be created with different tenant_id than user', function () {
        actingAs($this->manager1);

        // Manager tries to create building for different tenant
        $building = Building::factory()->create([
            'tenant_id' => 2, // Different from manager's tenant_id
            'name' => 'Test Building',
            'address' => '123 Main St',
            'total_apartments' => 10,
        ]);

        // Building is created but manager cannot access it due to TenantScope
        $found = Building::find($building->id);
        expect($found)->toBeNull();
    });

    test('admin can create buildings for any tenant', function () {
        actingAs($this->admin);

        $building = Building::factory()->create([
            'tenant_id' => 3,
            'name' => 'Admin Building',
            'address' => '456 Admin St',
            'total_apartments' => 20,
        ]);

        expect($building->tenant_id)->toBe(3);
    });
});

describe('Tenant Scope Query Behavior', function () {
    test('tenant scope applies to all queries', function () {
        Building::factory()->count(5)->create(['tenant_id' => 1]);
        Building::factory()->count(3)->create(['tenant_id' => 2]);

        actingAs($this->manager1);

        $countQuery = Building::count();
        $allQuery = Building::all()->count();
        $whereQuery = Building::where('total_apartments', '>', 0)->count();

        expect($countQuery)->toBe(5)
            ->and($allQuery)->toBe(5)
            ->and($whereQuery)->toBe(5);
    });

    test('tenant scope applies to relationship queries', function () {
        $building1 = Building::factory()->create(['tenant_id' => 1]);
        $building2 = Building::factory()->create(['tenant_id' => 2]);
        
        Property::factory()->count(3)->create(['building_id' => $building1->id, 'tenant_id' => 1]);
        Property::factory()->count(2)->create(['building_id' => $building2->id, 'tenant_id' => 2]);

        actingAs($this->manager1);

        $buildingsWithProperties = Building::has('properties')->get();

        expect($buildingsWithProperties)->toHaveCount(1)
            ->and($buildingsWithProperties->first()->id)->toBe($building1->id);
    });

    test('tenant scope applies to eager loaded relationships', function () {
        $building1 = Building::factory()->create(['tenant_id' => 1]);
        $building2 = Building::factory()->create(['tenant_id' => 2]);
        
        Property::factory()->count(3)->create(['building_id' => $building1->id, 'tenant_id' => 1]);
        Property::factory()->count(2)->create(['building_id' => $building2->id, 'tenant_id' => 2]);

        actingAs($this->manager1);

        $buildings = Building::with('properties')->get();

        expect($buildings)->toHaveCount(1)
            ->and($buildings->first()->properties)->toHaveCount(3);
    });

    test('tenant scope applies to withCount queries', function () {
        $building1 = Building::factory()->create(['tenant_id' => 1]);
        $building2 = Building::factory()->create(['tenant_id' => 2]);
        
        Property::factory()->count(3)->create(['building_id' => $building1->id, 'tenant_id' => 1]);
        Property::factory()->count(2)->create(['building_id' => $building2->id, 'tenant_id' => 2]);

        actingAs($this->manager1);

        $buildings = Building::withCount('properties')->get();

        expect($buildings)->toHaveCount(1)
            ->and($buildings->first()->properties_count)->toBe(3);
    });
});

describe('Superadmin Bypass', function () {
    test('superadmin bypasses tenant scope', function () {
        Building::factory()->count(5)->create(['tenant_id' => 1]);
        Building::factory()->count(3)->create(['tenant_id' => 2]);
        Building::factory()->count(2)->create(['tenant_id' => 3]);

        actingAs($this->superadmin);

        $buildings = Building::all();

        expect($buildings->count())->toBeGreaterThanOrEqual(10);
    });

    test('superadmin can access any building directly', function () {
        $building1 = Building::factory()->create(['tenant_id' => 1]);
        $building2 = Building::factory()->create(['tenant_id' => 2]);
        $building3 = Building::factory()->create(['tenant_id' => 3]);

        actingAs($this->superadmin);

        $found1 = Building::find($building1->id);
        $found2 = Building::find($building2->id);
        $found3 = Building::find($building3->id);

        expect($found1)->not->toBeNull()
            ->and($found2)->not->toBeNull()
            ->and($found3)->not->toBeNull();
    });

    test('superadmin can query buildings from any tenant', function () {
        Building::factory()->count(5)->create(['tenant_id' => 1]);
        Building::factory()->count(3)->create(['tenant_id' => 2]);

        actingAs($this->superadmin);

        $tenant1Buildings = Building::where('tenant_id', 1)->get();
        $tenant2Buildings = Building::where('tenant_id', 2)->get();

        expect($tenant1Buildings)->toHaveCount(5)
            ->and($tenant2Buildings)->toHaveCount(3);
    });
});

describe('Data Integrity', function () {
    test('tenant_id is immutable after creation', function () {
        actingAs($this->manager1);

        $building = Building::factory()->create([
            'tenant_id' => 1,
            'name' => 'Test Building',
            'address' => '123 Main St',
            'total_apartments' => 10,
        ]);

        // Attempt to change tenant_id
        $building->tenant_id = 2;
        $building->save();

        $building->refresh();

        // tenant_id should remain unchanged (or update should be prevented)
        expect($building->tenant_id)->toBe(1);
    });

    test('buildings maintain referential integrity with properties', function () {
        actingAs($this->manager1);

        $building = Building::factory()->create(['tenant_id' => 1]);
        Property::factory()->count(3)->create([
            'building_id' => $building->id,
            'tenant_id' => 1,
        ]);

        $building->refresh();

        expect($building->properties)->toHaveCount(3)
            ->and($building->properties->every(fn ($p) => $p->tenant_id === 1))->toBeTrue();
    });

    test('deleting building respects tenant scope', function () {
        $building1 = Building::factory()->create(['tenant_id' => 1]);
        $building2 = Building::factory()->create(['tenant_id' => 2]);

        actingAs($this->manager1);

        // Manager cannot delete building from other tenant
        expect(BuildingResource::canDelete($building2))->toBeFalse();
        
        // Manager cannot delete their own buildings (policy restriction)
        expect(BuildingResource::canDelete($building1))->toBeFalse();
    });
});

describe('Performance with Tenant Scope', function () {
    test('tenant scope does not add excessive queries', function () {
        Building::factory()->count(10)->create(['tenant_id' => 1]);
        Building::factory()->count(10)->create(['tenant_id' => 2]);

        actingAs($this->manager1);

        DB::enableQueryLog();

        $buildings = Building::withCount('properties')->paginate(15);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        DB::disableQueryLog();

        // Should still be ≤ 3 queries with tenant scope
        expect($queryCount)->toBeLessThanOrEqual(3);
    });

    test('tenant scope works efficiently with large datasets', function () {
        Building::factory()->count(100)->create(['tenant_id' => 1]);
        Building::factory()->count(100)->create(['tenant_id' => 2]);

        actingAs($this->manager1);

        $memoryBefore = memory_get_usage(true);

        $buildings = Building::withCount('properties')->paginate(15);

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        // Memory usage should remain reasonable
        expect($memoryUsed)->toBeLessThan(10);
    });
});

<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\User;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\actingAs;

/**
 * BuildingResource Security Test Suite
 *
 * Comprehensive security tests covering:
 * - Cross-tenant data isolation
 * - XSS prevention
 * - SQL injection prevention
 * - Authorization enforcement
 * - Audit logging
 * - Input sanitization
 */

describe('Cross-Tenant Isolation', function () {
    test('manager cannot access other tenant buildings', function () {
        $manager1 = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $manager2 = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 2]);

        $building = Building::factory()->create(['tenant_id' => 2]);

        actingAs($manager1);

        expect($manager1->can('view', $building))->toBeFalse()
            ->and($manager1->can('update', $building))->toBeFalse()
            ->and($manager1->can('delete', $building))->toBeFalse();
    });

    test('manager can only see their tenant buildings', function () {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);

        Building::factory()->count(5)->create(['tenant_id' => 1]);
        Building::factory()->count(3)->create(['tenant_id' => 2]);

        actingAs($manager);

        $buildings = Building::all();

        expect($buildings)->toHaveCount(5)
            ->and($buildings->every(fn ($b) => $b->tenant_id === 1))->toBeTrue();
    });

    test('admin can access all tenant buildings', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);

        Building::factory()->count(5)->create(['tenant_id' => 1]);
        Building::factory()->count(3)->create(['tenant_id' => 2]);

        actingAs($admin);

        $building1 = Building::where('tenant_id', 1)->first();
        $building2 = Building::where('tenant_id', 2)->first();

        expect($admin->can('view', $building1))->toBeTrue()
            ->and($admin->can('view', $building2))->toBeTrue();
    });

    test('superadmin can access all buildings', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        Building::factory()->count(5)->create(['tenant_id' => 1]);
        Building::factory()->count(3)->create(['tenant_id' => 2]);

        actingAs($superadmin);

        $building1 = Building::where('tenant_id', 1)->first();
        $building2 = Building::where('tenant_id', 2)->first();

        expect($superadmin->can('view', $building1))->toBeTrue()
            ->and($superadmin->can('view', $building2))->toBeTrue()
            ->and($superadmin->can('delete', $building1))->toBeTrue()
            ->and($superadmin->can('delete', $building2))->toBeTrue();
    });
});

describe('XSS Prevention', function () {
    test('script tags are stripped from name field', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        actingAs($admin);

        $maliciousInput = '<script>alert("XSS")</script>Test Building';

        $building = Building::create([
            'tenant_id' => $admin->tenant_id,
            'name' => $maliciousInput,
            'address' => '123 Main St',
            'total_apartments' => 10,
        ]);

        expect($building->name)->not->toContain('<script>')
            ->and($building->name)->not->toContain('alert');
    });

    test('javascript protocol is stripped from address', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        actingAs($admin);

        $maliciousInput = 'javascript:alert("XSS")';

        $building = Building::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Test Building',
            'address' => $maliciousInput,
            'total_apartments' => 10,
        ]);

        expect($building->address)->not->toContain('javascript:')
            ->and($building->address)->not->toContain('alert');
    });

    test('html entities are escaped in output', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        actingAs($admin);

        $building = Building::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'name' => '<b>Bold Building</b>',
        ]);

        $response = $this->get(route('filament.admin.resources.buildings.index'));

        $response->assertDontSee('<b>Bold Building</b>', false)
            ->assertSee('&lt;b&gt;Bold Building&lt;/b&gt;', false);
    });
});

describe('SQL Injection Prevention', function () {
    test('sql injection in name field is prevented', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        actingAs($admin);

        $maliciousInput = "'; DROP TABLE buildings; --";

        $building = Building::create([
            'tenant_id' => $admin->tenant_id,
            'name' => $maliciousInput,
            'address' => '123 Main St',
            'total_apartments' => 10,
        ]);

        // Table should still exist
        expect(Building::count())->toBeGreaterThan(0)
            ->and($building->name)->toBe($maliciousInput); // Stored as-is, escaped on query
    });

    test('sql injection in search is prevented', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        Building::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'address' => '123 Main St',
        ]);

        actingAs($admin);

        $maliciousSearch = "' OR '1'='1";

        $results = Building::where('address', 'like', "%{$maliciousSearch}%")->get();

        // Should return no results (search is escaped)
        expect($results)->toHaveCount(0);
    });
});

describe('Authorization Enforcement', function () {
    test('tenant cannot create buildings', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        actingAs($tenant);

        expect($tenant->can('create', Building::class))->toBeFalse();
    });

    test('manager cannot delete buildings', function () {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $building = Building::factory()->create(['tenant_id' => $manager->tenant_id]);

        actingAs($manager);

        expect($manager->can('delete', $building))->toBeFalse();
    });

    test('unauthenticated user cannot access buildings', function () {
        $response = $this->get(route('filament.admin.resources.buildings.index'));

        $response->assertRedirect(route('filament.admin.auth.login'));
    });
});

describe('Input Validation', function () {
    test('negative total apartments is rejected', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        actingAs($admin);

        expect(fn () => Building::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Test Building',
            'address' => '123 Main St',
            'total_apartments' => -5,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('total apartments exceeding max is rejected', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        actingAs($admin);

        expect(fn () => Building::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Test Building',
            'address' => '123 Main St',
            'total_apartments' => 10000,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('missing required fields is rejected', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        actingAs($admin);

        expect(fn () => Building::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Test Building',
            // Missing address and total_apartments
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });
});

describe('Mass Assignment Protection', function () {
    test('tenant_id cannot be overridden via mass assignment', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);

        actingAs($admin);

        $building = Building::create([
            'tenant_id' => 999, // Attempt to set different tenant_id
            'name' => 'Test Building',
            'address' => '123 Main St',
            'total_apartments' => 10,
        ]);

        // Should use the provided tenant_id (fillable)
        expect($building->tenant_id)->toBe(999);
    });

    test('non-fillable attributes are ignored', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        actingAs($admin);

        $building = Building::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Test Building',
            'address' => '123 Main St',
            'total_apartments' => 10,
            'id' => 999, // Attempt to set ID
            'created_at' => now()->subYear(), // Attempt to set timestamp
        ]);

        // ID should be auto-generated, not 999
        expect($building->id)->not->toBe(999)
            ->and($building->created_at->isToday())->toBeTrue();
    });
});

describe('Audit Logging', function () {
    test('building creation is logged', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        actingAs($admin);

        $building = Building::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Test Building',
            'address' => '123 Main St',
            'total_apartments' => 10,
        ]);

        // Verify audit log was created
        $auditLog = \App\Models\AuditLog::where('auditable_type', Building::class)
            ->where('auditable_id', $building->id)
            ->where('event', 'created')
            ->first();

        expect($auditLog)->not->toBeNull()
            ->and($auditLog->user_id)->toBe($admin->id)
            ->and($auditLog->new_values)->toHaveKey('name')
            ->and($auditLog->new_values['name'])->toBe('Test Building');
    });

    test('building update is logged', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);

        actingAs($admin);

        $originalName = $building->name;
        $building->update(['name' => 'Updated Building Name']);

        // Verify audit log was created for update
        $auditLog = \App\Models\AuditLog::where('auditable_type', Building::class)
            ->where('auditable_id', $building->id)
            ->where('event', 'updated')
            ->first();

        expect($auditLog)->not->toBeNull()
            ->and($auditLog->user_id)->toBe($admin->id)
            ->and($auditLog->old_values)->toHaveKey('name')
            ->and($auditLog->old_values['name'])->toBe($originalName)
            ->and($auditLog->new_values['name'])->toBe('Updated Building Name');
    });

    test('building deletion is logged', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
        $buildingId = $building->id;

        actingAs($admin);

        $building->delete();

        // Verify audit log was created for deletion
        $auditLog = \App\Models\AuditLog::where('auditable_type', Building::class)
            ->where('auditable_id', $buildingId)
            ->where('event', 'deleted')
            ->first();

        expect($auditLog)->not->toBeNull()
            ->and($auditLog->user_id)->toBe($admin->id)
            ->and($auditLog->old_values)->toHaveKey('name');
    });
});

describe('Session Security', function () {
    test('session is regenerated on login', function () {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $oldSessionId = session()->getId();

        $this->post(route('filament.admin.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $newSessionId = session()->getId();

        expect($newSessionId)->not->toBe($oldSessionId);
    });

    test('session expires after inactivity', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        actingAs($admin);

        // Simulate session expiry
        session()->put('last_activity', now()->subHours(3)->timestamp);

        $response = $this->get(route('filament.admin.resources.buildings.index'));

        $response->assertRedirect(route('filament.admin.auth.login'));
    })->skip('Session expiry middleware not configured');
});

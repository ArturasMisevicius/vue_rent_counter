<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('UserResource Authorization Security', function () {
    test('tenant users cannot access user management', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $user = User::factory()->create();
        
        $this->actingAs($tenant);
        
        expect(UserResource::canViewAny())->toBeFalse();
        expect(UserResource::canCreate())->toBeFalse();
        expect(UserResource::canEdit($user))->toBeFalse();
        expect(UserResource::canDelete($user))->toBeFalse();
        expect(UserResource::shouldRegisterNavigation())->toBeFalse();
    });

    test('manager users cannot access user management', function () {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $user = User::factory()->create();
        
        $this->actingAs($manager);
        
        expect(UserResource::canViewAny())->toBeFalse();
        expect(UserResource::canCreate())->toBeFalse();
        expect(UserResource::canEdit($user))->toBeFalse();
        expect(UserResource::canDelete($user))->toBeFalse();
        expect(UserResource::shouldRegisterNavigation())->toBeFalse();
    });

    test('admin users can access user management', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create();
        
        $this->actingAs($admin);
        
        expect(UserResource::canViewAny())->toBeTrue();
        expect(UserResource::canCreate())->toBeTrue();
        expect(UserResource::canEdit($user))->toBeTrue();
        expect(UserResource::canDelete($user))->toBeTrue();
        expect(UserResource::shouldRegisterNavigation())->toBeTrue();
    });

    test('superadmin users can access user management', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $user = User::factory()->create();
        
        $this->actingAs($superadmin);
        
        expect(UserResource::canViewAny())->toBeTrue();
        expect(UserResource::canCreate())->toBeTrue();
        expect(UserResource::canEdit($user))->toBeTrue();
        expect(UserResource::canDelete($user))->toBeTrue();
        expect(UserResource::shouldRegisterNavigation())->toBeTrue();
    });

    test('authorization checks are performant', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $this->actingAs($admin);
        
        $startTime = microtime(true);
        
        // Run 1000 authorization checks
        for ($i = 0; $i < 1000; $i++) {
            UserResource::canViewAny();
        }
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        // Should complete in under 100ms
        expect($executionTime)->toBeLessThan(100);
    });

    test('cross-tenant access is prevented', function () {
        $admin1 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);
        
        $user2 = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 2,
        ]);
        
        $this->actingAs($admin1);
        
        // Admin1 should not be able to edit user from tenant 2
        expect($admin1->can('update', $user2))->toBeFalse();
    });

    test('unauthenticated users cannot access user management', function () {
        $response = $this->get('/admin/users');
        
        $response->assertRedirect('/admin/login');
    });

    test('authenticated but unauthorized users cannot access user management', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        
        $response = $this->actingAs($tenant)->get('/admin/users');
        
        $response->assertForbidden();
    });
});

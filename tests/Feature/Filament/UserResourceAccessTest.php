<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UserResource HTTP Access', function () {
    test('superadmin can access user resource index', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $response = $this->actingAs($superadmin)
            ->get(UserResource::getUrl('index'));
        
        $response->assertSuccessful();
    });

    test('admin can access user resource index', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Subscription::factory()->active()->create(['user_id' => $admin->id]);
        
        $response = $this->actingAs($admin)
            ->get(UserResource::getUrl('index'));
        
        $response->assertSuccessful();
    });

    test('manager cannot access user resource index', function () {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        
        $response = $this->actingAs($manager)
            ->get(UserResource::getUrl('index'));
        
        $response->assertForbidden();
    });

    test('tenant cannot access user resource index', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        
        $response = $this->actingAs($tenant)
            ->get(UserResource::getUrl('index'));
        
        $response->assertForbidden();
    });

    test('unauthenticated user redirected to login', function () {
        $response = $this->get(UserResource::getUrl('index'));
        
        $response->assertRedirect('/admin/login');
    });
});

describe('UserResource Create Access', function () {
    test('superadmin can access create page', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $response = $this->actingAs($superadmin)
            ->get(UserResource::getUrl('create'));
        
        $response->assertSuccessful();
    });

    test('admin can access create page', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Subscription::factory()->active()->create(['user_id' => $admin->id]);
        
        $response = $this->actingAs($admin)
            ->get(UserResource::getUrl('create'));
        
        $response->assertSuccessful();
    });

    test('manager cannot access create page', function () {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        
        $response = $this->actingAs($manager)
            ->get(UserResource::getUrl('create'));
        
        $response->assertForbidden();
    });

    test('tenant cannot access create page', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        
        $response = $this->actingAs($tenant)
            ->get(UserResource::getUrl('create'));
        
        $response->assertForbidden();
    });
});

describe('UserResource Edit Access', function () {
    test('superadmin can access edit page', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $user = User::factory()->create();
        
        $response = $this->actingAs($superadmin)
            ->get(UserResource::getUrl('edit', ['record' => $user]));
        
        $response->assertSuccessful();
    });

    test('admin can access edit page for tenant users', function () {
        $tenantId = 1;
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $tenantId]);
        Subscription::factory()->active()->create(['user_id' => $admin->id]);
        $user = User::factory()->create(['tenant_id' => $tenantId]);
        
        $response = $this->actingAs($admin)
            ->get(UserResource::getUrl('edit', ['record' => $user]));
        
        $response->assertSuccessful();
    });

    test('manager cannot access edit page for tenant users', function () {
        $tenantId = 1;
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
        $user = User::factory()->create(['tenant_id' => $tenantId]);
        
        $response = $this->actingAs($manager)
            ->get(UserResource::getUrl('edit', ['record' => $user]));
        
        $response->assertForbidden();
    });

    test('tenant cannot access edit page', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $user = User::factory()->create();
        
        $response = $this->actingAs($tenant)
            ->get(UserResource::getUrl('edit', ['record' => $user]));
        
        $response->assertForbidden();
    });

    test('admin cannot edit users from different tenant', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        Subscription::factory()->active()->create(['user_id' => $admin->id]);
        $user = User::factory()->create(['tenant_id' => 2]);
        
        $response = $this->actingAs($admin)
            ->get(UserResource::getUrl('edit', ['record' => $user]));
        
        // Should not find the user due to tenant scope
        $response->assertNotFound();
    });
});

describe('UserResource View Access', function () {
    test('superadmin can access view page', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $user = User::factory()->create();
        
        $response = $this->actingAs($superadmin)
            ->get(UserResource::getUrl('view', ['record' => $user]));
        
        $response->assertSuccessful();
    });

    test('admin can access view page for tenant users', function () {
        $tenantId = 1;
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $tenantId]);
        Subscription::factory()->active()->create(['user_id' => $admin->id]);
        $user = User::factory()->create(['tenant_id' => $tenantId]);
        
        $response = $this->actingAs($admin)
            ->get(UserResource::getUrl('view', ['record' => $user]));
        
        $response->assertSuccessful();
    });

    test('tenant cannot access view page', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $user = User::factory()->create();
        
        $response = $this->actingAs($tenant)
            ->get(UserResource::getUrl('view', ['record' => $user]));
        
        $response->assertForbidden();
    });
});

describe('UserResource Navigation', function () {
    test('navigation item visible for superadmin', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $this->actingAs($superadmin);
        
        expect(UserResource::shouldRegisterNavigation())->toBeTrue();
    });

    test('navigation item visible for admin', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $this->actingAs($admin);
        
        expect(UserResource::shouldRegisterNavigation())->toBeTrue();
    });

    test('navigation item hidden for manager', function () {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        
        $this->actingAs($manager);
        
        expect(UserResource::shouldRegisterNavigation())->toBeFalse();
    });

    test('navigation item hidden for tenant', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        
        $this->actingAs($tenant);
        
        expect(UserResource::shouldRegisterNavigation())->toBeFalse();
    });
});

describe('UserResource Tenant Isolation', function () {
    test('admin only sees users from their tenant', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        User::factory()->count(5)->create(['tenant_id' => 1]);
        User::factory()->count(3)->create(['tenant_id' => 2]);
        
        $this->actingAs($admin);
        
        $query = UserResource::getEloquentQuery();
        $users = $query->get();
        
        expect($users)->toHaveCount(6) // 5 + admin
            ->and($users->every(fn($user) => $user->tenant_id === 1))->toBeTrue();
    });

    test('manager cannot access user resource tenant scope', function () {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        User::factory()->count(5)->create(['tenant_id' => 1]);
        User::factory()->count(3)->create(['tenant_id' => 2]);
        
        $this->actingAs($manager);

        expect(UserResource::canViewAny())->toBeFalse();
    });

    test('superadmin sees all users regardless of tenant', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        User::factory()->count(5)->create(['tenant_id' => 1]);
        User::factory()->count(3)->create(['tenant_id' => 2]);
        
        $this->actingAs($superadmin);
        
        $query = UserResource::getEloquentQuery();
        $users = $query->get();
        
        expect($users)->toHaveCount(9); // 5 + 3 + superadmin
    });
});

<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/**
 * Authorization tests for UserResource.
 *
 * Tests policy integration and access control:
 * - Navigation visibility by role
 * - CRUD operation authorization
 * - Cross-tenant access prevention
 * - Self-deletion prevention
 *
 * @group filament
 * @group user-resource
 * @group authorization
 */

test('navigation is visible to superadmin', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
    ]);

    actingAs($superadmin);

    expect(UserResource::shouldRegisterNavigation())->toBeTrue();
});

test('navigation is visible to admin', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    actingAs($admin);

    expect(UserResource::shouldRegisterNavigation())->toBeTrue();
});

test('navigation is visible to manager', function () {
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);

    actingAs($manager);

    expect(UserResource::shouldRegisterNavigation())->toBeTrue();
});

test('navigation is hidden from tenant', function () {
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    actingAs($tenant);

    expect(UserResource::shouldRegisterNavigation())->toBeFalse();
});

test('unauthorized user cannot access user list', function () {
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    actingAs($tenant);

    Livewire::test(ListUsers::class)
        ->assertForbidden();
});

test('unauthorized user cannot create users', function () {
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    actingAs($tenant);

    Livewire::test(CreateUser::class)
        ->assertForbidden();
});

test('admin cannot edit users outside their tenant', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $otherUser = User::factory()->create([
        'tenant_id' => 2,
    ]);

    actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $otherUser->id])
        ->assertForbidden();
});

test('manager cannot edit users outside their tenant', function () {
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);

    $otherUser = User::factory()->create([
        'tenant_id' => 2,
    ]);

    actingAs($manager);

    Livewire::test(EditUser::class, ['record' => $otherUser->id])
        ->assertForbidden();
});

test('superadmin can edit users in any tenant', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);

    $user = User::factory()->create([
        'tenant_id' => 1,
    ]);

    actingAs($superadmin);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->assertSuccessful();
});

test('user cannot delete themselves', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    expect($admin->can('delete', $admin))->toBeFalse();
});

test('admin can delete users in their tenant', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $user = User::factory()->create([
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    expect($admin->can('delete', $user))->toBeTrue();
});

test('admin cannot delete users outside their tenant', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $otherUser = User::factory()->create([
        'tenant_id' => 2,
    ]);

    actingAs($admin);

    expect($admin->can('delete', $otherUser))->toBeFalse();
});

test('user policy gates view any operation', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    expect($admin->can('viewAny', User::class))->toBeTrue()
        ->and($tenant->can('viewAny', User::class))->toBeFalse();
});

test('user policy gates create operation', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    expect($admin->can('create', User::class))->toBeTrue()
        ->and($tenant->can('create', User::class))->toBeFalse();
});

test('user policy gates update operation', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $sameTenanUser = User::factory()->create([
        'tenant_id' => 1,
    ]);

    $otherTenantUser = User::factory()->create([
        'tenant_id' => 2,
    ]);

    expect($admin->can('update', $sameTenantUser))->toBeTrue()
        ->and($admin->can('update', $otherTenantUser))->toBeFalse();
});

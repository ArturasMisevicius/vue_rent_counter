<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\OrganizationUsers\Pages\EditOrganizationUser;
use App\Livewire\Filament\ManagerPermissionMatrixPanel;
use App\Models\ManagerPermission;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the manager permission matrix on the superadmin organization user edit page for manager memberships', function (): void {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $organizationUser = OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'permissions' => null,
    ]);

    test()->actingAs($superadmin);

    Livewire::test(EditOrganizationUser::class, ['record' => $organizationUser->getRouteKey()])
        ->assertSee('Resource permissions')
        ->assertSee('Buildings')
        ->assertSee('Create')
        ->assertSee('Edit')
        ->assertSee('Delete')
        ->assertSee('View')
        ->assertSee('Changes take effect immediately.')
        ->assertSee($manager->name)
        ->assertSee($organization->name);
});

it('hides the superadmin banner when the matrix is rendered outside a superadmin context', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $organizationUser = OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'permissions' => null,
    ]);

    test()->actingAs($admin);

    Livewire::test(ManagerPermissionMatrixPanel::class, [
        'record' => $organizationUser,
        'organizationId' => $organization->id,
        'userId' => $manager->id,
    ])
        ->assertSee('Resource permissions')
        ->assertDontSee('Changes take effect immediately.');
});

it('does not render the manager permission matrix for non-manager organization memberships', function (): void {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $organizationUser = OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $admin->id,
        'role' => UserRole::ADMIN->value,
        'permissions' => null,
    ]);

    test()->actingAs($superadmin);

    Livewire::test(EditOrganizationUser::class, ['record' => $organizationUser->getRouteKey()])
        ->assertDontSee('Resource permissions')
        ->assertDontSee('Buildings')
        ->assertDontSee('Read only')
        ->assertDontSee('Copy from another manager');
});

it('saves the manager permission matrix through the panel component', function (): void {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $organizationUser = OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'permissions' => null,
    ]);

    test()->actingAs($superadmin);

    Livewire::test(ManagerPermissionMatrixPanel::class, [
        'record' => $organizationUser,
        'organizationId' => $organization->id,
        'userId' => $manager->id,
    ])
        ->set('matrix.buildings.can_create', true)
        ->set('matrix.properties.can_edit', true)
        ->call('save');

    expect(ManagerPermission::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $manager->id)
        ->where('resource', 'buildings')
        ->first())
        ->not->toBeNull()
        ->can_create->toBeTrue()
        ->can_edit->toBeFalse()
        ->can_delete->toBeFalse();

    expect(ManagerPermission::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $manager->id)
        ->where('resource', 'properties')
        ->first())
        ->not->toBeNull()
        ->can_create->toBeFalse()
        ->can_edit->toBeTrue()
        ->can_delete->toBeFalse();
});

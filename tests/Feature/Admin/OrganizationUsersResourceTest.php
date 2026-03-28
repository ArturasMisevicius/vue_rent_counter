<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\OrganizationUsers\Pages\ListOrganizationUsers;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lets admins browse manager memberships in their current organization only', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'name' => 'Current Organization Manager',
    ]);

    OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'permissions' => null,
    ]);

    $viewer = User::factory()->create([
        'organization_id' => $organization->id,
        'role' => UserRole::TENANT->value,
        'name' => 'Current Organization Viewer',
    ]);

    OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $viewer->id,
        'role' => UserRole::TENANT->value,
        'permissions' => null,
    ]);

    $otherOrganization = Organization::factory()->create();
    $otherManager = User::factory()->manager()->create([
        'organization_id' => $otherOrganization->id,
        'name' => 'Other Organization Manager',
    ]);

    OrganizationUser::factory()->create([
        'organization_id' => $otherOrganization->id,
        'user_id' => $otherManager->id,
        'role' => UserRole::MANAGER->value,
        'permissions' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organization-users.index'))
        ->assertSuccessful()
        ->assertSeeText('Current Organization Manager')
        ->assertDontSeeText('Current Organization Viewer')
        ->assertDontSeeText('Other Organization Manager');
});

it('scopes organization user list affordances by actor context', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'name' => 'Scoped Organization Manager',
    ]);

    $scopedMembership = OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'permissions' => null,
    ]);

    $otherOrganization = Organization::factory()->create([
        'name' => 'Other Organization',
    ]);

    $otherManager = User::factory()->manager()->create([
        'organization_id' => $otherOrganization->id,
        'name' => 'Other Organization Manager',
    ]);

    $otherMembership = OrganizationUser::factory()->create([
        'organization_id' => $otherOrganization->id,
        'user_id' => $otherManager->id,
        'role' => UserRole::MANAGER->value,
        'permissions' => null,
    ]);

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organization-users.index'))
        ->assertSuccessful()
        ->assertDontSee(route('filament.admin.resources.organization-users.create'), false);

    $this->actingAs($admin);

    Livewire::test(ListOrganizationUsers::class)
        ->assertActionDoesNotExist('create')
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.singular'))
        ->assertTableColumnHidden('organization.name')
        ->assertTableBulkActionDoesNotExist('deleteSelected')
        ->assertCanSeeTableRecords([$scopedMembership])
        ->assertCanNotSeeTableRecords([$otherMembership]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organization-users.index'))
        ->assertSuccessful()
        ->assertSee(route('filament.admin.resources.organization-users.create'), false);

    $this->actingAs($superadmin);

    Livewire::test(ListOrganizationUsers::class)
        ->assertActionExists('create')
        ->assertTableColumnVisible('organization.name')
        ->assertTableBulkActionExists('deleteSelected')
        ->assertCanSeeTableRecords([$scopedMembership, $otherMembership]);
});

it('lets admins view and edit manager memberships in their current organization', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();

    $inviter = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Membership Inviter',
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $organizationUser = OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'permissions' => null,
        'invited_by' => $inviter->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organization-users.view', ['record' => $organizationUser]))
        ->assertSuccessful()
        ->assertSeeText($manager->name)
        ->assertSeeText($inviter->name)
        ->assertDontSee('data-superadmin-surface="true"', false);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organization-users.edit', ['record' => $organizationUser]))
        ->assertSuccessful()
        ->assertSeeText(__('admin.manager_permissions.section'))
        ->assertDontSee('data-superadmin-surface="true"', false);
});

it('forbids admins from creating or opening organization users outside the scoped manager surface', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'permissions' => null,
    ]);

    $nonManager = User::factory()->create([
        'organization_id' => $organization->id,
        'role' => UserRole::TENANT->value,
    ]);

    $nonManagerMembership = OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $nonManager->id,
        'role' => UserRole::TENANT->value,
        'permissions' => null,
    ]);

    $otherOrganization = Organization::factory()->create();
    $otherManager = User::factory()->manager()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    $otherMembership = OrganizationUser::factory()->create([
        'organization_id' => $otherOrganization->id,
        'user_id' => $otherManager->id,
        'role' => UserRole::MANAGER->value,
        'permissions' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organization-users.create'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organization-users.view', ['record' => $nonManagerMembership]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organization-users.edit', ['record' => $nonManagerMembership]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organization-users.view', ['record' => $otherMembership]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organization-users.edit', ['record' => $otherMembership]))
        ->assertNotFound();
});

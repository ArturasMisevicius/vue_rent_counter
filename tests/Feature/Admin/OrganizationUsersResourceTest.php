<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

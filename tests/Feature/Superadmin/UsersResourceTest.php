<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the superadmin users list page contract', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);
    $building = Building::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $property = Property::factory()->create([
        'organization_id' => $organization->id,
        'building_id' => $building->id,
    ]);

    $managedUser = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Avery Admin',
        'email' => 'avery@example.com',
        'last_login_at' => now()->subHours(2)->startOfMinute(),
    ]);

    $suspendedUser = User::factory()->manager()->suspended()->create([
        'organization_id' => $organization->id,
        'name' => 'Morgan Manager',
        'email' => 'morgan@example.com',
    ]);

    $protectedTenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
        'email' => 'taylor@example.com',
    ]);

    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
        'tenant_user_id' => $protectedTenant->id,
    ]);

    $disposableUser = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'name' => 'Casey Cleaner',
        'email' => 'casey@example.com',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.users.index'))
        ->assertSuccessful()
        ->assertSeeText('Users')
        ->assertSeeText('New User')
        ->assertSee('Search by name or email', false)
        ->assertSeeText('Role')
        ->assertSeeText('Status')
        ->assertSeeText('Organization')
        ->assertSeeText('Last Login')
        ->assertSeeText('Clear All Filters')
        ->assertSeeText($managedUser->name)
        ->assertSeeText($managedUser->email)
        ->assertSeeText($organization->name)
        ->assertSeeText('Never')
        ->assertSee(route('filament.admin.resources.users.view', $managedUser), false)
        ->assertSee(route('filament.admin.resources.organizations.view', $organization), false);

    $this->actingAs($superadmin);

    Livewire::test(ListUsers::class)
        ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getLabel() === 'Full Name')
        ->assertTableColumnExists('email', fn (TextColumn $column): bool => $column->getLabel() === 'Email')
        ->assertTableColumnExists('role', fn (TextColumn $column): bool => $column->getLabel() === 'Role')
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableColumnExists('last_login_at', fn (TextColumn $column): bool => $column->getLabel() === 'Last Login')
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableFilterExists('role', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Role' && $filter->getPlaceholder() === 'All Roles')
        ->assertTableFilterExists('status', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Status' && $filter->getPlaceholder() === 'All')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertTableFilterExists('last_login', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Last Login' && $filter->getPlaceholder() === 'Any Time')
        ->assertTableActionHasLabel('view', 'View', record: $managedUser)
        ->assertTableActionHasLabel('edit', 'Edit', record: $managedUser)
        ->assertTableActionHasLabel('toggleUserStatus', 'Suspend', record: $managedUser)
        ->assertTableActionHasLabel('toggleUserStatus', 'Reinstate', record: $suspendedUser)
        ->assertTableActionHasLabel('resetPassword', 'Reset Password', record: $managedUser)
        ->assertTableActionHasLabel('impersonateUser', 'Impersonate', record: $managedUser)
        ->assertTableActionHasLabel('deleteUser', 'Delete', record: $managedUser)
        ->assertTableActionEnabled('deleteUser', record: $disposableUser)
        ->assertTableActionDisabled('deleteUser', record: $protectedTenant)
        ->assertTableActionExists(
            'deleteUser',
            checkActionUsing: fn (DeleteAction $action): bool => $action->isDisabled()
                && $action->getTooltip() === 'Cannot delete this user because linked invoices still exist.',
            record: $protectedTenant,
        )
        ->assertTableColumnStateSet('role', UserRole::ADMIN->label(), $managedUser)
        ->assertTableColumnStateSet('organization.name', $organization->name, $managedUser)
        ->assertTableColumnStateSet('last_login_at', $managedUser->last_login_at?->format('Y-m-d H:i'), $managedUser)
        ->assertTableColumnStateSet('last_login_at', 'Never', $suspendedUser)
        ->assertTableColumnStateSet('status', UserStatus::ACTIVE->label(), $managedUser);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.users.create'))
        ->assertSuccessful()
        ->assertSeeText('Role')
        ->assertSeeText('Organization')
        ->assertSeeText('Status');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.users.view', $managedUser))
        ->assertSuccessful()
        ->assertSeeText($managedUser->name)
        ->assertSeeText($managedUser->email);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.users.edit', $managedUser))
        ->assertSuccessful()
        ->assertSeeText('Save changes')
        ->assertSeeText($managedUser->email);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.users.index'))
        ->assertForbidden();
});

it('searches and filters users by name or email, role, status, organization, and last login state', function () {
    $superadmin = User::factory()->superadmin()->create();

    $matchingOrganization = Organization::factory()->create([
        'name' => 'Aurora Estates',
    ]);
    $filteredOutOrganization = Organization::factory()->create([
        'name' => 'Beacon Holdings',
    ]);

    $matchingUser = User::factory()->manager()->create([
        'organization_id' => $matchingOrganization->id,
        'name' => 'Aurora Manager',
        'email' => 'aurora@example.com',
        'status' => UserStatus::SUSPENDED,
        'last_login_at' => null,
    ]);

    $filteredOutUser = User::factory()->admin()->create([
        'organization_id' => $filteredOutOrganization->id,
        'name' => 'Beacon Admin',
        'email' => 'beacon@example.com',
        'status' => UserStatus::ACTIVE,
        'last_login_at' => now()->subDay(),
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListUsers::class)
        ->searchTable('aurora@example.com')
        ->assertCanSeeTableRecords([$matchingUser])
        ->assertCanNotSeeTableRecords([$filteredOutUser])
        ->searchTable()
        ->filterTable('role', UserRole::MANAGER->value)
        ->assertCanSeeTableRecords([$matchingUser])
        ->assertCanNotSeeTableRecords([$filteredOutUser])
        ->resetTableFilters()
        ->filterTable('status', UserStatus::SUSPENDED->value)
        ->assertCanSeeTableRecords([$matchingUser])
        ->assertCanNotSeeTableRecords([$filteredOutUser])
        ->resetTableFilters()
        ->filterTable('organization', $matchingOrganization->getKey())
        ->assertCanSeeTableRecords([$matchingUser])
        ->assertCanNotSeeTableRecords([$filteredOutUser])
        ->resetTableFilters()
        ->filterTable('last_login', 'never')
        ->assertCanSeeTableRecords([$matchingUser])
        ->assertCanNotSeeTableRecords([$filteredOutUser]);
});

it('blocks superadmin deletion when users still have invoices, buildings, or active linked records', function () {
    $organization = Organization::factory()->create();
    $invoiceProtectedUser = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $buildingProtectedUser = User::factory()->admin()->create();
    $activeDataProtectedUser = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $property = Property::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
        'tenant_user_id' => $invoiceProtectedUser->id,
    ]);

    $ownedOrganization = Organization::factory()->create([
        'owner_user_id' => $buildingProtectedUser->id,
    ]);

    Building::factory()->create([
        'organization_id' => $ownedOrganization->id,
    ]);

    PropertyAssignment::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
        'tenant_user_id' => $activeDataProtectedUser->id,
        'unassigned_at' => null,
    ]);

    $disposableUser = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    expect($invoiceProtectedUser->fresh()->canBeDeletedFromSuperadmin())->toBeFalse()
        ->and($invoiceProtectedUser->fresh()->superadminDeletionBlockedReason())->toBe('Cannot delete this user because linked invoices still exist.')
        ->and($buildingProtectedUser->fresh()->canBeDeletedFromSuperadmin())->toBeFalse()
        ->and($buildingProtectedUser->fresh()->superadminDeletionBlockedReason())->toBe('Cannot delete this user because their organization still has buildings.')
        ->and($activeDataProtectedUser->fresh()->canBeDeletedFromSuperadmin())->toBeFalse()
        ->and($activeDataProtectedUser->fresh()->superadminDeletionBlockedReason())->toBe('Cannot delete this user because active records are still tied to them.')
        ->and($disposableUser->fresh()->canBeDeletedFromSuperadmin())->toBeTrue()
        ->and($disposableUser->fresh()->superadminDeletionBlockedReason())->toBeNull();
});

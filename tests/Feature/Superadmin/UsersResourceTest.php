<?php

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Superadmin\Users\StartUserImpersonationAction;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\SecurityViolation;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
        ->assertSeeText(__('superadmin.users.plural'))
        ->assertSeeText(__('superadmin.users.actions.new'))
        ->assertSee(__('superadmin.users.search_placeholder'), false)
        ->assertSeeText(__('superadmin.users.columns.role'))
        ->assertSeeText(__('superadmin.users.columns.status'))
        ->assertSeeText(__('superadmin.users.columns.organization'))
        ->assertSeeText(__('superadmin.users.columns.last_login'))
        ->assertSeeText(__('superadmin.users.filters.clear_all'))
        ->assertSeeText($managedUser->name)
        ->assertSeeText($managedUser->email)
        ->assertSeeText($organization->name)
        ->assertSeeText(__('superadmin.users.placeholders.never'))
        ->assertSee(route('filament.admin.resources.users.view', $managedUser), false)
        ->assertSee(route('filament.admin.resources.organizations.view', $organization), false);

    $this->actingAs($superadmin);

    Livewire::test(ListUsers::class)
        ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.users.columns.full_name'))
        ->assertTableColumnExists('email', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.users.columns.email'))
        ->assertTableColumnExists('role', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.users.columns.role'))
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.users.columns.organization'))
        ->assertTableColumnExists('last_login_at', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.users.columns.last_login'))
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.users.columns.status'))
        ->assertTableFilterExists('role', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.users.filters.role') && $filter->getPlaceholder() === __('superadmin.users.filters.all_roles'))
        ->assertTableFilterExists('status', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.users.filters.status') && $filter->getPlaceholder() === __('superadmin.users.filters.all'))
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.users.filters.organization'))
        ->assertTableFilterExists('last_login', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.users.filters.last_login') && $filter->getPlaceholder() === __('superadmin.users.filters.any_time'))
        ->assertTableActionHasLabel('view', __('superadmin.users.actions.view'), record: $managedUser)
        ->assertTableActionHasLabel('edit', __('superadmin.users.actions.edit'), record: $managedUser)
        ->assertTableActionHasLabel('toggleUserStatus', __('superadmin.users.actions.suspend'), record: $managedUser)
        ->assertTableActionHasLabel('toggleUserStatus', __('superadmin.users.actions.reinstate'), record: $suspendedUser)
        ->assertTableActionHasLabel('resetPassword', __('superadmin.users.actions.reset_password'), record: $managedUser)
        ->assertTableActionHasLabel('impersonateUser', __('superadmin.users.actions.impersonate'), record: $managedUser)
        ->assertTableActionHasLabel('deleteUser', __('superadmin.users.actions.delete'), record: $managedUser)
        ->assertTableActionEnabled('deleteUser', record: $disposableUser)
        ->assertTableActionDisabled('deleteUser', record: $protectedTenant)
        ->assertTableActionExists(
            'deleteUser',
            checkActionUsing: fn (DeleteAction $action): bool => $action->isDisabled()
                && $action->getTooltip() === __('superadmin.users.deletion_reasons.wrapper', [
                    'reasons' => __('superadmin.users.deletion_reasons.invoices'),
                ]),
            record: $protectedTenant,
        )
        ->assertTableColumnStateSet('role', UserRole::ADMIN->label(), $managedUser)
        ->assertTableColumnStateSet('organization.name', $organization->name, $managedUser)
        ->assertTableColumnStateSet('last_login_at', $managedUser->last_login_at?->locale(app()->getLocale())->isoFormat('LLL'), $managedUser)
        ->assertTableColumnStateSet('last_login_at', __('superadmin.users.placeholders.never'), $suspendedUser)
        ->assertTableColumnStateSet('status', UserStatus::ACTIVE->label(), $managedUser);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.users.create'))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.users.fields.role'))
        ->assertSeeText(__('superadmin.users.fields.organization'))
        ->assertSeeText(__('superadmin.users.fields.status'));

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
        ->and($invoiceProtectedUser->fresh()->superadminDeletionBlockedReason())->toBe(__('superadmin.users.deletion_reasons.wrapper', [
            'reasons' => __('superadmin.users.deletion_reasons.invoices'),
        ]))
        ->and($buildingProtectedUser->fresh()->canBeDeletedFromSuperadmin())->toBeFalse()
        ->and($buildingProtectedUser->fresh()->superadminDeletionBlockedReason())->toBe(__('superadmin.users.deletion_reasons.wrapper', [
            'reasons' => __('superadmin.users.deletion_reasons.buildings'),
        ]))
        ->and($activeDataProtectedUser->fresh()->canBeDeletedFromSuperadmin())->toBeFalse()
        ->and($activeDataProtectedUser->fresh()->superadminDeletionBlockedReason())->toBe(__('superadmin.users.deletion_reasons.wrapper', [
            'reasons' => __('superadmin.users.deletion_reasons.active_records'),
        ]))
        ->and($disposableUser->fresh()->canBeDeletedFromSuperadmin())->toBeTrue()
        ->and($disposableUser->fresh()->superadminDeletionBlockedReason())->toBeNull();
});

it('blocks generic user impersonation when the users organization has an active critical security incident', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $managedUser = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    SecurityViolation::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $managedUser->id,
        'type' => SecurityViolationType::IMPERSONATION,
        'severity' => SecurityViolationSeverity::CRITICAL,
        'resolved_at' => null,
    ]);

    expect(fn () => app(StartUserImpersonationAction::class)->handle($superadmin, $managedUser))
        ->toThrow(AccessDeniedHttpException::class);
});

it('derives organization roster support affordances from ownership and invitation state', function () {
    $organization = Organization::factory()->create();

    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'status' => UserStatus::ACTIVE,
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    $invitedManager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'status' => UserStatus::INACTIVE,
        'email_verified_at' => null,
        'email' => 'invited.manager@example.test',
    ]);

    OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $owner->id,
        'email' => $invitedManager->email,
        'role' => UserRole::MANAGER,
        'accepted_at' => null,
        'expires_at' => now()->subDay(),
    ]);

    $activeManager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'status' => UserStatus::ACTIVE,
    ]);

    expect($owner->fresh()->canChangeRoleFromOrganizationRoster())->toBeFalse()
        ->and($owner->fresh()->canResendOrganizationInvitationFromRoster())->toBeFalse()
        ->and($invitedManager->fresh()->canChangeRoleFromOrganizationRoster())->toBeTrue()
        ->and($invitedManager->fresh()->canResendOrganizationInvitationFromRoster())->toBeTrue()
        ->and($invitedManager->fresh()->latestResendableOrganizationInvitation()?->email)->toBe($invitedManager->email)
        ->and($activeManager->fresh()->canChangeRoleFromOrganizationRoster())->toBeTrue()
        ->and($activeManager->fresh()->canResendOrganizationInvitationFromRoster())->toBeFalse();
});

<?php

declare(strict_types=1);

use App\Enums\ManagerMembershipStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\Authorization\EffectivePermissionsResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('defines the required manager permission presets', function (): void {
    $presets = ManagerPermissionCatalog::presets();

    expect($presets)->toHaveKeys([
        'full_manager',
        'billing_manager',
        'property_manager',
        'read_only_manager',
    ])
        ->and($presets['billing_manager']['matrix']['tariffs']['can_edit'])->toBeFalse()
        ->and($presets['billing_manager']['matrix']['service_configurations']['can_edit'])->toBeFalse()
        ->and($presets['property_manager']['matrix']['meter_readings']['can_edit'])->toBeFalse()
        ->and($presets['property_manager']['matrix']['invoices']['can_edit'])->toBeFalse();
});

it('resolves superadmin and admin permissions without tenant portal escalation', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $superadmin = User::factory()->superadmin()->create();
    $resolver = app(EffectivePermissionsResolver::class);

    expect($resolver->can($superadmin, Permission::PlatformDashboardView, $organization))->toBeTrue()
        ->and($resolver->can($superadmin, Permission::ImpersonationStart, $organization))->toBeTrue()
        ->and($resolver->can($superadmin, Permission::TenantPortalAccess, $organization))->toBeFalse()
        ->and($resolver->can($admin, Permission::OrganizationDashboardView, $organization))->toBeTrue()
        ->and($resolver->can($admin, Permission::TeamManage, $organization))->toBeTrue()
        ->and($resolver->can($admin, Permission::PlatformDashboardView, $organization))->toBeFalse()
        ->and($resolver->can($admin, Permission::OrganizationsManage, $organization))->toBeFalse();
});

it('keeps read only managers in view-only organization permissions', function (): void {
    ['organization' => $organization, 'manager' => $manager] = roleMatrixWorkspace();
    $resolver = app(EffectivePermissionsResolver::class);

    expect($resolver->can($manager, Permission::OrganizationDashboardView, $organization))->toBeTrue()
        ->and($resolver->can($manager, Permission::BuildingsView, $organization))->toBeTrue()
        ->and($resolver->can($manager, Permission::InvoicesView, $organization))->toBeTrue()
        ->and($resolver->can($manager, Permission::BuildingsCreate, $organization))->toBeFalse()
        ->and($resolver->can($manager, Permission::InvoicesApprove, $organization))->toBeFalse()
        ->and($resolver->can($manager, Permission::TeamManage, $organization))->toBeFalse();
});

it('maps billing manager preset to billing work without tariff or team permissions', function (): void {
    ['organization' => $organization, 'admin' => $admin, 'manager' => $manager] = roleMatrixWorkspace();

    Notification::fake();

    app(ManagerPermissionService::class)->saveMatrix(
        $manager,
        $organization,
        ManagerPermissionCatalog::presets()['billing_manager']['matrix'],
        $admin,
    );

    $resolver = app(EffectivePermissionsResolver::class);

    expect($resolver->can($manager, Permission::ReadingsApprove, $organization))->toBeTrue()
        ->and($resolver->can($manager, Permission::InvoicesGenerate, $organization))->toBeTrue()
        ->and($resolver->can($manager, Permission::InvoicesApprove, $organization))->toBeTrue()
        ->and($resolver->can($manager, Permission::PaymentsConfirm, $organization))->toBeTrue()
        ->and($resolver->can($manager, Permission::TariffsManage, $organization))->toBeFalse()
        ->and($resolver->can($manager, Permission::TeamManage, $organization))->toBeFalse();
});

it('maps property manager preset away from invoice approval and payment confirmation', function (): void {
    ['organization' => $organization, 'admin' => $admin, 'manager' => $manager] = roleMatrixWorkspace();

    Notification::fake();

    app(ManagerPermissionService::class)->saveMatrix(
        $manager,
        $organization,
        ManagerPermissionCatalog::presets()['property_manager']['matrix'],
        $admin,
    );

    $resolver = app(EffectivePermissionsResolver::class);

    expect($resolver->can($manager, Permission::PropertiesUpdate, $organization))->toBeTrue()
        ->and($resolver->can($manager, Permission::TenantsUpdate, $organization))->toBeTrue()
        ->and($resolver->can($manager, Permission::ContractsUpdate, $organization))->toBeTrue()
        ->and($resolver->can($manager, Permission::ReadingsApprove, $organization))->toBeFalse()
        ->and($resolver->can($manager, Permission::InvoicesApprove, $organization))->toBeFalse()
        ->and($resolver->can($manager, Permission::PaymentsConfirm, $organization))->toBeFalse();
});

it('blocks disabled manager memberships from effective organization permissions', function (): void {
    ['organization' => $organization, 'manager' => $manager] = roleMatrixWorkspace([
        'status' => ManagerMembershipStatus::DISABLED,
        'is_active' => false,
        'disabled_at' => now(),
    ]);

    $resolver = app(EffectivePermissionsResolver::class);

    expect($resolver->for($manager, $organization))->toBe([]);
});

it('scopes tenant permissions to active tenant portal access in their own organization', function (): void {
    ['organization' => $organization] = createOrgWithAdmin();
    $otherOrganization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $resolver = app(EffectivePermissionsResolver::class);

    expect($resolver->can($tenant, Permission::TenantPortalAccess, $organization))->toBeTrue()
        ->and($resolver->can($tenant, Permission::TenantInvoicesViewOwn, $organization))->toBeTrue()
        ->and($resolver->can($tenant, Permission::OrganizationDashboardView, $organization))->toBeFalse()
        ->and($resolver->can($tenant, Permission::TenantPortalAccess, $otherOrganization))->toBeFalse();
});

/**
 * @param  array<string, mixed>  $membershipOverrides
 * @return array{organization: Organization, admin: User, manager: User}
 */
function roleMatrixWorkspace(array $membershipOverrides = []): array
{
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    OrganizationUser::factory()->create(array_merge([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'status' => ManagerMembershipStatus::ACTIVE,
        'permissions' => null,
        'permissions_preset' => 'read_only_manager',
        'is_active' => true,
        'left_at' => null,
    ], $membershipOverrides));

    return [
        'organization' => $organization,
        'admin' => $admin,
        'manager' => $manager,
    ];
}

<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Exceptions\ManagerPermissions\InvalidPermissionResourceException;
use App\Exceptions\ManagerPermissions\UserIsNotManagerException;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Providers\ProviderResource;
use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use App\Filament\Resources\Tariffs\TariffResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Resources\UtilityServices\UtilityServiceResource;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\ManagerPermission;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Property;
use App\Models\User;
use App\Policies\InvoicePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    ManagerPermissionService::flushCache();
});

it('denies manager create access across the permission matrix by default', function (): void {
    ['organization' => $organization, 'manager' => $manager] = managerWorkspace();

    actingAs($manager);

    expect(BuildingResource::canCreate())->toBeFalse()
        ->and(PropertyResource::canCreate())->toBeFalse()
        ->and(TenantResource::canCreate())->toBeFalse()
        ->and(MeterResource::canCreate())->toBeFalse()
        ->and(MeterReadingResource::canCreate())->toBeFalse()
        ->and(Gate::forUser($manager)->allows('create', Invoice::class))->toBeFalse()
        ->and(TariffResource::canCreate())->toBeFalse()
        ->and(ProviderResource::canCreate())->toBeFalse()
        ->and(ServiceConfigurationResource::canCreate())->toBeFalse()
        ->and(UtilityServiceResource::canCreate())->toBeFalse()
        ->and(app(ManagerPermissionService::class)->can($manager, $organization, 'billing', 'create'))->toBeFalse();
});

it('allows a manager with building create permission to create a building', function (): void {
    ['organization' => $organization, 'admin' => $admin, 'manager' => $manager] = managerWorkspace();

    Notification::fake();

    app(ManagerPermissionService::class)->saveMatrix(
        $manager,
        $organization,
        fullPermissionMatrix([
            'buildings' => ['can_create' => true],
        ]),
        $admin,
    );

    actingAs($manager);

    expect(BuildingResource::canCreate())->toBeTrue()
        ->and(PropertyResource::canCreate())->toBeFalse();
});

it('does not allow building permission to leak into properties', function (): void {
    ['organization' => $organization, 'admin' => $admin, 'manager' => $manager] = managerWorkspace();

    Notification::fake();

    app(ManagerPermissionService::class)->saveMatrix(
        $manager,
        $organization,
        fullPermissionMatrix([
            'buildings' => ['can_create' => true],
        ]),
        $admin,
    );

    actingAs($manager);

    expect(BuildingResource::canCreate())->toBeTrue()
        ->and(PropertyResource::canCreate())->toBeFalse();
});

it('allows invoice edit without delete when only edit permission is granted', function (): void {
    ['organization' => $organization, 'admin' => $admin, 'manager' => $manager] = managerWorkspace();
    $invoice = tenantInvoiceForOrganization($organization);

    Notification::fake();

    app(ManagerPermissionService::class)->saveMatrix(
        $manager,
        $organization,
        fullPermissionMatrix([
            'invoices' => ['can_edit' => true],
        ]),
        $admin,
    );

    expect(Gate::forUser($manager)->allows('update', $invoice))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('delete', $invoice))->toBeFalse();
});

it('does not apply the manager matrix to org admins', function (): void {
    ['organization' => $organization, 'admin' => $admin] = managerWorkspace();

    ManagerPermission::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $admin->id,
        'resource' => 'buildings',
        'can_create' => false,
        'can_edit' => false,
        'can_delete' => false,
    ]);

    actingAs($admin);

    expect(BuildingResource::canCreate())->toBeTrue();
});

it('always allows superadmins regardless of manager permissions', function (): void {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $superadmin = User::factory()->superadmin()->create();

    expect(Gate::forUser($superadmin)->allows('update', $building))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('delete', $building))->toBeTrue();
});

it('syncs manager permissions with upsert semantics', function (): void {
    ['organization' => $organization, 'manager' => $manager] = managerWorkspace();

    ManagerPermission::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'resource' => 'buildings',
        'can_create' => false,
        'can_edit' => false,
        'can_delete' => false,
    ]);

    ManagerPermission::syncForManager($manager, $organization, [
        'buildings' => [
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => false,
        ],
        'properties' => [
            'can_create' => false,
            'can_edit' => true,
            'can_delete' => false,
        ],
    ]);

    $permissions = ManagerPermission::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $manager->id)
        ->orderBy('resource')
        ->get()
        ->keyBy('resource');

    expect($permissions)->toHaveCount(2)
        ->and($permissions['buildings']->can_create)->toBeTrue()
        ->and($permissions['buildings']->can_edit)->toBeTrue()
        ->and($permissions['buildings']->can_delete)->toBeFalse()
        ->and($permissions['properties']->can_create)->toBeFalse()
        ->and($permissions['properties']->can_edit)->toBeTrue();
});

it('rejects invalid permission resources when saving a matrix', function (): void {
    ['organization' => $organization, 'admin' => $admin, 'manager' => $manager] = managerWorkspace();

    $service = app(ManagerPermissionService::class);

    expect(fn () => $service->saveMatrix(
        $manager,
        $organization,
        fullPermissionMatrix([
            'unknown_resource' => ['can_create' => true],
        ]),
        $admin,
    ))->toThrow(InvalidPermissionResourceException::class);
});

it('rejects saving a matrix for a user who is not a manager', function (): void {
    ['organization' => $organization, 'admin' => $admin] = managerWorkspace();
    $user = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $service = app(ManagerPermissionService::class);

    expect(fn () => $service->saveMatrix(
        $user,
        $organization,
        fullPermissionMatrix([
            'buildings' => ['can_create' => true],
        ]),
        $admin,
    ))->toThrow(UserIsNotManagerException::class);
});

it('copies the full permission matrix from one manager to another', function (): void {
    ['organization' => $organization, 'admin' => $admin] = managerWorkspace();

    $sourceManager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $targetManager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    Notification::fake();

    $service = app(ManagerPermissionService::class);

    $sourceMatrix = fullPermissionMatrix([
        'buildings' => ['can_create' => true, 'can_edit' => true],
        'billing' => ['can_create' => true],
        'invoices' => ['can_edit' => true],
        'utility_services' => ['can_delete' => true],
    ]);

    $service->saveMatrix($sourceManager, $organization, $sourceMatrix, $admin);
    $service->copyFromManager($sourceManager, $targetManager, $organization, $admin);

    expect($service->getMatrix($targetManager, $organization))->toEqual($service->getMatrix($sourceManager, $organization));
});

it('resets manager permissions to defaults by deleting persisted rows', function (): void {
    ['organization' => $organization, 'admin' => $admin, 'manager' => $manager] = managerWorkspace();

    Notification::fake();

    $service = app(ManagerPermissionService::class);

    $service->saveMatrix(
        $manager,
        $organization,
        fullPermissionMatrix([
            'buildings' => ['can_create' => true],
            'properties' => ['can_edit' => true],
        ]),
        $admin,
    );

    $service->resetToDefaults($manager, $organization, $admin);

    expect(ManagerPermission::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $manager->id)
        ->count())->toBe(0)
        ->and($service->can($manager, $organization, 'buildings', 'create'))->toBeFalse()
        ->and($service->can($manager, $organization, 'properties', 'edit'))->toBeFalse()
        ->and($service->can($manager, $organization, 'providers', 'delete'))->toBeFalse();
});

it('resets persisted manager permissions when the user role changes away from manager', function (): void {
    ['organization' => $organization, 'admin' => $admin, 'manager' => $manager] = managerWorkspace();

    Notification::fake();

    app(ManagerPermissionService::class)->saveMatrix(
        $manager,
        $organization,
        fullPermissionMatrix([
            'buildings' => ['can_create' => true],
            'invoices' => ['can_edit' => true],
        ]),
        $admin,
    );

    actingAs($admin);

    $manager->update([
        'role' => UserRole::ADMIN,
    ]);

    expect(ManagerPermission::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $manager->id)
        ->count())->toBe(0);
});

it('caches a manager permission matrix in memory for repeated checks within one request', function (): void {
    ['organization' => $organization, 'admin' => $admin, 'manager' => $manager] = managerWorkspace();

    Notification::fake();

    $service = app(ManagerPermissionService::class);

    $service->saveMatrix(
        $manager,
        $organization,
        fullPermissionMatrix([
            'buildings' => ['can_create' => true],
        ]),
        $admin,
    );

    ManagerPermissionService::flushCache();
    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($service->can($manager, $organization, 'buildings', 'create'))->toBeTrue()
        ->and($service->can($manager, $organization, 'buildings', 'create'))->toBeTrue()
        ->and($service->can($manager, $organization, 'buildings', 'create'))->toBeTrue();

    $permissionQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains((string) $query['query'], 'manager_permissions'))
        ->values();

    expect($permissionQueries)->toHaveCount(1);
});

it('writes a full before and after matrix to the audit log when permissions are saved', function (): void {
    ['organization' => $organization, 'admin' => $admin, 'manager' => $manager] = managerWorkspace();

    Notification::fake();

    $service = app(ManagerPermissionService::class);

    $before = fullPermissionMatrix([
        'buildings' => ['can_create' => true],
        'properties' => ['can_edit' => true],
    ]);

    $after = fullPermissionMatrix([
        'buildings' => ['can_create' => true, 'can_edit' => true],
        'billing' => ['can_create' => true],
        'invoices' => ['can_edit' => true],
    ]);

    $service->saveMatrix($manager, $organization, $before, $admin);
    $service->saveMatrix($manager, $organization, $after, $admin);

    $auditLog = AuditLog::query()
        ->where('organization_id', $organization->id)
        ->latest('id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog?->metadata['before'] ?? null)->toEqual($before)
        ->and($auditLog?->metadata['after'] ?? null)->toEqual($after);
});

/**
 * @return array{
 *     organization: Organization,
 *     admin: User,
 *     manager: User
 * }
 */
function managerWorkspace(): array
{
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

    return [
        'organization' => $organization,
        'admin' => $admin,
        'manager' => $manager,
    ];
}

function tenantInvoiceForOrganization(Organization $organization): Invoice
{
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    return Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();
}

/**
 * @param  array<string, array<string, bool>>  $overrides
 * @return array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>
 */
function fullPermissionMatrix(array $overrides = []): array
{
    $resources = [
        'buildings',
        'properties',
        'tenants',
        'meters',
        'meter_readings',
        'billing',
        'invoices',
        'tariffs',
        'providers',
        'service_configurations',
        'utility_services',
    ];

    $matrix = collect($resources)
        ->mapWithKeys(fn (string $resource): array => [
            $resource => [
                'can_create' => false,
                'can_edit' => false,
                'can_delete' => false,
            ],
        ])
        ->all();

    foreach ($overrides as $resource => $flags) {
        $matrix[$resource] = [
            'can_create' => $flags['can_create'] ?? false,
            'can_edit' => $flags['can_edit'] ?? false,
            'can_delete' => $flags['can_delete'] ?? false,
        ];
    }

    return $matrix;
}

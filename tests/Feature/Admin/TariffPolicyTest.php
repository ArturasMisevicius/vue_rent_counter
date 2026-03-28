<?php

use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use App\Policies\TariffPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('registers tariff policy and resolves it for gate checks', function () {
    expect(Gate::getPolicyFor(Tariff::class))->toBeInstanceOf(TariffPolicy::class);
});

it('authorizes tariff actions only for admin-like users in the same organization', function () {
    $organization = Organization::factory()->create();
    $provider = Provider::factory()->forOrganization($organization)->create();
    $tariff = Tariff::factory()->for($provider)->create();

    $otherOrganization = Organization::factory()->create();
    $otherProvider = Provider::factory()->forOrganization($otherOrganization)->create();
    $foreignTariff = Tariff::factory()->for($otherProvider)->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $superadmin = User::factory()->superadmin()->create();

    Notification::fake();

    $managerMatrix = ManagerPermissionCatalog::defaultMatrix();
    $managerMatrix['tariffs']['can_create'] = true;

    app(ManagerPermissionService::class)->saveMatrix($manager, $organization, $managerMatrix, $admin);

    expect($admin->can('viewAny', Tariff::class))->toBeTrue()
        ->and($admin->can('create', Tariff::class))->toBeTrue()
        ->and($admin->can('view', $tariff))->toBeTrue()
        ->and($admin->can('update', $tariff))->toBeTrue()
        ->and($admin->can('delete', $tariff))->toBeTrue()
        ->and($admin->can('view', $foreignTariff))->toBeFalse();

    expect($manager->can('viewAny', Tariff::class))->toBeTrue()
        ->and($manager->can('create', Tariff::class))->toBeFalse()
        ->and($manager->can('view', $tariff))->toBeTrue()
        ->and($manager->can('view', $foreignTariff))->toBeFalse();

    expect($tenant->can('viewAny', Tariff::class))->toBeFalse()
        ->and($tenant->can('view', $tariff))->toBeFalse();

    expect($superadmin->can('viewAny', Tariff::class))->toBeTrue()
        ->and($superadmin->can('create', Tariff::class))->toBeTrue()
        ->and($superadmin->can('view', $tariff))->toBeTrue()
        ->and($superadmin->can('view', $foreignTariff))->toBeTrue();
});

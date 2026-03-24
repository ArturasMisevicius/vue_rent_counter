<?php

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Filament\Resources\ServiceConfigurations\Pages\ListServiceConfigurations;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UtilityService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows organization context on the service configurations list for superadmins while keeping admins scoped', function () {
    $organizationA = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $organizationB = Organization::factory()->create([
        'name' => 'Aurora Towers',
    ]);

    $buildingA = Building::factory()->for($organizationA)->create();
    $buildingB = Building::factory()->for($organizationB)->create();

    $propertyA = Property::factory()->for($organizationA)->for($buildingA)->create([
        'name' => 'A-101',
    ]);
    $propertyB = Property::factory()->for($organizationB)->for($buildingB)->create([
        'name' => 'B-202',
    ]);

    $providerA = Provider::factory()->for($organizationA)->create([
        'name' => 'Ignitis',
    ]);
    $providerB = Provider::factory()->for($organizationB)->create([
        'name' => 'Elektrum',
    ]);

    $tariffA = Tariff::factory()->for($providerA)->create([
        'name' => 'Night Saver',
    ]);
    $tariffB = Tariff::factory()->for($providerB)->create([
        'name' => 'Peak Plus',
    ]);

    $utilityServiceA = UtilityService::factory()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Electricity',
    ]);
    $utilityServiceB = UtilityService::factory()->create([
        'organization_id' => $organizationB->id,
        'name' => 'Water',
    ]);

    $configurationA = ServiceConfiguration::factory()->create([
        'organization_id' => $organizationA->id,
        'property_id' => $propertyA->id,
        'utility_service_id' => $utilityServiceA->id,
        'provider_id' => $providerA->id,
        'tariff_id' => $tariffA->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'distribution_method' => DistributionMethod::BY_CONSUMPTION,
        'effective_from' => now()->subMonth(),
        'effective_until' => null,
        'is_active' => true,
    ]);

    $configurationB = ServiceConfiguration::factory()->create([
        'organization_id' => $organizationB->id,
        'property_id' => $propertyB->id,
        'utility_service_id' => $utilityServiceB->id,
        'provider_id' => $providerB->id,
        'tariff_id' => $tariffB->id,
        'pricing_model' => PricingModel::FLAT,
        'distribution_method' => DistributionMethod::EQUAL,
        'effective_from' => now()->subMonths(2),
        'effective_until' => now()->addMonth(),
        'is_active' => false,
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.service-configurations.index'))
        ->assertSuccessful()
        ->assertSeeText('Service Configurations')
        ->assertSeeText($propertyA->name)
        ->assertSeeText($utilityServiceA->name)
        ->assertSeeText($providerA->name)
        ->assertDontSeeText($propertyB->name)
        ->assertDontSeeText($providerB->name);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.service-configurations.index'))
        ->assertSuccessful()
        ->assertSeeText('Service Configurations')
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name);

    $this->actingAs($superadmin);

    Livewire::test(ListServiceConfigurations::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableColumnExists('tariff.name', fn (TextColumn $column): bool => $column->getLabel() === 'Tariff')
        ->assertTableColumnExists('distribution_method', fn (TextColumn $column): bool => $column->getLabel() === 'Distribution Method')
        ->assertTableColumnExists('effective_from', fn (TextColumn $column): bool => $column->getLabel() === 'Effective From')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertTableFilterExists('is_active', fn (TernaryFilter $filter): bool => $filter->getLabel() === 'Active')
        ->assertTableColumnStateSet('pricing_model', PricingModel::CONSUMPTION_BASED->getLabel(), $configurationA)
        ->assertTableColumnStateSet('distribution_method', DistributionMethod::BY_CONSUMPTION->getLabel(), $configurationA)
        ->assertTableColumnStateSet('organization.name', $organizationA->name, $configurationA)
        ->assertTableColumnStateSet('organization.name', $organizationB->name, $configurationB)
        ->assertCanSeeTableRecords([$configurationA, $configurationB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$configurationA])
        ->assertCanNotSeeTableRecords([$configurationB])
        ->resetTableFilters()
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$configurationA])
        ->assertCanNotSeeTableRecords([$configurationB]);
});

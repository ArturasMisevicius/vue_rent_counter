<?php

use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Resources\UtilityServices\Pages\ListUtilityServices;
use App\Models\Organization;
use App\Models\User;
use App\Models\UtilityService;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows scoped and global utility services with clear context on the list page', function () {
    $organizationA = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $organizationB = Organization::factory()->create([
        'name' => 'Aurora Towers',
    ]);

    $ownedService = UtilityService::factory()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Northwind Electricity',
        'service_type_bridge' => ServiceType::ELECTRICITY,
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'is_global_template' => false,
        'is_active' => true,
    ]);

    $globalTemplate = UtilityService::factory()->globalTemplate()->create([
        'name' => 'Global Water Template',
        'service_type_bridge' => ServiceType::WATER,
        'default_pricing_model' => PricingModel::FLAT,
        'is_active' => true,
    ]);

    $foreignService = UtilityService::factory()->create([
        'organization_id' => $organizationB->id,
        'name' => 'Aurora Heating',
        'service_type_bridge' => ServiceType::HEATING,
        'default_pricing_model' => PricingModel::FIXED_MONTHLY,
        'is_global_template' => false,
        'is_active' => false,
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.utility-services.index'))
        ->assertSuccessful()
        ->assertSeeText('Utility Services')
        ->assertSeeText($ownedService->name)
        ->assertSeeText($globalTemplate->name)
        ->assertDontSeeText($foreignService->name);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.utility-services.index'))
        ->assertSuccessful()
        ->assertSeeText('Utility Services')
        ->assertSeeText($ownedService->name)
        ->assertSeeText($globalTemplate->name)
        ->assertSeeText($foreignService->name)
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name);

    $this->actingAs($superadmin);

    Livewire::test(ListUtilityServices::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableColumnExists('service_type_bridge', fn (TextColumn $column): bool => $column->getLabel() === 'Service Type')
        ->assertTableColumnExists('service_configurations_count', fn (TextColumn $column): bool => $column->getLabel() === 'Service Configurations')
        ->assertTableColumnExists('is_global_template', fn (IconColumn $column): bool => $column->getLabel() === 'Template')
        ->assertTableColumnExists('is_active', fn (IconColumn $column): bool => $column->getLabel() === 'Active')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertTableFilterExists('service_type_bridge', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Service Type')
        ->assertTableFilterExists('is_global_template', fn (TernaryFilter $filter): bool => $filter->getLabel() === 'Template')
        ->assertTableFilterExists('is_active', fn (TernaryFilter $filter): bool => $filter->getLabel() === 'Active')
        ->assertTableColumnStateSet('organization.name', $organizationA->name, $ownedService)
        ->assertTableColumnStateSet('service_type_bridge', ServiceType::ELECTRICITY->getLabel(), $ownedService)
        ->assertTableColumnStateSet('default_pricing_model', PricingModel::CONSUMPTION_BASED->getLabel(), $ownedService)
        ->assertCanSeeTableRecords([$ownedService, $globalTemplate, $foreignService])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$ownedService])
        ->assertCanNotSeeTableRecords([$globalTemplate, $foreignService])
        ->resetTableFilters()
        ->filterTable('service_type_bridge', ServiceType::WATER->value)
        ->assertCanSeeTableRecords([$globalTemplate])
        ->assertCanNotSeeTableRecords([$ownedService, $foreignService])
        ->resetTableFilters()
        ->filterTable('is_global_template', true)
        ->assertCanSeeTableRecords([$globalTemplate])
        ->assertCanNotSeeTableRecords([$ownedService, $foreignService])
        ->resetTableFilters()
        ->filterTable('is_active', false)
        ->assertCanSeeTableRecords([$foreignService])
        ->assertCanNotSeeTableRecords([$ownedService, $globalTemplate]);
});

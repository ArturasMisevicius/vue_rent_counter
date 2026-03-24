<?php

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Providers\ProviderResource;
use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use App\Filament\Resources\UtilityServices\UtilityServiceResource;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('applies role-based view and mutation checks for core billing resources', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $provider = Provider::factory()->forOrganization($organization)->create();
    $foreignProvider = Provider::factory()->forOrganization($otherOrganization)->create();

    $utilityService = UtilityService::factory()->create([
        'organization_id' => $organization->id,
        'is_global_template' => false,
    ]);

    $foreignUtilityService = UtilityService::factory()->create([
        'organization_id' => $otherOrganization->id,
        'is_global_template' => false,
    ]);

    $property = Property::factory()->for($organization)->create();
    $foreignProperty = Property::factory()->for($otherOrganization)->create();

    $meter = Meter::factory()->for($organization)->for($property)->create();
    $foreignMeter = Meter::factory()->for($otherOrganization)->for($foreignProperty)->create();

    $meterReading = MeterReading::factory()->for($organization)->for($property)->for($meter)->create();
    $foreignMeterReading = MeterReading::factory()->for($otherOrganization)->for($foreignProperty)->for($foreignMeter)->create();

    $serviceConfiguration = ServiceConfiguration::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $foreignServiceConfiguration = ServiceConfiguration::factory()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    actingAs($admin);

    expect(ProviderResource::canViewAny())->toBeTrue()
        ->and(ProviderResource::canView($provider))->toBeTrue()
        ->and(ProviderResource::canDelete($provider))->toBeTrue()
        ->and(ProviderResource::canView($foreignProvider))->toBeFalse()
        ->and(UtilityServiceResource::canViewAny())->toBeTrue()
        ->and(UtilityServiceResource::canEdit($utilityService))->toBeTrue()
        ->and(UtilityServiceResource::canView($foreignUtilityService))->toBeFalse()
        ->and(ServiceConfigurationResource::canViewAny())->toBeTrue()
        ->and(ServiceConfigurationResource::canEdit($serviceConfiguration))->toBeTrue()
        ->and(ServiceConfigurationResource::canView($foreignServiceConfiguration))->toBeFalse()
        ->and(PropertyResource::canViewAny())->toBeTrue()
        ->and(PropertyResource::canEdit($property))->toBeTrue()
        ->and(PropertyResource::canView($foreignProperty))->toBeFalse()
        ->and(MeterResource::canViewAny())->toBeTrue()
        ->and(MeterResource::canDelete($meter))->toBeTrue()
        ->and(MeterResource::canView($foreignMeter))->toBeFalse()
        ->and(MeterReadingResource::canViewAny())->toBeTrue()
        ->and(MeterReadingResource::canDelete($meterReading))->toBeTrue()
        ->and(MeterReadingResource::canView($foreignMeterReading))->toBeFalse();

    actingAs($manager);

    expect(ProviderResource::canViewAny())->toBeTrue()
        ->and(ProviderResource::canEdit($provider))->toBeTrue()
        ->and(ServiceConfigurationResource::canDelete($serviceConfiguration))->toBeTrue()
        ->and(MeterReadingResource::canDelete($meterReading))->toBeTrue();

    actingAs($tenant);

    expect(ProviderResource::canViewAny())->toBeFalse()
        ->and(UtilityServiceResource::canViewAny())->toBeFalse()
        ->and(ServiceConfigurationResource::canViewAny())->toBeFalse()
        ->and(MeterResource::canViewAny())->toBeFalse()
        ->and(MeterReadingResource::canViewAny())->toBeFalse();
});

it('allows invoice viewing for tenant role while restricting mutations by role', function () {
    $organization = Organization::factory()->create();

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $invoice = Invoice::factory()->create([
        'organization_id' => $organization->id,
        'tenant_user_id' => $tenant->id,
    ]);

    actingAs($tenant);

    expect(InvoiceResource::canViewAny())->toBeTrue()
        ->and(InvoiceResource::canView($invoice))->toBeTrue()
        ->and(InvoiceResource::canEdit($invoice))->toBeFalse()
        ->and(InvoiceResource::canDelete($invoice))->toBeFalse();
});

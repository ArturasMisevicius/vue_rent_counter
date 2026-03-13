<?php

use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\MeterReadingResource;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('Filament rejects missing meter_id using native form testing', function () {
    $tenantId = 1;
    
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => '123 Test St',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    Livewire::test(MeterReadingResource\Pages\CreateMeterReading::class)
        ->fillForm([
            'property_id' => $property->id,
            // meter_id is missing
            'reading_date' => now()->format('Y-m-d'),
            'value' => 100.0,
        ])
        ->call('create')
        ->assertHasFormErrors(['meter_id']);
});

test('Filament rejects negative value using native form testing', function () {
    $tenantId = 1;
    
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => '123 Test St',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'serial_number' => 'METER-1234',
        'type' => MeterType::ELECTRICITY,
        'installation_date' => now()->subYear(),
        'supports_zones' => false,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    Livewire::test(MeterReadingResource\Pages\CreateMeterReading::class)
        ->fillForm([
            'property_id' => $property->id,
            'meter_id' => $meter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => -10.0, // Negative value
        ])
        ->call('create')
        ->assertHasFormErrors(['value']);
});

test('Filament rejects monotonicity violation using native form testing', function () {
    $tenantId = 1;
    
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => '123 Test St',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'serial_number' => 'METER-1234',
        'type' => MeterType::ELECTRICITY,
        'installation_date' => now()->subYear(),
        'supports_zones' => false,
    ]);
    
    // Create previous reading
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(30),
        'value' => 500.0,
        'entered_by' => $manager->id,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    Livewire::test(MeterReadingResource\Pages\CreateMeterReading::class)
        ->fillForm([
            'property_id' => $property->id,
            'meter_id' => $meter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 400.0, // Lower than previous
        ])
        ->call('create')
        ->assertHasFormErrors(['value']);
});

test('Filament accepts valid meter reading using native form testing', function () {
    $tenantId = 1;
    
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => '123 Test St',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'serial_number' => 'METER-1234',
        'type' => MeterType::ELECTRICITY,
        'installation_date' => now()->subYear(),
        'supports_zones' => false,
    ]);
    
    // Create previous reading
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(30),
        'value' => 500.0,
        'entered_by' => $manager->id,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    Livewire::test(MeterReadingResource\Pages\CreateMeterReading::class)
        ->fillForm([
            'property_id' => $property->id,
            'meter_id' => $meter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 600.0, // Higher than previous - valid
        ])
        ->call('create')
        ->assertHasNoFormErrors();
        
    expect(MeterReading::count())->toBe(2);
});

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

// Feature: filament-admin-panel, Property 1: Tenant scope isolation for meter readings
// Validates: Requirements 2.1, 2.7
test('MeterReadingResource list page filters by tenant scope - simple test', function () {
    // Create two tenants
    $tenantId1 = 1;
    $tenantId2 = 2;
    
    // Create property and meter for tenant 1
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => '123 Main St',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $meter1 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'property_id' => $property1->id,
        'serial_number' => 'METER-001',
        'type' => MeterType::ELECTRICITY,
        'installation_date' => now()->subYear(),
        'supports_zones' => false,
    ]);
    
    // Create meter reading for tenant 1
    $reading1 = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'meter_id' => $meter1->id,
        'reading_date' => now()->subDays(1),
        'value' => 100.50,
        'entered_by' => null,
    ]);
    
    // Create property and meter for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => '456 Oak Ave',
        'type' => PropertyType::HOUSE,
        'area_sqm' => 100.0,
    ]);
    
    $meter2 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property2->id,
        'serial_number' => 'METER-002',
        'type' => MeterType::WATER_COLD,
        'installation_date' => now()->subYear(),
        'supports_zones' => false,
    ]);
    
    // Create meter reading for tenant 2
    $reading2 = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'meter_id' => $meter2->id,
        'reading_date' => now()->subDays(1),
        'value' => 200.75,
        'entered_by' => null,
    ]);
    
    // Create manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Test: Only tenant 1's meter readings should be visible
    $component = Livewire::test(MeterReadingResource\Pages\ListMeterReadings::class);
    
    $component->assertSuccessful();
    
    $tableRecords = $component->instance()->getTableRecords();
    
    // Should only see tenant 1's reading
    expect($tableRecords)->toHaveCount(1);
    expect($tableRecords->first()->id)->toBe($reading1->id);
    expect($tableRecords->first()->tenant_id)->toBe($tenantId1);
    
    // Tenant 2's reading should not be accessible
    expect(MeterReading::find($reading2->id))->toBeNull();
});

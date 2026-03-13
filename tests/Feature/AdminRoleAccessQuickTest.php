<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\BuildingResource;
use App\Models\Property;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin with tenant_id can see navigation', function () {
    $tenantId = 1;
    
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    $this->actingAs($adminUser);
    
    expect(PropertyResource::shouldRegisterNavigation())->toBeTrue();
    expect(BuildingResource::shouldRegisterNavigation())->toBeTrue();
});

test('admin with tenant_id can create resources', function () {
    $tenantId = 1;
    
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    $this->actingAs($adminUser);
    
    expect(PropertyResource::canViewAny())->toBeTrue();
    expect(PropertyResource::canCreate())->toBeTrue();
    expect(BuildingResource::canViewAny())->toBeTrue();
    expect(BuildingResource::canCreate())->toBeTrue();
});

test('admin can view resources within their tenant', function () {
    $tenantId = 1;
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => 'Test Address',
        'type' => 'apartment',
        'area_sqm' => 50.0,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => 'Test Building',
        'total_apartments' => 10,
    ]);
    
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    $this->actingAs($adminUser);
    
    expect($adminUser->can('view', $property))->toBeTrue();
    expect($adminUser->can('update', $property))->toBeTrue();
    expect($adminUser->can('view', $building))->toBeTrue();
    expect($adminUser->can('update', $building))->toBeTrue();
});

test('admin cannot view resources from other tenants', function () {
    $adminTenantId = 1;
    $otherTenantId = 2;
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $otherTenantId,
        'address' => 'Other Tenant Address',
        'type' => 'apartment',
        'area_sqm' => 50.0,
    ]);
    
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $adminTenantId,
    ]);
    
    $this->actingAs($adminUser);
    
    // Admin should NOT be able to view resources from other tenants
    expect($adminUser->can('view', $property))->toBeFalse();
});

<?php

declare(strict_types=1);

use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use App\Services\TenantBoundaryService;

it('integrates TenantBoundaryService with MeterReadingPolicy correctly', function () {
    // Create users in different tenants
    $tenant1User = User::factory()->create(['tenant_id' => 100]);
    $tenant1User->assignRole('manager');
    
    $tenant2User = User::factory()->create(['tenant_id' => 200]);
    $tenant2User->assignRole('manager');
    
    $superadmin = User::factory()->create(['tenant_id' => 100]);
    $superadmin->assignRole('superadmin');
    
    // Create meter readings in different tenants
    $tenant1Reading = MeterReading::factory()->create(['tenant_id' => 100]);
    $tenant2Reading = MeterReading::factory()->create(['tenant_id' => 200]);
    
    // Test tenant boundary service
    $service = app(TenantBoundaryService::class);
    
    // Tenant 1 user can access their tenant's reading
    expect($service->canAccessModel($tenant1User, $tenant1Reading))->toBeTrue();
    
    // Tenant 1 user cannot access tenant 2's reading
    expect($service->canAccessModel($tenant1User, $tenant2Reading))->toBeFalse();
    
    // Superadmin can access any reading
    expect($service->canAccessModel($superadmin, $tenant1Reading))->toBeTrue();
    expect($service->canAccessModel($superadmin, $tenant2Reading))->toBeTrue();
    
    // Test policy integration
    $policy = app(\App\Policies\MeterReadingPolicy::class);
    
    // Manager can view readings in their tenant
    expect($policy->view($tenant1User, $tenant1Reading))->toBeTrue();
    expect($policy->view($tenant1User, $tenant2Reading))->toBeFalse();
    
    // Superadmin can view any reading
    expect($policy->view($superadmin, $tenant1Reading))->toBeTrue();
    expect($policy->view($superadmin, $tenant2Reading))->toBeTrue();
});

it('prevents cross-tenant access in property policy', function () {
    $tenant1User = User::factory()->create(['tenant_id' => 100]);
    $tenant1User->assignRole('admin');
    
    $tenant2User = User::factory()->create(['tenant_id' => 200]);
    $tenant2User->assignRole('admin');
    
    $property1 = Property::factory()->create(['tenant_id' => 100]);
    $property2 = Property::factory()->create(['tenant_id' => 200]);
    
    $policy = app(\App\Policies\PropertyPolicy::class);
    
    // Users can only access properties in their tenant
    expect($policy->view($tenant1User, $property1))->toBeTrue();
    expect($policy->view($tenant1User, $property2))->toBeFalse();
    
    expect($policy->view($tenant2User, $property2))->toBeTrue();
    expect($policy->view($tenant2User, $property1))->toBeFalse();
});

it('validates tenant boundary service role checks', function () {
    $service = app(TenantBoundaryService::class);
    
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');
    
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    
    $tenant = User::factory()->create();
    $tenant->assignRole('tenant');
    
    // Test admin operations
    expect($service->canPerformAdminOperations($superadmin))->toBeTrue();
    expect($service->canPerformAdminOperations($admin))->toBeTrue();
    expect($service->canPerformAdminOperations($manager))->toBeFalse();
    expect($service->canPerformAdminOperations($tenant))->toBeFalse();
    
    // Test manager operations
    expect($service->canPerformManagerOperations($superadmin))->toBeTrue();
    expect($service->canPerformManagerOperations($admin))->toBeTrue();
    expect($service->canPerformManagerOperations($manager))->toBeTrue();
    expect($service->canPerformManagerOperations($tenant))->toBeFalse();
});

it('handles meter reading finalization with tenant boundaries', function () {
    $manager = User::factory()->create(['tenant_id' => 100]);
    $manager->assignRole('manager');
    
    $otherTenantManager = User::factory()->create(['tenant_id' => 200]);
    $otherTenantManager->assignRole('manager');
    
    $reading = MeterReading::factory()->create([
        'tenant_id' => 100,
        'is_finalized' => false
    ]);
    
    $policy = app(\App\Policies\MeterReadingPolicy::class);
    
    // Manager can finalize reading in their tenant
    $result = $policy->finalize($manager, $reading);
    expect($result->allowed())->toBeTrue();
    
    // Manager cannot finalize reading in different tenant
    $result = $policy->finalize($otherTenantManager, $reading);
    expect($result->denied())->toBeTrue();
    
    // Cannot finalize already finalized reading
    $reading->is_finalized = true;
    $result = $policy->finalize($manager, $reading);
    expect($result->denied())->toBeTrue();
    expect($result->message())->toContain('already finalized');
});
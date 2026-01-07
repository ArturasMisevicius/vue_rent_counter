<?php

declare(strict_types=1);

use App\Services\TenantBoundaryService;

it('registers TenantBoundaryService as singleton', function () {
    $service1 = app(TenantBoundaryService::class);
    $service2 = app(TenantBoundaryService::class);
    
    // Should be the same instance (singleton)
    expect($service1)->toBe($service2);
});

it('can resolve TenantBoundaryService from container', function () {
    $service = app(TenantBoundaryService::class);
    
    expect($service)->toBeInstanceOf(TenantBoundaryService::class);
});

it('TenantBoundaryService has required methods', function () {
    $service = app(TenantBoundaryService::class);
    
    expect($service)->toHaveMethod('canAccessTenant');
    expect($service)->toHaveMethod('canAccessModel');
    expect($service)->toHaveMethod('canCreateForCurrentTenant');
    expect($service)->toHaveMethod('canPerformAdminOperations');
    expect($service)->toHaveMethod('canPerformManagerOperations');
});
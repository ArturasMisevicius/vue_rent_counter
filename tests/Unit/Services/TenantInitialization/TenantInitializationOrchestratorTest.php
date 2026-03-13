<?php

declare(strict_types=1);

use App\Data\TenantInitialization\InitializationResult;
use App\Models\Organization;
use App\Services\TenantInitialization\TenantInitializationOrchestrator;
use App\Services\TenantInitialization\Validators\TenantInitializationValidator;
use App\Services\TenantInitialization\Commands\InitializeUniversalServicesCommand;
use App\Services\TenantInitialization\PropertyServiceAssigner;
use App\Services\TenantInitialization\Repositories\UtilityServiceRepositoryInterface;
use App\Exceptions\TenantInitializationException;

beforeEach(function () {
    $this->validator = Mockery::mock(TenantInitializationValidator::class);
    $this->initializeCommand = Mockery::mock(InitializeUniversalServicesCommand::class);
    $this->propertyAssigner = Mockery::mock(PropertyServiceAssigner::class);
    $this->repository = Mockery::mock(UtilityServiceRepositoryInterface::class);
    
    $this->orchestrator = new TenantInitializationOrchestrator(
        $this->validator,
        $this->initializeCommand,
        $this->propertyAssigner,
        $this->repository
    );
});

it('initializes universal services successfully', function () {
    $tenant = Organization::factory()->make(['id' => 1]);
    $result = Mockery::mock(InitializationResult::class);
    
    $this->validator->shouldReceive('validateForInitialization')
        ->once()
        ->with($tenant);
    
    $this->initializeCommand->shouldReceive('execute')
        ->once()
        ->with($tenant)
        ->andReturn($result);
    
    $actualResult = $this->orchestrator->initializeUniversalServices($tenant);
    
    expect($actualResult)->toBe($result);
});

it('throws exception when validation fails', function () {
    $tenant = Organization::factory()->make(['id' => 1]);
    
    $this->validator->shouldReceive('validateForInitialization')
        ->once()
        ->with($tenant)
        ->andThrow(new TenantInitializationException('Validation failed'));
    
    expect(fn() => $this->orchestrator->initializeUniversalServices($tenant))
        ->toThrow(TenantInitializationException::class, 'Validation failed');
});

it('checks heating compatibility successfully', function () {
    $tenant = Organization::factory()->make(['id' => 1]);
    $heatingService = Mockery::mock(\App\Models\UtilityService::class);
    $heatingService->service_type_bridge = \App\Enums\ServiceType::HEATING;
    $heatingService->default_pricing_model = \App\Enums\PricingModel::HYBRID;
    $heatingService->business_logic_config = ['supports_shared_distribution' => true];
    
    $this->validator->shouldReceive('validateForCompatibilityCheck')
        ->once()
        ->with($tenant);
    
    $this->repository->shouldReceive('findHeatingService')
        ->once()
        ->with($tenant)
        ->andReturn($heatingService);
    
    $result = $this->orchestrator->ensureHeatingCompatibility($tenant);
    
    expect($result)->toBeTrue();
});

it('returns false when no heating service found', function () {
    $tenant = Organization::factory()->make(['id' => 1]);
    
    $this->validator->shouldReceive('validateForCompatibilityCheck')
        ->once()
        ->with($tenant);
    
    $this->repository->shouldReceive('findHeatingService')
        ->once()
        ->with($tenant)
        ->andReturn(null);
    
    $result = $this->orchestrator->ensureHeatingCompatibility($tenant);
    
    expect($result)->toBeFalse();
});
<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\UtilityService;
use App\Services\TenantInitialization\Factories\UtilityServiceFactory;
use App\Services\TenantInitialization\Contracts\ServiceCreationStrategyInterface;
use App\Exceptions\TenantInitializationException;

beforeEach(function () {
    $this->strategy1 = Mockery::mock(ServiceCreationStrategyInterface::class);
    $this->strategy2 = Mockery::mock(ServiceCreationStrategyInterface::class);
    
    $this->factory = new UtilityServiceFactory(
        collect([$this->strategy1, $this->strategy2])
    );
});

it('creates service using appropriate strategy', function () {
    $tenant = Organization::factory()->make(['id' => 1]);
    $definition = ['service_type_bridge' => 'electricity', 'name' => 'Electricity'];
    $service = UtilityService::factory()->make();
    
    $this->strategy1->shouldReceive('canHandle')
        ->once()
        ->with($definition)
        ->andReturn(false);
    
    $this->strategy2->shouldReceive('canHandle')
        ->once()
        ->with($definition)
        ->andReturn(true);
    
    $this->strategy2->shouldReceive('createService')
        ->once()
        ->with($tenant, 'electricity', $definition)
        ->andReturn($service);
    
    $result = $this->factory->createService($tenant, 'electricity', $definition);
    
    expect($result)->toBe($service);
});

it('throws exception when no strategy can handle definition', function () {
    $tenant = Organization::factory()->make(['id' => 1]);
    $definition = ['invalid' => 'definition'];
    
    $this->strategy1->shouldReceive('canHandle')
        ->once()
        ->with($definition)
        ->andReturn(false);
    
    $this->strategy2->shouldReceive('canHandle')
        ->once()
        ->with($definition)
        ->andReturn(false);
    
    expect(fn() => $this->factory->createService($tenant, 'test', $definition))
        ->toThrow(TenantInitializationException::class);
});

it('creates multiple services in batch', function () {
    $tenant = Organization::factory()->make(['id' => 1]);
    $definitions = [
        'electricity' => ['service_type_bridge' => 'electricity', 'name' => 'Electricity'],
        'water' => ['service_type_bridge' => 'water', 'name' => 'Water'],
    ];
    
    $electricityService = UtilityService::factory()->make();
    $waterService = UtilityService::factory()->make();
    
    $this->strategy1->shouldReceive('canHandle')->twice()->andReturn(true);
    $this->strategy1->shouldReceive('createService')
        ->with($tenant, 'electricity', $definitions['electricity'])
        ->andReturn($electricityService);
    $this->strategy1->shouldReceive('createService')
        ->with($tenant, 'water', $definitions['water'])
        ->andReturn($waterService);
    
    $result = $this->factory->createBatch($tenant, $definitions);
    
    expect($result)->toHaveCount(2);
    expect($result['electricity'])->toBe($electricityService);
    expect($result['water'])->toBe($waterService);
});
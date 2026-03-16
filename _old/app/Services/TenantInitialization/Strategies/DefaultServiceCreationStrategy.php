<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization\Strategies;

use App\Models\Organization;
use App\Models\UtilityService;
use App\Services\TenantInitialization\Contracts\ServiceCreationStrategyInterface;
use App\Services\TenantInitialization\SlugGeneratorService;

/**
 * Creates utility services from scratch using default configurations.
 * 
 * This strategy creates new utility services when no global templates
 * are available, using the provided service definitions.
 */
final readonly class DefaultServiceCreationStrategy implements ServiceCreationStrategyInterface
{
    public function __construct(
        private SlugGeneratorService $slugGenerator,
    ) {}

    public function createService(
        Organization $tenant, 
        string $serviceKey, 
        array $definition
    ): UtilityService {
        return UtilityService::create([
            'tenant_id' => $tenant->id,
            'name' => $definition['name'],
            'slug' => $this->slugGenerator->generateUniqueSlug($definition['name'], $tenant->id),
            'unit_of_measurement' => $definition['unit_of_measurement'],
            'default_pricing_model' => $definition['default_pricing_model'],
            'calculation_formula' => $definition['calculation_formula'] ?? null,
            'is_global_template' => false,
            'created_by_tenant_id' => $tenant->id,
            'configuration_schema' => $definition['configuration_schema'] ?? [],
            'validation_rules' => $definition['validation_rules'] ?? [],
            'business_logic_config' => $definition['business_logic_config'] ?? [],
            'service_type_bridge' => $definition['service_type_bridge'],
            'description' => $definition['description'] ?? null,
            'is_active' => true,
        ]);
    }

    public function canHandle(array $definition): bool
    {
        return isset($definition['service_type_bridge'], $definition['name']);
    }
}
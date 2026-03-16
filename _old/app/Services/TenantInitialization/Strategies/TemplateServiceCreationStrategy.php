<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization\Strategies;

use App\Models\Organization;
use App\Models\UtilityService;
use App\Services\TenantInitialization\Contracts\ServiceCreationStrategyInterface;
use App\Services\TenantInitialization\Repositories\UtilityServiceRepositoryInterface;

/**
 * Creates utility services from global templates.
 * 
 * This strategy looks for existing global templates and creates
 * tenant-specific copies with customizations.
 */
final readonly class TemplateServiceCreationStrategy implements ServiceCreationStrategyInterface
{
    public function __construct(
        private UtilityServiceRepositoryInterface $repository,
    ) {}

    public function createService(
        Organization $tenant, 
        string $serviceKey, 
        array $definition
    ): UtilityService {
        $template = $this->repository->findGlobalTemplate($definition['service_type_bridge']);
        
        if (!$template) {
            throw new \InvalidArgumentException("No global template found for service type: {$definition['service_type_bridge']}");
        }

        return $template->createTenantCopy($tenant->id, [
            'name' => $definition['name'],
            'description' => $definition['description'] ?? null,
        ]);
    }

    public function canHandle(array $definition): bool
    {
        return isset($definition['service_type_bridge']) 
            && $this->repository->hasGlobalTemplate($definition['service_type_bridge']);
    }
}
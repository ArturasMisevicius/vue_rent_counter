<?php

declare(strict_types=1);

namespace App\Commands\TenantInitialization;

use App\Models\Organization;
use Illuminate\Support\Collection;

/**
 * Command for initializing property service assignments.
 */
final readonly class InitializePropertyAssignmentsCommand
{
    /**
     * @param Collection<string, \App\Models\UtilityService> $utilityServices
     */
    public function __construct(
        public Organization $tenant,
        public Collection $utilityServices,
        public array $propertyIds = [],
        public array $options = [],
    ) {}

    /**
     * Create command for all tenant properties.
     */
    public static function forAllProperties(Organization $tenant, Collection $utilityServices): self
    {
        return new self($tenant, $utilityServices);
    }

    /**
     * Create command for specific properties.
     */
    public static function forProperties(Organization $tenant, Collection $utilityServices, array $propertyIds): self
    {
        return new self($tenant, $utilityServices, $propertyIds);
    }

    /**
     * Check if should process all properties.
     */
    public function shouldProcessAllProperties(): bool
    {
        return empty($this->propertyIds);
    }

    /**
     * Get target property IDs.
     */
    public function getTargetPropertyIds(): array
    {
        return $this->propertyIds;
    }
}
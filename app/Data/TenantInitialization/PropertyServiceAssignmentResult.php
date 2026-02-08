<?php

declare(strict_types=1);

namespace App\Data\TenantInitialization;

use App\Models\ServiceConfiguration;
use Illuminate\Support\Collection;

/**
 * Data Transfer Object for property service assignment results.
 * 
 * Contains the results of assigning utility services to properties,
 * providing convenient access methods for retrieving configurations
 * by property and service type.
 * 
 * @package App\Data\TenantInitialization
 * @author Laravel Development Team
 * @since 1.0.0
 */
final readonly class PropertyServiceAssignmentResult
{
    /**
     * @param Collection<int, Collection<string, ServiceConfiguration>> $configurations Service configurations grouped by property ID
     */
    public function __construct(
        private Collection $configurations,
    ) {}

    /**
     * Create from array of configurations.
     * 
     * @param array<int, array<string, ServiceConfiguration>> $configurations
     */
    public static function fromArray(array $configurations): self
    {
        $collection = collect($configurations)->map(fn($configs) => collect($configs));
        
        return new self($collection);
    }

    /**
     * Get the number of properties configured.
     */
    public function getPropertyCount(): int
    {
        return $this->configurations->count();
    }

    /**
     * Get the total number of service configurations created.
     */
    public function getTotalConfigurationCount(): int
    {
        return $this->configurations->sum(fn($configs) => $configs->count());
    }

    /**
     * Get service configurations for a specific property.
     * 
     * @return Collection<string, ServiceConfiguration>|null
     */
    public function getPropertyConfigurations(int $propertyId): ?Collection
    {
        return $this->configurations->get($propertyId);
    }

    /**
     * Get a specific service configuration for a property.
     */
    public function getPropertyServiceConfiguration(int $propertyId, string $serviceType): ?ServiceConfiguration
    {
        return $this->configurations->get($propertyId)?->get($serviceType);
    }

    /**
     * Get all property IDs that have configurations.
     * 
     * @return array<int>
     */
    public function getPropertyIds(): array
    {
        return $this->configurations->keys()->toArray();
    }

    /**
     * Check if any configurations exist.
     */
    public function hasConfigurations(): bool
    {
        return $this->configurations->isNotEmpty();
    }

    /**
     * Check if a specific property has configurations.
     */
    public function hasPropertyConfigurations(int $propertyId): bool
    {
        return $this->configurations->has($propertyId);
    }

    /**
     * Get all service types configured across all properties.
     * 
     * @return array<string>
     */
    public function getAllServiceTypes(): array
    {
        return $this->configurations
            ->flatMap(fn($configs) => $configs->keys())
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Convert to array representation.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'configurations' => $this->configurations->map(fn($configs) => $configs->toArray())->toArray(),
            'property_count' => $this->getPropertyCount(),
            'total_configuration_count' => $this->getTotalConfigurationCount(),
            'property_ids' => $this->getPropertyIds(),
            'service_types' => $this->getAllServiceTypes(),
        ];
    }
}
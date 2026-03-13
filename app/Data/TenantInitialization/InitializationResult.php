<?php

declare(strict_types=1);

namespace App\Data\TenantInitialization;

use App\Models\UtilityService;
use Illuminate\Support\Collection;

/**
 * Data Transfer Object for tenant initialization results.
 * 
 * Contains the results of tenant initialization including created utility services
 * and meter configurations. Provides convenient access methods for retrieving
 * specific services and configurations.
 * 
 * @package App\Data\TenantInitialization
 * @author Laravel Development Team
 * @since 1.0.0
 */
final readonly class InitializationResult
{
    /**
     * @param Collection<string, UtilityService> $utilityServices Collection of created utility services keyed by service type
     * @param Collection<string, array<string, mixed>> $meterConfigurations Collection of meter configurations keyed by service type
     */
    public function __construct(
        public Collection $utilityServices,
        public Collection $meterConfigurations,
    ) {}

    /**
     * Get the number of utility services created.
     */
    public function getServiceCount(): int
    {
        return $this->utilityServices->count();
    }

    /**
     * Get the number of meter configurations created.
     */
    public function getMeterConfigurationCount(): int
    {
        return $this->meterConfigurations->count();
    }

    /**
     * Get a specific utility service by service type.
     */
    public function getUtilityService(string $serviceType): ?UtilityService
    {
        return $this->utilityServices->get($serviceType);
    }

    /**
     * Get a specific meter configuration by service type.
     * 
     * @return array<string, mixed>|null
     */
    public function getMeterConfiguration(string $serviceType): ?array
    {
        return $this->meterConfigurations->get($serviceType);
    }

    /**
     * Check if a specific service type was created.
     */
    public function hasService(string $serviceType): bool
    {
        return $this->utilityServices->has($serviceType);
    }

    /**
     * Get all service types that were created.
     * 
     * @return array<string>
     */
    public function getServiceTypes(): array
    {
        return $this->utilityServices->keys()->toArray();
    }

    /**
     * Convert to array representation.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'utility_services' => $this->utilityServices->toArray(),
            'meter_configurations' => $this->meterConfigurations->toArray(),
            'service_count' => $this->getServiceCount(),
            'meter_configuration_count' => $this->getMeterConfigurationCount(),
        ];
    }
}
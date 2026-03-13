<?php

declare(strict_types=1);

namespace App\Commands\TenantInitialization;

use App\Data\TenantInitialization\InitializationResult;
use App\Models\Organization;

/**
 * Command for initializing universal services for a tenant.
 * 
 * Encapsulates the data and intent for service initialization,
 * following the Command pattern for better separation of concerns.
 */
final readonly class InitializeUniversalServicesCommand
{
    public function __construct(
        public Organization $tenant,
        public array $serviceTypes = ['electricity', 'water', 'heating', 'gas'],
        public array $options = [],
    ) {}

    /**
     * Create command with default service types.
     */
    public static function forTenant(Organization $tenant): self
    {
        return new self($tenant);
    }

    /**
     * Create command with specific service types.
     */
    public static function withServices(Organization $tenant, array $serviceTypes): self
    {
        return new self($tenant, $serviceTypes);
    }

    /**
     * Create command with options.
     */
    public function withOptions(array $options): self
    {
        return new self($this->tenant, $this->serviceTypes, $options);
    }

    /**
     * Check if service type should be initialized.
     */
    public function shouldInitializeService(string $serviceType): bool
    {
        return in_array($serviceType, $this->serviceTypes, true);
    }

    /**
     * Get initialization options.
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }
}
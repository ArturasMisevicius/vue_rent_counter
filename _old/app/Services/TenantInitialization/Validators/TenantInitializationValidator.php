<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization\Validators;

use App\Exceptions\TenantInitializationException;
use App\Models\Organization;
use App\Services\TenantInitialization\Repositories\UtilityServiceRepositoryInterface;

/**
 * Validator for tenant initialization operations.
 * 
 * Provides comprehensive validation for different initialization scenarios
 * with specific error messages and context.
 */
final readonly class TenantInitializationValidator
{
    public function __construct(
        private UtilityServiceRepositoryInterface $utilityServiceRepository,
    ) {}

    /**
     * Validate tenant for service initialization.
     * 
     * @param Organization $tenant The tenant to validate
     * 
     * @throws TenantInitializationException If validation fails
     */
    public function validateForInitialization(Organization $tenant): void
    {
        $this->validateBasicTenantData($tenant);
        
        if ($this->utilityServiceRepository->tenantHasServices($tenant)) {
            throw TenantInitializationException::invalidTenantData(
                $tenant,
                'Tenant already has utility services initialized'
            );
        }
    }

    /**
     * Validate tenant for property assignment.
     * 
     * @param Organization $tenant The tenant to validate
     * 
     * @throws TenantInitializationException If validation fails
     */
    public function validateForPropertyAssignment(Organization $tenant): void
    {
        $this->validateBasicTenantData($tenant);
        
        if (!$this->utilityServiceRepository->tenantHasServices($tenant)) {
            throw TenantInitializationException::invalidTenantData(
                $tenant,
                'Tenant must have utility services before property assignment'
            );
        }
    }

    /**
     * Validate tenant for compatibility check.
     * 
     * @param Organization $tenant The tenant to validate
     * 
     * @throws TenantInitializationException If validation fails
     */
    public function validateForCompatibilityCheck(Organization $tenant): void
    {
        $this->validateBasicTenantData($tenant);
    }

    /**
     * Validate basic tenant data requirements.
     * 
     * @param Organization $tenant The tenant to validate
     * 
     * @throws TenantInitializationException If validation fails
     */
    private function validateBasicTenantData(Organization $tenant): void
    {
        if (!$tenant->exists) {
            throw TenantInitializationException::invalidTenantData(
                $tenant,
                'Tenant must be persisted to database'
            );
        }

        if (empty($tenant->name)) {
            throw TenantInitializationException::invalidTenantData(
                $tenant,
                'Tenant name is required'
            );
        }

        if (!$tenant->id) {
            throw TenantInitializationException::invalidTenantData(
                $tenant,
                'Tenant ID is required'
            );
        }
    }
}
<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization;

use App\Exceptions\TenantInitializationException;
use App\Models\Organization;

/**
 * Validator for tenant data before initialization operations.
 * 
 * Provides centralized validation logic for tenant initialization
 * to ensure data integrity and prevent initialization failures.
 * 
 * @package App\Services\TenantInitialization
 * @author Laravel Development Team
 * @since 1.0.0
 */
final readonly class TenantValidator
{
    /**
     * Validate tenant data before initialization.
     * 
     * @throws TenantInitializationException If tenant data is invalid
     */
    public function validate(Organization $tenant): void
    {
        $this->validateTenantExists($tenant);
        $this->validateTenantName($tenant);
        $this->validateTenantId($tenant);
    }

    /**
     * Validate that tenant exists in database.
     * 
     * @throws TenantInitializationException
     */
    private function validateTenantExists(Organization $tenant): void
    {
        if (!$tenant->exists) {
            throw TenantInitializationException::invalidTenantData(
                $tenant, 
                'Tenant must be persisted to database'
            );
        }
    }

    /**
     * Validate that tenant has a name.
     * 
     * @throws TenantInitializationException
     */
    private function validateTenantName(Organization $tenant): void
    {
        if (empty($tenant->name)) {
            throw TenantInitializationException::invalidTenantData(
                $tenant, 
                'Tenant name is required'
            );
        }
    }

    /**
     * Validate that tenant has an ID.
     * 
     * @throws TenantInitializationException
     */
    private function validateTenantId(Organization $tenant): void
    {
        if (!$tenant->id) {
            throw TenantInitializationException::invalidTenantData(
                $tenant, 
                'Tenant ID is required'
            );
        }
    }

    /**
     * Validate tenant for specific operations.
     * 
     * @param array<string> $requiredFields Additional fields to validate
     * 
     * @throws TenantInitializationException
     */
    public function validateForOperation(Organization $tenant, array $requiredFields = []): void
    {
        $this->validate($tenant);

        foreach ($requiredFields as $field) {
            if (empty($tenant->{$field})) {
                throw TenantInitializationException::invalidTenantData(
                    $tenant,
                    "Required field '{$field}' is missing or empty"
                );
            }
        }
    }
}
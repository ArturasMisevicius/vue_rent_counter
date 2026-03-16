<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Organization;
use Exception;
use Throwable;

/**
 * Exception thrown when tenant initialization operations fail.
 * 
 * Provides specific factory methods for different types of initialization
 * failures with contextual information for debugging and logging.
 * 
 * @package App\Exceptions
 * @author Laravel Development Team
 * @since 1.0.0
 */
final class TenantInitializationException extends Exception
{
    /**
     * Create exception for service creation failure.
     */
    public static function serviceCreationFailed(
        Organization $tenant,
        string $serviceType,
        Throwable $previous = null
    ): self {
        $message = "Failed to create {$serviceType} service for tenant {$tenant->id} ({$tenant->name})";
        
        return new self($message, 0, $previous);
    }

    /**
     * Create exception for property assignment failure.
     */
    public static function propertyAssignmentFailed(
        Organization $tenant,
        Throwable $previous = null
    ): self {
        $message = "Failed to assign services to properties for tenant {$tenant->id} ({$tenant->name})";
        
        return new self($message, 0, $previous);
    }

    /**
     * Create exception for invalid tenant data.
     */
    public static function invalidTenantData(
        Organization $tenant,
        string $reason
    ): self {
        $message = "Invalid tenant data for tenant {$tenant->id}: {$reason}";
        
        return new self($message);
    }

    /**
     * Create exception for heating compatibility failure.
     */
    public static function heatingCompatibilityFailed(
        Organization $tenant,
        Throwable $previous = null
    ): self {
        $message = "Heating compatibility check failed for tenant {$tenant->id} ({$tenant->name})";
        
        return new self($message, 0, $previous);
    }

    /**
     * Create exception for meter configuration failure.
     */
    public static function meterConfigurationFailed(
        Organization $tenant,
        string $reason,
        Throwable $previous = null
    ): self {
        $message = "Meter configuration failed for tenant {$tenant->id} ({$tenant->name}): {$reason}";
        
        return new self($message, 0, $previous);
    }

    /**
     * Create exception for template creation failure.
     */
    public static function templateCreationFailed(
        Organization $tenant,
        string $templateType,
        Throwable $previous = null
    ): self {
        $message = "Failed to create {$templateType} template for tenant {$tenant->id} ({$tenant->name})";
        
        return new self($message, 0, $previous);
    }

    /**
     * Create exception for configuration validation failure.
     */
    public static function configurationValidationFailed(
        Organization $tenant,
        string $configType,
        array $errors
    ): self {
        $errorList = implode(', ', $errors);
        $message = "Configuration validation failed for {$configType} in tenant {$tenant->id}: {$errorList}";
        
        return new self($message);
    }

    /**
     * Create exception for dependency resolution failure.
     */
    public static function dependencyResolutionFailed(
        Organization $tenant,
        string $dependency,
        Throwable $previous = null
    ): self {
        $message = "Failed to resolve dependency '{$dependency}' for tenant {$tenant->id} ({$tenant->name})";
        
        return new self($message, 0, $previous);
    }
}
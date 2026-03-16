<?php

declare(strict_types=1);

namespace App\Contracts\ServiceRegistration;

/**
 * Policy Registry Contract
 * 
 * Defines the interface for policy and gate registration services.
 * Enables dependency injection and testing with mock implementations.
 */
interface PolicyRegistryInterface
{
    /**
     * Register all model policies with defensive error handling
     * 
     * @return array{registered: int, skipped: int, errors: array<string, string>}
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function registerModelPolicies(): array;

    /**
     * Register settings gates with comprehensive validation
     * 
     * @return array{registered: int, skipped: int, errors: array<string, string>}
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function registerSettingsGates(): array;

    /**
     * Get all registered model policies
     * 
     * @return array<class-string, class-string>
     */
    public function getModelPolicies(): array;

    /**
     * Get all registered settings gates
     * 
     * @return array<string, array{class-string, string}>
     */
    public function getSettingsGates(): array;

    /**
     * Validate the current policy and gate configuration
     * 
     * @return array{valid: bool, policies: array{valid: int, invalid: int, errors: array<string, string>}, gates: array{valid: int, invalid: int, errors: array<string, string>}}
     */
    public function validateConfiguration(): array;
}
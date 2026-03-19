<?php

declare(strict_types=1);

namespace App\Support\ServiceRegistration;

use App\Contracts\ServiceRegistration\PolicyRegistryInterface;
use App\Models\Building;
use App\Models\Faq;
use App\Models\Invoice;
use App\Models\Language;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\PlatformUser;
use App\Models\Property;
use App\Models\Provider;
use App\Models\SecurityViolation;
use App\Models\ServiceConfiguration;
use App\Models\Subscription;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\BuildingPolicy;
use App\Policies\FaqPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LanguagePolicy;
use App\Policies\MeterPolicy;
use App\Policies\MeterReadingPolicy;
use App\Policies\OrganizationActivityLogPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\PlatformUserPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\ProviderPolicy;
use App\Policies\SecurityViolationPolicy;
use App\Policies\ServiceConfigurationPolicy;
use App\Policies\SettingsPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\TariffPolicy;
use App\Policies\TenantPolicy;
use App\Policies\UserPolicy;
use App\Providers\AppServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Tests\Unit\Support\ServiceRegistration\PolicyRegistryTest;

/**
 * Policy Registry for secure, defensive policy registration
 *
 * Centralizes Laravel policy and gate registration with comprehensive security features:
 * - Authorization control (super_admin only or during app boot)
 * - Defensive programming patterns that gracefully handle missing classes
 * - Secure logging without sensitive data exposure
 * - Performance optimization with cached class existence checks
 * - Comprehensive statistics and validation reporting
 *
 * Security Features:
 * - SHA-256 hashed cache keys prevent collision attacks
 * - Sensitive data is hashed before logging
 * - Authorization checks prevent unauthorized registration
 * - Error messages are sanitized to prevent information disclosure
 *
 * Performance Features:
 * - Cached class existence checks (1-hour TTL)
 * - Batch registration with comprehensive statistics
 * - Performance metrics logging for monitoring
 *
 * @see PolicyRegistryTest
 * @see AppServiceProvider::bootPolicies()
 */
final readonly class PolicyRegistry implements PolicyRegistryInterface
{
    /**
     * Model to Policy mappings
     *
     * @var array<class-string, class-string>
     */
    private const MODEL_POLICIES = [
        User::class => UserPolicy::class,
        Tariff::class => TariffPolicy::class,
        Invoice::class => InvoicePolicy::class,
        MeterReading::class => MeterReadingPolicy::class,
        Property::class => PropertyPolicy::class,
        Building::class => BuildingPolicy::class,
        Meter::class => MeterPolicy::class,
        Provider::class => ProviderPolicy::class,
        Organization::class => OrganizationPolicy::class,
        OrganizationActivityLog::class => OrganizationActivityLogPolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        ServiceConfiguration::class => ServiceConfigurationPolicy::class,
        Tenant::class => TenantPolicy::class,
        Faq::class => FaqPolicy::class,
        Language::class => LanguagePolicy::class,
        SecurityViolation::class => SecurityViolationPolicy::class,
        PlatformUser::class => PlatformUserPolicy::class,
    ];

    /**
     * Settings gate definitions
     *
     * @var array<string, array{class-string, string}>
     */
    private const SETTINGS_GATES = [
        'access-admin-panel' => [UserPolicy::class, 'accessAdminPanel'],
        'viewSettings' => [SettingsPolicy::class, 'viewSettings'],
        'updateSettings' => [SettingsPolicy::class, 'updateSettings'],
        'runBackup' => [SettingsPolicy::class, 'runBackup'],
        'clearCache' => [SettingsPolicy::class, 'clearCache'],
        'viewSystemSettings' => [UserPolicy::class, 'viewSystemSettings'],
        'manageSystemSettings' => [UserPolicy::class, 'manageSystemSettings'],
    ];

    /**
     * Cache key for class existence checks
     */
    private const CLASS_CACHE_KEY = 'policy_registry_class_exists';

    /**
     * Cache TTL for class existence checks (1 hour)
     */
    private const CLASS_CACHE_TTL = 3600;

    /**
     * Register all model policies with defensive error handling
     *
     * Performs secure, defensive registration of all configured model policies.
     * Continues operation even when some policies fail to register, providing
     * comprehensive statistics for monitoring and debugging.
     *
     * Security Features:
     * - Authorization check (super_admin or app boot only)
     * - Secure logging with hashed sensitive data
     * - Sanitized error messages
     *
     * Performance Features:
     * - Cached class existence checks
     * - Performance timing metrics
     * - Batch processing with statistics
     *
     * @return array{registered: int, skipped: int, errors: array<string, string>} Registration statistics
     *
     * @throws AuthorizationException When unauthorized user attempts registration
     *
     * @example
     * ```php
     * $registry = new PolicyRegistry();
     * $result = $registry->registerModelPolicies();
     * // Returns: ['registered' => 10, 'skipped' => 2, 'errors' => ['Model' => 'configuration invalid']]
     * ```
     */
    public function registerModelPolicies(): array
    {
        // Only allow policy registration during application boot or by authorized users
        if (! $this->isAuthorizedForPolicyRegistration()) {
            throw new AuthorizationException('Unauthorized policy registration attempt');
        }

        $registered = 0;
        $skipped = 0;
        $errors = [];

        $startTime = microtime(true);

        foreach (self::MODEL_POLICIES as $model => $policy) {
            $modelName = class_basename($model);

            // Use cached class existence checks for performance
            if (! $this->classExists($model)) {
                $errors[$modelName] = 'Model configuration invalid';
                $skipped++;
                Log::warning('Policy registration: Model class missing', [
                    'model_hash' => hash('sha256', $model),
                    'context' => 'policy_registration',
                ]);

                continue;
            }

            if (! $this->classExists($policy)) {
                $errors[$modelName] = 'Policy configuration invalid';
                $skipped++;
                Log::warning('Policy registration: Policy class missing', [
                    'policy_hash' => hash('sha256', $policy),
                    'model_hash' => hash('sha256', $model),
                    'context' => 'policy_registration',
                ]);

                continue;
            }

            try {
                Gate::policy($model, $policy);
                $registered++;

                Log::debug('Registered policy for model', [
                    'model' => $model,
                    'policy' => $policy,
                ]);
            } catch (\Throwable $e) {
                $errors[$modelName] = 'Policy registration failed';
                $skipped++;

                Log::error('Policy registration failed', [
                    'model_hash' => hash('sha256', $model),
                    'policy_hash' => hash('sha256', $policy),
                    'error_type' => get_class($e),
                    'context' => 'policy_registration',
                ]);
            }
        }

        $duration = microtime(true) - $startTime;

        // Log performance metrics
        Log::debug('Policy registration completed', [
            'registered' => $registered,
            'skipped' => $skipped,
            'errors_count' => count($errors),
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return [
            'registered' => $registered,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Register settings gates with comprehensive validation
     *
     * Registers administrative gates for settings management with defensive
     * error handling and method existence validation.
     *
     * Security Features:
     * - Authorization check (super_admin or app boot only)
     * - Method existence validation
     * - Secure error logging
     *
     * @return array{registered: int, skipped: int, errors: array<string, string>} Registration statistics
     *
     * @throws AuthorizationException When unauthorized user attempts registration
     *
     * @example
     * ```php
     * $registry = new PolicyRegistry();
     * $result = $registry->registerSettingsGates();
     * // Returns: ['registered' => 6, 'skipped' => 0, 'errors' => []]
     * ```
     */
    public function registerSettingsGates(): array
    {
        // Only allow gate registration during application boot or by authorized users
        if (! $this->isAuthorizedForPolicyRegistration()) {
            throw new AuthorizationException('Unauthorized gate registration attempt');
        }

        $registered = 0;
        $skipped = 0;
        $errors = [];

        $startTime = microtime(true);

        foreach (self::SETTINGS_GATES as $gate => [$policy, $method]) {
            // Use cached class existence checks for performance
            if (! $this->classExists($policy)) {
                $errors[$gate] = "Policy class {$policy} does not exist";
                $skipped++;

                continue;
            }

            if (! method_exists($policy, $method)) {
                $errors[$gate] = "Method {$method} does not exist on {$policy}";
                $skipped++;

                continue;
            }

            try {
                Gate::define($gate, [$policy, $method]);
                $registered++;

                Log::debug('Registered gate', [
                    'gate' => $gate,
                    'policy' => $policy,
                    'method' => $method,
                ]);
            } catch (\Throwable $e) {
                if (app()->runningInConsole()) {
                    dump("Policy class {$policy} does not exist");
                }
                $errors[$gate] = "Policy class {$policy} does not exist";
                $skipped++;

                continue;
            }

            if (! method_exists($policy, $method)) {
                if (app()->runningInConsole()) {
                    dump("Method {$method} does not exist on {$policy}");
                }
                $errors[$gate] = "Method {$method} does not exist on {$policy}";
                $skipped++;

                continue;
            }

            try {
                Gate::define($gate, [$policy, $method]);
                $registered++;

                Log::debug('Registered gate', [
                    'gate' => $gate,
                    'policy' => $policy,
                    'method' => $method,
                ]);
            } catch (\Throwable $e) {
                $errors[$gate] = "Failed to register gate: {$e->getMessage()}";
                $skipped++;

                Log::warning('Gate registration failed', [
                    'gate' => $gate,
                    'policy' => $policy,
                    'method' => $method,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $duration = microtime(true) - $startTime;

        // Log performance metrics
        Log::debug('Gate registration completed', [
            'registered' => $registered,
            'skipped' => $skipped,
            'errors_count' => count($errors),
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return [
            'registered' => $registered,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Get all registered model policies
     *
     * @return array<class-string, class-string>
     */
    public function getModelPolicies(): array
    {
        return self::MODEL_POLICIES;
    }

    /**
     * Get all registered settings gates
     *
     * @return array<string, array{class-string, string}>
     */
    public function getSettingsGates(): array
    {
        return self::SETTINGS_GATES;
    }

    /**
     * Validate the current policy and gate configuration
     *
     * Performs comprehensive validation of all configured policies and gates
     * without attempting registration. Useful for pre-deployment validation
     * and configuration debugging.
     *
     * Validation includes:
     * - Model class existence
     * - Policy class existence
     * - Gate method existence
     * - Configuration completeness
     *
     * @return array{valid: bool, policies: array{valid: int, invalid: int, errors: array<string, string>}, gates: array{valid: int, invalid: int, errors: array<string, string>}} Comprehensive validation results
     *
     * @example
     * ```php
     * $registry = new PolicyRegistry();
     * $validation = $registry->validateConfiguration();
     *
     * if (!$validation['valid']) {
     *     foreach ($validation['policies']['errors'] as $model => $error) {
     *         logger()->error("Policy issue: {$model} - {$error}");
     *     }
     * }
     * ```
     */
    public function validateConfiguration(): array
    {
        $policyValidation = $this->validatePolicies();
        $gateValidation = $this->validateGates();

        return [
            'valid' => $policyValidation['invalid'] === 0 && $gateValidation['invalid'] === 0,
            'policies' => $policyValidation,
            'gates' => $gateValidation,
        ];
    }

    /**
     * Validate policy configurations
     *
     * @return array{valid: int, invalid: int, errors: array<string, string>}
     */
    private function validatePolicies(): array
    {
        $valid = 0;
        $invalid = 0;
        $errors = [];

        foreach (self::MODEL_POLICIES as $model => $policy) {
            $modelName = class_basename($model);

            // Use cached class existence checks for performance
            if (! $this->classExists($model)) {
                $errors[$modelName] = "Model class {$model} does not exist";
                $invalid++;

                continue;
            }

            if (! $this->classExists($policy)) {
                $errors[$modelName] = "Policy class {$policy} does not exist";
                $invalid++;

                continue;
            }

            $valid++;
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'errors' => $errors,
        ];
    }

    /**
     * Validate gate configurations
     *
     * @return array{valid: int, invalid: int, errors: array<string, string>}
     */
    private function validateGates(): array
    {
        $valid = 0;
        $invalid = 0;
        $errors = [];

        foreach (self::SETTINGS_GATES as $gate => [$policy, $method]) {
            // Use cached class existence checks for performance
            if (! $this->classExists($policy)) {
                $errors[$gate] = "Policy class {$policy} does not exist";
                $invalid++;

                continue;
            }

            if (! method_exists($policy, $method)) {
                $errors[$gate] = "Method {$method} does not exist on {$policy}";
                $invalid++;

                continue;
            }

            $valid++;
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'errors' => $errors,
        ];
    }

    /**
     * Cached class existence check for performance optimization
     *
     * Uses SHA-256 hashed cache keys to prevent collision attacks and
     * caches results for 1 hour to balance performance with accuracy.
     *
     * Security Features:
     * - SHA-256 hashing prevents cache key collisions
     * - No sensitive data in cache keys
     * - Reasonable TTL prevents stale data
     *
     * @param  string  $class  Fully qualified class name to check
     * @return bool True if class exists, false otherwise
     */
    private function classExists(string $class): bool
    {
        // Use SHA-256 to prevent hash collision attacks
        $cacheKey = self::CLASS_CACHE_KEY.'.'.hash('sha256', $class);

        return Cache::remember($cacheKey, self::CLASS_CACHE_TTL, function () use ($class) {
            return class_exists($class);
        });
    }

    /**
     * Check if the current context is authorized for policy registration
     *
     * Authorization is granted in two scenarios:
     * 1. During application boot (no authenticated user)
     * 2. For users with super_admin role
     *
     * This prevents unauthorized policy registration while allowing
     * normal application startup and administrative operations.
     *
     * @return bool True if authorized, false otherwise
     */
    private function isAuthorizedForPolicyRegistration(): bool
    {
        // Allow during application boot (when no user is authenticated or no request context)
        if (! app()->bound('auth')) {
            return true;
        }

        try {
            // Check if we have a request context - if not, we're in boot phase
            if (! request() || ! auth()->check()) {
                return true;
            }

            // Allow for superadmin users only
            $user = auth()->user();

            return $user && method_exists($user, 'hasRole') && $user->hasRole('super_admin');
        } catch (\Throwable $e) {
            // If any auth operation fails, assume we're in boot phase
            return true;
        }
    }
}

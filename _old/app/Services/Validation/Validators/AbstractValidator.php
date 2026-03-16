<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\Contracts\ValidatorInterface;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;

/**
 * Abstract base validator with common functionality.
 * 
 * Provides shared services and utility methods for concrete validators.
 */
abstract class AbstractValidator implements ValidatorInterface
{
    protected const CACHE_TTL_SECONDS = 3600;
    protected const CACHE_PREFIX = 'validation';

    public function __construct(
        protected readonly CacheRepository $cache,
        protected readonly ConfigRepository $config,
        protected readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Default implementation - most validators apply to all contexts.
     */
    public function appliesTo(ValidationContext $context): bool
    {
        return true;
    }

    /**
     * Build a cache key for the validator.
     */
    protected function buildCacheKey(string $type, mixed $identifier): string
    {
        return sprintf('%s:%s:%s:%s', self::CACHE_PREFIX, $this->getName(), $type, $identifier);
    }

    /**
     * Get configuration value with caching.
     */
    protected function getConfigValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->buildCacheKey('config', $key);
        
        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn() => $this->config->get($key, $default)
        );
    }

    /**
     * Log validation activity for audit trail.
     */
    protected function logValidation(ValidationContext $context, ValidationResult $result): void
    {
        $this->logger->info('Validation completed', [
            'validator' => $this->getName(),
            'reading_id' => $context->reading->id,
            'meter_id' => $context->reading->meter_id,
            'is_valid' => $result->isValid,
            'error_count' => count($result->errors),
            'warning_count' => count($result->warnings),
        ]);
    }

    /**
     * Handle validation exceptions gracefully.
     */
    protected function handleException(\Exception $e, ValidationContext $context): ValidationResult
    {
        $this->logger->error('Validation error in ' . $this->getName(), [
            'reading_id' => $context->reading->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return ValidationResult::withError(
            'Validation system error in ' . $this->getName() . ': ' . $e->getMessage()
        );
    }
}
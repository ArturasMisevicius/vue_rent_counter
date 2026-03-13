<?php

declare(strict_types=1);

namespace App\Support\ServiceRegistration;

/**
 * Observer Registry for organized observer registration
 * 
 * Centralizes Eloquent observer registration following
 * Laravel 12 patterns and multi-tenancy requirements.
 */
final readonly class ObserverRegistry
{
    /**
     * Model to Observer mappings
     * 
     * @var array<class-string, class-string>
     */
    private const MODEL_OBSERVERS = [
        \App\Models\MeterReading::class => \App\Observers\MeterReadingObserver::class,
        \App\Models\Faq::class => \App\Observers\FaqObserver::class,
        \App\Models\Tariff::class => \App\Observers\TariffObserver::class,
        \App\Models\User::class => \App\Observers\UserObserver::class,
        \App\Models\Language::class => \App\Observers\LanguageObserver::class,
        \App\Models\Subscription::class => \App\Observers\SubscriptionObserver::class,
    ];

    /**
     * Superadmin audit observers
     * 
     * @var array<class-string, class-string>
     */
    private const SUPERADMIN_OBSERVERS = [
        \App\Models\Organization::class => \App\Observers\SuperadminOrganizationObserver::class,
        \App\Models\Subscription::class => \App\Observers\SuperadminSubscriptionObserver::class,
        \App\Models\User::class => \App\Observers\SuperadminUserObserver::class,
    ];

    /**
     * Cache invalidation observer models
     * 
     * @var array<class-string>
     */
    private const CACHE_INVALIDATION_MODELS = [
        \App\Models\Organization::class,
        \App\Models\Subscription::class,
        \App\Models\OrganizationActivityLog::class,
    ];

    /**
     * Register all model observers
     */
    public function registerModelObservers(): void
    {
        foreach (self::MODEL_OBSERVERS as $model => $observer) {
            $model::observe($observer);
        }
    }

    /**
     * Register superadmin audit observers
     */
    public function registerSuperadminObservers(): void
    {
        foreach (self::SUPERADMIN_OBSERVERS as $model => $observer) {
            $model::observe($observer);
        }
    }

    /**
     * Register cache invalidation observers
     */
    public function registerCacheInvalidationObservers(): void
    {
        $cacheObserver = app(\App\Observers\CacheInvalidationObserver::class);
        
        foreach (self::CACHE_INVALIDATION_MODELS as $model) {
            $model::observe($cacheObserver);
        }
    }

    /**
     * Get all registered model observers
     * 
     * @return array<class-string, class-string>
     */
    public function getModelObservers(): array
    {
        return self::MODEL_OBSERVERS;
    }

    /**
     * Get all registered superadmin observers
     * 
     * @return array<class-string, class-string>
     */
    public function getSuperadminObservers(): array
    {
        return self::SUPERADMIN_OBSERVERS;
    }

    /**
     * Get cache invalidation models
     * 
     * @return array<class-string>
     */
    public function getCacheInvalidationModels(): array
    {
        return self::CACHE_INVALIDATION_MODELS;
    }
}
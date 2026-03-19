<?php

declare(strict_types=1);

namespace App\Support\ServiceRegistration;

use App\Models\Faq;
use App\Models\Language;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\Subscription;
use App\Models\Tariff;
use App\Models\User;
use App\Observers\CacheInvalidationObserver;
use App\Observers\FaqObserver;
use App\Observers\LanguageObserver;
use App\Observers\MeterReadingObserver;
use App\Observers\SubscriptionObserver;
use App\Observers\SuperadminOrganizationObserver;
use App\Observers\SuperadminSubscriptionObserver;
use App\Observers\SuperadminUserObserver;
use App\Observers\TariffObserver;
use App\Observers\UserObserver;

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
        MeterReading::class => MeterReadingObserver::class,
        Faq::class => FaqObserver::class,
        Tariff::class => TariffObserver::class,
        User::class => UserObserver::class,
        Language::class => LanguageObserver::class,
        Subscription::class => SubscriptionObserver::class,
    ];

    /**
     * Superadmin audit observers
     *
     * @var array<class-string, class-string>
     */
    private const SUPERADMIN_OBSERVERS = [
        Organization::class => SuperadminOrganizationObserver::class,
        Subscription::class => SuperadminSubscriptionObserver::class,
        User::class => SuperadminUserObserver::class,
    ];

    /**
     * Cache invalidation observer models
     *
     * @var array<class-string>
     */
    private const CACHE_INVALIDATION_MODELS = [
        Organization::class,
        Subscription::class,
        OrganizationActivityLog::class,
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
        $cacheObserver = app(CacheInvalidationObserver::class);

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

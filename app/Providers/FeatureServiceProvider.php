<?php

declare(strict_types=1);

namespace App\Providers;

use App\Filament\Support\Features\OrganizationFeatureCatalog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

final class FeatureServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (OrganizationFeatureCatalog::keys() as $feature) {
            Feature::define(
                $feature,
                fn (Organization|User|null $scope = null): bool => OrganizationFeatureCatalog::defaultEnabled($feature, $scope),
            );
        }
    }
}

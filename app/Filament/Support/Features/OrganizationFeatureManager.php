<?php

declare(strict_types=1);

namespace App\Filament\Support\Features;

use App\Models\Organization;
use App\Models\OrganizationFeatureOverride;
use App\Models\User;
use Laravel\Pennant\Feature;

final class OrganizationFeatureManager
{
    public function enabled(Organization|User|null $scope, string $feature, bool $default = false): bool
    {
        $feature = OrganizationFeatureCatalog::normalize($feature);
        $organization = OrganizationFeatureCatalog::organizationFromScope($scope);

        if (! $organization instanceof Organization) {
            return $default;
        }

        $override = OrganizationFeatureOverride::query()
            ->select(['id', 'organization_id', 'feature', 'enabled', 'reason', 'created_by', 'created_at', 'updated_at'])
            ->forOrganization($organization->id)
            ->forFeature($feature)
            ->latestFirst()
            ->first();

        if ($override instanceof OrganizationFeatureOverride) {
            return $override->enabled;
        }

        return Feature::for($organization)->active($feature);
    }

    public function sync(Organization $organization, string $feature, bool $enabled): void
    {
        $feature = OrganizationFeatureCatalog::normalize($feature);
        $interaction = Feature::for($organization);

        if ($enabled) {
            $interaction->activate($feature);

            return;
        }

        $interaction->deactivate($feature);
    }
}

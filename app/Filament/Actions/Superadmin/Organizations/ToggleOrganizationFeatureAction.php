<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\Features\OrganizationFeatureCatalog;
use App\Filament\Support\Features\OrganizationFeatureManager;
use App\Models\Organization;
use App\Models\OrganizationFeatureOverride;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ToggleOrganizationFeatureAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OrganizationFeatureManager $featureManager,
    ) {}

    public function handle(
        Organization $organization,
        string $feature,
        bool $enabled,
        string $reason,
    ): OrganizationFeatureOverride {
        $feature = OrganizationFeatureCatalog::normalize($feature);

        if (blank($feature)) {
            throw ValidationException::withMessages([
                'feature' => __('superadmin.organizations.validation.feature_required'),
            ]);
        }

        return DB::transaction(function () use ($organization, $feature, $enabled, $reason): OrganizationFeatureOverride {
            $override = OrganizationFeatureOverride::query()->create([
                'organization_id' => $organization->id,
                'feature' => $feature,
                'enabled' => $enabled,
                'reason' => $reason,
                'created_by' => auth()->id(),
            ]);

            $this->featureManager->sync($organization, $feature, $enabled);

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $organization,
                [
                    'reason' => $reason,
                    'feature' => $override->feature,
                    'enabled' => $enabled,
                ],
                description: 'Organization feature flag toggled',
            );

            return $override;
        })->fresh();
    }
}

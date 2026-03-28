<?php

namespace App\Filament\Support\Superadmin\Organizations;

use App\Filament\Support\Superadmin\Integration\IntegrationHealthPageData;
use App\Models\Organization;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Builder;

final readonly class OrganizationIntegrationSnapshot
{
    /**
     * @param  list<array{
     *     label: string,
     *     summary: string,
     *     checked_at_label: string,
     *     status_badge_class: string
     * }>  $platformChecks
     * @param  list<array{
     *     label: string,
     *     summary: string,
     *     checked_at_label: string,
     *     status_badge_class: string
     * }>  $organizationIntegrations
     */
    public function __construct(
        public array $platformChecks,
        public array $organizationIntegrations,
        public string $integrationHealthUrl,
    ) {}

    public static function fromOrganization(
        Organization $organization,
        IntegrationHealthPageData $integrationHealthPageData,
    ): self {
        return new self(
            platformChecks: $integrationHealthPageData
                ->platformChecks()
                ->map(fn (array $check): array => [
                    'label' => $check['label'],
                    'summary' => $check['summary'],
                    'checked_at_label' => $check['checked_at_label'],
                    'status_badge_class' => $check['status_badge_class'],
                ])
                ->values()
                ->all(),
            organizationIntegrations: self::organizationIntegrations($organization),
            integrationHealthUrl: route('filament.admin.pages.integration-health'),
        );
    }

    /**
     * @return list<array{
     *     label: string,
     *     summary: string,
     *     checked_at_label: string,
     *     status_badge_class: string
     * }>
     */
    private static function organizationIntegrations(Organization $organization): array
    {
        return $organization->providers()
            ->select([
                'id',
                'organization_id',
                'name',
                'service_type',
                'updated_at',
            ])
            ->withCount([
                'serviceConfigurations as active_configurations_count' => fn (Builder $query): Builder => $query->where('is_active', true),
            ])
            ->ordered()
            ->get()
            ->map(fn (Provider $provider): array => [
                'label' => $provider->name,
                'summary' => $provider->active_configurations_count > 0
                    ? __('superadmin.organizations.overview.integration_summaries.configured_provider', [
                        'count' => $provider->active_configurations_count,
                    ])
                    : __('superadmin.organizations.overview.integration_summaries.needs_configuration'),
                'checked_at_label' => $provider->updated_at?->diffForHumans()
                    ?? __('superadmin.organizations.overview.placeholders.not_available'),
                'status_badge_class' => $provider->active_configurations_count > 0
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-amber-100 text-amber-700',
            ])
            ->all();
    }
}

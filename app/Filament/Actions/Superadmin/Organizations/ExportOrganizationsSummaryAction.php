<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Enums\SubscriptionStatus;
use App\Filament\Support\Superadmin\Organizations\OrganizationListQuery;
use App\Filament\Support\Superadmin\Organizations\OrganizationMrrResolver;
use App\Models\Organization;
use Illuminate\Support\Collection;

class ExportOrganizationsSummaryAction
{
    public function __construct(
        private readonly OrganizationListQuery $organizationListQuery,
        private readonly OrganizationMrrResolver $organizationMrrResolver,
    ) {}

    /**
     * @param  Collection<int, Organization>  $organizations
     * @param  array<int, string>  $visibleColumns
     */
    public function handle(Collection $organizations, array $visibleColumns = []): string
    {
        $organizations = $this->resolveOrganizations($organizations);
        $columnMap = $this->columnMap();
        $columnKeys = $this->resolveColumnKeys($visibleColumns, $columnMap);

        $path = tempnam(sys_get_temp_dir(), 'organizations-export-');

        if ($path === false) {
            abort(500, __('superadmin.organizations.messages.export_prepare_failed'));
        }

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            abort(500, __('superadmin.organizations.messages.export_write_failed'));
        }

        fputcsv($handle, array_map(
            fn (string $key): string => $columnMap[$key]['label'],
            $columnKeys,
        ));

        foreach ($organizations as $organization) {
            fputcsv($handle, array_map(
                fn (string $key): string => (string) $columnMap[$key]['value']($organization),
                $columnKeys,
            ));
        }

        fclose($handle);

        return $path;
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     * @return Collection<int, Organization>
     */
    private function resolveOrganizations(Collection $organizations): Collection
    {
        $organizationIds = $organizations
            ->map(fn (Organization $organization): int|string => $organization->getKey())
            ->values();

        if ($organizationIds->isEmpty()) {
            return collect();
        }

        $hydratedOrganizations = $this->organizationListQuery
            ->build()
            ->whereKey($organizationIds->all())
            ->get()
            ->keyBy(fn (Organization $organization): int|string => $organization->getKey());

        return $organizationIds
            ->map(fn (int|string $organizationId): ?Organization => $hydratedOrganizations->get($organizationId))
            ->filter();
    }

    /**
     * @param  array<int, string>  $visibleColumns
     * @param  array<string, array{label: string, value: \Closure(Organization): mixed}>  $columnMap
     * @return array<int, string>
     */
    private function resolveColumnKeys(array $visibleColumns, array $columnMap): array
    {
        $defaultColumns = [
            'name',
            'owner.email',
            'status',
            'currentSubscription.plan',
            'properties_count',
            'users_count',
            'mrr_display',
            'created_at',
        ];

        $requestedColumns = $visibleColumns === []
            ? $defaultColumns
            : $visibleColumns;

        return array_values(array_filter(
            $requestedColumns,
            static fn (string $column): bool => array_key_exists($column, $columnMap),
        ));
    }

    /**
     * @return array<string, array{label: string, value: \Closure(Organization): mixed}>
     */
    private function columnMap(): array
    {
        return [
            'name' => [
                'label' => __('superadmin.organizations.columns.name'),
                'value' => fn (Organization $organization): string => $organization->name,
            ],
            'owner.email' => [
                'label' => __('superadmin.organizations.columns.owner_email'),
                'value' => fn (Organization $organization): string => (string) $organization->owner?->email,
            ],
            'status' => [
                'label' => __('superadmin.organizations.columns.status'),
                'value' => fn (Organization $organization): string => $organization->status?->label() ?? '',
            ],
            'currentSubscription.plan' => [
                'label' => __('superadmin.organizations.form.fields.plan'),
                'value' => fn (Organization $organization): string => $organization->currentSubscription?->plan?->label() ?? '',
            ],
            'properties_count' => [
                'label' => __('superadmin.organizations.overview.usage_labels.properties'),
                'value' => fn (Organization $organization): int => (int) $organization->properties_count,
            ],
            'users_count' => [
                'label' => __('superadmin.organizations.columns.users_count'),
                'value' => fn (Organization $organization): int => (int) $organization->users_count,
            ],
            'mrr_display' => [
                'label' => __('superadmin.organizations.columns.mrr'),
                'value' => fn (Organization $organization): string => $this->organizationMrrResolver->displayFor($organization),
            ],
            'trial_or_grace_ends' => [
                'label' => __('superadmin.organizations.columns.trial_or_grace_ends'),
                'value' => fn (Organization $organization): string => $this->trialOrGraceEnds($organization),
            ],
            'created_at' => [
                'label' => __('superadmin.organizations.columns.created_at'),
                'value' => fn (Organization $organization): string => $organization->created_at?->locale(app()->getLocale())->isoFormat('ll') ?? '',
            ],
        ];
    }

    private function trialOrGraceEnds(Organization $organization): string
    {
        $subscription = $organization->currentSubscription;

        if (! $subscription?->expires_at) {
            return '';
        }

        if (! $subscription->is_trial && $subscription->status !== SubscriptionStatus::TRIALING) {
            return '';
        }

        return $subscription->expires_at->locale(app()->getLocale())->isoFormat('ll');
    }
}

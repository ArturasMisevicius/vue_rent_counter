<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Models\Organization;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class OrganizationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.resources.organizations.overview')
                ->viewData(fn (Organization $record): array => [
                    'overview' => self::overview($record),
                ]),
        ]);
    }

    /**
     * @return array{
     *     details: list<array{label: string, value: string}>,
     *     subscription: list<array{label: string, value: string}>,
     *     usage: list<array{label: string, current: int, limit: int, tone: string, percentage: int}>
     * }
     */
    private static function overview(Organization $organization): array
    {
        $organization->loadMissing([
            'owner:id,name,email',
            'currentSubscription:id,organization_id,plan,status,expires_at,property_limit_snapshot,tenant_limit_snapshot',
        ]);

        $organization->loadCount([
            'properties',
            'users as tenants_count' => fn ($query) => $query->tenants(),
        ]);

        $subscription = $organization->currentSubscription;
        $propertyLimit = (int) ($subscription?->property_limit_snapshot ?? 0);
        $tenantLimit = (int) ($subscription?->tenant_limit_snapshot ?? 0);

        return [
            'details' => [
                ['label' => __('superadmin.organizations.overview.fields.organization_name'), 'value' => $organization->name],
                ['label' => __('superadmin.organizations.overview.fields.url_slug'), 'value' => $organization->slug],
                ['label' => __('superadmin.organizations.overview.fields.current_status'), 'value' => $organization->status->label()],
                ['label' => __('superadmin.organizations.overview.fields.owner_name'), 'value' => $organization->owner?->name ?? __('superadmin.organizations.empty.owner')],
                ['label' => __('superadmin.organizations.overview.fields.owner_email'), 'value' => $organization->owner?->email ?? __('superadmin.organizations.empty.owner')],
                ['label' => __('superadmin.organizations.overview.fields.date_created'), 'value' => $organization->created_at?->locale(app()->getLocale())->isoFormat('ll') ?? __('superadmin.organizations.overview.placeholders.not_available')],
                ['label' => __('superadmin.organizations.overview.fields.last_updated'), 'value' => $organization->updated_at?->locale(app()->getLocale())->isoFormat('ll') ?? __('superadmin.organizations.overview.placeholders.not_available')],
            ],
            'subscription' => [
                ['label' => __('superadmin.organizations.overview.fields.current_plan'), 'value' => $subscription?->plan?->label() ?? __('superadmin.organizations.overview.placeholders.no_plan')],
                ['label' => __('superadmin.organizations.overview.fields.subscription_status'), 'value' => $subscription?->status?->label() ?? __('superadmin.organizations.overview.placeholders.no_subscription')],
                ['label' => __('superadmin.organizations.overview.fields.subscription_expiry_date'), 'value' => $subscription?->expires_at?->locale(app()->getLocale())->isoFormat('ll') ?? __('superadmin.organizations.overview.placeholders.not_available')],
            ],
            'usage' => [
                self::usageRow(__('superadmin.organizations.overview.usage_labels.properties'), (int) $organization->properties_count, $propertyLimit),
                self::usageRow(__('superadmin.organizations.overview.usage_labels.tenants'), (int) ($organization->tenants_count ?? 0), $tenantLimit),
            ],
        ];
    }

    /**
     * @return array{label: string, current: int, limit: int, tone: string, percentage: int}
     */
    private static function usageRow(string $label, int $current, int $limit): array
    {
        $percentage = $limit > 0 ? (int) min(100, round(($current / $limit) * 100)) : 0;

        return [
            'label' => $label,
            'current' => $current,
            'limit' => $limit,
            'tone' => match (true) {
                $percentage >= 95 => 'danger',
                $percentage >= 80 => 'warning',
                default => 'default',
            },
            'percentage' => $percentage,
        ];
    }
}

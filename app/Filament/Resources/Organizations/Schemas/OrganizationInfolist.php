<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Filament\Support\Superadmin\Organizations\OrganizationDashboardData;
use App\Models\Organization;
use App\Models\Subscription;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class OrganizationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.resources.organizations.overview')
                ->viewData(function (Organization $record): array {
                    $dashboardData = app(OrganizationDashboardData::class);

                    return [
                        'overview' => self::overview($record),
                        'activityFeed' => $dashboardData->activityFeedFor($record),
                        'auditTimelineUrl' => $dashboardData->organizationAuditTimelineUrl($record),
                    ];
                }),
        ]);
    }

    /**
     * @return array{
     *     details: list<array{label: string, value: string}>,
     *     subscription: list<array{label: string, value: string}>,
     *     health: list<array{label: string, value: string}>,
     *     usage: list<array{label: string, current: int, limit: int, tone: string, percentage: int}>
     * }
     */
    private static function overview(Organization $organization): array
    {
        $organization->loadMissing([
            'owner:id,name,email',
            'currentSubscription:id,organization_id,plan,status,expires_at,property_limit_snapshot,tenant_limit_snapshot,meter_limit_snapshot,invoice_limit_snapshot',
        ]);
        self::ensureOverviewAggregates($organization);

        $subscription = $organization->currentSubscription;
        $lastActivityAt = self::formattedActivityDate($organization->getAttribute('activity_logs_max_created_at'));

        if ($subscription instanceof Subscription) {
            $subscription->setRelation('organization', $organization);
        }

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
            'health' => [
                ['label' => __('superadmin.organizations.overview.health_labels.access'), 'value' => $organization->status->label()],
                ['label' => __('superadmin.organizations.overview.health_labels.recent_activity'), 'value' => (string) ($organization->activity_logs_count ?? 0)],
                ['label' => __('superadmin.organizations.overview.health_labels.security_violations'), 'value' => (string) ($organization->security_violations_count ?? 0)],
                ['label' => __('superadmin.organizations.overview.health_labels.last_activity'), 'value' => $lastActivityAt],
            ],
            'usage' => [
                self::usageRow(
                    __('superadmin.organizations.overview.usage_labels.properties'),
                    $subscription?->propertiesUsedCount() ?? (int) $organization->properties_count,
                    $subscription?->propertyLimit() ?? 0,
                ),
                self::usageRow(
                    __('superadmin.organizations.overview.usage_labels.tenants'),
                    $subscription?->tenantsUsedCount() ?? (int) ($organization->tenants_count ?? 0),
                    $subscription?->tenantLimit() ?? 0,
                ),
                self::usageRow(
                    __('superadmin.organizations.overview.usage_labels.meters'),
                    $subscription?->metersUsedCount() ?? (int) ($organization->meters_count ?? 0),
                    $subscription?->meterLimit() ?? 0,
                ),
                self::usageRow(
                    __('superadmin.organizations.overview.usage_labels.invoices'),
                    $subscription?->invoicesUsedCount() ?? (int) ($organization->invoices_count ?? 0),
                    $subscription?->invoiceLimit() ?? 0,
                ),
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

    private static function formattedActivityDate(mixed $value): string
    {
        if ($value === null) {
            return __('superadmin.organizations.overview.placeholders.not_available');
        }

        return Carbon::parse($value)
            ->locale(app()->getLocale())
            ->isoFormat('ll');
    }

    private static function ensureOverviewAggregates(Organization $organization): void
    {
        $missingCounts = collect([
            'properties' => 'properties_count',
            'meters' => 'meters_count',
            'invoices' => 'invoices_count',
            'activityLogs' => 'activity_logs_count',
            'securityViolations' => 'security_violations_count',
        ])
            ->filter(fn (string $attribute): bool => $organization->getAttribute($attribute) === null)
            ->keys()
            ->all();

        if ($missingCounts !== []) {
            $organization->loadCount($missingCounts);
        }

        if ($organization->getAttribute('tenants_count') === null) {
            $organization->loadCount([
                'users as tenants_count' => fn ($query) => $query->tenants(),
            ]);
        }

        if ($organization->getAttribute('activity_logs_max_created_at') === null) {
            $organization->loadMax('activityLogs', 'created_at');
        }
    }
}

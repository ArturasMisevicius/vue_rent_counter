<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Filament\Support\Superadmin\Organizations\OrganizationDashboardData;
use App\Filament\Support\Superadmin\Organizations\OrganizationFinancialSnapshot;
use App\Filament\Support\Superadmin\Organizations\OrganizationMrrResolver;
use App\Filament\Support\Superadmin\Organizations\OrganizationPortfolioSnapshot;
use App\Filament\Support\Superadmin\Organizations\OrganizationSubscriptionSnapshot;
use App\Filament\Support\Superadmin\Usage\OrganizationUsageReader;
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
     *     portfolio: list<array{label: string, value: string}>,
     *     financial: list<array{label: string, value: string}>,
     *     usage: list<array{label: string, current: int, limit: int, tone: string, percentage: int}>
     *     subscription_timeline: array{
     *         summary: list<array{label: string, value: string}>,
     *         renewals: list<string>
     *     }
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

        $portfolio = OrganizationPortfolioSnapshot::fromOrganization($organization);
        $financial = OrganizationFinancialSnapshot::fromOrganization(
            $organization,
            app(OrganizationMrrResolver::class),
        );
        $usage = app(OrganizationUsageReader::class)->forOrganization($organization);
        $subscriptionTimeline = OrganizationSubscriptionSnapshot::fromOrganization($organization);

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
            'portfolio' => [
                ['label' => __('superadmin.organizations.overview.portfolio_labels.buildings'), 'value' => (string) $portfolio->buildingsCount],
                ['label' => __('superadmin.organizations.overview.portfolio_labels.properties'), 'value' => (string) $portfolio->propertiesCount],
                ['label' => __('superadmin.organizations.overview.portfolio_labels.occupied_units'), 'value' => (string) $portfolio->occupiedUnitsCount],
                ['label' => __('superadmin.organizations.overview.portfolio_labels.vacant_units'), 'value' => (string) $portfolio->vacantUnitsCount],
                ['label' => __('superadmin.organizations.overview.portfolio_labels.occupancy_rate'), 'value' => "{$portfolio->occupancyRatePercentage}%"],
                ['label' => __('superadmin.organizations.overview.portfolio_labels.active_tenants'), 'value' => (string) $portfolio->activeTenantsCount],
            ],
            'financial' => [
                ['label' => __('superadmin.organizations.overview.financial_labels.mrr'), 'value' => $financial->mrrDisplay],
                ['label' => __('superadmin.organizations.overview.financial_labels.outstanding_total'), 'value' => $financial->outstandingDisplay],
                ['label' => __('superadmin.organizations.overview.financial_labels.overdue_total'), 'value' => $financial->overdueDisplay],
                ['label' => __('superadmin.organizations.overview.financial_labels.collected_this_month'), 'value' => $financial->collectedThisMonthDisplay],
                ['label' => __('superadmin.organizations.overview.financial_labels.average_days_to_pay'), 'value' => $financial->avgDaysToPayLabel],
            ],
            'usage' => collect($usage->rows())
                ->map(fn (array $row): array => [
                    'label' => __("superadmin.organizations.overview.usage_labels.{$row['key']}"),
                    'current' => $row['current'],
                    'limit' => $row['limit'],
                    'tone' => $row['tone'],
                    'percentage' => $row['percentage'],
                ])
                ->all(),
            'subscription_timeline' => [
                'summary' => [
                    ['label' => __('superadmin.organizations.overview.subscription_timeline_labels.current_plan'), 'value' => $subscriptionTimeline->currentPlanLabel],
                    ['label' => __('superadmin.organizations.overview.subscription_timeline_labels.current_status'), 'value' => $subscriptionTimeline->statusLabel],
                    ['label' => __('superadmin.organizations.overview.subscription_timeline_labels.billing_cycle'), 'value' => $subscriptionTimeline->billingCycleLabel],
                    ['label' => __('superadmin.organizations.overview.subscription_timeline_labels.next_billing_date'), 'value' => $subscriptionTimeline->nextBillingDateLabel],
                    ['label' => __('superadmin.organizations.overview.payment_method_on_file'), 'value' => $subscriptionTimeline->paymentMethodLabel],
                ],
                'renewals' => $subscriptionTimeline->renewalHistory,
            ],
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

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
                ['label' => 'Organization Name', 'value' => $organization->name],
                ['label' => 'URL Slug', 'value' => $organization->slug],
                ['label' => 'Current Status', 'value' => $organization->status->label()],
                ['label' => 'Owner Name', 'value' => $organization->owner?->name ?? 'No owner assigned'],
                ['label' => 'Owner Email', 'value' => $organization->owner?->email ?? 'No owner assigned'],
                ['label' => 'Date Created', 'value' => $organization->created_at?->format('d M Y') ?? 'Not available'],
                ['label' => 'Last Updated', 'value' => $organization->updated_at?->format('d M Y') ?? 'Not available'],
            ],
            'subscription' => [
                ['label' => 'Current Plan', 'value' => $subscription?->plan?->label() ?? 'No plan'],
                ['label' => 'Subscription Status', 'value' => $subscription?->status?->label() ?? 'No subscription'],
                ['label' => 'Subscription Expiry Date', 'value' => $subscription?->expires_at?->format('d M Y') ?? 'Not available'],
            ],
            'usage' => [
                self::usageRow('Properties', (int) $organization->properties_count, $propertyLimit),
                self::usageRow('Tenants', (int) ($organization->tenants_count ?? 0), $tenantLimit),
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

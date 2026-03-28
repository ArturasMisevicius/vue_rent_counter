<?php

namespace App\Filament\Support\Superadmin\Usage;

use App\Models\Organization;
use App\Models\Subscription;

class OrganizationUsageReader
{
    public function forOrganization(Organization $organization): OrganizationUsageSnapshot
    {
        $organization->loadMissing([
            'currentSubscription:id,organization_id,plan,status,property_limit_snapshot,tenant_limit_snapshot,meter_limit_snapshot,invoice_limit_snapshot',
        ]);
        $this->ensureUsageCounts($organization);

        $subscription = $organization->currentSubscription;

        return new OrganizationUsageSnapshot(
            propertiesUsed: (int) ($organization->properties_count ?? 0),
            propertiesLimit: $subscription?->propertyLimit() ?? 0,
            tenantsUsed: (int) ($organization->tenants_count ?? 0),
            tenantsLimit: $subscription?->tenantLimit() ?? 0,
            metersUsed: (int) ($organization->meters_count ?? 0),
            metersLimit: $subscription?->meterLimit() ?? 0,
            invoicesUsed: (int) ($organization->invoices_count ?? 0),
            invoicesLimit: $subscription?->invoiceLimit() ?? 0,
        );
    }

    public function forSubscription(Subscription $subscription): OrganizationUsageSnapshot
    {
        $organization = $subscription->relationLoaded('organization')
            ? $subscription->organization
            : $subscription->organization()->firstOrFail();

        return $this->forOrganization($organization);
    }

    private function ensureUsageCounts(Organization $organization): void
    {
        $missingCounts = collect([
            'properties' => 'properties_count',
            'meters' => 'meters_count',
            'invoices' => 'invoices_count',
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
    }
}

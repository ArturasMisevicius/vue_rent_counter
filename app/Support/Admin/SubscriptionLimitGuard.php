<?php

namespace App\Support\Admin;

use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubscriptionLimitGuard
{
    public function canCreateProperty(Organization|int $organization): bool
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;
        $limit = $this->propertyLimitFor($organizationId);

        if ($limit === null) {
            return true;
        }

        return Property::query()
            ->where('organization_id', $organizationId)
            ->count() < $limit;
    }

    public function ensureCanCreateProperty(Organization|int $organization): void
    {
        if ($this->canCreateProperty($organization)) {
            return;
        }

        throw ValidationException::withMessages([
            'property' => __('admin.properties.messages.limit_reached'),
        ]);
    }

    public function canCreateTenant(Organization|int $organization): bool
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;
        $limit = $this->tenantLimitFor($organizationId);

        if ($limit === null) {
            return true;
        }

        return User::query()
            ->where('organization_id', $organizationId)
            ->where('role', 'tenant')
            ->count() < $limit;
    }

    public function ensureCanCreateTenant(Organization|int $organization): void
    {
        if ($this->canCreateTenant($organization)) {
            return;
        }

        throw ValidationException::withMessages([
            'tenant' => __('admin.tenants.messages.limit_reached'),
        ]);
    }

    private function propertyLimitFor(int $organizationId): ?int
    {
        return Subscription::query()
            ->select([
                'id',
                'organization_id',
                'property_limit_snapshot',
                'starts_at',
                'status',
            ])
            ->where('organization_id', $organizationId)
            ->whereIn('status', ['trialing', 'active'])
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->value('property_limit_snapshot');
    }

    private function tenantLimitFor(int $organizationId): ?int
    {
        return Subscription::query()
            ->select([
                'id',
                'organization_id',
                'tenant_limit_snapshot',
                'starts_at',
                'status',
            ])
            ->where('organization_id', $organizationId)
            ->whereIn('status', ['trialing', 'active'])
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->value('tenant_limit_snapshot');
    }
}

<?php

namespace App\Filament\Support\Admin;

use App\Filament\Support\Admin\SubscriptionEnforcement\SubscriptionEnforcementMessage;
use App\Models\Organization;
use App\Services\SubscriptionChecker;
use Illuminate\Validation\ValidationException;

class SubscriptionLimitGuard
{
    public function __construct(
        private readonly SubscriptionChecker $subscriptionChecker,
        private readonly SubscriptionEnforcementMessage $subscriptionEnforcementMessage,
    ) {}

    public function canCreateProperty(Organization|int $organization): bool
    {
        return ! $this->subscriptionChecker
            ->accessStateForOrganization($organization)
            ->blocksCreation('properties');
    }

    public function ensureCanCreateProperty(Organization|int $organization): void
    {
        if ($this->canCreateProperty($organization)) {
            return;
        }

        $message = $this->subscriptionEnforcementMessage->forResource(
            'properties',
            $this->subscriptionChecker->accessStateForOrganization($organization),
        );

        throw ValidationException::withMessages([
            'property' => $message['body'],
        ]);
    }

    public function canCreateTenant(Organization|int $organization): bool
    {
        return ! $this->subscriptionChecker
            ->accessStateForOrganization($organization)
            ->blocksCreation('tenants');
    }

    public function ensureCanCreateTenant(Organization|int $organization): void
    {
        if ($this->canCreateTenant($organization)) {
            return;
        }

        $message = $this->subscriptionEnforcementMessage->forResource(
            'tenants',
            $this->subscriptionChecker->accessStateForOrganization($organization),
        );

        throw ValidationException::withMessages([
            'tenant' => $message['body'],
        ]);
    }

    public function canWrite(Organization|int $organization): bool
    {
        return $this->subscriptionChecker
            ->accessStateForOrganization($organization)
            ->canWrite();
    }

    public function ensureCanWrite(Organization|int $organization): void
    {
        if ($this->canWrite($organization)) {
            return;
        }

        $message = $this->subscriptionEnforcementMessage->forResource(
            'properties',
            $this->subscriptionChecker->accessStateForOrganization($organization),
        );

        throw ValidationException::withMessages([
            'subscription' => $message['body'],
        ]);
    }
}

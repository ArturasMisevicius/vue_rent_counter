<?php

namespace App\Filament\Support\Admin\SubscriptionEnforcement;

use App\Models\Organization;
use App\Services\SubscriptionChecker;

class OrganizationSubscriptionAccess
{
    public function __construct(
        private readonly SubscriptionChecker $subscriptionChecker,
    ) {}

    public function forOrganization(Organization|int|null $organization): SubscriptionAccessState
    {
        return $this->subscriptionChecker->accessStateForOrganization($organization);
    }
}

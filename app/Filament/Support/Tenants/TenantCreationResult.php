<?php

declare(strict_types=1);

namespace App\Filament\Support\Tenants;

use App\Models\OrganizationInvitation;
use App\Models\PropertyAssignment;
use App\Models\User;

final readonly class TenantCreationResult
{
    /**
     * @param  array<int, string>  $nextSteps
     */
    public function __construct(
        public User $tenant,
        public ?PropertyAssignment $assignment,
        public ?OrganizationInvitation $invitation,
        public TenantBillingReadinessResult $billingReadiness,
        public array $nextSteps,
    ) {}

    public function invitationWasSent(): bool
    {
        return $this->invitation instanceof OrganizationInvitation;
    }
}

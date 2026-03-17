<?php

namespace App\Actions\Superadmin\Organizations;

use App\Actions\Auth\TerminateOrganizationSessionsAction;
use App\Enums\OrganizationStatus;
use App\Models\Organization;

class SuspendOrganizationAction
{
    public function __construct(
        private readonly TerminateOrganizationSessionsAction $terminateOrganizationSessionsAction,
    ) {}

    public function handle(Organization $organization): Organization
    {
        $organization->update([
            'status' => OrganizationStatus::SUSPENDED,
        ]);

        $this->terminateOrganizationSessionsAction->handle($organization);

        return $organization->fresh();
    }
}

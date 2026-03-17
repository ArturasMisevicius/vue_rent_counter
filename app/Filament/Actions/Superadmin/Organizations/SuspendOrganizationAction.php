<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Enums\OrganizationStatus;
use App\Filament\Actions\Auth\TerminateOrganizationSessionsAction;
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

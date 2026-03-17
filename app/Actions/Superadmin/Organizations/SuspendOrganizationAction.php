<?php

namespace App\Actions\Superadmin\Organizations;

use App\Actions\Auth\TerminateOrganizationSessionsAction;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class SuspendOrganizationAction
{
    public function __construct(
        private readonly TerminateOrganizationSessionsAction $terminateOrganizationSessions,
    ) {}

    public function handle(Organization $organization): Organization
    {
        return DB::transaction(function () use ($organization): Organization {
            $organization->forceFill([
                'status' => OrganizationStatus::SUSPENDED,
            ])->save();

            $this->terminateOrganizationSessions->handle($organization);

            return $organization->fresh();
        });
    }
}

<?php

namespace App\Actions\Auth;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserSession;

class TerminateOrganizationSessionsAction
{
    public function handle(Organization $organization): int
    {
        return UserSession::query()
            ->whereIn(
                'user_id',
                User::query()
                    ->select(['id'])
                    ->where('organization_id', $organization->id),
            )
            ->delete();
    }
}

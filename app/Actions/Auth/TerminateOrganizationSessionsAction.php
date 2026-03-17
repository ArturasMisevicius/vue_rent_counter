<?php

namespace App\Actions\Auth;

use App\Models\DatabaseSession;
use App\Models\Organization;

class TerminateOrganizationSessionsAction
{
    public function handle(Organization $organization): void
    {
        DatabaseSession::query()
            ->whereIn('user_id', $organization->users()->select('id'))
            ->delete();
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Users;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;

class SyncOrganizationMembershipAction
{
    public function handle(Organization $organization, User $user, User $actor): OrganizationUser
    {
        $membership = OrganizationUser::query()->firstOrNew([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        if (! $membership->exists) {
            $membership->joined_at = now();
            $membership->invited_by = $actor->id;
            $membership->permissions = null;
        }

        $membership->role = $user->role->value;
        $membership->is_active = true;
        $membership->left_at = null;
        $membership->save();

        return $membership->refresh();
    }
}

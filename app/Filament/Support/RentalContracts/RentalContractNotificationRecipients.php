<?php

namespace App\Filament\Support\RentalContracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class RentalContractNotificationRecipients
{
    /**
     * @return Collection<int, User>
     */
    public function adminAndManagers(int $organizationId): Collection
    {
        return User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
            ->forOrganization($organizationId)
            ->adminLike()
            ->active()
            ->get();
    }
}

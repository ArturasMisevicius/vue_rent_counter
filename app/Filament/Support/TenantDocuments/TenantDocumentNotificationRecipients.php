<?php

declare(strict_types=1);

namespace App\Filament\Support\TenantDocuments;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TenantDocumentNotificationRecipients
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

<?php

namespace App\Filament\Support\Admin;

use App\Models\Organization;
use App\Models\User;

class OrganizationContext
{
    public function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    public function currentOrganizationId(): ?int
    {
        return $this->currentUser()?->organization_id;
    }

    public function currentOrganization(): ?Organization
    {
        $organizationId = $this->currentOrganizationId();

        if ($organizationId === null) {
            return null;
        }

        return Organization::query()
            ->select([
                'id',
                'name',
                'slug',
                'status',
                'owner_user_id',
                'created_at',
                'updated_at',
            ])
            ->find($organizationId);
    }
}

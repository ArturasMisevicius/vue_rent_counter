<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Tenants;

use App\Models\Organization;
use App\Models\User;

class CreateTenantAction
{
    public function __construct(
        private readonly CreateTenantWithAssignment $createTenantWithAssignment,
    ) {}

    public function handle(User $actor, array $data, ?Organization $organization = null): User
    {
        return $this->createTenantWithAssignment->handle($actor, $data, $organization)->tenant;
    }
}

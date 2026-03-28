<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class UpdateOrganizationUserRoleAction
{
    public function handle(User $user, UserRole $role): User
    {
        if (! $user->canChangeRoleFromOrganizationRoster()) {
            throw ValidationException::withMessages([
                'role' => __('validation.prohibited', ['attribute' => __('superadmin.organizations.relations.users.columns.role')]),
            ]);
        }

        if ($role === UserRole::SUPERADMIN) {
            throw ValidationException::withMessages([
                'role' => __('validation.in', ['attribute' => __('superadmin.organizations.relations.users.columns.role')]),
            ]);
        }

        $user->update([
            'role' => $role,
        ]);

        return $user->refresh();
    }
}

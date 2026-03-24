<?php

namespace App\Filament\Actions\Admin\Tariffs;

use App\Models\Tariff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteTariffAction
{
    public function handle(Tariff $tariff): void
    {
        $user = Auth::guard()->user();

        if ($user instanceof User && Gate::forUser($user)->denies('delete', $tariff)) {
            throw new AuthorizationException;
        }

        if (! $tariff->canBeDeletedFromAdminWorkspace()) {
            throw ValidationException::withMessages([
                'tariff' => $tariff->adminDeletionBlockedReason(),
            ]);
        }

        $tariff->delete();
    }
}

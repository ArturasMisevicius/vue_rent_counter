<?php

namespace App\Filament\Actions\Admin\Providers;

use App\Models\Provider;
use Illuminate\Validation\ValidationException;

class DeleteProviderAction
{
    public function handle(Provider $provider): void
    {
        if (! $provider->canBeDeletedFromAdminWorkspace()) {
            throw ValidationException::withMessages([
                'provider' => $provider->adminDeletionBlockedReason(),
            ]);
        }

        $provider->delete();
    }
}

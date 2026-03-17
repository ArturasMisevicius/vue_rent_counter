<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StartOrganizationImpersonationAction
{
    public function handle(User $impersonator, User $target): void
    {
        abort_unless($impersonator->isSuperadmin(), 403);

        session()->put([
            'impersonator_id' => $impersonator->id,
            'impersonator_name' => $impersonator->name,
            'impersonator_email' => $impersonator->email,
        ]);

        Auth::guard('web')->login($target);
    }
}

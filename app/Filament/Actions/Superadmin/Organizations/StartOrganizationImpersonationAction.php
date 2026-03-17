<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Http\Requests\Superadmin\Organizations\ImpersonateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StartOrganizationImpersonationAction
{
    public function handle(User $impersonator, User $target): void
    {
        /** @var ImpersonateUserRequest $request */
        $request = new ImpersonateUserRequest;
        $request->validatePayload([
            'user_id' => $target->id,
        ], $impersonator);

        session()->put([
            'impersonator_id' => $impersonator->id,
            'impersonator_name' => $impersonator->name,
            'impersonator_email' => $impersonator->email,
        ]);

        Auth::guard('web')->login($target);
    }
}

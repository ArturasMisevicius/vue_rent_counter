<?php

namespace App\Livewire\Tenant;

use App\Filament\Actions\Tenant\Profile\UpdateTenantPasswordAction;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;

class UpdatePasswordEndpoint extends Component
{
    public function update(
        UpdatePasswordRequest $request,
        UpdateTenantPasswordAction $updateTenantPasswordAction,
    ): RedirectResponse {
        $updateTenantPasswordAction->handle($request->user(), $request->validated('password'));

        return to_route('tenant.profile.edit')->with('status', 'tenant-password-updated');
    }
}

<?php

namespace App\Livewire\Tenant;

use App\Filament\Actions\Tenant\Profile\UpdateTenantPasswordAction;
use App\Filament\Requests\Tenant\UpdateTenantPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;

class UpdatePasswordEndpoint extends Component
{
    public function update(
        UpdateTenantPasswordRequest $request,
        UpdateTenantPasswordAction $updateTenantPasswordAction,
    ): RedirectResponse {
        $updateTenantPasswordAction->handle($request->user(), $request->validated('password'));

        return to_route('tenant.profile.edit')->with('status', 'tenant-password-updated');
    }
}

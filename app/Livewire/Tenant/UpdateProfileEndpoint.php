<?php

namespace App\Livewire\Tenant;

use App\Filament\Actions\Tenant\Profile\UpdateTenantProfileAction;
use App\Filament\Requests\Tenant\UpdateTenantProfileRequest;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;

class UpdateProfileEndpoint extends Component
{
    public function update(
        UpdateTenantProfileRequest $request,
        UpdateTenantProfileAction $updateTenantProfileAction,
    ): RedirectResponse {
        $updateTenantProfileAction->handle($request->user(), $request->validated());

        return to_route('tenant.profile.edit')->with('status', 'tenant-profile-updated');
    }
}

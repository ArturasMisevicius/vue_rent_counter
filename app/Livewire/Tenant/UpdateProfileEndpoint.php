<?php

namespace App\Livewire\Tenant;

use App\Filament\Actions\Tenant\Profile\UpdateTenantProfileAction;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;

class UpdateProfileEndpoint extends Component
{
    public function update(
        UpdateProfileRequest $request,
        UpdateTenantProfileAction $updateTenantProfileAction,
    ): RedirectResponse {
        $updateTenantProfileAction->handle($request->user(), $request->validated());

        return to_route('tenant.profile.edit')->with('status', 'tenant-profile-updated');
    }
}

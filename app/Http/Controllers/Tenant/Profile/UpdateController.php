<?php

namespace App\Http\Controllers\Tenant\Profile;

use App\Actions\Tenant\Profile\UpdateTenantProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantProfileRequest;
use Illuminate\Http\RedirectResponse;

class UpdateController extends Controller
{
    public function __invoke(
        UpdateTenantProfileRequest $request,
        UpdateTenantProfileAction $updateTenantProfileAction,
    ): RedirectResponse {
        $updateTenantProfileAction->handle($request->user(), $request->validated());

        return to_route('tenant.profile.edit')->with('status', 'tenant-profile-updated');
    }
}

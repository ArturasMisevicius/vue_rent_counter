<?php

namespace App\Http\Controllers\Tenant\Profile;

use App\Actions\Tenant\Profile\UpdateTenantPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantPasswordRequest;
use Illuminate\Http\RedirectResponse;

class UpdatePasswordController extends Controller
{
    public function __invoke(
        UpdateTenantPasswordRequest $request,
        UpdateTenantPasswordAction $updateTenantPasswordAction,
    ): RedirectResponse {
        $updateTenantPasswordAction->handle($request->user(), $request->validated('password'));

        return to_route('tenant.profile.edit')->with('status', 'tenant-password-updated');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\ImpersonationManager;
use App\Support\Shell\DashboardUrlResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StopImpersonationController extends Controller
{
    public function __invoke(
        Request $request,
        ImpersonationManager $impersonationManager,
        DashboardUrlResolver $dashboardUrlResolver,
    ): RedirectResponse {
        $impersonator = $impersonationManager->resolveImpersonator($request);

        $impersonationManager->forget($request);

        if ($impersonator !== null) {
            Auth::guard('web')->login($impersonator);

            return redirect()->to($dashboardUrlResolver->for($impersonator));
        }

        return redirect()->to($dashboardUrlResolver->for($request->user()));
    }
}

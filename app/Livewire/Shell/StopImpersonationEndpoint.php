<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Auth\ImpersonationManager;
use App\Filament\Support\Shell\DashboardUrlResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StopImpersonationEndpoint extends Component
{
    public function stop(
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

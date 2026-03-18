<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\DashboardUrlResolver;
use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Livewire\Component;

class StopImpersonationEndpoint extends Component
{
    public function stop(
        Request $request,
        ImpersonationService $impersonationService,
        DashboardUrlResolver $dashboardUrlResolver,
    ): RedirectResponse {
        $impersonator = $impersonationService->stop($request);
        $redirectUser = $impersonator ?? $request->user();

        return redirect()->to($dashboardUrlResolver->for($redirectUser));
    }
}

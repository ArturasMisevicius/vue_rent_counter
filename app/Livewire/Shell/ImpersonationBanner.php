<?php

declare(strict_types=1);

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\DashboardUrlResolver;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Services\ImpersonationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class ImpersonationBanner extends Component
{
    use AppliesShellLocale;

    /**
     * @return array{id: int, name: string, email: string}|null
     */
    #[Computed]
    public function impersonation(): ?array
    {
        return app(ImpersonationService::class)->current(request());
    }

    public function stopImpersonating(
        Request $request,
        ImpersonationService $impersonationService,
        DashboardUrlResolver $dashboardUrlResolver,
    ): RedirectResponse {
        $impersonator = $impersonationService->stop($request);
        $redirectUser = $impersonator ?? $request->user();

        return redirect()->to($dashboardUrlResolver->for($redirectUser));
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();
        unset($this->impersonation);
    }

    public function render(): View
    {
        return view('livewire.shell.impersonation-banner', [
            'impersonation' => $this->impersonation,
        ]);
    }
}

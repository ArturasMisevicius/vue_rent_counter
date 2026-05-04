<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\DashboardUrlResolver;
use App\Filament\Support\Shell\Navigation\NavigationBuilder;
use App\Livewire\Concerns\AppliesShellLocale;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    use AppliesShellLocale;

    #[On('shell-locale-updated')]
    public function refresh(): void
    {
        $this->applyShellLocale();
    }

    public function render(
        DashboardUrlResolver $dashboardUrlResolver,
        NavigationBuilder $navigationBuilder,
    ): View {
        $user = auth()->user();

        return view('livewire.shell.sidebar', [
            'dashboardUrl' => $dashboardUrlResolver->for($user),
            'isTenant' => (bool) $user?->isTenant(),
            'groups' => $user ? $navigationBuilder->forUser($user, request()) : [],
        ]);
    }
}

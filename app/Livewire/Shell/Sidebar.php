<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\DashboardUrlResolver;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Sidebar extends Component
{
    public function render(DashboardUrlResolver $dashboardUrlResolver): View
    {
        $user = auth()->user();

        return view('livewire.shell.sidebar', [
            'dashboardUrl' => $dashboardUrlResolver->for($user),
            'groups' => $user ? Filament::getNavigation() : [],
        ]);
    }
}

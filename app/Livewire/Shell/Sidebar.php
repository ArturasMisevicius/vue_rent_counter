<?php

namespace App\Livewire\Shell;

use App\Support\Shell\DashboardUrlResolver;
use App\Support\Shell\Navigation\NavigationBuilder;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Sidebar extends Component
{
    public function render(NavigationBuilder $navigationBuilder, DashboardUrlResolver $dashboardUrlResolver): View
    {
        $user = auth()->user();

        return view('livewire.shell.sidebar', [
            'dashboardUrl' => $dashboardUrlResolver->for($user),
            'groups' => $user ? $navigationBuilder->adminLike($user, request()) : [],
        ]);
    }
}

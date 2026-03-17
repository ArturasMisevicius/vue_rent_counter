<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\Navigation\NavigationBuilder;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TenantBottomNavigation extends Component
{
    public function render(NavigationBuilder $navigationBuilder): View
    {
        return view('livewire.shell.tenant-bottom-navigation', [
            'items' => $navigationBuilder->tenant(request()),
        ]);
    }
}

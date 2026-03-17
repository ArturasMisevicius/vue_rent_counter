<?php

namespace App\Livewire\Shell;

use App\Models\User;
use App\Support\Shell\Navigation\NavigationBuilder;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class TenantBottomNavigation extends Component
{
    #[On('shell-locale-updated')]
    public function refresh(): void {}

    public function render(): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        return view('livewire.shell.tenant-bottom-navigation', [
            'items' => app(NavigationBuilder::class)->tenantItemsFor($user),
        ]);
    }
}

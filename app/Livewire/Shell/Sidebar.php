<?php

namespace App\Livewire\Shell;

use App\Models\User;
use App\Support\Shell\Navigation\NavigationBuilder;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Sidebar extends Component
{
    public function render(): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        return view('livewire.shell.sidebar', [
            'groups' => app(NavigationBuilder::class)->sidebarGroupsFor($user),
        ]);
    }
}

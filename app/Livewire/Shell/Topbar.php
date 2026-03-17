<?php

namespace App\Livewire\Shell;

use App\Models\User;
use App\Support\Shell\DashboardUrlResolver;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Topbar extends Component
{
    public function render(): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        return view('livewire.shell.topbar', [
            'dashboardUrl' => app(DashboardUrlResolver::class)->for($user),
            'locale' => config('tenanto.locales.'.app()->getLocale(), config('tenanto.locales.en')),
            'user' => $user,
        ]);
    }
}

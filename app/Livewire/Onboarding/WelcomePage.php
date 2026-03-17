<?php

namespace App\Livewire\Onboarding;

use App\Support\Auth\LoginRedirector;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class WelcomePage extends Component
{
    public function mount(LoginRedirector $loginRedirector): void
    {
        $user = auth()->user();

        if (! $user || ! $user->isAdmin() || filled($user->organization_id)) {
            $this->redirect($loginRedirector->for($user));
        }
    }

    public function render(): View
    {
        return view('onboarding.welcome');
    }
}

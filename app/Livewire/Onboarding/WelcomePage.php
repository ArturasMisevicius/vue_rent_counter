<?php

namespace App\Livewire\Onboarding;

use App\Filament\Actions\Auth\CompleteOnboardingAction;
use App\Filament\Support\Auth\LoginRedirector;
use App\Http\Requests\Auth\CompleteOnboardingRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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

    public function store(
        CompleteOnboardingRequest $request,
        CompleteOnboardingAction $completeOnboarding,
        LoginRedirector $loginRedirector,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user || ! $user->isAdmin() || filled($user->organization_id)) {
            return redirect()->to($loginRedirector->for($user));
        }

        $completeOnboarding->handle(
            $user,
            $request->validated(),
        );

        return redirect()->to($loginRedirector->for($user->fresh()));
    }

    public function render(): View
    {
        return view('onboarding.welcome')
            ->extends('layouts.guest');
    }
}

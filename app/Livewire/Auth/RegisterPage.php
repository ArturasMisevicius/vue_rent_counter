<?php

namespace App\Livewire\Auth;

use App\Filament\Actions\Auth\RegisterAdminAction;
use App\Filament\Support\Auth\AuthenticatedSessionHistory;
use App\Http\Requests\Auth\RegisterRequest;
use App\Livewire\Concerns\AppliesShellLocale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class RegisterPage extends Component
{
    use AppliesShellLocale;

    public function store(
        RegisterRequest $request,
        RegisterAdminAction $registerAdminAction,
        AuthenticatedSessionHistory $authenticatedSessionHistory,
    ): RedirectResponse {
        $user = $registerAdminAction->handle($request->validated());

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('welcome.show')
            ->withCookie($authenticatedSessionHistory->remember());
    }

    public function render(): View
    {
        return view('auth.register')
            ->extends('layouts.guest');
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();
    }
}

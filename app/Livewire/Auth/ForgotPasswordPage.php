<?php

namespace App\Livewire\Auth;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Livewire\Concerns\AppliesShellLocale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\On;
use Livewire\Component;

class ForgotPasswordPage extends Component
{
    use AppliesShellLocale;

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->validated());

        return back()->with('status', __('auth.reset_link_generic'));
    }

    public function render(): View
    {
        return view('auth.forgot-password')
            ->extends('layouts.guest');
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();
    }
}

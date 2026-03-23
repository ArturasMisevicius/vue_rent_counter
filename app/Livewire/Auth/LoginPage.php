<?php

namespace App\Livewire\Auth;

use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;
use App\Filament\Support\Auth\AuthenticatedSessionHistory;
use App\Filament\Support\Auth\LoginDemoAccountPresenter;
use App\Filament\Support\Auth\LoginRedirector;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LoginPage extends Component
{
    public function store(
        LoginRequest $request,
        LoginRedirector $loginRedirector,
        AuthenticatedSessionHistory $authenticatedSessionHistory,
    ): RedirectResponse {
        if (! Auth::attempt($request->credentials())) {
            throw ValidationException::withMessages([
                'email' => __('auth.invalid_credentials'),
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if (
            $user->status === UserStatus::SUSPENDED ||
            $user->organization?->status === OrganizationStatus::SUSPENDED
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => __('auth.account_suspended'),
            ]);
        }

        if (
            $user->organization?->status instanceof OrganizationStatus &&
            ! $user->organization->status->permitsAccess()
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => __('auth.account_inactive'),
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()
            ->intended($loginRedirector->for($user))
            ->withCookie($authenticatedSessionHistory->remember());
    }

    public function render(): View
    {
        return view('auth.login', [
            'demoAccounts' => $this->demoAccounts,
        ])->extends('layouts.guest');
    }

    /**
     * @return array<int, array{name: string, email: string, password: string, role: string}>
     */
    #[Computed]
    public function demoAccounts(): array
    {
        return app(LoginDemoAccountPresenter::class)->accounts() ?: config('tenanto.demo_accounts', []);
    }
}

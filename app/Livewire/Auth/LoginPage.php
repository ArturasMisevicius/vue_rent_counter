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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LoginPage extends Component
{
    public function mount(Request $request): void
    {
        $intendedUrl = $this->resolveIntendedUrl($request);

        if ($intendedUrl !== null && ! $request->session()->has('url.intended')) {
            $request->session()->put('url.intended', $intendedUrl);
        }

        if ($request->boolean('session_expired') && ! $request->session()->has('auth.session_expired')) {
            $request->session()->flash('auth.session_expired', __('auth.session_expired'));
        }
    }

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

    private function resolveIntendedUrl(Request $request): ?string
    {
        $intendedUrl = trim((string) $request->query('intended', ''));

        if (
            $intendedUrl === ''
            || strlen($intendedUrl) > 2048
            || ! str_starts_with($intendedUrl, '/')
            || str_starts_with($intendedUrl, '//')
            || preg_match('/[\x00-\x1F\x7F]/', $intendedUrl) === 1
        ) {
            return null;
        }

        return $intendedUrl;
    }
}

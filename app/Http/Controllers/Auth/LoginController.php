<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Auth\LoginRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * @throws ValidationException
     */
    public function store(LoginRequest $request, LoginRedirector $loginRedirector): RedirectResponse
    {
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

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended($loginRedirector->for($user));
    }
}

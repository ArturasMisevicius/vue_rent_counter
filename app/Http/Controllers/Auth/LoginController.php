<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login Controller
 * 
 * Handles user authentication including login form display,
 * credential verification, and logout functionality.
 * 
 * Requirements: 1.1, 7.1, 8.1, 8.4
 */
final class LoginController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authService
    ) {}

    /**
     * Display the login form with available users.
     * 
     * Note: User list is displayed for demo/testing purposes.
     * In production, this should be removed or restricted.
     */
    public function showLoginForm(): View
    {
        $users = $this->authService->getActiveUsersForLoginDisplay();

        return view('auth.login', compact('users'));
    }

    /**
     * Handle user login attempt.
     * 
     * Validates credentials, checks account status, regenerates session,
     * and redirects to role-appropriate dashboard.
     * 
     * Requirements: 1.1, 7.1, 8.1, 8.4
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return $this->handleFailedLogin();
        }

        $user = Auth::user();

        if (!$this->authService->isAccountActive($user)) {
            Auth::logout();
            return $this->handleDeactivatedAccount();
        }

        $request->session()->regenerate();

        return $this->authService->redirectToDashboard($user);
    }

    /**
     * Handle user logout.
     * 
     * Logs out user, invalidates session, and regenerates CSRF token.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Handle failed login attempt.
     */
    private function handleFailedLogin(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->withErrors([
                'email' => __('auth.failed'),
            ])
            ->onlyInput('email');
    }

    /**
     * Handle deactivated account login attempt.
     */
    private function handleDeactivatedAccount(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->withErrors([
                'email' => __('auth.account_deactivated'),
            ])
            ->onlyInput('email');
    }
}

<?php

namespace App\Http\Middleware;

use App\Filament\Support\Auth\AuthenticatedSessionHistory;
use Closure;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdminPanel extends FilamentAuthenticate
{
    public function __construct(
        Auth $auth,
        private readonly AuthenticatedSessionHistory $authenticatedSessionHistory,
    ) {
        parent::__construct($auth);
    }

    public function handle($request, Closure $next, ...$guards): Response
    {
        try {
            $this->authenticate($request, $guards);
        } catch (AuthenticationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }

            $response = redirect()->guest($this->redirectTo($request));

            if ($this->authenticatedSessionHistory->has($request)) {
                $response->with('auth.session_expired', __('auth.session_expired'));
            }

            return $response;
        }

        return $next($request);
    }

    protected function redirectTo($request): string
    {
        return route('login');
    }
}

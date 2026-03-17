<?php

namespace App\Http\Middleware;

use App\Support\Auth\AuthenticatedSessionMarker;
use Closure;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;

class AuthenticateAdminPanel extends FilamentAuthenticate
{
    public function __construct(
        Auth $auth,
        private readonly AuthenticatedSessionMarker $authenticatedSessionMarker,
    ) {
        parent::__construct($auth);
    }

    public function handle($request, Closure $next, ...$guards)
    {
        try {
            $this->authenticate($request, $guards);
        } catch (AuthenticationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }

            if ($this->authenticatedSessionMarker->shouldFlashSessionExpired($request)) {
                $request->session()->flash('auth.session_expired', true);
            }

            return redirect()
                ->guest(route('login'))
                ->withCookie($this->authenticatedSessionMarker->forget());
        }

        return $next($request);
    }
}

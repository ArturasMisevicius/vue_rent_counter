<?php

namespace App\Http\Middleware;

use App\Support\Auth\AuthenticatedSessionHistory;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate extends Middleware
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

    protected function redirectTo(Request $request): string
    {
        return route('login');
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->hasAuthenticatedUser($request)) {
            return $this->redirectToLogin('Authentication required.');
        }

        if (!$this->hasTenantContext()) {
            return $this->redirectToLogin('Tenant context is missing. Please log in again.');
        }

        if (!$this->isValidTenantContext($request)) {
            session()->forget('tenant_id');
            return $this->redirectToLogin('Invalid tenant context. Please log in again.');
        }

        return $next($request);
    }

    /**
     * Check if the request has an authenticated user.
     */
    protected function hasAuthenticatedUser(Request $request): bool
    {
        return $request->user() !== null;
    }

    /**
     * Check if tenant context exists in session.
     */
    protected function hasTenantContext(): bool
    {
        return session()->has('tenant_id');
    }

    /**
     * Validate that session tenant_id matches user's tenant_id.
     */
    protected function isValidTenantContext(Request $request): bool
    {
        return session('tenant_id') === $request->user()->tenant_id;
    }

    /**
     * Redirect to login with error message.
     */
    protected function redirectToLogin(string $message): Response
    {
        return redirect()->route('login')->with('error', $message);
    }
}

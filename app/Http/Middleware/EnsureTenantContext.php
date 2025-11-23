<?php

namespace App\Http\Middleware;

use App\Models\OrganizationActivityLog;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        TenantContext::initialize();

        // Allow unauthenticated requests to proceed (e.g., login, invites)
        if (! $request->user()) {
            return $next($request);
        }

        $user = $request->user();

        // Superadmin can operate without an active tenant context
        if ($user->isSuperadmin()) {
            if (! TenantContext::has()) {
                Log::info('Superadmin accessing without tenant context', [
                    'user_id' => $user->id,
                    'url' => $request->url(),
                ]);
            }

            return $next($request);
        }

        // Ensure tenant context is set for regular users
        if (! TenantContext::has() && $user->tenant_id) {
            TenantContext::set($user->tenant_id);
        }

        if (! TenantContext::has()) {
            return $this->redirectToLogin('Tenant context is missing. Please log in again.');
        }

        $tenant = TenantContext::get();

        // Validate tenant is active
        if (! $tenant->isActive()) {
            auth()->logout();

            return redirect()->route('login')
                ->with('error', 'Your organization has been suspended.');
        }

        // Log write operations for audit trail
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            OrganizationActivityLog::log(
                action: $request->method().' '.$request->path(),
                metadata: ['route' => $request->route()?->getName()]
            );
        }

        return $next($request);
    }

    /**
     * Redirect to login with error message.
     */
    protected function redirectToLogin(string $message): Response
    {
        return redirect()->route('login')->with('error', $message);
    }
}

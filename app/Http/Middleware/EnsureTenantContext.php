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
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantContext->initialize();

        // Allow unauthenticated requests to proceed (e.g., login, invites)
        if (! $request->user()) {
            return $next($request);
        }

        $user = $request->user();

        // Superadmin can operate without an active tenant context
        if ($user->isSuperadmin()) {
            if (! $this->tenantContext->has()) {
                Log::info('Superadmin accessing without tenant context', [
                    'user_id' => $user->id,
                    'url' => $request->url(),
                ]);
            }

            return $next($request);
        }

        // Ensure tenant context is set for regular users
        if (! $this->tenantContext->has() && $user->tenant_id) {
            $this->tenantContext->set($user->tenant_id);
        }

        if (! $this->tenantContext->has()) {
            return $this->redirectToLogin('Tenant context is missing. Please log in again.');
        }

        $tenantId = $this->tenantContext->get();

        // Validate tenant is active (simplified check since we only have tenant_id)
        if ($tenantId === null) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Invalid tenant context.');
        }

        // Log write operations for audit trail
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Simplified logging since OrganizationActivityLog might not exist yet
            Log::info('Tenant operation', [
                'method' => $request->method(),
                'path' => $request->path(),
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
            ]);
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

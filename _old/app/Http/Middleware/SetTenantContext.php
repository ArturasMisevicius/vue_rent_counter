<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\TenantContextInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for setting tenant context based on authenticated user.
 * 
 * This middleware ensures that the tenant context is properly initialized
 * for each authenticated request, providing the foundation for multi-tenant
 * data isolation throughout the application.
 */
final readonly class SetTenantContext
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process for authenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        try {
            // Initialize tenant context for the authenticated user
            $this->tenantContext->initialize($user);

            // Log context initialization for audit purposes
            $currentTenantId = $this->tenantContext->get();
            if ($currentTenantId) {
                Log::channel('tenant_context')->debug('Tenant context initialized', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_role' => $user->role->value,
                    'tenant_id' => $currentTenantId,
                    'request_path' => $request->path(),
                    'session_id' => $request->session()->getId(),
                ]);
            }
        } catch (\Exception $e) {
            // Log the error but don't block the request
            Log::channel('tenant_context')->error('Failed to initialize tenant context', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
                'request_path' => $request->path(),
                'session_id' => $request->session()->getId(),
            ]);

            // For security, clear any potentially invalid context
            $this->tenantContext->clear();
        }

        return $next($request);
    }
}
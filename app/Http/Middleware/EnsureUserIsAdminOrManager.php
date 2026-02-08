<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure only admin and manager users can access Filament admin panel.
 *
 * This middleware provides defense-in-depth authorization for the Filament
 * admin panel, complementing the primary authorization gate in User::canAccessPanel().
 *
 * Requirements:
 * - 9.1: Admin panel access control
 * - 9.2: Manager role permissions
 * - 9.3: Tenant role restrictions
 * - 9.4: Authorization logging
 *
 * @see \App\Models\User::canAccessPanel()
 * @see \App\Providers\Filament\AdminPanelProvider
 */
final class EnsureUserIsAdminOrManager
{
    /**
     * Handle an incoming request.
     *
     * Validates that the authenticated user has admin or manager role.
     * Logs authorization failures for security monitoring.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Ensure user is authenticated
        if (! $user) {
            $this->logAuthorizationFailure($request, null, 'No authenticated user');
            abort(403, __('app.auth.authentication_required'));
        }

        // Check if user has admin, manager, or superadmin role using model helpers
        if ($user->isAdmin() || $user->isManager() || $user->isSuperadmin()) {
            return $next($request);
        }

        // Log unauthorized access attempt
        $this->logAuthorizationFailure($request, $user, 'Insufficient role privileges');

        abort(403, __('app.auth.no_permission_admin_panel'));
    }

    /**
     * Log authorization failure for security monitoring.
     *
     * Requirement 9.4: Authorization logging
     */
    private function logAuthorizationFailure(Request $request, $user, string $reason): void
    {
        Log::warning('Admin panel access denied', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_role' => $user?->role?->value,
            'reason' => $reason,
            'url' => $request->url(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}

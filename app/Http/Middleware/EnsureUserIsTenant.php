<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure User Is Tenant Middleware
 * 
 * Restricts access to tenant-only areas of the application.
 * This middleware ensures that only users with the TENANT role
 * can access the tenant panel and related functionality.
 * 
 * ## Security Features
 * 
 * - Role validation: Only TENANT role allowed
 * - Authentication check: User must be logged in
 * - Property assignment check: Tenant must be assigned to a property
 * - Graceful handling: Redirects non-tenants to appropriate dashboard
 * 
 * ## Usage
 * 
 * Applied to:
 * - Tenant Filament panel
 * - Property-specific routes
 * - Tenant dashboard and profile
 * 
 * @package App\Http\Middleware
 */
final class EnsureUserIsTenant
{
    /**
     * Handle an incoming request.
     * 
     * @param Request $request The incoming request
     * @param Closure $next The next middleware in the pipeline
     * @return Response The response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has tenant role
        if ($user->role !== UserRole::TENANT) {
            // Redirect to appropriate dashboard based on role
            return match ($user->role) {
                UserRole::SUPERADMIN => redirect('/superadmin'),
                UserRole::ADMIN => redirect('/admin'),
                UserRole::MANAGER => redirect()->route('manager.dashboard'),
                default => abort(403, 'Access denied'),
            };
        }

        // Check if tenant is assigned to a property
        if (!$user->property_id) {
            abort(403, 'Tenant not assigned to any property. Please contact your administrator.');
        }

        return $next($request);
    }
}
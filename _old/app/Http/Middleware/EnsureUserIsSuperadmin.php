<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure User Is Superadmin Middleware
 * 
 * Restricts access to superadmin-only areas of the application.
 * This middleware ensures that only users with the SUPERADMIN role
 * can access the superadmin panel and related functionality.
 * 
 * ## Security Features
 * 
 * - Role validation: Only SUPERADMIN role allowed
 * - Authentication check: User must be logged in
 * - Graceful handling: Redirects non-superadmins to appropriate dashboard
 * 
 * ## Usage
 * 
 * Applied to:
 * - Superadmin Filament panel
 * - System-wide management routes
 * - Organization and subscription management
 * 
 * @package App\Http\Middleware
 */
final class EnsureUserIsSuperadmin
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

        // Enforce strict role access for superadmin-only routes.
        if ($user->role !== UserRole::SUPERADMIN) {
            abort(403, 'Access denied');
        }

        return $next($request);
    }
}

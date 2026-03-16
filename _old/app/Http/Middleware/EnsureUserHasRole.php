<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure User Has Role Middleware
 * 
 * Checks if the authenticated user has one of the specified roles.
 * Uses the UserRole enum instead of Spatie Permission database roles.
 * 
 * @package App\Http\Middleware
 */
final class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     * 
     * @param Request $request The incoming request
     * @param Closure $next The next middleware in the pipeline
     * @param string ...$roles Allowed roles (e.g., 'superadmin', 'admin', 'manager')
     * @return Response The response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $userRole = $user->role->value;

        if (in_array($userRole, $roles, true)) {
            return $next($request);
        }

        abort(403, 'User does not have the required role.');
    }
}

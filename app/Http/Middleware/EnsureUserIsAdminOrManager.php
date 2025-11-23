<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminOrManager
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            abort(403, 'Authentication required.');
        }
        
        // Allow admin and manager roles
        if ($user->role === \App\Enums\UserRole::ADMIN || $user->role === \App\Enums\UserRole::MANAGER) {
            return $next($request);
        }
        
        abort(403, 'You do not have permission to access the admin panel.');
    }
}

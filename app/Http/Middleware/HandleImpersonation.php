<?php

namespace App\Http\Middleware;

use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleImpersonation
{
    /**
     * Handle an incoming request.
     * 
     * Requirements: 11.3
     */
    public function handle(Request $request, Closure $next): Response
    {
        $impersonationService = app(ImpersonationService::class);

        // Check if impersonation has timed out
        if ($impersonationService->isImpersonating() && $impersonationService->hasTimedOut()) {
            $impersonationService->endImpersonation();
            
            return redirect()->route('filament.admin.pages.dashboard')
                ->with('warning', __('app.impersonation.timeout'));
        }

        return $next($request);
    }
}

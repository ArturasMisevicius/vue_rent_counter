<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\SecurityHeaderService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function __construct(
        private readonly SecurityHeaderService $securityHeaderService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->securityHeaderService->prepare($request);

        /** @var Response $response */
        $response = $next($request);

        return $this->securityHeaderService->apply($request, $response);
    }
}

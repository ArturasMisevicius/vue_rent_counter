<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Http\Request;

final class SecurityHeaderFactory
{
    public function __construct(
        private readonly NonceGeneratorService $nonceGeneratorService,
        private readonly ViteCspIntegration $viteCspIntegration,
        private readonly CspHeaderBuilder $cspHeaderBuilder,
    ) {}

    /**
     * @return array{nonce: string, headers: array<string, string>}
     */
    public function make(Request $request): array
    {
        $nonce = $this->viteCspIntegration->apply(
            $this->nonceGeneratorService->generate(),
        );

        $headers = array_filter([
            'Content-Security-Policy' => $this->cspHeaderBuilder->build($request, $nonce),
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), geolocation=(), microphone=()',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Strict-Transport-Security' => $request->isSecure() || app()->isProduction()
                ? 'max-age=31536000; includeSubDomains'
                : null,
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ]);

        return [
            'nonce' => $nonce,
            'headers' => $headers,
        ];
    }
}

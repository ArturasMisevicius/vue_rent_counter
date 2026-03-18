<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaderService
{
    private const HEADERS_ATTRIBUTE = 'security.headers';

    private const NONCE_ATTRIBUTE = 'security.csp_nonce';

    public function __construct(
        private readonly SecurityHeaderFactory $securityHeaderFactory,
    ) {}

    public function prepare(Request $request): void
    {
        if ($request->attributes->has(self::HEADERS_ATTRIBUTE)) {
            return;
        }

        $payload = $this->securityHeaderFactory->make($request);

        $request->attributes->set(self::HEADERS_ATTRIBUTE, $payload['headers']);
        $request->attributes->set(self::NONCE_ATTRIBUTE, $payload['nonce']);
    }

    public function apply(Request $request, Response $response): Response
    {
        /** @var array<string, string> $headers */
        $headers = $request->attributes->get(self::HEADERS_ATTRIBUTE, []);

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}

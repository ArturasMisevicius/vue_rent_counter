<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

final class CspHeaderBuilder
{
    public function build(Request $request, string $nonce): string
    {
        $viteOrigin = $this->viteOrigin();
        $websocketOrigin = $viteOrigin !== null ? $this->websocketOrigin($viteOrigin) : null;

        $scriptSources = [
            "'self'",
            sprintf("'nonce-%s'", $nonce),
            "'unsafe-inline'",
            "'unsafe-eval'",
        ];

        $scriptElementSources = [
            "'self'",
            "'unsafe-inline'",
        ];

        $styleSources = [
            "'self'",
            sprintf("'nonce-%s'", $nonce),
            "'unsafe-inline'",
            'https://fonts.bunny.net',
        ];

        $styleElementSources = [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.bunny.net',
        ];

        $connectSources = [
            "'self'",
            'https:',
        ];

        if ($viteOrigin !== null) {
            $scriptSources[] = $viteOrigin;
            $scriptElementSources[] = $viteOrigin;
            $styleSources[] = $viteOrigin;
            $styleElementSources[] = $viteOrigin;
            $connectSources[] = $viteOrigin;

            if ($websocketOrigin !== null) {
                $connectSources[] = $websocketOrigin;
            }
        }

        $directives = [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'connect-src' => $connectSources,
            'font-src' => ["'self'", 'data:', 'https://fonts.bunny.net'],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'img-src' => ["'self'", 'data:', 'https:'],
            'manifest-src' => ["'self'"],
            'media-src' => ["'self'", 'data:', 'https:'],
            'object-src' => ["'none'"],
            'script-src' => $scriptSources,
            'script-src-elem' => $scriptElementSources,
            'script-src-attr' => ["'unsafe-inline'"],
            'style-src' => $styleSources,
            'style-src-elem' => $styleElementSources,
            'style-src-attr' => ["'unsafe-inline'"],
        ];

        if ($this->shouldUpgradeInsecureRequests($request)) {
            $directives['upgrade-insecure-requests'] = [];
        }

        if (Route::has('security.csp.report')) {
            $directives['report-uri'] = [
                route('security.csp.report', absolute: false),
            ];
        }

        return collect($directives)
            ->map(
                static fn (array $values, string $directive): string => $values === []
                    ? $directive
                    : sprintf('%s %s', $directive, implode(' ', array_unique($values))),
            )
            ->implode('; ');
    }

    private function shouldUpgradeInsecureRequests(Request $request): bool
    {
        return $request->isSecure() || app()->isProduction();
    }

    private function viteOrigin(): ?string
    {
        $hotPath = public_path('hot');

        if (! is_file($hotPath)) {
            return null;
        }

        $origin = trim((string) file_get_contents($hotPath));

        if ($origin === '' || ! str_starts_with($origin, 'http')) {
            return null;
        }

        return rtrim($origin, '/');
    }

    private function websocketOrigin(string $origin): ?string
    {
        $parts = parse_url($origin);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'] === 'https' ? 'wss' : 'ws';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return sprintf('%s://%s%s', $scheme, $host, $port);
    }
}

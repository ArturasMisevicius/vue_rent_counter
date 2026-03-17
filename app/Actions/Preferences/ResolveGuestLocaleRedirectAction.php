<?php

namespace App\Actions\Preferences;

use Illuminate\Http\Request;

class ResolveGuestLocaleRedirectAction
{
    /**
     * @var list<string>
     */
    private const ALLOWED_PATH_PATTERNS = [
        '#^/$#',
        '#^/login$#',
        '#^/register$#',
        '#^/forgot-password$#',
        '#^/reset-password/[^/]+$#',
        '#^/invite/[^/]+$#',
    ];

    public function handle(Request $request): string
    {
        $previousUrl = $request->headers->get('referer');

        if (! is_string($previousUrl) || $previousUrl === '') {
            return route('home');
        }

        $previousHost = parse_url($previousUrl, PHP_URL_HOST);

        if (is_string($previousHost) && $previousHost !== '' && $previousHost !== $request->getHost()) {
            return route('home');
        }

        if (! $this->isAllowedPath($previousUrl)) {
            return route('home');
        }

        return $previousUrl;
    }

    private function isAllowedPath(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return false;
        }

        foreach (self::ALLOWED_PATH_PATTERNS as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }
}

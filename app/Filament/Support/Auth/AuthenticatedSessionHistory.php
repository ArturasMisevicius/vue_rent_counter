<?php

namespace App\Filament\Support\Auth;

use Illuminate\Contracts\Cookie\Factory as CookieFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class AuthenticatedSessionHistory
{
    public function __construct(
        private readonly CookieFactory $cookies,
    ) {}

    public function has(Request $request): bool
    {
        return $request->cookies->has($this->name());
    }

    public function remember(): Cookie
    {
        return $this->cookies->make(
            $this->name(),
            '1',
            $this->lifetimeMinutes(),
        );
    }

    public function forget(): Cookie
    {
        return $this->cookies->forget($this->name());
    }

    private function name(): string
    {
        return (string) config('tenanto.auth.session_history_cookie_name', 'tenanto_authenticated_session');
    }

    private function lifetimeMinutes(): int
    {
        return (int) config('tenanto.auth.session_history_cookie_minutes', 10080);
    }
}

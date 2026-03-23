<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;

final class FaviconUrlResolver
{
    public function __construct(
        private readonly Router $router,
        private readonly UrlGenerator $urlGenerator,
    ) {}

    public function resolve(): string
    {
        if ($this->router->has('favicon')) {
            return $this->urlGenerator->route('favicon');
        }

        return $this->urlGenerator->asset('favicon.ico');
    }
}

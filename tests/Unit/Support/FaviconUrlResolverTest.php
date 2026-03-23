<?php

declare(strict_types=1);

use App\Filament\Support\FaviconUrlResolver;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;

it('returns the named favicon route url when the route exists', function () {
    $router = mock(Router::class);
    $urlGenerator = mock(UrlGenerator::class);

    $router->shouldReceive('has')
        ->once()
        ->with('favicon')
        ->andReturnTrue();

    $urlGenerator->shouldReceive('route')
        ->once()
        ->with('favicon')
        ->andReturn('https://tenanto.test/favicon');

    $resolver = new FaviconUrlResolver($router, $urlGenerator);

    expect($resolver->resolve())->toBe('https://tenanto.test/favicon');
});

it('falls back to the public favicon asset when the route is unavailable', function () {
    $router = mock(Router::class);
    $urlGenerator = mock(UrlGenerator::class);

    $router->shouldReceive('has')
        ->once()
        ->with('favicon')
        ->andReturnFalse();

    $urlGenerator->shouldReceive('asset')
        ->once()
        ->with('favicon.ico')
        ->andReturn('https://tenanto.test/favicon.ico');

    $resolver = new FaviconUrlResolver($router, $urlGenerator);

    expect($resolver->resolve())->toBe('https://tenanto.test/favicon.ico');
});

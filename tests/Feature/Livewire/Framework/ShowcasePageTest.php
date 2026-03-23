<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('removes the legacy livewire showcase endpoint', function () {
    expect(Route::has('framework.livewire.showcase'))->toBeFalse();

    expect(fn () => app('router')->getRoutes()->match(Request::create('/framework/livewire-showcase', 'GET')))
        ->toThrow(NotFoundHttpException::class);
});

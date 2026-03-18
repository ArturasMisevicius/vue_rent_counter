<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->get('/__test/intended', function () {
        abort_unless(app()->runningUnitTests(), 404);

        return 'intended';
    })
    ->name('test.intended');

Route::middleware(['web', 'auth'])
    ->get('/__test/session-timeout', function () {
        abort_unless(app()->runningUnitTests(), 404);

        return 'session timeout';
    })
    ->name('test.session-timeout.web');

Route::middleware(['web', 'auth'])
    ->get('/__test/security/secure-page', function () {
        abort_unless(app()->runningUnitTests(), 404);

        return 'secure';
    })
    ->name('test.security.secure-page');

Route::middleware('web')
    ->get('/__test/errors/forbidden', function () {
        abort_unless(app()->runningUnitTests(), 404);

        abort(403);
    })
    ->name('test.errors.forbidden');

Route::middleware('web')
    ->get('/__test/errors/server', function (): never {
        abort_unless(app()->runningUnitTests(), 404);

        abort(500);
    })
    ->name('test.errors.server');

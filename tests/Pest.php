<?php

use Carbon\Carbon;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

beforeEach(function (): void {
    auth()->logout();
    Carbon::setTestNow();

    config()->set('app.supported_locales', [
        'en' => 'EN',
        'lt' => 'LT',
        'ru' => 'RU',
    ]);

    config()->set('tenanto.locales', [
        'en' => 'English',
        'lt' => 'Lietuvių',
        'ru' => 'Русский',
    ]);

    app()->setLocale(config('app.locale', 'en'));
});

afterEach(function (): void {
    auth()->logout();
    Carbon::setTestNow();
    app()->setLocale(config('app.locale', 'en'));
});

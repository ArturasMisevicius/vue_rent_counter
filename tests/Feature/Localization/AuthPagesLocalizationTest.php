<?php

declare(strict_types=1);

use function Pest\Laravel\get;

test('login page renders localized content', function () {
    app()->setLocale('lt');

    $response = get(route('login'));

    $response->assertOk();
    $response->assertSee(__('app.auth.login_page.heading'));
    $response->assertSee(__('app.auth.login_page.sign_in'));
    $response->assertSee(__('app.auth.login_page.quick_access'));
});

test('register page renders localized content', function () {
    app()->setLocale('ru');

    $response = get(route('register'));

    $response->assertOk();
    $response->assertSee(__('app.auth.register_page.heading'));
    $response->assertSee(__('app.auth.register_page.submit'));
    $response->assertSee(__('app.auth.register_page.already_have_account'));
});

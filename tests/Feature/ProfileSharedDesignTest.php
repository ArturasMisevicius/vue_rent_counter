<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\Subscription;
use App\Models\User;

it('renders shared profile design primitives across all roles', function (callable $makeUser, string $routeName): void {
    $user = $makeUser();

    if ($user->role === UserRole::ADMIN) {
        Subscription::factory()->active()->create([
            'user_id' => $user->id,
        ]);
    }

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertOk()
        ->assertSee('ds-shell', false)
        ->assertSee('ds-card', false)
        ->assertSee('ds-btn', false);
})->with([
    'superadmin profile' => [
        fn (): User => User::factory()->superadmin()->create(),
        'pages.profile.show-superadmin',
    ],
    'admin profile' => [
        fn (): User => User::factory()->admin()->create(),
        'pages.profile.show-admin',
    ],
    'manager profile' => [
        fn (): User => User::factory()->manager()->create(),
        'pages.profile.show-manager',
    ],
    'tenant profile' => [
        fn (): User => User::factory()->tenant()->create(),
        'pages.profile.show-tenant',
    ],
]);

it('renders profile language blocks and descriptive copy for each role', function (callable $makeUser, string $routeName, array $translationKeys): void {
    $user = $makeUser();
    Language::factory()->active()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'display_order' => 1]);
    Language::factory()->active()->create(['code' => 'lt', 'name' => 'Lithuanian', 'native_name' => 'Lietuviu', 'display_order' => 2]);
    Language::factory()->active()->create(['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Russkii', 'display_order' => 3]);

    if ($user->role === UserRole::ADMIN) {
        Subscription::factory()->active()->create([
            'user_id' => $user->id,
        ]);
    }

    $response = $this->actingAs($user)
        ->get(route($routeName))
        ->assertOk();

    foreach ($translationKeys as $key) {
        $response->assertSee(__($key));
    }
})->with([
    'superadmin profile copy' => [
        fn (): User => User::factory()->superadmin()->create(),
        'pages.profile.show-superadmin',
        [
            'profile.superadmin.language_form.title',
            'profile.superadmin.language_form.description',
            'profile.superadmin.profile_form.description',
        ],
    ],
    'admin profile copy' => [
        fn (): User => User::factory()->admin()->create(),
        'pages.profile.show-admin',
        [
            'profile.admin.language_form.title',
            'profile.admin.language_form.description',
            'profile.admin.profile_form.description',
            'profile.admin.password_form.description',
        ],
    ],
    'manager profile copy' => [
        fn (): User => User::factory()->manager()->create(),
        'pages.profile.show-manager',
        [
            'manager.profile.language_preference',
            'manager.profile.language_description',
            'manager.profile.account_information_description',
            'manager.profile.portfolio.description',
        ],
    ],
    'tenant profile copy' => [
        fn (): User => User::factory()->tenant()->create(),
        'pages.profile.show-tenant',
        [
            'tenant.profile.language_preference',
            'tenant.profile.language.description',
            'tenant.profile.account_information_description',
            'tenant.profile.update_description',
        ],
    ],
]);

<?php

declare(strict_types=1);

use App\Filament\Support\Auth\LoginRedirector;
use App\Filament\Support\Shell\DashboardUrlResolver;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('routes authenticated users to the shared dashboard entrypoint', function (): void {
    $organization = Organization::factory()->create();
    $redirector = app(LoginRedirector::class);

    expect($redirector->for(User::factory()->superadmin()->make()))
        ->toBe(route('filament.admin.pages.dashboard'))
        ->and($redirector->for(User::factory()->admin()->make([
            'organization_id' => null,
        ])))->toBe(route('welcome.show'))
        ->and($redirector->for(User::factory()->admin()->make([
            'organization_id' => $organization->id,
        ])))->toBe(route('filament.admin.pages.dashboard'))
        ->and($redirector->for(User::factory()->manager()->make([
            'organization_id' => $organization->id,
        ])))->toBe(route('filament.admin.pages.dashboard'))
        ->and($redirector->for(User::factory()->tenant()->make([
            'organization_id' => $organization->id,
        ])))->toBe(route('filament.admin.pages.dashboard'));
});

it('uses the shared dashboard resolver for every authenticated role', function (): void {
    $organization = Organization::factory()->create();
    $resolver = app(DashboardUrlResolver::class);

    expect($resolver->for(null))->toBe(route('login'))
        ->and($resolver->for(User::factory()->superadmin()->make()))->toBe(route('filament.admin.pages.dashboard'))
        ->and($resolver->for(User::factory()->admin()->make([
            'organization_id' => $organization->id,
        ])))->toBe(route('filament.admin.pages.dashboard'))
        ->and($resolver->for(User::factory()->manager()->make([
            'organization_id' => $organization->id,
        ])))->toBe(route('filament.admin.pages.dashboard'))
        ->and($resolver->for(User::factory()->tenant()->make([
            'organization_id' => $organization->id,
        ])))->toBe(route('filament.admin.pages.dashboard'));
});

it('redirects successful logins to the shared dashboard entrypoint', function (Closure $userFactory): void {
    $user = $userFactory();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('filament.admin.pages.dashboard'));

    $this->assertAuthenticatedAs($user);
})->with([
    'superadmin' => [
        fn (): User => User::factory()->superadmin()->create(),
    ],
    'admin' => [
        fn (): User => User::factory()->admin()->create([
            'organization_id' => Organization::factory(),
        ]),
    ],
    'manager' => [
        fn (): User => User::factory()->manager()->create(),
    ],
    'tenant' => [
        fn (): User => User::factory()->tenant()->create(),
    ],
]);

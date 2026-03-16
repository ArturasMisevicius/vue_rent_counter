<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Services\RoleDashboardResolver;
use Illuminate\Http\RedirectResponse;

it('maps each role to the expected filament dashboard route', function (UserRole $role, string $expectedRoute) {
    $resolver = app(RoleDashboardResolver::class);

    expect($resolver->dashboardRouteNameForRole($role))->toBe($expectedRoute);
})->with([
    'superadmin' => [UserRole::SUPERADMIN, 'filament.superadmin.pages.dashboard'],
    'admin' => [UserRole::ADMIN, 'filament.admin.pages.dashboard'],
    'manager' => [UserRole::MANAGER, 'filament.admin.pages.dashboard'],
    'tenant' => [UserRole::TENANT, 'filament.tenant.pages.dashboard'],
]);

it('returns a concrete redirect response for dashboard redirects', function () {
    $user = User::factory()->superadmin()->make();
    $resolver = app(RoleDashboardResolver::class);
    $response = $resolver->redirectToDashboard($user);

    expect($response)
        ->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('filament.superadmin.pages.dashboard'));
});

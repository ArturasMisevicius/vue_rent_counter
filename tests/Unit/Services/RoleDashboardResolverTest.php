<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Services\RoleDashboardResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('role dashboard resolver maps each role to canonical dashboard route', function (UserRole $role, string $expectedRoute): void {
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::SUPERADMIN ? null : 1001,
    ]);

    $resolver = app(RoleDashboardResolver::class);

    expect($resolver->dashboardRouteNameFor($user))->toBe($expectedRoute);
})->with([
    [UserRole::SUPERADMIN, 'superadmin.dashboard'],
    [UserRole::ADMIN, 'admin.dashboard'],
    [UserRole::MANAGER, 'manager.dashboard'],
    [UserRole::TENANT, 'tenant.dashboard'],
]);

test('role dashboard resolver redirects to canonical role dashboard', function (UserRole $role, string $expectedPath): void {
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::SUPERADMIN ? null : 1001,
    ]);

    $response = app(RoleDashboardResolver::class)->redirectToDashboard($user);

    expect($response->getTargetUrl())->toContain($expectedPath);
})->with([
    [UserRole::SUPERADMIN, '/superadmin/dashboard'],
    [UserRole::ADMIN, '/admin/dashboard'],
    [UserRole::MANAGER, '/manager/dashboard'],
    [UserRole::TENANT, '/tenant/dashboard'],
]);

test('role dashboard resolver only allows matching role entry access', function (): void {
    $resolver = app(RoleDashboardResolver::class);
    $manager = User::factory()->manager(1001)->create();

    expect($resolver->canAccessRoleEntry($manager, UserRole::MANAGER))->toBeTrue()
        ->and($resolver->canAccessRoleEntry($manager, UserRole::ADMIN))->toBeFalse()
        ->and($resolver->canAccessRoleEntry($manager, UserRole::SUPERADMIN))->toBeFalse()
        ->and($resolver->canAccessRoleEntry($manager, UserRole::TENANT))->toBeFalse();
});

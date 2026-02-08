<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;

beforeEach(function (): void {
    $tenantId = 1001;

    $this->superadmin = User::factory()->superadmin()->create();
    $this->admin = User::factory()->admin($tenantId)->create();
    $this->manager = User::factory()->manager($tenantId)->create();
    $this->tenant = User::factory()->tenant($tenantId, null, $this->admin->id)->create();

    Subscription::factory()->active()->create([
        'user_id' => $this->admin->id,
    ]);
});

test('role route access matrix is enforced for primary route groups', function (): void {
    $routes = [
        '/superadmin/dashboard' => [UserRole::SUPERADMIN],
        '/admin/dashboard' => [UserRole::ADMIN],
        '/manager/dashboard' => [UserRole::MANAGER],
        '/tenant/dashboard' => [UserRole::TENANT],
        '/invoices' => [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER],
    ];

    $usersByRole = [
        UserRole::SUPERADMIN->value => $this->superadmin,
        UserRole::ADMIN->value => $this->admin,
        UserRole::MANAGER->value => $this->manager,
        UserRole::TENANT->value => $this->tenant,
    ];

    foreach (array_keys($routes) as $uri) {
        auth()->logout();
        $this->app['auth']->forgetGuards();

        $this->get($uri)->assertRedirect(route('login'));
    }

    foreach ($routes as $uri => $allowedRoles) {
        $expectedLayoutMarker = str_starts_with($uri, '/tenant/')
            ? 'data-layout="tenant"'
            : 'data-layout="backoffice"';

        foreach ($usersByRole as $role => $user) {
            $response = $this->actingAs($user)->get($uri);

            if (in_array(UserRole::from($role), $allowedRoles, true)) {
                $response->assertOk();
                $response->assertSee($expectedLayoutMarker, false);
            } else {
                $response->assertForbidden();
            }
        }
    }
});

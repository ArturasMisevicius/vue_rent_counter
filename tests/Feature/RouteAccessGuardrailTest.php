<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;

beforeEach(function (): void {
    $tenantId = 1201;

    $this->superadmin = User::factory()->superadmin()->create();
    $this->admin = User::factory()->admin($tenantId)->create();
    $this->manager = User::factory()->manager($tenantId)->create();
    $this->property = Property::factory()->create([
        'tenant_id' => $tenantId,
    ]);
    $this->tenant = User::factory()->tenant($tenantId, $this->property->id, $this->admin->id)->create();

    Subscription::factory()->active()->create([
        'user_id' => $this->admin->id,
    ]);
});

dataset('guarded role entry routes', [
    'superadmin dashboard' => ['superadmin.dashboard', UserRole::SUPERADMIN],
    'superadmin profile' => ['superadmin.profile.show', UserRole::SUPERADMIN],
    'admin dashboard' => ['admin.dashboard', UserRole::ADMIN],
    'admin profile' => ['admin.profile.show', UserRole::ADMIN],
    'manager dashboard' => ['manager.dashboard', UserRole::MANAGER],
    'manager profile' => ['manager.profile.show', UserRole::MANAGER],
    'tenant dashboard' => ['tenant.dashboard', UserRole::TENANT],
    'tenant profile' => ['tenant.profile.show', UserRole::TENANT],
]);

it('enforces route access by role for key role entry pages', function (string $routeName, UserRole $allowedRole): void {
    $url = route($routeName);

    auth()->logout();
    $this->app['auth']->forgetGuards();

    $this->get($url)->assertRedirect(route('login'));

    $usersByRole = [
        UserRole::SUPERADMIN->value => $this->superadmin,
        UserRole::ADMIN->value => $this->admin,
        UserRole::MANAGER->value => $this->manager,
        UserRole::TENANT->value => $this->tenant,
    ];

    foreach ($usersByRole as $role => $user) {
        $response = $this->actingAs($user)->get($url);

        if ($role === $allowedRole->value) {
            $response->assertOk();

            continue;
        }

        $response->assertForbidden();
    }
})->with('guarded role entry routes');

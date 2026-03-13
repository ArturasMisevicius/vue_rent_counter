<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;

it('allows superadmin to update preferred currency', function () {
    $user = User::factory()->superadmin()->create([
        'currency' => 'EUR',
    ]);

    $this->actingAs($user)
        ->patch(route('superadmin.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'currency' => 'CHF',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->refresh()->currency)->toBe('CHF');
});

it('allows manager to update preferred currency', function () {
    $user = User::factory()->manager()->create([
        'currency' => 'EUR',
    ]);

    $this->actingAs($user)
        ->patch(route('manager.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'currency' => 'PLN',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->refresh()->currency)->toBe('PLN');
});

it('allows admin to update preferred currency', function () {
    $user = User::factory()->admin()->create([
        'currency' => 'EUR',
    ]);

    Subscription::factory()->active()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->patch(route('admin.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'organization_name' => $user->organization_name,
            'currency' => 'GBP',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->refresh()->currency)->toBe('GBP');
});

it('rejects unsupported currency for privileged profile updates', function (UserRole $role, string $routeName): void {
    $user = User::factory()->create([
        'role' => $role,
        'currency' => 'EUR',
    ]);

    if ($role === UserRole::SUPERADMIN) {
        $user->tenant_id = null;
        $user->save();
    }

    if ($role === UserRole::ADMIN) {
        Subscription::factory()->active()->create([
            'user_id' => $user->id,
        ]);
    }

    $payload = [
        'name' => $user->name,
        'email' => $user->email,
        'currency' => 'USD',
    ];

    if ($role === UserRole::ADMIN) {
        $payload['organization_name'] = $user->organization_name;
    }

    $this->actingAs($user)
        ->patch(route($routeName), $payload)
        ->assertSessionHasErrors('currency');

    expect($user->refresh()->currency)->toBe('EUR');
})->with([
    [UserRole::SUPERADMIN, 'superadmin.profile.update'],
    [UserRole::ADMIN, 'admin.profile.update'],
    [UserRole::MANAGER, 'manager.profile.update'],
]);

it('does not allow tenant profile update endpoint to change currency', function () {
    $user = User::factory()->tenant()->create([
        'currency' => 'EUR',
    ]);

    $this->actingAs($user)
        ->put(route('tenant.profile.update'), [
            'name' => 'Tenant Renamed',
            'email' => 'tenant-currency-test@example.com',
            'currency' => 'CHF',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $user->refresh();

    expect($user->name)->toBe('Tenant Renamed')
        ->and($user->email)->toBe('tenant-currency-test@example.com')
        ->and($user->currency)->toBe('EUR');
});

<?php

declare(strict_types=1);

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'password' => Hash::make('password123'),
    ]);
});

test('tenant can view profile page', function () {
    $response = $this->actingAs($this->tenant)
        ->get(route('tenant.profile.show'));

    $response->assertOk()
        ->assertViewIs('pages.profile.show-tenant')
        ->assertViewHas('user', $this->tenant);
});

test('tenant can update name and email', function () {
    $response = $this->actingAs($this->tenant)
        ->put(route('tenant.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'newemail@example.com',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->tenant->refresh();
    expect($this->tenant->name)->toBe('Updated Name')
        ->and($this->tenant->email)->toBe('newemail@example.com');
});

test('tenant can update password with correct current password', function () {
    $response = $this->actingAs($this->tenant)
        ->put(route('tenant.profile.update'), [
            'name' => $this->tenant->name,
            'email' => $this->tenant->email,
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->tenant->refresh();
    expect(Hash::check('newpassword123', $this->tenant->password))->toBeTrue();
});

test('tenant cannot update password with incorrect current password', function () {
    $response = $this->actingAs($this->tenant)
        ->put(route('tenant.profile.update'), [
            'name' => $this->tenant->name,
            'email' => $this->tenant->email,
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

    $response->assertSessionHasErrors('current_password');

    $this->tenant->refresh();
    expect(Hash::check('password123', $this->tenant->password))->toBeTrue();
});

test('tenant cannot update password without current password', function () {
    $response = $this->actingAs($this->tenant)
        ->put(route('tenant.profile.update'), [
            'name' => $this->tenant->name,
            'email' => $this->tenant->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

    $response->assertSessionHasErrors('current_password');
});

test('tenant cannot update with invalid email', function () {
    $response = $this->actingAs($this->tenant)
        ->put(route('tenant.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'invalid-email',
        ]);

    $response->assertSessionHasErrors('email');
});

test('tenant cannot update with duplicate email', function () {
    $otherUser = User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $response = $this->actingAs($this->tenant)
        ->put(route('tenant.profile.update'), [
            'name' => $this->tenant->name,
            'email' => 'existing@example.com',
        ]);

    $response->assertSessionHasErrors('email');
});

test('tenant cannot update with short password', function () {
    $response = $this->actingAs($this->tenant)
        ->put(route('tenant.profile.update'), [
            'name' => $this->tenant->name,
            'email' => $this->tenant->email,
            'current_password' => 'password123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

    $response->assertSessionHasErrors('password');
});

test('tenant cannot update with mismatched password confirmation', function () {
    $response = $this->actingAs($this->tenant)
        ->put(route('tenant.profile.update'), [
            'name' => $this->tenant->name,
            'email' => $this->tenant->email,
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

    $response->assertSessionHasErrors('password');
});

test('tenant can update profile without changing password', function () {
    $response = $this->actingAs($this->tenant)
        ->put(route('tenant.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'newemail@example.com',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->tenant->refresh();
    expect($this->tenant->name)->toBe('Updated Name')
        ->and($this->tenant->email)->toBe('newemail@example.com')
        ->and(Hash::check('password123', $this->tenant->password))->toBeTrue();
});

test('unauthenticated user cannot access profile', function () {
    $response = $this->get(route('tenant.profile.show'));

    $response->assertRedirect(route('login'));
});

test('non-tenant user cannot access tenant profile', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $response = $this->actingAs($admin)
        ->get(route('tenant.profile.show'));

    $response->assertForbidden();
});

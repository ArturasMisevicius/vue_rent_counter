<?php

use App\Enums\UserRole;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function registerOnboardingRouteFixtures(): void
{
    Route::get('/welcome', fn () => 'welcome')->name('welcome.show');

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

it('creates the foundation auth domain schema', function () {
    expect(Schema::hasTable('organizations'))->toBeTrue();
    expect(Schema::hasTable('subscriptions'))->toBeTrue();
    expect(Schema::hasTable('organization_invitations'))->toBeTrue();

    expect(Schema::hasColumns('users', [
        'role',
        'status',
        'locale',
        'organization_id',
        'last_login_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('organizations', [
        'name',
        'slug',
        'status',
        'owner_user_id',
    ]))->toBeTrue();

    expect(Schema::hasColumns('subscriptions', [
        'organization_id',
        'plan',
        'status',
        'starts_at',
        'expires_at',
        'is_trial',
    ]))->toBeTrue();

    expect(Schema::hasColumns('organization_invitations', [
        'organization_id',
        'inviter_user_id',
        'email',
        'role',
        'full_name',
        'token',
        'expires_at',
        'accepted_at',
    ]))->toBeTrue();
});

it('exposes role helpers for shared auth routing', function () {
    $superadmin = User::factory()->superadmin()->make();
    $admin = User::factory()->admin()->make(['organization_id' => null]);
    $manager = User::factory()->manager()->make();
    $tenant = User::factory()->tenant()->make();
    $panel = Panel::make()->id('admin');

    expect($superadmin->isSuperadmin())->toBeTrue()
        ->and($admin->isAdminLike())->toBeTrue()
        ->and($manager->isAdminLike())->toBeTrue()
        ->and($tenant->isAdminLike())->toBeFalse()
        ->and($superadmin->canAccessPanel($panel))->toBeTrue()
        ->and($admin->canAccessPanel($panel))->toBeTrue()
        ->and($manager->canAccessPanel($panel))->toBeTrue()
        ->and($tenant->canAccessPanel($panel))->toBeFalse();
});

it('renders the register page', function () {
    registerOnboardingRouteFixtures();

    $this->get(route('register'))
        ->assertSuccessful()
        ->assertSeeText('Create your account')
        ->assertSeeText('Full Name')
        ->assertSeeText('Email Address')
        ->assertSeeText('Password')
        ->assertSeeText('Confirm Password')
        ->assertSeeText('Create Account');
});

it('registers an admin and redirects to welcome', function () {
    registerOnboardingRouteFixtures();

    $response = $this->post(route('register.store'), [
        'name' => 'Asta Admin',
        'email' => 'asta@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('welcome.show'));
    $this->assertAuthenticated();

    $user = User::firstOrFail();

    expect($user->role)->toBe(UserRole::ADMIN)
        ->and($user->organization_id)->toBeNull();
});

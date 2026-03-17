<?php

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function registerDashboardRouteFixtures(): void
{
    if (! Route::has('filament.admin.pages.organization-dashboard')) {
        Route::get('/__test/organization-dashboard', fn () => 'organization dashboard')
            ->name('filament.admin.pages.organization-dashboard');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

beforeEach(function (): void {
    registerDashboardRouteFixtures();
});

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
        ->and($tenant->canAccessPanel($panel))->toBeTrue();
});

it('renders the register page', function () {
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

it('allows an incomplete admin to view onboarding', function () {
    $admin = User::factory()->admin()->create([
        'organization_id' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('welcome.show'))
        ->assertSuccessful()
        ->assertSeeText('Start your free trial')
        ->assertSeeText('Set up your organization to unlock your admin workspace.')
        ->assertSeeText('Organization Name')
        ->assertSeeText('Organization Slug')
        ->assertSeeText('Activate Free Trial');
});

it('completes onboarding and creates the organization trial subscription', function () {
    $admin = User::factory()->admin()->create([
        'organization_id' => null,
    ]);

    $response = $this->actingAs($admin)->post(route('welcome.store'), [
        'name' => 'North Hall',
        'slug' => 'north-hall',
    ]);

    $response->assertRedirect(route('filament.admin.pages.dashboard'));

    $organization = Organization::query()->firstOrFail();
    $subscription = Subscription::query()->firstOrFail();

    expect($organization->name)->toBe('North Hall')
        ->and($organization->slug)->toBe('north-hall')
        ->and($organization->owner_user_id)->toBe($admin->id)
        ->and($admin->fresh()->organization_id)->toBe($organization->id)
        ->and($subscription->organization_id)->toBe($organization->id)
        ->and($subscription->plan)->toBe(SubscriptionPlan::BASIC)
        ->and($subscription->status)->toBe(SubscriptionStatus::TRIALING)
        ->and($subscription->is_trial)->toBeTrue();
});

it('requires a unique slug during onboarding', function () {
    Organization::factory()->create([
        'slug' => 'north-hall',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => null,
    ]);

    $this->actingAs($admin)
        ->from(route('welcome.show'))
        ->post(route('welcome.store'), [
            'name' => 'Another North Hall',
            'slug' => 'north-hall',
        ])
        ->assertRedirect(route('welcome.show'))
        ->assertSessionHasErrors(['slug']);
});

it('blocks repeat onboarding access after completion', function () {
    $organization = Organization::factory()->create([
        'slug' => 'original-slug',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $organization->update([
        'owner_user_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('welcome.show'))
        ->assertRedirect(route('filament.admin.pages.dashboard'));

    $this->actingAs($admin)
        ->post(route('welcome.store'), [
            'name' => 'Changed Name',
            'slug' => 'changed-slug',
        ])
        ->assertRedirect(route('filament.admin.pages.dashboard'));

    expect($organization->fresh()->slug)->toBe('original-slug')
        ->and(Organization::query()->count())->toBe(1);
});

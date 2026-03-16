<?php

declare(strict_types=1);

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;

beforeEach(function (): void {
    $this->superadmin = User::factory()->superadmin()->create();
    $this->admin = User::factory()->admin()->create();
    $this->managedUser = User::factory()->create();
    $this->organization = Organization::factory()->create([
        'created_by' => $this->superadmin->id,
    ]);
    $this->subscription = Subscription::factory()->create([
        'user_id' => $this->managedUser->id,
    ]);
});

dataset('superadmin crud routes', [
    'organizations index' => ['superadmin.organizations.index', 'none'],
    'organizations create' => ['superadmin.organizations.create', 'none'],
    'organizations show' => ['superadmin.organizations.show', 'organization'],
    'organizations edit' => ['superadmin.organizations.edit', 'organization'],
    'subscriptions index' => ['superadmin.subscriptions.index', 'none'],
    'subscriptions show' => ['superadmin.subscriptions.show', 'subscription'],
    'subscriptions edit' => ['superadmin.subscriptions.edit', 'subscription'],
]);

it('keeps core superadmin crud pages protected', function (string $routeName, string $parameterSet): void {
    $routeParameters = match ($parameterSet) {
        'organization' => [$this->organization],
        'subscription' => [$this->subscription],
        default => [],
    };

    $url = route($routeName, $routeParameters);

    auth()->logout();
    $this->app['auth']->forgetGuards();

    $this->get($url)->assertRedirect(route('login'));

    $this->actingAs($this->superadmin)
        ->get($url)
        ->assertOk();

    $this->actingAs($this->admin)
        ->get($url)
        ->assertForbidden();
})->with('superadmin crud routes');

it('keeps the superadmin organization crud workflow available', function (): void {
    $organizationData = [
        'name' => 'Guardrail Organization',
        'slug' => 'guardrail-organization',
        'email' => 'guardrail@example.com',
        'phone' => '+37061234567',
        'domain' => 'guardrail.example.com',
        'plan' => SubscriptionPlan::PROFESSIONAL->value,
        'max_properties' => 50,
        'max_users' => 25,
        'subscription_ends_at' => now()->addYear()->format('Y-m-d H:i:s'),
        'timezone' => 'Europe/Vilnius',
        'locale' => 'en',
        'currency' => 'EUR',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post(route('superadmin.organizations.store'), $organizationData)
        ->assertForbidden();

    $this->actingAs($this->superadmin)
        ->post(route('superadmin.organizations.store'), $organizationData)
        ->assertRedirect();

    $organization = Organization::query()->where('slug', 'guardrail-organization')->firstOrFail();

    expect($organization->name)->toBe('Guardrail Organization');

    $updatedPayload = array_merge($organizationData, [
        'name' => 'Guardrail Organization Updated',
        'email' => 'updated-guardrail@example.com',
        'plan' => SubscriptionPlan::ENTERPRISE->value,
        'max_properties' => 150,
        'max_users' => 75,
    ]);

    $this->actingAs($this->admin)
        ->put(route('superadmin.organizations.update', $organization), $updatedPayload)
        ->assertForbidden();

    $this->actingAs($this->superadmin)
        ->put(route('superadmin.organizations.update', $organization), $updatedPayload)
        ->assertRedirect();

    $organization->refresh();

    expect($organization->name)->toBe('Guardrail Organization Updated')
        ->and($organization->email)->toBe('updated-guardrail@example.com')
        ->and($organization->plan)->toBe(SubscriptionPlan::ENTERPRISE);

    $this->actingAs($this->admin)
        ->delete(route('superadmin.organizations.destroy', $organization))
        ->assertForbidden();

    $this->actingAs($this->superadmin)
        ->delete(route('superadmin.organizations.destroy', $organization))
        ->assertRedirect();

    $this->assertDatabaseMissing('organizations', [
        'id' => $organization->id,
    ]);
});

it('keeps the superadmin subscription crud workflow available', function (): void {
    $subscriptionOwner = User::factory()->create();

    $subscriptionData = [
        'user_id' => $subscriptionOwner->id,
        'plan_type' => SubscriptionPlan::PROFESSIONAL->value,
        'status' => SubscriptionStatus::ACTIVE->value,
        'starts_at' => now()->format('Y-m-d H:i:s'),
        'expires_at' => now()->addYear()->format('Y-m-d H:i:s'),
        'max_properties' => 200,
        'max_tenants' => 100,
        'auto_renew' => true,
        'renewal_period' => 'annually',
    ];

    $this->actingAs($this->admin)
        ->post(route('superadmin.subscriptions.store'), $subscriptionData)
        ->assertForbidden();

    $this->actingAs($this->superadmin)
        ->post(route('superadmin.subscriptions.store'), $subscriptionData)
        ->assertRedirect();

    $subscription = Subscription::query()
        ->where('user_id', $subscriptionOwner->id)
        ->latest('id')
        ->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE);

    $updatedPayload = array_merge($subscriptionData, [
        'plan_type' => SubscriptionPlan::ENTERPRISE->value,
        'max_properties' => 500,
        'max_tenants' => 250,
        'auto_renew' => false,
    ]);

    $this->actingAs($this->admin)
        ->put(route('superadmin.subscriptions.update', $subscription), $updatedPayload)
        ->assertForbidden();

    $this->actingAs($this->superadmin)
        ->put(route('superadmin.subscriptions.update', $subscription), $updatedPayload)
        ->assertRedirect();

    $subscription->refresh();

    expect($subscription->plan_type)->toBe(SubscriptionPlan::ENTERPRISE->value)
        ->and($subscription->max_properties)->toBe(500)
        ->and($subscription->max_tenants)->toBe(250)
        ->and($subscription->auto_renew)->toBeFalse();

    $this->actingAs($this->admin)
        ->delete(route('superadmin.subscriptions.destroy', $subscription))
        ->assertForbidden();

    $this->actingAs($this->superadmin)
        ->delete(route('superadmin.subscriptions.destroy', $subscription))
        ->assertRedirect();

    $this->assertDatabaseMissing('subscriptions', [
        'id' => $subscription->id,
    ]);
});

<?php

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Subscription;
use App\Models\SystemHealthMetric;

test('Organization model has daysUntilExpiry method', function () {
    $org = Organization::factory()->create([
        'subscription_ends_at' => now()->addDays(30),
    ]);

    expect($org->daysUntilExpiry())->toBe(30);
});

test('Organization model can be suspended and reactivated', function () {
    $org = Organization::factory()->create();

    expect($org->isSuspended())->toBeFalse();

    $org->suspend('Test suspension');

    expect($org->isSuspended())->toBeTrue()
        ->and($org->suspension_reason)->toBe('Test suspension')
        ->and($org->is_active)->toBeFalse();

    $org->reactivate();

    expect($org->isSuspended())->toBeFalse()
        ->and($org->suspension_reason)->toBeNull()
        ->and($org->is_active)->toBeTrue();
});

test('Subscription model can be renewed', function () {
    $subscription = Subscription::factory()->create([
        'expires_at' => now()->addDays(30),
        'status' => 'active',
    ]);

    $newExpiry = now()->addYear();
    $subscription->renew($newExpiry);

    expect($subscription->expires_at->format('Y-m-d'))->toBe($newExpiry->format('Y-m-d'))
        ->and($subscription->status)->toBe('active');
});

test('Subscription model can be suspended and activated', function () {
    $subscription = Subscription::factory()->create([
        'status' => 'active',
    ]);

    $subscription->suspend();
    expect($subscription->status)->toBe('suspended');

    $subscription->activate();
    expect($subscription->status)->toBe('active');
});

test('Subscription model has isSuspended method', function () {
    $subscription = Subscription::factory()->create([
        'status' => 'suspended',
    ]);

    expect($subscription->isSuspended())->toBeTrue();

    $subscription->activate();
    expect($subscription->isSuspended())->toBeFalse();
});

test('OrganizationInvitation model has isPending method', function () {
    $invitation = OrganizationInvitation::factory()->create([
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    expect($invitation->isPending())->toBeTrue();

    $invitation->accept();
    expect($invitation->isPending())->toBeFalse();
});

test('OrganizationInvitation model can be resent', function () {
    $invitation = OrganizationInvitation::factory()->create([
        'expires_at' => now()->addDays(1),
    ]);

    $originalToken = $invitation->token;
    $originalExpiry = $invitation->expires_at;

    $invitation->resend();

    expect($invitation->token)->not->toBe($originalToken)
        ->and($invitation->expires_at->greaterThan($originalExpiry))->toBeTrue();
});

test('SystemHealthMetric model has status checking methods', function () {
    $healthyMetric = SystemHealthMetric::factory()->healthy()->create();
    $warningMetric = SystemHealthMetric::factory()->warning()->create();
    $dangerMetric = SystemHealthMetric::factory()->danger()->create();

    expect($healthyMetric->isHealthy())->toBeTrue()
        ->and($healthyMetric->isWarning())->toBeFalse()
        ->and($healthyMetric->isDanger())->toBeFalse();

    expect($warningMetric->isHealthy())->toBeFalse()
        ->and($warningMetric->isWarning())->toBeTrue()
        ->and($warningMetric->isDanger())->toBeFalse();

    expect($dangerMetric->isHealthy())->toBeFalse()
        ->and($dangerMetric->isWarning())->toBeFalse()
        ->and($dangerMetric->isDanger())->toBeTrue();
});

test('SystemHealthMetric model returns correct status color', function () {
    $healthyMetric = SystemHealthMetric::factory()->healthy()->create();
    $warningMetric = SystemHealthMetric::factory()->warning()->create();
    $dangerMetric = SystemHealthMetric::factory()->danger()->create();

    expect($healthyMetric->getStatusColor())->toBe('green')
        ->and($warningMetric->getStatusColor())->toBe('yellow')
        ->and($dangerMetric->getStatusColor())->toBe('red');
});

test('SystemHealthMetric model has scopes', function () {
    SystemHealthMetric::factory()->database()->healthy()->create();
    SystemHealthMetric::factory()->backup()->warning()->create();
    SystemHealthMetric::factory()->queue()->danger()->create();

    $databaseMetrics = SystemHealthMetric::latestByType('database')->get();
    expect($databaseMetrics)->toHaveCount(1)
        ->and($databaseMetrics->first()->metric_type)->toBe('database');

    $unhealthyMetrics = SystemHealthMetric::unhealthy()->get();
    expect($unhealthyMetrics)->toHaveCount(2);
});

<?php

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Organization;
use App\Models\Subscription;

it('builds stable showcase organizations for each subscription plan', function () {
    $organizations = [
        'starter' => Organization::factory()->starterShowcase()->make(),
        'basic' => Organization::factory()->basicShowcase()->make(),
        'professional' => Organization::factory()->professionalShowcase()->make(),
        'enterprise' => Organization::factory()->enterpriseShowcase()->make(),
        'custom' => Organization::factory()->customShowcase()->make(),
    ];

    expect($organizations['starter']->name)->toBe('Starter Showcase Organization')
        ->and($organizations['starter']->slug)->toBe('showcase-starter')
        ->and($organizations['starter']->status)->toBe(OrganizationStatus::ACTIVE)
        ->and($organizations['basic']->slug)->toBe('showcase-basic')
        ->and($organizations['professional']->slug)->toBe('showcase-professional')
        ->and($organizations['enterprise']->slug)->toBe('showcase-enterprise')
        ->and($organizations['custom']->slug)->toBe('showcase-custom');
});

it('hydrates subscription snapshot limits from the selected plan', function () {
    $subscription = Subscription::factory()->enterprise()->make([
        'organization_id' => null,
    ]);

    expect($subscription->plan)->toBe(SubscriptionPlan::ENTERPRISE)
        ->and($subscription->property_limit_snapshot)->toBe(SubscriptionPlan::ENTERPRISE->limits()['properties'])
        ->and($subscription->tenant_limit_snapshot)->toBe(SubscriptionPlan::ENTERPRISE->limits()['tenants'])
        ->and($subscription->meter_limit_snapshot)->toBe(SubscriptionPlan::ENTERPRISE->limits()['meters'])
        ->and($subscription->invoice_limit_snapshot)->toBe(SubscriptionPlan::ENTERPRISE->limits()['invoices']);
});

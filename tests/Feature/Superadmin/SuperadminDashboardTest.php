<?php

use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the superadmin dashboard metrics', function () {
    $superadmin = User::factory()->superadmin()->create();

    Organization::factory()->count(3)->create();
    Subscription::factory()->count(2)->create([
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
    ]);
    SubscriptionPayment::factory()->create([
        'amount' => 9900,
        'paid_at' => now(),
    ]);
    SecurityViolation::factory()->count(2)->create();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertOk()
        ->assertSeeText('Total Organizations')
        ->assertSeeText('Active Subscriptions')
        ->assertSeeText('Platform Revenue This Month')
        ->assertSeeText('Security Violations (7 Days)');
});

<?php

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
    Subscription::factory()->count(2)->active()->create();
    SubscriptionPayment::factory()->create([
        'amount' => 9900,
        'paid_at' => now(),
    ]);
    SecurityViolation::factory()->count(2)->create([
        'occurred_at' => now()->subDays(2),
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Total Organizations')
        ->assertSeeText('Active Subscriptions')
        ->assertSeeText('Platform Revenue This Month')
        ->assertSeeText('Security Violations (7 Days)')
        ->assertSeeText('3')
        ->assertSeeText('2');
});

it('keeps the platform dashboard restricted to superadmins', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertForbidden();
});

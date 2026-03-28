<?php

use App\Enums\OrganizationStatus;
use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows security health counts, unreviewed violations, and user last logins on the org detail page', function () {
    $organization = Organization::factory()->create([
        'name' => 'Northwind Security',
        'slug' => 'northwind-security',
        'status' => OrganizationStatus::ACTIVE,
    ]);

    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Olivia Owner',
        'email' => 'owner@northwind.test',
        'last_login_at' => now()->subHours(2),
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'name' => 'Maya Manager',
        'email' => 'maya.manager@northwind.test',
        'last_login_at' => now()->subDay(),
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    SecurityViolation::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $owner->id,
        'type' => SecurityViolationType::AUTHENTICATION,
        'severity' => SecurityViolationSeverity::CRITICAL,
        'occurred_at' => now()->subDays(3),
        'metadata' => ['source' => 'test'],
    ]);

    SecurityViolation::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'type' => SecurityViolationType::RATE_LIMIT,
        'severity' => SecurityViolationSeverity::HIGH,
        'occurred_at' => now()->subDays(5),
        'metadata' => [
            'source' => 'test',
            'review' => [
                'reviewed_at' => now()->subDay()->toIso8601String(),
                'reviewed_by_user_id' => $owner->id,
                'note' => 'Reviewed already',
            ],
        ],
    ]);

    SecurityViolation::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => null,
        'type' => SecurityViolationType::SUSPICIOUS_IP,
        'severity' => SecurityViolationSeverity::LOW,
        'occurred_at' => now()->subDays(7),
        'metadata' => ['source' => 'test'],
    ]);

    SecurityViolation::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => null,
        'type' => SecurityViolationType::AUTHORIZATION,
        'severity' => SecurityViolationSeverity::MEDIUM,
        'occurred_at' => now()->subDays(45),
        'metadata' => ['source' => 'test'],
    ]);

    $superadmin = User::factory()->superadmin()->create();

    $securityViolationsUrl = htmlspecialchars(
        route('filament.admin.resources.security-violations.index').'?'.http_build_query([
            'tableFilters' => [
                'organization' => [
                    'value' => $organization->getKey(),
                ],
            ],
        ]),
        ENT_QUOTES,
        'UTF-8',
        false,
    );

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.view', $organization))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.organizations.overview.security_health_heading'))
        ->assertSeeText(__('superadmin.organizations.overview.security_health_labels.critical'))
        ->assertSeeText(__('superadmin.organizations.overview.security_health_labels.high'))
        ->assertSeeText(__('superadmin.organizations.overview.security_health_labels.medium'))
        ->assertSeeText(__('superadmin.organizations.overview.security_health_labels.low'))
        ->assertSeeText(__('superadmin.organizations.overview.security_health_labels.unreviewed'))
        ->assertSeeText('1')
        ->assertSeeText('2')
        ->assertSeeText('Olivia Owner')
        ->assertSeeText('Maya Manager')
        ->assertSee($securityViolationsUrl, false);
});

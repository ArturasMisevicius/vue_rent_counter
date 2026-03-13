<?php

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('organization factory populates slug and defaults', function () {
    $organization = Organization::factory()->create([
        'name' => 'Example Seed Org',
    ]);

    expect($organization->slug)->toBe('example-seed-org')
        ->and($organization->plan)->not->toBeEmpty()
        ->and($organization->settings)->not->toBeNull()
        ->and($organization->features)->not->toBeNull()
        ->and($organization->is_active)->toBeTrue();
});

test('organization factory suspended state sets flags', function () {
    $organization = Organization::factory()->suspended()->create();

    expect($organization->is_active)->toBeFalse()
        ->and($organization->suspended_at)->not->toBeNull()
        ->and($organization->suspension_reason)->toBe('Suspended for non-payment');
});

<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves shared translation key for manager profile title', function (): void {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager);

    expect(__('shared.profile.title'))->toBe(__('manager.profile.title'));
});

it('resolves shared translation key for tenant profile title', function (): void {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant);

    expect(__('shared.profile.title'))->toBe(__('tenant.profile.title'));
});

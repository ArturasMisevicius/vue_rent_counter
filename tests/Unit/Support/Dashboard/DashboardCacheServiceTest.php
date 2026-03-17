<?php

use App\Enums\UserRole;
use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('cache.default', 'array');
    Cache::flush();
});

it('uses the expected ttl per dashboard role', function () {
    $service = app(DashboardCacheService::class);

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->tenant()->create();

    expect($service->ttlFor($superadmin))->toBe(60)
        ->and($service->ttlFor($admin))->toBe(30)
        ->and($service->ttlFor($tenant))->toBe(120);
});

it('scopes dashboard cache entries by user id', function () {
    $service = app(DashboardCacheService::class);

    $firstAdmin = User::factory()->admin()->create();
    $secondAdmin = User::factory()->admin()->create();

    $firstValue = $service->remember($firstAdmin, 'stats', fn (): array => ['owner' => $firstAdmin->id]);
    $secondValue = $service->remember($secondAdmin, 'stats', fn (): array => ['owner' => $secondAdmin->id]);

    expect($firstValue)->toBe(['owner' => $firstAdmin->id])
        ->and($secondValue)->toBe(['owner' => $secondAdmin->id])
        ->and($service->keyFor($firstAdmin, 'stats'))->not->toBe($service->keyFor($secondAdmin, 'stats'));
});

it('treats managers as admin dashboard users for ttl purposes', function () {
    $service = app(DashboardCacheService::class);
    $manager = User::factory()->manager()->create([
        'role' => UserRole::MANAGER,
    ]);

    expect($service->ttlFor($manager))->toBe(30);
});

<?php

use App\Enums\UserRole;
use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Models\Organization;
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

it('increments organization version keys used in dashboard cache keys', function () {
    $service = app(DashboardCacheService::class);
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $firstKey = $service->keyFor($admin, 'stats');

    $service->touchOrganization($organization->id);

    $secondKey = $service->keyFor($admin, 'stats');

    expect($firstKey)->toContain('org-version-1')
        ->and($secondKey)->toContain('org-version-2')
        ->and($secondKey)->not->toBe($firstKey);
});

it('flushes in-request memoized dashboard segments when organization versions change', function () {
    $service = app(DashboardCacheService::class);
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $callbackRuns = 0;

    $firstPayload = $service->remember($admin, 'stats', function () use (&$callbackRuns): array {
        $callbackRuns++;

        return ['run' => $callbackRuns];
    });

    $service->touchOrganization($organization->id);

    $secondPayload = $service->remember($admin, 'stats', function () use (&$callbackRuns): array {
        $callbackRuns++;

        return ['run' => $callbackRuns];
    });

    expect($firstPayload)->toBe(['run' => 1])
        ->and($secondPayload)->toBe(['run' => 2])
        ->and($callbackRuns)->toBe(2);
});

it('falls back to put when organization version touch fails during a mutation', function () {
    $service = app(DashboardCacheService::class);
    $organization = Organization::factory()->create();
    $versionKey = 'dashboard:organization-version:'.$organization->id;

    Cache::shouldReceive('add')
        ->once()
        ->withArgs(fn (string $key, int $value, mixed $ttl): bool => $key === $versionKey
            && $value === 1
            && $ttl instanceof DateTimeInterface)
        ->andReturn(false);

    Cache::shouldReceive('increment')
        ->once()
        ->with($versionKey)
        ->andReturn(5);

    Cache::shouldReceive('touch')
        ->once()
        ->withArgs(fn (string $key, mixed $ttl): bool => $key === $versionKey
            && $ttl instanceof DateTimeInterface)
        ->andReturn(false);

    Cache::shouldReceive('put')
        ->once()
        ->withArgs(fn (string $key, int $value, mixed $ttl): bool => $key === $versionKey
            && $value === 5
            && $ttl instanceof DateTimeInterface)
        ->andReturnTrue();

    $service->touchOrganization($organization->id);
});

it('falls back to put when organization version touch fails during key resolution', function () {
    $service = app(DashboardCacheService::class);
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $versionKey = 'dashboard:organization-version:'.$organization->id;

    Cache::shouldReceive('add')
        ->once()
        ->withArgs(fn (string $key, int $value, mixed $ttl): bool => $key === $versionKey
            && $value === 1
            && $ttl instanceof DateTimeInterface)
        ->andReturn(false);

    Cache::shouldReceive('get')
        ->once()
        ->with($versionKey, 1)
        ->andReturn(7);

    Cache::shouldReceive('touch')
        ->once()
        ->withArgs(fn (string $key, mixed $ttl): bool => $key === $versionKey
            && $ttl instanceof DateTimeInterface)
        ->andReturn(false);

    Cache::shouldReceive('put')
        ->once()
        ->withArgs(fn (string $key, int $value, mixed $ttl): bool => $key === $versionKey
            && $value === 7
            && $ttl instanceof DateTimeInterface)
        ->andReturnTrue();

    expect($service->keyFor($admin, 'stats'))->toContain('org-version-7');
});

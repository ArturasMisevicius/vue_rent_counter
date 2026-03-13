<?php

declare(strict_types=1);

use App\Models\Tariff;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('default database seeder seeds base tariffs for admin pages', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    expect(Tariff::query()->where('name', 'Ignitis Standard Time-of-Use')->exists())->toBeTrue()
        ->and(Tariff::query()->where('name', 'VV Standard Water Rates')->exists())->toBeTrue()
        ->and(Tariff::query()->where('name', 'VE Heating Standard')->exists())->toBeTrue()
        ->and(Tariff::query()->count())->toBeGreaterThanOrEqual(3);

    Tariff::query()->each(function (Tariff $tariff): void {
        expect($tariff->provider_id)->not->toBeNull()
            ->and($tariff->configuration)->toBeArray()
            ->and($tariff->configuration['currency'] ?? null)->toBe('EUR');
    });
});

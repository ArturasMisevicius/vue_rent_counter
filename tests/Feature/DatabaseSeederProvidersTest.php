<?php

declare(strict_types=1);

use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('default database seeder seeds base providers for admin pages', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    expect(Provider::query()->where('name', 'Ignitis')->exists())->toBeTrue()
        ->and(Provider::query()->where('name', 'Vilniaus Vandenys')->exists())->toBeTrue()
        ->and(Provider::query()->where('name', 'Vilniaus Energija')->exists())->toBeTrue()
        ->and(Provider::count())->toBeGreaterThanOrEqual(3);
});

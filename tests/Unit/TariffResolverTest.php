<?php

use App\Models\Provider;
use App\Models\Tariff;
use App\Services\TariffResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->resolver = new TariffResolver();
});

test('resolve returns active tariff for given date', function () {
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()
        ->for($provider)
        ->activeFrom(Carbon::parse('2024-01-01'))
        ->create();

    $resolved = $this->resolver->resolve($provider, Carbon::parse('2024-06-15'));

    expect($resolved->id)->toBe($tariff->id);
});

test('resolve returns most recent tariff when multiple are active', function () {
    $provider = Provider::factory()->create();
    
    $olderTariff = Tariff::factory()
        ->for($provider)
        ->activeFrom(Carbon::parse('2024-01-01'))
        ->create();
    
    $newerTariff = Tariff::factory()
        ->for($provider)
        ->activeFrom(Carbon::parse('2024-06-01'))
        ->create();

    $resolved = $this->resolver->resolve($provider, Carbon::parse('2024-07-01'));

    expect($resolved->id)->toBe($newerTariff->id);
});

test('resolve respects active_until date', function () {
    $provider = Provider::factory()->create();
    
    $expiredTariff = Tariff::factory()
        ->for($provider)
        ->activeFrom(Carbon::parse('2024-01-01'))
        ->activeUntil(Carbon::parse('2024-05-31'))
        ->create();
    
    $currentTariff = Tariff::factory()
        ->for($provider)
        ->activeFrom(Carbon::parse('2024-06-01'))
        ->create();

    $resolved = $this->resolver->resolve($provider, Carbon::parse('2024-07-01'));

    expect($resolved->id)->toBe($currentTariff->id);
});

test('resolve throws exception when no active tariff exists', function () {
    $provider = Provider::factory()->create();
    
    Tariff::factory()
        ->for($provider)
        ->activeFrom(Carbon::parse('2024-06-01'))
        ->create();

    $this->resolver->resolve($provider, Carbon::parse('2024-01-01'));
})->throws(ModelNotFoundException::class);

test('calculateCost works with flat rate tariff', function () {
    $tariff = Tariff::factory()->flat()->make([
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
        ],
    ]);

    $cost = $this->resolver->calculateCost($tariff, 100.0);

    expect($cost)->toBe(15.0);
});

test('calculateCost works with time-of-use tariff during day', function () {
    $tariff = Tariff::factory()->timeOfUse()->make();
    $timestamp = Carbon::parse('2024-06-15 14:00:00'); // Saturday afternoon

    $cost = $this->resolver->calculateCost($tariff, 100.0, $timestamp);

    expect($cost)->toBe(18.0); // 100 * 0.18 (day rate)
});

test('calculateCost works with time-of-use tariff during night', function () {
    $tariff = Tariff::factory()->timeOfUse()->make();
    $timestamp = Carbon::parse('2024-06-17 02:00:00'); // Monday night

    $cost = $this->resolver->calculateCost($tariff, 100.0, $timestamp);

    expect($cost)->toBe(10.0); // 100 * 0.10 (night rate)
});

test('calculateCost applies weekend logic correctly', function () {
    $tariff = Tariff::factory()->ignitis()->make();
    $timestamp = Carbon::parse('2024-06-15 14:00:00'); // Saturday afternoon

    $cost = $this->resolver->calculateCost($tariff, 100.0, $timestamp);

    expect($cost)->toBe(10.0); // 100 * 0.10 (night rate applied on weekend)
});

test('calculateCost handles midnight crossing time ranges', function () {
    $tariff = Tariff::factory()->timeOfUse()->make();
    $timestamp = Carbon::parse('2024-06-17 23:30:00'); // Monday 23:30 (night time)

    $cost = $this->resolver->calculateCost($tariff, 100.0, $timestamp);

    expect($cost)->toBe(10.0); // 100 * 0.10 (night rate)
});

test('calculateCost uses current time when timestamp not provided', function () {
    $tariff = Tariff::factory()->flat()->make([
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.20,
        ],
    ]);

    $cost = $this->resolver->calculateCost($tariff, 50.0);

    expect($cost)->toBe(10.0);
});


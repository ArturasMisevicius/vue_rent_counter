<?php

declare(strict_types=1);

use App\Enums\MeterType;
use App\Filament\Support\Tenant\Portal\TenantMeterNameLocalizer;
use App\Models\Meter;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    app()->setLocale(config('app.locale', 'en'));
});

it('localizes operations demo meter names for every supported locale', function (string $locale, string $expected): void {
    app()->setLocale($locale);

    $meter = new Meter([
        'name' => 'Operations Demo Meter',
        'type' => MeterType::ELECTRICITY,
    ]);

    expect(app(TenantMeterNameLocalizer::class)->displayName($meter))->toBe($expected);
})->with([
    'english' => ['en', 'Operations Demo Meter: Electricity'],
    'lithuanian' => ['lt', 'Operacijų demonstracinis skaitiklis: Elektra'],
    'russian' => ['ru', 'Демонстрационный операционный счетчик: Электричество'],
    'spanish' => ['es', 'Contador de demostración de operaciones: Electricidad'],
]);

it('localizes generated demo meter names for every supported locale', function (string $locale, string $expected): void {
    app()->setLocale($locale);

    $meter = new Meter([
        'name' => 'Demo Electricity Meter',
        'type' => MeterType::ELECTRICITY,
    ]);

    expect(app(TenantMeterNameLocalizer::class)->displayName($meter))->toBe($expected);
})->with([
    'english' => ['en', 'Demo Electricity Meter'],
    'lithuanian' => ['lt', 'Demonstracinis skaitiklis: Elektra'],
    'russian' => ['ru', 'Демонстрационный счетчик: Электричество'],
    'spanish' => ['es', 'Contador de demostración: Electricidad'],
]);

it('recognizes generated demo names created with localized enum labels', function (): void {
    app()->setLocale('ru');

    $meter = new Meter([
        'name' => 'Demo Elektra Meter',
        'type' => MeterType::ELECTRICITY,
    ]);

    expect(app(TenantMeterNameLocalizer::class)->displayName($meter))
        ->toBe('Демонстрационный счетчик: Электричество');
});

it('keeps custom meter names unchanged', function (): void {
    app()->setLocale('lt');

    $meter = new Meter([
        'name' => 'Main Hall Meter',
        'type' => MeterType::ELECTRICITY,
    ]);

    expect(app(TenantMeterNameLocalizer::class)->displayName($meter))->toBe('Main Hall Meter');
});

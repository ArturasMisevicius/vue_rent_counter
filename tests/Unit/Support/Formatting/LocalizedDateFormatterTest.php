<?php

declare(strict_types=1);

use App\Filament\Support\Formatting\LocalizedDateFormatter;
use Carbon\CarbonImmutable;
use Tests\TestCase;

uses(TestCase::class);

it('formats lithuanian dates with full month names', function (): void {
    app()->setLocale('lt');

    expect(LocalizedDateFormatter::date(CarbonImmutable::parse('2026-03-02')))
        ->toBe('2026 m. kovo 2 d.')
        ->not->toContain('kov 2, 2026');
});

it('formats localized date times with full date names', function (): void {
    app()->setLocale('lt');

    expect(LocalizedDateFormatter::dateTime(CarbonImmutable::parse('2026-03-28 18:50', 'Europe/Vilnius')))
        ->toBe('2026 m. kovo 28 d. 18:50');
});

it('provides locale-aware filament date formats', function (string $locale, string $expectedDate, string $expectedDateTime): void {
    app()->setLocale($locale);

    $date = CarbonImmutable::parse('2026-04-02 09:15', 'Europe/Vilnius')
        ->locale(app()->getLocale());

    expect($date->translatedFormat(LocalizedDateFormatter::dateFormat()))
        ->toBe($expectedDate)
        ->not->toContain(' m. ')
        ->not->toContain(' d.');

    expect($date->translatedFormat(LocalizedDateFormatter::dateTimeFormat()))
        ->toBe($expectedDateTime)
        ->not->toContain(' m. ')
        ->not->toContain(' d.');
})->with([
    'spanish' => ['es', '2 de abril de 2026', '2 de abril de 2026 09:15'],
    'english' => ['en', 'April 2, 2026', 'April 2, 2026 09:15'],
]);

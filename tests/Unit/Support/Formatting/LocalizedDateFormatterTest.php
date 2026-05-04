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

<?php

declare(strict_types=1);

use App\Filament\Support\Formatting\LocalizedNumberFormatter;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    app()->setLocale(config('app.locale', 'en'));
});

it('formats fixed decimal values for the active locale', function (): void {
    app()->setLocale('lt');

    expect(LocalizedNumberFormatter::decimal(1234.5, 2))
        ->toBe("1\u{00A0}234,50");
});

it('formats integers without fraction digits', function (): void {
    app()->setLocale('en');

    expect(LocalizedNumberFormatter::integer(1234.5))
        ->toBe('1,234');
});

it('formats flexible decimal values without forced trailing zeroes', function (): void {
    app()->setLocale('lt');

    expect(LocalizedNumberFormatter::flexible(1234.5, 0, 3))
        ->toBe("1\u{00A0}234,5");
});

it('returns a placeholder for missing values', function (): void {
    expect(LocalizedNumberFormatter::decimal(null))
        ->toBe('—')
        ->and(LocalizedNumberFormatter::decimal(''))
        ->toBe('—');
});

<?php

declare(strict_types=1);

use App\Filament\Support\Formatting\MeasurementFormatter;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    app()->setLocale(config('app.locale', 'en'));
});

it('formats square meter values with a hard space before the unit', function (): void {
    app()->setLocale('lt');

    expect(MeasurementFormatter::squareMeters(48.25))
        ->toBe("48,25\u{00A0}m²");
});

it('formats meter readings with localized decimals and units', function (): void {
    app()->setLocale('lt');

    expect(MeasurementFormatter::meterReading(1234.5, 'm³'))
        ->toBe("1\u{00A0}234,500\u{00A0}m³");
});

it('returns only the localized number when no unit is provided', function (): void {
    app()->setLocale('lt');

    expect(MeasurementFormatter::format(12.5, null, 1))
        ->toBe('12,5');
});

it('returns a placeholder for missing values', function (): void {
    expect(MeasurementFormatter::consumption(null, 'kWh'))
        ->toBe('—');
});

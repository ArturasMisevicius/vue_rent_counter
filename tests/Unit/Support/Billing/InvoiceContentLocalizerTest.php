<?php

declare(strict_types=1);

use App\Filament\Support\Billing\InvoiceContentLocalizer;
use Tests\TestCase;

uses(TestCase::class);

it('localizes seeded invoice line item descriptions and units for supported locales', function (string $locale, string $description, string $unit): void {
    app()->setLocale($locale);

    $localizer = app(InvoiceContentLocalizer::class);

    expect($localizer->lineItemDescription('Shared services fee'))->toBe($description)
        ->and($localizer->unit('month'))->toBe($unit);
})->with([
    'english' => ['en', 'Shared services fee', 'month'],
    'lithuanian' => ['lt', 'Bendrų paslaugų mokestis', 'mėn.'],
    'spanish' => ['es', 'Cuota de servicios comunes', 'mes'],
    'russian' => ['ru', 'Плата за общие услуги', 'мес.'],
]);

it('keeps custom invoice content unchanged', function (): void {
    app()->setLocale('lt');

    $localizer = app(InvoiceContentLocalizer::class);

    expect($localizer->lineItemDescription('Custom concierge package'))->toBe('Custom concierge package')
        ->and($localizer->unit('custom-unit'))->toBe('custom-unit');
});

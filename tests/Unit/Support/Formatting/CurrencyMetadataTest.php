<?php

declare(strict_types=1);

use App\Filament\Support\Formatting\CurrencyMetadata;

it('resolves currency metadata through symfony intl', function (): void {
    expect(CurrencyMetadata::symbol('EUR', 'en'))->toBe('€')
        ->and(CurrencyMetadata::name('EUR', 'en'))->toBe('Euro')
        ->and(CurrencyMetadata::fractionDigits('EUR'))->toBe(2);
});

it('falls back to the normalized code for unknown currencies', function (): void {
    expect(CurrencyMetadata::symbol('x-demo'))->toBe('X-DEMO')
        ->and(CurrencyMetadata::name('x-demo'))->toBe('X-DEMO')
        ->and(CurrencyMetadata::fractionDigits('x-demo'))->toBe(2);
});

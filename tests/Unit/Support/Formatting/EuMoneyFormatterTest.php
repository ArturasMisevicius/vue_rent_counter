<?php

declare(strict_types=1);

use App\Filament\Support\Formatting\EuMoneyFormatter;

it('formats euro prices with EU separators and trailing symbol', function (): void {
    expect(EuMoneyFormatter::format(1234.56))
        ->toBe("1 234,56\u{00A0}€");
});

it('keeps non euro currency codes after the amount', function (): void {
    expect(EuMoneyFormatter::format(75, 'USD'))
        ->toBe("75,00\u{00A0}USD");
});

it('formats numeric values with a comma decimal separator', function (): void {
    expect(EuMoneyFormatter::number(1234.5))
        ->toBe('1 234,50');
});

<?php

declare(strict_types=1);

use App\Filament\Support\Formatting\EuMoneyFormatter;
use Brick\Money\Money;

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

it('formats brick money values with the same eu display rules', function (): void {
    expect(EuMoneyFormatter::format(Money::of('1234.56', 'EUR')))
        ->toBe("1 234,56\u{00A0}€");
});

it('creates money from minor units before formatting', function (): void {
    expect(EuMoneyFormatter::format(EuMoneyFormatter::moneyFromMinor(123456, 'EUR')))
        ->toBe("1 234,56\u{00A0}€");
});

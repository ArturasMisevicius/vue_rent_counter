<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\Currency;
use Carbon\Carbon;

final readonly class ConversionResult
{
    public function __construct(
        public float $originalAmount,
        public float $convertedAmount,
        public ?Currency $fromCurrency,
        public ?Currency $toCurrency,
        public ?float $exchangeRate,
        public Carbon $conversionDate,
        public string $source,
        public ?string $error = null
    ) {}

    /**
     * Check if the conversion was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->error === null && $this->exchangeRate !== null;
    }

    /**
     * Check if this is a same currency conversion.
     */
    public function isSameCurrency(): bool
    {
        return $this->source === 'same_currency';
    }

    /**
     * Check if the conversion is reliable (from database or same currency).
     */
    public function isReliable(): bool
    {
        return in_array($this->source, ['database', 'same_currency']);
    }

    /**
     * Get the original amount formatted with currency.
     */
    public function getFormattedOriginalAmount(): string
    {
        $symbol = $this->fromCurrency?->symbol ?? '';
        $formatted = $this->fromCurrency?->formatAmount($this->originalAmount) ?? number_format($this->originalAmount, 2);
        return "{$symbol} {$formatted}";
    }

    /**
     * Get the converted amount formatted with currency.
     */
    public function getFormattedConvertedAmount(): string
    {
        $symbol = $this->toCurrency?->symbol ?? '';
        $formatted = $this->toCurrency?->formatAmount($this->convertedAmount) ?? number_format($this->convertedAmount, 2);
        return "{$symbol} {$formatted}";
    }

    /**
     * Get a summary of the conversion.
     */
    public function getConversionSummary(): string
    {
        if ($this->hasFailed()) {
            return "Conversion failed: {$this->error}";
        }

        return "{$this->getFormattedOriginalAmount()} → {$this->getFormattedConvertedAmount()}";
    }

    /**
     * Get the conversion factor (same as exchange rate).
     */
    public function getConversionFactor(): ?float
    {
        return $this->exchangeRate;
    }

    /**
     * Get the original amount.
     */
    public function getOriginalAmount(): float
    {
        return $this->originalAmount;
    }

    /**
     * Get the converted amount.
     */
    public function getConvertedAmount(): float
    {
        return $this->convertedAmount;
    }

    /**
     * Get the exchange rate.
     */
    public function getExchangeRate(): ?float
    {
        return $this->exchangeRate;
    }

    /**
     * Get the conversion source.
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get the conversion date.
     */
    public function getConversionDate(): Carbon
    {
        return $this->conversionDate;
    }

    /**
     * Check if the conversion failed.
     */
    public function hasFailed(): bool
    {
        return !$this->isSuccessful();
    }

    /**
     * Get the conversion rate as a percentage.
     */
    public function getConversionRatePercentage(): ?float
    {
        if ($this->exchangeRate === null) {
            return null;
        }

        return ($this->exchangeRate - 1) * 100;
    }

    /**
     * Get a formatted display of the conversion.
     */
    public function getFormattedConversion(): string
    {
        if ($this->hasFailed()) {
            return "Conversion failed: {$this->error}";
        }

        $fromSymbol = $this->fromCurrency?->symbol ?? '';
        $toSymbol = $this->toCurrency?->symbol ?? '';
        $fromCode = $this->fromCurrency?->code ?? 'Unknown';
        $toCode = $this->toCurrency?->code ?? 'Unknown';

        $originalFormatted = $this->fromCurrency?->formatAmount($this->originalAmount) ?? number_format($this->originalAmount, 2);
        $convertedFormatted = $this->toCurrency?->formatAmount($this->convertedAmount) ?? number_format($this->convertedAmount, 2);

        return "{$fromSymbol}{$originalFormatted} {$fromCode} = {$toSymbol}{$convertedFormatted} {$toCode}";
    }

    /**
     * Get the exchange rate with proper formatting.
     */
    public function getFormattedExchangeRate(): string
    {
        if ($this->exchangeRate === null) {
            return 'N/A';
        }

        $fromCode = $this->fromCurrency?->code ?? 'Unknown';
        $toCode = $this->toCurrency?->code ?? 'Unknown';

        return "1 {$fromCode} = " . number_format($this->exchangeRate, 6) . " {$toCode}";
    }

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'original_amount' => $this->originalAmount,
            'converted_amount' => $this->convertedAmount,
            'from_currency' => [
                'code' => $this->fromCurrency?->code,
                'name' => $this->fromCurrency?->name,
                'symbol' => $this->fromCurrency?->symbol,
            ],
            'to_currency' => [
                'code' => $this->toCurrency?->code,
                'name' => $this->toCurrency?->name,
                'symbol' => $this->toCurrency?->symbol,
            ],
            'exchange_rate' => $this->exchangeRate,
            'conversion_date' => $this->conversionDate->toDateString(),
            'source' => $this->source,
            'successful' => $this->isSuccessful(),
            'error' => $this->error,
            'formatted_conversion' => $this->getFormattedConversion(),
            'formatted_exchange_rate' => $this->getFormattedExchangeRate(),
            'conversion_summary' => $this->getConversionSummary(),
        ];
    }

    /**
     * Create a failed conversion result.
     */
    public static function failed(
        float $originalAmount,
        ?Currency $fromCurrency = null,
        ?Currency $toCurrency = null,
        string $error = 'Conversion failed'
    ): self {
        return new self(
            originalAmount: $originalAmount,
            convertedAmount: $originalAmount,
            fromCurrency: $fromCurrency,
            toCurrency: $toCurrency,
            exchangeRate: null,
            conversionDate: now(),
            source: 'failed',
            error: $error
        );
    }

    /**
     * Create a successful same-currency result.
     */
    public static function sameCurrency(float $amount, Currency $currency): self
    {
        return new self(
            originalAmount: $amount,
            convertedAmount: $amount,
            fromCurrency: $currency,
            toCurrency: $currency,
            exchangeRate: 1.0,
            conversionDate: now(),
            source: 'same_currency'
        );
    }
}
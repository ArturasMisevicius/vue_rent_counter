<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\ValueObjects\ConversionResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final readonly class CurrencyConversionService
{
    public function __construct(
        private ExchangeRateProviderService $exchangeRateProvider
    ) {}

    /**
     * Convert an amount from one currency to another.
     */
    public function convert(
        float $amount,
        Currency $fromCurrency,
        Currency $toCurrency,
        ?Carbon $date = null
    ): ConversionResult {
        $date = $date ?? now();

        // If same currency, no conversion needed
        if ($fromCurrency->id === $toCurrency->id) {
            return new ConversionResult(
                originalAmount: $amount,
                convertedAmount: $amount,
                fromCurrency: $fromCurrency,
                toCurrency: $toCurrency,
                exchangeRate: 1.0,
                conversionDate: $date,
                source: 'same_currency'
            );
        }

        // Try to get exchange rate from database
        $exchangeRate = $this->getExchangeRate($fromCurrency->id, $toCurrency->id, $date);

        if ($exchangeRate) {
            $convertedAmount = $exchangeRate->convert($amount);

            return new ConversionResult(
                originalAmount: $amount,
                convertedAmount: $convertedAmount,
                fromCurrency: $fromCurrency,
                toCurrency: $toCurrency,
                exchangeRate: $exchangeRate->rate,
                conversionDate: $date,
                source: 'database'
            );
        }

        // Try reverse rate calculation
        $reverseRate = $this->getExchangeRate($toCurrency->id, $fromCurrency->id, $date);
        
        if ($reverseRate) {
            $rate = $reverseRate->getInverseRate();
            $convertedAmount = $amount * $rate;

            return new ConversionResult(
                originalAmount: $amount,
                convertedAmount: $convertedAmount,
                fromCurrency: $fromCurrency,
                toCurrency: $toCurrency,
                exchangeRate: $rate,
                conversionDate: $date,
                source: 'reverse_calculation'
            );
        }

        // Try to fetch from external provider
        $rate = $this->fetchExchangeRateFromProvider($fromCurrency, $toCurrency, $date);
        
        if ($rate !== null) {
            $convertedAmount = $amount * $rate;
            
            return new ConversionResult(
                originalAmount: $amount,
                convertedAmount: $convertedAmount,
                fromCurrency: $fromCurrency,
                toCurrency: $toCurrency,
                exchangeRate: $rate,
                conversionDate: $date,
                source: 'external_provider'
            );
        }

        // No rate found, throw exception
        throw new \InvalidArgumentException(
            "No exchange rate found for {$fromCurrency->code} to {$toCurrency->code}"
        );
    }

    /**
     * Convert using currency codes.
     */
    public function convertByCodes(
        float $amount,
        string $fromCurrencyCode,
        string $toCurrencyCode,
        ?Carbon $date = null
    ): ConversionResult {
        $fromCurrency = Currency::where('code', $fromCurrencyCode)->first();
        $toCurrency = Currency::where('code', $toCurrencyCode)->first();

        if (!$fromCurrency || !$toCurrency) {
            return new ConversionResult(
                originalAmount: $amount,
                convertedAmount: $amount,
                fromCurrency: $fromCurrency,
                toCurrency: $toCurrency,
                exchangeRate: null,
                conversionDate: $date ?? now(),
                source: 'failed',
                error: 'Currency not found'
            );
        }

        return $this->convert($amount, $fromCurrency, $toCurrency, $date);
    }

    /**
     * Get the latest exchange rate between two currencies.
     */
    public function getLatestRate(Currency $fromCurrency, Currency $toCurrency): ?float
    {
        $exchangeRate = ExchangeRate::getLatestRate($fromCurrency->id, $toCurrency->id);
        
        return $exchangeRate?->rate;
    }

    /**
     * Get historical exchange rates for a currency pair.
     */
    public function getHistoricalRates(
        Currency $fromCurrency,
        Currency $toCurrency,
        Carbon $startDate,
        Carbon $endDate
    ): \Illuminate\Support\Collection {
        return ExchangeRate::active()
            ->betweenCurrencies($fromCurrency->id, $toCurrency->id)
            ->whereBetween('effective_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('effective_date')
            ->get()
            ->map(fn($rate) => [
                'date' => $rate->effective_date,
                'rate' => $rate->rate,
                'source' => $rate->source,
            ]);
    }

    /**
     * Convert multiple amounts in batch.
     */
    public function convertBatch(
        array $amounts,
        Currency $fromCurrency,
        Currency $toCurrency,
        ?Carbon $date = null
    ): array {
        $results = [];
        
        foreach ($amounts as $key => $amount) {
            try {
                $results[$key] = $this->convert($amount, $fromCurrency, $toCurrency, $date);
            } catch (\Exception $e) {
                $results[$key] = [
                    'error' => $e->getMessage(),
                    'original_amount' => $amount,
                ];
            }
        }
        
        return $results;
    }

    /**
     * Convert invoice amounts to a different currency.
     */
    public function convertInvoiceAmounts(array $invoiceData, Currency $targetCurrency): array
    {
        $fromCurrency = Currency::find($invoiceData['currency_id']);
        
        if (!$fromCurrency) {
            throw new \InvalidArgumentException('Invalid source currency');
        }

        $converted = $invoiceData;
        $converted['currency_id'] = $targetCurrency->id;
        $converted['conversion_date'] = now()->toDateString();

        // Convert main amounts
        $amountFields = ['subtotal', 'tax_amount', 'total_amount', 'paid_amount', 'balance'];
        
        foreach ($amountFields as $field) {
            if (isset($invoiceData[$field])) {
                $result = $this->convert($invoiceData[$field], $fromCurrency, $targetCurrency);
                $converted[$field] = $result->convertedAmount;
                $converted[$field . '_original'] = $invoiceData[$field];
                $converted[$field . '_exchange_rate'] = $result->exchangeRate;
            }
        }

        // Convert line items
        if (isset($invoiceData['line_items']) && is_array($invoiceData['line_items'])) {
            foreach ($invoiceData['line_items'] as $index => $item) {
                if (isset($item['amount'])) {
                    $result = $this->convert($item['amount'], $fromCurrency, $targetCurrency);
                    $converted['line_items'][$index]['amount'] = $result->convertedAmount;
                    $converted['line_items'][$index]['amount_original'] = $item['amount'];
                    $converted['line_items'][$index]['exchange_rate'] = $result->exchangeRate;
                }
            }
        }

        return $converted;
    }

    /**
     * Update exchange rates from external provider.
     */
    public function updateExchangeRates(array $currencyCodes = []): array
    {
        $results = [];
        $currencies = empty($currencyCodes) 
            ? Currency::active()->get() 
            : Currency::active()->whereIn('code', $currencyCodes)->get();

        foreach ($currencies as $fromCurrency) {
            foreach ($currencies as $toCurrency) {
                if ($fromCurrency->id === $toCurrency->id) {
                    continue;
                }

                $rate = $this->fetchExchangeRateFromProvider($fromCurrency, $toCurrency);
                
                if ($rate !== null) {
                    ExchangeRate::updateOrCreate(
                        [
                            'from_currency_id' => $fromCurrency->id,
                            'to_currency_id' => $toCurrency->id,
                            'effective_date' => now()->toDateString(),
                        ],
                        [
                            'rate' => $rate,
                            'source' => 'external_provider',
                            'is_active' => true,
                        ]
                    );

                    $results[] = [
                        'from' => $fromCurrency->code,
                        'to' => $toCurrency->code,
                        'rate' => $rate,
                        'status' => 'updated',
                    ];
                } else {
                    $results[] = [
                        'from' => $fromCurrency->code,
                        'to' => $toCurrency->code,
                        'rate' => null,
                        'status' => 'failed',
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Get exchange rate from database with caching.
     */
    private function getExchangeRate(int $fromCurrencyId, int $toCurrencyId, Carbon $date): ?ExchangeRate
    {
        $cacheKey = "exchange_rate_{$fromCurrencyId}_{$toCurrencyId}_{$date->toDateString()}";
        
        return Cache::remember($cacheKey, 3600, function () use ($fromCurrencyId, $toCurrencyId, $date) {
            return ExchangeRate::getLatestRate($fromCurrencyId, $toCurrencyId, $date);
        });
    }

    /**
     * Fetch exchange rate from external provider.
     */
    private function fetchExchangeRateFromProvider(
        Currency $fromCurrency,
        Currency $toCurrency,
        ?Carbon $date = null
    ): ?float {
        try {
            return $this->exchangeRateProvider->getExchangeRate(
                $fromCurrency->code,
                $toCurrency->code,
                $date
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch exchange rate from provider', [
                'from_currency' => $fromCurrency->code,
                'to_currency' => $toCurrency->code,
                'date' => $date?->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
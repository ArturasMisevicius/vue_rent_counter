<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class ExchangeRateProviderService
{
    private const PROVIDERS = [
        'exchangerate-api' => 'https://api.exchangerate-api.com/v4/latest/',
        'fixer' => 'https://api.fixer.io/latest',
        'currencylayer' => 'https://api.currencylayer.com/live',
    ];

    public function __construct(
        private string $primaryProvider = 'exchangerate-api',
        private ?string $apiKey = null
    ) {}

    /**
     * Get exchange rate between two currencies.
     */
    public function getExchangeRate(
        string $fromCurrency,
        string $toCurrency,
        ?Carbon $date = null
    ): ?float {
        $providers = array_keys(self::PROVIDERS);
        
        // Try primary provider first
        if (in_array($this->primaryProvider, $providers)) {
            $rate = $this->fetchFromProvider($this->primaryProvider, $fromCurrency, $toCurrency, $date);
            if ($rate !== null) {
                return $rate;
            }
        }

        // Try other providers as fallback
        foreach ($providers as $provider) {
            if ($provider === $this->primaryProvider) {
                continue; // Already tried
            }

            $rate = $this->fetchFromProvider($provider, $fromCurrency, $toCurrency, $date);
            if ($rate !== null) {
                return $rate;
            }
        }

        return null;
    }

    /**
     * Get multiple exchange rates for a base currency.
     */
    public function getMultipleRates(string $baseCurrency, array $targetCurrencies): array
    {
        $rates = [];

        foreach ($targetCurrencies as $targetCurrency) {
            $rate = $this->getExchangeRate($baseCurrency, $targetCurrency);
            if ($rate !== null) {
                $rates[$targetCurrency] = $rate;
            }
        }

        return $rates;
    }

    /**
     * Check if the service is available.
     */
    public function isAvailable(): bool
    {
        try {
            $rate = $this->getExchangeRate('USD', 'EUR');
            return $rate !== null;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Fetch exchange rate from a specific provider.
     */
    private function fetchFromProvider(
        string $provider,
        string $fromCurrency,
        string $toCurrency,
        ?Carbon $date = null
    ): ?float {
        try {
            return match ($provider) {
                'exchangerate-api' => $this->fetchFromExchangeRateApi($fromCurrency, $toCurrency),
                'fixer' => $this->fetchFromFixer($fromCurrency, $toCurrency),
                'currencylayer' => $this->fetchFromCurrencyLayer($fromCurrency, $toCurrency),
                default => null,
            };
        } catch (\Exception $e) {
            Log::warning("Failed to fetch from {$provider}", [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch from ExchangeRate-API (free, no API key required).
     */
    private function fetchFromExchangeRateApi(string $fromCurrency, string $toCurrency): ?float
    {
        $url = self::PROVIDERS['exchangerate-api'] . $fromCurrency;
        
        $response = Http::timeout(10)->get($url);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (!isset($data['rates'][$toCurrency])) {
            return null;
        }

        return (float) $data['rates'][$toCurrency];
    }

    /**
     * Fetch from Fixer.io (requires API key).
     */
    private function fetchFromFixer(string $fromCurrency, string $toCurrency): ?float
    {
        if (!$this->apiKey) {
            return null; // API key required
        }

        $url = self::PROVIDERS['fixer'];
        
        $response = Http::timeout(10)->get($url, [
            'access_key' => $this->apiKey,
            'base' => $fromCurrency,
            'symbols' => $toCurrency,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (!isset($data['rates'][$toCurrency])) {
            return null;
        }

        return (float) $data['rates'][$toCurrency];
    }

    /**
     * Fetch from CurrencyLayer (requires API key).
     */
    private function fetchFromCurrencyLayer(string $fromCurrency, string $toCurrency): ?float
    {
        if (!$this->apiKey) {
            return null; // API key required
        }

        $url = self::PROVIDERS['currencylayer'];
        
        $response = Http::timeout(10)->get($url, [
            'access_key' => $this->apiKey,
            'source' => $fromCurrency,
            'currencies' => $toCurrency,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        $key = $fromCurrency . $toCurrency;

        if (!isset($data['quotes'][$key])) {
            return null;
        }

        return (float) $data['quotes'][$key];
    }
}
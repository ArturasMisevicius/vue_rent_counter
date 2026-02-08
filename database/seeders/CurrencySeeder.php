<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

final class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        // Create major world currencies
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => true, // USD as default
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound Sterling',
                'symbol' => '£',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'JPY',
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'decimal_places' => 0,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'CAD',
                'name' => 'Canadian Dollar',
                'symbol' => 'C$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'AUD',
                'name' => 'Australian Dollar',
                'symbol' => 'A$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'CHF',
                'name' => 'Swiss Franc',
                'symbol' => 'CHF',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'CNY',
                'name' => 'Chinese Yuan',
                'symbol' => '¥',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'SEK',
                'name' => 'Swedish Krona',
                'symbol' => 'kr',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'NOK',
                'name' => 'Norwegian Krone',
                'symbol' => 'kr',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'DKK',
                'name' => 'Danish Krone',
                'symbol' => 'kr',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($currencies as $currencyData) {
            Currency::updateOrCreate(
                ['code' => $currencyData['code']],
                $currencyData
            );
        }

        // Create some sample exchange rates for testing
        $this->createSampleExchangeRates();
    }

    private function createSampleExchangeRates(): void
    {
        $usd = Currency::where('code', 'USD')->first();
        $eur = Currency::where('code', 'EUR')->first();
        $gbp = Currency::where('code', 'GBP')->first();
        $jpy = Currency::where('code', 'JPY')->first();

        if (!$usd || !$eur || !$gbp || !$jpy) {
            return;
        }

        // Sample exchange rates (approximate values for testing)
        $exchangeRates = [
            // USD to other currencies
            [$usd->id, $eur->id, 0.85, 'USD to EUR'],
            [$usd->id, $gbp->id, 0.73, 'USD to GBP'],
            [$usd->id, $jpy->id, 110.0, 'USD to JPY'],
            
            // EUR to other currencies
            [$eur->id, $usd->id, 1.18, 'EUR to USD'],
            [$eur->id, $gbp->id, 0.86, 'EUR to GBP'],
            [$eur->id, $jpy->id, 129.0, 'EUR to JPY'],
            
            // GBP to other currencies
            [$gbp->id, $usd->id, 1.37, 'GBP to USD'],
            [$gbp->id, $eur->id, 1.16, 'GBP to EUR'],
            [$gbp->id, $jpy->id, 151.0, 'GBP to JPY'],
            
            // JPY to other currencies
            [$jpy->id, $usd->id, 0.009, 'JPY to USD'],
            [$jpy->id, $eur->id, 0.0078, 'JPY to EUR'],
            [$jpy->id, $gbp->id, 0.0066, 'JPY to GBP'],
        ];

        foreach ($exchangeRates as [$fromId, $toId, $rate, $description]) {
            ExchangeRate::updateOrCreate(
                [
                    'from_currency_id' => $fromId,
                    'to_currency_id' => $toId,
                    'effective_date' => now()->toDateString(),
                ],
                [
                    'rate' => $rate,
                    'source' => 'seeder',
                    'is_active' => true,
                ]
            );
        }
    }
}
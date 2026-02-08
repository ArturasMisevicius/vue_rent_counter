<?php

declare(strict_types=1);

namespace App\Support;

final class EuropeanCurrencyOptions
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'EUR' => 'EUR - Euro',
            'ALL' => 'ALL - Albanian Lek',
            'AMD' => 'AMD - Armenian Dram',
            'AZN' => 'AZN - Azerbaijani Manat',
            'BAM' => 'BAM - Bosnia and Herzegovina Convertible Mark',
            'BGN' => 'BGN - Bulgarian Lev',
            'BYN' => 'BYN - Belarusian Ruble',
            'CHF' => 'CHF - Swiss Franc',
            'CZK' => 'CZK - Czech Koruna',
            'DKK' => 'DKK - Danish Krone',
            'GBP' => 'GBP - Pound Sterling',
            'GEL' => 'GEL - Georgian Lari',
            'HUF' => 'HUF - Hungarian Forint',
            'ISK' => 'ISK - Icelandic Krona',
            'MDL' => 'MDL - Moldovan Leu',
            'MKD' => 'MKD - Macedonian Denar',
            'NOK' => 'NOK - Norwegian Krone',
            'PLN' => 'PLN - Polish Zloty',
            'RON' => 'RON - Romanian Leu',
            'RSD' => 'RSD - Serbian Dinar',
            'RUB' => 'RUB - Russian Ruble',
            'SEK' => 'SEK - Swedish Krona',
            'TRY' => 'TRY - Turkish Lira',
            'UAH' => 'UAH - Ukrainian Hryvnia',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_keys(self::options());
    }
}

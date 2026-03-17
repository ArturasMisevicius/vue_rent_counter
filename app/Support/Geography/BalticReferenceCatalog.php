<?php

namespace App\Support\Geography;

class BalticReferenceCatalog
{
    /**
     * @return array<int, array{
     *     code: string,
     *     slug: string,
     *     name: string,
     *     native_name: string,
     *     name_translations: array{en: string, lt: string, ru: string},
     *     region: string,
     *     currency_code: string,
     *     timezone: string,
     *     is_active: bool,
     *     capital_city_slug: string
     * }>
     */
    public static function countries(): array
    {
        return [
            [
                'code' => 'LT',
                'slug' => 'lithuania',
                'name' => 'Lithuania',
                'native_name' => 'Lietuva',
                'name_translations' => [
                    'en' => 'Lithuania',
                    'lt' => 'Lietuva',
                    'ru' => 'Литва',
                ],
                'region' => 'baltics',
                'currency_code' => 'EUR',
                'timezone' => 'Europe/Vilnius',
                'is_active' => true,
                'capital_city_slug' => 'vilnius',
            ],
            [
                'code' => 'LV',
                'slug' => 'latvia',
                'name' => 'Latvia',
                'native_name' => 'Latvija',
                'name_translations' => [
                    'en' => 'Latvia',
                    'lt' => 'Latvija',
                    'ru' => 'Латвия',
                ],
                'region' => 'baltics',
                'currency_code' => 'EUR',
                'timezone' => 'Europe/Riga',
                'is_active' => true,
                'capital_city_slug' => 'riga',
            ],
            [
                'code' => 'EE',
                'slug' => 'estonia',
                'name' => 'Estonia',
                'native_name' => 'Eesti',
                'name_translations' => [
                    'en' => 'Estonia',
                    'lt' => 'Estija',
                    'ru' => 'Эстония',
                ],
                'region' => 'baltics',
                'currency_code' => 'EUR',
                'timezone' => 'Europe/Tallinn',
                'is_active' => true,
                'capital_city_slug' => 'tallinn',
            ],
        ];
    }

    /**
     * @return array<int, array{
     *     country_code: string,
     *     slug: string,
     *     name: string,
     *     native_name: string,
     *     name_translations: array{en: string, lt: string, ru: string},
     *     latitude: float,
     *     longitude: float,
     *     timezone: string,
     *     is_capital: bool,
     *     population: int,
     *     postal_code_pattern: string
     * }>
     */
    public static function cities(): array
    {
        return [
            [
                'country_code' => 'LT',
                'slug' => 'vilnius',
                'name' => 'Vilnius',
                'native_name' => 'Vilnius',
                'name_translations' => ['en' => 'Vilnius', 'lt' => 'Vilnius', 'ru' => 'Вильнюс'],
                'latitude' => 54.6872,
                'longitude' => 25.2797,
                'timezone' => 'Europe/Vilnius',
                'is_capital' => true,
                'population' => 592389,
                'postal_code_pattern' => 'LT-0###',
            ],
            [
                'country_code' => 'LT',
                'slug' => 'kaunas',
                'name' => 'Kaunas',
                'native_name' => 'Kaunas',
                'name_translations' => ['en' => 'Kaunas', 'lt' => 'Kaunas', 'ru' => 'Каунас'],
                'latitude' => 54.8985,
                'longitude' => 23.9036,
                'timezone' => 'Europe/Vilnius',
                'is_capital' => false,
                'population' => 305120,
                'postal_code_pattern' => 'LT-4###',
            ],
            [
                'country_code' => 'LT',
                'slug' => 'klaipeda',
                'name' => 'Klaipėda',
                'native_name' => 'Klaipėda',
                'name_translations' => ['en' => 'Klaipėda', 'lt' => 'Klaipėda', 'ru' => 'Клайпеда'],
                'latitude' => 55.7033,
                'longitude' => 21.1443,
                'timezone' => 'Europe/Vilnius',
                'is_capital' => false,
                'population' => 152008,
                'postal_code_pattern' => 'LT-9###',
            ],
            [
                'country_code' => 'LT',
                'slug' => 'siauliai',
                'name' => 'Šiauliai',
                'native_name' => 'Šiauliai',
                'name_translations' => ['en' => 'Šiauliai', 'lt' => 'Šiauliai', 'ru' => 'Шяуляй'],
                'latitude' => 55.9349,
                'longitude' => 23.3137,
                'timezone' => 'Europe/Vilnius',
                'is_capital' => false,
                'population' => 100653,
                'postal_code_pattern' => 'LT-7###',
            ],
            [
                'country_code' => 'LT',
                'slug' => 'panevezys',
                'name' => 'Panevėžys',
                'native_name' => 'Panevėžys',
                'name_translations' => ['en' => 'Panevėžys', 'lt' => 'Panevėžys', 'ru' => 'Паневежис'],
                'latitude' => 55.7348,
                'longitude' => 24.3575,
                'timezone' => 'Europe/Vilnius',
                'is_capital' => false,
                'population' => 86496,
                'postal_code_pattern' => 'LT-35###',
            ],
            [
                'country_code' => 'LV',
                'slug' => 'riga',
                'name' => 'Riga',
                'native_name' => 'Rīga',
                'name_translations' => ['en' => 'Riga', 'lt' => 'Ryga', 'ru' => 'Рига'],
                'latitude' => 56.9496,
                'longitude' => 24.1052,
                'timezone' => 'Europe/Riga',
                'is_capital' => true,
                'population' => 605273,
                'postal_code_pattern' => 'LV-1###',
            ],
            [
                'country_code' => 'LV',
                'slug' => 'daugavpils',
                'name' => 'Daugavpils',
                'native_name' => 'Daugavpils',
                'name_translations' => ['en' => 'Daugavpils', 'lt' => 'Daugpilis', 'ru' => 'Даугавпилс'],
                'latitude' => 55.8747,
                'longitude' => 26.5362,
                'timezone' => 'Europe/Riga',
                'is_capital' => false,
                'population' => 79046,
                'postal_code_pattern' => 'LV-54##',
            ],
            [
                'country_code' => 'LV',
                'slug' => 'liepaja',
                'name' => 'Liepāja',
                'native_name' => 'Liepāja',
                'name_translations' => ['en' => 'Liepāja', 'lt' => 'Liepāja', 'ru' => 'Лиепая'],
                'latitude' => 56.5047,
                'longitude' => 21.0108,
                'timezone' => 'Europe/Riga',
                'is_capital' => false,
                'population' => 66780,
                'postal_code_pattern' => 'LV-34##',
            ],
            [
                'country_code' => 'LV',
                'slug' => 'jelgava',
                'name' => 'Jelgava',
                'native_name' => 'Jelgava',
                'name_translations' => ['en' => 'Jelgava', 'lt' => 'Jelgava', 'ru' => 'Елгава'],
                'latitude' => 56.6511,
                'longitude' => 23.7211,
                'timezone' => 'Europe/Riga',
                'is_capital' => false,
                'population' => 55072,
                'postal_code_pattern' => 'LV-30##',
            ],
            [
                'country_code' => 'LV',
                'slug' => 'jurmala',
                'name' => 'Jūrmala',
                'native_name' => 'Jūrmala',
                'name_translations' => ['en' => 'Jūrmala', 'lt' => 'Jūrmala', 'ru' => 'Юрмала'],
                'latitude' => 56.9680,
                'longitude' => 23.7704,
                'timezone' => 'Europe/Riga',
                'is_capital' => false,
                'population' => 49568,
                'postal_code_pattern' => 'LV-20##',
            ],
            [
                'country_code' => 'EE',
                'slug' => 'tallinn',
                'name' => 'Tallinn',
                'native_name' => 'Tallinn',
                'name_translations' => ['en' => 'Tallinn', 'lt' => 'Talinas', 'ru' => 'Таллин'],
                'latitude' => 59.4370,
                'longitude' => 24.7536,
                'timezone' => 'Europe/Tallinn',
                'is_capital' => true,
                'population' => 461602,
                'postal_code_pattern' => '10###',
            ],
            [
                'country_code' => 'EE',
                'slug' => 'tartu',
                'name' => 'Tartu',
                'native_name' => 'Tartu',
                'name_translations' => ['en' => 'Tartu', 'lt' => 'Tartu', 'ru' => 'Тарту'],
                'latitude' => 58.3776,
                'longitude' => 26.7290,
                'timezone' => 'Europe/Tallinn',
                'is_capital' => false,
                'population' => 97754,
                'postal_code_pattern' => '50###',
            ],
            [
                'country_code' => 'EE',
                'slug' => 'narva',
                'name' => 'Narva',
                'native_name' => 'Narva',
                'name_translations' => ['en' => 'Narva', 'lt' => 'Narva', 'ru' => 'Нарва'],
                'latitude' => 59.3772,
                'longitude' => 28.1903,
                'timezone' => 'Europe/Tallinn',
                'is_capital' => false,
                'population' => 53381,
                'postal_code_pattern' => '20###',
            ],
            [
                'country_code' => 'EE',
                'slug' => 'parnu',
                'name' => 'Pärnu',
                'native_name' => 'Pärnu',
                'name_translations' => ['en' => 'Pärnu', 'lt' => 'Pernu', 'ru' => 'Пярну'],
                'latitude' => 58.3859,
                'longitude' => 24.4971,
                'timezone' => 'Europe/Tallinn',
                'is_capital' => false,
                'population' => 40105,
                'postal_code_pattern' => '80###',
            ],
            [
                'country_code' => 'EE',
                'slug' => 'kohtla-jarve',
                'name' => 'Kohtla-Järve',
                'native_name' => 'Kohtla-Järve',
                'name_translations' => ['en' => 'Kohtla-Järve', 'lt' => 'Kohtla-Jervė', 'ru' => 'Кохтла-Ярве'],
                'latitude' => 59.3986,
                'longitude' => 27.2731,
                'timezone' => 'Europe/Tallinn',
                'is_capital' => false,
                'population' => 33077,
                'postal_code_pattern' => '30###',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function supportedLocaleCodes(): array
    {
        return ['en', 'lt', 'ru'];
    }

    /**
     * @return array<string, array{
     *     code: string,
     *     slug: string,
     *     name: string,
     *     native_name: string,
     *     name_translations: array{en: string, lt: string, ru: string},
     *     region: string,
     *     currency_code: string,
     *     timezone: string,
     *     is_active: bool,
     *     capital_city_slug: string
     * }>
     */
    public static function countriesByCode(): array
    {
        $countries = [];

        foreach (self::countries() as $country) {
            $countries[$country['code']] = $country;
        }

        return $countries;
    }

    /**
     * @return array<string, array{
     *     country_code: string,
     *     slug: string,
     *     name: string,
     *     native_name: string,
     *     name_translations: array{en: string, lt: string, ru: string},
     *     latitude: float,
     *     longitude: float,
     *     timezone: string,
     *     is_capital: bool,
     *     population: int,
     *     postal_code_pattern: string
     * }>
     */
    public static function citiesBySlug(): array
    {
        $cities = [];

        foreach (self::cities() as $city) {
            $cities[$city['slug']] = $city;
        }

        return $cities;
    }

    /**
     * @return array{
     *     country_code: string,
     *     slug: string,
     *     name: string,
     *     native_name: string,
     *     name_translations: array{en: string, lt: string, ru: string},
     *     latitude: float,
     *     longitude: float,
     *     timezone: string,
     *     is_capital: bool,
     *     population: int,
     *     postal_code_pattern: string
     * }
     */
    public static function cityFor(string $slug): array
    {
        return self::citiesBySlug()[$slug];
    }
}
